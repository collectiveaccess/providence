<?php
/** ---------------------------------------------------------------------
 * app/lib/Browse/BrowseManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017-2022 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/BaseFindEngine.php');
require_once(__CA_LIB_DIR__.'/Browse/BrowseCache.php');
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
require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');
require_once(__CA_LIB_DIR__.'/PluginTrait.php');
require_once(__CA_APP_DIR__.'/helpers/searchHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/browseHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');

require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
require_once(__CA_LIB_DIR__.'/Browse/BrowseResult.php');



require_once(__CA_LIB_DIR__."/plugins/Browse/SqlBrowse.php");


/**
 *
 */
class BrowseManager {

    use PluginTrait;

    # ------------------------------------------------------
    /**
     *
     */
    private $db;
    
    /**
     *
     */
    private $table_name;
    
    /**
     *
     */
    private $table_num;
    
    /**
     *
     */
    private $app_config;
    
    /**
     *
     */
    private $browse_config;
    
    /**
     *
     */
    private $browse_settings;
    
    /**
     *
     */
    private $criteria = [];
    
    /**
     *
     */
    private $browse_cache;
    
    /**
     *
     */
    private $criteria_have_changed = false;
    
    /**
     *
     */
    static $s_plugin_names;
    
    # ---- Plugin trait properties
    /**
     *
     */
    static $plugin_path = __CA_LIB_DIR__."/plugins/Browse";
    
    /**
     *
     */
    static $plugin_namespace = "BrowsePlugins";
    
    /**
     *
     */
    static $plugin_exclude_patterns = ["Result\\.php$", "BaseBrowsePlugin\\.php"];
    
    /**
     *
     */
    static $plugin_names = [];
    
    /**
     *
     */
    static $plugin_cache = [];
    
    
    /**
     *
     */
    static $engine;
    
    
    /**
     *
     */
    private static $browseable_tables = ['ca_objects', 'ca_entities'];     // TODO: expand to full set
    
    
    # ------------------------------------------------------
    /**
     *
     */
    public function __construct($subject_table_name_or_num, $browse_key=null, $browse_context='') {
        $this->db = new Db();
        
        if (!($this->table_name = Datamodel::getTableName($subject_table_name_or_num))) {
            throw new ApplicationException(_t('Invalid browse subject'));
        }
        
        $this->table_num = Datamodel::getTableNum($this->table_name);
        
        $this->app_config = Configuration::load();
        $this->browse_config = Configuration::load(__CA_CONF_DIR__.'/browse.conf');
        $this->browse_settings = $this->browse_config->getAssoc($this->table_name);
        
        // Add "virtual" search facet - allows seeding of a browse with a search
        $this->browse_settings['facets']['_search'] = array(
            'label_singular' => _t('Search'),
            'label_plural' => _t('Searches')
        );
        // Add "virtual" relationship types facet - allows filtering of a browse by relationship types in a specific relationships (Eg. only return records that have at least one relationship with one of the specified types)
        $this->browse_settings['facets']['_reltypes'] = array(
            'label_singular' => _t('Relationship type'),
            'label_plural' => _t('Relationship types')
        );
        
        
        
        $this->_processBrowseSettings();
        
        $this->browse_cache = new BrowseCache();
        $this->browse_cache->setParameter('table_num', $this->table_num);
        if ($pn_browse_id) {
            $this->browse_cache->load($pn_browse_id);
        } else {
            $this->setContext($ps_browse_context);
        }
        
        $this->engine = self::getPlugin(($browse_plugin = $this->app_config->get('browse_engine_plugin')) ? $browse_plugin : "SqlBrowse");
    }
    # ------------------------------------------------------
    # Criteria
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
            if (!($va_facet_info = $this->getFacetInfo($ps_facet_name))) { return false; }
            if (!$this->isValidFacetName($ps_facet_name)) { return false; }
        }

        if (!is_array($pa_row_ids)) { $pa_row_ids = array($pa_row_ids); }
        foreach($pa_row_ids as $vn_i => $vn_row_id) {
            $this->criteria[$ps_facet_name][urldecode($vn_row_id)] = true;
        }
        $this->browse_cache->setParameter('criteria', $this->criteria);
        //$this->browse_cache->setParameter('criteria_display_strings', $va_criteria_display_strings);
        $this->browse_cache->setParameter('sort', null);
        $this->browse_cache->setParameter('facet_html', null);

        $this->criteria_have_changed = true;
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

        if($ps_facet_name) {
            return isset($this->criteria[$ps_facet_name]) ? $this->criteria[$ps_facet_name] : null;
        }
        return is_array($this->criteria) ? $this->criteria : null;
    }
    # ------------------------------------------------------
    /**
     * Indicates if the browse criteria have changed since the last execute()
     *
     * @return bool
     */
    public function criteriaHaveChanged() {
        return $this->criteria_have_changed;
    }
    # ------------------------------------------------------
    /**
     * Returns the number of criteria on the current browse
     *
     * @return int
     */
    public function numCriteria() {
        if (is_array($this->criteria) && is_array($this->criteria)) {
            $vn_c = 0;
            foreach($this->criteria as $vn_table_num => $va_criteria_list) {
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

        if($ps_facet_name) {
            $this->criteria[$ps_facet_name] = [];
        } else {
            $this->criteria = [];
        }

        $this->browse_cache->setParameter('criteria', $this->criteria);
        $this->criteria_have_changed = true;

        return true;
    }
    # ------------------------------------------------------
    /**
     * Returns true if facet exists, false if not
     *
     * @param string $ps_facet_name
     * @return bool
     */
    public function isValidFacetName($ps_facet_name) {
        $va_facets = $this->getFacetList();
        return (isset($va_facets[$ps_facet_name])) ? true : false;
    }
    # ------------------------------------------------------
    /**
     * Returns list of all facets configured for this for browse subject
     *
     * @return array
     */
    public function getFacetList() {
        return $this->browse_settings['facets'];
    }
    # ------------------------------------------------------
    /**
     * Return info for specified facet, or null if facet is not valid
     *
     * @param string $ps_facet_name
     * @return array
     */
    public function getFacetInfo($ps_facet_name) {
        if (!$this->isValidFacetName($ps_facet_name)) { return null; }
        $va_facets = $this->getFacetList();
        return isset($va_facets[$ps_facet_name]) ? $va_facets[$ps_facet_name] : null;
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function removeCriteria() {
    
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function clearAllCriteria() {
    
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function getBrowseID() {
        return md5(
            $this->table_name."/".
            print_r($this->getCriteria(), true)
        );
    }
    # ------------------------------------------------------
    # Facets
    # ------------------------------------------------------
    /**
     *
     */
    public function getFacets() {
    
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function getFacetContent($ps_facet_name, $pa_options=null) {
        $values =  $this->engine->getFacetContent($this->table_name, $ps_facet_name, [], $pa_options);
        
        // group
        $grouped = [];
        foreach($values as $value) {
            $grouped[substr($value['value'], 0, 1)][$value['id']] = $value;
        }
        return $grouped;
    }
    # ------------------------------------------------------
    # Results
    # ------------------------------------------------------
    /**
     *
     */
    public function execute() {
        $criteria_by_facet = $this->getCriteria();
        
        $acc = null;
        
        $acc = $this->engine->execute($this->table_name, $criteria_by_facet);
        // foreach($criteria_by_facet as $facet => $criteria) {
//             $value_ids = [];
//             foreach(array_keys($criteria) as $value) {
//                 if (!($value_id = $this->engine->getFacetValue($this->table_name, $facet, $value, ['item_id' => ((int)$value > 0) ? (int)$value : null, 'dontCreate' => true]))) { continue; }
//                 
//                 //print "$value => $value_id\n";
//                 $value_ids[$value_id] = 1;
//             }
//             $value_ids = array_keys($value_ids);
//             if (sizeof($value_ids) > 0) {
//                 $acc =  $this->engine->getResultsForFacet($this->table_name, $facet, $value_ids, $acc);
//                print "fOR $facet: ".sizeof($acc)."\n";
//             }
//         }

            $this->result_ids = $acc;
        return $acc;
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function doGetResults($results, $options=null) {
        return caMakeSearchResult($this->table_name, $this->result_ids);
        if (!is_array($this->result_ids)) { return null; }

			$vs_sort = caGetOption('sort', $pa_options, null);
			$vs_sort_direction = strtolower(caGetOption('sortDirection', $pa_options, caGetOption('sort_direction', $pa_options, null)));

			$t_item = Datamodel::getInstance($this->table_name, true);
			$table_num = $t_item->tableNum();
			$vb_will_sort = false; // ($vs_sort && (($this->getCachedSortSetting() != $vs_sort) || ($this->getCachedSortDirectionSetting() != $vs_sort_direction)));

			$vs_pk = $t_item->primaryKey();
			$vs_label_display_field = null;

			//if(sizeof($va_results =  $this->opo_ca_browse_cache->getResults())) {
			if (sizeof($va_results = $this->result_ids)) {
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
				$po_result->init(new WLPlugSearchEngineBrowseEngine($va_results, $table_num), array(), $pa_options);

				return $po_result;
			} else {
				return new WLPlugSearchEngineBrowseEngine($va_results, $table_num);
			}
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function numResults() {
        return sizeof($this->result_ids);
    }
    # ------------------------------------------------------
    # Context
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
        $this->browse_cache->setParameter('context', $ps_browse_context);
        return true;
    }
    # ------------------------------------------------------
    /**
     * Returns current browse context
     *
     * @return string
     */
    public function getContext() {
        return ($vs_context = $this->browse_cache->getParameter('context')) ? $vs_context : '';
    }
    # ------------------------------------------------------
    # Settings
    # ------------------------------------------------------
    /**
     * Rewrite browse config settings as needed before starting actual processing of browse
     */
    private function _processBrowseSettings() {
        $va_revised_facets = [];
        foreach($this->browse_settings['facets'] as $vs_facet_name => $va_facet_info) {

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

            // generate_facets_for_types config directive triggers auto-generation of facet config for each type of an authority item
            // it's typically employed to provide browsing of occurrences where the various types are unrelated
            // you can also use this on other authorities to provide a finer-grained browse without having to know the type hierarchy ahead of time
            if (($va_facet_info['type'] === 'authority') && isset($va_facet_info['generate_facets_for_types']) && $va_facet_info['generate_facets_for_types']) {
                // get types for authority
                $t_table = Datamodel::getInstance($va_facet_info['table'], true);

                $va_type_list = $t_table->getTypeList();

                // auto-generate facets
                foreach($va_type_list as $vn_type_id => $va_type_info) {
                    if ($va_type_info['is_enabled']) {
                        $va_facet_info = array_merge($va_facet_info, [
                            'label_singular' => $va_type_info['name_singular'],
                            'label_singular_with_indefinite_article' => _t('a').' '.$va_type_info['name_singular'],
                            'label_plural' => $va_type_info['name_plural'],
                            'restrict_to_types' => [$va_type_info['item_id']]
                        ]);
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
                    if ($t_element->load(['element_code' => array_pop(explode(".", $va_facet_info['element_code']))])) {
                        if (($t_element->get('datatype') == __CA_ATTRIBUTE_VALUE_LIST__) && ($vn_list_id = $t_element->get('list_id'))) {
                            if ($vn_item_id = caGetListItemID($vn_list_id, $va_facet_info['single_value'])) {
                                $va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
                            }
                        }
                    }
                    break;
                case 'fieldList':
                    $t_instance = Datamodel::getInstance($this->table_name, true);
                    if ($vn_item_id = caGetListItemID($t_instance->getFieldInfo($va_facet_info['field'], 'LIST_CODE'), $va_facet_info['single_value'])) {
                        $va_revised_facets[$vs_facet]['single_value'] = $vn_item_id;
                    }
                    break;
            }
        }

        $this->browse_settings['facets'] = $va_revised_facets;
    }
    # ------------------------------------------------------
    /**
     *
     */
    public static function reindex() {
        $config = caGetBrowseConfig();
        
        $bm = new BrowseManager('ca_objects');
        
        $bm->engine->truncateIndex(); // TODO: get reference to browse plugin dynamically
        
        foreach(self::$browseable_tables as $table) {
            if (!is_array($table_info = $config->get($table))) { continue; }
            if (!is_array($facets = $table_info['facets'])) { continue; }
            
            
            foreach($facets as $facet_code => $facet_info) {
                // TODO: get reference to browse plugin dynamically
                $bm->engine->reindexFacet($table, $facet_code, $facet_info);
            }
        }
    }
	# ----------------------------------------------------------
}
