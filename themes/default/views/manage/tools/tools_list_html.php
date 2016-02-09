<?php
/* ----------------------------------------------------------------------
 * manage/export/tools_list_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

$va_tool_list = $this->getVar('tool_list');

if (!$this->request->isAjax()) {
    if(sizeof($va_tool_list)>0){
?>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#caToolList').caFormatListTable();
	});
</script>
<?php
    }
?>
<div class="sectionBox">
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caToolList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			''
		);
	?>
<?php
}
?>
	<div id="caToolListContainer">
		<table id="caToolList" class="listtable">
			<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php _p('Tool'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php _p('Description'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php _p('Commands'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort" style="width: 75px">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
<?php
	if (sizeof($va_tool_list) > 0) {
		foreach($va_tool_list as $vs_name => $o_tool) {
?>
			<tr>
				<td>
					<?php print $o_tool->getToolName(); ?>
				</td>
				<td>
					<?php print $o_tool->getToolDescription(); ?>
				</td>
				<td>
<?php
				$va_commands = $o_tool->getCommands();
				foreach($va_commands as $vs_command) {
					print "<u>{$vs_command}</u> – <em>".$o_tool->getShortHelpText($vs_command)."</em>";
				}
?>
				</td>
				<td>
					<?php print caNavButton($this->request, __CA_NAV_ICON_GO__, _t("Run"), '', 'manage', 'Tools', 'Settings', array('tool' => $o_tool->getToolIdentifier()), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
	<tr><td colspan="4" align="center"><?php print _t('No tools are available'); ?></td></tr>
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
				jQuery("#caToolListContainer").load("<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'Index'); ?>");
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