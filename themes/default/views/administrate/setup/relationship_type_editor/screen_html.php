<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/relationship_type_editor/screen_html.php : 
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
$t_item 		= $this->getVar('t_subject');
$type_id 		= $this->getVar('subject_id');
$parent_id 		= $this->getVar('parent_id');
$above_id 		= $this->getVar('above_id');
$after_id 		= $this->getVar('after_id');
$context_id 	= $this->getVar('_context_id');	// used to restrict idno uniqueness checking to within the current list

$t_ui = $this->getVar('t_ui');

$can_edit	 	= $t_item->isSaveable($this->request);
$can_delete		= $t_item->isDeletable($this->request);
if ($can_edit) {
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'RelationshipTypeEditorForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/setup/relationship_type_editor', 'RelationshipTypeEditor', 'Edit/'.$this->request->getActionExtra(), ['type_id' => $type_id, 'parent_id' => $parent_id]), 
		'', 
		(intval($type_id) > 0) ? caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/setup/relationship_type_editor', 'RelationshipTypeEditor', 'Delete/'.$this->request->getActionExtra(), array('type_id' => $type_id)) : ''
	);
}
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/type_id/'.$type_id, 'RelationshipTypeEditorForm', null, 'POST', 'multipart/form-data');
			
			$form_elements = $t_item->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'RelationshipTypeEditorForm'), $bundle_list);
			
			print join("\n", $form_elements);
			
			if ($can_edit) { print $control_box; }
?>
			<input type='hidden' name='_context_id' value='<?= $this->getVar('_context_id'); ?>'/>
			<input type='hidden' name='type_id' value='<?= $type_id; ?>'/>
			<input type='hidden' name='above_id' value='<?= $above_id; ?>'/>
			<input type='hidden' name='after_id' value='<?= $after_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?= caSetupEditorScreenOverlays($this->request, $t_item, $bundle_list); ?>