<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/list_item_editor/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2024 Whirl-i-Gig
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
$restrict_to_lists = $this->getVar('restrict_to_lists');

$field_name_prefix = $this->getVar('field_name_prefix');
$n 				= $this->getVar('n');
$q				= caUcFirstUTF8Safe($this->getVar('q'), true);

$vb_can_edit	 	= $t_subject->isSaveable($this->request);

$form_name = "ListItemQuickAddForm";
?>		
<script type="text/javascript">
	var caQuickAddFormHandler = caUI.initQuickAddFormHandler({
		formID: '<?= $form_name.$field_name_prefix.$n; ?>',
		formErrorsPanelID: '<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>',
		formTypeSelectID: '<?= $form_name; ?>TypeID<?= $field_name_prefix.$n; ?>', 
		
		formUrl: '<?= caNavUrl($this->request, 'administrate/setup/list_item_editor', 'ListItemQuickAdd', 'Form'); ?>',
		fileUploadUrl: '<?= caNavUrl($this->request, "administrate/setup/list_item_editor", "ListItemEditor", "UploadFiles"); ?>',
		saveUrl: '<?= caNavUrl($this->request, "administrate/setup/list_item_editor", "ListItemQuickAdd", "Save"); ?>',
		
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
			// Output hierarchy browser
			$lookup_urls = caJSONLookupServiceUrl($this->request, 'ca_list_items');
?>
	<div class='bundleLabel'><span class="formLabelText"><?= _t('Location in hierarchy'); ?></span><br/>
		<div class="bundleContainer">
			<div class="caItemList">
				<div class="hierarchyBrowserContainer">
					<div id="caQuickAdd<?= $form_name; ?>HierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
					<div>
						<?= _t('Search'); ?>: <input type="text" id="caQuickAdd<?= $form_name; ?>HierarchyBrowserSearch" name="search" value="<?= htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8'); ?>" size="100"/>
					</div>
				</div>
							
				<script type="text/javascript">
					// Set up "add" hierarchy browser
					var o<?= $form_name.$field_name_prefix; ?>HierarchyBrowser = null;				
					if (!o<?= $form_name.$field_name_prefix; ?>HierarchyBrowser) {
						o<?= $form_name.$field_name_prefix; ?>HierarchyBrowser = caUI.initHierBrowser('caQuickAdd<?= $form_name.$field_name_prefix; ?>HierarchyBrowser', {
							levelDataUrl: '<?= $lookup_urls['levelList']; ?>/lists/<?= $restrict_to_lists; ?>',
							initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
							editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						
							readOnly: false,
							selectOnLoad: true,
							
							initItemID: '<?= (int)$this->getVar("default_parent_id"); ?>',
							indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
							displayCurrentSelectionOnLoad: true,
							
							currentSelectionIDID: '<?= $form_name; ?>_parent_id',
							currentSelectionDisplayID: 'browseCurrentSelection',
							onSelection: function(item_id, parent_id, name, display, type_id) {
								jQuery('#<?= $form_name; ?>_parent_id').val(item_id);
							}
						});
					}
					jQuery('#caQuickAdd<?= $form_name.$field_name_prefix; ?>HierarchyBrowserSearch').autocomplete(
						{
							minLength: 3, delay: 800,
							source: '<?= caNavUrl($this->request, 'lookup', 'ListItem', 'Get', array('noInline' => 1)); ?>',
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									o<?= $form_name.$field_name_prefix; ?>HierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								jQuery('#caQuickAdd<?= $form_name.$field_name_prefix; ?>HierarchyBrowserSearch').val('');
							}
						}
					);
				</script>
				<input type="hidden" name="parent_id" value="<?= (int)$this->getVar("default_parent_id"); ?>" id="<?= $form_name; ?>_parent_id"/>
			</div>
		</div>
	</div>
	
<?php
			$form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
				'request' => $this->request, 
				'restrictToTypes' => array($t_subject->get('type_id')),
				'formName' => $form_name.$field_name_prefix.$n,
				'forceLabelForNew' => $this->getVar('forceLabel'),							// force query text to be default in label fields
				'omit' => ['parent_id', 'hierarchy_location', 'hierarchy_navigation'],
				'quickadd'=> true
			));
			
			print join("\n", $form_elements);
?>
		<input type='hidden' name='_formName' value='<?= $form_name.$field_name_prefix.$n; ?>'/>
		<input type='hidden' name='q' value='<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>'/>
		<input type='hidden' name='screen' value='<?= htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='hier_id' value='<?= $this->getVar('hier_id'); ?>'/>
		<input type='hidden' name='lists' value='<?= $restrict_to_lists; ?>'/>
		<input type='hidden' name='types' value='<?= htmlspecialchars(is_array($restrict_to_types) ? join(',', $restrict_to_types) : ''); ?>'/>
	</div>
</form>
<?= TooltipManager::getLoadHTML(); ?>
