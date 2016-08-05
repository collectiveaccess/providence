<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_history.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				= (isset($va_settings['readonly']) && $va_settings['readonly']);
	
	$va_history					= $this->getVar('history');
	
	$vs_mode					= $this->getVar('mode');
	$vs_relationship_type		= $this->getVar('location_relationship_type');
	$vs_change_location_url		= $this->getVar('location_change_url');

	$va_storage_location_elements = caGetOption('ca_storage_locations_elements', $va_settings, array());
	
	$va_occ_types  				= $this->getVar('occurrence_types');
	
	if (!($vs_add_label = $this->getVar('add_label'))) { $vs_add_label = _t('Update location'); }
	
	$va_lookup_params = array();
	
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $va_settings);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
<?php
	if (!$vb_read_only) {
?>
			<div class="caUseHistoryButtonBar labelInfo">
<?php
			if(!caGetOption('hide_add_to_loan_controls', $va_settings, false)) {
?>
				<div style='float: left;' class='button caAddLoanButton'><a href="#" id="<?php print $vs_id_prefix; ?>AddLoan"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Add to loan'); ?></a></div>
<?php
			}
			if(!caGetOption('hide_update_location_controls', $va_settings, false)) {
?>
				<div style='float: left;'  class='button caChangeLocationButton'><a href="#" id="<?php print $vs_id_prefix; ?>ChangeLocation"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Update location'); ?></a></div>
<?php
			}
			
			if(!caGetOption('hide_add_to_occurrence_controls', $va_settings, false)) {
				foreach($va_occ_types as $vn_type_id => $va_type_info) {
?>
				<div style='float: left;'  class='button caAddOccurrenceButton caAddOccurrenceButton<?php print $vn_type_id; ?>'><a href="#" id="<?php print $vs_id_prefix; ?>AddOcc<?php print $vn_type_id; ?>"><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print _t('Add to %1', $va_type_info['name_singular']); ?></a></div>
<?php		
				}
			}
			
?>
				<br style='clear: both;'/>
			</div>
<?php
	}
?>			
		<div class="caLocationList"> </div>
		<div class="caLoanList"> </div>
<?php
if(!caGetOption('hide_add_to_occurrence_controls', $va_settings, false)) {
	foreach($va_occ_types as $vn_type_id => $va_type_info) {
?>
		<div class="caOccurrenceList<?php print $vn_type_id; ?>"> </div>
<?php
	}
}
	
	foreach($va_history as $vn_date => $va_history_entries_for_date) {
		foreach($va_history_entries_for_date as $vn_i => $va_history_entry) {
?>
			<div class="caUseHistoryEntry <?php print ($vn_i == 0) ? 'caUseHistoryEntryFirst' : ''; ?>">
				<?php print $va_history_entry['icon']; ?>
				<div><?php print $va_history_entry['display']; ?></div>
				<div class="caUseHistoryDate"><?php print $va_history_entry['date']; ?></div>
				<br class="clear"/>
			</div>
<?php
		}
	}
?>
	</div>
<?php
	if ($vs_mode == 'ca_storage_locations') {
	//
	// Template to generate controls for creating new storage location
	//
?>
	<textarea class='caUseHistorySetLocationTemplate' style='display: none;'>
		<div class="clear"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Location_{n}" class="labelInfo caRelatedLocation">
			<h2 class="caUseHistorySetLocationHeading"><?php print _t('Update location'); ?></h2>
<?php
	if (!(bool)$va_settings['useHierarchicalBrowser']) {
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
				
			<table class='caUseHistoryUpdateLocationMetadata'><?php 
				if(is_array($va_storage_location_elements) && sizeof($va_storage_location_elements)) {
					$o_dm = Datamodel::load();
					$t_rel = $o_dm->getInstanceByTableName('ca_objects_x_storage_locations', true);		
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
							print "<td>".$t_rel->getDisplayLabel($t_rel->tableName().".".$vs_element)."</td><td>".$t_rel->getAttributeHTMLFormBundle($this->request, null, $vs_element, $this->getVar('placement_code'), $va_settings, ['elementsOnly' => true])."</td>";
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
					
						indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
					
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
}
if(!caGetOption('hide_add_to_loan_controls', $va_settings, false)) {
?>
	<textarea class='caUseHistorySetLoanTemplate' style='display: none;'>
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

if(!caGetOption('hide_add_to_occurrence_controls', $va_settings, false)) {
	foreach($va_occ_types as $vn_type_id => $va_type_info) {
?>
	<textarea class='caUseHistorySetOccurrenceTemplate<?php print $vn_type_id; ?>' style='display: none;'>
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
						<a href="#" class="caDeleteOccurrenceButton<?php print $vn_type_id; ?>"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
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

<?php
	if (!$vb_read_only) {
?>
<script type="text/javascript">
	var caRelationQuickAddPanel<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
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
		}
<?php
		if ($vs_mode == 'ca_storage_locations') {
?>
			caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
				fieldNamePrefix: '<?php print $vs_id_prefix; ?>_location_',
				templateValues: ['label', 'type_id', 'id'],
				initialValues: [],
				initialValueOrder: [],
				itemID: '<?php print $vs_id_prefix; ?>Location_',
				placementID: '<?php print $vn_placement_id; ?>',
				templateClassName: 'caUseHistorySetLocationTemplate',
				initialValueTemplateClassName: null,
				itemListClassName: 'caLocationList',
				listItemClassName: 'caRelatedLocation',
				addButtonClassName: 'caChangeLocationButton',
				deleteButtonClassName: 'caDeleteLocationButton',
				showEmptyFormsOnLoad: 0,
				relationshipTypes: <?php print json_encode($this->getVar('location_relationship_types_by_sub_type')); ?>,
				autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', $va_lookup_params); ?>',
				minChars:1,
				readonly: false,
				isSortable: false,
				listSortItems: 'div.roundedRel',			
				autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
				quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
				quickaddUrl: '<?php print caNavUrl($this->request, 'editor/storage_locations', 'StorageLocationQuickAdd', 'Form', array('location_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$va_settings['dont_include_subtypes_in_type_restriction'])); ?>',
				minRepeats: 0,
				maxRepeats: 2,
				addMode: 'prepend',
				useAnimation: 1,
				onAddItem: function(id, options, isNew) {
					jQuery(".caUseHistoryButtonBar").slideUp(250);
				},
				onDeleteItem: function(id) {
					jQuery(".caUseHistoryButtonBar").slideDown(250);
				}
			});
<?php
		} else {
?>
			var panelContentID = '#' + caRelationQuickAddPanel<?php print $vs_id_prefix; ?>.getPanelContentID();
			jQuery(panelContentID)
				.data('relatedID', <?php print (int)$t_subject->getPrimaryKey(); ?>)
				.data('relatedTable', 'ca_objects')
				.data('relationshipType', '<?php print $vs_relationship_type; ?>')
				.data('panel', caRelationQuickAddPanel<?php print $vs_id_prefix; ?>); 
		
			jQuery("#<?php print $vs_id_prefix; ?>ChangeLocation").on("click", function() { 
				caRelationQuickAddPanel<?php print $vs_id_prefix; ?>.showPanel('<?php print $vs_change_location_url; ?>'); 
				return false;
			});
<?php
		}
?>
		caRelationBundle<?php print $vs_id_prefix; ?>_ca_loans = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_loan_',
			templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
			initialValues: [],
			initialValueOrder: [],
			itemID: '<?php print $vs_id_prefix; ?>Loan_',
			placementID: '<?php print $vn_placement_id; ?>',
			templateClassName: 'caUseHistorySetLoanTemplate',
			initialValueTemplateClassName: null,
			itemListClassName: 'caLoanList',
			listItemClassName: 'caRelatedLoan',
			addButtonClassName: 'caAddLoanButton',
			deleteButtonClassName: 'caDeleteLoanButton',
			hideOnNewIDList: [],
			showEmptyFormsOnLoad: 0,
			relationshipTypes: <?php print json_encode($this->getVar('loan_relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Loan', 'Get', $va_lookup_params); ?>',
			types: <?php print json_encode($va_settings['restrict_to_types']); ?>,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print ($vb_read_only || $vs_sort) ? "false" : "true"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>LoanBundleList',
			listSortItems: 'div.roundedRel',
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
			quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
			quickaddUrl: '<?php print caNavUrl($this->request, 'editor/loans', 'LoanQuickAdd', 'Form', array('loan_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$va_settings['dont_include_subtypes_in_type_restriction'])); ?>',
			minRepeats: 0,
			maxRepeats: 2,
			useAnimation: 1,
			onAddItem: function(id, options, isNew) {
				jQuery(".caUseHistoryButtonBar").slideUp(250);
			},
			onDeleteItem: function(id) {
				jQuery(".caUseHistoryButtonBar").slideDown(250);
			}
		});
<?php
if(!caGetOption('hide_add_to_occurrence_controls', $va_settings, false)) {
	foreach($va_occ_types as $vn_type_id => $va_type_info) {
?>
		caRelationBundle<?php print $vs_id_prefix; ?>_ca_occurrences_<?php print $vn_type_id; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_',
			templateValues: ['label', 'id', 'type_id', 'typename', 'idno_sort'],
			initialValues: [],
			initialValueOrder: [],
			itemID: '<?php print $vs_id_prefix; ?>Occurrence_<?php print $vn_type_id; ?>_',
			placementID: '<?php print $vn_placement_id; ?>',
			templateClassName: 'caUseHistorySetOccurrenceTemplate<?php print $vn_type_id; ?>',
			initialValueTemplateClassName: null,
			itemListClassName: 'caOccurrenceList<?php print $vn_type_id; ?>',
			listItemClassName: 'caRelatedOccurrence',
			addButtonClassName: 'caAddOccurrenceButton<?php print $vn_type_id; ?>',
			deleteButtonClassName: 'caDeleteOccurrenceButton<?php print $vn_type_id; ?>',
			hideOnNewIDList: [],
			showEmptyFormsOnLoad: 0,
			relationshipTypes: <?php print json_encode($this->getVar('occurrence_relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Occurrence', 'Get', $va_lookup_params); ?>',
			types: <?php print json_encode($va_settings['restrict_to_types']); ?>,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print ($vb_read_only || $vs_sort) ? "false" : "true"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>OccurrenceBundleList',
			listSortItems: 'div.roundedRel',
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_occurrence_<?php print $vn_type_id; ?>_autocomplete',
			quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
			quickaddUrl: '<?php print caNavUrl($this->request, 'editor/occurrences', 'OccurrenceQuickAdd', 'Form', array('types' => $vn_type_id,'occurrence_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$va_settings['dont_include_subtypes_in_type_restriction'])); ?>',
			minRepeats: 0,
			maxRepeats: 2,
			useAnimation: 1,
			onAddItem: function(id, options, isNew) {
				jQuery(".caUseHistoryButtonBar").slideUp(250);
			},
			onDeleteItem: function(id) {
				jQuery(".caUseHistoryButtonBar").slideDown(250);
			}
		});
<?php
	}
}
?>
	});
</script>
<?php
	}