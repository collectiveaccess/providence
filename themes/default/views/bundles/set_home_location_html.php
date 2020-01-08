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
?>
<script type="text/javascript">
	var caSetHomeLocationPanel;
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
</script>
<div id="caSetHomeLocationPanel" class="caSetHomeLocationPanel"> 
	<div class='dialogHeader'><?php print _t('Set home location for %1', $t_item->getProperty('NAME_SINGULAR')); ?></div>
	<div id="caSetHomeLocationPanelContentArea">
		<?php print caFormTag($this->request, 'ChangeType', 'caChangeTypeForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => true, 'disableUnsavedChangesWarning' => true]); ?>
			<p>Set home location here</p>
			
	
			<div id="caSetHomeLocationPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caChangeTypeForm'); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caSetHomeLocationPanel.hidePanel(); return false;'), array('size' => '30px')); ?></td>
					</tr>
				</table>
			</div>
			
			<?php print caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey())); ?>
		</form>
	</div>
</div>
