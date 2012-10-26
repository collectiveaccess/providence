<?php
/* ----------------------------------------------------------------------
 * bundles/change_type_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
	JavascriptLoadManager::register("panel");
	$t_item = $this->getVar('t_item');
	//if (sizeof($t_item->getTypeList()) <= 1) { return ''; }
?>
<script type="text/javascript">
	var caTypeChangePanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caTypeChangePanel = caUI.initPanel({ 
				panelID: "caTypeChangePanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caTypeChangePanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
<div id="caTypeChangePanel" class="caTypeChangePanel"> 
	<div class='dialogHeader'><?php print _t('Change %1 type', $t_item->getProperty('NAME_SINGULAR')); ?></div>
	<div id="caTypeChangePanelContentArea">
		<?php print caFormTag($this->request, 'ChangeType', 'caChangeTypeForm', null, $ps_method='post', 'multipart/form-data', '_top', array()); ?>
			<p><?php print _t('<strong>Warning:</strong> changing the %1 type will cause information in all fields not applicable to the new type to be discarded. This action cannot be undone.', $t_item->getProperty('NAME_SINGULAR')); ?></p>
			<p><?php print _t('Change type from <em>%1</em> to %2', $t_item->getTypeName(), $t_item->getTypeListAsHTMLFormElement('type_id', array('id' => 'caChangeTypeFormTypeID'), array('omitItemsWithID' => array($t_item->getTypeID()), 'childrenOfCurrentTypeOnly' => false, 'directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__))); ?></p>
	
			<div id="caTypeChangePanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t('Save'), 'caChangeTypeForm'); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caTypeChangePanel.hidePanel(); return false;'), array()); ?></td>
					</tr>
				</table>
			</div>
			
			<?php print caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey())); ?>
		</form>
	</div>
</div>