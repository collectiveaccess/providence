<?php
/* ----------------------------------------------------------------------
 * app/views/manage/sets/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source sets management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	$t_set = $this->getVar('t_subject');
	$vn_set_id = $this->getVar('subject_id');
	$can_delete = $this->getVar('can_delete');
	
	$t_ui = $this->getVar('t_ui');	
?>
	<div class="sectionBox">
<?php
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'SetEditorForm').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'manage/sets', 'SetEditor', 'Edit/'.$this->request->getActionExtra(), array('set_id' => $vn_set_id)), 
			'', 
			((intval($vn_set_id) > 0) && ($can_delete)) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage/sets', 'SetEditor', 'Delete/'.$this->request->getActionExtra(), array('set_id' => $vn_set_id)) : ''
		);
		
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/set_id/'.$vn_set_id, 'SetEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_set->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'SetEditorForm'));
									
			if (!$vn_set_id) {
				// For new sets, show mandatory fields...
				// ... BUT ...
				// if table_num is set on the url then create a hidden element rather than show it as a mandatory field
				// This allows us to set the content type for the set from the calling control
				$va_mandatory_fields = $t_set->getMandatoryFields();
				if (($vn_index = array_search('table_num', $va_mandatory_fields)) !== false) {
					if (($vn_table_num = $t_set->get('table_num')) > 0) {
						print caHTMLHiddenInput('table_num', array('value' => $vn_table_num));
						unset($va_form_elements['table_num']);
						unset($va_mandatory_fields[$vn_index]);
					}
				}
			}
			
			print join("\n", $va_form_elements);
			
			print $vs_control_box;
?>
			<input type='hidden' name='set_id' value='<?php print $vn_set_id; ?>'/>
		</form>
	
		<div class="editorBottomPadding"><!-- empty --></div>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
