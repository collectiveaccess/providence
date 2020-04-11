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
	$bundles_to_edit = caGetOption('showBundlesForEditing', $settings, [], ['castTo' => 'array']);
 	$bundles_to_edit_proc = array_map(function($v) { return array_pop(explode('.', $v)); }, $bundles_to_edit);
 	
	if ($use_classic_interface) {
		print $this->render('ca_object_representations_classic.php');
		return;
	}
 ?>
 <div id="<?php print "{$id_prefix}{$table_num}_rel"; ?>">
 	<div class="bundleContainer"> </div>
 	
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
			<div class="mediaUploadContainer">
				<div style="float: left;">
					<div class="caObjectRepresentationListItemImageThumb"><a href="#" onclick="caMediaPanel.showPanel('<?php print urldecode(caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $t_subject->getPrimaryKey(), 'representation_id' => '{representation_id}'))); ?>'); return false;">{icon}</a></div>
				</div>
				<div class="mediaUploadInfoArea">
					<div style="float: left; width: 100%;">
						<div class='caObjectRepresentationListInfo'>
 <?php
						foreach($bundles_to_edit_proc as $f) {
							if($t_item->hasField($f)) { // intrinsic
								print "<div class='formLabel'>".$t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '225px', 'value' => '{'.$f.'}', 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."</div>\n";
							} elseif($t_item->hasElement($f)) {
								$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '225px', 'height' => null, 'elementsOnly' => true, 'value' => '{{'.$f.'}}', 'textAreaTagName' => 'textentry']);
								print "<div class='formLabel''>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".array_shift(array_shift($form_element_info['elements']))."</div>\n"; 
							}
						}
?>
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
		<h2><?php print ($t_item_rel->hasField('type_id')) ? _t('Add representation with relationship type %1', $t_item_rel->getRelationshipTypesAsHTMLSelect($vs_rel_dir, $vn_left_sub_type_id, $vn_right_sub_type_id, array('name' => '{fieldNamePrefix}rel__type_id_{n}'), $settings)) : _t('Add representation'); ?></h2>
<?php
    } else {
        // Embed type when only a single type is available
        print caHTMLHiddenInput('{fieldNamePrefix}rel_type_id_{n}', ['value' => array_shift(array_keys($rel_types))]);
    }
?>
			<div class="mediaUploadContainer">
				<div style="float: left;">
					<div id="<?php print $id_prefix; ?>UploadArea{n}" class="mediaUploadArea">
						<input type="file" style="display: none;" id="<?php print $id_prefix; ?>UploadFileControl{n}" multiple/>
						<div id="<?php print $id_prefix; ?>UploadAreaMessage{n}" class="mediaUploadAreaMessage"> </div>
					</div>
				</div>
				<div class="mediaUploadInfoArea">
<?php
				foreach($bundles_to_edit_proc as $f) {
					if($t_item->hasField($f)) { // intrinsic
						print "<div class='formLabel'>".$t_item->htmlFormElement($f, null, ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'width' => '500px', 'textAreaTagName' => 'textentry', 'no_tooltips' => true])."</div>\n";
					} elseif($t_item->hasElement($f)) {
						$form_element_info = $t_item->htmlFormElementForSimpleForm($this->request, "ca_object_representations.{$f}", ['id' => "{$id_prefix}_{$f}_{n}", 'name' => "{$id_prefix}_{$f}_{n}", 'removeTemplateNumberPlaceholders' => false, 'width' => '500px', 'height' => null, 'elementsOnly' => true, 'textAreaTagName' => 'textentry']);
						print "<div class='formLabel'>".$t_item->getDisplayLabel("ca_object_representations.{$f}")."<br/>".array_shift(array_shift($form_element_info['elements']))."</div>\n"; 
					}
				}
?>
				</div>
			</div>
			<input type="hidden" id="<?php print $id_prefix; ?>MediaRefs{n}" name="<?php print $id_prefix; ?>_mediarefs{n}"/>
			<br class="clear"/>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				caUI.initMediaUploadManager({
					fieldNamePrefix: '<?= $id_prefix; ?>',
					uploadURL:  '<?= caNavUrl($this->request, '*', '*', 'UploadFiles'); ?>',
					index: '{n}',
					uploadAreaMessage: <?= json_encode(caNavIcon(__CA_NAV_ICON_ADD__, '30px').'<br/>'._t("Add media")); ?>,
					uploadAreaIndicator: <?= json_encode(caNavIcon(__CA_NAV_ICON_SPINNER__, '30px').'<br/>'._t("Uploading... %percent")); ?>,
				});	
			});
		</script>
	</textarea>
	
	<div class="bundleContainer">
	    <div class='bundleSubLabel'>
<?php
            print caEditorBundleSortControls($this->request, $vs_id_prefix, $t_item->tableName(), array_merge($settings, ['includeInterstitialSortsFor' => $t_subject->tableName()]));

		    if (($vn_rep_count > 1) && $this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
			    print "<div style='float: right'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1)." "._t('Download all'), 'button', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()])."</div>";
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
