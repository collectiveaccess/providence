<?php
/* ----------------------------------------------------------------------
 * app/views/manage/metadata_alert_rules/screen_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source sets management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	$t_alert = $this->getVar('t_subject');
	$vn_rule_id = $this->getVar('subject_id');
	$vn_table_num = $t_alert->get("table_num"); 
	
	$t_ui = $this->getVar('t_ui');	
?>
	<div class="sectionBox">
<?php
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'MetadataAlertRuleEditorForm').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'manage/metadata_alert_rules', 'MetadataAlertRuleEditor', 'Edit/'.$this->request->getActionExtra(), array('rule_id' => $vn_rule_id)),
			'', 
			(intval($vn_rule_id) > 0) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage/metadata_alert_rules', 'MetadataAlertRuleEditor', 'Delete/'.$this->request->getActionExtra(), array('rule_id' => $vn_rule_id)) : ''
		);
		
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/rule_id/'.$vn_rule_id, 'MetadataAlertRuleEditorForm', null, 'POST', 'multipart/form-data');
			
			$va_form_elements = $t_alert->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'MetadataAlertRuleEditorForm'));
			
			if (!$vn_rule_id) {
				// For new forms, show mandatory fields...
				// ... BUT ...
				// if table_num is set on the url then create a hidden element rather than show it as a mandatory field
				// This allows us to set the content type for the form from the calling control
				$va_mandatory_fields = $t_alert->getMandatoryFields();
				if (($vn_index = array_search('table_num', $va_mandatory_fields)) !== false) {
					if ($vn_table_num) {
						print caHTMLHiddenInput('table_num', array('value' => $vn_table_num));
						unset($va_form_elements['table_num']);
						unset($va_mandatory_fields[$vn_index]);
					}
				}
			}
			
			print join("\n", $va_form_elements);
			
			print $vs_control_box;
?>
			<input type='hidden' name='table_num' value='<?= $vn_table_num; ?>'/>
			<input type='hidden' name='rule_id' value='<?= $vn_rule_id; ?>'/>
		</form>
	
		<div class="editorBottomPadding"><!-- empty --></div>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
