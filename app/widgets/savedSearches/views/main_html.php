<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	$po_request			= $this->getVar('request');
	$vs_widget_id			= $this->getVar('widget_id');
	$va_searches_by_table = $this->getVar("saved_searches");

?>

<div class="dashboardWidgetContentContainer dashboardWidgetScrollMedium" style="padding-right:10px;">
<?php
foreach($va_searches_by_table as $vs_table => $va_searches){
	if((sizeof($va_searches['advanced_search']) > 0) || (sizeof($va_searches['basic_search']) > 0)){
		print "<div style='font-weight:bold; padding:2px 5px 2px 5px; margin:0px 0px 5px 0px; font-size:12px; background-color:#e9e9e9;'>";
		$vs_path = "find/Search";			
		switch($vs_table){
			case "ca_objects":
				print _t("Object Searches");
				$vs_path .= "Objects";
			break;
			# ------------------------
			case "ca_entities":
				print _t("Entity Searches");
				$vs_path .= "Entities";
			break;
			# ------------------------
			case "ca_places":
				print _t("Place Searches");
				$vs_path .= "Places";
			break;
			# ------------------------
			case "ca_object_lots":
				print _t("Object Lot Searches");
				$vs_path .= "ObjectLots";
			break;
			# ------------------------
			case "ca_storage_locations":
				print _t("Storage Location Searches");
				$vs_path .= "StorageLocations";
			break;
			# ------------------------
			case "ca_collections":
				print _t("Collection Searches");
				$vs_path .= "Collections";
			break;
			# ------------------------
			case "ca_occurrences":
				print _t("Occurrence Searches");
				$vs_path .= "Occurrences";
			break;
			# ------------------------
		}
		print "</div><div style='margin-bottom:15px; padding:0px 0px 0px 5px;'>";
		foreach($va_searches as $vs_search_type => $va_search_info){
			if (sizeof($va_search_info) > 0) {
				print "<div style='float:".(($vs_search_type == "basic_search") ? "left": "right").";'>";
				print caFormTag($po_request, 'doSavedSearch', 'caSavedSearchesForm'.$vs_table.$vs_search_type, $vs_path.(($vs_search_type == "advanced_search") ? "Advanced": ""), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
				print (($vs_search_type == "advanced_search") ? _t("advanced"): _t("basic")).": <select name='saved_search_key' style='width:100px;'>\n";			
				foreach(array_reverse($va_search_info) as $vs_key => $va_search) {
					$vs_search = strip_tags($va_search['_label']);
					print "<option value='".htmlspecialchars($vs_key, ENT_QUOTES, 'UTF-8')."'>".$vs_search."</option>\n";
				}
				print "</select>\n ";
				print caFormSubmitLink($po_request, _t('Search').' &rsaquo;', 'button', 'caSavedSearchesForm'.$vs_table.$vs_search_type);
				print "</form></div>\n";
			}
		}
		print "<div style='clear:both; height:1px;'><!-- empty --></div></div>";
	}
}
?>
</div>