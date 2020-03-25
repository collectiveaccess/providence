<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 
 	$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');			// object representation
	$t_item_label		= $t_item->getLabelTableInstance();
	$table_num 			= $t_item->tableNum();
	
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');		// object
	$add_label 			= $this->getVar('add_label');
	$settings 			= $this->getVar('settings');
	
	$vs_rel_dir         = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? 'ltol' : 'rtol';
	$vn_left_sub_type_id = ($t_item_rel->getLeftTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
	$vn_right_sub_type_id = ($t_item_rel->getRightTableName() == $t_subject->tableName()) ? $t_subject->get('type_id') : null;
	$rel_types          = $t_item_rel->getRelationshipTypes($vn_left_sub_type_id, $vn_right_sub_type_id);
	

	$read_only			= (isset($settings['readonly']) && $settings['readonly']);
	$is_batch			= $this->getVar('batch');
	
	$lookup_params = [];
	
	$num_per_page = 100;
	
	$initial_values = caSanitizeArray($t_subject->getBundleFormValues($this->getVar('bundle_name'), $this->getVar('placement_code'), $settings, array('start' => 0, 'limit' => $num_per_page, 'request' => $this->request)), ['removeNonCharacterData' => false]);
	$rep_count = sizeof($initial_values);
	
	$errors = $failed_inserts = [];
	
	$primary_id = null;
	foreach($initial_values as $vn_representation_id => $va_rep) {
		if(is_array($va_action_errors = $this->request->getActionErrors('ca_object_representations', $vn_representation_id))) {
			foreach($va_action_errors as $o_error) {
				$errors[$vn_representation_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
		}
		if ($va_rep['is_primary']) {
			$primary_id = $va_rep['representation_id'];
		}
	}
	
	$use_classic_interface = (($settings['uiStyle'] === 'CLASSIC') || $is_batch);		// use classic UI for batch always
	$bundles_to_edit = caGetOption('showBundlesForEditing', $settings, []);
 	$bundles_to_edit_proc = array_map(function($v) { return array_pop(explode('.', $v)); }, $bundles_to_edit);
 	
	if ($use_classic_interface) {
		print $this->render('ca_object_representations_classic.php');
		return;
	}
 
 
 ?>
 <div id="<?php print "{$id_prefix}{$table_num}_rel"; ?>">
 	<div class="bundleContainer">
	    <div class='bundleSubLabel'> 
<?php
	print caEditorBundleSortControls($this->request, $id_prefix, $t_item->tableName(), $settings);
?>
	    </div>
		<div class="caItemList">
			
		</div>
<?php
	if (!$read_only) {
?>
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $add_label ? $add_label : _t("Add representation")." &rsaquo;"; ?></a></div>
<?php
	}
?>
 	</div>
	<input type="hidden" id="<?php print $id_prefix; ?>_ObjectRepresentationBundleList" name="<?php print $id_prefix; ?>_ObjectRepresentationBundleList" value=""/>

 
 
 <?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
<?php if (!$read_only) { ?>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
<?php } ?>	
			<div style="width: 680px;">
				<div style="float: left;">
					<div class="caObjectRepresentationListItemImageThumb"><a href="#" onclick="caMediaPanel.showPanel('<?php print urldecode(caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $t_subject->getPrimaryKey(), 'representation_id' => '{representation_id}'))); ?>'); return false;">{icon}</a></div>
				</div>
				<div style="float: right; width: 550px;">
					<div style="float: left; width: 80%;">
						<div id='{fieldNamePrefix}rep_info_ro{n}'>
							<div class='caObjectRepresentationListInfo'>
 <?php
	foreach($bundles_to_edit_proc as $f) {
		if($t_item->hasField($f)) { // intrinsic
			print "<div class='formLabel' style='float: left; margin: 0 5px 0 5px;'>".$t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '200px', 'value' => '{'.$f.'}'])."</div>\n";
		} elseif($t_item->hasElement($f)) {
			$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '200px', 'elementsOnly' => true, 'value' => '{{'.$f.'}}']);
			$form_element = array_shift(array_shift($form_element_info['elements']));
			print "<div class='formLabel' style='float: left; margin: 0 5px 0 5px;'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".$form_element."</div>\n"; 
		}
	}
?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br class="clear"/>
		</div>
	</textarea>
		
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div id="<?php print $id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
<?php if (!$read_only) { ?>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
<?php } ?>	

<?php
    if(sizeof($rel_types) > 1) {
?>
			<h2><?php print ($t_item_rel->hasField('type_id')) ? _t('Add representation with relationship type %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($vs_rel_dir, $vn_left_sub_type_id, $vn_right_sub_type_id, array('name' => '{fieldNamePrefix}rel__type_id_{n}'), $va_settings)) : _t('Add representation'); ?></h2>
<?php
    } else {
        // Embed type when only a single type is available
        print caHTMLHiddenInput('{fieldNamePrefix}rel_type_id_{n}', ['value' => array_shift(array_keys($rel_types))]);
    }
?>
			
			<div id="<?php print $id_prefix; ?>objectRepresentationImporterUploadArea{n}" class="objectRepresentationImporterUploadArea">
				<div id="<?php print $id_prefix; ?>objectRepresentationImporterUploadAreaMessage{n}" class="objectRepresentationImporterUploadAreaMessage"> </div>
			</div>
			<div id="<?php print $id_prefix; ?>objectRepresentationMediaUploadCount{n}" data-count="0"></div>
			<div id="<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}" style="display: none;">
				<div class="objectRepresentationMediaUploadStatus"><span id="<?php print $id_prefix; ?>batchProcessingTableStatus{n}" > </span></div>
				<div id="<?php print $id_prefix; ?>progressbar{n}"></div>
			</div>
			
			<div>
<?php
	foreach($bundles_to_edit_proc as $f) {
		if($t_item->hasField($f)) {
			// instrinsic
			print "<div class='formLabel' style='float: left; margin: 0 5px 0 5px;'>".$t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '200px'])."</div>\n";
		} elseif($t_item->hasElement($f)) {
			$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '200px', 'elementsOnly' => true]);
			$form_element = array_shift(array_shift($form_element_info['elements']));
			print "<div class='formLabel' style='float: left; margin: 0 5px 0 5px;'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".$form_element."</div>\n"; 
		}
	}
?>
			</div>
			
			<input type="hidden" id="<?php print $id_prefix; ?>_objectRepresentationMediaRefs_{n}" name="<?php print $id_prefix; ?>_objectRepresentationMediaRefs_{n}"/>
			<br class="clear"/>
		</div>
		
		<script>
			var upload_message = <?php print json_encode(caNavIcon(__CA_NAV_ICON_ADD__, '30px').'<br/>'._t("Add media")); ?>;
			jQuery('#<?php print $id_prefix; ?>objectRepresentationImporterUploadAreaMessage{n}').html(upload_message);
			jQuery('#<?php print $id_prefix; ?>progressbar{n}').progressbar({ value: 0 });
			jQuery('#<?php print $id_prefix; ?>objectRepresentationImporterUploadArea{n}').fileupload({
				dataType: 'json',
				url: '<?php print caNavUrl($this->request, '*', '*', 'UploadFiles'); ?>',
				dropZone: jQuery('#<?php print $id_prefix; ?>objectRepresentationImporterUploadArea{n}'),
				singleFileUploads: false,
				done: function (e, data) {
					if (data.result.error) {
						jQuery("#<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}").show(250);
						jQuery("#<?php print $id_prefix; ?>batchProcessingTableStatus{n}").html(data.result.error);
						setTimeout(function() {
							jQuery("#<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}").hide(250);
						}, 3000);
					} else {
						jQuery("#<?php print $id_prefix; ?>batchProcessingTableStatus{n}").html(data.result.msg ? data.result.msg : "");
						setTimeout(function() {
							jQuery("#<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}").hide(250);
							jQuery("#<?php print $id_prefix; ?>objectRepresentationImporterUploadArea{n}").show(150);
						}, 1500);
					}
					
					var existing_files = jQuery("#<?php print $id_prefix; ?>_objectRepresentationMediaRefs_{n}").val();
					var files = (existing_files && existing_files.length > 0) ? existing_files.split(";") : [];
					files = files.concat(data.result.files);
					
					jQuery("#<?php print $id_prefix; ?>objectRepresentationImporterUploadAreaMessage{n}").html(files.length + " uploaded"); //.html(files.length + " uploaded")
					jQuery("#<?php print $id_prefix; ?>_objectRepresentationMediaRefs_{n}").val(files.join(";"));
					jQuery("#<?php print $id_prefix; ?>objectRepresentationMediaUploadCount{n}").data('count', files.length);
				},
				progressall: function (e, data) {
					jQuery("#<?php print $id_prefix; ?>objectRepresentationImporterUploadArea{n}").hide(150);
					if (jQuery("#<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}").css('display') == 'none') {
						jQuery("#<?php print $id_prefix; ?>batchProcessingTableProgressGroup{n}").show(250);
					}
					var progress = parseInt(data.loaded / data.total * 100, 10);
					jQuery('#<?php print $id_prefix; ?>progressbar{n}').progressbar("value", progress);
		
					var msg = "<?php print _t("Progress: "); ?>%1";
					jQuery("#<?php print $id_prefix; ?>batchProcessingTableStatus{n}").html(msg.replace("%1", caUI.utils.formatFilesize(data.loaded) + " (" + progress + "%)"));
				
				}
			});		
		</script>
	</textarea>
</div>

 <script type="text/javascript">
	var caRelationBundle<?php print $id_prefix; ?>;
 	jQuery(document).ready(function() {
		caRelationBundle<?php print $id_prefix; ?> = caUI.initRelationBundle('#<?php print "{$id_prefix}{$table_num}_rel"; ?>', {
			fieldNamePrefix: '<?php print $id_prefix; ?>_',
			templateValues: ['_display', 'status', 'access', 'access_display', 'is_primary', 'is_primary_display', 'is_transcribable', 'is_transcribable_display', 'num_transcriptions', 'media', 'locale_id', 'icon', 'type', 'dimensions', 'filename', 'num_multifiles', 'metadata', 'rep_type_id', 'type_id', 'typename', 'fetched', 'label', 'rep_label', 'idno', 'id', 'fetched_from','mimetype', 'center_x', 'center_y', 'idno' <?php print (is_array($bundles_to_edit_proc) && sizeof($bundles_to_edit_proc)) ? ", ".join(", ", array_map(function($v) { return "'{$v}'"; }, $bundles_to_edit_proc)) : ''; ?>],
			initialValues: <?php print json_encode($initial_values); ?>,
			initialValueOrder: <?php print json_encode(array_keys($initial_values)); ?>,
			errors: <?php print json_encode($errors); ?>,
			forceNewValues: <?php print json_encode($failed_inserts); ?>,
			itemID: '<?php print $id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			itemClassName: 'labelInfo',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showOnNewIDList: ['<?php print $id_prefix; ?>_media_'],
			hideOnNewIDList: ['<?php print $id_prefix; ?>_edit_','<?php print $id_prefix; ?>_download_', '<?php print $id_prefix; ?>_media_metadata_container_', '<?php print $id_prefix; ?>_edit_annotations_', '<?php print $id_prefix; ?>_edit_image_center_'],
			enableOnNewIDList: [],
			showEmptyFormsOnLoad: 1,
			readonly: <?php print $read_only ? "true" : "false"; ?>,
			isSortable: true, //<?php print !$read_only && !$batch ? "true" : "false"; ?>,
			listSortOrderID: '<?php print $id_prefix; ?>_ObjectRepresentationBundleList',
			defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'ObjectRepresentation', 'Get', $lookup_params); ?>',
			autocompleteInputID: '<?php print $id_prefix; ?>_autocomplete',
			
			extraParams: { exact: 1 },
			
			minRepeats: <?php print $batch ? 1 : caGetOption('minRelationshipsPerRow', $settings, 0); ?>,
			maxRepeats: <?php print $batch ? 1 : caGetOption('maxRelationshipsPerRow', $settings, 65535); ?>,
			
			sortUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Sort', array('table' => $t_item_rel->tableName())); ?>',
			
			totalValueCount: <?php print (int)$rep_count; ?>,
			//partialLoadUrl: '<?php print caNavUrl($this->request, '*', '*', 'loadBundles', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $settings['placement_id'], 'bundle' => 'ca_object_representations')); ?>',
			loadSize: <?php print $num_per_page; ?>,
			partialLoadMessage: '<?php print addslashes(_t('Load next %num of %total')); ?>',
			partialLoadIndicator: '<?php print addslashes(caBusyIndicatorIcon($this->request)); ?>',
			onPartialLoad: function(d) {				
				// Hide annotation editor links for non-timebased media
				//jQuery(".caAnnoEditorLaunchButton").hide();
				//jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
			}
		
		});
	});
</script>
