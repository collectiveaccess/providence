<?php
/** ---------------------------------------------------------------------
 * app/lib/Browse/BrowseEngine.php : Base class for browse interfaces
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2018 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/BaseFindEngine.php');
 	require_once(__CA_LIB_DIR__.'/Datamodel.php');
 	require_once(__CA_LIB_DIR__.'/Db.php');
 	require_once(__CA_LIB_DIR__.'/Attributes/Values/AuthorityAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/CollectionsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/EntitiesAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/LoansAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/MovementsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/ObjectLotsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/ObjectRepresentationsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/ObjectsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/OccurrencesAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/PlacesAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/StorageLocationsAttributeValue.php');
	require_once(__CA_LIB_DIR__.'/Attributes/Values/ListAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/Browse/BrowseResult.php');
 	require_once(__CA_LIB_DIR__.'/Browse/BrowseCache.php');
 	require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');
 	require_once(__CA_APP_DIR__.'/helpers/searchHelpers.php');
	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');

 	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
	require_once(__CA_MODELS_DIR__.'/ca_acl.php');
	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');

	class BrowseEngine extends BaseFindEngine {
		# ------------------------------------------------------
		# Properties
		# ------------------------------------------------------
		/**
		 * @var Table number of browse subject (ie. the kind of record we're browsing for)
		 */
		private $opn_browse_table_num;

		/**
		 * @var Table name of browse subject
		 */
		private $ops_browse_table_name;

		/**
		 * @var Instance of BrowseCache class
		 */
		private $opo_ca_browse_cache;

		/**
		 * @var subject type_id to limit browsing to (eg. only browse ca_objects with type_id = 10)
		 */
		private $opa_browse_type_ids = null;

		/**
		 * @var option to expand type restrictions hierarchically
		 */
		private $opb_dont_expand_type_restrictions = false;
		private $opb_dont_expand_source_restrictions = false;

		/**
		 * @var Instance of Datamodel class
		 */
		protected $opo_datamodel;

		/**
		 * @var Instance of Db database client
		 */
		protected $opo_db;

		/**
		 * @var Instance of Configuration class loaded with application configuration (app.conf)
		 */
		private $opo_config;

		/**
		 * @var Instance of Configuration class loaded with browse configuration (browse.conf)
		 */
		private $opo_ca_browse_config;

		/**
		 * @var Array of browse settings loaded from browse.conf
		 */
		private $opa_browse_settings;

		/**
		 * @var Array of filters to apply to browse results
		 */
		private $opa_result_filters;

		/**
		 * @var
		 */
		private $ops_facet_group = null;

		/**
		 * @var Flag indicating if browse criteria have changed since the last execute()
		 */
		private $opb_criteria_have_changed = false;
		# ------------------------------------------------------
		/**
		 * @var Type_id cache
		 */
		static $s_type_id_cache = array();
		static $s_source_id_cache = array();
		# ------------------------------------------------------
		/**
		 *
		 */
		public function __construct($pm_subject_table_name_or_num, $pn_browse_id=null, $ps_browse_context='') {
			$this->opo_db = new Db();

			$this->opa_result_filters = array();

			if (is_numeric($pm_subject_table_name_or_num)) {
				$this->opn_browse_table_num = intval($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = Datamodel::getTableName($this->opn_browse_table_num);
			} else {
				$this->opn_browse_table_num = Datamodel::getTableNum($pm_subject_table_name_or_num);
				$this->ops_browse_table_name = $pm_subject_table_name_or_num;
			}

			$this->opo_config = Configuration::load();
			$this->opo_ca_browse_config = Configuration::load(__CA_CONF_DIR__.'/browse.conf');
			$this->opa_browse_settings = $this->opo_ca_browse_config->getAssoc($this->ops_browse_table_name);

			// Add "virtual" search facet - allows seeding of a browse with a search
			$this->opa_browse_settings['facets']['_search'] = array(
				'label_singular' => _t('Search'),
				'label_plural' => _t('Searches')
			);
			// Add "virtual" relationship types facet - allows filtering of a browse by relationship types in a specific relationships (Eg. only return records that have at least one relationship with one of the specified types)
			$this->opa_browse_settings['facets']['_reltypes'] = array(
				'label_singular' => _t('Relationship type'),
				'label_plural' => _t('Relationship types')
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

				global $g_ui_locale;
				if(is_array($va_facet_info['label_singular'])) {
					if(isset($va_facet_info['label_singular'][$g_ui_locale])) {
						$va_facet_info['label_singular'] = $va_facet_info['label_singular'][$g_ui_locale];
					}
				}

				if(is_array($va_facet_info['label_plural'])) {
					if(isset($va_facet_info['label_plural'][$g_ui_locale])) {
						$va_facet_info['label_plural'] = $va_facet_info['label_plural'][$g_ui_locale];
					}
				}
				
				
				// group_mode = hierarchical is only supported for location facets when current location criteria is storage locations-only
				if (($va_facet_info['type'] === 'location') && (caGetOption('group_mode', $va_facet_info, null) == 'hierarchical')) {
					if((is_array($current_location_criteria = $this->opo_config->get('current_location_criteria'))) && (sizeof($current_location_criteria) == 1) && isset($current_location_criteria['ca_storage_locations'])) {
				    	$va_facet_info['table'] = 'ca_storage_locations';
				    } else {
				    	$va_facet_info['group_mode'] == 'none';
				    }
				}

				// generate_facets_for_types config directive triggers auto-generation of facet config for each type of an authority item
				// it's typically employed to provide browsing of occurrences where the various types are unrelated
				// you can also use this on other authorities to provide a finer-grained browse without having to know the type hierarchy ahead of time
				if (($va_facet_info['type'] === 'authority') && isset($va_facet_info['generate_facets_for_types']) && $va_facet_info['generate_facets_for_types']) {
					// get types for authority
					$t_table = Datamodel::getInstanceByTableName($va_facet_info['table'], true);

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
						if ($t_element->load(array('element_code' => array_pop(explode(".", $va_facet_info['element_code']))))) {
							if (($t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_LIST__) && ($vn_list_id = $t_element->get('list_id'))) {
								if ($vn_item_id = caGetListItemID($vn_list_id, $va_facet_info['single_value'])) {
									$va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
								}
							}
						}
						break;
					case 'fieldList':
						$t_instance = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true);
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
		 * Returns unique id for current browse. The key is calculated based upon the browse subject,
		 * various options and the current browse criteria.
		 *
		 * @return string
		 */
		public function getBrowseID() {
			return $this->opo_ca_browse_cache->getCacheKey();
		}
		# ------------------------------------------------------
		/**
		 * Returns the table number of the browse subject
		 *
		 * @return int
		 */
		public function getSubject() {
			return $this->opn_browse_table_num;
		}
		# ------------------------------------------------------
		/**
		 * Returns an instance of the current browse subject
		 *
		 * @return BaseModel
		 */
		public function getSubjectInstance() {
			return Datamodel::getInstanceByTableNum($this->opn_browse_table_num, true);
		}
		# ------------------------------------------------------
		/**
		 * Sets the current browse context.
		 * Separate cache namespaces are maintained for each browse context; this means that
		 * if you do the same browse in different contexts each will be cached separately. This
		 * is handy when you have multiple interfaces (say the cataloguing back-end and a public front-end)
		 * using the same browse engine and underlying cache tables.
		 *
		 * @param string $ps_browse_context
		 * @return bool True on success
		 */
		public function setContext($ps_browse_context) {
			$va_params = $this->opo_ca_browse_cache->setParameter('context', $ps_browse_context);

			return true;
		}
		# ------------------------------------------------------
		/**
		 * Returns current browse context
		 *
		 * @return string
		 */
		public function getContext() {
			return ($vs_context = $this->opo_ca_browse_cache->getParameter('context')) ? $vs_context : '';
		}
		# ------------------------------------------------------
		# Add/remove browse criteria
		# ------------------------------------------------------
		/**
		 * Add criteria to the current browse
		 *
		 * @param string $ps_facet_name Name of facet for which to add criteria
		 * @param array $pa_row_ids One or more facet values to browse on
		 *
		 * @return boolean - true on success, false on error
		 */
		public function addCriteria($ps_facet_name, $pa_row_ids, $pa_display_strings=null) {
			if (is_null($pa_row_ids)) { return null;}
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			if (!in_array($ps_facet_name, ['_search', '_reltypes'])) {
				if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return false; }
				if (!$this->isValidFacetName($ps_facet_name)) { return false; }
			}

			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			$va_criteria_display_strings = $this->opo_ca_browse_cache->getParameter('criteria_display_strings');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
			
			$purifier = new HTMLPurifier();
			foreach($pa_row_ids as $vn_i => $vn_row_id) {
			    $vn_row_id = $purifier->purify(urldecode($vn_row_id)); // sanitize facet values
				$va_criteria[$ps_facet_name][urldecode($vn_row_id)] = true;
				
				if (isset($pa_display_strings[$vn_i])) { $va_criteria_display_strings[$ps_facet_name][urldecode($vn_row_id)] = $pa_display_strings[$vn_i]; }
			}
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('criteria_display_strings', $va_criteria_display_strings);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);

			$this->opb_criteria_have_changed = true;
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Remove criteria from the current browse
		 *
		 * @param string $ps_facet_name Name of facet for which to remove criteria
		 * @param array $pa_row_ids One or more facet values applied as criteria. Values that are not current criteria are ignored.
		 *
		 * @return boolean - true on success, false on error
		 */
		public function removeCriteria($ps_facet_name, $pa_row_ids) {
			if (is_null($pa_row_ids)) { return false;}
			if (!($va_facet_info = $this->getInfoForFacet($ps_facet_name))) { return false; }
			if (!$this->isValidFacetName($ps_facet_name)) { return false; }

			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			$va_criteria_display_strings = $this->opo_ca_browse_cache->getParameter('criteria_display_strings');
			if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }

			foreach($pa_row_ids as $vn_row_id) {
				unset($va_criteria[$ps_facet_name][urldecode($vn_row_id)]);
				unset($va_criteria_display_strings[$ps_facet_name][urldecode($vn_row_id)]);
				if(is_array($va_criteria[$ps_facet_name]) && !sizeof($va_criteria[$ps_facet_name])) {
					unset($va_criteria[$ps_facet_name]);
					unset($va_criteria_display_strings[$ps_facet_name]);
				}
			}
			
			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('criteria_display_strings', $va_criteria_display_strings);
			$this->opo_ca_browse_cache->setParameter('sort', null);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);

			$this->opb_criteria_have_changed = true;

			return true;
		}
		# ------------------------------------------------------
		/**
		 * Indicates if the browse criteria have changed since the last execute()
		 *
		 * @return bool
		 */
		public function criteriaHaveChanged() {
			return $this->opb_criteria_have_changed;
		}
		# ------------------------------------------------------
		/**
		 * Returns the number of criteria on the current browse
		 *
		 * @return int
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
		 * Removes all criteria from the current browse. If the $ps_facet_name parameter is set
		 * then all criteria for that facet are removed, otherwise all criteria for all facets is removed.
		 *
		 * @param string $ps_facet_name Optional name of facet for which to remove criteria
		 * @return bool True on success, false on failure
		 *
		 */
		public function removeAllCriteria($ps_facet_name=null) {
			if ($ps_facet_name && !$this->isValidFacetName($ps_facet_name)) { return false; }

			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			$va_criteria_display_strings = $this->opo_ca_browse_cache->getParameter('criteria_display_strings');
			if($ps_facet_name) {
				$va_criteria[$ps_facet_name] = array();
				$va_criteria_display_strings[$ps_facet_name] = array();
			} else {
				$va_criteria = array();
				$va_criteria_display_strings = array();
			}

			$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);
			$this->opo_ca_browse_cache->setParameter('criteria_display_strings', $va_criteria_display_strings);
			$this->opo_ca_browse_cache->setParameter('facet_html', null);

			$this->opb_criteria_have_changed = true;

			return true;
		}
		# ------------------------------------------------------
		/**
		 * Returns a list of criteria on the current browse. If the $ps_facet_name parameter is set
		 * then only criteria for the facet are returned, otherwise all criteria for all facets are returned.
		 * The returned array contains only facet codes and values.
		 *
		 * @param string $ps_facet_name Optional name of facet for which to list criteria
		 * @return array
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
		 * Returns a list of criteria on the current browse. If the $ps_facet_name parameter is set
		 * then only criteria for the facet are returned, otherwise all criteria for all facets are returned.
		 * The returned array contains facet codes, values and display labels for criterion. Use this method
		 * only if you need display labels. If you only need basic criteria data use BrowseEngine::getCriteria()
		 * which is faster.
		 *
		 * @param string $ps_facet_name Optional name of facet for which to list criteria
		 * @return array
		 *
		 * @see BrowseEngine::getCriteria
		 */
		public function getCriteriaWithLabels($ps_facet_name=null) {
			if ($ps_facet_name && (!$this->isValidFacetName($ps_facet_name))) { return null; }

			$va_criteria = $this->opo_ca_browse_cache->getParameter('criteria');
			$va_criteria_display_strings = $this->opo_ca_browse_cache->getParameter('criteria_display_strings');
			
			$va_criteria_with_labels = array();
			if($ps_facet_name) {
				if (is_array($va_criteria_display_strings[$ps_facet_name])) {
					foreach($va_criteria_display_strings[$ps_facet_name] as $vm_criterion => $vs_display_criterion) {
						$va_criteria_with_labels[$vm_criterion] = $this->getCriterionLabel($ps_facet_name, $vs_display_criterion);
					}
				} elseif(is_array($va_criteria[$ps_facet_name])) {
					foreach($va_criteria[$ps_facet_name] as $vm_criterion => $vn_tmp) {
						$va_criteria_with_labels[$vm_criterion] = $this->getCriterionLabel($ps_facet_name, $vm_criterion);
					}
				}
			} else {
				if (is_array($va_criteria)) {
					foreach($va_criteria as $vs_facet_name => $va_criteria_by_facet) {
						if (is_array($va_criteria_display_strings[$vs_facet_name])) {
							foreach($va_criteria_display_strings[$vs_facet_name] as $vm_criterion => $vs_display_criterion) {
								$va_criteria_with_labels[$vs_facet_name][$vm_criterion] = $this->getCriterionLabel($vs_facet_name, $vs_display_criterion);
							}
						} else {
							foreach($va_criteria_by_facet as $vm_criterion => $vn_tmp) {
								$va_criteria_with_labels[$vs_facet_name][$vm_criterion] = $this->getCriterionLabel($vs_facet_name, $vm_criterion);
							}
						}
					}
				}
			}
			return $va_criteria_with_labels;
		}
		# ------------------------------------------------------
		/**
		 * Returns a display label for a given criterion and facet.
		 *
		 * @param string $ps_facet_name Name of facet
		 * @param mixed $pm_criterion
		 * @return string
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
					if (!($t_table = Datamodel::getInstanceByTableName((isset($va_facet_info['relative_to']) && $va_facet_info['relative_to']) ? $va_facet_info['relative_to'] : $this->ops_browse_table_name, true))) { break; }
					if (!$t_table->load($pn_row_id)) { return '???'; }

					return $t_table->getLabelForDisplay();
					break;
				# -----------------------------------------------------
				case 'authority':
					if (!($t_table = Datamodel::getInstanceByTableName($va_facet_info['table'], true))) { break; }
					if (!$t_table->load($pn_row_id)) { return '???'; }

					return $t_table->getLabelForDisplay();
					break;
				# -----------------------------------------------------
			    case 'relationship_types':  
			        if (!($t_rel = Datamodel::getInstanceByTableName($va_facet_info['relationship_table'], true))) { break; }
			        $t_rel_type = new ca_relationship_types();
			        $info = $t_rel_type->getRelationshipInfo($va_facet_info['relationship_table']);
			        return isset($info[(int)$pn_row_id]) ? $info[(int)$pn_row_id]['typename'] : "???";
			        break;
				# -----------------------------------------------------
				case 'attribute':
					$t_element = new ca_metadata_elements();
					if (!$t_element->load(array('element_code' => array_pop(explode(".", $va_facet_info['element_code']))))) {
						return urldecode($pn_row_id);
					}

					$vn_element_id = $t_element->getPrimaryKey();
					switch($vn_element_type = $t_element->get('datatype')) {
						case __CA_ATTRIBUTE_VALUE_LIST__:
							$vs_label =  caProcessTemplateForIDs("^ca_list_items.hierarchy.preferred_labels.name_plural", "ca_list_items", array($pn_row_id), array("delimiter" => " ➜ "));
							
							if(is_array($va_facet_info['relabel']) && isset($va_facet_info['relabel'][$vs_label])) {
							    $vs_label = $va_facet_info['relabel'][$vs_label];
							}
							return $vs_label;
							break;
						case __CA_ATTRIBUTE_VALUE_OBJECTS__:
						case __CA_ATTRIBUTE_VALUE_ENTITIES__:
						case __CA_ATTRIBUTE_VALUE_PLACES__:
						case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
						case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
						case __CA_ATTRIBUTE_VALUE_LOANS__:
						case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
						case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
						case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
							if ($t_rel_item = AuthorityAttributeValue::elementTypeToInstance($vn_element_type)) {
								return ($t_rel_item->load($pn_row_id)) ? $t_rel_item->getLabelForDisplay() : "???";
							}
							break;
						case __CA_ATTRIBUTE_VALUE_GEONAMES__:
						    $value = ca_attribute_values::getValuesFor($pn_row_id);
							return preg_replace('![ ]*\[[^\]]*\]!', '', $value['value_longtext1']);
						case __CA_ATTRIBUTE_VALUE_CURRENCY__:
						    $value = ca_attribute_values::getValuesFor($pn_row_id);
							return $value['value_longtext1'].' '.sprintf("%4.2f", $value['value_decimal1']);
							break;
						default:
						    $value = ca_attribute_values::getValuesFor($pn_row_id);
							return $value['value_longtext1'];
							break;
					}

					break;
				# -----------------------------------------------------
				case 'field':
					if (!($t_item = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
					if($vb_is_bit = ($t_item->getFieldInfo($va_facet_info['field'], 'FIELD_TYPE') == FT_BIT)) {
						return ((bool)$pn_row_id) ? caGetOption('label_yes', $va_facet_info, _t('Yes')) : caGetOption('label_no', $va_facet_info, _t('No'));
					}

					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'violations':
					if (!($t_rule = Datamodel::getInstanceByTableName('ca_metadata_dictionary_rules', true))) { break; }
					if ($t_rule->load(array('rule_code' => $pn_row_id))) {
						return $t_rule->getSetting('label');
					}
					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'checkouts':
					$vs_status_text = null;
					$vs_status_code = (isset($va_facet_info['status']) && $va_facet_info['status']) ? $va_facet_info['status'] : $pn_row_id;
					switch($vs_status_code) {
						case 'overdue':
							$vs_status_text = _t('Overdue');
							break;
						case 'reserved':
							$vs_status_text = _t('Reserved');
							break;
						case 'available':
							$vs_status_text = _t('Available');
							break;
						default:
						case 'out':
							$vs_status_text = _t('Out');
							break;
					}


					$va_params = array();
					switch($va_facet_info['mode']) {
						case 'user':
							$vs_name = null;
							$t_user = new ca_users($pn_row_id);
							if ($t_user->getPrimaryKey()) {
								$vs_name = $t_user->get('fname').' '.$t_user->get('lname').(($vs_email = $t_user->get('email')) ? " ({$vs_email})" : "");

								return _t('%1 for %2', $vs_status_text, $vs_name);
							}
							break;
						default:
						case 'all':
							return $vs_status_text;
							break;
					}
					return urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'location':
					$va_row_tmp = explode(":", urldecode($pn_row_id));
					if (
						(sizeof($va_row_tmp) < 3)
						&&
						($object_location_tracking_relationship_type = $this->opo_config->get('object_storage_location_tracking_relationship_type'))
						&&
						($object_location_tracking_relationship_type_id = array_shift(caMakeRelationshipTypeIDList('ca_objects_x_storage_locations', [$object_location_tracking_relationship_type])))
					) {
						//
						// Hierarchical display of current location facets is only available when pure storage location tracking (ie. only 
						// locations, not loans, occurrences etc.) is configured. The value of location criteria is typically in the 
						// form <table num>:<type id>:<row id> but is shortened to just the row_id (which is the storage location location_id)
						// by the hierarchy browser. In this case we can assume the table number is 119 (ca_objects_x_storage_locations) and
						// the type_id is whatever is configured in "object_storage_location_tracking_relationship_type" in app.conf.
						//
						// We prepend those values below, allowing the criterion value to behave as a standard location value/
						//
						array_unshift($va_row_tmp, $object_location_tracking_relationship_type_id); 
						if (sizeof($va_row_tmp) < 3) { array_unshift($va_row_tmp, 119); }	// assume ca_objects_x_storage_locations
					}
		
					$vs_loc_table_name = Datamodel::getTableName($va_row_tmp[0]);
					$va_collapse_map = $this->getCollapseMapForLocationFacet($va_facet_info);

					$t_instance = Datamodel::getInstanceByTableName($vs_loc_table_name, true);

					if (($vs_table_name = $vs_loc_table_name) == 'ca_objects_x_storage_locations') {
						$vs_table_name = 'ca_storage_locations';
					}
					
					if (isset($va_collapse_map[$vs_table_name][$va_row_tmp[1]])) {
						// Class/subclass is collapsable
						return $va_collapse_map[$vs_table_name][$va_row_tmp[1]];
					} elseif(isset($va_collapse_map[$vs_table_name]['*'])) {
						// Class is collapsable
						return $va_collapse_map[$vs_table_name]['*'];
					} elseif($va_row_tmp[2] && ($qr_res = caMakeSearchResult($vs_table_name, [$va_row_tmp[2]])) && $qr_res->nextHit()) {
						// Return label for id
						$va_config = ca_objects::getConfigurationForCurrentLocationType($vs_table_name, $va_row_tmp[1]);
						$vs_template = isset($va_config['template']) ? $va_config['template'] : "^{$vs_table_name}.preferred_labels";

						return caTruncateStringWithEllipsis($qr_res->getWithTemplate($vs_template), 30, 'end');
					}
					return '???';
					break;
				# -----------------------------------------------------
				case 'normalizedLength':
					$vn_start = urldecode($pn_row_id);
					if (!($vs_output_units = caGetLengthUnitType($vs_units=caGetOption('units', $va_facet_info, 'm')))) {
						$vs_output_units = Zend_Measure_Length::METER;
					}
					$vs_increment = caGetOption('increment', $va_facet_info, '1 m');
					$vo_increment = caParseLengthDimension($vs_increment);
					$vn_increment_in_current_units = (float)$vo_increment->convertTo($vs_output_units, 6, 'en_US');
					$vn_end = $vn_start + $vn_increment_in_current_units;
					return "{$vn_start} {$vs_units} - {$vn_end} {$vs_units}";
					break;
				# -----------------------------------------------------
				case 'normalizedDates':
					return ($pn_row_id === 'null') ? _t('Date unknown') : urldecode($pn_row_id);
					break;
				# -----------------------------------------------------
				case 'fieldList':
					if (!($t_item = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true))) { break; }
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

					if($va_facet_info['table'] && ($t_browse_table = Datamodel::getInstanceByTableName($vs_facet_table = $va_facet_info['table'], true))) {
						if (!($app = AppController::getInstance())) { return '???'; }
						if ($t_browse_table->load($pn_row_id) && $t_browse_table->isReadable($app->getRequest(), 'preferred_labels')) {
							
							return $t_browse_table->getWithTemplate(isset($va_facet_info['display']) ? $va_facet_info['display'] : "^".$t_browse_table->tableName().".preferred_labels");
						}
					}
					return '???';
					break;
				# -----------------------------------------------------
				case 'dupeidno':
					return _t("%1 repeats", $pn_row_id);
					break;
				# -----------------------------------------------------
				default:
					if (in_array($ps_facet_name, ['_search', '_reltypes'])) { 
						return $pn_row_id; 
					}
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
		 *
		 * @return array
		 */
		public function getInfoForFacets() {
			return $this->opa_browse_settings['facets'];
		}
		# ------------------------------------------------------
		/**
		 * Return info for specified facet, or null if facet is not valid
		 *
		 * @param string $ps_facet_name
		 * @return array
		 */
		public function getInfoForFacet($ps_facet_name) {
			if (!$this->isValidFacetName($ps_facet_name)) { return null; }
			$va_facets = $this->opa_browse_settings['facets'];
			return $va_facets[$ps_facet_name];
		}
		# ------------------------------------------------------
		/**
		 * Returns true if facet exists, false if not
		 *
		 * @param string $ps_facet_name
		 * @return bool
		 */
		public function isValidFacetName($ps_facet_name) {
			$va_facets = $this->getInfoForFacets();
			return (isset($va_facets[$ps_facet_name])) ? true : false;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all valid facet names
		 *
		 * @return array()
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
			$t_subject = Datamodel::getInstanceByTableNum($this->opn_browse_table_num, true);
			$vs_type_list_code = $t_subject->getTypeListCode();

			$va_criteria_facets = is_array($va_tmp = $this->getCriteria()) ? array_keys($this->getCriteria()) : array();

			//
			if (is_array($va_type_restrictions) && sizeof($va_type_restrictions)) {
				$va_facets = array();
				foreach($this->opa_browse_settings['facets'] as $vs_facet_name => $va_facet_info) {
					if (in_array($vs_facet_name, $va_criteria_facets) && (caGetOption('type', $va_facet_info, null) == 'field')) { continue; }	// fields can only appear once
					if (isset($va_facet_info['requires']) && !is_array($va_facet_info['requires']) && $va_facet_info['requires']) { $va_facet_info['requires'] = array($va_facet_info['requires']); }
					//
					// enforce "requires" setting, which allows one to specify that a given facet should only appear if any one
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
		 * @return bool
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
		 *
		 * @return array
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
		 * Returns list of facets that will return content for the current browse table assuming no criteria
		 * It is the list of facets returned as "available" when no criteria are specific, in other words.
		 *
		 * Note that this method does NOT take into account type restrictions
		 *
		 * @return array List of facet codes
		 */
		public function getFacetsWithContentList() {
			$t_browse = new BrowseEngine($this->opn_browse_table_num, null, $this->getContext());
			return $t_browse->getFacetList();
		}
		# ------------------------------------------------------
		/**
		 * Returns list of all facets that will return content for the current browse table assuming no criteria
		 * with full facet information is included. If all you need is the list of facets with content, and not
		 * full facet information consider using BrowseEngine::getFacetsWithContentList() which is faster.
		 *
		 * @return array
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
		 * Perform the browse using currently applied criteria, calculating the result set and browse facets
		 * required for subsequent browse refinement. You need to call execute() after setting up your browse
		 * criteria and options to:
		 *		• Get the result set reflecting the current browse state
		 *		• Fetch browse facets that reflect the current browse state
		 *
		 * @param array $pa_options Options include:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 *		noCache = don't use cached browse results
		 *		showDeleted = if set to true, related items that have been deleted are returned. Default is false.
		 *		limitToModifiedOn = if set returned results will be limited to rows modified within the specified date range. The value should be a date/time expression parse-able by TimeExpressionParser
		 *		user_id = If set item level access control is performed relative to specified user_id, otherwise defaults to logged in user
		 *		expandResultsHierarchically = expand result set items that are hierarchy roots to include their entire hierarchy. [Default is false]
		 *		omitChildRecords = only return records that are the root of whatever hierarchy they are in. [Default is false]
		 *		rootRecordsOnly = Synonym for omitChildRecords.
		 *		omitChildRecordsForTypes = List of types for which to return only records that are not children [Default is null]
		 *
		 * @return bool True on success, null if the browse could not be executed (Eg. no settings), false no error
		 */
		public function execute($pa_options=null) {
			global $AUTH_CURRENT_USER_ID;
			if (!is_array($this->opa_browse_settings)) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			if ((!isset($pa_options['omitChildRecords'])) && isset($pa_options['rootRecordsOnly'])) { 
			    $pa_options['omitChildRecords'] = $pa_options['rootRecordsOnly'];
			}
			
			if (is_array($omit_child_records_for_types = caGetOption('omitChildRecordsForTypes', $pa_options, null)) && sizeof($omit_child_records_for_types)) {
			    $omit_child_records_for_types = caMakeTypeIDList($this->opn_browse_table_num, $omit_child_records_for_types);
			    $pa_options['omitChildRecords'] = false;        // omitChildRecordsForTypes supercedes omitChildRecords
			}
			
			$vn_user_id = caGetOption('user_id', $pa_options, $AUTH_CURRENT_USER_ID, array('castTo' => 'int'));
			$t_user = new ca_users($vn_user_id);
			
			$vb_no_cache = caGetOption('noCache', $pa_options, caGetOption('no_cache', $pa_options, false, array('castTo' => 'bool')), array('castTo' => 'bool'));

			$va_params = $this->opo_ca_browse_cache->getParameters();

			$vb_need_to_cache_facets = false;
			$vb_results_cached = false;
			$vb_need_to_save_in_cache = false;

			$vs_cache_key = $this->opo_ca_browse_cache->getCurrentCacheKey();

			if ($this->opo_ca_browse_cache->load($vs_cache_key)) {
				$vn_created_on = $this->opo_ca_browse_cache->getParameter('created_on'); //$t_new_browse->get('created_on', array('getDirectDate' => true));
				$vn_cache_timeout = (int) $this->opo_ca_browse_config->get('cache_timeout');

				$va_criteria = $this->getCriteria();
				if (!$vb_no_cache && (intval(time() - $vn_created_on) < $vn_cache_timeout)) {
					$vb_results_cached = true;
					$this->opo_ca_browse_cache->setParameter('created_on', time() + $vn_cache_timeout);
					$vb_need_to_save_in_cache = true;

					Debug::msg("Cache hit for {$vs_cache_key}");
				} else {
					$va_criteria = $this->getCriteria();
					//$this->opo_ca_browse_cache->remove();
					//$this->opo_ca_browse_cache->setParameter('criteria', $va_criteria);

					$vb_need_to_save_in_cache = true;
					$vb_need_to_cache_facets = true;

					Debug::msg("Cache expire for {$vs_cache_key}");
				}
			} else {
				$va_criteria = $this->getCriteria();
				$vb_need_to_save_in_cache = true;

				Debug::msg("Cache miss for {$vs_cache_key}");
			}
			if (!$vb_results_cached) {
				$this->opo_ca_browse_cache->setParameter('sort', null);
				$this->opo_ca_browse_cache->setParameter('created_on', time());
				$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num);
				$vb_need_to_cache_facets = true;
			}
			$this->opb_criteria_have_changed = false;

			$t_item = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true);

			$va_results = array();

			if (is_array($va_criteria) && (sizeof($va_criteria) > 0)) {
				if (!$vb_results_cached) {

					$va_acc = $va_container_ids = [];
					$vn_i = 0;
					foreach($va_criteria as $vs_facet_name => $va_row_ids) {
						$vs_target_browse_table_name = $t_item->tableName();
						$vs_target_browse_table_num = $t_item->tableNum();
						$vs_target_browse_table_pk = $t_item->primaryKey();

						$va_facet_info = $this->getInfoForFacet($vs_facet_name);
						
						$vb_is_relative_to_parent = ($va_facet_info['relative_to'] && $this->_isParentRelative($va_facet_info['relative_to']));
						
						$va_row_ids = array_keys($va_row_ids);
                            
                        $va_sql_params = [];
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
									
									if (isset($va_facet_info['element_code']) && $t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name,  array_shift(explode('.', $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { 
                                        break; 
                                    } elseif((isset($va_facet_info['table']) && $t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name,  $va_facet_info['table']) < __CA_BUNDLE_ACCESS_READONLY__))) { 
                                        break; 
                                    }

									if ($va_facet_info['element_code']) {
										$t_element = new ca_metadata_elements();
										if (!$t_element->load(array('element_code' => array_pop(explode('.', $va_facet_info['element_code']))))) {
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

										$vn_table_num = Datamodel::getTableNum($vs_rel_table_name);
										$vs_rel_table_pk = Datamodel::primaryKey($vn_table_num);

											switch(sizeof($va_path = array_keys(Datamodel::getPath($vs_target_browse_table_name, $vs_rel_table_name)))) {
												case 3:
													$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
													$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
													$vs_key = 'relation_id';
													break;
												case 2:
													$t_item_rel = null;
													$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
													$vs_key = $t_rel_item->primaryKey();
													break;
												default:
													// bad related table
													return null;
													break;
											}
											
											if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
											if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item)))) { $va_restrict_to_types = []; }
											$va_restrict_to_types = array_filter($va_restrict_to_types, "strlen");
						
											if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
											if (!is_array($va_exclude_types = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item)))) { $va_exclude_types = []; }
											$va_exclude_types = array_filter($va_exclude_types, "strlen");


											$vs_cur_table = array_shift($va_path);

											$vn_state = array_pop($va_row_ids);

											foreach($va_path as $vs_join_table) {
												$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
												$va_joins[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
												$vs_cur_table = $vs_join_table;
											}


											$va_wheres = array();
											if (is_array($va_restrict_to_relationship_types) && (sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel) && (bool)$vn_state) {
												$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
											}
											if (is_array($va_exclude_relationship_types) && (sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel) && (bool)$vn_state) {
												$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
											}

															// yes option
											$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NOT NULL)";
											if ($t_rel_item->hasField('deleted')) {
												$va_wheres[] = "(".$t_rel_item->tableName().".deleted = 0)";
											}
											if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
												$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
											}

											if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && is_object($t_rel_item)) {
												$va_wheres[] = "(".$t_rel_item->tableName().".type_id IN (".join(',', $va_restrict_to_types)."))";
											}
											if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && is_object($t_rel_item)) {
												$va_wheres[] = "((".$t_rel_item->tableName().".type_id NOT IN (".join(',', $va_exclude_types).")) OR (".$t_rel_item->tableName().".type_id IS NULL))";
											}
										}

										if ($t_item->hasField('deleted')) {
											$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
										}

										if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
											$va_wheres[] = "(".$t_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
										}
										
											
                                        if ($t_item_rel && isset($va_facet_info['filter_on_interstitial']) && is_array($va_facet_info['filter_on_interstitial']) && sizeof($va_facet_info['filter_on_interstitial'])) {
                                            foreach($va_facet_info['filter_on_interstitial'] as $vs_field_name => $va_values) {
                                                if (!$va_values) { continue; }
                                                if (!is_array($va_values)) { $va_values = [$va_values]; }
                    
                                                if (!($vn_element_id = (int)ca_metadata_elements::getElementID($vs_field_name))) { continue; }
                                                if (!($o_value = Attribute::getValueInstance(ca_metadata_elements::getElementDatatype($vs_field_name)))) { continue; }
                    
                                                $va_element_value = $o_value->parseValue($va_values[0], array_merge(ca_metadata_elements::getElementSettingsForId($vs_field_name), ['list_id' => ca_metadata_elements::getElementListID($vs_field_name)], ['matchOn' => ['idno']]));
            
                                                $va_joins[] = "INNER JOIN ca_attributes c_a ON c_a.row_id = ".$t_item_rel->primaryKey(true)." AND c_a.table_num = ".$t_item_rel->tableNum();
                                                $va_joins[] = "INNER JOIN ca_attribute_values c_av ON c_a.attribute_id = c_av.attribute_id";
                                                $va_wheres[] = "c_av.element_id = {$vn_element_id} AND ".(isset($va_element_value['item_id']) ? "c_av.item_id = ?" : "c_av.value_longtext1 = ?");
                
                                                $va_sql_params[] = (isset($va_element_value['item_id'])) ? (int)$va_element_value['item_id'] : $va_element_value['value_longtext1'];
                                            }
                                        }

										$vs_join_sql = join("\n", $va_joins);
										$vs_where_sql = '';
										if (is_array($va_wheres) && sizeof($va_wheres) > 0) {
											$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);
										}

										if ($vn_state) {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												{$vs_where_sql}";

											$qr_res = $this->opo_db->query($vs_sql, $va_sql_params);
										} else {
											$this->_createTempTable("_browseTmp");
											$vs_sql = "
												INSERT IGNORE INTO _browseTmp
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												{$vs_where_sql}";

											$this->opo_db->query($vs_sql, $va_sql_params);
											
											$qr_res = $this->opo_db->query("SELECT t.".$t_item->primaryKey()." FROM ".$this->ops_browse_table_name." t LEFT JOIN _browseTmp AS b ON ".$t_item->primaryKey()." = b.row_id WHERE b.row_id IS NULL");
											
											$this->_dropTempTable("_browseTmp");
										}
										
										$va_acc[$vn_i] = $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey());
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

											$t_target = Datamodel::getInstanceByTableName($va_facet_info['relative_to'], true);
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

									$va_labels = $t_item->getPreferredDisplayLabelsForIDs($va_row_ids);

									foreach($va_row_ids as $vn_row_id) {
										if ($vn_i == 0) {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.".".$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													{$vs_label_table_name}.{$vs_label_display_field} = ?";
											//print "$vs_sql [".intval($this->opn_browse_table_num)."]<hr>";
											$qr_res = $this->opo_db->query($vs_sql, trim($va_labels[$vn_row_id]));
										} else {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.".".$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													{$vs_label_table_name}.{$vs_label_display_field} = ?";
											$qr_res = $this->opo_db->query($vs_sql, trim($va_labels[$vn_row_id]));

										}
										
										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
								# -----------------------------------------------------
								case 'field':
									$vs_field_name = $va_facet_info['field'];
									$vs_table_name = $this->ops_browse_table_name;
									
                                    // Map deaccession fields to deaccession bundle, which is used for access control on all
                                    $bundle_name = (in_array($vs_field_name, ['is_deaccessioned', 'deaccession_date', 'deaccession_notes', 'deaccession_type_id', 'deaccession_disposal_date'])) ? 'ca_objects_deaccession' : $vs_field_name;
                                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_table_name, $bundle_name) < __CA_BUNDLE_ACCESS_READONLY__)) { break; }
                    

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
										$vn_row_id = urldecode(str_replace('&#47;', '/', $vn_row_id));
										if ($vn_i == 0) {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";

											$qr_res = $this->opo_db->query($vs_sql, (string)$vn_row_id);
										} else {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";

											$qr_res = $this->opo_db->query($vs_sql, (string)$vn_row_id);

										}

										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
								# -----------------------------------------------------
								case 'attribute':
									$t_element = new ca_metadata_elements();
									$va_element_code = explode(".", $va_facet_info['element_code']);	
		
									if (!$t_element->load(array('element_code' => array_pop($va_element_code)))) { return [];}
								    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { break; }
					
									if ($t_user->getBundleAccessLevel($this->ops_browse_table_name, $va_facet_info['element_code']) < __CA_BUNDLE_ACCESS_READONLY__) { break; }
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
									
									if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
									$va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_item));


									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) Text or Number attribute
									// (do we support other types as well?)

									$vn_element_id = $t_element->getPrimaryKey();
									$o_attr = Attribute::getValueInstance($vn_datatype);
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										$vn_row_id = str_replace('&#47;', '/', $vn_row_id);
										
										if (in_array($vn_datatype, [__CA_ATTRIBUTE_VALUE_OBJECTS__, __CA_ATTRIBUTE_VALUE_OBJECTS__, __CA_ATTRIBUTE_VALUE_PLACES__, __CA_ATTRIBUTE_VALUE_OCCURRENCES__, __CA_ATTRIBUTE_VALUE_COLLECTIONS__, __CA_ATTRIBUTE_VALUE_LOANS__, __CA_ATTRIBUTE_VALUE_MOVEMENTS__, __CA_ATTRIBUTE_VALUE_MOVEMENTS__, __CA_ATTRIBUTE_VALUE_OBJECTLOTS__, __CA_ATTRIBUTE_VALUE_LIST__])) {
										    $va_value = $o_attr->parseValue($vn_row_id, $t_element->getFieldValuesArray());
										} else {
										    $va_value = ca_attribute_values::getValuesFor($vn_row_id);
										}
										$va_attr_sql = [];
										$va_attr_values = [intval($vs_target_browse_table_num), $vn_element_id];

										if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && method_exists($t_item, "getTypeList")) {
											$va_attr_sql[] = "(".$this->ops_browse_table_name.".type_id IN (".join(", ", $va_restrict_to_types)."))";
										}
										
										if (is_array($va_value)) {
											foreach($va_value as $vs_f => $vs_v) {
												switch($vn_datatype) {			
													case __CA_ATTRIBUTE_VALUE_LIST__:
														if ($vs_f != 'item_id') { continue; }

														// Include sub-items
														$t_list_item = new ca_list_items();
														$va_item_ids = $t_list_item->getHierarchy((int)$vs_v, array('idsOnly' => true, 'includeSelf' => true));

														$va_item_ids[] = (int)$vs_v;
														$va_attr_sql[] = "(ca_attribute_values.{$vs_f} IN (?))";
														$va_attr_values[] = array_map(function($v) { return (int)$v; }, array_unique($va_item_ids));
														break;
													default:
														$va_attr_sql[] = "(ca_attribute_values.{$vs_f} ".(is_null($vs_v) ? " IS " : " = ")." ?)";
														$va_attr_values[] = $vs_v;
														break;
													
												}
											}
										}

										if ($vs_attr_sql = join(" AND ", $va_attr_sql)) {
											$vs_attr_sql = " AND ".$vs_attr_sql;
										}
										
										$vs_container_sql = '';
										if (is_array($va_element_code) && (sizeof($va_element_code) == 1) && is_array($va_container_ids[$va_element_code[0]]) && sizeof($va_container_ids[$va_element_code[0]])) {
										    $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
										    $va_attr_values[] = $va_container_ids[$va_element_code[0]];
										}
										$vs_sql = "
											SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().", ca_attributes.attribute_id
											FROM ".$this->ops_browse_table_name."
											{$vs_relative_to_join}
											INNER JOIN ca_attributes ON ca_attributes.row_id = ".((!$vb_is_relative_to_parent ? "{$vs_target_browse_table_name}.{$vs_target_browse_table_pk}" : "parent.{$vs_target_browse_table_pk}"))." AND ca_attributes.table_num = ?
											INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
											WHERE
												(ca_attribute_values.element_id = ?) {$vs_attr_sql} {$vs_container_sql}";

										$qr_res = $this->opo_db->query($vs_sql, $va_attr_values);
										
										if (!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));
										
										if (is_array($va_element_code) && sizeof($va_element_code) == 1) {
										    // is sub-element in container
										    $va_container_ids[$va_element_code[0]] = array_unique($qr_res->getAllFieldValues('attribute_id'));
										    $this->opo_ca_browse_cache->setParameter('container_ids', $va_container_ids[$va_element_code[0]]);
										}
										if (!caGetOption('multiple', $va_facet_info, false)) {
											$vn_i++;
										}
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
								# -----------------------------------------------------
								case 'normalizedDates':
									$t_element = new ca_metadata_elements();
									$va_element_code = explode('.', $va_facet_info['element_code']);

									$vb_is_element = $vb_is_field = false;
									if (!($vb_is_element = $t_element->load(array('element_code' => array_pop($va_element_code)))) && !($vb_is_field = ($t_item->hasField($va_facet_info['element_code']) && ($t_item->getFieldInfo($va_facet_info['element_code'], 'FIELD_TYPE') === FT_HISTORIC_DATERANGE)))) {
										return array();
									}
									
								    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { break; }

									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) DateRange attribute

									$vs_normalization = $va_facet_info['normalization'];
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

									$vn_element_id = $vb_is_element ? $t_element->getPrimaryKey() : null;

									$vs_browse_start_fld = $vs_browse_start_fld = null;
									if ($vb_is_field) {
										$vs_browse_start_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'START');
										$vs_browse_end_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'END');
									}

									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);

										$va_dates = null;
										if ($vn_row_id !== 'null') {
											if (!$o_tep->parse($vn_row_id)) { continue; } // invalid date?
											$va_dates = $o_tep->getHistoricTimestamps();
										}
										
										if (
										    ($va_dates[0] <= (int)$va_dates[0] + 0.01010000000)
										    &&
										    ($va_facet_info['treat_before_dates_as_circa'] || $va_facet_info['treat_after_dates_as_circa'])
										) {
                                            $va_dates[0] = $va_dates['start'] = (int)$va_dates[0];
                                        }

										if ($vb_is_element) {
										    $vs_container_sql = '';
										    $va_attr_values = null;
                                            if (is_array($va_element_code) && (sizeof($va_element_code) == 1) && is_array($va_container_ids[$va_element_code[0]]) && sizeof($va_container_ids[$va_element_code[0]])) {
                                                $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
                                                $va_attr_values = $va_container_ids[$va_element_code[0]];
                                            }
                                            
											if (is_null($va_dates)) {
											    $va_params = [intval($vs_target_browse_table_num), $vn_element_id];
											    if ($va_attr_values) { $va_params[] = $va_attr_values; }
											    
												$vs_sql = "
													SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
													FROM ".$this->ops_browse_table_name."
													{$vs_relative_to_join}
													INNER JOIN ca_attributes ON ca_attributes.row_id = ".$vs_target_browse_table_name.'.'.$vs_target_browse_table_pk." AND ca_attributes.table_num = ?
													INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
													WHERE
														(ca_attribute_values.element_id = ?)
														AND
														(ca_attribute_values.value_decimal1 IS NULL)
														AND
														(ca_attribute_values.value_decimal2 IS NULL)
														{$vs_container_sql}
												";
												$qr_res = $this->opo_db->query($vs_sql, $va_params);
											} else {
											    $va_params = [intval($vs_target_browse_table_num), $vn_element_id, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']];
												if ($va_attr_values) { $va_params[] = $va_attr_values; }
												
												$vs_sql = "
													SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().", ca_attributes.attribute_id
													FROM ".$this->ops_browse_table_name."
													{$vs_relative_to_join}
													INNER JOIN ca_attributes ON ca_attributes.row_id = ".$vs_target_browse_table_name.'.'.$vs_target_browse_table_pk." AND ca_attributes.table_num = ?
													INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
													WHERE
														(ca_attribute_values.element_id = ?) AND

														(
															(
																(ca_attribute_values.value_decimal1 <= ?) AND
																(ca_attribute_values.value_decimal2 >= ?) AND
																(ca_attribute_values.value_decimal1 <> ".TEP_START_OF_UNIVERSE.".0000000000) AND
																(ca_attribute_values.value_decimal2 <> ".TEP_END_OF_UNIVERSE.".1231235959) 
															)
															OR
															(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
															OR
															(ca_attribute_values.value_decimal2 BETWEEN ? AND ?)
														)
														{$vs_container_sql}
												";
												$qr_res = $this->opo_db->query($vs_sql, $va_params);
											}
										} else {
											// is intrinsic
											if (is_null($va_dates)) {
												$vs_sql = "
													SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
													FROM ".$this->ops_browse_table_name."
													{$vs_relative_to_join}
													WHERE
														({$this->ops_browse_table_name}.{$vs_browse_start_fld} IS NULL)
														AND
														({$this->ops_browse_table_name}.{$vs_browse_end_fld} IS NULL)
												";
												$qr_res = $this->opo_db->query($vs_sql);
											} else {
												$vs_sql = "
													SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
													FROM ".$this->ops_browse_table_name."
													{$vs_relative_to_join}
													WHERE
														(
															(
																({$this->ops_browse_table_name}.{$vs_browse_start_fld} <= ?) AND
																({$this->ops_browse_table_name}.{$vs_browse_end_fld} >= ?) AND
																({$this->ops_browse_table_name}.{$vs_browse_start_fld} <> ".TEP_START_OF_UNIVERSE.") AND
																({$this->ops_browse_table_name}.{$vs_browse_end_fld} <> ".TEP_END_OF_UNIVERSE.") 
															)
															OR
															({$this->ops_browse_table_name}.{$vs_browse_start_fld} BETWEEN ? AND ?)
															OR
															({$this->ops_browse_table_name}.{$vs_browse_end_fld} BETWEEN ? AND ?)
														)
												";
												$qr_res = $this->opo_db->query($vs_sql, $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end'], $va_dates['start'], $va_dates['end']);
											}
										}

										if(!is_array($va_acc[$vn_i] )) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));
										if ($vb_is_element && is_array($va_element_code) && (sizeof($va_element_code) == 1)) {
										    // is sub-element in container
										    $va_container_ids[$va_element_code[0]] = array_unique($qr_res->getAllFieldValues('attribute_id'));
										    $this->opo_ca_browse_cache->setParameter('container_ids', $va_container_ids);
										}
										
										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
								# -----------------------------------------------------
								case 'normalizedLength':
									$t_element = new ca_metadata_elements();
									$va_element_code = explode('.', $va_facet_info['element_code']);

									$vb_is_element = $vb_is_field = false;
									if (!($vb_is_element = $t_element->load(array('element_code' => array_pop($va_element_code)))) && !($vb_is_field = ($t_item->hasField($va_facet_info['element_code']) && ($t_item->getFieldInfo($va_facet_info['element_code'], 'FIELD_TYPE') === FT_HISTORIC_DATERANGE)))) {
										return array();
									}
                                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { break; }

									// TODO: check that it is a *single-value* (ie. no hierarchical ca_metadata_elements) DateRange attribute

									$vs_normalization = $va_facet_info['normalization'];
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

									$vn_element_id = $vb_is_element ? $t_element->getPrimaryKey() : null;

									$vs_browse_start_fld = $vs_browse_start_fld = null;
									if ($vb_is_field) {
										$vs_browse_start_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'START');
										$vs_browse_end_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'END');
									}

									if (!($vs_output_units = caGetLengthUnitType($vs_units=caGetOption('units', $va_facet_info, 'm')))) {
										$vs_output_units = Zend_Measure_Length::METER;
									}
									$vs_increment = caGetOption('increment', $va_facet_info, '1 m');
									$vo_increment = caParseLengthDimension($vs_increment);
									$vn_increment_in_current_units = (float)$vo_increment->convertTo($vs_output_units, 6, 'en_US');

									foreach($va_row_ids as $vn_row_id) {
										$vn_start = urldecode($vn_row_id); // is start dimension

										// calculate end dimension
										$vn_end = $vn_start + $vn_increment_in_current_units;

										// convert to meters
										$vo_start = new Zend_Measure_Length($vn_start, $vs_output_units, 'en_US');
										$vo_end = new Zend_Measure_Length($vn_end, $vs_output_units, 'en_US');
										$vn_start_in_meters = (float)$vo_start->convertTo(Zend_Measure_Length::METER, 6, 'en_US');
										$vn_end_in_meters = (float)$vo_end->convertTo(Zend_Measure_Length::METER, 6, 'en_US');
										
										$va_params = [intval($vs_target_browse_table_num), $vn_element_id, $vn_start_in_meters, $vn_end_in_meters];
										$vs_container_sql = '';
                                        if (is_array($va_element_code) && (sizeof($va_element_code) == 1) && is_array($va_container_ids[$va_element_code[0]]) && sizeof($va_container_ids[$va_element_code[0]])) {
                                            $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
                                            $va_params[] = $va_container_ids[$va_element_code[0]];
                                        }

										$vs_sql = "
											SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey().", ca_attributes.attribute_id
											FROM ".$this->ops_browse_table_name."
											{$vs_relative_to_join}
											INNER JOIN ca_attributes ON ca_attributes.row_id = ".$vs_target_browse_table_name.'.'.$vs_target_browse_table_pk." AND ca_attributes.table_num = ?
											INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id
											WHERE
												(ca_attribute_values.element_id = ?) AND
												(ca_attribute_values.value_decimal1 BETWEEN ? AND ?)
                                                {$vs_container_sql}
										";
										$qr_res = $this->opo_db->query($vs_sql, $va_params);

										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));
										
										if ($vb_is_element && is_array($va_element_code) && (sizeof($va_element_code) == 1)) {
										    // is sub-element in container
										    $va_container_ids[$va_element_code[0]] = array_unique($qr_res->getAllFieldValues('attribute_id'));
										    $this->opo_ca_browse_cache->setParameter('container_ids', $va_container_ids);
										}
										
										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
								# -----------------------------------------------------
								case 'authority':
								case 'relationship_types':  
									$vs_rel_table_name = $va_facet_info['table'];
									
									if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name, $vs_rel_table_name) < __CA_BUNDLE_ACCESS_READONLY__)) {  break; }
					
									if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
									$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);

									if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }
									$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);
					
									$vn_table_num = Datamodel::getTableNum($vs_rel_table_name);
									$vs_rel_table_pk = Datamodel::primaryKey($vn_table_num);

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
										$va_sql_params = [];

										switch(sizeof($va_path = array_keys(Datamodel::getPath($vs_target_browse_table_name, $vs_rel_table_name)))) {
											case 3:
												$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
												$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
												$vs_key = 'relation_id';
												break;
											case 2:
												$t_item_rel = null;
												$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
												$vs_key = $t_rel_item->primaryKey();
												break;
											default:
												// bad related table
												return null;
												break;
										}

										$vs_cur_table = array_shift($va_path);
										$va_joins = array();
										$va_wheres = array();
										
										foreach($va_path as $vs_join_table) {
											$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
											$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
											$vs_cur_table = $vs_join_table;
										}
										
										if ($t_item_rel && isset($va_facet_info['filter_on_interstitial']) && is_array($va_facet_info['filter_on_interstitial']) && sizeof($va_facet_info['filter_on_interstitial'])) {
											foreach($va_facet_info['filter_on_interstitial'] as $vs_field_name => $va_values) {
												if (!$va_values) { continue; }
												if (!is_array($va_values)) { $va_values = [$va_values]; }
							
												if (!($vn_element_id = (int)ca_metadata_elements::getElementID($vs_field_name))) { continue; }
												if (!($o_value = Attribute::getValueInstance(ca_metadata_elements::getElementDatatype($vs_field_name)))) { continue; }
							
												$va_element_value = $o_value->parseValue($va_values[0], array_merge(ca_metadata_elements::getElementSettingsForId($vs_field_name), ['list_id' => ca_metadata_elements::getElementListID($vs_field_name)], ['matchOn' => ['idno']]));
					
												$va_joins[] = "INNER JOIN ca_attributes c_a ON c_a.row_id = ".$t_item_rel->primaryKey(true)." AND c_a.table_num = ".$t_item_rel->tableNum();
												$va_joins[] = "INNER JOIN ca_attribute_values c_av ON c_a.attribute_id = c_av.attribute_id";
												$va_wheres[] = "c_av.element_id = {$vn_element_id} AND ".(isset($va_element_value['item_id']) ? "c_av.item_id = ?" : "c_av.value_longtext1 = ?");
						
												$va_sql_params[] = (isset($va_element_value['item_id'])) ? (int)$va_element_value['item_id'] : $va_element_value['value_longtext1'];
											}
										}

										$vs_join_sql = join("\n", $va_joins);

										if (is_array($va_restrict_to_relationship_types) && (sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
										}
										if (is_array($va_exclude_relationship_types) && (sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
											$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
										}

										$vs_where_sql = '';
										if (is_array($va_wheres) && sizeof($va_wheres) > 0) {
											$vs_where_sql = ' AND '.join(' AND ', $va_wheres);
										}

                                        if ($va_facet_info['type'] == 'relationship_types') {
                                            $vs_get_item_sql = "(".$t_item_rel->tableName().".type_id = ".(int)$vn_row_id.")";
										} elseif ((!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) && $t_rel_item->isHierarchical() && $t_rel_item->load((int)$vn_row_id)) {
											$vs_hier_left_fld = $t_rel_item->getProperty('HIERARCHY_LEFT_INDEX_FLD');
											$vs_hier_right_fld = $t_rel_item->getProperty('HIERARCHY_RIGHT_INDEX_FLD');

											$vs_get_item_sql = "{$vs_rel_table_name}.{$vs_hier_left_fld} >= ".$t_rel_item->get($vs_hier_left_fld). " AND {$vs_rel_table_name}.{$vs_hier_right_fld} <= ".$t_rel_item->get($vs_hier_right_fld);
											if ($vn_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
												$vs_get_item_sql .= " AND {$vs_rel_table_name}.{$vn_hier_id_fld} = ".(int)$t_rel_item->get($vn_hier_id_fld);
											}
											
											if ($t_rel_item->hasField('deleted')) { 
												$vs_get_item_sql .= " AND ({$vs_rel_table_name}.deleted = 0)";
											}
											
											$vs_get_item_sql = "({$vs_get_item_sql})";
										} else {
											$vs_get_item_sql = "({$vs_rel_table_name}.{$vs_rel_table_pk} = ".(int)$vn_row_id.")";
										}

										if ($vn_i == 0) {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";

											$qr_res = $this->opo_db->query($vs_sql, $va_sql_params);
										} else {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												{$vs_join_sql}
												WHERE
													{$vs_get_item_sql}
													{$vs_where_sql}";

											$qr_res = $this->opo_db->query($vs_sql, $va_sql_params);

										}

										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
								break;
							# -----------------------------------------------------
								case 'location':
								    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($this->ops_browse_table_name, 'ca_objects_location') < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
									foreach($va_row_ids as $vn_row_id) {
										$vn_row_id = urldecode($vn_row_id);
										$va_row_tmp = explode(":", $vn_row_id);
										
										if (
											(sizeof($va_row_tmp) < 3)
											&&
											($object_location_tracking_relationship_type = $this->opo_config->get('object_storage_location_tracking_relationship_type'))
											&&
											($object_location_tracking_relationship_type_id = array_shift(caMakeRelationshipTypeIDList('ca_objects_x_storage_locations', [$object_location_tracking_relationship_type])))
										) {
											//
											// Hierarchical display of current location facets is only available when pure storage location tracking (ie. only 
											// locations, not loans, occurrences etc.) is configured. The value of location criteria is typically in the 
											// form <table num>:<type id>:<row id> but is shortened to just the row_id (which is the storage location location_id)
											// by the hierarchy browser. In this case we can assume the table number is 119 (ca_objects_x_storage_locations) and
											// the type_id is whatever is configured in "object_storage_location_tracking_relationship_type" in app.conf.
											//
											// We prepend those values below, allowing the criterion value to behave as a standard location value/
											//	
											array_unshift($va_row_tmp, $object_location_tracking_relationship_type_id); 
											if (sizeof($va_row_tmp) < 3) { array_unshift($va_row_tmp, 119); }	// ca_objects_x_storage_locations
										}
										
										if ($va_row_tmp[0] == 119) { // ca_objects_x_storage_locations
											$t_loc = new ca_storage_locations();
											
											if (!is_array($va_loc_ids = $t_loc->getHierarchy($va_row_tmp[2], ['returnAsArray' => true, 'includeSelf' => true, 'idsOnly' => true])) || !sizeof($va_loc_ids)) { continue; }
											
											array_pop($va_row_tmp);
											$va_row_tmp[] = array_values($va_loc_ids);
											
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												WHERE
													({$this->ops_browse_table_name}.current_loc_class = ?)"
														.((sizeof($va_row_tmp) == 2) ? " AND ({$this->ops_browse_table_name}.current_loc_id IN (?))" : "")
														.((sizeof($va_row_tmp) > 2) ? " AND ({$this->ops_browse_table_name}.current_loc_subclass = ?) AND ({$this->ops_browse_table_name}.current_loc_id IN (?))" : "");
												
											$qr_res = $this->opo_db->query($vs_sql, $va_row_tmp);
										} else {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												WHERE
													({$this->ops_browse_table_name}.current_loc_class = ?)"
														.((sizeof($va_row_tmp) > 1) ? " AND ({$this->ops_browse_table_name}.current_loc_subclass = ?)" : "")
														.((sizeof($va_row_tmp) > 2) ? " AND ({$this->ops_browse_table_name}.current_loc_id = ?)" : "");
											$qr_res = $this->opo_db->query($vs_sql, $va_row_tmp);
										}
										
										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;
							# -----------------------------------------------------
								case 'fieldList':
									$vs_field_name = $va_facet_info['field'];
									$vs_table_name = $this->ops_browse_table_name;
									
									// Map deaccession fields to deaccession bundle, which is used for access control on all
                                    $bundle_name = (in_array($vs_field_name, ['deaccession_type_id'])) ? 'ca_objects_deaccession' : $vs_field_name;
                                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_table_name, $bundle_name) < __CA_BUNDLE_ACCESS_READONLY__)) { break; }
                    

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
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";

											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
										} else {
											$vs_sql = "
												SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
												FROM ".$this->ops_browse_table_name."
												{$vs_relative_to_join}
												WHERE
													({$vs_table_name}.{$vs_field_name} = ?)";

											$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);

										}
										
										if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
										$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

										if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									}
									if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
									break;

							# -----------------------------------------------------
							case 'violations':
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
											SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
											FROM ".$this->ops_browse_table_name."
											INNER JOIN ca_metadata_dictionary_rule_violations ON ca_metadata_dictionary_rule_violations.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_metadata_dictionary_rule_violations.table_num = ".$t_item->tableNum()."
											INNER JOIN ca_metadata_dictionary_rules ON ca_metadata_dictionary_rules.rule_id = ca_metadata_dictionary_rule_violations.rule_id
											{$vs_relative_to_join}
											WHERE
												(ca_metadata_dictionary_rules.rule_code = ?)";

										$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);
									} else {
										$vs_sql = "
											SELECT ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()."
											FROM ".$this->ops_browse_table_name."
											INNER JOIN ca_metadata_dictionary_rule_violations ON ca_metadata_dictionary_rule_violations.row_id = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey()." AND ca_metadata_dictionary_rule_violations.table_num = ".$t_item->tableNum()."
											INNER JOIN ca_metadata_dictionary_rules ON ca_metadata_dictionary_rules.rule_id = ca_metadata_dictionary_rule_violations.rule_id
											{$vs_relative_to_join}
											WHERE
												(ca_metadata_dictionary_rules.rule_code = ?)";

										$qr_res = $this->opo_db->query($vs_sql, $vn_row_id);

									}
									
									if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
									$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($this->ops_browse_table_name.'.'.$t_item->primaryKey()));

									if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
								}
								if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
								break;
							# -----------------------------------------------------
							case 'checkouts':
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

								$vs_where = null;
								$vn_current_time = time();

								foreach($va_row_ids as $vn_row_id) {
									$vs_checkout_join_sql = "INNER JOIN ca_object_checkouts ON ca_object_checkouts.object_id = ca_objects.object_id";

									$vs_status_code = (isset($va_facet_info['status']) && $va_facet_info['status']) ? $va_facet_info['status'] : $vn_row_id;
									switch($vs_status_code) {
										case 'overdue':
											$vs_where = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL) AND (ca_object_checkouts.due_date <= {$vn_current_time}))";
											break;
										case 'reserved':
											$vs_where = "((ca_object_checkouts.checkout_date IS NULL) AND (ca_object_checkouts.return_date IS NULL))";
											break;
										case 'available':
											$vs_checkout_join_sql = '';
											$vs_where = "(ca_objects.object_id NOT IN (SELECT object_id FROM ca_object_checkouts WHERE (ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL)))";
											break;
										case 'all':
											$vs_where = "(ca_object_checkouts.checkout_date <= {$vn_current_time})";
											break;
										default:
										case 'out':
											$vs_where = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL))";
											break;
									}

									$vs_sql = "
										SELECT ca_objects.object_id
										FROM ca_objects
										{$vs_checkout_join_sql}
										WHERE
											{$vs_where}
									";

									$vs_user_sql = null;
									$va_params = array();
									switch($va_facet_info['mode']) {
										case 'user':
											foreach($va_row_ids as $vn_index => $vn_row_id) {
												$va_row_ids[$vn_index] = (int)$vn_row_id;
											}
											$va_params[] = $va_row_ids;
											$vs_user_sql .= " AND (ca_object_checkouts.user_id IN (?))";
											break;
										case 'all':
										default:
											// noop
											break;
									}

									$qr_res = $this->opo_db->query($vs_sql.$vs_user_sql, $va_params);
									
									if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
									$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues('ca_objects.object_id'));

									if (!caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
								}
								if (caGetOption('multiple', $va_facet_info, false)) { $vn_i++; }
								break;
							# -----------------------------------------------------
							case 'dupeidno':
								//select count(*) c, idno from ca_objects where deleted = 0 group by idno having c > 1;^C
								$vs_browse_table_name = $this->ops_browse_table_name;
								if (!($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true))) { break; }
								$idno_fld = $t_item->getProperty('ID_NUMBERING_ID_FIELD');

								$va_wheres = [];
								
								if ($va_facet_info['relative_to']) {
									if ($va_relative_execute_sql_data = $this->_getRelativeExecuteSQLData($va_facet_info['relative_to'], $pa_options)) {
										$va_relative_to_join = $va_relative_execute_sql_data['relative_joins'];
										$vs_relative_to_join = join("\n", $va_relative_to_join);
										$vs_table_name = $vs_target_browse_table_name = $va_relative_execute_sql_data['target_table_name'];
										$vs_target_browse_table_num = $va_relative_execute_sql_data['target_table_num'];
										$vs_target_browse_table_pk = $va_relative_execute_sql_data['target_table_pk'];
									}
								}
								
								if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
								if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item)))) { $va_restrict_to_types = []; }
								$va_restrict_to_types = array_filter($va_restrict_to_types, "strlen");
			
								if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
								if (!is_array($va_exclude_types = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item)))) { $va_exclude_types = []; }
								$va_exclude_types = array_filter($va_exclude_types, "strlen");

								if ($t_item->hasField('deleted')) { 
									$va_wheres[] = "{$vs_browse_table_name}.deleted = 0";
								}
								if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)) {
									$va_wheres[] = "{$va_restrict_to_types}.type_id IN (".join(",", $va_restrict_to_types).")";
								}
								if (is_array($va_exclude_types) && sizeof($va_exclude_types)) {
									$va_wheres[] = "{$va_restrict_to_types}.type_id IN (".join(",", $va_exclude_types).")";
								}

								$vs_sql = "
									SELECT count(*) c, {$vs_browse_table_name}.{$idno_fld}
									FROM {$vs_browse_table_name}
									{$vs_relative_to_join}
									".((sizeof($va_wheres) > 0) ? " WHERE ".join(" AND ", $va_wheres) : "")."
									GROUP BY {$vs_browse_table_name}.{$idno_fld}
									HAVING c IN (?)
									";

								$qr_res = $this->opo_db->query($vs_sql, [$va_row_ids]);
							
								$va_wheres[] = "{$vs_browse_table_name}.{$idno_fld} IN (?)";
								$vs_sql = "
										SELECT {$vs_browse_table_name}.".$t_item->primaryKey()."
										FROM {$vs_browse_table_name}
										{$vs_relative_to_join}
										".((sizeof($va_wheres) > 0) ? " WHERE ".join(" AND ", $va_wheres) : "")."
										";

								$qr_res = $this->opo_db->query($vs_sql, [$qr_res->getAllFieldValues($idno_fld)]);
								
								if(!is_array($va_acc[$vn_i])) { $va_acc[$vn_i] = []; }
								$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($vs_browse_table_name.'.'.$t_item->primaryKey()));

								$vn_i++;
								break;
							# -----------------------------------------------------
							default:
								switch($vs_facet_name) {
									case '_search':
										// handle "search" criteria - search engine queries that can be browsed
										if (!($o_search = caGetSearchInstance($this->ops_browse_table_name))) {
											$this->postError(2900, _t("Invalid search type"), "BrowseEngine->execute()");
											break;
										}

										if (is_array($va_type_ids = $this->getTypeRestrictionList()) && sizeof($va_type_ids)) {
											$o_search->setTypeRestrictions($va_type_ids);
										}
										if (is_array($va_source_ids = $this->getSourceRestrictionList()) && sizeof($va_source_ids)) {
											$o_search->setSourceRestrictions($va_source_ids);
										}
										$va_options = $pa_options;
										unset($va_options['sort']);					// browse engine takes care of sort so there is no reason to waste time having the search engine do so
										$va_options['filterNonPrimaryRepresentations'] = true;	// filter out non-primary representations in ca_objects results to save (a bit) of time

										$o_search->setOption('strictPhraseSearching', caGetOption('strictPhraseSearching', $va_options, true));
										
										if (is_array($va_row_ids) && sizeof($va_row_ids) > 1) {
											// only allow singleton wildcards without other searches, otherwise we're wasting our time
											$va_row_ids = array_filter($va_row_ids, function($a) { return !($a === '*'); });
										}
										$qr_res = $o_search->search(join(" AND ", $va_row_ids), $va_options);

										$va_acc[$vn_i] = $qr_res->getPrimaryKeyValues();
										$vn_i++;
										break;
									case '_reltypes':
										$va_acc[$vn_i] = [];
										foreach($va_row_ids as $vs_tmp) {
											$va_tmp = explode(":", $vs_tmp);
											if (!($t_target = Datamodel::getInstanceByTableName($va_tmp[0], true))) { break; }
											
											$va_path_to_target = array_keys(Datamodel::getPath($vs_target_browse_table_name, $t_target->tableName()));
											
											if (is_array($va_path_to_target) && (sizeof($va_path_to_target) == 3)) {
												if (!($t_target_rel = Datamodel::getInstanceByTableName($va_path_to_target[1], true)) || !$t_target_rel->isRelationship() || !$t_target_rel->hasField('type_id')) { break; }
												$va_ids_from_rel = array_unique($this->_getRelationshipTypeIDs(explode(",", $va_tmp[1]), $va_path_to_target[1]));
												if (is_array($va_ids_from_rel) && (sizeof($va_ids_from_rel) > 0)) {
													$qr_res = $this->opo_db->query("SELECT DISTINCT {$vs_target_browse_table_pk} FROM ".$va_path_to_target[1]." WHERE type_id IN (?)", [$va_ids_from_rel]);
													$va_acc[$vn_i] = array_merge($va_acc[$vn_i], $qr_res->getAllFieldValues($vs_target_browse_table_pk));
												}
											}
										}
										$vn_i++;
										break;
									default:
										$this->postError(2900, _t("Invalid criteria type"), "BrowseEngine->execute()");
										break;
								}
								break;
							# -----------------------------------------------------
						}
					}

					// preserve sort order using first facet if first facet is a search
					// (needed for relevance sort)
					$vb_preserve_order = (bool)(array_keys($va_criteria)[0]== '_search');
					
					foreach($va_acc as $vn_i => $va_hits) {
						$va_acc[$vn_i] = array_flip($va_hits);
					}
					$vn_smallest_list_index = null;
					foreach($va_acc as $vn_i => $va_hits) {
						if (is_null($vn_smallest_list_index)) { $vn_smallest_list_index = $vn_i; continue; }
						if (sizeof($va_hits) < sizeof($va_acc[$vn_smallest_list_index])) { $vn_smallest_list_index = $vn_i; }
					}
					
					if ((!isset($pa_options['omitChildRecords']) || !$pa_options['omitChildRecords']) && caGetOption('expandResultsHierarchically', $pa_options, false) && ($vs_hier_id_fld = Datamodel::getTableProperty($this->ops_browse_table_name, 'HIERARCHY_ID_FLD'))) {
                       $vs_parent_id_fld = Datamodel::getTableProperty($this->ops_browse_table_name, 'PARENT_ID_FLD');
                       $omit_child_records_for_type_sql = '';
                       
                       foreach($va_acc as $vn_i => $va_acc_content) {
                            $params = [array_keys($va_acc_content)];

                            if(is_array($omit_child_records_for_types) && sizeof($omit_child_records_for_types)) {
                                $omit_child_records_for_type_sql = "AND (({$vs_parent_id_fld} IS NULL) OR ({$vs_parent_id_fld} IS NOT NULL AND type_id NOT IN (?)))";
                                $params[] = $omit_child_records_for_types;
                            }
                            if(!sizeof($va_acc_content)) { continue; }
                            $qr_expand =  $this->opo_db->query("
                                SELECT ".$this->ops_browse_table_name.".".$t_item->primaryKey()." 
                                FROM ".$this->ops_browse_table_name."
                                WHERE
                                    {$vs_hier_id_fld} IN (?)
                                    {$omit_child_records_for_type_sql}
                            ",$params);

                            if(is_array($va_expanded_res = $qr_expand->getAllFieldValues($t_item->primaryKey())) && sizeof($va_expanded_res)) {
                                $va_acc[$vn_i] = array_flip($va_expanded_res);
                            }
                        }
                    }

					$va_res = array();
					$va_acc_indices = array_keys($va_acc);
					if(is_array($va_acc[$vn_smallest_list_index])) {
						foreach($va_acc[$vn_smallest_list_index] as $vn_row_id => $vb_dummy) {
							foreach($va_acc_indices as $vn_i) {
								if ($vn_i == $vn_smallest_list_index) { continue; }
								if (!isset($va_acc[$vn_i][$vn_row_id])) { continue(2); }
							}
							$va_res[$vn_row_id] = true;
						}
					}
					
					if ($vb_preserve_order) {
						$va_tmp = [];
						foreach(array_keys($va_acc[0]) as $vn_x) {
							if (isset($va_res[$vn_x])) { $va_tmp[] = $vn_x; }
						}
						$va_res = array_flip($va_tmp);
					}
					
					if (sizeof($va_res)) {
						$vs_filter_join_sql = $vs_filter_where_sql = '';
						$va_wheres = array();
						$va_joins = array();
						$vs_sql_distinct = '';

						if (sizeof($this->opa_result_filters)) {
							$va_tmp = array();
							foreach($this->opa_result_filters as $va_filter) {
								$vm_val = $this->_filterValueToQueryValue($va_filter);

								$va_wheres[] = $this->ops_browse_table_name.'.'.$va_filter['field']." ".$va_filter['operator']." ".$vm_val;
							}

						}
						if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
							$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
						}

						if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_item->hasField('deleted')) {
							$va_wheres[] = "(".$this->ops_browse_table_name.".deleted = 0)";
						}
						
						if ((isset($pa_options['omitChildRecords']) && $pa_options['omitChildRecords']) && $t_item->hasField('parent_id')) {
							$va_wheres[] = "(".$this->ops_browse_table_name.".parent_id IS NULL)";
						}
						
						if (is_array($omit_child_records_for_types) && sizeof($omit_child_records_for_types) && $t_item->hasField('parent_id')) {
							$va_wheres[] = "(({$this->ops_browse_table_name}.parent_id IS NULL) OR ({$this->ops_browse_table_name}.parent_id IS NOT NULL AND {$this->ops_browse_table_name}.type_id NOT IN (".join(",", $omit_child_records_for_types).")))";
						}


						if ((isset($pa_options['limitToModifiedOn']) && $pa_options['limitToModifiedOn'])) {
							$o_tep = new TimeExpressionParser();
							if ($o_tep->parse($pa_options['limitToModifiedOn'])) {
								$va_range = $o_tep->getUnixTimestamps();

								$va_joins['ca_change_log_subjects'] = "INNER JOIN ca_change_log_subjects ON ca_change_log_subjects.subject_row_id = ".$this->ops_browse_table_name.".".$t_item->primaryKey()." AND ca_change_log_subjects.subject_table_num = ".$t_item->tableNum();
								$va_joins['ca_change_log'] = "INNER JOIN ca_change_log ON ca_change_log.log_id = ca_change_log_subjects.log_id";

								$va_wheres[] = "(((ca_change_log.log_datetime BETWEEN ".(int)$va_range['start']." AND ".(int)$va_range['end'].") AND (ca_change_log.changetype IN ('I', 'U', 'D'))))";

								$vs_sql_distinct = 'DISTINCT';	// need to pull distinct rows since joining the change log can cause dupes
							}
						}

						if (($va_browse_type_ids = $this->getTypeRestrictionList()) && is_array($va_browse_type_ids) && sizeof($va_browse_type_ids)) {
							$t_subject = $this->getSubjectInstance();
							$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).')'.($t_subject->getFieldInfo('type_id', 'IS_NULL') ? " OR (".$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName()." IS NULL)" : '').')';
						}

						if (($va_browse_source_ids = $this->getSourceRestrictionList()) && is_array($va_browse_source_ids) && sizeof($va_browse_source_ids)) {
							$t_subject = $this->getSubjectInstance();
							$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getSourceFieldName().' IN ('.join(', ', $va_browse_source_ids).') OR ('.$this->ops_browse_table_name.'.'.$t_subject->getSourceFieldName().' IS NULL))';
						}

						$vs_filter_where_sql = "WHERE (".$this->ops_browse_table_name.".".$t_item->primaryKey()." IN (?)) ";
						if (is_array($va_wheres) && sizeof($va_wheres)) {
							$vs_filter_where_sql .= ' AND ('.join(' AND ', $va_wheres).')';
						}
						if (is_array($va_joins) && sizeof($va_joins)) {
							$vs_filter_join_sql = join("\n", $va_joins);
						}

						$qr_res = $this->opo_db->query("
							SELECT {$vs_sql_distinct} ".$this->ops_browse_table_name.".".$t_item->primaryKey()."
							FROM ".$this->ops_browse_table_name."
							{$vs_filter_join_sql}
							{$vs_filter_where_sql}
						", array($va_possible_values = array_keys($va_res)));

						$va_results = $vb_preserve_order ? array_intersect($va_possible_values, $qr_res->getAllFieldValues($t_item->primaryKey())) : $qr_res->getAllFieldValues($t_item->primaryKey());

						if ((!isset($pa_options['dontFilterByACL']) || !$pa_options['dontFilterByACL']) && $this->opo_config->get('perform_item_level_access_checking') && method_exists($t_item, "supportsACL") && $t_item->supportsACL()) {
							$va_results = $this->filterHitsByACL($va_results, $this->opn_browse_table_num, $vn_user_id, __CA_ACL_READONLY_ACCESS__);
						}

						$this->opo_ca_browse_cache->setResults(array_values($va_results));
						$vb_need_to_save_in_cache = true;
					} else {
						// No results for some reason - we're here because we don't want to throw a SQL error
						$this->opo_ca_browse_cache->setResults($va_results = array());
						$vb_need_to_save_in_cache = true;
					}
				}
			} else {
				// no criteria - don't try to find anything unless configured to do so
				$va_settings = $this->opo_ca_browse_config->getAssoc($this->ops_browse_table_name);
				if (
					(isset($va_settings['show_all_for_no_criteria_browse']) && $va_settings['show_all_for_no_criteria_browse'])
					||
					(isset($pa_options['showAllForNoCriteriaBrowse']) && $pa_options['showAllForNoCriteriaBrowse'])
				) {
					$va_wheres = $va_joins = array();
					$vs_pk = $t_item->primaryKey();

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ((!isset($pa_options['showDeleted']) || !$pa_options['showDeleted']) && $t_item->hasField('deleted')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".deleted = 0)";
					}
					if ((isset($pa_options['omitChildRecords']) && $pa_options['omitChildRecords']) && $t_item->hasField('parent_id')) {
						$va_wheres[] = "(".$this->ops_browse_table_name.".parent_id IS NULL)";
					}					
        
                    if (is_array($omit_child_records_for_types) && sizeof($omit_child_records_for_types) && $t_item->hasField('parent_id')) {
                        $va_wheres[] = "(({$this->ops_browse_table_name}.parent_id IS NULL) OR ({$this->ops_browse_table_name}.parent_id IS NOT NULL AND {$this->ops_browse_table_name}.type_id NOT IN (".join(",", $omit_child_records_for_types).")))";
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

					$va_browse_type_ids = $this->getTypeRestrictionList();
					$va_browse_source_ids = $this->getSourceRestrictionList();

					if (
						(is_array($va_browse_type_ids) && sizeof($va_browse_type_ids))
						||
						(is_array($va_browse_source_ids) && sizeof($va_browse_source_ids))
					) {
						$t_subject = $this->getSubjectInstance();

						if (is_array($va_browse_type_ids) && sizeof($va_browse_type_ids)) {
							$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).')'.($t_subject->getFieldInfo('type_id', 'IS_NULL') ? " OR (".$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName()." IS NULL)" : '').')';
						}
						if (is_array($va_browse_source_ids) && sizeof($va_browse_source_ids)) {
							$va_wheres[] = '('.$this->ops_browse_table_name.'.'.$t_subject->getSourceFieldName().' IN ('.join(', ', $va_browse_source_ids).') OR ('.$this->ops_browse_table_name.'.'.$t_subject->getSourceFieldName().' IS NULL))';
						}
					}


					if (is_array($va_wheres) && sizeof($va_wheres)) {
						$vs_filter_where_sql = 'WHERE '.join(' AND ', $va_wheres);
					}
					if (is_array($va_joins) && sizeof($va_joins)) {
						$vs_filter_join_sql = join("\n", $va_joins);
					}

					$qr_res = $this->opo_db->query("
						SELECT {$vs_pk}
						FROM ".$t_item->tableName()."
						{$vs_filter_join_sql}
						{$vs_filter_where_sql}
						ORDER BY
							{$vs_pk}
					");
					$va_results = $qr_res->getAllFieldValues($vs_pk);

					if ((!isset($pa_options['dontFilterByACL']) || !$pa_options['dontFilterByACL']) && $this->opo_config->get('perform_item_level_access_checking') && method_exists($t_item, "supportsACL") && $t_item->supportsACL()) {
						$va_results = array_keys($this->filterHitsByACL($va_results, $this->opn_browse_table_num, $vn_user_id, __CA_ACL_READONLY_ACCESS__));
					}
					$this->opo_ca_browse_cache->setResults($va_results);
				} else {
					$this->opo_ca_browse_cache->setResults(array());
				}
				$vb_need_to_save_in_cache = true;
			}

			if ($vb_need_to_cache_facets && !$pa_options['dontCheckFacetAvailability']) {
				$this->loadFacetContent($pa_options);
			}
			if ($vb_need_to_save_in_cache) {
				$this->opo_ca_browse_cache->save();
			}
			return true;
		}
		# ------------------------------------------------------
		/**
		 * Generates content for all browse facets for the current browse. Typically called by
		 * BrowseEngine::execute() after the browse results are calculated to update the facets.
		 *
		 * @param array $pa_options Options are the same as for BrowseEngine::getFacetContent()
		 * @return bool Always returns true
		 */
		public function loadFacetContent($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_facets_with_content = array();
			$o_results = $this->getResults();
			if (!is_array($va_criteria = $this->getCriteria())) { $va_criteria = []; }

			if (($o_results->numHits() > 1) || !sizeof($va_criteria)) {
				$va_facets = $this->getFacetList();
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
					$va_facet_info = $this->getInfoForFacet($vs_facet_name);
					if (
						(isset($va_criteria[$vs_facet_name]) && isset($va_facet_info['multiple']) && $va_facet_info['multiple']) // facets supporting multiple selection always have content
						|| 
						$this->getFacet($vs_facet_name, array_merge($pa_options, array('checkAvailabilityOnly' => true)))
					) {
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
			$this->opo_ca_browse_cache->save();

			return true;
		}
		# ------------------------------------------------------
		# Get facet
		# ------------------------------------------------------
		/**
		 * Return list of items from the specified facet that are related to the current browse set
		 *
		 * Options:
		 *		checkAccess = array of access values to filter facets that have an 'access' field by
		 */
		public function getFacet($ps_facet_name, $pa_options=null) {
			if (!is_array($this->opa_browse_settings)) { return null; }

			$pn_start = caGetOption('start', $pa_options, 0);
			$pn_limit = caGetOption('limit', $pa_options, null);

			$va_facet_cache = $this->opo_ca_browse_cache->getFacet($ps_facet_name);

			// is facet cached?
			$va_facet_content = null;
			if (!isset($va_facet_cache) || !is_array($va_facet_cache)) {
				$va_facet_content = $va_facet_cache = $this->getFacetContent($ps_facet_name, $pa_options);
				$vb_needs_caching = true;
			}

			if ($pn_limit > 0) {
				$va_facet_cache = array_slice($va_facet_cache, (int)$pn_start, $pn_limit);
			} elseif ($pn_start > 0) {
				$va_facet_cache = array_slice($va_facet_cache, (int)$pn_start);
			}

			if($vb_needs_caching) {
				if(!is_array($va_facet_content)) { $va_facet_content = array(); }
				$this->opo_ca_browse_cache->setFacet($ps_facet_name, $va_facet_content);
				$this->opo_ca_browse_cache->save();
			}
			return $va_facet_cache;
		}
		# ------------------------------------------------------
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

			$t_model = Datamodel::getInstance($va_facet_info['table']);
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
									if (is_array($va_item)) { $va_groups[] = mb_substr($va_item[$va_label_order_by_fields[0]], 0, 1);}
								}
								break;
						}

						foreach($va_groups as $vs_group) {
							$vs_group = caUcFirstUTF8Safe($vs_group);
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
				$va_facet_cache[$ps_facet_name] = $this->getFacet($ps_facet_name, $pa_options);
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
		 * Return list of items from the specified table that are related to the current browse set. This is the method that actually
		 * pulls the facet content, regardless of whether the facet is cached yet or not. If you want to use the facet cache, call
		 * BrowseEngine::getFacet()
		 *
		 * @see BrowseEngine::getFacet()

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

			if (!is_array($va_criteria = $this->getCriteria($ps_facet_name))) { $va_criteria = []; }

			$va_facet_info = $this->opa_browse_settings['facets'][$ps_facet_name];

			$t_subject = $this->getSubjectInstance();

			$vb_is_relative_to_parent = false;
			if ($va_facet_info['relative_to']) {
				if ($this->_isParentRelative($va_facet_info['relative_to'])) {
					$vb_is_relative_to_parent = true;
				} else {
					$vs_browse_table_name = $va_facet_info['relative_to'];
				}
				$vs_browse_table_num = Datamodel::getTableNum($vs_browse_table_name);
			}

			$vs_browse_type_limit_sql = '';
			if (($va_browse_type_ids = $this->getTypeRestrictionList()) && is_array($va_browse_type_ids) && sizeof($va_browse_type_ids)) {		// type restrictions
				$vs_browse_type_limit_sql = '('.$t_subject->tableName().'.'.$t_subject->getTypeFieldName().' IN ('.join(', ', $va_browse_type_ids).')'.($t_subject->getFieldInfo('type_id', 'IS_NULL') ? " OR (".$this->ops_browse_table_name.'.'.$t_subject->getTypeFieldName()." IS NULL)" : '').')';

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

			$vs_browse_source_limit_sql = '';
			if (($va_browse_source_ids = $this->getSourceRestrictionList()) && is_array($va_browse_source_ids) && sizeof($va_browse_source_ids)) {		// source restrictions
				$vs_browse_source_limit_sql = '('.$t_subject->tableName().'.'.$t_subject->getSourceFieldName().' IN ('.join(', ', $va_browse_source_ids).')'.($t_subject->getFieldInfo('source_id', 'IS_NULL') ? " OR (".$this->ops_browse_table_name.'.'.$t_subject->getSourceFieldName()." IS NULL)" : '').')';

				if (is_array($va_facet_info['source_restrictions'])) { 		// facet source restrictions bind a facet to specific sources; we check them here
					$va_restrict_to_sources = $this->_convertSourceCodesToIDs($va_facet_info['source_restrictions']);
					$vb_is_ok_to_browse = false;
					foreach($va_browse_source_ids as $vn_source_id) {
						if (in_array($vn_source_id, $va_restrict_to_sources)) {
							$vb_is_ok_to_browse = true;
							break;
						}
					}

					if (!$vb_is_ok_to_browse) { return array(); }
				}
			}

			// Values to exclude from list attributes and authorities; can be idnos or ids
			$va_exclude_values = caGetOption('exclude_values', $va_facet_info, array(), array('castTo' => 'array'));

			// Force all facet content when facet supports multiple selection
			$va_full_criteria = $this->getCriteria();
			if (isset($va_facet_info['multiple']) && $va_facet_info['multiple'] && isset($va_full_criteria[$ps_facet_name])) { $pa_options['returnFullFacet'] = true; }
			
			if (caGetOption('returnFullFacet', $pa_options, false)) {
			    $va_results = null; $va_container_ids = null;
			} else {
			    $va_results = $this->opo_ca_browse_cache->getResults();
			    if (!is_array($va_container_ids = $this->opo_ca_browse_cache->getParameter('container_ids'))) { $va_container_ids = []; }
			}

			$vb_single_value_is_present = false;
			$vs_single_value = isset($va_facet_info['single_value']) ? $va_facet_info['single_value'] : null;

			$va_wheres = array();

			switch($va_facet_info['type']) {
				# -----------------------------------------------------
				case 'has':
					$vn_state = null;
					if (isset($va_all_criteria[$ps_facet_name])) { break; }		// only one instance of this facet allowed per browse

					if (!($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true))) { break; }
					
					if (isset($va_facet_info['element_code']) && $t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name,  array_shift(explode('.', $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { 
					    return []; 
					} elseif((isset($va_facet_info['table']) && $t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name,  $va_facet_info['table']) < __CA_BUNDLE_ACCESS_READONLY__))) { 
					    return []; 
					}
					

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
						if (!$t_element->load(array('element_code' => array_pop(explode('.', $va_facet_info['element_code']))))) {
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

							if (is_array($va_results) && sizeof($va_results)) {
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
								if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							if (is_array($va_wheres) && sizeof($va_wheres) > 0) {
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
									SELECT DISTINCT ".$vs_browse_table_name.'.'.$t_item->primaryKey()."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}";
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->numRows() > 0) {
									$va_facet[$vs_state_name] = array_merge($va_state_info, ['content_count' => $qr_res->numRows()]);
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

						$vn_table_num = Datamodel::getTableNum($vs_rel_table_name);
						$vs_rel_table_pk = Datamodel::primaryKey($vn_table_num);

						switch(sizeof($va_path = array_keys(Datamodel::getPath($vs_browse_table_name, $vs_rel_table_name)))) {
							case 3:
								$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
								$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
								$vs_key = 'relation_id';
								break;
							case 2:
								$t_item_rel = null;
								$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
								$vs_key = $t_rel_item->primaryKey();
								break;
							default:
								// bad related table
								return null;
								break;
						}
						
						if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
						if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item)))) { $va_restrict_to_types = []; }
						$va_restrict_to_types = array_filter($va_restrict_to_types, "strlen");
						
						if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
						if (!is_array($va_exclude_types = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item)))) { $va_exclude_types = []; }
						$va_exclude_types = array_filter($va_exclude_types, "strlen");

						$vs_cur_table = array_shift($va_path);
						$va_joins_init = array();

						foreach($va_path as $vs_join_table) {
							$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
							$va_joins_init[] = ($vn_state ? 'INNER' : 'LEFT').' JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
							$vs_cur_table = $vs_join_table;
						}

						$va_facet = array();
						$va_counts = array();
						foreach($va_facet_values as $vs_state_name => $va_state_info) {
							$va_wheres = array();
							$va_joins = $va_joins_init;
							
							if (!(bool)$va_state_info['id']) {	// no option
								$vn_num_wheres = sizeof($va_wheres);
								$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
								
								if ($t_rel_item->hasField('deleted')) {
									$va_wheres[] = "((".$t_rel_item->tableName().".deleted = 0) OR (".$t_rel_item->tableName().".deleted IS NULL))";
								}
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
									$va_wheres[] = "((".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess']).")) OR (".$t_rel_item->tableName().".access IS NULL))";
								}

								if (is_array($va_restrict_to_relationship_types) && (sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
									$va_wheres[] = "((".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_restrict_to_relationship_types).")) OR (".$t_item_rel->tableName().".type_id IS NULL))";
								}
								if (is_array($va_exclude_relationship_types) && (sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
									$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_exclude_relationship_types)."))";
								}
								
								if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)) {
									$va_wheres[] = "((".$t_rel_item->tableName().".type_id NOT IN (".join(',', $va_restrict_to_types).")) OR (".$t_rel_item->tableName().".type_id IS NULL))";
								}
								
								if (is_array($va_exclude_types) && sizeof($va_exclude_types)) {
									$va_wheres[] = "(".$t_rel_item->tableName().".type_id IN (".join(',', $va_exclude_types).")) ";
								}

								if (is_array($va_wheres) && sizeof($va_wheres) == $vn_num_wheres) {
									$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NULL)";
								}
							} else {							// yes option
								$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." IS NOT NULL)";
								if ($t_rel_item->hasField('deleted')) {
									$va_wheres[] = "(".$t_rel_item->tableName().".deleted = 0)";
								}
								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
									$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";
								}

								if (is_array($va_restrict_to_relationship_types) && (sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
									$va_wheres[] = "(".$t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types)."))";
								}
								if (is_array($va_exclude_relationship_types) && (sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
									$va_wheres[] = "(".$t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types)."))";
								}
								
								if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)) {
									$va_wheres[] = "(".$t_rel_item->tableName().".type_id IN (".join(',', $va_restrict_to_types)."))";
								}
								
								if (is_array($va_exclude_types) && sizeof($va_exclude_types)) {
									$va_wheres[] = "((".$t_rel_item->tableName().".type_id NOT IN (".join(',', $va_exclude_types).")) OR (".$t_rel_item->tableName().".type_id IS NULL))";
								}
							}


							if ($t_item->hasField('deleted')) {
								$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
							}

							if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
								$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
							}

							if (is_array($va_results) && sizeof($va_results)) {
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
								if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							if (is_array($va_wheres) && sizeof($va_wheres) > 0) {
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
									SELECT DISTINCT ".$vs_browse_table_name.'.'.$t_item->primaryKey()."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									{$vs_where_sql}";
									
								//print "$vs_sql<hr>";
								$qr_res = $this->opo_db->query($vs_sql);
								if ($qr_res->numRows() > 0) {
									$va_facet[$vs_state_name] = array_merge($va_state_info, ['content_count' => $qr_res->numRows()]);
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
					if (!($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true))) { break; }
					if (!($t_label = $t_item->getLabelTableInstance())) { break; }
					
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name,  'preferred_labels') < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					
					if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
					if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
					
					if(sizeof($va_label_order_by_fields = isset($va_facet_info['order_by_label_fields']) ? $va_facet_info['order_by_label_fields'] : [])) {
					    $va_label_order_by_fields = array_map(function($v) { return "l.{$v}"; }, $va_label_order_by_fields);
					} else {
					    $va_label_order_by_fields = [];
					}

					$vs_item_pk = $t_item->primaryKey();
					$vs_label_table_name = $t_label->tableName();
					$vs_label_pk = $t_label->primaryKey();
					$vs_label_display_field = $t_item->getLabelDisplayField();
					$va_label_ui_fields = $t_item->getLabelUIFields(); 
					$vs_label_sort_field = $t_item->getLabelSortField();
					
					if(is_array($va_label_ui_fields) && sizeof($va_label_ui_fields)) {
					    $va_label_ui_fields = array_map(function($v) { return "l.{$v}"; }, $va_label_ui_fields);
					}
					
					foreach($va_label_ui_fields as $x) {
					    if (!in_array($x, $va_label_order_by_fields)) { $va_label_order_by_fields[] = $x; }
					}
					

					$vs_where_sql = $vs_join_sql = '';
					$vb_needs_join = false;

					$va_where_sql = array();
					$va_joins = array();

					if ($vs_browse_type_limit_sql) {
						$va_where_sql[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_where_sql[] = $vs_browse_source_limit_sql;
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

					if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)) {
						$va_restrict_to_type_ids = caMakeTypeIDList($vs_browse_table_name, $va_restrict_to_types, array('dont_include_subtypes_in_type_restriction' => true));
						if (is_array($va_restrict_to_type_ids) && sizeof($va_restrict_to_type_ids)) {
							$va_where_sql[] = "(".$vs_browse_table_name.".".$t_item->getTypeFieldName()." IN (".join(", ", $va_restrict_to_type_ids).")".($t_item->getFieldInfo('type_id', 'IS_NULL') ? " OR (".$vs_browse_table_name.'.'.$t_item->getTypeFieldName()." IS NULL)" : '').")";
							$vb_needs_join = true;
						}
					}
					if (is_array($va_exclude_types) && sizeof($va_exclude_types)) {
						$va_exclude_type_ids = caMakeTypeIDList($vs_browse_table_name, $va_exclude_types, array('dont_include_subtypes_in_type_restriction' => true));
						if (is_array($va_exclude_type_ids) && sizeof($va_exclude_type_ids)) {
							$va_where_sql[] = "(".$vs_browse_table_name.".".$t_item->getTypeFieldName()." NOT IN (".join(", ", $va_exclude_type_ids).")".($t_item->getFieldInfo('type_id', 'IS_NULL') ? " OR (".$vs_browse_table_name.'.'.$t_item->getTypeFieldName()." IS NULL)" : '').")";
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


					if (is_array($va_results) && sizeof($va_results)) {
						if ($va_facet_info['relative_to']) {
							$va_where_sql[] = $this->ops_browse_table_name.".".$t_subject->primaryKey()." IN (".join(",", $va_results).")";
						} else {
							$va_where_sql[] = "l.{$vs_item_pk} IN (".join(",", $va_results).")";
						}
					}


					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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

					if (is_array($va_where_sql) && sizeof($va_where_sql)) {
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
						$qr_res = $this->opo_db->query($vs_sql);

						return ((int)$qr_res->numRows() > 0) ? true : false;
					} else {
						$vs_parent_fld_select = (($vs_parent_fld = $t_item->getProperty('HIERARCHY_PARENT_ID_FLD')) ? $vs_browse_table_name.".".$vs_parent_fld : '');
						
						$group_by_fields = array_unique(array_merge($va_label_order_by_fields, $va_label_ui_fields, ["l.{$vs_label_display_field}", $vs_parent_fld_select, "l.locale_id", "l.{$vs_item_pk}"]));
						$vs_sql = "
							SELECT COUNT(*) as _count, l.locale_id, l.{$vs_label_display_field} ".($vs_parent_fld_select ? ", {$vs_parent_fld_select}" : "").", l.{$vs_item_pk}".((sizeof($va_label_ui_fields) > 0) ? ", ".join(", ", $va_label_ui_fields) : "")."
							FROM {$vs_label_table_name} l
								{$vs_join_sql}
								{$vs_where_sql}
							GROUP BY ".join(", ", $group_by_fields)."
							ORDER BY ".((sizeof($va_label_order_by_fields) > 0) ? join(", ", $va_label_order_by_fields) : "l.{$vs_label_display_field}")."
						";

						$qr_res = $this->opo_db->query($vs_sql);

						$va_values = array();
						$va_child_counts = array();
						$vn_parent_id = null;

						$va_unique_values = array();
						$vn_id = 0;
						
						$vs_label_template = caGetOption('template', $va_facet_info, null);
						while($qr_res->nextRow()) {
							$vn_id++;

							if ($vs_parent_fld) {
								$vn_parent_id = $qr_res->get($vs_parent_fld);
								if ($vn_parent_id) { $va_child_counts[$vn_parent_id]++; }
							}

                            if ($vs_label_template) {
                                $vs_label = caProcessTemplate($vs_label_template, $qr_res->getRow());
                            } else {
							    $vs_label = trim($qr_res->get($vs_label_display_field));
							}
							$vs_sort_label = trim($qr_res->get($vs_label_sort_field));
							
							//if (isset($va_unique_values[$vs_label])) { continue; }
							$va_unique_values[$vs_label] = true;
                            $vs_label_key = strtolower($vs_label);
                            if (!isset($va_values[$vs_label_key][$qr_res->get('locale_id')])) {
                                $va_values[$vs_label_key][$qr_res->get('locale_id')] = array_merge($qr_res->getRow(), array(
                                    'id' => $qr_res->get($vs_item_pk),
                                    'parent_id' => $vn_parent_id,
                                    'label' => $vs_label,
                                    'sort_label' =>  mb_strtolower($vs_sort_label ? $vs_sort_label :  $vs_label),
                                    'content_count' => $qr_res->get('_count')
                                ));
                            } else {
                                $va_values[$vs_label_key][$qr_res->get('locale_id')]['content_count'] += (int)$qr_res->get('_count');
                            }
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
						return array_values($va_values);
					}
					break;
				# -----------------------------------------------------
				case 'attribute':
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					$t_element = new ca_metadata_elements();
					$va_element_code = explode(".", $va_facet_info['element_code']);
					
					if (!$t_element->load(array('element_code' => array_pop($va_element_code)))) { return []; }
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					$vs_container_code = (is_array($va_element_code) && (sizeof($va_element_code) > 0)) ? array_pop($va_element_code) : null;

					$vn_element_type = $t_element->get('datatype');
					$vn_element_id = $t_element->getPrimaryKey();

					$va_joins = array(
						'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
						'INNER JOIN '.(!$vb_is_relative_to_parent ? "{$vs_browse_table_name} ON {$vs_browse_table_name}." : "{$vs_browse_table_name} AS parent ON parent.").$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($vs_browse_table_num)
					);

					$va_wheres = array();
					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}


					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							SELECT 1
							FROM ca_attributes

							{$vs_join_sql}
							WHERE
								(ca_attribute_values.element_id = ?) {$vs_criteria_exclude_sql} {$vs_where_sql}
							LIMIT 2";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql,$vn_element_id);

						return ((int)$qr_res->numRows() > 1) ? true : false;
					} else {
					    $vs_container_sql = '';
					    $va_params = [$vn_element_id];
					    if (is_array($va_container_ids[$vs_container_code]) && sizeof($va_container_ids[$vs_container_code])) {
					        $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
					        $va_params[] = $va_container_ids[$vs_container_code];
					    }
						$vs_sql = "
							SELECT COUNT(*) as _count, ca_attribute_values.value_longtext1, ca_attribute_values.value_decimal1, ca_attribute_values.value_longtext2, ca_attribute_values.value_integer1, ca_attribute_values.element_id
							FROM ca_attributes

							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ? {$vs_where_sql} {$vs_container_sql}
						    GROUP BY value_longtext1, value_decimal1, value_longtext2, value_integer1
						";
						$qr_res = $this->opo_db->query($vs_sql, $va_params);

						$va_values = [];
                        $va_list_items = $va_suppress_values = null;
						
						if ($va_facet_info['suppress'] && !is_array($va_facet_info['suppress'])) {
							$va_facet_info['suppress'] = array($va_facet_info['suppress']);
						}

						if(!is_array($va_suppress_values = caGetOption('suppress', $va_facet_info, null))) {
							$va_suppress_values = caGetOption('exclude_values', $va_facet_info, null);
						}

						
						switch($vn_element_type) {
							case __CA_ATTRIBUTE_VALUE_LIST__:
								if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_facet_info['restrict_to_types'], array('instance' => new ca_list_items(), 'dontExpandHierarchically' => true)))) { $va_restrict_to_types = array(); }

								$va_values = $qr_res->getAllFieldValues('value_longtext1');
								$va_value_counts = $qr_res->getAllFieldValues('_count');
								$qr_res->seek(0);

								$t_list_item = new ca_list_items();
								$va_list_item_cache = $t_list_item->getFieldValuesForIDs($va_values, array('type_id', 'idno', 'item_value', 'parent_id', 'access', 'deleted', 'rank'));
								$va_list_child_count_cache = array();
								if (is_array($va_list_item_cache)) {
									foreach($va_list_item_cache as $vn_id => $va_item) {
										if (!($vn_parent_id = $va_item['parent_id'])) { continue; }
										if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && !in_array($va_item['access'], $pa_options['checkAccess'])) { continue; }
										$va_list_child_count_cache[$vn_parent_id]++;
									}
								}
								$va_list_label_cache = $t_list_item->getPreferredDisplayLabelsForIDs($va_values);

								// Translate value idnos to ids
								if (is_array($va_suppress_values)) { $va_suppress_values = ca_lists::getItemIDsFromList($t_element->get('list_id'), $va_suppress_values); }

								$va_facet_list = [];
								$va_children_by_parent_id = [];
								foreach($va_values as $i => $vn_val) {
									if (!$vn_val) { continue; }
									if ($va_list_item_cache[$vn_val]['deleted'] > 0) { continue; }
									if (is_array($va_suppress_values) && (in_array($vn_val, $va_suppress_values))) { continue; }
									if (is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && !in_array($va_list_item_cache[$vn_val]['access'], $pa_options['checkAccess'])) { continue; }

									if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types) && !in_array($va_list_item_cache[$vn_val]['type_id'], $va_restrict_to_types)) { continue; } 
									
									if ($va_criteria[$vn_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on
									$vn_child_count = isset($va_list_child_count_cache[$vn_val]) ? $va_list_child_count_cache[$vn_val] : 0;
									
									if (!($vs_label = html_entity_decode($va_list_label_cache[$vn_val]))) { $vs_label = '['.caGetBlankLabelText().']'; }
									if (is_array($va_facet_info['relabel']) && isset($va_facet_info['relabel'][$vs_label])) {
									    $vs_label = $va_facet_info['relabel'][$vs_label];
									}
									$va_facet_list[$vn_val] = array(
										'id' => $vn_val,
										'label' => $vs_label,
										'parent_id' => $vn_parent_id = isset($va_list_item_cache[$vn_val]['parent_id']) ? $va_list_item_cache[$vn_val]['parent_id'] : null,
										'child_count' => $vn_child_count,
										'content_count' => $va_value_counts[$i],
										'rank' => $va_list_item_cache[$vn_val]['rank'],
										'item_value' => $va_list_item_cache[$vn_val]['item_value'],
										'idno' => $va_list_item_cache[$vn_val]['idno']
									);
									$va_children_by_parent_id[$vn_parent_id][] = $vn_val;
								}
								
								if (!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) {
									$t_rel_item = new ca_list_items();
									$qr_res->seek(0);
									$va_ids = $qr_res->getAllFieldValues($vs_rel_pk);
									$qr_ancestors = call_user_func($t_rel_item->tableName().'::getHierarchyAncestorsForIDs', $va_values, array('returnAs' => 'SearchResult'));

									$vs_rel_table = $t_rel_item->tableName();
									$vs_rel_pk = $t_rel_item->primaryKey();

									$vb_check_ancestor_access = (bool)(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access'));

									if($qr_ancestors) {
									    $va_parent_counts = [];
										while($qr_ancestors->nextHit()) {
											if ($qr_ancestors->get('deleted')) { continue; }
											$vn_ancestor_id = (int)$qr_ancestors->get("{$vs_rel_pk}");
											
											$vn_parent_type_id = $qr_ancestors->get('type_id');
											if (is_array($va_suppress_values) && (in_array($vn_ancestor_id, $va_suppress_values))) { continue; }
											if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && in_array($vn_parent_type_id, $va_exclude_types)) { continue; }
											if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && !in_array($vn_parent_type_id, $va_restrict_to_types)) { continue; }
											if ($vb_check_ancestor_access && !in_array($qr_ancestors->get('access'), $pa_options['checkAccess'])) { continue; }
											if (!($vn_parent_id = $qr_ancestors->get("parent_id"))) { continue; }
											

                                            if ((!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) && $t_rel_item->isHierarchical()) {
                                                $vs_hier_left_fld = $t_rel_item->getProperty('HIERARCHY_LEFT_INDEX_FLD');
                                                $vs_hier_right_fld = $t_rel_item->getProperty('HIERARCHY_RIGHT_INDEX_FLD');

                                                $vs_get_item_sql = "{$vs_rel_table}.{$vs_hier_left_fld} >= ".$qr_ancestors->get($vs_hier_left_fld). " AND {$vs_rel_table}.{$vs_hier_right_fld} <= ".$qr_ancestors->get($vs_hier_right_fld);
                                                if ($vn_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
                                                    $vs_get_item_sql .= " AND {$vs_rel_table}.{$vn_hier_id_fld} = ".(int)$qr_ancestors->get($vn_hier_id_fld);
                                                }
                                                $vs_get_item_sql = "({$vs_get_item_sql})";
                                            } else {
                                                $vs_get_item_sql = "({$vs_rel_table}.{$vs_rel_pk} = {$vn_ancestor_id})";
                                            }
                                            
                                            $vs_sql = "
                                                SELECT COUNT(DISTINCT ca_attributes.row_id, ca_attributes.table_num) as _count
                                                FROM ca_attributes

                                                {$vs_join_sql}
                                                INNER JOIN ca_list_items ON ca_list_items.item_id = ca_attribute_values.item_id
                                                WHERE
                                                    ca_attribute_values.element_id = ? {$vs_where_sql} AND {$vs_get_item_sql}	
                                            ";
                                            $q_hier_count = $this->opo_db->query($vs_sql, $vn_element_id);
										    $q_hier_count->nextRow();
										
											$va_facet_list[$vn_ancestor_id] = array(
												'id' => $vn_ancestor_id,
												'label' => ($vs_label = $qr_ancestors->get('ca_list_items.preferred_labels.name_plural')) ? $vs_label : '['.caGetBlankLabelText().']',
												'parent_id' => $vn_parent_id,
												'hierarchy_id' => $qr_ancestors->get('list_id'),
												'child_count' => 1,
										        'content_count' => (int)$q_hier_count->get('_count'),
										        'rank' => $qr_ancestors->get('rank'),
										        'item_value' => $qr_ancestors->get('item_value'),
										        'idno' => $qr_ancestors->get('idno')
											);
										}
									}
								}
								
								// preserve order of list
								if ($vn_list_id = $t_element->get('list_id')) {
								    $t_list = new ca_lists($vn_list_id);
								    switch($t_list->get('default_sort')) {
								        case __CA_LISTS_SORT_BY_RANK__:
								            return caSortArrayByKeyInValue($va_facet_list, array('rank')); 
								            break;
								        case __CA_LISTS_SORT_BY_IDENTIFIER__:
								            return caSortArrayByKeyInValue($va_facet_list, array('idno')); 
								            break;
								        case __CA_LISTS_SORT_BY_VALUE__:
								            return caSortArrayByKeyInValue($va_facet_list, array('item_value')); 
								            break;
								        default:
								            return caSortArrayByKeyInValue($va_facet_list, ['label'], 'ASC', ['naturalSort' => true]); 
								            break;
								    }
								} else {
								    return caSortArrayByKeyInValue($va_facet_list, ['label'], 'ASC', ['naturalSort' => true]); 
								}
								break;
							case __CA_ATTRIBUTE_VALUE_OBJECTS__:
							case __CA_ATTRIBUTE_VALUE_ENTITIES__:
							case __CA_ATTRIBUTE_VALUE_PLACES__:
							case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
							case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
							case __CA_ATTRIBUTE_VALUE_LOANS__:
							case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
							case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
							case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
								if ($t_rel_item = AuthorityAttributeValue::elementTypeToInstance($vn_element_type)) {
									$va_ids = $qr_res->getAllFieldValues('value_integer1');
									$va_auth_items = $t_rel_item->getPreferredDisplayLabelsForIDs($va_ids);
									$qr_res->seek(0);
								}
								break;
							default:
								if (isset($va_facet_info['suppress']) && is_array($va_facet_info['suppress'])) {
									$va_suppress_values = $va_facet_info['suppress'];
								}
								break;
						}

						while($qr_res->nextRow()) {
							$o_attr = Attribute::getValueInstance($vn_element_type, $row = $qr_res->getRow(), true);
							if (!($vs_val = trim($o_attr->getDisplayValue()))) { continue; }
							if (is_array($va_suppress_values) && (in_array($vs_val, $va_suppress_values))) { continue; }
							if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

							switch($vn_element_type) {
								case __CA_ATTRIBUTE_VALUE_LIST__:
									$vn_child_count = 0;

									if ($va_list_parent_ids[$vs_val]) {
										 $vn_child_count++;
									}
									$vn_id = $qr_res->get('value_integer1');
									$va_values[strToLower($vs_val)] = array(
										'id' => $vn_id, 
										'label' => html_entity_decode($va_list_items[$vs_val]['name_plural'] ? $va_list_items[$vs_val]['name_plural'] : $va_list_items[$vs_val]['item_value']),
										'parent_id' => $va_list_items[$vs_val]['parent_id'],
										'child_count' => $vn_child_count,
										'content_count' => $qr_res->get('_count')
									);
									break;
								case __CA_ATTRIBUTE_VALUE_OBJECTS__:
								case __CA_ATTRIBUTE_VALUE_ENTITIES__:
								case __CA_ATTRIBUTE_VALUE_PLACES__:
								case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
								case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
								case __CA_ATTRIBUTE_VALUE_LOANS__:
								case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
								case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
								case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
									$vn_id = $qr_res->get('value_integer1');
									$va_values[strToLower($vs_val)] = array(
										'id' => $vn_id,
										'label' => html_entity_decode($va_auth_items[$vn_id] ? $va_auth_items[$vn_id] : $vs_val),
										'content_count' => $qr_res->get('_count')
									);
									break;
								case __CA_ATTRIBUTE_VALUE_LCSH__:
								    $value_id = ca_attribute_values::getValueIDFor($o_attr->getElementID(), $vs_val);
									$va_values[strToLower($vs_val)] = array(
										'id' => $value_id,
										'label' => preg_replace('![ ]*\[[^\]]*\]!', '', $vs_val),
										'content_count' => $qr_res->get('_count')
									);
									break;
								case __CA_ATTRIBUTE_VALUE_GEONAMES__:
								    $value_id = ca_attribute_values::getValueIDFor($o_attr->getElementID(), preg_replace('![ ]*\[[^\]]*\]$!', '', $vs_val));
									$va_values[strToLower($vs_val)] = array(
										'id' => $value_id,
										'label' => preg_replace('![ ]*\[[^\]]*\]!', '', $vs_val),
										'content_count' => $qr_res->get('_count')
									);
									break;
								case __CA_ATTRIBUTE_VALUE_CURRENCY__:
								    $value_id = ca_attribute_values::getValueIDFor($o_attr->getElementID(), $row);
									$va_values[sprintf("%014.2f", preg_replace("![\D]+!", "", $vs_val))] = array(
										'id' => $value_id, 
										'label' => $vs_val,
										'content_count' => $qr_res->get('_count')
									);
									break;
								default:
								    $value_id = ca_attribute_values::getValueIDFor($o_attr->getElementID(), $vs_val);
									$va_values[strToLower($vs_val)] = array(
										'id' => $value_id,
										'label' => $vs_val,
										'content_count' => $qr_res->get('_count')
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

						ksort($va_values);
						return $va_values;
					}
					break;
				# -----------------------------------------------------
				case 'location':
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, 'ca_objects_location') < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }

					$vs_sort_field = null;
					if (($t_item->getProperty('ID_NUMBERING_ID_FIELD') == $vs_field_name)) {
						$vs_sort_field = $t_item->getProperty('ID_NUMBERING_SORT_FIELD');
					}

					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';

					$va_wheres[] = "({$vs_browse_table_name}.current_loc_class IS NOT NULL)";
					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
					}

					if ($t_item->hasField('deleted')) {
						$va_wheres[] = "(".$vs_browse_table_name.".deleted = 0)";
					}

					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
						if(sizeof($va_criteria) > 0) { return false; }		// only one current location criteria allowed
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
						if(sizeof($va_criteria) > 0) { return array(); }	// only one current location criteria allowed

						$vs_pk = $t_item->primaryKey();
						$vs_sql = "
							SELECT COUNT(*) _count, {$vs_browse_table_name}.current_loc_class, {$vs_browse_table_name}.current_loc_subclass, {$vs_browse_table_name}.current_loc_id
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
							WHERE
								{$vs_where_sql}
							GROUP BY {$vs_browse_table_name}.current_loc_class, {$vs_browse_table_name}.current_loc_subclass, {$vs_browse_table_name}.current_loc_id	
							";
						if($vs_sort_field) {
							$vs_sql .= " ORDER BY {$vs_sort_field}";
						}
						$qr_res = $this->opo_db->query($vs_sql);

						$va_collapse_map = $this->getCollapseMapForLocationFacet($va_facet_info);

						$va_values = $va_values_by_table = array();
						while($qr_res->nextRow()) {
							if (!($vs_loc_class = trim($qr_res->get('current_loc_class')))) { continue; }
							if (!($vs_loc_subclass = trim($qr_res->get('current_loc_subclass')))) { continue; }
							if (!($vs_loc_id = trim($qr_res->get('current_loc_id')))) { continue; }
							$vs_val = "{$vs_loc_class}:{$vs_loc_subclass}:{$vs_loc_id}";
							if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

							$va_values_by_table[$vs_loc_class][$vs_loc_subclass][$vs_loc_id] = true;
						}


						foreach($va_values_by_table as $vs_loc_class => $va_loc_id_by_subclass) {
							foreach($va_loc_id_by_subclass as $vs_loc_subclass => $va_loc_ids) {
								if(sizeof($va_tmp = array_keys($va_loc_ids))) {
									$vs_table_name = $vs_loc_table_name = Datamodel::getTableName($vs_loc_class);
									$vs_hier_table_name = (($vs_loc_table_name) == 'ca_objects_x_storage_locations') ? 'ca_storage_locations' : $vs_loc_table_name;

									$qr_res = caMakeSearchResult($vs_hier_table_name, $va_tmp);

									if (isset($va_collapse_map[$vs_hier_table_name]) && isset($va_collapse_map[$vs_hier_table_name]['*']) && $va_collapse_map[$vs_hier_table_name]['*']) {
										$va_values[$vs_id = "{$vs_loc_class}"] = array(
											'id' => $vs_id,
											'label' => $va_collapse_map[$vs_hier_table_name]['*']
										);
										continue;
									}
									
									$va_config = ca_objects::getConfigurationForCurrentLocationType($vs_table_name, $vs_loc_subclass, array('facet' => isset($va_facet_info['display']) ? $va_facet_info['display'] : null));

									if ($vs_hier_table_name == 'ca_storage_locations') {
										
										if (!($vn_max_browse_depth = caGetOption('maximumBrowseDepth', $va_facet_info, null))) {
											$vn_max_browse_depth = caGetOption('maximumBrowseDepth', $va_config, null);
										}
										if (!$vn_max_browse_depth) { $vn_max_browse_depth = null; } else { $vn_max_browse_depth++; }	// add one to account for invisible root
										
										$vs_hier_pk = Datamodel::primaryKey($vs_hier_table_name, false);
									
										$va_hier_ids = [];
										while($qr_res->nextHit()) {
											if (is_array($va_ids = $qr_res->get("{$vs_hier_table_name}.hierarchy.{$vs_hier_pk}", ['returnAsArray' => true, 'maxLevelsFromBottom' => $vn_max_browse_depth]))) {
												foreach($va_ids as $vn_id) {
													$va_hier_ids[$vn_id] = true;
												}
											}
										}	
										$va_hier_ids = array_keys($va_hier_ids);
										$qr_res = caMakeSearchResult($vs_hier_table_name, $va_hier_ids);
									}
									
									$vs_template = strip_tags(isset($va_config['template']) ? $va_config['template'] : "^{$vs_table_name}.preferred_labels");
	
									while($qr_res->nextHit()) {
										$vn_id = $qr_res->getPrimaryKey();

										if (isset($va_collapse_map[$vs_table_name]) && isset($va_collapse_map[$vs_table_name][$vs_loc_subclass]) && $va_collapse_map[$vs_table_name][$vs_loc_subclass]) {
											if (!($vs_label = $va_collapse_map[$vs_table_name][$vs_loc_subclass])) { continue; }
											$va_values[$vs_id = "{$vs_loc_class}:{$vs_loc_subclass}"] = array(
												'id' => $vs_id,
												'label' => $vs_label
											);
											continue;
										}
										
										if (!$vn_id || !($vs_label = $qr_res->getWithTemplate($vs_template, $va_config))) { continue; }
										$va_values[$vs_id = "{$vs_loc_class}:{$vs_loc_subclass}:{$vn_id}"] = array(
											'id' => $vs_id,
											'label' => $vs_label,
											'content_count' => $qr_res->get('_count')
										);
									}
								}
							}
						}

						if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
							return array();
						}
						return caSortArrayByKeyInValue($va_values, array('label'));
					}

					return array();
					break;
				# -----------------------------------------------------
				case 'fieldList':
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					$vs_field_name = $va_facet_info['field'];
					
					// Map deaccession fields to deaccession bundle, which is used for access control on all
					$bundle_name = (in_array($vs_field_name, ['deaccession_type_id'])) ? 'ca_objects_deaccession' : $vs_field_name;
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, $bundle_name) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
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
						if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
							$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
						}

						if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
							$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
							$va_wheres[] = "(li.access IN (".join(',', $pa_options['checkAccess'])."))";
						}

						if ($vs_browse_type_limit_sql) {
							$va_wheres[] = $vs_browse_type_limit_sql;
						}

						if ($vs_browse_source_limit_sql) {
							$va_wheres[] = $vs_browse_source_limit_sql;
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
							$va_wheres[] = "(li.item_id NOT IN (".join(",", caQuoteList(array_keys($va_criteria)))."))";
						}

						if ($this->opo_config->get('perform_item_level_access_checking')) {
							if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							return ((int)$qr_res->numRows() > 1) ? true : false;
						} else {
							// Get label ordering fields
							$va_ordering_fields_to_fetch = (isset($va_facet_info['order_by_label_fields']) && is_array($va_facet_info['order_by_label_fields'])) ? $va_facet_info['order_by_label_fields'] : array();

							$va_orderbys = array();
							
							// Get label ordering fields
							if (isset($va_facet_info['order_by_label_fields']) && is_array($va_facet_info['order_by_label_fields']) && sizeof($va_facet_info['order_by_label_fields'])) {
								$t_rel_item_label = new ca_list_item_labels();
								foreach($va_facet_info['order_by_label_fields'] as $vs_sort_by_field) {
									if (!$t_rel_item_label->hasField($vs_sort_by_field)) { continue; }
									$va_orderbys[] = $va_label_selects[] = 'lil.'.$vs_sort_by_field;
								}
							} else {
								$t_list->load(array('list_code' => $vs_list_name));
								$vn_sort = $t_list->get('default_sort');
								switch($vn_sort) {
									default:
									case __CA_LISTS_SORT_BY_LABEL__:	// by label
										$va_orderbys[] = 'lil.name_plural';
										break;
									case __CA_LISTS_SORT_BY_RANK__:	// by rank
										$va_orderbys[] = 'li.rank';
										break;
									case __CA_LISTS_SORT_BY_VALUE__:	// by value
										$va_orderbys[] = 'li.item_value';
										break;
									case __CA_LISTS_SORT_BY_IDENTIFIER__:	// by identifier
										$va_orderbys[] = 'li.idno_sort';
										break;
								}
							}

							$vs_order_by = (sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
							$vs_sql = "
								SELECT COUNT(*) _count, lil.item_id, lil.name_singular, lil.name_plural, lil.name_sort, lil.locale_id, li.rank, li.idno_sort, li.parent_id
								FROM ca_list_items li
								INNER JOIN ca_list_item_labels AS lil ON lil.item_id = li.item_id
								{$vs_join_sql}
								WHERE
									ca_lists.list_code = ?  AND lil.is_preferred = 1 {$vs_where_sql}
								GROUP BY lil.item_id, lil.name_singular, lil.name_plural, lil.name_sort, lil.locale_id, li.rank, li.idno_sort 
								{$vs_order_by}
								";
							//print $vs_sql." [$vs_list_name]";
							$qr_res = $this->opo_db->query($vs_sql, $vs_list_name);

							$va_values = [];
							$vn_root_id = $t_list->getRootItemIDForList();
							while($qr_res->nextRow()) {
							    $vn_parent_id = $qr_res->get('parent_id');
								$vn_id = $qr_res->get('item_id');
								if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

								$va_values[$vn_id][$qr_res->get('locale_id')] = array(
									'id' => $vn_id,
									'label' => $qr_res->get('name_plural'),
									'content_count' => $qr_res->get('_count'),
									'parent_id' => $vn_parent_id,
									'child_count' => 0
								);
								if (!is_null($vs_single_value) && ($vn_id == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
							}
							
							$va_values = caExtractValuesByUserLocale($va_values);
							if ($va_facet_info['group_mode'] == 'hierarchical') {
                                $t_rel_item = new ca_list_items();
                                $qr_ancestors = call_user_func($t_rel_item->tableName().'::getHierarchyAncestorsForIDs', array_keys($va_values), array('returnAs' => 'SearchResult'));

                                $vs_rel_table = $t_rel_item->tableName();
                                $vs_rel_pk = $t_rel_item->primaryKey();

                                $vb_check_ancestor_access = (bool)(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access'));

                                if($qr_ancestors) {
                                    while($qr_ancestors->nextHit()) {
                                        if ($qr_ancestors->get('deleted')) { continue; }
                                        $vn_ancestor_id = (int)$qr_ancestors->get("{$vs_rel_pk}");
                                        $vn_parent_type_id = $qr_ancestors->get('type_id');
                                        if (is_array($va_suppress_values) && (in_array($vn_ancestor_id, $va_suppress_values))) { continue; }
                                        if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && in_array($vn_parent_type_id, $va_exclude_types)) { continue; }
                                        if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && !in_array($vn_parent_type_id, $va_restrict_to_types)) { continue; }
                                        if ($vb_check_ancestor_access && !in_array($qr_ancestors->get('access'), $pa_options['checkAccess'])) { continue; }
                                        $va_values[$vn_ancestor_id] = array(
                                            'id' => $vn_ancestor_id,
                                            'label' => ($vs_label = $qr_ancestors->get('ca_list_items.preferred_labels.name_plural')) ? $vs_label : '['.caGetBlankLabelText().']',
                                            'parent_id' => $qr_ancestors->get('ca_list_items.parent_id'),
                                            'child_count' => 1
                                        );
                                    }
                                }
                            }
							
							
							foreach($va_values as $vn_id => $va_value) {
                                if ($va_value['parent_id'] && isset($va_values[$va_value['parent_id']])) {
                                    $va_values[$va_value['parent_id']]['child_count']++;
                                }
                            }

							if (!is_null($vs_single_value) && !$vb_single_value_is_present) {
								return array();
							}
							return $va_values;
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

							if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
								$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
							}

							if ($vs_browse_type_limit_sql) {
								$va_wheres[] = $vs_browse_type_limit_sql;
							}

							if ($vs_browse_source_limit_sql) {
								$va_wheres[] = $vs_browse_source_limit_sql;
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
								if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
									SELECT COUNT(*) _count, ".$vs_browse_table_name.'.'.$vs_field_name."
									FROM ".$vs_browse_table_name."
									{$vs_join_sql}
									".($vs_where_sql ? 'WHERE' : '')."
										{$vs_where_sql}
									GROUP BY {$vs_browse_table_name}.{$vs_field_name}
								";
								//print $vs_sql." [$vs_list_name]";

								$qr_res = $this->opo_db->query($vs_sql);
								$va_values = array();
								while($qr_res->nextRow()) {
									$vn_id = $qr_res->get($vs_field_name);
									if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

									if (isset($va_list_items_by_value[$vn_id])) {
										$va_values[$vn_id] = array(
											'id' => $vn_id,
											'label' => $va_list_items_by_value[$vn_id],
											'content_count' => $qr_res->get('_count')
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
							if ($t_browse_table = Datamodel::getInstanceByTableName($vs_facet_table = $va_facet_info['table'], true)) {
								// Handle fields containing ca_list_item.item_id's
								$va_joins = array(
									'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$vs_field_name.' = '.$vs_facet_table.'.'.$t_browse_table->primaryKey()
								);


								$vs_display = caGetOption('display', $va_facet_info, "^".$t_browse_table->tableName().".preferred_labels");
								if (method_exists($t_browse_table, 'getLabelTableInstance')) {
									$t_label_instance = $t_browse_table->getLabelTableInstance();
									$va_joins[] = 'INNER JOIN '.$t_label_instance->tableName()." AS lab ON lab.".$t_browse_table->primaryKey().' = '.$t_browse_table->tableName().'.'.$t_browse_table->primaryKey();
								}

								if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
									$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
								}

								if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
									$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
								}

								if ($vs_browse_type_limit_sql) {
									$va_wheres[] = $vs_browse_type_limit_sql;
								}

								if ($vs_browse_source_limit_sql) {
									$va_wheres[] = $vs_browse_source_limit_sql;
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
									if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
										SELECT COUNT(*) _count, ".$t_browse_table->primaryKey(true)."
										FROM {$vs_facet_table}

										{$vs_join_sql}
										{$vs_where_sql}
									    GROUP BY ".$t_browse_table->primaryKey(true)."
									";
									//print $vs_sql;
									$qr_res = $this->opo_db->query($vs_sql);

									$va_values = array();
									$vs_pk = $t_browse_table->primaryKey();
									
									$values = caProcessTemplateForIDs($vs_display, $t_browse_table->tableName(), $qr_res->getAllFieldValues($vs_pk), ['returnAsArray' => true, 'indexWithIDs' => true]);
									
									$qr_res->seek(0);
									while($qr_res->nextRow()) {
										$vn_id = $qr_res->get($vs_pk);
										if ($va_criteria[$vn_id]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

										$va_values[$vn_id][$qr_res->get('locale_id')] = array(
											'id' => $vn_id,
											'label' => isset($values[$vn_id]) ? $values[$vn_id] : "???", //$qr_res->get($vs_display_field_name),
									        'content_count' => $qr_res->get('_count')
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
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					if (!is_array($va_restrict_to_types = $va_facet_info['restrict_to_types'])) { $va_restrict_to_types = array(); }
					if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_item, 'dontExpandHierarchically' => true)))) { $va_restrict_to_types = array(); }
					$va_restrict_to_types_expanded = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_item));

					$vs_field_name = $va_facet_info['field'];
					
					// Map deaccession fields to deaccession bundle, which is used for access control on all
					$bundle_name = (in_array($vs_field_name, ['is_deaccessioned', 'deaccession_date', 'deaccession_notes', 'deaccession_type_id', 'deaccession_disposal_date'])) ? 'ca_objects_deaccession' : $vs_field_name;
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, $bundle_name) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					$va_field_info = $t_item->getFieldInfo($vs_field_name);

					$vs_sort_field = null;
					if (($t_item->getProperty('ID_NUMBERING_ID_FIELD') == $vs_field_name)) {
						$vs_sort_field = $vs_browse_table_name . '.' . $t_item->getProperty('ID_NUMBERING_SORT_FIELD');
					}

					$t_list = new ca_lists();
					$t_list_item = new ca_list_items();

					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';


					$va_facet_values = null;
					if($vb_is_bit = ($va_field_info['FIELD_TYPE'] == FT_BIT)) {
						$vs_yes_text = caGetOption('label_yes', $va_facet_info, _t('Yes'));
						$vs_no_text = caGetOption('label_no', $va_facet_info, _t('No'));

						$va_facet_values = array(
							1 => array(
								'id' => 1,
								'label' => $vs_yes_text
							),
							0 => array(
								'id' => 0,
								'label' => $vs_no_text
							)
						);
					}

					if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && method_exists($t_rel_item, "getTypeList")) {
						$va_wheres[] = "{$va_restrict_to_types_expanded}.type_id IN (".join(',', caGetOption('dont_include_subtypes', $va_facet_info, false) ? $va_restrict_to_types : $va_restrict_to_types_expanded).")";
						$va_selects[] = "{$va_restrict_to_types_expanded}.type_id";
					}

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							SELECT DISTINCT {$vs_browse_table_name}.{$vs_field_name}
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
							WHERE
								{$vs_where_sql}
							LIMIT 2";
						$qr_res = $this->opo_db->query($vs_sql);

						if ($qr_res->numRows() > 1) {
							return true;
						}
						return false;
					} else {

						$vs_pk = $t_item->primaryKey();
						$vs_sql = "
							SELECT COUNT(*) _count, {$vs_browse_table_name}.{$vs_field_name}
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
							WHERE
								{$vs_where_sql}
						    GROUP BY {$vs_browse_table_name}.{$vs_field_name}".($vs_sort_field ? ", {$vs_sort_field}" : "")."
						";
						if($vs_sort_field) {
							$vs_sql .= " ORDER BY {$vs_sort_field}";
						}
						$qr_res = $this->opo_db->query($vs_sql);

						$va_values = array();
						while($qr_res->nextRow()) {
							if (!($vs_val = trim($qr_res->get($vs_field_name))) && !$vb_is_bit) { continue; }
							if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

							if ($vb_is_bit && isset($va_facet_values[$vs_val])) {
								$va_values[$vs_val] = $va_facet_values[$vs_val];
							} else {
								$va_values[$vs_val] = array(
									'id' => str_replace('/', '&#47;', $vs_val),
									'label' => $vs_val,
									'content_count' => $qr_res->get('_count')
								);
							}
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
				case 'violations':
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);

					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';

					$va_facet_values = null;

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
							INNER JOIN ca_metadata_dictionary_rule_violations ON ca_metadata_dictionary_rule_violations.row_id = {$vs_browse_table_name}.".$t_item->primaryKey()." AND ca_metadata_dictionary_rule_violations.table_num = {$vs_browse_table_num}
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
							SELECT COUNT(*) _count, ca_metadata_dictionary_rules.rule_id
							FROM {$vs_browse_table_name}
							INNER JOIN ca_metadata_dictionary_rule_violations ON ca_metadata_dictionary_rule_violations.row_id = {$vs_browse_table_name}.".$t_item->primaryKey()." AND ca_metadata_dictionary_rule_violations.table_num = {$vs_browse_table_num}
							INNER JOIN ca_metadata_dictionary_rules ON ca_metadata_dictionary_rules.rule_id = ca_metadata_dictionary_rule_violations.rule_id
							{$vs_join_sql}
							WHERE
								{$vs_where_sql}
							GROUP BY ca_metadata_dictionary_rules.rule_id
						";

						$qr_res = $this->opo_db->query($vs_sql);

						$va_values = array();
						$t_rule = new ca_metadata_dictionary_rules();
						while($qr_res->nextRow()) {
							if ($t_rule->load($qr_res->get('rule_id'))) {
								if (!($vs_val = trim($t_rule->getSetting('label')))) { continue; }
								$vs_code = $t_rule->get('rule_code');
								if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

								if (isset($va_facet_values[$vs_code])) {
									$va_values[$vs_code] = $va_facet_values[$vs_code];
								} else {
									$va_values[$vs_code] = array(
										'id' => $vs_code,
										'label' => $vs_val,
									    'content_count' => $qr_res->get('_count')
									);
								}
								if (!is_null($vs_single_value) && ($vs_code == $vs_single_value)) {
									$vb_single_value_is_present = true;
								}
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
				case 'checkouts':
					if ($vs_browse_table_name != 'ca_objects') { return array(); }
					$t_item = new ca_objects();

					$va_joins = array();
					$va_wheres = array();
					$vs_where_sql = '';

					$va_facet_values = null;

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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

					$vs_checkout_join_sql = "INNER JOIN ca_object_checkouts ON ca_object_checkouts.object_id = ca_objects.object_id";
					$vn_current_time = time();
					switch($va_facet_info['status']) {
						case 'overdue':
							$va_wheres[] = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL) AND (ca_object_checkouts.due_date <= {$vn_current_time}))";
							break;
						case 'reserved':
							$va_wheres[] = "((ca_object_checkouts.checkout_date IS NULL) AND (ca_object_checkouts.return_date IS NULL))";
							break;
						case 'available':
							$vs_checkout_join_sql = '';
							$va_wheres[] = "(ca_objects.object_id NOT IN (SELECT object_id FROM ca_object_checkouts WHERE (ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL)))";
							break;
						default:
						case 'out':
							$va_wheres[] = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL))";
							break;
					}
					if ($vs_checkout_join_sql) {
						$va_joins[] = $vs_checkout_join_sql;
						$va_joins[] = "INNER JOIN ca_users ON ca_object_checkouts.user_id = ca_users.user_id";
					}

					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
						switch($va_facet_info['mode']) {
							case 'user':
								$vs_sql = "
									SELECT 1
									FROM ca_objects
									{$vs_join_sql}
									WHERE
										{$vs_where_sql} AND ca_objects.deleted = 0
									LIMIT 2";
								break;
							default:
							case 'all':
								$vs_sql = "
									SELECT 1
									FROM ca_objects
									{$vs_join_sql}
									WHERE
										ca_objects.deleted = 0 ".(sizeof($va_results) ? "AND (".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))" : "")."
									LIMIT 2";
								break;
						}

						$qr_res = $this->opo_db->query($vs_sql);

						if ($qr_res->nextRow()) {
							return ((int)$qr_res->numRows() > 0) ? true : false;
						}
						return false;
					} else {
						$va_values = array();

						$vs_pk = $t_item->primaryKey();
						switch($va_facet_info['mode']) {
							case 'user':
								$vs_sql = "
									SELECT COUNT(*) _count, ca_object_checkouts.user_id, ca_users.fname, ca_users.lname, ca_users.email
									FROM ca_objects
									{$vs_join_sql}
									WHERE
										{$vs_where_sql} ".((sizeof($va_results) ? " AND (".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))" : "")).
									" GROUP BY ca_object_checkouts.user_id, ca_users.fname, ca_users.lname, ca_users.email";

								$qr_res = $this->opo_db->query($vs_sql);

								while($qr_res->nextRow()) {
									$vn_user_id = $qr_res->get('user_id');
									$vs_val = $qr_res->get('fname').' '.$qr_res->get('lname').(($vs_email = $qr_res->get('email')) ? "({$vs_email})" : '');
									if ($va_criteria[$vs_val]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

									if (isset($va_facet_values[$vn_user_id])) {
										$va_values[$vn_user_id] = $va_facet_values[$vn_user_id];
									} else {
										$va_values[$vn_user_id] = array(
											'id' => $vn_user_id,
											'label' => $vs_val,
											'count' => $qr_res->get('_count')
										);
									}
									if (!is_null($vs_single_value) && ($vn_user_id == $vs_single_value)) {
										$vb_single_value_is_present = true;
									}
								}
								break;
							case 'all':
							default:
								foreach(array(
									_t('Available') => 'available',
									_t('Out') => 'out',
									_t('Reserved') => 'reserved',
									_t('Overdue') => 'overdue'
								) as $vs_status_text => $vs_status) {
									$vs_join_sql = "INNER JOIN ca_object_checkouts ON ca_object_checkouts.object_id = ca_objects.object_id";
									switch($vs_status) {
										case 'overdue':
											$vs_where = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL) AND (ca_object_checkouts.due_date <= {$vn_current_time}))";
											break;
										case 'reserved':
											$vs_where = "((ca_object_checkouts.checkout_date IS NULL) AND (ca_object_checkouts.return_date IS NULL))";
											break;
										case 'available':
											$vs_join_sql = '';
											$vs_where = "(ca_objects.object_id NOT IN (SELECT object_id FROM ca_object_checkouts WHERE (ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL)))";
											break;
										default:
										case 'out':
											$vs_where = "((ca_object_checkouts.checkout_date <= {$vn_current_time}) AND (ca_object_checkouts.return_date IS NULL))";
											break;
									}

									if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
										$vs_where .= " AND (".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
									}

									$vs_sql = "
										SELECT count(*) c
										FROM ca_objects
										{$vs_join_sql}
										WHERE
											{$vs_where}
									";
									$qr_res = $this->opo_db->query($vs_sql);

									$qr_res->nextRow();
									if (!$qr_res->get('c')) { continue; }
									$va_values[$vs_status] = array(
										'id' => $vs_status,
										'label' => $vs_status_text,
										'count' => $qr_res->get('c')
									);

									if (!is_null($vs_single_value) && ($vs_status == $vs_single_value)) {
										$vb_single_value_is_present = true;
									}
								}
								break;
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
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					
					$va_element_code = explode(".", $va_facet_info['element_code']);
					$t_element = new ca_metadata_elements();

                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					$vb_is_element = $vb_is_field = false;
					if (!($vb_is_element = $t_element->load(array('element_code' => array_pop($va_element_code)))) && !($vb_is_field = ($t_item->hasField($va_facet_info['element_code']) && ($t_item->getFieldInfo($va_facet_info['element_code'], 'FIELD_TYPE') === FT_HISTORIC_DATERANGE)))) {
						return array();
					}
					
					$vs_container_code = (is_array($va_element_code) && (sizeof($va_element_code) > 0)) ? array_pop($va_element_code) : null;
                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, $vs_container_code) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					if ($vb_is_element) {
						$va_joins = array(
							'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
							'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($vs_browse_table_num)
						);
					} else {
						$va_joins = array();
					}

					$va_wheres = array();
					$vs_normalization = $va_facet_info['normalization'];	// how do we construct the date ranges presented to uses. In other words - how do we want to allow users to browse dates? By year, decade, century?

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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

					$vs_where_sql = '';
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}

					$vs_join_sql = join("\n", $va_joins);

					if ($vb_is_element) {
						$vn_element_id = $t_element->getPrimaryKey();

						$vs_dir = (strtoupper($va_facet_info['sort']) === 'DESC') ? "DESC" : "ASC";

						$o_tep = new TimeExpressionParser();
						
						// If a date criterion is already set then force current facet to only return values within the
						// envelope set by the existing criterion.
						if (is_array($va_current_criteria = $this->getCriteria())) {
						    $vn_min = $vn_max = null;
							foreach($va_current_criteria as $vs_criteria_facet => $va_criteria_values) {
								if (is_array($va_criteria_facet_info = $this->getInfoForFacet($vs_criteria_facet))) {
									if ($va_criteria_facet_info['type'] == 'normalizedDates') {
										foreach(array_keys($va_criteria_values) as $vs_date) {
											if ($o_tep->parse($vs_date)) {
											    $va_ts = $o_tep->getHistoricTimestamps();
											 
											    if (is_null($vn_min) || ($va_ts['start'] < $vn_min)) {
												    $va_facet_info['minimum_date'] = $o_tep->getText(['start_as_iso8601' => true]);
												    $vn_min = $va_ts['start'];
												}
												if (is_null($vn_max) || ($va_ts['end'] > $vn_max)) {
												    $va_facet_info['maximum_date'] = $o_tep->getText(['end_as_iso8601' => true]);
												    $vn_max = $va_ts['end'];
												}
											}
										}
									}
								}
							}
						}
						
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
						    $vs_container_sql = '';
                            $va_params = [$vn_element_id];
                            if (is_array($va_container_ids[$vs_container_code]) && sizeof($va_container_ids[$vs_container_code])) {
                                $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
                                $va_params[] = $va_container_ids[$vs_container_code];
                            }
							$vs_sql = "
								SELECT COUNT(*) _count, ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2
								FROM ca_attributes
								{$vs_join_sql}
								WHERE
									ca_attribute_values.element_id = ?
									{$vs_min_sql}
									{$vs_max_sql}
									{$vs_where_sql}
									{$vs_container_sql}
								GROUP BY ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2
							";
							//print $vs_sql;
							$qr_res = $this->opo_db->query($vs_sql, $va_params);

							$vn_current_year = (int)date("Y");
							$va_values = array();

							$vb_include_unknown = (bool)caGetOption('include_unknown', $va_facet_info, false);
							$vb_unknown_is_set = false;

							while($qr_res->nextRow()) {
								$vn_start = $qr_res->get('value_decimal1');
								$vn_end = $qr_res->get('value_decimal2');
								
								if (((int)$vn_start === -2000000000) && $va_facet_info['treat_before_dates_as_circa']) {
								    $vn_start = (int)$vn_end;
								}
								if (((int)$vn_end === 2000000000) && $va_facet_info['treat_after_dates_as_circa']) {
								    $vn_end = (int)$vn_start + .1231235959;
								}
								
								if (!($vn_start && $vn_end)) {
									if ($vb_include_unknown) {
										$vb_unknown_is_set = true;
									}
									continue;
								}
								if ($vn_end > $vn_current_year + 50) { continue; } // bad years can make for large facets that cause timeouts so cut it off 50 years into the future
								$va_normalized_values = $o_tep->normalizeDateRange($vn_start, $vn_end, $vs_normalization);
								foreach($va_normalized_values as $vn_sort_value => $vs_normalized_value) {
									if ($va_criteria[$vs_normalized_value]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on


									if (is_numeric($vs_normalized_value) && (int)$vs_normalized_value === 0) { continue; }		// don't include year=0
									
									if (!isset($va_values[$vn_sort_value][$vs_normalized_value])) {
                                        $va_values[$vn_sort_value][$vs_normalized_value] = array(
                                            'id' => $vs_normalized_value,
                                            'label' => $vs_normalized_value,
                                            'content_count' => (int)$qr_res->get('_count')
                                        );
                                    } else {
                                         $va_values[$vn_sort_value][$vs_normalized_value]['content_count'] += $qr_res->get('_count');
                                    }
									if (!is_null($vs_single_value) && ($vs_normalized_value == $vs_single_value)) {
										$vb_single_value_is_present = true;
									}
								}
							}

							if ($vb_include_unknown && !$vb_unknown_is_set) {
								// Check for rows where no data is set at all as opposed to null dates
								$vs_sql = "
									SELECT DISTINCT ca_attributes.row_id
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
								if ($qr_res->numRows() < sizeof($va_results)) {
									$vb_unknown_is_set = true;
								}
							}
							if ($vb_unknown_is_set && (sizeof($va_values) > 0)) {
							    if(!isset($va_values['999999999'][_t('Date unknown')])) { 
                                    $va_values['999999999'][_t('Date unknown')] = array(
                                        'id' => 'null',
                                        'label' => _t('Date unknown'),
                                        'content_count' => $qr_res->numRows()
                                    );
                                } else {
                                     $va_values['999999999'][_t('Date unknown')]['content_count'] += (int)$qr_res->numRows();
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
					} else {
						// is intrinsic
						$vs_dir = (strtoupper($va_facet_info['sort']) === 'DESC') ? "DESC" : "ASC";

						$vs_browse_start_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'START');
						$vs_browse_end_fld = $t_item->getFieldInfo($va_facet_info['element_code'], 'END');

						$o_tep = new TimeExpressionParser();
						$vn_min_date = $vn_max_date = null;
						$vs_min_sql = $vs_max_sql = '';
						if (isset($va_facet_info['minimum_date'])) {
							if ($o_tep->parse($va_facet_info['minimum_date'])) {
								$va_tmp = $o_tep->getHistoricTimestamps();
								$vn_min_date = (float)$va_tmp['start'];
								$vs_min_sql = " AND ({$vs_browse_table_name}.{$vs_browse_start_fld} >= {$vn_min_date})";
							}
						}
						if (isset($va_facet_info['maximum_date'])) {
							if ($o_tep->parse($va_facet_info['maximum_date'])) {
								$va_tmp = $o_tep->getHistoricTimestamps();
								$vn_max_date = (float)$va_tmp['end'];
								$vs_max_sql = " AND ({$vs_browse_table_name}.{$vs_browse_end_fld} <= {$vn_max_date})";
							}
						}

						if ($vb_check_availability_only) {
							$vs_sql = "
								SELECT 1
								FROM {$vs_browse_table_name}
								{$vs_join_sql}
								WHERE
									1 = 1
									{$vs_min_sql}
									{$vs_max_sql}
									{$vs_where_sql}
									LIMIT 1";
							//print $vs_sql;
							$qr_res = $this->opo_db->query($vs_sql);

							return ((int)$qr_res->numRows() > 0) ? true : false;
						} else {
							$vs_sql = "
								SELECT COUNT(*) _count, {$vs_browse_table_name}.{$vs_browse_start_fld}, {$vs_browse_table_name}.{$vs_browse_end_fld}
								FROM {$vs_browse_table_name}
								{$vs_join_sql}
								WHERE
									1 = 1
									{$vs_min_sql}
									{$vs_max_sql}
									{$vs_where_sql}
								GROUP BY {$vs_browse_table_name}.{$vs_browse_start_fld}, {$vs_browse_table_name}.{$vs_browse_end_fld}
							";
							//print $vs_sql;
							$qr_res = $this->opo_db->query($vs_sql);

							$va_values = array();
							while($qr_res->nextRow()) {
								$vn_start = $qr_res->get($vs_browse_start_fld);
								$vn_end = $qr_res->get($vs_browse_end_fld);

								if (!($vn_start && $vn_end)) { continue; }
								$va_normalized_values = $o_tep->normalizeDateRange($vn_start, $vn_end, $vs_normalization);
								foreach($va_normalized_values as $vn_sort_value => $vs_normalized_value) {
									if ($va_criteria[$vs_normalized_value]) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

									if (is_numeric($vs_normalized_value) && (int)$vs_normalized_value === 0) { continue; }		// don't include year=0
									if(!isset($va_values[$vn_sort_value][$vs_normalized_value])) {
                                        $va_values[$vn_sort_value][$vs_normalized_value] = array(
                                            'id' => $vs_normalized_value,
                                            'label' => $vs_normalized_value,
                                            'content_count' => $qr_res->get('_count')
                                        );
                                    } else {
                                        $va_values[$vn_sort_value][$vs_normalized_value]['content_count'] += (int)$qr_res->get('_count');
                                    }
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
					}
					break;
				# -----------------------------------------------------
				case 'normalizedLength':
					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);
					
					$va_element_code = explode(".", $va_facet_info['element_code']);
					$t_element = new ca_metadata_elements();
					
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, array_shift(explode(".", $va_facet_info['element_code']))) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }


					$vb_is_element = $vb_is_field = false;
					if (!($vb_is_element = $t_element->load(array('element_code' => array_pop($va_element_code)))) && !($vb_is_field = ($t_item->hasField($va_facet_info['element_code']) && ($t_item->getFieldInfo($va_facet_info['element_code'], 'FIELD_TYPE') === FT_HISTORIC_DATERANGE)))) {
						return array();
					}
					
					$vs_container_code = (is_array($va_element_code) && (sizeof($va_element_code) > 0)) ? array_pop($va_element_code) : null;
                    if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, $vs_container_code) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					
					if ($vb_is_element) {
						$va_joins = array(
							'INNER JOIN ca_attribute_values ON ca_attributes.attribute_id = ca_attribute_values.attribute_id',
							'INNER JOIN '.$vs_browse_table_name.' ON '.$vs_browse_table_name.'.'.$t_item->primaryKey().' = ca_attributes.row_id AND ca_attributes.table_num = '.intval($vs_browse_table_num)
						);
					} else {
						$va_joins = array();
					}

					$va_wheres = array();
					$vs_normalization = $va_facet_info['normalization'];	// how do we construct the dimensions ranges presented to users. In other words - what increments do we can to use to  browse measurments?

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
						$va_wheres[] = "(".$t_subject->tableName().'.'.$t_subject->primaryKey()." IN (".join(',', $va_results)."))";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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

					$vs_where_sql = '';
					if (is_array($va_wheres) && sizeof($va_wheres) && ($vs_where_sql = join(' AND ', $va_wheres))) {
						$vs_where_sql = ' AND ('.$vs_where_sql.')';
					}



					$vs_join_sql = join("\n", $va_joins);

					$vn_element_id = $t_element->getPrimaryKey();

					$vs_dir = (strtoupper($va_facet_info['sort']) === 'DESC') ? "DESC" : "ASC";

					$vs_min_sql = $vs_max_sql = '';
					$vo_minimum_dimension = caParseLengthDimension(caGetOption('minimum_dimension', $va_facet_info, "0 in"));
					$vo_maximum_dimension = caParseLengthDimension(caGetOption('maximum_dimension', $va_facet_info, "0 in"));
					if ($vo_minimum_dimension) {
						$vn_tmp = (float)$vo_minimum_dimension->convertTo('METER', 6, 'en_US');
						$vs_min_sql = " AND (ca_attribute_values.value_decimal1 >= {$vn_tmp})";
					}
					if (caGetOption('maximum_dimension', $va_facet_info, null) && $vo_maximum_dimension) {
						$vn_tmp = (float)$vo_maximum_dimension->convertTo('METER', 6, 'en_US');
						$vs_max_sql = " AND (ca_attribute_values.value_decimal1 <= {$vn_tmp})";
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
					    $vs_container_sql = '';
                        $va_params = [$vn_element_id];
                        if (is_array($va_container_ids[$vs_container_code]) && sizeof($va_container_ids[$vs_container_code])) {
                            $vs_container_sql = " AND ca_attributes.attribute_id IN (?)";
                            $va_params[] = $va_container_ids[$vs_container_code];
                        }
						$vs_sql = "
							SELECT COUNT(*) _count, ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2, ca_attribute_values.value_longtext1, ca_attribute_values.value_longtext2, ca_attributes.attribute_id
							FROM ca_attributes
							{$vs_join_sql}
							WHERE
								ca_attribute_values.element_id = ?
								{$vs_min_sql}
								{$vs_max_sql}
								{$vs_where_sql}
								{$vs_container_sql}
							GROUP BY ca_attribute_values.value_decimal1, ca_attribute_values.value_decimal2, ca_attribute_values.value_longtext1, ca_attribute_values.value_longtext2
						";
						//print $vs_sql;
						$qr_res = $this->opo_db->query($vs_sql, $va_params);

						$va_values = array();

						if (!($vs_output_units = caGetLengthUnitType($vs_units=caGetOption('units', $va_facet_info, 'm')))) {
							$vs_output_units = Zend_Measure_Length::METER;
						}

						$vs_increment = caGetOption('increment', $va_facet_info, '1 m');
						$vo_increment = caParseLengthDimension($vs_increment);
						$vn_increment_in_current_units = (float)$vo_increment->convertTo($vs_output_units, 6, 'en_US');

						while($qr_res->nextRow()) {
							$vn_meters = $qr_res->get('value_decimal1');	// measurement in meters

							// convert to target dimensions

							// normalize
							$vo_dim = new Zend_Measure_Length($vn_meters, Zend_Measure_Length::METER, 'en_US');
							$vs_dim = $vo_dim->convertTo($vs_output_units, 6, 'en_US');
							$vn_dim = (float)$vs_dim;

							$vn_normalized = (floor($vn_dim/$vn_increment_in_current_units) * $vn_increment_in_current_units);
							if (isset($va_criteria[$vn_normalized])) { continue; }
							$vs_normalized_range_with_units = "{$vn_normalized} {$vs_units} - ".($vn_normalized + $vn_increment_in_current_units)." {$vs_units}";
							$va_values[$vn_normalized][$vn_normalized] = array(
								'id' => $vn_normalized,
								'label' => $vs_normalized_range_with_units,
								'content_count' => $qr_res->get('_count')
							);
							if (!is_null($vs_single_value) && ($vn_normalized == $vs_single_value)) {
								$vb_single_value_is_present = true;
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
			    case 'relationship_types':  
					$vs_rel_table_name = $va_facet_info['table'];
					$va_params = $this->opo_ca_browse_cache->getParameters();
					
					if ($t_user && $t_user->isLoaded() && ($t_user->getBundleAccessLevel($vs_browse_table_name, $vs_rel_table_name) < __CA_BUNDLE_ACCESS_READONLY__)) { return []; }
					

					// Make sure we honor type restrictions for the related authority
					$va_user_type_restrictions = caGetTypeRestrictionsForUser($vs_rel_table_name);
					$va_restrict_to_types = $va_facet_info['restrict_to_types'];
					if(is_array($va_user_type_restrictions)){
						if (!is_array($va_restrict_to_types)) {
							$va_restrict_to_types = $va_user_type_restrictions;
						} else {
							$va_restrict_to_types = array_intersect($va_restrict_to_types, $va_user_type_restrictions);
						}
					}
					
					$va_sql_params = [];

					if (!is_array($va_exclude_types = $va_facet_info['exclude_types'])) { $va_exclude_types = array(); }
					if (!is_array($va_restrict_to_relationship_types = $va_facet_info['restrict_to_relationship_types'])) { $va_restrict_to_relationship_types = array(); }
					if (!is_array($va_exclude_relationship_types = $va_facet_info['exclude_relationship_types'])) { $va_exclude_relationship_types = array(); }

					$t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true);

					if ($vs_browse_table_name == $vs_rel_table_name) {
						// browsing on self-relations not supported
						break;
					} else {
						switch(sizeof($va_path = array_keys(Datamodel::getPath($vs_browse_table_name, $vs_rel_table_name)))) {
							case 3:
								$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
								$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
								$vs_key = 'relation_id';
								break;
							case __CA_ATTRIBUTE_VALUE_DATERANGE__:
								$t_item_rel = null;
								$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
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
					if(!is_array($va_restrict_to_types = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item, 'dontExpandHierarchically' => true)))) { $va_restrict_to_types = array(); }

					if(!is_array($va_exclude_types = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item, 'dontExpandHierarchically' => true)))) { $va_exclude_types = array(); }

					$va_restrict_to_types_expanded = $this->_convertTypeCodesToIDs($va_restrict_to_types, array('instance' => $t_rel_item));
					$va_exclude_types_expanded = $this->_convertTypeCodesToIDs($va_exclude_types, array('instance' => $t_rel_item));

					// look up relationship type restrictions
					$va_restrict_to_relationship_types = $this->_getRelationshipTypeIDs($va_restrict_to_relationship_types, $va_facet_info['relationship_table']);
					$va_exclude_relationship_types = $this->_getRelationshipTypeIDs($va_exclude_relationship_types, $va_facet_info['relationship_table']);
					
					$va_joins = $va_selects = $va_select_flds =  $va_wheres = $va_orderbys = [];

if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {
					$vs_cur_table = array_shift($va_path);

					foreach($va_path as $vs_join_table) {
						$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
						$vs_cur_table = $vs_join_table;
					}
} else {
					if ($va_facet_info['show_all_when_first_facet']) {
						$va_path = array_reverse($va_path);		// in "show_all" mode we turn the browse on it's head and grab records by the "subject" table, rather than the browse table
						$vs_cur_table = array_shift($va_path);
						$vs_join_table = $va_path[0];
						$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
						$va_joins[] = 'LEFT JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";

					}
}

					if (is_array($va_results) && sizeof($va_results) && ($this->numCriteria() > 0)) {
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
						$va_wheres[] = "{$vs_rel_table_name}.type_id IN (".join(',', caGetOption('dont_include_subtypes', $va_facet_info, false) ? $va_restrict_to_types : $va_restrict_to_types_expanded).")".($t_rel_item->getFieldInfo('type_id', 'IS_NULL') ? " OR ({$vs_rel_table_name}.type_id IS NULL)" : '');
						
						if($va_facet_info['type'] !== 'relationship_types') {
						    $va_selects[] = "{$vs_rel_table_name}.type_id";
						}
					}
					
					if($va_facet_info['type'] == 'relationship_types') {
					    $va_selects[] = "ca_relationship_types.type_id rel_type_id";
					    $va_selects[] = "ca_relationship_types.type_code";
					    $va_selects[] = "ca_relationship_type_labels.typename";
					    $va_joins[] = "INNER JOIN ca_relationship_types ON ".$t_item_rel->tableName().".type_id = ca_relationship_types.type_id";
					    $va_joins[] = "INNER JOIN ca_relationship_type_labels ON ca_relationship_type_labels.type_id = ca_relationship_types.type_id";
					}

					if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && method_exists($t_rel_item, "getTypeList")) {
						$va_wheres[] = "{$vs_rel_table_name}.type_id NOT IN (".join(',', caGetOption('dont_include_subtypes', $va_facet_info, false) ? $va_exclude_types : $va_exclude_types_expanded).")";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
						$va_wheres[] = "(".$t_rel_item->tableName().".access IN (".join(',', $pa_options['checkAccess'])."))";				// exclude non-accessible authority items
						if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {
							$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";		// exclude non-accessible browse items
						}
					}
					
					if ($t_item_rel && isset($va_facet_info['filter_on_interstitial']) && is_array($va_facet_info['filter_on_interstitial']) && sizeof($va_facet_info['filter_on_interstitial'])) {
						foreach($va_facet_info['filter_on_interstitial'] as $vs_field_name => $va_values) {
							if (!$va_values) { continue; }
							if (!is_array($va_values)) { $va_values = [$va_values]; }
							
							if (!($vn_element_id = (int)ca_metadata_elements::getElementID($vs_field_name))) { continue; }
							if (!($o_value = Attribute::getValueInstance(ca_metadata_elements::getElementDatatype($vs_field_name)))) { continue; }
							
							$va_element_value = $o_value->parseValue($va_values[0], array_merge(ca_metadata_elements::getElementSettingsForId($vs_field_name), ['list_id' => ca_metadata_elements::getElementListID($vs_field_name)], ['matchOn' => ['idno']]));
					
							$va_joins[] = "INNER JOIN ca_attributes c_a ON c_a.row_id = ".$t_item_rel->primaryKey(true)." AND c_a.table_num = ".$t_item_rel->tableNum();
							$va_joins[] = "INNER JOIN ca_attribute_values c_av ON c_a.attribute_id = c_av.attribute_id";
							$va_wheres[] = "c_av.element_id = {$vn_element_id} AND ".(isset($va_element_value['item_id']) ? "c_av.item_id = ?" : "c_av.value_longtext1 = ?");
						
							$va_sql_params[] = (isset($va_element_value['item_id'])) ? (int)$va_element_value['item_id'] : $va_element_value['value_longtext1'];
						}
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
//if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {
					//$va_selects[] = $t_item->tableName().'.'.$t_item->primaryKey();			// get primary key of subject
//}
					$va_selects[] = $t_rel_item->tableName().'.'.$vs_rel_pk;				// get primary key of related


					$vs_hier_parent_id_fld = $vs_hier_id_fld = null;
					if ($vb_rel_is_hierarchical) {
						$vs_hier_parent_id_fld = $t_rel_item->getProperty('HIERARCHY_PARENT_ID_FLD');
						$va_selects[] = $t_rel_item->tableName().'.'.$vs_hier_parent_id_fld;

						if ($vs_hier_id_fld = $t_rel_item->getProperty('HIERARCHY_ID_FLD')) {
							$va_selects[] = $t_rel_item->tableName().'.'.$vs_hier_id_fld;
						}
					}
					
					$va_select_flds = $va_selects;

					// analyze group_fields (if defined) and add them to the query
					$va_groupings_to_fetch = array();
					if (isset($va_facet_info['groupings']) && is_array($va_facet_info['groupings']) && sizeof($va_facet_info['groupings'])) {
						foreach($va_facet_info['groupings'] as $vs_grouping => $vs_grouping_name) {
							// is grouping type_id?
							if (($vs_grouping === 'type') && $t_rel_item->hasField('type_id')) {
								$va_selects[] = $t_rel_item->tableName().'.type_id';
								$va_select_flds[] = $t_rel_item->tableName().'.type_id';
								$va_groupings_to_fetch[] = 'type_id';
							}

							// is group field a relationship type?
							if ($vs_grouping === 'relationship_types') {
								$va_selects[] = $va_facet_info['relationship_table'].'.type_id rel_type_id';
								$va_select_flds[] = $va_facet_info['relationship_table'].'.type_id';
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
						// TODO: do this everywhere
						//$va_restrict_to_relationship_types = array();
						//$vs_browse_type_limit_sql = '';

						if ($t_subject->hasField('deleted')) {
							$va_wheres[] = "(".$t_subject->tableName().".deleted = 0)";
						}
						if ($va_relative_sql_data = $this->_getRelativeFacetSQLData($va_facet_info['relative_to'], $pa_options)) {
							$va_joins = array_merge($va_joins, $va_relative_sql_data['joins']);
							$va_wheres = array_merge($va_wheres, $va_relative_sql_data['wheres']);
						}
					}


					if (is_array($va_restrict_to_relationship_types) && (sizeof($va_restrict_to_relationship_types) > 0) && is_object($t_item_rel)) {
						$va_wheres[] = $t_item_rel->tableName().".type_id IN (".join(',', $va_restrict_to_relationship_types).")";
					}
					if (is_array($va_exclude_relationship_types) && (sizeof($va_exclude_relationship_types) > 0) && is_object($t_item_rel)) {
						$va_wheres[] = $t_item_rel->tableName().".type_id NOT IN (".join(',', $va_exclude_relationship_types).")";
					}
					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
					}

					if ($this->opo_config->get('perform_item_level_access_checking')) {
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {

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
					
					if (is_array($va_criteria) && sizeof($va_criteria) > 0) {
						$va_wheres[] = "(".$t_rel_item->tableName().".".$t_rel_item->primaryKey()." NOT IN (".join(",", caQuoteList(array_map("intval", array_keys($va_criteria))))."))";	
					}

					$vs_join_sql = join("\n", $va_joins);
					if ($vb_check_availability_only) {
	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {
						$vs_sql = "
							SELECT 1
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	} else {
						$vs_sql = "
							SELECT 1
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)." LIMIT 1";
	}
						$qr_res = $this->opo_db->query($vs_sql, $va_sql_params);
						//print "<hr>$vs_sql<hr>\n";

						return ((int)$qr_res->numRows() > 0) ? true : false;
					} else {

	if (!$va_facet_info['show_all_when_first_facet'] || ($this->numCriteria() > 0)) {
						$vs_sql = "
							SELECT COUNT(*) _count, ".join(', ', $va_selects)."
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	} else {
						$vs_sql = "
							SELECT COUNT(*) _count, ".join(', ', $va_selects)."
							FROM ".$t_rel_item->tableName()."
							{$vs_join_sql}
								".(sizeof($va_wheres) ? ' WHERE ' : '').join(" AND ", $va_wheres)."
								".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '');
	}                  
	                    $vs_sql .= " GROUP BY ".join(', ', $va_select_flds);
						//print "<hr>$vs_sql<hr>\n";
						$qr_res = $this->opo_db->query($vs_sql, $va_sql_params);

						$va_facet = $va_facet_items = array();
						$vs_rel_pk = $t_rel_item->primaryKey();

						// First get related ids with type and relationship type values
						// (You could get all of the data we need for the facet in a single query but it turns out to be faster for very large facets to
						// do it in separate queries, one for the primary ids and another for the labels; a third is done if attributes need to be fetched.
						// There appears to be a significant [~10%] performance for smaller facets and a larger one [~20-25%] for very large facets)

						$vn_max_level = caGetOption('maximum_levels', $va_facet_info, null);
						while($qr_res->nextRow()) {
							$va_fetched_row = $qr_res->getRow();
							$vn_id = $va_fetched_row[$vs_rel_pk];
							//if (isset($va_facet_items[$vn_id])) { continue; } --- we can't do this as then we don't detect items that have multiple rel_type_ids... argh.
							if (isset($va_criteria[$vn_id])) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

                            if($va_facet_info['type'] === 'relationship_types') {
                                if(sizeof($va_restrict_to_relationship_types) && !in_array($va_fetched_row['rel_type_id'], $va_restrict_to_relationship_types)) { continue; }
                                if(sizeof($va_exclude_relationship_types) && in_array($va_fetched_row['rel_type_id'], $va_exclude_relationship_types)) { continue; }
                                $va_facet_items[$va_fetched_row['rel_type_id']] = [
                                    'label' => $va_fetched_row['typename'],
                                    'id' => $va_fetched_row['rel_type_id']
                                ];
                                continue;
                            }

							if (!$va_facet_items[$va_fetched_row[$vs_rel_pk]]) {

								if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types) && $va_fetched_row['type_id'] && !in_array($va_fetched_row['type_id'], $va_restrict_to_types)) {
									continue;
								}

								$va_facet_items[$va_fetched_row[$vs_rel_pk]] = array(
									'id' => $va_fetched_row[$vs_rel_pk],
									'type_id' => array(),
									'parent_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_parent_id_fld] : null,
									'hierarchy_id' => $vb_rel_is_hierarchical ? $va_fetched_row[$vs_hier_id_fld] : null,
									'rel_type_id' => array(),
									'child_count' => 0,
									'content_count' => $va_fetched_row['_count']
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
						
						if($va_facet_info['type'] === 'relationship_types') {
						    return $va_facet_items;
						}

		if (!isset($va_facet_info['dont_expand_hierarchically']) || !$va_facet_info['dont_expand_hierarchically']) {
						$qr_res->seek(0);
						$va_ids = $qr_res->getAllFieldValues($vs_rel_pk);
						$qr_ancestors = call_user_func($t_rel_item->tableName().'::getHierarchyAncestorsForIDs', $va_ids, array('returnAs' => 'SearchResult'));

						$vs_rel_table = $t_rel_item->tableName();
						$vs_rel_pk = $t_rel_item->primaryKey();

						$vb_check_ancestor_access = (bool)(isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access'));

						if($qr_ancestors) {
							while($qr_ancestors->nextHit()) {
								if ($qr_ancestors->get("{$vs_rel_table}.deleted")) { continue; }
								if (!($vn_parent_type_id = $qr_ancestors->get('type_id'))) { continue; }
								
								$vn_ancestor_id = (int)$qr_ancestors->get("{$vs_rel_pk}");
								if (isset($va_criteria[$vn_ancestor_id])) { continue; }		// skip items that are used as browse critera - don't want to browse on something you're already browsing on

								if (is_array($va_exclude_types) && (sizeof($va_exclude_types) > 0) && in_array($vn_parent_type_id, $va_exclude_types)) { continue; }
								if (is_array($va_restrict_to_types) && (sizeof($va_restrict_to_types) > 0) && !in_array($vn_parent_type_id, $va_restrict_to_types)) { continue; }
								if ($vb_check_ancestor_access && !in_array($qr_ancestors->get('access'), $pa_options['checkAccess'])) { continue; }

								$va_facet_items[$vn_ancestor_id] = array(
									'id' => $vn_ancestor_id,
									'type_id' => array(),
									'parent_id' => $vb_rel_is_hierarchical ? $qr_ancestors->get("{$vs_hier_parent_id_fld}") : null,
									'hierarchy_id' => ($vb_rel_is_hierarchical && $vs_hier_id_fld) ? $qr_ancestors->get($vs_hier_id_fld) : null,
									'rel_type_id' => array(),
									'child_count' => 0
								);
							}
						}
		}


						// Set child counts
						foreach($va_facet_items as $vn_i => $va_item) {
							if ($va_item['parent_id'] && isset($va_facet_items[$va_item['parent_id']])) {
								$va_facet_items[$va_item['parent_id']]['child_count']++;
							}
						}

                        $natural_sort = caGetOption('natural_sort', $va_facet_info, false);
                        
						// Get labels for facet items
						if (sizeof($va_row_ids = array_keys($va_facet_items))) {
							if ($vs_label_table_name = $t_rel_item->getLabelTableName()) {
								$t_rel_item_label = Datamodel::getInstanceByTableName($vs_label_table_name, true);
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
									if ($t_rel_item_label->hasField($vs_sort_by_field)) { 
										$va_orderbys[] = $va_label_selects[] = $vs_label_table_name.'.'.$vs_sort_by_field;
									} elseif($t_rel_item->hasField($vs_sort_by_field)) {
										$va_orderbys[] = $va_label_selects[] = $t_rel_item->tableName().'.'.$vs_sort_by_field;
									}
								}

								// get labels
								$vs_sql = "
									SELECT ".join(', ', $va_label_selects)."
									FROM {$vs_label_table_name}
									INNER JOIN ".$t_rel_item->tableName()." ON ".$t_rel_item->tableName().".{$vs_rel_pk} = {$vs_label_table_name}.{$vs_rel_pk}
										".(sizeof($va_label_wheres) ? ' WHERE ' : '').join(" AND ", $va_label_wheres)."
										".(sizeof($va_orderbys) ? "ORDER BY ".join(', ', $va_orderbys) : '')."";
								//print $vs_sql;
								$qr_labels = $this->opo_db->query($vs_sql);

								while($qr_labels->nextRow()) {
									$va_fetched_row = $qr_labels->getRow();
									
									$label_values = ['label' => $va_fetched_row[$vs_label_display_field]];
									if ($natural_sort) { $label_values['label_sort_'] = caSortableValue($va_fetched_row[$vs_label_display_field]); }
									
									$va_facet_item = array_merge($va_facet_items[$va_fetched_row[$vs_rel_pk]], $label_values);

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
						
						if ($natural_sort) {
						    return caSortArrayByKeyInValue(caExtractValuesByUserLocale($va_facet), ['label']);
						}
						return caExtractValuesByUserLocale($va_facet);
					}
					break;
				# -----------------------------------------------------
				case 'dupeidno':
					//select count(*) c, idno from ca_objects where deleted = 0 group by idno having c > 1;
					
					if (isset($va_all_criteria[$ps_facet_name])) { break; }		// only one instance of this facet allowed per browse

					if (!($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true))) { break; }
						
					$idno_fld = $t_item->getProperty('ID_NUMBERING_ID_FIELD');
					
					$va_facet = $va_counts = $va_wheres = $va_joins = [];


					if ($t_item->hasField('deleted')) {
						$va_wheres[] = "(".$t_item->tableName().".deleted = 0)";
					}

					if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_item->hasField('access')) {
						$va_wheres[] = "(".$vs_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
					}

					if (is_array($va_results) && sizeof($va_results)) {
						$va_wheres[] = $vs_browse_table_name.".".$t_item->primaryKey()." IN (".join(",", $va_results).")";
					}
					

					if ($vs_browse_type_limit_sql) {
						$va_wheres[] = $vs_browse_type_limit_sql;
					}

					if ($vs_browse_source_limit_sql) {
						$va_wheres[] = $vs_browse_source_limit_sql;
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
						if ($t_item = Datamodel::getInstanceByTableName($vs_browse_table_name, true)) {
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
					if (is_array($va_wheres) && sizeof($va_wheres) > 0) {
						$vs_where_sql = ' WHERE '.join(' AND ', $va_wheres);
					}

					if ($vb_check_availability_only) {
						$vs_sql = "
							SELECT count(*) c, {$vs_browse_table_name}.{$idno_fld} 
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
							{$vs_where_sql}
							GROUP BY {$vs_browse_table_name}.{$idno_fld}  
							HAVING c > 1
							LIMIT 2
						";
						//print "$vs_sql<hr>";
						$qr_res = $this->opo_db->query($vs_sql);
						if ($qr_res->nextRow()) {
							return true;
						}
					} else {
						$vs_sql = "
							SELECT count(*) c, {$vs_browse_table_name}.{$idno_fld} 
							FROM {$vs_browse_table_name}
							{$vs_join_sql}
							{$vs_where_sql}
							GROUP BY {$vs_browse_table_name}.{$idno_fld}
							HAVING c > 1";
						//print "$vs_sql<hr>";
						$qr_res = $this->opo_db->query($vs_sql);
						while($qr_res->nextRow()) {
							$c = $qr_res->get('c');
							
							if (isset($va_facet[$c])) {
								$va_facet[$c]['content_count']++;
							} else {
								$va_facet[$c] = [
									'label' => _t('%1 repeats', $c),
									'id' => $c,
									'content_count' => 1
								];
							}
						}
					}

					return $va_facet;
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

			$vs_sort = caGetOption('sort', $pa_options, null);
			$vs_sort_direction = strtolower(caGetOption('sortDirection', $pa_options, caGetOption('sort_direction', $pa_options, null)));

			$t_item = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true);
			$vb_will_sort = ($vs_sort && (($this->getCachedSortSetting() != $vs_sort) || ($this->getCachedSortDirectionSetting() != $vs_sort_direction)));

			$vs_pk = $t_item->primaryKey();
			$vs_label_display_field = null;

			if(is_array($va_results =  $this->opo_ca_browse_cache->getResults()) && sizeof($va_results)) {
				if ($vb_will_sort) {
					$va_results = $this->sortHits($va_results, $this->ops_browse_table_name, $vs_sort, $vs_sort_direction);

					$this->opo_ca_browse_cache->setParameter('table_num', $this->opn_browse_table_num);
					$this->opo_ca_browse_cache->setParameter('sort', $vs_sort);
					$this->opo_ca_browse_cache->setParameter('sort_direction', $vs_sort_direction);

					$this->opo_ca_browse_cache->setResults($va_results);
					$this->opo_ca_browse_cache->save();
				}

				$vn_start = (int) caGetOption('start', $pa_options, 0);
				$vn_limit = (int) caGetOption('limit', $pa_options, 0);
				if (($vn_start > 0) || ($vn_limit > 0)) {
					$va_results = array_slice($va_results, $vn_start, $vn_limit);
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
			if (!in_array($ps_operator, array('=', '<', '>', '<=', '>=', 'in', 'not in', 'is', 'is not'))) { return false; }
			$t_table = Datamodel::getInstanceByTableName($this->ops_tablename, true);
			$va_tmp = explode(".", $ps_field);
			$ps_field = array_pop($va_tmp);
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
		 *		dontExpandHierarchically =
		 * @return boolean True on success, false on failure
		 */
		public function setTypeRestrictions($pa_type_codes_or_ids, $pa_options=null) {
			$this->opa_browse_type_ids = $this->_convertTypeCodesToIDs($pa_type_codes_or_ids, array('dontExpandHierarchically' => caGetOption('dontExpandHierarchically', $pa_options, false)));
			$this->opo_ca_browse_cache->setTypeRestrictions($this->opa_browse_type_ids);
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 *
		 * @param array $pa_type_codes_or_ids List of type codes or ids
		 * @param array $pa_options Options include
		 *		dontExpandHierarchically =
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
					if ((!caGetOption('dontExpandHierarchically', $pa_options, false)) && !$this->opb_dont_expand_type_restrictions) {
						$t_item = new ca_list_items();
						$va_ids = $t_item->getHierarchy($vn_type_id, array('idsOnly' => true));
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
		# ------------------------------------------------------
		# Source filtering
		# ------------------------------------------------------
		/**
		 * When source restrictions are specified, the browse will only browse upon items with the given sources.
		 * If you specify a source that has hierarchical children then the children will automatically be included
		 * in the restriction. You may pass numeric source_id and alphanumeric source codes interchangeably.
		 *
		 * @param array $pa_source_codes_or_ids List of source_id or code values to filter browse by. When set, the browse will only consider items of the specified sources. Using a hierarchical parent source will automatically include its children in the restriction.
		 * @param array $pa_options Options include
	 	 *		includeSubsources = include any child sources in the restriction. Default is true.
		 * @return boolean True on success, false on failure
		 */
		public function setSourceRestrictions($pa_source_codes_or_ids, $pa_options=null) {
			$this->opa_browse_source_ids = $this->_convertSourceCodesToIDs($pa_source_codes_or_ids);
			$this->opo_ca_browse_cache->setSourceRestrictions($this->opa_browse_source_ids);
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 *
		 * @param array $pa_source_codes_or_ids List of source codes or ids
		 * @param array $pa_options Options include
		 *		includeSubsources = include any child sources in the restriction. Default is true.
		 * @return array List of source_ids
		 */
		private function _convertSourceCodesToIDs($pa_source_codes_or_ids, $pa_options=null) {
			$vs_md5 = caMakeCacheKeyFromOptions($pa_source_codes_or_ids);

			if (isset(BrowseEngine::$s_source_id_cache[$vs_md5])) { return BrowseEngine::$s_source_id_cache[$vs_md5]; }

			if (isset($pa_options['instance']) && is_object($pa_options['instance'])) {
				$t_instance = $pa_options['instance'];
			} else {
				$t_instance = $this->getSubjectInstance();
			}
			$va_source_ids = array();

			if (!$pa_source_codes_or_ids) { return false; }
			if (is_array($pa_source_codes_or_ids) && !sizeof($pa_source_codes_or_ids)) { return false; }
			if (!is_array($pa_source_codes_or_ids)) { $pa_source_codes_or_ids = array($pa_source_codes_or_ids); }

			$t_list = new ca_lists();
			if (!method_exists($t_instance, 'getSourceListCode')) { return false; }
			if (!($vs_list_name = $t_instance->getSourceListCode())) { return false; }
			$va_source_list = $t_instance->getSourceList();

			foreach($pa_source_codes_or_ids as $vs_code_or_id) {
				if (!trim($vs_code_or_id)) { continue; }
				if (!is_numeric($vs_code_or_id)) {
					$vn_source_id = $t_list->getItemIDFromList($vs_list_name, $vs_code_or_id);
				} else {
					$vn_source_id = (int)$vs_code_or_id;
				}

				if (!$vn_source_id) { return false; }

				if (isset($va_source_list[$vn_source_id]) && $va_source_list[$vn_source_id]) {	// is valid source for this subject
					// See if there are any child sources
					if (caGetOption('includeSubsources', $pa_options, true) && $this->opb_dont_expand_source_restrictions) {
						$t_item = new ca_list_items($vn_source_id);
						$va_ids = $t_item->getHierarchyChildren(null, array('idsOnly' => true));
					}
					$va_ids[] = $vn_source_id;
					$va_source_ids = array_merge($va_source_ids, $va_ids);
				}
			}
			$va_source_ids = array_keys(array_flip($va_source_ids));
			BrowseEngine::$s_source_id_cache[$vs_md5] = $va_source_ids;
			return $va_source_ids;
		}
		# ------------------------------------------------------
		/**
		 * Returns list of source_id values to restrict browse to. Return values are always numeric sources,
		 * never codes, and will include all source_ids to filter on, including children of hierarchical sources.
		 *
		 * @return array List of source_id values to restrict browse to.
		 */
		public function getSourceRestrictionList() {
			if (function_exists("caGetSourceRestrictionsForUser")) {
				$va_pervasive_sources = caGetSourceRestrictionsForUser($this->ops_tablename);	// restrictions set in app.conf or by associated user role

				if (!is_array($va_pervasive_sources) || !sizeof($va_pervasive_sources)) { return $this->opa_browse_source_ids; }

				if (is_array($this->opa_browse_source_ids) && sizeof($this->opa_browse_source_ids)) {
					$va_filtered_sources = array();
					foreach($this->opa_browse_source_ids as $vn_id) {
						if (in_array($vn_id, $va_pervasive_sources)) {
							$va_filtered_sources[] = $vn_id;
						}
					}
					return $va_filtered_sources;
				} else {
					return $va_pervasive_sources;
				}
			}
			return $this->opa_browse_source_ids;
		}
		# ------------------------------------------------------
		/**
		 * Removes any specified source restrictions on the browse
		 *
		 * @return boolean Always returns true
		 */
		public function clearSourceRestrictionList() {
			$this->opa_browse_source_ids = null;
			return true;
		}
		# ------------------------------------------------------
		/**
		 * If set source restrictions will not be expanded to include child sources.
		 *
		 * @param bool $pb_value If set to true, source restriction will not be expanded; default is true if omitted
		 *
		 * @return boolean Always returns true
		 */
		public function dontExpandSourceRestrictions($pb_value=true) {
			$this->opb_dont_expand_source_restrictions = (bool)$pb_value;
			return true;
		}
		# ------------------------------------------------------------------
		#
		# ------------------------------------------------------------------
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

				$va_ids = $t_rel_type->getHierarchy($vn_type_id, array('idsOnly' => true));

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
		private function _isParentRelative($ps_relative_to_table) {
			$va_relative_to_tmp = explode('.', $ps_relative_to_table);
			return ($va_relative_to_tmp[1] == 'parent_id') ? $va_relative_to_tmp[0] : false;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		private function _getRelativeFacetSQLData($ps_relative_to_table, $pa_options) {

			$va_joins = $va_wheres = array();
			
			if ($vs_relative_to_table = $this->_isParentRelative($ps_relative_to_table)) {
				$ps_relative_to_table = $vs_relative_to_table;
				
				$va_joins[] = "INNER JOIN {$ps_relative_to_table} ON ".$this->ops_browse_table_name.".parent_id = parent.object_id";
				$va_wheres = [];
				
				return array('joins' => $va_joins, 'wheres' => $va_wheres);
			} 
			switch(sizeof($va_path = array_keys(Datamodel::getPath($ps_relative_to_table, $this->ops_browse_table_name)))) {
				case __CA_ATTRIBUTE_VALUE_LIST__:
					$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
					$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
					$vs_key = 'relation_id';
					break;
				case __CA_ATTRIBUTE_VALUE_DATERANGE__:
					$t_item_rel = null;
					$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
					$vs_key = $t_rel_item->primaryKey();
					break;
				default:
					// bad table
					return null;
					break;
			}

			$vs_cur_table = array_shift($va_path);
			foreach($va_path as $vs_join_table) {
				$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
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
		private function _getRelativeExecuteSQLData($ps_relative_to_table, $pa_options=null) {
			if ($vs_relative_to_table = $this->_isParentRelative($ps_relative_to_table)) {
				$ps_relative_to_table = $vs_relative_to_table;
				
				$va_relative_joins[] = "INNER JOIN {$ps_relative_to_table} AS parent ON ".$this->ops_browse_table_name.".parent_id = parent.object_id";
				$va_wheres = [];
						
				if (!($t_target = Datamodel::getInstanceByTableName($vs_relative_to_table, true))) { return null; }
				$vs_target_browse_table_num = $t_target->tableNum();
				$vs_target_browse_table_pk = $t_target->primaryKey();
				
				return array(
					'joins' => [], 'wheres' => $va_wheres, 'relative_joins' => $va_relative_joins,
					'target_table_name' => $vs_relative_to_table,
					'target_table_num' => $vs_target_browse_table_num,
					'target_table_pk' => $vs_target_browse_table_pk
				);
			}
		
		
			if (!($t_target = Datamodel::getInstanceByTableName($ps_relative_to_table, true))) { return null; }
			$vs_target_browse_table_num = $t_target->tableNum();
			$vs_target_browse_table_pk = $t_target->primaryKey();
			$t_item = Datamodel::getInstanceByTableName($this->ops_browse_table_name, true);

			switch(sizeof($va_path = array_keys(Datamodel::getPath($ps_relative_to_table, $this->ops_browse_table_name)))) {
				case __CA_ATTRIBUTE_VALUE_LIST__:
					$t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
					$t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
					$vs_key = 'relation_id';
					break;
				case __CA_ATTRIBUTE_VALUE_DATERANGE__:
					$t_item_rel = null;
					$t_rel_item = Datamodel::getInstanceByTableName($va_path[1], true);
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
				$va_rel_info = Datamodel::getRelationships($vs_cur_table, $vs_join_table);
				$va_joins[] = 'INNER JOIN '.$vs_join_table.' ON '.$vs_cur_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][0].' = '.$vs_join_table.'.'.$va_rel_info[$vs_cur_table][$vs_join_table][0][1]."\n";
				$vs_cur_table = $vs_join_table;
			}

			if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) && $t_rel_item->hasField('access')) {
				$va_wheres[] = "(".$this->ops_browse_table_name.".access IN (".join(',', $pa_options['checkAccess'])."))";
			}

			$va_relative_to_join = array();

			if ($t_item_rel) { // foo_x_bar Table exists ==> join foo_x_bar and relative_to table
				$va_relative_to_join[] = "INNER JOIN ".$t_item_rel->tableName()." ON ".$t_item_rel->tableName().".".$t_item->primaryKey()." = ".$this->ops_browse_table_name.'.'.$t_item->primaryKey();
				$va_relative_to_join[] = "INNER JOIN {$ps_relative_to_table} ON {$ps_relative_to_table}.{$vs_target_browse_table_pk} = ".$t_item_rel->tableName().".".$t_target->primaryKey();
			} else { // path of length 2, i.e. direct relationship like ca_objects.lot_id = ca_object_lots.lot_id ==> join relative_to and browse target tables directly
				$va_rel_info = Datamodel::getRelationships($ps_relative_to_table, $t_rel_item->tableName());
				$va_relative_to_join[] = "INNER JOIN {$ps_relative_to_table} ON {$ps_relative_to_table}.{$va_rel_info[$t_rel_item->tableName()][$ps_relative_to_table][0][0]} = {$t_rel_item->tableName()}.{$va_rel_info[$ps_relative_to_table][$t_rel_item->tableName()][0][0]}";
			}

			return array(
				'joins' => $va_joins, 'wheres' => $va_wheres, 'relative_joins' => $va_relative_to_join,
				'target_table_name' => $ps_relative_to_table,
				'target_table_num' => $vs_target_browse_table_num,
				'target_table_pk' => $vs_target_browse_table_pk
			);
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		private function getCollapseMapForLocationFacet($pa_facet_info) {
			$va_collapse_map = array();
			if(is_array($pa_facet_info['collapse'])) {
				foreach($pa_facet_info['collapse'] as $vs_selector => $vs_text) {
					$va_selector = explode('/', $vs_selector);
					if (sizeof($va_selector) == 1) {
						$va_collapse_map[$va_selector[0]]['*'] = $vs_text;
					} elseif(sizeof($va_selector) > 1) {
						switch($va_selector[0]) {
							case 'ca_objects_x_storage_locations':
								$t_rel_type = new ca_relationship_types();
								$vn_type_id = $t_rel_type->getRelationshipTypeID('ca_objects_x_storage_locations', $va_selector[1]);
								break;
							default:
								$vn_type_id = null;
								if ($t_instance = Datamodel::getInstanceByTableName($va_selector[0], true)) {
									$vn_type_id = $t_instance->getTypeIDForCode($va_selector[1]);
								}
								break;
						}
						$va_collapse_map[$va_selector[0]][$vn_type_id] = $vs_text;
					}
				}
			}
			return $va_collapse_map;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
	    public function getAvailableRelationshipTypesForCurrentResult($ps_rel_table, $pa_rel_ids, $pa_options=null) {
	        if(!$pa_rel_ids) { return null; }
	        if(!is_array($pa_rel_ids)) { $pa_rel_ids = [$pa_rel_ids]; }
	        
	        $pa_rel_ids = array_map(function($v) { return (int)$v; }, $pa_rel_ids);
	        
	        if(is_array($va_results =  $this->opo_ca_browse_cache->getResults()) && sizeof($va_results)) {
	            switch(sizeof($va_path = array_keys(Datamodel::getPath($this->ops_browse_table_name, $ps_rel_table)))) {
                    case 3:
                        $t_item_rel = Datamodel::getInstanceByTableName($va_path[1], true);
                        $t_rel_item = Datamodel::getInstanceByTableName($va_path[2], true);
                        $vs_key = 'relation_id';
                        break;
                    default:
                        // bad related table
                        return null;
                        break;
                }
                
                $qr = $this->opo_db->query("
                    SELECT DISTINCT ca_relationship_types.*, ca_relationship_type_labels.*
                    FROM {$va_path[1]}
                    INNER JOIN ca_relationship_types ON ca_relationship_types.type_id = {$va_path[1]}.type_id
                    INNER JOIN ca_relationship_type_labels ON ca_relationship_types.type_id = ca_relationship_type_labels.type_id
                    WHERE
                        {$va_path[1]}.".$t_rel_item->primaryKey()." IN (?)
                    GROUP BY ca_relationship_types.type_id
                ", [$pa_rel_ids]);
                
                $result = [];
                while($qr->nextRow()) {
                    $result[] = $qr->getRow();
                }
                
                return $result;
	        }
	            
	        return null;
	    }
		# ------------------------------------------------------
	}
