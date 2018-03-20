<?php
/* ----------------------------------------------------------------------
 * bundles/ca_metadata_alert_rule_type_restrictions.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *q
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

$vs_id_prefix = $this->getVar('placement_code').$this->getVar('id_prefix');
$vn_table_num = $this->getVar('table_num');
/** @var ca_metadata_alert_triggers $t_trigger */
$t_trigger = $this->getVar('t_trigger');

$va_errors = array();
if(is_array($va_action_errors = $this->getVar('errors'))) {
	foreach($va_action_errors as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
}

print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">
				<?php
				if (is_array($va_errors) && sizeof($va_errors)) {
					?>
					<span class="formLabelError"><?php print join('; ', $va_errors); ?></span>
					<?php
				}
				?>
				<?php print $t_trigger->htmlFormElement('trigger_type', null, ['name' => $vs_id_prefix . '_trigger_type', 'id' => $vs_id_prefix.'triggerTypeSelect']); ?>
				<div id="<?php print $vs_id_prefix; ?>triggerTypeSettingsForm"></div>
			</div>
		</div>
	</div>
</div>


<script type="text/javascript">
	function caSetTriggerSettingsForm(opts) {
		if (!opts) { opts = {}; }
		opts['triggerType'] = jQuery("#<?php print $vs_id_prefix.'triggerTypeSelect'; ?>").val();
		opts['trigger_id'] = <?php print (int)$t_trigger->getPrimaryKey(); ?>;
		opts['id_prefix'] = '<?php print $vs_id_prefix; ?>';
		jQuery("#<?php print $vs_id_prefix; ?>triggerTypeSettingsForm").load('<?php print caNavUrl($this->request, 'manage/metadata_alert_rules', 'MetadataAlertRuleEditor', 'getTriggerTypeSettingsForm'); ?>', opts);
	}

	jQuery(document).ready(function() {
		caSetTriggerSettingsForm();

		jQuery("#<?php print $vs_id_prefix.'triggerTypeSelect'; ?>").change(function() { caSetTriggerSettingsForm(); });
	});
</script>
