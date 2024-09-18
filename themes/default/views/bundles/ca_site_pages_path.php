<?php
/* ----------------------------------------------------------------------
 * bundles/ca_site_pages_path.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 	$element 			= $this->getVar('form_element_raw');
 	$settings 			= $this->getVar('settings');
 	$t_instance			= $this->getVar('t_instance');
 	$bundle_name 		= $this->getVar('bundle_name');
 	$is_batch			= $this->getVar('batch');
 	
 	$app_select         = caHTMLSelect("{$id_prefix}_ca_site_pages_path_app", [_t('Providence help menu') => 'PROVIDENCE_HELP_MENU', _t('Pawtucket') => 'PAWTUCKET'], ['id' => "{$id_prefix}_ca_site_pages_path_app"]);

	// fetch data for bundle preview
	$bundle_preview = $t_instance->get($bundle_name, array('convertCodesToDisplayText' => true));
	if(is_array($bundle_preview)) { $bundle_preview = ''; }
 	
 	$errors = array();
 	if(is_array($action_errors = $this->getVar('errors'))) {
 		foreach($action_errors as $o_error) {
 			$errors[] = $o_error->getErrorDescription();
 		}
 	}
 	if ($is_batch) {
		print caBatchEditorIntrinsicModeControl($t_instance, $id_prefix);
	} 
	print caEditorBundleMetadataDictionary($this->request, "intrinsic_{$bundle_name}", $settings);
?>
	<div>
		<div class="bundleContainer <?= $is_batch ? "editorBatchBundleContent" : ''; ?>" id="<?= $id_prefix; ?>">
			<div class="caItemList">
				<div class="labelInfo">	
<?php
					if (is_array($errors) && sizeof($errors)) {
?>
						<span class="formLabelError"><?= join('; ', $errors); ?></span>
<?php
					}
?>
					<div class="formLabelPlain"><?= _t("Available <span id='{$id_prefix}_ca_site_pages_path_app_text' style='display: none;'>at %1</span> in %2", $element, $app_select); ?></div>
				</div>
			</div>
		</div>
	</div>
	
	<script type="text/javascript">
	    jQuery(document).ready(function() {
            jQuery("#<?= "{$id_prefix}_ca_site_pages_path_app"; ?>").val("<?= ($t_instance->get('path') !== 'PROVIDENCE_HELP_MENU') ? 'PAWTUCKET' : 'PROVIDENCE_HELP_MENU'; ?>");
            jQuery("#<?= "{$id_prefix}_ca_site_pages_path_app_text"; ?>").<?= ($t_instance->get('path') !== 'PROVIDENCE_HELP_MENU') ? "show()" : "hide()"; ?>;

	        jQuery("#<?= "{$id_prefix}_ca_site_pages_path_app"; ?>").on('change', function(e) {
	            if(jQuery(this).val() == 'PROVIDENCE_HELP_MENU') {
	                jQuery("#<?= "{$id_prefix}_ca_site_pages_path_app_text"; ?>").hide();
	                jQuery("#path_path").val('PROVIDENCE_HELP_MENU');
	            } else {
	                jQuery("#<?= "{$id_prefix}_ca_site_pages_path_app_text"; ?>").show();
	                if (jQuery("#path_path").val() === 'PROVIDENCE_HELP_MENU') {
	                    jQuery("#path_path").val('');
	                }
	            }
	        });
	        
	        jQuery("#path_path").on('keyup', function(e) {
	            if(jQuery(this).val().substr(0,1) !== '/') {
	                jQuery(this).val('/' + jQuery(this).val());
	            }
	        });
	    });
	</script>
