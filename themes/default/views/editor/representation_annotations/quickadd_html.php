 <?php
/* ----------------------------------------------------------------------
 * app/views/editor/representation_annotations/quickadd_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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

$field_name_prefix = $this->getVar('field_name_prefix');
$n 				= $this->getVar('n');
$q				= caUcFirstUTF8Safe($this->getVar('q'));

$vb_can_edit	 	= $t_subject->isSaveable($this->request);

$form_name 		= "RepresentationAnnotationQuickAddForm";

$notifications 	= $this->getVar('notifications');
?>		
<form action="#" class="quickAddSectionForm" name="<?= $form_name; ?>" method="POST" enctype="multipart/form-data" id="<?= $form_name.$field_name_prefix.$n; ?>">
	<div class='quickAddDialogHeader'><?php 	
		if ($vb_can_edit) {
			if (($subject_id > 0) && (preg_match("!timebased!i", $t_subject->getAnnotationType()))) {
				print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete annotation"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "caConfirmDeleteAnnotation(true);"))."</div>\n";
			}
			print "<div style='float: left;'>".caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save annotation"), "caAnnoEditorScreenSaveButton", array( "onclick" => "caSaveAnnotation{$form_name}{$field_name_prefix}{$n}(event);"))
				.' '.caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "return caAnnoEditorDisableAnnotationForm();"))."</div><br style='clear: both;'/>\n";
		}
?>
	</div>
	
	<div class="quickAddFormTopPadding"><!-- empty --></div>
	<div class="caAnnoEditorEditorErrorContainer" id="<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>"></div>
	<div class="quickAddSectionBox" id="<?= $form_name.'Container'.$field_name_prefix.$n; ?>">
<?php
		$form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
				'width' => '625px',
				'request' => $this->request, 
				'formName' => $form_name.$field_name_prefix.$n,
				'forceLabelForNew' => $this->getVar('forceLabel'),							// force query text to be default in label fields
				'quickadd' => true
			));
			
			print join("\n", $form_elements);
?>
		<input type='hidden' name='_formName' value='<?= $form_name.$field_name_prefix.$n; ?>'/>
		<input type='hidden' name='q' value='<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>'/>
		
		<input type='hidden' id='caAnnoEditorAnnotationID' name='annotation_id' value='<?= $subject_id; ?>'/>
		<input type='hidden' name='representation_id' value='<?= $t_subject->get('representation_id'); ?>'/>
		<input type='hidden' name='type_code' value='<?= $t_subject->get('type_code'); ?>'/>
		
		<input type='hidden' name='screen' value='<?= htmlspecialchars($this->getVar('screen')); ?>'/>
		
		<script type="text/javascript">
			function caSaveAnnotation<?= $form_name.$field_name_prefix.$n; ?>(e) {
				jQuery.post('<?= caNavUrl($this->request, "editor/representation_annotations", "RepresentationAnnotationQuickAdd", "Save"); ?>', jQuery("#<?= $form_name.$field_name_prefix.$n; ?>").serialize(), function(resp, textStatus) {
					if (resp.status == 0) {
						
						var inputID = jQuery("#<?= $form_name.$field_name_prefix.$n; ?>").parent().data('autocompleteInputID');
						var itemIDID = jQuery("#<?= $form_name.$field_name_prefix.$n; ?>").parent().data('autocompleteItemIDID');
					
						jQuery('#' + inputID).val(resp.display);
						jQuery('#' + itemIDID).val(resp.id);
						
<?php
	if ($subject_id) {
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
						caAnnoEditorEdit(0, caAnnoEditorGetPlayerTime(true), caAnnoEditorGetPlayerTime(true) + 10);
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").html(content).slideDown(200);
						
						var quickAddClearErrorInterval = setInterval(function() {
							jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").slideUp(500);
							clearInterval(quickAddClearErrorInterval);
						}, 3000);
					}
				}, "json");
			}
			
			function caConfirmDeleteAnnotation(show) {
				if (show) {
					var content = 	'<div class="notification-info-box rounded"><ul class="notification-info-box">' + 
										'<li class="notification-info-box"><?= addslashes(_t("Really delete annotation? %1 %2", 
												caJSButton($this->request, __CA_NAV_ICON_DELETE__, _t("Yes"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "caDeleteAnnotation(true);")),
												caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("No"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "caConfirmDeleteAnnotation(false); return false;"))
											)); ?></li>' +
										'</ul></div>';
					jQuery('#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>').html(content).slideDown(200);
				} else {
					jQuery('#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>').slideUp(200, function() { jQuery(this).html(''); });
				}
			}
			
			function caDeleteAnnotation() {
				jQuery.getJSON('<?= caNavUrl($this->request, '*', '*', 'deleteAnnotation'); ?>', {annotation_id: <?= (int)$subject_id; ?>}, function(resp) {
					if (resp.code == 0) {
						// delete succeeded... so update clip list
						caAnnoEditorTlRemove(jQuery("#caAnnoEditorTlCarousel"), <?= (int)$subject_id; ?>);
						caAnnoEditorDisableAnnotationForm();
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").html(content).slideDown(200);
					}
				});
			}
			
<?php
			//
			// If any notifications are set by the controller loading this form we want to display them
			//
			if(is_array($notifications) && sizeof($notifications)) {
?>
				jQuery(document).ready(function() {
					var content = '<div class="notification-info-box rounded"><ul class="notification-info-box">';
<?php
					$content = '';
					foreach($notifications as $notification) {
						switch($notification['type']) {
							case __NOTIFICATION_TYPE_ERROR__:
								$content .= "<li class='notification-error-box'>".$notification['message']."</li>";
								break;
							case __NOTIFICATION_TYPE_WARNING__:
								$content .= "<li class='notification-warning-box'>".$notification['message']."</li>";
								break;
							case __NOTIFICATION_TYPE_INFO__:
							default:
								$content .= "<li class='notification-info-box'>".$notification['message']."</li>";
								break;
						}
					}
?>	
					content += '<?= addslashes($content); ?>';
					content += '</ul></div>';
					jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").hide().html(content).slideDown(200);
						
					var quickAddClearErrorInterval = setInterval(function() {
						jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").slideUp(500);
						clearInterval(quickAddClearErrorInterval);
					}, 3000);
				});
<?php
			}
?>
		</script>
	</div>
</form>
<?= TooltipManager::getLoadHTML(); ?>
