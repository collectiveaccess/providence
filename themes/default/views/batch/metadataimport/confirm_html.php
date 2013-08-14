<?php
/* ----------------------------------------------------------------------
 * bundles/confirm_html.php : 
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
	
 	$va_last_settings = $this->getVar('batch_metadataimport_last_settings');
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
		jQuery("#caBatchMetadataImportForm").submit();
	}
</script>
<div id="caConfirmBatchExecutionPanel" class="caConfirmBatchExecutionPanel"> 
	<div class='dialogHeader'><?php print _t('Import data'); ?></div>
	<div id="caConfirmBatchExecutionPanelContentArea">

			<div class="caConfirmBatchExecutionPanelAlertText" id="caConfirmBatchExecutionPanelAlertText">
<?php
				print _t("You are about to import data.");
?>			
			</div>
			<div class="caConfirmBatchExecutionPanelAlertControls">
				<table class="caConfirmBatchExecutionPanelAlertControls">
					<tr style="vertical-align: top;">
<?php
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
					print caHTMLCheckboxInput('send_sms_when_done', $va_opts);
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
						<td align="right"><?php print caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t('Execute data import'), 'caConfirmBatchExecutionFormExecuteButton', array('onclick' => 'caExecuteBatch(); return false;'), array()); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Cancel'), 'caConfirmBatchExecutionFormCancelButton', array('onclick' => 'caConfirmBatchExecutionPanel.hidePanel(); return false;'), array()); ?></td>
					</tr>
				</table>
			</div>
	</div>
</div>