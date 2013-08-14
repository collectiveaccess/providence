<?php
/* ----------------------------------------------------------------------
 * app/views/editor/representation_annotations/quickadd_html.php : 
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
	
	$vs_field_name_prefix = $this->getVar('field_name_prefix');
	$vs_n 				= $this->getVar('n');
	$vs_q				= caUcFirstUTF8Safe($this->getVar('q'), true);

	$vb_can_edit	 	= $t_subject->isSaveable($this->request);
	
	$vs_form_name = "RepresentationAnnotationQuickAddForm";
?>		
<form action="#" class="quickAddSectionForm" name="<?php print $vs_form_name; ?>" method="POST" enctype="multipart/form-data" id="<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>">
	<div class='dialogHeader' style='position: width: 100%; padding: 8px;'><?php 
	
	if ($vb_can_edit) {
		if ($vn_subject_id > 0) {
			print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete annotation"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caConfirmDeleteAnnotation(true);"))."</div>\n";
		}
		print "<div style='float: left;'>".caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save annotation"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caSaveAnnotation{$vs_form_name}{$vs_field_name_prefix}{$vs_n}(event);"))
			.' '.caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "jQuery(\".caAnnotationEditorPanel\").animate({ \"height\": \"240px\" }, function() { jQuery(\"#caAnnotationEditorEditorScreen\").html(\"\").hide(); });"))."</div><br style='clear: both;'/>\n";
	}
?>
	</div>
	<div class="caAnnotationEditorEditorErrorContainer" id="<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>"></div>
	<div class="quickAddSectionBox" id="{$vs_form_name}Container<?php print $vs_field_name_prefix.$vs_n; ?>">
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
		
		<input type='hidden' name='annotation_id' value='<?php print $vn_subject_id; ?>'/>
		<input type='hidden' name='representation_id' value='<?php print $t_subject->get('representation_id'); ?>'/>
		<input type='hidden' name='type_code' value='<?php print $t_subject->get('type_code'); ?>'/>
		
		<input type='hidden' name='screen' value='<?php print htmlspecialchars($this->getVar('screen')); ?>'/>
		
		<script type="text/javascript">
			function caSaveAnnotation<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>(e) {
				jQuery.post('<?php print caNavUrl($this->request, "editor/representation_annotations", "RepresentationAnnotationQuickAdd", "Save"); ?>', jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").serialize(), function(resp, textStatus) {
					if (resp.status == 0) {
						var inputID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteInputID');
						var itemIDID = jQuery("#<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>").parent().data('autocompleteItemIDID');
					
						jQuery('#' + inputID).val(resp.display);
						jQuery('#' + itemIDID).val(resp.id);
						
						
						var content = 	'<div class="notification-info-box rounded"><ul class="notification-info-box">' + 
										'<li class="notification-info-box"><?php print addslashes(_t('Saved annotation')); ?></li>' +
										'</ul></div>';
						
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").html(content).slideDown(200);
						jQuery('.rounded').corner('round 8px');
						
						var quickAddClearErrorInterval = setInterval(function() {
							jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").slideUp(500);
							clearInterval(quickAddClearErrorInterval);
							jQuery(".caAnnotationEditorPanel").animate({ "height": "240px" }, function() {
								jQuery("#caAnnotationEditorEditorScreen").html('').hide();	// empty form area
							});	// fold up form
						}, 3000);
						
						loadActions(jQuery('#silo').data('jcarousel'), 'next', true);		// refresh timeline
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
			
			function caConfirmDeleteAnnotation(show) {
				if (show) {
					var content = 	'<div class="notification-info-box rounded"><ul class="notification-info-box">' + 
										'<li class="notification-info-box"><?php print addslashes(_t("Really delete annotation? %1 %2", 
												caJSButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Yes"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caDeleteAnnotation(true);")),
												caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("No"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caConfirmDeleteAnnotation(false); return false;"))
											)); ?></li>' +
										'</ul></div>';
					jQuery('#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>').html(content).slideDown(200);
					jQuery('.rounded').corner('round 8px');
				} else {
					jQuery('#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>').slideUp(200, function() { jQuery(this).html(''); });
				}
			}
			
			function caDeleteAnnotation() {
				jQuery.getJSON('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'deleteAnnotation'); ?>', {annotation_id: <?php print (int)$vn_subject_id; ?>}, function(resp) {
					if (resp.code == 0) {
						// delete succeeded... so update clip list
						loadActions(jQuery('#silo').data('jcarousel'), 'init', true);
						
						// ... and fold up form
						jQuery(".caAnnotationEditorPanel").animate({ "height": "240px" }, function() { jQuery("#caAnnotationEditorEditorScreen").html("").hide(); });
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").html(content).slideDown(200);
						jQuery('.rounded').corner('round 8px');
					}
				});
			}
		</script>
	</div>
</form>