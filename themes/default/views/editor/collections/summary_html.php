<?php
/* ----------------------------------------------------------------------
 * views/editor/collections/summary_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2018 Whirl-i-Gig
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
 	$t_item 				= $this->getVar('t_subject');
	$vn_item_id 			= $this->getVar('subject_id');
	
	$t_display 				= $this->getVar('t_display');
	$va_placements 			= $this->getVar("placements");
?>
	<div id="summary" style="clear: both;">
<?php
    print caEditorPrintSummaryControls($this);
?>
	<div id="title">
		<?php print $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($va_placements as $vn_placement_id => $va_info) {
			$vs_class = "";
			if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array())))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$vs_display_value = "&lt;"._t('not defined')."&gt;";
				$vs_class = " notDefined";
			}
			print "<div class=\"unit".$vs_class."\"><span class=\"heading".$vs_class."\">".$va_info['display'].":</span> ".$vs_display_value."</div>\n";
		}



		if ($t_item->get('ca_collections.children.collection_id')) {
			print "<div class='heading' style='margin-bottom:10px;'>"._t("%1 contents", $t_item->get('ca_collections.type_id', array('convertCodesToDisplayText' => true)))."</div>";
			//
			if (
				(!is_array($va_sort_fields = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_sort_values')) && !sizeof($va_sort_fields))
				&&
				(!is_array($va_sort_fields = $t_item->getAppConfig()->get('ca_collections_hierarchy_browser_sort_values')) && !sizeof($va_sort_fields))
			) {
				$va_sort_fields = ['ca_collections.preferred_labels.name'];
			}
			if(
				!($vs_template = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_display_settings'))
				&&
				!($vs_template = $t_item->getAppConfig()->get('ca_collections_hierarchy_browser_display_settings'))
			) {
				$vs_template = "<l>^ca_collections.preferred_labels.name</l> (^ca_collections.idno)";
			}
			
			$va_hierarchy = $t_item->hierarchyWithTemplate($vs_template, array('collection_id' => $vn_item_id, 'sort' => $va_sort_fields, 'objectTemplate' => $t_item->getAppConfig()->get('ca_objects_hierarchy_summary_display_settings')));
			foreach($va_hierarchy as $vn_i => $va_hierarchy_item) {
				$vs_margin = $va_hierarchy_item['level']*20;
				print "<div style='margin-left:".$vs_margin."px;margin-bottom:10px;'><i class='fa fa-angle-right' ></i> ".$va_hierarchy_item['display']."</div>";
			}
		}
?>
			</td>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
<?php
		TooltipManager::add('#printButton', _t("Download Summary as PDF"));
		TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));
