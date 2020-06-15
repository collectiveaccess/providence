<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/SearchIndexer.php : indexing of content for search
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2018 Whirl-i-Gig
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

/**
 *
 */

require_once(__CA_LIB_DIR__."/Search/SearchIndexer.php");

class CustomSearchIndexer extends SearchIndexer {
	# ------------------------------------------------

	private $opa_dependencies_to_update;

	private $opo_metadata_element = null;

	/**
	 * @var null|ca_search_indexing_queue
	 */
	private $opo_search_indexing_queue = null;
    
	# ------------------------------------------------
	/**
	 * Constructor takes Db() instance which it uses for all database access. You should pass an instance in
	 * because all the database accesses need to be in the same transactional context as whatever else you're doing. In
	 * the case of BaseModel::insert(), BaseModel::update() and BaseModel::delete()they're always in a transactional context
	 * so this is critical. If you don't pass an Db() instance then the constructor creates a new one, which is useful for
	 * cases where you're reindexing and not in a transaction.
	 */
	public function __construct($opo_db=null, $ps_engine=null) {
		parent::__construct($opo_db, $ps_engine);

		$this->opo_metadata_element = new ca_metadata_elements();
		$this->opo_search_indexing_queue = new ca_search_indexing_queue();
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
		if (!$pb_reindex_mode && is_array($pa_changed_fields) && !sizeof($pa_changed_fields)) { return; }	// don't bother indexing if there are no changed fields

		$vs_subject_tablename = $this->opo_datamodel->getTableName($pn_subject_table_num);
		$t_subject = $this->opo_datamodel->getInstanceByTableName($vs_subject_tablename, true);
		$t_subject->setDb($this->getDb());	// force the subject instance to use the same db connection as the indexer, in case we're operating in a transaction

		// Prevent endless recursive reindexing
		if (is_array($pa_exclusion_list[$pn_subject_table_num]) && (isset($pa_exclusion_list[$pn_subject_table_num][$pn_subject_row_id]))) { return; }

		if(caGetOption('queueIndexing', $pa_options, false) && !$t_subject->getAppConfig()->get('disable_out_of_process_search_indexing')) {
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

		//
		// index fields in subject table itself
		//
		$va_fields_to_index = $this->getFieldsToIndex($pn_subject_table_num);

		if(is_array($va_fields_to_index)) {

			foreach($va_fields_to_index as $vs_k => $va_data) {
				if (preg_match('!^ca_attribute_(.*)$!', $vs_k, $va_matches)) {
					unset($va_fields_to_index[$vs_k]);
					if ($va_data['DONT_INDEX']) {	// remove attribute from indexing list
						unset($va_fields_to_index['_ca_attribute_'.$va_matches[1]]);
					} else {
						if ($vn_element_id = ca_metadata_elements::getElementID($va_matches[1])) {
							$va_fields_to_index['_ca_attribute_'.$vn_element_id] = $va_data;
						}
					}
				}
			}

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
				if(is_array($va_data['BOOST'])) {
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

					if($va_data['DONT_INDEX'] && is_array($va_data['DONT_INDEX'])){
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
					$this->_indexAttribute($t_subject, $pn_subject_row_id, $va_matches[1], $va_data);

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
					if (((isset($va_data['INDEX_ANCESTORS']) && $va_data['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_data))) {
						if ($t_subject && $t_subject->isHierarchical()) {
							$vn_fld_num = $t_subject->fieldNum($vs_field);
							if ($va_hier_values = $this->_genHierarchicalPath($pn_subject_row_id, $vs_field, $t_subject, $va_data)) {
								$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_hier_values['values'], $va_data);
								$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_hier_values['values'], $va_data);
								
								if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_data, false) !== false) {
									$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('DONT_TOKENIZE' => 1, 'TOKENIZE' => 1)));
									$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$va_hier_values['path']], array_merge($va_data, array('DONT_TOKENIZE' => 1, 'TOKENIZE' => 1)));
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
							continue;
						}
					}

					// specialized identifier (idno) processing; uses IDNumbering plugin to generate searchable permutations of identifier
					if (((isset($va_data['INDEX_AS_IDNO']) && $va_data['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_data)) && method_exists($t_subject, "getIDNoPlugInInstance") && ($o_idno = $t_subject->getIDNoPlugInInstance())) {
						$va_values = $o_idno->getIndexValues($pa_field_data[$vs_field], $va_data);
						$vn_fld_num = $t_subject->fieldNum($vs_field);
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_values, $va_data);
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_values, $va_data);
						continue;
					}
					// specialized mimetype processing
					if (((isset($va_data['INDEX_AS_MIMETYPE']) && $va_data['INDEX_AS_MIMETYPE']) || in_array('INDEX_AS_MIMETYPE', $va_data))) {
						$va_values = [];
						if ($vs_typename = Media::getTypenameForMimetype($pa_field_data[$vs_field])) {
							$va_values[] = $vs_typename;
						}
						$vn_fld_num = $t_subject->fieldNum($vs_field);
						
						// Index mimetype as-is
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$pa_field_data[$vs_field]], array_merge($va_data, array('DONT_TOKENIZE' => true)));
												
						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, $va_values, $va_data);
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, $va_values, $va_data);
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

						if (isset($va_field_list[$vs_field]['LIST_CODE']) && $va_field_list[$vs_field]['LIST_CODE']) {
							// Is reference to list item so index preferred label values
							$t_item = new ca_list_items((int)$pa_field_data[$vs_field]);
							$va_labels = $t_item->getPreferredDisplayLabelsForIDs(array((int)$pa_field_data[$vs_field]), array('returnAllLocales' => true));

							foreach($va_labels as $vn_label_row_id => $va_labels_per_row) {
								foreach($va_labels_per_row as $vn_locale_id => $va_label_list) {
									foreach($va_label_list as $vs_label) {
										$va_content[$vs_label] = true;
									}
								}
							}
							$va_content[$t_item->get('idno')] = true;
						}  else {
							// is this field related to something?
							if (is_array($va_rels = $this->opo_datamodel->getManyToOneRelations($vs_subject_tablename)) && ($va_rels[$vs_field])) {
								if (isset($va_rels[$vs_field])) {
									if ($pa_changed_fields[$vs_field]) {
										$pb_reindex_mode = true;	// trigger full reindex of record so it reflects text of related item (if so indexed)
									}
								}

								$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$pn_content], $va_data);
								$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$pn_content], $va_data);
							}
						}
						$va_content[$pa_field_data[$vs_field]] = true;

						$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, array_keys($va_content), $va_data);
						$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, array_keys($va_content), $va_data);
						continue;
					}

					$this->opo_engine->indexField($pn_subject_table_num, "I{$vn_fld_num}", $pn_subject_row_id, [$pn_content], $va_data);
					$this->_genIndexInheritance($t_subject, null, "I{$vn_fld_num}", $pn_subject_row_id, $pn_subject_row_id, [$pn_content], $va_data);
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
		if (!$vb_can_do_incremental_indexing || $pb_reindex_mode) {
			if (is_array($va_related_tables = $this->getRelatedIndexingTables($pn_subject_table_num))) {
				if (!$vb_started_indexing) {
					$this->opo_engine->startRowIndexing($pn_subject_table_num, $pn_subject_row_id);
					$vb_started_indexing = true;
				}

				// Needs self-indexing?
				$va_self_info = $this->getTableIndexingInfo($vs_subject_tablename, $vs_subject_tablename);

				if (is_array($va_self_info['related']['fields']) && sizeof($va_self_info['related']['fields']) && !in_array($vs_subject_tablename, $va_related_tables)) {
					$va_related_tables[] = $vs_subject_tablename;
				}
				
                $va_restrict_self_indexing_to_types = null;
                if (is_array($va_self_info['related']['types']) && sizeof($va_self_info['related']['types'])) {
                    $va_restrict_self_indexing_to_types = caMakeTypeIDList($vs_subject_tablename, $va_self_info['related']['types']);
                }

				foreach($va_related_tables as $vs_related_table) {
				    $va_tmp = explode(".", $vs_related_table);
				    $vs_related_table = array_shift($va_tmp);
				    $vb_force_related = (strtolower($va_tmp[0]) === 'related');
				                    
                    $va_restrict_indexing_to_types = null;
                    $va_info = $this->getTableIndexingInfo($vs_subject_tablename, $vb_force_related ? "{$vs_related_table}.related" : $vs_related_table);
                    if (is_array($va_info['types']) && sizeof($va_info['types'])) {
                        $va_restrict_indexing_to_types = caMakeTypeIDList($vs_related_table, $va_info['types']);
                    }
				    
					$vn_private = 0;
					$va_queries = [];

					$vn_related_table_num = $this->opo_datamodel->getTableNum($vs_related_table);
					$vs_related_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_related_table_num);

					$t_rel = $this->opo_datamodel->getInstanceByTableNum($vn_related_table_num, true);
					$t_rel->setDb($this->getDb());

					$va_params = null;

					$va_query_info = $this->_getQueriesForRelatedRows($t_subject, $pn_subject_row_id, $t_rel, $pb_reindex_mode, ['forceRelated' => $vb_force_related, 'restrictToTypes' => $va_restrict_indexing_to_types]);
					
					$va_queries 			= $va_query_info['queries'];
					$va_fields_to_index 	= $va_query_info['fields_to_index'];
					
					foreach($va_queries as $vn_i => $va_query) {
						$va_linking_table_config = is_array($va_query_info['linking_table_config_per_query'][$vn_i]) ? $va_query_info['linking_table_config_per_query'][$vn_i] : [];
						
						// Check for configured "private" relationships
						$va_private_rel_types = null;
						foreach($va_linking_table_config as $vs_linking_table => $va_linking_config) {
							if (is_array($va_linking_config) && sizeof($va_linking_config) && isset($va_linking_config['PRIVATE']) && $this->opo_datamodel->isRelationship($vs_linking_table)) {
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
						
						if ($vb_index_count = (isset($va_fields_to_index['_count']) && is_array($va_fields_to_index['_count']))) {
							$va_counts = $this->_getInitedCountList($t_rel); 
						}
						
						while($qr_res->nextRow()) {
                            
							$vn_count++;
							
							$va_field_data = $qr_res->getRow();
							
							$vn_row_id = $qr_res->get($vs_related_pk);
							
							$vn_rel_type_id = (int)$qr_res->get('rel_type_id');
							$vn_row_type_id = (int)$qr_res->get('type_id');
							
                            if(!$vb_force_related && ($vs_related_table == $vs_subject_tablename) && is_array($va_restrict_self_indexing_to_types) && !in_array($vn_row_type_id, $va_restrict_self_indexing_to_types)) {
                                continue;
                            }
                            if(is_array($va_restrict_indexing_to_types) && !in_array($vn_row_type_id, $va_restrict_indexing_to_types)) {
                                continue;
                            }
                            
							
							$vn_private = ((!is_array($va_private_rel_types) || !sizeof($va_private_rel_types) || !in_array($vn_rel_type_id, $va_private_rel_types))) ? 0 : 1;
							
							foreach($va_fields_to_index as $vs_rel_field => $va_rel_field_info) {
								if(is_array($va_rel_field_info['BOOST'])) {
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

									$this->_indexAttribute($t_rel, $vn_row_id, $va_matches[1], array_merge($va_rel_field_info, array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)), ['t_inheritance_subject' => $t_subject, 'inheritance_subject_id' => $pn_subject_row_id]);
								}

								$vs_fld_data = trim($va_field_data[$vs_rel_field]);

								//
								// Hierarchical indexing in related tables
								//
								if (((isset($va_rel_field_info['INDEX_ANCESTORS']) && $va_rel_field_info['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_rel_field_info))) {
									// is this current field a label?
									$t_hier_rel = $t_rel;
									$vn_fld_num = $t_rel->fieldNum($vs_rel_field);
									$vn_id = $vn_row_id;


									if ($t_hier_rel && ($t_hier_rel->isHierarchical() || is_subclass_of($t_hier_rel, "BaseLabel"))) {
										// get hierarchy
										if ($va_hier_values = $this->_genHierarchicalPath($vn_id, $vs_rel_field, $t_hier_rel, $va_rel_field_info)) {
											$this->opo_engine->indexField($vn_related_table_num, "I{$vn_fld_num}", $vn_id, array_merge([$vs_fld_data], $va_hier_values['values']), array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											$this->_genIndexInheritance($t_subject, $t_hier_rel, "I{$vn_fld_num}", $pn_subject_row_id, $vn_id, array_merge([$vs_fld_data], $va_hier_values['values']), array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											
											if(caGetOption('INDEX_ANCESTORS_AS_PATH_WITH_DELIMITER', $va_rel_field_info, false) !== false) {
												$this->opo_engine->indexField($vn_related_table_num, "I{$vn_fld_num}", $vn_id, [$va_hier_values['path']], array_merge($va_rel_field_info, array('DONT_TOKENIZE' => 1, 'relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
												$this->_genIndexInheritance($t_subject, $t_hier_rel, "I{$vn_fld_num}", $pn_subject_row_id, $vn_id, [$va_hier_values['path']], array_merge($va_rel_field_info, array('DONT_TOKENIZE' => 1, 'relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											}
										}
										
										continue;
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
										if ($vb_is_attr) {
										//	$this->opo_engine->indexField($vn_related_table_num, 'A'.$va_matches[1], $vn_id = $qr_res->get($vs_related_pk), [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
										//	$this->_genIndexInheritance($t_subject, $t_rel, 'A'.$va_matches[1], $pn_subject_row_id, $vn_id, [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
										} else {
											if (((isset($va_rel_field_info['INDEX_AS_IDNO']) && $va_rel_field_info['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_rel_field_info)) && method_exists($t_rel, "getIDNoPlugInInstance") && ($o_idno = $t_rel->getIDNoPlugInInstance())) {
												// specialized identifier (idno) processing; uses IDNumbering plugin to generate searchable permutations of identifier
												$va_values = $o_idno->getIndexValues($vs_fld_data, $va_rel_field_info);
												$this->opo_engine->indexField($vn_related_table_num, 'I'.($vn_fn = $this->opo_datamodel->getFieldNum($vs_related_table, $vs_rel_field)), $vn_id = $qr_res->get($vs_related_pk), $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
												$this->_genIndexInheritance($t_subject, $t_rel, "I{$vn_fn}", $pn_subject_row_id, $vn_id, $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											} elseif (((isset($va_rel_field_info['INDEX_AS_MIMETYPE']) && $va_rel_field_info['INDEX_AS_MIMETYPE']) || in_array('INDEX_AS_MIMETYPE', $va_rel_field_info))) {
												// specialized mimetype processing
												$va_values = [];
												if ($vs_typename = Media::getTypenameForMimetype($vs_fld_data)) {
													$va_values[] = $vs_typename;
												}
												// Index mimetype as-is
												$this->opo_engine->indexField($vn_related_table_num, 'I'.($vn_fn = $this->opo_datamodel->getFieldNum($vs_related_table, $vs_rel_field)), $vn_id = $qr_res->get($vs_related_pk), [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private, 'DONT_TOKENIZE' => true)));
												
												// Index typename
												$this->opo_engine->indexField($vn_related_table_num, 'I'.($vn_fn = $this->opo_datamodel->getFieldNum($vs_related_table, $vs_rel_field)), $vn_id = $qr_res->get($vs_related_pk), $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
												$this->_genIndexInheritance($t_subject, $t_rel, "I{$vn_fn}", $pn_subject_row_id, $vn_id, $va_values, array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											} else {
												// regular intrinsic
												$this->opo_engine->indexField($vn_related_table_num, 'I'.($vn_fn = $this->opo_datamodel->getFieldNum($vs_related_table, $vs_rel_field)), $vn_rid = $qr_res->get($vs_related_pk), [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
												$this->_genIndexInheritance($t_subject, $t_rel, "I{$vn_fn}", $pn_subject_row_id, $vn_rid, [$vs_fld_data], array_merge($va_rel_field_info, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
											}
										}
										break;
								}
//
// END: Index attributes in related tables
//							
							}
							// index label for self-relation?
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

										if (!$vb_skip && is_array($va_labels = $t_rel->getPreferredLabels(null, false, array('row_id' => $vn_row_id)))) {
											foreach($va_labels as $vn_x => $va_labels_by_locale) {
												foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
													foreach($va_label_list as $va_label) {
													    $vn_label_id = $va_label['label_id'];

														foreach($va_label_info['related']['fields'] as $vs_label_field => $va_config) {
															$this->opo_engine->indexField($vn_label_table_num, 'I'.($vn_fn = $this->opo_datamodel->getFieldNum($vn_label_table_num, $vs_label_field)), $vn_label_id, [$va_label[$vs_label_field]], array_merge($va_config, array('relationship_type_id' => $vn_rel_type_id, 'PRIVATE' => $vn_private)));
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
						
						// index counts?
						if ($vb_index_count) {
							foreach($va_counts as $vs_key => $vn_count) {
								$this->opo_engine->indexField($vn_related_table_num, 'COUNT', 0, [(int)$vn_count], ['relationship_type_id' => $vs_key, 'PRIVATE' => $vn_private]);
								$this->_genIndexInheritance($t_subject, $t_rel, 'COUNT', $pn_subject_row_id, 0, [(int)$vn_count], ['relationship_type_id' => $vs_key, 'PRIVATE' => $vn_private]);
							}
						}
					}
				}
			}
        }
        
        

		// save indexing on subject
		if ($vb_started_indexing) {
			$this->opo_engine->commitRowIndexing();
		}


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
				$va_rows_to_reindex_by_row_id = array();
				$va_row_ids_to_reindex_by_table = array();
				foreach($va_rows_to_reindex as $vs_key => $va_row_to_reindex) {
					foreach($va_row_to_reindex['field_nums'] as $vs_fld_name => $vn_fld_num) {
						$vs_new_key = $va_row_to_reindex['table_num'].'/'.$va_row_to_reindex['field_table_num'].'/'.$vn_fld_num.'/'.$va_row_to_reindex['field_row_id'];

						if(!isset($va_rows_to_reindex_by_row_id[$vs_new_key])) {
							if(is_array($va_row_to_reindex['indexing_info'][$vs_fld_name]['BOOST'])) {
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
								'private' => $va_row_to_reindex['private']
							);
						}
						$va_rows_to_reindex_by_row_id[$vs_new_key]['row_ids'][] = $va_row_to_reindex['row_id'];
						$va_row_ids_to_reindex_by_table[$va_row_to_reindex['field_table_num']][] = $va_row_to_reindex['field_row_id'];
					}
				}

				foreach($va_row_ids_to_reindex_by_table as $vn_rel_table_num => $va_rel_row_ids) {
					$va_rel_row_ids = array_unique($va_rel_row_ids);
					if ($t_rel = $this->opo_datamodel->getInstanceByTableNum($vn_rel_table_num, true)) {
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
					$vn_rel_type_id = $va_row_to_reindex['relationship_type_id'];
					$vn_private = $va_row_to_reindex['private'];
					
					$t_rel = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['field_table_num'], true);
					$t_rel->setDb($this->getDb());

					if (substr($va_row_to_reindex['field_name'], 0, 14) == '_ca_attribute_') {		// is attribute
						$va_row_to_reindex['indexing_info']['datatype'] = ca_metadata_elements::getElementDatatype(substr($va_row_to_reindex['field_name'], 14));
					}
					
					if ($va_row_to_reindex['field_name'] == '_count') {
						foreach( $va_row_to_reindex['row_ids'] as $vn_subject_row_id) {
							$this->_doCountIndexing($this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num'], true), $vn_subject_row_id, $t_rel, false);
						}
					}

					if (((isset($va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) && $va_row_to_reindex['indexing_info']['INDEX_ANCESTORS']) || in_array('INDEX_ANCESTORS', $va_row_to_reindex['indexing_info']))) {
						if (!is_array($va_row_to_reindex['row_ids'])) { continue; }

						$t_label = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['field_table_num'], true);
						$t_label->setDb($this->getDb());

						foreach($va_row_to_reindex['row_ids'] as $vn_row_id) {
							$va_content = $this->_genHierarchicalPath($va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_name'], $t_label, $va_row_to_reindex['indexing_info']);
							$vs_content = is_array($va_content['values']) ? join(" ", $va_content['values']) : "";

							$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], array($vn_row_id), $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $vs_content, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id, 'literalContent' => $va_content['path'])));
						}
					} elseif (((isset($va_row_to_reindex['indexing_info']['INDEX_AS_IDNO']) && $va_row_to_reindex['indexing_info']['INDEX_AS_IDNO']) || in_array('INDEX_AS_IDNO', $va_row_to_reindex['indexing_info'])) && method_exists($t_rel, "getIDNoPlugInInstance") && ($o_idno = $t_rel->getIDNoPlugInInstance())) {
						foreach($va_row_to_reindex['row_ids'] as $vn_row_id) {
							$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
						}
					} else {
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

													$va_additional_indexing = $vo_value->getDataForSearchIndexing();
													if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
														foreach($va_additional_indexing as $vs_additional_value) {
															$vs_value_to_index .= " ; ".$vs_additional_value;
														}
													}
													if ($t_rel = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num'], true)) {
														$this->opo_engine->indexField($va_row_to_reindex['table_num'], "A{$vn_sub_element_id}", $va_row_to_reindex['field_row_id'], [$vs_value_to_index], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
														$this->_genIndexInheritance($t_subject, $t_rel, "A{$vn_sub_element_id}", $pn_subject_row_id, $va_row_to_reindex['field_row_id'], [$vs_value_to_index], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
													}
												}
											}
										} else {
											// we are deleting a container so cleanup existing sub-values
											$va_sub_elements = $this->opo_metadata_element->getElementsInSet($vs_element_code);

											foreach($va_sub_elements as $vn_i => $va_element_info) {
												if ($t_rel = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num'], true)) {
													$this->opo_engine->indexField($va_row_to_reindex['table_num'], 'A'.$va_element_info['element_id'], $va_row_to_reindex['field_row_id'], [''], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
													$this->_genIndexInheritance($t_subject, $t_rel, 'A'.$va_element_info['element_id'], $pn_subject_row_id, $va_row_to_reindex['field_row_id'], [''], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
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

									$va_new_values = array();
									$t_item = new ca_list_items();
									$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_tmp, array('returnAllLocales' => true));

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


									$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $vs_v, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));

									break;
								default:

									//$va_tmp = array();
									if (is_array($va_attributes = $t_rel->getAttributesByElement($vs_element_code, array('row_id' => $va_row_to_reindex['field_row_id'])))) {
										foreach($va_attributes as $vo_attribute) {
											foreach($vo_attribute->getValues() as $vo_value) {
												$vs_value_to_index = $vo_value->getDisplayValue($vn_list_id);

												$va_additional_indexing = $vo_value->getDataForSearchIndexing();
												if(is_array($va_additional_indexing) && (sizeof($va_additional_indexing) > 0)) {
													foreach($va_additional_indexing as $vs_additional_value) {
														$vs_value_to_index .= " ; ".$vs_additional_value;
													}
												}

												//$va_tmp[$vo_attribute->getAttributeID()] = $vs_value_to_index;
												$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $vo_attribute->getAttributeID(), $va_row_to_reindex['field_row_id'], $vs_value_to_index, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
											}
										}
									}


									// foreach($va_tmp as $vn_attribute_id => $vn_item_id) {
// 										if(!$vn_item_id) { continue; }
// 										$vs_v = join(' ;  ', $va_tmp);
// 									}
									//$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], $va_row_to_reindex['field_row_id'], $vs_v, array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
									break;
							}
						} else {
							$this->opo_engine->updateIndexingInPlace($va_row_to_reindex['table_num'], $va_row_to_reindex['row_ids'], $va_row_to_reindex['field_table_num'], $va_row_to_reindex['field_num'], null, $va_row_to_reindex['field_row_id'], $va_row_to_reindex['field_values'][$va_row_to_reindex['field_name']], array_merge($va_row_to_reindex['indexing_info'], array('PRIVATE' => $vn_private, 'relationship_type_id' => $vn_rel_type_id)));
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
						$t_dep = $this->opo_datamodel->getInstanceByTableNum($va_row_to_reindex['table_num']);
					}

					$vb_support_attributes = is_subclass_of($t_dep, 'BaseModelWithAttributes') ? true : false;
					if (is_array($pa_exclusion_list[$va_row_to_reindex['table_num']]) && (isset($pa_exclusion_list[$va_row_to_reindex['table_num']][$va_row_to_reindex['row_id']]))) { continue; }
					// trigger reindexing
					$this->opo_engine->removeRowIndexing($va_row_to_reindex['table_num'], $va_row_to_reindex['row_id'], null, null, null, $va_row_to_reindex['relationship_type_id']);
					if ($vb_support_attributes) {
						if ($t_dep->load($va_row_to_reindex['row_id'])) {
							// 
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
	}
}
