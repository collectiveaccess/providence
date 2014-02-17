<?php
/* ----------------------------------------------------------------------
 * app/views/editor/representation_annotations/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
	
	$va_notifications = $this->getVar('notifications');
?>		
<form action="#" class="quickAddSectionForm" name="<?php print $vs_form_name; ?>" method="POST" enctype="multipart/form-data" id="<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>">
	<div class='dialogHeader' style='position: width: 100%; padding: 8px;'><?php 
	
	if ($vb_can_edit) {
		if ($vn_subject_id > 0) {
			print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete annotation"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "caConfirmDeleteAnnotation(true);"))."</div>\n";
		}
		print "<div style='float: left;'>".caJSButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save annotation"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("id" => "caAnnoEditorScreenSaveButton", "onclick" => "caSaveAnnotation{$vs_form_name}{$vs_field_name_prefix}{$vs_n}(event);"))
			.' '.caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), "{$vs_form_name}{$vs_field_name_prefix}{$vs_n}", array("onclick" => "return caAnnoEditorDisableAnnotationForm();"))."</div><br style='clear: both;'/>\n";
	}
?>
	</div>
	<div class="caAnnoEditorEditorErrorContainer" id="<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>"></div>
	<div class="quickAddSectionBox" id="<?php print $vs_form_name; ?>Container<?php print $vs_field_name_prefix.$vs_n; ?>">
<?php

			$va_force_new_label = array();
			foreach($t_subject->getLabelUIFields() as $vn_i => $vs_fld) {
				$va_force_new_label[$vs_fld] = '';
			}
			$va_force_new_label['locale_id'] = $g_ui_locale_id;							// use default locale
			$va_force_new_label[$t_subject->getLabelDisplayField()] = $vs_q;				// query text is used for display field
			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
					'width' => '625px',
					'request' => $this->request, 
					'formName' => $vs_form_name.$vs_field_name_prefix.$vs_n,
					'forceLabelForNew' => $va_force_new_label							// force query text to be default in label fields
			));
			
			print join("\n", $va_form_elements);
?>
		<input type='hidden' name='_formName' value='<?php print $vs_form_name.$vs_field_name_prefix.$vs_n; ?>'/>
		<input type='hidden' name='q' value='<?php print htmlspecialchars($vs_q, ENT_QUOTES, 'UTF-8'); ?>'/>
		
		<input type='hidden' id='caAnnoEditorAnnotationID' name='annotation_id' value='<?php print $vn_subject_id; ?>'/>
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
						
<?php
	if ($vn_subject_id) {
?>
						// Reload the item that has changed
						caAnnoEditorTlReload(jQuery("#caAnnoEditorTlCarousel"), resp.id);
<?php
	} else {	
?>	
						// Add the newly created item
						caAnnoEditorTlLoad(jQuery("#caAnnoEditorTlCarousel"), 0);
<?php
	}
?>
						// Get new form with current in-point
						caAnnoEditorEdit(0, caAnnoEditorGetPlayerTime(), caAnnoEditorGetPlayerTime() + 10);
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").html(content).slideDown(200);
						
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
				} else {
					jQuery('#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>').slideUp(200, function() { jQuery(this).html(''); });
				}
			}
			
			function caDeleteAnnotation() {
				jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'deleteAnnotation'); ?>', {annotation_id: <?php print (int)$vn_subject_id; ?>}, function(resp) {
					if (resp.code == 0) {
						// delete succeeded... so update clip list
						caAnnoEditorTlRemove(jQuery("#caAnnoEditorTlCarousel"), <?php print (int)$vn_subject_id; ?>);
						caAnnoEditorDisableAnnotationForm();
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").html(content).slideDown(200);
					}
				});
			}
			
<?php
			//
			// If any notifications are set by the controller loading this form we want to display them
			//
			if(is_array($va_notifications) && sizeof($va_notifications)) {
?>
				jQuery(document).ready(function() {
					var content = '<div class="notification-info-box rounded"><ul class="notification-info-box">';
<?php
					$vs_content = '';
					foreach($va_notifications as $va_notification) {
						switch($va_notification['type']) {
							case __NOTIFICATION_TYPE_ERROR__:
								$vs_content .= "<li class='notification-error-box'>".$va_notification['message']."</li>";
								break;
							case __NOTIFICATION_TYPE_WARNING__:
								$vs_content .= "<li class='notification-warning-box'>".$va_notification['message']."</li>";
								break;
							case __NOTIFICATION_TYPE_INFO__:
							default:
								$vs_content .= "<li class='notification-info-box'>".$va_notification['message']."</li>";
								break;
						}
					}
?>	
					content += '<?php print addslashes($vs_content); ?>';
					content += '</ul></div>';
					jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").hide().html(content).slideDown(200);
						
					var quickAddClearErrorInterval = setInterval(function() {
						jQuery("#<?php print $vs_form_name; ?>Errors<?php print $vs_field_name_prefix.$vs_n; ?>").slideUp(500);
						clearInterval(quickAddClearErrorInterval);
					}, 3000);
				});
<?php
			}
?>
		</script>
	</div>
</form>