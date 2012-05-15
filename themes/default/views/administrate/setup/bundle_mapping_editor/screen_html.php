<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/bundle_mapping_editor/screen_html.php : 
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
	
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'MappingEditorForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup/bundle_mapping_editor', 'BundleMappingEditor', 'Edit/'.$this->request->getActionExtra(), array('mapping_id' => $vn_subject_id)), 
		'', 
		(intval($vn_subject_id) > 0) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup/bundle_mapping_editor', 'BundleMappingEditor', 'Delete/'.$this->request->getActionExtra(), array('mapping_id' => $vn_subject_id)) : ''
	);
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/mapping_id/'.$vn_subject_id, 'MappingEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'MappingEditorForm'));
			
			print join("\n", $va_form_elements);
			
			print $vs_control_box;
?>
			<input type='hidden' name='mapping_id' value='<?php print $vn_subject_id; ?>'/>
<?php
	if(!$t_subject->getPrimaryKey()) {
?>
			<input type='hidden' name='target' value='<?php print $t_subject->get('target'); ?>'/>
			<input type='hidden' name='direction' value='<?php print $t_subject->get('direction'); ?>'/>
			<input type='hidden' name='table_num' value='<?php print $t_subject->get('table_num'); ?>'/>
<?php
	}
?>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>