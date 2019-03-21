<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/ElasticSearch.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/Datamodel.php');
require_once(__CA_LIB_DIR__.'/Plugins/WLPlug.php');
require_once(__CA_LIB_DIR__.'/Plugins/IWLPlugSearchEngine.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/BaseSearchPlugin.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearchResult.php');

require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/Field.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/Mapping.php');
require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/Query.php');

class WLPlugSearchEngineElasticSearch extends BaseSearchPlugin implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	protected $opa_index_content_buffer = array();

	protected $opn_indexing_subject_tablenum = null;
	protected $opn_indexing_subject_row_id = null;
	protected $ops_indexing_subject_tablename = null;

	/**
	 * @var \Elasticsearch\Client
	 */
	protected $opo_client;

	private $opa_doc_content_buffer = array();
	private $opa_update_content_buffer = array();
	private $opa_delete_buffer = array();

	protected $ops_elasticsearch_index_name = '';
	protected $ops_elasticsearch_base_url = '';
	
	protected $version;
	# -------------------------------------------------------
	public function __construct($po_db=null) {
		parent::__construct($po_db);

		// allow overriding settings from search.conf via constant (usually defined in bootstrap file)
		// this is useful for multi-instance setups which have the same set of config files for multiple instances
		if(defined('__CA_ELASTICSEARCH_BASE_URL__') && (strlen(__CA_ELASTICSEARCH_BASE_URL__)>0)) {
			$this->ops_elasticsearch_base_url = __CA_ELASTICSEARCH_BASE_URL__;
		} else {
			$this->ops_elasticsearch_base_url = $this->opo_search_config->get('search_elasticsearch_base_url');
		}
		$this->ops_elasticsearch_base_url = trim($this->ops_elasticsearch_base_url, "/");   // strip trailing slashes as they cause errors with ElasticSearch 5.x

		if(defined('__CA_ELASTICSEARCH_INDEX_NAME__') && (strlen(__CA_ELASTICSEARCH_INDEX_NAME__)>0)) {
			$this->ops_elasticsearch_index_name = __CA_ELASTICSEARCH_INDEX_NAME__;
		} else {
			$this->ops_elasticsearch_index_name = $this->opo_search_config->get('search_elasticsearch_index_name');
		}
		
		$this->version = $this->opo_search_config->get('elasticsearch_version');
		if (!in_array($this->version, [2, 5])) { $this->version = 5; }

		$this->opo_client = Elasticsearch\ClientBuilder::create()
			->setHosts([$this->ops_elasticsearch_base_url])
			->setRetries(2)
			->build();
	}
	# -------------------------------------------------------
	/**
	 * Get ElasticSearch index name
	 * @return string
	 */
	protected function getIndexName() {
		return $this->ops_elasticsearch_index_name;
	}
	# -------------------------------------------------------
	/**
	 * Refresh ElasticSearch mapping if necessary
	 * @param bool $pb_force force refresh if set to true [default is false]
	 * @throws \Exception
	 */
	public function refreshMapping($pb_force=false) {
		$o_mapping = new ElasticSearch\Mapping();
		if($o_mapping->needsRefresh() || $pb_force) {
			try {
				if(!$this->getClient()->indices()->exists(array('index' => $this->getIndexName()))) {
					$this->getClient()->indices()->create(array('index' => $this->getIndexName()));
				}
				// if we don't refresh() after creating, ES throws a IndexPrimaryShardNotAllocatedException
				// @see https://groups.google.com/forum/#!msg/elasticsearch/hvMhx162E-A/on-3druwehwJ
				// -- seems to be fixed in 2.x
				//$this->getClient()->indices()->refresh(array('index' => $this->getIndexName()));
			} catch (Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
				// noop -- the exception happens when the index already exists, which is good
			}

			try {
				$this->setIndexSettings();

				foreach ($o_mapping->get() as $vs_table => $va_config) {
				    $params = [
						'index' => $this->getIndexName(),
						'type' => $vs_table,
						'update_all_types' => true,
						'body' => array($vs_table => $va_config)
					];
				    if($this->version < 5) {  $params['ignore_conflicts'] = true; }
					$this->getClient()->indices()->putMapping($params);
				}
			} catch (Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
				throw new \Exception(_t("Updating the ElasticSearch mapping failed. This is probably because of a type conflict. Try recreating the entire search index. The original error was %1", $e->getMessage()));
			}

			// resets the mapping cache key so that needsRefresh() returns false for 24h
			$o_mapping->ping();
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
	 *		literalContent = array of text content to be applied without tokenization
	 *		BOOST = Indexing boost to apply
	 *		PRIVATE = Set indexing to private
	 */
	public function updateIndexingInPlace($pn_subject_tablenum, $pa_subject_row_ids, $pn_content_tablenum, $ps_content_fieldnum, $pn_content_container_id, $pn_content_row_id, $ps_content, $pa_options=null) {
		$vs_table_name = Datamodel::getTableName($pn_subject_tablenum);

		$o_field = new ElasticSearch\Field($pn_content_tablenum, $ps_content_fieldnum);
		$va_fragment = $o_field->getIndexingFragment($ps_content, $pa_options);

		foreach($pa_subject_row_ids as $pn_subject_row_id) {
			// fetch the record
			try {
				$va_record = $this->getClient()->get([
						'index' => $this->getIndexName(),
						'type' => $vs_table_name,
						'id' => $pn_subject_row_id
				])['_source'];
 			} catch(\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
				$va_record = array(); // record doesn't exist yet --> the update API will create it
			}

			$this->addFragmentToUpdateContentBuffer($va_fragment, $va_record, $vs_table_name, $pn_subject_row_id, $pn_content_row_id);
		}

		if ((
				sizeof($this->opa_doc_content_buffer) +
				sizeof($this->opa_update_content_buffer) +
				sizeof($this->opa_delete_buffer)
			) > $this->getOption('maxIndexingBufferSize'))
		{
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------
	/**
	 * Utility function that adds a given indexing fragment to the update content buffer
	 * @param array $pa_fragment
	 * @param array $pa_record
	 * @param $ps_table_name
	 * @param $pn_subject_row_id
	 * @param $pn_content_row_id
	 */
	private function addFragmentToUpdateContentBuffer(array $pa_fragment, array $pa_record, $ps_table_name, $pn_subject_row_id, $pn_content_row_id) {
		foreach($pa_fragment as $vs_key => $vm_val) {
			if(isset($pa_record[$vs_key])) {
				// find the index for this content row id in our _content_ids index list
				$va_values = $pa_record[$vs_key];
				$va_indexes = $pa_record[$vs_key.'_content_ids'];
				$vn_index = array_search($pn_content_row_id, $va_indexes);
				if($vn_index !== false) {
					// replace that very index in the value array for this field -- all the other values stay intact
					$va_values[$vn_index] = $vm_val;
				} else { // this particular content row id hasn't been indexed yet --> just add it
					$va_values[] = $vm_val;
					$va_indexes[] = $pn_content_row_id;
				}
				$this->opa_update_content_buffer[$ps_table_name][$pn_subject_row_id][$vs_key.'_content_ids'] = $va_indexes;
				$this->opa_update_content_buffer[$ps_table_name][$pn_subject_row_id][$vs_key] = $va_values;
			} else { // this field wasn't indexed yet -- just add it
				$this->opa_update_content_buffer[$ps_table_name][$pn_subject_row_id][$vs_key][] = $vm_val;
				$this->opa_update_content_buffer[$ps_table_name][$pn_subject_row_id][$vs_key.'_content_ids'][] = $pn_content_row_id;
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Get ElasticSearch client
	 * @return \Elasticsearch\Client
	 */
	protected function getClient() {
		return $this->opo_client;
	}
	# -------------------------------------------------------
	public function init() {
		if(($vn_max_indexing_buffer_size = (int)$this->opo_search_config->get('elasticsearch_indexing_buffer_size')) < 1) {
			$vn_max_indexing_buffer_size = 250;
		}

		$this->opa_options = array(
			'start' => 0,
			'limit' => 100000,												// maximum number of hits to return [default=100000],
			'maxIndexingBufferSize' => $vn_max_indexing_buffer_size			// maximum number of indexed content items to accumulate before writing to the index
		);

		$this->opa_capabilities = array(
			'incremental_reindexing' => true
		);
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex)
	 *
	 * @param null|int $pn_table_num
	 * @param bool $pb_dont_refresh
	 * @return bool
	 */
	public function truncateIndex($pn_table_num = null, $pb_dont_refresh = false) {
		if(!$pn_table_num) {
			// nuke the entire index
			try {
				$this->getClient()->indices()->delete(['index' => $this->getIndexName()]);
			} catch(Elasticsearch\Common\Exceptions\Missing404Exception $e) {
				// noop
			} //finally {
				if(!$pb_dont_refresh) {
					$this->refreshMapping(true);
				}
			//}
		} else {
			// use scroll API to find all documents in a particular mapping/table and delete them using the bulk API
			// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
			// @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_search_operations.html#_scan_scroll

			if(!$vs_table_name = Datamodel::getTableName($pn_table_num)) { return false; }

			$va_search_params = array(
				'scroll' => '1m',          // how long between scroll requests. should be small!
				'index' => $this->getIndexName(),
				'type' => $vs_table_name,
				'body' => array(
					'query' => array(
						'match_all' => $this->version >= 5 ? new \stdClass() : []
					)
				)
			);
			if ($this->version < 5){
				$va_search_params['search_type'] = 'scan';
			}

			$va_tmp = $this->getClient()->search($va_search_params);   // Execute the search
			$vs_scroll_id = $va_tmp['_scroll_id'];   // The response will contain no results, just a _scroll_id

			// Now we loop until the scroll "cursors" are exhausted
			$va_delete_params = array();
			while (\true) {

				$va_response = $this->getClient()->scroll([
						'scroll_id' => $vs_scroll_id,  //...using our previously obtained _scroll_id
						'scroll' => '1m'           // and the same timeout window
					]
				);

				if (sizeof($va_response['hits']['hits']) > 0) {
					foreach($va_response['hits']['hits'] as $va_result) {
						$va_delete_params['body'][] = array(
							'delete' => array(
								'_index' => $this->getIndexName(),
								'_type' => $vs_table_name,
								'_id' => $va_result['_id']
							)
						);
					}

					// Must always refresh your _scroll_id!  It can change sometimes
					$vs_scroll_id = $va_response['_scroll_id'];
				} else {
					// No results, scroll cursor is empty
					break;
				}
			}

			if(sizeof($va_delete_params) > 0) {
				$this->getClient()->bulk($va_delete_params);
			}
		}
		return true;
	}

	# -------------------------------------------------------
	public function setTableNum($pn_table_num) {
		$this->opn_indexing_subject_tablenum = $pn_table_num;
	}
	# -------------------------------------------------------
	public function __destruct() {
		if(!defined('__CollectiveAccess_Installer__') || !__CollectiveAccess_Installer__) {
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
	 * @return WLPlugSearchEngineElasticSearchResult
	 */
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null) {
		Debug::msg("[ElasticSearch] incoming search query is: {$ps_search_expression}");
		Debug::msg("[ElasticSearch] incoming query filters are: " . print_r($pa_filters, true));

		$o_query = new ElasticSearch\Query($pn_subject_tablenum, $ps_search_expression, $po_rewritten_query, $pa_filters);
		$vs_query = $o_query->getSearchExpression();

		Debug::msg("[ElasticSearch] actual search query sent to ES: {$vs_query}");

		$va_search_params = array(
			'index' => $this->getIndexName(),
			'type' => Datamodel::getTableName($pn_subject_tablenum),
			'body' => array(
				// we do paging in our code
				'from' => 0, 'size' => 2147483647, // size is Java's 32bit int, for ElasticSearch
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
		if(($va_additional_filters = $o_query->getAdditionalFilters()) && is_array($va_additional_filters) && (sizeof($va_additional_filters) > 0)) {
			foreach($va_additional_filters as $va_filter) {
				$va_search_params['body']['query']['bool']['must'][] = $va_filter;
			}
		}

		Debug::msg("[ElasticSearch] actual query filters are: " . print_r($va_additional_filters, true));
		try {
			$va_results = $this->getClient()->search($va_search_params);
		} catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
			$va_results = ['hits' => ['hits' => []]];
		}

		return new WLPlugSearchEngineElasticSearchResult($va_results['hits']['hits'], $pn_subject_tablenum);
	}
	# -------------------------------------------------------
	/**
	 * Start row indexing
	 * @param int $pn_subject_tablenum
	 * @param int $pn_subject_row_id
	 */
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id){
		$this->opa_index_content_buffer = array();
		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;
		$this->ops_indexing_subject_tablename = Datamodel::getTableName($pn_subject_tablenum);
	}
	# -------------------------------------------------------
	/**
	 * Index field
	 * @param int $pn_content_tablenum
	 * @param string $ps_content_fieldname
	 * @param int $pn_content_row_id
	 * @param mixed $pm_content
	 * @param array $pa_options
	 * @return null
	 */
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options) {
		$o_field = new ElasticSearch\Field($pn_content_tablenum, $ps_content_fieldname);
		if(!is_array($pm_content)) { $pm_content = [$pm_content]; }

		foreach($pm_content as $ps_content) {
			$va_fragment = $o_field->getIndexingFragment($ps_content, $pa_options);
			$va_record = null;

			if(!$this->isReindexing()) {
				try {
					$va_record = $this->getClient()->get([
						'index' => $this->getIndexName(),
						'type' => $this->ops_indexing_subject_tablename,
						'id' => $this->opn_indexing_subject_row_id
					])['_source'];
				} catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
					$va_record = null;
				}
			}

			// if the record already exists, do incremental indexing
			if (is_array($va_record) && (sizeof($va_record) > 0)) {
				$this->addFragmentToUpdateContentBuffer($va_fragment, $va_record, $this->ops_indexing_subject_tablename, $this->opn_indexing_subject_row_id, $pn_content_row_id);
			} else { // otherwise create record in index
				foreach ($va_fragment as $vs_key => $vm_val) {
					$this->opa_index_content_buffer[$vs_key][] = $vm_val;
					// this list basically indexes the values above by content row id. we need that to have a chance
					// to update indexing for specific values [content row ids] in place
					$this->opa_index_content_buffer[$vs_key . '_content_ids'][] = $pn_content_row_id;
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
		if(sizeof($this->opa_index_content_buffer) > 0) {
			$this->opa_doc_content_buffer[
				$this->ops_indexing_subject_tablename.'/'.
				$this->opn_indexing_subject_row_id
			] = $this->opa_index_content_buffer;
		}

		unset($this->opn_indexing_subject_tablenum);
		unset($this->opn_indexing_subject_row_id);
		unset($this->ops_indexing_subject_tablename);

		if ((
				sizeof($this->opa_doc_content_buffer) +
				sizeof($this->opa_update_content_buffer) +
				sizeof($this->opa_delete_buffer)
			) > $this->getOption('maxIndexingBufferSize'))
		{
			$this->flushContentBuffer();
		}
	}
	# -------------------------------------------------------
	/**
	 * Delete indexing for row
	 * @param int $pn_subject_tablenum
	 * @param int $pn_subject_row_id
	 * @param int|null $pn_field_tablenum
	 * @param int|null|array $pm_field_nums
	 * @param int|null $pn_content_row_id
	 */
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id, $pn_field_tablenum=null, $pm_field_nums=null, $pn_content_row_id=null) {
		$vs_table_name = Datamodel::getTableName($pn_subject_tablenum);
		// if the field table num is set, we only remove content for this field and don't nuke the entire record!
		if($pn_field_tablenum) {
			if (is_array($pm_field_nums)) {
				foreach($pm_field_nums as $ps_content_fieldnum) {
					$o_field = new ElasticSearch\Field($pn_field_tablenum, $ps_content_fieldnum);
					$va_fragment = $o_field->getIndexingFragment('');

					// fetch the record
					try {
						$va_record = $this->getClient()->get([
							'index' => $this->getIndexName(),
							'type' => $vs_table_name,
							'id' => $pn_subject_row_id
						])['_source'];
					} catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
						// record is gone?
						unset($this->opa_update_content_buffer[$vs_table_name][$pn_subject_row_id]);
						continue;
					}

					foreach($va_fragment as $vs_key => $vm_val) {
						if(isset($va_record[$vs_key])) {
							// find the index for this content row id in our _content_ids index list
							$va_values = $va_record[$vs_key];
							$va_indexes = $va_record[$vs_key.'_content_ids'];
							if(is_array($va_indexes)) {
								$vn_index = array_search($pn_content_row_id, $va_indexes);
								// nuke that very index in the value array for this field -- all the other values, including the indexes stay intact
								unset($va_values[$vn_index]);
								unset($va_indexes[$vn_index]);
							} else {
								if(sizeof($va_values) == 1) {
									$va_values = array();
									$va_indexes = array();
								}
							}

							// we reindex both value and index arrays here, starting at 0
							// json_encode seems to treat something like array(1=>'foo') as object/hash, rather than a list .. which is not good
							$this->opa_update_content_buffer[$vs_table_name][$pn_subject_row_id][$vs_key] = array_values($va_values);
							$this->opa_update_content_buffer[$vs_table_name][$pn_subject_row_id][$vs_key.'_content_ids'] = array_values($va_indexes);
						}
					}
				}
			}

			if ((
					sizeof($this->opa_doc_content_buffer) +
					sizeof($this->opa_update_content_buffer) +
					sizeof($this->opa_delete_buffer)
				) > $this->getOption('maxIndexingBufferSize'))
			{
				$this->flushContentBuffer();
			}

		} else {
			// queue record for removal -- also make sure we don't try do any unecessary indexing
			unset($this->opa_update_content_buffer[$vs_table_name][$pn_subject_row_id]);
			$this->opa_delete_buffer[$vs_table_name][] = $pn_subject_row_id;
		}
	}
	# ------------------------------------------------
	/**
	 * Flush content buffer and write to index
	 * @throws Elasticsearch\Common\Exceptions\NoNodesAvailableException
	 */
	public function flushContentBuffer() {
		$this->refreshMapping();

		$va_bulk_params = array();

		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
		// @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_indexing_documents.html#_bulk_indexing

		// delete docs
		foreach($this->opa_delete_buffer as $vs_table_name => $va_rows) {
			foreach(array_unique($va_rows) as $vn_row_id) {
				$va_bulk_params['body'][] = array(
					'delete' => array(
						'_index' => $this->getIndexName(),
						'_type' => $vs_table_name,
						'_id' => $vn_row_id
					)
				);

				// also make sure we don't do unessecary indexing for this record below
				unset($this->opa_update_content_buffer[$vs_table_name][$vn_row_id]);
			}
		}

		// newly indexed docs
		foreach($this->opa_doc_content_buffer as $vs_key => $va_doc_content_buffer) {
			$va_tmp = explode('/', $vs_key);
			$vs_table_name = $va_tmp[0];
			$vn_primary_key = intval($va_tmp[1]);

			$va_bulk_params['body'][] = array(
				'index' => array(
					'_index' => $this->getIndexName(),
					'_type' => $vs_table_name,
					'_id' => $vn_primary_key
				)
			);

			// add changelog to index
			$va_doc_content_buffer = array_merge(
				$va_doc_content_buffer,
				caGetChangeLogForElasticSearch(
					$this->opo_db,
					Datamodel::getTableNum($vs_table_name),
					$vn_primary_key
				)
			);

			$va_bulk_params['body'][] = $va_doc_content_buffer;
		}

		// update existing docs
		foreach($this->opa_update_content_buffer as $vs_table_name => $va_rows) {
			foreach($va_rows as $vn_row_id => $va_fragment) {

				$va_bulk_params['body'][] = array(
					'update' => array(
						'_index' => $this->getIndexName(),
						'_type' => $vs_table_name,
						'_id' => (int) $vn_row_id
					)
				);

				// add changelog to fragment
				$va_fragment = array_merge(
					$va_fragment,
					caGetChangeLogForElasticSearch(
						$this->opo_db,
						Datamodel::getTableNum($vs_table_name),
						$vn_row_id
					)
				);

				$va_bulk_params['body'][] = array('doc' => $va_fragment);
			}
		}

		if(sizeof($va_bulk_params['body'])) {
			$this->getClient()->bulk($va_bulk_params);

			// we usually don't need indexing to be available *immediately* unless we're running automated tests of course :-)
			if(caIsRunFromCLI() && $this->getIndexName() && (!defined('__CollectiveAccess_IS_REINDEXING__') || !__CollectiveAccess_IS_REINDEXING__)) {
				$this->getClient()->indices()->refresh(array('index' => $this->getIndexName()));
			}
		}

		$this->opa_index_content_buffer = array();
		$this->opa_doc_content_buffer = array();
		$this->opa_update_content_buffer = array();
		$this->opa_delete_buffer = array();
	}
	# -------------------------------------------------------
	/**
	 * Set additional index-level settings like analyzers or token filters
	 */
	protected function setIndexSettings() {
		$this->getClient()->indices()->refresh(array('index' => $this->getIndexName()));


		$this->getClient()->indices()->close(array(
			'index' => $this->getIndexName()
		));

		try {
		    $params = array(
                'index' => $this->getIndexName(),
                'body' => array(
                    'max_result_window' => 2147483647,
                    'analysis' => array(
                        'analyzer' => array(
                            'keyword_lowercase' => array(
                                'tokenizer' => 'keyword',
                                'filter' => 'lowercase'
                            ),
                            'whitespace' => array(
                                'tokenizer' => 'whitespace',
                                'filter' => 'lowercase'
                            ),
                        )
                    )
                )
            );
            if ($this->version >= 5) {
                $params['body']['index.mapping.total_fields.limit'] = 20000;
            }
			$this->getClient()->indices()->putSettings($params);
		} catch(\Exception $e) {
			// noop
		}

		$this->getClient()->indices()->open(array(
			'index' => $this->getIndexName()
		));
	}
	# -------------------------------------------------------
	public function optimizeIndex($pn_tablenum) {
		$this->getClient()->indices()->forceMerge(array(
			'index' => $this->getIndexName()
		));
	}
	# -------------------------------------------------------
	public function engineName() {
		return 'ElasticSearch';
	}
	# -------------------------------------------------------
	/**
	 * Performs the quickest possible search on the index for the specfied table_num in $pn_table_num
	 * using the text in $ps_search. Unlike the search() method, quickSearch doesn't support
	 * any sort of search syntax. You give it some text and you get a collection of (hopefully) relevant results back quickly.
	 * quickSearch() is intended for autocompleting search suggestion UI's and the like, where performance is critical
	 * and the ability to control search parameters is not required.
	 *
	 * @param $pn_table_num - The table index to search on
	 * @param $ps_search - The text to search on
	 * @param $pa_options - an optional associative array specifying search options. Supported options are: 'limit' (the maximum number of results to return)
	 *
	 * @return array - an array of results is returned keyed by primary key id. The array values boolean true. This is done to ensure no duplicate row_ids
	 *
	 */
	public function quickSearch($pn_table_num, $ps_search, $pa_options=array()) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vn_limit = caGetOption('limit', $pa_options, 0);

		$o_result = $this->search($pn_table_num, $ps_search);
		$va_pks = $o_result->getPrimaryKeyValues();
		if($vn_limit) {
			$va_pks = array_slice($va_pks, 0, $vn_limit);
		}

		return array_flip($va_pks);
	}
	# -------------------------------------------------------
	public function isReindexing() {
		return (defined('__CollectiveAccess_IS_REINDEXING__') && __CollectiveAccess_IS_REINDEXING__);
	}
	# -------------------------------------------------------
}
