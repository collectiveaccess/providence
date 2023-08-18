<?php
/* ----------------------------------------------------------------------
 * bundles/history_tracking_chronology.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
	$bundle_name				= $this->getVar('bundle_name');
	
	$history					= $this->getVar('history');
	$current_value 				= $t_subject->getCurrentValue();
	
	$placement_code 			= $this->getVar('placement_code');
	$placement_id				= (int)($settings['placement_id'] ?? null);
	
	$show_return_home_controls = false;
	if($t_subject->hasField('home_location_id') && !caGetOption('hide_return_to_home_location_controls', $settings, false) && ($home_location_id = (int)$t_subject->get('home_location_id')) && (($current_value['type'] !== 'ca_storage_locations') || ((int)$current_value['id'] !== $home_location_id))) {
		$show_return_home_controls = true;
	}

	$vs_relationship_type		= $this->getVar('location_relationship_type');
	$vs_change_location_url		= $this->getVar('location_change_url');
	
	$occ_types  				= $this->getVar('occurrence_types');
	$occ_lookup_params['types'] = join(",",array_map(function($v) { return $v['item_id']; }, $occ_types));
	
	$coll_types  				= $this->getVar('collection_types');
	$coll_lookup_params['types'] = join(",",array_map(function($v) { return $v['item_id']; }, $coll_types));
	
	$entity_types  				= $this->getVar('entity_types');
	$entity_lookup_params['types'] = join(",",array_map(function($v) { return $v['item_id']; }, $entity_types));
	
	$policy  					= $this->getVar('policy');
	$policy_info  				= $this->getVar('policy_info');
	
	$display_mode 				= caGetOption('displayMode', $settings, null);
	
	$allow_value_interstitial_edit 	= !caGetOption('hide_value_interstitial_edit', $settings, false);
	$allow_value_delete 		= !caGetOption('hide_value_delete', $settings, false);
	
	$batch			= $this->getVar('batch');
	
	$home_location_idno = null;
	if ($t_subject->hasField('home_location_id')) {
		$t_location = ca_storage_locations::find($t_subject->get('home_location_id'), ['returnAs' => 'firstModelInstance']);
		$home_location_idno = $t_location ? $t_location->getWithTemplate($this->request->config->get('ca_storage_locations_hierarchy_browser_display_settings')) : null;
	}
	
    if (!$this->request->isAjax()) {
    	if ($batch) {
			print caBatchEditorRelationshipModeControl($t_subject, $vs_id_prefix);
		} else {
			print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $settings);
			print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $settings);
		}
	}
	
	$show_loan_controls = $show_movement_controls = $show_location_controls = $show_object_controls = $show_occurrence_controls = $show_collection_controls = $show_entity_controls = false;
?>
<div id="<?= $vs_id_prefix; ?>" <?= $batch ? "class='editorBatchBundleContent'" : ''; ?>>
	<div class="bundleContainer">
			<div class="caHistoryTrackingButtonBar labelInfo">
<?php
            if(!caGetOption('hide_include_child_history_controls', $settings, false) && ($this->getVar('child_count') > 0)) {
?>
                <div style='float: left;' class='button caSetChildViewButton'><a href="#" id="<?= $vs_id_prefix; ?>SetChildView"><?= caNavIcon(__CA_NAV_ICON_CHILD__, '15px'); ?> <?= Session::getVar('ca_objects_history_showChildHistory') ? _t('Hide child history') : _t('Include child history'); ?></a></div>
<?php
            }
			if(!$read_only && !caGetOption('hide_add_to_loan_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_loans'))) {
			    $show_loan_controls = true;
?>			    
				<div style='float: left;' class='button caAddLoanButton'><a href="#" id="<?= $vs_id_prefix; ?>AddLoan"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= caGetOption('loan_control_label', $settings, _t('Add to loan'), ['defaultOnEmptyString' => true]); ?></a></div>
<?php
			}
			if(!$read_only && !caGetOption('hide_add_to_movement_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_movements'))) {
                $show_movement_controls = true;
?>
				<div style='float: left;' class='button caAddMovementButton'><a href="#" id="<?= $vs_id_prefix; ?>AddMovement"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= caGetOption('movement_control_label', $settings, _t('Add to movement'), ['defaultOnEmptyString' => true]); ?></a></div>
<?php
			}
			if(!$read_only && !caGetOption('hide_update_location_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_storage_locations'))) {
                $show_location_controls = true;
?>
				<div style='float: left;'  class='button caChangeLocationButton <?= $vs_id_prefix; ?>caChangeLocationButton'><a href="#" id="<?= $vs_id_prefix; ?>ChangeLocation"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= caGetOption('update_location_control_label', $settings, _t('Update location'), ['defaultOnEmptyString' => true]); ?></a></div>
<?php
			}
			
			if(!$read_only && $show_return_home_controls && !caGetOption('hide_return_to_home_location_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_storage_locations'))) {
?>
				<div style='float: left;' class='button caReturnToHomeLocationButton <?= $vs_id_prefix; ?>caReturnToHomeLocationButton'><a href="#" id="<?= $vs_id_prefix; ?>ReturnToHomeLocation"><?= caNavIcon(__CA_NAV_ICON_HOME__, '15px'); ?> <?= caGetOption('return_to_home_location_control_label', $settings, _t('Return to home location'), ['defaultOnEmptyString' => true]); ?></a></div>
<?php
			}
			
			if(!$read_only && !caGetOption('hide_add_to_occurrence_controls', $settings, false)) {
			    $show_occurrence_controls = true;
			
				foreach($occ_types as $vn_type_id => $va_type_info) {
					if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_occurrences', $va_type_info['idno'])) { continue; }
?>
					<div style='float: left;' class='button caAddOccurrenceButton <?= $vs_id_prefix; ?>caAddOccurrenceButton<?= $vn_type_id; ?>'><a href="#" id="<?= $vs_id_prefix; ?>AddOcc<?= $vn_type_id; ?>"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= caGetOption('occurrence_control_label', $settings, _t('Add to %1', caGetOption('name_singular', $va_type_info, 'occurrence')), ['defaultOnEmptyString' => true]); ?></a></div>
<?php					
				}
			}
			
			if(!$read_only && !caGetOption('hide_add_to_collection_controls', $settings, false)) {
			    $show_collection_controls = true;
			
				foreach($coll_types as $vn_type_id => $va_type_info) {
					if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_collections', $va_type_info['idno'])) { continue; }
?>
				<div style='float: left;'  class='button caAddCollectionButton <?= $vs_id_prefix; ?>caAddCollectionButton<?= $vn_type_id; ?>'><a href="#" id="<?= $vs_id_prefix; ?>AddColl<?= $vn_type_id; ?>"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= _t(caGetOption('collection_control_label', $settings, _t('Add to %1', caGetOption('name_singular', $va_type_info, 'collection')), ['defaultOnEmptyString' => true]), $va_type_info['name_singular']); ?></a></div>
<?php		
				}
			}
			
			if(!$read_only && !caGetOption('hide_add_to_entity_controls', $settings, false)) {
			    $show_entity_controls = true;
			
				foreach($entity_types as $vn_type_id => $va_type_info) {
					if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_entities', $va_type_info['idno'])) { continue; }
?>
				<div style='float: left;' class='button caAddEntityButton <?= $vs_id_prefix; ?>caAddEntityButton<?= $vn_type_id; ?>'><a href="#" id="<?= $vs_id_prefix; ?>AddEntity<?= $vn_type_id; ?>"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= _t(caGetOption('entity_control_label', $settings, _t('Add to %1', caGetOption('name_singular', $va_type_info, 'entity')), ['defaultOnEmptyString' => true]), $va_type_info['name_singular']); ?></a></div>
<?php		
				}
			}
			
			if(!$read_only && !caGetOption('hide_add_to_object_controls', $settings, false) && ($subject_table::historyTrackingPolicyUses($policy, 'ca_objects'))) {
			    $show_object_controls = true;
?>
				<div style='float: left;' class='button caAddObjectButton'><a href="#" id="<?= $vs_id_prefix; ?>AddObject"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= caGetOption('object_control_label', $settings, _t('Add to object'), ['defaultOnEmptyString' => true]); ?></a></div>
<?php
			}
?>
				<br style='clear: both;'/>
			</div>
					
		<div class="<?= $vs_id_prefix; ?>caLocationList"> </div>
		<div class="caLoanList"> </div>
		<div class="caMovementList"> </div>
		<div class="caObjectList"> </div>
<?php
if($show_occurrence_controls) {
	foreach($occ_types as $vn_type_id => $va_type_info) {
		if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_occurrences', $va_type_info['idno'])) { continue; }
?>
		<div class="<?= $vs_id_prefix; ?>caOccurrenceList<?= $vn_type_id; ?>"> </div>
<?php
	}
}
if($show_collection_controls) {
	foreach($coll_types as $vn_type_id => $va_type_info) {
		if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_collections', $va_type_info['idno'])) { continue; }
?>
		<div class="caCollectionList<?= $vn_type_id; ?>"> </div>
<?php
	}
}
if($show_entity_controls) {
	foreach($entity_types as $vn_type_id => $va_type_info) {
		if (!$subject_table::historyTrackingPolicyUses($policy, 'ca_entities', $va_type_info['idno'])) { continue; }
?>
		<div class="caEntityList<?= $vn_type_id; ?>"> </div>
<?php
	}
}

switch($display_mode) {
	case 'tabs':
?>
			<div id="<?= $vs_id_prefix; ?>Container" class="editorHierarchyBrowserContainer">		
				<div id="<?= $vs_id_prefix; ?>Tabs">
					<ul>
						<li><a href="#<?= $vs_id_prefix; ?>Tabs-location"><span><?= _t('Current %1', mb_strtolower($policy_info['name'])); ?></span></a></li>
						<li><a href="#<?= $vs_id_prefix; ?>Tabs-history"><span><?= _t('History'); ?></span></a></li>
					</ul>
					<div id="<?= $vs_id_prefix; ?>Tabs-location" class="hierarchyBrowseTab">	
<?php
						if (($current_value = array_reduce($history, function($c, $v) { 
						    foreach($v as $e) {
						        if ($e['status'] === 'CURRENT') { $c[] = $e; }
						    }
						    return $c;
						}, [])) && ($current_value = array_shift($current_value))) { 
							print "<div id='caHistoryTrackingEntry{$vs_id_prefix}".Datamodel::getTableName($current_value['tracked_table_num']).'-'.$current_value['tracked_row_id']."' class='caHistoryTrackingCurrent' style='background-color:#".$settings['currentValueColor']."'><div class='caHistoryTrackingContent'>{$current_value['icon']} {$current_value['display']}<div class='caHistoryTrackingEntryDate'>{$current_value['date']}</div></div>";
							
                                if (!$read_only && $allow_value_interstitial_edit && ($current_value['tracked_table_num'] !== $current_value['current_table_num']) && ca_editor_uis::loadDefaultUI($current_value['tracked_table_num'], $this->request, ['restrictToTypes' => $history_entry['tracked_type_id']])) {
?>
                                    <div class="caHistoryTrackingEntryInterstitialEdit"><a href="#" class="caInterstitialEditButton listRelEditButton" data-table="<?= Datamodel::getTableName($current_value['tracked_table_num']); ?>" data-relation_id="<?= $current_value['tracked_row_id']; ?>"  data-primary="<?= Datamodel::getTableName($current_value['current_table_num']); ?>" data-primary_id="<?= $current_value['current_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a></div><?php
                                }
                                if (!$read_only && $allow_value_delete && $allow_value_delete) {
?>
                                    <div class="caHistoryTrackingEntryDelete"><a href="#" class="caDeleteItemButton listRelDeleteButton"  data-table="<?= Datamodel::getTableName($current_value['tracked_table_num']); ?>" data-relation_id="<?= $current_value['tracked_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div><?php
                                }
							
							print "</div>";	 
							
							print "<br class=\"clear\"/>\n";
						} else {
?>
							<?= _t('No %1 set', mb_strtolower($policy_info['name'])); ?>
<?php
						}
?>
					</div>
					<div id="<?= $vs_id_prefix; ?>Tabs-history" class="hierarchyBrowseTab caHistoryTrackingTab">	
<?php
						if (is_array($history) && sizeof($history)) {
							foreach($history as $vs_date => $history_by_date) {
								foreach($history_by_date as $history_entry) {
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
									
                                    print "<div id='caHistoryTrackingEntry{$vs_id_prefix}".Datamodel::getTableName($history_entry['tracked_table_num']).'-'.$history_entry['tracked_row_id']."' class='caHistoryTracking' style='background-color:#{$color}'><div class='caHistoryTrackingContent'>".$history_entry['icon'].' '.$history_entry['display']."<div class=\"caHistoryTrackingEntryDate\">{$history_entry['date']}</div></div>";
                                    if (!$read_only && $allow_value_interstitial_edit && ($history_entry['tracked_table_num'] !== $history_entry['current_table_num']) && ca_editor_uis::loadDefaultUI($history_entry['tracked_table_num'], $this->request, ['restrictToTypes' => $history_entry['tracked_type_id']])) {
?>
                                        <div class="caHistoryTrackingEntryInterstitialEdit"><a href="#" class="caInterstitialEditButton listRelEditButton" data-table="<?= Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?= $history_entry['tracked_row_id']; ?>"  data-primary="<?= Datamodel::getTableName($history_entry['current_table_num']); ?>" data-primary_id="<?= $history_entry['current_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a></div><?php
                                    }
                                    if (!$read_only && $allow_value_delete && $allow_value_delete) {
?>
                                        <div class="caHistoryTrackingEntryDelete"><a href="#" class="caDeleteItemButton listRelDeleteButton"  data-table="<?= Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?= $history_entry['tracked_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div><?php
                                    }
                                    print "</div>\n";
								}
							}
						} else {
?>
							<?= _t('No %1 set', mb_strtolower($policy_info['name'])); ?>
<?php
						}
?>
					</div>
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
				<div id="caHistoryTrackingEntry<?= $vs_id_prefix.Datamodel::getTableName($history_entry['tracked_table_num']).'-'.$history_entry['tracked_row_id']; ?>" class="caHistoryTrackingEntry <?= ($vn_i == 0) ? 'caHistoryTrackingEntryFirst' : ''; ?>" style="background-color:#<?= $color; ?>">
					<div class='caHistoryTrackingContent'>
						<?= $history_entry['icon']; ?>
						<div><?= $history_entry['display']; ?></div>					
<?php
					if (!$read_only && $allow_value_interstitial_edit && ($history_entry['tracked_table_num'] !== $history_entry['current_table_num']) && ca_editor_uis::loadDefaultUI($history_entry['tracked_table_num'], $this->request, ['restrictToTypes' => $history_entry['tracked_type_id']])) {
?>
						<div class="caHistoryTrackingEntryInterstitialEdit"><a href="#" class="caInterstitialEditButton listRelEditButton" data-table="<?= Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?= $history_entry['tracked_row_id']; ?>"  data-primary="<?= Datamodel::getTableName($history_entry['current_table_num']); ?>" data-primary_id="<?= $history_entry['current_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a></div><?php
					}
					if (!$read_only && $allow_value_delete && $allow_value_delete) {
?>
						<div class="caHistoryTrackingEntryDelete"><a href="#" class="caDeleteItemButton listRelDeleteButton"  data-table="<?= Datamodel::getTableName($history_entry['tracked_table_num']); ?>" data-relation_id="<?= $history_entry['tracked_row_id']; ?>"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div><?php
					}
?>
						<div class="caHistoryTrackingEntryDate"><?= $history_entry['date']; ?></div>
					</div>
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
	if($show_return_home_controls) {
?>
		<textarea class='<?= $vs_id_prefix; ?>caHistoryTrackingReturnToHomeLocationTemplate' style='display: none;'>
			<div class="clear"><!-- empty --></div>
			<div id="<?= $vs_id_prefix; ?>_ca_storage_locations_return_home_{n}" class="labelInfo caRelatedLocation <?= $vs_id_prefix; ?>caRelatedLocation">
				<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteReturnToHomeLocationButton <?= $vs_id_prefix; ?>caDeleteReturnToHomeLocationButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			
				<h2 id="<?= $vs_id_prefix; ?>_ca_storage_locations_return_home_heading"></h2>
			
				<?= ca_storage_locations::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['placement_code' => $vs_id_prefix]); ?>	

				<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_storage_locations_return_home{n}" id="<?= $vs_id_prefix; ?>_ca_storage_locations_return_home{n}" value="0"/>
			</div>
		</textarea>
<?php
	}
    if ($show_location_controls || $show_return_home_controls) {
?>	
	<textarea class='<?= $vs_id_prefix; ?>caHistoryTrackingSetLocationTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?= $vs_id_prefix; ?>_ca_storage_locations_{n}" class="labelInfo caRelatedLocation <?= $vs_id_prefix; ?>caRelatedLocation">
			<h2 class="caHistoryTrackingSetLocationHeading"><?= caGetOption('update_location_control_label', $settings, _t('Update location')); ?></h2>
<?php
	if (!(bool)$settings['useHierarchicalBrowser']) {
?>
		<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteLocationButton <?= $vs_id_prefix; ?>caDeleteLocationButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			
		<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_storage_locations_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_storage_locations_autocomplete{n}" class="lookupBg"/>
		<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_storage_locations_id{n}" id="<?= $vs_id_prefix; ?>_ca_storage_locations_id{n}" value="{id}"/>
	
		<?= ca_storage_locations::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['placement_code' => $vs_id_prefix]); ?>	
<?php
	} else {
?>
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteLocationButton <?= $vs_id_prefix; ?>caDeleteLocationButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			
			<div style='width: 700px; height: 200px;'>				
				<div style="float: right;">
					<div class='hierarchyBrowserSearchBar'><input type='text' id='<?= $vs_id_prefix; ?>_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='40' placeholder=<?= json_encode(_t('Search')); ?>/></div>
				</div>
				
				<div class="clear"><!-- empty --></div>
				
				<div id='<?= $vs_id_prefix; ?>_hierarchyBrowser{n}' style='width: 100%; height: 165px;' class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	
				
				<div class="hierarchyBrowserCurrentSelectionText">
					<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_storage_locations_id{n}" id="<?= $vs_id_prefix; ?>_ca_storage_locations_id{n}" value="{id}"/>
				
					<span class="hierarchyBrowserCurrentSelectionText" id="<?= $vs_id_prefix; ?>_browseCurrentSelectionText{n}"> </span>
				</div>
				
				<div style="clear: both; width: 1px; height: 5px;"><!-- empty --></div>
			</div>
			
			<div class="clear" style="height: 20px;"><!-- empty --></div>

			<?= ca_storage_locations::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['placement_code' => $vs_id_prefix]); ?>	

			<div class="clear"><!-- empty --></div>
		
			<script type='text/javascript'>
				jQuery(document).ready(function() { 
					var <?= $vs_id_prefix; ?>oHierBrowser{n} = caUI.initHierBrowser('<?= $vs_id_prefix; ?>_hierarchyBrowser{n}', {
						uiStyle: 'horizontal',
						levelDataUrl: '<?= caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyLevel', array()); ?>',
						initDataUrl: '<?= caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyAncestorList'); ?>',
					
						selectOnLoad : true,
						browserWidth: '100%',
					
						dontAllowEditForFirstLevel: false,
					
						className: 'hierarchyBrowserLevel',
						classNameContainer: 'hierarchyBrowserContainer',
						currentSelectionIDID: '<?= $vs_id_prefix; ?>_ca_storage_locations_id{n}',
						currentSelectionDisplayPrefix: <?= json_encode('<span class="hierarchyBrowserCurrentSelectionHeader">'._t('Selected').'</span>: '); ?>,
					
						indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
						editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						disabledButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
					
						displayCurrentSelectionOnLoad: false,
						currentSelectionDisplayID: '<?= $vs_id_prefix; ?>_browseCurrentSelectionText{n}',
						onSelection: function(item_id, parent_id, name, display, type_id) {
							caRelationBundle<?= $vs_id_prefix; ?>_ca_storage_locations.select('{n}', {id: item_id, type_id: type_id}, display);
						}
					});
				
					jQuery('#<?= $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').autocomplete({
							source: '<?= caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', array('noInline' => 1)); ?>',
							minLength: <?= (int)$t_subject->getAppConfig()->get(["ca_storage_locations_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>, delay: 800, html: true,
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									<?= $vs_id_prefix; ?>oHierBrowser{n}.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								event.preventDefault();
								jQuery('#<?= $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').val('');
							}
						}
					);
<?php
    if (caGetOption('ca_storage_locations_useDatePicker', $settings, false)) {
?>
					jQuery('#<?= $vs_id_prefix; ?>_ca_storage_locations__effective_date{n}').datepicker({dateFormat: 'yy-mm-dd'});  // attempt to add date picker
<?php
    }
?>
				});
			</script>
<?php
	}
?>
		</div>
	</textarea>
<?php
}

if($show_loan_controls) {
?>
	<textarea class='caHistoryTrackingSetLoanTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		
		<div id="<?= $vs_id_prefix; ?>_ca_loans_{n}" class="labelInfo caRelatedLoan">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteLoanButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= caGetOption('loan_control_label', $settings, _t('Add to loan')); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_loans_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_loans_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_loans_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_loans_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_loans_id{n}" id="<?= $vs_id_prefix; ?>_ca_loans_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_loans::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}
	
	if($show_movement_controls) {
?>
	<textarea class='caHistoryTrackingSetMovementTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
	
		<div id="<?= $vs_id_prefix; ?>_ca_movements_{n}" class="labelInfo caRelatedMovement">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteMovementButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= caGetOption('movement_control_label', $settings, _t('Add to movement')); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_movements_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_movements_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_movements_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_movements_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_movements_id{n}" id="<?= $vs_id_prefix; ?>_ca_movements_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_movements::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}
	
	if($show_object_controls) {
?>
	<textarea class='caHistoryTrackingSetObjectTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		
		<div id="<?= $vs_id_prefix; ?>_ca_objects_{n}" class="labelInfo caRelatedObject">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteObjectButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= caGetOption('object_control_label', $settings, _t('Add to object')); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_objects_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_objects_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_objects_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_objects_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_objects_id{n}" id="<?= $vs_id_prefix; ?>_ca_objects_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_objects::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}

if($show_occurrence_controls) {
	foreach($occ_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='<?= $vs_id_prefix; ?>caHistoryTrackingSetOccurrenceTemplate<?= $vn_type_id; ?>' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		
		<div id="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_{n}" class="labelInfo caRelatedOccurrence <?= $vs_id_prefix; ?>caRelatedOccurrence">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="<?= $vs_id_prefix; ?>caDeleteOccurrenceButton<?= $vn_type_id; ?>"><?= caNavIcon($this->request, __CA_NAV_ICON_DEL_BUNDLE__); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= _t(caGetOption('occurrence_control_label', $settings, _t('Add to %1')), $va_type_info['name_singular']); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_id{n}" id="<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_occurrences::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}
}

if($show_collection_controls) {
	foreach($coll_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='caHistoryTrackingSetCollectionTemplate<?= $vn_type_id; ?>' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		
		<div id="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_{n}" class="labelInfo caRelatedCollection">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteCollectionButton<?= $vn_type_id; ?>"><?= caNavIcon($this->request, __CA_NAV_ICON_DEL_BUNDLE__); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= _t(caGetOption('collection_control_label', $settings, _t('Add to %1')), $va_type_info['name_singular']); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_id{n}" id="<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_collections::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}
}

if($show_entity_controls) {
	foreach($entity_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='caHistoryTrackingSetEntityTemplate<?= $vn_type_id; ?>' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		
		<div id="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_{n}" class="labelInfo caRelatedEntity">
			<div class="caHistoryTrackingButtonBarDelete"><a href="#" class="caDeleteEntityButton<?= $vn_type_id; ?>"><?= caNavIcon($this->request, __CA_NAV_ICON_DEL_BUNDLE__); ?></a></div>
			<table class="caListItem">
				<tr>
					<td><h2><?= _t(caGetOption('entity_control_label', $settings,_t('Add to %1')), $va_type_info['name_singular']); ?></h2></td>
					<td>
						<input type="text" size="60" name="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_autocomplete{n}" value="{{label}}" id="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_type_id{n}" id="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_id{n}" id="<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_id{n}" value="{id}"/>
					</td>
				</tr>
			</table>
			<?= ca_entities::getHistoryTrackingChronologyInterstitialElementAddHTMLForm($this->request, $vs_id_prefix, $subject_table, $settings, ['type' => $va_type_info['idno'], 'placement_code' => $vs_id_prefix]); ?>
		</div>
	</textarea>
<?php
	}
}
?>
</div>

<div id="caRelationQuickAddPanel<?= $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?= $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?= _t('Quick Add'); ?></div>
		
	</div>
</div>
<div id="caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>" class="caRelationInterstitialEditPanel"> 
	<div id="caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>ContentArea">
	    <div class='dialogHeader'><?= _t('Edit'); ?></div>
	</div>
</div>

<?php
	if (!$read_only) {
?>
<script type="text/javascript">
	var caRelationQuickAddPanel<?= $vs_id_prefix; ?>, caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
	
		jQuery("#<?= $vs_id_prefix; ?>").find(".caInterstitialEditButton").on('click', null,  {}, function(e) {
			// Trigger interstitial edit panel
			var table = jQuery(this).data('table');
			var relation_id = jQuery(this).data('relation_id');
			var primary = jQuery(this).data('primary');
			var primary_id = jQuery(this).data('primary_id');
			var u = '<?= caNavUrl($this->request, 'editor', 'Interstitial', 'Form', []); ?>/t/' + table + '/relation_id/' + relation_id + '/primary/' + primary + '/primary_id/' + primary_id;
		
			caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>.showPanel(u);
		
			jQuery('#' + caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>.getPanelContentID()).data('panel', caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>);
		
			e.preventDefault();
			return false;
		});
	
		jQuery("#<?= $vs_id_prefix; ?>").find(".caDeleteItemButton").on('click', null,  {}, function(e) {
			// Handle delete of chronology item
			var table = jQuery(this).data('table');
			var relation_id = jQuery(this).data('relation_id');
		
			jQuery("#caHistoryTrackingEntry<?= $vs_id_prefix; ?>" + table + "-" + relation_id).remove();
			jQuery("#<?= $vs_id_prefix; ?>").append("<input type='hidden' name='<?= $vs_id_prefix; ?>_delete_" + table + "[]' value='" +relation_id + "'/>");
			e.preventDefault();
		});
		
	<?php if($display_mode === 'tabs') { ?>
			jQuery("#<?= $vs_id_prefix; ?>Tabs").tabs({ selected: 0 });	
	<?php } ?>	
	
			if (caUI.initPanel) {
				caRelationQuickAddPanel<?= $vs_id_prefix; ?> = caUI.initPanel({ 
					panelID: "caRelationQuickAddPanel<?= $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
					panelContentID: "caRelationQuickAddPanel<?= $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
				caRelationInterstitialEditPanel<?= $vs_id_prefix; ?> = caUI.initPanel({ 
					panelID: "caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
					panelContentID: "caRelationInterstitialEditPanel<?= $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
							caBundleUpdateManager.reloadBundle('<?= $bundle_name; ?>'); 
							caBundleUpdateManager.reloadInspector(); 
						}
					}
				});
			}
<?php	
	if($show_location_controls || $show_return_home_controls) {
?>	
			caRelationBundle<?= $vs_id_prefix; ?>_ca_storage_locations = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_storage_locations_',
				templateValues: ['label', 'type_id', 'id'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_storage_locations_',
				placementID: '<?= $placement_id; ?>',
				templateClassName: '<?= $vs_id_prefix; ?>caHistoryTrackingSetLocationTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: '<?= $vs_id_prefix; ?>caLocationList',
				listItemClassName: '<?= $vs_id_prefix; ?>caRelatedLocation',
				addButtonClassName: '<?= $vs_id_prefix; ?>caChangeLocationButton',
				deleteButtonClassName: '<?= $vs_id_prefix; ?>caDeleteLocationButton',
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?= json_encode($this->getVar('location_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', []); ?>',
				minChars:<?= (int)$t_subject->getAppConfig()->get(["ca_storage_locations_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				readonly: false,
				isSortable: false,
				listSortItems: 'div.roundedRel',			
				autocompleteInputID: '<?= $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/storage_locations', 'StorageLocationQuickAdd', 'Form', array('location_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)($settings['dont_include_subtypes_in_type_restriction'] ?? false))); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				addMode: 'prepend',
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
<?php
		if($show_return_home_controls) {
?>			
			if (!_currentHomeLocation) {
				_currentHomeLocation = <?= json_encode($home_location_idno); ?>;
			}
			caRelationBundle<?= $vs_id_prefix; ?>_ca_storage_locations_return_home = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_storage_locations_return_home_',
				templateValues: ['label', 'type_id', 'id'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_storage_locations_return_home_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: '<?= $vs_id_prefix; ?>caHistoryTrackingReturnToHomeLocationTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: '<?= $vs_id_prefix; ?>caLocationList',
				listItemClassName: '<?= $vs_id_prefix; ?>caRelatedLocation',
				addButtonClassName: '<?= $vs_id_prefix; ?>caReturnToHomeLocationButton',
				deleteButtonClassName: '<?= $vs_id_prefix; ?>caDeleteReturnToHomeLocationButton',
				showEmptyFormsOnLoad: 0,
				minChars:1,
				readonly: false,
				isSortable: false,
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
					jQuery("#<?= $vs_id_prefix; ?>_ca_storage_locations_return_homenew_0").val(1);
					
					var msg = <?= json_encode(caGetOption('return_to_home_location_control_label', $settings, _t('Return to home location '))); ?>;
					jQuery("#<?= $vs_id_prefix; ?>_ca_storage_locations_return_home_heading").html(msg + "<em>" + _currentHomeLocation + "</em>");
				
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
					jQuery("#<?= $vs_id_prefix; ?>_ca_storage_locations_return_homenew_0").val(0);
				}
			});
<?php
		}	
    }
	if($show_loan_controls) {
?>			
			caRelationBundle<?= $vs_id_prefix; ?>_ca_loans = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_loans_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_loans_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetLoanTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caLoanList',
				listItemClassName: 'caRelatedLoan',
				addButtonClassName: 'caAddLoanButton',
				deleteButtonClassName: 'caDeleteLoanButton',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_loans_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('loan_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'Loan', 'Get', []); ?>',
				types: <?= json_encode($settings['restrict_to_types']); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: <?= ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?= $vs_id_prefix; ?>LoanBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/loans', 'LoanQuickAdd', 'Form', array('loan_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
<?php	
			if(caGetOption('always_create_new_loan', $settings, false)) {
?>
				jQuery('#<?= $vs_id_prefix; ?>AddLoan').on('click', function(e) {
					caRelationBundle<?= $vs_id_prefix; ?>_ca_loans.triggerQuickAdd('', 'new_0', { usePolicy: <?= json_encode($policy); ?> }, {'addBundle': true });
					e.preventDefault();
					return false;
				});
<?php
			}
    }
	if($show_movement_controls) {
?>			
			caRelationBundle<?= $vs_id_prefix; ?>_ca_movements = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_movements_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_movements_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetMovementTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caMovementList',
				listItemClassName: 'caRelatedMovement',
				addButtonClassName: 'caAddMovementButton',
				deleteButtonClassName: 'caDeleteMovementButton',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_movements_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('movement_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'Movement', 'Get', []); ?>',
				types: <?= json_encode($settings['restrict_to_types']); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: <?= ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?= $vs_id_prefix; ?>MovementBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/movements', 'MovementQuickAdd', 'Form', array('movement_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
<?php	
			if(caGetOption('always_create_new_movement', $settings, false)) {
?>
				jQuery('#<?= $vs_id_prefix; ?>AddMovement').on('click', function(e) {
					caRelationBundle<?= $vs_id_prefix; ?>_ca_movements.triggerQuickAdd('', 'new_0', { usePolicy: <?= json_encode($policy); ?> }, {'addBundle': true });
					e.preventDefault();
					return false;
				});
<?php
			}
    }
	if($show_object_controls) {
?>
			caRelationBundle<?= $vs_id_prefix; ?>_ca_objects = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_objects_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_objects_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetObjectTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caObjectList',
				listItemClassName: 'caRelatedObject',
				addButtonClassName: 'caAddObjectButton',
				deleteButtonClassName: 'caDeleteObjectButton',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_objects_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('object_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'object', 'Get', []); ?>',
				types: <?= json_encode($settings['restrict_to_types']); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: <?= ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?= $vs_id_prefix; ?>ObjectBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/objects', 'ObjectQuickAdd', 'Form', array('object_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
<?php
    }
	if($show_occurrence_controls) {
		foreach($occ_types as $vn_type_id => $va_type_info) {
?>
			caRelationBundle<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>_',
				placementID: '<?= $placement_id; ?>',
				templateClassName: '<?= $vs_id_prefix; ?>caHistoryTrackingSetOccurrenceTemplate<?= $vn_type_id; ?>',
				initialValueTemplateClassName: null,
				itemListClassName: '<?= $vs_id_prefix; ?>caOccurrenceList<?= $vn_type_id; ?>',
				listItemClassName: '<?= $vs_id_prefix; ?>caRelatedOccurrence',
				addButtonClassName: '<?= $vs_id_prefix; ?>caAddOccurrenceButton<?= $vn_type_id; ?>',
				deleteButtonClassName: '<?= $vs_id_prefix; ?>caDeleteOccurrenceButton<?= $vn_type_id; ?>',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_occurrences_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('occurrence_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'Occurrence', 'Get', array_merge($occ_lookup_params, ['types' => $vn_type_id])); ?>',
				types: <?= json_encode($settings['restrict_to_types'] ?? null); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: false,
				listSortOrderID: '<?= $vs_id_prefix; ?>OccurrenceBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_occurrence_<?= $vn_type_id; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/occurrences', 'OccurrenceQuickAdd', 'Form', array('types' => $vn_type_id,'occurrence_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)($settings['dont_include_subtypes_in_type_restriction'] ?? null))); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
<?php
			if(caGetOption('always_create_new_occurrence', $settings, false)) {
?>
				jQuery('#<?= $vs_id_prefix; ?>AddOcc<?= $vn_type_id; ?>').on('click', function(e) { 
					caRelationBundle<?= $vs_id_prefix; ?>_ca_occurrences_<?= $vn_type_id; ?>.triggerQuickAdd('', 'new_0', { usePolicy: <?= json_encode($policy); ?> }, {'addBundle': true });
					e.preventDefault();
					return false;
				});
<?php
			}
		}
	}
	if($show_collection_controls) {
		foreach($coll_types as $vn_type_id => $va_type_info) {
	?>
			caRelationBundle<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_collections_<?= $vn_type_id; ?>_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetCollectionTemplate<?= $vn_type_id; ?>',
				initialValueTemplateClassName: null,
				itemListClassName: 'caCollectionList<?= $vn_type_id; ?>',
				listItemClassName: 'caRelatedCollection',
				addButtonClassName: 'caAddCollectionButton<?= $vn_type_id; ?>',
				deleteButtonClassName: 'caDeleteCollectionButton<?= $vn_type_id; ?>',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_collections_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('collection_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'collection', 'Get', array_merge($coll_lookup_params, ['types' => $vn_type_id])); ?>',
				types: <?= json_encode($settings['restrict_to_types']); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: <?= ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?= $vs_id_prefix; ?>CollectionBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_collection_<?= $vn_type_id; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/collections', 'collectionQuickAdd', 'Form', array('types' => $vn_type_id,'collection_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
	<?php
		}
	}
	if($show_entity_controls) {
		foreach($entity_types as $vn_type_id => $va_type_info) {
	?>
			caRelationBundle<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?> = caUI.initRelationBundle('#<?= $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_',
				templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?= $vs_id_prefix; ?>_ca_entities_<?= $vn_type_id; ?>_',
				placementID: '<?= $vn_placement_id; ?>',
				templateClassName: 'caHistoryTrackingSetEntityTemplate<?= $vn_type_id; ?>',
				initialValueTemplateClassName: null,
				itemListClassName: 'caEntityList<?= $vn_type_id; ?>',
				listItemClassName: 'caRelatedEntity',
				addButtonClassName: 'caAddEntityButton<?= $vn_type_id; ?>',
				deleteButtonClassName: 'caDeleteEntityButton<?= $vn_type_id; ?>',
				hideOnNewIDList: [],
				showEmptyFormsOnLoad: 0,
				minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_entities_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
				relationshipTypes: <?= json_encode($this->getVar('entity_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'entity', 'Get', array_merge($entity_lookup_params, ['types' => $vn_type_id])); ?>',
				types: <?= json_encode($settings['restrict_to_types']); ?>,
				readonly: <?= $read_only ? "true" : "false"; ?>,
				isSortable: <?= ($read_only || $vs_sort) ? "false" : "true"; ?>,
				listSortOrderID: '<?= $vs_id_prefix; ?>EntityBundleList',
				listSortItems: 'div.roundedRel',
				autocompleteInputID: '<?= $vs_id_prefix; ?>_entity_<?= $vn_type_id; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?= $vs_id_prefix; ?>,
				quickaddUrl: '<?= caNavUrl($this->request, 'editor/entities', 'entityQuickAdd', 'Form', array('types' => $vn_type_id,'entity_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery("#<?= $vs_id_prefix; ?>").find(".caHistoryTrackingButtonBar").slideDown(250);
				}
			});
	<?php
		}
	}
	?>
		jQuery('#<?= $vs_id_prefix; ?>SetChildView').on('click', function(e) {
			if(caBundleUpdateManager) { 
				caBundleUpdateManager.reloadBundle('ca_objects_history', {'showChildHistory': <?= Session::getVar('ca_objects_history_showChildHistory') ? 0 : 1; ?>}); 
				caBundleUpdateManager.reloadBundle('history_tracking_chronology', {'showChildHistory': <?= Session::getVar('ca_objects_history_showChildHistory') ? 0 : 1; ?>}); 
			}

		});
	});
</script>
<?php
	}
