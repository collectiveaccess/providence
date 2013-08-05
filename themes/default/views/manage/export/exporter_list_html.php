<?php
/* ----------------------------------------------------------------------
 * manage/export/exporter_list_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

$va_exporter_list = $this->getVar('exporter_list');

if (!$this->request->isAjax()) {
    if(sizeof($va_exporter_list)>0){
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
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caExporterList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			caJSButton($this->request, __CA_NAV_BUTTON_ADD_LARGE__, _t("Add exporters"), 'caAddExportersButton', array('onclick' => 'jQuery("#exporterUploadArea").slideToggle(150); return false;'))
		);
	?>
	
	
	<div id="batchProcessingTableProgressGroup" style="display: none;">
		<div class="batchProcessingStatus"><span id="batchProcessingTableStatus" > </span></div>
		<div id="progressbar"></div>
	</div>
	
	<div id="exporterUploadArea" style="border: 2px dashed #999999; text-align: center; padding: 20px; display: none;">
		<span style="font-size: 20px; color: #aaaaaa; font-weight: bold;"><?php print _t("Drag exporter worksheets here to add or update"); ?></span>
	</div>
<?php
}
?>
	<div id="caExporterListContainer">
		<table id="caExporterList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
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
				<th class="{sorter: false} list-header-nosort" style="width: 75px">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
<?php
	foreach($va_exporter_list as $va_exporter) {
?>
			<tr>
				<td>
					<?php print $va_exporter['label']; ?>
				</td>
				<td>
					<?php print $va_exporter['exporter_code']; ?>
				</td>
				<td>
					<?php print $va_exporter['exporter_type']; ?>
				</td>
				<td>
					<?php print caGetLocalizedDate($va_exporter['last_modified_on'], array('dateFormat' => 'delimited')); ?>
				</td>
				<td>
					<!--<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'manage', 'MetadataExport', 'Edit', array('exporter_id' => $va_exporter['exporter_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>-->
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage', 'MetadataExport', 'Delete', array('exporter_id' => $va_exporter['exporter_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_GO__, _t("Export data"), 'manage', 'MetadataExport', 'Run', array('exporter_id' => $va_exporter['exporter_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
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
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({ value: 0 });
		
		jQuery('#exporterUploadArea').fileupload({
			dataType: 'json',
			url: '<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'UploadExporters'); ?>',
			dropZone: jQuery('#exporterUploadArea'),
			singleFileUploads: false,
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
						}, 3000);
				}
				jQuery("#caExporterListContainer").load("<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'Index'); ?>");
			},
			progressall: function (e, data) {
				jQuery("#exporterUploadArea").hide(150);
				if (jQuery("#batchProcessingTableProgressGroup").css('display') == 'none') {
					jQuery("#batchProcessingTableProgressGroup").show(250);
				}
				var progress = parseInt(data.loaded / data.total * 100, 10);
				jQuery('#progressbar').progressbar("value", progress);
			
				var msg = "<?php print _t("Progress: "); ?>%1";
				jQuery("#batchProcessingTableStatus").html(msg.replace("%1", caUI.utils.formatFilesize(data.loaded) + " (" + progress + "%)"));
				
			}
		});
	});
</script>
<?php
}
?>