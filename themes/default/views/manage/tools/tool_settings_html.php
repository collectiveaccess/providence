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
 
	$o_tool = $this->getVar('tool');
	$vs_tool_identifier = $o_tool->getToolIdentifier();
?>
<h1><?php print $o_tool->getToolName(); ?></h1>
<div class="toolPluginHelpText">
	<p><?php print $o_tool->getToolDescription(); ?></p>
</div>
<?php
	//$va_last_settings = $this->getVar('batch_mediaimport_last_settings');
 
	print $vs_control_box = caFormControlBox(
		caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Run"), "caTool{$vs_tool_identifier}", array('onclick' => 'caShowConfirmBatchExecutionPanel(); return false;')).' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'manage', 'Tools', 'Settings/'.$this->request->getActionExtra(), array('tool' => $vs_tool_identifier)),
		'', 
		''
	);
	
	print caFormTag($this->request, 'Run/'.$this->request->getActionExtra(), "caTool{$vs_tool_identifier}", null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));

	$va_settings = $o_tool->getAvailableSettings();
	
	// Print command <select>
	print "<div class='formLabel'>"._t('Command')."<br/>".caHTMLSelect('command', $o_tool->getCommands())."</div>\n";
	
	// Print settings
	print $o_tool->getHTMLSettingForm(array('request' => $this->request));

	print $this->render("tools/confirm_html.php");
	print $vs_control_box;
?>
	</form>
	
<div class="editorBottomPadding"><!-- empty --></div>
	
<script type="text/javascript">
	function caShowConfirmBatchExecutionPanel() {
		var msg = '<?php print addslashes(_t("You are about to run <em>%1</em> in <em>%2</em>")); ?>';
		msg = msg.replace("%1", jQuery('#caDirectoryValue').val());
		caConfirmBatchExecutionPanel.showPanel();
		jQuery('#caConfirmBatchExecutionPanelAlertText').html(msg);
	}
	
	$(document).bind('drop dragover', function (e) {
		e.preventDefault();
	});
</script>