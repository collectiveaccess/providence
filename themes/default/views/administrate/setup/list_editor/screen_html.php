<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/list_editor_screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2026 Whirl-i-Gig
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
$t_list 		= $this->getVar('t_subject');
$list_id 		= $this->getVar('subject_id');

$control_box 	= caEditorFormControls($this, 'ListEditorForm');

print $control_box;
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/list_id/'.$list_id, 'ListEditorForm', null, 'POST', 'multipart/form-data');
		
		$form_elements = $t_list->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
								'request' => $this->request, 
								'formName' => 'ListEditorForm'), $bundle_list);
		
		print join("\n", $form_elements);
		
		print $control_box;
?>
		<input type='hidden' name='list_id' value='<?= $list_id; ?>'/>
	</form>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<?= caEditorFieldList($this->request, $t_list, $bundle_list); ?>
