<?php
/* ----------------------------------------------------------------------
 * manage/export/exporter_list_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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
$exporter_list = $this->getVar('exporter_list');

if (!$this->request->isAjax()) {
    if(sizeof($exporter_list)>0){
?>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#caExporterList').caFormatListTable();
	});
</script>
<?php
    }
?>
<div class="sectionBox">
	<?= caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caExporterList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			caFormJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Add exporters"), 'caAddExportersButton', array('onclick' => 'caOpenExporterUploadArea(true, true); return false;', 'id' => 'caAddExportersButton')).
			caFormJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Close"), 'caCloseExportersButton', array('onclick' => 'caOpenExporterUploadArea(false, true); return false;', 'id' => 'caCloseExportersButton'))
		);
	?>
	

	<div id="batchProcessingTableProgressGroup" style="display: none;">
		<div class="batchProcessingStatus"><span id="batchProcessingTableStatus" > </span></div>
		<div id="progressbar"></div>
	</div>
	
	<div id="exporterUploadArea">
		<form enctype="multipart/form-data" method='post' action="#" style="display: none;">
			<input type="file" name="mapping[]" id="mappingUploadInput" multiple/>
		</form>
		<div class="exporterUploadText"><?= caNavIcon(__CA_NAV_ICON_ADD__, '20px'); ?> <?= _t("Click or drag exporter worksheets here to add or update"); ?></div>
	</div>
<?php
}
?>
	<div id="caExporterListContainer">
		<table id="caExporterList" class="listtable">
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
					<?php _p('Last modified'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort" style="width: 20px">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
<?php
	foreach($exporter_list as $exporter) {
?>
			<tr>
				<td>
					<?= $exporter['label']; ?>
				</td>
				<td>
					<?= $exporter['exporter_code']; ?>
				</td>
				<td>
					<?= $exporter['exporter_type']; ?>
				</td>
				<td>
					<?= caGetLocalizedDate($exporter['last_modified_on'], array('dateFormat' => 'delimited')); ?>
				</td>
				<td>
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage', 'MetadataExport', 'Delete', array('exporter_id' => $exporter['exporter_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
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
		
	function caOpenExporterUploadArea(open, animate) {
		batchCookieJar.set('exporterUploadAreaIsOpen', open);
		if (open) {
			jQuery("#exporterUploadArea").slideDown(animate ? 150 : 0);
			jQuery("#caCloseExportersButton").css('display', 'block');
			jQuery("#caAddExportersButton").hide();
		} else {
			jQuery("#exporterUploadArea").slideUp(animate ? 150 : 0);
			jQuery("#caCloseExportersButton").hide();
			jQuery("#caAddExportersButton").css('display', 'block');
		}
	}
	
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({ value: 0 });
		
		jQuery('#exporterUploadArea').fileupload({
			dataType: 'json',
			url: '<?= caNavUrl($this->request, 'manage', 'MetadataExport', 'UploadExporters'); ?>',
			dropZone: jQuery('#exporterUploadArea'),
			singleFileUploads: false,
			fileInput: jQuery("#mappingUploadInput"),
			done: function (e, data) {
				jQuery("#exporterUploadArea").hide(150);
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
							jQuery("#exporterUploadArea").show(150);
						}, 3000);
				}
				jQuery("#caExporterListContainer").load("<?= caNavUrl($this->request, 'manage', 'MetadataExport', 'Index'); ?>");
			},
			progressall: function (e, data) {
				jQuery("#exporterUploadArea").hide(150);
				if (jQuery("#batchProcessingTableProgressGroup").css('display') == 'none') {
					jQuery("#batchProcessingTableProgressGroup").show(250);
				}
				var progress = parseInt(data.loaded / data.total * 100, 10);
				jQuery('#progressbar').progressbar("value", progress);
			
				var msg = "<?= _t("Progress: "); ?>%1";
				jQuery("#batchProcessingTableStatus").html(msg.replace("%1", caUI.utils.formatFilesize(data.loaded) + " (" + progress + "%)"));
				
			}
		});
		
		jQuery('div.exporterUploadText').on('click', function(e) {
			jQuery("#mappingUploadInput").click(); 
			e.preventDefault();
		});
		
		caOpenExporterUploadArea(batchCookieJar.get('exporterUploadAreaIsOpen'), false);
	});
</script>
<?php
}
?>
