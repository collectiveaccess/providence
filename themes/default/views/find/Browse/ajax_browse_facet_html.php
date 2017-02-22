<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ajax_browse_facet.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
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
 	
	$va_facet = $this->getVar('grouped_facet');
	$vs_facet_name = $this->getVar('facet_name');
	$va_facet_info = $this->getVar('facet_info');
	$vs_grouping_field = $this->getVar('grouping');
	$vs_group_mode = $va_facet_info["group_mode"];
	
	$t_item = $this->getVar('t_item');
	$t_subject = $this->getVar('t_subject');
	
	$va_types = $this->getVar('type_list');
	$va_relationship_types = $this->getVar('relationship_type_list');
	
	$va_row_size = $this->request->config->get('browse_row_size');
	$va_td_width = intval(100/$va_row_size);
	
	$vb_individual_group_display = (bool)$this->getVar('individual_group_display');

	$va_service_urls = caJSONLookupServiceUrl($this->request, $va_facet_info['table'], array('noInline' => 1, 'noSymbols' => 1));

	if (!$va_facet||!$vs_facet_name) { 
		print _t('No facet defined'); 
		return;
	}
	
	$vb_multiple_selection_facet = caGetOption('multiple', $va_facet_info, false, ['castTo' => 'boolean']);
	
	$vm_modify_id = $this->getVar('modify') ? $this->getVar('modify') : '0';
?>
<script type="text/javascript">
	function caUpdateFacetDisplay(grouping) {
		caUIBrowsePanel.showBrowsePanel('<?php print $vs_facet_name; ?>', <?php print ((intval($vm_modify_id) > 0) ? 'true' : 'false'); ?>, <?php print ((intval($vm_modify_id) > 0) ?  $vm_modify_id : 'null'); ?>, grouping);
	}
</script>

<div class="browseSelectPanelContentArea <?php print ($vb_multiple_selection_facet) ? "browseSelectMultiplePanelContentArea" : "" ?>" id="browseSelectPanelContentArea">
<?php
	if ($vb_multiple_selection_facet) {
?>
		<div class='applyFacetContainer'><a href="#" id="facet_apply" data-facet="<?php print $vs_facet_name; ?>" class="facetApply">Apply</a></div>
<?php
	}

	$va_grouped_items = array();
	switch($va_facet_info['group_mode']) {
		# ------------------------------------------------------------
		case 'hierarchical';
?>
	<h2 class='browse'><?php print unicode_ucfirst($va_facet_info['label_plural']); ?></h2>
	<div class='clearDivide'></div>
	<div id="hierarchyBrowserContainer"><div id="<?php print $vs_facet_name; ?>_facet_container">
		<div id="hierarchyBrowser" class='hierarchyBrowser'>
			<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
		</div>
		<div class="hierarchyBrowserSearchBar">
			<label for="hierarchyBrowserSearch"><?php print _t("Search"); ?>:</label>
			<input id="hierarchyBrowserSearch" type="text" size="40" />
			<span class="ui-helper-hidden-accessible" role="status" aria-live="polite"></span>
		</div>
<?php
		if ($t_item && $t_subject) {
?>
			<div class="hierarchyBrowserHelpText">
				<?php print _t("Click on a %1 to find %2 related to it. Click on the arrow next to a %3 to see more specific %4 within that %5, or use the search field.", $t_item->getProperty('NAME_SINGULAR'), $t_subject->getProperty('NAME_PLURAL'), $t_item->getProperty('NAME_SINGULAR'), $t_item->getProperty('NAME_PLURAL'), $t_item->getProperty('NAME_SINGULAR') ); ?>
			</div>
<?php
		}
?>
	</div></div>
	
	<script type="text/javascript">
		var oHierBrowser;

		jQuery(document).ready(function() {

			oHierBrowser = caUI.initHierBrowser('hierarchyBrowser', {
				levelDataUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyLevel', array('facet' => $vs_facet_name)); ?>',
				initDataUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyAncestorList', array('facet' => $vs_facet_name)); ?>',

				editUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addCriteria', array('facet' => $vs_facet_name, 'id' => '')); ?>',
				editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__ ,1); ?>",

				initItemID: '<?php print $this->getVar('browse_last_id'); ?>',
				indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",

				currentSelectionDisplayID: 'browseCurrentSelection',
				
				selectMultiple: <?php print ($vb_multiple_selection_facet) ? 1 : 0; ?>
			});

			jQuery('#hierarchyBrowserSearch').autocomplete({
				source: '<?php print $va_service_urls['search']; ?>',
				minLength: 3,
				delay: 800,
				html: true,
				select: function(event, ui) {
					if (parseInt(ui.item.id) > 0) {
						oHierBrowser.setUpHierarchy(ui.item.id); // jump browser to selected item
					}
					event.preventDefault();
					jQuery('#hierarchyBrowserSearch').val('');
				}
			});
		});
	</script>
<?php
			break;
		# ------------------------------------------------------------
		case 'none':
?>
	<h2 class='browse'><?php print unicode_ucfirst($va_facet_info['label_plural']); ?></h2>
	<div class='clearDivide'></div>

	<div class="browseSelectPanelList">
		<table class='browseSelectPanelListTable' id='<?php print $vs_facet_name; ?>_facet_container'>
<?php
			$va_row = array();
			foreach($va_facet as $vn_i => $va_item) {
				$vs_label = caGetLabelForDisplay($va_facet, $va_item, $va_facet_info);
				
				$va_row[] = "<td class='browseSelectPanelListCell facetItem' width='{$va_td_width}%;' data-facet_item_id='{$va_item['id']}'>".caNavLink($this->request, html_entity_decode($vs_label), 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($vm_modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $vs_facet_name, 'id' => urlencode($va_item['id']), 'mod_id' => $vm_modify_id))."</td>";
				
				if (sizeof($va_row) == $va_row_size) {
					print "<tr valign='top'>".join('', $va_row)."</tr>\n";
					
					$va_row = array();
				}
			}
			if (sizeof($va_row) > 0) {
				if (sizeof($va_row) < $va_row_size) {
					for($vn_i = sizeof($va_row); $vn_i <= $va_row_size; $vn_i++) {
						$va_row[] = '<td> </td>';
					}
				}
				print "<tr valign='top'>".join('', $va_row)."</tr>\n";
			}
?>
		</table>
	</div>
<?php
			break;
		# ------------------------------------------------------------
		case 'alphabetical';
		default:
			$va_groups = array_keys($va_facet);
?>

	<div class="browseSelectPanelHeader">
	<h2 class='browse'><?php print unicode_ucfirst($va_facet_info['label_plural']); ?></h2>

<?php 
	$vs_g = null;
	if($vb_individual_group_display) {
		if (!($vs_g = $this->getVar('only_show_group'))) { 
			$va_tmp = array_keys($va_grouped_items);
			$vs_g = array_shift($va_tmp);
		}
	}
		print "<div class='jumpToGroup'>";
	
		foreach($va_groups as $vs_group) {
			if ($vb_individual_group_display) {
				print " <a href='#' onclick='loadFacetGroup(\"".(($vs_group === '~') ? '~' : $vs_group)."\"); return false;' ".(($vs_g == $vs_group) ? "class='browseSelectPanelFacetGroupSelected'" : "class='browseSelectPanelFacetGroup'").">{$vs_group}</a> ";
			} else {
				print " <a href='#".(($vs_group === '~') ? '~' : $vs_group)."'>{$vs_group}</a> ";
			}
		}
?>	
		</div><!-- end jumpToGroup-->
		<div style="float: right; clear:right;" id='browseFacetGroupingControls'>
		<?php 
			if (isset($va_facet_info['groupings']) && is_array($va_facet_info['groupings']) && sizeof($va_facet_info['groupings'] )) {
				print _t('Group by').': '; 
		
				foreach($va_facet_info['groupings'] as $vs_grouping => $vs_grouping_label) {
					print "<a href='#' onclick='caUpdateFacetDisplay(\"{$vs_grouping}\");' style='".(($vs_grouping == $vs_grouping_field) ? 'color:#333; text-decoration:underline;' : '')."'>{$vs_grouping_label}</a> ";
				}
			}
		?>
		</div>		
	</div>
	<div class="browseSelectPanelList" id="browseSelectPanelList"><div id='<?php print $vs_facet_name; ?>_facet_container'>
<?php
			
			if (($vs_g) && (isset($va_facet[$vs_g]))) {
				$va_facet = array($vs_g => $va_facet[$vs_g]);
			}
			foreach($va_facet as $vs_group => $va_items) {
				$va_row = array();
				if ($vs_group === '~') {
					$vs_group = '~';
				}
				print "<div class='browseSelectPanelListGroupHeading'><a name='{$vs_group}' class='browseSelectPanelListGroupHeading'>{$vs_group}</a></div>\n";
?>
		<table class='browseSelectPanelListTable'>
<?php
				foreach($va_items as $va_item) {
					$vs_label = caGetLabelForDisplay($va_facet, $va_item, $va_facet_info);
				
					$va_row[] = "<td class='browseSelectPanelListCell facetItem' width='{$va_td_width}%;' data-facet_item_id='{$va_item['id']}'>".caNavLink($this->request, html_entity_decode($vs_label), 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($vm_modify_id) > 0) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $vs_facet_name, 'id' => urlencode($va_item['id']), 'mod_id' => $vm_modify_id))."</td>";
					
					if (sizeof($va_row) == $va_row_size) {
						print "<tr valign='top'>".join('', $va_row)."</tr>\n";
						
						$va_row = array();
					}
				}
				if (sizeof($va_row) > 0) {
					if (sizeof($va_row) < $va_row_size) {
						for($vn_i = sizeof($va_row); $vn_i <= $va_row_size; $vn_i++) {
							$va_row[] = '<td> </td>';
						}
					}
					print "<tr valign='top'>".join('', $va_row)."</tr>\n";
				}
?>
		</table>
<?php
			}
?>
	</div></div>
<?php
			break;
		# ------------------------------------------------------------
	}
?>
</div>

<script type="text/javascript">
	function loadFacetGroup(g) {
		jQuery('#browseSelectPanelContentArea').parent().load("<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacet', array('facet' => $vs_facet_name, 'grouping' => $this->getVar('grouping'), 'show_group' => '')); ?>" + escape(g));
	}
	
	
	
	
	
<?php
if($vb_multiple_selection_facet){
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
			var facet = '<?php print $vs_facet_name; ?>';
			
			var ids = [];
			jQuery.each(jQuery("#" + facet + "_facet_container").find("[facet_item_selected=1]"), function(k,v) {
				ids.push(jQuery(v).data('facet_item_id'));
			});

			if(ids.length){
				window.location = '<?php print caNavUrl($this->request, 'find', $this->request->getController(),((strlen($vm_modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('mod_id' => $vm_modify_id)); ?>/facet/' + facet + '/id/' + ids.join('|');
			}
			e.preventDefault();
		});
	});	
<?php
}
?>	
	
	
	
	
	
</script>