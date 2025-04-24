<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_tour_stops_list_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2025 Whirl-i-Gig
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
$t_display				= $this->getVar('t_display');
$va_display_list 		= $this->getVar('display_list');
$vo_result 				= $this->getVar('result');
$vn_items_per_page 		= $this->getVar('current_items_per_page');
$vs_current_sort 		= $this->getVar('current_sort');
$vs_default_action		= $this->getVar('default_action');
$vo_ar					= $this->getVar('access_restrictions');
$vs_current_sort_dir    = $this->getVar('current_sort_direction');
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
			<th class='list-header-nosort'>
				<?= ($vs_default_action	== "Edit" ? _t("Edit") : _t("View")); ?>
			</th>
<?php
			// output headers
			$vn_id_count = 0;
			foreach($va_display_list as $va_display_item) {
				$vs_item_display_str =
					((mb_strlen($va_display_item['display']) > 30) ? strip_tags(mb_substr($va_display_item['display'], 0, 27))."..." : $va_display_item['display']);
			
				if ($va_display_item['is_sortable']) {
					if ($vs_current_sort == $va_display_item['bundle_sort']) {
						if($vs_current_sort_dir == 'desc') {
							$vs_th_class = 'list-header-sorted-desc';
							$vs_new_sort_direction = 'asc';
						} else {
							$vs_th_class = 'list-header-sorted-asc';
							$vs_new_sort_direction = 'desc';
						}
			
						print "<th class='{$vs_th_class}'><span id='listHeader".$vn_id_count."'><nobr>".
							caNavLink($this->request, $vs_item_display_str, '', $this->request->getModulePath(), $this->request->getController(), 'Index', array('sort' => $va_display_item['bundle_sort'], 'direction' => $vs_new_sort_direction))
							."</nobr></span></th>";
						TooltipManager::add('#listHeader'.$vn_id_count , _t("Currently sorting by ").$va_display_item['display']);
					} else {
						print "<th class='list-header-unsorted'><span id='listHeader1".$vn_id_count."'><nobr>".caNavLink($this->request, $vs_item_display_str, '', $this->request->getModulePath(), $this->request->getController(), 'Index', array('sort' => $va_display_item['bundle_sort'])) ."</nobr></span></th>";
						TooltipManager::add('#listHeader1'.$vn_id_count , _t("Click to sort by ").$va_display_item['display']);
					}
				} else {
					print "<th class='list-header-nosort'><span id='listHeader2".$vn_id_count."'><nobr>". $vs_item_display_str ."</nobr></span></th>";
					TooltipManager::add('#listHeader2'.$vn_id_count , $va_display_item['display']);
				}
				$vn_id_count++;
			}
?>
			</tr></thead><tbody>
<?php
			$i = 0;
			$vn_item_count = 0;
			
			while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
				$vn_stop_id = $vo_result->get('stop_id');
				
				($i == 2) ? $i = 0 : "";
?>
				<tr <?= ($i ==1) ? "class='odd'" : ""; ?>>
					<td class="addItemToSetControl">
						<input type='checkbox' name='add_to_set_ids' value='<?= (int)$vn_stop_id; ?>' class="addItemToSetControl" />
						<div><?= $vn_start + $vn_item_count + 1; ?></div>
					</td>
<?php
					print "<td style='width:5%;'>".caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 2), '', 'ca_tour_stops', $vn_stop_id, array())."</td>";
					foreach($va_display_list as $placement_id => $info) {
						print "<td><div class='result-content'>".$t_display->getDisplayValue($vo_result, ($placement_id > 0) ? $placement_id : $info['bundle_name'], array_merge(array('request' => $this->request), is_array($info['settings']) ? $info['settings'] : array()))."</div></td>";
					}
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
						foreach($va_bottom_line as $placement_id => $vs_bottom_line_value) {
							print "<td>{$vs_bottom_line_value}</td>";
						}
?>
					</tr>
				</tfoot>
<?php
			}
			if ($vs_bottom_line_totals = $this->getVar('bottom_line_totals')) {
?>				
					<tr>
						<td colspan="<?= sizeof($va_display_list) + 2; ?>" class="listtableAggregateTotals"><?= $vs_bottom_line_totals; ?></td>
					</tr>
<?php		
			}
?>
		</table>
	</form>
</div><!--end scrollingResults -->