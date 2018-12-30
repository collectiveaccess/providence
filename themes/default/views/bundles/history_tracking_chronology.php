<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_history.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
 
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	
	$t_subject					= $this->getVar('t_subject');
	$subject_table				= $t_subject->tableName();
	
	$settings 					= $this->getVar('settings');

	$read_only					= (isset($settings['readonly']) && $settings['readonly']);
	
	$history					= $this->getVar('history');
	
	$vs_relationship_type		= $this->getVar('location_relationship_type');
	$vs_change_location_url		= $this->getVar('location_change_url');

	$va_storage_location_elements = caGetOption('ca_storage_locations_elements', $settings, array());
	
	$occ_types  				= $this->getVar('occurrence_types');
	$occ_lookup_params['types'] = join(",",array_map(function($v) { return $v['item_id']; }, $occ_types));
	
	$coll_types  				= $this->getVar('collection_types');
	$coll_lookup_params['types'] = join(",",array_map(function($v) { return $v['item_id']; }, $coll_types));
	
	$policy  					= $this->getVar('policy');
	$policy_info  				= $this->getVar('policy_info');
	
	$display_mode 				= caGetOption('displayMode', $settings, null);
	
	$allow_value_interstitial_edit 	= !caGetOption('hide_value_interstitial_edit', $settings, false);
	$allow_value_delete 		= !caGetOption('hide_value_delete', $settings, false);
	
	if (!($add_label = $this->getVar('add_label'))) { $add_label = _t('Update location'); }
	
    if (!$this->request->isAjax()) {
	    print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $settings);
	}
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
			<div class="caHistoryTrackingButtonBar labelInfo">
<?php
            if(!caGetOption('hide_include_child_history_controls', $settings, false) && ($this->getVar('child_count') > 0)) {
?>
                <div style='float: left;' class='button caSetChildViewButton'><a href="#" id="<?php print $vs_id_prefix; ?>SetChildView"><?php print caNavIcon(__CA_NAV_ICON_CHILD__, '15px'); ?> <?php print Session::getVar('ca_objects_history_showChildHistory') ? _t('Hide child history') : _t('Include child history'); ?></a></div>
<?php
            }
			if(!$read_only && !caGetOption('hide_add_to_loan_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_loans'))) {
?>
				<div style='float: left;' class='button caAddLoanButton'><a href="#" id="<?php print $vs_id_prefix; ?>AddLoan"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Add to loan'); ?></a></div>
<?php
			}
			if(!$read_only && !caGetOption('hide_update_location_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_storage_locations'))) {
?>
				<div style='float: left;'  class='button caChangeLocationButton'><a href="#" id="<?php print $vs_id_prefix; ?>ChangeLocation"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Update location'); ?></a></div>
<?php
			}
			
			if(!$read_only && !caGetOption('hide_add_to_occurrence_controls', $settings, false)) {
			
				foreach($occ_types as $vn_type_id => $va_type_info) {
					if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_occurrences', $va_type_info['idno'])) { continue; }
?>
				<div style='float: left;'  class='button caAddOccurrenceButton caAddOccurrenceButton<?php print $vn_type_id; ?>'><a href="#" id="<?php print $vs_id_prefix; ?>AddOcc<?php print $vn_type_id; ?>"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Add to %1', $va_type_info['name_singular']); ?></a></div>
<?php		
				}
			}
			
			if(!$read_only && !caGetOption('hide_add_to_collection_controls', $settings, false)) {
			
				foreach($coll_types as $vn_type_id => $va_type_info) {
					if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_collections', $va_type_info['idno'])) { continue; }
?>
				<div style='float: left;'  class='button caAddCollectionButton caAddCollectionButton<?php print $vn_type_id; ?>'><a href="#" id="<?php print $vs_id_prefix; ?>AddColl<?php print $vn_type_id; ?>"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Add to %1', $va_type_info['name_singular']); ?></a></div>
<?php		
				}
			}
			
?>
				<br style='clear: both;'/>
			</div>
					
		<div class="caLocationList"> </div>
		<div class="caLoanList"> </div>
<?php
if(!caGetOption('hide_add_to_occurrence_controls', $settings, false)) {
	foreach($occ_types as $vn_type_id => $va_type_info) {
		if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_occurrences', $va_type_info['idno'])) { continue; }
?>
		<div class="caOccurrenceList<?php print $vn_type_id; ?>"> </div>
<?php
	}
}
if(!caGetOption('hide_add_to_collection_controls', $settings, false)) {
	foreach($coll_types as $vn_type_id => $va_type_info) {
		if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_collections', $va_type_info['idno'])) { continue; }
?>
		<div class="caCollectionList<?php print $vn_type_id; ?>"> </div>
<?php
	}
}

switch($display_mode) {
	case 'tabs':
?>
			<div id="<?php print $vs_id_prefix; ?>Container" class="editorHierarchyBrowserContainer">		
				<div  id="<?php print $vs_id_prefix; ?>Tabs">
					<ul>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-location"><span><?php print _t('Current %1', mb_strtolower($policy_info['name'])); ?></span></a></li>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-history"><span><?php print _t('History'); ?></span></a></li>
					</ul>
					<div id="<?php print $vs_id_prefix; ?>Tabs-location" class="hierarchyBrowseTab">	
<?php
						if (($current_value = reset($history)) && ($current_value = array_shift($current_value))) { 
							print "<div class='caHistoryTrackingCurrent' style='background-color:#".$settings['currentValueColor']."'>{$current_value['icon']} {$current_value['display']}<div class=\"caHistoryTrackingEntryDate\">{$current_value['date']}</div></div>";	 
							
							print "<br class=\"clear\"/>\n";
						} else {
?>
							<?php print _t('No %1 set', mb_strtolower($policy_info['name'])); ?>
<?php
						}
?>
					</div>
					<div id="<?php print $vs_id_prefix; ?>Tabs-history" class="hierarchyBrowseTab caHistoryTrackingTab">	
<?php
						if (is_array($history) && sizeof($history)) {
							foreach($history as $vs_date => $history_by_date) {
								foreach($history_by_date as $history_entry) {
									switch($history_entry['status']) {
										case 'FUTURE':
											print "<div class='caHistoryTracking' style='background-color:#".$settings['futureValueColor']."'>".$history_entry['icon'].' '.$history_entry['display']."<div class=\"caHistoryTrackingEntryDate\">{$history_entry['date']}</div></div>\n";
											break;
										case 'CURRENT':
											print "<div class='caHistoryTrackingCurrent' style='background-color:#".$settings['currentValueColor']."'>".$history_entry['icon'].' '.$history_entry['display']."<div class=\"caHistoryTrackingEntryDate\">{$history_entry['date']}</div></div>\n";
											break;
										case 'PAST':
										default:
											print "<div class='caHistoryTracking' style='background-color:#".$settings['pastValueColor']."'>".$history_entry['icon'].' '.$history_entry['display']."<div class=\"caHistoryTrackingEntryDate\">{$history_entry['date']}</div></div>\n";
											break;	
									}
								}
							}
						} else {
?>
							<?php print _t('No %1 set', mb_strtolower($policy_info['name'])); ?>
<?php
						}
?>
					</div>
<?php
	// get current location
?>
				</div>
			</div>
<?php
	
		break;	
	case 'chronology':
	default:
		foreach($history as $vn_date => $history_entries_for_date) {
			foreach($history_entries_for_date as $vn_i => $history_entry) {
				switch($history_entry['status']) {
					case 'FUTURE':
						$color = $settings['futureValueColor'];
					break;
					case 'CURRENT':
						$color = $settings['currentValueColor'];
						break;
					case 'PAST':
					default:
						$color = $settings['pastValueColor'];
						break;
				}	
?>
				<div id="caHistoryTrackingEntry<?php print Datamodel::getTableName($history_entry['tracked_table_num']).'-'.$history_entry['tracked_row_id']; ?>" class="caHistoryTrackingEntry <?php print ($vn_i == 0) ? 'caHistoryTrackingEntryFirst' : ''; ?>" style="background-color:#<?php print $color; ?>">
					<?php print $history_entry['icon']; ?>
					<div><?php print $history_entry['display']; ?></div>
					
									
<?php
					if (!$read_only && $allow_value_interstitial_edit && ($history_entry['tracked_table_num'] !== $history_entry['current_table_num']) && ca_editor_uis::loadDefaultUI($history_entry['tracked_table_num'], $this->request)) {
?>
						<div class="caHistoryTrackingEntryInterstitialEdit"><a href="#" class="caInterstitialEditButton listRelEditButton" data-table="<?php print Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?php print $history_entry['tracked_row_id']; ?>"  data-primary="<?php print Datamodel::getTableName($history_entry['current_table_num']); ?>" data-primary_id="<?php print $history_entry['current_row_id']; ?>"><?php print caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a></div><?php
					}
					if (!$read_only && $allow_value_delete && !$vb_dont_show_del) {
?>
						<div class="caHistoryTrackingEntryDelete"><a href="#" class="caDeleteItemButton listRelDeleteButton"  data-table="<?php print Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?php print $history_entry['tracked_row_id']; ?>"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div><?php
					}
?>
					<div class="caHistoryTrackingEntryDate"><?php print $history_entry['date']; ?></div>
					<br class="clear"/>
				</div>
<?php
			}
		}
		break;
}
?>
	</div>
<?php
	//
	// Template to generate controls for creating new storage location
	//
?>
	<textarea class='caHistoryTrackingSetLocationTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Location_{n}" class="labelInfo caRelatedLocation">
			<h2 class="caHistoryTrackingSetLocationHeading"><?php print _t('Update location'); ?></h2>
<?php
	if (!(bool)$settings['useHierarchicalBrowser']) {
?>
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_location_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_location_autocomplete{n}" class="lookupBg"/>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_location_id{n}" id="<?php print $vs_id_prefix; ?>_location_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteLocationButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
					</td>
				</tr>
			</table>
<?php
	} else {
?>
			<div style="float: right;"><a href="#" class="caDeleteLocationButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			
			<div style='width: 700px; height: 200px;'>				
				<div style="float: right;">
					<div class='hierarchyBrowserSearchBar'><?php print _t('Search'); ?>: <input type='text' id='<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='40'/></div>
				</div>
				
				<div class="clear"><!-- empty --></div>
				
				<div id='<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}' style='width: 100%; height: 165px;' class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	
				
				<div class="hierarchyBrowserCurrentSelectionText">
					<input type="hidden" name="<?php print $vs_id_prefix; ?>_location_id{n}" id="<?php print $vs_id_prefix; ?>_location_id{n}" value="{id}"/>
				
					<span class="hierarchyBrowserCurrentSelectionText" id="<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}"> </span>
				</div>
				
				<div style="clear: both; width: 1px; height: 5px;"><!-- empty --></div>
			</div>
			
			<div class="clear" style="height: 20px;"><!-- empty --></div>
				
			<table class='caHistoryTrackingUpdateLocationMetadata'><?php 
				if(is_array($va_storage_location_elements) && sizeof($va_storage_location_elements)) {
					$t_rel = Datamodel::getInstanceByTableName('ca_objects_x_storage_locations', true);		
					foreach($va_storage_location_elements as $vs_element) {
						print "<tr>";
						if ($t_rel->hasField($vs_element)) {
							switch($t_rel->getFieldInfo($vs_element, 'FIELD_TYPE')) {
								case FT_DATETIME:
								case FT_HISTORIC_DATETIME:
								case FT_DATERANGE:
								case FT_HISTORIC_DATERANGE:
									$vs_field_class = 'dateBg';
									break;
								default:
									$vs_field_class = '';
									break;
							}
							print "<td><div class='attributeListItem'>".$t_rel->getDisplayLabel($t_rel->tableName().".".$vs_element)."</td><td>".$t_rel->htmlFormElement($vs_element, '', ['name' => $vs_id_prefix.'_location_'.$vs_element.'{n}', 'id' => $vs_id_prefix.'_location_'.$vs_element.'{n}', 'value' => _t('now'), 'classname' => $vs_field_class])."</td>";
						} else {
							print "<td>".$t_rel->getDisplayLabel($t_rel->tableName().".".$vs_element)."</td><td>".$t_rel->getAttributeHTMLFormBundle($this->request, null, $vs_element, $this->getVar('placement_code'), $settings, ['elementsOnly' => true])."</td>";
						}	
						print "</tr>\n";
					}
				}
			?></table>
			
			<div class="clear"><!-- empty --></div>
		
			<script type='text/javascript'>
				jQuery(document).ready(function() { 
					var <?php print $vs_id_prefix; ?>oHierBrowser{n} = caUI.initHierBrowser('<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}', {
						uiStyle: 'horizontal',
						levelDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyLevel', array()); ?>',
						initDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyAncestorList'); ?>',
					
						selectOnLoad : true,
						browserWidth: '100%',
					
						dontAllowEditForFirstLevel: false,
					
						className: 'hierarchyBrowserLevel',
						classNameContainer: 'hierarchyBrowserContainer',
					
						indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
						editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						disabledButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
					
						displayCurrentSelectionOnLoad: false,
						currentSelectionDisplayID: '<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}',
						onSelection: function(item_id, parent_id, name, display, type_id) {
							caRelationBundle<?php print $vs_id_prefix; ?>.select('{n}', {id: item_id, type_id: type_id}, display);
						}
					});
				
					jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').autocomplete({
							source: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', array('noInline' => 1)); ?>',
							minLength: 3, delay: 800, html: true,
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									<?php print $vs_id_prefix; ?>oHierBrowser{n}.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								event.preventDefault();
								jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').val('');
							}
						}
					);
					
					jQuery('#<?php print $vs_id_prefix; ?>_location_effective_date{n}').datepicker({dateFormat: 'yy-mm-dd'});  // attempt to add date picker
				});
			</script>
<?php
	}
?>
		</div>
	</textarea>
<?php

if(!caGetOption('hide_add_to_loan_controls', $settings, false)) {
?>
	<textarea class='caHistoryTrackingSetLoanTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Loan_{n}" class="labelInfo caRelatedLoan">
			<table class="caListItem">
				<tr>
					<td><h2><?php print _t('Add to loan'); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_loan_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_loan_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_loan_type_id{n}" id="<?php print $vs_id_prefix; ?>_loan_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_loan_id{n}" id="<?php print $vs_id_prefix; ?>_loan_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteLoanButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
<?php
	}

if(!caGetOption('hide_add_to_occurrence_controls', $settings, false)) {
	foreach($occ_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='caHistoryTrackingSetOccurrenceTemplate<?php print $vn_type_id; ?>' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Occurrence_<?php print $vn_type_id; ?>_{n}" class="labelInfo caRelatedOccurrence">
			<table class="caListItem">
				<tr>
					<td><h2><?php print _t('Add to %1', $va_type_info['name_singular']); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteOccurrenceButton<?php print $vn_type_id; ?>"><?php print caNavIcon($this->request, __CA_NAV_ICON_DEL_BUNDLE__); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
<?php
	}
}

if(!caGetOption('hide_add_to_collection_controls', $settings, false)) {
	foreach($coll_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='caHistoryTrackingSetCollectionTemplate<?php print $vn_type_id; ?>' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Collection_<?php print $vn_type_id; ?>_{n}" class="labelInfo caRelatedCollection">
			<table class="caListItem">
				<tr>
					<td><h2><?php print _t('Add to %1', $va_type_info['name_singular']); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteCollectionButton<?php print $vn_type_id; ?>"><?php print caNavIcon($this->request, __CA_NAV_ICON_DEL_BUNDLE__); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
<?php
	}
}
?>
</div>

<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Quick Add'); ?></div>
		
	</div>
</div>
<div id="caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>" class="caRelationInterstitialEditPanel"> 
	<div id="caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Edit'); ?></div>
		
	</div>
</div>

<?php
	if (!$read_only) {
?>
<script type="text/javascript">
	var caRelationQuickAddPanel<?php print $vs_id_prefix; ?>, caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
	
		jQuery("#<?php print $vs_id_prefix; ?>").find(".caInterstitialEditButton").on('click', null,  {}, function(e) {
			// Trigger interstitial edit panel
			var table = jQuery(this).data('table');
			var relation_id = jQuery(this).data('relation_id');
			var primary = jQuery(this).data('primary');
			var primary_id = jQuery(this).data('primary_id');
			var u = '<?php print caNavUrl($this->request, 'editor', 'Interstitial', 'Form', []); ?>/t/' + table + '/relation_id/' + relation_id + '/primary/' + primary + '/primary_id/' + primary_id;
		
			caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>.showPanel(u);
		
			jQuery('#' + caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>.getPanelContentID()).data('panel', caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>);
		
			e.preventDefault();
			return false;
		});
	
		jQuery("#<?php print $vs_id_prefix; ?>").find(".caDeleteItemButton").on('click', null,  {}, function(e) {
			// handle delete of chronology item
			var table = jQuery(this).data('table');
			var relation_id = jQuery(this).data('relation_id');
		
			jQuery("#caHistoryTrackingEntry" + table + "-" + relation_id).remove();
			jQuery("#<?php print $vs_id_prefix; ?>").append("<input type='hidden' name='<?php print $vs_id_prefix; ?>_delete_" + table + "[]' value='" +relation_id + "'/>");
		});
		
	<?php if($display_mode === 'tabs') { ?>
			jQuery("#<?php print $vs_id_prefix; ?>Tabs").tabs({ selected: 0 });	
	<?php } ?>	
	
			if (caUI.initPanel) {
				caRelationQuickAddPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
					panelID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
					panelContentID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
					exposeBackgroundColor: "#000000",				
					exposeBackgroundOpacity: 0.7,					
					panelTransitionSpeed: 400,						
					closeButtonSelector: ".close",
					center: true,
					onOpenCallback: function() {
						jQuery("#topNavContainer").hide(250);
					},
					onCloseCallback: function() {
						jQuery("#topNavContainer").show(250);
					}
				});
				caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
					panelID: "caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
					panelContentID: "caRelationInterstitialEditPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
					exposeBackgroundColor: "#000000",				
					exposeBackgroundOpacity: 0.7,					
					panelTransitionSpeed: 400,						
					closeButtonSelector: ".close",
					center: true,
					onOpenCallback: function() {
						jQuery("#topNavContainer").hide(250);
					},
					onCloseCallback: function() {
						jQuery("#topNavContainer").show(250);
						if(caBundleUpdateManager) { 
							caBundleUpdateManager.reloadBundle('history_tracking_chronology'); 
							caBundleUpdateManager.reloadInspector(); 
						}
					}
				});
			}
		
			caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?php print $vs_id_prefix; ?>_location_',
				templateValues: ['label', 'type_id', 'id'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?php print $vs_id_prefix; ?>Location_',
				placementID: '<?php print $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetLocationTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caLocationList',
				listItemClassName: 'caRelatedLocation',
				addButtonClassName: 'caChangeLocationButton',
				deleteButtonClassName: 'caDeleteLocationButton',
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?php print json_encode($this->getVar('location_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', []); ?>',
				minChars:1,
				readonly: false,
				isSortable: false,
				listSortItems: 'div.roundedRel',			
				autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
				quickaddUrl: '<?php print caNavUrl($this->request, 'editor/storage_locations', 'StorageLocationQuickAdd', 'Form', array('location_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				addMode: 'prepend',
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
			
			caRelationBundle<?php print $vs_id_prefix; ?>_ca_loans = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?php print $vs_id_prefix; ?>_loan_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?php print $vs_id_prefix; ?>Loan_',
				placementID: '<?php print $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetLoanTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caLoanList',
				listItemClassName: 'caRelatedLoan',
				addButtonClassName: 'caAddLoanButton',
				deleteButtonClassName: 'caDeleteLoanButton',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?php print json_encode($this->getVar('loan_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Loan', 'Get', []); ?>',
				types: <?php print json_encode($settings['restrict_to_types']); ?>,
				readonly: <?php print $read_only ? "true" : "false"; ?>,
				isSortable: <?php print ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?php print $vs_id_prefix; ?>LoanBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
				quickaddUrl: '<?php print caNavUrl($this->request, 'editor/loans', 'LoanQuickAdd', 'Form', array('loan_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
	<?php
	if(!caGetOption('hide_add_to_occurrence_controls', $settings, false)) {
		foreach($occ_types as $vn_type_id => $va_type_info) {
	?>
			caRelationBundle<?php print $vs_id_prefix; ?>_ca_occurrences_<?php print $vn_type_id; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?php print $vs_id_prefix; ?>Occurrence_<?php print $vn_type_id; ?>_',
				placementID: '<?php print $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetOccurrenceTemplate<?php print $vn_type_id; ?>',
				initialValueTemplateClassName: null,
				itemListClassName: 'caOccurrenceList<?php print $vn_type_id; ?>',
				listItemClassName: 'caRelatedOccurrence',
				addButtonClassName: 'caAddOccurrenceButton<?php print $vn_type_id; ?>',
				deleteButtonClassName: 'caDeleteOccurrenceButton<?php print $vn_type_id; ?>',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?php print json_encode($this->getVar('occurrence_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Occurrence', 'Get', $occ_lookup_params); ?>',
				types: <?php print json_encode($settings['restrict_to_types']); ?>,
				readonly: <?php print $read_only ? "true" : "false"; ?>,
				isSortable: <?php print ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?php print $vs_id_prefix; ?>OccurrenceBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
				quickaddUrl: '<?php print caNavUrl($this->request, 'editor/occurrences', 'OccurrenceQuickAdd', 'Form', array('types' => $vn_type_id,'occurrence_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
	<?php
		}
	}
	if(!caGetOption('hide_add_to_collection_controls', $settings, false)) {
		foreach($coll_types as $vn_type_id => $va_type_info) {
	?>
			caRelationBundle<?php print $vs_id_prefix; ?>_ca_collections_<?php print $vn_type_id; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?php print $vs_id_prefix; ?>Collection_<?php print $vn_type_id; ?>_',
				placementID: '<?php print $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetCollectionTemplate<?php print $vn_type_id; ?>',
				initialValueTemplateClassName: null,
				itemListClassName: 'caCollectionList<?php print $vn_type_id; ?>',
				listItemClassName: 'caRelatedCollection',
				addButtonClassName: 'caAddCollectionButton<?php print $vn_type_id; ?>',
				deleteButtonClassName: 'caDeleteCollectionButton<?php print $vn_type_id; ?>',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?php print json_encode($this->getVar('collection_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'collection', 'Get', $coll_lookup_params); ?>',
				types: <?php print json_encode($settings['restrict_to_types']); ?>,
				readonly: <?php print $read_only ? "true" : "false"; ?>,
				isSortable: <?php print ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?php print $vs_id_prefix; ?>CollectionBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?php print $vs_id_prefix; ?>_collection_<?php print $vn_type_id; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
				quickaddUrl: '<?php print caNavUrl($this->request, 'editor/collections', 'collectionQuickAdd', 'Form', array('types' => $vn_type_id,'collection_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
	<?php
		}
	}
	?>
		jQuery('#<?php print $vs_id_prefix; ?>SetChildView').on('click', function(e) {
			if(caBundleUpdateManager) { 
				caBundleUpdateManager.reloadBundle('ca_objects_history', {'showChildHistory': <?php print Session::getVar('ca_objects_history_showChildHistory') ? 0 : 1; ?>}); 
				caBundleUpdateManager.reloadBundle('history_tracking_chronology', {'showChildHistory': <?php print Session::getVar('ca_objects_history_showChildHistory') ? 0 : 1; ?>}); 
			}

		});
	});
</script>
<?php
	}
