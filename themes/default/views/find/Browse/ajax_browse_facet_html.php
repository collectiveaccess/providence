<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ajax_browse_facet.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	
	$va_facet = $this->getVar('facet');
	$vs_facet_name = $this->getVar('facet_name');
	$va_facet_info = $this->getVar('facet_info');
	
	$t_item = $this->getVar('t_item');
	$t_subject = $this->getVar('t_subject');
	
	$va_types = $this->getVar('type_list');
	$va_relationship_types = $this->getVar('relationship_type_list');
	
	$vb_individual_group_display = (bool)$this->getVar('individual_group_display');

	$vs_grouping_field = $this->getVar('grouping');
	if ((!isset($va_facet_info['groupings'][$vs_grouping_field]) || !($va_facet_info['groupings'][$vs_grouping_field])) && is_array($va_facet_info['groupings'])) { 
		$va_tmp = array_keys($va_facet_info['groupings']);
		$vs_grouping_field = $va_tmp[0]; 
	}
	
	$vn_element_datatype = null;
	if ($vs_grouping_attribute_element_code = (preg_match('!^ca_attribute_([\w]+)!', $vs_grouping_field, $va_matches)) ? $va_matches[1] : null) {
		$t_element = new ca_metadata_elements();
		$t_element->load(array('element_code' => $vs_grouping_attribute_element_code));
		$vn_grouping_attribute_id = $t_element->getPrimaryKey();
		$vn_element_datatype = $t_element->get('datatype');
	}
	
	$vs_group_mode = $this->getVar('group_mode');
	if (!$va_facet||!$vs_facet_name) { 
		print _t('No facet defined'); 
		return;
	}
	
	
	$vm_modify_id = $this->getVar('modify') ? $this->getVar('modify') : '0';
?>
<script type="text/javascript">
	function caUpdateFacetDisplay(grouping) {
		caUIBrowsePanel.showBrowsePanel('<?php print $vs_facet_name; ?>', <?php print ((intval($vm_modify_id) > 0) ? 'true' : 'false'); ?>, <?php print ((intval($vm_modify_id) > 0) ?  $vm_modify_id : 'null'); ?>, grouping);
	}
</script>
<div style="float: right;" id='browseFacetGroupingControls'>
<?php 
	if (isset($va_facet_info['groupings']) && is_array($va_facet_info['groupings']) && sizeof($va_facet_info['groupings'] )) {
		print _t('Group by').': '; 
		
		foreach($va_facet_info['groupings'] as $vs_grouping => $vs_grouping_label) {
			print "<a href='#' onclick='caUpdateFacetDisplay(\"{$vs_grouping}\");' style='".(($vs_grouping == $vs_grouping_field) ? 'font-weight: bold; font-style: italic;' : '')."'>{$vs_grouping_label}</a> ";
		}
	}
?>
</div>
<h2><?php print unicode_ucfirst($va_facet_info['label_plural']); ?></h2>


<div class="browseSelectPanelContentArea" id="browseSelectPanelContentArea">

<?php	
	$va_grouped_items = array();
	switch($va_facet_info['group_mode']) {
		# ------------------------------------------------------------
		case 'hierarchical';
?>
	<!--- BEGIN HIERARCHY BROWSER --->
	<div id="hierarchyBrowser" class='hierarchyBrowser'>
		<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
	</div><!-- end hierarchyBrowser -->

<?php
	if ($t_item && $t_subject) {
?>
	<div class="hierarchyBrowserHelpText">
		<?php print _t("Click on a %1 to see more specific %2 within that %3. Click on the arrow next to a %4 to find %5 related to it.", $t_item->getProperty('NAME_SINGULAR'), $t_item->getProperty('NAME_PLURAL'), $t_item->getProperty('NAME_SINGULAR'), $t_item->getProperty('NAME_SINGULAR'), $t_subject->getProperty('NAME_PLURAL') ); ?>
	</div>
<?php
	}
?>
	
	<script type="text/javascript">
			var oHierBrowser;
			
			jQuery(document).ready(function() {
				
				oHierBrowser = caUI.initHierBrowser('hierarchyBrowser', {
					levelDataUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyLevel', array('facet' => $vs_facet_name)); ?>',
					initDataUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacetHierarchyAncestorList', array('facet' => $vs_facet_name)); ?>',
					
					editUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addCriteria', array('facet' => $vs_facet_name, 'id' => '')); ?>',
					editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="<?php print _t("Browse with this term"); ?>">',
					
					initItemID: '<?php print $this->getVar('browse_last_id'); ?>',
					indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
					
					currentSelectionDisplayID: 'browseCurrentSelection'
				});
			});
		</script>
<?php
			break;
		# ------------------------------------------------------------
		case 'none':
?>
	<div class="browseSelectPanelList">
		<table class='browseSelectPanelListTable'>
<?php
			$va_row = array();
			foreach($va_facet as $vn_i => $va_item) {
				$vs_label = caGetLabelForDisplay($va_facet, $va_item, $va_facet_info);
				
				$va_row[] = "<td class='browseSelectPanelListCell'>".caNavLink($this->request, $vs_label, 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($vm_modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $vs_facet_name, 'id' => $va_item['id'], 'mod_id' => $vm_modify_id))."</td>";
				
				if (sizeof($va_row) == 5) {
					print "<tr valign='top'>".join('', $va_row)."</tr>\n";
					
					$va_row = array();
				}
			}
			if (sizeof($va_row) > 0) {
				if (sizeof($va_row) < 5) {
					for($vn_i = sizeof($va_row); $vn_i <= 5; $vn_i++) {
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
			$o_tep = new TimeExpressionParser();
		
			// TODO: how do we handle non-latin characters?
			$va_label_order_by_fields = isset($va_facet_info['order_by_label_fields']) ? $va_facet_info['order_by_label_fields'] : array('label');
			foreach($va_facet as $vn_i => $va_item) {
				$va_groups = array();
				switch($vs_grouping_field) {
					case 'label':
						$va_groups[] = mb_substr(trim($va_item[$va_label_order_by_fields[0]]), 0, 1, 'UTF-8');	
						break;
					case 'relationship_types':
						foreach($va_item['rel_type_id'] as $vs_g) {
							if (isset($va_relationship_types[$vs_g]['typename'])) {
								$va_groups[] = trim($va_relationship_types[$vs_g]['typename']);
							} else {
								$va_groups[] = trim($vs_g);
							}
						}
						break;
					case 'type':
						foreach($va_item['type_id'] as $vs_g) {
							if (isset($va_types[$vs_g]['name_plural'])) {
								$va_groups[] = trim($va_types[$vs_g]['name_plural']);
							} else {
								$va_groups[] = trim(_t('Type ').$vs_g);
							}
						}
						break;
					default:
						if ($vn_grouping_attribute_id) {
							switch($vn_element_datatype) {
								case 2: //date
									$va_tmp = explode(':', $vs_grouping_field);
									if(isset($va_item['ca_attribute_'.$vn_grouping_attribute_id]) && is_array($va_item['ca_attribute_'.$vn_grouping_attribute_id])) {
										foreach($va_item['ca_attribute_'.$vn_grouping_attribute_id] as $vn_i => $va_v) {
											$va_v = $o_tep->normalizeDateRange($va_v['value_decimal1'], $va_v['value_decimal2'], (isset($va_tmp[1]) && in_array($va_tmp[1], array('years', 'decades', 'centuries'))) ? $va_tmp[1] : 'decades');
											foreach($va_v as $vn_i => $vn_v) {
												$va_groups[] = trim($vn_v);
											}
										}
									}
									break;
								default:
									if(isset($va_item['ca_attribute_'.$vn_grouping_attribute_id]) && is_array($va_item['ca_attribute_'.$vn_grouping_attribute_id])) {
										foreach($va_item['ca_attribute_'.$vn_grouping_attribute_id] as $vn_i => $va_v) {
											$va_groups[] = trim($va_v['value_longtext1']);
										}
									}
									break;
							}
						} else {
							$va_groups[] = mb_substr(trim($va_item[$va_label_order_by_fields[0]]), 0, 1, 'UTF-8');	
						}
						break;
				}
				
				foreach($va_groups as $vs_group) {
					if (!($vs_group = trim(unicode_ucfirst($vs_group)))) { $vs_group = "~"; }
					$vs_alpha_key = '';
					foreach($va_label_order_by_fields as $vs_f) {
						$vs_alpha_key .= $va_item[$vs_f];
					}
					$vs_alpha_key = trim($vs_alpha_key);
					$va_grouped_items[$vs_group][$vs_alpha_key] = $va_item;
				}
			}
			
			// sort lists alphabetically
			foreach($va_grouped_items as $vs_key => $va_list) {
				ksort($va_list);
				$va_grouped_items[$vs_key] = $va_list;
			}
			ksort($va_grouped_items);
			$va_groups = array_keys($va_grouped_items);
?>

	<div class="browseSelectPanelHeader">
<?php 
	$vs_g = null;
	if($vb_individual_group_display) {
		if (!($vs_g = $this->getVar('only_show_group'))) { $vs_g = array_shift(array_keys($va_grouped_items)); }
	}
	
	print _t("Jump to").': '; 
	
	foreach($va_groups as $vs_group) {
		if ($vb_individual_group_display) {
			print " <a href='#' onclick='loadFacetGroup(\"".(($vs_group === '~') ? '~' : $vs_group)."\"); return false;' ".(($vs_g == $vs_group) ? "class='browseSelectPanelFacetGroupSelected'" : "class='browseSelectPanelFacetGroup'").">{$vs_group}</a> ";
		} else {
			print " <a href='#".(($vs_group === '~') ? '~' : $vs_group)."'>{$vs_group}</a> ";
		}
	}
?>
	</div>
	<div class="browseSelectPanelList" id="browseSelectPanelList">
<?php
			
			if (($vs_g) && (isset($va_grouped_items[$vs_g]))) {
				$va_grouped_items = array($vs_g => $va_grouped_items[$vs_g]);
			}
			foreach($va_grouped_items as $vs_group => $va_items) {
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
				
					$va_row[] = "<td class='browseSelectPanelListCell'>".caNavLink($this->request, $vs_label, 'browseSelectPanelLink', 'find', $this->request->getController(), ((strlen($vm_modify_id) > 0) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $vs_facet_name, 'id' => $va_item['id'], 'mod_id' => $vm_modify_id))."</td>";
					
					if (sizeof($va_row) == 5) {
						print "<tr valign='top'>".join('', $va_row)."</tr>\n";
						
						$va_row = array();
					}
				}
				if (sizeof($va_row) > 0) {
					if (sizeof($va_row) < 5) {
						for($vn_i = sizeof($va_row); $vn_i <= 5; $vn_i++) {
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
	</div>
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
</script>