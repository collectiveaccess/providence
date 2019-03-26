<?php
/** ---------------------------------------------------------------------
 * app/helpers/browseHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
require_once(__CA_MODELS_DIR__.'/ca_lists.php');


	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_facet
	 * @param array $pa_item
	 * @param array $pa_facet_info
	 * @param array $pa_options List of display options. Supported options are:
	 *		termClass = CSS class to <span> browse term with. If not set, defaults to 'hierarchyBrowserItemTerm' 
	 *		pathClass = CSS class to <span> browse path elements with. If not set, defaults to 'hierarchyBrowserItemPath' 
	 *		
	 * @return string 
	 */
	function caGetLabelForDisplay(&$pa_facet, $pa_item, $pa_facet_info, $pa_options=null) {
		$vs_term_class = (isset($pa_options['termClass']) && $pa_options['termClass']) ? $pa_options['termClass'] : 'hierarchyBrowserItemTerm';
		$vs_label = "<span class='{$vs_term_class}'>".htmlentities(isset($pa_item['display_label']) ? $pa_item['display_label'] : $pa_item['label'], ENT_COMPAT, 'UTF-8')."</span>";
		if ($pa_facet_info['show_hierarchy'] && $pa_item['parent_id']) {
			$va_hierarchy = caGetHierarchicalLabelsForDisplay($pa_facet, $pa_item['parent_id'], $pa_options);
			array_unshift($va_hierarchy, $vs_label);
			if (isset($pa_facet_info['remove_first_items']) && ($pa_facet_info['remove_first_items'] > 0)) {
				if (($vn_l = sizeof($va_hierarchy) - $pa_facet_info['remove_first_items']) > 0) {
					$va_hierarchy = array_slice($va_hierarchy, 0, $vn_l);
				}
			}
			
			if (isset($pa_facet_info['hierarchy_limit']) && ($pa_facet_info['hierarchy_limit'] > 0) && (sizeof($va_hierarchy) > $pa_facet_info['hierarchy_limit'])) {
				$va_hierarchy = array_slice($va_hierarchy, 0, $pa_facet_info['hierarchy_limit']);
			}
			
			if (strtolower($pa_facet_info['hierarchy_order']) == 'asc') {
				$va_hierarchy = array_reverse($va_hierarchy);
			}
			
			return join($pa_facet_info['hierarchical_delimiter'], $va_hierarchy);
		}
		
		return $vs_label;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_facet
	 * @param int $pn_id
	 * @param array $pa_facet_info
	 * @param array $pa_options List of display options. Supported options are:
	 *		pathClass = CSS class to <span> browse path elements with. If not set, defaults to 'hierarchyBrowserItemPath' 
	 *
	 * @return array
	 */
	function caGetHierarchicalLabelsForDisplay(&$pa_facet, $pn_id, $pa_options=null) {
		if (!$pa_facet[$pn_id]['label']) { return array(); }
		
		$vs_path_class = (isset($pa_options['pathClass']) && $pa_options['pathClass']) ? $pa_options['pathClass'] : 'hierarchyBrowserItemPath';
		$va_values = array("<span class='{$vs_path_class}'>".htmlentities($pa_facet[$pn_id]['label'], ENT_COMPAT, 'UTF-8')."</span>");
		if ($vn_parent_id = $pa_facet[$pn_id]['parent_id']) {
			$va_values = array_merge($va_values, caGetHierarchicalLabelsForDisplay($pa_facet, $vn_parent_id));
		}
		return $va_values;
	}
	# ---------------------------------------
	/**
	 * Get browse instance
	 *
	 * @param string|int $pm_table_name_or_num
	 * @param null $pa_options
	 * @return BaseBrowse
	 */
	function caGetBrowseInstance($pm_table_name_or_num, $pa_options=null) {
		
		$vs_table = (is_numeric($pm_table_name_or_num)) ? Datamodel::getTableName((int)$pm_table_name_or_num) : $pm_table_name_or_num;
		
		if (!($t_instance = Datamodel::getInstanceByTableName($vs_table, true))) { return null; }
		if ($t_instance->isRelationship()) { 
			require_once(__CA_LIB_DIR__.'/Browse/InterstitialBrowse.php');
			return new InterstitialBrowse(null, null, $vs_table);
		}
		
		switch($vs_table) {
			case 'ca_objects':
				require_once(__CA_LIB_DIR__.'/Browse/ObjectBrowse.php');
				return new ObjectBrowse();
				break;
			case 'ca_entities':
				require_once(__CA_LIB_DIR__.'/Browse/EntityBrowse.php');
				return new EntityBrowse();
				break;
			case 'ca_places':
				require_once(__CA_LIB_DIR__.'/Browse/PlaceBrowse.php');
				return new PlaceBrowse();
				break;
			case 'ca_occurrences':
				require_once(__CA_LIB_DIR__.'/Browse/OccurrenceBrowse.php');
				return new OccurrenceBrowse();
				break;
			case 'ca_collections':
				require_once(__CA_LIB_DIR__.'/Browse/CollectionBrowse.php');
				return new CollectionBrowse();
				break;
			case 'ca_loans':
				require_once(__CA_LIB_DIR__.'/Browse/LoanBrowse.php');
				return new LoanBrowse();
				break;
			case 'ca_movements':
				require_once(__CA_LIB_DIR__.'/Browse/MovementBrowse.php');
				return new MovementBrowse();
				break;
			case 'ca_lists':
				require_once(__CA_LIB_DIR__.'/Browse/ListBrowse.php');
				return new ListBrowse();
				break;
			case 'ca_list_items':
				require_once(__CA_LIB_DIR__.'/Browse/ListItemBrowse.php');
				return new ListItemBrowse();
				break;
			case 'ca_object_lots':
				require_once(__CA_LIB_DIR__.'/Browse/ObjectLotBrowse.php');
				return new ObjectLotBrowse();
				break;
			case 'ca_object_representations':
				require_once(__CA_LIB_DIR__.'/Browse/ObjectRepresentationBrowse.php');
				return new ObjectRepresentationBrowse();
				break;
			case 'ca_tours':
				require_once(__CA_LIB_DIR__.'/Browse/TourBrowse.php');
				return new TourBrowse();
				break;
			case 'ca_tour_stops':
				require_once(__CA_LIB_DIR__.'/Browse/TourStopBrowse.php');
				return new TourStopBrowse();
				break;
			case 'ca_storage_locations':
				require_once(__CA_LIB_DIR__.'/Browse/StorageLocationBrowse.php');
				return new StorageLocationBrowse();
				break;
			default:
				return null;
				break;
		}
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return Configuration 
	 */
	function caGetBrowseConfig() {
		$o_config = Configuration::load();
		return Configuration::load(__CA_CONF_DIR__.'/browse.conf');
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return array 
	 */
	function caGetInfoForBrowseType($ps_browse_type) {
		$o_browse_config = caGetBrowseConfig();
		
		if (!is_array($va_browse_types = $o_browse_config->getAssoc('browseTypes'))) { return null; }
		
		$ps_browse_type = strtolower($ps_browse_type);
		
		if (isset($va_browse_types[$ps_browse_type])) {
			return $va_browse_types[$ps_browse_type];
		} else {
		    // Try to match case insensitively
		    $keys = array_keys($va_browse_types);
		    
		    $dict = [];
		    foreach($keys as $k) {
		        $dict[strtolower($k)] = $k;
		    }
		    if (isset($dict[$ps_browse_type])) {
		        return $va_browse_types[$dict[$ps_browse_type]];
		    }
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return array 
	 */
	function caGetBrowseTypes($pa_options=null) {
		$o_browse_config = caGetBrowseConfig();
		
		$va_browse_types = $o_browse_config->getAssoc('browseTypes');
		if(caGetOption('forMenuBar', $pa_options, false)) {
			foreach($va_browse_types as $vs_k => $va_browse_info) {
				if (isset($va_browse_info['dontIncludeInBrowseMenu']) && (bool)$va_browse_info['dontIncludeInBrowseMenu']) {
					unset($va_browse_types[$vs_k]);
				}
			}
		}
		
		return $va_browse_types;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @return (string)
	 */
	function caGetFacetForMenuBar($po_request, $vs_browse_type, $pa_options=null) {
		$vb_select_default_facet = caGetOption('selectDefaultFacet', $pa_options, true);
		$vs_default_facet = caGetOption('defaultFacet', $pa_options, null);
		
		$o_browse_config = caGetBrowseConfig();
		$vs_key = '';//Session::getVar('objects_last_browse_id');
		
		if (!($va_browse_info = caGetInfoForBrowseType($vs_browse_type))) {
			// invalid browse type – throw error
			return null;
		}
		$o_browse = caGetBrowseInstance($va_browse_info["table"]);
		if ($vs_key) { $o_browse->reload($vs_key); }
		
		if ((isset($va_browse_info['facetGroup']) && (($vs_menu_bar_facet_group = $o_browse_config->get('menubarFacetGroup')) ||($vs_menu_bar_facet_group = $va_browse_info['facetGroup'])))) {
			$o_browse->setFacetGroup($vs_menu_bar_facet_group);
		}
		
		if (is_array($va_browse_info['restrictToTypes']) && sizeof($va_browse_info['restrictToTypes'])) { 
			$o_browse->setTypeRestrictions($va_browse_info['restrictToTypes']);
		}
		
		$o_browse->execute(array('checkAccess' => caGetUserAccessValues($po_request), 'showAllForNoCriteriaBrowse' => true));
	
		$va_facets = $o_browse->getInfoForAvailableFacets();
		
		$vs_buf = '';
		foreach($va_facets as $vs_facet_name => $va_facet_info) {
			if (!$vs_default_facet) { $vs_default_facet = $vs_facet_name; }
			$vs_buf .= "<li ".((($vs_default_facet == $vs_facet_name) && $vb_select_default_facet) ? "class='active'" : "")."><a href='#' onclick='jQuery(\".browseMenuFacet\").load(\"".caNavUrl($po_request, '*', 'Browse', $vs_browse_type, array('facet' => $vs_facet_name, 'getFacet' => 1, 'key' => $vs_key, 'isNav' => 1))."\", function() { jQuery(this).parent().scrollTop(0); }); jQuery(this).parent().siblings().removeClass(\"active\"); jQuery(this).parent().addClass(\"active\"); return false;'>".caUcFirstUTF8Safe($va_facet_info['label_plural'])."</a></li>\n";
		}
		
		if ($vs_default_facet && $vb_select_default_facet) {
			$vs_buf .= "<script type='text/javascript'>jQuery(document).ready(function() { jQuery(\".browseMenuFacet\").load(\"".caNavUrl($po_request, '*', 'Browse', $vs_browse_type, array('facet' => $vs_default_facet, 'getFacet' => 1, 'key' => $vs_key, 'isNav' => 1))."\"); });</script>\n";
		}
		return $vs_buf;
	}
	# ---------------------------------------
