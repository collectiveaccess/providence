<?php
/* ----------------------------------------------------------------------
 * manage/export/exporter_run_html.php :
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

$t_exporter = $this->getVar('t_exporter');
$va_exporters = ca_data_exporters::getExporters($t_exporter->get('table_num'));

print $vs_control_box = caFormControlBox(
		CaFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Execute data export"), 'caBatchMetadataExportForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'manage', 'MetadataExport', 'Index', array()),
		'', 
		''
	);
?>
<div class="sectionBox">
	<div class='formLabel' style='margin-bottom:20px'><?php print _t("You are about to export a set of <em>%1</em> using the <em>%2</em> exporter",$va_exporters[$t_exporter->getPrimaryKey()]['exporter_type'],$t_exporter->getLabelForDisplay()); ?></div>
<?php
		print caFormTag($this->request, 'ExportData/'.$this->request->getActionExtra(), 'caBatchMetadataExportForm', null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));

		print caHTMLHiddenInput('exporter_id', array('value' => $t_exporter->getPrimaryKey()));

		print "<div class='formLabel' id='caExportSearchExprContainer'>"._t('Search expression')."<br>\n".caHTMLTextInput('search', array('id' => 'caExportSearchExpr'), array('width' => '300px'))."</div>\n";
?>
		</form>
</div>
<?php
	print $vs_control_box; 

?>
<div class="editorBottomPadding"><!-- empty --></div>