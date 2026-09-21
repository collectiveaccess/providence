<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/interface_editor/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2026 Whirl-i-Gig
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
$t_subject 		= $this->getVar('t_subject');
$subject_id 	= $this->getVar('subject_id');

$t_ui 			= $this->getVar('t_ui');

$control_box 	= caEditorFormControls($this, 'InterfaceEditorForm');

print $control_box;
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/ui_id/'.$subject_id, 'InterfaceEditorForm', null, 'POST', 'multipart/form-data');
		
		$form_elements = $t_subject->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
								'request' => $this->request, 
								'formName' => 'InterfaceEditorForm'));
		
		print join("\n", $form_elements);
		
		print $control_box;
?>
		<input type='hidden' name='editor_type' value='<?= $t_subject->get('editor_type'); ?>'/>
		<input type='hidden' name='ui_id' value='<?= $subject_id; ?>'/>
	</form>
</div>

<div class="editorBottomPadding"><!-- empty --></div>
