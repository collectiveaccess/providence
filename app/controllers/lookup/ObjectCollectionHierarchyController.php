<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ObjectCollectionHierarchyController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2017 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/BaseLookupController.php");
require_once(__CA_LIB_DIR__."/Search/ObjectSearch.php");
require_once(__CA_LIB_DIR__."/Search/CollectionSearch.php");

class ObjectCollectionHierarchyController extends BaseLookupController {
	# -------------------------------------------------------
	protected $opb_uses_hierarchy_browser = false;
	protected $ops_table_name = 'ca_collections';		// name of "subject" table (what we're editing)
	protected $ops_name_singular = 'collection';
	protected $ops_search_class = 'CollectionSearch';
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		if ($this->ops_search_class) { require_once(__CA_LIB_DIR__."/Search/".$this->ops_search_class.".php"); }
		require_once(__CA_MODELS_DIR__."/".$this->ops_table_name.".php");
		parent::__construct($po_request, $po_response, $pa_view_paths);
		$this->opo_item_instance = new $this->ops_table_name();
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function Get($pa_additional_query_params=null, $pa_options=null) {
		$ps_query = $this->request->getParameter('term', pString);
		$pb_exact = $this->request->getParameter('exact', pInteger);
		$ps_exclude = $this->request->getParameter('exclude', pString);
		$va_excludes = explode(";", $ps_exclude);
		$ps_type = $this->request->getParameter('type', pString);
		$ps_types = $this->request->getParameter('types', pString);
		$pb_no_subtypes = (bool)$this->request->getParameter('noSubtypes', pInteger);
		$pb_quickadd = (bool)$this->request->getParameter('quickadd', pInteger);
		$pb_no_inline = (bool)$this->request->getParameter('noInline', pInteger);

		$t_object = new ca_objects();
		$t_collection = new ca_collections();

		$o_config = Configuration::load();

		if (!($pn_limit = $this->request->getParameter('limit', pInteger))) { $pn_limit = 100; }
		$va_items = [];
		if (($vn_str_len = mb_strlen($ps_query)) > 0) {
			if ($vn_str_len < 3) { $pb_exact = true; }		// force short strings to be an exact match (using a very short string as a stem would perform badly and return too many matches in most cases)

			$o_object_search = new ObjectSearch();
			$o_collection_search = new CollectionSearch();

			$pa_types = [];
			if ($ps_types) {
				$pa_types = explode(';', $ps_types);
			} else {
				if ($ps_type) {
					$pa_types = array($ps_type);
				}
			}

			// Get type_ids
			$vs_type_query = '';
			$va_ids = [];
			if (sizeof($pa_types)) {
				$va_types = $this->opo_item_instance->getTypeList();
				$va_types_proc = [];
				foreach($va_types as $vn_type_id => $va_type) {
					$va_types_proc[$vn_type_id] = $va_types_proc[$va_type['idno']] = $vn_type_id;
				}
				foreach($pa_types as $ps_type) {
					if (isset($va_types_proc[$ps_type])) {
						$va_ids[$va_types_proc[$ps_type]] = true;
					}
				}
				$va_ids = array_keys($va_ids);

				if (sizeof($va_ids) > 0) {
					$t_list = new ca_lists();

					if (!$pb_no_subtypes) {
						foreach($va_ids as $vn_id) {
							$va_children = $t_list->getItemsForList($this->opo_item_instance->getTypeListCode(), array('item_id' => $vn_id, 'idsOnly' => true));
							$va_ids = array_merge($va_ids, $va_children);
						}
						$va_ids = array_flip(array_flip($va_ids));
					}
					$o_object_search->addResultFilter($this->opo_item_instance->tableName().'.'.$this->opo_item_instance->getTypeFieldName(), 'IN', join(",", $va_ids));
				}
			} else {
				$va_ids = null;
			}

			// add any additional search elements
			$vs_additional_query_params = '';
			if (is_array($pa_additional_query_params) && sizeof($pa_additional_query_params)) {
				$vs_additional_query_params = ' AND ('.join(' AND ', $pa_additional_query_params).')';
			}


			// add filters
			if (isset($pa_options['filters']) && is_array($pa_options['filters']) && sizeof($pa_options['filters'])) {
				foreach($pa_options['filters'] as $va_filter) {
					$o_object_search->addResultFilter($va_filter[0], $va_filter[1], $va_filter[2]);
				}
			}

			// do search
			$va_opts = array('exclude' => $va_excludes, 'limit' => $pn_limit);

			if (($vn_restrict_to_hier_id = $this->request->getParameter('currentHierarchyOnly', pInteger))) {
				$o_object_search->addResultFilter('ca_objects.hier_object_id', '=', (int)$vn_restrict_to_hier_id);
			}

			if (!$pb_exact) {
				$o_search_config = caGetSearchConfig();
				$ps_query = trim(preg_replace("![".str_replace("!", "\\!", $o_search_config->get('search_tokenizer_regex'))."]+!u", " ", $ps_query));
			}

			if (!(is_array($va_object_sort = $o_config->getList('ca_objects_lookup_sort'))) || !sizeof($va_object_sort)) {
				$va_object_sort = array('ca_objects.idno_sort');
			}

			$qr_res = $o_object_search->search(
				'('.$ps_query.(intval($pb_exact) ? '' : '*').')'.$vs_type_query.$vs_additional_query_params,
				array('search_source' => 'Lookup', 'no_cache' => false, 'sort' => $va_object_sort)
			);

			$qr_res->setOption('prefetch', $pn_limit);
			$qr_res->setOption('dontPrefetchAttributes', true);

			if (is_array($va_objects = caProcessRelationshipLookupLabel($qr_res, new ca_objects(), $va_opts))) {
				foreach($va_objects as $vn_object_id => $va_object) {
					$va_objects[$vn_object_id]['id'] = 'ca_objects-'.$va_objects[$vn_object_id]['id'];
				}
			}

			//if ($vs_hier_fld && ($vn_restrict_to_hier_id = $this->request->getParameter('currentHierarchyOnly', pInteger))) {
			//$o_collection_search->addResultFilter('ca_collections.hier_collection_id', '=', (int)$vn_restrict_to_hier_id);

			// How to restrict objects?
			//}

			if (!(is_array($va_col_sort = $o_config->getList('ca_collections_lookup_sort'))) || !sizeof($va_col_sort)) {
				$va_col_sort = array('ca_collections.idno_sort');
			}

			$qr_res = $o_collection_search->search(
				'('.$ps_query.(intval($pb_exact) ? '' : '*').')'.$vs_type_query.$vs_additional_query_params,
				array('search_source' => 'Lookup', 'no_cache' => false, 'sort' => $va_col_sort)
			);

			$qr_res->setOption('prefetch', $pn_limit);
			$qr_res->setOption('dontPrefetchAttributes', true);

			if (is_array($va_collections = caProcessRelationshipLookupLabel($qr_res, new ca_collections(), $va_opts))) {
				foreach($va_collections as $vn_collection_id => $va_collection) {
					$va_collections[$vn_collection_id]['id'] = 'ca_collections-'.$va_collections[$vn_collection_id]['id'];
				}
			}
		}
		if (!is_array($va_objects)) { $va_objects = []; }
		if (!is_array($va_collections)) { $va_collections = []; }

		if (!sizeof($va_objects) && !sizeof($va_collections)) {
			$va_objects[-1] = _t('No matches found for <em>%1</em>', $ps_query);
		}

		$this->view->setVar('object_list', $va_objects);
		$this->view->setVar('collection_list', $va_collections);
		return $this->render(str_replace(' ', '_', 'ajax_object_collection_list_html.php'));
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyLevel() {
		$t_item = $this->opo_item_instance;
		if (!$t_item->isHierarchical()) { return; }

		$ps_bundle = (string)$this->request->getParameter('bundle', pString);
		$pa_ids = explode(";", $ps_ids = $this->request->getParameter('id', pString));
		if (!sizeof($pa_ids)) { $pa_ids = array(null); }

		$va_level_data = $this->GetHierarchyLevelData($pa_ids);

		if (!$this->request->getParameter('init', pInteger)) {
			// only set remember "last viewed" if the load is done interactively
			// if the GetHierarchyLevel() call is part of the initialization of the hierarchy browser
			// then all levels are loaded, sometimes out-of-order; if we record these initialization loads
			// as the 'last viewed' we can end up losing the true 'last viewed' value
			//
			// ... so the hierbrowser passes an extra 'init' parameters set to 1 if the GetHierarchyLevel() call
			// is part of a browser initialization
			Session::setVar($this->ops_table_name.'_'.$ps_bundle.'_browse_last_id', array_pop($pa_ids));
		}

		$this->view->setVar(str_replace(' ', '_', $this->ops_name_singular).'_list', $va_level_data);

		return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_level_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 */
	protected function GetHierarchyLevelData($pa_ids) {

		$o_config = Configuration::load();
		$t_object = new ca_objects();

		$va_level_data = [];
		foreach($pa_ids as $vn_i => $pn_id) {
			$va_params = $this->getItemIDComponents($pn_id);
			$vs_table = $va_params['table'];
			$vn_id = $va_params['id'];
			$vn_start = $va_params['start'];
			
			if ((($vn_max_items_per_page = $this->request->getParameter('max', pInteger)) < 1) || ($vn_max_items_per_page > 1000)) {
				$vn_max_items_per_page = null;
			}

			$vn_item_count = 0;

			$t_item = Datamodel::getInstanceByTableName($vs_table, true);
			$vs_label_table_name = $t_item->getLabelTableName();
			$vs_label_display_field_name = $t_item->getLabelDisplayField();
			$vs_pk = $t_item->primaryKey();

			$vs_level_pk = $vs_pk;

			if($vn_start < 0) { $vn_start = 0; }

			$va_items_for_locale = [];
			if ((!($vn_id)) && method_exists($t_item, "getHierarchyList")) {
				$vn_id = $this->request->getParameter('root_item_id', pString);

				$va_params = $this->getItemIDComponents($vn_id);
				$vs_table = $va_params['table'];
				$vn_id = $va_params['id'];
				$vn_start = $va_params['start'];
				$t_item = Datamodel::getInstanceByTableName($vs_table, true);

				$vs_label_table_name = $t_item->getLabelTableName();
				$vs_label_display_field_name = $t_item->getLabelDisplayField();
				$vs_pk = $t_item->primaryKey();

				$t_item->load($vn_id);

				$va_tmp = array(
					$vs_pk => $vn_id = $t_item->get($vs_table.'.'.$vs_pk),
					'item_id' => $vs_table.'-'.$vn_id,
					'parent_id' => $t_item->get($vs_table.'.parent_id'),
					'idno' => $t_item->get($vs_table.'.idno'),
					$vs_label_display_field_name => $t_item->get($vs_table.'.preferred_labels.'.$vs_label_display_field_name),
					'locale_id' => $t_item->get($vs_table.'.'.'locale_id')
				);
				if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = $va_tmp['idno']; }
				if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = '???'; }

				if (!($vs_item_template = trim($o_config->get("{$vs_table}_hierarchy_browser_display_settings")))) {
					$vs_item_template = "^{$vs_table}.preferred_labels.{$vs_label_display_field_name}";
				}
				$va_tmp['name'] = caProcessTemplateForIDs($vs_item_template, $vs_table, array($va_tmp[$vs_pk]));

				// Child count is only valid if has_children is not null
				$va_tmp['children'] = $t_item->get('has_children') ? (int)$t_item->get('child_count') : 1;	// TODO: fix

				$va_items[$va_tmp[$vs_pk]][$va_tmp['locale_id']] = $va_tmp;
				$va_items_for_locale = caExtractValuesByUserLocale($va_items);

			} elseif ($t_item->load($vn_id)) {		// id is the id of the parent for the level we're going to return

				$va_additional_wheres = [];
				$t_label_instance = $t_item->getLabelTableInstance();
				if ($t_label_instance && $t_label_instance->hasField('is_preferred')) {
					$va_additional_wheres[] = "(({$vs_label_table_name}.is_preferred = 1) OR ({$vs_label_table_name}.is_preferred IS NULL))";
				}

				$va_sorts = $o_config->getList($vs_table.'_hierarchy_browser_sort_values');
				if (!is_array($va_sorts) || !sizeof($va_sorts)) {
					$va_sorts = [];
				}
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

				$vs_sort_dir = strtolower($o_config->get($vs_table.'_hierarchy_browser_sort_direction'));
				if (!in_array($vs_sort_dir, array('asc', 'desc'))) {
					$vs_sort_dir = 'asc';
				}

				$qr_children = $t_item->getHierarchyChildrenAsQuery(
					$t_item->getPrimaryKey(),
					array(
						'additionalTableToJoin' => $vs_label_table_name,
						'additionalTableJoinType' => 'LEFT',
						'additionalTableSelectFields' => array($vs_label_display_field_name, 'locale_id'),
						'additionalTableWheres' => $va_additional_wheres,
						'returnChildCounts' => true,
						'sort' => $va_sorts,
						'sortDirection' => $vs_sort_dir
					)
				);

				$va_items = [];

				if (!($vs_item_template = trim($o_config->get("{$vs_table}_hierarchy_browser_display_settings")))) {
					$vs_item_template = "^{$vs_table}.preferred_labels.{$vs_label_display_field_name}";
				}

				$va_child_counts = [];
				$vn_c = 0;

				$vn_item_count = $qr_children->numRows();

				//$qr_children->seek($vn_start);
				$va_item_ids = [];
				
				$qr_children = caMakeSearchResult($vs_table, array_slice($qr_children->getAllFieldValues($vs_pk), $vn_start, $vn_max_items_per_page));
				while($qr_children->nextHit()) {
					$va_tmp = array(
						$vs_pk => $vn_id = $qr_children->get($vs_table.'.'.$vs_pk),
						'item_id' => $vs_table.'-'.$vn_id,
						'parent_id' => $qr_children->get($vs_table.'.parent_id'),
						'idno' => $qr_children->get($vs_table.'.idno'),
						'locale_id' => $qr_children->get($vs_table.'.'.'locale_id')
					);
					if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = $va_tmp['idno']; }
					if (!$va_tmp[$vs_label_display_field_name]) { $va_tmp[$vs_label_display_field_name] = '???'; }

					$va_tmp['name'] = caProcessTemplateForIDs($vs_item_template, $vs_table, array($va_tmp[$vs_pk]));

					// Child count is only valid if has_children is not null
					$va_tmp['children'] = sizeof($qr_children->get("{$vs_table}.children.{$vs_pk}", ['returnAsArray' => true]));

					if ($t_item->tableName() == 'ca_collections') {
						$va_tmp['children'] += sizeof($qr_children->get('ca_objects.object_id', ['returnAsArray' => true, 'restrictToRelationshipTypes' => [$vs_object_collection_rel_type]]));
					}

					if (is_array($va_sorts)) {
						$vs_sort_acc = [];
						foreach($va_sorts as $vs_sort) {
							$vs_sort_acc[] = $qr_children->get($vs_sort);
						}
						$va_tmp['sort'] = join(";", $vs_sort_acc);
					}

					$va_items[$va_tmp['item_id']][$va_tmp['locale_id']] = $va_tmp;
					$va_item_ids[] = $vn_id;

					$vn_c++;
					if (!is_null($vn_max_items_per_page) && ($vn_c >= $vn_max_items_per_page)) { break; }
				}

				// if sorts are set, re-sort $va_items using a search result here. that way we can sort on non-intrinsics
				if(sizeof($va_sorts) && sizeof($va_item_ids)) {
					$va_sorted_items = [];
					$o_res = caMakeSearchResult($vs_table, $va_item_ids, array(
						'sort' => $va_sorts,
						'sortDirection' => $vs_sort_dir
					));

					while($o_res->nextHit()) {
						$va_sorted_items[$vs_table.'-'.$o_res->get($vs_table.'.'.$vs_pk)] = $va_items[$vs_table.'-'.$o_res->get($vs_table.'.'.$vs_pk)];
					}

					$va_items = $va_sorted_items;
				}


				if ($t_item->tableName() == 'ca_collections') {
					$va_object_sorts =  $o_config->getList('ca_objects_hierarchy_browser_sort_values');
					if (!is_array($va_object_sorts) || !sizeof($va_object_sorts)) {
						$va_object_sorts = [];
					}

					$vs_object_sort_dir = strtolower($o_config->get('ca_objects_hierarchy_browser_sort_direction'));
					if (!in_array($vs_object_sort_dir, array('asc', 'desc'))) {
						$vs_object_sort_dir = 'asc';
					}

					$vs_object_collection_rel_type = $o_config->get('ca_objects_x_collections_hierarchy_relationship_type');
					
					$vn_count = 0;
					$va_cross_table_items = $t_item->getRelatedItems(
						'ca_objects',
						array(
							'sort' => $va_object_sorts,
							'sortDirection' => $vs_object_sort_dir,
							'restrictToRelationshipTypes' => array($vs_object_collection_rel_type),
							'start' => $vn_start,
							'limit' => $vn_max_items_per_page
						), $vn_count
					);

					$vn_item_count += $vn_count;
					$va_ids = [];
					foreach($va_cross_table_items as $vn_x_item_id => $va_x_item) {
						$va_items['ca_objects-'.$va_x_item['object_id']][$va_x_item['locale_id']] = $va_x_item;

						$va_items['ca_objects-'.$va_x_item['object_id']][$va_x_item['locale_id']]['item_id'] = 'ca_objects-'.$va_x_item['object_id'];
						$va_items['ca_objects-'.$va_x_item['object_id']][$va_x_item['locale_id']]['parent_id'] = $vn_id;

						unset($va_items['ca_objects-'.$va_x_item['object_id']][$va_x_item['locale_id']]['labels']);

						$va_items['ca_objects-'.$va_x_item['object_id']][$va_x_item['locale_id']]['children'] = 0;

						$va_ids[] = $va_x_item['object_id'];
					}

					if (!($vs_item_template = trim($o_config->get("ca_objects_hierarchy_browser_display_settings")))) {
						$vs_item_template = "^ca_objects.preferred_labels.name";
					}
					if(sizeof($va_ids)) {
						$vs_level_pk = 'object_id';
						$va_child_counts = $t_object->getHierarchyChildCountsForIDs($va_ids);
						$va_templates = caProcessTemplateForIDs($vs_item_template, 'ca_objects', $va_ids, array('returnAsArray' => true));

						foreach($va_child_counts as $vn_id => $vn_c) {
							$va_items['ca_objects-'.$vn_id][$va_x_item['locale_id']]['children'] = $vn_c;
						}
						foreach($va_ids as $vn_i => $vn_id) {
							$va_items['ca_objects-'.$vn_id][$va_x_item['locale_id']]['name'] = $va_templates[$vn_i];
						}
					}
				}

				$va_items_for_locale = caExtractValuesByUserLocale($va_items);
				$vs_rank_fld = $t_item->getProperty('RANK');
			}

			$va_items_for_locale['_sortOrder'] = array_keys($va_items_for_locale);
			$va_items_for_locale['_primaryKey'] = $vs_level_pk;	// pass the name of the primary key so the hierbrowser knows where to look for item_id's
			$va_items_for_locale['_itemCount'] = $vn_item_count;

			$va_level_data[$pn_id] = $va_items_for_locale;
		}

		return $va_level_data;
	}
	# -------------------------------------------------------
	/**
	 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
	 * Returned data is JSON format
	 */
	public function GetHierarchyAncestorList() {
		$pn_id = $this->request->getParameter('id', pString);

		$va_params = $this->getItemIDComponents($pn_id, 'ca_objects');
		$vs_table = $va_params['table'];
		$vn_id = $va_params['id'];
		$vn_start = $va_params['start'];

		$t_item = Datamodel::getInstanceByTableName($vs_table, true);
		$t_item->load($vn_id);
		$va_ancestors = [];
		if ($t_item->getPrimaryKey()) {
			$va_ancestors = array_reverse($t_item->getHierarchyAncestors(null, array('includeSelf' => true, 'idsOnly' => true)));
		}
		$vn_top_id = $va_ancestors[0];
		foreach($va_ancestors as $vn_i => $vn_ancestor_id) {
			$va_ancestors[$vn_i] = $vs_table.'-'.$vn_ancestor_id;
		}

		// get collections
		if ($vs_table == 'ca_objects') {
			$o_config = Configuration::load();
			$vs_object_collection_rel_type = $o_config->get('ca_objects_x_collections_hierarchy_relationship_type');

			$t_item->load($vn_top_id);
			// try to pull related collections - the first one is considered the parent
			$va_cross_table_items = $t_item->getRelatedItems('ca_collections', array('restrictToRelationshipTypes' => array($vs_object_collection_rel_type)));

			if(is_array($va_cross_table_items)) {
				$t_collection = new ca_collections();
				foreach($va_cross_table_items as $vn_x_item_id => $va_x_item) {
					array_unshift($va_ancestors, 'ca_collections-'.$va_x_item['collection_id']);

					if (!($va_collection_ancestor_list = $t_collection->getHierarchyAncestors($va_x_item['collection_id'], array(
						'additionalTableToJoin' => 'ca_collection_labels',
						'additionalTableJoinType' => 'LEFT',
						'additionalTableSelectFields' => array('name', 'locale_id'),
						'additionalTableWheres' => array('(ca_collection_labels.is_preferred = 1 OR ca_collection_labels.is_preferred IS NULL)'),
						'includeSelf' => false
					)))) {
						$va_collection_ancestor_list = [];
					}
					foreach($va_collection_ancestor_list as $vn_id => $va_collection_ancestor) {
						array_unshift($va_ancestors, 'ca_collections-'.$va_collection_ancestor['NODE']['collection_id']);
					}

					break;	// for now only show first one
				}
			}
		}

		$this->view->setVar('ancestors', $va_ancestors);

		return $this->render(str_replace(' ', '_', $this->ops_name_singular).'_hierarchy_ancestors_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 */
	public function Edit() {
		$vs_id = $this->request->getParameter('id', pString);
		list($vs_table, $vn_id) = explode('-', $vs_id);
		if(!$vn_id) { $vn_id = $vs_table; $vs_table = 'ca_collections'; }
		$this->response->setRedirect(caEditorUrl($this->request, $vs_table, $vn_id));
		return;
	}
	# -------------------------------------------------------
	/**
	 *
	 *
	 */
	private function getItemIDComponents($ps_id, $ps_table_default='ca_collections') {
		$vn_start = 0;
		$va_tmp = explode(":", $ps_id);
		$va_tmp2 = explode("-", $va_tmp[0]);

		if (sizeof($va_tmp2) > 1) {
			$vs_table = $va_tmp2[0];
			$vn_id = $va_tmp2[1];
		} else {
			$vs_table = $ps_table_default;
			$vn_id = (int)$va_tmp[0];
		}
		$vn_start = (int)$va_tmp[1];

		return array(
			'table' => $vs_table,
			'id' => $vn_id,
			'start' => $vn_start
		);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function SetSortOrder() {
		$ps_after_id 	=  $this->getRequest()->getParameter('after_id', pString);
		$ps_id 			=  $this->getRequest()->getParameter('id', pString);
		
		$va_tmp = explode('-', $ps_id);
		if (in_array($va_tmp[0], ['ca_objects', 'ca_collections'])) {
			$this->ops_table_name = $va_tmp[0];
		}
		$this->getRequest()->setParameter('id', (int)$va_tmp[1]);
		
		$va_tmp = explode('-', $ps_after_id);
		$this->getRequest()->setParameter('after_id', (int)$va_tmp[1]);
		
		return parent::SetSortOrder();
	}		
	# -------------------------------------------------------
}