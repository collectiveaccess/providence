<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_deaccession.php : 
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
 
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				=	(isset($va_settings['readonly']) && $va_settings['readonly']);
	
	if (!($vs_add_label 		= $this->getVar('add_label'))) { $vs_add_label = _t('Update location'); }

	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $va_settings, (bool)$t_subject->get('is_deaccessioned'), ((bool)$t_subject->get('is_deaccessioned') ? _t('Yes') : _t('No')));
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">	
				<div style="margin-bottom: 10px;">
<?php
	if ($vb_read_only) {
?>
		<div class='formLabel'><?php print _t('Deaccessioned: %1', ((bool)$t_subject->get('is_deaccessioned')) ? _t('Yes') : _t('No')); ?></div>
<?php
	} else {
		print $t_subject->htmlFormElement('is_deaccessioned', '^ELEMENT '._t('Deaccessioned?'), array('name' => "{$vs_id_prefix}is_deaccessioned", 'id' => "{$vs_id_prefix}IsDeaccessioned", 'onclick' => 'return caShowDeaccessionControls(); '));
	}
?>
				</div>
				<div id='<?php print $vs_id_prefix; ?>DeaccessionContainer' <?php print ((bool)$t_subject->get('is_deaccessioned') ? "" : "style='display: none;'"); ?>>
<?php
	print $t_subject->htmlFormElement('deaccession_date', "<div class='formLabel' style='float: left;'>^EXTRA^LABEL<br/>^ELEMENT</div>", array('name' => "{$vs_id_prefix}deaccession_date", 'id' => "{$vs_id_prefix}DeaccessionDate", 'classname' => 'dateBg', 'readonly' => $vb_read_only));
	print $t_subject->htmlFormElement('deaccession_type_id', "<div class='formLabel' style='float: left;'>^EXTRA"._t('Type')."<br/>^ELEMENT</div>", array('name' => "{$vs_id_prefix}deaccession_type_id", 'id' => "{$vs_id_prefix}DeaccessionTypeID", 'readonly' => $vb_read_only));
?>
	<br class="clear"/>
<?php
	print $t_subject->htmlFormElement('deaccession_notes', "<div class='formLabel'>^EXTRA"._t('Notes')."<br/>^ELEMENT</div>", array('name' => "{$vs_id_prefix}deaccession_notes", 'id' => "{$vs_id_prefix}DeaccessionNotes", 'readonly' => $vb_read_only));
?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
	if (!$vb_read_only) {
?>
<script type="text/javascript">
	function caShowDeaccessionControls() {
		jQuery('#<?php print $vs_id_prefix; ?>IsDeaccessioned').is(':checked') ? jQuery('#<?php print $vs_id_prefix; ?>DeaccessionContainer').slideDown(250) : jQuery('#<?php print $vs_id_prefix; ?>DeaccessionContainer').slideUp(250);
		return true;
	}
	jQuery(document).ready(function() {
		jQuery('#<?php print $vs_id_prefix; ?>DeaccessionDate').datepicker({constrainInput: false});
	});
</script>
<?php
	}
?>