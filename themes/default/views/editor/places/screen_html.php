<?php
/* ----------------------------------------------------------------------
 * app/views/editor/places/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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
 	$t_place 			= $this->getVar('t_subject');
	$vn_place_id 		= $this->getVar('subject_id');
	$vn_above_id 		= $this->getVar('above_id');
	$vn_after_id 		= $this->getVar('after_id');
	$vs_context_id 		= $this->getVar('_context_id');	// used to restrict idno uniqueness checking to within the current list

	$vb_can_edit	 	= $t_place->isSaveable($this->request);
	$vb_can_delete		= $t_place->isDeletable($this->request);

	$vs_rel_table		= $this->getVar('rel_table');
	$vn_rel_type_id		= $this->getVar('rel_type_id');
	$vn_rel_id			= $this->getVar('rel_id');

	if ($vb_can_edit) {
		$va_cancel_parameters = ($vn_place_id ? array('place_id' => $vn_place_id) : array('type_id' => $t_place->getTypeID()));
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'PlaceEditorForm').' '.
			($this->getVar('show_save_and_return') ? caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save and return"), 'PlaceEditorForm', array('isSaveAndReturn' => true)) : '').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'editor/places', 'PlaceEditor', 'Edit/'.$this->request->getActionExtra(), $va_cancel_parameters),
			'', 
			((intval($vn_place_id) > 0) && $vb_can_delete) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'editor/places', 'PlaceEditor', 'Delete/'.$this->request->getActionExtra(), array('place_id' => $vn_place_id)) : ''
		);
	}	
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/place_id/'.$vn_place_id, 'PlaceEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_place->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'PlaceEditorForm',
									'context_id' => $vs_context_id
								), $va_bundle_list);
									
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='_context_id' value='<?php print $this->getVar('_context_id'); ?>'/>
			<input type='hidden' name='place_id' value='<?php print $vn_place_id; ?>'/>
			<input type='hidden' name='above_id' value='<?php print $vn_above_id; ?>'/>
			<input id='isSaveAndReturn' type='hidden' name='is_save_and_return' value='0'/>
			<input type='hidden' name='rel_table' value='<?php print $vs_rel_table; ?>'/>
			<input type='hidden' name='rel_type_id' value='<?php print $vn_rel_type_id; ?>'/>
			<input type='hidden' name='rel_id' value='<?php print $vn_rel_id; ?>'/>
			<input type='hidden' name='after_id' value='<?php print $vn_after_id; ?>'/>
<?php
			if($this->request->getParameter('rel', pInteger)) {
?>
				<input type='hidden' name='rel' value='1'/>
<?php
			}
?>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_place, $va_bundle_list); ?>