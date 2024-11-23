<?php
/* ----------------------------------------------------------------------
 * app/views/manage/sets/set_item_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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

$t_subject 				= $this->getVar('t_subject');

$restrict_to_types 	= $this->getVar('restrict_to_types');

$field_name_prefix 	= $this->getVar('field_name_prefix');
$n 					= $this->getVar('n');
$q					= caUcFirstUTF8Safe($this->getVar('q'), true);

$item_id = $this->request->getParameter('item_id', pInteger);
$set_id = $this->request->getParameter('set_id', pInteger);

$can_edit	 		= $t_subject->isSaveable($this->request);

$form_name = "SetItemAddForm";	
?>		
<form action="#" name="<?= $form_name; ?>" method="POST" enctype="multipart/form-data" id="<?= $form_name.$field_name_prefix.$n; ?>">
	<div class='dialogHeader quickaddDialogHeader'><?php 
?>
<?php
$can_edit = true;
	if ($can_edit) {
		print "<div style='float: right;'>".caJSButton($this->request, __CA_NAV_ICON_ADD__, _t("Save"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "caSave{$form_name}{$field_name_prefix}{$n}(event);"))
		.' '.caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), "{$form_name}{$field_name_prefix}{$n}", array("onclick" => "jQuery(\"#{$form_name}{$field_name_prefix}{$n}\").parent().data(\"panel\").hidePanel();"))."</div><br style='clear: both;'/>\n";
	}
?>
	</div>
	
	<div class="componentAddErrorContainer" id="<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>"> </div>
	
	<div class="componentAddSectionBox" id="<?= $form_name; ?>Container<?= $field_name_prefix.$n; ?>">
		<div class="componentAddFormTopPadding"><!-- empty --></div>
<?php

			$va_force_new_label = array();
			foreach($t_subject->getLabelUIFields() as $vn_i => $vs_fld) {
				$va_force_new_label[$vs_fld] = '';
			}
			$va_force_new_label['locale_id'] = $g_ui_locale_id;							// use default locale
			$va_force_new_label[$t_subject->getLabelDisplayField()] = $q;				// query text is used for display field
			
			$va_form_elements = $t_subject->getBundleFormHTMLForScreen($this->getVar('screen'), array(
					'request' => $this->request, 
					'formName' => $form_name.$field_name_prefix.$n,
					'forceLabelForNew' => $va_force_new_label							// force query text to be default in label fields
			));
			
			print join("\n", $va_form_elements);
?>
		<input type='hidden' name='_formName' value='<?= $form_name.$field_name_prefix.$n; ?>'/>
		<input type='hidden' name='screen' value='<?= htmlspecialchars($this->getVar('screen')); ?>'/>
		<input type='hidden' name='item_id' value='<?= $item_id; ?>'/>
		<input type='hidden' name='set_id' value='<?= $set_id; ?>'/>
		
		<script type="text/javascript">
			function caSave<?= $form_name.$field_name_prefix.$n; ?>(e) {
				jQuery.post('<?= caNavUrl($this->request, "manage/sets", "SetItem", "Save"); ?>', jQuery("#<?= $form_name.$field_name_prefix.$n; ?>").serialize(), function(resp, textStatus) {
					if (resp.status == 0) {
						// Reload inspector and components bundle in parent form
						if(caBundleUpdateManager) { 
							caBundleUpdateManager.reloadBundle('ca_set_items'); 
							//caBundleUpdateManager.reloadInspector(); 
						}
						
						let msg = resp.display;
						if(resp.duplication_status) { msg += "<br/>" + resp.duplication_status; }
						
						jQuery.jGrowl(<?= json_encode(_t('Saved %1 ', $t_subject->getTypeName())); ?> + ' <em>' + msg + '</em>', { header: '<?= addslashes(_t('Component add %1', $t_subject->getTypeName())); ?>' }); 
						jQuery("#<?= $form_name.$field_name_prefix.$n; ?>").parent().data('panel').hidePanel();
					} else {
						// error
						var content = '<div class="notification-error-box rounded"><ul class="notification-error-box">';
						for(var e in resp.errors) {
							content += '<li class="notification-error-box">' + e + '</li>';
						}
						content += '</ul></div>';
						
						jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").html(content).slideDown(200);
						
						var componentAddClearErrorInterval = setInterval(function() {
							jQuery("#<?= $form_name; ?>Errors<?= $field_name_prefix.$n; ?>").slideUp(500);
							clearInterval(componentAddClearErrorInterval);
						}, 3000);
					}
				}, "json");
			}
		</script>
	</div>
</form>
