<?php
/* ----------------------------------------------------------------------
 * bundles/ca_storage_locations_contents.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_subject 			= $this->getVar('t_subject');				// ca_storage_locations
	$t_subject_rel 		= $this->getVar('t_subject_rel');
	$va_settings 		= $this->getVar('settings');
	$vs_placement_code 	= $this->getVar('placement_code');
	$vn_placement_id	= (int)$va_settings['placement_id'];
	
	$vs_color 			= ((isset($va_settings['colorItem']) && $va_settings['colorItem'])) ? $va_settings['colorItem'] : '';
	
	$qr_result			= $this->getVar('qr_result');
	
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_subject->tableNum().'_rel', $va_settings, caInitialValuesArrayHasValue($vs_id_prefix.$t_subject->tableNum().'_rel', $this->getVar('initialValues')));
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.$t_subject->tableNum().'_rel', $va_settings);
	
	$va_errors = array();
	foreach($va_action_errors = $this->request->getActionErrors($vs_placement_code) as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
?>
<div id="<?php print $vs_id_prefix.$t_subject->tableNum().'_rel'; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
<?php
	if ($qr_result && $qr_result->numHits() > 0) {

	//
	// Template to generate display for existing items
	//
    if (!$va_settings['displayTemplate']) { $va_settings['displayTemplate'] = "<l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)"; }
	switch($va_settings['list_format']) {
		case 'list':

			while($qr_result->nextHit()) {
?>
		<div class="labelInfo listRel caRelatedItem" <?php print $vs_color ? "style=\"background-color: #{$vs_color};\"" : ""; ?>>
<?php	
				print $qr_result->getWithTemplate($va_settings['displayTemplate']);		
?>
		</div>
<?php
			}
?>
		</div>
<?php
			break;
		case 'bubbles':
		default:
			while($qr_result->nextHit()) {
?>
		<div class="labelInfo roundedRel caRelatedItem" <?php print $vs_color ? "style=\"background-color: #{$vs_color};\"" : ""; ?>>
<?php	
				print $qr_result->getWithTemplate($va_settings['displayTemplate']);		
?>
		</div>
<?php
			}
			break;
		}
	} else {
?>
		<div class="labelInfo"><table><tr><td><?php print _t('Location is empty'); ?></td></tr></table></div>
<?php
	}
?>
		</div>
	</div>
</div>