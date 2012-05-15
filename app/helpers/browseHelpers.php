<?php
/** ---------------------------------------------------------------------
 * app/helpers/browseHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
		$vs_label = "<span class='{$vs_term_class}'>".htmlentities($pa_item['label'], ENT_COMPAT, 'UTF-8')."</span>";
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
	 * 
	 *
	 * @return string 
	 */
	function caGetBrowseInstance($pm_table_name_or_num, $pa_options=null) {
		$o_dm = Datamodel::load();
		
		$vs_table = (is_numeric($pm_table_name_or_num)) ? $o_dm->getTableName((int)$pm_table_name_or_num) : $pm_table_name_or_num;
		
		switch($vs_table) {
			case 'ca_objects':
				require_once(__CA_LIB_DIR__.'/ca/Browse/ObjectBrowse.php');
				return new ObjectBrowse();
				break;
			case 'ca_entities':
				require_once(__CA_LIB_DIR__.'/ca/Browse/EntityBrowse.php');
				return new EntityBrowse();
				break;
			case 'ca_places':
				require_once(__CA_LIB_DIR__.'/ca/Browse/PlaceBrowse.php');
				return new PlaceBrowse();
				break;
			case 'ca_occurrences':
				require_once(__CA_LIB_DIR__.'/ca/Browse/OccurrenceBrowse.php');
				return new OccurrenceBrowse();
				break;
			case 'ca_collections':
				require_once(__CA_LIB_DIR__.'/ca/Browse/CollectionBrowse.php');
				return new CollectionBrowse();
				break;
			case 'ca_loans':
				require_once(__CA_LIB_DIR__.'/ca/Browse/LoanBrowse.php');
				return new LoanBrowse();
				break;
			case 'ca_movements':
				require_once(__CA_LIB_DIR__.'/ca/Browse/MovementBrowse.php');
				return new MovementBrowse();
				break;
			case 'ca_lists':
				require_once(__CA_LIB_DIR__.'/ca/Browse/ListBrowse.php');
				return new ListBrowse();
				break;
			case 'ca_list_items':
				require_once(__CA_LIB_DIR__.'/ca/Browse/ListItemBrowse.php');
				return new ListItemBrowse();
				break;
			case 'ca_object_lots':
				require_once(__CA_LIB_DIR__.'/ca/Browse/ObjectLotBrowse.php');
				return new ObjectLotBrowse();
				break;
			case 'ca_object_representations':
				require_once(__CA_LIB_DIR__.'/ca/Browse/ObjectRepresentationBrowse.php');
				return new ObjectRepresentationBrowse();
				break;
			case 'ca_tours':
				require_once(__CA_LIB_DIR__.'/ca/Browse/TourBrowse.php');
				return new TourBrowse();
				break;
			case 'ca_tour_stops':
				require_once(__CA_LIB_DIR__.'/ca/Browse/TourStopBrowse.php');
				return new TourStopBrowse();
				break;
			case 'ca_storage_locations':
				require_once(__CA_LIB_DIR__.'/ca/Browse/StorageLocationBrowse.php');
				return new StorageLocationBrowse();
				break;
			default:
				return null;
				break;
		}
	}
	# ---------------------------------------
?>