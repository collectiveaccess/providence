<?php
/* ----------------------------------------------------------------------
 * app/views/editor/places/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
	$vs_q				= caUcFirstUTF8Safe($this->getVar('q'), true);

	$vb_can_edit	 	= $t_subject->isSaveable($this->request);
	
	$vs_form_name = "PlaceQuickAddForm";
?>		
<form action="#" name="<?php print $vs_form_name; ?>" method="POST" enctype="multipart/form-data" id="<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>">
	<div class='dialogHeader quickAddDialogHeader'><?php 
	print "<div class='quickAddTypeList'>"._t('Quick Add %1', $t_subject->getTypeListAsHTMLFormElement('change_type_id', array('id' => "{$vs_form_name}TypeID{$vs_field_name_prefix}{$vs_n}", 'onchange' => "caSwitchTypeQuickAddForm{$vs_field_name_prefix}{$vs_n}();"), array('value' => $t_subject->get('type_id'), 'restrictToTypes' => $va_restrict_to_types)))."</div>"; 
	
	if ($vb_can_edit) {
		print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_BUTTON_ADD__, _t("Add %1", $t_subject->getProperty('NAME_SINGULAR')), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caSave{$vs_form_name}{$vs_field_name_prefix}{$vs_n}(event);"))
		.' '.caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "jQuery(\"#{$vs_form_name}{$vs_field_name_prefix}{$vs_n}\").parent().data(\"panel\").hidePanel();"))."</div><br style='clear: both;'/>\n";
	}
?>
	</div>
	
	<div class="quickAddErrorContainer" id="<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>"> </div>
	
	<div class="quickAddSectionBox" id="{$vs_form_name}Container<?php print $vs_field_name_prefix.$vs_n; ?>">
		<div class="quickAddFormTopPadding"><!-- empty --></div>
<?php
			// Output hierarchy browser
			$va_lookup_urls = caJSONLookupServiceUrl($this->request, 'ca_places');
?>
	<div class='bundleLabel'><span class="formLabelText" id="ObjectEditorForm_ca_entities"><?php print _t('Location in hierarchy'); ?></span><br/>
		<div class="bundleContainer">
			<div class="caItemList">
				<div class="hierarchyBrowserContainer">
					<div id="caQuickAdd<?php print $vs_form_name; ?>HierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
					<div>
						<?php print _t('Search'); ?>: <input type="text" id="caQuickAdd<?php print $vs_form_name; ?>HierarchyBrowserSearch" name="search" value="<?php print htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8'); ?>" size="100"/>
					</div>
				</div>
							
				<script type="text/javascript">
					// Set up "add" hierarchy browser
					var o<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowser = null;				
					if (!o<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowser) {
						o<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowser = caUI.initHierBrowser('caQuickAdd<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowser', {
							levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
							initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
							editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit place">',
						
							readOnly: false,
							selectOnLoad: true,
							
							initItemID: '<?php print (int)$this->getVar("default_parent_id"); ?>',
							indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
							displayCurrentSelectionOnLoad: true,
							
							currentSelectionIDID: '<?php print $vs_form_name; ?>_parent_id',
							currentSelectionDisplayID: 'browseCurrentSelection',
							onSelection: function(item_id, parent_id, name, display, type_id) {
								jQuery('#<?php print $vs_form_name; ?>_parent_id').val(item_id);
							}
						});
					}
					jQuery('#caQuickAdd<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowserSearch').autocomplete(
						{
							minLength: 3, delay: 800,
							source: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'Get', array('noInline' => 1)); ?>',
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									o<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								jQuery('#caQuickAdd<?php print $vs_form_name.$vs_field_name_prefix; ?>HierarchyBrowserSearch').val('');
							}
						}
					);
				</script>
				<input type="hidden" name="parent_id" value="<?php print (int)$this->getVar("default_parent_id"); ?>" id="<?php print $vs_form_name; ?>_parent_id"/>
			</div>
		</div>
	</div>
<?php
			$va_force_new_label = array();
			foreach($t_subject->getLabelUIFields() as $vn_i => $vs_fld) {
				$va_force_new_label[$vs_fld] = '';
			}
			$va_force_new_label['locale_id'] = $g_ui_locale_id;							// use default locale
			$va_force_new_label[$t_subject->getLabelDisplayField()] = $vs_q;				// query text is used for display field
			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
					'request' => $this->request, 
					'formName' => $vs_form_name.$vs_field_name_prefix.$vs_n,
					'forceLabelForNew' => $va_force_new_label,							// force query text to be default in label fields
					'omit' => array('parent_id')
			));
			
			print join("\n", $va_form_elements);
?>
		<input type='hidden' name='_formName' value='<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>'/>
		<input type='hidden' name='q' value='<?php print htmlspecialchars($vs_q, ENT_QUOTES, 'UTF-8'); ?>'/>
		<input type='hidden' name='screen' value='<?php print htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='types' value='<?php print htmlspecialchars(is_array($va_restrict_to_types) ? join(',', $va_restrict_to_types) : ''); ?>'/>
		
		

		<script type="text/javascript">
			function caSave<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>(e) {
				jQuery.each(CKEDITOR.instances, function(k, instance) {
					instance.updateElement();
				});
						
				jQuery.post('<?php print caNavUrl($this->request, "editor/places", "PlaceQuickAdd", "Save"); ?>', jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").serialize(), function(resp, textStatus) {
				
					if (resp.status == 0) {
						var inputID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteInputID');
						var itemIDID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteItemIDID');
						var typeIDID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteTypeIDID');
						var relationbundle = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('relationbundle');
					
						jQuery('#' + inputID).val(resp.display);
						jQuery('#' + itemIDID).val(resp.id);
						jQuery('#' + typeIDID).val(resp.type_id);
						
						relationbundle.select(null, resp);
						
						jQuery.jGrowl('<?php print addslashes(_t('Created place ')); ?> <em>' + resp.display + '</em>', { header: '<?php print addslashes(_t('Quick add %1', $t_subject->getProperty('NAME_SINGULAR'))); ?>' }); 
						jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('panel').hidePanel();
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").html(content).slideDown(200);
						jQuery('.rounded').corner('round 8px');
						
						var quickAddClearErrorInterval = setInterval(function() {
							jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").slideUp(500);
							clearInterval(quickAddClearErrorInterval);
						}, 3000);
					}
				}, "json");
			}
			function caSwitchTypeQuickAddForm<?php print $vs_field_name_prefix.$vs_n; ?>() {
				jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?> input[name=type_id]").val(jQuery("#<?php print $vs_form_name; ?>TypeID<?php print $vs_field_name_prefix.$vs_n; ?>").val());
				var data = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").serialize();
				jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().load("<?php print caNavUrl($this->request, 'editor/places', 'PlaceQuickAdd', 'Form'); ?>", data);
			}
		</script>
	</div>
</form>