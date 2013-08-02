<?php
/* ----------------------------------------------------------------------
 * app/views/editor/movements/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
	
	$vs_form_name = "MovementQuickAddForm";
?>		
<form action="#" name="<?php print $vs_form_name; ?>" method="POST" enctype="multipart/form-data" id="<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>">
	<div class='dialogHeader' style='position: fixed; width: 890px; z-index:33000;'><?php 
	print "<div class='quickAddTypeList'>"._t('Quick Add %1', $t_subject->getTypeListAsHTMLFormElement('change_type_id', array('id' => "{$vs_form_name}TypeID{$vs_field_name_prefix}{$vs_n}", 'onchange' => "caSwitchTypeQuickAddForm{$vs_field_name_prefix}{$vs_n}();"), array('value' => $t_subject->get('type_id'), 'restrictToTypes' => $va_restrict_to_types)))."</div>"; 
	
	if ($vb_can_edit) {
		print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_BUTTON_ADD__, _t("Add %1", $t_subject->getTypeName()), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caSave{$vs_form_name}{$vs_field_name_prefix}{$vs_n}(event);"))
		.' '.caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "jQuery(\"#{$vs_form_name}".$vs_field_name_prefix.$vs_n."\").parent().data(\"panel\").hidePanel();"))."</div><br style='clear: both;'/>\n";
	}
?>
	</div>
	
	<div class="quickAddErrorContainer" id="<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>"> </div>
	
	<div class="quickAddSectionBox" id="{$vs_form_name}Container<?php print $vs_field_name_prefix.$vs_n; ?>">
		<div class="quickAddFormTopPadding"><!-- empty --></div>
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
					'forceLabelForNew' => $va_force_new_label							// force query text to be default in label fields
			));
			
			print join("\n", $va_form_elements);
?>
		<input type='hidden' name='_formName' value='<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>'/>
		<input type='hidden' name='q' value='<?php print htmlspecialchars($vs_q, ENT_QUOTES, 'UTF-8'); ?>'/>
		<input type='hidden' name='screen' value='<?php print htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='types' value='<?php print htmlspecialchars(is_array($va_restrict_to_types) ? join(',', $va_restrict_to_types) : ''); ?>'/>
		
		

		<script type="text/javascript">
			function caSave<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>(e) {
				jQuery.post('<?php print caNavUrl($this->request, "editor/movements", "MovementQuickAdd", "Save"); ?>', jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").serialize(), function(resp, textStatus) {
				
					if (resp.status == 0) {
						var inputID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteInputID');
						var itemIDID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteItemIDID');
						var typeIDID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteTypeIDID');
						var relationbundle = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('relationbundle');
					
						jQuery('#' + inputID).val(resp.display);
						jQuery('#' + itemIDID).val(resp.id);
						jQuery('#' + typeIDID).val(resp.type_id);
						
						relationbundle.select(null, resp);
						
						jQuery.jGrowl('<?php print addslashes(_t('Created %1 ', $t_subject->getTypeName())); ?> <em>' + resp.display + '</em>', { header: '<?php print addslashes(_t('Quick add %1', $t_subject->getTypeName())); ?>' }); 
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
				jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().load("<?php print caNavUrl($this->request, 'editor/movements', 'MovementQuickAdd', 'Form'); ?>", data);
			}
		</script>
	</div>
</form>