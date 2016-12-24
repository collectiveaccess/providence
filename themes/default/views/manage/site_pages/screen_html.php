<?php
/* ----------------------------------------------------------------------
 * app/views/manage/site_pages/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 	$t_page 			= $this->getVar('t_subject');
	$vn_page_id 		= $this->getVar('subject_id');
	$vb_can_edit	 	= $t_page->isSaveable($this->request);
	$vb_can_delete		= $t_page->isDeletable($this->request);
	
	$pn_template_id		= $this->request->getParameter('template_id', pInteger);
	
	$t_ui = $this->getVar('t_ui');	
?>
	<div class="sectionBox">
<?php

		if ($vb_can_edit) {
			print $vs_control_box = caFormControlBox(
				caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'SitePageEditorForm').' '.
				caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'manage/site_pages', 'SitePageEditor', 'Edit/'.$this->request->getActionExtra(), array('page_id' => $vn_page_id)), 
				'', 
				((intval($vn_page_id) > 0) && ($vb_can_delete)) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), 'deleteButton form-button', 'manage/site_pages', 'SitePageEditor', 'Delete/'.$this->request->getActionExtra(), array('page_id' => $vn_page_id)) : ''
			);
		}
		
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/page_id/'.$vn_page_id, 'SitePageEditorForm', null, 'POST', 'multipart/form-data');
			
			if (!$vn_page_id) {
				// For new pages, show mandatory fields...
				// ... BUT ...
				// if template_id is page on the url then create a hidden element rather than show it as a mandatory field
				// This allows us to set the content type for the set from the calling control
				$va_mandatory_fields = $t_page->getMandatoryFields();
				
				if (($vn_index = array_search('template_id', $va_mandatory_fields)) !== false) {
					if ($pn_template_id > 0) {
						$t_page->set('template_id', $pn_template_id);
						
						print caHTMLHiddenInput('template_id', array('value' => $pn_template_id));
						unset($va_form_elements['template_id']);
						unset($va_mandatory_fields[$vn_index]);
					}
				}
			}
			
			$va_form_elements = $t_page->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'SitePageEditorForm'));
												
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) {
				print $vs_control_box;
			}
?>
			<input type='hidden' name='page_id' value='<?php print $vn_page_id; ?>'/>
		</form>
	
		<div class="editorBottomPadding"><!-- empty --></div>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
