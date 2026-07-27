<?php
/* ----------------------------------------------------------------------
 * app/views/editor/object_lots/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source object_lots management software
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
$t_lot 			= $this->getVar('t_subject');
$lot_id 		= $this->getVar('subject_id');

$rel_table		= $this->getVar('rel_table');
$rel_type_id	= $this->getVar('rel_type_id');
$rel_id			= $this->getVar('rel_id');

$forced_values 	= $this->getVar('forced_values') ?? [];
$control_box 	= caEditorFormControls($this, 'ObjectLotEditorForm');

print $control_box;
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/lot_id/'.$lot_id, 'ObjectLotEditorForm', null, 'POST', 'multipart/form-data');
		
		$form_elements = $t_lot->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
								'request' => $this->request, 
								'formName' => 'ObjectLotEditorForm',
								'forcedValues' => $forced_values), $bundle_list);
								
		print join("\n", $form_elements);
		
		print $control_box;
?>
		<input type='hidden' name='lot_id' value='<?= $lot_id; ?>'/>
		<input id='isSaveAndReturn' type='hidden' name='is_save_and_return' value='0'/>
		<input type='hidden' name='rel_table' value='<?= $rel_table; ?>'/>
		<input type='hidden' name='rel_type_id' value='<?= $rel_type_id; ?>'/>
		<input type='hidden' name='rel_id' value='<?= $rel_id; ?>'/>
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

<?= caSetupEditorScreenOverlays($this->request, $t_lot, $bundle_list); ?>
