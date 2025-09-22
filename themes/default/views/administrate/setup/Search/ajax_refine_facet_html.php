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
$facet 				= $this->getVar('facet');
$facet_name 			= $this->getVar('facet_name');
$facet_info 			= $this->getVar('facet_info');
$types 				= $this->getVar('type_list');
$relationship_types 	= $this->getVar('relationship_type_list');

$grouping_field 		= $this->getVar('grouping');
if ((!isset($facet_info['groupings'][$grouping_field]) || !($facet_info['groupings'][$grouping_field])) && is_array($facet_info['groupings'])) { 
	$tmp = array_keys($facet_info['groupings']);
	$grouping_field = $tmp[0]; 
}

$element_datatype = null;
if ($grouping_attribute_element_code = (preg_match('!^ca_attribute_([\w]+)!', $grouping_field, $matches)) ? $matches[1] : null) {
	$t_element = new ca_metadata_elements();
	$t_element->load(array('element_code' => $grouping_attribute_element_code));
	$grouping_attribute_id = $t_element->getPrimaryKey();
	$element_datatype = $t_element->get('datatype');
}

$group_mode 			= $this->getVar('group_mode');
if (!$facet||!$facet_name) { 
	print _t('No facet defined'); 
	return;
}

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
<h2><?= caUcFirstUTF8Safe($facet_info['label_plural']); ?></h2>


<div class="browseSelectPanelContentArea">

<?php	
	$grouped_items = array();
	switch($facet_info['group_mode']) {
		# ------------------------------------------------------------
		case 'none':
?>
	<div class="browseSelectPanelList">
		<table class='browseSelectPanelListTable'>
<?php
			$row = array();
			foreach($facet as $i => $item) {
?>
<?php
				$row[] = "<td class='browseSelectPanelListCell'>".caNavLink($this->request, $item['label'], 'browseSelectPanelLink', $this->request->getModulePath(), $this->request->getController(), ((strlen($modify_id)) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $facet_name, 'id' => $item['id'], 'mod_id' => $modify_id))."</td>";
				
				if (sizeof($row) == 5) {
					print "<tr valign='top'>".join('', $row)."</tr>\n";
					
					$row = array();
				}
			}
			if (sizeof($row) > 0) {
				if (sizeof($row) < 5) {
					for($i = sizeof($row); $i <= 5; $i++) {
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
			$o_tep = new TimeExpressionParser();
		
			// TODO: how do we handle non-latin characters?
			$label_order_by_fields = isset($facet_info['order_by_label_fields']) ? $facet_info['order_by_label_fields'] : array('label');
			foreach($facet as $i => $item) {
				$groups = array();
				switch($grouping_field) {
					case 'label':
						$groups[] = mb_substr($item[$label_order_by_fields[0]], 0, 1);	
						break;
					case 'relationship_types':
						foreach($item['rel_type_id'] as $g) {
							if (isset($relationship_types[$g]['typename'])) {
								$groups[] = $relationship_types[$g]['typename'];
							} else {
								$groups[] = $g;
							}
						}
						break;
					case 'type':
						foreach($item['type_id'] as $g) {
							if (isset($types[$g]['name_plural'])) {
								$groups[] = $types[$g]['name_plural'];
							} else {
								$groups[] = _t('Type ').$g;
							}
						}
						break;
					default:
						if ($grouping_attribute_id) {
							switch($element_datatype) {
								case 2: //date
									$tmp = explode(':', $grouping_field);
									if(isset($item['ca_attribute_'.$grouping_attribute_id]) && is_array($item['ca_attribute_'.$grouping_attribute_id])) {
										foreach($item['ca_attribute_'.$grouping_attribute_id] as $i => $v) {
											$v = $o_tep->normalizeDateRange($v['value_decimal1'], $v['value_decimal2'], (isset($tmp[1]) && in_array($tmp[1], array('years', 'decades', 'centuries'))) ? $tmp[1] : 'decades');
											foreach($v as $i => $v) {
												$groups[] = $v;
											}
										}
									}
									break;
								default:
									if(isset($item['ca_attribute_'.$grouping_attribute_id]) && is_array($item['ca_attribute_'.$grouping_attribute_id])) {
										foreach($item['ca_attribute_'.$grouping_attribute_id] as $i => $v) {
											$groups[] = $v['value_longtext1'];
										}
									}
									break;
							}
						} else {
							$groups[] = mb_substr($item[$label_order_by_fields[0]], 0, 1);	
						}
						break;
				}
				
				foreach($groups as $group) {
					$group = caUcFirstUTF8Safe($group);
					$alpha_key = '';
					foreach($label_order_by_fields as $f) {
						$alpha_key .= $item[$f];
					}
					$alpha_key = trim($alpha_key);
					if (preg_match('!^[A-Z0-9]{1}!', $group)) {
						$grouped_items[$group][$alpha_key] = $item;
					} else {
						$grouped_items['~'][$alpha_key] = $item;
					}
				}
			}
			
			// sort lists alphabetically
			foreach($grouped_items as $key => $list) {
				ksort($list);
				$grouped_items[$key] = $list;
			}
			ksort($grouped_items);
			$groups = array_keys($grouped_items);
?>

	<div class="browseSelectPanelHeader">
<?php 
	print _t("Jump to").': '; 
	
	foreach($groups as $group) {
		print " <a href='#".(($group === '~') ? '~' : $group)."'>{$group}</a> ";
	}
?>
	</div>
	<div class="browseSelectPanelList">
<?php
			foreach($grouped_items as $group => $items) {
				$row = array();
				if ($group === '~') {
					$group = '~';
				}
				print "<div class='browseSelectPanelListGroupHeading'><a name='{$group}' class='browseSelectPanelListGroupHeading'>{$group}</a></div>\n";
?>
		<table class='browseSelectPanelListTable'>
<?php
				foreach($items as $item) {
					$row[] = "<td class='browseSelectPanelListCell'>".caNavLink($this->request, $item['label'], 'browseSelectPanelLink', $this->request->getModulePath(), $this->request->getController(), ((strlen($modify_id) > 0) ? 'modifyCriteria' : 'addCriteria'), array('facet' => $facet_name, 'id' => $item['id'], 'mod_id' => $modify_id))."</td>";
					
					if (sizeof($row) == 5) {
						print "<tr valign='top'>".join('', $row)."</tr>\n";
						
						$row = array();
					}
				}
				if (sizeof($row) > 0) {
					if (sizeof($row) < 5) {
						for($i = sizeof($row); $i <= 5; $i++) {
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
</div>
