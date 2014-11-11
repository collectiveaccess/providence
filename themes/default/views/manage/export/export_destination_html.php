<?php
/* ----------------------------------------------------------------------
 * manage/export/export_destination_html.php:
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

$t_exporter = $this->getVar('t_exporter');
$vs_export = $this->getVar('export');
$vn_id = $this->getVar('item_id');
$vs_ext = $t_exporter->getFileExtension();
$vs_content_type = $t_exporter->getContentType();
$va_errors = $this->getVar('errors');
$vs_filename = ($this->getVar('file_name') ? $this->getVar('file_name') : $vn_id);
$vs_file_base = $this->getVar('file_base', pString);

$va_destinations = $this->getVar('exporter_alternate_destinations');

if($va_errors && is_array($va_errors)){
	print "<div class='notification-error-box'><h2 style='margin-left:25px;'>"._t("Export mapping has errors")."</h2>";
	print "<ul>";

	foreach($va_errors as $vs_error){
		print "<li class='notification-error-box'>$vs_error</li>";
	}

	print "</ul></div>";
} else {
	print "<h2>"._t("The export has been processed. Configure your download below.")."</h2>\n";
	print caHTMLHiddenInput('exporter_id', array('value' => $t_exporter->getPrimaryKey()));
	print caHTMLHiddenInput('item_id', array('value' => $vn_id));
	print caHTMLHiddenInput('exportDestinationsSet', array('value' => 1));
	if(isset($vs_file_base)) {
		print caHTMLHiddenInput('file', array('value' => $vs_file_base));
	}
?>
	<table>
		<tr>
			<td><span class='formLabelPlain'><?php print _t("File name"); ?>&colon;</td>
			<td><?php print caHTMLTextInput('file_name', array('id' => 'file_name', 'value' => $vs_filename, 'size' => 40)); ?></td>
		</tr>
		<tr>
			<td style="vertical-align: top;"><span class='formLabelPlain'><?php print _t("Destination(s)"); ?>&colon;</td>
			<td>

<?php
				if(is_array($va_destinations)) {
					foreach($va_destinations as $vs_code => $va_dest) {
						if(!isset($va_dest['type']) || ($va_dest['type'] != 'github')) { continue; }

						$va_attributes = array();
						if(isset($va_dest['checked']) && $va_dest['checked']) { $va_attributes['checked'] = 'checked'; }
						if(!isset($va_dest['display']) || !$va_dest['display']) { $va_dest['display'] = "???"; }

						print "<div>".caJSButton($this->request, __CA_NAV_BUTTON_UPDATE__, $va_dest['display'], $vs_code, array('onclick' => 'caProcessDestination("'.$vs_code.'"); return false;'))."<div/>\n";
					}
				}
?>
				<div><?php print caJSButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, _t('Download'), 'file_download', array('onclick' => 'caProcessDestination("file_download");')); ?></div>
			</td>
		</tr>
	</table>
	<div id="caExporterDestinationFeedback"></div>
<?php
}
?>

<script type="text/javascript">
	function caProcessDestination(dest_code) {
		var file_name = jQuery('#file_name').val();
		jQuery('#caExporterDestinationFeedback').html("<?php print caBusyIndicatorIcon($this->request); ?>");
		jQuery("#caExporterDestinationFeedback").load('<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'ProcessDestination'); ?>', { file_name : file_name, destination : dest_code });
	}
</script>