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

if($va_errors && is_array($va_errors)){
	print "<div class='notification-error-box'><h2 style='margin-left:25px;'>"._t("Export mapping has errors")."</h2>";
	print "<ul>";

	foreach($va_errors as $vs_error){
		print "<li class='notification-error-box'>$vs_error</li>";
	}

	print "</ul></div>";
} else {
	if($vs_export) {
		print "<h2>"._t("Your export is ready for download. The preview below may be inaccurate. Download the export for best results.")."</h2>\n";
		print "<pre class='caExportPreview'>".caEscapeForXML($vs_export)."</pre>\n";
	} else {
		$vs_file_base = $this->getVar('file_base', pString);
		print "<h2>"._t("Your export is ready for download.")."</h2>\n";

	}
	print "<h2>"._t("Choose destination(s) for your export.")."</h2>\n";
	print caFormTag($this->request, $this->request->getAction(), 'caExportDestinationForm', 'manage/MetadataExport');
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
			<td><?php print caHTMLTextInput('file_name', array('value' => $vs_filename.'.'.$vs_ext, 'size' => 40)); ?></td>
		</tr>
		<tr>
			<td style="vertical-align: top;"><span class='formLabelPlain'><?php print _t("Destination(s)"); ?>&colon;</td>
			<td>
				<div><?php print caHTMLCheckboxInput('exportDestinationFile', array('checked' => 'checked')); ?> File download</div>
				<div><?php print caHTMLCheckboxInput('exportDestinationGitHub', array('checked' => 'checked')); ?> GitHub</div>
			</td>
		</tr>
		<tr>
			<td><?php if($vn_id) { print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Back to previous screen'), 'backButton', array('onclick' => 'window.history.back()')); } ?>&nbsp;</td>
			<td><?php print caFormSubmitButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, _t('Go'), 'caExportDestinationForm'); ?></td>
		</tr>
	</table>
	</form>
<?php
}
