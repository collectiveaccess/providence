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
$t_page 		= $this->getVar('t_subject');
$page_id 		= $this->getVar('subject_id');
$can_edit	 	= $t_page->isSaveable($this->request);
$can_delete		= $t_page->isDeletable($this->request);

$control_box 	= caEditorFormControls($this, 'SitePageEditorForm');

$template_id	= $this->request->getParameter('template_id', pInteger);
$t_ui 			= $this->getVar('t_ui');	

print $control_box;
?>
<div class="sectionBox">
<?php
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/page_id/'.$page_id, 'SitePageEditorForm', null, 'POST', 'multipart/form-data');
	
	if (!$page_id) {
		// For new pages, show mandatory fields...
		// ... BUT ...
		// if template_id is page on the url then create a hidden element rather than show it as a mandatory field
		// This allows us to set the content type for the set from the calling control
		$mandatory_fields = $t_page->getMandatoryFields();
		
		if (($index = array_search('template_id', $mandatory_fields)) !== false) {
			if ($pn_template_id > 0) {
				$t_page->set('template_id', $pn_template_id);
				
				print caHTMLHiddenInput('template_id', array('value' => $pn_template_id));
				unset($form_elements['template_id']);
				unset($mandatory_fields[$index]);
			}
		}
	}
	
	$form_elements = $t_page->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
							'request' => $this->request, 
							'formName' => 'SitePageEditorForm'));
										
	print join("\n", $form_elements);
	
	print $control_box;
?>
		<input type='hidden' name='page_id' value='<?= $page_id; ?>'/>
	</form>

	<div class="editorBottomPadding"><!-- empty --></div>
</div>

<div class="editorBottomPadding"><!-- empty --></div>
