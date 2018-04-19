<?php
/* ----------------------------------------------------------------------
 * bundles/ca_site_page_media.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
	$t_item 			= $this->getVar('t_item');			// ca_site_page_media
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');		// object
	$vs_add_label 		= $this->getVar('add_label');
	$va_settings 		= $this->getVar('settings');

	$vb_read_only		=	(isset($va_settings['readonly']) && $va_settings['readonly']);

	
	$vb_allow_fetching_from_urls = $this->request->getAppConfig()->get('allow_fetching_of_media_from_remote_urls');
	
	
	if (!in_array($vs_default_upload_type = $this->getVar('defaultRepresentationUploadType'), array('upload', 'url', 'search'))) {
		$vs_default_upload_type = 'upload';
	}
	
	// generate list of inital form values; the bundle Javascript call will
	// use the template to generate the initial form
	$va_errors = [];
	
	$vn_page_media_count = $t_subject->pageMediaCount($va_settings);
	$va_initial_values = caSanitizeArray($t_subject->getBundleFormValues('ca_site_page_media', $this->getVar('placement_code'), $va_settings, ['request' => $this->request]), ['removeNonCharacterData' => false]);

	foreach($va_initial_values as $vn_media_id => $va_media) {
		if(is_array($va_action_errors = $this->request->getActionErrors('ca_site_page_media', $vn_media_id))) {
			foreach($va_action_errors as $o_error) {
				$va_errors[$vn_media_id][] = array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber());
			}
		}
	}
	
	$va_failed_inserts = [];
	foreach($this->request->getActionErrorSubSources('ca_site_page_media') as $vs_error_subsource) {
		if (substr($vs_error_subsource, 0, 4) === 'new_') {
			$va_action_errors = $this->request->getActionErrors('ca_site_page_media', $vs_error_subsource);
			foreach($va_action_errors as $o_error) {
				$va_failed_inserts[] = array('icon' => '', '_errors' => array(array('errorDescription' => $o_error->getErrorDescription(), 'errorCode' => $o_error->getErrorNumber())));
			}
		}
	}
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings, (sizeof($va_initial_values) > 0), _t("Number of media: %1", sizeof($va_initial_values)));
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix.$t_item->tableNum().'_rel', $va_settings);
?>
<div id="<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>">
<?php
	//
	// Template to generate display for existing site page media
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
					<div class="caObjectRepresentationListItemImageThumb"><a href="#" onclick="caMediaPanel.showPanel('<?php print urldecode(caNavUrl($this->request, 'manage/site_pages', 'SitePageEditor', 'GetMediaOverlay', array('page_id' => $t_subject->getPrimaryKey(), 'media_id' => '{n}'))); ?>'); return false;">{icon}</a></div>
				</div>
				<div style="float: right; width: 550px;">
					<div style="float: left; width: 80%;">
						<div id='{fieldNamePrefix}rep_info_ro{n}'>
											
							<div class='caObjectRepresentationListInfoSubDisplay'>
								<em>{title}</em><br/>
								<h3><?php print _t('Identifier'); ?></h3> {idno}<br/>
								<h3><?php print _t('File name'); ?></h3> <span class="caObjectRepresentationListInfoSubDisplayFilename" id="{fieldNamePrefix}filename_display_{n}">{filename}</span><br/>
                                <h3><?php print _t('Access'); ?></h3> {access_display}<br/>
<?php
	TooltipManager::add("#{$vs_id_prefix}_filename_display_{n}", _t('File name: %1', "{{filename}}"), 'bundle_ca_site_page_media');
?>
								<h3><?php print _t('Versions'); ?></h3> {versions}
								</div>
								<div class='caObjectRepresentationListInfoSubDisplay'>
									<h3><?php print _t('Format'); ?></h3> {type};
									<h3><?php print _t('Dimensions'); ?></h3> {dimensions}
								</div>
								
						
								<div id='{fieldNamePrefix}change_indicator_{n}' class='caObjectRepresentationChangeIndicator'><?php print _t('Changes will be applied when you save'); ?></div>
							</div>		
<?php
	if (!$vb_read_only) {
?>
							<div id='{fieldNamePrefix}detail_editor_{n}' class="caObjectRepresentationDetailEditorContainer">
                                <div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('title', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}title_{n}", 'name' => "{fieldNamePrefix}title_{n}", 'value' => "{title}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
                                <div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('caption', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}caption_{n}", 'name' => "{fieldNamePrefix}caption_{n}", 'value' => "{caption}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
                                <div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('idno', null, array('classname' => 'caObjectRepresentationDetailEditorElementReadOnly', 'id' => "{fieldNamePrefix}idno_{n}", 'name' => "{fieldNamePrefix}idno_{n}", 'value' => "{idno}", "readonly" => true, 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?> <div style='margin-bottom: 10px;' id='{fieldNamePrefix}idno_status_{n}'></div></div>
								<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
						
								<br class="clear"/>
							
								<div class="caObjectRepresentationDetailEditorHeading"><?php print _t('Update media'); ?></div>
								<table id="{fieldNamePrefix}upload_options{n}">
									<tr>
										<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($vs_default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
										<td class='formLabel'><?php print $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media', 'class' => 'uploadInput')); ?></td>
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
									print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Done'), '{fieldNamePrefix}detail_editor_save_button{n}', array('onclick' => 'caCloseMediaDetailEditor("{n}"); return false;')); 
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
										
										jQuery("#{fieldNamePrefix}title_{n}").on('keyup', function(e) {
										    var t = jQuery(this).val();
										    jQuery("#{fieldNamePrefix}idno_{n}").val(t.replace(/[^A-Za-z0-9\_]+/g, "_")).trigger('change');
										});
									});
									
                                    caUI.initIDNoChecker({
                                        errorIcon: "<?php print caNavIcon(__CA_NAV_ICON_ALERT__, 1); ?>",
                                        processIndicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
                                        idnoStatusID: '{fieldNamePrefix}idno_status_{n}',
                                        lookupUrl: '<?php print caNavUrl($this->request, 'lookup', 'SitePageMedia', 'IDNo'); ?>',
                                        searchUrl: '',
                                        idnoFormElementIDs: ['#{fieldNamePrefix}idno_{n}'],
                                        separator: '',
                                        row_id: "{n}",
                                        context_id: "{page_id}",

                                        singularAlreadyInUseMessage: '<?php print addslashes(_t('Identifier is already in use')); ?>',
                                        pluralAlreadyInUseMessage: '<?php print addslashes(_t('Identifier is already in use %1 times')); ?>'
                                    });
								</script>
							</div>
<?php
	}
?>
						</div>
					
						<div class="mediaRight">	

							<div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}download_{n}"><?php print urldecode(caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1).' '._t('Download'), '', '*', '*', 'DownloadMedia', array('version' => 'original', 'media_id' => "{n}", $t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'download' => 1), array('id' => "{fieldNamePrefix}download_button_{n}"))); ?></span>
							</div>
<?php
	if (!$vb_read_only) {
?>                          <div class='caObjectRepresentationListActionButton'>
								<span id="{fieldNamePrefix}change_{n}" class="caObjectRepresentationListInfoSubDisplayUpdate"><a href='#' class='updateIcon' onclick="caOpenMediaDetailEditor('{n}'); return false;"><?php print caNavIcon(__CA_NAV_ICON_UPDATE__, 1).' '._t('Settings').'</a>'; ?></span>
                            </div>
<?php
	}
?>
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
				
				<br class="clear"/>
			</div>
		</textarea>
<?php
	//
	// Template to generate controls for creating new site page media
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>	
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<div style="float: right;">
				<div style="margin: 0 0 10px 5px;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
			</div>
			
			<div id='{fieldNamePrefix}detail_editor_{n}'>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('title', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}title_{n}", 'name' => "{fieldNamePrefix}title_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('caption', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}caption_{n}", 'name' => "{fieldNamePrefix}caption_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('idno', null, array('classname' => 'caObjectRepresentationDetailEditorElementReadOnly', 'id' => "{fieldNamePrefix}idno_{n}", 'name' => "{fieldNamePrefix}idno_{n}", "readonly" => false, 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?> <div style='margin-bottom: 10px;' id='{fieldNamePrefix}idno_status_{n}'></div></div>
				<div class="caObjectRepresentationDetailEditorElement"><?php print $t_item->htmlFormElement('access', null, array('classname' => 'caObjectRepresentationDetailEditorElement', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media')); ?></div>
				
				<br class="clear"/>
			</div>
			
			<table id="{fieldNamePrefix}upload_options{n}">
				<tr>
					<td class='formLabel'><?php print caHTMLRadioButtonInput('{fieldNamePrefix}upload_type{n}', array('id' => '{fieldNamePrefix}upload_type_upload{n}', 'class' => '{fieldNamePrefix}upload_type{n}', 'value' => 'upload'), array('checked' => ($vs_default_upload_type == 'upload') ? 1 : 0)).' '._t('using upload'); ?></td>
					<td class='formLabel'><?php print $t_item->htmlFormElement('media', '^ELEMENT', array('name' => "{fieldNamePrefix}media_{n}", 'id' => "{fieldNamePrefix}media_{n}", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_site_page_media', 'class' => 'uploadInput')); ?></td>
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
?>
			</table>
			
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
					jQuery("#{fieldNamePrefix}upload_type_search{n}").click(function() {
						jQuery("#{fieldNamePrefix}media_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}media_url_{n}").prop('disabled', true);
						jQuery("#{fieldNamePrefix}autocomplete{n}").prop('disabled', false);
					});
					
					jQuery("input.{fieldNamePrefix}upload_type{n}:checked").click();
					
                    jQuery("#{fieldNamePrefix}title_{n}").on('keyup', function(e) {
                        var t = jQuery(this).val();
                        jQuery("#{fieldNamePrefix}idno_{n}").val(t.replace(/[^A-Za-z0-9\_]+/g, "_")).trigger('change');
                    });
                    
									
                    caUI.initIDNoChecker({
                        errorIcon: "<?php print caNavIcon(__CA_NAV_ICON_ALERT__, 1); ?>",
                        processIndicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
                        idnoStatusID: '{fieldNamePrefix}idno_status_{n}',
                        lookupUrl: '<?php print caNavUrl($this->request, 'lookup', 'SitePageMedia', 'IDNo'); ?>',
                        searchUrl: '',
                        idnoFormElementIDs: ['#{fieldNamePrefix}idno_{n}'],
                        separator: '',
                        row_id: "",
                        context_id: "{page_id}",

                        singularAlreadyInUseMessage: '<?php print addslashes(_t('Identifier is already in use')); ?>',
                        pluralAlreadyInUseMessage: '<?php print addslashes(_t('Identifier is already in use %1 times')); ?>'
                    });
				});
			</script>
	</div>
			
</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_site_page_media');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="downloadAll">
<?php
		if ($vn_page_media_count > 1) {
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
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add media")." &rsaquo;"; ?></a></div>
<?php
	}
?>
	</div>
</div>

<input type="hidden" id="<?php print $vs_id_prefix; ?>_MediaBundleList" name="<?php print $vs_id_prefix; ?>_MediaBundleList" value=""/>
<?php
	// order element
	TooltipManager::add('.updateIcon', _t("Update Media"));
?>		
<script type="text/javascript">
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	
	function caOpenMediaDetailEditor(id) {
		jQuery('#<?php print $vs_id_prefix; ?>_detail_editor_' + id).slideDown(250);
		jQuery('#<?php print $vs_id_prefix; ?>_rep_info_ro' + id).slideUp(250);
	}
	
	function caCloseMediaDetailEditor(id) {
		jQuery('#<?php print $vs_id_prefix; ?>_detail_editor_' + id).slideUp(250);
		jQuery('#<?php print $vs_id_prefix; ?>_rep_info_ro' + id).slideDown(250);
		jQuery('#<?php print $vs_id_prefix; ?>_change_indicator_' + id).show();
	}
	
	jQuery(document).ready(function() {
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.$t_item->tableNum().'_rel'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['access', 'access_display', 'media', 'icon', 'type', 'dimensions', 'filename', 'fetched', 'idno', 'id', 'page_id', 'fetched_from','mimetype', 'idno', 'title', 'caption'],
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
			listSortOrderID: '<?php print $vs_id_prefix; ?>_MediaBundleList',
			defaultLocaleID: <?php print ca_locales::getDefaultCataloguingLocaleID(); ?>,
			
			minRepeats: <?php print caGetOption('minRelationshipsPerRow', $va_settings, 0); ?>,
			maxRepeats: <?php print caGetOption('maxRelationshipsPerRow', $va_settings, 65535); ?>,
			
			totalValueCount: <?php print (int)$vn_page_media_count; ?>
		
		});
	});
	
</script>