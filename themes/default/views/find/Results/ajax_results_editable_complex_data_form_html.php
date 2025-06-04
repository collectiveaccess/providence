<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ajax_results_editable_complex_data_form_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2025 Whirl-i-Gig
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
$t_subject = 		$this->getVar('t_subject');
$subject_id = 		$t_subject->getPrimaryKey();

$bundles = 			$this->getVar('bundles');
$bundle = 			$this->getVar('bundle');
$row = 				$this->getVar('row');
$col = 				$this->getVar('col');
$placement_id = 	$this->getVar('placement_id');

$can_edit = true;

$form_elements = $t_subject->getBundleFormHTMLForScreen(null, array(
		'request' => $this->request, 
		'formName' => 'complex',
		'bundles' => $bundles,
		'dontAllowBundleShowHide' => true
));

print caFormTag($this->request, '#', 'caEditableResultsComplexDataForm', null, 'POST', 'multipart/form-data', null, array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'disableSubmit' => true));
?>
	<div class="caResultsComplexDataEditorErrorContainer" id="caEditableResultsComplexDataFormErrors"> </div>
	
	<div id="caResultsComplexDataEditorPanelControlButtons">
<?php
	if ($can_edit) {
		print caFormControlBox(caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), "caEditableResultsComplexDataFormSaveButton", array("onclick" => "caEditableResultsComplexDataFormHandler.save(event);"))
			.' '.caFormJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), "caEditableResultsComplexDataFormCancelButton", array("onclick" => "jQuery(\"#caEditableResultsComplexDataForm\").parent().parent().data(\"panel\").hidePanel();")), 
			'',
			''
		);
	}
?>
	</div>
	<div class="caResultsComplexDataEditorSectionBox">
		<div style="margin: 5px;"> </div>
<?php
		print join("\n", $form_elements); 

		print caHTMLHiddenInput('id', array('value' => $subject_id));
		print caHTMLHiddenInput('bundle', array('value' => $bundle));
		print caHTMLHiddenInput('row', array('value' => $row));
		print caHTMLHiddenInput('col', array('value' => $col));
		print caHTMLHiddenInput('placement_id', array('value' => $placement_id));
?>
	</div>
</form>
	
<script type="text/javascript">
	var caEditableResultsComplexDataFormHandler = caUI.initQuickAddFormHandler({
		formID: 'caEditableResultsComplexDataForm',
		formErrorsPanelID: 'caEditableResultsComplexDataFormErrors',
		formTypeSelectID: null, 
	
		formUrl: <?= json_encode(caNavUrl($this->request, '*', '*', 'resultsComplexDataEditor')); ?>,
		fileUploadUrl: <?= json_encode(caNavUrl($this->request, "*", "*", "saveResultsEditorFiles")); ?>,
		saveUrl: <?= json_encode(caNavUrl($this->request, "*", "*", "saveResultsEditorData")); ?>,
		csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>,
	
		headerText: <?= json_encode(_t('Edit %1', $t_subject->getTypeName())); ?>,
		saveText: <?= json_encode(_t('Updated %1 ', $t_subject->getTypeName())." <em>%1</em>"); ?>,
		busyIndicator: <?= json_encode(caBusyIndicatorIcon($this->request)); ?>,
		onSave: function(resp) { 
			if (resp.status == 0) {
				var ht = jQuery("#caResultsEditorWrapper .caResultsEditorContent").data('handsontable');
				ht.setDataAtCell(<?= (int)$row; ?>, <?= (int)$col; ?>, resp.display, 'external');
				if (jQuery("#caEditableResultsComplexDataForm") && jQuery("#caEditableResultsComplexDataForm").parent() && jQuery("#caEditableResultsComplexDataForm").parent().parent() && jQuery("#caEditableResultsComplexDataForm").parent().parent().data("panel")) { jQuery("#caEditableResultsComplexDataForm").parent().parent().data("panel").hidePanel();  }
				jQuery(".caResultsEditorStatus").html("Saved changes").show();
				setTimeout(function() { jQuery('.caResultsEditorStatus').fadeOut(500); }, 5000);
			} else {
				caEditableResultsComplexDataFormHandler.setErrors(resp.errors);
				jQuery("#caEditableResultsComplexDataForm input[name=form_timestamp]").val(resp.time);	// update form timestamp
			}
		}
	});
</script>	
