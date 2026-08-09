<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/related_list_results_list_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2026 Whirl-i-Gig
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
$display_list 		= $this->getVar('display_list');
/** @var EntitySearchResult $result */
$result 			= $this->getVar('result');
$items_per_page 	= $this->getVar('current_items_per_page');
$current_sort 		= $this->getVar('current_sort');
$current_sort_dir   = $this->getVar('current_sort_direction');
$default_action		= $this->getVar('default_action');
$ar					= $this->getVar('access_restrictions');
$rel_id_typenames 	= $this->getVar('relationIdTypeNames');
$rel_id_index	 	= $this->getVar('relationIDsToRelatedIDs');

$interstitial_prefix= $this->getVar('interstitialPrefix');
$primary_table		= $this->getVar('primaryTable');
$primary_id			= $this->getVar('primaryID');
$related_table		= $this->getVar('relatedTable');
$related_rel_table	= $this->getVar('relatedRelTable');
/** @var BundlableLabelableBaseModelWithAttributes $t_related_instance */
$t_related_instance	= $this->getVar('relatedInstance');
$settings			= $this->getVar('settings');

$dont_show_relationship_type = $settings['dontShowRelationshipType'] ?? false;
$dont_show_interstitial_editor = $settings['dontShowInterstitialEditor'] ?? false;
?>
<div id="scrollingResults">
	<form id="caFindResultsForm<?= $interstitial_prefix; ?>">
		<table id="<?= $interstitial_prefix; ?>RelatedList" class="listtable attributeListItem" width="100%" border="0" cellpadding="0" cellspacing="1">
			<thead>
			<tr>
			<th></th>
			<th style="width:10px; text-align:center;" class='list-header-nosort'><!-- column for edit/view button --></th>
<?php
			if(!$dont_show_interstitial_editor) {
?>
			<th style="width:10px; text-align:center;" class='list-header-nosort'><!-- column for interstitial buttons --></th>
<?php
			}
			if(!$dont_show_relationship_type) {
?>
			<th style="text-align:center;" class='list-header-nosort'></th>
<?php
			}
			// output headers
			$id_count = 0;
			foreach($display_list as $display_item) {
				$item_display_str =
					((mb_strlen($display_item['display']) > 30) ? strip_tags(mb_substr($display_item['display'], 0, 27))."..." : $display_item['display']);

				if ($display_item['is_sortable']) {
					if ($current_sort == $display_item['bundle_sort']) {
						if($current_sort_dir == 'desc') {
							$th_class = 'list-header-sorted-desc';
							$new_sort_direction = 'asc';
						} else {
							$th_class = 'list-header-sorted-asc';
							$new_sort_direction = 'desc';
						}

						print "<th class='{$th_class}'><span id='listHeader".$id_count."'><nobr>".
							caNavLink($this->request, $item_display_str, '', $this->request->getModulePath(), $this->request->getController(), 'Index', array('sort' => $display_item['bundle_sort'], 'direction' => $new_sort_direction))
							."</nobr></span></th>";
						TooltipManager::add('#listHeader'.$id_count , _t("Currently sorting by ").$display_item['display']);
					} else {
						print "<th class='list-header-unsorted'><span id='listHeader1".$id_count."'><nobr>".caNavLink($this->request, $item_display_str, '', $this->request->getModulePath(), $this->request->getController(), 'Index', array('sort' => $display_item['bundle_sort'])) ."</nobr></span></th>";
						TooltipManager::add('#listHeader1'.$id_count , _t("Click to sort by ").$display_item['display']);
					}
				} else {
					print "<th class='list-header-nosort'><span id='listHeader2".$id_count."'><nobr>". $item_display_str ."</nobr></span></th>";
					TooltipManager::add('#listHeader2'.$id_count , $display_item['display']);
				}
				$id_count++;
			}
?>
				<th></th>
			</tr></thead><tbody>
<?php
			$i = 0;
			$item_count = 0;

			while(($item_count < $items_per_page) && $result->nextHit()) {
				$id = $result->get($t_related_instance->primaryKey());
				$relation_id = key($rel_id_index); next($rel_id_index);
				
				($i == 2) ? $i = 0 : "";
?>

				<tr <?= ($i ==1) ? "class='odd'" : ""; ?> <?= "id='{$interstitial_prefix}{$relation_id}'"; ?>>
					<td style="width:10px" class="addItemToBatchControl">
						<?= caHTMLCheckboxInput('selected_ids', ['value' => $id, 'class' => 'addItemToBatchControl dontTriggerUnsavedChangeWarning', 'id' =>'selectedId'.$id], []); ?>
					</td>
					<td style='width:5%;'><?= caEditorLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 2), '', $related_table, $id, array(), array()); ?></td>
<?php
					if(!$dont_show_interstitial_editor) {
?>				
					<td style="width:10px">
						<a href="#" class="caInterstitialEditButton listRelEditButton"><?= caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a>
					</td>
<?php
					}
					if(!$dont_show_relationship_type) {
?>
					<td style="padding-left: 5px; padding-right: 5px;">
						<?= $rel_id_typenames[$relation_id]; ?>
					</td>
<?php
					}	
					foreach($display_list as $placement_id => $info) {
                        print "<td><div class='result-content'>";

						// if there's a template, evaluate template against relationship
						if($template = $info['settings']['format']) {
							$opts = array_merge($info, array(
								'resolveLinksUsing' => $primary_table,
								'primaryIDs' =>
									array (
										$primary_table => array($primary_id),
									),
							));
							print caProcessTemplateForIDs($template, $related_rel_table, array($relation_id), $opts);
						} else {
							print $t_display->getDisplayValue($result, $placement_id, array_merge(array('request' => $this->request), is_array($info['settings']) ? $info['settings'] : array()));
						}

						print "</div></td>";
                    }
?>	
					<td style="width:10px">
						<a href="#" class="caDeleteItemButton listRelDeleteButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
					</td>
				</tr>
<?php
				$i++;
				$item_count++;
			}
?>
			</tbody>
<?php
			if (is_array($bottom_line = $this->getVar('bottom_line'))) {
?>
				<tfoot>
					<tr>
						<td colspan="2" class="listtableTotals"><?= _t('Totals'); ?></td>
<?php
						foreach($bottom_line as $placement_id => $bottom_line_value) {
							print "<td>{$bottom_line_value}</td>";
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
	if($current_sort == '_user') {
?>
		<script type="text/javascript">
			jQuery('#<?= $interstitial_prefix; ?>RelatedList tbody').sortable({
				update: function() {
					var ids = [];
					jQuery('#<?= $interstitial_prefix; ?>RelatedList tbody tr').each(function() {
						ids.push(jQuery(this).attr('id').replace('<?= $interstitial_prefix; ?>', ''));
					});

					jQuery.post(
						'<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'SaveUserSort'); ?>',
						{ ids: ids, related_rel_table: "<?= $related_rel_table; ?>" }
					);
				}
			}).disableSelection();
		</script>
<?php
}
