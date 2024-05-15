<?php
/* ----------------------------------------------------------------------
 * views/batch/mediaimport/import_options_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
AssetLoadManager::register("fileupload");

$t_instance = $this->getVar('t_instance');
$o_config = $t_instance->getAppConfig();
$t_rep = $this->getVar('t_rep');

$va_last_settings = $this->getVar('batch_mediaimport_last_settings');

print $vs_control_box = caFormControlBox(
	caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Execute media import"), 'caBatchMediaImportFormButton', array('onclick' => 'caShowConfirmBatchExecutionPanel(); return false;')).' '.
	caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'batch', 'MediaImport', 'Index/'.$this->request->getActionExtra(), array()),
	'',
	''
);
?>
	<div id="batchProcessingTableProgressGroup" style="display: none;">
		<div class="batchProcessingStatus"><span id="batchProcessingTableStatus" > </span></div>
		<div id="progressbar"></div>
	</div>

	<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'caBatchMediaImportForm', null, 'POST', 'multipart/form-data', '_top', array('noCSRFToken' => false, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
		print caHTMLHiddenInput('import_target', array('value' => $this->getVar('import_target')));
?>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Import target'); ?></span>
			<div class="bundleContainer">
				<div class="caLabelList" >
					<p>
						<?php
						print $this->getVar('import_target');
						?>
					</p>
				</div>
			</div>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Directory to import'); ?></span>
			<div class="bundleContainer">
				<div class="caLabelList" >
						<!--- begin directoryBrowser --->
				<div id="directoryBrowser" class='directoryBrowser'>
					<!-- Content for directory browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end directoryBrowser -->
<script type="text/javascript">
	var oDirBrowser;
	jQuery(document).ready(function() {
		oDirBrowser = caUI.initDirectoryBrowser('directoryBrowser', {
			levelDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryLevel'); ?>',
			initDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryAncestorList'); ?>',

			openDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
			disabledDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1, array('class' => 'disabled')); ?>",

			folderIcon: "<?= caNavIcon(__CA_NAV_ICON_FOLDER__, 1); ?>",
			fileIcon: "<?= caNavIcon(__CA_NAV_ICON_FILE__, 1); ?>",

			displayFiles: true,
			allowFileSelection: false,

			uploadProgressMessage: <?= json_encode(_t("Upload progress: %1")); ?>,
			uploadProgressID: "batchProcessingTableProgressGroup",
			uploadProgressBarID: "progressbar",
			uploadProgressStatusID: "batchProcessingTableStatus",
			allowDragAndDropUpload: <?= (sizeof(array_filter(caGetAvailableMediaUploadPaths(), 'is_writable')) > 0) ? "true" : "false"; ?>,
			dragAndDropUploadUrl: "<?= caNavUrl($this->request, 'batch', 'MediaImport', 'UploadFiles'); ?>",

			initItemID: <?= json_encode($va_last_settings['importFromDirectory'] ?? null); ?>,
			indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",

			currentSelectionDisplayID: 'browseCurrentSelection',

			onSelection: function(item_id, path, name, type) {
				if (type == 'DIR') { jQuery('#caDirectoryValue').val(path); } else { jQuery('#caDirectoryValue').val(''); }
			}
		});

		jQuery('#progressbar').progressbar({ value: 0 });
	});
</script>
<?php
		print caHTMLHiddenInput('directory', array('value' => '', 'id' => 'caDirectoryValue'));
?>
				</div>
				<div style="margin: 8px 0px 0px 0px; padding-bottom:5px;">
<?php
				$va_opts = array('id' => 'caIncludeSubDirectories', 'value' => 1);
				if (isset($va_last_settings['includeSubDirectories']) && $va_last_settings['includeSubDirectories']) {
					$va_opts['checked'] = 1;
				}
				print caHTMLCheckboxInput('include_subdirectories', $va_opts).' '._t('Include all sub-directories');
				$va_opts['style'] = 'margin-left: 10px';
				
				if($this->getVar('user_can_delete_media_on_import')) {
					print caHTMLCheckboxInput('delete_media_on_import', $va_opts).' '._t('Delete media after import');
				}
?>
				</div>
			</div>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Import mode'); ?></span>
				<div class="bundleContainer">
					<div class="caLabelList" >
						<p>
<?php
			print $this->getVar('import_mode');
?>
						</p>
					</div>
				</div>
		</div>

		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Type'); ?></span>
				<div class="bundleContainer">
					<div class="caLabelList">
						<div style='padding:10px 0px 10px 10px;'>
							<table style="width: 100%;">
								<tr>
									<td class='formLabel'>
<?php
										print _t('Type used for newly created %1', caGetTableDisplayName($t_instance->tableName(), false))."<br/>\n".$this->getVar($t_instance->tableName().'_type_list')."\n";
?>
									</td>
									<td class='formLabel'>
<?php
										print _t('Type used for newly created parent %1', caGetTableDisplayName($t_instance->tableName(), false))."<br/>\n".$this->getVar($t_instance->tableName().'_parent_type_list')."\n";
?>
									</td>
									<td class='formLabel'>
<?php
										print _t('Type used for newly created child %1', caGetTableDisplayName($t_instance->tableName(), false))."<br/>\n".$this->getVar($t_instance->tableName().'_child_type_list')."\n";
?>
									</td>
									<td class='formLabel'>
<?php
										print _t('Type used for newly created object representations')."<br/>\n".$this->getVar('ca_object_representations_type_list')."</div>\n";
?>
									</td>
<?php if($vs_reltypes = $this->getVar($t_instance->tableName().'_representation_relationship_type')) { ?>
									<td class='formLabel'>
										<?php
										print _t('Type used for relationship')."<br/>\n".$vs_reltypes."</div>\n";
										?>
									</td>
<?php } ?>
								</tr>
							</table>
						</div>
					</div>
				</div>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('Set'); ?></span>
			<div class="bundleContainer">
				<div class="caLabelList" id="caMediaImportSetControls">
					<div style='padding:10px 0px 10px 10px;'>
						<table>
<?php
	if (is_array($this->getVar('available_sets')) && sizeof($this->getVar('available_sets'))) {
?>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'add', 'checked' => 1, 'id' => 'caAddToSet');
									if (isset($va_last_settings['setMode']) && ($va_last_settings['setMode'] == 'add')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('set_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Add imported media to set %1', caHTMLSelect('set_id', $this->getVar('available_sets'), array('id' => 'caAddToSetID', 'class' => 'searchSetsSelect', 'width' => '300px'), array('value' => null, 'width' => '170px'))); ?></td>
							</tr>
<?php
	}
?>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'create', 'id' => 'caCreateSet');
									if (isset($va_last_settings['setMode']) && ($va_last_settings['setMode'] == 'create')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('set_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Create set %1 with imported media', caHTMLTextInput('set_create_name', array('value' => '', 'width' => '200px', 'id' => 'caSetCreateName'))); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'none', 'id' => 'caNoSet');
									if (!((isset($va_last_settings['setMode']) && (in_array($va_last_settings['setMode'], array('add', 'create')))))) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('set_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Do not associate imported media with a set'); ?></td>
							</tr>
						</table>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								jQuery("#caAddToSet").click(function() {
									jQuery("#caAddToSetID").prop('disabled', false);
									jQuery("#caSetCreateName").prop('disabled', true);
								});
								jQuery("#caCreateSet").click(function() {
									jQuery("#caAddToSetID").prop('disabled', true);
									jQuery("#caSetCreateName").prop('disabled', false);
								});
								jQuery("#caNoSet").click(function() {
									jQuery("#caAddToSetID").prop('disabled', true);
									jQuery("#caSetCreateName").prop('disabled', true);
								});

								jQuery("#caMediaImportSetControls").find("input:checked").click();
							});

						</script>
					</div>
				</div>
			</div>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('%1 identifier', ucfirst(caGetTableDisplayName($t_instance->tableName(), false))); ?></span>
			<div class="bundleContainer">
				<div class="caLabelList" id="caMediaImportIdnoControls">
					<div style='padding:10px 0px 10px 10px;'>
						<table>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'form', 'checked' => 1, 'id' => 'caIdnoFormMode');
									if (isset($va_last_settings['idnoMode']) && ($va_last_settings['idnoMode'] == 'form')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('idno_mode', $va_attrs);
								?></td>
								<td class='formLabel' id='caIdnoFormModeForm'><?= _t('Set %1 identifier to %2', caGetTableDisplayName($t_instance->tableName(), false),  $t_instance->htmlFormElement('idno', '^ELEMENT', array('request' => $this->request))); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'filename', 'id' => 'caIdnoFilenameMode');
									if (isset($va_last_settings['idnoMode']) && ($va_last_settings['idnoMode'] == 'filename')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('idno_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 identifier to file name', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'filename_no_ext', 'id' => 'caIdnoFilenameNoExtMode');
									if (isset($va_last_settings['idnoMode']) && ($va_last_settings['idnoMode'] == 'filename_no_ext')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('idno_mode', $va_attrs);
									?></td>
								<td class='formLabel'><?= _t('Set %1 identifier to file name without extension', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'directory_and_filename', 'id' => 'caIdnoDirectoryAndFilenameMode');
									if (isset($va_last_settings['idnoMode']) && ($va_last_settings['idnoMode'] == 'directory_and_filename')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('idno_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 identifier to directory and file name', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
						</table>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								jQuery("#caIdnoFormMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', false);
								});
								jQuery("#caIdnoFilenameMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});
								jQuery("#caIdnoFilenameNoExtMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});
								jQuery("#caIdnoDirectoryAndFilenameMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});

								jQuery("#caMediaImportIdnoControls").find("input:checked").click();
							});

						</script>
					</div>
				</div>
			</div>
		</div>

		<div class='bundleLabel'>
			<span class="formLabelText"><?= _t('%1 title', ucfirst(caGetTableDisplayName($t_instance->tableName(), false))); ?></span>
			<div class="bundleContainer">
				<div class="caLabelList" id="caMediaImportLabelControls">
					<div style='padding:10px 0px 10px 10px;'>
						<table>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'blank', 'checked' => 1, 'id' => 'caLabelBlankMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'blank')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 title to %2', caGetTableDisplayName($t_instance->tableName(), false), '['.caGetBlankLabelText($t_instance->tableName()).']'); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'user', 'id' => 'caLabelUserMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'user')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 title to %2', caGetTableDisplayName($t_instance->tableName(), false), caHTMLTextInput('label_text', ['id' => 'caLabelUserModeText', 'value' => $va_last_settings['labelText'] ?? null], ['width' => '400px'])); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'form', 'id' => 'caLabelIdnoMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'idno')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
								?></td>
								<td class='formLabel' id='caIdnoFormModeForm'><?= _t('Set %1 title to identifier', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'filename', 'id' => 'caLabelFilenameMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'filename')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 title to file name', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'filename_no_ext', 'id' => 'caLabelFilenameNoExtMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'filename_no_ext')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
									?></td>
								<td class='formLabel'><?= _t('Set %1 title to file name without extension', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
							<tr>
								<td><?php
									$va_attrs = array('value' => 'directory_and_filename', 'id' => 'caLabelDirectoryAndFilenameMode');
									if (isset($va_last_settings['labelMode']) && ($va_last_settings['labelMode'] == 'directory_and_filename')) { $va_attrs['checked'] = 1; }
									print caHTMLRadioButtonInput('label_mode', $va_attrs);
								?></td>
								<td class='formLabel'><?= _t('Set %1 title to directory and file name', caGetTableDisplayName($t_instance->tableName(), false)); ?></td>
							</tr>
						</table>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								jQuery("#caIdnoFormMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', false);
								});
								jQuery("#caIdnoFilenameMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});
								jQuery("#caIdnoFilenameNoExtMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});
								jQuery("#caIdnoDirectoryAndFilenameMode").click(function() {
									jQuery("#caIdnoFormModeForm input").prop('disabled', true);
								});

								jQuery("#caMediaImportIdnoControls").find("input:checked").click();
							});

						</script>
					</div>
				</div>
			</div>
		</div>
		<div class='bundleLabel'>
			<span class="formLabelText"><?= (($this->getVar('ca_object_representations_mapping_list_count') > 0) || ($this->getVar($t_instance->tableName().'_mapping_list_count') > 0)) ? _t('Status, access &amp; metadata extraction') : _t('Status &amp; access'); ?></span>
				<div class="bundleContainer">
					<div class="caLabelList" >
						<div style='padding:10px 0px 10px 10px;'>
							<table style="width: 100%;">
								<tr style="vertical-align: top;">
									<td class='formLabel'>
<?php
											print _t('Set %1 status to<br/>%2', caGetTableDisplayName($t_instance->tableName(), false), $t_instance->htmlFormElement('status', '', array('name' => $t_instance->tableName().'_status')));
											print "<br/>";
											print _t('Set %1 access to<br/>%2', caGetTableDisplayName($t_instance->tableName(), false), $t_instance->htmlFormElement('access', '', array('name' => $t_instance->tableName().'_access')));

											if ($this->getVar($t_instance->tableName().'_mapping_list_count') > 0) {
												print "<br/>";
												print _t('Extract embedded metadata into %1 using mapping<br/>%2', caGetTableDisplayName($t_instance->tableName(), false), $this->getVar($t_instance->tableName().'_mapping_list'));
											}
?>
									</td>
									<td class='formLabel'>
<?php
											print _t('Set representation status to<br/>%1', $t_rep->htmlFormElement('status', '', array('name' => 'ca_object_representations_status')));
											print "<br/>";
											print _t('Set representation access to<br/>%1', $t_rep->htmlFormElement('access', '', array('name' => 'ca_object_representations_access')));

											if ($this->getVar('ca_object_representations_mapping_list_count') > 0) {
												print "<br/>";
												print _t('Extract embedded metadata into representation using mapping<br/>%1', $this->getVar('ca_object_representations_mapping_list'));
											}
?>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
		</div>

		<div class='bundleLabel'>
			<span id="caBatchMediaAdvancedHeader" class="formLabelText"><a href="#" id="caBatchMediaAdvancedHeaderText"><?= _t("Show advanced options"); ?> &gt;</a></span>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#caBatchMediaAdvancedHeader").click(function(e) {
					e.preventDefault();
					$content = jQuery("#caBatchMediaAdvancedContent");
					$content.slideToggle(500, function () {
						jQuery("#caBatchMediaAdvancedHeaderText").text(function () {
							return $content.is(":visible") ? "< <?= _t("Hide advanced options"); ?>" : "<?= _t("Show advanced options"); ?> >";
						});
					});
					// scroll down so that you can actually see the advanced section after expanding
					jQuery('html, body').animate({
						scrollTop: 1000
					}, 1000);
				});
			});
		</script>
		<div id="caBatchMediaAdvancedContent" style="display: none">

			<div class='bundleLabel'>
				<span class="formLabelText"><?= _t('Matching'); ?></span>
				<div class="bundleContainer">
					<div class="caLabelList" >
						<table style="width: 100%;">
							<tr>
								<td class='formLabel'>
									<?php
									print $this->getVar('match_mode');
									print "\n<br/>\n";
									print _t('where identifier %1 value', $this->getVar('match_type'));
									?>
								</td>
								<td class='formLabel'>
									<?php
									print _t("Limit to types")."<br/>\n".$this->getVar($t_instance->tableName().'_limit_to_types_list');
									?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>

			<div class='bundleLabel'>
				<span class="formLabelText"><?= _t('%1 identifier', ucfirst(caGetTableDisplayName('ca_object_representations', false))); ?></span>
				<div class="bundleContainer">
					<div class="caLabelList" id="caMediaImportRepresentationIdnoControls">
						<div style='padding:10px 0px 10px 10px;'>
							<table>
								<tr>
									<td><?php
										$va_attrs = array('value' => 'form', 'checked' => 1, 'id' => 'caRepresentationIdnoFormMode');
										if (isset($va_last_settings['representationIdnoMode']) && ($va_last_settings['representationIdnoMode'] == 'form')) { $va_attrs['checked'] = 1; }
										print caHTMLRadioButtonInput('representation_idno_mode', $va_attrs);
										?></td>
									<td class='formLabel' id='caRepresentationIdnoFormModeForm'><?= _t('Set %1 identifier to %2', caGetTableDisplayName('ca_object_representations', false) , $t_rep->htmlFormElement('idno', '^ELEMENT', array('request' => $this->request))); ?></td>
								</tr>
								<tr>
									<td><?php
										$va_attrs = array('value' => 'filename', 'id' => 'caRepresentationIdnoFilenameMode');
										if (isset($va_last_settings['representationIdnoMode']) && ($va_last_settings['representationIdnoMode'] == 'filename')) { $va_attrs['checked'] = 1; }
										print caHTMLRadioButtonInput('representation_idno_mode', $va_attrs);
										?></td>
									<td class='formLabel'><?= _t('Set %1 identifier to file name', caGetTableDisplayName('ca_object_representations', false)); ?></td>
								</tr>
								<tr>
									<td><?php
										$va_attrs = array('value' => 'filename_no_ext', 'id' => 'caRepresentationIdnoFilenameNoExtMode');
										if (isset($va_last_settings['representationIdnoMode']) && ($va_last_settings['representationIdnoMode'] == 'filename_no_ext')) { $va_attrs['checked'] = 1; }
										print caHTMLRadioButtonInput('representation_idno_mode', $va_attrs);
										?></td>
									<td class='formLabel'><?= _t('Set %1 identifier to file name without extension', caGetTableDisplayName('ca_object_representations', false)); ?></td>
								</tr>
								<tr>
									<td><?php
										$va_attrs = array('value' => 'directory_and_filename', 'id' => 'caRepresentationIdnoDirectoryAndFilenameMode');
										if (isset($va_last_settings['representationIdnoMode']) && ($va_last_settings['representationIdnoMode'] == 'directory_and_filename')) { $va_attrs['checked'] = 1; }
										print caHTMLRadioButtonInput('representation_idno_mode', $va_attrs);
										?></td>
									<td class='formLabel'><?= _t('Set %1 identifier to directory and file name', caGetTableDisplayName('ca_object_representations', false)); ?></td>
								</tr>
							</table>
							<script type="text/javascript">
								jQuery(document).ready(function() {
									jQuery("#caRepresentationIdnoFormMode").click(function() {
										jQuery("#caRepresentationIdnoFormModeForm input").prop('disabled', false);
									});
									jQuery("#caRepresentationIdnoFilenameMode").click(function() {
										jQuery("#caRepresentationIdnoFormModeForm input").prop('disabled', true);
									});
									jQuery("#caRepresentationIdnoFilenameNoExtMode").click(function() {
										jQuery("#caRepresentationIdnoFormModeForm input").prop('disabled', true);
									});
									jQuery("#caRepresentationIdnoDirectoryAndFilenameMode").click(function() {
										jQuery("#caRepresentationIdnoFormModeForm input").prop('disabled', true);
									});

									jQuery("#caMediaImportRepresentationIdnoControls").find("input:checked").click();
								});

							</script>
						</div>
					</div>
				</div>
			</div>

			<div class='bundleLabel'>
				<span class="formLabelText"><?= _t('Relationships'); ?></span>
					<div class="bundleContainer">
						<div class="caLabelList" >
							<p class="bundleDisplayPlacementEditorHelpText">
	<?php
		print _t('Relationships will be created by matching the identifier extracted from the media file name with identifiers in related records.');
	?>
							</p>
							<div style='padding:10px 0px 10px 10px;'>
								<table>
	<?php
		foreach(array('ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections') as $vs_rel_table) {
			if ($o_config->get("{$vs_rel_table}_disable")) { continue; }
			if (!($t_rel_table = Datamodel::getInstanceByTableName($vs_rel_table))) { continue; }
			$t_rel = ca_relationship_types::getRelationshipTypeInstance($t_instance->tableName(), $vs_rel_table);
			if (!$t_rel) { continue; }
	?>
									<tr>
										<td class='formLabel'>
	<?php
				$checked = (is_array($va_last_settings['create_relationship_for'] ?? null) && in_array($vs_rel_table, $va_last_settings['create_relationship_for'] ?? null)) ? true : false;
				$default_rel_type_id = isset($va_last_settings['relationship_type_id_for_'.$vs_rel_table]) ? $va_last_settings['relationship_type_id_for_'.$vs_rel_table] : null;
				print caHTMLCheckboxInput('create_relationship_for[]', array('value' => $vs_rel_table,  'id' => "caCreateRelationshipForMedia{$vs_rel_table}", 'checked' => $checked, 'onclick' => "jQuery('#caRelationshipTypeIdFor{$vs_rel_table}').prop('disabled', !jQuery('#caCreateRelationshipForMedia{$vs_rel_table}').prop('checked'))"), ['dontConvertAttributeQuotesToEntities' => true]);
				print ' '._t("to %1 with relationship type", $t_rel_table->getProperty('NAME_SINGULAR'));
	?>
										</td>
										<td class='formLabel'>
	<?php
				$opts = ['name' => "relationship_type_id_for_{$vs_rel_table}", 'id' => "caRelationshipTypeIdFor{$vs_rel_table}"];
				if(!$checked) { $opts['disabled'] = true; }
				print $t_rel->getRelationshipTypesAsHTMLSelect('ltor', null, null, $opts, ['value' => $default_rel_type_id]);
	?>
										</td>
									</tr>
	<?php
		}
	?>
								</table>
							</div>
						</div>
					</div>
			</div>
			<div class='bundleLabel'>
				<span class="formLabelText"><?= _t('Skip files'); ?></span>
					<div class="bundleContainer">
						<div class="caLabelList" >
							<p class="bundleDisplayPlacementEditorHelpText">
	<?php
		print _t('List names of files you wish to skip during import below, one per line. You may use asterisks ("*") as wildcards to make partial matches. Values enclosed in "/" characters will be treated as <a href="http://www.pcre.org/pcre.txt" target="_new">Perl-compatible regular expressions</a>.');
	?>
							</p>
							<p>
	<?php
				print caHTMLTextInput('skip_file_list', array('value' => $va_last_settings['skipFileList'] ?? null,  'id' => "caSkipFilesList"), array('width' => '700px', 'height' => '100px'));
	?>
							</p>
						</div>
					</div>
			</div>
			<div class='bundleLabel'>
				<span class="formLabelText"><?= _t('Miscellaneous'); ?></span>
					<div class="bundleContainer">
						<div class="caLabelList" >
							<table>
								<tr valign="top">
									<td>
							<p class='formLabel'>
	<?php
								print _t('Log level').'<br/>';
								print caHTMLSelect('log_level', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $va_last_settings['logLevel'] ?? null));
	?>
							</p>
									</td>
									<td>
							<p class='formLabel'>
	<?php
				print caHTMLCheckboxInput('allow_duplicate_media', array('value' => 1,  'id' => 'caAllowDuplicateMedia', 'checked' => $va_last_settings['allowDuplicateMedia'] ?? null), []);
				print " "._t('Allow duplicate media?');
	?>
							</p>
									</td>
									<td>
							<p class='formLabel'>
	<?php
				print caHTMLCheckboxInput('replace_existing_media', array('value' => 1,  'id' => 'caReplaceExistingMedia', 'checked' => 0), array());
				print " "._t('Replace existing media?');
	?>
							</p>
									</td>
								</tr>
							</table>
						</div>
					</div>
			</div>
		</div>

<?php
			print $this->render("mediaimport/confirm_html.php");

			print $vs_control_box;
?>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>

	<script type="text/javascript">
		function caShowConfirmBatchExecutionPanel() {
			var msg = <?= json_encode(_t("You are about to import files from <em>%1</em>")); ?>;
			var dir = jQuery('#caDirectoryValue').val();
			msg = msg.replace("%1", dir ? dir : <?= json_encode('the import root'); ?>);
			caConfirmBatchExecutionPanel.showPanel();
			jQuery('#caConfirmBatchExecutionPanelAlertText').html(msg);
		}
		
		function caUpdateFormForMode(m) {
			if(m === 'DIRECTORY_AS_HIERARCHY') {
				jQuery('#primary_type_id').parent('td').hide();
				jQuery('#parent_type_id, #child_type_id').parent('td').show();
			} else {
				jQuery('#primary_type_id').parent('td').show();
				jQuery('#parent_type_id, #child_type_id').parent('td').hide();
			}
		}

		jQuery(document).bind('drop dragover', function (e) {
			e.preventDefault();
		});
		
		jQuery(document).ready(function() {
			jQuery('#importMode').on('change', function(e) {
				caUpdateFormForMode(jQuery('#importMode').val());
			});
			caUpdateFormForMode(jQuery('#importMode').val());
		});
		
	</script>
