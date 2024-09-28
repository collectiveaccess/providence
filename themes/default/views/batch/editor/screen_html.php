<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2023 Whirl-i-Gig
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
$t_subject 			= $this->getVar('t_subject');
$rs	 				= $this->getVar('record_selection');
$id	 				= $this->getVar('id');

print $vs_control_box = caFormControlBox(
	caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Execute batch edit"), 'caBatchEditorFormButton', ['onclick' => 'caConfirmBatchExecutionPanel.showPanel(); return false;']).' '.
	caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'batch', 'Editor', 'Edit/'.$this->request->getActionExtra(), ['id' => $id]),
	'', 
	''
);
?>
<div class="sectionBox">
<?php
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'caBatchEditorForm', null, 'POST', 'multipart/form-data', '_top', ['noTimestamp' => true]);
	
		$va_bundle_list = [];
		$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->request->getActionExtra(), [
								'request' => $this->request, 
								'formName' => 'caBatchEditorForm',
								'batch' => true,
								'restrictToTypes' => array_keys($rs->getTypesForItems(array('includeParents' => true))),
								'ui_instance' => $this->getVar('t_ui'),
								'id' => $id,
								'recordSet' => $this->getVar('recordSet')
							], $va_bundle_list);
							
		print join("\n", $va_form_elements);
		print $vs_control_box; 
?>
		<?= caHTMLHiddenInput('id', ['value' => $id]); ?>
		<?= $this->render("editor/confirm_html.php"); ?>
	</form>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<?= caSetupEditorScreenOverlays($this->request, $t_subject, $va_bundle_list); ?>
