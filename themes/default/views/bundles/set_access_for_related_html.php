<?php
/* ----------------------------------------------------------------------
 * bundles/set_access_to_related_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
$targets = $this->getVar('targets');
$options = [];
foreach($targets as $tc => $ti) {
	$options[$ti['label'] ?: '???'] = $tc;
}
?>
<script type="text/javascript">
	var caSetAccessForRelatedPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caSetAccessForRelatedPanel = caUI.initPanel({ 
				panelID: "caSetAccessForRelatedPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caSetAccessForRelatedPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
<div id="caSetAccessForRelatedPanel" class="caSetAccessForRelatedPanel"> 
	<div class='dialogHeader'><?= _t('Set access for related'); ?></div>
	<div id="caSetAccessForRelatedPanelContentArea">
		<?= caFormTag($this->request, 'SetAccessForRelated', 'caSetAccessForRelated', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]); ?>
			<p><?= _t('Set access for %1<br>with status %2 to %3', caHTMLSelect('target', $options, ['id' => 'setAccessForRelatedTarget'], ['width' => '120px']), $t_item->htmlFormElement('status', '^ELEMENT', ['value' => '', 'nullOption' => '-', 'id' => 'setAccessForRelatedStatus', 'width' => '120px']), $t_item->htmlFormElement('access', '^ELEMENT', ['id' => 'setAccessForRelatedAccess','width' => '120px'])); ?></p>
			
			<div id="caSetAccessForRelatedPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'SetAccessForRelated', ['onclick' => 'caSetAccessForRelated(); return false;'], ['size' => '30px']); ?></td>
						<td align="left"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'SetAccessForRelatedFormCancelButton', ['onclick' => 'caSetAccessForRelatedPanel.hidePanel(); return false;'], ['size' => '30px']); ?></td>
					</tr>
				</table>
			</div>
			
			<div class="caSetAccessForRelatedPanelSpinner"><?= caBusyIndicatorIcon($this->request); ?></div>
			<?= caHTMLHiddenInput($t_item->primaryKey(), array('value' => $t_item->getPrimaryKey())); ?>
		</form>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		
	});
	function caSetAccessForRelated() {
		let target = jQuery('#setAccessForRelatedTarget').val();
		let status = jQuery('#setAccessForRelatedStatus').val();
		let access = jQuery('#setAccessForRelatedAccess').val();
		
		jQuery('.caSetAccessForRelatedPanelSpinner').show();
		jQuery.ajax({
			type: 'POST',
			url: <?= json_encode(caNavUrl($this->request, '*', '*', 'setAccessForRelated')); ?>,
			data: jQuery('#caSetAccessForRelated').serialize(),
			success: caSetAccessForRelatedResults
		});
	}
	function caSetAccessForRelatedResults(resp) {
		jQuery('.caSetAccessForRelatedPanelSpinner').hide();
		if(!resp || !resp['ok']) { 
			let errors = resp['errors'] ? resp['errors'].join('; ') : null;
			alert(<?= json_encode(_t('Could not set access: ')); ?> + errors); 
			return;
		}
		let msg = <?= json_encode(_t('Set access for %1 items')); ?>.replace("%1", resp['changed'] ?? 0);
		jQuery.jGrowl(msg, { header: <?= json_encode(_t('Set access for related')); ?>});
		caSetAccessForRelatedPanel.hidePanel();
	}
</script>
