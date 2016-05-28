<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations_access_status.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
	$va_errors 					= $this->getVar('errors');
	
	$vb_batch					=	$this->getVar('batch');
	
	$t_rep = new ca_object_representations();

	//if (!$vb_batch) { return; }
	
	print "<div class='editorBatchModeControl'>"._t("In batch")." ".
		caHTMLSelect($vs_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('set') => '_set_'
		), array('id' => $vs_id_prefix.$t_subject->tableNum()."_rel_batch_mode_select"))."</div>\n";
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#<?php print $vs_id_prefix.$t_subject->tableNum(); ?>_rel_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#<?php print $vs_id_prefix.$t_subject->tableNum().'_ca_object_representations_access_status'; ?>').slideUp(250);
				} else {
					jQuery('#<?php print $vs_id_prefix.$t_subject->tableNum().'_ca_object_representations_access_status'; ?>').slideDown(250);
				}
			});
			jQuery('#<?php print $vs_id_prefix.$t_subject->tableNum().'_ca_object_representations_access_status'; ?>').hide();
		});
	</script>

<div id="<?php print $vs_id_prefix.$t_subject->tableNum(); ?>_ca_object_representations_access_status">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">
				<div class="caObjectRepresentationDetailEditorText">
					<?php print _t('Sets access and status values for <strong>all</strong> representations related to %1 in this batch.', $t_subject->getProperty('NAME_PLURAL')); ?>
				</div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_rep->htmlFormElement('access', null, array('classname' => '', 'id' => "{$vs_id_prefix}access", 'name' => "{$vs_id_prefix}_access", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations_access_status')); ?></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_rep->htmlFormElement('status', null, array('classname' => '', 'id' => "{$vs_id_prefix}status", 'name' => "{$vs_id_prefix}_status", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations_access_status')); ?></div>
				<br class="clear"/>
			</div>
		</div>
	</div>
</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_object_representations_access_status');
?>