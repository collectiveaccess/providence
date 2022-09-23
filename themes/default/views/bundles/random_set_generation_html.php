<?php
/* ----------------------------------------------------------------------
 * bundles/random_set_generation_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
	$t_content = $t_item->getItemTypeInstance();
	$t_type = $t_item->getTypeInstance();
	$settings = $t_type->getSettings();
	$default_count = caGetOption('random_generation_size', $settings, 5);
?>
<div id="caRandomSetGenerationPanel" class="caRandomSetGenerationPanel"> 
	<div class='dialogHeader'><?= _t('Add random content to set'); ?></div>
	<div id="caRandomSetGenerationPanelContentArea">
		<?= caFormTag($this->request, 'randomSetGeneration', 'caRandomSetGenetationForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]); ?>
			<p><?= _t('Add %1 random %2 to this set', caHTMLTextInput('count', ['value' => $default_count], ['width' => '22px']), $t_content->getProperty('NAME_PLURAL')); ?></p>
			<p><?= _t('Limit selection to %1<br/>', $t_content->getTypeListAsHTMLFormElement('type_id[]', ['multiple' => true, 'height' => 10, 'id' => 'caRandomSetGenetationFormTypeID', 'childrenOfCurrentTypeOnly' => false, 'directChildrenOnly' => false, 'returnHierarchyLevels' => true]));
			?></p>
	
			<div id="caRandomSetGenerationPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?= caJSButton($this->request, __CA_NAV_ICON_ADD__, _t('Add'), 'caRandomSetGenetationForm', ['onclick' => 'caAddRandomItems(); return false;']); ?></td>
						<td align="left"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caRandomSetGenetationFormCancelButton', ['onclick' => 'caRandomSetGenerationPanel.hidePanel(); return false;'], ['size' => '30px']); ?></td>
					</tr>
				</table>
			</div>
			
			<?= caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey())); ?>
		</form>
	</div>
</div>

<script type="text/javascript">
	var caRandomSetGenerationPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caRandomSetGenerationPanel = caUI.initPanel({ 
				panelID: "caRandomSetGenerationPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRandomSetGenerationPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
	function caAddRandomItems() {
		jQuery.getJSON('<?= caNavUrl($this->request, '*', '*', 'randomSetGeneration'); ?>', jQuery("#caRandomSetGenetationForm").serialize(), function(e) {
			// Reload inspector and set list bundle in parent form
			if(caBundleUpdateManager) { 
				caBundleUpdateManager.reloadBundle('ca_set_items'); 
				caBundleUpdateManager.reloadInspector(); 
			}
			caRandomSetGenerationPanel.hidePanel();
		});
	}
</script>