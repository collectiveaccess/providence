<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/list_item_editor_screen_html.php : 
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
$item_id 		= $this->getVar('subject_id');
$above_id 		= $this->getVar('above_id');
$after_id 		= $this->getVar('after_id');

$can_edit	 	= $t_item->isSaveable($this->request);

$forced_values 	= $this->getVar('forced_values') ?? [];
$context_id 	= $this->getVar('_context_id');	// used to restrict idno uniqueness checking to within the current list

$control_box 	= caEditorFormControls($this, 'ListItemEditorForm');

print $control_box;
?>
<div class="sectionBox">
<?php
	if(!$can_edit){
?>
		<div class='notification-warning-box'>
			<ul class='notification-warning-box'>
				<li class='notification-info-box'><?= ((intval($item_id) == 0) ? _t("You are not allowed to add items to this list") : _t("You are not allowed to edit items in this list") );?></li>
			</ul>
		</div>
<?php
	} 

	if((intval($item_id) > 0) || $can_edit){

		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/item_id/'.$item_id, 'ListItemEditorForm', null, 'POST', 'multipart/form-data');
		
			$form_elements = $t_item->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
							'request' => $this->request, 
							'formName' => 'ListItemEditorForm',
							'context_id' => $context_id,
							'forcedValues' => $forced_values
						), $bundle_list);
			
			print join("\n", $form_elements);
			
			print $control_box;
?>
			<input type='hidden' name='_context_id' value='<?= $this->getVar('_context_id'); ?>'/>
			<input type='hidden' name='item_id' value='<?= $item_id; ?>'/>
			<input type='hidden' name='above_id' value='<?= $above_id; ?>'/>
			<input type='hidden' name='after_id' value='<?= $after_id; ?>'/>

		</form>
<?php
	}
?>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<?= caEditorFieldList($this->request, $t_item, $bundle_list); ?>
