<?php
/* ----------------------------------------------------------------------
 * app/views/batch/metadataimport/importer_run_html.php :
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

$t_importer = $this->getVar('t_importer');

print $vs_control_box = caFormControlBox(
		caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Execute data import"), 'caBatchMetadataImportForm', array('onclick' => 'caShowConfirmBatchExecutionPanel(); return false;')).' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'batch', 'MetadataImport', 'Index', array()),
		'', 
		''
	);
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'ImportData/'.$this->request->getActionExtra(), 'caBatchMetadataImportForm', null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));

		print "<div class='formLabel'>"._t('Importer')."<br>\n".ca_data_importers::getImporterListAsHTMLFormElement('importer_id', null, array('id' => 'caImporterList', 'onchange' => 'caSetBatchMetadataImportFormState();'), array('value' => $t_importer->getPrimaryKey()))."</div>\n";
		print "<div class='formLabel'>"._t('Data format')."<br>\n".ca_data_importers::getInputFormatListAsHTMLFormElement('inputFormat', array('id' => 'caInputFormatList', 'onchange' => 'caSetBatchMetadataImportFormState();'))."</div>\n";
		
		print "<div class='formLabel' id='caSourceFileContainer'>"._t('Data file')."<br>\n"."<input type='file' name='sourceFile' id='caSourceFile'/>"."</div>\n";
		print "<div class='formLabel' id='caSourceUrlContainer'>"._t('Data URL')."<br>\n".caHTMLTextInput('sourceUrl', array('id' => 'caSourceUrl', 'class' => 'urlBg'), array('width' => '300px'))."</div>\n";


		print $this->render("metadataimport/confirm_html.php");	
?>
		</form>
</div>
<?php
	print $vs_control_box; 
?>
<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	function caShowConfirmBatchExecutionPanel() {
		var msg = '<?php print addslashes(_t("You are about to import data using the <em>%1</em> importer")); ?>';
		msg = msg.replace("%1", jQuery("#caImporterList option:selected").text())
		
		caConfirmBatchExecutionPanel.showPanel();
		jQuery('#caConfirmBatchExecutionPanelAlertText').html(msg);
	}
	
	$(document).bind('drop dragover', function (e) {
		e.preventDefault();
	});
	
	var caDataReaderInfo = <?php print json_encode(ca_data_importers::getInfoForAvailableInputFormats()); ?>;
	var caImporterInfo = <?php print json_encode(ca_data_importers::getImporters()); ?>;
	
	function caSetBatchMetadataImportFormState(dontAnimate) {
		var info;
		var currentFormat = jQuery("#caInputFormatList").val();
		
		// Set format list
		var relevantFormats = [];
		var curImporterID = jQuery("#caImporterList").val();
		if (caImporterInfo[curImporterID]) {
			relevantFormats = caImporterInfo[curImporterID]['settings']['inputFormats'];
		}
		
		var opts = [];
		var formatInfo = {};
		for(var reader in caDataReaderInfo) {
			for(var i in relevantFormats) {
				if (jQuery.inArray(relevantFormats[i].toLowerCase(), caDataReaderInfo[reader]['formats']) > -1) {
					formatInfo[relevantFormats[i].toLowerCase()] = caDataReaderInfo[reader];
					opts.push("<option value='" + relevantFormats[i] + "'>" + caDataReaderInfo[reader]['displayName']+ "</option>");
					break;
				}
			}
		}
		
		jQuery("#caInputFormatList").html(opts.join("\n")).val(currentFormat);
		
		currentFormat = jQuery("#caInputFormatList").val();
		
		// Set visibility of source input field based upon format
		
		if (info = formatInfo[currentFormat.toLowerCase()]) {
			if (info['inputType'] == 0) {
				// file
				jQuery('#caSourceUrlContainer').hide(dontAnimate ? 0 : 150);
				jQuery('#caSourceUrl').prop('disabled', true);
				jQuery('#caSourceFileContainer').show(dontAnimate ? 0 : 150);
				jQuery('#caSourceFile').prop('disabled', false);
			} else {
				// url
				jQuery('#caSourceUrlContainer').show(dontAnimate ? 0 : 150);
				jQuery('#caSourceUrl').prop('disabled', false);
				jQuery('#caSourceFileContainer').hide(dontAnimate ? 0 : 150);
				jQuery('#caSourceFile').prop('disabled', true);
			}
		}
	}
	
	jQuery(document).ready(function() {
		caSetBatchMetadataImportFormState(true);
	});
</script>