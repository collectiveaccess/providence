<?php
/* ----------------------------------------------------------------------
 * bundles/create_child_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2015 Whirl-i-Gig
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
	
	$vs_table_name = $t_item->tableName();
?>
<script type="text/javascript">
	var caCreateChildPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caCreateChildPanel = caUI.initPanel({ 
				panelID: "caCreateChildPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caCreateChildPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
</script>
<div id="caCreateChildPanel" class="caCreateChildPanel"> 
	<div class='dialogHeader'><?php print _t('Create child record under this %1', $t_item->getProperty('NAME_SINGULAR')); ?></div>
	<div id="caCreateChildPanelContentArea">
<?php	
			if ($vs_type_list = $this->getVar('type_list')) {
?>
			<div class="addChild">
				<div class="addChildMessage"><?php print _t('Select a record type to add a child record under this one'); ?></div>
					<?php print caFormTag($this->request, 'Edit', 'caNewChildForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
						<?php print _t('Add a %1 under this', $vs_type_list).caHTMLHiddenInput($t_item->primaryKey(), array('value' => '0')).caHTMLHiddenInput('parent_id', array('value' => $t_item->getPrimaryKey())); ?>
						<div id="caTypeChangePanelControlButtons">
							<table>
								<tr>
									<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caNewChildForm'); ?></td>
									<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caCreateChildPanel.hidePanel(); return false;'), array('size' => '30px')); ?></td>
								</tr>
							</table>
						</div>
				</form>
			</div>
			
<?php
				}
				
				if (($t_item->tableName() == 'ca_collections') && $this->request->config->get('ca_objects_x_collections_hierarchy_enabled')) {
					$t_object = new ca_objects();
					if ((bool)$this->request->config->get('ca_objects_enforce_strict_type_hierarchy')) {
						// strict menu
						$vs_type_list = $t_object->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('childrenOfCurrentTypeOnly' => true, 'directChildrenOnly' => ($this->request->config->get($vs_table_name.'_enforce_strict_type_hierarchy') == '~') ? false : true, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__));
					} else {
						// all types
						$vs_type_list = $t_object->getTypeListAsHTMLFormElement('type_id', array('style' => 'width: 90px; font-size: 9px;'), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
					}
?>
					<div style="border-top: 1px solid #aaaaaa; margin-top: 5px; font-size: 10px; padding-top:10px" class="addChild">
						<?php print caFormTag($this->request, 'Edit', 'caNewChildObjectForm', 'editor/objects/ObjectEditor', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
						<?php print _t('Add a %1 under this', $vs_type_list).caHTMLHiddenInput('object_id', array('value' => '0')).caHTMLHiddenInput('collection_id', array('value' => $t_item->getPrimaryKey())); ?>
						<div id="caTypeChangePanelControlButtons">
							<table>
								<tr>
									<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caNewChildObjectForm'); ?></td>
									<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caCreateChildPanel.hidePanel(); return false;'), array('size' => '30px')); ?></td>
								</tr>
							</table>
						</div>
						</form>
					</div>
<?php
				}
?>			
	</div>
</div>
