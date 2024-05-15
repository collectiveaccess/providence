<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/SearchIndexer.php : indexing of content for search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Search/SearchBase.php");
require_once(__CA_LIB_DIR__.'/Utils/Graph.php');
require_once(__CA_LIB_DIR__.'/Utils/Timer.php');
require_once(__CA_LIB_DIR__.'/Utils/CLIProgressBar.php');
require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');

class SearchIndexer extends SearchBase {
	# ------------------------------------------------

	private $opa_dependencies_to_update;

	private $opo_metadata_element = null;

	/**
	 * @var null|ca_search_indexing_queue
	 */
	private $opo_search_indexing_queue = null;

	static $s_search_indexing_queue_inserts = [];
	static $s_search_unindexing_queue_inserts = [];
	
	static $queued_entry_count = 0;
	
	/**
	 *
	 */
	static $s_search_config_field_entry_to_index_field_nums_cache = [];

	# ------------------------------------------------
	/**
	 * Constructor takes Db() instance which it uses for all database access. You should pass an instance in
	 * because all the database accesses need to be in the same transactional context as whatever else you're doing. In
	 * the case of BaseModel::insert(), BaseModel::update() and BaseModel::delete()they're always in a transactional context
	 * so this is critical. If you don't pass an Db() instance then the constructor creates a new one, which is useful for
	 * cases where you're reindexing and not in a transaction.
	 */
	public function __construct($opo_db=null, $ps_engine=null) {
		require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
		parent::__construct($opo_db, $ps_engine);

		$this->opo_metadata_element = new ca_metadata_elements();
		$this->opo_search_indexing_queue = new ca_search_indexing_queue();
	}
	# -------------------------------------------------------
	public function __destruct() {
		$o_db = new Db();
		if(($c = sizeof(self::$s_search_indexing_queue_inserts)) > 0) {
			$va_insert_segments = array();
			foreach (self::$s_search_indexing_queue_inserts as $va_insert_data) {
				$va_insert_segments[] = "('" . join("','", $va_insert_data) . "')";
			}
			self::$s_search_indexing_queue_inserts = []; // nuke cache
            foreach($va_insert_segments as $x) {
			    $o_db->query("INSERT INTO ca_search_indexing_queue (table_num, row_id, field_data, reindex, changed_fields, options) VALUES {$x}");
            }
		}

		if(($c = sizeof(self::$s_search_unindexing_queue_inserts)) > 0) {
			$va_insert_segments = array();
			foreach (self::$s_search_unindexing_queue_inserts as $va_insert_data) {
				$va_insert_segments[] = "('" . join("','", $va_insert_data) . "')";
			}
			self::$s_search_unindexing_queue_inserts = array(); // nuke cache
            foreach($va_insert_segments as $x) {
			    $o_db->query("INSERT INTO ca_search_indexing_queue (table_num, row_id, is_unindex, dependencies) VALUES {$x}");
            }
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function setDb($po_db) {
		if (($this->opo_engine) && (method_exists($this->opo_engine, "setDb"))) {
			$this->opo_engine->setDb($po_db);
		}
	}
	# -------------------------------------------------------
	/**
	 * Returns a list of tables the require indexing
	 */
	public function getIndexedTables() {
		if(ExternalCache::contains('getIndexedTables', 'SearchIndexer')) {  return ExternalCache::fetch('getIndexedTables', 'SearchIndexer'); };
		$va_table_names = Datamodel::getTableNames();

		$o_db = $this->opo_db;
		$va_tables_to_index = $va_tables_by_size = array();
		foreach($va_table_names as $vs_table) {
			$vn_table_num = Datamodel::getTableNum($vs_table);
			$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
			$va_fields_to_index = $this->getFieldsToIndex($vn_table_num);
			if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
				continue;
			}

			$qr_all = $o_db->query("SELECT count(*) c FROM {$vs_table}".($t_instance->hasField('deleted') ? " WHERE deleted = 0" : ""));
			$qr_all->nextRow();
			$vn_num_rows = (int)$qr_all->get('c');

			$va_tables_to_index[$vs_table] = array('name' => $vs_table, 'num' => $vn_table_num, 'count' => $vn_num_rows, 'displayName' => $t_instance->getProperty('NAME_PLURAL'));
			$va_tables_by_size[$vs_table] = $vn_num_rows;
		}

		asort($va_tables_by_size);
		$va_tables_by_size = array_reverse($va_tables_by_size);

		$va_sorted_tables = array();
		foreach($va_tables_by_size as $vs_table => $vn_count) {
			$va_sorted_tables[$va_tables_to_index[$vs_table]['num']] = $va_tables_to_index[$vs_table];
		}

		ExternalCache::save('getIndexedTables', $va_sorted_tables, 'SearchIndexer', 3600);
		return $va_sorted_tables;
	}
	# -------------------------------------------------------
	/**
	 * Check if table is indexed for search.
	 *
	 * @param string|int $table_name_or_num Name or number of table
	 *
	 * @return bool
	 */
	public static function isIndexed($table_name_or_num) : bool {
		$table_num = is_numeric($table_name_or_num) ? (int)$table_name_or_num : Datamodel::getTableNum($table_name_or_num);
		if(!$table_num) {
			return false;
		}
		$o_indexer = new SearchIndexer();
		$tables = $o_indexer->getIndexedTables();
		
		return isset($tables[$table_num]);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function truncateIndex() {
		return $this->opo_engine->truncateIndex();
	}
	# -------------------------------------------------------
	/**
	 * Forces a full reindex of all rows in the database or, optionally, a single table
	 *
	 * @param array $pa_table_names
	 * @param array $pa_options Reindexing options:
	 *			showProgress
	 *			interactiveProgressDisplay
	 *			log
	 *			callback
	 * @return null|false
	 */
	public function reindex($pa_table_names=null, $pa_options=null) {
		define('__CollectiveAccess_IS_REINDEXING__', 1);
		$t_timer = new Timer();

		$pb_display_progress = isset($pa_options['showProgress']) ? (bool)$pa_options['showProgress'] : true;
		$pb_interactive_display = isset($pa_options['interactiveProgressDisplay']) ? (bool)$pa_options['interactiveProgressDisplay'] : false;
		$ps_callback = isset($pa_options['callback']) ? (string)$pa_options['callback'] : false;

		if ($pa_table_names) {
			if (!is_array($pa_table_names)) { $pa_table_names = array($pa_table_names); }

			$va_table_names = array();
			foreach($pa_table_names as $vs_table) {
				if (Datamodel::tableExists($vs_table)) {
					$vn_num = Datamodel::getTableNum($vs_table);
					if($pb_display_progress) {
						print _t("\nTRUNCATING %1\n\n", $vs_table);
					}
					$this->opo_engine->truncateIndex($vn_num);
					$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
					$va_table_names[$vn_num] = array('name' => $vs_table, 'num' => $vn_num, 'displayName' => $t_instance->getProperty('NAME_PLURAL'));
				}
			}
			if (!sizeof($va_table_names)) { return false; }
		} else {
			// full reindex
			ca_search_indexing_queue::flush();
			$this->opo_engine->truncateIndex();
			$va_table_names = $this->getIndexedTables();
		}

		$o_db = $this->opo_db;

		if ($pb_display_progress || $ps_callback) {
			$va_names = array();
			foreach($va_table_names as $vn_table_num => $va_table_info) {
				$va_names[] = $va_table_info['displayName'];
			}
			if ($pb_display_progress) {
				print _t("\nWILL INDEX [%1]\n\n", join(", ", $va_names));
			}
		}

		$vn_tc = 0;

		foreach($va_table_names as $vn_table_num => $va_table_info) {
			$vs_table = $va_table_info['name'];
			$t_instance = Datamodel::getInstanceByTableName($vs_table, true);

			$vn_table_num = $t_instance->tableNum();

			$va_fields_to_index = $this->getFieldsToIndex($vn_table_num);
			if (!is_array($va_fields_to_index) || (sizeof($va_fields_to_index) == 0)) {
				continue;
			}
			$qr_all = $o_db->query("SELECT ".$t_instance->primaryKey()." FROM {$vs_table}".($t_instance->hasField('deleted') ? " WHERE deleted = 0" : ""));

			$vn_num_rows = $qr_all->numRows();
			if ($pb_display_progress) {
				print CLIProgressBar::start($vn_num_rows, _t('Indexing %1', $t_instance->getProperty('NAME_PLURAL')));
			}

			$vn_c = 0;
			$va_ids = $qr_all->getAllFieldValues($t_instance->primaryKey());

			$va_element_ids = null;
			if (method_exists($t_instance, "getApplicableElementCodes")) {
				$va_element_ids = array_keys($t_instance->getApplicableElementCodes(null, false, false));
			}

			$vn_table_num = $t_instance->tableNum();
			$vs_table_pk = $t_instance->primaryKey();
			$va_field_data = array();

			$va_intrinsic_list = $this->getFieldsToIndex($vs_table, $vs_table, array('intrinsicOnly' => true));
			$va_intrinsic_list[$vs_table_pk] = array();
			foreach($va_ids as $vn_i => $vn_id) {
				if (!($vn_i % 500)) {	// Pre-load attribute values for next 500 items to index; improves index performance
					$va_id_slice = array_slice($va_ids, $vn_i, 500);
					if ($va_element_ids) {
						ca_attributes::prefetchAttributes($o_db, $vn_table_num, $va_id_slice, $va_element_ids);
					}
					$qr_field_data = $o_db->query("
						SELECT ".join(", ", array_map(function($v) { return "`{$v}`"; }, array_keys($va_intrinsic_list)))." 
						FROM {$vs_table}
						WHERE {$vs_table_pk} IN (?)	
					", array($va_id_slice));

					$va_field_data = array();
					while($qr_field_data->nextRow()) {
						$va_field_data[(int)$qr_field_data->get($vs_table_pk)] = $qr_field_data->getRow();
					}

					SearchResult::clearCaches();
				}

				$this->indexRow($vn_table_num, $vn_id, $va_field_data[$vn_id], true);
				if ($pb_display_progress && $pb_interactive_display) {
					CLIProgressBar::setMessage(_t("Memory: %1", caGetMemoryUsage()));
					print CLIProgressBar::next();
				}

				if (($ps_callback) && (!($vn_c % 100))) {
					$ps_callback(
						$vn_c,
						$vn_num_rows,
						null,
						null,
						(float)$t_timer->getTime(2),
						memory_get_usage(true),
						$va_table_names,
						$vn_table_num,
						$t_instance->getProperty('NAME_PLURAL'),
						$vn_tc+1
					);
				}
				$vn_c++;
			}
			$qr_all->free();
			// Leaving this statement in to 'repair' any tables that had their keys disabled by a previous failed reindex.
			$o_db->query("ALTER TABLE {$vs_table} ENABLE KEYS");
			
			unset($t_instance);
			if ($pb_display_progress && $pb_interactive_display) {
				print CLIProgressBar::finish();
			}
			$this->opo_engine->optimizeIndex($vn_table_num);

			$vn_tc++;
		}

		if ($pb_display_progress) {
			print _t("\n\n\nDone! [Indexing for %1 took %2]\n", join(", ", $va_names), caFormatInterval((float)$t_timer->getTime(4)));
			print _t("Note that if you're using an external search service like ElasticSearch, the data may only now be sent to the actual service because it was buffered until now. So you still might have to wait a while for the script to finish.\n");
		}
		if ($ps_callback) {
			$ps_callback(
				1,
				1,
				_t('Elapsed time: %1', caFormatInterval((float)$t_timer->getTime(2))),
				_t('Index rebuild complete!'),
				(float)$t_timer->getTime(2),
				memory_get_usage(true),
				$va_table_names,
				null,
				null,
				sizeof($va_table_names)
			);
		}
	}
	# ------------------------------------------------
	/**
	 * Reindex selected rows in a table. Rows are indexed in the same way as would be done in a full reindex.
	 *
	 * @param mixed $pm_table_name_or_num The name or number of the table to reindex
	 * @param array $pa_ids A list of ids in the table to reindex
	 * @param array $pa_options An array of options to pass through to SearchIndexer::indexRow(). Normally omitted.
	 *
	 * @return mixed True on success, null if the table is invalid or no ids are specified.
	 */
	public function reindexRows($pm_table_name_or_num, $pa_ids, $pa_options=null) {
		if(!($t_instance = Datamodel::getInstance($pm_table_name_or_num))) { return null; }
		if (!is_array($pa_ids) && !sizeof($pa_ids)) { return null; }

		$va_element_ids = null;
		if (method_exists($t_instance, "getApplicableElementCodes")) {
			$va_element_ids = array_keys($t_instance->getApplicableElementCodes(null, false, false));
		}

		$vs_table = $t_instance->tableName();
		$vn_table_num = $t_instance->tableNum();
		$vs_table_pk = $t_instance->primaryKey();
		$va_field_data = array();

		$va_intrinsic_list = $this->getFieldsToIndex($vs_table, $vs_table, array('intrinsicOnly' => true));
		$va_intrinsic_list[$vs_table_pk] = array();

		foreach($pa_ids as $vn_i => $vn_id) {
			if ($va_element_ids && (!($vn_i % 200))) {	// Pre-load attribute values for next 200 items to index; improves index performance
				ca_attributes::prefetchAttributes($this->getDb(), $vn_table_num, $va_id_slice = array_slice($pa_ids, $vn_i, 200), $va_element_ids);

				$qr_field_data = $this->getDb()->query("
					SELECT ".join(", ", array_keys($va_intrinsic_list))." 
					FROM {$vs_table}
					WHERE {$vs_table_pk} IN (?)	
				", array($va_id_slice));

				$va_field_data = array();
				while($qr_field_data->nextRow()) {
					$va_field_data[(int)$qr_field_data->get($vs_table_pk)] = $qr_field_data->getRow();
				}
			}

			$this->indexRow($vn_table_num, $vn_id, $va_field_data[$vn_id], false, null, array($vs_table_pk => true), $pa_options);

		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Fetches list of dependencies for a given table
	 */
	public function getDependencies($ps_subject_table) {
		/* handle total cache miss (completely new cache has been generated) */
		if(ExternalCache::contains('ca_table_dependency_array', 'SearchIndexer')) {
			$va_cache_data = ExternalCache::fetch('ca_table_dependency_array', 'SearchIndexer');
		}

		/* cache outdated? (i.e. changes to search_indexing.conf) */
		$va_configfile_stat = stat(__CA_CONF_DIR__.'/search_indexing.conf');
		if($va_configfile_stat['mtime'] != ExternalCache::fetch('ca_table_dependency_array_mtime', 'SearchIndexer')) {
			ExternalCache::save('ca_table_dependency_array_mtime', $va_configfile_stat['mtime'], 'SearchIndexer');
			$va_cache_data = array();
		}

		if(isset($va_cache_data[$ps_subject_table]) && is_array($va_cache_data[$ps_subject_table])) { /* cache hit */
			/* return data from cache */
			//Debug::msg("Got table dependency array for table {$ps_subject_table} from external cache");
			return $va_cache_data[$ps_subject_table];
		} else { /* cache miss */
			//Debug::msg("Cache miss for {$ps_subject_table}");
			/* build dependency graph, store it in cache and return it */
			$va_deps = $this->_getDependencies($ps_subject_table);
			$va_cache_data[$ps_subject_table] = $va_deps;
			ExternalCache::save('ca_table_dependency_array', $va_cache_data, 'SearchIndexer');
			return $va_deps;
		}
	}
	# ------------------------------------------------
	/**
	 * Generate hierarchical values for using in indexing of hierarchical values with INDEX_ANCESTORS enabled
	 */
	private function _genHierarchicalPath($pn_subject_row_id, $ps_field, $t_subject, $pa_options=null) {
		$vs_key = caMakeCacheKeyFromOptions($pa_options, "{$pn_subject_row_id}/{$ps_field}");
		if(MemoryCache::contains($vs_key, 'SearchIndexerHierPaths')) {
			return MemoryCache::fetch($vs_key, 'SearchIndexerHierPaths');
		}

		$pn_start = caGetOption('INDEX_ANCESTORS_START_AT_LEVEL', $pa_options, 0);
		$pn_max_levels = caGetOption('INDEX_ANCESTORS_MAX_NUMBER_OF_LEVELS', $pa_options, null);
		$ps_delimiter = caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $pa_options, '; ');
		
		$hier_type = $t_subject->getProperty('HIERARCHY_TYPE');

		// Automagically generate hierarchical paths for preferred labels passed as label table + label field
		$is_label = false;
		if (is_subclass_of($t_subject, "BaseLabel")) {
			if (!($t_subject->getPrimaryKey() == $pn_subject_row_id)) { $t_subject->load($pn_subject_row_id); }
			$pn_subject_row_id = $t_subject->get($t_subject->getSubjectKey());
			$t_subject = $t_subject->getSubjectTableInstance(array('dontLoadInstance' => true));
			$ps_field = "preferred_labels.{$ps_field}";
			$is_label = true;
		}
		$va_ids = $t_subject->getHierarchyAncestors($pn_subject_row_id, array('idsOnly' => true, 'includeSelf' => true, 'omitRoot' => ($hier_type === __CA_HIER_TYPE_MULTI_MONO__)));
		$vs_subject_tablename = $t_subject->tableName();

		if (is_array($va_ids) && sizeof($va_ids) > 0) {
			$qr_hier_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_ids, array('db' => $this->getDb()));

			$va_hier_values = array();
			while($qr_hier_res->nextHit()) {
				if ($vs_v = $qr_hier_res->get($vs_subject_tablename.".".$ps_field)) {
					// return as array in case somehow there are dupe preferred labels (ie. the database is corrupt)
					$label_id = is_array($label_ids = $qr_hier_res->get($vs_subject_tablename.'.preferred_labels.label_id', ['returnAsArray' => true])) ? (int)array_shift($label_ids) : null;
					$va_hier_values[($is_label && ($label_id > 0)) ? $label_id : $qr_hier_res->getPrimaryKey()] = $vs_v;
				}
			}

			$va_hier_values = array_reverse($va_hier_values, true);

			if (($pn_start > 0) && (sizeof($va_hier_values) > $pn_start + 1)) {
				$va_hier_values = array_slice($va_hier_values, $pn_start, null, true);
			}
			if ($pn_max_levels > 0) {
				$va_hier_values = array_slice($va_hier_values, 0, $pn_max_levels, true);
			}

			if(MemoryCache::itemCountForNamespace('SearchIndexerHierPaths') > 100) {
				MemoryCache::flush('SearchIndexerHierPaths');
			}
			$va_return = array('values' => $va_hier_values, 'path' => join($ps_delimiter, $va_hier_values));
			MemoryCache::save($vs_key, $va_return, 'SearchIndexerHierPaths');

			return $va_return;
		}

		MemoryCache::save($vs_key, null, 'SearchIndexerHierPaths');
		return null;
	}
	# ------------------------------------------------
	/**
	 * 
	 */
	private function _genIndexInheritance($pt_subject, $pt_rel, $ps_field_num, $pn_subject_row_id, $pn_content_row_id, $pa_values_to_index, $pa_data, $pa_options=null) {
		$is_generic = caGetOption('isGeneric', $pa_options, false);
		$subject_table_num = $pt_subject->tableNum();
		
		if (caGetOption('CHILDREN_INHERIT', $pa_data, false) || (array_search('CHILDREN_INHERIT', $pa_data, true) !== false)) {
			$o_indexer = new SearchIndexer($this->opo_db);
		
			$vn_rel_table_num = $pt_rel ? $pt_rel->tableNum() : $subject_table_num;
			if (is_array($va_ids = $pt_subject->getHierarchy($pn_subject_row_id, ['idsOnly' => true]))) {
				foreach($va_ids as $vn_id) {
					if ($vn_id == $pn_subject_row_id) { continue; }
					
					$o_indexer->opo_engine->startRowIndexing($subject_table_num, $vn_id);
					$o_indexer->opo_engine->indexField($is_generic ? $subject_table_num : $vn_rel_table_num, $ps_field_num, $pn_content_row_id, $pa_values_to_index, array_merge($pa_data));
					$o_indexer->opo_engine->commitRowIndexing();
				}
			}
		}
		if (caGetOption('ANCESTORS_INHERIT', $pa_data, false) || (array_search('ANCESTORS_INHERIT', $pa_data, true) !== false)) {
			if (!$o_indexer) { $o_indexer = new SearchIndexer($this->opo_db); }
			if(!$vn_rel_table_num) { $vn_rel_table_num = $pt_rel ? $pt_rel->tableNum() : $subject_table_num; }
		
			if (is_array($va_ids = $pt_subject->getHierarchyAncestors($pn_subject_row_id, ['idsOnly' => true]))) {
				foreach($va_ids as $vn_id) {
					if ($vn_id == $pn_subject_row_id) { continue; }

					$o_indexer->opo_engine->startRowIndexing($subject_table_num, $vn_id);
					$o_indexer->opo_engine->indexField($is_generic ? $subject_table_num : $vn_rel_table_num, $ps_field_num, $is_generic ? $pn_subject_row_id : $pn_content_row_id, $pa_values_to_index, array_merge($pa_data));
					$o_indexer->opo_engine->commitRowIndexing();
				}
			}
		}
	}
	# ------------------------------------------------
	private function queueIndexRow($pa_row_values) {
		foreach($pa_row_values as $vs_fld => &$vm_val) {
			if(!$this->opo_search_indexing_queue->hasField($vs_fld)) {
				return false;
			}

			if(is_null($vm_val)) {
				$vm_val = array();
			}

			if(is_array($vm_val)) {
				$vm_val = caSerializeForDatabase($vm_val);
			}
		}

		self::$s_search_indexing_queue_inserts[] = array(
			'table_num' => $pa_row_values['table_num'],
			'row_id' => $pa_row_values['row_id'],
			'field_data' => $pa_row_values['field_data'],
			'reindex' => $pa_row_values['reindex'] ? 1 : 0,
			'changed_fields' => $pa_row_values['changed_fields'],
			'options' => $pa_row_values['options'],
		);
		SearchIndexer::$queued_entry_count++;

		return true;
	}
	# ------------------------------------------------
	private function queueUnIndexRow($pa_row_values) {
		foreach($pa_row_values as $vs_fld => &$vm_val) {
			if(!$this->opo_search_indexing_queue->hasField($vs_fld)) {
				return false;
			}

			if(is_null($vm_val)) {
				$vm_val = array();
			}

			if(is_array($vm_val)) {
				$vm_val = caSerializeForDatabase($vm_val);
			}
		}

		self::$s_search_unindexing_queue_inserts[] = array(
			'table_num' => $pa_row_values['table_num'],
			'row_id' => $pa_row_values['row_id'],
			'is_unindex' => 1,
			'dependencies' => $pa_row_values['dependencies'],
		);
		SearchIndexer::$queued_entry_count++;

		return true;
	}
	# ------------------------------------------------
	/**
	 * Indexes single row in a table; this is the public call when one needs to index content.
	 * indexRow() will analyze the dependencies of the row being indexed and automatically
	 * apply the indexing of the row to all dependent rows in other tables.  (Note that while I call this
	 * a "public" call in fact you shouldn't need to call this directly. BaseModel.php does this for you
	 * during insert() and update().)
	 *
	 * For example, if you are indexing a row in table 'entities', then indexRow()
	 * will automatically apply the indexing not just to the entities record, but also
	 * to all objects, place_names, occurrences, lots, etc. that reference the entity.
	 * The dependencies are configured in the search_indexing.conf configuration file.
	 *
	 * "subject" tablenum/row_id refer to the row **to which the indexing is being applied**. This may be the row being indexed
	 * or it may be a dependent row. The "content" tablenum/fieldnum/row_id parameters define the specific row and field being indexed.
	 * This is always the actual row being indexed. $pm_content is the content to be indexed and $pa_options is an optional associative
	 * array of indexing options passed through from the search_indexing.conf (no options are defined yet - but will be soon)
	 *
	 * @param int $pn_subject_table_num subject table number
	 * @param int $pn_subject_row_id subject record, identified by primary key
	 * @param array $pa_field_data array of field name => value mappings containing the data to index
	 * @param bool $pb_reindex_mode are we in full reindex mode?
	 * @param null|array $pa_exclusion_list list of records to exclude from indexing
	 * 		(to prevent endless recursive reindexing). Should always be null when called externally.
	 * @param null|array $pa_changed_fields list of fields that have changed (and must be indexed)
	 * @param null|array $pa_options
	 * 		queueIndexing -
	 * 		isNewRow -
	 * @throws Exception
	 * @return bool
	 */
	public function indexRow($pn_subject_table_num, $pn_subject_row_id, $pa_field_data, $pb_reindex_mode=false, $pa_exclusion_list=null, $pa_changed_fields=null, $pa_options=null) {
		$vb_initial_reindex_mode = $pb_reindex_mode;
		$for_current_value_reindex = caGetOption('forCurrentValueReindex', $pa_options, false);
		if (!$pb_reindex_mode && !$for_current_value_reindex && is_array($pa_changed_fields) && !sizeof($pa_changed_fields)) { return; }	// don't bother indexing if there are no changed fields

		$vb_started_indexing = false;

		$vs_subject_tablename = Datamodel::getTableName($pn_subject_table_num);
		$t_subject = Datamodel::getInstanceByTableName($vs_subject_tablename, true);
		$t_subject->setDb($this->getDb());	// force the subject instance to use the same db connection as the indexer, in case we're operating in a transaction

		// Prevent endless recursive reindexing
		if (is_array($pa_exclusion_list[$pn_subject_table_num] ?? null) && (isset($pa_exclusion_list[$pn_subject_table_num][$pn_subject_row_id]))) { return; }

		if(caGetOption('queueIndexing', $pa_options, false) && !$t_subject->getAppConfig()->get('disable_out_of_process_search_indexing') && !defined('__CA_DONT_QUEUE_SEARCH_INDEXING__')) {
			$this->queueIndexRow(array(
				'table_num' => $pn_subject_table_num,
				'row_id' => $pn_subject_row_id,
				'field_data' => $pa_field_data,
				'reindex' => $pb_reindex_mode ? 1 : 0,
				'changed_fields' => $pa_changed_fields,
				'options' => $pa_options
			));
			return;
		}
		
		$pb_is_new_row = (int)caGetOption('isNewRow', $pa_options, false);
		$vb_reindex_children = false;

		$vs_subject_pk = $t_subject->primaryKey();
		$vs_subject_type_code = method_exists($t_subject, 'getTypeCode') ? $t_subject->getTypeCode() : null;
		
		if (!is_array($pa_changed_fields)) { $pa_changed_fields = array(); }

		foreach($pa_changed_fields as $vs_k => $vb_bool) {
			if (!isset($pa_field_data[$vs_k])) { $pa_field_data[$vs_k] = null; }
		}

		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?

		if (!$pa_exclusion_list) { $pa_exclusion_list = array(); }
		$pa_exclusion_list[$pn_subject_table_num][$pn_subject_row_id] = true;
if (!$for_current_value_reindex) {
		//
		// index fields in subject table itself
		//
		$va_fields_to_index = $this->getFieldsToIndex($pn_subject_table_num);

		if(is_array($va_fields_to_index)) {
			// always index type id if applicable
			if(method_exists($t_subject, 'getTypeFieldName') && ($vs_type_field = $t_subject->getTypeFieldName()) && !isset($va_fields_to_index[$vs_type_field])) {
				$va_fields_to_index[$vs_type_field] = array('STORE', 'DONT_TOKENIZE');
			}
		}

		// 
		// If location in hierarchy has changed we need to reindex this record and all of its children
		//
		if ($t_subject->isHierarchical() && isset($pa_changed_fields['parent_id']) && $pa_changed_fields['parent_id'] && method_exists($t_subject, "makeSearchResult")) {
			$pb_reindex_mode = true;
			$vb_reindex_children = true;
		}
		$vb_started_indexing = false;
		if (is_array($va_fields_to_index)) {
			$this->opo_engine->startRowIndexing($pn_subject_table_num, $pn_subject_row_id);
			$vb_started_indexing = true;

			foreach($va_fields_to_index as $vs_field => $va_data) {
				if(is_array($va_data['BOOST'] ?? null)) {
					if (isset($va_data['BOOST'][$vs_subject_type_code])) {
						$va_data['BOOST'] = $va_data['BOOST'][$vs_subject_type_code];
					} elseif(isset($va_data['BOOST']['*'])) {
						$va_data['BOOST'] = $va_data['BOOST']['*'];
					} else {
						$va_data['BOOST'] = 0;
					}
				}
				
				if (substr($vs_field, 0, 14) === '_ca_attribute_') {
					//
					// Is attribute
					//
					if (!preg_match('!^_ca_attribute_(.*)$!', $vs_field, $va_matches)) { continue; }

					if ($vb_can_do_incremental_indexing && (!$pb_is_new_row) && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field]) || !$pa_changed_fields[$vs_field])) {
						continue;	// skip unchanged attribute value
					}

					if(($va_data['DONT_INDEX'] ?? null) && is_array($va_data['DONT_INDEX'])){
						$vb_cont = false;
						foreach($va_data["DONT_INDEX"] as $vs_exclude_type){
							if(ca_metadata_elements::getElementID($vs_exclude_type) == intval($va_matches[1])){
								$vb_cont = true;
								break;
							}
						}
						if($vb_cont) continue; // skip excluded attribute type
					}

					$va_data['datatype'] = (int)ca_metadata_elements::getElementDatatype($va_matches[1]);
					
					if ($va_data['datatype'] === 0) {
						// get child ids for container - we need to pass indexing settings for each
						if(is_array($child_ids = ca_metadata_elements::getElementsForSet($va_matches[1], ['idsOnly' => true]))) {
							foreach($child_ids as $child_id) {
								if(!is_array($va_fields_to_index['_ca_attribute_'.$child_id] ?? null)) { continue; }
								$va_data['_ca_attribute_'.$child_id] = $va_fields_to_index['_ca_attribute_'.$child_id];
							}
						}
					}
					$this->_indexAttribute($t_subject, $pn_subject_row_id, $va_matches[1], $va_data, ['reindex' => $pb_reindex_mode]);

				} else {
					//
					// Plain old field
					//
					if ($vb_can_do_incremental_indexing && (!$pb_is_new_row) && (!$pb_reindex_mode) && (!isset($pa_changed_fields[$vs_field])) && ($vs_field != $vs_subject_pk) ) {	// skip unchanged
						continue;
					}

					if (is_null($vn_fld_num = $t_subject->fieldNum($vs_field))) { continue; }

					//
					// Hierarchical indexing in primary table
					//
					$fld_init = false;
					if (((isset($va_data['INDEX_ANCESTORS']) && $va_data['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_data, true))) {
						if ($t_subject && $t_subject->isHierarchical()) {
							$vn_fld_num = $t_subject->fieldNum($vs_field);
							if ($va_hier_values = $this->_genHierarchicalPath($pn_subject_row_id, $vs_field, $t_subject, $va_data)) {
								$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_hier_values['values'], array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
								$fld_init = true;
								
								$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_hier_values['values'], array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
								
								if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_data, false) !== false) {
									$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('DONT_TOKENIZE' => 1, 'dontRemoveExistingIndexing' => $fld_init)));
									$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('TOKENIZE' => 1, 'dontRemoveExistingIndexing' => $fld_init)));
									$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('DONT_TOKENIZE' => 1, 'dontRemoveExistingIndexing' => $fld_init)));
									$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('TOKENIZE' => 1, 'dontRemoveExistingIndexing' => $fld_init)));
								}
							}

							$va_children_ids = $t_subject->getHierarchyAsList($pn_subject_row_id, array('idsOnly' => true));

							if (!$pb_reindex_mode && is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
								// trigger reindexing of children
								$o_indexer = new SearchIndexer($this->opo_db);
								$qr_children_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_children_ids, array('db' => $this->getDb()));
								while($qr_children_res->nextHit()) {
									$o_indexer->indexRow($pn_subject_table_num, $qr_children_res->get($vs_subject_pk), array('parent_id' => $qr_children_res->get('parent_id'), $vs_field => $qr_children_res->get($vs_field)), false, $pa_exclusion_list, array($vs_field => true));
								}
							}
						}
					}

					// specialized identifier (idno) processing; uses IDNumbering plugin to generate searchable permutations of identifier
					if (((isset($va_data['INDEX_AS_IDNO']) && $va_data['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_data, true)) && method_exists($t_subject, "getIDNoPlugInInstance") && ($o_idno = $t_subject->getIDNoPlugInInstance())) {
						if ((is_array($va_data['IDNO_DELIMITERS'] ?? null) && count($va_data['IDNO_DELIMITERS'])) || strlen($va_data['IDNO_DELIMITERS'] ?? null)) {
							if (!is_array($va_data['IDNO_DELIMITERS'])) { $va_data['IDNO_DELIMITERS'] = [$va_data['IDNO_DELIMITERS']]; }
							$va_values = array_map(function($v) { return trim($v); }, preg_split('!('.join('|', $va_data['IDNO_DELIMITERS']).')!', $pa_field_data[$vs_field]));
						} else {
							$va_values = $o_idno->getIndexValues($pa_field_data[$vs_field], $va_data);
						}
						$vn_fld_num = $t_subject->fieldNum($vs_field);
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_values, array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
						$fld_init = true;
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_values, array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
					}
					// specialized mimetype processing
					if (((isset($va_data['INDEX_AS_MIMETYPE']) && $va_data['INDEX_AS_MIMETYPE']) || in_array('INDEX_AS_MIMETYPE', $va_data, true))) {
						$va_values = [];
						if ($vs_typename = Media::getTypenameForMimetype($pa_field_data[$vs_field])) {
							$va_values[] = $vs_typename;
						}
						$vn_fld_num = $t_subject->fieldNum($vs_field);
						
						// Index mimetype as-is
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$pa_field_data[$vs_field]], array_merge($va_data, ['DONT_TOKENIZE' => true, 'dontRemoveExistingIndexing' => $fld_init]));
						$fld_init = true;
						
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_values, array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_values, array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
						continue;
					}
					

					$va_field_list = $t_subject->getFieldsArray();
					if(in_array($va_field_list[$vs_field]['FIELD_TYPE'],array(FT_DATERANGE,FT_HISTORIC_DATERANGE))) {
						// if the field is a daterange type get content from start and end fields
						$start_field = $va_field_list[$vs_field]['START'];
						$end_field = $va_field_list[$vs_field]['END'];
						if(!$pa_field_data[$start_field] || !$pa_field_data[$start_field]) { continue; }
						$pn_content = $pa_field_data[$start_field] . " - " .$pa_field_data[$end_field];
					} else {
						$va_content = array();

						if (
							($is_list_code = (isset($va_field_list[$vs_field]['LIST_CODE']) && $va_field_list[$vs_field]['LIST_CODE']))
							||
							($is_list = (isset($va_field_list[$vs_field]['LIST']) && $va_field_list[$vs_field]['LIST']))
						) {
							if($is_list_code) {
								// Is reference to list item
								$t_item = new ca_list_items((int)$pa_field_data[$vs_field]);
							} else {
								// Is list item value
								$t_item = ca_list_items::findAsInstance(['list_id' => caGetListID($va_field_list[$vs_field]['LIST']), 'item_value' => $pa_field_data[$vs_field]]);
							}
							if(!$t_item) { continue; }		// list item doesn't exist (can happen for access and status values)
							
							// Index idnos, values and preferred label values
							$va_labels = $t_item->getPreferredDisplayLabelsForIDs([(int)$t_item->getPrimaryKey()], ['returnAllLocales' => true]);

							foreach($va_labels as $vn_label_row_id => $va_labels_per_row) {
								foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
									foreach($va_label_list as $vs_label) {
										$va_content[$vs_label] = true;
									}
								}
							}
							$va_content[$t_item->get('idno')] = true;
							$va_content[$t_item->get('item_value')] = true;
						}  else {
							// is this field related to something?
							if (is_array($va_rels = Datamodel::getManyToOneRelations($vs_subject_tablename)) && ($va_rels[$vs_field] ?? null)) {
								if (isset($va_rels[$vs_field])) {
									if (($pa_changed_fields[$vs_field] ?? null) && ($va_rels[$vs_field]['one_table'] ?? null)) {
										$pb_reindex_mode = true;	// trigger full reindex of record so it reflects text of related item (if so indexed)
										$this->opo_engine->removeRowIndexing($pn_subject_table_num, $pn_subject_row_id, Datamodel::getTableNum($va_rels[$vs_field]['one_table']), ["I{$vn_fld_num}"]);
									}
								}
							}
						}
						if($pa_field_data[$vs_field] ?? null)  { $va_content[$pa_field_data[$vs_field]] = true; }

						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, array_keys($va_content), array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
						$fld_init = true;
						
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, array_keys($va_content), array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
						continue;
					}

					$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$pn_content], array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
					$fld_init = true;
					
					$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$pn_content], array_merge($va_data, ['dontRemoveExistingIndexing' => $fld_init]));
				}
			}
		}
}
		// -------------------------------------
		//
		// index related fields
		//
		// Here's where we generate indexing on the subject from content in related rows (data stored externally to the subject row)
		// If the underlying engine doesn't support incremental indexing (if it can't change existing indexing for a row in-place, in other words)
		// then we need to do this every time we update the indexing for a row; if the engine *does* support incremental indexing then
		// we can just update the existing indexing with content from the changed fields.
		//
		// We also do this indexing if we're in "reindexing" mode. When reindexing is indicated it means that we need to act as if
		// we're indexing this row for the first time, and all indexing should be performed.
		if (!$vb_can_do_incremental_indexing || $pb_reindex_mode || $for_current_value_reindex) {
			$public_access = array_map('intval', $this->opo_app_config->getList('public_access_settings') ?? []);
			
			if (is_array($va_related_tables = $this->getRelatedIndexingTables($pn_subject_table_num))) {
				if (!$vb_started_indexing) {
					$this->opo_engine->startRowIndexing($pn_subject_table_num, $pn_subject_row_id);
					$vb_started_indexing = true;
				}

				// Needs self-indexing?
				$va_self_info = $this->getTableIndexingInfo($vs_subject_tablename, $vs_subject_tablename);

				if (is_array($va_self_info['related']['fields'] ?? null) && sizeof($va_self_info['related']['fields']) && !in_array($vs_subject_tablename, $va_related_tables)) {
					$va_related_tables[] = $vs_subject_tablename;
				}
				
                $va_restrict_self_indexing_to_types = null;
                if (is_array($va_self_info['related']['types'] ?? null) && sizeof($va_self_info['related']['types'])) {
                    $va_restrict_self_indexing_to_types = caMakeTypeIDList($vs_subject_tablename, $va_self_info['related']['types']);
                }

				foreach($va_related_tables as $vs_related_table) {
				    $va_tmp = explode(".", $vs_related_table);
				    $vs_related_table = array_shift($va_tmp);
				    $vb_force_related = (strtolower($va_tmp[0] ?? null) === 'related');
				                    
                    $va_restrict_indexing_to_types = null;
                    $va_info = $this->getTableIndexingInfo($vs_subject_tablename, $vb_force_related ? "{$vs_related_table}.related" : $vs_related_table);
                    
                    if (is_array($va_info['types'] ?? null) && sizeof($va_info['types'])) {
                        $va_restrict_indexing_to_types = caMakeTypeIDList($vs_related_table, $va_info['types']);
                    }
					$vn_private = 0;
					$va_queries = [];

					$vn_related_table_num = Datamodel::getTableNum($vs_related_table);
					$vs_related_pk = Datamodel::primaryKey($vn_related_table_num);

					$t_rel = Datamodel::getInstanceByTableNum($vn_related_table_num, true);
					$t_rel->setDb($this->getDb());
					
                    
                    // Get current values	
                    $current_value_ids = [];	
                    $current_history_type = (is_a($t_rel, "BaseLabel")) ? $t_rel->getSubjectTableName() : $vs_related_table;    // for labels use the subject table as type
                    if(is_array($policies = ($va_info['current_values'] ?? null))) {
                        foreach($policies as $p => $pinfo) {
                            $history = $t_subject->getHistory(['policy' => $p,  'limit' => 1, 'row_id' => $pn_subject_row_id]); //'currentOnly' => true,
                            
                            if (is_array($history) && (sizeof($history) > 0)) { 
                                $current_value = array_shift(array_shift($history));
                                if ($current_history_type !== $current_value['type']) { continue; }
                                $current_value_ids[$current_value['current_row_id']][] = $p;
                            }
                        }
                    }

					$va_params = null;

					$va_query_info = $this->_getQueriesForRelatedRows($t_subject, $pn_subject_row_id, $t_rel, $pb_reindex_mode, ['forceRelated' => $vb_force_related, 'restrictToTypes' => $va_restrict_indexing_to_types, 'forCurrentValueReindex' => $for_current_value_reindex]);
					$va_queries 			= $va_query_info['queries'];
					$va_fields_to_index 	= $va_query_info['fields_to_index'];
					$va_cv_fields_to_index  = $va_query_info['current_value_fields_to_index'];
					if(!is_array($va_query_info)) {
						throw new Exception(_t("Cannot get queries for: %1; search_indexing.conf is misconfigured?", $vs_related_table));
					}
					if ($vb_index_count = (isset($va_fields_to_index['_count']) && is_array($va_fields_to_index['_count']))) {
						$va_counts = $this->_getInitedCountList($t_rel); 
					}
					
					foreach($va_queries as $vn_i => $va_query) {
						$va_linking_table_config = is_array($va_query_info['linking_table_config_per_query'][$vn_i] ?? null) ? $va_query_info['linking_table_config_per_query'][$vn_i] : [];
						
						// Check for configured "private" relationships
						$va_private_rel_types = null;
						foreach($va_linking_table_config as $vs_linking_table => $va_linking_config) {
							if (is_array($va_linking_config) && sizeof($va_linking_config) && isset($va_linking_config['PRIVATE']) && Datamodel::isRelationship($vs_linking_table)) {
								$va_private_rel_types = caMakeRelationshipTypeIDList($vs_linking_table, $va_linking_config['PRIVATE'], []);
								break;
							}
						}
						
						$vs_sql = $va_query['sql'];
						$va_params = $va_query['params'];
						
						$qr_res = $this->opo_db->query($vs_sql, $va_params);

						if ($this->opo_db->numErrors()) {
							// Shouldn't ever happen
							throw new Exception(_t("SQL error while getting content for index of related fields: %1; SQL was %2", $this->opo_db->getErrors(), $vs_sql));
						}

						if (method_exists($t_rel, "getApplicableElementCodes")) {
							if (is_array($va_element_ids = array_keys($t_rel->getApplicableElementCodes(null, false, false))) && sizeof($va_element_ids)) {
								$va_rel_row_ids = $qr_res->getAllFieldValues($vs_related_pk, ['limit' => ca_attributes::attributeCacheSize()]); // only pull as many rows as the cache can hold, anything more than that you're just wasting time
								
								if(sizeof($va_rel_row_ids) > 0) {
									ca_attributes::prefetchAttributes($this->opo_db, $vn_related_table_num, $va_rel_row_ids , $va_element_ids);
								}
							}
						}

						if(!$qr_res->seek(0)) {
							$qr_res = $this->opo_db->query($vs_sql, $va_params);
						}
						
						$vn_count = 0;
						while($qr_res->nextRow()) {
							$vn_count++;
							
							$va_field_data = $qr_res->getRow();
							$vn_rel_subject_row_id = $vn_row_id = $qr_res->get($vs_related_pk);
							if (is_a($t_rel, "BaseLabel")) { $vn_rel_subject_row_id = $qr_res->get($t_rel->getSubjectTableInstance(['dontLoadInstance' => true])->primaryKey()); }
							
							$vn_rel_type_id = (int)$qr_res->get('rel_type_id');
							$vn_row_type_id = (int)$qr_res->get('type_id');
							
                            if(!$vb_force_related && ($vs_related_table == $vs_subject_tablename) && is_array($va_restrict_self_indexing_to_types) && !in_array($vn_row_type_id, $va_restrict_self_indexing_to_types)) {
                                continue;
                            }
                            if(is_array($va_restrict_indexing_to_types) && !in_array($vn_row_type_id, $va_restrict_indexing_to_types)) {
                                continue;
                            }
							
							$vn_private = ((!is_array($va_private_rel_types) || !sizeof($va_private_rel_types) || !in_array($vn_rel_type_id, $va_private_rel_types))) ? 0 : 1;
							
							if(
								!$vn_private && $t_rel->hasField('access') && 
								!(is_a($t_rel, "BaseLabel") && $this->opo_app_config->get('dont_check_label_access')) && 
								(!in_array($t_rel->tableName(), ['ca_sets', 'ca_set_items'], true)) && 
								(!in_array((int)$qr_res->get('access'), $public_access ?? [], true))
							) {
								$vn_private = 1;
							}
				
							$fields_to_index_list = ['fields' => $va_fields_to_index, 'current_values' => $va_cv_fields_to_index];
							if ($for_current_value_reindex) {  $fields_to_index_list = ['current_values' => $va_cv_fields_to_index]; }
					
							foreach($fields_to_index_list as $m => $field_lists) {
								if($m == 'fields') { $field_lists = [$field_lists]; }
								foreach($field_lists as $p => $field_list) {					        
									if ($m == 'current_values') {
										if (!isset($current_value_ids[$vn_rel_subject_row_id]) || !in_array($p, $current_value_ids[$vn_rel_subject_row_id])) { continue; }
									}
									foreach($field_list as $vs_rel_field => $va_rel_field_info) {
										if(is_array($va_rel_field_info['BOOST'] ?? null)) {
											if (isset($va_rel_field_info['BOOST'][$vs_subject_type_code])) {
												$va_rel_field_info['BOOST'] = $va_rel_field_info['BOOST'][$vs_subject_type_code];
											} elseif(isset($va_rel_field_info['BOOST']['*'])) {
												$va_rel_field_info['BOOST'] = $va_rel_field_info['BOOST']['*'];
											} else {
												$va_rel_field_info['BOOST'] = 0;
											}
										}
		//
		// BEGIN: Index attributes in related tables
		//						
										$vb_is_attr = false;
										if (substr($vs_rel_field, 0, 14) === '_ca_attribute_') {
											if (!preg_match('!^_ca_attribute_(.*)$!', $vs_rel_field, $va_matches)) { continue; }

											if($va_rel_field_info['DONT_INDEX'] && is_array($va_rel_field_info['DONT_INDEX'])){
												$vb_cont = false;
												foreach($va_rel_field_info["DONT_INDEX"] as $vs_exclude_type){
													if(ca_metadata_elements::getElementID($vs_exclude_type) == intval($va_matches[1])){
														$vb_cont = true;
														break;
													}
												}
												if($vb_cont) continue; // skip excluded attribute type
											}

											$vb_is_attr = true;

											$va_rel_field_info['datatype'] = (int)ca_metadata_elements::getElementDatatype($va_matches[1]);
						
											$va_data = array_merge($va_rel_field_info, ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]);
											if ($va_rel_field_info['datatype'] === 0) {
												// get child ids for container - we need to pass indexing settings for each
												if(is_array($child_ids = ca_metadata_elements::getElementsForSet($va_matches[1], ['idsOnly' => true]))) {
													foreach($child_ids as $child_id) {
														if(!is_array($field_list['_ca_attribute_'.$child_id])) { continue; }
														$va_data['_ca_attribute_'.$child_id] = $field_list['_ca_attribute_'.$child_id];
													}
												}
											}
											$this->_indexAttribute($t_rel, $vn_row_id, $va_matches[1], $va_data, ['reindex' => $pb_reindex_mode, 't_inheritance_subject' => $t_subject, 'inheritance_subject_id' => $pn_subject_row_id, 'currentValues' => ($m == 'current_values'), 'policy' => $p]);
										}

										$vs_fld_data = trim($va_field_data[$vs_rel_field]);

										//
										// Hierarchical indexing in related tables
										//
										if (((isset($va_rel_field_info['INDEX_ANCESTORS']) && $va_rel_field_info['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_rel_field_info, true))) {
											// is this current field a label?
											$t_hier_rel = $t_rel;
											$vn_fn = $t_rel->fieldNum($vs_rel_field);
											$field_nums = ($m == 'current_values') ? ["CV{$p}_I{$vn_fn}" => false, "CV{$p}" => true]: ["I{$vn_fn}" => false];
											$vn_id = $vn_row_id;


											if ($t_hier_rel && ($t_hier_rel->isHierarchical() || is_subclass_of($t_hier_rel, "BaseLabel"))) {
												// get hierarchy
												if ($va_hier_values = $this->_genHierarchicalPath($vn_id, $vs_rel_field, $t_hier_rel, $va_rel_field_info)) {
													foreach($field_nums as $field_num => $is_generic) {
														
														// Index each hierarchical values from ancestors against the record
														foreach($va_hier_values['values'] as $v_id => $v) {
															$this->opo_engine->indexField($is_generic ? $pn_subject_table_num : $vn_related_table_num, $field_num, $is_generic ? $pn_subject_row_id : $v_id, $v, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
														}
													
														if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_rel_field_info, false) !== false) {
															$label_id = array_shift(array_keys($va_hier_values['values']));
															$this->_genIndexInheritance($t_subject, $t_hier_rel, $field_num, $pn_subject_row_id, $is_generic ? $pn_subject_row_id : $label_id, [$va_hier_values['path']], array_merge($va_rel_field_info, array('TOKENIZE' => 1, 'relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'isGeneric' => $is_generic)));
														}
													}
												}
											}
										}

										switch($vs_rel_field){
											case '_count':
												if ($vb_index_count) {
													$va_counts['_total']++;
										
													if ($vn_rel_type_id || $vn_row_type_id || !$t_rel->hasField('type_id')) {
														$va_counts[$t_rel->isRelationship() ? $vn_rel_type_id : $vn_row_type_id]++;
													}
												}
												break;
											default:
												if (strlen($vn_fn = Datamodel::getFieldNum($vs_related_table, $vs_rel_field)) > 0) {
													$field_nums = ($m == 'current_values') ? ["CV{$p}_I{$vn_fn}" => false, "CV{$p}" => true] : ["I{$vn_fn}" => false];
												
													foreach($field_nums as $field_num => $is_generic) {
														if (((isset($va_rel_field_info['INDEX_AS_IDNO']) && $va_rel_field_info['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_rel_field_info, true)) && method_exists($t_rel, "getIDNoPlugInInstance") && ($o_idno = $t_rel->getIDNoPlugInInstance())) {
															// specialized identifier (idno) processing; uses IDNumbering plugin to generate searchable permutations of identifier
														
															if (strlen($va_rel_field_info['IDNO_DELIMITERS']) || (is_array($va_rel_field_info['IDNO_DELIMITERS']) && count($va_rel_field_info['IDNO_DELIMITERS']))) {
																if (!is_array($va_rel_field_info['IDNO_DELIMITERS'])) { $va_rel_field_info['IDNO_DELIMITERS'] = [$va_rel_field_info['IDNO_DELIMITERS']]; }
																$va_values = array_map(function($v) { return trim($v); }, preg_split('!('.join('|', $va_rel_field_info['IDNO_DELIMITERS']).')!', $vs_fld_data));
															} else {
																$va_values = $o_idno->getIndexValues($vs_fld_data, $va_rel_field_info);
															}
															$this->opo_engine->indexField($is_generic ? $pn_subject_table_num : $vn_related_table_num, $field_num, $vn_id = $is_generic ? $pn_subject_row_id : $qr_res->get($vs_related_pk), $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
															$this->_genIndexInheritance($t_subject, $t_rel, $field_num, $pn_subject_row_id, $vn_id, $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'isGeneric' => $is_generic)));
														}
														if (((isset($va_rel_field_info['INDEX_AS_MIMETYPE']) && $va_rel_field_info['INDEX_AS_MIMETYPE']) || in_array('INDEX_AS_MIMETYPE', $va_rel_field_info, true))) {
															// specialized mimetype processing
															$va_values = [];
															if ($vs_typename = Media::getTypenameForMimetype($vs_fld_data)) {
																$va_values[] = $vs_typename;
															}
															// Index mimetype as-is
															$this->opo_engine->indexField($is_generic ? $pn_subject_table_num : $vn_related_table_num, $field_num, $vn_id = $is_generic ? $pn_subject_row_id : $qr_res->get($vs_related_pk), [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'DONT_TOKENIZE' => true)));
											
															// Index typename
															$this->opo_engine->indexField($is_generic ? $pn_subject_table_num : $vn_related_table_num, $field_num, $vn_id = $is_generic ? $pn_subject_row_id : $qr_res->get($vs_related_pk), $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
															$this->_genIndexInheritance($t_subject, $t_rel, $field_num, $pn_subject_row_id, $vn_id, $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'isGeneric' => $is_generic)));
														} else {
															// regular intrinsic
															$this->opo_engine->indexField(($is_generic ? $pn_subject_table_num : $vn_related_table_num), $field_num, $vn_rid = $is_generic ? $pn_subject_row_id : $qr_res->get($vs_related_pk), [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
															$this->_genIndexInheritance($t_subject, $t_rel, $field_num, $pn_subject_row_id, $vn_rid, [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'isGeneric' => $is_generic)));
														}
													}
												}
												break;
										}
		//
		// END: Index attributes in related tables
		//							
									}
								}
							}
					
							if (!$for_current_value_reindex) {
								// index label for self-relation?
								// * Performed output of "normal" indexing and therefore not support for current value indexing *
								// * (Self relationships are relevant for current value policies so this should not be an issue) *
								if (!$vb_force_related && ($vs_subject_tablename == $vs_related_table)) {
									if ($t_label = $t_rel->getLabelTableInstance()) {
										$t_label->setDb($this->getDb());
										$va_label_info = $this->getTableIndexingInfo($vs_subject_tablename, $t_label->tableName());
										if (is_array($va_label_info['related']['fields']) && sizeof($va_label_info['related']['fields'])) {
											$vn_label_table_num = $t_label->tableNum();

											$vb_skip = false;
											if(is_array($va_restrict_self_indexing_to_types) && $t_rel->load($vn_row_id) && !in_array($t_rel->getTypeID(), $va_restrict_self_indexing_to_types)) {
												$vb_skip = true;
											}

											if (!$vb_skip && is_array($va_labels = array_merge($t_rel->getPreferredLabels(null, false, array('row_id' => $vn_row_id)), $t_rel->getNonPreferredLabels(null, false, array('row_id' => $vn_row_id)) ?? []))) {
												foreach($va_labels as $vn_x => $va_labels_by_locale) {
													foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
														foreach($va_label_list as $va_label) {
															$vn_label_id = $va_label['label_id'];

															foreach($va_label_info['related']['fields'] as $vs_label_field => $va_config) {
																$this->opo_engine->indexField($vn_label_table_num, 'I'.($vn_fn = Datamodel::getFieldNum($vn_label_table_num, $vs_label_field)), $vn_label_id, [$va_label[$vs_label_field]], array_merge($va_config, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
																$this->_genIndexInheritance($t_subject, $t_label, "I{$vn_fn}", $pn_subject_row_id, $vn_label_id, [$va_label[$vs_label_field]], array_merge($va_config, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				
					// index counts?
					// * Counts are not supported for current value indexing *
					// * (Not an issue as the count would always be 1...) *
					if ($vb_index_count) {
						foreach($va_counts as $vs_key => $vn_count) {
							$this->opo_engine->indexField($vn_related_table_num, 'COUNT', 0, [(int)$vn_count], ['relationship_type_id' => $vs_key, 'PRIVATE' => $vn_private]);
							$this->_genIndexInheritance($t_subject, $t_rel, 'COUNT', $pn_subject_row_id, 0, [(int)$vn_count], ['relationship_type_id' => $vs_key, 'PRIVATE' => $vn_private]);
						}
					}
				}
			}
		}

		// save indexing on subject
		if ($vb_started_indexing) {
			$this->opo_engine->commitRowIndexing();
		}
       	// if ($for_current_value_reindex) { return true; }

		if ((!$vb_initial_reindex_mode) && (sizeof($pa_changed_fields) > 0)) {
			//
			// When not reindexing then we consider the effect of the change on this row upon related rows that use it
			// in their indexing. This means figuring out which related tables have indexing that depend upon the subject row.
			//
			// We deal with this by pulling up a dependency map generated from the search_indexing.conf file and then reindexing
			// those rows
			//
			$va_deps = $this->getDependencies($vs_subject_tablename);

			$va_changed_field_nums = array();
			foreach(array_keys($pa_changed_fields) as $vs_f) {
				if ($t_subject->hasField($vs_f)) {
					$va_changed_field_nums[$vs_f] = 'I'.$t_subject->fieldNum($vs_f);
				} else {
					if (preg_match('!^_ca_attribute_([\d]+)$!', $vs_f, $va_matches)) {
						$va_changed_field_nums[$vs_f] = 'A'.ca_metadata_elements::getElementID($va_matches[1]);
					}
				}
			}

			//
			// reindex rows in dependent tables that use the subject_row_id
			//
			$va_rows_to_reindex = $this->_getDependentRowsForSubject($pn_subject_table_num, $pn_subject_row_id, $va_deps, $va_changed_field_nums);

			if ($vb_can_do_incremental_indexing) {
				if (method_exists($vs_subject_tablename, "getDependentCurrentValues")) {
			    	$current_values = $vs_subject_tablename::getDependentCurrentValues($pn_subject_table_num, $pn_subject_row_id, ['db' => $this->getDb()]);
				} else {
					$current_values = [];
				}
				
				$va_rows_to_reindex_by_row_id = [];
				$va_row_ids_to_reindex_by_table = [];
				
				foreach($va_rows_to_reindex as $vs_key => $va_row_to_reindex) {
					foreach($va_row_to_reindex['field_nums'] as $vs_fld_name => $vn_fld_num) {
						$vs_new_key = $va_row_to_reindex['table_num'].'/'.$va_row_to_reindex['field_table_num'].'/'.$vn_fld_num.'/'.$va_row_to_reindex['field_row_id'];

						if(!isset($va_rows_to_reindex_by_row_id[$vs_new_key])) {
							if(is_array($va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'] ?? null)) {
								if (isset($va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'][$vs_subject_type_code])) {
									$va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'] = $va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'][$vs_subject_type_code];
								} elseif(isset($va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST']['*'])) {
									$va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'] = $va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST']['*'];
								} else {
									$va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'] = 0;
								}
							}
							
							$va_rows_to_reindex_by_row_id[$vs_new_key] = array(
								'table_num' => $va_row_to_reindex['table_num'],
								'row_ids' => array(),
								'field_table_num' => $va_row_to_reindex['field_table_num'],
								'field_num' => $vn_fld_num,
								'field_name' => $vs_fld_name,
								'field_row_id' => $va_row_to_reindex['field_row_id'],
								'field_values' => $va_row_to_reindex['field_values'],
								'relationship_type_id' => $va_row_to_reindex['relationship_type_id'],
								'indexing_info' => $va_row_to_reindex['indexing_info'][$vs_fld_name],
								'cv_indexing_info' => $va_row_to_reindex['cv_indexing_info'][$vs_fld_name],
								'private' => $va_row_to_reindex['private']
							);
						}
						$va_rows_to_reindex_by_row_id[$vs_new_key]['row_ids'][] = $va_row_to_reindex['row_id'];
						$va_row_ids_to_reindex_by_table[$va_row_to_reindex['field_table_num']][] = $va_row_to_reindex['field_row_id'];
					}
				}

				foreach($va_row_ids_to_reindex_by_table as $vn_rel_table_num => $va_rel_row_ids) {
					$va_rel_row_ids = array_unique($va_rel_row_ids);
					if ($t_rel = Datamodel::getInstanceByTableNum($vn_rel_table_num, true)) {
						$t_rel->setDb($this->getDb());
						if (method_exists($t_rel, "getApplicableElementCodes")) {
							if (is_array($va_element_ids = array_keys($t_rel->getApplicableElementCodes(null, false, false))) && sizeof($va_element_ids)) {
								ca_attributes::prefetchAttributes($this->opo_db, $vn_rel_table_num, $va_rel_row_ids, $va_element_ids);
							}
						}
					}
				}
				
				$o_indexer = new SearchIndexer($this->opo_db);
				foreach($va_rows_to_reindex_by_row_id as $va_row_to_reindex) {
					if (!is_array($va_row_to_reindex['row_ids'])) { continue; }
					$vn_rel_type_id = $va_row_to_reindex['relationship_type_id'];
					$vn_private = $va_row_to_reindex['private'];
					
					$t_rel = Datamodel::getInstanceByTableNum($va_row_to_reindex['field_table_num'], true);
					$t_rel->setDb($this->getDb());
					if (substr($va_row_to_reindex['field_name'], 0, 14) == '_ca_attribute_') {		// is attribute
						$va_row_to_reindex['indexing_info']['datatype'] = ca_metadata_elements::getElementDatatype(substr($va_row_to_reindex['field_name'], 14));
					}
					
					if ($va_row_to_reindex['field_name'] == '_count') {
						foreach( $va_row_to_reindex['row_ids'] as $vn_subject_row_id) {
							$this->_doCountIndexing(Datamodel::getInstanceByTableNum($va_row_to_reindex['table_num'], true), $vn_subject_row_id, $t_rel, false);
						}
					}

					$index_ancestors = ((isset($va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) && $va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_row_to_reindex['indexing_info'], true));
					$children_inherit = ((isset($va_row_to_reindex['indexing_info']['CHILDREN_INHERIT']) && $va_row_to_reindex['indexing_info']['CHILDREN_INHERIT']) || in_array('CHILDREN_INHERIT', $va_row_to_reindex['indexing_info'], true));
					$ancestors_inherit = ((isset($va_row_to_reindex['indexing_info']['ANCESTORS_INHERIT']) && $va_row_to_reindex['indexing_info']['ANCESTORS_INHERIT']) || in_array('ANCESTORS_INHERIT', $va_row_to_reindex['indexing_info'], true));
					
					$t_instance = null;
					if ($index_ancestors || $children_inherit || $ancestors_inherit) {
						$t_label = Datamodel::getInstanceByTableNum($va_row_to_reindex['field_table_num'], true);
						if ($t_label->load($va_row_to_reindex['field_row_id'], false)) {
							$t_label->setDb($this->getDb());

							foreach($va_row_to_reindex['row_ids'] as $vn_row_id) {
								$t_instance = Datamodel::getInstance($va_row_to_reindex['table_num'], true, $vn_row_id);
								
								if($children_inherit) {
									if (is_array($ids = $t_instance->getHierarchyAsList($vn_row_id, ['idsOnly' => true]))) {
										foreach($ids as $id) {
											$v = $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']];
											$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$id], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $v, array_merge($va_row_to_reindex['indexing_info'], ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]));
										}
									}
								}
								if($ancestors_inherit) {
									if (is_array($ids = $t_instance->getHierarchyAncestors($vn_row_id, ['idsOnly' => true]))) {
										foreach($ids as $id) {
											$v = $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']];
											$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$id], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $v, array_merge($va_row_to_reindex['indexing_info'], ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]));
										}
									}
								}
								if($index_ancestors) {
									$va_content = $this->_genHierarchicalPath($va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_name'], $t_label, $va_row_to_reindex['indexing_info']);
							
									if(!is_array($va_children_ids = $t_instance->getHierarchyAsList($vn_row_id, ['idsOnly' => true]))) { $va_children_ids = []; }
									$va_children_ids[] = $vn_row_id;

									if (is_array($va_row_to_reindex['indexing_info']) && is_array($va_children_ids) && is_array($va_content['values'])) {
										foreach($va_children_ids as $id) {		// Apply indexing to each child
											foreach($va_content['values'] as $v_id => $v) {		// Apply each hierarchical value with correct field row_id
												$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$id], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $v_id, $v, array_merge($va_row_to_reindex['indexing_info'], ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]));
											}
											if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_row_to_reindex['indexing_info'], false) !== false) {
												$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$id], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $v_id, $va_content['path'], array_merge($va_row_to_reindex['indexing_info'], ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]));
											}
										}
									}
									if (is_array($va_row_to_reindex['cv_indexing_info'])) {
										foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
											if(!isset($current_values[$p][$va_row_to_reindex['table_num']][$vn_row_id])) { continue; }
											foreach($va_children_ids as $id) {		// Apply indexing to each child
												foreach($va_content['values'] as $v_id => $v) {		// Apply each hierarchical value with correct field row_id
													$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$id], $va_row_to_reindex['field_table_num'], "CV{$p}_".$va_row_to_reindex['field_num'], null, $v_id, $v, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id, 'literalContent' => $va_content['path'])));
												}
											}
										}
									}
							
									$this->_genIndexInheritance(Datamodel::getInstance($va_row_to_reindex['table_num'], true), Datamodel::getInstance($va_row_to_reindex['field_table_num'], true), $va_row_to_reindex['field_num'], $vn_row_id, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']], array_merge($va_row_to_reindex['indexing_info'], ['PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id]));
								}
							}
						}
					}
					if (((isset($va_row_to_reindex['indexing_info']['INDEX_AS_IDNO']) && $va_row_to_reindex['indexing_info']['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_row_to_reindex['indexing_info'], true)) && method_exists($t_rel, "getIDNoPlugInInstance") && ($o_idno = $t_rel->getIDNoPlugInInstance())) {
						foreach($va_row_to_reindex['row_ids'] as $vn_row_id) {
							if (is_array($va_row_to_reindex['indexing_info'])) {
							    $this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']] ?? null, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id, 'INDEX_AS_IDNO' => $va_row_to_reindex['indexing_info']['INDEX_AS_IDNO'] ?? null, 'IDNO_DELIMITERS' => $va_row_to_reindex['indexing_info']['IDNO_DELIMITERS'] ?? null)));
							}
							if (is_array($va_row_to_reindex['cv_indexing_info'])) {
							    foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
							        if(!isset($current_values[$p][$va_row_to_reindex['table_num']][$vn_row_id])) { continue; }
							        
							        $this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], "CV{$p}_".$va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']] ?? null, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id, 'INDEX_AS_IDNO' => $va_row_to_reindex['indexing_info']['INDEX_AS_IDNO'] ?? null, 'IDNO_DELIMITERS' => $va_row_to_reindex['indexing_info']['IDNO_DELIMITERS'] ?? null)));
						        }
						    }
						}
						continue;
					}
					  
					$vs_element_code = substr($va_row_to_reindex['field_name'], 14);

					if (isset($va_row_to_reindex['indexing_info']['datatype'])) {
						$vs_v = '';
						switch($va_row_to_reindex['indexing_info']['datatype']) {
							case __CA_ATTRIBUTE_VALUE_CONTAINER__: 		// container
								// index components of complex multi-value attributes
								foreach($va_row_to_reindex['row_ids'] as $vn_rel_row_id) {
									$this->opo_engine->startRowIndexing($va_row_to_reindex['table_num'], $vn_rel_row_id);

									$va_attributes = $t_rel->getAttributesByElement($vs_element_code, array('row_id' => $va_row_to_reindex['field_row_id']));
									if (sizeof($va_attributes)) {
										foreach($va_attributes as $vo_attribute) {
											foreach($vo_attribute->getValues() as $vo_value) {
												$vn_sub_element_id = $vo_value->getElementID();
												$vn_list_id = ca_metadata_elements::getElementListID($vn_sub_element_id);
												$vs_value_to_index = $vo_value->getDisplayValue($vn_list_id);
												$attribute_id = $vo_attribute->getAttributeID();

												$va_additional_indexing = $vo_value->getDataForSearchIndexing();
												if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
													foreach($va_additional_indexing as $vs_additional_value) {
														$vs_value_to_index .= " ; ".$vs_additional_value;
													}
												}
												if ($t_to_reindex = Datamodel::getInstanceByTableNum($va_row_to_reindex['table_num'], true)) {
													if (is_array($va_row_to_reindex['indexing_info'])) {
														$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$vn_rel_row_id], $va_row_to_reindex['field_table_num'], "A{$vn_sub_element_id}", $attribute_id, $va_row_to_reindex['field_row_id'], $vs_value_to_index, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
													}    
													if (is_array($va_row_to_reindex['cv_indexing_info'])) {
														foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
															if(!isset($current_values[$p][$va_row_to_reindex['table_num']][$vn_rel_row_id])) { continue; }
															
															$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], [$vn_rel_row_id], $va_row_to_reindex['field_table_num'], "CV{$p}_A{$vn_sub_element_id}", $attribute_id, $va_row_to_reindex['field_row_id'], $vs_value_to_index, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
														}
													}
												}
											}
										}
									} else {
										// we are deleting a container so cleanup existing sub-values
										$va_sub_elements = $this->opo_metadata_element->getElementsInSet($vs_element_code);

										foreach($va_sub_elements as $vn_i => $va_element_info) {
											if ($t_to_reindex = Datamodel::getInstanceByTableNum($va_row_to_reindex['table_num'], true)) {
												if (is_array($va_row_to_reindex['indexing_info'])) {
													$this->opo_engine->removeRowIndexing($va_row_to_reindex['table_num'], $vn_rel_row_id, $va_row_to_reindex['field_table_num'], ['A'.$va_element_info['element_id']], $va_row_to_reindex['field_row_id'], $vn_rel_type_id);				
												}
												if (is_array($va_row_to_reindex['cv_indexing_info'])) {
													foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
														if(!isset($current_values[$p][$va_row_to_reindex['table_num']][$vn_rel_row_id])) { continue; }
														
														$this->opo_engine->removeRowIndexing($va_row_to_reindex['table_num'], $vn_rel_row_id, $va_row_to_reindex['field_table_num'], ["CV{$p}_A".$va_element_info['element_id']], $va_row_to_reindex['field_row_id'], $vn_rel_type_id);	
													}
												}
											}
										}
									}
									$this->opo_engine->commitRowIndexing();
								}
								break;
							case __CA_ATTRIBUTE_VALUE_LIST__:			// list
								$va_tmp = array();
								if (is_array($va_attributes = $t_rel->getAttributesByElement($vs_element_code, array('row_id' => $va_row_to_reindex['field_row_id'])))) {
									foreach($va_attributes as $vo_attribute) {
										foreach($vo_attribute->getValues() as $vo_value) {
											$va_tmp[$vo_attribute->getAttributeID()] = $vo_value->getDisplayValue();
										}
									}
								}

								$va_new_values = [];
								$t_item = new ca_list_items();
								$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, ['returnAllTypes' => true, 'returnAllLocales' => true]);

								foreach($va_labels as $vn_label_row_id => $va_labels_per_row) {
									foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
										foreach($va_label_list as $vs_label) {
											$va_new_values[$vn_label_row_id][$vs_label] = true;
										}
									}
								}

								foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
									if(!$vn_item_id) { continue; }
									if(!isset($va_new_values[$vn_item_id]) || !is_array($va_new_values[$vn_item_id])) { continue; }
									$vs_v = join(' ;  ', array_merge(array($vn_item_id), array_keys($va_new_values[$vn_item_id])));
								}

								if (is_array($va_row_to_reindex['indexing_info'])) {
									$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $vs_v, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
								}
								if (is_array($va_row_to_reindex['cv_indexing_info'])) {
									foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {   
										if(!isset($current_values[$p][$va_row_to_reindex['table_num']])) { continue; }
										$row_ids = array_intersect(array_keys($current_values[$p][$va_row_to_reindex['table_num']]), $va_row_to_reindex['row_ids']);
										if (!$row_ids || !is_array($row_ids) || !sizeof($row_ids)) { continue; }
										
										$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $row_ids, "CV{$p}_".$va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $vs_v, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
									}
								}
								break;
							default:
								if (method_exists($t_rel, 'getAttributesByElement') && is_array($va_attributes = $t_rel->getAttributesByElement($vs_element_code, array('row_id' => $va_row_to_reindex['field_row_id'])))) {
									foreach($va_attributes as $vo_attribute) {
										foreach($vo_attribute->getValues() as $vo_value) {
											$vs_value_to_index = $vo_value->getDisplayValue();

											$va_additional_indexing = $vo_value->getDataForSearchIndexing();
											if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
												foreach($va_additional_indexing as $vs_additional_value) {
													$vs_value_to_index .= " ; ".$vs_additional_value;
												}
											}
											if (is_array($va_row_to_reindex['indexing_info'])) {
												$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $vo_attribute->getAttributeID(), $va_row_to_reindex['field_row_id'], $vs_value_to_index, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
											}
											if (is_array($va_row_to_reindex['cv_indexing_info'])) {
												foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
													if(!isset($current_values[$p][$va_row_to_reindex['table_num']])) { continue; }
													$row_ids = array_intersect(array_keys($current_values[$p][$va_row_to_reindex['table_num']]), $va_row_to_reindex['row_ids']);
													if (!$row_ids || !is_array($row_ids) || !sizeof($row_ids)) { continue; }
													
													$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $row_ids, $va_row_to_reindex['field_table_num'], "CV{$p}_".$va_row_to_reindex['field_num'], $vo_attribute->getAttributeID(), $va_row_to_reindex['field_row_id'], $vs_value_to_index, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
												}
											}
										}
									}
								}
								break;
						}
					} else {
						if (is_array($va_row_to_reindex['indexing_info'])) {
							$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']] ?? null, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
						}
						if (is_array($va_row_to_reindex['cv_indexing_info'])) {
							foreach($va_row_to_reindex['cv_indexing_info'] as $p => $pinfo) {
								if(!isset($current_values[$p][$va_row_to_reindex['table_num']])) { continue; }
								$row_ids = array_intersect(array_keys($current_values[$p][$va_row_to_reindex['table_num']]), $va_row_to_reindex['row_ids']);
								if (!$row_ids || !is_array($row_ids) || !sizeof($row_ids)) { continue; }
								
								$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $row_ids, $va_row_to_reindex['field_table_num'], "CV{$p}_".$va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']] ?? null, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
							}
						}
					}
				}
				
				
				// reindex any rows that have authority metadata elements that reference this
				if (is_subclass_of($t_subject, "BaseLabel") && ($t_base = $t_subject->getSubjectTableInstance(['dontLoadInstance' => false])) && method_exists($t_base, "getAuthorityElementReferences") && is_array($element_references = $t_base->getAuthorityElementReferences(['row_id' => $t_base->getPrimaryKey()]))) {
					foreach($element_references as $element_table_num => $references) {
						if(!is_array($references) || (sizeof($references) === 0)) { continue; }
						
						$labels = $t_base->getPreferredDisplayLabelsForIDs([$t_base->getPrimaryKey()], ['noCache' => true, 'returnAllLocales' => true]);

						$values = [];
						foreach($labels as $row_id => $labels_per_row) {
							foreach($labels_per_row as $locale_id => $label_list) {
								foreach($label_list as $label) {
									$values[$label] = true;	
								}
							}
						}
						$rtable_num = $t_base->tableNum();
						foreach($references as $row_id => $elements) {
							$content = join('; ', array_keys($values ?? []));
							$element_fields_to_index = $this->getFieldsToIndex($element_table_num, $rtable_num);
							foreach($elements as $e_id) {
								$this->opo_engine->updateIndexingInPlace($element_table_num, [$row_id], $element_table_num, "A_{$e_id}", null, $row_id, $content, $element_fields_to_index["_ca_attribute_{$e_id}"] ?? []);
							}
						}
					}
				}
				
			} else {
				//
				// If the underlying engine doesn't support incremental indexing then
				// we fall back to reindexing each dependenting row completely and independently.
				// This can be *really* slow for subjects with many dependent rows (for example, a ca_list_item value used as a type for many ca_objects rows)
				// and we need to think about how to optimize this for such engines; ultimately since no matter how you slice it in such
				// engines you're going to have a lot of reindexing going on, we may just have to construct a facility to handle large
				// indexing tasks in a separate process when the number of dependent rows exceeds a certain threshold
				//
				$o_indexer = new SearchIndexer($this->opo_db);
				$t_dep = null;
				$va_rows_seen = array();
				foreach($va_rows_to_reindex as $va_row_to_reindex) {
					if(isset($va_rows_seen[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']])) { continue; }
					if ((!$t_dep) || ($t_dep->tableNum() != $va_row_to_reindex['table_num'])) {
						$t_dep = Datamodel::getInstanceByTableNum($va_row_to_reindex['table_num']);
					}

					$vb_support_attributes = is_subclass_of($t_dep, 'BaseModelWithAttributes') ? true : false;
					if (is_array($pa_exclusion_list[$va_row_to_reindex['table_num']]) && (isset($pa_exclusion_list[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']]))) { continue; }
					
					// trigger reindexing
					$this->opo_engine->removeRowIndexing($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], null, null, null, $va_row_to_reindex['relationship_type_id']);
					if ($vb_support_attributes) {
						if ($t_dep->load($va_row_to_reindex['row_id'])) {
							$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $t_dep->getFieldValuesArray(), true, $pa_exclusion_list);
						}
					} else {
						$o_indexer->indexRow($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], $va_row_to_reindex['field_values'], true, $pa_exclusion_list);
					}

					$va_rows_seen[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']] = true;
				}
				$o_indexer = null;
			}
		}

		if ($vb_reindex_children && method_exists($t_subject, "makeSearchResult")) {
			//
			// Force reindexing of children of this record, typically because the record has shifted location in the hierarchy and is hierarchically indexed
			//
			$va_children_ids = $t_subject->getHierarchyAsList($pn_subject_row_id, array('idsOnly' => true));
			if (is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
				// trigger reindexing of children
				$o_indexer = new SearchIndexer($this->opo_db);
				$qr_children_res = $t_subject->makeSearchResult($vs_subject_tablename, $va_children_ids, array('db' => $this->getDb()));
				while($qr_children_res->nextHit()) {
					$o_indexer->indexRow($pn_subject_table_num, $vn_id=$qr_children_res->get($vs_subject_pk), array($vs_subject_pk => $vn_id, 'parent_id' => $qr_children_res->get('parent_id')), true, $pa_exclusion_list, array());
				}
			}
		}
		
		return true;
	}
	# ------------------------------------------------
	/**
	 * Index attribute
	 *
	 * @param BaseModel $pt_subject
	 * @param int $pn_row_id
	 * @param mixed $pm_element_code_or_id
	 * @param array $pa_data
	 * @param array $pa_options Options include:
	 *      currentValues = 
	 *      policy = 
	 *      t_inheritance_subject = 
	 *      inheritance_subject_id = 
	 * @return bool
	 */
	private function _indexAttribute($pt_subject, $pn_row_id, $pm_element_code_or_id, $pa_data, $pa_options=null) {
		if(!method_exists($pt_subject, "getAttributesByElement")) { return true; } 
		$va_attributes = $pt_subject->getAttributesByElement($pm_element_code_or_id, array('row_id' => $pn_row_id));
		$pn_subject_table_num = $pt_subject->tableNum();
		
		$vn_count = 0;
		
		$policy = caGetOption('policy', $pa_options, null);
		$field_num_prefix = ($policy && caGetOption('currentValues', $pa_options, false)) ? "CV{$policy}_A" : "A";
		
		$t_inheritance_subject = caGetOption('t_inheritance_subject', $pa_options, null);
		$pn_inheritance_subject_id = caGetOption('inheritance_subject_id', $pa_options, null);

		$vn_datatype = isset($pa_data['datatype']) ? $pa_data['datatype'] : ca_metadata_elements::getElementDatatype($pm_element_code_or_id);

		switch($vn_datatype) {
			case __CA_ATTRIBUTE_VALUE_CONTAINER__: 		// container
				$vn_element_id = is_numeric($pm_element_code_or_id) ? $pm_element_code_or_id : ca_metadata_elements::getElementID($pm_element_code_or_id);
				
				// index components of complex multi-value attributes
				if ($vn_count = sizeof($va_attributes)) {
					
					// Create value map to support conditional privacy in containers
					$map = [];
					if ($private_when = caGetOption('PRIVATE_WHEN', $pa_data, null)) {
						foreach($va_attributes as $i => $vo_attribute) {
							foreach($vo_attribute->getValues() as $vo_value) {
								$map[$i][ca_metadata_elements::getElementCodeForId($vo_value->getElementID())] = $vo_value->getDisplayValue(['returnIdno' => true]);
							}
						}
					}
					foreach($va_attributes as $i => $vo_attribute) {
						/* index each element of the container */
						$va_sub_element_ids = $this->opo_metadata_element->getElementsInSet($vn_element_id, true, array('idsOnly' => true));
						if (is_array($va_sub_element_ids) && sizeof($va_sub_element_ids)) {
							
							// Enforce "PRIVATE_WHEN" setting – allows for conditional privacy of values in container based upon any container value
							if(is_array($private_when)) {
								foreach($private_when as $pf => $pv) {
									if (!isset($map[$i][$pf])) { continue; }
									if(!is_array($pv)) { $pv = [$pv]; }
									$pv = array_filter($pv, "strlen");
									if(!sizeof($pv)) { continue; }
									
									if(in_array($map[$i][$pf], $pv, true)) { $pa_data['PRIVATE'] = 1; break; }
								}
							}
						
							$va_sub_element_ids = array_flip($va_sub_element_ids);
							
							$va_values_to_index = [];
							foreach($vo_attribute->getValues() as $vo_value) {								
								$vn_sub_element_id = $vo_value->getElementID();
								
								$vs_sub_element_code = ca_metadata_elements::getElementCodeForId($vn_sub_element_id);
								$vn_list_id = ca_metadata_elements::getElementListID($vn_sub_element_id);
								$vs_value_to_index = $vo_value->getDisplayValue(['list_id' => $vn_list_id]);


								$va_additional_indexing = $vo_value->getDataForSearchIndexing();
								if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
									foreach($va_additional_indexing as $vs_additional_value) {
										$vs_value_to_index .= " ; ".$vs_additional_value;
									}
								}
								
								$va_values_to_index = [$vs_value_to_index];
								
								if (!in_array($vs_raw_display_value = $vo_value->getDisplayValue(), $va_values_to_index)) {
									$va_values_to_index[] = $vs_raw_display_value;
								}
								
								if ($vo_value->getDatatype() == __CA_ATTRIBUTE_VALUE_LIST__) {
                                    $va_values_to_index[] = $vo_value->getDisplayValue(['output' => 'idno']);
                                }
								
								$va_sub_data = $pa_data;
								$va_sub_data['container_id'] = $vo_attribute->getAttributeID();
								
								if(isset($pa_data['_ca_attribute_'.$vn_sub_element_id]) && is_array($pa_data['_ca_attribute_'.$vn_sub_element_id])) {
									$va_sub_data = array_merge($pa_data, $pa_data['_ca_attribute_'.$vn_sub_element_id]);
								}
								$va_sub_data = array_filter($va_sub_data, function($v) { return !is_array($v) && (strlen($v) > 0); });
								
								$this->opo_engine->indexField($pn_subject_table_num, "{$field_num_prefix}{$vn_sub_element_id}", $pn_row_id, $va_values_to_index, $va_sub_data);
								$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, "{$field_num_prefix}{$vn_sub_element_id}", $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, $va_values_to_index, $va_sub_data, $pa_options);
								
								unset($va_sub_element_ids[$vn_sub_element_id]);
							}

							// Clear out any elements that aren't defined
							foreach(array_keys($va_sub_element_ids) as $vn_e) {
								$va_sub_data = $pa_data;
								if(isset($pa_data['_ca_attribute_'.$vn_e]) && is_array($pa_data['_ca_attribute_'.$vn_e])) {
									$va_sub_data = array_merge($pa_data, $pa_data['_ca_attribute_'.$vn_e]);
								}
								$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_e, $pn_row_id, [''], array_merge($va_sub_data, ['container_id' => $vo_attribute->getAttributeID()]));
								$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_e, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [''], $pa_data);
							}
						}
					}
				} else {
					// we are deleting a container so cleanup existing sub-values
					if (is_array($va_sub_elements = $this->opo_metadata_element->getElementsInSet($pm_element_code_or_id))) {
						foreach($va_sub_elements as $vn_i => $va_element_info) {
							$va_sub_data = $pa_data;
							
							$sub_element_id = $va_element_info['element_id'];
							if(isset($pa_data['_ca_attribute_'.$sub_element_id]) && is_array($pa_data['_ca_attribute_'.$sub_element_id])) {
								$va_sub_data = array_merge($pa_data, $pa_data['_ca_attribute_'.$sub_element_id]);
							}
							$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$sub_element_id, $pn_row_id, [''], $va_sub_data);
							$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$va_element_info['element_id'], $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [''], $pa_data);
						}
					}
				}
				break;
			case __CA_ATTRIBUTE_VALUE_LIST__:
			case __CA_ATTRIBUTE_VALUE_OBJECTS__:
			case __CA_ATTRIBUTE_VALUE_ENTITIES__:
			case __CA_ATTRIBUTE_VALUE_PLACES__:
			case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
			case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
			case __CA_ATTRIBUTE_VALUE_LOANS__:
			case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
			case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
			case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
				// We pull the preferred labels of list items for indexing here. We do so for all languages. Note that
				// this only done for list attributes that are standalone and not a sub-element in a container. Perhaps
				// we should also index the text of sub-element lists, but it's not clear that it is a good idea yet. The list_id's of
				// sub-elements *are* indexed however, so advanced search forms passing ids instead of text will work.
				$va_tmp = [];

				$vn_element_id = is_numeric($pm_element_code_or_id) ? $pm_element_code_or_id : ca_metadata_elements::getElementID($pm_element_code_or_id);
				$va_attributes = $pt_subject->getAttributesByElement($vn_element_id, array('row_id' => $pn_row_id));

				if (is_array($va_attributes) && ($vn_count = sizeof($va_attributes))) {
					foreach($va_attributes as $vo_attribute) {
						foreach($vo_attribute->getValues() as $vo_value) {
							$vs_value_to_index = $vo_value->getDisplayValue(['idsOnly' => true]);

							$va_additional_indexing = $vo_value->getDataForSearchIndexing();
							if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
								foreach($va_additional_indexing as $vs_additional_value) {
									$vs_value_to_index .= " ; ".$vs_additional_value;
								}
							}
							
							$va_values_to_index = [$vs_value_to_index];
							
							if (!in_array($vs_raw_display_value = $vo_value->getDisplayValue(), $va_values_to_index)) {
								$va_values_to_index[] = $vs_raw_display_value;
							}
							
							if ($vn_datatype == __CA_ATTRIBUTE_VALUE_LIST__) {
								$v = [$vo_value->getDisplayValue(['output' => 'idno']), $vo_value->getDisplayValue(['output' => 'text'])];
								$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, $v, array_merge($pa_data, ['DONT_TOKENIZE' => false]));
								$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject,
									$t_inheritance_subject ? $pt_subject : null,
									$field_num_prefix.$vn_element_id,
									$pn_inheritance_subject_id ? $pn_inheritance_subject_id :
										($pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id), $pn_row_id, $v, array_merge($pa_data, ['DONT_TOKENIZE' => 1]));
							}
							$va_tmp[$vo_attribute->getAttributeID()] = $vs_value_to_index;
						}
					}
				} else {
					// Delete indexing
					$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [''], $pa_data);
					$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [''], $pa_data);
				}

				// Index labels of authority items
				if(is_array($va_tmp) && sizeof($va_tmp)) {
					$va_new_values = array();
					if ($t_item = AuthorityAttributeValue::elementTypeToInstance($vn_datatype)) {
						$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, array('returnAllLocales' => true));

						foreach($va_labels as $vn_row_id => $va_labels_per_row) {
							foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
								foreach($va_label_list as $vs_label) {
									$va_new_values[$vn_row_id][$vs_label] = true;
								}
							}
						}

						foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
							if(!$vn_item_id) { continue; }
							if(!isset($va_new_values[$vn_item_id]) || !is_array($va_new_values[$vn_item_id])) { continue; }
							$vs_v = join(' ;  ', array_merge(array($vn_item_id), array_keys($va_new_values[$vn_item_id])));
							$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [$vs_v], $pa_data);
							$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [$vs_v], $pa_data, $pa_options);
							
							foreach(["preferred_labels.".$t_item->getLabelDisplayField(), $t_item->primaryKey()] as $vs_f) {
								if ($va_hier_values = $this->_genHierarchicalPath($vn_item_id, $vs_f, $t_item, $pa_data)) {

									$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, array_merge([$vs_v], $va_hier_values['values']), $pa_data);
									$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, array_merge([$vs_v], $va_hier_values['values']), $pa_data, $pa_options);
								
									if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $pa_data, false) !== false) {
										$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [$va_hier_values['path']], [$pa_data, ['DONT_TOKENIZE' => 1]]);
										$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [$va_hier_values['path']], [$pa_data, ['DONT_TOKENIZE' => 1]], $pa_options);
									}
								}
							}
							
							
						}
					}
				}

				break;
			default:
				$vn_element_id = is_numeric($pm_element_code_or_id) ? $pm_element_code_or_id : ca_metadata_elements::getElementID($pm_element_code_or_id);

				$va_attributes = $pt_subject->getAttributesByElement($pm_element_code_or_id, array('row_id' => $pn_row_id));
				if (!is_array($va_attributes)) { $va_attributes = array(); }

				if(($vn_count = sizeof($va_attributes)) > 0) {
					foreach($va_attributes as $vo_attribute) {
						foreach($vo_attribute->getValues() as $vo_value) {
							$vs_value_to_index = $vo_value->getDisplayValue();

							$va_additional_indexing = $vo_value->getDataForSearchIndexing();
							if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
								foreach($va_additional_indexing as $vs_additional_value) {
									$vs_value_to_index .= " ; ".$vs_additional_value;
								}
							}

							if (((isset($pa_data['INDEX_AS_IDNO']) && $pa_data['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $pa_data, true)) && method_exists($pt_subject, "getIDNoPlugInInstance") && ($o_idno = $pt_subject->getIDNoPlugInInstance())) {
								if (strlen($pa_data['IDNO_DELIMITERS']) || (is_array($pa_data['IDNO_DELIMITERS']) && count($pa_data['IDNO_DELIMITERS']))) {
									if (!is_array($pa_data['IDNO_DELIMITERS'])) { $pa_data['IDNO_DELIMITERS'] = [$pa_data['IDNO_DELIMITERS']]; }
									$va_values = array_map(function($v) { return trim($v); }, preg_split('!('.join('|', $pa_data['IDNO_DELIMITERS']).')!', $vs_value_to_index));
								} else {
									$va_values = $o_idno->getIndexValues($vs_value_to_index, $pa_data);
								}
								$this->opo_engine->indexField($pn_subject_table_num, "{$field_num_prefix}{$vn_element_id}", $pn_row_id, $va_values, $pa_data);
								$this->_genIndexInheritance($pt_subject, null, "{$field_num_prefix}{$vn_element_id}", $pn_row_id, $pn_row_id, $va_values, $pa_data);
							}
							$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [$vs_value_to_index], $pa_data);
							$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [$vs_value_to_index], $pa_data);
						}
					}
				} else {
					// Delete indexing
					$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [''], $pa_data);
					$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [''], $pa_data);
				}

				$vs_subject_pk = $pt_subject->primaryKey();

				// reindex children?
				if((caGetOption('INDEX_ANCESTORS', $pa_data, false) !== false) || (in_array('INDEX_ANCESTORS', $pa_data, true))) {
					if ($pt_subject && $pt_subject->isHierarchical()) {
						if ($va_hier_values = $this->_genHierarchicalPath($pn_row_id, $vs_element_code = ca_metadata_elements::getElementCodeForId($vn_element_id), $pt_subject, $pa_data)) {
							$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, $va_hier_values['values'], $pa_data);
							$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, $va_hier_values['values'], $pa_data);
							
							if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $pa_data, false) !== false) {
								$this->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $pn_row_id, [$va_hier_values['path']], array_merge($pa_data, ['DONT_TOKENIZE' => 1]));
								$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [$va_hier_values['path']], array_merge($pa_data, ['DONT_TOKENIZE' => 1]));
							}
						}

						$va_children_ids = $pt_subject->getHierarchyAsList($pn_row_id, array('idsOnly' => true, 'includeSelf' => false));

						if (!caGetOption('reindex', $pa_options, false) && is_array($va_children_ids) && sizeof($va_children_ids) > 0) {
							// trigger reindexing of children
							$o_indexer = new SearchIndexer($this->opo_db);
							$pt_subject->load($pn_row_id);
							$va_content = $pt_subject->get($pt_subject->tableName().".".$vs_element_code, array('returnWithStructure' => true,'returnAsArray' => true, 'returnAllLocales' => true));

							foreach($va_children_ids as $vn_id) {
								if($vn_id == $pn_row_id) { continue; }
								$o_indexer->opo_engine->startRowIndexing($pn_subject_table_num, $vn_id);
								foreach($va_content as $vn_i => $va_by_locale) {
									foreach($va_by_locale as $vn_locale_id => $va_content_list) {
										foreach($va_content_list as $va_content_container) {
											$o_indexer->opo_engine->indexField($pn_subject_table_num, $field_num_prefix.$vn_element_id, $vn_id, [$va_content_container[$vs_element_code]], array_merge($pa_data, ['DONT_TOKENIZE' => 1, 'TOKENIZE' => 1]));
											$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, $field_num_prefix.$vn_element_id, $pn_row_id, $vn_id, [$va_content_container[$vs_element_code]], array_merge($pa_data, ['DONT_TOKENIZE' => 1, 'TOKENIZE' => 1]));
										}
									}
								}
								$o_indexer->opo_engine->commitRowIndexing();
							}
						}
						break;
					}
				}
				break;
		}
		
		if ((isset($pa_data['COUNT']) && (bool)$pa_data['COUNT']) || in_array('COUNT', $pa_data, true)) {
			$this->opo_engine->indexField($pn_subject_table_num, 'COUNT'.$vn_element_id, $pn_row_id, [$vn_count], array_merge($pa_data, ['relationship_type_id' => 0]));
			$this->_genIndexInheritance($t_inheritance_subject ? $t_inheritance_subject : $pt_subject, $t_inheritance_subject ? $pt_subject : null, 'COUNT'.$vn_element_id, $pn_inheritance_subject_id ? $pn_inheritance_subject_id : $pn_row_id, $pn_row_id, [$vn_count], array_merge($pa_data, ['relationship_type_id' => 0]));
		}	
		return true;
	}
	# ------------------------------------------------
	/**
	 * Removes indexing for specified row in table; this is the public call when one is deleting a record
	 * and needs to remove the associated indexing. unindexRow() will also remove indexing for the specified
	 * row from all dependent rows in other tables. It essentially undoes the results of indexRow().
	 * (Note that while this is called this a "public" call in fact you shouldn't need to call this directly. BaseModel.php does
	 * this for you during delete().)
	 */
	public function startRowUnIndexing($pn_subject_table_num, $pn_subject_row_id) {
		$vs_subject_tablename = Datamodel::getTableName($pn_subject_table_num);
		$va_deps = $this->getDependencies($vs_subject_tablename);

		$va_indexed_tables = $this->getIndexedTables();

		// Trigger reindexing if:
		//		* This row has dependencies
		//		* The row's table is indexed
		//		* We're changing an attribute or attribute value
		if (in_array($pn_subject_table_num, array(3,4)) || isset($va_indexed_tables[$pn_subject_table_num]) || sizeof($va_deps)) {
			$this->opa_dependencies_to_update = $this->_getDependentRowsForSubject($pn_subject_table_num, $pn_subject_row_id, $va_deps);
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function removeDependentIndexing($table_num, $row_id, $options = null) {
		return $this->opo_engine->removeRowIndexing(null, null, $table_num, null, $row_id);
	}
	# ------------------------------------------------
	public function commitRowUnIndexing($pn_subject_table_num, $pn_subject_row_id, $pa_options = null) {
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;		// can the engine do incremental indexing? Or do we need to reindex the entire row every time?

		if(caGetOption('queueIndexing', $pa_options, false) && !$this->opo_app_config->get('disable_out_of_process_search_indexing') && !defined('__CA_DONT_QUEUE_SEARCH_INDEXING__')) {
			$this->queueUnIndexRow(array(
				'table_num' => $pn_subject_table_num,
				'row_id' => $pn_subject_row_id,
				'dependencies' => $this->opa_dependencies_to_update
			));
			return;
		}

		if($va_deps = caGetOption('dependencies', $pa_options, null)) {
			$this->opa_dependencies_to_update = $va_deps;
		}

		// if dependencies have not been set at this point -- either by startRowUnindexing
		// (which may have been skipped) or by passing the dependencies option -- then get them now
		if(!$this->opa_dependencies_to_update) {
			$va_deps = $this->getDependencies(Datamodel::getTableName($pn_subject_table_num));
			$this->opa_dependencies_to_update = $this->_getDependentRowsForSubject($pn_subject_table_num, $pn_subject_row_id, $va_deps);
		}

		// delete index from subject
		$this->opo_engine->removeRowIndexing($pn_subject_table_num, $pn_subject_row_id);
		if (is_array($this->opa_dependencies_to_update)) {
			$t_subject = Datamodel::getInstanceByTableNum($pn_subject_table_num, true);
			
			if (!$vb_can_do_incremental_indexing) {
				$va_seen_items = array();

				// Get row content for indexing in one pass
				$va_id_list = array();
				foreach($this->opa_dependencies_to_update as $va_item) {
					$va_id_list[$va_item['table_num']][$va_item['row_id']] = true;
				}
				$va_field_values = array();
				foreach($va_id_list as $vn_table_num => $va_row_ids) {
					if($t_instance = Datamodel::getInstanceByTableNum($vn_table_num, true)) {
						$va_field_values[$vn_table_num] = $t_instance->getFieldValuesForIDs(array_keys($va_row_ids));
					}
				}

				// Perform reindexing
				foreach($this->opa_dependencies_to_update as $va_item) {
					// trigger reindexing of related rows in dependent tables
					if (isset($va_seen_items[$va_item['table_num']][$va_item['row_id']])) { continue; }
					$this->opo_engine->removeRowIndexing($va_item['table_num'], $va_item['row_id'], null, null, null, $va_item['relationship_type_id']);

					$this->indexRow($va_item['table_num'], $va_item['row_id'], $va_field_values[$va_item['table_num']][$va_item['row_id']]);
					$this->_doCountIndexing(Datamodel::getInstanceByTableNum($va_item['table_num'], true), $va_item['row_id'], $t_subject, false);
				
					$va_seen_items[$va_item['table_num']][$va_item['row_id']] = true;
				}
			} else {
				// incremental indexing engines delete dependent rows here
				// delete from index where other subjects reference it 

				foreach($this->opa_dependencies_to_update as $va_item) {
					$this->opo_engine->removeRowIndexing($va_item['table_num'], $va_item['row_id'], $va_item['field_table_num'], $va_item['field_nums'], $va_item['field_row_id'], $va_item['relationship_type_id']);
					
					// Remove current value indexing
					if (is_array($va_item['cv_indexing_info'])) {
					    $cv_field_nums = [];
					    foreach($va_item['cv_indexing_info'] as $f => $finfo) {
					        if(!is_array($finfo)) { continue; }
					        foreach($finfo as $p => $pinfo) {
					            if(is_array($fn = self::_searchConfigFieldEntryToIndexFieldNums($va_item['field_table_num'], $f, ['policy' => $p]))) {
					                $cv_field_nums += $fn;
					            }
					        }
					    }
					    if (sizeof($cv_field_nums)) {
					        $this->opo_engine->removeRowIndexing($va_item['table_num'], $va_item['row_id'], $va_item['field_table_num'], $cv_field_nums, $va_item['field_row_id'], $va_item['relationship_type_id']);
					    }
					}
					// Remove existing count index and recreate
					$this->opo_engine->removeRowIndexing($va_item['table_num'], $va_item['row_id'], $va_item['field_table_num'], null, 0, $va_item['relationship_type_id']);
					$this->_doCountIndexing($t_rel = Datamodel::getInstanceByTableNum($va_item['table_num'], true), $va_item['row_id'], $t_subject, false);
					
					// Force recounts on related items when relationship record is modified
					if ($t_subject->isRelationship()) {
						if ($opp = $t_subject->getOppositeTableName($t_rel->tableName())) {
							$this->_doCountIndexing($t_rel, $va_item['row_id'], Datamodel::getInstanceByTableName($opp, true), false);
						}
					}
				}
			}
		}
		$this->opa_dependencies_to_update = null;
	}
	# ------------------------------------------------
	/**
	 * Determine if any of the fields to index are in the list of changed field nums
	 *
	 * @param array $pa_fields_to_index Array of fields to index as returned by SearchBase::getFieldsToIndex()
	 * @param array $pa_changed_field_nums Array of fields that have changed, where array keys are field names and array values are field number codes (Eg. I15 or A4)
	 * @return bool
	 */
	private function _indexedFieldsHaveChanged($pa_fields_to_index, $pa_changed_field_nums) {
		foreach($pa_fields_to_index as $vs_field => $va_indexing_info) {
			switch($vs_field) {
				case '_count':
					// noop
					break;
				default:
					$vn_fld_num = null;
					if (is_array($pa_changed_field_nums)) {
						if (isset($pa_changed_field_nums[$vs_field]) && $pa_changed_field_nums[$vs_field]) {
							return true;
						}
					}
					break;
			}
		}
		return false;
	}
	# ------------------------------------------------
	/**
	 * Returns an array with info about rows that need to be reindexed due to change in content for the given subject
	 */
	private function _getDependentRowsForSubject($pn_subject_table_num, $pn_subject_row_id, $va_deps, $pa_changed_field_nums=null) {
		$va_dependent_rows = array();
		$vs_subject_tablename = Datamodel::getTableName($pn_subject_table_num);

		$t_subject = Datamodel::getInstanceByTableName($vs_subject_tablename, false);
		$vs_subject_pk = $t_subject->primaryKey();
		
		$public_access = array_map('intval', $this->opo_app_config->getList('public_access_settings') ?? []);

// Loop through dependent tables

		foreach($va_deps as $vs_dep_table) {
			$t_dep 				= Datamodel::getInstanceByTableName($vs_dep_table, true);
			if (!$t_dep) { continue; }
			$vs_dep_pk 			= $t_dep->primaryKey();
			$vn_dep_table_num 	= $t_dep->tableNum();

			//
			// Handle indexing of self relationships (Eg. indexing of tables such as ca_objects_x_objects)
			// *and* 
			// 'related' indexing (indexing across self-relationships for a record; Eg. indexing of related objects against objects)
			//
			if (
				(method_exists($t_subject, 'isSelfRelationship') && $t_subject->isSelfRelationship())
				||
				($vs_subject_tablename === $vs_dep_table)
			) {
			
				$va_params = null;
				if(($vs_subject_tablename === $vs_dep_table) && ($vs_self_rel_table_name = $t_dep->getSelfRelationTableName())) {
					//
					// dependency for 'related' indexing; translate dependency into a set of self-relations
					//
					$t_self_rel = Datamodel::getInstanceByTableName($vs_self_rel_table_name, true);
					$va_params = [$pn_subject_row_id, $pn_subject_row_id];
					
					$vs_sql_joins = $vs_delete_sql = '';
					if ($t_subject->hasField('deleted')) {
						$vs_sql_joins = "
							INNER JOIN {$vs_subject_tablename} AS t1 ON t1.{$vs_subject_pk} = t0.".$t_self_rel->getLeftTableFieldName()."
							INNER JOIN {$vs_subject_tablename} AS t2 ON t2.{$vs_subject_pk} = t0.".$t_self_rel->getRightTableFieldName();
						$vs_delete_sql = "AND (t1.deleted = 0 AND t2.deleted = 0)";
					}
					
					$vs_sql = "
						SELECT *
						FROM {$vs_self_rel_table_name} t0
						{$vs_sql_joins}
						WHERE
							(t0.".$t_self_rel->getLeftTableFieldName()." = ? OR t0.".$t_self_rel->getRightTableFieldName()." = ?)
							{$vs_delete_sql}
					";
				} else {
					//
					// dependency is a specific row in a self-relation
					//
					$t_self_rel = $t_subject;
					
					$va_params = [$pn_subject_row_id];
					
					$vs_sql_joins = $vs_delete_sql = '';
					if ($t_subject->hasField('deleted')) {
						$vs_sql_joins = "
							INNER JOIN {$vs_subject_tablename} AS t1 ON t1.{$vs_subject_pk} = t0.".$t_self_rel->getLeftTableFieldName()."
							INNER JOIN {$vs_subject_tablename} AS t2 ON t2.{$vs_subject_pk} = t0.".$t_self_rel->getRightTableFieldName();
						$vs_delete_sql = "AND (t1.deleted = 0 AND t2.deleted = 0)";
					}
					
					// get related rows via self relation
					$vs_sql = "
						SELECT *
						FROM ".$t_self_rel->tableName()." t0
						{$vs_sql_joins}
						WHERE
							t0.".$t_self_rel->primaryKey()." = ? {$vs_delete_sql}
					";
				}

				$qr_res = $this->opo_db->query($vs_sql, $va_params);

				while($qr_res->nextRow()) {
					$vn_left_id = $qr_res->get($t_self_rel->getLeftTableFieldName());
					$vn_right_id = $qr_res->get($t_self_rel->getRightTableFieldName());
					$vn_rel_type_id = $qr_res->get('type_id');

					$va_info = $this->getTableIndexingInfo($vs_dep_table, $vs_dep_table);
					
					if($filters = caGetOption('filter', $va_info, null)) {
						if($t_subject->isLoaded() || $t_subject->load($pn_subject_row_id)) {
							foreach($filters as $filter_fld => $filter_val) {
								$filter_vals = is_array($filter_val) ? $filter_val : preg_split('![;,]+!', $filter_val);
								if(!in_array($t_subject->get($filter_fld), $filter_vals)) {
									continue(2);
								}
							}
						}
					}
					
					// Index related records
					$va_field_nums = $va_field_names = array();
					if(isset($va_info['related']) && is_array($va_info['related']['fields'])) {
						foreach($va_info['related']['fields'] as $vs_field => $va_config) {
							$vn_field_num = $this->_getFieldNumForIndexing($t_dep, $vn_dep_table_num, $vs_field);
							$va_field_nums[$vs_field] = $vn_field_num;
							$va_field_names[$vn_field_num] = $vs_field;
						}
					}

					// Index labels from related records?
					$vb_index_labels = false;
					$va_label_info = $vn_label_table_num = null;
					$va_label_field_names = $va_label_field_nums = array();
					if ($t_label = $t_dep->getLabelTableInstance()) {
						$va_label_info = $this->getTableIndexingInfo($vs_dep_table, $t_label->tableName());
						if (is_array($va_label_info['related']['fields'] ?? null) && sizeof($va_label_info['related']['fields'])) {
							$vb_index_labels = true;
							$vn_label_table_num = $t_label->tableNum();

							foreach($va_label_info['related']['fields'] as $vs_label_field => $va_config) {
								$vn_label_field_num = $this->_getFieldNumForIndexing($t_label, $vn_label_table_num, $vs_label_field);
								$va_label_field_nums[$vs_label_field] = $vn_label_field_num;
								$va_label_field_names[$vn_label_field_num] = $vs_label_field;
							}
						}
					}

					foreach(array($vn_left_id => $vn_right_id, $vn_right_id => $vn_left_id) as $vn_id_1 => $vn_id_2) {
						$vs_key = $vn_dep_table_num.'/'.$vn_id_1.'/'.$vn_dep_table_num.'/'.$vn_id_2;
						$t_dep->load($vn_id_2);
						$va_dependent_rows[$vs_key] = array(
							'table_num' => $vn_dep_table_num,
							'row_id' => $vn_id_1,
							'field_table_num' => $vn_dep_table_num,
							'field_row_id' => $vn_id_2,
							'field_values' => $t_dep->getFieldValuesArray(),
							'relationship_type_id' => $vn_rel_type_id,
							'field_nums' => $va_field_nums,
							'field_names' => $va_field_names,
							'indexing_info' => $va_info['related']['fields'] ?? null
						);

						if ($vb_index_labels) {
							$va_labels = array_merge($t_dep->getPreferredLabels() ?? [], $t_dep->getNonPreferredLabels() ?? []);
							foreach($va_labels as $vn_x => $va_labels_by_locale) {
								foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
									foreach($va_label_list as $va_label) {
									    $vn_label_id = $va_label['label_id'];

										$vs_key = $vn_dep_table_num.'/'.$vn_id_1.'/'.$vn_label_table_num.'/'.$vn_label_id;

										$va_dependent_rows[$vs_key] = array(
											'table_num' => $vn_dep_table_num,
											'row_id' => $vn_id_1,
											'field_table_num' => $vn_label_table_num,
											'field_row_id' => $vn_label_id,
											'field_values' => $va_label,
											'relationship_type_id' => $vn_rel_type_id,
											'field_nums' => $va_label_field_nums,
											'field_names' => $va_label_field_names,
											'indexing_info' => $va_label_info['related']['fields'] ?? null
										);
									}
								}
							}
						}
					}

				}
				continue;
			}

			$va_dep_rel_indexing_tables = $this->getRelatedIndexingTables($vs_dep_table);

// Loop through tables indexed against dependency
			foreach($va_dep_rel_indexing_tables as $vs_dep_rel_table) {


				$va_table_info = $this->getTableIndexingInfo($vs_dep_table, $vs_dep_rel_table);

				if($filters = caGetOption('filter', $va_table_info, null)) {
					if($t_subject->isLoaded() || $t_subject->load($pn_subject_row_id)) {
						foreach($filters as $filter_fld => $filter_val) {
							$filter_vals = is_array($filter_val) ? $filter_val : preg_split('![;,]+!', $filter_val);
							if(!in_array($t_subject->get($filter_fld), $filter_vals)) {
								continue(2);
							}
						}
					}
				}

				if (isset($va_table_info['key']) && $va_table_info['key']) {
					$va_table_list_list = array('key' => array($vs_dep_table));
					$va_table_key_list = array();
				} else {
					$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
					$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
				}
// loop through the tables for each relationship between the subject and the dep

				$va_rel_tables_to_index_list = array();

				foreach($va_table_list_list as $vs_list_name => $va_linking_tables_config) {
					if (caIsIndexedArray($va_linking_tables_config)) {
						$va_tmp = array();
						foreach($va_linking_tables_config as $vs_t) {
							$va_tmp[$vs_t] = [];
						}
						$va_linking_tables_config = $va_tmp;
					}
					$va_linking_tables = array_keys($va_linking_tables_config);
					
					$va_linking_tables = is_array($va_linking_tables) ? array_reverse($va_linking_tables) : array();		// they come out of the conf file reversed from how we want them
					array_unshift($va_linking_tables, $vs_dep_rel_table);
					array_push($va_linking_tables, $vs_dep_table);															// the dep table name is not listed in the config file (it's redundant)

					if(in_array($vs_subject_tablename, $va_linking_tables)) {
						$va_rel_tables_to_index_list[] = $vs_dep_rel_table;
					}
				}

// update indexing for each relationship
				foreach($va_rel_tables_to_index_list as $vs_rel_table) {
					$va_indexing_info = $this->getTableIndexingInfo($vn_dep_table_num, $vs_rel_table);
					$vn_rel_table_num = Datamodel::getTableNum(preg_replace("/\.related$/", "", $vs_rel_table));
					$vn_rel_pk = Datamodel::primaryKey($vn_rel_table_num);
					$t_rel = Datamodel::getInstanceByTableNum($vn_rel_table_num, true);
					$t_rel->setDb($this->getDb());

					if (is_array($va_indexing_info['tables']) && (sizeof($va_indexing_info['tables']))) {
						$va_table_path = $va_indexing_info['tables'];
					} else {
						if ($va_indexing_info['key']) {
							$va_table_path = array(0 => array($vs_dep_table, $vs_rel_table));
						} else {
							continue;
						}
					}
					
					foreach($va_table_path as $vs_n => $va_linking_tables_config) {
					    $va_table_list = (caIsIndexedArray($va_linking_tables_config)) ? $va_linking_tables_config : array_keys($va_linking_tables_config);
						if (caIsIndexedArray($va_linking_tables_config)) {
							$va_tmp = array();
							foreach($va_linking_tables_config as $vs_t) {
								$va_tmp[$vs_t] = [];
							}
							$va_linking_tables_config = $va_tmp;
						}
						
						if (!in_array($vs_dep_table, $va_table_list)) { array_unshift($va_table_list, $vs_dep_table); }
						if (!in_array($vs_rel_table, $va_table_list)) { $va_table_list[] = $vs_rel_table; }
						if (!in_array($vs_subject_tablename, $va_table_list)) { continue; }

						$va_fields_to_index = $this->getFieldsToIndex($vn_dep_table_num, $vs_rel_table);
						$va_cv_fields_to_index = $this->getFieldsToIndex($vn_dep_table_num, $vs_rel_table, ['currentValueFields' => true]);
						
						$all_fields_to_index = $va_fields_to_index;
						foreach($va_cv_fields_to_index as $p => $pinfo) {
						    foreach($pinfo as $pf => $pi) {
						        if (!isset($all_fields_to_index[$pf])) { $all_fields_to_index[$pf] = $pi; }
						    }
						}
						
						if ($vs_rel_table == $vs_subject_tablename) {
							if (is_array($pa_changed_field_nums) && !$this->_indexedFieldsHaveChanged($all_fields_to_index, $pa_changed_field_nums)) { continue; } // check if the current field actually needs indexing; only do this check if we've been passed a list of changed fields, otherwise we have to assume that everything has changed
						}
						$va_full_path = $va_table_list;
						
						$va_rows = $this->_getRelatedRows(array_reverse($va_full_path), $va_linking_tables_config, isset($va_table_key_list[$vs_list_name]) ? $va_table_key_list[$vs_list_name] : null, $vs_subject_tablename, $pn_subject_row_id, $vs_rel_table ? $vs_rel_table : $vs_dep_table, $all_fields_to_index);

						// Check for configured "private" relationships
						$va_private_rel_types = null;
						foreach($va_linking_tables_config as $vs_linking_table => $va_linking_config) {
							if (is_array($va_linking_config) && sizeof($va_linking_config) && isset($va_linking_config['PRIVATE']) && Datamodel::isRelationship($vs_linking_table)) {
								$va_private_rel_types = caMakeRelationshipTypeIDList($vs_linking_table, $va_linking_config['PRIVATE'], []);
								break;
							}
						}
						
						if (is_array($va_rows) && sizeof($va_rows)) {
							foreach($va_rows as $va_row) {
								foreach($all_fields_to_index as $vs_field => $va_indexing_info) {
									switch($vs_field) {
										case '_count':
											$vn_fld_num = '_count';
											break;
										default:
											$vn_fld_num = $this->_getFieldNumForIndexing($t_rel, $vn_rel_table_num, $vs_field);
											break;
									}

									if (!$vn_fld_num) { continue; }

									$vn_fld_row_id = $va_row[$vn_rel_pk];
									$vn_row_id = $va_row[$vs_dep_pk];
									$vn_rel_type_id = $va_row['rel_type_id'] ?? null;
									
									$vn_private = (is_array($va_private_rel_types) && sizeof($va_private_rel_types) && in_array($vn_rel_type_id, $va_private_rel_types)) ? 1 : 0;
									
									if(
										!$vn_private && $t_rel->hasField('access') && 
										!(is_a($t_rel, "BaseLabel") && $this->opo_app_config->get('dont_check_label_access')) && 
										(!in_array((int)$va_row['access'], $public_access ?? [], true))
									) {
										$vn_private = 1;
									}
									
									$vs_key = $vn_dep_table_num.'/'.$vn_row_id.'/'.$vn_rel_table_num.'/'.$vn_fld_row_id;

									if (!isset($va_dependent_rows[$vs_key])) {
										$va_dependent_rows[$vs_key] = array(
											'table_num' => $vn_dep_table_num,
											'row_id' => $vn_row_id,
											'field_table_num' => $vn_rel_table_num,
											'field_row_id' => $vn_fld_row_id,
											'field_values' => $va_row,
											'relationship_type_id' => $vn_rel_type_id,
											'field_nums' => [],
											'field_names' => [],
											'private' => $vn_private
										);
									}
									$va_dependent_rows[$vs_key]['field_nums'][$vs_field] = $vn_fld_num;
									$va_dependent_rows[$vs_key]['field_names'][$vn_fld_num] = $vs_field;
									$va_dependent_rows[$vs_key]['indexing_info'][$vs_field] = isset($va_fields_to_index[$vs_field]) ? $va_fields_to_index[$vs_field] : null;
									
									$va_dependent_rows[$vs_key]['cv_indexing_info'][$vs_field] = null;
                                    foreach($va_cv_fields_to_index as $p => $indexing_config) {
                                        if (isset($indexing_config[$vs_field]) && is_array($indexing_config[$vs_field])) { 
                                             $va_dependent_rows[$vs_key]['cv_indexing_info'][$vs_field] = [$p => $indexing_config[$vs_field]];  
                                        }
                                    }	
								}
							}
						}
					}
				}
			}
		}
		
		return $va_dependent_rows;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _getFieldNumForIndexing($pt_rel, $pn_rel_table_num, $ps_field) {
		if(MemoryCache::contains("{$pn_rel_table_num}/{$ps_field}", 'SearchIndexerFieldNums')) {
			return MemoryCache::fetch("{$pn_rel_table_num}/{$ps_field}", 'SearchIndexerFieldNums');
		}

		$vn_fld_num = null;
		if (preg_match('!^_ca_attribute_([\d]+)$!', $ps_field, $va_matches)) {
			$vn_fld_num = 'A'.$va_matches[1];
		} else {
			$vn_fld_num = 'I'.$pt_rel->fieldNum($ps_field);
		}
		MemoryCache::save("{$pn_rel_table_num}/{$ps_field}", $vn_fld_num, 'SearchIndexerFieldNums');
		return $vn_fld_num;
	}
	# ------------------------------------------------
	/**
	 * Returns query result with rows related via tables specified in $pa_tables to the specified subject; used by
	 * _getDependentRowsForSubject() to generate dependent row set
	 */
	private function _getRelatedRows($pa_tables, $pa_linking_tables_config, $pa_table_keys, $ps_subject_tablename, $pn_row_id, $ps_table_to_index, $pa_fields_to_index) {
		$vs_key = md5(print_r($pa_tables, true)."/".print_r($pa_table_keys, true)."/".$ps_subject_tablename);

		$va_flds = $va_fld_names = $va_wheres = array();
		
		if(true) { //!MemoryCache::contains($vs_key, 'SearchIndexerRelatedRowsJoins')) {
			$vs_left_table = $vs_select_table = preg_replace("/\.related$/", "", array_shift($pa_tables));

			$t_select = Datamodel::getInstanceByTableName($vs_select_table, true);
			$vs_select_pk = $t_select->primaryKey();
			
			$t_subject = Datamodel::getInstanceByTableName($ps_subject_tablename, true);
			$vs_subject_pk = $t_subject->primaryKey();
			
			$va_joins = array();

			$vn_t = 1;
			$va_aliases = [$vs_select_table => [0 => 't0']];
			$va_alias_stack = ['t0'];
			
			foreach($pa_tables as $vs_right_table) {
				$va_rel_type_ids = array();
				$vs_rel_type_res_sql = '';
				if (($va_type_res = $pa_linking_tables_config[$vs_right_table]['types'] ?? null) && is_array($va_type_res) && sizeof($va_type_res)) {
					$va_rel_type_ids = caMakeRelationshipTypeIDList($vs_right_table, $va_type_res);
				}
				
				$vs_t = null;
			
				$t_left_table = Datamodel::getInstanceByTableName($vs_left_table, true);
				$t_right_table = Datamodel::getInstanceByTableName($vs_right_table, true);
				
				$vs_alias = $va_aliases[$vs_right_table][] = $va_alias_stack[] = "t{$vn_t}";
				$vs_prev_alias = $va_alias_stack[sizeof($va_alias_stack)-2];
				
				if (is_array($pa_table_keys) && (isset($pa_table_keys[$vs_right_table][$vs_left_table]) || isset($pa_table_keys[$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
					if (isset($pa_table_keys[$vs_left_table][$vs_right_table])) {
						$va_key_spec = $pa_table_keys[$vs_left_table][$vs_right_table];
						
						if(sizeof($va_rel_type_ids) > 0) {
							$vs_rel_type_res_sql = " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
						}
						
						$vs_join = "INNER JOIN {$vs_right_table} AS {$vs_alias} ON ({$vs_alias}.{$va_key_spec['right_key']} = {$vs_prev_alias}.{$va_key_spec['left_key']}".$vs_rel_type_res_sql;

						if (($va_key_spec['left_table_num'] ?? null) || ($va_key_spec['right_table_num'] ?? null)) {
							if ($va_key_spec['right_table_num'] ?? null) {
								$vs_join .= " AND {$vs_alias}.{$va_key_spec['right_table_num']} = ".Datamodel::getTableNum($vs_left_table);
								$vs_t = $vs_right_table;
							} else {
								$vs_join .= " AND {$vs_prev_alias}.{$va_key_spec['left_table_num']} = ".Datamodel::getTableNum($vs_right_table);
								$vs_t = $vs_left_table;
							}
						}
						$vs_join .= ')';
					} else {
						$va_key_spec = $pa_table_keys[$vs_right_table][$vs_left_table];
						
						if(sizeof($va_rel_type_ids) > 0) {
							$vs_rel_type_res_sql = " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
						}
						
						$vs_join = "INNER JOIN {$vs_right_table} AS {$vs_alias} ON ({$vs_alias}.{$va_key_spec['left_key']} = {$vs_prev_alias}.{$va_key_spec['right_key']}".$vs_rel_type_res_sql;

						if (($va_key_spec['left_table_num'] ?? null) || ($va_key_spec['right_table_num'] ?? null)) {
							if ($va_key_spec['right_table_num']) {
								$vs_join .= " AND {$vs_prev_alias}.{$va_key_spec['right_table_num']} = ".Datamodel::getTableNum($vs_right_table);
								$vs_t = $vs_left_table;
							} else {
								$vs_join .= " AND {$vs_alias}.{$va_key_spec['left_table_num']} = ".Datamodel::getTableNum($vs_left_table);
								$vs_t = $vs_right_table;
							}
						}
						$vs_join .= ')';
					}
					$vs_left = Datamodel::primaryKey($vs_left_table);
					$vs_right = Datamodel::primaryKey($vs_right_table);
					
					if (isset($va_field_names[$vs_left])) { unset($va_flds[$va_field_names[$vs_left]]); }
					$va_flds[$va_field_names[$vs_left] = "{$vs_prev_alias}.{$vs_left}"] = true;
					
					if (isset($va_field_names[$vs_right])) { unset($va_flds[$va_field_names[$vs_right]]); }
					$va_flds[$va_field_names[$vs_right] = "{$vs_alias}.{$vs_right}"] = true;

					$va_joins[] = $vs_join;

				} elseif ($va_rel = Datamodel::getOneToManyRelations($vs_left_table, $vs_right_table)) {
					$vs_t = $va_rel['many_table'] ?? null;
					
					$vs_many = Datamodel::primaryKey($va_rel['many_table'] ?? null);
					if (isset($va_field_names[$vs_many] )) { unset($va_flds[$va_field_names[$vs_many]]); }
					$va_flds[$va_field_names[$vs_many] = "{$vs_alias}.{$vs_many}"] = true;
					
					if(sizeof($va_rel_type_ids) > 0) {
						$vs_rel_type_res_sql = " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
					}
					
					if (method_exists($t_right_table, "isSelfRelationship") && ($t_right_table->isSelfRelationship())) {
						$va_joins[] = array(
										"INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.".$t_right_table->getLeftTableFieldName().$vs_rel_type_res_sql,
										"INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.".$t_right_table->getRightTableFieldName().$vs_rel_type_res_sql
									);
					} else {
						$va_joins[] = "INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.{$va_rel['many_table_field']}".$vs_rel_type_res_sql;
					}
				} elseif ($va_rel = Datamodel::getOneToManyRelations($vs_right_table, $vs_left_table)) {
					$vs_t = $va_rel['one_table'];
					
					$vs_one = Datamodel::primaryKey($va_rel['one_table']);
					
					if (isset($va_field_names[$vs_one])) { unset($va_flds[$va_field_names[$vs_one]]); }
					$va_flds[$va_field_names[$vs_one] = "{$vs_alias}.{$vs_one}"] = true;
					
					if(sizeof($va_rel_type_ids) > 0) {
						$vs_rel_type_res_sql = " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
					}
					
					if (method_exists($t_left_table, "isSelfRelationship") && ($t_left_table->isSelfRelationship())) {
						$va_joins[] = array(
										"INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.".$t_left_table->getRightTableFieldName().$vs_rel_type_res_sql,
										"INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.".$t_left_table->getLeftTableFieldName().$vs_rel_type_res_sql
									);
					} else {
						$va_joins[] = "INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.{$va_rel['many_table_field']}".$vs_rel_type_res_sql;
					}
				}

				// Add relationship type with "rel_type_id" alias to distinguish it from record type_id fields (eg. ca_objects.type_id);
				if (($t_table = Datamodel::getInstanceByTableName($vs_right_table, true)) && ($t_table->isRelationship() && $t_table->hasField('type_id'))) {
					$va_flds["{$vs_alias}.type_id rel_type_id"] = true;
				}
				$vs_left_table = $vs_right_table;
					
				$vn_t++;
			}
			
			
			// Add fields being indexed 
			if ($t_indexed_table = Datamodel::getInstanceByTableName($ps_table_to_index, true)) {
				foreach($pa_fields_to_index as $vs_f => $va_field_info) {
					if (!$t_indexed_table->hasField($vs_f)) { continue; }
					if (in_array($t_indexed_table->getFieldInfo($vs_f, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
					if (isset($va_fld_names[$vs_f]) && $va_fld_names[$vs_f]) { continue; }

					if (isset($va_fld_names[$vs_f])) { continue; }
					$vs_tmp = end($va_aliases[$ps_table_to_index]);
					$va_flds[$va_fld_names[$vs_f] = "{$vs_tmp}.{$vs_f}"] = true;
				}
			}
		
			$va_tmp = array_keys($t_select->getFormFields(true));
			foreach($va_tmp as $vn_i => $vs_v) {
				if (in_array($t_select->getFieldInfo($vs_v, 'FIELD_TYPE'), array(FT_MEDIA, FT_FILE, FT_VARS, FT_DATERANGE, FT_HISTORIC_DATERANGE, FT_TIMERANGE))) { continue; }
				if(isset($va_fld_names[$vs_v]) && $va_fld_names[$vs_v]) { continue; }

				$vs_tmp = end($va_aliases[$vs_select_table]);
				$va_flds[$va_fld_names[$vs_v] = "{$vs_tmp}.{$vs_v}"] = true;
			}
			
			$va_wheres[] = end($va_aliases[$ps_subject_tablename]).".{$vs_subject_pk} = ?";
			
			MemoryCache::save($vs_key, $va_joins, 'SearchIndexerRelatedRowsJoins');
			MemoryCache::save($vs_key, $va_flds, 'SearchIndexerRelatedFieldsJoins');
			MemoryCache::save($vs_key, $va_wheres, 'SearchIndexerRelatedWheres');
		} else {
			$va_joins = MemoryCache::fetch($vs_key, 'SearchIndexerRelatedRowsJoins');
			$va_flds = MemoryCache::fetch($vs_key, 'SearchIndexerRelatedFieldsJoins');

			$va_wheres = MemoryCache::fetch($vs_key, 'SearchIndexerRelatedWheres');
		}
		
		// process joins
		$vn_num_queries_required = 1;
		foreach($va_joins as $vn_i => $va_join_list) {
			if(is_array($va_join_list) && (sizeof($va_join_list) > $vn_num_queries_required)) {
				$vn_num_queries_required = sizeof($va_join_list);
			}
		}
		foreach($va_joins as $vn_i => $va_join_list) {
			if(!is_array($va_joins[$vn_i])) { $va_joins[$vn_i] = array($va_joins[$vn_i]); }
			$va_joins[$vn_i] = array_pad($va_joins[$vn_i], $vn_num_queries_required, $va_joins[$vn_i][0]);
		}
		
		$vs_deleted_sql = '';
		if ($t_select->hasField('deleted')) {
			$vs_deleted_sql = "(t0.deleted = 0) AND ";
		}
		
		$va_rows = [];
		for($i=0; $i < $vn_num_queries_required; $i++) {
			$vs_joins = '';
			foreach($va_joins as $va_join_list) {
				$vs_joins .= $va_join_list[$i]."\n";
			}
								
			$vs_sql = "
				SELECT ".join(", ", array_keys($va_flds))."
				FROM {$vs_select_table} t0
				{$vs_joins}
				WHERE
				{$vs_deleted_sql}
				".join(" AND ", $va_wheres);
				
			$qr_res = $this->opo_db->query($vs_sql, [$pn_row_id]);
			if (!$qr_res) {
				throw new Exception(_t("Invalid _getRelatedRows query: %1", join("; ", $this->opo_db->getErrors())));
			}
			
			while($qr_res->nextRow()) {
				$va_rows[] = $qr_res->getRow();
			}
		}

		return $va_rows;
	}
	# ------------------------------------------------
	/**
	 * Generates directed graph that represents indexing dependencies between tables in the database
	 * and then derives a list of indexed tables that might contain rows needing to be reindexed because
	 * they use the subject table as part of their indexing.
	 */
	private function _getDependencies($ps_subject_table) {
		$o_graph = new Graph();
		$va_indexed_tables = $this->getIndexedTables();

		$va_indexed_table_name_list = array();
		foreach($va_indexed_tables as $vn_table_num => $va_table_info) {
			$va_indexed_table_name_list[] = $vs_indexed_table = $va_table_info['name'];
			if ($vs_indexed_table == $ps_subject_table) { continue; }		// the subject can't be dependent upon itself

			// get list related tables used to index the subject table
			$va_related_tables = $this->getRelatedIndexingTables($vs_indexed_table);
			foreach($va_related_tables as $vs_related_table) {
				// get list of tables in indexing relationship
				// eg. if the subject is 'objects', and the related table is 'entities' then
				// the table list would be ['objects', 'objects_x_entities', 'entities']
				$va_info = $this->getTableIndexingInfo($vs_indexed_table, $vs_related_table);
				$va_table_list_list = $va_info['tables'];

				if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) {
					if ($vs_table_key = ($va_info['key'] ?? null)) {
						// Push direct relationship through one-to-many key onto table list
						$va_table_list_list = array($vs_related_table => array());
					} else {
						$va_table_list_list = array();
					}
				}

				foreach($va_table_list_list as $vs_list_name => $va_linking_tables_config) {					
					if (caIsIndexedArray($va_linking_tables_config)) {
						$va_tmp = array();
						foreach($va_linking_tables_config as $vs_t) {
							$va_tmp[$vs_t] = [];
						}
						$va_linking_tables_config = $va_tmp;
					}
					$va_table_list = array_keys($va_linking_tables_config);
				
					array_unshift($va_table_list,$vs_indexed_table);
					array_push($va_table_list, $vs_related_table);

					if (in_array($ps_subject_table, $va_table_list)) {			// we only care about indexing relationships that include the subject table
						// for each each related table record the intervening tables in the graph
						$vs_last_table = '';
						foreach($va_table_list as $vs_tablename) {
							$o_graph->addNode($vs_tablename);
							if ($vs_last_table) {
								if ($va_rel = Datamodel::getOneToManyRelations($vs_tablename, $vs_last_table)) {		// determining direction of relationship (directionality is from the "many" table to the "one" table
									$o_graph->addRelationship($vs_tablename, $vs_last_table, 10, true);
								} else {
									$o_graph->addRelationship($vs_last_table, $vs_tablename, 10, true);
								}
							}
							$vs_last_table = $vs_tablename;
						}
					}
				}
			}
		}

		$va_topo_list = $o_graph->getTopologicalSort();

		$va_deps = array();
		foreach($va_topo_list as $vs_tablename) {
			if ($vs_tablename == $ps_subject_table) { continue; }
			if (!in_array($vs_tablename, $va_indexed_table_name_list)) { continue; }

			$va_deps[] = $vs_tablename;
		}

		// Any self indexing?
		$va_self_info = $this->getTableIndexingInfo($ps_subject_table, $ps_subject_table);
		if (isset($va_self_info['related'])) {
			$va_deps[] = $ps_subject_table;
		}

		// Is this a self relation?
		$t_subject = Datamodel::getInstanceByTableName($ps_subject_table, true);
		if (method_exists($t_subject, 'isSelfRelationship') && $t_subject->isSelfRelationship()) {
			$va_deps[] = $t_subject->getLeftTableName();
		}
		return $va_deps;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _getQueriesForRelatedRows($pt_subject, $pn_subject_row_id, $pt_rel, $pb_reindex_mode, $pa_options=null) {
	    $for_current_value_reindex = caGetOption('forCurrentValueReindex', $pa_options, false);
		$vs_subject_tablename = $pt_subject->tableName();
		$vs_subject_pk = $pt_subject->primaryKey();
		$vs_related_table = $pt_rel->tableName();
		$vs_related_pk = $pt_rel->primaryKey();
		
		$pb_force_related = caGetOption('forceRelated', $pa_options, false);
		$pa_restrict_to_types = caGetOption('restrictToTypes', $pa_options, false);
		
		$vb_can_do_incremental_indexing = $this->opo_engine->can('incremental_reindexing') ? true : false;
		
		
		$va_table_info = $this->getTableIndexingInfo($vs_subject_tablename, $pb_force_related ? "{$vs_related_table}.related" : $vs_related_table);
		
		$va_queries = [];
		$va_linking_tables_per_query = $va_linking_table_config_per_query = [];
			
		if (!$pb_force_related && ($vs_subject_tablename == $vs_related_table)) {
			// self-relation
			if (!($vs_self_rel_table_name = $pt_rel->getSelfRelationTableName())) { return null; }
			$t_self_rel = Datamodel::getInstanceByTableName($vs_self_rel_table_name, true);
			$va_proc_field_list = array();
			
			$va_self_info = $this->getTableIndexingInfo($vs_subject_tablename, $vs_subject_tablename);
			if (!is_array($va_fields_to_index = $va_self_info['related']['fields'])) { $va_fields_to_index = []; }
			if (!is_array($va_cv_fields_to_index = ($va_self_info['related']['current_values'] ?? null))) { $va_cv_fields_to_index = []; }
			
			$va_field_list = array_keys($va_fields_to_index);

			$vn_field_list_count = sizeof($va_field_list);
			for($vn_i=0; $vn_i < $vn_field_list_count; $vn_i++) {
				if ($va_field_list[$vn_i] == '_count') { continue; }
				if (substr($va_field_list[$vn_i], 0, 14) === '_ca_attribute_') { continue; }
				if (!trim($va_field_list[$vn_i])) { continue; }
				$va_proc_field_list[$vn_i] = $vs_related_table.'.'.$va_field_list[$vn_i];
			}
			$va_proc_field_list[] = $vs_related_table.'.'.$vs_related_pk;
			if ($vs_self_rel_table_name) { $va_proc_field_list[] = $vs_self_rel_table_name.'.type_id rel_type_id'; }
			if ($pt_rel->hasField('type_id')) { $va_proc_field_list[] = $vs_related_table.'.type_id'; }
			if ($pt_rel->hasField('access')) { $va_proc_field_list[] = $vs_related_table.'.access'; }

			$vs_delete_sql = $pt_rel->hasField('deleted') ? " AND {$vs_related_table}.deleted = 0" : '';
			$vs_sql = "
				SELECT ".join(",", $va_proc_field_list)."
				FROM {$vs_related_table}
				INNER JOIN {$vs_self_rel_table_name} ON {$vs_self_rel_table_name}.".$t_self_rel->getLeftTableFieldName()." = {$vs_related_table}.{$vs_related_pk}
				WHERE
					(".$vs_self_rel_table_name.'.'.$t_self_rel->getRightTableFieldName().' = ?)
					'.$vs_delete_sql.'
				UNION
			
				SELECT '.join(",", $va_proc_field_list)."
				FROM {$vs_related_table}
				INNER JOIN {$vs_self_rel_table_name} ON {$vs_self_rel_table_name}.".$t_self_rel->getRightTableFieldName()." = {$vs_related_table}.{$vs_related_pk}
				WHERE
					(".$vs_self_rel_table_name.'.'.$t_self_rel->getLeftTableFieldName().' = ?)
					'.$vs_delete_sql.'
			';
			$va_params = array($pn_subject_row_id, $pn_subject_row_id);
			
			$va_va_linking_table_config_per_query[] = [
				$vs_self_rel_table_name => []	
			];

			$va_queries[] = array('sql' => $vs_sql, 'params' => $va_params);
		} else {
			if (!is_array($va_fields_to_index = $this->getFieldsToIndex($vs_subject_tablename, $pb_force_related ? "{$vs_related_table}.related" : $vs_related_table))) { $va_fields_to_index = []; }
			if (!is_array($va_cv_fields_to_index = $this->getFieldsToIndex($vs_subject_tablename, $pb_force_related ? "{$vs_related_table}.related" : $vs_related_table, ['currentValueFields' => true]))) { $va_cv_fields_to_index = []; }
			$va_field_list = array_keys($va_fields_to_index);

			$va_table_list_list = $va_table_key_list = array();

			if (isset($va_table_info['key']) && $va_table_info['key']) {
				$va_table_list_list = array('key' => array($vs_related_table));
				$va_table_key_list = array();
			} else {
				$va_table_list_list = isset($va_table_info['tables']) ? $va_table_info['tables'] : null;
				$va_table_key_list = isset($va_table_info['keys']) ? $va_table_info['keys'] : null;
			}
			
			if (!is_array($va_table_list_list) || !sizeof($va_table_list_list)) {  return null; }
			foreach($va_table_list_list as $vs_list_name => $va_linking_tables_config) {
				if (caIsIndexedArray($va_linking_tables_config)) {
				    $va_linking_tables = array_values($va_linking_tables_config);
					$va_tmp = [];
					foreach($va_linking_tables_config as $vs_t) {
						$va_tmp[$vs_t] = [];
					}
					$va_linking_tables_config = $va_tmp;
				} else {
				    $va_linking_tables = array_keys($va_linking_tables_config);
				}
				
				
				$va_linking_table_config_per_query[] = $va_linking_tables_config;
		
				array_push($va_linking_tables, $vs_related_table);
				$vs_left_table = $vs_subject_tablename;

				$va_joins = [];
				$vs_rel_type_id_fld = $vs_type_id_fld = null;
			
				$vn_t = 1;
				$va_aliases = [$vs_subject_tablename => [0 => 't0']];
				$va_alias_stack = ['t0'];
			
				foreach($va_linking_tables as $vs_right_table) {
					$va_rel_type_ids = array();
					$vs_rel_type_res_sql = '';
					if (($va_type_res = $va_linking_tables_config[$vs_right_table]['types'] ?? null) && is_array($va_type_res) && sizeof($va_type_res)) {
						$va_rel_type_ids = caMakeRelationshipTypeIDList($vs_right_table, $va_type_res);
					}
					
					if (is_array($va_table_key_list) && (isset($va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table]) || isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table]))) {		// are the keys for this join specified in the indexing config?
												
						$vs_alias = $va_aliases[$vs_right_table][] = $va_alias_stack[] = "t{$vn_t}";
						$vs_prev_alias = $va_alias_stack[sizeof($va_alias_stack)-2];
				
						if(sizeof($va_rel_type_ids) > 0) {
							$vs_rel_type_res_sql = " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
						}

						if (Datamodel::getFieldInfo($vs_right_table, 'deleted')) {
						    $vs_rel_type_res_sql .= " AND {$vs_alias}.deleted = 0";
						}
						
						if (isset($va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table])) {
							$va_key_spec = $va_table_key_list[$vs_list_name][$vs_left_table][$vs_right_table];
							$vs_join = "INNER JOIN {$vs_right_table} AS {$vs_alias} ON ({$vs_alias}.{$va_key_spec['right_key']} = {$vs_prev_alias}.{$va_key_spec['left_key']}".$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table], $vs_alias);
							if (($va_key_spec['left_table_num'] ?? null) || ($va_key_spec['right_table_num'] ?? null)) {
								if ($va_key_spec['right_table_num']) {
									$vs_join .= " AND {$vs_alias}.{$va_key_spec['right_table_num']} = ".Datamodel::getTableNum($vs_left_table);
								} else {
									$vs_join .= " AND {$vs_prev_alias}.{$va_key_spec['left_table_num']} = ".Datamodel::getTableNum($vs_right_table);
								}
								
								if(Datamodel::getFieldNum($vs_right_table, 'deleted')) {
									$vs_join .= " AND {$vs_prev_alias}.deleted = 0";
								}
							}
							$vs_join .= ")";
						} else {
							$va_key_spec = $va_table_key_list[$vs_list_name][$vs_right_table][$vs_left_table];
							$vs_join = "INNER JOIN {$vs_right_table} AS {$vs_alias} ON ({$vs_alias}.{$va_key_spec['left_key']} = {$vs_prev_alias}.{$va_key_spec['right_key']}".$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table], $vs_alias);
							if ($va_key_spec['left_table_num'] || $va_key_spec['right_table_num']) {
								if ($va_key_spec['right_table_num'] ?? null) {
									$vs_join .= " AND {$vs_prev_alias}.{$va_key_spec['right_table_num']} = ".Datamodel::getTableNum($vs_right_table);
								} else {
									$vs_join .= " AND {$vs_alias}.{$va_key_spec['left_table_num']} = ".Datamodel::getTableNum($vs_left_table);
								}
								
								if(Datamodel::getFieldNum($vs_right_table, 'deleted')) {
									$vs_join .= " AND {$vs_alias}.deleted = 0";
								}
							}
							$vs_join .= ")";
						}

						if (($pt_rel_instance = Datamodel::getInstanceByTableName($vs_right_table, true)) && method_exists($pt_rel_instance, "isRelationship") && $pt_rel_instance->isRelationship() && $pt_rel_instance->hasField('type_id')) {
							$vs_rel_type_id_fld = "{$vs_alias}.type_id";
						}
						
						$va_joins[] = [$vs_join];
					} else {
						if ($va_rel = Datamodel::getOneToManyRelations($vs_left_table, $vs_right_table)) {
							$vs_alias = $va_aliases[$vs_right_table][] = $va_alias_stack[] = "t{$vn_t}";
							$vs_prev_alias = $va_alias_stack[sizeof($va_alias_stack)-2];
						
							if(sizeof($va_rel_type_ids) > 0) {
								$vs_rel_type_res_sql .= " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
							}
							
							if (Datamodel::getFieldInfo($vs_right_table, 'deleted')) {
								$vs_rel_type_res_sql .= " AND {$vs_alias}.deleted = 0";
							}
						
							if(Datamodel::isSelfRelationship($va_rel['many_table'])) {
								$t_self_rel = Datamodel::getInstanceByTableName($va_rel['many_table'], true);
							
								$va_joins[] = [
									"INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.".$t_self_rel->getLeftTableFieldName().$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table], $vs_alias),
									"INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.".$t_self_rel->getRightTableFieldName().$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table], $vs_alias)
								];
										
								if ($t_self_rel->hasField('type_id')) {
									$vs_rel_type_id_fld = "{$vs_alias}.type_id";
								}
							
							} else {
								if (($pt_rel_instance = Datamodel::getInstanceByTableName($va_rel['many_table'], true)) && method_exists($pt_rel_instance, "isRelationship") && $pt_rel_instance->isRelationship() && $pt_rel_instance->hasField('type_id')) {
									$vs_rel_type_id_fld = "{$vs_alias}.type_id";
								} elseif(($vn_t > 0) && ($vs_related_table == $va_rel['many_table']) && is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types) && $pt_rel_instance->hasField('type_id'))  {
								    $vs_type_id_fld = "{$vs_alias}.type_id";
								    $vs_rel_type_res_sql .= " AND {$vs_type_id_fld} IN (".join(',', $pa_restrict_to_types).")";
								}
								$va_joins[] = ["INNER JOIN {$va_rel['many_table']} AS {$vs_alias} ON {$vs_prev_alias}.{$va_rel['one_table_field']} = {$vs_alias}.{$va_rel['many_table_field']}".$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table] ?? null, $vs_alias)];
							}
						} elseif ($va_rel = Datamodel::getOneToManyRelations($vs_right_table, $vs_left_table)) {
							$vs_alias = $va_aliases[$vs_right_table][] = $va_alias_stack[] = "t{$vn_t}";
							$vs_prev_alias = $va_alias_stack[sizeof($va_alias_stack)-2];
						
							if(sizeof($va_rel_type_ids) > 0) {
								$vs_rel_type_res_sql .= " AND {$vs_alias}.type_id IN (".join(",", $va_rel_type_ids).")";
							}
							
							if (Datamodel::getFieldInfo($vs_right_table, 'deleted')) {
								$vs_rel_type_res_sql .= " AND {$vs_alias}.deleted = 0";
							}
						
							if(Datamodel::isSelfRelationship($va_rel['many_table'])) {
								$t_self_rel = Datamodel::getInstanceByTableName($va_rel['many_table'], true);
							
								$va_joins[] = [
												"INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.".$t_self_rel->getRightTableFieldName().$vs_rel_type_res_sql,
												"INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.".$t_self_rel->getLeftTableFieldName().$vs_rel_type_res_sql
											];
										
								if ($t_self_rel->hasField('type_id')) {
									$vs_rel_type_id_fld = "{$vs_alias}.type_id";
								}
							} else {
								if (($pt_rel_instance = Datamodel::getInstanceByTableName($va_rel['one_table'], true)) && method_exists($pt_rel_instance, "isRelationship") && $pt_rel_instance->isRelationship() && $pt_rel_instance->hasField('type_id')) {
									$vs_rel_type_id_fld = "{$vs_prev_alias}.type_id";
								} elseif(($vn_t > 0) && ($vs_related_table == $va_rel['one_table']) && is_array($pa_restrict_to_types) && sizeof($pa_restrict_to_types) && $pt_rel_instance->hasField('type_id'))  {
								    $vs_type_id_fld = "{$vs_alias}.type_id";
								    $vs_rel_type_res_sql .= " AND {$vs_type_id_fld} IN (".join(',', $pa_restrict_to_types).")";
								}
								$va_joins[] = ["INNER JOIN {$va_rel['one_table']} AS {$vs_alias} ON {$vs_alias}.{$va_rel['one_table_field']} = {$vs_prev_alias}.{$va_rel['many_table_field']}".$vs_rel_type_res_sql.self::_genQueryFilters($vs_right_table, $va_table_info, $va_linking_tables_config[$vs_right_table] ?? null, $vs_alias)];
								
							}
						}
					}
					$vs_left_table = $vs_right_table;
				
					$vn_t++;
				}

				$va_proc_field_list = array();
				$vn_field_list_count = sizeof($va_field_list);
				for($vn_i=0; $vn_i < $vn_field_list_count; $vn_i++) {
					if ($va_field_list[$vn_i] == '_count') {
						continue;
					}
					if (substr($va_field_list[$vn_i], 0, 14) === '_ca_attribute_') { continue; }
					if (!trim($va_field_list[$vn_i])) { continue; }
					$va_proc_field_list[$vn_i] = $va_aliases[$vs_related_table][sizeof($va_aliases[$vs_related_table])-1].'.'.$va_field_list[$vn_i];
				}
				$va_proc_field_list[] = $va_aliases[$vs_related_table][sizeof($va_aliases[$vs_related_table])-1].'.'.$vs_related_pk;
				if ($vs_rel_type_id_fld) { $va_proc_field_list[] = $vs_rel_type_id_fld.' rel_type_id'; }
				if ($vs_type_id_fld) { $va_proc_field_list[] = $vs_type_id_fld; }
				if (isset($va_rel['many_table']) && $va_rel['many_table']) {
					$va_proc_field_list[] = $va_aliases[$va_rel['many_table']][sizeof($va_aliases[$va_rel['many_table']])-1].'.'.$va_rel['many_table_field'];
				}

				// process joins
				$vn_num_queries_required = 1;
				foreach($va_joins as $vn_i => $va_join_list) {
					if(is_array($va_join_list) && (sizeof($va_join_list) > $vn_num_queries_required)) {
						$vn_num_queries_required = sizeof($va_join_list);
					}
				}
				if ($vn_num_queries_required > 1) {
					foreach($va_joins as $vn_i => $va_join_list) {
						if(!is_array($va_joins[$vn_i])) { $va_joins[$vn_i] = array($va_joins[$vn_i]); }
						$va_joins[$vn_i] = array_pad($va_joins[$vn_i], $vn_num_queries_required, $va_joins[$vn_i][0]);
					}
				}
			
				$vs_deleted_sql = '';
				if ($pt_subject->hasField('deleted')) {
					$vs_deleted_sql = "(t0.deleted = 0) AND ";
				}
				if($pt_rel->hasField('access') && ($rt = $pt_rel->tableName()) && isset($va_aliases[$rt][0])) {
					$va_proc_field_list[] = $va_aliases[$rt][0].'.access';
				}
				for($i=0; $i < $vn_num_queries_required; $i++) {
					$vs_joins = '';
					foreach($va_joins as $va_join_list) {
						$vs_joins .= (is_array($va_join_list) ? $va_join_list[$i] : $va_join_list)."\n";
					}
					$vs_sql = "
						SELECT DISTINCT ".join(",", $va_proc_field_list)."
						FROM ".$vs_subject_tablename." AS t0
						{$vs_joins}
						WHERE
							{$vs_deleted_sql}
							(".$va_aliases[$vs_subject_tablename][0].'.'.$vs_subject_pk.' = ?)
					';

					$va_queries[] = array('sql' => $vs_sql, 'params' => array($pn_subject_row_id));
				}
			}
		}
		return ['queries' => $va_queries, 'fields_to_index' => $va_fields_to_index, 'current_value_fields_to_index' => $va_cv_fields_to_index, 'field_list' => $va_field_list, 'table_info' => $va_table_info, 'linking_table_config_per_query' => $va_linking_table_config_per_query];
	}
	# ------------------------------------------------
	/**
	 * Generate join clauses for table-level filters. 
	 * Filters may be specified directly in a table block or in a linking table entry.
	 *
	 * @param string $table Table name
	 * @param array $table_info Table configuration
	 * @param array $linking_tables_config Config from linking tables block 
	 * @param string $alias Table alias
	 *
	 * @return string
	 */
	private static function _genQueryFilters(string $table, array $table_info, ?array $linking_tables_config,  string $alias) {
		$filter_sql = null;
		if($filters = caGetOption('filter', $table_info, caGetOption('filter', $linking_tables_config, null))) {
			$filter_criteria = [];
			foreach($filters as $k => $v) {
				if(!($field_info = Datamodel::getFieldInfo($table, $k))) { continue; }
				switch($k) {
					case 'status':
					case 'access':
						$criteria = [];
						$vals = is_array($v) ? $v : preg_split('![;,]+!', $v);
						foreach($vals as $t) {
							$criteria[] = (int)$t;
						}
						if(sizeof($criteria) > 0) {
							$filter_criteria[] = "({$alias}.{$k} IN (".join(', ', $criteria)."))";
						}
						break;
					case 'is_preferred':
						$value = (bool)$v ? 1 : 0;
						$filter_criteria[] = "{$alias}.{$k} = {$value}";
						break;
					case 'type_id':
						$type_criteria = [];
						$types = is_array($v) ? $v : preg_split('![;,]+!', $v);
						foreach($types as $t) {
							if(is_numeric($t)) {
								$type_criteria[] = (int)$t;
							} elseif(!empty($field_info['LIST_CODE']) && ($type_id = caGetListItemID($field_info['LIST_CODE'], $t))) {
								$type_criteria[] = $type_id;
							}
						}
						if(sizeof($type_criteria) > 0) {
							$filter_criteria[] = "({$alias}.{$k} IN (".join(', ', $type_criteria).") OR {$alias}.{$k} IS NULL)";
						}
						break;
				}
			}
			if (sizeof($filter_criteria) > 0) {
				$filter_sql = ' AND ('.join(' AND ', $filter_criteria).')';
			}
		}
		return $filter_sql;
	}
	# ------------------------------------------------
	/**
	 * Generate count indexing  the number of relationships on the subject, broken out by type
	 *
	 * @param BaseModel $pt_subject
	 * @param int $pn_subject_row_id
	 * @param BaseModel $pt_rel
	 * @param bool $pb_reindex_mode
	 * @param array $pa_options No options are currently supported
	 *
	 * @return void
	 * @throws ApplicationException
	 */
	private function _doCountIndexing($pt_subject, $pn_subject_row_id, $pt_rel, $pb_reindex_mode, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = []; }
		
		$va_query_info = $this->_getQueriesForRelatedRows($pt_subject, $pn_subject_row_id, $pt_rel, $pb_reindex_mode);
		
		$va_queries 			= $va_query_info['queries'] ?? null;
		$va_fields_to_index 	= $va_query_info['fields_to_index'] ?? null;

		if(isset($va_fields_to_index['_count']) && is_array($va_fields_to_index['_count'])) {
			$vn_subject_table_num = $pt_subject->tableNum();
			$vn_related_table_num = $pt_rel->tableNum();
			if (!is_array($va_rel_field_info = $va_fields_to_index['_count'])) { $va_rel_field_info = []; }
		
			foreach($va_queries as $va_query) {
				$vs_sql = $va_query['sql'];
				$va_params = $va_query['params'];

				$qr_res = $this->opo_db->query($vs_sql, $va_params);

				if ($this->opo_db->numErrors()) {
					// Shouldn't ever happen
					throw new ApplicationException(_t("SQL error while getting content for index of related fields: %1; SQL was %2", $this->opo_db->getErrors(), $vs_sql));
				}

				$va_counts = $this->_getInitedCountList($pt_rel); 
				
				while($qr_res->nextRow()) {
					$vn_rel_type_id = (int)$qr_res->get('rel_type_id');
					$vn_row_type_id = (int)$qr_res->get('type_id');

					$va_counts['_total']++;

					if ($vn_rel_type_id || $vn_row_type_id || !$pt_rel->hasField('type_id')) {
						$va_counts[$pt_rel->isRelationship() ? $vn_rel_type_id : $vn_row_type_id]++;
					}
				}
				// index counts?
				foreach($va_counts as $vs_key => $vn_count) {
					if ($pb_reindex_mode) {
						$this->opo_engine->indexField($vn_related_table_num, 'COUNT', 0, [(int)$vn_count], array_merge($va_rel_field_info, ['relationship_type_id' => ($vs_key != '_total') ? $vs_key : 0]));
						$this->_genIndexInheritance($pt_subject, $pt_rel, 'COUNT', $pn_subject_row_id, 0, [(int)$vn_count], array_merge($va_rel_field_info, ['relationship_type_id' => ($vs_key != '_total') ? $vs_key : 0]));
					} else {
						if ((caGetOption('CHILDREN_INHERIT', $pa_options, false) || (array_search('CHILDREN_INHERIT', $pa_options, true) !== false)) && (is_array($va_ids = $pt_subject->getHierarchy($pn_subject_row_id, ['idsOnly' => true])))) {
							$this->opo_engine->updateIndexingInPlace($vn_subject_table_num, $va_ids, $vn_related_table_num, 'COUNT', null, 0, $vn_count, array_merge($va_rel_field_info, ['relationship_type_id' => ($vs_key != '_total') ? $vs_key : 0]));
						} else {
							$this->opo_engine->updateIndexingInPlace($vn_subject_table_num, [$pn_subject_row_id], $vn_related_table_num, 'COUNT', null, 0, $vn_count, array_merge($va_rel_field_info, ['relationship_type_id' => ($vs_key != '_total') ? $vs_key : 0]));
						}
					}
				}
			}
		}
	}
	# ------------------------------------------------
	/**
	 * Create initialized count array. The array contains slots for type id or relationship id and total ("_total").
	 * All values are initialized to zero.
	 *
	 * @param BaseModel $pt_rel
	 * @return array Initialized count array, with keys set to type ids and '_total'; values are all zero
	 */
	private function _getInitedCountList($pt_rel) {
		$va_counts = ['_total' => 0];
						
		// Set counts for all types to zero
		$va_type_ids = null;
		if (method_exists($pt_rel, 'isRelationship') && $pt_rel->isRelationship()) {
			$va_type_ids = $pt_rel->getRelationshipTypes(null, null, ['idsOnly' => true]);
		} elseif (method_exists($pt_rel, 'getTypeList')) {
			$va_type_ids = $pt_rel->getTypeList(['idsOnly' => true]);
		} 
		if (is_array($va_type_ids)) {
			foreach($va_type_ids as $vn_type_id) {
				$va_counts[$vn_type_id] = 0;
			}
		}
		
		return $va_counts;
	}
	# ------------------------------------------------
	/**
	 * 
	 *
	 */
	private static function _searchConfigFieldEntryToIndexFieldNums($table_num, $entry, $options=null) {
	    $prefix = (isset($options['policy']) && ($p = $options['policy'])) ? "CV{$p}_" : '';
	    if (isset(self::$s_search_config_field_entry_to_index_field_nums_cache[$table_num][$entry][$prefix])) {
	        return self::$s_search_config_field_entry_to_index_field_nums_cache[$table_num][$entry][$prefix];
	    }
	    if (strlen($fn = Datamodel::getFieldNum($table_num, $entry)) > 0) {
            return self::$s_search_config_field_entry_to_index_field_nums_cache[$table_num][$entry][$prefix] = ["{$prefix}I{$fn}"]; 
        } elseif(preg_match("!^_ca_attribute_([\d]+)$!", $entry, $m)) {
            $ids = ca_metadata_elements::getElementsForSet($m[1], ['idsOnly' => true]);
            $field_nums = [];
            foreach($ids as $id) {
                $field_nums[] = "{$prefix}A{$id}"; 
            }
            return self::$s_search_config_field_entry_to_index_field_nums_cache[$table_num][$entry][$prefix] = $field_nums;
        }
        return self::$s_search_config_field_entry_to_index_field_nums_cache[$table_num][$entry][$prefix] = null;
	}
	# ------------------------------------------------
	/**
	 * 
	 *
	 */
	public function updateCurrentValueIndexing($policy, $subject_table_num, $subject_row_id, $values, $options=null) {
	    $indexing_tables = $this->getRelatedIndexingTables($subject_table_num);
	    foreach($indexing_tables as $rel_table) {
	        if(is_array($fields_to_index = $this->getFieldsToIndex($subject_table_num, $rel_table, ['currentValueFields' => true]))) {
	            $field_nums = [];
	            foreach($fields_to_index as $p => $pinfo) {
	                foreach($pinfo as $f => $finfo) {
	                	if(is_array($fn = self::_searchConfigFieldEntryToIndexFieldNums($rel_table, $f, ['policy' => $p]))) {
	                        $field_nums = array_merge($field_nums, $fn);
	                    }
	                }
	            }
	            if (sizeof($field_nums) === 0) { continue; }
	            $this->opo_engine->removeRowIndexing($subject_table_num, $subject_row_id, Datamodel::getTableNum($rel_table), $field_nums);
	        }
	    }
	    $this->indexRow($subject_table_num, $subject_row_id, $values, false, null, null, ['forCurrentValueReindex' => $policy]);
	}
	# ------------------------------------------------
}
