<?php
/* ----------------------------------------------------------------------
 * views/find/Search/ajax_refine_facet.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2025 Whirl-i-Gig
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
$facet 				= $this->getVar('grouped_facet');
$facet_name 		= $this->getVar('facet_name');
$facet_info 		= $this->getVar('facet_info');
$grouping_field		= $this->getVar('grouping');
$group_mode 		= $facet_info["group_mode"];
$row_size = $this->request->config->get('browse_row_size');
$td_width = intval(100/$row_size);

if (!$facet||!$facet_name) { 
	print _t('No facet defined'); 
	return;
}
$multiple_selection_facet = caGetOption('multiple', $facet_info, false, ['castTo' => 'boolean']);
$modify_id 			= $this->getVar('modify') ? $this->getVar('modify') : '0';
?>
<script type="text/javascript">
	function caUpdateFacetDisplay(grouping) {
		caUIBrowsePanel.showBrowsePanel('<?= $facet_name; ?>', <?= ((intval($modify_id) > 0) ? 'true' : 'false'); ?>, <?= ((intval($modify_id) > 0) ?  $modify_id : 'null'); ?>, grouping);
	}
</script>
<div id='browseFacetGroupingControls'>
<?php 
	if (isset($facet_info['groupings']) && is_array($facet_info['groupings']) && sizeof($facet_info['groupings'] )) {
		print _t('Group by').': '; 
		
		foreach($facet_info['groupings'] as $grouping => $grouping_label) {
			print "<a href='#' onclick='caUpdateFacetDisplay(\"{$grouping}\");' style='".(($grouping == $grouping_field) ? 'font-weight: bold; font-style: italic;' : '')."'>{$grouping_label}</a> ";
		}
	}
?>
</div>

<div class="browseSelectPanelContentArea <?= ($multiple_selection_facet) ? "browseSelectMultiplePanelContentArea" : "" ?>">
<?php
	if ($multiple_selection_facet) {
?>
		<div class='applyFacetContainer'><a href="#" id="facet_apply" data-facet="<?= $facet_name; ?>" class="facetApply">Apply</a></div>
<?php
	}
?>
	<h2 class='browse'><?php print caUcFirstUTF8Safe($facet_info['label_plural']); ?></h2>

<?php
	switch($group_mode) {
		# ------------------------------------------------------------
		case 'hierarchical';
?>
	<div class='clearDivide'></div>
	<!--- BEGIN HIERARCHY BROWSER --->
	<div id="hierarchyBrowserContainer"><div id="<?= $facet_name; ?>_facet_container">
	<div id="hierarchyBrowser" class='hierarchyBrowser'>
		<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
	</div><!-- end hierarchyBrowser -->

<?php
	if ($t_item && $t_subject) {
?>
	<div class="hierarchyBrowserHelpText">
		<?= _t("Click on a %1 to see more specific %2 within that %3. Click on the arrow next to a %4 to find %5 related to it.", $t_item->getProperty('NAME_SINGULAR'), $t_item->getProperty('NAME_PLURAL'), $t_item->getProperty('NAME_SINGULAR'), $t_item->getProperty('NAME_SINGULAR'), $t_subject->getProperty('NAME_PLURAL') ); ?>
	</div>
<?php
	}
?>
	</div></div>
	
	<script type="text/javascript">
			var oHierBrowser;
			
			jQuery(document).ready(function() {
				
				oHierBrowser = caUI.initHierBrowser('hierarchyBrowser', {
					levelDataUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyLevel', array('facet' => $facet_name)); ?>',
					initDataUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyAncestorList', array('facet' => $facet_name)); ?>',
					
					editUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addCriteria', array('facet' => $facet_name, 'id' => '')); ?>',
					editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__ ,1); ?>",
					
					initItemID: '<?= $this->getVar('browse_last_id'); ?>',
					indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
					
					currentSelectionDisplayID: 'browseCurrentSelection',
				selectMultiple: <?= ($multiple_selection_facet) ? 1 : 0; ?>
				});
			});
		</script>
<?php
			break;
		# ------------------------------------------------------------
		case 'none':
?>
	<div class="browseSelectPanelList" id='<?= $facet_name; ?>_facet_container'>
		<table class='browseSelectPanelListTable'>
<?php
			$row = array();
			foreach($facet as $vn_i => $item) {
?>
<?php
                $content_count = (isset($item['content_count']) && ($item['content_count'] > 0)) ? " (".$item['content_count'].")" : "";
				$row[] = "<td class='browseSelectPanelListCell facetItem' width='{$td_width}%;' data-facet_item_id='{$item['id']}'>".caNavLink($this->request, $item['label'], 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $facet_name, 'id' => $item['id'], 'mod_id' => $modify_id))."{$content_count}</td>";
				
				if (sizeof($row) == 5) {
					print "<tr valign='top'>".join('', $row)."</tr>\n";
					
					$row = [];
				}
			}
			if (sizeof($row) > 0) {
				if (sizeof($row) < 5) {
					for($vn_i = sizeof($row); $vn_i <= 5; $vn_i++) {
						$row[] = '<td> </td>';
					}
				}
				print "<tr valign='top'>".join('', $row)."</tr>\n";
			}
?>
		</table>
	</div>
<?php
			break;
		# ------------------------------------------------------------
		case 'alphabetical';
		default:
			$groups = array_keys($facet);
?>

	<div class="browseSelectPanelHeader">
		<div class="jumpToGroup">
<?php 	
	foreach($groups as $group) {
		print " <a href='#".(($group === '~') ? '~' : $group)."'>{$group}</a> ";
	}
?>
		</div>
	</div>
	<div class="browseSelectPanelList" id='<?= $facet_name; ?>_facet_container'>
<?php
			foreach($facet as $group => $items) {
				$row = array();
				if ($group === '~') {
					$group = '~';
				}
				print "<div class='browseSelectPanelListGroupHeading'><a name='{$group}' class='browseSelectPanelListGroupHeading'>{$group}</a></div>\n";
?>
		<table class='browseSelectPanelListTable'>
<?php
				foreach($items as $item) {
				    $content_count = (isset($item['content_count']) && ($item['content_count'] > 0)) ? " (".$item['content_count'].")" : "";
					$row[] = "<td class='browseSelectPanelListCell facetItem' width='{$td_width}%;' data-facet_item_id='{$item['id']}'>".caNavLink($this->request, $item['label'], 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($modify_id) > 0) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $facet_name, 'id' => $item['id'], 'mod_id' => $modify_id))."{$content_count}</td>";
					
					if (sizeof($row) == 5) {
						print "<tr valign='top'>".join('', $row)."</tr>\n";
						
						$row = [];
					}
				}
				if (sizeof($row) > 0) {
					if (sizeof($row) < 5) {
						for($vn_i = sizeof($row); $vn_i <= 5; $vn_i++) {
							$row[] = '<td> </td>';
						}
					}
					print "<tr valign='top'>".join('', $row)."</tr>\n";
				}
?>
		</table>
<?php
			}
?>
	</div>
<?php
			break;
		# ------------------------------------------------------------
	}
?>
	<a href="#" onclick="$('#showRefine').show(); caUIBrowsePanel.hideBrowsePanel(); " class="browseSelectPanelButton"><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, '18px'); ?></a>
	<div style='clear:both;width:100%'></div>

</div>

<script type="text/javascript">
	
<?php
if($multiple_selection_facet){
?>	
	jQuery(document).ready(function() {
		jQuery(".facetApply").hide();
		
		jQuery(".facetItem").on('click', function(e) { 
			if (jQuery(this).attr('facet_item_selected') == '1') {
				jQuery(this).attr('facet_item_selected', '');
			} else {
				jQuery(this).attr('facet_item_selected', '1');
			}
			
			if (jQuery(".facetItem[facet_item_selected='1']").length > 0) {
				jQuery("#facet_apply").show();
			} else {
				jQuery("#facet_apply").hide();
			}
			
			e.preventDefault();
			return false;
		});
		
		jQuery(".facetApply").on('click', function(e) { 
			var facet = '<?= $facet_name; ?>';
			var ids = [];
			jQuery.each(jQuery("#" + facet + "_facet_container").find("[facet_item_selected=1]"), function(k,v) {
				var id = jQuery(v).data('facet_item_id');
				if(!id) { id = jQuery(v).data('item_id'); }
				if ((''+id).length > 0) { ids.push(id); }
			});

			if(ids.length){
				window.location = '<?= caNavUrl($this->request, 'find', $this->request->getController(),((strlen($modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('mod_id' => $modify_id)); ?>/facet/' + facet + '/id/' + ids.join('|');
			}
			e.preventDefault();
		});
	});	
<?php
}
?>	
</script>
