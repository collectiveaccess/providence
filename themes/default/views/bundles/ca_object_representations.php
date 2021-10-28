<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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
	AssetLoadManager::register('fileupload');
	AssetLoadManager::register('sortableUI');
	AssetLoadManager::register('3dmodels');
	AssetLoadManager::register('directoryBrowser');
	
	$upload_max_filesize = caFormatFileSize(caReturnValueInBytes(ini_get( 'upload_max_filesize' )));
	
	$settings 			= $this->getVar('settings');
	
	$is_batch			= $this->getVar('batch');
	$use_classic_interface 	= (($settings['uiStyle'] === 'CLASSIC') || $is_batch);		// use classic UI for batch always

	if ($use_classic_interface || $is_batch) {
		print $this->render('ca_object_representations_classic.php');
		return;
	}
	
 	$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');			// object representation
	$table_num 			= $t_item->tableNum();
	
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');		// object
	$add_label 			= $this->getVar('add_label');
	
	$rel_dir         	= ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? 'ltol' : 'rtol';
	$left_sub_type_id 	= ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
	$right_sub_type_id 	= ($t_item_rel->getRightTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
	$rel_types          = $t_item_rel->getRelationshipTypes($left_sub_type_id, $right_sub_type_id, ['restrict_to_relationship_types' => caGetOption(['restrict_to_relationship_types', 'restrictToRelationshipTypes'], $settings, null)]);

	$read_only			= (isset($settings['readonly']) && $settings['readonly']);
	
	$num_per_page 		= caGetOption('numPerPage', $settings, 10);
	$initial_values 	= caSanitizeArray($this->getVar('initialValues'), ['removeNonCharacterData' => false]);
	
	// Dyamically loaded sort ordering
	$loaded_sort 			= $this->getVar('sort');
	$loaded_sort_direction 	= $this->getVar('sortDirection');
	
	$rep_count = $t_subject->getRepresentationCount($settings);
	$allow_fetching_from_urls = $this->request->getAppConfig()->get('allow_fetching_of_media_from_remote_urls');
	$allow_relationships_to_existing_representations = (bool)$this->request->getAppConfig()->get($t_subject->tableName().'_allow_relationships_to_existing_representations') && !(bool)caGetOption('dontAllowRelationshipsToExistingRepresentations', $settings, false);
	$dont_allow_access_to_import_directory = caGetOption('dontAllowAccessToImportDirectory', $settings, false);
	
	$errors = $failed_inserts = [];
	
	$primary_id = null;
	foreach($initial_values as $representation_id => $rep) {
		if(is_array($action_errors = $this->request->getActionErrors('ca_object_representations', $representation_id))) {
			foreach($action_errors as $o_error) {
				$errors[$representation_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
		}
		if ($rep['is_primary']) { $primary_id = (int)$rep['representation_id']; }
	}
	
	$bundles_to_edit = caGetOption('showBundlesForEditing', $settings, [], ['castTo' => 'array']);
	$bundles_to_edit_order = preg_split("![;,\n\r]+!", caGetOption('showBundlesForEditingOrder', $settings, '', ['castTo' => 'string']));
 	$bundles_to_edit_proc = array_map(function($v) { $f = explode('.', $v); return join('.', (sizeof($f) > 1) ? array_slice($f,  1) : $f); }, $bundles_to_edit);
 
 	if (is_array($bundles_to_edit_order) && sizeof($bundles_to_edit_order)) {
 		$bundles_to_edit_sorted = [];
 		foreach($bundles_to_edit_order as $o) {
 			if (!($t = join('.', array_slice(explode('.', $o), 1)))) { continue; }
 			if (in_array($t, $bundles_to_edit_proc)) { $bundles_to_edit_sorted[] = $t; }
 		}
 		foreach($bundles_to_edit_proc as $t) {
 			if (!in_array($t, $bundles_to_edit_sorted)) { $bundles_to_edit_sorted[] = $t; }
 		}
 		$bundles_to_edit_proc = $bundles_to_edit_sorted;
 	}
 	
	$embedded_import_opts = (bool)$this->request->getAppConfig()->get('allow_user_selection_of_embedded_metadata_extraction_mapping') ? ca_data_importers::getImportersAsHTMLOptions(['formats' => ['exif', 'mediainfo'], 'tables' => [$t_instance->tableName(), 'ca_object_representations'], 'nullOption' => (bool)$this->request->getAppConfig()->get('allow_user_embedded_metadata_extraction_mapping_null_option') ? '-' : null]) : [];
 
	$count = $this->getVar('relationship_count');
	
	if (!RequestHTTP::isAjax()) {
		if(caGetOption('showCount', $va_settings, false)) { print $count ? "({$count})" : ''; }
	
		if ($vb_batch) {
			print caBatchEditorRelationshipModeControl($t_item, $id_prefix);
		} else {		
			print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, caInitialValuesArrayHasValue($id_prefix, $this->getVar('initialValues')));
		}
		print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);
	} 
?>
 <div id="<?= $id_prefix; ?>">
 	<div class="bundleContainer"> </div>
 	
	<input type="hidden" id="<?= $id_prefix; ?>_ObjectRepresentationBundleList" name="<?= $id_prefix; ?>_ObjectRepresentationBundleList" value=""/>
 <?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
<?php if (!$read_only) { ?>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
<?php } ?>	
			<div class="mediaUploadContainer">
				<div class="objectRepresentationListImageContainer">
					<div class="objectRepresentationListImage"><a href="#" onclick="caMediaPanel.showPanel('<?= urldecode(caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $t_subject->getPrimaryKey(), 'representation_id' => '{representation_id}'))); ?>'); return false;">{icon}</a></div>
					
					<div id='{fieldNamePrefix}change_{n}' class='mediaMetadataActionButton'><a href='#' id='{fieldNamePrefix}MediaMetadataEditButton{n}'><?= caNavIcon(__CA_NAV_ICON_EDIT_TEXT__, 1).' '._t('Edit').'</a> '; ?></div>
				
					<div id='{fieldNamePrefix}is_primary_indicator_{n}' class='caObjectRepresentationPrimaryIndicator'><?= _t('Will be primary after save'); ?></div>
					<div id='{fieldNamePrefix}change_indicator_{n}' class='caObjectRepresentationChangeIndicator'><?= _t('Changes will be applied on save'); ?></div>
					
					<input type="hidden" name="{fieldNamePrefix}is_primary_{n}" id="{fieldNamePrefix}is_primary_{n}" class="{fieldNamePrefix}is_primary" value="{is_primary}"/>
				</div>
				
				<div class='objectRepresentationMetadataEditorMediaRightCol' id='{fieldNamePrefix}objectRepresentationMetadataEditorMediaRightCol{n}'>
					<div id="{fieldNamePrefix}primary_{n}" class='mediaMetadataActionButton'>
						<span id="{fieldNamePrefix}primary_{n}"><a href='#' id='{fieldNamePrefix}SetAsPrimaryButton{n}'><?= caNavIcon(__CA_NAV_ICON_MAKE_PRIMARY__, 1).' '._t('Make primary'); ?></a></span>
					</div>
					
					<div id="{fieldNamePrefix}edit_{n}" class='mediaMetadataActionButton'>
						<span id="{fieldNamePrefix}edit_{n}"><?= urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 1).' '._t('Full record'), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit', array('representation_id' => "{representation_id}"), array('id' => "{fieldNamePrefix}edit_button_{n}"))); ?></span>
					</div>
					
					<div class="caAnnoEditorLaunchButton mediaMetadataActionButton annotationTypeClip{annotation_type}">
						<span id="{fieldNamePrefix}edit_annotations_{n}"><a href="#" id="{fieldNamePrefix}edit_annotations_button_{n}"><?= caNavIcon(__CA_NAV_ICON_CLOCK__, 1); ?> <?= _t('Annotations'); ?></a></span>
					</div>
					<div class="caSetImageCenterLaunchButton mediaMetadataActionButton annotationTypeSetCenter{annotation_type}">
						<span id="{fieldNamePrefix}edit_image_center_{n}"><a href="#" id="{fieldNamePrefix}edit_image_center_{n}"><?= caNavIcon(__CA_NAV_ICON_SET_CENTER__, 1); ?> <?= _t('Set center'); ?></a></span>
					</div>
					<div class="mediaMetadataActionButton">
						<span id="{fieldNamePrefix}edit_image_center_{n}"><a href="#" id="{fieldNamePrefix}caObjectRepresentationMetadataButton_{n}"><?php print caNavIcon(__CA_NAV_ICON_MEDIA_METADATA__, '1').' '._t('Metadata'); ?></a></a></span>
					</div>
<?php
	if($this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
?>
					<div class='mediaMetadataActionButton'>
						<span id="{fieldNamePrefix}download_{n}"><?= urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1).' '._t('Download'), '', '*', '*', 'DownloadMedia', array('version' => 'original', 'representation_id' => "{representation_id}", $t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'download' => 1), array('id' => "{fieldNamePrefix}download_button_{n}"))); ?></span>
					</div>
<?php
	}
?>
				</div>
				
				<div class="mediaUploadInfoArea">
					<div style="float: left; width: 100%;">
						<div id='{fieldNamePrefix}rep_info_ro{n}' class='mediaMetadataDisplay'>
							{_display}
						</div>
						<div id='{fieldNamePrefix}detail_editor_{n}' class="objectRepresentationMetadataEditorContainer">
 <?php
    if($t_item_rel->hasField('type_id') && (sizeof($rel_types) > 1)) {
?>
						<div class='formLabel'><?= _t('Relationship type: %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($rel_dir, $left_sub_type_id, $right_sub_type_id, array('id' => '{fieldNamePrefix}rel_type_id_{n}', 'name' => '{fieldNamePrefix}rel_type_id_{n}', 'value' => '{rel_type_id}'), $settings)); ?></div>
<?php
	} 
	if ($allow_fetching_from_urls) { 
?>
						<div class='formLabel'><?= _t('Fetch media from URL'); ?><br/><?php print caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '500px')); ?></div>			
<?php 
	} 
						foreach($bundles_to_edit_proc as $f) {
							if($f === 'type_id') { // type
								print "<div class='formLabel''>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".$t_item->getTypeListAsHTMLFormElement("{$id_prefix}_{$f}_{n}", ['id' => "{$id_prefix}_{$f}_{n}", 'value' => '{'.$f.'}'], ['restrictToTypes' => caGetOption(['restrict_to_types', 'restrictToTypes'], $settings, null), 'width' => '500px', 'height' => null, 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."</div>\n";
							} elseif($t_item->hasField($f)) { // intrinsic
								print $t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '500px', 'height' => null, 'value' => '{'.$f.'}', 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."\n";
							} elseif($t_item->hasElement($f)) {
								$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '500px', 'height' => null, 'elementsOnly' => true, 'value' => '{{'.$f.'}}', 'textAreaTagName' => 'textentry']);
								print "<div class='formLabel''>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".array_shift(array_shift($form_element_info['elements']))."</div>\n"; 
							} elseif($f === 'preferred_labels.name') {
								print "<div class='formLabel'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".caHTMLTextInput("{$id_prefix}_rep_label_{n}", ['width' => '500px', 'name' => "{$id_prefix}_rep_label_{n}", 'id' => "{$id_prefix}_rep_label_{n}", 'value' => '{{rep_label}}'])."</div>\n"; 
							}
						}

	if(is_array($embedded_import_opts) && sizeof($embedded_import_opts)) {
?>
							<div class="formLabel">
<?php
								print _t('Import embedded metadata using').' '.caHTMLSelect('{fieldNamePrefix}importer_id_{n}', $embedded_import_opts);
?>
							</div>
<?php
	}
?>
							<div class='objectRepresentationMetadataEditorDoneButton'>
<?php 
								print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Done'), '{fieldNamePrefix}MediaMetadataSaveButton{n}'); 
?>
							</div>	
						</div>
					</div>
				</div>
			</div>
			<div id="{fieldNamePrefix}media_metadata_container_{n}">	
				<div id="{fieldNamePrefix}media_metadata_{n}" class="caObjectRepresentationMetadata">
					<div class='caObjectRepresentationSourceDisplay'>{fetched}</div>
					<div class='caObjectRepresentationMD5Display'>{md5}</div>
					<div class='caObjectRepresentationMetadataDisplay'>{metadata}</div>
				</div>
			</div>
			<br class="clear"/>
		</div>
		<script type="text/javascript">
			var <?= $id_prefix; ?>MUM{n}; 
			jQuery(document).ready(function() {
				<?= $id_prefix; ?>MUM{n} = caUI.initMediaUploadManager({
					fieldNamePrefix: '<?= $id_prefix; ?>',
					uploadURL:  <?= json_encode(caNavUrl($this->request, '*', '*', 'UploadFiles')); ?>,
					setCenterURL: <?= json_encode(urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetImageCenterEditor', ['representation_id' => '{representation_id}']))); ?>,
					annotationEditorURL: <?= json_encode(urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetAnnotationEditor', ['representation_id' => '{representation_id}']))); ?>,
					index: '{n}',
					primaryID: <?= $primary_id ? $primary_id : 'null'; ?>,
					representationID: {representation_id},
					uploadAreaMessage: <?= json_encode(caNavIcon(__CA_NAV_ICON_ADD__, '30px').'<div style=\'margin-top: 5px;\'>'._t("Upload media").'</div>'); ?>,
					uploadAreaIndicator: <?= json_encode(caNavIcon(__CA_NAV_ICON_SPINNER__, '30px').'<br/>'._t("Uploading... %percent")); ?>,
					isPrimaryLabel: <?= json_encode(caNavIcon(__CA_NAV_ICON_IS_PRIMARY__, 1).' '._t('Is primary')); ?>
				});	
			});
		</script>
		
		<!-- image center coordinates -->
		<input type="hidden" name="{fieldNamePrefix}center_x_{n}" id="{fieldNamePrefix}center_x_{n}" value="{center_x}"/>
		<input type="hidden" name="{fieldNamePrefix}center_y_{n}" id="{fieldNamePrefix}center_y_{n}" value="{center_y}"/>
	</textarea>
		
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">
			<div id="<?= $id_prefix; ?>objectRepresentationAddForm{n}" class="objectRepresentationAddForm">
				<span class="formLabelError">{error}</span>
				<div style="float: right;">
					<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
				</div>
				<div class="mediaUploadContainer">
					<div style="float: left;">
	<?php					
				if($this->request->getAppConfig()->get('allow_representations_without_media')) {
	?>
					<div class="formLabelPlain"><?= caHTMLCheckBoxInput("{$id_prefix}_no_media_{n}", ['value' => 1, 'id' => $id_prefix.'NoMedia{n}'])._t('No media'); ?></div>
	<?php
				}
	?>
						<div id="<?= $id_prefix; ?>UploadArea{n}" class="mediaUploadArea">
							<input type="file" style="display: none;" id="<?= $id_prefix; ?>UploadFileControl{n}" multiple/>
							<div id="<?= $id_prefix; ?>UploadAreaMessage{n}" class="mediaUploadAreaMessage"> </div>
						</div>
<?php 
	if(!$dont_allow_access_to_import_directory) { 
?>
						<div class='mediaMetadataActionButton'><a href="#" onclick='<?= $id_prefix; ?>showMediaBrowser{n}(); return false;'><?= caNavIcon(__CA_NAV_ICON_FOLDER_OPEN__, 1).' '._t('Media on server'); ?></a></div>
<?php
	}
	if ($allow_relationships_to_existing_representations) {
?>					
						<div class='mediaMetadataActionButton'><a href="#" onclick='<?= $id_prefix; ?>switchMode{n}("REL"); return false;'><?= caNavIcon(__CA_NAV_ICON_ADD__, 1).' '._t('Search media'); ?></a></div>
<?php
	}
?>
					</div>
					<div class="mediaUploadEditArea">
	
	<?php if ($allow_fetching_from_urls) { ?>
					<div class='formLabel'><?= _t('Fetch media from URL'); ?><br/><?php print caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '500px')); ?></div>
				
	<?php } ?>				
	<?php
		if($t_item_rel->hasField('type_id') && (sizeof($rel_types) > 1)) {
	?>
					<div class='formLabel'><?= _t('Relationship type: %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($rel_dir, $left_sub_type_id, $right_sub_type_id, array('name' => '{fieldNamePrefix}rel_type_id_{n}'), $settings)); ?></div>
	<?php
		} else {
					// Embed type when only a single type is available
					print caHTMLHiddenInput('{fieldNamePrefix}rel_type_id_{n}', ['value' => array_shift(array_keys($rel_types))]);
		}
	
					foreach($bundles_to_edit_proc as $f) {
						if(in_array($f, ['media'])) { continue; }
					
						if($f === 'type_id') { // type
							print "<div class='formLabel'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".$t_item->getTypeListAsHTMLFormElement("{$id_prefix}_{$f}_{n}", ['id' => "{$id_prefix}_{$f}_{n}", 'value' => '{'.$f.'}'], ['restrictToTypes' => caGetOption(['restrict_to_types', 'restrictToTypes'], $settings, null), 'width' => '500px', 'height' => null, 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."</div>\n";
						} elseif($t_item->hasField($f)) { // intrinsic
							print $t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '500px', 'height' => null, 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."\n";
						} elseif($t_item->hasElement($f)) {
							$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '500px', 'height' => null, 'elementsOnly' => true, 'textAreaTagName' => 'textentry']);
							print "<div class='formLabel'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".array_shift(array_shift($form_element_info['elements']))."</div>\n"; 
						} elseif($f === 'preferred_labels.name') {
							print "<div class='formLabel'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".caHTMLTextInput("{$id_prefix}_rep_label_{n}", ['width' => '500px', 'id' => "{$id_prefix}_rep_label_{n}", 'value' => ''])."</div>\n"; 
						}
					}
				
					if(is_array($embedded_import_opts) && sizeof($embedded_import_opts)) {
?>
						<div class="formLabel">
<?php
							print _t('Import embedded metadata using').' '.caHTMLSelect('{fieldNamePrefix}importer_id_{n}', $embedded_import_opts);
?>
						</div>
<?php
		}
?>
					</div>
				</div>
				<input type="hidden" id="<?= $id_prefix; ?>MediaRefs{n}" name="<?= $id_prefix; ?>_mediarefs{n}"/>
				<br class="clear"/>
			</div>
<?php
	if ($allow_relationships_to_existing_representations) {
?>
			<div id="<?= $id_prefix; ?>objectRepresentationRelateForm{n}" class="objectRepresentationRelateForm">
				<span class="formLabelError">{error}</span>
				<div style="float: right;">
					<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
				</div>
				
				<div class='mediaMetadataActionButton'><a href="#" onclick='<?= $id_prefix; ?>switchMode{n}("UPLOAD"); return false;'><?= caNavIcon(__CA_NAV_ICON_UPLOAD__, 1).' '._t('Upload media'); ?></a></div>
			
				<?php print caHTMLTextInput('{fieldNamePrefix}autocomplete{n}', array('placeholder' => caExtractSettingsValueByUserLocale('autocompletePlaceholderText', $settings, ['default' => _t('Search for representation')]), 'value' => '{{label}}', 'id' => '{fieldNamePrefix}autocomplete{n}', 'class' => 'lookupBg uploadInput'), array('width' => '425px')); ?>
<?php
		if ($t_item_rel && $t_item_rel->hasField('type_id')) {
?>
				<select name="<?= $id_prefix; ?>_type_id{n}" id="<?= $id_prefix; ?>_type_id{n}" style="display: none; width: 72px;"></select>
<?php
		}
?>
				<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
			</div>
<?php
	}
?>	
		</div>
		<script type="text/javascript">
			var <?= $id_prefix; ?>MUM{n}; 
			var <?= $id_prefix; ?>MediaBrowserPanel;
			jQuery(document).ready(function() {
				<?= $id_prefix; ?>MUM{n} = caUI.initMediaUploadManager({
					fieldNamePrefix: '<?= $id_prefix; ?>',
					uploadURL:  '<?= caNavUrl($this->request, '*', '*', 'UploadFiles'); ?>',
					index: '{n}',
					maxFilesize: <?= caReturnValueInBytes(ini_get('upload_max_filesize')); ?>,
					maxFilesizeTxt: "<?= $upload_max_filesize; ?>",
					primaryID: <?= $primary_id ? $primary_id : 'null'; ?>,
					uploadAreaMessage: <?= json_encode(caNavIcon(__CA_NAV_ICON_ADD__, '30px').'<div style=\'margin-top: 5px;\'>'._t("Upload media").'</div>'); ?>,
					uploadAreaIndicator: <?= json_encode(caNavIcon(__CA_NAV_ICON_SPINNER__, '30px').'<br/>'._t("Uploading... %percent")); ?>,
				});	
				
				jQuery('#<?= $id_prefix; ?>NoMedia{n}').on('click', function(e) {
					if(jQuery(this).attr('checked')) {
						jQuery('#<?= $id_prefix; ?>UploadArea{n}').slideUp(250);
					} else {
						jQuery('#<?= $id_prefix; ?>UploadArea{n}').slideDown(250);
					}
				});
			});

			function <?= $id_prefix;?>showMediaBrowser{n}() {
				<?= $id_prefix; ?>MediaBrowserPanel.showPanel('<?= caNavUrl($this->request, '*', '*', 'MediaBrowser'); ?>', function(d) { 
					let path = oDirBrowser.getSelectedPath().join('/');
					let files = oDirBrowser.getSelectedFles();
					
					for(let i in files) {
						<?= $id_prefix; ?>MUM{n}.addFiles(path + '/' + files[i]);
					}
				}, true, null, {n: '{n}'});
			}
			
			function <?= $id_prefix;?>switchMode{n}(mode) {
				if(mode === 'REL') {
					jQuery('#<?= $id_prefix; ?>objectRepresentationAddForm{n}').slideUp(250);
					jQuery('#<?= $id_prefix; ?>objectRepresentationRelateForm{n}').slideDown(250);
				} else {
					jQuery('#<?= $id_prefix; ?>objectRepresentationAddForm{n}').slideDown(250);
					jQuery('#<?= $id_prefix; ?>objectRepresentationRelateForm{n}').slideUp(250);
				}
			}
		</script>
	</textarea>
	
	<div class="bundleContainer">
<?php
	if(is_array($initial_values) && sizeof($initial_values)) {
?>
	    <div class='bundleSubLabel'>
<?php
			print caEditorBundleBatchEditorControls($this->request, $vn_placement_id, $t_subject, $t_instance->tableName(), $va_settings);
            print caEditorBundleSortControls($this->request, $id_prefix, $t_item->tableName(), $t_instance->tableName(), array_merge($settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction]));

		    if (($rep_count > 1) && $this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
			    print "<div class='mediaMetadataActionButton' style='float: right'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1)." "._t('Download all'), 'button', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()])."</div>";
            }
?>
		</div>
		<br class="clear"/>
<?php
	}
?>
		<div class="caItemList">
			
		</div>
<?php 
	if (!$read_only) {
?>
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label ? $add_label : _t("Add representation")." &rsaquo;"; ?></a></div>
<?php
	}
?>
	</div>
</div>

 <script type="text/javascript">
	var caRelationBundle<?= $id_prefix; ?>;
 	jQuery(document).ready(function() {
		caRelationBundle<?= $id_prefix; ?> = caUI.initRelationBundle('#<?= "{$id_prefix}"; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			templateValues: ['label', 'id', '_display', 'status', 'access', 'access_display', 'is_primary', 'is_primary_display', 'media', 'locale_id', 'icon', 'type', 'metadata', 'rep_type_id', 'type_id', 'typename', 'center_x', 'center_y', 'idno' <?= (is_array($bundles_to_edit_proc) && sizeof($bundles_to_edit_proc)) ? ", ".join(", ", array_map(function($v) { return "'{$v}'"; }, $bundles_to_edit_proc)) : ''; ?>],
			initialValues: <?= json_encode($initial_values); ?>,
			initialValueOrder: <?= json_encode(array_keys($initial_values)); ?>,
			errors: <?= json_encode($errors); ?>,
			forceNewValues: <?= json_encode($failed_inserts); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			itemClassName: 'labelInfo',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showOnNewIDList: ['<?= $id_prefix; ?>_media_'],
			hideOnNewIDList: ['<?= $id_prefix; ?>_edit_','<?= $id_prefix; ?>_download_', '<?= $id_prefix; ?>_media_metadata_container_', '<?= $id_prefix; ?>_edit_annotations_', '<?= $id_prefix; ?>_edit_image_center_'],
			enableOnNewIDList: [],
			placementID: <?= json_encode($settings['placement_id']); ?>,
			showEmptyFormsOnLoad: 1,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			isSortable: <?= !$read_only && !$batch ? "true" : "false"; ?>,
			listSortOrderID: '<?= $id_prefix; ?>_ObjectRepresentationBundleList',
			defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			relationshipTypes: <?= json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'ObjectRepresentation', 'Get', []); ?>',
			autocompleteInputID: '<?= $id_prefix; ?>_autocomplete',
			
			extraParams: { exact: 1 },
			
			minRepeats: <?= $batch ? 1 : caGetOption('minRelationshipsPerRow', $settings, 0); ?>,
			maxRepeats: <?= $batch ? 1 : caGetOption('maxRelationshipsPerRow', $settings, 65535); ?>,
			
			sortUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Sort', array('table' => $t_item_rel->tableName())); ?>',
			
			loadedSort: <?= json_encode($loaded_sort); ?>,
			loadedSortDirection: <?= json_encode($loaded_sort_direction); ?>,
			
			totalValueCount: <?= (int)$rep_count; ?>,
			partialLoadUrl: '<?= caNavUrl($this->request, '*', '*', 'loadBundleValues', [$t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $settings['placement_id'], 'bundle' => 'ca_object_representations']); ?>',
			loadSize: <?= $num_per_page; ?>,
			partialLoadMessage: '<?= addslashes(_t('Load next %num of %total')); ?>',
			partialLoadIndicator: '<?= addslashes(caBusyIndicatorIcon($this->request)); ?>',
			onPartialLoad: function(d) {				
				// Hide annotation editor links for non-timebased media
				jQuery(".caAnnoEditorLaunchButton").hide();
				jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
			}
		
		});
		if (caUI.initPanel) {
			<?= $id_prefix; ?>MediaBrowserPanel = caUI.initPanel({ 
				panelID: "caMediaBrowserPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caMediaBrowserPanelContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				finallyCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
	});
</script>

<div id="caMediaBrowserPanel" class="caMediaBrowserPanel"> 
	<div class='dialogHeader'><?php print _t('Choose media from server'); ?></div>
	<div id="caMediaBrowserPanelContentArea">
	
	</div>
	
</div>
