<?php
/* ----------------------------------------------------------------------
 * app/views/batch/metadataimport/importer_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2019 Whirl-i-Gig
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

$va_importer_list = $this->getVar('importer_list');

if (!$this->request->isAjax()) {
?>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#caItemList').caFormatListTable();
	});
</script>
<div class="sectionBox">
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			caFormJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Add importers"), 'caAddImportersButton', array('onclick' => 'caOpenImporterUploadArea(true, true); return false;', 'id' => 'caAddImportersButton')).
			caFormJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Close"), 'caCloseImportersButton', array('onclick' => 'caOpenImporterUploadArea(false, true); return false;', 'id' => 'caCloseImportersButton'))
		);
	?>
	
	
	<div id="batchProcessingTableProgressGroup" style="display: none;">
		<div class="batchProcessingStatus"><span id="batchProcessingTableStatus" > </span></div>
		<div id="progressbar"></div>
	</div>
	
	<div id="importerUploadArea" style="border: 1px dashed #ccc; text-align: center; padding: 10px; display: none;">
		<span style="font-size: 16px; color: #333; "><?= _t("Drag importer worksheets here to add or update"); ?></span>
	</div>
	<div style="margin: 10px 0 0 0;">
<?php 
			print caFormTag($this->request, 'Load', 'caLoadFromGoogleDrive', null, 'post', 'multipart/form-data', '_top', ['disableUnsavedChangesWarning' => true, 'submitOnReturn' => true, 'noCSRFToken' => true]);
			print _t('Load importer worksheet from GoogleDrive: %1', caHTMLTextInput('google_drive_url', ['class' => 'urlBg'], ['width' => '300px'])); 
			print caFormSubmitButton($this->request, __CA_NAV_ICON_GO__, "", 'caLoadFromGoogleDrive', ['size' => '24px']);
?>
			</form>
	</div>
<?php
}
?>
	<div id="caImporterListContainer">
		<table id="caItemList" class="listtable">
			<thead>
			<tr>
				<th>
					<?php _p('Name'); ?>
				</th>
				<th>
					<?php _p('Code'); ?>
				</th>
				<th>
					<?php _p('Type'); ?>
				</th>
				<th>
					<?php _p('Source'); ?>
				</th>
				<th>
					<?php _p('Mapping'); ?>
				</th>
				<th>
					<?php _p('Last modified'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
<?php
	if(sizeof($va_importer_list) == 0) {
?>
			<tr>
				<td colspan='7'>
					<div align="center"><?= _t('No importers defined'); ?></div>
				</td>
			</tr>
<?php
	} else {
		foreach($va_importer_list as $va_importer) {
?>
			<tr>
				<td>
					<?= $va_importer['label']; ?>
				</td>
				<td>
					<?= $va_importer['importer_code']; ?>
				</td>
				<td>
					<?= $va_importer['importer_type']; ?>
				</td>
				<td>
					<?= (isset($va_importer['settings']['sourceUrl'])) ? _t('Google Drive') : _t('File upload'); ?>
				</td>
				<td>
<?php  
					print $va_importer['worksheet'] ? caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t("Download"), '', 'batch', 'MetadataImport', 'Download', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)) : '' ; 

					if(isset($va_importer['settings']['sourceUrl'])) {
						print caNavButton($this->request, __CA_NAV_ICON_ROTATE__, _t("Reload"), '', 'batch', 'MetadataImport', 'Load', array('google_drive_url' => urlencode($va_importer['settings']['sourceUrl'])), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true));
					}
?>				</td>
				<td>
					<?= caGetLocalizedDate($va_importer['last_modified_on'], array('timeOmit' => false, 'dateFormat' => 'delimited')); ?>
				</td>
				<td class="listtableEditDelete">
					<?= caNavButton($this->request, __CA_NAV_ICON_GO__, _t("Import data"), '', 'batch', 'MetadataImport', 'Run', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'batch', 'MetadataImport', 'Delete', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	}
?>
			</tbody>
		</table>
	</div>
<?php
if (!$this->request->isAjax()) {
?>
</div>
<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	var batchCookieJar = jQuery.cookieJar('caCookieJar');
		
	function caOpenImporterUploadArea(open, animate) {
		batchCookieJar.set('importerUploadAreaIsOpen', open);
		if (open) {
			jQuery("#importerUploadArea").slideDown(animate ? 150 : 0);
			jQuery("#caCloseImportersButton").show();
			jQuery("#caAddImportersButton").hide();
		} else {
			jQuery("#importerUploadArea").slideUp(animate ? 150 : 0);
			jQuery("#caCloseImportersButton").hide();
			jQuery("#caAddImportersButton").show();
		}
	}
	
	function caImporterUploadAreaIsOpen() {
		return batchCookieJar.get('importerUploadAreaIsOpen');
	}
	
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({ value: 0 });
		
		jQuery('#importerUploadArea').fileupload({
			dataType: 'json',
			url: '<?= caNavUrl($this->request, 'batch', 'MetadataImport', 'UploadImporters'); ?>',
			dropZone: jQuery('#importerUploadArea'),
			singleFileUploads: false,
			done: function (e, data) {
				if (data.result.error) {
					jQuery("#batchProcessingTableProgressGroup").show(250);
					jQuery("#batchProcessingTableStatus").html(data.result.error);
					setTimeout(function() {
						jQuery("#batchProcessingTableProgressGroup").hide(250);
					}, 3000);
				} else {
					var msg = [];
					
					if (data.result.uploadMessage) {
						msg.push(data.result.uploadMessage);
					}
					if (data.result.skippedMessage) {
						msg.push(data.result.skippedMessage);
					}
					jQuery("#batchProcessingTableStatus").html(msg.join('; '));
					setTimeout(function() {
							jQuery("#batchProcessingTableProgressGroup").hide(250);
							jQuery("#importerUploadArea").show(150);
						}, 3000);
				}
				jQuery("#caImporterListContainer").load("<?= caNavUrl($this->request, 'batch', 'MetadataImport', 'Index'); ?>");
			},
			progressall: function (e, data) {
				jQuery("#importerUploadArea").hide(150);
				if (jQuery("#batchProcessingTableProgressGroup").css('display') == 'none') {
					jQuery("#batchProcessingTableProgressGroup").show(250);
				}
				var progress = parseInt(data.loaded / data.total * 100, 10);
				jQuery('#progressbar').progressbar("value", progress);
			
				var msg = "<?= _t("Progress: "); ?>%1";
				jQuery("#batchProcessingTableStatus").html(msg.replace("%1", caUI.utils.formatFilesize(data.loaded) + " (" + progress + "%)"));
				
			}
		});
		
		caOpenImporterUploadArea(batchCookieJar.get('importerUploadAreaIsOpen'), false);
	});
</script>
<?php
}
