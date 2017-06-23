<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2017 Whirl-i-Gig
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
	
	$t_set	 			= $this->getVar('t_set');
	$vn_set_id	 		= $this->getVar('set_id');
	
	print $vs_control_box = caFormControlBox(
		caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Execute batch edit"), 'caBatchEditorFormButton', array('onclick' => 'caConfirmBatchExecutionPanel.showPanel(); return false;')).' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'batch', 'Editor', 'Edit/'.$this->request->getActionExtra(), array('set_id' => $vn_set_id)),
		'', 
		''
	);
?>
	<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra(), 'caBatchEditorForm', null, 'POST', 'multipart/form-data', '_top', array('noTimestamp' => true));
		
			$va_bundle_list = array();
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'caBatchEditorForm',
									'batch' => true,
									'restrictToTypes' => array_keys($t_set->getTypesForItems(array('includeParents' => true))),
									'ui_instance' => $this->getVar('t_ui'),
									'set_id' => $vn_set_id
								), $va_bundle_list);
								
			print join("\n", $va_form_elements);
			
			print $vs_control_box; 
?>
			<input type='hidden' name='set_id' value='<?php print $vn_set_id; ?>'/>
<?php 
			print $this->render("editor/confirm_html.php");
?>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_subject, $va_bundle_list); ?>
