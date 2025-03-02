<?php
/* ----------------------------------------------------------------------
 * app/views/editor/occurrences/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
$subject_id 		= $this->getVar('subject_id');

$restrict_to_types = $this->getVar('restrict_to_types');

$field_name_prefix = $this->getVar('field_name_prefix');
$n 				= $this->getVar('n');
$q				= caUcFirstUTF8Safe($this->getVar('q'));

$vb_can_edit	 	= $t_subject->isSaveable($this->request);

$form_name = "OccurrenceQuickAddForm";
?>	
<script type="text/javascript">
	var caQuickAddFormHandler = caUI.initQuickAddFormHandler({
		formID: '<?= $form_name.$field_name_prefix.$n; ?>',
		formErrorsPanelID: '<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>',
		formTypeSelectID: '<?= $form_name; ?>TypeID<?= $field_name_prefix.$n; ?>', 
		
		formUrl: '<?= caNavUrl($this->request, 'editor/occurrences', 'OccurrenceQuickAdd', 'Form'); ?>',
		fileUploadUrl: '<?= caNavUrl($this->request, "editor/occurrences", "OccurrenceEditor", "UploadFiles"); ?>',
		saveUrl: '<?= caNavUrl($this->request, "editor/occurrences", "OccurrenceQuickAdd", "Save"); ?>',
		
		headerText: '<?= addslashes(_t('Quick add %1', $t_subject->getTypeName())); ?>',
		saveText: '<?= addslashes(_t('Created %1 ', $t_subject->getTypeName())); ?> <em>%1</em>',
		busyIndicator: '<?= addslashes(caBusyIndicatorIcon($this->request)); ?>'
	});
</script>	
<form action="#" class="quickAddSectionForm" name="<?= $form_name; ?>" method="POST" enctype="multipart/form-data" id="<?= $form_name.$field_name_prefix.$n; ?>">
	<div class='quickAddDialogHeader'><?php 
		print "<div class='quickAddTypeList'>"._t('Quick Add %1', $t_subject->getTypeListAsHTMLFormElement('change_type_id', array('id' => "{$form_name}TypeID{$field_name_prefix}{$n}", 'onchange' => "caQuickAddFormHandler.switchForm();"), array('value' => $t_subject->get('type_id'), 'restrictToTypes' => $restrict_to_types)))."</div>"; 
		if ($vb_can_edit) {
			print "<div class='quickAddControls'>".caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Add %1", $t_subject->getTypeName()), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "caQuickAddFormHandler.save(event);"))
			.' '.caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), "{$form_name}{$field_name_prefix}{$n}", ["onclick" => "caQuickAddFormHandler.cancel(event);"])."</div>\n";
		}
		print "<div class='quickAddProgress'></div><br style='clear: both;'/>";
?>
	</div>
	
	<div class="quickAddFormTopPadding"><!-- empty --></div>
	<div class="quickAddErrorContainer" id="<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>"> </div>
	<div class="quickAddSectionBox" id="<?= $form_name.'Container'.$field_name_prefix.$n; ?>">
<?php
			$form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
				'request' => $this->request, 
				'restrictToTypes' => array($t_subject->get('type_id')),
				'formName' => $form_name.$field_name_prefix.$n,
				'forceLabelForNew' => $this->getVar('forceLabel'),							// force query text to be default in label fields
				'quickadd' => true
			));
			
			print join("\n", $form_elements);
?>
		<input type='hidden' name='_formName' value='<?= $form_name.$field_name_prefix.$n; ?>'/>
		<input type='hidden' name='q' value='<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>'/>
		<input type='hidden' name='screen' value='<?= htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='types' value='<?= htmlspecialchars(is_array($restrict_to_types) ? join(',', $restrict_to_types) : ''); ?>'/>
	</div>
</form>
<?= TooltipManager::getLoadHTML(); ?>
