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
	$va_sets_by_table = $this->getVar("sets_by_table");

?>

<div class="dashboardWidgetContentContainer dashboardWidgetScrollMedium" style="padding-right:10px;">
<?php
foreach($va_sets_by_table as $vs_table => $va_sets){
	if(sizeof($va_sets) > 0){
		print "<div style='font-weight:bold; padding:2px 5px 2px 5px; margin:0px 0px 5px 0px; font-size:12px; background-color:#e9e9e9;'>";
		$vs_path = "find/Search";			
		switch($vs_table){
			case "ca_objects":
				print _t("Object Sets");
				$vs_path .= "Objects";
			break;
			# ------------------------
			case "ca_entities":
				print _t("Entity Sets");
				$vs_path .= "Entities";
			break;
			# ------------------------
			case "ca_places":
				print _t("Place Sets");
				$vs_path .= "Places";
			break;
			# ------------------------
			case "ca_object_lots":
				print _t("Object Lot Sets");
				$vs_path .= "ObjectLots";
			break;
			# ------------------------
			case "ca_storage_locations":
				print _t("Storage Location Sets");
				$vs_path .= "StorageLocations";
			break;
			# ------------------------
			case "ca_collections":
				print _t("Collection Sets");
				$vs_path .= "Collections";
			break;
			# ------------------------
			case "ca_occurrences":
				print _t("Occurrence Sets");
				$vs_path .= "Occurrences";
			break;
			# ------------------------
		}
		print "</div><div style='margin-bottom:15px; padding:0px 0px 0px 5px;'>";
		foreach($va_sets as $vn_i => $va_set_info){
			if (sizeof($va_set_info) > 0) {
				print caFormTag($po_request, 'doSavedSearch', 'caSearchSetsForm'.$vs_table, $vs_path, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
				print "<select name='search' style='width:300px;'>\n";			
				foreach($va_set_info as $vn_id => $va_set) {
					print "<option value='set:".$vn_id."'>".$va_set["name"]."</option>\n";
				}
				print "</select>\n ";
				print caFormSubmitLink($po_request, _t('Search').' &rsaquo;', 'button', 'caSearchSetsForm'.$vs_table);
				print "</form>\n";
			}
		}
		print "<div style='clear:both; height:1px;'><!-- empty --></div></div>";
	}
}
?>
</div>