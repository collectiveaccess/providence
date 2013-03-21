<?php
/* ----------------------------------------------------------------------
 * app/views/batch/metadataexport/exporter_run_html.php :
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

print $vs_control_box = caFormControlBox(
		CaFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Execute data export"), 'caBatchMetadataExportForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'batch', 'MetadataExport', 'Index', array()),
		'', 
		''
	);
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'ExportData/'.$this->request->getActionExtra(), 'caBatchMetadataExportForm', null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));

		print "<div class='formLabel'>"._t('Exporter')."<br>\n".ca_data_exporters::getExporterListAsHTMLFormElement('exporter_id', null, array('id' => 'caExporterList'), array('value' => $t_exporter->getPrimaryKey()))."</div>\n";
		print "<div class='formLabel' id='caExportSearchExprContainer'>"._t('Search expression')."<br>\n".caHTMLTextInput('search', array('id' => 'caExportSearchExpr'), array('width' => '300px'))."</div>\n";
?>
		</form>
</div>
<?php
	print $vs_control_box; 
?>
<div class="editorBottomPadding"><!-- empty --></div>