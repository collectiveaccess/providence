<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/access/role_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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

	$t_role = $this->getVar('t_role');
	$vn_role_id = $this->getVar('role_id');
	
	$va_bundle_list = $this->getVar('bundle_list');
	$va_type_list = $this->getVar('type_list');
	$va_table_names = $this->getVar('table_display_names');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'RolesForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/access', 'Roles', 'ListRoles', array('role_id' => 0)), 
		'', 
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/access', 'Roles', 'Delete', array('role_id' => $vn_role_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'RolesForm');

		foreach($t_role->getFormFields() as $vs_f => $va_role_info) {
			print $t_role->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
		}
		
		$va_actions = $t_role->getRoleActionList();
		
		$vn_num_cols = 5;
		$vn_c = 0;
		
		$va_tooltips = array();
?>
	<div id="role_tabs"  class="tabContainer">
		<ul>
			<li><a href="#role_actions" class='formLabel'><?php print _t('Actions'); ?></a></li>
			<li><a href="#role_bundles" class='formLabel'><?php print _t('Metadata'); ?></a></li>
<?php
	if ($t_role->getAppConfig()->get('perform_type_access_checking')) { 
?>
			<li><a href="#type_list" class='formLabel'><?php print _t('Types'); ?></a></li>
<?php
	}
?>
		</ul>
		<div id="role_actions">
			<table>
<?php
			$va_current_actions = $t_role->getRoleActions();
			
			foreach($va_actions as $vs_group => $va_group_info) {
				if ((caTableIsActive($vs_group) === false) && ($vs_group != 'ca_object_representations')) { continue; }		// will return null if group name is not a table name; true if it's an enabled table and false if it's a disabled table
				$vs_check_all_link = '<a href="#" onclick="jQuery(\'.role_action_group_'.$vs_group.'\').attr(\'checked\', true); return false;" class="roleCheckAllNoneButton">'._t('All').'</a>';
				$vs_check_none_link = '<a href="#" onclick="jQuery(\'.role_action_group_'.$vs_group.'\').attr(\'checked\', false); return false;" class="roleCheckAllNoneButton">'._t('None').'</a>';
				
				print "<tr><td colspan='".($vn_num_cols * 2)."' class='formLabel roleCheckGroupHeading'><span id='group_label_".$vs_group."'>".$va_group_info['label']."</span> <span class='roleCheckAllNoneButtons'>{$vs_check_all_link}/{$vs_check_none_link}</span></td></tr>\n";
				TooltipManager::add('#group_label_'.$vs_group, "<h3>".$va_group_info['label']."</h3>".$va_group_info['description']);

				foreach($va_group_info['actions'] as $vs_action => $va_action_info) {
					if ($vn_c == 0) {
						print "<tr valign='top'>";
					} 
					$va_attributes = array('value' => 1);
					if (in_array($vs_action, $va_current_actions)) {
						$va_attributes['checked'] = 1;
					}
					$va_attributes['class'] = 'role_action_group_'.$vs_group;
					
					print "<td>".caHTMLCheckboxInput($vs_action, $va_attributes)."</td><td width='120'><span id='role_label_".$vs_action."'>".$va_action_info['label']."</span></td>";
					TooltipManager::add('#role_label_'.$vs_action, "<h3>".$va_action_info['label']."</h3>".$va_action_info['description']);
					
					$vn_c++;
					
					if ($vn_c >= $vn_num_cols) {
						$vn_c = 0;
						print "</tr>\n";
					}
				}
									
				if ($vn_c > 0) {
					print "</tr>\n";
				}
				$vn_c = 0;
			}
?>
			</table>
		</div>
		<div id="role_bundles">
<?php
	$o_dm = Datamodel::load();
	foreach($va_bundle_list as $vs_table => $va_bundles_by_table) {
		if (!caTableIsActive($vs_table) && ($vs_table != 'ca_object_representations')) { continue; }
		print "<table width='100%'>\n";
		print "<tr><td colspan='4'><h1>".$va_table_names[$vs_table]."</h1></td></tr>\n";				
		print "<tr align='center' valign='middle'><th width='180' align='left'>"._t('Element')."</th><th width='180'><a href='#' onclick='jQuery(\".{$vs_table}_bundle_access_none\").prop(\"checked\", 1); return false;'>"._t('No access')."</a></th><th width='180'><a href='#' onclick='jQuery(\".{$vs_table}_bundle_access_readonly\").prop(\"checked\", 1); return false;'>"._t('Read-only access')."</a></th><th><a href='#' onclick='jQuery(\".{$vs_table}_bundle_access_edit\").prop(\"checked\", 1); return false;'>"._t('Read/edit access')."</a></th></tr>\n";
		
		$t_instance = $o_dm->getInstanceByTableName($vs_table, true);
		$vs_pk = $t_instance->primaryKey();
		foreach($va_bundles_by_table as $vs_bundle_name => $va_info) {
			print "<tr align='center' valign='middle'>";
			print "<td align='left'>".$va_info['bundle_info']['display']."</td>";
			
			$vs_access = $va_info['access'];
			
			if (in_array($vs_bundle_name, array('preferred_labels', $vs_pk))) {	// don't allow preferred labels and other critical UI fields to be set to readonly
				print "<td>-</td>\n";
			} else {
				print "<td>".caHTMLRadioButtonInput($vs_table.'_'.$vs_bundle_name, array('value' => __CA_BUNDLE_ACCESS_NONE__, 'class' => "{$vs_table}_bundle_access_none"), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_NONE__)))."</td>\n";
			}
			print "<td>".caHTMLRadioButtonInput($vs_table.'_'.$vs_bundle_name, array('value' => __CA_BUNDLE_ACCESS_READONLY__, 'class' => "{$vs_table}_bundle_access_readonly"), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_READONLY__)))."</td>\n";
			print "<td>".caHTMLRadioButtonInput($vs_table.'_'.$vs_bundle_name, array('value' => __CA_BUNDLE_ACCESS_EDIT__, 'class' => "{$vs_table}_bundle_access_edit"), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_EDIT__)))."</td>\n";
		}
		print "</tr>\n";
		print "</table>\n";
	}
?>
		</div>
<?php
	if ($t_role->getAppConfig()->get('perform_type_access_checking')) { 
?>
		<div id="type_list">
<?php
		$t_list = new ca_lists();
		foreach($va_type_list as $vs_table => $va_types_by_table) {
			print "<table width='100%'>\n";
			print "<tr><td colspan='4'><h1>".$va_table_names[$vs_table]."</h1></td></tr>\n";	
			print "<tr align='center' valign='middle'><th width='180' align='left'>"._t('Type')."</th><th width='180'>"._t('No access')."</th><th width='180'>"._t('Read-only access')."</th><th>"._t('Read/edit access')."</th></tr>\n";
			
			$t_instance = $o_dm->getInstanceByTableName($vs_table, true);
			$vs_pk = $t_instance->primaryKey();
			
			foreach($va_types_by_table as $vn_id => $va_type) {
				if (!$va_type['type_info']['parent_id']) { continue; } 
				print "<tr align='center' valign='middle'>";
				if (($vn_indent = 5*((int)$va_type['type_info']['level'])) < 0) { $vn_indent = 0; }
				print "<td align='left'>".str_repeat("&nbsp;", $vn_indent).$va_type['type_info']['name_plural']."</td>";
				
				$vs_access = $va_type['access'];
				
				print "<td>".caHTMLRadioButtonInput($vs_table.'_type_'.$va_type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_NONE__), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_NONE__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($vs_table.'_type_'.$va_type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_READONLY__), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_READONLY__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($vs_table.'_type_'.$va_type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_EDIT__), array('checked' => ($vs_access == __CA_BUNDLE_ACCESS_EDIT__)))."</td>\n";
	
			}
			
			print "</tr>\n";
			print "</table>\n";
		}
?>
		</div>
<?php
	}
?>
	</div>
	</form>
	
	<div class="editorBottomPadding"><!-- empty --></div>
<?php
	print $vs_control_box;
?>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#role_tabs").tabs();
	});
</script>