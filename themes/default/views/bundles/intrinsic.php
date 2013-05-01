<?php
/* ----------------------------------------------------------------------
 * bundles/intrinsic.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
 
	$vs_id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
 	$vs_element 			= $this->getVar('form_element');
 	$va_settings 			= $this->getVar('settings');
 	$t_instance				= $this->getVar('t_instance');
 	$vs_bundle_name 		= $this->getVar('bundle_name');
 	$vb_batch				= $this->getVar('batch');
 	
 	$va_errors = array();
 	if(is_array($va_action_errors = $this->getVar('errors'))) {
 		foreach($va_action_errors as $o_error) {
 			$va_errors[] = $o_error->getErrorDescription();
 		}
 	}
 	if ($vb_batch) {
		print caBatchEditorIntrinsicModeControl($t_instance, $vs_bundle_name);
	} else {
		print caEditorBundleShowHideControl($this->request, "intrinsic_{$vs_bundle_name}");
	}
?>
	<div>
<?php
	if (isset($va_settings['forACLAccessScreen']) && $va_settings['forACLAccessScreen']) {
?>
		<div class="globalAccess">	
			<div class='title'><?php print $t_instance->getFieldInfo($vs_bundle_name, 'LABEL'); ?></div>
			<p>
<?php
	} else {
?>
		<div class="bundleContainer <?php print $vb_batch ? "editorBatchBundleContent" : ''; ?>" id="intrinsic_<?php print $vs_bundle_name; ?>">
			<div class="caItemList">
				<div class="labelInfo">	
<?php
	}
					if (is_array($va_errors) && sizeof($va_errors)) {
?>
						<span class="formLabelError"><?php print join('; ', $va_errors); ?></span>
<?php
					}
					
					if ($vs_media = $this->getVar('display_media')) {
?>
						<div style="float: right; margin: 5px 10px 5px 0px;"><?php print $vs_media; ?></div>
<?php
					}
?>
					<?php print $vs_element; ?>
<?php
	if ($vs_media) {
?>
					<br style="clear: both;"/>
<?php
	}	
	if (isset($va_settings['forACLAccessScreen']) && $va_settings['forACLAccessScreen']) {
?>
		</p>
<?php
	} else {
?>
				</div>
			</div>
<?php
	}
?>
		</div>
	</div>