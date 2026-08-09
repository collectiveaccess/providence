<?php
/* ----------------------------------------------------------------------
 * app/views/manage/set_items_items/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source sets management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2026 Whirl-i-Gig
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
$t_set_item 	= $this->getVar('t_subject');
$item_id 		= $this->getVar('subject_id');

$control_box 	= caEditorFormControls($this, 'SetItemEditorForm');
$t_ui 			= $this->getVar('t_ui');

$forced_values 	= $this->getVar('forced_values') ?? [];

print $control_box;
?>
<div class="sectionBox">
<?php
	print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/item_id/'.$item_id, 'SetItemEditorForm', null, 'POST', 'multipart/form-data');
	
	$form_elements = $t_set_item->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
							'request' => $this->request, 
							'formName' => 'SetItemEditorForm',
							'forcedValues' => $forced_values));
									
	print join("\n", $form_elements);
	
	print $control_box;
?>
	<input type='hidden' name='item_id' value='<?= $item_id; ?>'/>
</form>
</div>
