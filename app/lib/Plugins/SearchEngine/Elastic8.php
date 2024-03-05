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
	public function __construct( $db = null ) {
		parent::__construct( $db );

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
	 * @param bool $force force refresh if set to true [default is false]
	 *
	 * @throws Exception
	 */
	public function refreshMapping( $force = false ) {

		static $mapping;
		if ( ! $mapping ) {
			$mapping = new Elastic8\Mapping();
		}

		if ( $mapping->needsRefresh() || $force ) {
			foreach ( $mapping->get() as $table => $config ) {
				$index_name = $this->getIndexName( $table );
				try {
					if ( ! $this->getClient()->indices()->exists( [ 'index' => $index_name ] )->asBool() ) {
						$this->getClient()->indices()->create( [ 'index' => $index_name ] );
					}
					// if we don't refresh() after creating, ES throws a IndexPrimaryShardNotAllocatedException
					// @see https://groups.google.com/forum/#!msg/elasticsearch/hvMhx162E-A/on-3druwehwJ
					// -- seems to be fixed in 2.x
					//$this->getClient()->indices()->refresh(array('index' => $this->getIndexName($table)));
				} catch ( Elastic\ElasticSearch\Exception\ClientResponseException $e ) {
					// noop -- the exception happens when the index already exists, which is good
				}

				try {
					$this->setIndexSettings( $table );

					$params = [
						'index' => $index_name,
						'body' => $config,
					];
					$this->getClient()->indices()->putMapping( $params );
				} catch ( ClientResponseException $e ) {
					throw new Exception( _t( "Updating the ElasticSearch mapping failed. This is probably because of a type conflict. Try recreating the entire search index. The original error was %1",
						$e->getMessage() ) );
				}
			}

			// resets the mapping cache key so that needsRefresh() returns false for 24h
			$mapping->ping();
		}
	}
	# -------------------------------------------------------

	/**
	 *
	 *
	 * @param int $subject_tablenum
	 * @param array $subject_row_ids
	 * @param int $content_tablenum
	 * @param string $content_fieldnum
	 * @param int $content_row_id
	 * @param string $content
	 * @param array $options
	 *    literalContent = array of text content to be applied without tokenization
	 *    BOOST = Indexing boost to apply
	 *    PRIVATE = Set indexing to private
	 */
	public function updateIndexingInPlace(
		$subject_tablenum, $subject_row_ids, $content_tablenum, $content_fieldnum, $content_container_id,
		$content_row_id, $content, $options = null
	) {
		$table = Datamodel::getTableName( $subject_tablenum );

		$field = new Elastic8\Field( $content_tablenum, $content_fieldnum );
		$fragment = $field->getIndexingFragment( $content, $options );

		foreach ( $subject_row_ids as $subject_row_id ) {
			// fetch the record
			try {
				$record = $this->record_cache[ $table ][ $subject_row_id ] ?? null;
				if ( is_null( $record ) ) {
					$f = [
						'index' => $this->getIndexName( $table ),
						'id' => $subject_row_id
					];
					$record = $this->getClient()->get( $f )['_source'];
				}
			} catch ( ClientResponseException $e ) {
				$record = []; // record doesn't exist yet --> the update API will create it
			}
			$this->record_cache[ $table ][ $subject_row_id ] = $record;

			$this->addFragmentToUpdateContentBuffer( $fragment, $record, $table, $subject_row_id,
				$content_row_id );
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
	 * @param array $fragment
	 * @param array $record
	 * @param $table_name
	 * @param $subject_row_id
	 * @param $content_row_id
	 */
	private function addFragmentToUpdateContentBuffer(
		array $fragment, array $record, $table_name, $subject_row_id, $content_row_id
	) {
		foreach ( $fragment as $key => $val ) {
			if ( isset( $record[ $key ] ) ) {
				// find the index for this content row id in our _content_ids index list
				$values = $record[ $key ];
				$indexes = $record[ $key . '_content_ids' ];
				$index = array_search( $content_row_id, $indexes );
				if ( $index !== false ) {
					// replace that very index in the value array for this field -- all the other values stay intact
					$values[ $index ] = $val;
				} else { // this particular content row id hasn't been indexed yet --> just add it
					$values[] = $val;
					$indexes[] = $content_row_id;
				}
				self::$update_content_buffer[ $table_name ][ $subject_row_id ][ $key . '_content_ids' ]
					= $indexes;
				self::$update_content_buffer[ $table_name ][ $subject_row_id ][ $key ] = $values;
			} else { // this field wasn't indexed yet -- just add it
				self::$update_content_buffer[ $table_name ][ $subject_row_id ][ $key ][] = $val;
				self::$update_content_buffer[ $table_name ][ $subject_row_id ][ $key . '_content_ids' ][]
					= $content_row_id;
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
		if ( ( $max_indexing_buffer_size = (int) $this->search_config->get( 'elasticsearch_indexing_buffer_size' ) )
			< 1
		) {
			$max_indexing_buffer_size = 250;
		}

		$this->options = array(
			'start' => 0,
			'limit' => 100000,
			// maximum number of hits to return [default=100000],
			'maxIndexingBufferSize' => $max_indexing_buffer_size
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
	 * @param null|int $table_num
	 * @param bool $dont_refresh
	 *
	 * @return bool
	 */
	public function truncateIndex( $table_num = null, $dont_refresh = false ) {
		if ( ! $table_num ) {
			// nuke the entire index
			try {
				$mapping = new Elastic8\Mapping();

				foreach ( $mapping->get() as $table => $config ) {
					$this->getClient()->indices()->delete( [ 'index' => $this->getIndexName( $table ) ] );
				}
			} catch ( ClientResponseException $e ) {
				// noop
			} //finally {
			if ( ! $dont_refresh ) {
				$this->refreshMapping( true );
			}
			//}
		} else {
			// use scroll API to find all documents in a particular mapping/table and delete them using the bulk API
			// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
			// @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_search_operations.html#_scan_scroll
			$table = Datamodel::getTableName( $table_num );
			$search_params = array(
				'scroll' => '1m',          // how long between scroll requests. should be small!
				'index' => $this->getIndexName( $table ),
				'body' => array(
					'query' => array(
						'match_all' => new stdClass()
					)
				)
			);

			$tmp = $this->getClient()->search( $search_params );   // Execute the search
			$scroll_id = $tmp['_scroll_id'];   // The response will contain no results, just a _scroll_id

			// Now we loop until the scroll "cursors" are exhausted
			$delete_params = [];
			while ( true ) {

				$response = $this->getClient()->scroll( [
						'scroll_id' => $scroll_id,  //...using our previously obtained _scroll_id
						'scroll' => '1m'           // and the same timeout window
					]
				);

				if ( sizeof( $response['hits']['hits'] ) > 0 ) {
					foreach ( $response['hits']['hits'] as $result ) {
						$delete_params['body'][] = array(
							'delete' => array(
								'_index' => $this->getIndexName( $table_num ),
								'_id' => $result['_id']
							)
						);
					}

					// Must always refresh your _scroll_id!  It can change sometimes
					$scroll_id = $response['_scroll_id'];
				} else {
					// No results, scroll cursor is empty
					break;
				}
			}

			if ( sizeof( $delete_params ) > 0 ) {
				$this->getClient()->bulk( $delete_params );
			}
		}

		return true;
	}

	# -------------------------------------------------------
	public function setTableNum( $table_num ) {
		$this->indexing_subject_tablenum = $table_num;
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
	 * @param int $subject_tablenum
	 * @param string $search_expression
	 * @param array $filters
	 * @param null|Zend_Search_Lucene_Search_Query_Boolean $rewritten_query
	 *
	 * @return WLPlugSearchEngineElasticSearchResult
	 */
	public function search(
		int $subject_tablenum, string $search_expression, array $filters = [], $rewritten_query
	) {
		Debug::msg( "[ElasticSearch] incoming search query is: {$search_expression}" );
		Debug::msg( "[ElasticSearch] incoming query filters are: " . print_r( $filters, true ) );

		$query = new Elastic8\Query( $subject_tablenum, $search_expression, $rewritten_query, $filters );
		$query_string = $query->getSearchExpression();

		Debug::msg( "[ElasticSearch] actual search query sent to ES: {$query_string}" );

		$search_params = array(
			'index' => $this->getIndexName( $subject_tablenum ),
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
									'query' => $query_string,
									'default_operator' => 'AND'
								),
							)
						)
					)
				)
			)
		);

		// apply additional filters that may have been set by the query
		if ( ( $additional_filters = $query->getAdditionalFilters() ) && is_array( $additional_filters )
			&& ( sizeof( $additional_filters ) > 0 )
		) {
			foreach ( $additional_filters as $filter ) {
				$search_params['body']['query']['bool']['must'][] = $filter;
			}
		}

		Debug::msg( "[ElasticSearch] actual query filters are: " . print_r( $additional_filters, true ) );
		try {
			$results = $this->getClient()->search( $search_params );
		} catch ( ClientResponseException $e ) {
			$results = [ 'hits' => [ 'hits' => [] ] ];
		}

		return new WLPlugSearchEngineElasticSearchResult( $results['hits']['hits'], $subject_tablenum );
	}
	# -------------------------------------------------------

	/**
	 * Start row indexing
	 *
	 * @param int $subject_tablenum
	 * @param int $subject_row_id
	 */
	public function startRowIndexing( int $subject_tablenum, int $subject_row_id ): void {
		$this->index_content_buffer = [];
		$this->indexing_subject_tablenum = $subject_tablenum;
		$this->indexing_subject_row_id = $subject_row_id;
		$this->indexing_subject_tablename = Datamodel::getTableName( $subject_tablenum );
	}
	# -------------------------------------------------------

	/**
	 * Index field
	 *
	 * @param int $content_tablenum
	 * @param string $content_fieldname
	 * @param int $content_row_id
	 * @param mixed $content
	 * @param array $options
	 *
	 * @return null
	 */
	public function indexField(
		int $content_tablenum, string $content_fieldname, int $content_row_id, $content,
		?array $options = null
	) {
		$field = new Elastic8\Field( $content_tablenum, $content_fieldname );
		if ( ! is_array( $content ) ) {
			$content = [ $content ];
		}

		foreach ( $content as $ps_content ) {
			$fragment = $field->getIndexingFragment( $ps_content, $options );
			$record = null;

			if ( ! $this->isReindexing() ) {
				try {
					$record = $this->getClient()->get( [
						'index' => $this->getIndexName( $this->indexing_subject_tablename ),
						'id' => $this->indexing_subject_row_id
					] )['_source'];
				} catch ( ClientResponseException $e ) {
					$record = null;
				}
			}

			// if the record already exists, do incremental indexing
			if ( is_array( $record ) && ( sizeof( $record ) > 0 ) ) {
				$this->addFragmentToUpdateContentBuffer( $fragment, $record, $this->indexing_subject_tablename,
					$this->indexing_subject_row_id, $content_row_id );
			} else { // otherwise create record in index
				foreach ( $fragment as $key => $val ) {
					$this->index_content_buffer[ $key ][] = $val;
					// this list basically indexes the values above by content row id. we need that to have a chance
					// to update indexing for specific values [content row ids] in place
					$this->index_content_buffer[ $key . '_content_ids' ][] = $content_row_id;
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
	 * @param int $subject_tablenum
	 * @param int $subject_row_id
	 * @param int|null $field_tablenum
	 * @param int|null|array $field_nums
	 * @param int|null $content_row_id
	 */
	public function removeRowIndexing(
		int $subject_tablenum, int $subject_row_id, ?int $field_tablenum = null, $field_nums = null,
		?int $content_row_id = null, ?int $rel_type_id = null
	) {
		$table = Datamodel::getTableName( $subject_tablenum );
		// if the field table num is set, we only remove content for this field and don't nuke the entire record!
		if ( $field_tablenum ) {
			if ( is_array( $field_nums ) ) {
				foreach ( $field_nums as $content_fieldnum ) {
					$field = new Elastic8\Field( $field_tablenum, $content_fieldnum );
					$fragment = $field->getIndexingFragment( '' );

					// fetch the record
					try {
						$record = $this->getClient()->get( [
							'index' => $this->getIndexName( $table ),
							'id' => $subject_row_id
						] )['_source'];
					} catch ( ClientResponseException $e ) {
						// record is gone?
						unset( self::$update_content_buffer[ $table ][ $subject_row_id ] );
						continue;
					}

					foreach ( $fragment as $key => $val ) {
						if ( isset( $record[ $key ] ) ) {
							// find the index for this content row id in our _content_ids index list
							$values = $record[ $key ];
							$indexes = $record[ $key . '_content_ids' ];
							if ( is_array( $indexes ) ) {
								$index = array_search( $content_row_id, $indexes );
								// nuke that very index in the value array for this field -- all the other values, including the indexes stay intact
								unset( $values[ $index ] );
								unset( $indexes[ $index ] );
							} else {
								if ( sizeof( $values ) == 1 ) {
									$values = [];
									$indexes = [];
								}
							}

							// we reindex both value and index arrays here, starting at 0
							// json_encode seems to treat something like array(1=>'foo') as object/hash, rather than a list .. which is not good
							self::$update_content_buffer[ $table ][ $subject_row_id ][ $key ]
								= array_values( $values );
							self::$update_content_buffer[ $table ][ $subject_row_id ][ $key . '_content_ids' ]
								= array_values( $indexes );
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
			unset( self::$update_content_buffer[ $table ][ $subject_row_id ] );
			self::$delete_buffer[ $table ][] = $subject_row_id;
		}
	}
	# ------------------------------------------------

	/**
	 * Flush content buffer and write to index
	 *
	 * @throws ClientResponseException
	 */
	public function flushContentBuffer() {
		$this->refreshMapping();

		$bulk_params = [];

		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
		// @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_indexing_documents.html#_bulk_indexing

		// delete docs
		foreach ( self::$delete_buffer as $table => $rows ) {
			foreach ( array_unique( $rows ) as $row_id ) {
				$bulk_params['body'][] = array(
					'delete' => array(
						'_index' => $this->getIndexName( $table ),
						'_id' => $row_id
					)
				);

				// also make sure we don't do unessecary indexing for this record below
				unset( self::$update_content_buffer[ $table ][ $row_id ] );
			}
		}

		// newly indexed docs
		foreach ( self::$doc_content_buffer as $key => $doc_content_buffer ) {
			$tmp = explode( '/', $key );
			$table = $tmp[0];
			$primary_key = intval( $tmp[1] );

			$f = array(
				'index' => array(
					'_index' => $this->getIndexName( $table ),
					'_id' => $primary_key
				)
			);
			$bulk_params['body'][] = $f;

			// add changelog to index
			$doc_content_buffer = array_merge(
				$doc_content_buffer,
				caGetChangeLogForElasticSearch(
					$this->db,
					Datamodel::getTableNum( $table ),
					$primary_key
				)
			);

			$bulk_params['body'][] = $doc_content_buffer;
		}

		// update existing docs
		foreach ( self::$update_content_buffer as $table => $rows ) {
			foreach ( $rows as $row_id => $fragment ) {

				$f = array(
					'update' => array(
						'_index' => $this->getIndexName( $table ),
						'_id' => (int) $row_id
					)
				);
				$bulk_params['body'][] = $f;

				// add changelog to fragment
				$fragment = array_merge(
					$fragment,
					caGetChangeLogForElasticSearch(
						$this->db,
						Datamodel::getTableNum( $table ),
						$row_id
					)
				);

				$bulk_params['body'][] = array( 'doc' => $fragment );
			}
		}

		if ( ! empty( $bulk_params['body'] ) ) {
			// Improperly encoded UTF8 characters in the body will make
			// Elastic throw errors and result in records being omitted from the index.
			// We force the document to UTF8 here to avoid that fate.
			$bulk_params['body'] = caEncodeUTF8Deep( $bulk_params['body'] );

			try {
				$resp = $this->getClient()->bulk( $bulk_params );
			} catch ( ElasticsearchException $e ) {
				throw new ApplicationException( _t( 'Indexing error %2', $e->getMessage() ) );
			}

			// we usually don't need indexing to be available *immediately* unless we're running automated tests of course :-)
			if ( caIsRunFromCLI() && $this->getIndexNamePrefix()
				&& ( ! defined( '__CollectiveAccess_IS_REINDEXING__' )
					|| ! __CollectiveAccess_IS_REINDEXING__ )
			) {
				$mapping = new Elastic8\Mapping();

				foreach ( $mapping->get() as $table => $config ) {
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
	protected function setIndexSettings( $table ) {
		$index_name = $this->getIndexName( $table );

		$this->getClient()->indices()->refresh( [ 'index' => $index_name ] );
		$this->getClient()->indices()->close( [ 'index' => $index_name ] );

		try {
			$params = [
				'index' => $index_name,
				'body' => [
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
			];
			$this->getClient()->indices()->putSettings( $params );
		} catch ( Exception $e ) {
			// noop
		}

		$this->getClient()->indices()->open( [ 'index' => $this->getIndexName( $table ) ] );
	}

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
	 * Performs the quickest possible search on the index for the specfied table_num in $table_num
	 * using the text in $search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back
	 * quickly. quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is
	 * critical and the ability to control search parameters is not required.
	 *
	 * @param $table_num - The table index to search on
	 * @param $search - The text to search on
	 * @param $options - an optional associative array specifying search options. Supported options are: 'limit'
	 *     (the maximum number of results to return)
	 *
	 * @return array - an array of results is returned keyed by primary key id. The array values boolean true. This is
	 *     done to ensure no duplicate row_ids
	 *
	 */
	public function quickSearch( $table_num, $search, $options = [] ) {
		if ( ! is_array( $options ) ) {
			$options = [];
		}
		$limit = caGetOption( 'limit', $options, 0 );

		$result = $this->search( $table_num, $search );
		$pks = $result->getPrimaryKeyValues();
		if ( $limit ) {
			$pks = array_slice( $pks, 0, $limit );
		}

		return array_flip( $pks );
	}

	# -------------------------------------------------------
	public function isReindexing() {
		return ( defined( '__CollectiveAccess_IS_REINDEXING__' ) && __CollectiveAccess_IS_REINDEXING__ );
	}
	# -------------------------------------------------------
}
