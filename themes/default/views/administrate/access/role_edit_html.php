<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/access/role_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2026 Whirl-i-Gig
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
$role_id = $this->getVar('role_id');

$bundle_list = $this->getVar('bundle_list');
$source_list = $this->getVar('source_list');
$type_list = $this->getVar('type_list');
$access_status_list = $this->getVar('access_status_list');

$table_names = $this->getVar('table_display_names');
?>
<div class="sectionBox">
<?php
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'RolesForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/access', 'Roles', 'ListRoles', array('role_id' => 0)), 
		'', 
		($role_id > 0) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/access', 'Roles', 'Delete', array('role_id' => $role_id)) : ''
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'RolesForm');

		foreach($t_role->getFormFields() as $f => $role_info) {
			print $t_role->htmlFormElement($f, null, array('field_errors' => $this->request->getActionErrors('field_'.$f)));
		}
		
		$actions = $t_role->getRoleActionList();
		
		$num_cols = 5;
		$c = 0;
		
		$tooltips = array();
?>
	<div id="role_tabs"  class="tabContainer">
		<ul>
			<li><a href="#role_actions" class='formLabel'><?= _t('Actions'); ?></a></li>
			<li><a href="#role_bundles" class='formLabel'><?= _t('Metadata'); ?></a></li>
<?php
	if ($t_role->getAppConfig()->get('perform_type_access_checking')) { 
?>
			<li><a href="#type_list" class='formLabel'><?= _t('Types'); ?></a></li>
<?php
	}
	if ($t_role->getAppConfig()->get('perform_source_access_checking')) { 
?>
			<li><a href="#source_list" class='formLabel'><?= _t('Sources'); ?></a></li>
<?php
	}
?>
			<li><a href="#pawtucket" class='formLabel'><?= _t('Pawtucket'); ?></a></li>
		</ul>
		<div id="role_actions">
			<table>
<?php
			$current_actions = $t_role->getRoleActions();
			
			foreach($actions as $group => $group_info) {
				if ((caTableIsActive($group) === false) && ($group != 'ca_object_representations')) { continue; }		// will return null if group name is not a table name; true if it's an enabled table and false if it's a disabled table
				$check_all_link = '<a href="#" onclick="jQuery(\'.role_action_group_'.$group.'\').attr(\'checked\', true); return false;" class="roleCheckAllNoneButton">'._t('All').'</a>';
				$check_none_link = '<a href="#" onclick="jQuery(\'.role_action_group_'.$group.'\').attr(\'checked\', false); return false;" class="roleCheckAllNoneButton">'._t('None').'</a>';
				
				print "<tr><td colspan='".($num_cols * 2)."' class='formLabel roleCheckGroupHeading'><span id='group_label_".$group."'>".$group_info['label']."</span> <span class='roleCheckAllNoneButtons'>{$check_all_link}/{$check_none_link}</span></td></tr>\n";
				TooltipManager::add('#group_label_'.$group, "<div class='tooltipHead'>".$group_info['label']."</div>".$group_info['description']);

				foreach($group_info['actions'] as $action => $action_info) {
					if ($c == 0) {
						print "<tr valign='top'>";
					} 
					$attributes = array('value' => 1);
					if (in_array($action, $current_actions)) {
						$attributes['checked'] = 1;
					}
					$attributes['class'] = 'role_action_group_'.$group;
					
					print "<td>".caHTMLCheckboxInput($action, $attributes)."</td><td width='120'><span id='role_label_".$action."'>".$action_info['label']."</span></td>";
					TooltipManager::add('#role_label_'.$action, "<div class='tooltipHead'>".$action_info['label']."</div>".$action_info['description']);
					
					$c++;
					
					if ($c >= $num_cols) {
						$c = 0;
						print "</tr>\n";
					}
				}
									
				if ($c > 0) {
					print "</tr>\n";
				}
				$c = 0;
			}
?>
			</table>
		</div>
		<div id="role_bundles">
<?php
	foreach($bundle_list as $table => $bundles_by_table) {
		if (!caTableIsActive($table) && ($table != 'ca_object_representations')) { continue; }
		print "<table width='100%'>\n";
		print "<tr><td colspan='4'><h1>".$table_names[$table]."</h1></td></tr>\n";				
		print "<tr align='center' valign='middle'>
			<th width='180' align='left'>"._t('Element')."</th>
			<th width='180'><a href='#' onclick='jQuery(\".{$table}_bundle_access_none\").prop(\"checked\", 1); return false;'>"._t('No access')."</a></th>
			<th width='180'><a href='#' onclick='jQuery(\".{$table}_bundle_access_readonly\").prop(\"checked\", 1); return false;'>"._t('Read-only access')."</a></th>
			<th><a href='#' onclick='jQuery(\".{$table}_bundle_access_edit\").prop(\"checked\", 1); return false;'>"._t('Edit access')."</a></th>
		</tr>\n";
		
		$t_instance = Datamodel::getInstanceByTableName($table, true);
		$pk = $t_instance->primaryKey();
		foreach($bundles_by_table as $bundle_name => $info) {
			print "<tr align='center' valign='middle'>";
			print "<td align='left'>".$info['bundle_info']['display']."</td>";
			
			$access = $info['access'];
			
			if (in_array($bundle_name, array('preferred_labels', $pk))) {	// don't allow preferred labels and other critical UI fields to be set to no access
				print "<td>-</td>\n";
			} else {
				print "<td>".caHTMLRadioButtonInput($table.'_'.$bundle_name, array('value' => __CA_BUNDLE_ACCESS_NONE__, 'class' => "{$table}_bundle_access_none"), array('checked' => ($access == __CA_BUNDLE_ACCESS_NONE__)))."</td>\n";
			}
			print "<td>".caHTMLRadioButtonInput($table.'_'.$bundle_name, array('value' => __CA_BUNDLE_ACCESS_READONLY__, 'class' => "{$table}_bundle_access_readonly"), array('checked' => ($access == __CA_BUNDLE_ACCESS_READONLY__)))."</td>\n";
			print "<td>".caHTMLRadioButtonInput($table.'_'.$bundle_name, array('value' => __CA_BUNDLE_ACCESS_EDIT__, 'class' => "{$table}_bundle_access_edit"), array('checked' => ($access == __CA_BUNDLE_ACCESS_EDIT__)))."</td>\n";
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
		foreach($type_list as $table => $types_by_table) {
			print "<table width='100%'>\n";
			print "<tr><td colspan='4'><h1>".$table_names[$table]."</h1></td></tr>\n";	
			print "<tr align='center' valign='middle'>
				<th width='180' align='left'>"._t('Type')."</th>
				<th width='180'>"._t('No access')."</th>
				<th width='180'>"._t('Read-only access')."</th>
				<th width='180'>"._t('Edit access')."</th>
				<th width='180'>"._t('Edit/delete access')."</th>
			</tr>\n";
			
			$t_instance = Datamodel::getInstanceByTableName($table, true);
			$pk = $t_instance->primaryKey();
			
			foreach($types_by_table as $id => $type) {
				if (!$type['type_info']['parent_id']) { continue; } 
				print "<tr align='center' valign='middle'>";
				if (($indent = 5*((int)$type['type_info']['level'])) < 0) { $indent = 0; }
				print "<td align='left'>".str_repeat("&nbsp;", $indent).$type['type_info']['name_plural']."</td>";
				
				$access = $type['access'];
				
				print "<td>".caHTMLRadioButtonInput($table.'_type_'.$type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_NONE__), array('checked' => ($access == __CA_BUNDLE_ACCESS_NONE__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_type_'.$type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_READONLY__), array('checked' => ($access == __CA_BUNDLE_ACCESS_READONLY__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_type_'.$type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_EDIT__), array('checked' => ($access == __CA_BUNDLE_ACCESS_EDIT__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_type_'.$type['type_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_EDIT_DELETE__), array('checked' => ($access == __CA_BUNDLE_ACCESS_EDIT_DELETE__)))."</td>\n";
	
			}
			
			print "</tr>\n";
			print "</table>\n";
		}
?>
		</div>
<?php
	}
	
	if ($t_role->getAppConfig()->get('perform_source_access_checking')) { 
?>
		<div id="source_list">
<?php
		$t_list = new ca_lists();
		foreach($source_list as $table => $sources_by_table) {
			if(!is_array($sources_by_table) || (sizeof($sources_by_table) <= 1)) { continue; }
			print "<table width='100%'>\n";
			print "<tr><td colspan='4'><h1>".$table_names[$table]."</h1></td></tr>\n";	
			print "<tr align='center' valign='middle'>
				<th>"._t('Default')."</th><th width='180' align='left'>"._t('Source')."</th>
				<th width='180'>"._t('No access')."</th>
				<th width='180'>"._t('Read-only access')."</th>
				<th width='180'>"._t('Edit access')."</th>
				<th width='180'>"._t('Edit/delete access')."</th>
			</tr>\n";
			
			$t_instance = Datamodel::getInstanceByTableName($table, true);
			$pk = $t_instance->primaryKey();
			
			foreach($sources_by_table as $id => $source) {
				if (!$source['source_info']['parent_id']) { continue; } 
				$is_default = (isset($source['default']) && $source['default']) ? true : false;
				
				print "<tr align='center' valign='middle'>";
				$opts = array('value' => $source['source_info']['item_id']);
				if ($is_default) { $opts['checked'] = 1; }
				print "<td>".caHTMLRadioButtonInput($table.'_default_source', $opts)."</td>";
				if (($indent = 5 * ((int)$source['source_info']['level'])) < 0) { $indent = 0; }
				print "<td align='left'>".str_repeat("&nbsp;", $indent).$source['source_info']['name_plural']."</td>";
				
				$access = $source['access'];
				
				print "<td>".caHTMLRadioButtonInput($table.'_source_'.$source['source_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_NONE__), array('checked' => ($access == __CA_BUNDLE_ACCESS_NONE__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_source_'.$source['source_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_READONLY__), array('checked' => ($access == __CA_BUNDLE_ACCESS_READONLY__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_source_'.$source['source_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_EDIT__), array('checked' => ($access == __CA_BUNDLE_ACCESS_EDIT__)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput($table.'_source_'.$source['source_info']['item_id'], array('value' => __CA_BUNDLE_ACCESS_EDIT_DELETE__), array('checked' => ($access == __CA_BUNDLE_ACCESS_EDIT_DELETE__)))."</td>\n";
	
			}
			
			print "</tr>\n";
			print "</table>\n";
		}
?>
		</div>
<?php
	}
?>
		<div id="pawtucket">
<?php
	print "<p>"._t('Access values are used to control if and to whom a record should be displayed in a public-facing Pawtucket-based interface. Users logged into the Pawtucket interface will be given the levels of access specified here. If you wish to use the default Pawtucket access policy for an access value set the level to "Use Pawtucket default."')."</p>\n";
?>
			<table width='100%'>
				<tr align='center' valign='middle'><th width='180' align='left'><?= _t('Access status'); ?></th><th width='180'><?= _t('No access'); ?></th><th width='180'><?= _t('Read access'); ?></th><th><?= _t('Use Pawtucket default'); ?></th></tr>
<?php
			foreach($access_status_list as $id => $access_status) {
				print "<tr align='center' valign='middle'>";
				if (($indent = 5*((int)($access_status_list['access_status_info']['level'] ?? 0))) < 0) { $indent = 0; }
				print "<td align='left'>".str_repeat("&nbsp;", $indent).($access_status['access_status_info']['name_plural'] ?? null)."</td>";
				
				$access = !strlen($access_status['access']) ? null : (int)$access_status['access'];
				print "<td>".caHTMLRadioButtonInput('access_status_'.$access_status['access_status_info']['item_id'] ?? null, array('value' => 0), array('checked' => ($access === 0)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput('access_status_'.$access_status['access_status_info']['item_id'] ?? null, array('value' => 1), array('checked' => ($access === 1)))."</td>\n";
				print "<td>".caHTMLRadioButtonInput('access_status_'.$access_status['access_status_info']['item_id'] ?? null, array('value' => null), array('checked' => (is_null($access))))."</td>\n";
			}
?>			
				</tr>
			</table>
		</div>
	</div>
	</form>
	
	<div class="editorBottomPadding"><!-- empty --></div>
<?php
	print $control_box;
?>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#role_tabs").tabs();
	});
</script>
