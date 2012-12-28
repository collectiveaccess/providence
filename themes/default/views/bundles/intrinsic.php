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
		print "<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect("{$vs_bundle_name}_batch_mode", array(
				_t("do not use") => "_disabled_", 
				_t('set for each item') => '_replace_'
		), array("id" => "{$vs_bundle_name}_batch_mode_select"))."</div>\n";
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#<?php print $vs_bundle_name; ?>_batch_mode_select').change(function() {
				if (jQuery(this).val() == '_disabled_') {
					jQuery('#<?php print $vs_bundle_name; ?>').slideUp(250);
				} else {
					jQuery('#<?php print $vs_bundle_name; ?>').slideDown(250);
				}
			});
			jQuery('#<?php print $vs_bundle_name; ?>').hide();
		});
	</script>
<?php
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
		<div class="bundleContainer" id="<?php print $vs_bundle_name; ?>">
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