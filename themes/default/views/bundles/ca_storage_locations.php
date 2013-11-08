<?php
/* ----------------------------------------------------------------------
 * bundles/ca_storage_locations.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 
	JavascriptLoadManager::register('hierBrowser');
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');				// storage location
	$t_item_rel 		= $this->getVar('t_item_rel');
	$va_settings 		= $this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	$va_rel_types		= $this->getVar('relationship_types');
	$vs_placement_code 	= $this->getVar('placement_code');
	$vn_placement_id	= (int)$va_settings['placement_id'];
	$vb_batch			= $this->getVar('batch');

	$vs_first_color 	= 	((isset($va_settings['colorFirstItem']) && $va_settings['colorFirstItem'])) ? $va_settings['colorFirstItem'] : '';
	$vs_last_color 		= 	((isset($va_settings['colorLastItem']) && $va_settings['colorLastItem'])) ? $va_settings['colorLastItem'] : '';
		
	$vs_sort			=	((isset($va_settings['sort']) && $va_settings['sort'])) ? $va_settings['sort'] : '';
	$vb_read_only		=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_storage_locations') == __CA_BUNDLE_ACCESS_READONLY__));
	$vb_dont_show_del	=	((isset($va_settings['dontShowDeleteButton']) && $va_settings['dontShowDeleteButton'])) ? true : false;
	
	$va_initial_values	= $this->getVar('initialValues');
	
	// params to pass during occurrence lookup
	$va_lookup_params = array('type' => isset($va_settings['restrict_to_type']) ? $va_settings['restrict_to_type'] : '', 'noSubtypes' => (int)$va_settings['dont_include_subtypes_in_type_restriction']);

	if ($vb_batch) {
		print caBatchEditorRelationshipModeControl($t_item, $vs_id_prefix);
	} else {
		print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_item->tableNum().'_rel');
	}
	
	$va_errors = array();
	foreach($va_action_errors = $this->request->getActionErrors($vs_placement_code) as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
?>
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>" <?php print $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;' <?php print $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	switch($va_settings['list_format']) {
		case 'list':
?>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo listRel caRelatedItem">
<?php
	if (!$vb_read_only && ca_editor_uis::loadDefaultUI($t_item_rel->tableNum(), $this->request)) {
?><a href="#" class="caInterstitialEditButton listRelEditButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_INTERSTITIAL_EDIT_BUNDLE__); ?></a><?php
	}
	if (!$vb_read_only && !$vb_dont_show_del) {
?><a href="#" class="caDeleteItemButton listRelDeleteButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a><?php
	}
?>
			<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_occurrences', '{occurrence_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"></a>
<?php
			print caGetRelationDisplayString($this->request, 'ca_storage_locations', array(), array('display' => '_display', 'makeLink' => false));
?>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
		</div>
<?php
			break;
		case 'bubbles':
		default:
?>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo roundedRel caRelatedItem">
<?php
			print caGetRelationDisplayString($this->request, 'ca_storage_locations', array('class' => 'caEditItemButton', 'id' => "{$vs_id_prefix}_edit_related_{n}"), array('display' => '_display', 'makeLink' => true));
?>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" value="{type_id}"/>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
<?php
	if (!$vb_read_only && ca_editor_uis::loadDefaultUI($t_item_rel->tableNum(), $this->request)) {
?><a href="#" class="caInterstitialEditButton listRelEditButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_INTERSTITIAL_EDIT_BUNDLE__); ?></a><?php
	}
	if (!$vb_read_only && !$vb_dont_show_del) {
?><a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a><?php
	}
?>			
			<div style="display: none;" class="itemName">{label}</div>
			<div style="display: none;" class="itemIdno">{idno_sort}</div>
		</div>
<?php
	}
?>
	</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo caRelatedItem">
<?php
	if (!(bool)$va_settings['useHierarchicalBrowser']) {
?>
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_storage_locations', '{location_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>
					</td>
				</tr>
			</table>
<?php
} else {
?>
			<div style="float: right;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a></div>
			<div style='width: 690px; height: <?php print $va_settings['hierarchicalBrowserHeight']; ?>;'>
				
				<div id='<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}' style='width: 100%; height: 100%;' class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	</div>
				
			<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
			<div style="float: right;">
				<div class='hierarchyBrowserSearchBar'><?php print _t('Search'); ?>: <input type='text' id='<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='40'/></div>
			</div>
			<div style="float: left;" class="hierarchyBrowserCurrentSelectionText">
				<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
				<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
				
				<span class="hierarchyBrowserCurrentSelectionText" id="<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}"> </span>
			</div>	

			
			<script type='text/javascript'>
				jQuery(document).ready(function() { 
					var <?php print $vs_id_prefix; ?>oHierBrowser{n} = caUI.initHierBrowser('<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}', {
						uiStyle: 'horizontal',
						levelDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyLevel', array()); ?>',
						initDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'GetHierarchyAncestorList'); ?>',
						
						selectOnLoad : true,
						browserWidth: "<?php print $va_settings['hierarchicalBrowserWidth']; ?>",
						
						dontAllowEditForFirstLevel: false,
						
						className: 'hierarchyBrowserLevel',
						classNameContainer: 'hierarchyBrowserContainer',
						
						editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit"/>',
						
						//initItemID: <?php print (int)$this->request->session->getVar('ca_storage_locations_browse_last_id'); ?>,
						indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
						
						displayCurrentSelectionOnLoad: false,
						currentSelectionDisplayID: '<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}',
						onSelection: function(item_id, parent_id, name, display, type_id) {
							caRelationBundle<?php print $vs_id_prefix; ?>.select('{n}', {id: item_id, type_id: type_id}, display);
						}
					});
					
					jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').autocomplete(
						{
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
				});
			</script>
<?php
	}
?>
		</div>
	</textarea>
	
	<div class="bundleContainer">
<?php
	if(sizeof($va_initial_values) && !$vb_read_only && !$vs_sort) {
?>
		<div class="caItemListSortControlTrigger" id="<?php print $vs_id_prefix; ?>caItemListSortControlTrigger">
			<?php print _t('Sort by'); ?>
			<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/bg.gif" alt='Sort'/>
		</div>
		<div class="caItemListSortControls" id="<?php print $vs_id_prefix; ?>caItemListSortControls">
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('name'); return false;" class='caItemListSortControl'><?php print _t('name'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('idno'); return false;" class='caItemListSortControl'><?php print _t('idno'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('type'); return false;" class='caItemListSortControl'><?php print _t('type'); ?></a><br/>
			<a href='#' onclick="caRelationBundle<?php print $vs_id_prefix; ?>.sort('entry'); return false;" class='caItemListSortControl'><?php print _t('entry'); ?></a><br/>
		</div>
<?php
	}
?>

		<div class="caItemList">
<?php
	if (sizeof($va_errors)) {
?>
		<span class="formLabelError"><?php print join("; ", $va_errors); ?><br class="clear"/></span>
<?php
	}
?>
		
		</div>
		<input type="hidden" name="<?php print $vs_id_prefix; ?>BundleList" id="<?php print $vs_id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
<?php
	if (!$vb_read_only) {
?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print $vs_add_label ? $vs_add_label :  _t("Add relationship"); ?></a></div>
<?php
	}
?>
	</div>
</div>
	
<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Quick Add', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
</div>
<div id="caRelationEditorPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationEditorPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Relation editor', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
</div>	
	
<script type="text/javascript">
	var caRelationQuickAddPanel<?php print $vs_id_prefix; ?>;
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideToggle(200); });
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideUp(200); });
		
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
			caRelationEditorPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationEditorPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationEditorPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
		
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['label', 'type_id', 'id'],
			initialValues: <?php print json_encode($va_initial_values); ?>,
			initialValueOrder: <?php print json_encode(array_keys($va_initial_values)); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			placementID: '<?php print $vn_placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			listItemClassName: 'caRelatedItem',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_related_'],
			showEmptyFormsOnLoad: 1,
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'StorageLocation', 'Get', $va_lookup_params); ?>',
			minChars:1,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print ($vb_read_only || $vs_sort) ? "false" : "true"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>BundleList',
			listSortItems: 'div.roundedRel',			
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
			quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
			quickaddUrl: '<?php print caNavUrl($this->request, 'editor/storage_locations', 'StorageLocationQuickAdd', 'Form', array('location_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$va_settings['dont_include_subtypes_in_type_restriction'])); ?>',
			
			interstitialButtonClassName: 'caInterstitialEditButton',
			interstitialPanel: caRelationEditorPanel<?php print $vs_id_prefix; ?>,
			interstitialUrl: '<?php print caNavUrl($this->request, 'editor', 'Interstitial', 'Form', array('t' => $t_item_rel->tableName())); ?>',
			interstitialPrimaryTable: '<?php print $t_instance->tableName(); ?>',
			interstitialPrimaryID: <?php print (int)$t_instance->getPrimaryKey(); ?>,
			firstItemColor: '<?php print $vs_first_color; ?>',
			lastItemColor: '<?php print $vs_last_color; ?>',
			
			minRepeats: <?php print caGetOption('minRelationshipsPerRow', $va_settings, 0); ?>,
			maxRepeats: <?php print caGetOption('maxRelationshipsPerRow', $va_settings, 65535); ?>
		});
	});
</script>
<?php
	foreach($va_initial_values as $vn_id => $va_info) {
		TooltipManager::add("#{$vs_id_prefix}_edit_related_{$vn_id}", "<h2>".$va_info['label']."</h2>");
	}
?>