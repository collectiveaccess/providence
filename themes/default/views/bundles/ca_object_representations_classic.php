<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations_classic.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
AssetLoadManager::register('sortableUI');

$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_instance 		= $this->getVar('t_instance');
$t_item 			= $this->getVar('t_item');			// object representation
$t_item_label		= $t_item->getLabelTableInstance();
$t_item_rel 		= $this->getVar('t_item_rel');
$t_subject 			= $this->getVar('t_subject');		// object
$add_label 		= $this->getVar('add_label');
$settings 			= $this->getVar('settings');

$read_only		=	(isset($settings['readonly']) && $settings['readonly']);
$batch			=	$this->getVar('batch');

$dont_show_preferred_label = caGetOption('dontShowPreferredLabel', $settings, false);
$dont_show_idno = caGetOption('dontShowIdno', $settings, false);
$dont_show_access = caGetOption('dontShowAccess', $settings, false);
$dont_show_status = caGetOption('dontShowStatus', $settings, false);
$dont_show_transcribe = $this->request->getAppConfig()->get('allow_transcription') ? caGetOption('dontShowTranscribe', $settings, false) : true;


// Dyamically loaded sort ordering
$loaded_sort 			= $this->getVar('sort');
$loaded_sort_direction 	= $this->getVar('sortDirection');


$rel_dir         = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? 'ltol' : 'rtol';
$left_sub_type_id = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
$right_sub_type_id = ($t_item_rel->getRightTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
$rel_types          = $t_item_rel->getRelationshipTypes($left_sub_type_id, $right_sub_type_id);

$embedded_import_opts = (bool)$this->request->getAppConfig()->get('allow_user_selection_of_embedded_metadata_extraction_mapping') ? ca_data_importers::getImportersAsHTMLOptions(['formats' => ['exif', 'mediainfo'], 'tables' => [$t_instance->tableName(), 'ca_object_representations'], 'nullOption' => (bool)$this->request->getAppConfig()->get('allow_user_embedded_metadata_extraction_mapping_null_option') ? '-' : null]) : [];

// don't allow editing if user doesn't have access to any of the representation types
if(sizeof(caGetTypeListForUser('ca_object_representations', array('access' => __CA_BUNDLE_ACCESS_EDIT__)))  < 1) {
	$read_only = true;
}

if (!in_array($default_upload_type = $this->getVar('defaultRepresentationUploadType'), array('upload', 'url', 'search'))) {
	$default_upload_type = 'upload';
}

$allow_fetching_from_urls = $this->request->getAppConfig()->get('allow_fetching_of_media_from_remote_urls');

// Paging
$start = 0;
$num_per_page = 20;
$primary_id = 0;

// generate list of inital form values; the bundle Javascript call will
// use the template to generate the initial form
$rep_type_list = $t_item->getTypeList();
$errors = array();

$rep_count = $t_subject->getRepresentationCount($settings);
$initial_values = caSanitizeArray($t_subject->getBundleFormValues($this->getVar('bundle_name'), $this->getVar('placement_code'), $settings, array('start' => 0, 'limit' => $num_per_page, 'request' => $this->request)), ['removeNonCharacterData' => false]);

foreach($initial_values as $representation_id => $rep) {
	if(is_array($action_errors = $this->request->getActionErrors('ca_object_representations', $representation_id))) {
		foreach($action_errors as $o_error) {
			$errors[$representation_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
		}
	}
	if ($rep['is_primary']) {
		$primary_id = $rep['representation_id'];
	}
}

$failed_inserts = array();
foreach($this->request->getActionErrorSubSources('ca_object_representations') as $error_subsource) {
	if (substr($error_subsource, 0, 4) === 'new_') {
		$action_errors = $this->request->getActionErrors('ca_object_representations', $error_subsource);
		foreach($action_errors as $o_error) {
			$failed_inserts[] = array('icon' => '', '_errors' => array(array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber())));
		}
	}
}

if ($batch) {
	print caBatchEditorRelationshipModeControl($t_item, $id_prefix);
} else {
	print caEditorBundleShowHideControl($this->request, $id_prefix, $settings, (sizeof($initial_values) > 0), _t("Number of representations: %1", sizeof($initial_values)));
}
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);
?>
<div id="<?= $id_prefix; ?>" <?= $batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	if ($batch) {
		print caBatchEditorConditionalUITrigger($id_prefix);
		print caBatchEditorConditionalUI($id_prefix, []);
	}

	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">

			<span class="formLabelError">{error}</span>
<?php 
	if (!$read_only) {
?>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
<?php
	}
?>	
			<div style="width: 680px;">
				<div style="float: left;">
					<div class="caObjectRepresentationListItemImageThumb"><a href="#" onclick="caMediaPanel.showPanel('<?= urldecode(caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $t_subject->getPrimaryKey(), 'representation_id' => '{representation_id}'))); ?>'); return false;">{icon}</a></div>
				</div>
				<div style="float: right; width: 550px;">
					<div style="float: left; width: 80%;">
						<div id='{fieldNamePrefix}rep_info_ro{n}'>
							<div class='caObjectRepresentationListInfo'>
<?php
    if(!$dont_show_preferred_label) {
?>
								<a title="{filename}">{rep_label}</a>
<?php
    }
	if (!$read_only) {
?>
								<span id="{fieldNamePrefix}change_{n}" class="caObjectRepresentationListInfoSubDisplayUpdate"><a href='#' class='updateIcon' onclick="caOpenRepresentationDetailEditor('{n}'); return false;"><?= caNavIcon(__CA_NAV_ICON_UPDATE__, 1).'</a>'; ?></span>
<?php
	}
?>
								<span class='caObjectRepresentationPrimaryDisplay'>{is_primary_display}</span>
							</div>
											
							<div class='caObjectRepresentationListInfoSubDisplay'>
								{_display}
<?php
    if(!$dont_show_idno) {
?>
								<em>{idno}</em><br/>
<?php
    }
?>
								<h3><?= _t('File name'); ?></h3> <span class="caObjectRepresentationListInfoSubDisplayFilename" id="{fieldNamePrefix}filename_display_{n}">{filename}</span>
<?php
	TooltipManager::add("#{$id_prefix}_filename_display_{n}", _t('File name: %1', "{{filename}}"), 'bundle_ca_object_representations');
?>
								</div>
								<div class='caObjectRepresentationListInfoSubDisplay'>
									<h3><?= _t('Format'); ?></h3> {type};
									<h3><?= _t('Dimensions'); ?></h3> {dimensions}; {num_multifiles}
								</div>
						
								<div class='caObjectRepresentationListInfoSubDisplay'>
									<h3><?= _t('Type'); ?></h3> {rep_type};
<?php
    if(!$dont_show_access) {
?>
									<h3><?= _t('Access'); ?></h3> {access_display};
<?php
    }
    if(!$dont_show_status) {
?>
									<h3><?= _t('Status'); ?></h3> {status_display}
<?php
    }
    if(!$dont_show_transcribe) {
?>
									<h3><?= _t('Transcribable?'); ?></h3> {is_transcribable_display}
									<h3><?= _t('Transcriptions'); ?></h3> {num_transcriptions}
<?php
    }
?>
								</div>
						
								<div id='{fieldNamePrefix}is_primary_indicator_{n}' class='caObjectRepresentationPrimaryIndicator'><?= _t('Will be primary after save'); ?></div>
								<div id='{fieldNamePrefix}change_indicator_{n}' class='caObjectRepresentationChangeIndicator'><?= _t('Changes will be applied when you save'); ?></div>
								<input type="hidden" name="{fieldNamePrefix}is_primary_{n}" id="{fieldNamePrefix}is_primary_{n}" class="{fieldNamePrefix}is_primary" value=""/>
							</div>		
<?php
	if (!$read_only) {
?>
							<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationDetailEditorContainer">
<?php
    if(!$dont_show_preferred_label) {
?>
								<div class="caObjectRepresentationDetailEditorElement"><?= $t_item_label->htmlFormElement('name', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_label_{n}", 'name' => "{fieldNamePrefix}rep_label_{n}", "value" => "{rep_label}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'textAreaTagName' => 'textentry', 'width' => 60)); ?></div>
<?php
    } 
    if(!$dont_show_idno) {
?>
								<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('idno', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}idno_{n}", 'name' => "{fieldNamePrefix}idno_{n}", "value" => "{idno}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'width' => 60)); ?></div>
<?php
    }
    if (!$dont_show_preferred_label || !$dont_show_idno) {
?>
								<br class="clear"/>
<?php
    }
    if (!$dont_show_access) {
?>
								<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if (!$dont_show_status) {
?>
								<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('status', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", "value" => "{status}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if (!$dont_show_transcribe) {
?>
								<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('is_transcribable', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}is_transcribable_{n}", 'name' => "{fieldNamePrefix}is_transcribable_{n}", "value" => "{is_transcribable}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
?>					
								<br class="clear"/>
<?php
	if(is_array($embedded_import_opts) && sizeof($embedded_import_opts)) {
?>
	<div class="caObjectRepresentationDetailEditorElement">
		<div class="formLabel">
<?php
			print _t('Import embedded metadata using').' '.caHTMLSelect('{fieldNamePrefix}importer_id_{n}', $embedded_import_opts);
?>
		</div>
	</div>
	<br class="clear"/>
<?php
	}
?>
							
								<div class="caObjectRepresentationDetailEditorHeading"><?= _t('Update media'); ?></div>
								<table id="{fieldNamePrefix}upload_options{n}">
									<tr>
										<td class='formLabel'><?= caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
										<td class='formLabel'><?= $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'class' => 'uploadInput')); ?></td>
									</tr>
<?php
							if ($allow_fetching_from_urls) {
?>
									<tr>
										<td class='formLabel'><?= caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_url{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'url'), array('checked' => ($default_upload_type == 'url') ? 1 : 0)).' '._t('from URL'); ?></td>
										<td class='formLabel'><?= caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '235px')); ?></td>
									</tr>
<?php
							}
?>
								</table>
							
								<div class='caObjectRepresentationDetailEditorDoneButton'>
<?php 
									print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Done'), '{fieldNamePrefix}detail_editor_save_button{n}', array('onclick' => 'caCloseRepresentationDetailEditor("{n}"); return false;')); 
?>
								</div>	
							
								<script type="text/javascript">
									jQuery(document).ready(function() {
										jQuery("#{fieldNamePrefix}upload_options{n} tr td .uploadInput").prop('disabled', true);
										jQuery("#{fieldNamePrefix}upload_type_upload{n}").click(function() {
											jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', false);
											jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', true);
											jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', true);
										});
										jQuery("#{fieldNamePrefix}upload_type_url{n}").click(function() {
											jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', true);
											jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', false);
											jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', true);
										});
					
										jQuery("input.{fieldNamePrefix}upload_type{n}:checked").click();
									});
								</script>
							</div>
<?php
	}
?>
						</div>
					
						<div class="mediaRight">						
							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}primary_{n}"><a href='#' onclick='caSetRepresentationAsPrimary("{n}"); return false;'><?= caNavIcon(__CA_NAV_ICON_MAKE_PRIMARY__, 1).' '._t('Make primary'); ?></a></span>
							</div>
							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}edit_{n}"><?= urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 1).' '._t('Edit full record'), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit', array('representation_id' => "{representation_id}"), array('id' => "{fieldNamePrefix}edit_button_{n}"))); ?></span>
							</div>
<?php
							if($this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
?>
							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}download_{n}"><?= urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1).' '._t('Download'), '', '*', '*', 'DownloadMedia', array('version' => 'original', 'representation_id' => "{representation_id}", $t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'download' => 1), array('id' => "{fieldNamePrefix}download_button_{n}"))); ?></span>
							</div>
<?php
							}
?>
							<div class="caAnnoEditorLaunchButton annotationTypeClip{annotation_type} caObjectRepresentationListActionButton">
								<span id="{fieldNamePrefix}edit_annotations_{n}"><a href="#" onclick="caAnnoEditor<?= $id_prefix; ?>.showPanel('<?= urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetAnnotationEditor', array('representation_id' => '{representation_id}'))); ?>'); return false;" id="{fieldNamePrefix}edit_annotations_button_{n}"><?= caNavIcon(__CA_NAV_ICON_CLOCK__, 1); ?> <?= _t('Annotations'); ?></a></span>
							</div>
							<div class="caSetImageCenterLaunchButton annotationTypeSetCenter{annotation_type} caObjectRepresentationListActionButton">
								<span id="{fieldNamePrefix}edit_image_center_{n}"><a href="#" onclick="caImageCenterEditor<?= $id_prefix; ?>.showPanel('<?= urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetImageCenterEditor', array('representation_id' => '{representation_id}'))); ?>', caSetImageCenterForSave<?= $id_prefix; ?>, true, {}, {'id': '{n}'}); return false;" id="{fieldNamePrefix}edit_image_center_{n}"><?= caNavIcon(__CA_NAV_ICON_SET_CENTER__, 1); ?> <?= _t('Set center'); ?></a></span>
							</div>
						</div>	
					</div>
				</div>
			
				<br class="clear"/>
				
				<div id="{fieldNamePrefix}media_metadata_container_{n}">	
					<div class="caObjectRepresentationMetadataButton">
						<a href="#" id="{fieldNamePrefix}caObjectRepresentationMetadataButton_{n}" onclick="caToggleDisplayObjectRepresentationMetadata('{fieldNamePrefix}media_metadata_{n}', '{fieldNamePrefix}caObjectRepresentationMetadataButton_{n}'); return false;" class="caObjectRepresentationMetadataButton"><?= caNavIcon(__CA_NAV_ICON_MEDIA_METADATA__, '15px').' '._t('Media metadata'); ?></a>
					</div>
					<div>
						<div id="{fieldNamePrefix}media_metadata_{n}" class="caObjectRepresentationMetadata">
							<div class='caObjectRepresentationSourceDisplay'>{fetched}</div>
							<div class='caObjectRepresentationMD5Display'>{md5}</div>
							<div class='caObjectRepresentationMetadataDisplay'>{metadata}</div>
						</div>
					</div>
				</div>
	
				<br class="clear"/>
			</div>
<?php
			print TooltipManager::getLoadHTML('bundle_ca_object_representations');
?>
			<!-- image center coordinates -->
			<input type="hidden" name="<?= $id_prefix; ?>_center_x_{n}" id="<?= $id_prefix; ?>_center_x_{n}" value="{center_x}"/>
			<input type="hidden" name="<?= $id_prefix; ?>_center_y_{n}" id="<?= $id_prefix; ?>_center_y_{n}" value="{center_y}"/>
		</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>	
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">
<?php
    if ($batch) {
?>
			<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationBatchDetailEditorContainer">
<?php
    if(!$dont_show_preferred_label) {
?>
				<div class="caObjectRepresentationBatchDetailEditorElement formLabel"><?= caHTMLCheckboxInput('{fieldNamePrefix}rep_label_{n}_enabled', ['value' => 1, 'class' => 'caObjectRepresentationBatchDetailEditorElementEnable']); ?><?= $t_item_label->htmlFormElement('name', '^EXTRA^LABEL<br/>^ELEMENT', array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_label_{n}", 'name' => "{fieldNamePrefix}rep_label_{n}", "value" => "{rep_label}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'textAreaTagName' => 'textentry', 'width' => "75")); ?></div>
				<br class="clear"/>
<?php
    }
?>
				<div class="caObjectRepresentationBatchDetailEditorElement formLabel"><?= caHTMLCheckboxInput('{fieldNamePrefix}rep_type_id_{n}_enabled', ['value' => 1, 'class' => 'caObjectRepresentationBatchDetailEditorElementEnable']); ?><?= $t_item->htmlFormElement('type_id', '^EXTRA^LABEL<br/>^ELEMENT', array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_type_id_{n}", 'name' => "{fieldNamePrefix}rep_type_id_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'restrictToTypes' => caGetOption('restrict_to_types', $settings, null))); ?></div>
<?php
    if(!$dont_show_access) {
?>
				<div class="caObjectRepresentationBatchDetailEditorElement formLabel"><?= caHTMLCheckboxInput('{fieldNamePrefix}access_{n}_enabled', ['value' => 1, 'class' => 'caObjectRepresentationBatchDetailEditorElementEnable']); ?><?= $t_item->htmlFormElement('access', '^EXTRA^LABEL<br/>^ELEMENT', array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if(!$dont_show_status) {
?>
				<div class="caObjectRepresentationBatchDetailEditorElement formLabel"><?= caHTMLCheckboxInput('{fieldNamePrefix}status_{n}_enabled', ['value' => 1, 'class' => 'caObjectRepresentationBatchDetailEditorElementEnable']); ?><?= $t_item->htmlFormElement('status', '^EXTRA^LABEL<br/>^ELEMENT', array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if(!$dont_show_transcribe) {
?>
				<br/><div class="caObjectRepresentationBatchDetailEditorElement formLabel"><?= caHTMLCheckboxInput('{fieldNamePrefix}is_transcribable_{n}_enabled', ['value' => 1, 'class' => 'caObjectRepresentationBatchDetailEditorElementEnable']); ?><?= $t_item->htmlFormElement('is_transcribable', '^EXTRA^LABEL<br/>^ELEMENT', array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}is_transcribable_{n}", 'name' => "{fieldNamePrefix}is_transcribable_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
?>		
				<br class="clear"/>
			</div>
			<script type='text/javascript'>
			    jQuery(document).ready(function() {
			        jQuery('input.caObjectRepresentationDetailEditorElement, select.caObjectRepresentationDetailEditorElement, textarea.caObjectRepresentationDetailEditorElement').prop('disabled', true);
			        jQuery('#<?= $id_prefix; ?>').on('click', '.caObjectRepresentationBatchDetailEditorElementEnable', function(e) {
			            var n = jQuery(this).attr('name').replace("_enabled", "");
			            jQuery('#' + n).attr('disabled', !jQuery(this).prop('checked'));
			        }); 
			    });
			</script>
<?php
    } else {
        if(sizeof($rel_types) > 1) {
?>
			<h2><?= ($t_item_rel->hasField('type_id')) ? _t('Add representation with relationship type %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($rel_dir, $left_sub_type_id, $right_sub_type_id, array('name' => '{fieldNamePrefix}rel_type_id_{n}'), $settings)) : _t('Add representation'); ?></h2>
<?php
    } else {
        // Embed type when only a single type is available
        print caHTMLHiddenInput('{fieldNamePrefix}rel_type_id_{n}', ['value' => array_shift(array_keys($rel_types))]);
    }
?>
			<span class="formLabelError">{error}</span>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
			
			<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationNewDetailEditorContainer">
<?php
    if(!$dont_show_preferred_label) {
?>
				<div class="caObjectRepresentationDetailEditorElement"><?= $t_item_label->htmlFormElement('name', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_label_{n}", 'name' => "{fieldNamePrefix}rep_label_{n}", "value" => "{rep_label}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'textAreaTagName' => 'textentry', 'width' => "75")); ?></div>
				<br class="clear"/>
<?php
    }
?>
				<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('type_id', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_type_id_{n}", 'name' => "{fieldNamePrefix}rep_type_id_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'restrictToTypes' => caGetOption('restrict_to_types', $settings, null))); ?></div>
<?php
    if (!$dont_show_access) {
?>
				<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if (!$dont_show_status) {
?>
				<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('status', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
    if (!$dont_show_transcribe) {
?>
				<div class="caObjectRepresentationDetailEditorElement"><?= $t_item->htmlFormElement('is_transcribable', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}is_transcribable_{n}", 'name' => "{fieldNamePrefix}is_transcribable_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
<?php
    }
?>		
				<br class="clear"/>
<?php
	if(is_array($embedded_import_opts) && sizeof($embedded_import_opts)) {
?>
	<div class="caObjectRepresentationDetailEditorElement">
		<div class="formLabel">
<?php
			print _t('Import embedded metadata using').' '.caHTMLSelect('{fieldNamePrefix}importer_id_{n}', $embedded_import_opts);
?>
		</div>
	</div>
	<br class="clear"/>
<?php
	}
?>
			</div>
			
			<table id="{fieldNamePrefix}upload_options{n}">
				<tr>
					<td class='formLabel'><?= caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
					<td class='formLabel'><?= $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'class' => 'uploadInput')); ?></td>
				</tr>
<?php
		if ($allow_fetching_from_urls) {
?>
				<tr>
					<td class='formLabel'><?= caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_url{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'url'), array('checked' => ($default_upload_type == 'url') ? 1 : 0)).' '._t('from URL'); ?></td>
					<td class='formLabel'><?= caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '410px')); ?></td>
				</tr>
<?php
		}
		
		if ((bool)$this->request->getAppConfig()->get($t_subject->tableName().'_allow_relationships_to_existing_representations')) {
?>
				<tr>
					<td class='formLabel'><?= caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_search{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'search'), array('checked' => ($default_upload_type == 'search') ? 1 : 0)).' '._t('using existing'); ?></td>
					<td class='formLabel'>
						<?= caHTMLTextInput('{fieldNamePrefix}autocomplete{n}', array('value' => '{{label}}', 'id' => '{fieldNamePrefix}autocomplete{n}', 'class' => 'lookupBg uploadInput'), array('width' => '425px')); ?>
<?php
	if ($t_item_rel && $t_item_rel->hasField('type_id')) {
?>
						<select name="<?= $id_prefix; ?>_type_id{n}" id="<?= $id_prefix; ?>_type_id{n}" style="display: none; width: 72px;"></select>
<?php
	}
?>
						<input type="hidden" name="<?= $id_prefix; ?>_id{n}" id="<?= $id_prefix; ?>_id{n}" value="{id}"/>
					</td>
				</tr>
<?php
		}
?>
			</table>
			
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#{fieldNamePrefix}upload_options{n} tr td .uploadInput").prop('disabled', true);
					jQuery("#{fieldNamePrefix}upload_type_upload{n}").click(function() {
						jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', false);
						jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', true);
						jQuery('#{fieldNamePrefix}detail_editor_{n}').slideDown(250);
					});
					jQuery("#{fieldNamePrefix}upload_type_url{n}").click(function() {
						jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', false);
						jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', true);
						jQuery('#{fieldNamePrefix}detail_editor_{n}').slideDown(250);
					});
					jQuery("#{fieldNamePrefix}upload_type_search{n}").click(function() {
						jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', false);
						jQuery('#{fieldNamePrefix}detail_editor_{n}').slideUp(250);
					});
					
					jQuery("input.{fieldNamePrefix}upload_type{n}:checked").click();
				});
			</script>
	</div>
<?php
    } 
?>	
</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_object_representations');
?>
	</textarea>
	
	<div class="bundleContainer">
	    <div class='bundleSubLabel'>
<?php
            print caEditorBundleSortControls($this->request, $id_prefix, $t_item->tableName(), $t_instance->tableName(), array_merge($settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction]));

		    if (($rep_count > 1) && $this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
			    print "<div style='float: right'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1)." "._t('Download all'), 'button', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()])."</div>";
            }
?>
		</div>
		<br class="clear"/>
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

<input type="hidden" id="<?= $id_prefix; ?>_ObjectRepresentationBundleList" name="<?= $id_prefix; ?>_ObjectRepresentationBundleList" value=""/>
<?php
	// order element
	TooltipManager::add('.updateIcon', _t("Update Media"));
?>		
<script type="text/javascript">
	function caToggleDisplayObjectRepresentationMetadata(media_metadata_id, media_metadata_button_id) {
		var m = jQuery('#' + media_metadata_id).is(':hidden');
		jQuery('#' + media_metadata_id).slideToggle(300);
		jQuery('#' + media_metadata_button_id + ' img').rotate({ duration:500, angle: m ? 0 : 180, animateTo: m ? 180 : 0 });
	}
	
	function caOpenRepresentationDetailEditor(id) {
		jQuery('#<?= $id_prefix; ?>_detail_editor_' + id).slideDown(250);
		jQuery('#<?= $id_prefix; ?>_rep_info_ro' + id).slideUp(250);
	}
	
	function caCloseRepresentationDetailEditor(id) {
		jQuery('#<?= $id_prefix; ?>_detail_editor_' + id).slideUp(250);
		jQuery('#<?= $id_prefix; ?>_rep_info_ro' + id).slideDown(250);
		jQuery('#<?= $id_prefix; ?>_change_indicator_' + id).show();
	}
	
	function caSetRepresentationAsPrimary(id) {
		jQuery('.<?= $id_prefix; ?>_is_primary').val('');
		jQuery('#<?= $id_prefix; ?>_is_primary_' + id).val('1');
		jQuery('.caObjectRepresentationPrimaryIndicator').hide();
		if (id != <?= (int)$primary_id; ?>) {
			jQuery('#<?= $id_prefix; ?>_is_primary_indicator_' + id).show();
		}
	}
	
	var caAnnoEditor<?= $id_prefix; ?>;
	var caImageCenterEditor<?= $id_prefix; ?>;
	var caRelationBundle<?= $id_prefix; ?>;
	
	jQuery(document).ready(function() {
		caRelationBundle<?= $id_prefix; ?> = caUI.initRelationBundle('#<?= $id_prefix; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			templateValues: ['_display', 'status', 'access', 'access_display', 'is_primary', 'is_primary_display', 'is_transcribable', 'is_transcribable_display', 'num_transcriptions', 'media', 'locale_id', 'icon', 'type', 'dimensions', 'filename', 'num_multifiles', 'metadata', 'rep_type_id', 'type_id', 'typename', 'fetched', 'label', 'rep_label', 'idno', 'id', 'fetched_from','mimetype', 'center_x', 'center_y', 'idno'],

			initialValues: <?= json_encode($initial_values, JSON_INVALID_UTF8_IGNORE); ?>,
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
			showEmptyFormsOnLoad: 1,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			isSortable: <?= !$read_only &&!$batch ? "true" : "false"; ?>,
			listSortOrderID: '<?= $id_prefix; ?>_ObjectRepresentationBundleList',
			defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			relationshipTypes: <?= json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'ObjectRepresentation', 'Get', $lookup_params); ?>',
			autocompleteInputID: '<?= $id_prefix; ?>_autocomplete',
			
			extraParams: { exact: 1 },

			minRepeats: <?= $batch ? 1 : caGetOption('minRelationshipsPerRow', $settings, 0); ?>,
			maxRepeats: <?= $batch ? 1 : caGetOption('maxRelationshipsPerRow', $settings, 65535); ?>,
			
			sortUrl: '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Sort', array('table' => $t_item_rel->tableName())); ?>',
			
			totalValueCount: <?= (int)$rep_count; ?>,

			partialLoadUrl: '<?= caNavUrl($this->request, '*', '*', 'loadBundles', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $settings['placement_id'], 'bundle' => 'ca_object_representations')); ?>',
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
			caAnnoEditor<?= $id_prefix; ?> = caUI.initPanel({ 
				panelID: "caAnnoEditor<?= $id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caAnnoEditor<?= $id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				centerHorizontal: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
			
			caImageCenterEditor<?= $id_prefix; ?> = caUI.initPanel({ 
				panelID: "caImageCenterEditor<?= $id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caImageCenterEditor<?= $id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				centerHorizontal: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		
		jQuery("body").append('<div id="caAnnoEditor<?= $id_prefix; ?>" class="caAnnoEditorPanel"><div id="caAnnoEditor<?= $id_prefix; ?>ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
		jQuery("body").append('<div id="caImageCenterEditor<?= $id_prefix; ?>" class="caAnnoEditorPanel"><div id="caImageCenterEditor<?= $id_prefix; ?>ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
	
		// Hide annotation editor links for non-timebased media
		jQuery(".caAnnoEditorLaunchButton").hide();
		jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
		
		jQuery(".caSetImageCenterLaunchButton").hide();
		jQuery(".annotationTypeSetCenterImage, .annotationTypeSetCenterDocument").show();
	});
	
	function caSetImageCenterForSave<?= $id_prefix; ?>(data) {
		var id = data['id'];
		jQuery("#topNavContainer").show(250);
		jQuery('#<?= $id_prefix; ?>_change_indicator_' + id).show();
		
		var center_x = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('left'))/parseInt(jQuery('#caImageCenterEditorImage').width());
		var center_y = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('top'))/parseInt(jQuery('#caImageCenterEditorImage').height());
		jQuery('#<?= $id_prefix; ?>_center_x_' + id).val(center_x);
		jQuery('#<?= $id_prefix; ?>_center_y_' + id).val(center_y);
	}
</script>
