<?php
/* ----------------------------------------------------------------------
 * app/views/editor/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 	$t_stop 			= $this->getVar('t_subject');
	$vn_stop_id 		= $this->getVar('subject_id');
	$vn_above_id 		= $this->getVar('above_id');

	$vb_can_edit	 	= $t_stop->isSaveable($this->request);
	$vb_can_delete		= $t_stop->isDeletable($this->request);
	
	$t_ui 				= $this->getVar('t_ui');
	$vs_context_id 		= $this->getVar('_context_id');	// used to restrict idno uniqueness checking to within the current list
	
	if ($vb_can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'TourStopEditorForm').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'editor/tour_stops', 'TourStopEditor', 'Edit/'.$this->request->getActionExtra(), array('stop_id' => $vn_stop_id)), 
			'', 
			((intval($vn_stop_id) > 0) && ($vb_can_delete)) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'editor/tour_stops', 'TourStopEditor', 'Delete/'.$this->request->getActionExtra(), array('stop_id' => $vn_stop_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/stop_id/'.$vn_stop_id, 'TourStopEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_stop->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'TourStopEditorForm',
									'context_id' => $vs_context_id
								), $va_bundle_list);
			
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='_context_id' value='<?php print $this->getVar('_context_id'); ?>'/>
			<input type='hidden' name='stop_id' value='<?php print $vn_stop_id; ?>'/>
			<input type='hidden' name='above_id' value='<?php print $vn_above_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_stop, $va_bundle_list); ?>