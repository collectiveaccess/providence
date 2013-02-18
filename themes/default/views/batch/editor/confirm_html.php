<?php
/* ----------------------------------------------------------------------
 * bundles/confirm_html.php : 
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
	
	$t_subject 			= $this->getVar('t_subject');
	$t_set	 			= $this->getVar('t_set');
	
	$vn_num_items_in_set = $t_set->getItemCount(array('user_id' => $this->request->getUserID()));
	$vb_queue_enabled = (bool)$this->request->config->get('queue_enabled');
 	$va_last_settings = $this->getVar('batch_editor_last_settings');
?>
<script type="text/javascript">
	var caConfirmBatchExecutionPanel;
	
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caConfirmBatchExecutionPanel = caUI.initPanel({ 
				panelID: "caConfirmBatchExecutionPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caConfirmBatchExecutionPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
	
	function caExecuteBatch() {
		jQuery("#caBatchEditorForm").submit();
	}
</script>
<div id="caConfirmBatchExecutionPanel" class="caConfirmBatchExecutionPanel"> 
	<div class='dialogHeader'><?php print _t('Batch edit (%1 %2)', $vn_num_items_in_set, $t_subject->getProperty(($vn_num_items_in_set == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL')); ?></div>
	<div id="caConfirmBatchExecutionPanelContentArea">

			<div class="caConfirmBatchExecutionPanelAlertText">
<?php
				print _t("You are about to apply changes to %1 %2. These changes cannot be undone.", $vn_num_items_in_set, $t_subject->getProperty(($vn_num_items_in_set == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL'));
?>			
			</div>
			<div class="caConfirmBatchExecutionPanelAlertControls">
				<table class="caConfirmBatchExecutionPanelAlertControls">
					<tr style="vertical-align: top;">
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
					</tr>
				</table>
			</div>
			<br class="clear"/>
			<div id="caConfirmBatchExecutionPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t('Execute batch edit'), 'caConfirmBatchExecutionFormExecuteButton', array('onclick' => 'caExecuteBatch(); return false;'), array()); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Cancel'), 'caConfirmBatchExecutionFormCancelButton', array('onclick' => 'caConfirmBatchExecutionPanel.hidePanel(); return false;'), array()); ?></td>
					</tr>
				</table>
			</div>
	</div>
</div>