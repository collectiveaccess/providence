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

$vs_filename = ($this->getVar('file_name') ? $this->getVar('file_name') : $vn_id);
$va_destinations = $this->getVar('exporter_alternate_destinations');

print "<h2>"._t("The export has been processed. Configure your download below.")."</h2>\n";
?>
<table>
	<tr>
		<td><span class='formLabelPlain'><?php print _t("File name"); ?>&colon;</td>
		<td><?php print caHTMLTextInput('file_name', array('id' => 'file_name', 'value' => $vs_filename, 'size' => 40)); ?></td>
	</tr>
	<tr>
		<td style="vertical-align: top;"><span class='formLabelPlain'><?php print _t("Destination(s)"); ?>&colon;</td>
		<td>
			<div><?php print caJSButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t('Download'), 'file_download', array('id' => 'file_download', 'onclick' => 'caProcessDestination("file_download");')); ?></div>
<?php
			if(is_array($va_destinations)) {
				foreach($va_destinations as $vs_code => $va_dest) {
					if(!isset($va_dest['type']) || ($va_dest['type'] != 'github')) { continue; } // we only support github atm
					if(!isset($va_dest['display']) || !$va_dest['display']) { $va_dest['display'] = "???"; }

					print "<div>".caJSButton($this->request, __CA_NAV_ICON_UPDATE__, $va_dest['display'], $vs_code, array('onclick' => 'caProcessDestination("'.$vs_code.'"); return false;'))."<div/>\n";
				}
			}
?>
		</td>
	</tr>
</table>
<div id="caExporterDestinationFeedback" style="margin-top: 20px; text-align: center;"></div>


<script type="text/javascript">
	jQuery("#file_name").keyup(function(event){
		if(event.keyCode == 13){
			jQuery("#file_download").click();
		}
	});

	function caProcessDestination(dest_code) {
		var file_name = jQuery('#file_name').val();

		if(dest_code == 'file_download') { // for file download, really redirect to action
			window.location.href = "<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'ProcessDestination'); ?>?file_name=" + encodeURIComponent(file_name) + "&destination=file_download";
		} else { // for other destinations like github, load async
			jQuery('#caExporterDestinationFeedback').html("<?php print caBusyIndicatorIcon($this->request); ?>");
			jQuery("#caExporterDestinationFeedback").load('<?php print caNavUrl($this->request, 'manage', 'MetadataExport', 'ProcessDestination'); ?>', { file_name : file_name, destination : dest_code });
		}
	}
</script>
