<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/data_dictionary_entries/screen_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source sets management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2026 Whirl-i-Gig
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
$t_alert 		= $this->getVar('t_subject');
$entry_id 		= $this->getVar('subject_id');
$table_num 		= $t_alert->get("table_num"); 

$control_box 	= caEditorFormControls($this, 'DataDictionaryEntryEditorForm');

$t_ui = $this->getVar('t_ui');	

print $control_box;
?>
<div class="sectionBox">
<?php
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/entry_id/'.$entry_id, 'DataDictionaryEntryEditorForm', null, 'POST', 'multipart/form-data');
	
	$form_elements = $t_alert->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
							'request' => $this->request, 
							'formName' => 'DataDictionaryEntryEditorForm'));
	
	if (!$entry_id) {
		// For new forms, show mandatory fields...
		// ... BUT ...
		// if table_num is set on the url then create a hidden element rather than show it as a mandatory field
		// This allows us to set the content type for the form from the calling control
		$mandatory_fields = $t_alert->getMandatoryFields();
		if (($index = array_search('table_num', $mandatory_fields)) !== false) {
			if ($table_num) {
				print caHTMLHiddenInput('table_num', array('value' => $table_num));
				unset($form_elements['table_num']);
				unset($mandatory_fields[$index]);
			}
		}
	}
	
	print join("\n", $form_elements);
	
	print $control_box;
?>
		<input type='hidden' name='table_num' value='<?= $table_num; ?>'/>
		<input type='hidden' name='entry_id' value='<?= $entry_id; ?>'/>
	</form>

	<div class="editorBottomPadding"><!-- empty --></div>
</div>

<div class="editorBottomPadding"><!-- empty --></div>
