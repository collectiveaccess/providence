<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_deaccession.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2026 Whirl-i-Gig
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
$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
$table_num 			= $this->getVar('table_num');

$t_subject			= $this->getVar('t_subject');
$settings 			= $this->getVar('settings');

$read_only			= (isset($settings['readonly']) && $settings['readonly']);
$batch				= $this->getVar('batch');

$always_open		= caGetOption('alwaysOpen', $settings, false);
$show				= ((bool)$t_subject->get('is_deaccessioned') || $always_open);

if ($batch) {
	print caBatchEditorIntrinsicModeControl($t_subject, $id_prefix);
} else {
	print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, (bool)$t_subject->get('is_deaccessioned'), ((bool)$t_subject->get('is_deaccessioned') ? _t('Yes') : _t('No')));
}
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);
?>
<div id="<?= $id_prefix; ?>" class="<?= $batch ? "editorBatchBundleContent" : ''; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">	
				<div style="margin-bottom: 10px;">
<?php
	if ($read_only) {
?>
		<div class='formLabel'><?= _t('Deaccessioned: %1', ((bool)$t_subject->get('is_deaccessioned')) ? _t('Yes') : _t('No')); ?></div>
<?php
	} elseif($t_subject->getAppConfig()->get('deaccession_bundle_use_radio_buttons')) {
		print $t_subject->htmlFormElement('is_deaccessioned', _t('Deaccessioned').' ^ELEMENT', array('DISPLAY_TYPE' => DT_RADIO_BUTTONS, 'name' => "{$id_prefix}is_deaccessioned", 'id' => "{$id_prefix}IsDeaccessioned"));
	} else {
		print $t_subject->htmlFormElement('is_deaccessioned', '^ELEMENT '._t('Deaccessioned'), array('name' => "{$id_prefix}is_deaccessioned", 'id' => "{$id_prefix}IsDeaccessioned", 'onclick' => 'return caShowDeaccessionControls(); '));
	}
?>
				</div>
				<div id='<?= $id_prefix; ?>DeaccessionContainer' <?= ($show ? "" : "style='display: none;'"); ?>>
<?php
	print $t_subject->htmlFormElement('deaccession_date', "<div class='formLabel' style='float: left;'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$id_prefix}deaccession_date", 'id' => "{$id_prefix}DeaccessionDate", 'classname' => 'dateBg', 'readonly' => $read_only, 'timeOmit' => true));
	
	if ($this->request->config->get('deaccession_use_disposal_date')) {
	    print $t_subject->htmlFormElement('deaccession_disposal_date', "<div class='formLabel' style='float: left;'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$id_prefix}deaccession_disposal_date", 'id' => "{$id_prefix}DeaccessionDisposalDate", 'classname' => 'dateBg', 'readonly' => $read_only, 'timeOmit' => true));
    }
	print $t_subject->htmlFormElement('deaccession_type_id', "<div class='formLabel' style='float: left;'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$id_prefix}deaccession_type_id", 'id' => "{$id_prefix}DeaccessionTypeID", 'readonly' => $read_only));
?>
	<br class="clear"/>
<?php
	print $t_subject->htmlFormElement('deaccession_authorized_by', "<div class='formLabel'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$id_prefix}deaccession_authorized_by", 'id' => "{$id_prefix}DeaccessionAuthorizedBy", 'readonly' => $read_only));

	print $t_subject->htmlFormElement('deaccession_notes', "<div class='formLabel'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$id_prefix}deaccession_notes", 'id' => "{$id_prefix}DeaccessionNotes", 'readonly' => $read_only));
?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
if (!$read_only) {
?>
<script type="text/javascript">
	function caShowDeaccessionControls(isRadio=false) {
		<?= $always_open ? "return true;\n" : ""; ?>
		jQuery(isRadio ? '#<?= $id_prefix; ?>IsDeaccessioned_1' : '#<?= $id_prefix; ?>IsDeaccessioned').is(':checked') ? jQuery('#<?= $id_prefix; ?>DeaccessionContainer').slideDown(250) : jQuery('#<?= $id_prefix; ?>DeaccessionContainer').slideUp(250);
		return true;
	}
	jQuery(document).ready(function() {
		jQuery('#<?= $id_prefix; ?>DeaccessionDate').datepicker({constrainInput: false});
		jQuery('#<?= $id_prefix; ?>DeaccessionDisposalDate').datepicker({constrainInput: false});
		
		jQuery('#<?= $id_prefix; ?>DeaccessionAuthorizedBy').autocomplete( 
			{ 
				source: '<?= caNavUrl($this->request, 'lookup', 'Intrinsic', 'Get', ['max' => 500, 'bundle' => 'ca_objects.deaccession_authorized_by']); ?>',
				minLength: 3, delay: 800
			}
		);
		
		jQuery('#<?= $id_prefix; ?>IsDeaccessioned_1, #<?= $id_prefix; ?>IsDeaccessioned_0').on('click', function() { caShowDeaccessionControls(true)});
	});
</script>
<?php
}
