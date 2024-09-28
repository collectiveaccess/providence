<?php
/* ----------------------------------------------------------------------
 * views/manage/tools/tool_settings_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2024 Whirl-i-Gig
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
$o_tool = 			$this->getVar('tool');
$tool_identifier = 	$o_tool->getToolIdentifier();
$settings = 		$this->getVar('available_settings');
$form_id = 			$this->getVar('form_id');
$last_settings =	$this->getVar('last_settings');
$settings_visibility_map = $this->getVar('settings_visibility_map');
?>
<h1><?= $o_tool->getToolName(); ?></h1>
<div class="toolPluginHelpText">
	<p><?= $o_tool->getToolDescription(); ?></p>
</div>
<?php
	print caFormTag($this->request, 'Run', $form_id, null, 'POST', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
	
	print $control_box = caFormControlBox(
		caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Run"), "caRunTool{$tool_identifier}", array('onclick' => 'caShowConfirmToolExecutionPanel(); return false;')).' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'manage', 'Tools', 'Settings', array('tool' => $tool_identifier)),
		'', 
		''
	);
	// Print command <select>
?>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Command'); ?></span> 
			<div class="bundleContainer">
				<div class="caLabelList">
					<p>
						<?= caHTMLSelect('command', $o_tool->getCommands(), array('id' => 'caToolCommand'));?>	
					</p>
				</div>
			</div>
		</div>
		<div class='bundleLabel toolSettingsListContainer'>
			<span class="formLabelText"><?= _t('Settings'); ?></span> 
<?php	

	// Print settings controls
	foreach($settings as $setting => $setting_info) {
?>
			<div class="bundleContainer toolSettingContainer" id="toolSettingContainer_<?= $setting; ?>">
				<div class="caLabelList" >
					<p>
						<?= $o_tool->settingHTMLFormElement($setting, array('id' => "{$form_id}_{$setting}", 'class' => 'toolSettingInput', 'name' => "{$form_id}_{$setting}", 'request' => $this->request, 'noContainerDiv' => true)); ?>	
					</p>
				</div>
			</div>
<?php
	}
?>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Log level'); ?></span> 
			<div class="bundleContainer">
				<div class="caLabelList" >
					<p>
						<?= caHTMLSelect('logLevel', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $last_settings['logLevel'])); ?>
					</p>
				</div>
			</div>
		</div>
<?php
	print $this->render("tools/confirm_html.php");
	print $control_box;
	print caHTMLHiddenInput("tool", array('value' => $tool_identifier));
?>
	</form>
	
<div class="editorBottomPadding"><!-- empty --></div>
	
<script type="text/javascript">
	let caSettingsVisibilityMap = <?= json_encode($settings_visibility_map); ?>;
	function caShowConfirmToolExecutionPanel() {
		var msg = '<?= addslashes(_t("You are about to run <em>%2</em> in <em>%1</em>", $o_tool->getToolName())); ?>';
		msg = msg.replace("%2", jQuery('#caToolCommand').val());
		caConfirmBatchExecutionPanel.showPanel();
		jQuery('#caConfirmBatchExecutionPanelAlertText').html(msg);
	}
	
	function caUpdateSettingsVisibility(command) {
		if(caSettingsVisibilityMap[command]) {
			jQuery('.toolSettingContainer').hide();
			
			let c = 0;
			for(let i in caSettingsVisibilityMap[command]) {
				jQuery('#toolSettingContainer_' + caSettingsVisibilityMap[command][i]).show();
				c++;
			}
			if(c == 0) {
				jQuery('.toolSettingsListContainer').hide();
			} else {
				jQuery('.toolSettingsListContainer').show();
			}
		} else {
			jQuery('.toolSettingContainer').show();
		}
	}
	jQuery(document).ready(function() {
		jQuery('#caToolCommand').on('change', function(e) {
			caUpdateSettingsVisibility(jQuery(this).val());
		});
		caUpdateSettingsVisibility(jQuery('#caToolCommand').val());
	});
</script>
