<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/related_list_results_list_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

	/** @var ca_bundle_displays $t_display */
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	/** @var EntitySearchResult $vo_result */
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vs_current_sort_dir    = $this->getVar('current_sort_direction');
	$vs_default_action		= $this->getVar('default_action');
	$vo_ar					= $this->getVar('access_restrictions');
	$va_rel_id_typenames 	= $this->getVar('relationIdTypeNames');
	$va_rel_id_index	 	= $this->getVar('relationIDsToRelatedIDs');

	$vs_interstitial_prefix	= $this->getVar('interstitialPrefix');
	$vs_primary_table		= $this->getVar('primaryTable');
	$vn_primary_id			= $this->getVar('primaryID');
	$vs_related_table		= $this->getVar('relatedTable');
	$vs_related_rel_table	= $this->getVar('relatedRelTable');
	/** @var BundlableLabelableBaseModelWithAttributes $t_related_instance */
	$t_related_instance		= $this->getVar('relatedInstance');

?>
<div id="scrollingResults">
	<form id="caFindResultsForm<?php print $vs_interstitial_prefix; ?>">
		<table id="<?php print $vs_interstitial_prefix; ?>RelatedList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
			<thead>
			<tr>
			<th style="width:10px; text-align:center;" class='list-header-nosort'><!-- column for interstitial and delete buttons --></th>
			<th class='list-header-nosort'>
				<?php print ($vs_default_action	== "Edit" ? _t("Edit") : _t("View")); ?>
			</th>
			<th style="text-align:center;" class='list-header-nosort'></th>
<?php
			// output headers
			$vn_id_count = 0;
			foreach($va_display_list as $va_display_item) {
				$vs_item_display_str =
					((unicode_strlen($va_display_item['display']) > 30) ? strip_tags(mb_substr($va_display_item['display'], 0, 27))."..." : $va_display_item['display']);

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
				$vn_id = $vo_result->get($t_related_instance->primaryKey());
				$vn_relation_id = key($va_rel_id_index); next($va_rel_id_index);
				
				($i == 2) ? $i = 0 : "";
?>

				<tr <?php print ($i ==1) ? "class='odd'" : ""; ?> <?php print "id='{$vs_interstitial_prefix}{$vn_relation_id}'"; ?>>
					<td style="width:10px">
						<a href="#" class="caInterstitialEditButton listRelEditButton"><?php print caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a>
						<a href="#" class="caDeleteItemButton listRelDeleteButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
					</td>
<?php
					print "<td style='width:5%;'>".caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 2), '', $vs_related_table, $vn_id, array(), array())."</td>";
?>
					<td style="padding-left: 5px; padding-right: 5px;">
						<?php print $va_rel_id_typenames[$vn_relation_id]; ?>
					</td>
<?php
						
					foreach($va_display_list as $vn_placement_id => $va_info) {
                        print "<td><span class=\"read-more\">";

						// if there's a template, evaluate template against relationship
						if($vs_template = $va_info['settings']['format']) {
							$va_opts = array_merge($va_info, array(
								'resolveLinksUsing' => $vs_primary_table,
								'primaryIDs' =>
									array (
										$vs_primary_table => array($vn_primary_id),
									),
							));
							print caProcessTemplateForIDs($vs_template, $vs_related_rel_table, array($vn_relation_id), $va_opts);
						} else {
							print $t_display->getDisplayValue($vo_result, $vn_placement_id, array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array()));
						}

						print "</span></td>";
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
						<td colspan="2" class="listtableTotals"><?php print _t('Totals'); ?></td>
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
	// if set to user defined, make tbody drag+droppable
	if($vs_current_sort == '_user') {
?>
		<script type="text/javascript">
			jQuery('#<?php print $vs_interstitial_prefix; ?>RelatedList tbody').sortable({
				update: function() {
					var ids = [];
					jQuery('#<?php print $vs_interstitial_prefix; ?>RelatedList tbody tr').each(function() {
						ids.push(jQuery(this).attr('id').replace('<?php print $vs_interstitial_prefix; ?>', ''));
					});

					jQuery.get(
						'<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'SaveUserSort'); ?>',
						{ ids: ids, related_rel_table: "<?php print $vs_related_rel_table; ?>" }
					);
				}
			}).disableSelection();
		</script>
<?php
	}
?>
