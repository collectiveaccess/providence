<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/list_item_editor_screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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
 	$t_item 			= $this->getVar('t_subject');
	$vn_item_id 		= $this->getVar('subject_id');
	$vn_above_id 		= $this->getVar('above_id');

	$vb_can_edit	 	= $t_item->isSaveable($this->request);
	$vb_can_delete		= $t_item->isDeletable($this->request);
	
	$vs_context_id 		= $this->getVar('_context_id');	// used to restrict idno uniqueness checking to within the current list
	
	
	if($vb_can_edit){
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ListItemEditorForm').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup/list_item_editor', 'ListItemEditor', 'Edit/'.$this->request->getActionExtra(), array('item_id' => $vn_item_id)), 
			'', 
			((intval($vn_item_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup/list_item_editor', 'ListItemEditor', 'Delete/'.$this->request->getActionExtra(), array('item_id' => $vn_item_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php
			if(!$vb_can_edit){

?>
				<div class='notification-warning-box'>
					<ul class='notification-warning-box'>
						<li class='notification-info-box'><?php print ((intval($vn_item_id) == 0) ? _t("You are not allowed to add items to this list") : _t("You are not allowed to edit items in this list") );?></li>
					</ul>
				</div>
<?php

			} 

			if((intval($vn_item_id) > 0) || $vb_can_edit){

				print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/item_id/'.$vn_item_id, 'ListItemEditorForm', null, 'POST', 'multipart/form-data');
				
					$va_form_elements = $t_item->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
											'request' => $this->request, 
											'formName' => 'ListItemEditorForm',
											'context_id' => $vs_context_id
										), $va_bundle_list);
					
					print join("\n", $va_form_elements);
					
					if($vb_can_edit) { print $vs_control_box; }
?>
					<input type='hidden' name='_context_id' value='<?php print $this->getVar('_context_id'); ?>'/>
					<input type='hidden' name='item_id' value='<?php print $vn_item_id; ?>'/>
					<input type='hidden' name='above_id' value='<?php print $vn_above_id; ?>'/>

				</form>
<?php
			}
?>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caEditorFieldList($this->request, $va_bundle_list); ?>