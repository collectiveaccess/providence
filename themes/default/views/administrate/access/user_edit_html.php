<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/user_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
$t_user = $this->getVar('t_user');
$user_id = $this->getVar('user_id');

$roles = $this->getVar('roles');
$groups = $this->getVar('groups');
$password_policies = $this->getVar('password_policies') ?? [];
?>
<div class="sectionBox">
<?php
	print $control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'UsersForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/access', 'Users', 'ListUsers', array('user_id' => 0)), 
		'', 
		($user_id > 0) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/access', 'Users', 'Delete', array('user_id' => $user_id)) : ''
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'UsersForm');
?>
	<h2><?= _t('Login information'); ?></h2>
<?php
		// ca_users fields
		foreach($t_user->getFormFields() as $f => $user_info) {
			
			switch($f) {
				case 'password':
					if(AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_UPDATE_PASSWORDS__)) {
?>
						<div class="userPasswordInput">
							<?= $t_user->htmlFormElement($f, null, array('includeVisibilityButton' => true, 'value' => '', 'placeholder' => _t('Change password'), 'field_errors' => $this->request->getActionErrors('field_'.$f))); ?>
						</div>
						<div class="userPasswordInput">
							<?= $t_user->htmlFormElement($f, str_replace('^LABEL', _t("Confirm password"), $this->appconfig->get('form_element_display_format')), array('includeVisibilityButton' => true, 'value' => '', 'placeholder' => _t('Confirm password'), 'name' => 'password_confirm', 'LABEL' => 'Confirm password')); ?>
							<div id="password_errors"></div>
						</div>
<?php
					}
					break;
				case 'entity_id':
					print "<div class='formLabel'><span id='_ca_user_entity_id_'>".($entity_label = $t_user->getFieldInfo('entity_id', 'LABEL'))."</span><br/>";
					$lookup_template = $this->request->config->get('ca_entities_lookup_settings');
					if(!is_array($lookup_template) && $lookup_template) {
						$lookup_template = [$lookup_template];
					}
					$template = join($this->request->config->get('ca_entities_lookup_delimiter'), $lookup_template);
					print caHTMLTextInput('entity_id_lookup', array('class' => 'lookupBg', 'size' => 70, 'id' => 'ca_users_entity_id_lookup', 'value' => caProcessTemplateForIDs($template, 'ca_entities', array($entity_id = $t_user->get('entity_id')))));
					if ($entity_id) { print "<a href='#' onclick='caClearUserEntityID(); return false;'>"._t('Clear')." &rsaquo;</a>\n"; }
					print caHTMLHiddenInput('entity_id', array('value' => $entity_id, 'id' => 'ca_users_entity_id_value'));
					print "</div>\n";
					
					ToolTipManager::add(
						'#_ca_user_entity_id_', "<h3>{$entity_label}</h3>\n".$t_user->getFieldInfo('entity_id', 'DESCRIPTION')
					);
					break;
				default:
					print $t_user->htmlFormElement($f, null, array('field_errors' => $this->request->getActionErrors('field_'.$f)));
					break;
			}
		}
?>
					<div class="roles">
						<h2><?= _t('Roles'); ?></h2>
						
						<div class="roleList">
<?php
		// roles
		print $t_user->roleListAsHTMLFormElement(['name' => 'roles', 'size' => 6, 'renderAs' => DT_CHECKBOXES, 'includeLabel' => false]);
?>
						</div>
					</div>
					<div class="groups">
						<h2><?= _t('Groups'); ?></h2>
						
						<div class="groupList">
<?php
		// Groups
		print $t_user->groupListAsHTMLFormElement(['name' => 'groups', 'size' => 6, 'renderAs' => DT_CHECKBOXES, 'includeLabel' => false]);
?>
						</div>
					</div>
					<h2><?= _t('User profile'); ?></h2>
<?php
		// Output user profile settings if defined
		$user_profile_settings = $this->getVar('profile_settings');
		if (is_array($user_profile_settings) && sizeof($user_profile_settings)) {
			foreach($user_profile_settings as $field => $info) {
				if($errors[$field] ?? null){
					print "<div class='formErrors' style='text-align: left;'>".$errors[$field]."</div>";
				}
				print $info['element']."\n";
			}
		}
?>				
	</form>
<?php
	print $control_box;
?>
</div>
	<div class="editorBottomPadding"><!-- empty --></div>
	
<script type='text/javascript'>
	jQuery(document).ready(function() {
 		jQuery('#ca_users_entity_id_lookup').autocomplete( 
			{ 
				minLength: 3, delay: 800,
				source: '<?= caNavUrl($this->request, 'lookup', 'Entity', 'Get', array()); ?>',	
				select: function(event,ui) {
					if (parseInt(ui.item.id) >= 0) {
						jQuery('#ca_users_entity_id_value').val(parseInt(ui.item.id));
					}
				}
			}
		);
		
		const pwchecker = caUI.initPasswordChecker({
			'policies': <?= json_encode($password_policies); ?>,
			'messagePrefix': <?= json_encode(caNavIcon(__CA_NAV_ICON_ALERT__, 1).' '); ?>,
			'minimumPasswordScore': <?= (int)$this->getVar('requireMinimumPasswordScore'); ?>,
			'messages': {
				'INCLUDES_PHRASE': <?= json_encode(_t('Password must not include the phrase "%value"')); ?>,
				'DOES_NOT_INCLUDE_SPECIAL_CHARACTERS_SINGULAR': <?= json_encode(_t('Password must include at least %value special character')); ?>,
				'DOES_NOT_INCLUDE_SPECIAL_CHARACTERS_PLURAL': <?= json_encode(_t('Password must include at least %value special characters')); ?>,
				'DOES_NOT_INCLUDE_DIGITS_SINGULAR': <?= json_encode(_t('Password must include at least %value digit')); ?>,
				'DOES_NOT_INCLUDE_DIGITS_PLURAL': <?= json_encode(_t('Password must include at least %value digits')); ?>,
				'DOES_NOT_INCLUDE_LOWERCASE_SINGULAR': <?= json_encode(_t('Password must include at least %value lowercase character')); ?>,
				'DOES_NOT_INCLUDE_LOWERCASE_PLURAL': <?= json_encode(_t('Password must include at least %value lowercase characters')); ?>,
				'DOES_NOT_INCLUDE_UPPERCASE_SINGULAR': <?= json_encode(_t('Password must include at least %value uppercase character')); ?>,
				'DOES_NOT_INCLUDE_UPPERCASE_PLURAL': <?= json_encode(_t('Password must include at least %value uppercase characters')); ?>,
				'IS_NOT_MIN_LENGTH_SINGULAR': <?= json_encode(_t('Password must be at least %value character')); ?>,
				'IS_NOT_MIN_LENGTH_PLURAL': <?= json_encode(_t('Password must be at least %value characters')); ?>,
				'IS_NOT_MAX_LENGTH_SINGULAR': <?= json_encode(_t('Password must be at less than %value character')); ?>,
				'IS_NOT_MAX_LENGTH_PLURAL': <?= json_encode(_t('Password must be at less than %value characters')); ?>,
				'DO_NOT_MATCH': <?= json_encode(_t('Passwords do not match')); ?>,
				'EASY_TO_GUESS': <?= json_encode(_t('Password is easy to guess')); ?>
			},
		});
		jQuery("#password, #password_confirm").on('keyup', function(e) {
 			pwchecker.checkPasswordInput('password', 'password_errors');
		});
	});
	
	function caClearUserEntityID() {
		jQuery('#ca_users_entity_id_lookup').val('');
		jQuery('#ca_users_entity_id_value').val(0);
	}	
 </script>
