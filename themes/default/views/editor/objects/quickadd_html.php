<?php
/* ----------------------------------------------------------------------
 * app/views/editor/objects/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2017 Whirl-i-Gig
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
 
 	global $g_ui_locale_id;
 
 	$t_subject 			= $this->getVar('t_subject');
	$vn_subject_id 		= $this->getVar('subject_id');
	
	$va_restrict_to_types = $this->getVar('restrict_to_types');
	
	$vs_field_name_prefix = $this->getVar('field_name_prefix');
	$vs_n 				= $this->getVar('n');
	$vs_q				= caUcFirstUTF8Safe($this->getVar('q'));

	$vb_can_edit	 	= $t_subject->isSaveable($this->request);
	
	$vs_form_name = "ObjectQuickAddForm";
?>	
<script type="text/javascript">
	var caQuickAddFormHandler = caUI.initQuickAddFormHandler({
		formID: '<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>',
		formErrorsPanelID: '<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>',
		formTypeSelectID: '<?php print $vs_form_name; ?>TypeID<?php print $vs_field_name_prefix.$vs_n; ?>', 
		
		formUrl: '<?php print caNavUrl($this->request, 'editor/objects', 'ObjectQuickAdd', 'Form'); ?>',
		fileUploadUrl: '<?php print caNavUrl($this->request, "editor/objects", "ObjectEditor", "UploadFiles"); ?>',
		saveUrl: '<?php print caNavUrl($this->request, "editor/objects", "ObjectQuickAdd", "Save"); ?>',
		
		headerText: '<?php print addslashes(_t('Quick add %1', $t_subject->getTypeName())); ?>',
		saveText: '<?php print addslashes(_t('Created %1 ', $t_subject->getTypeName())); ?> <em>%1</em>',
		busyIndicator: '<?php print addslashes(caBusyIndicatorIcon($this->request)); ?>'
	});
</script>	
<form action="#" class="quickAddSectionForm" name="<?php print $vs_form_name; ?>" method="POST" enctype="multipart/form-data" id="<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>">
	<div class='quickAddDialogHeader'><?php 
		print "<div class='quickAddTypeList'>"._t('Quick Add %1', $t_subject->getTypeListAsHTMLFormElement('change_type_id', array('id' => "{$vs_form_name}TypeID{$vs_field_name_prefix}{$vs_n}", 'onchange' => "caQuickAddFormHandler.switchForm();"), array('value' => $t_subject->get('type_id'), 'restrictToTypes' => $va_restrict_to_types)))."</div>"; 
		if ($vb_can_edit) {
			print "<div class='quickAddControls'>".caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Add %1", $t_subject->getTypeName()), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caQuickAddFormHandler.save(event);"))
			.' '.caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "jQuery(\"#{$vs_form_name}".$vs_field_name_prefix.$vs_n."\").parent().data(\"panel\").hidePanel();"))."</div>\n";
		}
		print "<div class='quickAddProgress'></div><br style='clear: both;'/>";
?>
	</div>
	
	<div class="quickAddFormTopPadding"><!-- empty --></div>
	<div class="quickAddErrorContainer" id="<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>"> </div>
	<div class="quickAddSectionBox" id="{$vs_form_name}Container<?php print $vs_field_name_prefix.$vs_n; ?>">
<?php			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
					'request' => $this->request, 
					'restrictToTypes' => array($t_subject->get('type_id')),
					'formName' => $vs_form_name.$vs_field_name_prefix.$vs_n,
					'forceLabelForNew' => $this->getVar('forceLabel')						// force query text to be default in label fields
			));
			
			print join("\n", $va_form_elements);
?>
		<input type='hidden' name='_formName' value='<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>'/>
		<input type='hidden' name='q' value='<?php print htmlspecialchars($vs_q, ENT_QUOTES, 'UTF-8'); ?>'/>
		<input type='hidden' name='screen' value='<?php print htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='types' value='<?php print htmlspecialchars(is_array($va_restrict_to_types) ? join(',', $va_restrict_to_types) : ''); ?>'/>
	</div>
</form>