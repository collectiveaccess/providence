<?php
/* ----------------------------------------------------------------------
 * views/editor/object_events/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
	
 	$t_object_event 	= $this->getVar('t_subject');
	$vn_event_id 		= $this->getVar('subject_id');
	$t_ui 				= $this->getVar('t_ui');

	$vb_can_edit	 	= $t_object_event->isSaveable($this->request);
	$vb_can_delete		= $t_object_event->isDeletable($this->request);
		
	//get a screen name from the type_id
	$type_list_item = new ca_list_items($this->request->getParameter('type_id',1));
	
	$screens = $t_ui->getScreens($this->request);
	foreach($screens as $screen) {
		if($screen['idno'] == $type_list_item->get('idno')) {
			$screen_from_type = 'Screen'.$screen['screen_id'];
		}
	}
	$screen_name = $screen_from_type ? $screen_from_type : $this->request->getActionExtra();
	
	if ($vb_can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ObjectEventEditorForm').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'editor/object_events', 'ObjectEventEditor', 'Edit/'.$this->request->getActionExtra(), array('event_id' => $vn_event_id)),
			'', 
			((intval($vn_event_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'editor/object_events', 'ObjectEventEditor', 'Delete/'.$this->request->getActionExtra(), array('event_id' => $vn_event_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/event_id/'.$vn_event_id, 'ObjectEventEditorForm', null, 'POST', 'multipart/form-data');
		
			$va_form_elements = $t_object_event->getBundleFormHTMLForScreen($screen_name, array(
									'request' => $this->request, 
									'formName' => 'ObjectEventEditorForm'));
			
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='event_id' value='<?php print $vn_event_id; ?>'/>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>