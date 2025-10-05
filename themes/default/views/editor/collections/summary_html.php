<?php
/* ----------------------------------------------------------------------
 * views/editor/collections/summary_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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
	$item_id 				= $this->getVar('subject_id');
	
	$t_display 				= $this->getVar('t_display');
	$placements 			= $this->getVar("placements");
?>
<div id="summary" style="clear: both;">
	<?= caEditorPrintSummaryControls($this); ?>
	<div id="title">
		<?= $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($placements as $placement_id => $info) {
			$class = "";
			if (!strlen($display_value = $t_display->getDisplayValue($t_item, ($placement_id > 0) ? $placement_id : $info['bundle_name'], array_merge(array('request' => $this->request), is_array($info['settings']) ? $info['settings'] : [])))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$display_value = "<"._t('not defined').">";
				$class = " notDefined";
			}
			print "<div class=\"unit{$class}\"><span class=\"heading{$class}\">{$info['display']}</span><span class='summaryData'> {$display_value}</span></div>\n";
		}
		
		if ($t_item->get('ca_collections.children.collection_id')) {
			print "<div class='heading' style='margin-bottom:10px;'>"._t("%1 contents", $t_item->get('ca_collections.type_id', array('convertCodesToDisplayText' => true)))."</div>";
			//
			if (
				(!is_array($sort_fields = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_sort_values')) || !sizeof($sort_fields))
				&&
				(!is_array($sort_fields = caGetHierarchyBrowserSortValues('ca_collections', $t_item))|| !sizeof($sort_fields))
			) {
				$sort_fields = ['ca_collections.preferred_labels.name'];
			}
			if(
				!($template = $t_item->getAppConfig()->get('ca_collections_hierarchy_summary_display_settings'))
				&&
				!($template = $t_item->getAppConfig()->get('ca_collections_hierarchy_browser_display_settings'))
			) {
				$template = "<l>^ca_collections.preferred_labels.name</l> (^ca_collections.idno)";
			}
			
			$hierarchy = $t_item->hierarchyWithTemplate($template, array('collection_id' => $item_id, 'sort' => $sort_fields, 'objectTemplate' => $t_item->getAppConfig()->get('ca_objects_hierarchy_summary_display_settings')));
			foreach($hierarchy as $vn_i => $hierarchy_item) {
				$margin = $hierarchy_item['level']*20;
				print "<div style='margin-left:".$margin."px;margin-bottom:10px;'><i class='fa fa-angle-right' ></i> ".$hierarchy_item['display']."</div>";
			}
		}		
?>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
<?php
TooltipManager::add('#printButton', _t("Download Summary as PDF"));
TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));
