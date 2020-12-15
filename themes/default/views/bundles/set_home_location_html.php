<?php
/* ----------------------------------------------------------------------
 * bundles/set_home_location_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 
	AssetLoadManager::register("panel");
	$t_item = $this->getVar('t_item');
	
	$t_location = ca_storage_locations::find($t_item->get('home_location_id'), ['returnAs' => 'firstModelInstance']);
	$home_location_idno = $t_location ? $t_location->getWithTemplate($this->request->config->get(['inspector_home_location_display_template', 'ca_storage_locations_hierarchy_browser_display_settings'])) : null;
	$home_location_message = _t('Home location is <em>%</em>');
	
	
	$va_lookup_urls 			= caJSONLookupServiceUrl($this->request, 'ca_storage_locations', []);
	$vs_edit_url = caEditorUrl($this->request, $t_item->tableName());
?>
<script type="text/javascript">
	var caSetHomeLocationPanel;
	var _currentHomeLocation = ''; /* Global containing name of current home location; may be picked up by history_tracking_chronology bundle (and others?) */
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caSetHomeLocationPanel = caUI.initPanel({ 
				panelID: "caSetHomeLocationPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caSetHomeLocationPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
	});
	
	var oSetHomeLocationHierarchyBrowser = null;
	
	function _initSetHomeLocationHierarchyBrowser() {
		caSetHomeLocationPanel.showPanel();
		if (!oSetHomeLocationHierarchyBrowser) {
			oSetHomeLocationHierarchyBrowser = caUI.initHierBrowser('SetHomeLocationHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
			
				dontAllowEditForFirstLevel: true,
			
				readOnly: false,
			
				editUrl: null,
				editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
				disabledButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
			
				allowDragAndDropSorting: false,

				initItemID: <?php print (int)$t_item->get('home_location_id'); ?>,
				indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				incrementalLoadIndicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1).' '._t('Loading'); ?>",
				displayCurrentSelectionOnLoad: false,
				autoShrink: false,
				
				currentSelectionIDID: 'new_home_location_id',
				currentSelectionDisplayID: 'SetHomeLocationHierarchyBrowserSelectionMessage',
				currentSelectionDisplayFormat: <?= json_encode(_t('Home location will be set to <em><ifdef code="parent">^parent ➜ </ifdef>^current</em> on save.')); ?>,
				
				onSelection: function(id, parent_id, name, formattedDisplay) {
					if (id > 1) {
						jQuery("#SetHomeLocationHierarchyBrowserSelectionMessage").html(formattedDisplay);
					} else {
						jQuery("#SetHomeLocationHierarchyBrowserSelectionMessage").html('');
					}
				},
			});
			
			// Set up hierarchy browse search
			jQuery('#SetHomeLocationHierarchyBrowserSearch').autocomplete(
				{
					source: '<?php print $va_lookup_urls['search']; ?>', minLength: 3, delay: 800, html: true,
					select: function( event, ui ) {
						if (ui.item.id) {
							jQuery("#SetHomeLocationHierarchyBrowser").slideDown(350);
							oSetHomeLocationHierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
						}
						event.preventDefault();
						jQuery('#SetHomeLocationHierarchyBrowser').val('');
					}
				}
			).click(function() { this.select() });
			
			_currentHomeLocation = '<?php print addslashes($home_location_idno); ?>';
			jQuery("#SetHomeLocationHierarchyBrowserSelectionMessage").html('<?php print addslashes($home_location_idno ? str_replace('%', $home_location_idno, $home_location_message) : _t('Home location is not set')); ?>');
		}
	}
	
	function setHomeLocation() {
		var new_home_location_id = jQuery("#new_home_location_id").val();
		jQuery.post(
			'<?php print caNavUrl($this->request, '*', '*', 'SetHomeLocation'); ?>',
			{ 'location_id': new_home_location_id, '<?= $t_item->primaryKey(); ?>': <?php print $t_item->getPrimaryKey(); ?> },
			function(data, textStatus, jqXHR) {
				if(data && data['ok'] && (parseInt(data['ok']) == 1)) {
					var home_location_message = '<?php print addslashes($home_location_message); ?>';
					caSetHomeLocationPanel.hidePanel();
					
					_currentHomeLocation = data.label;
					jQuery("#SetHomeLocationHierarchyBrowserSelectionMessage").html(home_location_message.replace('%', data.label));
					
					// Reload chronology bundle, which may rely upon current home location
					if(caBundleUpdateManager) { 
						caBundleUpdateManager.reloadBundle('history_tracking_chronology'); 
						caBundleUpdateManager.reloadBundle('ca_objects_history'); 
						caBundleUpdateManager.reloadBundle('ca_objects_location'); 
						caBundleUpdateManager.reloadInspector(); 
					}
					jQuery("input[name='form_timestamp']").val(data.timestamp);
				} else {
					alert("Failed to set location: " + (data['errors'] ? data['errors'].join('; ') : 'Unknown error'));
				}
			},
			'json'
		);
	}
</script>
<div id="caSetHomeLocationPanel" class="caSetHomeLocationPanel"> 
	<div class='dialogHeader'><?php print _t('Set home location'); ?></div>
	<div id="caSetHomeLocationPanelContentArea">
		<?php print caFormTag($this->request, '#', 'caSetHomeLocationForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => true, 'disableUnsavedChangesWarning' => true]); ?>
			<div>
				<div class="hierarchyBrowserFind" style="float: right;">
					<?php print _t('Find'); ?>: <input type="text" id="SetHomeLocationHierarchyBrowserSearch" name="search" value="" size="25"/>
				</div>
				<div class="hierarchyBrowserMessageContainer">
					<div id='SetHomeLocationHierarchyBrowserSelectionMessage' class='hierarchyBrowserNewLocationMessage'><!-- Message specifying move destination is dynamically inserted here by ca.hierbrowser --></div>	
				</div>
				<div class="clear"><!-- empty --></div>
				<div id="SetHomeLocationHierarchyBrowser" class="hierarchyBrowserSmall">
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->		
			</div>
			
			<div id="caSetHomeLocationPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caSetHomeLocationForm', ['onclick' => 'setHomeLocation(); return false;']); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caSetHomeLocationFormCancelButton', array('onclick' => 'caSetHomeLocationPanel.hidePanel(); return false;'), array('size' => '30px')); ?></td>
					</tr>
				</table>
			</div>
			
			<?php print caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey())); ?>
			<input type='hidden' name='new_home_location_id' id='new_home_location_id' value=''/>
		</form>
	</div>
</div>
