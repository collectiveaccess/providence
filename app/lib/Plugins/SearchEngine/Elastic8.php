<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2021 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use Elastic\Elasticsearch\Client;
use Elastic\ElasticSearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic8\Mapping;

require_once( __CA_LIB_DIR__ . '/Configuration.php' );
require_once( __CA_LIB_DIR__ . '/Datamodel.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/WLPlug.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/IWLPlugSearchEngine.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/BaseSearchPlugin.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8Result.php' );

require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/Field.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/Mapping.php' );
require_once( __CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/Query.php' );

class WLPlugSearchEngineElastic8 extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	protected $index_content_buffer = [];

	protected $indexing_subject_tablenum = null;
	protected $indexing_subject_row_id = null;
	protected $indexing_subject_tablename = null;

	/**
	 * @var Client
	 */
	static protected $client;

	static private $doc_content_buffer = [];
	static private $update_content_buffer = [];
	static private $delete_buffer = [];
	static private $record_cache = [];

	protected $elasticsearch_index_name = '';
	protected $elasticsearch_base_url = '';

	protected $version = 8;

	# -------------------------------------------------------
	public function __construct( $po_db = null ) {
		parent::__construct( $po_db );

		// allow overriding settings from search.conf via constant (usually defined in bootstrap file)
		// this is useful for multi-instance setups which have the same set of config files for multiple instances
		if ( defined( '__CA_ELASTICSEARCH_BASE_URL__' ) && ( strlen( __CA_ELASTICSEARCH_BASE_URL__ ) > 0 ) ) {
			$this->elasticsearch_base_url = __CA_ELASTICSEARCH_BASE_URL__;
		} else {
			$this->elasticsearch_base_url = $this->search_config->get( 'search_elasticsearch_base_url' );
		}
		$this->elasticsearch_base_url = trim( $this->elasticsearch_base_url,
			"/" );   // strip trailing slashes as they cause errors with ElasticSearch 5.x

		if ( defined( '__CA_ELASTICSEARCH_INDEX_NAME__' ) && ( strlen( __CA_ELASTICSEARCH_INDEX_NAME__ ) > 0 ) ) {
			$this->elasticsearch_index_name = __CA_ELASTICSEARCH_INDEX_NAME__;
		} else {
			$this->elasticsearch_index_name = $this->search_config->get( 'search_elasticsearch_index_name' );
		}
	}
	# -------------------------------------------------------

	/**
	 * Get ElasticSearch index name prefix
	 *
	 * @return string
	 */
	protected function getIndexNamePrefix() {
		return $this->elasticsearch_index_name;
	}
	# -------------------------------------------------------

	/**
	 * Get ElasticSearch index name
	 *
	 * @return string
	 */
	protected function getIndexName( $table ) {
		if ( is_numeric( $table ) ) {
			$table = Datamodel::getTableName( $table );
		}

		return $this->getIndexNamePrefix() . "_{$table}";
	}
	# -------------------------------------------------------

	/**
	 * Refresh ElasticSearch mapping if necessary
	 *
	 * @param bool $pb_force force refresh if set to true [default is false]
	 *
	 * @throws Exception
	 */
	public function refreshMapping( $pb_force = false ) {

		/** @var Mapping $o_mapping */
		static $o_mapping;
		if ( ! $o_mapping ) {
			$o_mapping = new Mapping();
		}

		if ( $pb_force ) {
			$indexPrefix = $this->getIndexNamePrefix();
			// TODO: Move away from plain index template in favour of composable templates when the ES PHP API supports them.
			$indexSettings = [
				'settings' => [
					'max_result_window' => 2147483647,
					'analysis' => [
						'analyzer' => [
							'keyword_lowercase' => [
								'tokenizer' => 'keyword',
								'filter' => 'lowercase'
							],
							'whitespace' => [
								'tokenizer' => 'whitespace',
								'filter' => 'lowercase'
							],
						]
					],
					'index.mapping.total_fields.limit' => 20000,
				],
				'mappings' => [
					'_source' => [
						'enabled' => true,
					],
					'dynamic' => "true",
					'dynamic_templates' => $o_mapping->getDynamicTemplates(),
				]
			];
			$client = $this->getClient();
			$indices = $client->indices();
			$indices->putTemplate( [
				'name' => $indexPrefix,
				'body' => [ 'index_patterns' => [ $indexPrefix . "_*" ] ] + $indexSettings
			] );
			foreach ( $o_mapping->getTables() as $table ) {
				$indexName = $this->getIndexName( $table );
				if ( ! $indices->exists( [ 'index' => $indexName, 'ignore_missing' => true ] )->asBool() ) {
					$indices->create( [ 'index' => $indexName ] );
				}
				$indices->putSettings( [
					'index' => $indexName,
					'reopen' => true,
					'body' => $indexSettings['settings']
				] );
				$indices->putMapping( [ 'index' => $indexName, 'body' => $indexSettings['mappings'] ] );
			}
		}
	}
	# -------------------------------------------------------

	/**
	 *
	 *
	 * @param int $pn_subject_tablenum
	 * @param array $pa_subject_row_ids
	 * @param int $pn_content_tablenum
	 * @param string $ps_content_fieldnum
	 * @param int $pn_content_row_id
	 * @param string $ps_content
	 * @param array $pa_options
	 *    literalContent = array of text content to be applied without tokenization
	 *    BOOST = Indexing boost to apply
	 *    PRIVATE = Set indexing to private
	 */
	public function updateIndexingInPlace(
		$pn_subject_tablenum, $pa_subject_row_ids, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_container_id,
		$pn_content_row_id, $ps_content, $pa_options = null
	) {
		$table = Datamodel::getTableName( $pn_subject_tablenum );

		$o_field = new Elastic8\Field( $pn_content_tablenum, $ps_content_fieldnum );
		$va_fragment = $o_field->getIndexingFragment( $ps_content, $pa_options );

		foreach ( $pa_subject_row_ids as $pn_subject_row_id ) {
			// fetch the record
			try {
				$record = $this->record_cache[ $table ][ $pn_subject_row_id ] ?? null;
				if ( is_null( $record ) ) {
					$f = [
						'index' => $this->getIndexName( $table ),
						'id' => $pn_subject_row_id
					];
					$va_record = $this->getClient()->get( $f )['_source'];
				}
			} catch ( ClientResponseException $e ) {
				$va_record = []; // record doesn't exist yet --> the update API will create it
			}
			$this->record_cache[ $table ][ $pn_subject_row_id ] = $record;

			$this->addFragmentToUpdateContentBuffer( $va_fragment, $va_record, $table, $pn_subject_row_id,
				$pn_content_row_id );
		}

		if ( (
				sizeof( self::$doc_content_buffer ) +
				sizeof( self::$update_content_buffer ) +
				sizeof( self::$delete_buffer )
			) > $this->getOption( 'maxIndexingBufferSize' )
		) {
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------

	/**
	 * Utility function that adds a given indexing fragment to the update content buffer
	 *
	 * @param array $pa_fragment
	 * @param array $pa_record
	 * @param $ps_table_name
	 * @param $pn_subject_row_id
	 * @param $pn_content_row_id
	 */
	private function addFragmentToUpdateContentBuffer(
		array $pa_fragment, array $pa_record, $ps_table_name, $pn_subject_row_id, $pn_content_row_id
	) {
		foreach ( $pa_fragment as $vs_key => $vm_val ) {
			if ( isset( $pa_record[ $vs_key ] ) ) {
				// find the index for this content row id in our _content_ids index list
				$va_values = $pa_record[ $vs_key ];
				$va_indexes = $pa_record[ $vs_key . '_content_ids' ];
				$vn_index = array_search( $pn_content_row_id, $va_indexes );
				if ( $vn_index !== false ) {
					// replace that very index in the value array for this field -- all the other values stay intact
					$va_values[ $vn_index ] = $vm_val;
				} else { // this particular content row id hasn't been indexed yet --> just add it
					$va_values[] = $vm_val;
					$va_indexes[] = $pn_content_row_id;
				}
				self::$update_content_buffer[ $ps_table_name ][ $pn_subject_row_id ][ $vs_key . '_content_ids' ]
					= $va_indexes;
				self::$update_content_buffer[ $ps_table_name ][ $pn_subject_row_id ][ $vs_key ] = $va_values;
			} else { // this field wasn't indexed yet -- just add it
				self::$update_content_buffer[ $ps_table_name ][ $pn_subject_row_id ][ $vs_key ][] = $vm_val;
				self::$update_content_buffer[ $ps_table_name ][ $pn_subject_row_id ][ $vs_key . '_content_ids' ][]
					= $pn_content_row_id;
			}
		}
	}
	# -------------------------------------------------------

	/**
	 * Get ElasticSearch client
	 *
	 * @return Client
	 */
	protected function getClient() {
		if ( ! self::$client ) {
			self::$client = Elastic\Elasticsearch\ClientBuilder::create()
				->setHosts( [ $this->elasticsearch_base_url ] )
				->setRetries( 3 )
				->build();
		}

		return self::$client;
	}

	# -------------------------------------------------------
	public function init() {
		if ( ( $vn_max_indexing_buffer_size = (int) $this->search_config->get( 'elasticsearch_indexing_buffer_size' ) )
			< 1
		) {
			$vn_max_indexing_buffer_size = 250;
		}

		$this->options = array(
			'start' => 0,
			'limit' => 100000,
			// maximum number of hits to return [default=100000],
			'maxIndexingBufferSize' => $vn_max_indexing_buffer_size
			// maximum number of indexed content items to accumulate before writing to the index
		);

		$this->capabilities = array(
			'incremental_reindexing' => true
		);
	}
	# -------------------------------------------------------

	/**
	 * Completely clear index (usually in preparation for a full reindex)
	 *
	 * @param null|int $pn_table_num
	 * @param bool $pb_dont_refresh
	 *
	 * @return bool
	 */
	public function truncateIndex( $pn_table_num = null, $pb_dont_refresh = false ) {
		$o_mapping = new Elastic8\Mapping();
		if ( $pn_table_num ) {
			$tables = [ Datamodel::getTableName( $pn_table_num ) ];
		} else {
			$tables = $o_mapping->getTables();
		}
		$this->getClient()->indices()->delete( [ 'index' =>  array_map($this->getIndexName, $tables), 'ignore_unavailable' => true ] );
		$this->refreshMapping( true );
		return true;
	}

	# -------------------------------------------------------
	public function setTableNum( $pn_table_num ) {
		$this->indexing_subject_tablenum = $pn_table_num;
	}

	# -------------------------------------------------------
	public function __destruct() {
		if ( ! defined( '__CollectiveAccess_Installer__' ) || ! __CollectiveAccess_Installer__ ) {
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------

	/**
	 * Do search
	 *
	 * @param int $pn_subject_tablenum
	 * @param string $ps_search_expression
	 * @param array $pa_filters
	 * @param null|Zend_Search_Lucene_Search_Query_Boolean $po_rewritten_query
	 *
	 * @return WLPlugSearchEngineElasticSearchResult
	 */
	public function search(
		int $pn_subject_tablenum, string $ps_search_expression, array $pa_filters = [], $po_rewritten_query
	) {
		Debug::msg( "[ElasticSearch] incoming search query is: {$ps_search_expression}" );
		Debug::msg( "[ElasticSearch] incoming query filters are: " . print_r( $pa_filters, true ) );

		$o_query = new Elastic8\Query( $pn_subject_tablenum, $ps_search_expression, $po_rewritten_query, $pa_filters );
		$vs_query = $o_query->getSearchExpression();

		Debug::msg( "[ElasticSearch] actual search query sent to ES: {$vs_query}" );

		$va_search_params = array(
			'index' => $this->getIndexName( $pn_subject_tablenum ),
			'body' => array(
				// we do paging in our code
				'from' => 0,
				'size' => 2147483647, // size is Java's 32bit int, for ElasticSearch
				'_source' => false,
				'query' => array(
					'bool' => array(
						'must' => array(
							array(
								'query_string' => array(
									'analyze_wildcard' => true,
									'query' => $vs_query,
									'default_operator' => 'AND'
								),
							)
						)
					)
				)
			)
		);

		// apply additional filters that may have been set by the query
		if ( ( $va_additional_filters = $o_query->getAdditionalFilters() ) && is_array( $va_additional_filters )
			&& ( sizeof( $va_additional_filters ) > 0 )
		) {
			foreach ( $va_additional_filters as $va_filter ) {
				$va_search_params['body']['query']['bool']['must'][] = $va_filter;
			}
		}

		Debug::msg( "[ElasticSearch] actual query filters are: " . print_r( $va_additional_filters, true ) );
		try {
			$va_results = $this->getClient()->search( $va_search_params );
		} catch ( ClientResponseException $e ) {
			$va_results = [ 'hits' => [ 'hits' => [] ] ];
		}

		return new WLPlugSearchEngineElasticSearchResult( $va_results['hits']['hits'], $pn_subject_tablenum );
	}
	# -------------------------------------------------------

	/**
	 * Start row indexing
	 *
	 * @param int $pn_subject_tablenum
	 * @param int $pn_subject_row_id
	 */
	public function startRowIndexing( int $pn_subject_tablenum, int $pn_subject_row_id ): void {
		$this->index_content_buffer = [];
		$this->indexing_subject_tablenum = $pn_subject_tablenum;
		$this->indexing_subject_row_id = $pn_subject_row_id;
		$this->indexing_subject_tablename = Datamodel::getTableName( $pn_subject_tablenum );
	}
	# -------------------------------------------------------

	/**
	 * Index field
	 *
	 * @param int $pn_content_tablenum
	 * @param string $ps_content_fieldname
	 * @param int $pn_content_row_id
	 * @param mixed $pm_content
	 * @param array $pa_options
	 *
	 * @return null
	 */
	public function indexField(
		int $pn_content_tablenum, string $ps_content_fieldname, int $pn_content_row_id, $pm_content,
		?array $pa_options = null
	) {
		$o_field = new Elastic8\Field( $pn_content_tablenum, $ps_content_fieldname );
		if ( ! is_array( $pm_content ) ) {
			$pm_content = [ $pm_content ];
		}

		foreach ( $pm_content as $ps_content ) {
			$va_fragment = $o_field->getIndexingFragment( $ps_content, $pa_options );
			$va_record = null;

			if ( ! $this->isReindexing() ) {
				try {
					$va_record = $this->getClient()->get( [
						'index' => $this->getIndexName( $this->indexing_subject_tablename ),
						'id' => $this->indexing_subject_row_id
					] )['_source'];
				} catch ( ClientResponseException $e ) {
					$va_record = null;
				}
			}

			// if the record already exists, do incremental indexing
			if ( is_array( $va_record ) && ( sizeof( $va_record ) > 0 ) ) {
				$this->addFragmentToUpdateContentBuffer( $va_fragment, $va_record, $this->indexing_subject_tablename,
					$this->indexing_subject_row_id, $pn_content_row_id );
			} else { // otherwise create record in index
				foreach ( $va_fragment as $vs_key => $vm_val ) {
					$this->index_content_buffer[ $vs_key ][] = $vm_val;
					// this list basically indexes the values above by content row id. we need that to have a chance
					// to update indexing for specific values [content row ids] in place
					$this->index_content_buffer[ $vs_key . '_content_ids' ][] = $pn_content_row_id;
				}
			}
		}
	}
	# -------------------------------------------------------

	/**
	 * Commit indexing for row
	 * That doesn't necessarily mean it's actually written to the index.
	 * We still keep the data local until the document buffer is full.
	 */
	public function commitRowIndexing() {
		if ( sizeof( $this->index_content_buffer ) > 0 ) {
			self::$doc_content_buffer[ $this->indexing_subject_tablename . '/' .
			$this->indexing_subject_row_id ]
				= $this->index_content_buffer;
		}

		unset( $this->indexing_subject_tablenum );
		unset( $this->indexing_subject_row_id );
		unset( $this->indexing_subject_tablename );

		if ( (
				sizeof( self::$doc_content_buffer ) +
				sizeof( self::$update_content_buffer ) +
				sizeof( self::$delete_buffer )
			) > $this->getOption( 'maxIndexingBufferSize' )
		) {
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------

	/**
	 * Delete indexing for row
	 *
	 * @param int $pn_subject_tablenum
	 * @param int $pn_subject_row_id
	 * @param int|null $pn_field_tablenum
	 * @param int|null|array $pm_field_nums
	 * @param int|null $pn_content_row_id
	 */
	public function removeRowIndexing(
		int $pn_subject_tablenum, int $pn_subject_row_id, ?int $pn_field_tablenum = null, $pm_field_nums = null,
		?int $pn_content_row_id = null, ?int $pn_rel_type_id = null
	) {
		$table = Datamodel::getTableName( $pn_subject_tablenum );
		// if the field table num is set, we only remove content for this field and don't nuke the entire record!
		if ( $pn_field_tablenum ) {
			if ( is_array( $pm_field_nums ) ) {
				foreach ( $pm_field_nums as $ps_content_fieldnum ) {
					$o_field = new Elastic8\Field( $pn_field_tablenum, $ps_content_fieldnum );
					$va_fragment = $o_field->getIndexingFragment( '' );

					// fetch the record
					try {
						$va_record = $this->getClient()->get( [
							'index' => $this->getIndexName( $table ),
							'id' => $pn_subject_row_id
						] )['_source'];
					} catch ( ClientResponseException $e ) {
						// record is gone?
						unset( self::$update_content_buffer[ $table ][ $pn_subject_row_id ] );
						continue;
					}

					foreach ( $va_fragment as $vs_key => $vm_val ) {
						if ( isset( $va_record[ $vs_key ] ) ) {
							// find the index for this content row id in our _content_ids index list
							$va_values = $va_record[ $vs_key ];
							$va_indexes = $va_record[ $vs_key . '_content_ids' ];
							if ( is_array( $va_indexes ) ) {
								$vn_index = array_search( $pn_content_row_id, $va_indexes );
								// nuke that very index in the value array for this field -- all the other values, including the indexes stay intact
								unset( $va_values[ $vn_index ] );
								unset( $va_indexes[ $vn_index ] );
							} else {
								if ( sizeof( $va_values ) == 1 ) {
									$va_values = [];
									$va_indexes = [];
								}
							}

							// we reindex both value and index arrays here, starting at 0
							// json_encode seems to treat something like array(1=>'foo') as object/hash, rather than a list .. which is not good
							self::$update_content_buffer[ $table ][ $pn_subject_row_id ][ $vs_key ]
								= array_values( $va_values );
							self::$update_content_buffer[ $table ][ $pn_subject_row_id ][ $vs_key . '_content_ids' ]
								= array_values( $va_indexes );
						}
					}
				}
			}

			if ( (
					sizeof( self::$doc_content_buffer ) +
					sizeof( self::$update_content_buffer ) +
					sizeof( self::$delete_buffer )
				) > $this->getOption( 'maxIndexingBufferSize' )
			) {
				$this->flushContentBuffer();
			}

		} else {
			// queue record for removal -- also make sure we don't try do any unecessary indexing
			unset( self::$update_content_buffer[ $table ][ $pn_subject_row_id ] );
			self::$delete_buffer[ $table ][] = $pn_subject_row_id;
		}
	}
	# ------------------------------------------------

	/**
	 * Flush content buffer and write to index
	 *
	 * @throws ClientResponseException
	 */
	public function flushContentBuffer() {

		$va_bulk_params = [];

		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
		// @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_indexing_documents.html#_bulk_indexing

		// delete docs
		foreach ( self::$delete_buffer as $table => $va_rows ) {
			foreach ( array_unique( $va_rows ) as $vn_row_id ) {
				$va_bulk_params['body'][] = array(
					'delete' => array(
						'_index' => $this->getIndexName( $table ),
						'_id' => $vn_row_id
					)
				);

				// also make sure we don't do unessecary indexing for this record below
				unset( self::$update_content_buffer[ $table ][ $vn_row_id ] );
			}
		}

		// newly indexed docs
		foreach ( self::$doc_content_buffer as $vs_key => $va_doc_content_buffer ) {
			$va_tmp = explode( '/', $vs_key );
			$table = $va_tmp[0];
			$vn_primary_key = intval( $va_tmp[1] );

			$f = array(
				'index' => array(
					'_index' => $this->getIndexName( $table ),
					'_id' => $vn_primary_key
				)
			);
			$va_bulk_params['body'][] = $f;

			// add changelog to index
			$va_doc_content_buffer = array_merge(
				$va_doc_content_buffer,
				caGetChangeLogForElasticSearch(
					$this->db,
					Datamodel::getTableNum( $table ),
					$vn_primary_key
				)
			);

			$va_bulk_params['body'][] = $va_doc_content_buffer;
		}

		// update existing docs
		foreach ( self::$update_content_buffer as $table => $va_rows ) {
			foreach ( $va_rows as $vn_row_id => $va_fragment ) {

				$f = array(
					'update' => array(
						'_index' => $this->getIndexName( $table ),
						'_id' => (int) $vn_row_id
					)
				);
				$va_bulk_params['body'][] = $f;

				// add changelog to fragment
				$va_fragment = array_merge(
					$va_fragment,
					caGetChangeLogForElasticSearch(
						$this->db,
						Datamodel::getTableNum( $table ),
						$vn_row_id
					)
				);

				$va_bulk_params['body'][] = array( 'doc' => $va_fragment );
			}
		}

		if ( ! empty( $va_bulk_params['body'] ) ) {
			// Improperly encoded UTF8 characters in the body will make
			// Elastic throw errors and result in records being omitted from the index.
			// We force the document to UTF8 here to avoid that fate.
			$va_bulk_params['body'] = caEncodeUTF8Deep( $va_bulk_params['body'] );

			try {
				$resp = $this->getClient()->bulk( $va_bulk_params );
			} catch ( ElasticsearchException $e ) {
				throw new ApplicationException( _t( 'Indexing error %2', $e->getMessage() ) );
			}

			// we usually don't need indexing to be available *immediately* unless we're running automated tests of course :-)
			if ( caIsRunFromCLI() && $this->getIndexNamePrefix()
				&& ( ! defined( '__CollectiveAccess_IS_REINDEXING__' )
					|| ! __CollectiveAccess_IS_REINDEXING__ )
			) {
				$o_mapping = new Elastic8\Mapping();

				foreach ( $o_mapping->getTables() as $table ) {
					$this->getClient()->indices()->refresh( [ 'index' => $this->getIndexName( $table ) ] );
				}
			}
		}

		$this->index_content_buffer = [];
		self::$doc_content_buffer = [];
		self::$update_content_buffer = [];
		self::$delete_buffer = [];
		self::$record_cache = [];
	}
	# -------------------------------------------------------

	/**
	 * Set additional index-level settings like analyzers or token filters
	 */

	# -------------------------------------------------------
	public function optimizeIndex( int $table_num ) {
		$this->getClient()->indices()->forceMerge( [ 'index' => $this->getIndexName( $table_num ) ] );
	}

	# -------------------------------------------------------
	public function engineName() {
		return 'Elastic8';
	}
	# -------------------------------------------------------

	/**
	 * Performs the quickest possible search on the index for the specfied table_num in $pn_table_num
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back
	 * quickly. quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is
	 * critical and the ability to control search parameters is not required.
	 *
	 * @param $pn_table_num - The table index to search on
	 * @param $ps_search - The text to search on
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit'
	 *     (the maximum number of results to return)
	 *
	 * @return array - an array of results is returned keyed by primary key id. The array values boolean true. This is
	 *     done to ensure no duplicate row_ids
	 *
	 */
	public function quickSearch( $pn_table_num, $ps_search, $pa_options = [] ) {
		if ( ! is_array( $pa_options ) ) {
			$pa_options = [];
		}
		$vn_limit = caGetOption( 'limit', $pa_options, 0 );

		$o_result = $this->search( $pn_table_num, $ps_search );
		$va_pks = $o_result->getPrimaryKeyValues();
		if ( $vn_limit ) {
			$va_pks = array_slice( $va_pks, 0, $vn_limit );
		}

		return array_flip( $va_pks );
	}

	# -------------------------------------------------------
	public function isReindexing() {
		return ( defined( '__CollectiveAccess_IS_REINDEXING__' ) && __CollectiveAccess_IS_REINDEXING__ );
	}
	# -------------------------------------------------------
}
