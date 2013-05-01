<?php
/* ----------------------------------------------------------------------
 * app/views/editor/object_lots/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source object_lots management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 	$t_lot 				= $this->getVar('t_subject');
	$vn_lot_id 			= $this->getVar('subject_id');

	$vb_can_edit	 	= $t_lot->isSaveable($this->request);
	$vb_can_delete		= $t_lot->isDeletable($this->request);
	
	if ($vb_can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ObjectLotEditorForm').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'editor/object_lots', 'ObjectLotEditor', 'Edit/'.$this->request->getActionExtra(), array('lot_id' => $vn_lot_id)),
			'', 
			((intval($vn_lot_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'editor/object_lots', 'ObjectLotEditor', 'Delete/'.$this->request->getActionExtra(), array('lot_id' => $vn_lot_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/lot_id/'.$vn_lot_id, 'ObjectLotEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_lot->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'ObjectLotEditorForm'), $va_bundle_list);
									
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='lot_id' value='<?php print $vn_lot_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_lot, $va_bundle_list); ?>