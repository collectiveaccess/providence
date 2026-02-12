<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseSearchController.php : base controller for search interface
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2026 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

class BaseLookupController extends ActionController {
	# -------------------------------------------------------
	protected $opb_uses_hierarchy_browser = false;
	protected $ops_table_name = '';
	protected $ops_name_singular = '';
	protected $ops_search_class = '';
	protected $opo_item_instance;
	
	/**
	 * @property $opa_filters Criteria to filter list Get() return with; array keys are <tablename>.<fieldname> 
	 * bundle specs; array values are *array* lists of values. If an item is not equal to a value in the array it will not be 
	 * returned. Leave set to null or empty array if you don't want to filter.
	 */
	protected $opa_filters = []; 
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $view_paths=null) {
		if ($this->ops_search_class) { require_once(__CA_LIB_DIR__."/Search/".$this->ops_search_class.".php"); }
		require_once(__CA_MODELS_DIR__."/".$this->ops_table_name.".php");
		parent::__construct($po_request, $po_response, $view_paths);
		$this->opo_item_instance = new $this->ops_table_name();
	}
	# -------------------------------------------------------
	# AJAX handlers
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Get(?array $additional_query_params=null, ?array $options=null) {
		$this->response->setContentType("application/json");
		if (!ca_user_roles::isValidAction('can_search_'.$this->ops_table_name) || ($this->request->user->canDoAction('can_search_'.$this->ops_table_name))) { 
			$o_config = Configuration::load();
			$o_search_config = caGetSearchConfig();
			
			if (!$this->ops_search_class) { return null; }
			$query = $query_proc = $this->request->getParameter('term', pString);
			
			$max_auto_quote_length = (bool)$o_config->getScalar($this->ops_table_name.'_auto_quote_lookup_maximimum_length') ?: (int)$o_config->getScalar('auto_quote_lookup_maximimum_length');
			$auto_quote_require_whitespace = (bool)$o_config->getScalar($this->ops_table_name.'_auto_quote_lookup_require_whitespace') ?: (bool)$o_config->getScalar('auto_quote_lookup_require_whitespace');
			
			if(($max_auto_quote_length > 0) && (mb_strlen($query) <= $max_auto_quote_length) && (!$auto_quote_require_whitespace || (strpos($query, ' ') !== false)) && (strpos($query, '"') === false)){
				$query = $query_proc = '"'.$query.'"';
				$exact = true;
			}
		
			$exact = $this->request->getParameter('exact', pInteger);
			$exclude = $this->request->getParameter('exclude', pString);
			$va_excludes = explode(";", $exclude);
			$type = $this->request->getParameter('type', pString);
			$types = $this->request->getParameter('types', pString);
			$restrict_to_access_point = trim($this->request->getParameter('restrictToAccessPoint', pString));
			$restrict_to_search = trim($this->request->getParameter('restrictToSearch', pString));
			$no_subtypes = (bool)$this->request->getParameter('noSubtypes', pInteger);
			$quickadd = (bool)$this->request->getParameter('quickadd', pInteger);
			$no_inline = (bool)$this->request->getParameter('noInline', pInteger);
			$quiet = (bool)$this->request->getParameter('quiet', pInteger);
			$self = explode(':', $this->request->getParameter('self', pString));		// table:id of calling record
			
			if((!(bool)$o_config->get('allow_duplicate_items_in_sets')) && ($set_id = $this->request->getParameter('set_id', pInteger))) {
				$t_set = new ca_sets($set_id);
				$va_excludes = array_keys($t_set->getItemRowIDs());
			}
			
			if (!($limit = $this->request->getParameter('limit', pInteger))) { $limit = 100; }
			$va_items = array();
			if (($vn_str_len = mb_strlen($query)) > 0) {
				if ($vn_str_len < 3) { $exact = true; }		// force short strings to be an exact match (using a very short string as a stem would perform badly and return too many matches in most cases)
			
				if (is_array($va_asis_regexes = $o_search_config->getList('asis_regexes'))) {
					foreach($va_asis_regexes as $vs_asis_regex) {
						if (preg_match("!{$vs_asis_regex}!", $query)) {
							$exact = true;
							break;
						}
					}
				}
			
			
				$o_search = new $this->ops_search_class();
			
				$types = array();
				if ($types) {
					$types = explode(';', $types);
				} else {
					if ($type) {
						$types = array($type);
					}
				}
			
				// Get type_ids
				$va_ids = array();
				if (sizeof($types)) {
					$va_types = $this->opo_item_instance->getTypeList();
					$va_types_proc = array();
					foreach($va_types as $vn_type_id => $va_type) {
						$va_types_proc[$vn_type_id] = $va_types_proc[$va_type['idno']] = $vn_type_id;
					}
					foreach($types as $type) {
						if (isset($va_types_proc[$type])) {
							$va_ids[$va_types_proc[$type]] = true;
						} elseif (is_numeric($type)) {
							$va_ids[(int)$type] = true;
						}
					}
					$va_ids = array_keys($va_ids);
				
					if (sizeof($va_ids) > 0) {
						$t_list = new ca_lists();
					
						if (!$no_subtypes) {
							foreach($va_ids as $vn_id) {
								if (is_array($va_children = $t_list->getItemsForList($this->opo_item_instance->getTypeListCode(), array('item_id' => $vn_id, 'idsOnly' => true)))) {
									$va_ids = array_merge($va_ids, $va_children);
								}
							}
							$va_ids = array_flip(array_flip($va_ids));
						}
						$o_search->addResultFilter($this->opo_item_instance->tableName().'.'.$this->opo_item_instance->getTypeFieldName(), 'IN', join(",", $va_ids));
					}
				}
		
				// add any additional search elements
				$vs_additional_query_params = '';
				if (is_array($additional_query_params) && sizeof($additional_query_params)) {
					$vs_additional_query_params = ' AND ('.join(' AND ', $additional_query_params).')';
				}

				$vs_restrict_to_access_point = '';
				if((strlen($restrict_to_access_point) > 0) && $query) {
					$query_proc = $restrict_to_access_point.":\"".str_replace('"', '', $query)."\"";
				}
			
				$vs_restrict_to_search = '';
				if(strlen($restrict_to_search) > 0) {
					$vs_restrict_to_search .= ' AND ('.$restrict_to_search.')';
				}
			
				// get sort field
				$vs_sort = $this->request->getAppConfig()->get($this->opo_item_instance->tableName().'_lookup_sort');
				if(!$vs_sort) { $vs_sort = '_natural'; }

				$vs_hier_parent_id_fld 		= $this->opo_item_instance->getProperty('HIERARCHY_PARENT_ID_FLD');
				$vs_hier_fld 						= $this->opo_item_instance->getProperty('HIERARCHY_ID_FLD');
				if ($vs_hier_fld && ($vn_restrict_to_hier_id = $this->request->getParameter('currentHierarchyOnly', pInteger))) {
					$o_search->addResultFilter($this->opo_item_instance->tableName().'.'.$vs_hier_fld, '=', (int)$vn_restrict_to_hier_id);
				}
			
				// add filters
				if (isset($options['filters']) && is_array($options['filters']) && sizeof($options['filters'])) {
					foreach($options['filters'] as $va_filter) {
						$o_search->addResultFilter($va_filter[0], $va_filter[1], $va_filter[2]);
					}
				}
				if(is_array($this->opa_filters)) {
					foreach($this->opa_filters as $f => $v) {
						$o_search->addResultFilter($f, is_array($v) ? 'IN' : '=', is_array($v) ? join(',', $v) : $v);
					}
				}
	
				if (preg_match("![\/\.\-]!", $query)) { $exact = true; }
			
				// do search
				if($vs_additional_query_params || $vs_restrict_to_search) {
					$vs_search = '('.trim($query_proc).(intval($exact) ? '' : '*').')'.$vs_additional_query_params.$vs_restrict_to_search;
				} else {
					$vs_search = trim($query_proc).(intval($exact) ? '' : '*');
				}
			
				$qr_res = $o_search->search($vs_search, array('search_source' => 'Lookup', 'no_cache' => false, 'sort' => $vs_sort));
			
				$qr_res->setOption('prefetch', $limit);
				$qr_res->setOption('dontPrefetchAttributes', true);
			
				$va_opts = array('exclude' => $va_excludes, 'limit' => $limit, 'request' => $this->getRequest());
				if(!$no_inline && ($quickadd || (!strlen($quickadd) && $this->request->user && $this->request->user->canDoAction('can_quickadd_'.$this->opo_item_instance->tableName()) && !((bool) $o_config->get($this->opo_item_instance->tableName().'_disable_quickadd'))))) {
					// if the lookup was restricted by search, try the lookup without the restriction
					// so that we can notify the user that he might be about to create a duplicate
					if((strlen($restrict_to_search) > 0)) {
						$o_no_filter_result = $o_search->search(trim($query_proc) . (intval($exact) ? '' : '*') . $vs_additional_query_params, array('search_source' => 'Lookup', 'no_cache' => false, 'sort' => $vs_sort));
						if ($o_no_filter_result->numHits() != $qr_res->numHits()) {
							$va_opts['inlineCreateMessageDoesNotExist'] = _t("<em>%1</em> doesn't exist with this filter but %2 record(s) match overall. Create <em>%1</em>?", $query, $o_no_filter_result->numHits());
							$va_opts['inlineCreateMessage'] = _t('<em>%1</em> matches %2 more record(s) without the current filter. Create <em>%1</em>?', $query, ($o_no_filter_result->numHits() - $qr_res->numHits()));
						}
					}
					if(!isset($va_opts['inlineCreateMessageDoesNotExist'])) { $va_opts['inlineCreateMessageDoesNotExist'] = _t('<em>%1</em> does not exist. Create?', $query); }
					if(!isset($va_opts['inlineCreateMessage'])) { $va_opts['inlineCreateMessage'] = _t('Create <em>%1</em>?', $query); }
					$va_opts['inlineCreateQuery'] = $query;
				} elseif(!$quiet) {
					$va_opts['emptyResultQuery'] = $query;
					$va_opts['emptyResultMessage'] = _t('No matches found for "%1"', $query);
				}
			
				if(isset($self[0]) && ($self[0] === $this->ops_table_name) && isset($self[1]) && ($self[1] > 0)) {
					$va_opts['self_id'] = $self[1];
				}
				$va_items = caProcessRelationshipLookupLabel($qr_res, $this->opo_item_instance, $va_opts);
			}
			if (!is_array($va_items)) { $va_items = []; }
		
			// Optional output simple list of labels instead of full data format
			if ((bool)$this->request->getParameter('simple', pInteger)) { 
				$va_items = caExtractValuesFromArrayList($va_items, 'label', array('preserveKeys' => false)); 
			}
			$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', array_values($va_items));
		} else {
			$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', [_t('You do not have access to search %1', $this->opo_item_instance->getProperty('NAME_PLURAL'))]);
		}
		return $this->render(str_replace(' ', '_', 'ajax_'.$this->ops_name_singular.'_list_html.php'));
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyLevel() {
		$this->response->setContentType("application/json");
		
		$qr_children = null;

		$bundle = (string)$this->request->getParameter('bundle', pString);
		$ids = explode(";", $ids = $this->request->getParameter('id', pString));
		if (!sizeof($ids)) { $ids = array(null); }
		$t_item = $this->opo_item_instance;
		if (!$t_item->isHierarchical()) { return; }

		$va_level_data = array();
		foreach($ids as $id) {
			$va_tmp = explode(":", $id);
			$vn_id = $va_tmp[0];
			$vn_start = (int)$va_tmp[1];
			if($vn_start < 0) { $vn_start = 0; }
			if(sizeof($va_tmp) < 2) {
				$id = '0:0';
			}

			$va_items_for_locale = array();
			$vb_gen = true;
			if ((!($vn_id)) && method_exists($t_item, "getHierarchyList")) {
				$vn_id = (int)$this->request->getParameter('root_item_id', pInteger);
				$t_item->load($vn_id);
				// no id so by default return list of available hierarchies
				if(!is_array($va_items_for_locale = $t_item->getHierarchyList())) { 
					$va_items_for_locale = array();
				}
				
				if((sizeof($va_items_for_locale) == 1) && $this->request->getAppConfig()->get($t_item->tableName().'_hierarchy_browser_hide_root')) {
					$va_item = array_shift($va_items_for_locale);
					$vn_id = $va_item['item_id'];
				} else {
					$vb_gen = false;
				}
			}
			if ($vb_gen && $t_item->load($vn_id)) {		// id is the id of the parent for the level we're going to return
				$vs_table_name = $t_item->tableName();
				$vs_label_table_name = $this->opo_item_instance->getLabelTableName();
				$vs_label_display_field_name = $this->opo_item_instance->getLabelDisplayField();
				$vs_pk = $this->opo_item_instance->primaryKey();

				$va_additional_wheres = array();
				$t_label_instance = $this->opo_item_instance->getLabelTableInstance();
				if ($t_label_instance && $t_label_instance->hasField('is_preferred')) {
					$va_additional_wheres[] = "(({$vs_label_table_name}.is_preferred = 1) OR ({$vs_label_table_name}.is_preferred IS NULL))";
				}

				$o_config = Configuration::load();
				if (!(is_array($va_sorts = caGetHierarchyBrowserSortValues($vs_table_name, $t_item))) || !sizeof($va_sorts)) { $va_sorts = array(); }
				foreach($va_sorts as $vn_i => $vs_sort_fld) {
					$va_tmp = explode(".", $vs_sort_fld);

					if ($va_tmp[1] == 'preferred_labels') {
						$va_tmp[0] = $vs_label_table_name;
						if (!($va_tmp[1] = $va_tmp[2])) {
							$va_tmp[1] = $vs_label_display_field_name;
						}
						unset($va_tmp[2]);

						$va_sorts[$vn_i] = join(".", $va_tmp);
					}
				}

				if (!in_array($vs_sort_dir = strtolower($o_config->get($this->ops_table_name.'_hierarchy_browser_sort_direction')), array('asc', 'desc'))) {
					$vs_sort_dir = 'asc';
				}

				$va_items = array();
				
				$vs_rank_fld = $t_item->getProperty('RANK');
				$has_is_default_fld = $t_item->hasField('is_default');
				
				if (is_array($va_item_ids = $t_item->getHierarchyChildren($t_item->getPrimaryKey(), array('idsOnly' => true))) && sizeof($va_item_ids)) {
					$qr_children = $t_item->makeSearchResult($t_item->tableName(), $va_item_ids, ['sort' => $va_sorts, 'sortDirection' => $vs_sort_dir]);
					$va_child_counts = $t_item->getHierarchyChildCountsForIDs($va_item_ids);

					if (!($vs_item_template = trim($o_config->get("{$vs_table_name}_hierarchy_browser_display_settings")))) {
						$vs_item_template = "^{$vs_table_name}.preferred_labels.{$vs_label_display_field_name}";
					}

					if ((($vn_max_items_per_page = $this->request->getParameter('max', pInteger)) < 1) || ($vn_max_items_per_page > 1000)) {
						$vn_max_items_per_page = 100;
					}
					$vn_c = 0;

					if ($vn_start >0) { $qr_children->seek($vn_start); }
					while($qr_children->nextHit()) {
						$va_tmp = array(
							$vs_pk => $vn_id = $qr_children->get($this->ops_table_name.'.'.$vs_pk),
							'item_id' => $vn_id,
							'parent_id' => $qr_children->get($this->ops_table_name.'.parent_id'),
							'idno' => $qr_children->get($this->ops_table_name.'.idno'),
							$vs_label_display_field_name => $qr_children->get($this->ops_table_name.'.preferred_labels.'.$vs_label_display_field_name),
							'locale_id' => $qr_children->get($this->ops_table_name.'.'.'locale_id')
						);
						if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = $va_tmp['idno']; }
						if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = '???'; }

						$va_tmp['name'] = caProcessTemplateForIDs($vs_item_template, $vs_table_name, array($va_tmp[$vs_pk]), array('requireLinkTags' => true));
						if(!$va_tmp['name']) { $va_tmp['name'] = '??? '.$va_tmp[$vs_pk]; }

						if($has_is_default_fld && $qr_children->get($this->ops_table_name.'.is_default')) {
							$va_tmp['name'] .= ' ◉';
						}
						
						// Child count is only valid if has_children is not null
						$va_tmp['children'] = isset($va_child_counts[$vn_id]) ? (int)$va_child_counts[$vn_id] : 0;

						if(strlen($vs_enabled = $qr_children->get('is_enabled')) > 0) {
							$va_tmp['is_enabled'] = $vs_enabled;
						}
						$va_items[$va_tmp[$vs_pk]][$va_tmp['locale_id']] = $va_tmp;
						$vn_c++;
						
						if ($vn_c >= $vn_max_items_per_page) { break; }
					}

					$va_items_for_locale = caExtractValuesByUserLocale($va_items);
				}
			}

			$va_items_for_locale['_sortOrder'] = array_keys($va_items_for_locale);
			$va_items_for_locale['_primaryKey'] = $t_item->primaryKey();	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
			$va_items_for_locale['_itemCount'] = $qr_children ? $qr_children->numHits() : 0;
			$va_level_data[$id] = $va_items_for_locale;
		}

		if (!$this->request->getParameter('init', pInteger)) {
			// only set remember "last viewed" if the load is done interactively
			// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
			// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
			// as the 'last viewed' we can end up losing the true 'last viewed' value
			//
			// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
			// is part of a browser initialization
			Session::setVar($this->ops_table_name.'_'.$bundle.'_browse_last_id', array_pop($ids));
		}

		$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', $va_level_data);

		return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_level_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyAncestorList() {
		$this->response->setContentType("application/json");
		
		$id = $this->request->getParameter('id', pInteger);
		$t_item = new $this->ops_table_name($id);
		
		$va_ancestors = array();
		if ($t_item->getPrimaryKey()) {
			if(!is_array($va_ancestors = $t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)))) {
				$va_ancestors = [];
			}
			$va_ancestors = array_reverse($va_ancestors);
			if($this->request->getAppConfig()->get($t_item->tableName().'_hierarchy_browser_hide_root')) {
				if(($k = array_search($t_item->getHierarchyRootID(), $va_ancestors)) !== false) {
					unset($va_ancestors[$k]);
				}
			}
		}
		
		// Force ids to ints to prevent jQuery from getting confused
		// (jQuery.getJSON() incorrectly parses arrays of numbers-as-strings)
		$va_ancestors = array_map('intval', $va_ancestors);
		
		$this->view->setVar('ancestors', $va_ancestors);
		return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_ancestors_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function IDNo() {
		$this->response->setContentType("application/json");
		
		$ids = $sequences = [];
		if ($idno_field = $this->opo_item_instance->getProperty('ID_NUMBERING_ID_FIELD')) {
			$row_id =  $this->request->getParameter('id', pInteger);
			$type_id =  $this->request->getParameter('type_id', pInteger);
			
			if ($idno_context_field = $this->opo_item_instance->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {		// want to set context before doing identifier lookup, if the table supports contexts (ca_list_items and ca_place do, others don't)
				if($context_id =  $this->request->getParameter('_context_id', pInteger)) {
					$this->opo_item_instance->load(array($idno_context_field => $context_id));
				} else {
					$this->opo_item_instance->load($row_id);
				}
			}
			if ($idno = $this->request->getParameter('n', pString)) {
				$ids = $this->opo_item_instance->checkForDupeAdminIdnos($idno, false, $row_id);
			}
			
			$type = $this->opo_item_instance->getTypeCode($type_id);
			if (
				method_exists($this->opo_item_instance, "getIDNoPlugInInstance") && 
				($o_numbering_plugin = $this->opo_item_instance->getIDNoPlugInInstance()) &&
				method_exists($o_numbering_plugin, 'isSerialFormat') &&
				$o_numbering_plugin->isSerialFormat($this->opo_item_instance->tableName(), $type)
			) {
				$o_numbering_plugin->setFormat($this->opo_item_instance->tableName());
				$o_numbering_plugin->setType($type);
				
				if ($parent_value = $this->request->getParameter('parentValue', pString)) {
					$o_numbering_plugin->isChild(true, $parent_value);
				}
				$elements = $o_numbering_plugin->getElements();
				foreach($elements as $ename => $e) {
					if ($e['type'] === 'SERIAL') {
						$sequences[$ename] = $o_numbering_plugin->getNextValue($ename, $idno);
					}
				}
				
			}
		}
		$this->view->setVar('id_list', $ids);
		$this->view->setVar('sequences', $sequences);
	
		return $this->render('idno_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Checks value of instrinsic field and return list of primary keys that use the specified value
	 * Can be used to determine if a value that needs to be unique is actually unique.
	 */
	public function Intrinsic() {
		$this->response->setContentType("application/json");
		
		$table_num 	=  $this->request->getParameter('table_num', pInteger);
		$field 				=  $this->request->getParameter('field', pString);
		$val 				=  $this->request->getParameter('n', pString);
		$id 					=  $this->request->getParameter('id', pInteger);
		$within_fields	=  $this->request->getParameter('withinFields', pArray); 
		
		if (!($t_instance = Datamodel::getInstanceByTableNum($table_num, true))) {
			return null;	// invalid table number
		}
		
		if (!$t_instance->hasField($field)) {
			return null;	// invalid field
		}
		
		$o_db = new Db();
		$vs_pk = $t_instance->primaryKey();
		
		
		// If "unique within" fields are specified then we limit our query to values that have those fields
		// set similarly to the row we're checking.
		$va_unique_within = $t_instance->getFieldInfo($field, 'UNIQUE_WITHIN');
		
		$va_extra_wheres = array();
		if ($t_instance->hasField('deleted')) { $va_extra_wheres[] = "(deleted = 0)"; }
		$vs_extra_wheres = '';
		$va_params = array((string)$val, (int)$id);
		if (sizeof($va_unique_within)) {
			foreach($va_unique_within as $vs_within_field) {
				$va_extra_wheres[] = "({$vs_within_field} = ?)";
				$va_params[] = $within_fields[$vs_within_field];
			}
		}
		if (sizeof($va_extra_wheres) > 0) {
			$vs_extra_wheres = ' AND '.join(' AND ', $va_extra_wheres);
		}
		
		$qr_res = $o_db->query("
			SELECT {$vs_pk}
			FROM ".$t_instance->tableName()."
			WHERE
				({$field} = ?) AND ({$vs_pk} <> ?)
				{$vs_extra_wheres}
		", $va_params);
		$va_ids = array();
		while($qr_res->nextRow()) {
			$va_ids[] = (int)$qr_res->get($vs_pk);
		}
		
		$this->view->setVar('id_list', $va_ids);
		return $this->render('intrinsic_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Checks value given metadata element and return list of primary keys that use the
	 * specified value. Can be used to determine if a value that needs to be unique is actually unique.
	 */
	public function Attribute() {
		$element_id 	=  $this->getRequest()->getParameter('element_id', pInteger);
		$val 			=  $this->getRequest()->getParameter('n', pString);

		if(!ca_metadata_elements::getElementCodeForId($element_id)) {
			return null;
		}

		$o_db = new Db();
		if(mb_strlen($val) > 0) {
			$qr_values = $o_db->query('SELECT value_id FROM ca_attribute_values WHERE element_id=? AND value_longtext1=?', $element_id, $val);
			$va_value_list = $qr_values->getAllFieldValues('value_id');
		} else {
			$va_value_list = [];
		}

		$this->getView()->setVar('value_list', $va_value_list);

		return $this->render('attribute_json.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function SetSortOrder() {
		$after_id 	=  $this->getRequest()->getParameter('after_id', pInteger);
		$id 		=  $this->getRequest()->getParameter('id', pInteger);
		
		$vn_return = 0;
		$va_errors = [];
		
		try {	
			// Is user allowed to sort?
			$t_item = new $this->ops_table_name($id);
			if (!$t_item->isLoaded()) { throw new ApplicationException(_t('Could not load %1', $t_item->getProperty('NAME_PLURAL'))); }
		
			$vs_rank_fld = $t_item->getProperty('RANK');
		
			switch($t_item->getProperty('HIERARCHY_TYPE')) {
				case __CA_HIER_TYPE_SIMPLE_MONO__:
					$vs_def_table_name = $t_item->tableName();
					$t_def = $t_item;
					$vs_def_id_fld = $t_item->primaryKey();
					$vs_def_pk = $t_item->getProperty('HIERARCHY_PARENT_ID_FLD');
					$vn_def_id = $t_item->get($vs_def_pk);
					
					if (!$this->request->user->canDoAction("can_edit_{$this->ops_table_name}")) { throw new ApplicationException(_t('Access denied')); }
					break;
				case __CA_HIER_TYPE_ADHOC_MONO__:
					$vs_def_table_name = $t_item->tableName();
					$t_def = $t_item;
					$vs_def_id_fld = $t_item->getProperty('HIERARCHY_PARENT_ID_FLD');
					$vs_def_pk = $t_item->getProperty('HIERARCHY_PARENT_ID_FLD');
					$vn_def_id = $t_item->get($vs_def_pk);
					
					if (!$this->request->user->canDoAction("can_edit_{$this->ops_table_name}")) { throw new ApplicationException(_t('Access denied')); }
					break;
				case __CA_HIER_TYPE_MULTI_MONO__:
					$vs_def_table_name = $t_item->getProperty('HIERARCHY_DEFINITION_TABLE');
					$vs_def_id_fld = $t_item->getProperty('HIERARCHY_ID_FLD');
				
					$t_def = new $vs_def_table_name($vn_def_id = $t_item->get($vs_def_id_fld));
					if (!$t_def->isLoaded()) { throw new ApplicationException(_t('Could not load %1', $t_def->getProperty('NAME_PLURAL'))); }
					$vs_def_pk = $t_def->primaryKey();
					
					if (!$this->request->user->canDoAction("can_edit_{$this->ops_table_name}") && !$this->request->user->canDoAction("can_edit_{$vs_def_table_name}")) { throw new ApplicationException(_t('Access denied')); }
					break;
				default:
					throw new ApplicationException(_t('Invalid hierarchy type'));	
			}
		
			if (!$vs_rank_fld) { 
				throw new ApplicationException(_t('Sorting is not supported for %1', $t_item->getProperty('NAME_PLURAL')));
			}
		
			
			
			// Sort order must be "rank"
			// first look in default sort field (if it exists)
			$vb_has_sort_by_rank = false;
			if ($vs_def_table_name && $t_def->hasField('default_sort')) {
				if ((int)$t_def->get('default_sort') !== __CA_LISTS_SORT_BY_RANK__) { throw new ApplicationException(_t('%1 must have default sort set to rank', $t_def->getProperty('NAME_SINGULAR'))); }
				$vb_has_sort_by_rank = true;
			}
		
			// then fallback to app.conf defaults
			if (!$vb_has_sort_by_rank) {
				$va_sort_values = caGetHierarchyBrowserSortValues($this->ops_table_name);
				if ((sizeof($va_sort_values) < 1) || ($va_sort_values[0] != "{$this->ops_table_name}.{$vs_rank_fld}")) {
					throw new ApplicationException(_t('%1 must have sort configured to use rank', $t_item->getProperty('NAME_SINGULAR')));
				}
			}
		
		// manipulate ranks to place "id" row after "after_id"
			$t_item->setRankAfter($after_id);
			if ($t_item->numErrors()) { throw new ApplicationException(_t('Could not update rank: %1', join('; ', $t_item->getErrors()))); }
			$vn_return = 1;
		} catch (Exception $e) {
			$va_errors[] = $e->getMessage();
			$vn_return = 0;
		}
		
		$this->view->setVar("result", ['ok' => $vn_return, 'errors' => $va_errors, 'timestamp' => (sizeof($va_errors) == 0) ? time(): null]);
		
		return $this->render('set_sort_order_json.php');
	}
	# -------------------------------------------------------
}
