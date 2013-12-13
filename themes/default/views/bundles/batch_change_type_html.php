<?php
/* ----------------------------------------------------------------------
 * bundles/batch_change_type_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
	
	$vb_queue_enabled = (bool)$this->request->config->get('queue_enabled');
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
			<p><?php print _t('Change type from <em>%1</em> to %2', $t_item->getTypeName(), $t_item->getTypeListAsHTMLFormElement('new_type_id', array('id' => 'caChangeTypeFormTypeID'), array('childrenOfCurrentTypeOnly' => false, 'directChildrenOnly' => false, 'returnHierarchyLevels' => true, 'access' => __CA_BUNDLE_ACCESS_EDIT__))); ?></p>
	
<?php
	if ($vb_queue_enabled) {
?>
				<td class="caConfirmBatchExecutionPanelAlertControls">
<?php
					$va_opts = array('id' => 'caRunBatchInBackground', 'value' => 1);
					if (isset($va_last_settings['runInBackground']) && $va_last_settings['runInBackground']) {
						$va_opts['checked'] = 1;
					}
					print caHTMLCheckboxInput('run_in_background', $va_opts);
?>
				</td>
				<td class="caConfirmBatchExecutionPanelAlertControls">
<?php
					print _t('Process in background');
?>

				</td>
<?php
	}
	if ($vs_email = trim($this->request->user->get('email'))) {
?>
				<td class="caConfirmBatchExecutionPanelAlertControl">
<?php			
					$va_opts = array('id' => 'caSendEmailWhenDone', 'value' => 1);
					if (isset($va_last_settings['sendMail']) && $va_last_settings['sendMail']) {
						$va_opts['checked'] = 1;
					}
					print caHTMLCheckboxInput('send_email_when_done', $va_opts);
?>
				</td>
				<td class="caConfirmBatchExecutionPanelAlertControl">
<?php					
					print _t('Send email to <strong>%1</strong> when done', $vs_email);
?>			
				</td>
<?php
	}
	
	if (($vs_sms = trim($this->request->user->get('sms_number'))) && (bool)$this->request->config->get('enable_sms_notifications')) {
?>
				<td class="caConfirmBatchExecutionPanelAlertControl">
<?php			
					$va_opts = array('id' => 'caSendSMSWhenDone', 'value' => 1);
					if (isset($va_last_settings['sendSMS']) && $va_last_settings['sendSMS']) {
						$va_opts['checked'] = 1;
					}
					print caHTMLCheckboxInput('send_sms_when_done', array('id' => 'caSendSMSWhenDone', $va_opts));
?>
				</td>
				<td class="caConfirmBatchExecutionPanelAlertControl">
<?php
					print _t('Send SMS to <strong>%1</strong> when done', $vs_sms);
?>			
				</td>
<?php
	}
?>

			<div id="caTypeChangePanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t('Save'), 'caChangeTypeForm'); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Cancel'), 'caChangeTypeFormCancelButton', array('onclick' => 'caTypeChangePanel.hidePanel(); return false;'), array()); ?></td>
					</tr>
				</table>
			</div>
			
			<?php print caHTMLHiddenInput('set_id', array('value' => $this->getVar('set_id'))); ?>
		</form>
	</div>
</div>