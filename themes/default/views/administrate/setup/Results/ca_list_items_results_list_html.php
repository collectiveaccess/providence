<?php
/* ----------------------------------------------------------------------
 * themes/default/views/admninistrate/setup/ca_list_items_search_html.php :
 * 		basic object search form view script 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
if (!$this->getVar('no_hierarchies_defined')) {
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vn_start				= (int)$this->getVar('start');
?>
<div id="scrollingResults">

<form id="caFindResultsForm">
	<table class="listtable">
		<thead>
		<tr>
		<th class='list-header-nosort addItemToSetControl'>
			<input type='checkbox' name='record' value='' id='addItemToSetSelectAllControl' class='addItemToSetControl' onchange="jQuery('.addItemToSetControl').attr('checked', (jQuery('#addItemToSetSelectAllControl').attr('checked') == 'checked'));"/>
		</th>
<?php
		// output headers
		$vn_id_count = 0;
		foreach($va_display_list as $va_display_item) {
			if ($va_display_item['is_sortable']) {
				if ($vs_current_sort == $va_display_item['bundle_sort']) {
					print "<th class='list-header-sorted-asc'><span id='listHeader".$vn_id_count."'><nobr>".((mb_strlen($va_display_item['display']) > 17) ? strip_tags(mb_substr($va_display_item['display'], 0, 15))."..." : $va_display_item['display'])."</nobr></span></th>";
					TooltipManager::add('#listHeader'.$vn_id_count , 'Currently sorting by '.$va_display_item['display']);
				} else {
					print "<th class='list-header-unsorted'><span id='listHeader1".$vn_id_count."'><nobr>".caNavLink($this->request, ((mb_strlen($va_display_item['display']) > 17) ? strip_tags(mb_substr($va_display_item['display'], 0, 15))."..." : $va_display_item['display']), '', $this->request->getModulePath(), $this->request->getController(), 'Index', array('sort' => $va_display_item['bundle_sort'])) ."</nobr></span></th>";
					TooltipManager::add('#listHeader1'.$vn_id_count , 'Click to sort by '.$va_display_item['display']);
				}
			} else {
				print "<th class='list-header-nosort'><span id='listHeader2".$vn_id_count."'><nobr>".((mb_strlen($va_display_item['display']) > 17) ? strip_tags(mb_substr($va_display_item['display'], 0, 15))."..." : $va_display_item['display'])."</nobr></span></th>";
				TooltipManager::add('#listHeader2'.$vn_id_count , $va_display_item['display']);
			}
			$vn_id_count++;
		}
?>
		<th class='list-header-nosort listtableEditDelete'> </th>
		</tr></thead><tbody>
<?php
		$i = 0;
		$vn_item_count = 0;
		
		while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
			$vn_item_id = $vo_result->get('item_id');
			
			($i == 2) ? $i = 0 : "";
?>
			<tr <?= ($i ==1) ? "class='odd'" : ""; ?>>
				<td class="addItemToSetControl">
					<input type='checkbox' name='add_to_set_ids' value='<?= (int)$vn_item_id; ?>' class="addItemToSetControl" />
					<div><?= $vn_start + $vn_item_count + 1; ?></div>
				</td>
<?php
				foreach($va_display_list as $vn_placement_id => $va_display_item) {
					print "<td><div class='result-content'>".$t_display->getDisplayValue($vo_result, ($vn_placement_id > 0) ? $vn_placement_id : $va_display_item['bundle_name'])."</div></td>";
				}
				print "<td class='listtableEditDelete'>".caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 2), 'list-button', 'ca_list_items', $vn_item_id, array());
				print " <a href='#' onclick='caOpenBrowserWith({$vn_item_id}); return false;'>".caNavIcon(__CA_NAV_ICON_GO__, 2, array('title' => _t('View in hierarchy')))."</a>";
				print "</td>";		
?>	
			</tr>
<?php
			$i++;
			$vn_item_count++;
		}
?>
		</tbody>
<?php
		if (is_array($va_bottom_line = $this->getVar('bottom_line'))) {
?>
			<tfoot>
				<tr>
					<td colspan="2" class="listtableTotals"><?= _t('Totals'); ?></td>
<?php
					foreach($va_bottom_line as $vn_placement_id => $vs_bottom_line_value) {
						print "<td>{$vs_bottom_line_value}</td>";
					}
?>
				</tr>
			</tfoot>
<?php
		}
?>
	</table>
</form><!--end caFindResultsForm -->
</div><!--end scrollingResults -->
<?php
	TooltipManager::add('.hierarchyIcon', _t("View in Hierarchy"));
}
?>
<div class="editorBottomPadding"><!-- empty --></div>
