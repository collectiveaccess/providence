<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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

	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');			// object representation
	$t_item_label		= $t_item->getLabelTableInstance();
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');		// object
	$vs_add_label 		= $this->getVar('add_label');
	$va_settings 		= $this->getVar('settings');

	$vb_read_only		=	(isset($va_settings['readonly']) && $va_settings['readonly']);
	$vb_batch			=	$this->getVar('batch');

	// don't allow editing if user doesn't have access to any of the representation types
	if(sizeof(caGetTypeListForUser('ca_object_representations', array('access' => __CA_BUNDLE_ACCESS_EDIT__)))  < 1) {
		$vb_read_only = true;
	}
	
	if (!in_array($vs_default_upload_type = $this->getVar('defaultRepresentationUploadType'), array('upload', 'url', 'search'))) {
		$vs_default_upload_type = 'upload';
	}
	
	$vb_allow_fetching_from_urls = $this->request->getAppConfig()->get('allow_fetching_of_media_from_remote_urls');
	
	// Paging
	$vn_start = 0;
	$vn_num_per_page = 20;
	$vn_primary_id = 0;
	
	// generate list of inital form values; the bundle Javascript call will
	// use the template to generate the initial form
	$va_rep_type_list = $t_item->getTypeList();
	$va_errors = array();
	
	$vn_rep_count = $t_subject->getRepresentationCount($va_settings);
	$va_initial_values = caSanitizeArray($t_subject->getBundleFormValues($this->getVar('bundle_name'), $this->getVar('placement_code'), $va_settings, array('start' => 0, 'limit' => $vn_num_per_page, 'request' => $this->request)), ['removeNonCharacterData' => false]);

	foreach($va_initial_values as $vn_representation_id => $va_rep) {
		if(is_array($va_action_errors = $this->request->getActionErrors('ca_object_representations', $vn_representation_id))) {
			foreach($va_action_errors as $o_error) {
				$va_errors[$vn_representation_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
		}
		if ($va_rep['is_primary']) {
			$vn_primary_id = $va_rep['representation_id'];
		}
	}
	
	$va_failed_inserts = array();
	foreach($this->request->getActionErrorSubSources('ca_object_representations') as $vs_error_subsource) {
		if (substr($vs_error_subsource, 0, 4) === 'new_') {
			$va_action_errors = $this->request->getActionErrors('ca_object_representations', $vs_error_subsource);
			foreach($va_action_errors as $o_error) {
				$va_failed_inserts[] = array('icon' => '', '_errors' => array(array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber())));
			}
		}
	}
	
	if ($vb_batch) {
		print caBatchEditorRelationshipModeControl($t_item, $vs_id_prefix);
	} else {
		print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings, (sizeof($va_initial_values) > 0), _t("Number of representations: %1", sizeof($va_initial_values)));
	}
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings);
?>
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>" <?php print $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">

			<span class="formLabelError">{error}</span>
<?php 
	if (!$vb_read_only) {
?>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
<?php
	}
?>	
			<div style="width: 680px;">
				<div style="float: left;">
					<div class="caObjectRepresentationListItemImageThumb"><a href="#" onclick="caMediaPanel.showPanel('<?php print urldecode(caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $t_subject->getPrimaryKey(), 'representation_id' => '{n}'))); ?>'); return false;">{icon}</a></div>
				</div>
				<div style="float: right; width: 550px;">
					<div style="float: left; width: 80%;">
						<div id='{fieldNamePrefix}rep_info_ro{n}'>
							<div class='caObjectRepresentationListInfo'>
								<a title="{filename}">{rep_label}</a>
<?php
	if (!$vb_read_only) {
?>
								<span id="{fieldNamePrefix}change_{n}" class="caObjectRepresentationListInfoSubDisplayUpdate"><a href='#' class='updateIcon' onclick="caOpenRepresentationDetailEditor('{n}'); return false;"><?php print caNavIcon(__CA_NAV_ICON_UPDATE__, 1).'</a>'; ?></span>
<?php
	}
?>
								<span class='caObjectRepresentationPrimaryDisplay'>{is_primary_display}</span>
							</div>
											
							<div class='caObjectRepresentationListInfoSubDisplay'>
								{_display}
								<em>{idno}</em><br/>
								<h3><?php print _t('File name'); ?></h3> <span class="caObjectRepresentationListInfoSubDisplayFilename" id="{fieldNamePrefix}filename_display_{n}">{filename}</span>
<?php
	TooltipManager::add("#{$vs_id_prefix}_filename_display_{n}", _t('File name: %1', "{{filename}}"), 'bundle_ca_object_representations');
?>
								</div>
								<div class='caObjectRepresentationListInfoSubDisplay'>
									<h3><?php print _t('Format'); ?></h3> {type};
									<h3><?php print _t('Dimensions'); ?></h3> {dimensions}; {num_multifiles}
								</div>
						
								<div class='caObjectRepresentationListInfoSubDisplay'>
									<h3><?php print _t('Type'); ?></h3> {rep_type};
									<h3><?php print _t('Access'); ?></h3> {access_display};
									<h3><?php print _t('Status'); ?></h3> {status_display}
								</div>
						
								<div id='{fieldNamePrefix}is_primary_indicator_{n}' class='caObjectRepresentationPrimaryIndicator'><?php print _t('Will be primary after save'); ?></div>
								<div id='{fieldNamePrefix}change_indicator_{n}' class='caObjectRepresentationChangeIndicator'><?php print _t('Changes will be applied when you save'); ?></div>
								<input type="hidden" name="{fieldNamePrefix}is_primary_{n}" id="{fieldNamePrefix}is_primary_{n}" class="{fieldNamePrefix}is_primary" value=""/>
							</div>		
<?php
	if (!$vb_read_only) {
?>
							<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationDetailEditorContainer">
								<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item_label->htmlFormElement('name', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_label_{n}", 'name' => "{fieldNamePrefix}rep_label_{n}", "value" => "{rep_label}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'textAreaTagName' => 'textentry', 'width' => 60)); ?></div>
								<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('idno', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}idno_{n}", 'name' => "{fieldNamePrefix}idno_{n}", "value" => "{idno}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'width' => 60)); ?></div>
								<br class="clear"/>
								<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
								<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('status', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", "value" => "{status}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
						
								<br class="clear"/>
							
								<div class="caObjectRepresentationDetailEditorHeading"><?php print _t('Update media'); ?></div>
								<table id="{fieldNamePrefix}upload_options{n}">
									<tr>
										<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($vs_default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
										<td class='formLabel'><?php print $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'class' => 'uploadInput')); ?></td>
									</tr>
<?php
							if ($vb_allow_fetching_from_urls) {
?>
									<tr>
										<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_url{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'url'), array('checked' => ($vs_default_upload_type == 'url') ? 1 : 0)).' '._t('from URL'); ?></td>
										<td class='formLabel'><?php print caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '235px')); ?></td>
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
								<span id="{fieldNamePrefix}primary_{n}"><a href='#' onclick='caSetRepresentationAsPrimary("{n}"); return false;'><?php print caNavIcon(__CA_NAV_ICON_MAKE_PRIMARY__, 1).' '._t('Make primary'); ?></a></span>
							</div>
							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}edit_{n}"><?php print urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_EDIT__, 1).' '._t('Edit full record'), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit', array('representation_id' => "{n}"), array('id' => "{fieldNamePrefix}edit_button_{n}"))); ?></span>
							</div>
<?php
							if($this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
?>
							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}download_{n}"><?php print urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1).' '._t('Download'), '', '*', '*', 'DownloadMedia', array('version' => 'original', 'representation_id' => "{n}", $t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'download' => 1), array('id' => "{fieldNamePrefix}download_button_{n}"))); ?></span>
							</div>
<?php
							}
?>
							<div class="caAnnoEditorLaunchButton annotationTypeClip{annotation_type} caObjectRepresentationListActionButton">
								<span id="{fieldNamePrefix}edit_annotations_{n}"><a href="#" onclick="caAnnoEditor<?php print $vs_id_prefix; ?>.showPanel('<?php print urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetAnnotationEditor', array('representation_id' => '{n}'))); ?>'); return false;" id="{fieldNamePrefix}edit_annotations_button_{n}"><?php print caNavIcon(__CA_NAV_ICON_CLOCK__, 1); ?> <?php print _t('Annotations'); ?></a></span>
							</div>
							<div class="caSetImageCenterLaunchButton annotationTypeSetCenter{annotation_type} caObjectRepresentationListActionButton">
								<span id="{fieldNamePrefix}edit_image_center_{n}"><a href="#" onclick="caImageCenterEditor<?php print $vs_id_prefix; ?>.showPanel('<?php print urldecode(caNavUrl($this->request, 'editor/object_representations', 'ObjectRepresentationEditor', 'GetImageCenterEditor', array('representation_id' => '{n}'))); ?>', caSetImageCenterForSave<?php print $vs_id_prefix; ?>, true, {}, {'id': '{n}'}); return false;" id="{fieldNamePrefix}edit_image_center_{n}"><?php print caNavIcon(__CA_NAV_ICON_SET_CENTER__, 1); ?> <?php print _t('Set center'); ?></a></span>
							</div>
						</div>	
					</div>
				</div>
			
				<br class="clear"/>
				
				<div id="{fieldNamePrefix}media_replication_container_{n}" style="display: none;">
					<div class="caRepresentationMediaReplicationButton">
						<a href="#" id="{fieldNamePrefix}caRepresentationMediaReplicationButton_{n}" onclick="caToggleDisplayMediaReplication('{fieldNamePrefix}media_replication{n}', '{fieldNamePrefix}caRepresentationMediaReplicationButton_{n}', '{n}'); return false;" class="caRepresentationMediaReplicationButton"><?php print caNavIcon(__CA_NAV_ICON_MEDIA_METADATA__, '15px')." "._t('Replication'); ?></a>
					</div>
					<div>
						<div id="{fieldNamePrefix}media_replication{n}" class="caRepresentationMediaReplication">
							<?php print caBusyIndicatorIcon($this->request).' '._t('Loading'); ?>
						</div>
					</div>
				</div>
			
				<div id="{fieldNamePrefix}media_metadata_container_{n}">	
					<div class="caObjectRepresentationMetadataButton">
						<a href="#" id="{fieldNamePrefix}caObjectRepresentationMetadataButton_{n}" onclick="caToggleDisplayObjectRepresentationMetadata('{fieldNamePrefix}media_metadata_{n}', '{fieldNamePrefix}caObjectRepresentationMetadataButton_{n}'); return false;" class="caObjectRepresentationMetadataButton"><?php print caNavIcon(__CA_NAV_ICON_MEDIA_METADATA__, '15px').' '._t('Media metadata'); ?></a>
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
			<script type="text/javascript">
				jQuery(document).ready(function() {
					if (caMediaReplicationMimeTypes.indexOf('{mimetype}') !== -1) {	// is replication configured for this media?
						jQuery("#{fieldNamePrefix}media_replication_container_{n}").css("display", "block");
					}
				});
			</script>
<?php
			print TooltipManager::getLoadHTML('bundle_ca_object_representations');
?>
			<!-- image center coordinates -->
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_center_x_{n}" id="<?php print $vs_id_prefix; ?>_center_x_{n}" value="{center_x}"/>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_center_y_{n}" id="<?php print $vs_id_prefix; ?>_center_y_{n}" value="{center_y}"/>
		</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
	$vs_rel_dir = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? 'ltol' : 'rtol';
	$vn_left_sub_type_id = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
	$vn_right_sub_type_id = ($t_item_rel->getRightTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
?>
	<textarea class='caNewItemTemplate' style='display: none;'>	
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">

			<h2><?php print ($t_item_rel->hasField('type_id')) ? _t('Add representation with relationship type %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($vs_rel_dir, $vn_left_sub_type_id, $vn_right_sub_type_id, array('name' => '{fieldNamePrefix}type_id_{n}'), $va_settings)) : _t('Add representation'); ?></h2>

			<span class="formLabelError">{error}</span>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
			
			<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationNewDetailEditorContainer">
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item_label->htmlFormElement('name', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_label_{n}", 'name' => "{fieldNamePrefix}rep_label_{n}", "value" => "{rep_label}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'textAreaTagName' => 'textentry', 'width' => "75")); ?></div>
				<br class="clear"/>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('type_id', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}rep_type_id_{n}", 'name' => "{fieldNamePrefix}rep_type_id_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('status', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}status_{n}", 'name' => "{fieldNamePrefix}status_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations')); ?></div>
		
				<br class="clear"/>
			</div>
			
			<table id="{fieldNamePrefix}upload_options{n}">
				<tr>
					<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($vs_default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
					<td class='formLabel'><?php print $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations', 'class' => 'uploadInput')); ?></td>
				</tr>
<?php
		if ($vb_allow_fetching_from_urls) {
?>
				<tr>
					<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_url{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'url'), array('checked' => ($vs_default_upload_type == 'url') ? 1 : 0)).' '._t('from URL'); ?></td>
					<td class='formLabel'><?php print caHTMLTextInput("{fieldNamePrefix}media_url_{n}", array('id' => '{fieldNamePrefix}media_url_{n}', 'class' => 'urlBg uploadInput'), array('width' => '410px')); ?></td>
				</tr>
<?php
		}
		
		if ((bool)$this->request->getAppConfig()->get($t_subject->tableName().'_allow_relationships_to_existing_representations')) {
?>
				<tr>
					<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_search{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'search'), array('checked' => ($vs_default_upload_type == 'search') ? 1 : 0)).' '._t('using existing'); ?></td>
					<td class='formLabel'>
						<?php print caHTMLTextInput('{fieldNamePrefix}autocomplete{n}', array('value' => '{{label}}', 'id' => '{fieldNamePrefix}autocomplete{n}', 'class' => 'lookupBg uploadInput'), array('width' => '425px')); ?>
<?php
	if ($t_item_rel && $t_item_rel->hasField('type_id')) {
?>
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none; width: 72px;"></select>
<?php
	}
?>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
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
			
</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_object_representations');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="downloadAll">
<?php
		if ($vn_rep_count > 1) {
			print caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1)." "._t('Download all'), 'button', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()]);
		}
?>
		</div>
		<br class="clear"/>
		<div class="caItemList">
			
		</div>
<?php 
	if (!$vb_read_only) {
?>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add representation")." &rsaquo;"; ?></a></div>
<?php
	}
?>
	</div>
</div>

<input type="hidden" id="<?php print $vs_id_prefix; ?>_ObjectRepresentationBundleList" name="<?php print $vs_id_prefix; ?>_ObjectRepresentationBundleList" value=""/>
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
	
	function caToggleDisplayMediaReplication(media_replication_id, media_replication_button_id, n) {
		var m = jQuery('#' + media_replication_id).is(':hidden');
		jQuery('#' + media_replication_button_id + ' img').rotate({ duration:500, angle: m ? 0 : 180, animateTo: m ? 180 : 0 });
		
		jQuery('#' + media_replication_id).slideToggle(300, function() { 
			if(jQuery('#' + media_replication_id).css('display') == 'block') {
				jQuery('#' + media_replication_id).load('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'MediaReplicationControls', array('representation_id' => '')); ?>' + n); 
			} 
		});
	}
	
	function caOpenRepresentationDetailEditor(id) {
		jQuery('#<?php print $vs_id_prefix; ?>_detail_editor_' + id).slideDown(250);
		jQuery('#<?php print $vs_id_prefix; ?>_rep_info_ro' + id).slideUp(250);
	}
	
	function caCloseRepresentationDetailEditor(id) {
		jQuery('#<?php print $vs_id_prefix; ?>_detail_editor_' + id).slideUp(250);
		jQuery('#<?php print $vs_id_prefix; ?>_rep_info_ro' + id).slideDown(250);
		jQuery('#<?php print $vs_id_prefix; ?>_change_indicator_' + id).show();
	}
	
	function caSetRepresentationAsPrimary(id) {
		jQuery('.<?php print $vs_id_prefix; ?>_is_primary').val('');
		jQuery('#<?php print $vs_id_prefix; ?>_is_primary_' + id).val('1');
		jQuery('.caObjectRepresentationPrimaryIndicator').hide();
		if (id != <?php print (int)$vn_primary_id; ?>) {
			jQuery('#<?php print $vs_id_prefix; ?>_is_primary_indicator_' + id).show();
		}
	}
	
	var caMediaReplicationMimeTypes = <?php print json_encode(MediaReplicator::getMediaReplicationMimeTypes()); ?>;
	
	var caAnnoEditor<?php print $vs_id_prefix; ?>;
	var caImageCenterEditor<?php print $vs_id_prefix; ?>;
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	
	jQuery(document).ready(function() {
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['_display', 'status', 'access', 'access_display', 'is_primary', 'is_primary_display', 'media', 'locale_id', 'icon', 'type', 'dimensions', 'filename', 'num_multifiles', 'metadata', 'rep_type_id', 'type_id', 'typename', 'fetched', 'label', 'rep_label', 'idno', 'id', 'fetched_from','mimetype', 'center_x', 'center_y', 'idno'],
			initialValues: <?php print json_encode($va_initial_values); ?>,
			initialValueOrder: <?php print json_encode(array_keys($va_initial_values)); ?>,
			errors: <?php print json_encode($va_errors); ?>,
			forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			itemClassName: 'labelInfo',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showOnNewIDList: ['<?php print $vs_id_prefix; ?>_media_'],
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_','<?php print $vs_id_prefix; ?>_download_', '<?php print $vs_id_prefix; ?>_media_metadata_container_', '<?php print $vs_id_prefix; ?>_edit_annotations_', '<?php print $vs_id_prefix; ?>_edit_image_center_'],
			enableOnNewIDList: [],
			showEmptyFormsOnLoad: 1,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print !$vb_read_only ? "true" : "false"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>_ObjectRepresentationBundleList',
			defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'ObjectRepresentation', 'Get', $va_lookup_params); ?>',
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
			
			extraParams: { exact: 1 },
			
			minRepeats: <?php print caGetOption('minRelationshipsPerRow', $va_settings, 0); ?>,
			maxRepeats: <?php print caGetOption('maxRelationshipsPerRow', $va_settings, 65535); ?>,
			
			totalValueCount: <?php print (int)$vn_rep_count; ?>,
			partialLoadUrl: '<?php print caNavUrl($this->request, '*', '*', 'loadBundles', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $va_settings['placement_id'], 'bundle' => 'ca_object_representations')); ?>',
			loadSize: <?php print $vn_num_per_page; ?>,
			partialLoadMessage: '<?php print addslashes(_t('Load next %')); ?>',
			partialLoadIndicator: '<?php print addslashes(caBusyIndicatorIcon($this->request)); ?>',
			onPartialLoad: function(d) {				
				// Hide annotation editor links for non-timebased media
				jQuery(".caAnnoEditorLaunchButton").hide();
				jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
			}
		
		});
		if (caUI.initPanel) {
			caAnnoEditor<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caAnnoEditor<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caAnnoEditor<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
			
			caImageCenterEditor<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caImageCenterEditor<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caImageCenterEditor<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
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
		
		jQuery("body").append('<div id="caAnnoEditor<?php print $vs_id_prefix; ?>" class="caAnnoEditorPanel"><div id="caAnnoEditor<?php print $vs_id_prefix; ?>ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
		jQuery("body").append('<div id="caImageCenterEditor<?php print $vs_id_prefix; ?>" class="caAnnoEditorPanel"><div id="caImageCenterEditor<?php print $vs_id_prefix; ?>ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
	
		// Hide annotation editor links for non-timebased media
		jQuery(".caAnnoEditorLaunchButton").hide();
		jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
		
		jQuery(".caSetImageCenterLaunchButton").hide();
		jQuery(".annotationTypeSetCenterImage").show();
	});
	
	function caSetImageCenterForSave<?php print $vs_id_prefix; ?>(data) {
		var id = data['id'];
		jQuery("#topNavContainer").show(250);
		jQuery('#<?php print $vs_id_prefix; ?>_change_indicator_' + id).show();
		
		var center_x = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('left'))/parseInt(jQuery('#caImageCenterEditorImage').width());
		var center_y = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('top'))/parseInt(jQuery('#caImageCenterEditorImage').height());
		jQuery('#<?php print $vs_id_prefix; ?>_center_x_' + id).val(center_x);
		jQuery('#<?php print $vs_id_prefix; ?>_center_y_' + id).val(center_y);
	}
</script>