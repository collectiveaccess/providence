<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/bundle_mapping_group_editor/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 	$t_subject = $this->getVar('t_subject');
	$vn_subject_id = $this->getVar('subject_id');
	
	$t_group = $this->getVar('t_group');
	
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'MappingGroupEditorForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup/bundle_mapping_group_editor', 'MappingGroupEditor', 'Edit/'.$this->request->getActionExtra(), array('group_id' => $vn_subject_id)), 
		'', 
		(intval($vn_subject_id) > 0) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup/bundle_mapping_group_editor', 'MappingGroupEditor', 'Delete/'.$this->request->getActionExtra(), array('group_id' => $vn_subject_id)) : ''
	);
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/group_id/'.$vn_subject_id, 'MappingGroupEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'MappingGroupEditorForm'));
			
			print join("\n", $va_form_elements);
			
			print $vs_control_box;
?>
			<input type='hidden' name='group_id' value='<?php print $vn_subject_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>