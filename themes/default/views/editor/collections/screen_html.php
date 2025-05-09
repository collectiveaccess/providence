<?php
/* ----------------------------------------------------------------------
 * app/views/editor/collections/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
$t_collection 		= $this->getVar('t_subject');
$collection_id 	= $this->getVar('subject_id');
$above_id 		= $this->getVar('above_id');
$after_id 		= $this->getVar('after_id');

$can_edit	 	= $t_collection->isSaveable($this->request);
$can_delete		= $t_collection->isDeletable($this->request);

$rel_table		= $this->getVar('rel_table');
$rel_type_id		= $this->getVar('rel_type_id');
$rel_id			= $this->getVar('rel_id');

$forced_values 		= $this->getVar('forced_values') ?? [];

if ($can_edit) {
	$cancel_parameters = ($collection_id ? array('collection_id' => $collection_id) : array('type_id' => $t_collection->getTypeID()));
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'CollectionEditorForm').' '.
		($this->getVar('show_save_and_return') ? caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save and return"), 'CollectionEditorForm', array('isSaveAndReturn' => true)) : '').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'editor/collections', 'CollectionEditor', 'Edit/'.$this->request->getActionExtra(), $cancel_parameters),
		($this->getVar('show_show_notifications') ? caFormJSButton($this->request, __CA_NAV_ICON_ALERT__, _t("Show editor alerts"), '', ['class' => 'caEditorFormNotifications']) : ''), 
		((intval($collection_id) > 0) && $can_delete) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'editor/collections', 'CollectionEditor', 'Delete/'.$this->request->getActionExtra(), array('collection_id' => $collection_id)) : ''
	);
}
?>
<div class="sectionBox">
<?php
		print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/collection_id/'.$collection_id, 'CollectionEditorForm', null, 'POST', 'multipart/form-data');
		
		$bundle_list = [];
		$form_elements = $t_collection->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
								'request' => $this->request, 
								'formName' => 'CollectionEditorForm',
								'forcedValues' => $forced_values), $bundle_list);
											
		print join("\n", $form_elements);
		
		if ($can_edit) { print $control_box; }
?>
		<input type='hidden' name='collection_id' value='<?= $collection_id; ?>'/>
		<input type='hidden' name='above_id' value='<?= $above_id; ?>'/>
		<input id='isSaveAndReturn' type='hidden' name='is_save_and_return' value='0'/>
		<input type='hidden' name='rel_table' value='<?= $rel_table; ?>'/>
		<input type='hidden' name='rel_type_id' value='<?= $rel_type_id; ?>'/>
		<input type='hidden' name='rel_id' value='<?= $rel_id; ?>'/>
		<input type='hidden' name='after_id' value='<?= $after_id; ?>'/>
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

<?= caSetupEditorScreenOverlays($this->request, $t_collection, $bundle_list); ?>