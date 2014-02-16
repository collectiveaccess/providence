<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/BrowseEngine.php : Base class for browse interfaces
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/core/BaseFindEngine.php');
 	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
 	require_once(__CA_LIB_DIR__.'/ca/Browse/BrowseResult.php');
 	require_once(__CA_LIB_DIR__.'/ca/Browse/BrowseCache.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 	require_once(__CA_APP_DIR__.'/helpers/searchHelpers.php');
	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
	
 	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
	require_once(__CA_MODELS_DIR__.'/ca_acl.php');
 
	class BrowseEngine extends BaseFindEngine {
		# ------------------------------------------------------
		# Properties
		# ------------------------------------------------------
		private $opn_browse_table_num;
		private $ops_browse_table_name;
		private $opo_ca_browse_cache;
		
		/**
		 * @var subject type_id to limit browsing to (eg. only browse ca_objects with type_id = 10)
		 */
		private $opa_browse_type_ids = null;
		private $opb_dont_expand_type_restrictions = false;
		
		private $opo_datamodel;
		protected $opo_db;
		
		private $opo_config;
		private $opo_ca_browse_config;
		private $opa_browse_settings;
		
		private $opa_result_filters;
		
		private $ops_facet_group = null;
		
		private $opb_criteria_have_changed = false;
		# ------------------------------------------------------
		static $s_type_id_cache = array();
		# ------------------------------------------------------
		/**
		 *
		 */
		public function __construct($pm_subject_table_name_or_num, $pn_browse_id=null, $ps_browse_context='') {
			$this->opo_datamodel = Datamodel::load();
			$this->opo_db = new Db();
			
			$this->opa_result_filters = array();
			
			if (is_numeric($pm_subject_table_name_or_num)) {
				$this->opn_browse_table_num = intval($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = $this->opo_datamodel->getTableName($this->opn_browse_table_num);
			} else {
				$this->opn_browse_table_num = $this->opo_datamodel->getTableNum($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = $pm_subject_table_name_or_num;
			}
			
			$this->opo_config = Configuration::load();
			$this->opo_ca_browse_config = Configuration::load($this->opo_config->get('browse_config'));
			$this->opa_browse_settings = $this->opo_ca_browse_config->getAssoc($this->ops_browse_table_name);
			
			// Add "virtual" search facet - allows one to seed a browse with a search
			$this->opa_browse_settings['facets']['_search'] = array(
				'label_singular' => _t('Search'),
				'label_plural' => _t('Searches')
			);
			$this->_processBrowseSettings();
			
			$this->opo_ca_browse_cache = new BrowseCache();
			if ($pn_browse_id) {
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num); 
				$this->opo_ca_browse_cache->load($pn_browse_id);
			} else {
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num);
				$this->setContext($ps_browse_context);
			}
		}
		# ------------------------------------------------------
		/**
		 * Dynamically insert facet configuration
		 *
		 * @param string $ps_facet_name The name of the facet to insert
		 * @return bool true if add succeeded
		 */
		public function addFacetConfiguration($ps_facet_name, $pa_options) {
			$this->opa_browse_settings['facets'][$ps_facet_name] = $pa_options;
			$this->_processBrowseSettings();
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Forces reload of the browse instance (ie. a cached browse) from the database
		 *
		 * @param int $pn_browse_id The id of the browse to reload
		 * @return bool true if reload succeeded, false if browse_id was invalid
		 */
		public function reload($pn_browse_id) {
			$this->opo_ca_browse_cache = new BrowseCache();
			$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num);
			
			return (bool)$this->opo_ca_browse_cache->load($pn_browse_id);
		}
		# ------------------------------------------------------
		/**
		 * Rewrite browse config settings as needed before starting actual processing of browse
		 */
		private function _processBrowseSettings() {
			$va_revised_facets = array();
			foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
			
				// generate_facets_for_types config directive triggers auto-generation of facet config for each type of an authority item
				// it's typically employed to provide browsing of occurrences where the various types are unrelated
				// you can also use this on other authorities to provide a finer-grained browse without having to know the type hierarchy ahead of time
				if (($va_facet_info['type'] === 'authority') && isset($va_facet_info['generate_facets_for_types']) && $va_facet_info['generate_facets_for_types']) {
					// get types for authority
					$t_table = $this->opo_datamodel->getInstanceByTableName($va_facet_info['table'], true);
					
					$va_type_list = $t_table->getTypeList();
					
					// auto-generate facets
					foreach($va_type_list as $vn_type_id => $va_type_info) {
						if ($va_type_info['is_enabled']) {
							$va_facet_info = array_merge($va_facet_info, array(
								'label_singular' => $va_type_info['name_singular'],
								'label_singular_with_indefinite_article' => _t('a').' '.$va_type_info['name_singular'],
								'label_plural' => $va_type_info['name_plural'],
								'restrict_to_types' => array($va_type_info['item_id'])
							));
							$va_revised_facets[$vs_facet_name.'_'.$vn_type_id] = $va_facet_info;
						}
					}
				} else {
					$va_revised_facets[$vs_facet_name] = $va_facet_info;
				}
			}
			
			
			// rewrite single_value settings for attribute and fieldList facets
			foreach($va_revised_facets as $vs_facet => $va_facet_info) {
				if (!((isset($va_facet_info['single_value'])) && strlen($va_facet_info['single_value']))) { continue; }
				
				switch($va_facet_info['type']) {
					case 'attribute':
						$t_element = new ca_metadata_elements();
						if ($t_element->load(array('element_code' => $va_facet_info['element_code']))) {
							if (($t_element->get('datatype') == 3) && ($vn_list_id = $t_element->get('list_id'))) { // 3 = list
								if ($vn_item_id = caGetListItemID($vn_list_id, $va_facet_info['single_value'])) {
									$va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
								}
							}
						}
						break;
					case 'fieldList':
						$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
						if ($vn_item_id = caGetListItemID($t_instance->getFieldInfo($va_facet_info['field'], 'LIST_CODE'), $va_facet_info['single_value'])) {
							$va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
						}
						break;
				}
			}
			
			$this->opa_browse_settings['facets'] = $va_revised_facets;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getBrowseID() {
			return $this->opo_ca_browse_cache->getCacheKey();
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getSubject() {
			return $this->opn_browse_table_num;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getSubjectInstance() {
			return $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
		}
		# ------------------------------------------------------
		/**
		 * Sets the current browse context. 
		 * Separate cache namespaces are maintained for each browse context; this means that
		 * if you do the same browse in different contexts each will be cached separately. This 
		 * is handy when you have multiple interfaces (say the cataloguing back-end and a public front-end)
		 * using the same browse engine and underlying cache tables
		 */
		public function setContext($ps_browse_context) {
			$va_params = $this->opo_ca_browse_cache->setParameter('context', $ps_browse_context);
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Returns currently set browse context
		 */
		public function getContext() {
			return ($vs_context = $this->opo_ca_browse_cache->getParameter('context')) ? $vs_context : '';
		}
		# ------------------------------------------------------
		# Add/remove browse criteria
		# ------------------------------------------------------
		/**
		 * @param $ps_facet_name - name of facet for which to add criteria
		 * @param $pa_row_ids - one or more facet values to browse on
		 *
		 * @return boolean - true on success, null on error
		 */
		public function addCriteria($ps_facet_name, $pa_row_ids) {
			if (is_null($pa_row_ids)) { return null;}
			if ($ps_facet_name !== '_search') {
				if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
				if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			}
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			foreach($pa_row_ids as $vn_row_id) {
				$va_criteria[$ps_facet_name][urldecode($vn_row_id)] = true;
			}
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function removeCriteria($ps_facet_name, $pa_row_ids) {
			if (is_null($pa_row_ids)) { return null;}
			if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
			if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			
			foreach($pa_row_ids as $vn_row_id) {
				unset($va_criteria[$ps_facet_name][urldecode($vn_row_id)]);
				if(is_array($va_criteria[$ps_facet_name]) && !sizeof($va_criteria[$ps_facet_name])) {
					unset($va_criteria[$ps_facet_name]);
				}
			}
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function criteriaHaveChanged() {
			return $this->opb_criteria_have_changed;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function numCriteria() {
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if (isset($va_criteria) && is_array($va_criteria)) {
				$vn_c = 0;
				foreach($va_criteria as $vn_table_num => $va_criteria_list) {
					$vn_c += sizeof($va_criteria_list);
				}
				return $vn_c;
			}
			return 0;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function removeAllCriteria($ps_facet_name=null) {
			if ($ps_facet_name && !$this->isValidFacetName($ps_facet_name)) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			if($ps_facet_name) {
				$va_criteria[$ps_facet_name] = array();
			} else {
				$va_criteria = array();
			}
			
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);
			
			$this->opb_criteria_have_changed = true;
			
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriteria($ps_facet_name=null) {
			if ($ps_facet_name && (!$this->isValidFacetName($ps_facet_name))) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			
			if($ps_facet_name) {
				return isset($va_criteria[$ps_facet_name]) ? $va_criteria[$ps_facet_name] : null;
			}
			return isset($va_criteria) ? $va_criteria : null;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriteriaWithLabels($ps_facet_name=null) {
			if ($ps_facet_name && (!$this->isValidFacetName($ps_facet_name))) { return null; }
			
			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			
			$va_criteria_with_labels = array();
			if($ps_facet_name) {
				$va_criteria = isset($va_criteria[$ps_facet_name]) ? $va_criteria[$ps_facet_name] : null;
				
				foreach($va_criteria as $vm_criterion => $vn_tmp) {
					$va_criteria_with_labels[$vm_criterion] = $this->getCriterionLabel($ps_facet_name, $vm_criterion);
				}
			} else {
				if (is_array($va_criteria)) {
					foreach($va_criteria as $vs_facet_name => $va_criteria_by_facet) {
						foreach($va_criteria_by_facet as $vm_criterion => $vn_tmp) {
							$va_criteria_with_labels[$vs_facet_name][$vm_criterion] = $this->getCriterionLabel($vs_facet_name, $vm_criterion);
						}
					}
				}
			}
			return $va_criteria_with_labels;	
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function getCriterionLabel($ps_facet_name, $pn_row_id) {
			if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return null; }
			
			switch($va_facet_info['type']) {
				# -----------------------------------------------------
				case 'has':
					$vs_yes_text = (isset($va_facet_info['label_yes']) && $va_facet_info['label_yes']) ? $va_facet_info['label_yes'] : _t('Yes');
					$vs_no_text = (isset($va_facet_info['label_no']) && $va_facet_info['label_no']) ? $va_facet_info['label_no'] : _t('No');
					return ((bool)$pn_row_id) ? $vs_yes_text : $vs_no_text;
					break;
				# -----------------------------------------------------
				case 'label':
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName((isset($va_facet_info['relative_to']) && $va_facet_info['relative_to']) ? $va_facet_info['relative_to'] : $this->ops_browse_table_name, true))) { break; }
					if (!$t_table->load($pn_row_id)) { return '???'; }
					
					return $t_table->getLabelForDisplay();
					break;
				# -----------------------------------------------------
				case 'authority':
					if (!($t_table = $this->opo_datamodel->getInstanceByTableName($va_facet_info['table'], true))) { break; }
					if (!$t_table->load($pn_row_id)) { return '???'; }
					
					return $t_table->getLabelForDisplay();
					break;
				# -----------------------------------------------------
				case 'attribute':
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return urldecode($pn_row_id);
					}
					
					$vn_element_id = $t_element->getPrimaryKey();
					switch($t_element->get('datatype')) {
						case 3: // list
							$t_list = new ca_lists();
							return $t_list->getItemFromListForDisplayByItemID($t_element->get('list_id'), $pn_row_id , true);
							break;
						default:
							return urldecode($pn_row_id);
							break;
					}
					
					break;
				# -----------------------------------------------------
				case 'field':
					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'normalizedDates':
					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'fieldList':
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					$vs_field_name = $va_facet_info['field'];
					$va_field_info = $t_item->getFieldInfo($vs_field_name);
					
					$t_list = new ca_lists();
					
					if ($vs_list_name = $va_field_info['LIST_CODE']) {
						$t_list_item = new ca_list_items($pn_row_id);
						if ($vs_tmp = $t_list_item->getLabelForDisplay()) {
							return $vs_tmp;
						}
						return '???';
					} else {
						if ($vs_list_name = $va_field_info['LIST']) {
							if (is_array($va_list_items = $t_list->getItemsForList($vs_list_name))) {
								$va_list_items = caExtractValuesByUserLocale($va_list_items);
								foreach($va_list_items as $vn_id => $va_list_item) {
									if ($va_list_item['item_value'] == $pn_row_id) {
										return $va_list_item['name_plural'];
									}
								}
							}
						}
					}
						
					if(isset($va_field_info['BOUNDS_CHOICE_LIST'])) {
						$va_choice_list = $va_field_info['BOUNDS_CHOICE_LIST'];
						if (is_array($va_choice_list)) {
							foreach($va_choice_list as $vs_val => $vn_id) {
								if ($vn_id == $pn_row_id) {
									return $vs_val;
								}
							}
						}
					}
					return '???';
					break;
				# -----------------------------------------------------
				default:
					if ($ps_facet_name == '_search') { return $pn_row_id; }
					return 'Invalid type';
					break;
				# -----------------------------------------------------
			}
		}
		# ------------------------------------------------------
		# Facets
		# ------------------------------------------------------
		/**
		 * Returns list of all facets configured for this for browse subject
		 */
		public function getInfoForFacets() {
			return $this->opa_browse_settings['facets'];	
		}
		# ------------------------------------------------------
		/**
		 * Return info for specified facet, or null if facet is not valid
		 */
		public function getInfoForFacet($ps_facet_name) {
			if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			$va_facets = $this->opa_browse_settings['facets'];	
			return $va_facets[$ps_facet_name];
		}
		# ------------------------------------------------------
		/**
		 * Returns true if facet exists, false if not
		 */
		public function isValidFacetName($ps_facet_name) {
			$va_facets = $this->getInfoForFacets();
			return (isset($va_facets[$ps_facet_name])) ? true : false;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all valid facet names
		 */
		public function getFacetList() {
			if (!is_array($this->opa_browse_settings)) { return null; }
			
			// Facets can be restricted such that they are applicable only to certain types when browse type restrictions are in effect.
			// These restrictions are distinct from per-facet 'restrict_to_type' and 'restrict_to_relationship_types' settings, which affect
			// what items and *included* in the browse. 'restrict_to_type' restricts a browse to specific types of items (eg. only entities of type "individual" are returned in the facet);
			// 'restrict_to_relationship_types' restricts authority facets to items related to the browse subject by specific relationship types. By contrast, the
			// 'type_restrictions' setting indicates that a facet is only valid for specific types when a browse is limited to specific types (eg. there is a browse type
			// restriction in effect. Browse type restrictions apply to the browse result, not the facet content (eg. on an object browse, a type restriction of "documents" would limit 
			// the browse to only consider and return object of that type).
			$va_type_restrictions = $this->getTypeRestrictionList();
			
			$t_list = new ca_lists();
			$t_subject = $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
			$vs_type_list_code = $t_subject->getTypeListCode();
			
			$va_criteria_facets = is_array($va_tmp = $this->getCriteria()) ? array_keys($this->getCriteria()) : array(); 
			
			// 
			if (is_array($va_type_restrictions) && sizeof($va_type_restrictions)) {
				$va_facets = array();
				foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
					if (isset($va_facet_info['requires']) && !is_array($va_facet_info['requires']) && $va_facet_info['requires']) { $va_facet_info['requires'] = array($va_facet_info['requires']); }
					//
					// enforce "requires" setting, which allows one to specify that a given facet should old appear if any one
					// of the specified "required" facets is present in the criteria
					//
					$vb_facet_is_meets_requirements = true;
					if (isset($va_facet_info['requires']) && is_array($va_facet_info['requires'])) {
						$vb_facet_is_meets_requirements = false;
						foreach($va_facet_info['requires'] as $vs_req_facet) {
							if (in_array($vs_req_facet, $va_criteria_facets)) {
								$vb_facet_is_meets_requirements = true;
								break; 
							}
						}
					}
					if ($vb_facet_is_meets_requirements) {
						if (isset($va_facet_info['type_restrictions']) && is_array($va_facet_restrictions = $va_facet_info['type_restrictions']) && sizeof($va_facet_restrictions)) {
							foreach($va_facet_restrictions as $vs_code) {
								if ($va_item = $t_list->getItemFromList($vs_type_list_code, $vs_code)) {
									if (in_array($va_item['item_id'], $va_type_restrictions)) {
										$va_facets[] = $vs_facet_name;
										break;
									}
								}
							}
						} else {
							$va_facets[] = $vs_facet_name;
						}
					}
				}
				return $va_facets;
			} else {
				//
				// enforce "requires" setting, which allows one to specify that a given facet should only appear if any one
				// of the specified "required" facets is present in the criteria
				//
				$va_facets = array();
				
				foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
					if (isset($va_facet_info['requires']) && !is_array($va_facet_info['requires']) && $va_facet_info['requires']) { $va_facet_info['requires'] = array($va_facet_info['requires']); }
					if (isset($va_facet_info['requires']) && is_array($va_facet_info['requires'])) {
						foreach($va_facet_info['requires'] as $vs_req_facet) {
							if (in_array($vs_req_facet, $va_criteria_facets)) {
								$va_facets[] = $vs_facet_name;
								continue; 
							}
						}
					} else {
						$va_facets[] = $vs_facet_name;
					}
				}
				return $va_facets;
			}
		}
		# ------------------------------------------------------
		/**
		 * Sets the current facet group. Facet groups let you restrict what facets are displayed to the
		 * end-user. In the browse configuration file you tag facets with one or more group names; only
		 * facets tagged with the current facet group will be returnd by getInfoForAvailableFacets()
		 * If the facet group is set to null then all available facets will be displayed.
		 *
		 * @param string $ps_group Group name to restrict facets to
		 * @return bool Always returns true
		 */
		public function setFacetGroup($ps_group) {
			$this->ops_facet_group = $ps_group;
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Returns the current facet group.
		 *
		 * @return string The current facet group or null if no group is set
		 */
		public function getFacetGroup() {
			return $this->ops_facet_group ? $this->ops_facet_group : null;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all facets that currently have content (ie. that can refine the browse further)
		 * with full facet info included
		 */
		public function getInfoForAvailableFacets() {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$va_facets = $this->opa_browse_settings['facets'];	
			$va_facet_with_content = $this->opo_ca_browse_cache->getFacets();
			
			$vs_facet_group = $this->getFacetGroup();
			
			foreach($va_facets as $vs_facet_name => $va_facet_info) {
				if ($vs_facet_group) {
					if (!(isset($va_facet_info['facet_groups']) && is_array($va_facet_info['facet_groups']) && in_array($vs_facet_group, $va_facet_info['facet_groups']))) {
						unset($va_facets[$vs_facet_name]);
						continue;
					}
				}
				
				if (!isset($va_facet_with_content[$vs_facet_name]) || !$va_facet_with_content[$vs_facet_name]) {
					unset($va_facets[$vs_facet_name]);
				}
			}
			
			return $va_facets;
		}
		# ------------------------------------------------------
		/**
		 * Returns an HTML <select> of all facets that currently have content (ie. that can refine the browse further)
		 *
		 * Options:
		 *		select_message = Message to display as default message on <select> (default is "Browse by..." or localized equivalent)
		 *		dont_add_select_message = if true, no select_message is added to <select> (default is false)
		 *		use_singular = if true singular version of facet name is used, otherwise plural version is used
		 *		
		 */
		public function getAvailableFacetListAsHTMLSelect($ps_name, $pa_attributes=null, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_facets = $this->getInfoForAvailableFacets();
			
			$va_options = array();
			
			$vs_select_message = (isset($pa_options['select_message'])) ? $pa_options['select_message'] : _t('Browse by...');
			if (!isset($pa_options['dont_add_select_message']) || !$pa_options['dont_add_select_message']) {
				$va_options[$vs_select_message] = '';
			}
			
			foreach($va_facets as $vs_facet_code => $va_facet_info) {
				$va_options[(isset($pa_options['use_singular']) && $pa_options['use_singular']) ? $va_facet_info['label_singular'] : $va_facet_info['label_plural']] = $vs_facet_code;
			}
			
			return caHTMLSelect($ps_name, $va_options, $pa_attributes);
		}
		# ------------------------------------------------------
		/**
		 * Returns list of facets that will return content for the current browse table assuming no criteria
		 * It's the list of facets returned as "available" when no criteria are specific, in other words.
		 *
		 * Note that this method does NOT take into account type restrictions
		 */
		public function getFacetsWithContentList() {
			$t_browse = new BrowseEngine($this->opn_browse_table_num, null, $this->getContext());
			return $t_browse->getFacetList();
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all facets that will return content for the current browse table assuming no criteria
		 * with full facet info included
		 * It's the list of facets returned as "available" when no criteria are specific, in other words.
		 */
		public function getInfoForFacetsWithContent() {
			if (!($va_facets_with_content = $this->opo_ca_browse_cache->getGlobalParameter('facets_with_content'))) {
				$t_browse = new BrowseEngine($this->opn_browse_table_num, null, $this->getContext());
				$t_browse->execute();
				
			}
			
			if (is_array($va_facets_with_content)) {
				$va_facets = $this->opa_browse_settings['facets'];	
				$vs_facet_group = $this->getFacetGroup();
				
				$va_tmp = array();
				foreach($va_facets_with_content as $vs_facet) {
					if (($vs_facet_group && $va_facets[$vs_facet]['facet_groups'] && is_array($va_facets[$vs_facet]['facet_groups'])) && (!in_array($vs_facet_group, $va_facets[$vs_facet]['facet_groups']))) { continue; }
					$va_tmp[$vs_facet] = $va_facets[$vs_facet];
				}
				return $va_tmp;
			}
			
			return array();
		}
		# ------------------------------------------------------
		# Generation of browse results
		# ------------------------------------------------------
		/**
		 * Actually do the browse
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *		no_cache = don't use cached browse results
		 *		showDeleted = if set to true, related items that have been deleted are returned. Default is false.
		 *		limitToModifiedOn = if set returned results will be limited to rows modified within the specified date range. The value should be a date/time expression parse-able by TimeExpressionParser
		 *		user_id = If set item level access control is performed relative to specified user_id, otherwise defaults to logged in user
		 */
		public function execute($pa_options=null) {
			global $AUTH_CURRENT_USER_ID;
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vn_user_id = (isset($pa_options['user_id']) && (int)$pa_options['user_id']) ?  (int)$pa_options['user_id'] : (int)$AUTH_CURRENT_USER_ID;
			if (!is_array($this->opa_browse_settings)) { return null; }
			
			$va_params = $this->opo_ca_browse_cache->getParameters();
			
			$vb_need_to_cache_facets = false;
			$vb_results_cached = false;
			$vb_need_to_save_in_cache = false;
			
			$vs_cache_key = $this->opo_ca_browse_cache->getCurrentCacheKey();
			
			if ($this->opo_ca_browse_cache->load($vs_cache_key)) {
			
				$vn_created_on = $this->opo_ca_browse_cache->getParameter('created_on'); //$t_new_browse->get('created_on', array('GET_DIRECT_DATE' => true));
		
				$va_criteria = $this->getCriteria();
				if ((!isset($pa_options['no_cache']) || (!$pa_options['no_cache'])) && (intval(time() - $vn_created_on) < $this->opo_ca_browse_config->get('cache_timeout'))) {
					$vb_results_cached = true;
					//print "cache hit for [$vs_cache_key]<br>";
				} else {
					$va_criteria = $this->getCriteria();
					$this->opo_ca_browse_cache->remove();
					$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
					
					//print "cache expire for [$vs_cache_key]<br>";
					$vb_need_to_save_in_cache = true;
					$vb_need_to_cache_facets = true;
				}
			} else {
				$va_criteria = $this->getCriteria();
				//print "cache miss for [$vs_cache_key]<br>";
				$vb_need_to_save_in_cache = true;
			}
			if (!$vb_results_cached) {
				$this->opo_ca_browse_cache->setParameter('sort', null); 
				$this->opo_ca_browse_cache->setParameter('created_on', time()); 
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num); 
				$vb_need_to_cache_facets = true;
			}
			$this->opb_criteria_have_changed = false;
			
			$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
			
			$va_results = array();
			
			if (is_array($va_criteria) && (sizeof($va_criteria) > 0)) {		
				if (!$vb_results_cached) {
				
					// generate results
					$this->_createTempTable('ca_browses_acc');
					$this->_createTempTable('ca_browses_tmp');	
					
					
					$vn_i = 0;
					foreach($va_criteria as $vs_facet_name => $va_row_ids) {					
						$vs_target_browse_table_name = $t_item->tableName();
						$vs_target_browse_table_num = $t_item->tableNum();
						$vs_target_browse_table_pk = $t_item->primaryKey();
						
						$va_facet_info = $this->getInfoForFacet($vs_facet_name);
						$va_row_ids = array_keys($va_row_ids);
						
							$vs_relative_to_join = '';
							switch($va_facet_info['type']) {
								# -----------------------------------------------------
								case 'has':
									$vs_rel_table_name = $va_facet_info['table'];
																		
									$va_joins = array();
									
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_joins = array_merge($va_joins, $va_relative_execute_sql_data['relative_joins']);	
											
											$vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
									
									if ($va_facet_info['element_code']) {
										$t_element = new ca_metadata_elements();
										if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
											break;
										}
										$vs_element_code = $va_facet_info['element_code'];
										$vn_state = array_pop($va_row_ids);
										
										if ($vn_state == 0) {
											$va_wheres[] = $this->ops_browse_table_name.'.'.$t_item->primaryKey()." NOT IN (SELECT row_id FROM ca_attributes WHERE table_num = ".$t_item->tableNum()." AND element_id = ".$t_element->getPrimaryKey().")";
										} else {
											$va_joins[] = "INNER JOIN ca_attributes AS caa ON caa.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND caa.table_num = ".$t_item->tableNum();
											$va_wheres[] = "caa.element_id = ".$t_element->getPrimaryKey();
										}
									} else {
										if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
										$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
									
										if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }
										$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);
					
										$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
										$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
										
											switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($vs_target_browse_table_name, $vs_rel_table_name)))) {
												case 3:
													$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
													$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
													$vs_key = 'relation_id';
													break;
												case 2:
													$t_item_rel = null;
													$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
													$vs_key = $t_rel_item->primaryKey();
													break;
												default:
													// bad related table
													return null;
													break;
											}
										
											$vs_cur_table = array_shift($va_path);
										
											$vn_state = array_pop($va_row_ids);
										
											foreach($va_path as $vs_join_table) {
												$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
												$va_joins[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
												$vs_cur_table = $vs_join_table;
											}
										
										
											$va_wheres = array();
											if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
												$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
											}
											if ((sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
												$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
											}
										
											if (!(bool)$vn_state) {	// no option
												$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
												if ($t_rel_item->hasField('deleted')) {
													$va_wheres[] = "((".$t_rel_item->tableName().".deleted = 0) OR (".$t_rel_item->tableName().".deleted IS NULL))";
												}							
												if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
													$va_wheres[] = "((".$t_rel_item->tableName().".access NOT IN (".join(',', $pa_options['checkAccess']).")) OR ((".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL) AND (".$t_rel_item->tableName().".access IS NULL)))";
												}
											} else {							// yes option
												$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NOT NULL)";
												if ($t_rel_item->hasField('deleted')) {
													$va_wheres[] = "(".$t_rel_item->tableName().".deleted = 0)";
												}							
												if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
													$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
												}
											}
										}

										if ($t_item->hasField('deleted')) {
											$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
										}
												
										if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
											$va_wheres[] = "(".$t_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
										}

										$vs_join_sql = join("\n", $va_joins);
										$vs_where_sql = '';
										if (sizeof($va_wheres) > 0) {
											$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);	
										}
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												{$vs_where_sql}
											";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
										} else {
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												{$vs_relative_to_join}
												{$vs_join_sql}
												{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										}
										$vn_i++;
									
									break;
								# -----------------------------------------------------
								case 'label':
									if (!($t_label = $t_item->getLabelTableInstance())) { break; }
									
									$vs_label_item_pk = $vs_item_pk = $t_item->primaryKey();
									$vs_label_table_name = $t_label->tableName();
									$vs_label_pk = $t_label->primaryKey();
									$vs_label_display_field = $t_item->getLabelDisplayField();
									
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
											
											$t_target = $this->opo_datamodel->getInstanceByTableName($va_facet_info['relative_to'], true);
											$t_target_label = $t_target->getLabelTableInstance();
											
											$vs_item_pk = $t_target->primaryKey();
											
											$vs_label_table_name = $t_target_label->tableName();
											$vs_label_item_pk = $t_target_label->primaryKey();
											
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$va_relative_to_join[] = "INNER JOIN {$vs_label_table_name} ON {$vs_label_table_name}.{$vs_label_item_pk} = {$vs_target_browse_table_name}.{$vs_target_browse_table_pk}";
										}
									} else {
										$va_relative_to_join = array("INNER JOIN {$vs_label_table_name} ON {$vs_label_table_name}.{$vs_label_item_pk} = {$vs_target_browse_table_name}.{$vs_target_browse_table_pk}");
									}
									$vs_relative_to_join = join("\n", $va_relative_to_join);
									
									foreach($va_row_ids as $vn_row_id) {
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.".".$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													{$vs_label_table_name}.{$vs_label_item_pk} = ?";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
										} else {
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.".".$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													{$vs_label_table_name}.{$vs_label_item_pk} = ?";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'field':
									$vs_field_name = $va_facet_info['field'];
									$vs_table_name = $this->ops_browse_table_name;
									
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$vs_relative_to_join = join("\n", $va_relative_to_join);
											$vs_table_name = $vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, (string)$vn_row_id);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, (string)$vn_row_id);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'attribute':
									$t_element = new ca_metadata_elements();
									if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
										return array();
									}
									
									$vn_datatype = $t_element->get('datatype');
									 
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$vs_relative_to_join = join("\n", $va_relative_to_join);
											$vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
									
									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) Text or Number attribute
									// (do we support other types as well?)
									
									
									$vn_element_id = $t_element->getPrimaryKey();
									$o_attr = Attribute::getValueInstance($t_element->get('datatype'));
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										$vn_row_id = str_replace('&#47;', '/', $vn_row_id);
										$va_value = $o_attr->parseValue($vn_row_id, $t_element->getFieldValuesArray());
										$va_attr_sql = array();
										$va_attr_values = array(intval($vs_target_browse_table_num), $vn_element_id);
										
										if (is_array($va_value)) {
											foreach($va_value as $vs_f => $vs_v) {
												if ($vn_datatype == 3) {	// list
													$t_list_item = new ca_list_items((int)$vs_v);
													// Include sub-items
													$va_item_ids = $t_list_item->getHierarchy((int)$vs_v, array('idsOnly' => true, 'includeSelf' => true));
													
													$va_item_ids[] = (int)$vs_v;
													$va_attr_sql[] = "(ca_attribute_values.{$vs_f} IN (?))";
													$va_attr_values[] = $va_item_ids;
												} else {
													$va_attr_sql[] = "(ca_attribute_values.{$vs_f} ".(is_null($vs_v) ? " IS " : " = ")." ?)";
													$va_attr_values[] = $vs_v;
												}
											}
										}
										
										if ($vs_attr_sql = join(" AND ", $va_attr_sql)) {
											$vs_attr_sql = " AND ".$vs_attr_sql;
										}
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_attributes ON ca_attributes.row_id = {$vs_target_browse_table_name}.{$vs_target_browse_table_pk} AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												WHERE
													(ca_attribute_values.element_id = ?) {$vs_attr_sql}";
											//caDebug($vs_sql);
											//caDebug(intval($vs_target_browse_table_num)."/".$vn_element_id."/".$vn_row_id);
											//caDebug($va_attr_values);
											$qr_res = $this->opo_db->query($vs_sql, $va_attr_values);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_attributes ON ca_attributes.row_id = {$vs_target_browse_table_name}.{$vs_target_browse_table_pk} AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													(ca_attribute_values.element_id = ?) {$vs_attr_sql}";
											//print "$vs_sql [".intval($vs_target_browse_table_num)."/".$vn_element_id."/".$vn_row_id."]<hr>";print_R($va_attr_values);
											$qr_res = $this->opo_db->query($vs_sql, $va_attr_values);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'normalizedDates':
									$t_element = new ca_metadata_elements();
									if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
										return array();
									}
									
									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) DateRange attribute
									
									$vs_normalization = $va_facet_info['normalization'];
									$vn_element_id = $t_element->getPrimaryKey();
									$o_tep = new TimeExpressionParser();
									
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$vs_relative_to_join = join("\n", $va_relative_to_join);
											$vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										if (!$o_tep->parse($vn_row_id)) { continue; } // invalid date?
										
										$va_dates = $o_tep->getHistoricTimestamps();
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$vs_target_browse_table_name.'.'.$vs_target_browse_table_pk." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												WHERE
													(ca_attribute_values.element_id = ?) AND
													
													(
														(
															(ca_attribute_values.value_decimal1 <= ?) AND
															(ca_attribute_values.value_decimal2 >= ?)
														)
														OR
														(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
														OR 
														(ca_attribute_values.value_decimal2 BETWEEN ? AND ?)
													)
											";
											//print $vs_sql;
											$qr_res = $this->opo_db->query($vs_sql, intval($vs_target_browse_table_num), $vn_element_id, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_attributes ON ca_attributes.row_id = ".$vs_target_browse_table_name.'.'.$vs_target_browse_table_pk." AND ca_attributes.table_num = ?
												INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$t_item->tableName().'.'.$t_item->primaryKey()."
												WHERE
													(ca_attribute_values.element_id = ?) AND
													
													(
														(
															(ca_attribute_values.value_decimal1 <= ?) AND
															(ca_attribute_values.value_decimal2 >= ?)
														)
														OR
														(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
														OR 
														(ca_attribute_values.value_decimal2 BETWEEN ? AND ?)
													)
											";
											//print $vs_sql;
											$qr_res = $this->opo_db->query($vs_sql, intval($vs_target_browse_table_num), $vn_element_id, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
								# -----------------------------------------------------
								case 'authority':
									$vs_rel_table_name = $va_facet_info['table'];
									if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
									$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
									
									if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }
									$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);
					
									$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
									$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
										
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$vs_relative_to_join = join("\n", $va_relative_to_join);
											$vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
										
									foreach($va_row_ids as $vn_row_id) {
										
										switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($vs_target_browse_table_name, $vs_rel_table_name)))) {
											case 3:
												$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
												$vs_key = 'relation_id';
												break;
											case 2:
												$t_item_rel = null;
												$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
												$vs_key = $t_rel_item->primaryKey();
												break;
											default:
												// bad related table
												return null;
												break;
										}
										
										$vs_cur_table = array_shift($va_path);
										$va_joins = array();
										
										foreach($va_path as $vs_join_table) {
											$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
											$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
											$vs_cur_table = $vs_join_table;
										}
										
										$vs_join_sql = join("\n", $va_joins);
										
										$va_wheres = array();
										if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
										}
										if ((sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
										}
										
										$vs_where_sql = '';
										if (sizeof($va_wheres) > 0) {
											$vs_where_sql = ' AND '.join(' AND ', $va_wheres);	
										}
									
										if ((!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) && $t_rel_item->isHierarchical() && $t_rel_item->load((int)$vn_row_id)) {
											$vs_hier_left_fld = $t_rel_item->getProperty('HIERARCHY_LEFT_INDEX_FLD');
											$vs_hier_right_fld = $t_rel_item->getProperty('HIERARCHY_RIGHT_INDEX_FLD');
										
											$vs_get_item_sql = "{$vs_rel_table_name}.{$vs_hier_left_fld} >= ".$t_rel_item->get($vs_hier_left_fld). " AND {$vs_rel_table_name}.{$vs_hier_right_fld} <= ".$t_rel_item->get($vs_hier_right_fld);
											if ($vn_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
												$vs_get_item_sql .= " AND {$vs_rel_table_name}.{$vn_hier_id_fld} = ".(int)$t_rel_item->get($vn_hier_id_fld);
											}
											$vs_get_item_sql = "({$vs_get_item_sql})";
										} else {
											$vs_get_item_sql = "({$vs_rel_table_name}.{$vs_rel_table_pk} = ".(int)$vn_row_id.")";
										}
										
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
										} else {
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";
											//print "$vs_sql<hr>";
											$qr_res = $this->opo_db->query($vs_sql);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									
								break;
							# -----------------------------------------------------
								case 'fieldList':
									$vs_field_name = $va_facet_info['field'];
									$vs_table_name = $this->ops_browse_table_name;
									
									if ($va_facet_info['relative_to']) {
										if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
											$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];	
											$vs_relative_to_join = join("\n", $va_relative_to_join);
											$vs_table_name = $vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
											$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
											$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
										}
									}
									
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										if ($vn_i == 0) {
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_acc
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
										} else {
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_tmp");
											$vs_sql = "
												INSERT IGNORE INTO ca_browses_tmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												INNER JOIN ca_browses_acc ON ca_browses_acc.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."/".$vn_row_id."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
											
											
											$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
											$qr_res = $this->opo_db->query("INSERT IGNORE INTO ca_browses_acc SELECT row_id FROM ca_browses_tmp");
										} 
										
										$vn_i++;
									}
									break;
							# -----------------------------------------------------
							default:
								// handle "search" criteria - search engine queries that can be browsed
								if ($vs_facet_name === '_search') {
									$qr_res = $this->opo_db->query("TRUNCATE TABLE ca_browses_acc");
									if (!($o_search = caGetSearchInstance($this->ops_browse_table_name))) {
										$this->postError(2900, _t("Invalid search type"), "BrowseEngine->execute()");
										break;
									}
									$vs_pk = $t_item->primaryKey();
									
									
									if (is_array($va_type_ids = $this->getTypeRestrictionList()) && sizeof($va_type_ids)) {
										$o_search->setTypeRestrictions($va_type_ids);
									}
									$va_options = $pa_options;
									unset($va_options['sort']);					// browse engine takes care of sort so there is no reason to waste time having the search engine do so
									$va_options['filterNonPrimaryRepresentations'] = true;	// filter out non-primary representations in ca_objects results to save (a bit) of time
									
									$qr_res = $o_search->search($va_row_ids[0], $va_options);

									if ($qr_res->numHits() > 0) {
										$va_ids = array();
										$va_id_list = $qr_res->getPrimaryKeyValues();
										foreach($va_id_list as $vn_id) {
											$va_ids[] = "({$vn_id})";
										}
					
										$this->opo_db->query("INSERT IGNORE INTO ca_browses_acc VALUES ".join(",", $va_ids));
						
										$vn_i++;
									}
								} else {
									$this->postError(2900, _t("Invalid criteria type"), "BrowseEngine->execute()");
								}
								break;
							# -----------------------------------------------------
						}
					}
					$vs_filter_join_sql = $vs_filter_where_sql = '';
					$va_wheres = array();
					$va_joins = array();
					$vs_sql_distinct = '';
					
					if (sizeof($this->opa_result_filters)) {
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						
						$va_tmp = array();
						foreach($this->opa_result_filters as $va_filter) {
							$vm_val = $this->_filterValueToQueryValue($va_filter);
							
							$va_wheres[] = $this->ops_browse_table_name.'.'.$va_filter['field']." ".$va_filter['operator']." ".$vm_val;
						}
						
					}
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_item->hasField('deleted')) {
						if (!isset($va_joins[$this->ops_browse_table_name])) { $va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id'; }
						$va_wheres[] = "(".$this->ops_browse_table_name.".deleted = 0)";
					}
					
					if ((isset($pa_options['limitToModifiedOn']) && $pa_options['limitToModifiedOn'])) {
						$o_tep = new TimeExpressionParser();
						if ($o_tep->parse($pa_options['limitToModifiedOn'])) { 
							$va_range = $o_tep->getUnixTimestamps();
						
							$va_joins['ca_change_log_subjects'] = "INNER JOIN ca_change_log_subjects ON ca_change_log_subjects.subject_row_id = ca_browses_acc.row_id AND ca_change_log_subjects.subject_table_num = ".$t_item->tableNum();
							$va_joins['ca_change_log'] = "INNER JOIN ca_change_log ON ca_change_log.log_id = ca_change_log_subjects.log_id";
						
							$va_wheres[] = "(((ca_change_log.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].") AND (ca_change_log.changetype IN ('I', 'U', 'D'))))";
						
							$vs_sql_distinct = 'DISTINCT';	// need to pull distinct rows since joining the change log can cause dupes
						}
					}
					
					if (($va_browse_type_ids = $this->getTypeRestrictionList()) && sizeof($va_browse_type_ids)) {
						$t_subject = $this->getSubjectInstance();
						$va_joins[$this->ops_browse_table_name] = "INNER JOIN ".$this->ops_browse_table_name." ON ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().' = ca_browses_acc.row_id';
						$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).'))';
					}
					
					if (sizeof($va_wheres)) {
						$vs_filter_where_sql = 'WHERE '.join(' AND ', $va_wheres);
					}
					if (sizeof($va_joins)) {
						$vs_filter_join_sql = join("\n", $va_joins);
					}
					$qr_res = $this->opo_db->query("
						SELECT {$vs_sql_distinct} row_id
						FROM ca_browses_acc
						{$vs_filter_join_sql}
						{$vs_filter_where_sql}
					");
					while($qr_res->nextRow()) {
						$va_results[] = $qr_res->get('row_id', array('binary' => true));
					}
					
					$this->_dropTempTable('ca_browses_acc');
					$this->_dropTempTable('ca_browses_tmp');
					
					if ((!isset($pa_options['dontFilterByACL']) || !$pa_options['dontFilterByACL']) && $this->opo_config->get('perform_item_level_access_checking') && method_exists($t_item, "supportsACL") && $t_item->supportsACL()) {
						$va_results = array_keys($this->filterHitsByACL(array_flip($va_results), $vn_user_id, __CA_ACL_READONLY_ACCESS__));
					}
					
					$this->opo_ca_browse_cache->setResults($va_results);
					$vb_need_to_save_in_cache = true;
				}
			} else {
				// no criteria - don't try to find anything unless configured to do so
				$va_settings = $this->opo_ca_browse_config->getAssoc($this->ops_browse_table_name);
				if (isset($va_settings['show_all_for_no_criteria_browse']) && $va_settings['show_all_for_no_criteria_browse']) {
					$va_wheres = $va_joins = array();
					$vs_pk = $t_item->primaryKey();
						
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
						
					if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_item->hasField('deleted')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".deleted = 0)";
					}
					
					if ((isset($pa_options['limitToModifiedOn']) && $pa_options['limitToModifiedOn'])) {
						$o_tep = new TimeExpressionParser();
						if ($o_tep->parse($pa_options['limitToModifiedOn'])) { 
							$va_range = $o_tep->getUnixTimestamps();
						
							$va_joins['ca_change_log_subjects'] = "INNER JOIN ca_change_log_subjects ON ca_change_log_subjects.subject_row_id = ".$this->ops_browse_table_name.".{$vs_pk} AND ca_change_log_subjects.subject_table_num = ".$t_item->tableNum();
							$va_joins['ca_change_log'] = "INNER JOIN ca_change_log ON ca_change_log.log_id = ca_change_log_subjects.log_id";
						
							$va_wheres[] = "(((ca_change_log.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].") AND (ca_change_log.changetype IN ('I', 'U', 'D'))))";
						
							$vs_sql_distinct = 'DISTINCT';	// need to pull distinct rows since joining the change log can cause dupes
						}
					}
					
					if (($va_browse_type_ids = $this->getTypeRestrictionList()) && sizeof($va_browse_type_ids)) {
						$t_subject = $this->getSubjectInstance();
						$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).'))';
					}
					
					if (sizeof($va_wheres)) {
						$vs_filter_where_sql = 'WHERE '.join(' AND ', $va_wheres);
					}
					if (sizeof($va_joins)) {
						$vs_filter_join_sql = join("\n", $va_joins);
					}
					
					$qr_res = $this->opo_db->query("
						SELECT {$vs_pk}
						FROM ".$t_item->tableName()."
						{$vs_filter_join_sql}
						{$vs_filter_where_sql}
					");
					$va_results = $qr_res->getAllFieldValues($vs_pk);
					
					if ((!isset($pa_options['dontFilterByACL']) || !$pa_options['dontFilterByACL']) && $this->opo_config->get('perform_item_level_access_checking') && method_exists($t_item, "supportsACL") && $t_item->supportsACL()) {
						$va_results = array_keys($this->filterHitsByACL(array_flip($va_results), $vn_user_id, __CA_ACL_READONLY_ACCESS__));
					}
					$this->opo_ca_browse_cache->setResults($va_results);
				} else {
					$this->opo_ca_browse_cache->setResults(array());
				}
				$vb_need_to_save_in_cache = true;
			}
		
			if ($vb_need_to_cache_facets) {
				if (!$pa_options['dontCheckFacetAvailability']) {
					$this->loadFacetContent($pa_options);
				}
			}
	
			if ($vb_need_to_save_in_cache) {
				$this->opo_ca_browse_cache->save();
			}
	
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		public function loadFacetContent($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_facets_with_content = array();
			$o_results = $this->getResults();
			
			if ($o_results->numHits() != 1) {
				$va_facets = $this->getFacetList();
				$o_browse_cache = new BrowseCache();
				$va_parent_browse_params = $this->opo_ca_browse_cache->getParameters();
				
				//
				// If we couldn't get facets for a parent browse then use full facet list
				//
				if (!$va_facets) {
					$va_facets = $this->getFacetList();
				}
				
				//
				// Loop through facets to see if they are available
				//
	
				foreach($va_facets as $vs_facet_name) {
					if ($this->getFacetContent($vs_facet_name, array_merge($pa_options, array('checkAvailabilityOnly' => true)))) {
						$va_facets_with_content[$vs_facet_name] = true;
					}
				}
			}

			if ((!$va_criteria) || (is_array($va_criteria) && (sizeof($va_criteria) == 0))) {	
				// for the "starting" facets (no criteria) we need to stash some statistics
				// so getInfoForFacetsWithContent() can operate efficiently
				$this->opo_ca_browse_cache->setGlobalParameter('facets_with_content', array_keys($va_facets_with_content));
			}
			
			$this->opo_ca_browse_cache->setFacets($va_facets_with_content);
		}
		# ------------------------------------------------------
		# Get facet
		# ------------------------------------------------------
		/**
		 * Return list of items from the specified table that are related to the current browse set
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 */
		public function getFacet($ps_facet_name, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$va_facet_cache = $this->opo_ca_browse_cache->getFacets();
			
			// is facet cached?
			if (isset($va_facet_cache[$ps_facet_name]) && is_array($va_facet_cache[$ps_facet_name])) { return $va_facet_cache[$ps_facet_name]; }
			return $this->getFacetContent($ps_facet_name, $pa_options);
		}
		/**
		 * Return grouped list of items from the specified table that are related to the current browse set.
		 * Grouping of items is based on browse configuration.
		 * 
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 */
		public function getFacetWithGroups($ps_facet_name, $ps_group_mode, $ps_grouping_field=null, $pa_options=null){
			$va_facet_info = $this->getInfoForFacet($ps_facet_name);
			$va_facet = $this->getFacet($ps_facet_name, $pa_options);

			$t_rel_types = new ca_relationship_types();
			$va_relationship_types = $t_rel_types->getRelationshipInfo($va_facet_info['relationship_table']);

			$t_model = $this->opo_datamodel->getTableInstance($va_facet_info['table']);
			if (method_exists($t_model, "getTypeList")) {
				$va_types = $t_model->getTypeList();
			}

			$vn_element_datatype = null;
			if ($vs_grouping_attribute_element_code = (preg_match('!^ca_attribute_([\w]+)!', $ps_grouping_field, $va_matches)) ? $va_matches[1] : null) {
				$t_element = new ca_metadata_elements();
				$t_element->load(array('element_code' => $vs_grouping_attribute_element_code));
				$vn_grouping_attribute_id = $t_element->getPrimaryKey();
				$vn_element_datatype = $t_element->get('datatype');
			}

			if ((!isset($va_facet_info['groupings'][$ps_grouping_field]) || !($va_facet_info['groupings'][$ps_grouping_field])) && is_array($va_facet_info['groupings'])) { 
				$va_tmp = array_keys($va_facet_info['groupings']);
				$ps_grouping_field = $va_tmp[0];
			}

			$va_grouped_items = array();
			switch($ps_group_mode) {
				case 'none':
					// nothing to do here
					return $va_facet;
				case 'alphabetical';
				default:
					$o_tep = new TimeExpressionParser();
		
					// TODO: how do we handle non-latin characters?
					$va_label_order_by_fields = isset($va_facet_info['order_by_label_fields']) ? $va_facet_info['order_by_label_fields'] : array('label');
					foreach($va_facet as $vn_i => $va_item) {
						$va_groups = array();
						switch($ps_grouping_field) {
							case 'label':
								$va_groups[] = mb_substr($va_item[$va_label_order_by_fields[0]], 0, 1);	
								break;
							case 'relationship_types':
								foreach($va_item['rel_type_id'] as $vs_g) {
									if (isset($va_relationship_types[$vs_g]['typename'])) {
										$va_groups[] = $va_relationship_types[$vs_g]['typename'];
									} else {
										$va_groups[] = $vs_g;
									}
								}
								break;
							case 'type':
								foreach($va_item['type_id'] as $vs_g) {
									if (isset($va_types[$vs_g]['name_plural'])) {
										$va_groups[] = $va_types[$vs_g]['name_plural'];
									} else {
										$va_groups[] = _t('Type ').$vs_g;
									}
								}
								break;
							default:
								if ($vn_grouping_attribute_id) {
									switch($vn_element_datatype) {
										case 2: //date
											$va_tmp = explode(':', $ps_grouping_field);
											if(isset($va_item['ca_attribute_'.$vn_grouping_attribute_id]) && is_array($va_item['ca_attribute_'.$vn_grouping_attribute_id])) {
												foreach($va_item['ca_attribute_'.$vn_grouping_attribute_id] as $vn_i => $va_v) {
													$va_v = $o_tep->normalizeDateRange($va_v['value_decimal1'], $va_v['value_decimal2'], (isset($va_tmp[1]) && in_array($va_tmp[1], array('years', 'decades', 'centuries'))) ? $va_tmp[1] : 'decades');
													foreach($va_v as $vn_i => $vn_v) {
														$va_groups[] = $vn_v;
													}
												}
											}
											break;
										default:
											if(isset($va_item['ca_attribute_'.$vn_grouping_attribute_id]) && is_array($va_item['ca_attribute_'.$vn_grouping_attribute_id])) {
												foreach($va_item['ca_attribute_'.$vn_grouping_attribute_id] as $vn_i => $va_v) {
													$va_groups[] = $va_v['value_longtext1'];
												}
											}
											break;
									}
								} else {
									$va_groups[] = mb_substr($va_item[$va_label_order_by_fields[0]], 0, 1);	
								}
								break;
						}
						
						foreach($va_groups as $vs_group) {
							$vs_group = unicode_ucfirst($vs_group);
							$vs_alpha_key = '';
							foreach($va_label_order_by_fields as $vs_f) {
								$vs_alpha_key .= $va_item[$vs_f];
							}
							$vs_alpha_key = trim($vs_alpha_key);
							if (preg_match('!^[A-Z0-9]{1}!', $vs_group)) {
								$va_grouped_items[$vs_group][$vs_alpha_key] = $va_item;
							} else {
								$va_grouped_items['~'][$vs_alpha_key] = $va_item;
							}
						}
					}
					
					// sort lists alphabetically
					foreach($va_grouped_items as $vs_key => $va_list) {
						ksort($va_list);
						$va_grouped_items[$vs_key] = $va_list;
					}
					ksort($va_grouped_items);
					break;
			}

			return $va_grouped_items;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of hierarchy_ids used by entries in the specified authority facet. For example, if the current browse
		 * has an authority facet for vocabulary terms (aka. ca_list_items), then this methods will return list_ids for all of 
		 * the lists in which the facet entries reside. For a ca_places authority facet, this will return all of the place hierarchy_ids
		 * for which places in the facet reside.
		 *
		 * Note that this method only returns values for authority facets. It is not relevant for any other facet type.
		 *
		 * @param string $ps_facet_name The name of the authority facet
		 * @param array $pa_options Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *
		 * @return array A list of ids
		 */
		public function getHierarchyIDsForFacet($ps_facet_name, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$va_facet_cache = $this->opo_ca_browse_cache->getFacets();
			
			// is facet cached?
			if (!isset($va_facet_cache[$ps_facet_name]) || !is_array($va_facet_cache[$ps_facet_name])) {
				$va_facet_cache[$ps_facet_name] = $this->getFacetContent($ps_facet_name, $pa_options);
			}
			
			$va_hier_ids = array();
			foreach($va_facet_cache[$ps_facet_name] as $vn_id => $va_item) {
				if (!isset($va_item['hierarchy_id']) || !$va_item['hierarchy_id']) { continue; }
				$va_hier_ids[$va_item['hierarchy_id']] = true;
			}
			return array_keys($va_hier_ids);
		}
		# ------------------------------------------------------
		/**
		 * Return list of items from the specified table that are related to the current browse set
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *		checkAvailabilityOnly = if true then content is not actually fetch - only the availablility of content is verified
		 *		user_id = If set item level access control is performed relative to specified user_id, otherwise defaults to logged in user
		 */
		public function getFacetContent($ps_facet_name, $pa_options=null) {
			global $AUTH_CURRENT_USER_ID;
			
			$vs_browse_table_name = $this->ops_browse_table_name;
			$vs_browse_table_num = $this->opn_browse_table_num;
			
			$vn_user_id = (isset($pa_options['user_id']) && (int)$pa_options['user_id']) ?  (int)$pa_options['user_id'] : (int)$AUTH_CURRENT_USER_ID;
			$vb_show_if_no_acl = (bool)($this->opo_config->get('default_item_access_level') > __CA_ACL_NO_ACCESS__);
			
			$t_user = new ca_users($vn_user_id);
			if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
				$va_group_ids = array_keys($va_groups);
			} else {
				$va_group_ids = array();
			}
			
			if (!is_array($this->opa_browse_settings)) { return null; }
			if (!isset($this->opa_browse_settings['facets'][$ps_facet_name])) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_check_availability_only = (isset($pa_options['checkAvailabilityOnly'])) ? (bool)$pa_options['checkAvailabilityOnly'] : false;
			
			$va_all_criteria = $this->getCriteria();
			
			$va_criteria = $this->getCriteria($ps_facet_name);
			
			$va_facet_info = $this->opa_browse_settings['facets'][$ps_facet_name];
			
			$t_subject = $this->getSubjectInstance();
			
			if ($va_facet_info['relative_to']) {
				$vs_browse_table_name = $va_facet_info['relative_to'];
				$vs_browse_table_num = $this->opo_datamodel->getTableNum($vs_browse_table_name);
			}	
			
			$vs_browse_type_limit_sql = '';
			if (($va_browse_type_ids = $this->getTypeRestrictionList()) && sizeof($va_browse_type_ids)) {		// type restrictions
				$vs_browse_type_limit_sql = '('.$vs_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).'))';
				
				if (is_array($va_facet_info['type_restrictions'])) { 		// facet type restrictions bind a facet to specific types; we check them here 
					$va_restrict_to_types = $this->_convertTypeCodesToIDs($va_facet_info['type_restrictions']);
					$vb_is_ok_to_browse = false;
					foreach($va_browse_type_ids as $vn_type_id) {
						if (in_array($vn_type_id, $va_restrict_to_types)) {
							$vb_is_ok_to_browse = true;
							break;
						}
					}
					
					if (!$vb_is_ok_to_browse) { return array(); }
				}
			}
			
			$va_results = $this->opo_ca_browse_cache->getResults();
			
			$vb_single_value_is_present = false;
			$vs_single_value = isset($va_facet_info['single_value']) ? $va_facet_info['single_value'] : null;
			
			$va_wheres = array();
			
			switch($va_facet_info['type']) {
				# -----------------------------------------------------
				case 'has':
					if (isset($va_all_criteria[$ps_facet_name])) { break; }		// only one instance of this facet allowed per browse 
					
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true))) { break; }
					
					$vs_yes_text = (isset($va_facet_info['label_yes']) && $va_facet_info['label_yes']) ? $va_facet_info['label_yes'] : _t('Yes');
					$vs_no_text = (isset($va_facet_info['label_no']) && $va_facet_info['label_no']) ? $va_facet_info['label_no'] : _t('No');
					
					
						
					$va_facet_values = array(
						 'yes' => array(
							'id' => 1,
							'label' => $vs_yes_text
						),
						'no' => array(
							'id' => 0,
							'label' => $vs_no_text
						)
					);
					
					// Actually check that both yes and no values will result in something
					
					if ($va_facet_info['element_code']) {
						$t_element = new ca_metadata_elements();
						if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
							break;
						}
						$vs_element_code = $va_facet_info['element_code'];
						
					
					
						$va_facet = array();
						$va_counts = array();
						foreach($va_facet_values as $vs_state_name => $va_state_info) {
							$va_wheres = array();
							$va_joins = array();
						
							if (!(bool)$va_state_info['id']) {	// no option
								$va_wheres[] = $this->ops_browse_table_name.'.'.$t_item->primaryKey()." NOT IN (select row_id from ca_attributes where table_num = ".$t_item->tableNum()." AND element_id = ".$t_element->getPrimaryKey().")";
						
							} else {							// yes option
								$va_joins[] = "LEFT JOIN ca_attributes AS caa ON  ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." = caa.row_id AND ".$t_item->tableNum()." = caa.table_num";
						
								$va_wheres[] = "caa.element_id = ".$t_element->getPrimaryKey();
						
							}
						
											
							if ($t_item->hasField('deleted')) {
								$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
							}
									
							if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
								$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
							}
						
							if (sizeof($va_results)) {
								$va_wheres[] = $vs_browse_table_name.".".$t_item->primaryKey()." IN (".join(",", $va_results).")";
							}
											
							if ($va_facet_info['relative_to']) {
								if ($t_subject->hasField('deleted')) {
									$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
								}
								if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
									$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
									$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
								}
							}
						
							if ($this->opo_config->get('perform_item_level_access_checking')) {
								if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
									// Join to limit what browse table items are used to generate facet
									$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
									$va_wheres[] = "(
										((
											(ca_acl.user_id = ".(int)$vn_user_id.")
											".((sizeof($va_group_ids) > 0) ? "OR
											(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
											OR
											(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
										) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
										".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
									)";
								}
							}
							$vs_join_sql = join("\n", $va_joins);
						
							$vs_where_sql = '';
							if (sizeof($va_wheres) > 0) {
								$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);	
							}
	
							if ($vb_check_availability_only) {
								$vs_sql = "
									SELECT 1
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}
									LIMIT 2
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->nextRow()) {
									$va_counts[$vs_state_name] = (int)$qr_res->numRows();
								}
							} else {
								$vs_sql = "
									SELECT ".$vs_browse_table_name.'.'.$t_item->primaryKey()."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->numRows() > 0) {
									$va_facet[$vs_state_name] = $va_state_info;
								} else {
									return array();		// if either option in a "has" facet fails then don't show the facet
								}
							}
						}
					} else {
						$vs_rel_table_name = $va_facet_info['table'];
						if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
						$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					
						if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }
						$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);

						$vn_table_num = $this->opo_datamodel->getTableNum($vs_rel_table_name);
						$vs_rel_table_pk = $this->opo_datamodel->getTablePrimaryKeyName($vn_table_num);
					
						switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($vs_browse_table_name, $vs_rel_table_name)))) {
							case 3:
								$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
								$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
								$vs_key = 'relation_id';
								break;
							case 2:
								$t_item_rel = null;
								$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
								$vs_key = $t_rel_item->primaryKey();
								break;
							default:
								// bad related table
								return null;
								break;
						}
					
						$vs_cur_table = array_shift($va_path);
						$va_joins_init = array();
					
						foreach($va_path as $vs_join_table) {
							$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
							$va_joins_init[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
							$vs_cur_table = $vs_join_table;
						}
					
						$va_facet = array();
						$va_counts = array();
						foreach($va_facet_values as $vs_state_name => $va_state_info) {
							$va_wheres = array();
							$va_joins = $va_joins_init;
						
							if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
								$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
							}
							if ((sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
								$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
							}
						
							if (!(bool)$va_state_info['id']) {	// no option
								$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
								if ($t_rel_item->hasField('deleted')) {
									$va_wheres[] = "((".$t_rel_item->tableName().".deleted = 0) OR (".$t_rel_item->tableName().".deleted IS NULL))";
								}							
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
									$va_wheres[] = "((".$t_rel_item->tableName().".access NOT IN (".join(',', $pa_options['checkAccess']).")) OR (".$t_rel_item->tableName().".access IS NULL))";
								}
							} else {							// yes option
								$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NOT NULL)";
								if ($t_rel_item->hasField('deleted')) {
									$va_wheres[] = "(".$t_rel_item->tableName().".deleted = 0)";
								}							
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
									$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
								}
							}
						
											
							if ($t_item->hasField('deleted')) {
								$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
							}
									
							if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
								$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
							}
						
							if (sizeof($va_results)) {
								$va_wheres[] = $vs_browse_table_name.".".$t_item->primaryKey()." IN (".join(",", $va_results).")";
							}
											
							if ($va_facet_info['relative_to']) {
								if ($t_subject->hasField('deleted')) {
									$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
								}
								if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
									$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
									$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
								}
							}
						
							if ($this->opo_config->get('perform_item_level_access_checking')) {
								if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
									// Join to limit what browse table items are used to generate facet
									$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
									$va_wheres[] = "(
										((
											(ca_acl.user_id = ".(int)$vn_user_id.")
											".((sizeof($va_group_ids) > 0) ? "OR
											(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
											OR
											(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
										) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
										".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
									)";
								}
							}
							$vs_join_sql = join("\n", $va_joins);
						
							$vs_where_sql = '';
							if (sizeof($va_wheres) > 0) {
								$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);	
							}
	
							if ($vb_check_availability_only) {
								$vs_sql = "
									SELECT 1
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}
									LIMIT 2
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->nextRow()) {
									$va_counts[$vs_state_name] = (int)$qr_res->numRows();
								}
							} else {
								$vs_sql = "
									SELECT ".$vs_browse_table_name.'.'.$t_item->primaryKey()."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}
								";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->numRows() > 0) {
									$va_facet[$vs_state_name] = $va_state_info;
								} else {
									return array();		// if either option in a "has" facet fails then don't show the facet
								}
							}
						}
					}
					
					if ($vb_check_availability_only) {
						return (sizeof($va_counts) > 1) ? true : false;
					}
					
					return $va_facet;
					break;
				# -----------------------------------------------------
				case 'label':
					if (!($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true))) { break; }
					if (!($t_label = $t_item->getLabelTableInstance())) { break; }
					if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
					
					
					$vs_item_pk = $t_item->primaryKey();
					$vs_label_table_name = $t_label->tableName();
					$vs_label_pk = $t_label->primaryKey();
					$vs_label_display_field = $t_item->getLabelDisplayField();
					$vs_label_sort_field = $t_item->getLabelSortField();

					$vs_where_sql = $vs_join_sql = '';
					$vb_needs_join = false;
					
					$va_where_sql = array();
					$va_joins = array();
					
					if ($vs_browse_type_limit_sql) {
						$va_where_sql[] = $vs_browse_type_limit_sql;
					}
					
					if (isset($va_facet_info['preferred_labels_only']) && $va_facet_info['preferred_labels_only'] && $t_label->hasField('is_preferred')) {
						$va_where_sql[] = "l.is_preferred = 1";
					}
					
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_where_sql[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ($t_item->hasField('deleted')) {
						$va_where_sql[] = "(".$vs_browse_table_name.".deleted = 0)";
						$vb_needs_join = true;
					}
					
					if (sizeof($va_restrict_to_types)) {
						$va_restrict_to_type_ids = caMakeTypeIDList($vs_browse_table_name, $va_restrict_to_types, array('dont_include_subtypes_in_type_restriction' => true));
						if (sizeof($va_restrict_to_type_ids)) {
							$va_where_sql[] = "(".$vs_browse_table_name.".".$t_item->getTypeFieldName()." IN (".join(", ", $va_restrict_to_type_ids)."))";
							$vb_needs_join = true;
						}
					}
					if (sizeof($va_exclude_types)) {
						$va_exclude_type_ids = caMakeTypeIDList($vs_browse_table_name, $va_exclude_types, array('dont_include_subtypes_in_type_restriction' => true));
						if (sizeof($va_exclude_type_ids)) {
							$va_where_sql[] = "(".$vs_browse_table_name.".".$t_item->getTypeFieldName()." IN (".join(", ", $va_exclude_type_ids)."))";
							$vb_needs_join = true;
						}
					}
					
					if ($vb_needs_join) {
						$va_joins[] = "INNER JOIN ".$vs_browse_table_name." ON ".$vs_browse_table_name.".".$t_item->primaryKey()." = l.".$t_item->primaryKey();
					}
					
					if ($va_facet_info['relative_to']) {
						if ($t_subject->hasField('deleted')) {
							$va_where_sql[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
							$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
							$va_where_sql = array_merge($va_where_sql, $va_relative_sql_data['wheres']);
						}
					}
					
					
					if (sizeof($va_results)) {
						if ($va_facet_info['relative_to']) {
							$va_where_sql[] = $this->ops_browse_table_name.".".$t_subject->primaryKey()." IN (".join(",", $va_results).")";
						} else {
							$va_where_sql[] = "l.{$vs_item_pk} IN (".join(",", $va_results).")";
						}
					}
					
					
					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
							// Join to limit what browse table items are used to generate facet
							$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
							$va_where_sql[] = "(
								((
									(ca_acl.user_id = ".(int)$vn_user_id.")
									".((sizeof($va_group_ids) > 0) ? "OR
									(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
									OR
									(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
								) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
								".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
							)";
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
					
					if (sizeof($va_where_sql)) {
						$vs_where_sql = "WHERE ".join(" AND ", $va_where_sql);
					}
					
					
					if ($vb_check_availability_only) {
						$vs_sql = "
							SELECT 1
							FROM {$vs_label_table_name} l
								{$vs_join_sql}
								{$vs_where_sql}
							LIMIT 1
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql);
						
						return ((int)$qr_res->numRows() > 0) ? true : false;
					} else {
						$vs_parent_fld = $t_item->getProperty('HIERARCHY_PARENT_ID_FLD');
						$vs_sql = "
							SELECT  l.* ".(($vs_parent_fld) ? ", ".$vs_browse_table_name.".".$vs_parent_fld : '')." 
							FROM {$vs_label_table_name} l
								{$vs_join_sql}
								{$vs_where_sql}
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql);
						
						$va_values = array();
						$va_child_counts = array();
						$vn_parent_id = null;
						while($qr_res->nextRow()) {
							$vn_id = $qr_res->get($t_item->primaryKey());
							
							if ($vs_parent_fld) {
								$vn_parent_id = $qr_res->get($vs_parent_fld);
								if ($vn_parent_id) { $va_child_counts[$vn_parent_id]++; }
							}
							$va_values[$vn_id][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array(
								'id' => $vn_id,
								'parent_id' => $vn_parent_id,
								'label' => $qr_res->get($vs_label_display_field)
							));
							if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
								$vb_single_value_is_present = true;
							}
						}
						
						
						if ($vs_parent_fld) {
							foreach($va_values as $vn_id => $va_values_by_locale) {
								foreach($va_values_by_locale as $vn_locale_id => $va_value) {
									$va_values[$vn_id][$vn_locale_id]['child_count'] = (int)$va_child_counts[$vn_id]; 
								}
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						$va_values = caExtractValuesByUserLocale($va_values);
						return $va_values;
					}
					break;
				# -----------------------------------------------------
				case 'attribute':
					$t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true);
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return array();
					}
					
					$vn_element_id = $t_element->getPrimaryKey();
					
					$va_joins = array(
						'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
						'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($vs_browse_table_num)
					);
					
					$va_wheres = array();
					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					if ($t_item->hasField('deleted')) {
						$va_wheres[] = "(".$vs_browse_table_name.".deleted = 0)";
					}
					
					if ($va_facet_info['relative_to']) {
						if ($t_subject->hasField('deleted')) {
							$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
							$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
							$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
						}
					}
					
					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
							// Join to limit what browse table items are used to generate facet
							$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
							$va_wheres[] = "(
								((
									(ca_acl.user_id = ".(int)$vn_user_id.")
									".((sizeof($va_group_ids) > 0) ? "OR
									(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
									OR
									(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
								) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
								".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
							)";
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}
					
					if ($vb_check_availability_only) {
						// exclude criteria values
						$vs_criteria_exclude_sql = '';
						if (is_array($va_criteria) && sizeof($va_criteria)) { 
							$vs_criteria_exclude_sql = ' AND (ca_attribute_values.value_longtext1 NOT IN ('.join(", ", caQuoteList(array_keys($va_criteria))).')) ';
						}
						
						$vs_sql = "
							SELECT count(DISTINCT value_longtext1) c
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								(ca_attribute_values.element_id = ?) {$vs_criteria_exclude_sql} {$vs_where_sql}
							LIMIT 1";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql,$vn_element_id);
						
						if ($qr_res->nextRow()) {
							return ((int)$qr_res->get('c') > 0) ? true : false;
						}
						return false;
					} else {
						$vs_sql = "
							SELECT DISTINCT value_longtext1, value_decimal1, value_longtext2
							FROM ca_attributes
							
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? {$vs_where_sql}";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
						
						$va_values = array();
						
						$vn_element_type = $t_element->get('datatype');
						
						$va_list_items = null;
						
						
						$va_suppress_values = null;
						if ($va_facet_info['suppress'] && !is_array($va_facet_info['suppress'])) {
							$va_facet_info['suppress'] = array($va_facet_info['suppress']);
						}
						if ($vn_element_type == 3) { // list
							$t_list = new ca_lists();
							$va_list_items = caExtractValuesByUserLocale($t_list->getItemsForList($t_element->get('list_id')));
							
							if (isset($va_facet_info['suppress']) && is_array($va_facet_info['suppress'])) {
								$va_suppress_values = ca_lists::getItemIDsFromList($t_element->get('list_id'), $va_facet_info['suppress']);
							}
						} else {
							if (isset($va_facet_info['suppress']) && is_array($va_facet_info['suppress'])) {
								$va_suppress_values = $va_facet_info['suppress'];
							}
						}
						
						while($qr_res->nextRow()) {
							$o_attr = Attribute::getValueInstance($vn_element_type, $qr_res->getRow());
							if (!($vs_val = trim($o_attr->getDisplayValue()))) { continue; }
							if (is_array($va_suppress_values) && (in_array($vs_val, $va_suppress_values))) { continue; }
							switch($vn_element_type) {
								case 3:	// list
									if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
									$vn_child_count = 0;
									foreach($va_list_items as $vn_id => $va_item) {
										if ($va_item['parent_id'] == $vs_val) { $vn_child_count++; }	
									}
									$va_values[$vs_val] = array(
										'id' => $vs_val,
										'label' => $va_list_items[$vs_val]['name_plural'] ? $va_list_items[$vs_val]['name_plural'] : $va_list_items[$vs_val]['item_value'],
										'parent_id' => $va_list_items[$vs_val]['parent_id'],
										'child_count' => $vn_child_count
									);
									break;
								case 6: // currency
									$va_values[sprintf("%014.2f", preg_replace("![\D]+!", "", $vs_val))] = array(
										'id' => str_replace('/', '&#47;', $vs_val),
										'label' => $vs_val
									);
									break;
								default:
									if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
									$va_values[$vs_val] = array(
										'id' => str_replace('/', '&#47;', $vs_val),
										'label' => $vs_val
									);
									break;
							}
							
							if (!is_null($vs_single_value) && ($vs_val == $vs_single_value)) {
								$vb_single_value_is_present = true;
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						switch($vn_element_type) {
							case 3:	// list
								// preserve order of list
								$va_values_sorted_by_list_order = array();
								foreach($va_list_items as $vn_item_id => $va_item) {
									if(isset($va_values[$vn_item_id])) {
										$va_values_sorted_by_list_order[$vn_item_id] = $va_values[$vn_item_id];
									}
								}
								return $va_values_sorted_by_list_order;
								break;
							default:
								ksort($va_values);
								return $va_values;
								break;
						}			
					}
					break;
				# -----------------------------------------------------
				case 'fieldList':
					$t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true);
					$vs_field_name = $va_facet_info['field'];
					$va_field_info = $t_item->getFieldInfo($vs_field_name);
					
					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();
					
					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';
					
					if (isset($va_field_info['LIST_CODE']) && ($vs_list_name = $va_field_info['LIST_CODE'])) {
						// Handle fields containing ca_list_item.item_id's
						$va_joins = array(
							'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$vs_field_name.' = li.item_id',
							'INNER JOIN ca_lists ON ca_lists.list_id = li.list_id'
						);
						if (sizeof($va_results) && ($this->numCriteria() > 0)) {
							$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
						}
						
						if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
							$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
						}
						
						if ($vs_browse_type_limit_sql) {
							$va_wheres[] = $vs_browse_type_limit_sql;
						}
						
						if ($t_subject->hasField('deleted')) {
							$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						
						if ($va_facet_info['relative_to']) {
							if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
								$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
								$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
							}
						}
						
						if (is_array($va_criteria) && sizeof($va_criteria)) { 
							$va_wheres[] = "(li.item_id NOT IN (".join(",", array_keys($va_criteria))."))";
						}
					
						if ($this->opo_config->get('perform_item_level_access_checking')) {
							if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
								// Join to limit what browse table items are used to generate facet
								$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
								$va_wheres[] = "(
									((
										(ca_acl.user_id = ".(int)$vn_user_id.")
										".((sizeof($va_group_ids) > 0) ? "OR
										(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
										OR
										(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
									) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
									".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
								)";
							}
						}
						
						$vs_join_sql = join("\n", $va_joins);
						
						if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
							$vs_where_sql = ' AND ('.$vs_where_sql.')';
						}
						
						if ($vb_check_availability_only) {
							$vs_sql = "
								SELECT 1
								FROM ca_list_items li
								{$vs_join_sql}
								WHERE
									ca_lists.list_code = ? {$vs_where_sql}
								LIMIT 2";
							$qr_res = $this->opo_db->query($vs_sql, $vs_list_name);
						//print $vs_sql." [$vs_list_name]";
							return ((int)$qr_res->numRows() > 1) ? true : false;
						} else {						
							// Get label ordering fields
							$va_ordering_fields_to_fetch = (isset($va_facet_info['order_by_label_fields']) && is_array($va_facet_info['order_by_label_fields'])) ? $va_facet_info['order_by_label_fields'] : array();
	
							$va_orderbys = array();
							$t_rel_item_label = new ca_list_item_labels();
							foreach($va_ordering_fields_to_fetch as $vs_sort_by_field) {
								if (!$t_rel_item_label->hasField($vs_sort_by_field)) { continue; }
								$va_orderbys[] = $va_label_selects[] = 'lil.'.$vs_sort_by_field;
							}
							
							$vs_order_by = (sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
							$vs_sql = "
								SELECT DISTINCT lil.item_id, lil.name_singular, lil.name_plural, lil.locale_id
								FROM ca_list_items li
								INNER JOIN ca_list_item_labels AS lil ON lil.item_id = li.item_id
								{$vs_join_sql}
								WHERE
									ca_lists.list_code = ?  AND lil.is_preferred = 1 {$vs_where_sql} {$vs_order_by}";
							//print $vs_sql." [$vs_list_name]";
							$qr_res = $this->opo_db->query($vs_sql, $vs_list_name);
							
							$va_values = array();
							while($qr_res->nextRow()) {
								$vn_id = $qr_res->get('item_id');
								if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
							
								$va_values[$vn_id][$qr_res->get('locale_id')] = array(
									'id' => $vn_id,
									'label' => $qr_res->get('name_plural')
								);
								if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							
							if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
								return array();
							}
							return caExtractValuesByUserLocale($va_values);
						}
					} else {
					
						if ($vs_list_name = $va_field_info['LIST']) {
							$va_list_items_by_value = array();
								
							// fields with values set according to ca_list_items (not a foreign key ref)
							if ($va_list_items = caExtractValuesByUserLocale($t_list->getItemsForList($vs_list_name))) {
								foreach($va_list_items as $vn_id => $va_list_item) {
									$va_list_items_by_value[$va_list_item['item_value']] = $va_list_item['name_plural'];
								}
								
							} else {
								foreach($va_field_info['BOUNDS_CHOICE_LIST'] as $vs_val => $vn_id) {
									$va_list_items_by_value[$vn_id] = $vs_val;
								}
							}
							
							if (sizeof($va_results) && ($this->numCriteria() > 0)) {
								$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
							}
							
							if ($vs_browse_type_limit_sql) {
								$va_wheres[] = $vs_browse_type_limit_sql;
							}
							if ($t_subject->hasField('deleted')) {
								$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
							}
							if ($va_facet_info['relative_to']) {
								
								if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
									$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
									$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
								}
							}
							
							if ($this->opo_config->get('perform_item_level_access_checking')) {
								if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
									// Join to limit what browse table items are used to generate facet
									$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
									$va_wheres[] = "(
										((
											(ca_acl.user_id = ".(int)$vn_user_id.")
											".((sizeof($va_group_ids) > 0) ? "OR
											(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
											OR
											(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
										) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
										".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
									)";
								}
							}
							
							
							if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
								$vs_where_sql = '('.$vs_where_sql.')';
							}
							
							$vs_join_sql = join("\n", $va_joins);
							
							if ($vb_check_availability_only) {
								$vs_sql = "
									SELECT 1
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									".($vs_where_sql ? 'WHERE' : '')."
									{$vs_where_sql}
									LIMIT 2";
								$qr_res = $this->opo_db->query($vs_sql);
							
								return ((int)$qr_res->numRows() > 1) ? true : false;
							} else {
								$vs_sql = "
									SELECT DISTINCT ".$vs_browse_table_name.'.'.$vs_field_name."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									".($vs_where_sql ? 'WHERE' : '')."
										{$vs_where_sql}";
								//print $vs_sql." [$vs_list_name]";
								
								$qr_res = $this->opo_db->query($vs_sql);
								$va_values = array();
								while($qr_res->nextRow()) {
									$vn_id = $qr_res->get($vs_field_name);
									if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
								
									if (isset($va_list_items_by_value[$vn_id])) { 
										$va_values[$vn_id] = array(
											'id' => $vn_id,
											'label' => $va_list_items_by_value[$vn_id]
										);
										if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
											$vb_single_value_is_present = true;
										}
									}
								}
								
								if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
									return array();
								}
								return $va_values;
							}
						} else {
							if ($t_browse_table = $this->opo_datamodel->getInstanceByTableName($vs_facet_table = $va_facet_info['table'], true)) {
								// Handle fields containing ca_list_item.item_id's
								$va_joins = array(
									'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$vs_field_name.' = '.$vs_facet_table.'.'.$t_browse_table->primaryKey()
								);
								
								
								$vs_display_field_name = null;
								if (method_exists($t_browse_table, 'getLabelTableInstance')) {
									$t_label_instance = $t_browse_table->getLabelTableInstance();
									$vs_display_field_name = (isset($va_facet_info['display']) && $va_facet_info['display']) ? $va_facet_info['display'] : $t_label_instance->getDisplayField();
									$va_joins[] = 'INNER JOIN '.$t_label_instance->tableName()." AS lab ON lab.".$t_browse_table->primaryKey().' = '.$t_browse_table->tableName().'.'.$t_browse_table->primaryKey();
								}
								
								if (sizeof($va_results) && ($this->numCriteria() > 0)) {
									$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
								}
								
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
									$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
								}
								
								if ($vs_browse_type_limit_sql) {
									$va_wheres[] = $vs_browse_type_limit_sql;
								}
								
								if ($va_facet_info['relative_to']) {
									if ($t_subject->hasField('deleted')) {
										$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
									}
									if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
										$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
										$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
									}
								}
								
								if ($this->opo_config->get('perform_item_level_access_checking')) {
									if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
										// Join to limit what browse table items are used to generate facet
										$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
										$va_wheres[] = "(
											((
												(ca_acl.user_id = ".(int)$vn_user_id.")
												".((sizeof($va_group_ids) > 0) ? "OR
												(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
												OR
												(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
											) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
											".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
										)";
									}
								}
								
								$vs_join_sql = join("\n", $va_joins);
								
								if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
									$vs_where_sql = 'WHERE ('.$vs_where_sql.')';
								}
								
								if ($vb_check_availability_only) {
									$vs_sql = "
										SELECT 1
										FROM {$vs_facet_table}
										
										{$vs_join_sql}
										{$vs_where_sql}
										LIMIT 1";
									$qr_res = $this->opo_db->query($vs_sql);
									
									return ((int)$qr_res->numRows() > 0) ? true : false;
								} else {
									$vs_sql = "
										SELECT DISTINCT *
										FROM {$vs_facet_table}
										
										{$vs_join_sql}
										{$vs_where_sql}";
									//print $vs_sql;
									$qr_res = $this->opo_db->query($vs_sql);
									
									$va_values = array();
									$vs_pk = $t_browse_table->primaryKey();
									while($qr_res->nextRow()) {
										$vn_id = $qr_res->get($vs_pk);
										if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
										$va_values[$vn_id][$qr_res->get('locale_id')] = array(
											'id' => $vn_id,
											'label' => $qr_res->get($vs_display_field_name)
										);
										if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
											$vb_single_value_is_present = true;
										}
									}
									
									if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
										return array();
									}
									return caExtractValuesByUserLocale($va_values);
								}
							}
						}
					}
					return array();
					break;
				# -----------------------------------------------------
				case 'field':
					$t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true);
					$vs_field_name = $va_facet_info['field'];
					$va_field_info = $t_item->getFieldInfo($vs_field_name);
					
					$vs_sort_field = null;
					if (($t_item->getProperty('ID_NUMBERING_ID_FIELD') == $vs_field_name)) {
						$vs_sort_field = $t_item->getProperty('ID_NUMBERING_SORT_FIELD');
					}
					
					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();
					
					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';
					
					
						if (sizeof($va_results) && ($this->numCriteria() > 0)) {
							$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
						}
						
						if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
							$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
						}
						
						if ($vs_browse_type_limit_sql) {
							$va_wheres[] = $vs_browse_type_limit_sql;
						}
											
						if ($t_item->hasField('deleted')) {
							$va_wheres[] = "(".$vs_browse_table_name.".deleted = 0)";
						}
						
						if ($va_facet_info['relative_to']) {
							if ($t_subject->hasField('deleted')) {
								$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
							}
							if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
								$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
								$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
							}
						}
						
						if ($this->opo_config->get('perform_item_level_access_checking')) {
							if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
								// Join to limit what browse table items are used to generate facet
								$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
								$va_wheres[] = "(
									((
										(ca_acl.user_id = ".(int)$vn_user_id.")
										".((sizeof($va_group_ids) > 0) ? "OR
										(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
										OR
										(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
									) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
									".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
								)";
							}
						}
						
						$vs_join_sql = join("\n", $va_joins);
						
						if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
							$vs_where_sql = '('.$vs_where_sql.')';
						}
						
						if ($vb_check_availability_only) {
							$vs_sql = "
								SELECT 1
								FROM {$vs_browse_table_name}
								{$vs_join_sql}
								WHERE
									{$vs_where_sql}
								LIMIT 2";
							$qr_res = $this->opo_db->query($vs_sql);
						
							if ($qr_res->nextRow()) {
								return ((int)$qr_res->numRows() > 0) ? true : false;
							}
							return false;
						} else {
							
							$vs_pk = $t_item->primaryKey();
							$vs_sql = "
								SELECT DISTINCT {$vs_browse_table_name}.{$vs_field_name}
								FROM {$vs_browse_table_name}
								{$vs_join_sql}
								WHERE
									{$vs_where_sql}";
							if($vs_sort_field) {
								$vs_sql .= " ORDER BY {$vs_sort_field}";
							}
							//print $vs_sql." [$vs_list_name]";
							$qr_res = $this->opo_db->query($vs_sql);
							
							$va_values = array();
							while($qr_res->nextRow()) {
								if (!($vs_val = trim($qr_res->get($vs_field_name)))) { continue; }
								if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
							
								$va_values[$vs_val] = array(
									'id' => $vs_val,
									'label' => $vs_val
								);
								if (!is_null($vs_single_value) && ($vs_val == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							
							if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
								return array();
							}
							return $va_values;
						}
					
				
					return array();
					break;
				# -----------------------------------------------------
				case 'normalizedDates':
					$t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true);
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => $va_facet_info['element_code']))) {
						return array();
					}
					
					$va_wheres = array();
					
					$vn_element_id = $t_element->getPrimaryKey();
					
					$vs_normalization = $va_facet_info['normalization'];	// how do we construct the date ranges presented to uses. In other words - how do we want to allow users to browse dates? By year, decade, century?
					
					$va_joins = array(
						'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
						'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($vs_browse_table_num)
					);
					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					if ($t_item->hasField('deleted')) {
						$va_wheres[] = "(".$vs_browse_table_name.".deleted = 0)";
					}
					
					if ($va_facet_info['relative_to']) {
						if ($t_subject->hasField('deleted')) {
							$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
							$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
							$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
						}
					}
					
					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
							// Join to limit what browse table items are used to generate facet
							$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
							$va_wheres[] = "(
								((
									(ca_acl.user_id = ".(int)$vn_user_id.")
									".((sizeof($va_group_ids) > 0) ? "OR
									(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
									OR
									(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
								) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
								".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
							)";
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
					
					$vs_where_sql = '';
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}
					
					$vs_dir = (strtoupper($va_facet_info['sort']) === 'DESC') ? "DESC" : "ASC";
					
					$o_tep = new TimeExpressionParser();
					$vn_min_date = $vn_max_date = null;
					$vs_min_sql = $vs_max_sql = '';
					if (isset($va_facet_info['minimum_date'])) {
						if ($o_tep->parse($va_facet_info['minimum_date'])) {
							$va_tmp = $o_tep->getHistoricTimestamps();
							$vn_min_date = (float)$va_tmp['start'];
							$vs_min_sql = " AND (ca_attribute_values.value_decimal1 >= {$vn_min_date})";
						}
					}
					if (isset($va_facet_info['maximum_date'])) {
						if ($o_tep->parse($va_facet_info['maximum_date'])) {
							$va_tmp = $o_tep->getHistoricTimestamps();
							$vn_max_date = (float)$va_tmp['end'];
							$vs_max_sql = " AND (ca_attribute_values.value_decimal2 <= {$vn_max_date})";
						}
					}
					
					if ($vb_check_availability_only) {
						$vs_sql = "
							SELECT 1
							FROM ca_attributes
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? 
								{$vs_min_sql}
								{$vs_max_sql}
								{$vs_where_sql}
								LIMIT 1";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
						
						return ((int)$qr_res->numRows() > 0) ? true : false;
					} else {
						$vs_sql = "
							SELECT DISTINCT ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2
							FROM ca_attributes
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? 
								{$vs_min_sql}
								{$vs_max_sql}
								{$vs_where_sql}
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, $vn_element_id);
					
						$va_values = array();
						while($qr_res->nextRow()) {
							$vn_start = $qr_res->get('value_decimal1');
							$vn_end = $qr_res->get('value_decimal2');
							
							if (!($vn_start && $vn_end)) { continue; }
							$va_normalized_values = $o_tep->normalizeDateRange($vn_start, $vn_end, $vs_normalization);
							foreach($va_normalized_values as $vn_sort_value => $vs_normalized_value) {
								if ($va_criteria[$vs_normalized_value]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									
								if (is_numeric($vs_normalized_value) && (int)$vs_normalized_value === 0) { continue; }		// don't include year=0
								$va_values[$vn_sort_value][$vs_normalized_value] = array(
									'id' => $vs_normalized_value,
									'label' => $vs_normalized_value
								);	
								if (!is_null($vs_single_value) && ($vs_normalized_value == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
						}
						
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						
						ksort($va_values);
						
						if ($vs_dir == 'DESC') { $va_values = array_reverse($va_values); }
						$va_sorted_values = array();
						foreach($va_values as $vn_sort_value => $va_values_for_sort_value) {
							$va_sorted_values = array_merge($va_sorted_values, $va_values_for_sort_value);
						}
						return $va_sorted_values;
					}
					break;
				# -----------------------------------------------------
				case 'authority':
					$vs_rel_table_name = $va_facet_info['table'];
					$va_params = $this->opo_ca_browse_cache->getParameters();
					if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
					if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
					if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
					if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }
					
					$t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true);
					
					if ($vs_browse_table_name == $vs_rel_table_name) {
						// browsing on self-relations not supported
						break;
					} else {
						switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($vs_browse_table_name, $vs_rel_table_name)))) {
							case 3:
								$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
								$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
								$vs_key = 'relation_id';
								break;
							case 2:
								$t_item_rel = null;
								$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
								$vs_key = $t_rel_item->primaryKey();
								break;
							default:
								// bad related table
								return null;
								break;
						}
					}
					
					$vb_rel_is_hierarchical = (bool)$t_rel_item->isHierarchical();
					
					//
					// Convert related item type_code specs in restrict_to_types and exclude_types lists to numeric type_ids we need for the query
					//
					$va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item, 'includeSubtypes' => true));
					$va_exclude_types = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item, 'includeSubtypes' => true));
					
					$va_restrict_to_types_expanded = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item));
					$va_exclude_types_expanded = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item));
					
			
					// look up relationship type restrictions
					$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);
					$va_joins = array();
					$va_selects = array();
					$va_wheres = array();
					$va_orderbys = array();
					
if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
					$vs_cur_table = array_shift($va_path);
					
					foreach($va_path as $vs_join_table) {
						$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
						$vs_cur_table = $vs_join_table;
					}
} else {
					if ($va_facet_info['show_all_when_first_facet']) {
						$va_path = array_reverse($va_path);		// in "show_all" mode we turn the browse on it's head and grab records by the "subject" table, rather than the browse table
						$vs_cur_table = array_shift($va_path);
						$vs_join_table = $va_path[0];
						$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'LEFT JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
										
					}
}

					if (sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}
					
					if (!is_array($va_restrict_to_lists = $va_facet_info['restrict_to_lists'])) { $va_restrict_to_lists = array(); }
					if (is_array($va_restrict_to_lists) && (sizeof($va_restrict_to_lists) > 0) && ($t_rel_item->tableName() == 'ca_list_items')) {
						$va_list_ids = array();
						foreach($va_restrict_to_lists as $vm_list) {
							if (is_numeric($vm_list)) {
								$vn_list_id = (int)$vm_list;
							} else {
								$vn_list_id = (int)ca_lists::getListID($vm_list);
							}
							if ($vn_list_id) { $va_list_ids[] = $vn_list_id; }
						}
						
						if (sizeof($va_list_ids) > 0) {
							$va_wheres[] = "{$vs_rel_table_name}.list_id IN (".join(',', $va_list_ids).")";
						}
					}
					
					if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && method_exists($t_rel_item, "getTypeList")) {
						$va_wheres[] = "{$vs_rel_table_name}.type_id IN (".join(',', $va_restrict_to_types_expanded).")";
						$va_selects[] = "{$vs_rel_table_name}.type_id";
					}
					
					if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && method_exists($t_rel_item, "getTypeList")) {
						$va_wheres[] = "{$vs_rel_table_name}.type_id NOT IN (".join(',', $va_exclude_types_expanded).")";
					}
					
					if ((sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
						$va_wheres[] = $t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types).")";
					}
					if ((sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
						$va_wheres[] = $t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types).")";
					}
					
					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
						$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";				// exclude non-accessible authority items
						if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
							$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";		// exclude non-accessible browse items
						}
					}
					
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}
					
					if ($t_item->hasField('deleted') && !$va_facet_info['show_all_when_first_facet']) {
						$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
					}
					if ($t_rel_item->hasField('deleted')) {
						$va_wheres[] = "(".$t_rel_item->tableName().".deleted = 0)";
					}
					
					$vs_rel_pk = $t_rel_item->primaryKey();
					$va_rel_attr_elements = $t_rel_item->getApplicableElementCodes(null, true, false);
					
					$va_attrs_to_fetch = array();
if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {				
					$va_selects[] = $t_item->tableName().'.'.$t_item->primaryKey();			// get primary key of subject
}
					$va_selects[] = $t_rel_item->tableName().'.'.$vs_rel_pk;				// get primary key of related
					
					
					$vs_hier_parent_id_fld = $vs_hier_id_fld = null;
					if ($vb_rel_is_hierarchical) {
						$vs_hier_parent_id_fld = $t_rel_item->getProperty('HIERARCHY_PARENT_ID_FLD');
						$va_selects[] = $t_rel_item->tableName().'.'.$vs_hier_parent_id_fld;
						
						if ($vs_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
							$va_selects[] = $t_rel_item->tableName().'.'.$vs_hier_id_fld;
						}
					}
					
					// analyze group_fields (if defined) and add them to the query
					$va_groupings_to_fetch = array();
					if (isset($va_facet_info['groupings']) && is_array($va_facet_info['groupings']) && sizeof($va_facet_info['groupings'])) {
						foreach($va_facet_info['groupings'] as $vs_grouping => $vs_grouping_name) {
							// is grouping type_id?
							if (($vs_grouping === 'type') && $t_rel_item->hasField('type_id')) {
								$va_selects[] = $t_rel_item->tableName().'.type_id';
								$va_groupings_to_fetch[] = 'type_id';
							}
							
							// is group field a relationship type?
							if ($vs_grouping === 'relationship_types') {
								$va_selects[] = $va_facet_info['relationship_table'].'.type_id rel_type_id';
								$va_groupings_to_fetch[] = 'rel_type_id';
							}
							
							// is group field an attribute?
							if (preg_match('!^ca_attribute_([^:]*)!', $vs_grouping, $va_matches)) {
								if ($vn_element_id = array_search($va_matches[1], $va_rel_attr_elements)) {
									$va_attrs_to_fetch[] = $vn_element_id;
								}
							}
							
						}
					}
					
					if ($va_facet_info['relative_to']) {
						if ($t_subject->hasField('deleted')) {
							$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
							$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
							$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
						}
					}
					
					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = $this->opo_datamodel->getInstanceByTableName($vs_browse_table_name, true)) {
							
							// Join to limit what browse table items are used to generate facet
							$va_joins[] = 'LEFT JOIN ca_acl ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_acl.row_id AND ca_acl.table_num = '.$t_item->tableNum()."\n";
							$va_wheres[] = "(
								((
									(ca_acl.user_id = ".(int)$vn_user_id.")
									".((sizeof($va_group_ids) > 0) ? "OR
									(ca_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
									OR
									(ca_acl.user_id IS NULL and ca_acl.group_id IS NULL)
								) AND ca_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
								".(($vb_show_if_no_acl) ? "OR ca_acl.acl_id IS NULL" : "")."
							)";
							
							// Join to limit what related items are used to generate facet
							$va_joins[] = 'LEFT JOIN ca_acl AS rel_acl ON '.$t_rel_item->tableName().'.'.$t_rel_item->primaryKey().' = rel_acl.row_id AND rel_acl.table_num = '.$t_rel_item->tableNum()."\n";
							$va_wheres[] = "(
								((
									(rel_acl.user_id = ".(int)$vn_user_id.")
									".((sizeof($va_group_ids) > 0) ? "OR
									(rel_acl.group_id IN (".join(",", $va_group_ids)."))" : "")."
									OR
									(rel_acl.user_id IS NULL and rel_acl.group_id IS NULL)
								) AND rel_acl.access >= ".__CA_ACL_READONLY_ACCESS__.")
								".(($vb_show_if_no_acl) ? "OR rel_acl.acl_id IS NULL" : "")."
							)";
						}
					}
					
					$vs_join_sql = join("\n", $va_joins);
				
				
					if ($vb_check_availability_only) {
	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
						$vs_sql = "
							SELECT 1
							FROM ".$vs_browse_table_name."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	} else {
						$vs_sql = "
							SELECT 1
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	}
						$qr_res = $this->opo_db->query($vs_sql);
						//print "<hr>$vs_sql<hr>\n";
						
						return ((int)$qr_res->numRows() > 0) ? true : false;
					} else {
						
	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {	
						$vs_sql = "
							SELECT DISTINCT ".join(', ', $va_selects)."
							FROM ".$vs_browse_table_name."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	} else {
						$vs_sql = "
							SELECT DISTINCT ".join(', ', $va_selects)."
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	}
						//print "<hr>$vs_sql<hr>\n";
						
						$qr_res = $this->opo_db->query($vs_sql);
						
						$va_facet = $va_facet_items = array();
						$vs_rel_pk = $t_rel_item->primaryKey();
						
						// First get related ids with type and relationship type values
						// (You could get all of the data we need for the facet in a single query but it turns out to be faster for very large facets to 
						// do it in separate queries, one for the primary ids and another for the labels; a third is done if attributes need to be fetched.
						// There appears to be a significant [~10%] performance for smaller facets and a larger one [~20-25%] for very large facets)
						$va_facet_parents = array();
						while($qr_res->nextRow()) {
							$va_fetched_row = $qr_res->getRow();
							$vn_id = $va_fetched_row[$vs_rel_pk];
							//if (isset($va_facet_items[$vn_id])) { continue; } --- we can't do this as then we don't detect items that have multiple rel_type_ids... argh.
							if (isset($va_criteria[$vn_id])) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
							
							if (!$va_facet_items[$va_fetched_row[$vs_rel_pk]]) {
							
								
								if ($va_fetched_row[$vs_hier_parent_id_fld]) {
									$va_facet_parents[$va_fetched_row[$vs_hier_parent_id_fld]] = true;
								}
								if (is_array($va_restrict_to_types) && sizeof($va_restrict_types) && $va_fetched_row['type_id'] && !in_array($va_fetched_row['type_id'], $va_restrict_to_types)) {
									continue; 
								}
								$va_facet_items[$va_fetched_row[$vs_rel_pk]] = array(
									'id' => $va_fetched_row[$vs_rel_pk],
									'type_id' => array(),
									'parent_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_parent_id_fld] : null,
									'hierarchy_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_id_fld] : null,
									'rel_type_id' => array(),
									'child_count' => 0
								);
								
								if (!is_null($vs_single_value) && ($va_fetched_row[$vs_rel_pk] == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							if ($va_fetched_row['type_id']) {
								$va_facet_items[$va_fetched_row[$vs_rel_pk]]['type_id'][] = $va_fetched_row['type_id'];
							}
							if ($va_fetched_row['rel_type_id']) {
								$va_facet_items[$va_fetched_row[$vs_rel_pk]]['rel_type_id'][] = $va_fetched_row['rel_type_id'];
							}
						}
						
						// Expand facet to include ancestors
						if (!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) {
							while(sizeof($va_ids = array_keys($va_facet_parents))) {
								$vs_sql = "
									SELECT p.".$t_rel_item->primaryKey().", p.{$vs_hier_parent_id_fld}".(($vs_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) ? ", p.{$vs_hier_id_fld}" : "")."
									FROM ".$t_rel_item->tableName()." p
									WHERE
										(p.".$t_rel_item->primaryKey()." IN (?)) AND (p.{$vs_hier_parent_id_fld} IS NOT NULL)
								";
								$qr_res = $this->opo_db->query($vs_sql, array($va_ids));
								
								$va_facet_parents = array();
								while($qr_res->nextRow()) {
									$va_fetched_row = $qr_res->getRow();
									$va_facet_items[$va_fetched_row[$vs_rel_pk]] = array(
										'id' => $va_fetched_row[$vs_rel_pk],
										'type_id' => array(),
										'parent_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_parent_id_fld] : null,
										'hierarchy_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_id_fld] : null,
										'rel_type_id' => array(),
										'child_count' => 0
									);
									if ($va_fetched_row[$vs_hier_parent_id_fld]) { $va_facet_parents[$va_fetched_row[$vs_hier_parent_id_fld]] = true; }
								}
							}
						}
						
						// Set child counts
						foreach($va_facet_items as $vn_i => $va_item) {
							if ($va_item['parent_id'] && isset($va_facet_items[$va_item['parent_id']])) {
								$va_facet_items[$va_item['parent_id']]['child_count']++;
							}
						}
						
						// Get labels for facet items
						if (sizeof($va_row_ids = array_keys($va_facet_items))) {	
							if ($vs_label_table_name = $t_rel_item->getLabelTableName()) {
								$t_rel_item_label = $this->opo_datamodel->getInstanceByTableName($vs_label_table_name, true);
								$vs_label_display_field = $t_rel_item_label->getDisplayField();
								
								$vs_rel_pk = $t_rel_item->primaryKey();
								$va_label_wheres = array();
								
								if ($t_rel_item_label->hasField('is_preferred')) {
									$va_label_wheres[] = "({$vs_label_table_name}.is_preferred = 1)";
								}
								$va_label_wheres[] = "({$vs_label_table_name}.{$vs_rel_pk} IN (".join(",", $va_row_ids)."))";
								$va_label_selects[] = "{$vs_label_table_name}.{$vs_rel_pk}";
								$va_label_selects[] = "{$vs_label_table_name}.locale_id";
								
								$va_label_fields = $t_rel_item->getLabelUIFields();
								foreach($va_label_fields as $vs_label_field) {
									$va_label_selects[] = "{$vs_label_table_name}.{$vs_label_field}";
								}
								
								// Get label ordering fields
								$va_ordering_fields_to_fetch = (isset($va_facet_info['order_by_label_fields']) && is_array($va_facet_info['order_by_label_fields'])) ? $va_facet_info['order_by_label_fields'] : array();
		
								$va_orderbys = array();
								foreach($va_ordering_fields_to_fetch as $vs_sort_by_field) {
									if (!$t_rel_item_label->hasField($vs_sort_by_field)) { continue; }
									$va_orderbys[] = $va_label_selects[] = $vs_label_table_name.'.'.$vs_sort_by_field;
								}
								
								// get labels
								$vs_sql = "
									SELECT ".join(', ', $va_label_selects)."
									FROM ".$vs_label_table_name."
										".(sizeof($va_label_wheres) ? ' WHERE ' : '').join(" AND ", $va_label_wheres)."
										".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '')."";
								//print $vs_sql;
								$qr_labels = $this->opo_db->query($vs_sql);
								
								while($qr_labels->nextRow()) {
									$va_fetched_row = $qr_labels->getRow();
									$va_facet_item = array_merge($va_facet_items[$va_fetched_row[$vs_rel_pk]], array('label' => $va_fetched_row[$vs_label_display_field]));
															
									foreach($va_ordering_fields_to_fetch as $vs_to_fetch) {
										$va_facet_item[$vs_to_fetch] = $va_fetched_row[$vs_to_fetch];
									}
									
									$va_facet[$va_fetched_row[$vs_rel_pk]][$va_fetched_row['locale_id']] = $va_facet_item;
								}
							}
							
							// get attributes for facet items
							if (sizeof($va_attrs_to_fetch)) {
								$qr_attrs = $this->opo_db->query("
									SELECT c_av.*, c_a.locale_id, c_a.row_id
									FROM ca_attributes c_a
									INNER JOIN ca_attribute_values c_av ON c_a.attribute_id = c_av.attribute_id
									WHERE
										c_av.element_id IN (".join(',', $va_attrs_to_fetch).")
										AND
										c_a.table_num = ? 
										AND 
										c_a.row_id IN (".join(',', $va_row_ids).")
								", $t_rel_item->tableNum());
								while($qr_attrs->nextRow()) {
									$va_fetched_row = $qr_attrs->getRow();
									$vn_id = $va_fetched_row['row_id'];
									
									// if no locale is set for the attribute default it to whatever the locale for the item is
									if (!($vn_locale_id = $va_fetched_row['locale_id'])) {
										$va_tmp = array_keys($va_facet[$vn_id]);
										$vn_locale_id = $va_tmp[0];
									}
									$va_facet[$vn_id][$vn_locale_id]['ca_attribute_'.$va_fetched_row['element_id']][] = $va_fetched_row;
								}
							}
						}
						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						return caExtractValuesByUserLocale($va_facet);
					}
					break;
				# -----------------------------------------------------
				default:
					return null;
					break;
				# -----------------------------------------------------
			}
		}
		# ------------------------------------------------------
		# Get browse results
		# ------------------------------------------------------
		/**
		 * Fetch the number of rows found by the current browse (Can be called before getResults())
		 */
		public function numResults() {
			return $this->opo_ca_browse_cache->numResults();
		}
		# ------------------------------------------------------
		/**
		 * Fetch the subject rows found by an execute()'d browse
		 */
		public function getResults($pa_options=null) {
			return $this->doGetResults(null, $pa_options);
		}
		# ------------------------------------------------------
		/**
		 * Fetch the subject rows found by an execute()'d browse
		 */
		protected function doGetResults($po_result=null, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }
			$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
			$vb_will_sort = (isset($pa_options['sort']) && $pa_options['sort'] && (($this->getCachedSortSetting() != $pa_options['sort']) || ($this->getCachedSortDirectionSetting() != $pa_options['sort_direction'])));
			
			$vs_pk = $t_item->primaryKey();
			$vs_label_display_field = null;
			
			if(sizeof($va_results =  $this->opo_ca_browse_cache->getResults())) {
				if ($vb_will_sort) {
					$va_results_flipped = array_flip($va_results);
					$va_tmp = $this->sortHits($va_results_flipped, $pa_options['sort'], (isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : null));
					$va_results = array_keys($va_tmp);

					$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num); 
					$this->opo_ca_browse_cache->setParameter('sort', $pa_options['sort']);
					$this->opo_ca_browse_cache->setParameter('sort_direction', $pa_options['sort_direction']);
					
					$this->opo_ca_browse_cache->setResults($va_results);
					$this->opo_ca_browse_cache->save();
				}
				
				if (isset($pa_options['limit']) && ($vn_limit = $pa_options['limit'])) {
					if (isset($pa_options['start']) && ($vn_start = $pa_options['start'])) {
						$va_results = array_slice($va_results, $vn_start, $vn_limit);
					}
				}
			}
			if (!is_array($va_results)) { $va_results = array(); }
			
			if ($po_result) {
				$po_result->init(new WLPlugSearchEngineBrowseEngine($va_results, $this->opn_browse_table_num), array(), $pa_options);
				
				return $po_result;
			} else {
				return new WLPlugSearchEngineBrowseEngine($va_results, $this->opn_browse_table_num);
			}
		}
		# ------------------------------------------------------------------
		/**
		 * Returns string indicating what field the cached browse result is sorted on
		 */
		public function getCachedSortSetting() {
			return $this->opo_ca_browse_cache->getParameter('sort');
		}
		# ------------------------------------------------------------------
		/**
		 * Returns string indicating in which order the cached browse result is sorted
		 */
		public function getCachedSortDirectionSetting() {
			return $this->opo_ca_browse_cache->getParameter('sort_direction');
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function sortHits(&$pa_hits, $ps_field, $ps_direction='asc') {
			$vs_browse_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, $this->opo_ca_browse_cache->getCacheKey());
			
			if (!in_array(strtolower($ps_direction), array('asc', 'desc'))) { $ps_direction = 'asc'; }
			if (!is_array($pa_hits) || !sizeof($pa_hits)) { return $pa_hits; }
				
			$t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true);
			$vs_table_pk = $t_table->primaryKey();
			$vs_table_name = $this->ops_browse_table_name;
			
			$va_fields = explode(';', $ps_field);
			$va_sorted_hits = array();
			
			$vn_num_locales = ca_locales::numberOfCataloguingLocales();
			
			foreach($va_fields as $vs_field) {				
				$va_joins = $va_orderbys = array();
				$vs_locale_where = $vs_is_preferred_sql = '';
				
				$va_tmp = explode('.', $vs_field);
				
				if ($va_tmp[0] == $vs_table_name) {
					//
					// sort field is in search table
					//
					if (!$t_table->hasField($va_tmp[1])) { 
						//
						// is it an attribute?
						//
						$t_element = new ca_metadata_elements();
						$vs_sort_element_code = array_pop($va_tmp);
						if ($t_element->load(array('element_code' => $vs_sort_element_code))) {
							$vn_element_id = $t_element->getPrimaryKey();
							
							if (!($vs_sortable_value_fld = Attribute::getSortFieldForDatatype($t_element->get('datatype')))) {
								return $pa_hits;
							}
							
							if ((int)$t_element->get('datatype') == 3) {
								$vs_sortable_value_fld = 'lil.name_plural';
								
								$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
								$vs_locale_where = ($vn_num_locales > 1) ? ', lil.locale_id' : '';
					
								$vs_sql = "
									SELECT attr.row_id, lil.locale_id, lower({$vs_sortable_value_fld}) {$vs_sort_field}
									FROM ca_attributes attr
									INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
									INNER JOIN ca_list_item_labels AS lil ON lil.item_id = attr_vals.item_id
									INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = attr.row_id
									WHERE
										(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (lil.{$vs_sort_field} IS NOT NULL)
									ORDER BY lil.{$vs_sort_field}
								";
							} else {
								$vs_sortable_value_fld = 'attr_vals.'.$vs_sortable_value_fld;
						
								$vs_sort_field = array_pop(explode('.', $vs_sortable_value_fld));
								$vs_locale_where = ($vn_num_locales > 1) ? ', attr.locale_id' : '';
								
								$vs_sql = "
									SELECT attr.row_id, attr.locale_id, lower({$vs_sortable_value_fld}) {$vs_sort_field}
									FROM ca_attributes attr
									INNER JOIN ca_attribute_values AS attr_vals ON attr_vals.attribute_id = attr.attribute_id
									INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = attr.row_id
									WHERE
										(attr_vals.element_id = ?) AND (attr.table_num = ?) AND (attr_vals.{$vs_sort_field} IS NOT NULL)
									ORDER BY attr_vals.{$vs_sort_field}
								";
								//print $vs_sql." ; $vn_element_id/; ".$this->opn_browse_table_num."<br>";
							}
							$qr_sort = $this->opo_db->query($vs_sql, (int)$vn_element_id, (int)$this->opn_browse_table_num);
							
							while($qr_sort->nextRow()) {
								$va_row = $qr_sort->getRow();
								if (!$va_row['row_id']) { continue; }
								if ($vn_num_locales > 1) {
									$va_sorted_hits[$va_row['row_id']][$va_row['locale_id']] .= trim(str_replace(array("'", '"'), array('', ''), $va_row[$vs_sort_field]));
								} else {
									$va_sorted_hits[$va_row['row_id']] .= trim(str_replace(array("'", '"'), array('', ''), $va_row[$vs_sort_field]));
								}
								unset($pa_hits[$va_row['row_id']]);
							}
							
							// Add on hits that aren't sorted because they don't have an attribute associated
							foreach($pa_hits as $vn_id => $va_row) {
								if (!is_array($va_row)) { $va_row = array(); }
								
								if ($vn_num_locales > 1) {
									$va_sorted_hits[$vn_id][1] = $va_row;
								} else {
									$va_sorted_hits[$vn_id] = $va_row;
								}
							}
						}
						continue;
					} else {	
						$va_field_info = $t_table->getFieldInfo($va_tmp[1]);
						if ($va_field_info['START'] && $va_field_info['END']) {
							$va_orderbys[] = $va_field_info['START'].' '.$ps_direction;
							$va_orderbys[] = $va_field_info['END'].' '.$ps_direction;
						} else {
							$va_orderbys[] = $vs_field.' '.$ps_direction;
						}
						
						if ($t_table->hasField('locale_id')) {
							$vs_locale_where = ", ".$vs_table_name.".locale_id";
						}
						
						$vs_sortable_value_fld = $vs_field;
					}
				} else {
					// sort field is in related table 
					$va_path = $this->opo_datamodel->getPath($vs_table_name, $va_tmp[0]);
					
					if (sizeof($va_path) > 2) {
						// many-many
						$vs_last_table = null;
						// generate related joins
						foreach($va_path as $vs_table => $va_info) {
							$t_table = $this->opo_datamodel->getInstanceByTableName($vs_table, true);
							if ($vs_last_table) {
								$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_last_table, $vs_table);
								if (!sizeof($va_rels)) {
									$va_rels = $this->opo_datamodel->getOneToManyRelations($vs_table, $vs_last_table);
								}
    							if ($vs_table == $va_rels['one_table']) {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['one_table']." ON ".$va_rels['one_table'].".".$va_rels['one_table_field']." = ".$va_rels['many_table'].".".$va_rels['many_table_field'];
								} else {
									$va_joins[$vs_table] = "INNER JOIN ".$va_rels['many_table']." ON ".$va_rels['many_table'].".".$va_rels['many_table_field']." = ".$va_rels['one_table'].".".$va_rels['one_table_field'];
								}
							}
							$t_last_table = $t_table;
							$vs_last_table = $vs_table;
						}
						$va_orderbys[] = $vs_field.' '.$ps_direction;
						
						$vs_sortable_value_fld = $vs_field;
					} else {
						$va_rels = $this->opo_datamodel->getRelationships($vs_table_name, $va_tmp[0]);
						if (!$va_rels) { return $pa_hits; }							// return hits unsorted if field is not valid
						$t_rel = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);
						if (!$t_rel->hasField($va_tmp[1])) { return $pa_hits; }
						$va_joins[$va_tmp[0]] = 'LEFT JOIN '.$va_tmp[0].' ON '.$vs_table_name.'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][0].' = '.$va_tmp[0].'.'.$va_rels[$vs_table_name][$va_tmp[0]][0][1]."\n";
						$va_orderbys[] = $vs_field.' '.$ps_direction;
						
						// if the related supports preferred values (eg. *_labels tables) then only consider those in the sort
						if ($t_rel->hasField('is_preferred')) {
							$vs_is_preferred_sql = " ".$va_tmp[0].".is_preferred = 1";
						}
						if ($t_rel->hasField('locale_id')) {
							$vs_locale_where = ", ".$va_tmp[0].".locale_id";
						}
						
						$vs_sortable_value_fld = $vs_field;
					}
				}
				//
				// Grab values and index for sorting later
				//
				
				$va_tmp = explode('.', $vs_sortable_value_fld);
				$vs_sort_field = array_pop($va_tmp);
				$vs_join_sql = join("\n", $va_joins);
				$vs_sql = "
					SELECT {$vs_table_name}.{$vs_table_pk}{$vs_locale_where}, lower({$vs_sortable_value_fld}) {$vs_sort_field}
					FROM {$vs_table_name}
					{$vs_join_sql}
					INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = {$vs_table_name}.{$vs_table_pk}
					".($vs_is_preferred_sql ? 'WHERE' : '')."
						{$vs_is_preferred_sql}
				";
				//print $vs_sql;
				$qr_sort = $this->opo_db->query($vs_sql);
				
				while($qr_sort->nextRow()) {
					$va_row = $qr_sort->getRow();
					if (!($vs_sortable_value = str_replace(array("'", '"'), array('', ''), $va_row[$vs_sort_field]))) {
						$vs_sortable_value = '';
					}
					if (($vn_num_locales > 1) && $vs_locale_where) {
						$va_sorted_hits[$va_row[$vs_table_pk]][$va_row['locale_id']] .= $vs_sortable_value;
					} else {
						$va_sorted_hits[$va_row[$vs_table_pk]] .= $vs_sortable_value;
					}
				}
			}
			
			//
			// Actually sort the hits here...
			//
			if (($vn_num_locales > 1) && $vs_locale_where) {
				$va_sorted_hits = caExtractValuesByUserLocale($va_sorted_hits);
			}
			asort($va_sorted_hits, SORT_STRING);
			
			if ($ps_direction == 'desc') { $va_sorted_hits = array_reverse($va_sorted_hits, true); }
			
			//$this->cleanupTemporaryResultTable();
			
			return $va_sorted_hits;
		}
		# ------------------------------------------------------------------
		/**
		 * @param $pa_hits Array of row_ids to filter. *MUST HAVE row_ids AS KEYS, NOT VALUES*
		 */
		public function filterHitsByACL($pa_hits, $pn_user_id, $pn_access=__CA_ACL_READONLY_ACCESS__, $pa_options=null) {
			$vs_browse_tmp_table = $this->loadListIntoTemporaryResultTable($pa_hits, $this->opo_ca_browse_cache->getCacheKey());
			
			if (!sizeof($pa_hits)) { return $pa_hits; }
			if (!(int)$pn_user_id) { return $pa_hits; }
			if (!($t_table = $this->opo_datamodel->getInstanceByTableNum($this->opn_browse_table_num, true))) { return $pa_hits; }
			
			$vs_table_name = $t_table->tableName();
			$vs_table_pk = $t_table->primaryKey();
			
			$t_user = new ca_users($pn_user_id);
			if (is_array($va_groups = $t_user->getUserGroups()) && sizeof($va_groups)) {
				$va_group_ids = array_keys($va_groups);
				$vs_group_sql = '
						OR
						(ca_acl.group_id IN (?))';
				$va_params = array((int)$this->opn_browse_table_num, (int)$pn_user_id, $va_group_ids, (int)$pn_access);
			} else {
				$va_group_ids = null;
				$vs_group_sql = '';
				$va_params = array((int)$this->opn_browse_table_num, (int)$pn_user_id, (int)$pn_access);
			}
			
			$va_hits = array();
			
			if ($pn_access <= $this->opo_config->get('default_item_access_level')) {
				// Requested access is more restrictive than default access (so return items with default ACL)
				
					// Find records that have ACL that matches
					$qr_sort = $this->opo_db->query("
						SELECT ca_acl.row_id
						FROM ca_acl
						INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = ca_acl.row_id
						WHERE
							(ca_acl.table_num = ?)
							AND
							(
								(ca_acl.user_id = ?)
								{$vs_group_sql}
								OR 
								(ca_acl.user_id IS NULL AND ca_acl.group_id IS NULL)
							)
							AND
							(ca_acl.access >= ?)
					", $va_params);
					
					while($qr_sort->nextRow()) {
						$va_row = $qr_sort->getRow();
						$va_hits[$va_row['row_id']] = true;
					}
					
					// Find records with default ACL
					$qr_sort = $this->opo_db->query("
						SELECT {$vs_browse_tmp_table}.row_id
						FROM {$vs_browse_tmp_table}
						LEFT OUTER JOIN ca_acl ON {$vs_browse_tmp_table}.row_id = ca_acl.row_id AND ca_acl.table_num = ?
						WHERE
							ca_acl.row_id IS NULL;
					", array((int)$this->opn_browse_table_num));
					
					while($qr_sort->nextRow()) {
						$va_row = $qr_sort->getRow();
						$va_hits[$va_row['row_id']] = true;
					}
			} else {
				// Default access is more restrictive than requested access (so *don't* return items with default ACL)
				
					// Find records that have ACL that matches
					$qr_sort = $this->opo_db->query("
						SELECT ca_acl.row_id
						FROM ca_acl
						INNER JOIN {$vs_browse_tmp_table} ON {$vs_browse_tmp_table}.row_id = ca_acl.row_id
						WHERE
							(ca_acl.table_num = ?)
							AND
							(
								(ca_acl.user_id = ?)
								{$vs_group_sql}
								OR 
								(ca_acl.user_id IS NULL AND ca_acl.group_id IS NULL)
							)
							AND
							(ca_acl.access >= ?)
					", $va_params);
					
					while($qr_sort->nextRow()) {
						$va_row = $qr_sort->getRow();
						$va_hits[$va_row['row_id']] = true;
					}
			}
						
			//$this->cleanupTemporaryResultTable();
			
			return $va_hits;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function setCachedFacetHTML($ps_cache_key, $ps_content) {
			if (!is_array($va_cache = $this->opo_ca_browse_cache->getParameter('facet_html'))) { $va_cache = array(); }
			$va_cache[$ps_cache_key] =$ps_content;
			$this->opo_ca_browse_cache->setParameter('facet_html', $va_cache);
			$this->opo_ca_browse_cache->save();
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getCachedFacetHTML($ps_cache_key) {
			if (!is_array($va_cache = $this->opo_ca_browse_cache->getParameter('facet_html'))) { return null; }
			return isset($va_cache[$ps_cache_key]) ? $va_cache[$ps_cache_key] : null;
		}
		# ------------------------------------------------------------------
		
		# ------------------------------------------------------
		# Browse results buffer
		# ------------------------------------------------------
		/**
		 * Created temporary table for use while performing browse
		 */
		private function _createTempTable($ps_name) {
			$this->opo_db->query("
				CREATE TEMPORARY TABLE {$ps_name} (
					row_id int unsigned not null,
					
					primary key (row_id)
				) engine=memory;
			");
			if ($this->opo_db->numErrors()) {
				return false;
			}
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Drops temporary table created while performing browse
		 */
		private function _dropTempTable($ps_name) {
			$this->opo_db->query("
				DROP TABLE {$ps_name};
			");
			if ($this->opo_db->numErrors()) {
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Result filters are criteria through which the results of a browse are passed before being
		 * returned to the caller. They are often used to restrict the domain over which browses operate
		 * (for example, ensuring that a browse only returns rows with a certain "status" field value)
		 * You can only filter on actual fields in the subject table (ie. ca_objects.access, ca_objects.status)
		 * not attributes or fields in related tables
		 *
		 * $ps_access_point is the name of an indexed *intrinsic* field
		 * $ps_operator is one of the following: =, <, >, <=, >=, in, not in
		 * $pm_value is the value to apply; this is usually text or a number; for the "in" and "not in" operators this is a comma-separated list of string or numeric values
		 *			
		 *
		 */
		public function addResultFilter($ps_field, $ps_operator, $pm_value) {
			$ps_operator = strtolower($ps_operator);
			if (!in_array($ps_operator, array('=', '<', '>', '<=', '>=', 'in', 'not in'))) { return false; }
			$t_table = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
			if (!$t_table->hasField($ps_field)) { return false; }
			
			$this->opa_result_filters[] = array(
				'field' => $ps_field,
				'operator' => $ps_operator,
				'value' => $pm_value
			);
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function clearResultFilters() {
			$this->opa_result_filters = array();
		}
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function getResultFilters() {
			return $this->opa_result_filters;
		}
		# ------------------------------------------------------
		# Browse subject name (eg. the name of the table we're browsing as configured in browse.conf
		# ------------------------------------------------------
		/**
		 * Returns the display name of the table the engine is configured to browse, or optionally a specified table name
		 *
		 * @param string $ps_subject_table_name Optional table name to get display name for (eg. ca_objects might return "objects"); if not specified the table the instance is configured to browse is used
		 * @return string display name of table
		 */
		public function getBrowseSubjectName($ps_subject_table_name=null) {
			if (!$ps_subject_table_name) { $ps_subject_table_name = $this->ops_browse_table_name; }
			if (is_array($va_tmp = $this->opo_ca_browse_config->getAssoc($ps_subject_table_name)) && (isset($va_tmp['name']) && $va_tmp['name'])){
				return $va_tmp['name'];
			}
			return $this->ops_browse_table_name;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _filterValueToQueryValue($pa_filter) {
			switch(strtolower($pa_filter['operator'])) {
				case '>':
				case '<':
				case '=':
				case '>=':
				case '<=':
					return (int)$pa_filter['value'];
					break;
				case 'in':
				case 'not in':
					$va_tmp = explode(',', $pa_filter['value']);
					$va_values = array();
					foreach($va_tmp as $vs_tmp) {
						$va_values[] = (int)$vs_tmp;
					}
					return "(".join(",", $va_values).")";
					break;
				default:
					return $pa_filter['value'];
					break;
			}
		}
		# ------------------------------------------------------
		# Type filtering
		# ------------------------------------------------------
		/**
		 * When type restrictions are specified, the browse will only browse upon items of the given types. 
		 * If you specify a type that has hierarchical children then the children will automatically be included
		 * in the restriction. You may pass numeric type_id and alphanumeric type codes interchangeably.
		 *
		 * @param array $pa_type_codes_or_ids List of type_id or code values to filter browse by. When set, the browse will only consider items of the specified types. Using a hierarchical parent type will automatically include its children in the restriction. 
		 * @param array $pa_options Options include
	 	 *		includeSubtypes = include any child types in the restriction. Default is true.
		 * @return boolean True on success, false on failure
		 */
		public function setTypeRestrictions($pa_type_codes_or_ids, $pa_options=null) {
			$this->opa_browse_type_ids = $this->_convertTypeCodesToIDs($pa_type_codes_or_ids);
			$this->opo_ca_browse_cache->setTypeRestrictions($this->opa_browse_type_ids);
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 *
		 * @param array $pa_type_codes_or_ids List of type codes or ids 
		 * @param array $pa_options Options include
		 *		includeSubtypes = include any child types in the restriction. Default is true.
		 * @return array List of type_ids
		 */
		private function _convertTypeCodesToIDs($pa_type_codes_or_ids, $pa_options=null) {
			$vs_md5 = caMakeCacheKeyFromOptions($pa_type_codes_or_ids);
			
			if (isset(BrowseEngine::$s_type_id_cache[$vs_md5])) { return BrowseEngine::$s_type_id_cache[$vs_md5]; }
			
			if (isset($pa_options['instance']) && is_object($pa_options['instance'])) {
				$t_instance = $pa_options['instance'];
			} else {
				$t_instance = $this->getSubjectInstance();
			}
			$va_type_ids = array();
			
			if (!$pa_type_codes_or_ids) { return false; }
			if (is_array($pa_type_codes_or_ids) && !sizeof($pa_type_codes_or_ids)) { return false; }
			if (!is_array($pa_type_codes_or_ids)) { $pa_type_codes_or_ids = array($pa_type_codes_or_ids); }
			
			$t_list = new ca_lists();
			if (!method_exists($t_instance, 'getTypeListCode')) { return false; }
			if (!($vs_list_name = $t_instance->getTypeListCode())) { return false; }
			$va_type_list = $t_instance->getTypeList();
			
			foreach($pa_type_codes_or_ids as $vs_code_or_id) {
				if (!trim($vs_code_or_id)) { continue; }
				if (!is_numeric($vs_code_or_id)) {
					$vn_type_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
				} else {
					$vn_type_id = (int)$vs_code_or_id;
				}
				
				if (!$vn_type_id) { return false; }

				if (isset($va_type_list[$vn_type_id]) && $va_type_list[$vn_type_id]) {	// is valid type for this subject
					// See if there are any child types
					if (caGetOption('includeSubtypes', $pa_options, true) && $this->opb_dont_expand_type_restrictions) {
						$t_item = new ca_list_items($vn_type_id);
						$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
					}
					$va_ids[] = $vn_type_id;
					$va_type_ids = array_merge($va_type_ids, $va_ids);
				}
			}
			$va_type_ids = array_keys(array_flip($va_type_ids));
			BrowseEngine::$s_type_id_cache[$vs_md5] = $va_type_ids;
			return $va_type_ids;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of type_id values to restrict browse to. Return values are always numeric types, 
		 * never codes, and will include all type_ids to filter on, including children of hierarchical types.
		 *
		 * @return array List of type_id values to restrict browse to.
		 */
		public function getTypeRestrictionList() {
			if (function_exists("caGetTypeRestrictionsForUser")) {
				$va_pervasive_types = caGetTypeRestrictionsForUser($this->ops_tablename);	// restrictions set in app.conf or by associated user role
				
				if (!is_array($va_pervasive_types) || !sizeof($va_pervasive_types)) { return $this->opa_browse_type_ids; }
				
				if (is_array($this->opa_browse_type_ids) && sizeof($this->opa_browse_type_ids)) {
					$va_filtered_types = array();
					foreach($this->opa_browse_type_ids as $vn_id) {
						if (in_array($vn_id, $va_pervasive_types)) {
							$va_filtered_types[] = $vn_id;
						}
					}
					return $va_filtered_types;
				} else {
					return $va_pervasive_types;
				}
			}
			return $this->opa_browse_type_ids;
		}
		# ------------------------------------------------------
		/**
		 * Removes any specified type restrictions on the browse
		 *
		 * @return boolean Always returns true
		 */
		public function clearTypeRestrictionList() {
			$this->opa_browse_type_ids = null;
			return true;
		}
		# ------------------------------------------------------
		/**
		 * If set type restrictions will not be expanded to include child types.
		 *
		 * @param bool $pb_value If set to true, type restriction will not be expanded; default is true if omitted
		 *
		 * @return boolean Always returns true
		 */
		public function dontExpandTypeRestrictions($pb_value=true) {
			$this->opb_dont_expand_type_restrictions = (bool)$pb_value;
			return true;
		}
		# ------------------------------------------------------------------
		#
		# ------------------------------------------------------------------
		/**
		 * 
		 */
		public function getCountsByFieldForSearch($ps_search, $pa_options=null) {
			require_once(__CA_LIB_DIR__.'/core/Search/SearchCache.php');
			
			$vn_tablenum = $this->opo_datamodel->getTableNum($this->ops_tablename);
			
			$o_cache = new SearchCache();
			
			if ($o_cache->load($ps_search, $vn_tablenum, $pa_options)) {
				return $o_cache->getCounts();
			}
			return array();
		}
		# ------------------------------------------------------
		/**
		 * Converts list of relationships type codes and/or numeric ids to an id-only list
		 */
		private function _getRelationshipTypeIDs($pa_relationship_types, $pm_relationship_table_or_id) {
			$t_rel_type = new ca_relationship_types();
			$va_type_list = $pa_relationship_types;
			foreach($va_type_list as $vn_i => $vm_type) {
				if (!trim($vm_type)) { unset($pa_relationship_types[$vn_i]); continue;}
				if (!is_numeric($vm_type)) {
					// try to translate item_value code into numeric id
					if (!($vn_type_id = $t_rel_type->getRelationshipTypeID($pm_relationship_table_or_id, $vm_type))) {  unset($pa_relationship_types[$vn_i]); continue; }
					unset($pa_relationship_types[$vn_i]);
					$pa_relationship_types[] = $vn_type_id;
				}  else {
					if (!$t_rel_type->load($vm_type)) {  unset($pa_relationship_types[$vn_i]); continue; }
					$vn_type_id = $t_rel_type->getPrimaryKey();
				}
				
				$va_ids = $t_rel_type->getHierarchyChildren($vn_type_id, array('idsOnly' => true));
				
				if (is_array($va_ids)) {
					foreach($va_ids as $vn_id) {
						$pa_relationship_types[] = $vn_id;
					}
				}
			}
			
			return $pa_relationship_types;
		}
		# ------------------------------------------------------
		# Utilities
		# ------------------------------------------------------
		/**
		 *
		 */
		private function _getRelativeFacetSQLData($ps_relative_to_table, $pa_options) {
			switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($ps_relative_to_table, $this->ops_browse_table_name)))) {
				case 3:
					$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
					$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
					$vs_key = 'relation_id';
					break;
				case 2:
					$t_item_rel = null;
					$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
					$vs_key = $t_rel_item->primaryKey();
					break;
				default:
					// bad table
					return null;
					break;
			}
			
			$va_joins = $va_wheres = array();
			
			$vs_cur_table = array_shift($va_path);
			foreach($va_path as $vs_join_table) {
				$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
				$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				$vs_cur_table = $vs_join_table;
			}
			if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
				$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
			}
			
			return array('joins' => $va_joins, 'wheres' => $va_wheres);
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		private function _getRelativeExecuteSQLData($ps_relative_to_table) {
			if (!($t_target = $this->opo_datamodel->getInstanceByTableName($ps_relative_to_table, true))) { return null; }
			$vs_target_browse_table_num = $t_target->tableNum();
			$vs_target_browse_table_pk = $t_target->primaryKey();
			$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_browse_table_name, true);
			
			switch(sizeof($va_path = array_keys($this->opo_datamodel->getPath($ps_relative_to_table, $this->ops_browse_table_name)))) {
				case 3:
					$t_item_rel = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
					$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[2], true);
					$vs_key = 'relation_id';
					break;
				case 2:
					$t_item_rel = null;
					$t_rel_item = $this->opo_datamodel->getInstanceByTableName($va_path[1], true);
					$vs_key = $t_rel_item->primaryKey();
					break;
				default:
					// bad table
					return null;
					break;
			}
			
			$va_joins = $va_wheres = array();
			
			$vs_cur_table = array_shift($va_path);
			foreach($va_path as $vs_join_table) {
				$va_rel_info = $this->opo_datamodel->getRelationships($vs_cur_table, $vs_join_table);
				$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				$vs_cur_table = $vs_join_table;
			}
			if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
				$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
			}
			
			$va_relative_to_join = array();
			if ($t_item_rel) {
				$va_relative_to_join[] = "INNER JOIN ".$t_item_rel->tableName()." ON ".$t_item_rel->tableName().".".$t_item->primaryKey()." = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey();
			}
			$va_relative_to_join[] = "INNER JOIN {$ps_relative_to_table} ON {$ps_relative_to_table}.{$vs_target_browse_table_pk} = ".$t_item_rel->tableName().".".$t_target->primaryKey();
			
			$vs_relative_to_join = join("\n", $va_relative_to_join);
			
			return array(
				'joins' => $va_joins, 'wheres' => $va_wheres, 'relative_joins' => $va_relative_to_join,
				'target_table_name' => $ps_relative_to_table,
				'target_table_num' => $vs_target_browse_table_num,
				'target_table_pk' => $vs_target_browse_table_pk
			);
		}
		# ------------------------------------------------------
	}
?>