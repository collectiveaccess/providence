<?php
/* ----------------------------------------------------------------------
 * bundles/intrinsic.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
$element 			= $this->getVar('form_element');
$settings 			= $this->getVar('settings');
$t_instance			= $this->getVar('t_instance');
$bundle_name 		= $this->getVar('bundle_name');
$batch				= $this->getVar('batch');

$objects_x_collections_hierarchy_enabled = (bool)$this->request->config->get('ca_objects_x_collections_hierarchy_enabled');
$objects_x_collections_hierarchy_relationship_type = $this->request->config->get('ca_objects_x_collections_hierarchy_relationship_type');

// fetch data for bundle preview
$bundle_preview = $t_instance->get($bundle_name, array('convertCodesToDisplayText' => true));
if(is_array($bundle_preview)) { $bundle_preview = ''; }

$errors = array();
if(is_array($action_errors = $this->getVar('errors'))) {
	foreach($action_errors as $o_error) {
		$errors[] = $o_error->getErrorDescription();
	}
}
if ($batch) {
	print caBatchEditorIntrinsicModeControl($t_instance, $id_prefix);
} else {
	if(!caGetOption('forACLAccessScreen', $settings, false)) {
		print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, caInitialValuesArrayHasValue($id_prefix, $bundle_preview));

?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#' + '<?= $id_prefix; ?>' + '_BundleContentPreview').text(<?= caEscapeForBundlePreview($bundle_preview); ?>);
		});
	</script>
<?php
	}
}
print caEditorBundleMetadataDictionary($this->request, "intrinsic_{$bundle_name}", $settings);
?>
	<div>
<?php
	if (isset($settings['forACLAccessScreen']) && $settings['forACLAccessScreen']) {
?>
		<div class="globalAccess">	
			<div class='title'><?= $t_instance->getFieldInfo($bundle_name, 'LABEL'); ?></div>
			<p>
<?php
	} else {
?>
		<div class="bundleContainer <?= $batch ? "editorBatchBundleContent" : ''; ?>" id="<?= $id_prefix; ?>">
			<div class="caItemList">
				<div class="labelInfo">	
<?php
	}
					if (is_array($errors) && sizeof($errors)) {
?>
						<span class="formLabelError"><?= join('; ', $errors); ?></span>
<?php
					}
					
					if ($media = $this->getVar('display_media')) {
?>
						<div style="float: right; margin: 5px 10px 5px 0px;"><?= $media; ?></div>
<?php
					}
					
					//
					// Generate "inherit" control for access where supported
					//
					if (
						($bundle_name == 'access') && 
						(bool)$t_instance->getAppConfig()->get($t_instance->tableName().'_allow_access_inheritance') &&
						$t_instance->hasField('access_inherit_from_parent') && 
						(
							($t_instance->get('parent_id') > 0)
							||
							(
								$objects_x_collections_hierarchy_enabled && $objects_x_collections_hierarchy_relationship_type
								&&
								($t_instance->tableName() === 'ca_objects')
								&&
								($t_instance->getRelatedItems('ca_collections', ['returnAs' => 'count', 'restrictToRelationshipTypes' => $objects_x_collections_hierarchy_relationship_type]) > 0)
							)
						)
					) {
						print "<div class='inheritFromParent'>".caHTMLCheckboxInput($id_prefix.'access_inherit_from_parent', array('value' => 1, 'id' => $id_prefix.'access_inherit_from_parent'), array()).' '._t('Inherit from parent?')."</div>";
?>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								jQuery('#<?= $id_prefix; ?>access_inherit_from_parent').bind('click', function(e) {
									jQuery('#<?= $id_prefix; ?>access').prop('disabled', jQuery(this).prop('checked'));
								}).prop('checked', <?= (bool)$t_instance->get('access_inherit_from_parent') ? 'true' : 'false'; ?>);
			
								if (jQuery('#<?= $id_prefix; ?>access_inherit_from_parent').prop('checked')) { 
									jQuery('#<?= $id_prefix; ?>access').prop('disabled', true);
								}
							});
						</script>
<?php
					}
?>
					<?= $element; ?>
<?php
	if ($media) {
?>
					<br style="clear: both;"/>
<?php
	}	
	if (isset($settings['forACLAccessScreen']) && $settings['forACLAccessScreen']) {
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
