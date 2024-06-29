<?php
/* ----------------------------------------------------------------------
 * bundles/ca_storage_locations_contents.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2024 Whirl-i-Gig
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
if(!($qr_result = $this->getVar('qr_result'))) { return; }

$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_subject 		= $this->getVar('t_subject');				// ca_storage_locations
$settings 		= $this->getVar('settings');
$placement_code = $this->getVar('placement_code');
$placement_id	= $settings['placement_id'] ?? null;
$color 			= $settings['colorItem'] ?? '';

$rel_table 			= $qr_result->tableName();
$path 				= array_keys(Datamodel::getPath($t_subject->tableName(), $rel_table) ?? []);
$linking_table 		= $path[1] ?? null;
$errors 			= [];
	
if (!$this->request->isAjax()) {
	print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, caInitialValuesArrayHasValue($id_prefix.$t_subject->tableNum().'_rel', $this->getVar('initialValues')));
	print caEditorBundleMetadataDictionary($this->request, $id_prefix.$t_subject->tableNum().'_rel', $settings);
}	
	foreach($action_errors = $this->request->getActionErrors($placement_code) as $o_error) {
		$errors[] = $o_error->getErrorDescription();
	}
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
<?php
	if ($qr_result && ($qr_result->tableName() == 'ca_objects') && $qr_result->numHits() > 0) {
?>
		<div class="bundleSubLabel">
			<?= caEditorBundleBatchEditorControls($this->request, $placement_id, $t_subject, $qr_result->tableName(), $settings); ?>
			<?php if($linking_table) { caGetPrintFormatsListAsHTMLForRelatedBundles($id_prefix, $this->request, $t_subject, new $rel_table, new $linking_table, $placement_id); } ?>
	
			<?= caReturnToHomeLocationControlForRelatedBundle($this->request, $id_prefix, $t_subject, $this->getVar('policy'), $qr_result); ?>
		</div>
<?php
	}
?>
	<div class="caItemList">
<?php
	if ($qr_result && $qr_result->numHits() > 0) {
		

	//
	// Template to generate display for existing items
	//
    if (!$settings['displayTemplate']) { $settings['displayTemplate'] = "<l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)"; }
	switch($settings['list_format']) {
		case 'list':

			while($qr_result->nextHit()) {
?>
		<div class="labelInfo listRel caRelatedItem" <?= $color ? "style=\"background-color: #{$color};\"" : ""; ?>>
<?php	
				print $qr_result->getWithTemplate($settings['displayTemplate']);		
?>
		</div>
<?php
			}
			break;
		case 'bubbles':
		default:
			while($qr_result->nextHit()) {
?>
		<div class="labelInfo roundedRel caRelatedItem" <?= $color ? "style=\"background-color: #{$color};\"" : ""; ?>>
<?php	
				print $qr_result->getWithTemplate($settings['displayTemplate']);		
?>
		</div>
<?php
			}
			break;
		}
	} else {
?>
		<div class="labelInfo"><table><tr><td><?= _t('Empty'); ?></td></tr></table></div>
<?php
	}
?>
		</div>
	</div>
</div>
