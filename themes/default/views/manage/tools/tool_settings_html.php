<?php
/* ----------------------------------------------------------------------
 * views/manage/tools/tool_settings_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 
	$o_tool = 				$this->getVar('tool');
	$vs_tool_identifier = 	$o_tool->getToolIdentifier();
	
	$va_settings = 			$this->getVar('available_settings');
	$vs_form_id = 			$this->getVar('form_id');
	$va_last_settings =		$this->getVar('last_settings');
?>
<h1><?php print $o_tool->getToolName(); ?></h1>
<div class="toolPluginHelpText">
	<p><?php print $o_tool->getToolDescription(); ?></p>
</div>
<?php
	print caFormTag($this->request, 'Run', $vs_form_id, null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
	
	print $vs_control_box = caFormControlBox(
		caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Run"), "caRunTool{$vs_tool_identifier}", array('onclick' => 'caShowConfirmToolExecutionPanel(); return false;')).' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'manage', 'Tools', 'Settings', array('tool' => $vs_tool_identifier)),
		'', 
		''
	);
	// Print command <select>
?>
		<div class='bundleLabel'>
			<span class="formLabelText"><?php print _t('Command'); ?></span> 
			<div class="bundleContainer">
				<div class="caLabelList" >
					<p>
<?php
					print caHTMLSelect('command', $o_tool->getCommands(), array('id' => 'caToolCommand'));
?>	
					</p>
				</div>
			</div>
		</div>
<?php	

	// Print settings controls
	foreach($va_settings as $vs_setting => $va_setting_info) {
?>
		<div class='bundleLabel'>
			<span class="formLabelText"><?php print $va_setting_info['label']; ?></span> 
			<div class="bundleContainer">
				<div class="caLabelList" >
					<p>
<?php
					print $o_tool->settingHTMLFormElement($vs_setting, array('id' => "{$vs_form_id}_{$vs_setting}", 'name' => "{$vs_form_id}_{$vs_setting}", 'request' => $this->request));
?>	
					</p>
				</div>
			</div>
		</div>
<?php
	}
?>
		<div class='bundleLabel'>
			<span class="formLabelText"><?php print _t('Log level'); ?></span> 
			<div class="bundleContainer">
				<div class="caLabelList" >
					<p>
<?php
					print caHTMLSelect('logLevel', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $va_last_settings['logLevel']));
?>
					</p>
				</div>
			</div>
		</div>
<?php

	print $this->render("tools/confirm_html.php");

	print $vs_control_box;
	
	print caHTMLHiddenInput("tool", array('value' => $vs_tool_identifier));
?>
	</form>
	
<div class="editorBottomPadding"><!-- empty --></div>
	
<script type="text/javascript">
	function caShowConfirmToolExecutionPanel() {
		var msg = '<?php print addslashes(_t("You are about to run <em>%2</em> in <em>%1</em>", $o_tool->getToolName())); ?>';
		msg = msg.replace("%2", jQuery('#caToolCommand').val());
		caConfirmBatchExecutionPanel.showPanel();
		jQuery('#caConfirmBatchExecutionPanelAlertText').html(msg);
	}
</script>