<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/AccessControl.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
trait CLIUtilsAccessControl { 
	# -------------------------------------------------------
	/**
	 * Reset user password
	 */
	public static function reset_password($po_opts=null) {
		if (!($vs_user_name = (string)$po_opts->getOption('user')) && !($vs_user_name = (string)$po_opts->getOption('username'))) {
			$vs_user_name = readline("User: ");
		}
		if (!$vs_user_name) {
			CLIUtils::addError(_t("You must specify a user"));
			return false;
		}
		
		$t_user = new ca_users();
		if ((!$t_user->load(array("user_name" => $vs_user_name)))) {
			CLIUtils::addError(_t("User name %1 does not exist", $vs_user_name));
			return false;
		}
		
		if (!($vs_password = (string)$po_opts->getOption('password'))) {
			$vs_password = CLIUtils::_getPassword(_t('Password: '), true);
			print "\n\n";
		}
		if(!$vs_password) {
			CLIUtils::addError(_t("You must specify a password"));
			return false;
		}
		
		if($t_user->get('active') == 0) {
			CLIUtils::addMessage(_t('Set user %1 as active', $vs_user_name), array('color' => 'bold_green'));
		}
		$t_user->set('password', $vs_password);
		$t_user->set('active', 1);
		$t_user->update();
		if ($t_user->numErrors()) {
			CLIUtils::addError(_t("Password change for user %1 failed: %2", $vs_user_name, join("; ", $t_user->getErrors())));
			return false;
		}
		CLIUtils::addMessage(_t('Changed password for user %1', $vs_user_name), array('color' => 'bold_green'));
		return true;
		
		CLIUtils::addError(_t("You must specify a user"));
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Grab password from STDIN without showing input on STDOUT
	 */
	private static function _getPassword($ps_prompt, $pb_stars = false) {
		if ($ps_prompt) fwrite(STDOUT, $ps_prompt);
		// Get current style
		$vs_old_style = shell_exec('stty -g');

		if ($pb_stars === false) {
			shell_exec('stty -echo');
			$vs_password = rtrim(fgets(STDIN), "\n");
		} else {
			shell_exec('stty -icanon -echo min 1 time 0');

			$vs_password = '';
			while (true) {
				$vs_char = fgetc(STDIN);

				if ($vs_char === "\n") {
					break;
				} else if (ord($vs_char) === 127) {
					if (strlen($vs_password) > 0) {
						fwrite(STDOUT, "\x08 \x08");
						$vs_password = substr($vs_password, 0, -1);
					}
				} else {
					fwrite(STDOUT, "*");
					$vs_password .= $vs_char;
				}
			}
		}

		// Reset old style
		shell_exec('stty ' . $vs_old_style);

		// Return the password
		return $vs_password;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_passwordParamList() {
		return array(
			"username|n=s" => _t("User name to reset password for."),
			"user|u=s" => _t("User name to reset password for."),
			"password|p=s" => _t("New password for user")
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_passwordUtilityClass() {
		return _t('Access control');
	}

	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_passwordShortHelp() {
		return _t('Reset a user\'s password');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_passwordHelp() {
		return _t('Reset a user\'s password.');
	}
	# -------------------------------------------------------
	/**
	 * Reset user password
	 */
	public static function add_account($po_opts=null) {
		if (!($user_name = (string)$po_opts->getOption('user')) && !($user_name = (string)$po_opts->getOption('username'))) {
			$user_name = readline("User: ");
		}
		if (!$user_name) {
			CLIUtils::addError(_t("You must specify a user name"));
			return false;
		}
		
		$t_user = new ca_users();
		if (($t_user->load(array("user_name" => $user_name)))) {
			CLIUtils::addError(_t("User name %1 already exists", $user_name));
			return false;
		}
		
		$auto_generate_password = false;
		if (!($password = (string)$po_opts->getOption('password'))) {
			if($auto_generate_password = (bool)$po_opts->getOption('auto-generate-password')) {
				$password = caGenerateRandomPassword(8);
			} else {
				$password = CLIUtils::_getPassword(_t('Password: '), true);
				print "\n\n";
			}
		}
		
		if (!($lastname = (string)$po_opts->getOption('lastname'))) {
			CLIUtils::addError(_t("You must specify a last name for the user"));
			return false;
		}
		$firstname = (string)$po_opts->getOption('firstname');
		
		if (!($email = (string)$po_opts->getOption('email'))) {
			CLIUtils::addError(_t("You must specify an email address for the user"));
			return false;
		}
		
		if (!($user_class = strtoupper((string)$po_opts->getOption('userclass')))) {
			$user_class = 'FULL';
		} elseif(!in_array($user_class, ['FULL', 'PUBLIC'])) {
			CLIUtils::addError(_t("Invalid userclass. Must be either FULL or PUBLIC"));
			return false;	
		}
		
		$roles = (string)$po_opts->getOption('roles');
		$role_list = preg_split('![,;]+!', $roles);
		
		$t_roles = new ca_user_roles();
		$valid_role_codes = array_map(function($v) { return $v['code']; }, $t_roles->getRoleList());
		$role_list = array_filter($role_list, function($v) use ($valid_role_codes) {
			return in_array($v, $valid_role_codes);
		});
		
		if (!sizeof($role_list)) {
			CLIUtils::addError(_t("You must specify at least one valid role for the user"));
			return false;
		}
		
		if(strlen($groups = (string)$po_opts->getOption('groups'))) {
			$group_list = preg_split('![,;]+!', $groups);
		
			$t_groups = new ca_user_groups();
			$valid_group_codes = array_map(function($v) { return $v['code']; }, $t_groups->getGroupList());
			$group_list = array_filter($group_list, function($v) use ($valid_group_codes) {
				return in_array($v, $valid_group_codes);
			});
		
			if (!sizeof($group_list)) {
				CLIUtils::addError(_t("You must specify at least one valid group for the user, or omit the group option"));
				return false;
			}
		}
		
		$t_user->set('user_name', $user_name);
		$t_user->set('email', $email);
		$t_user->set('fname', $firstname);
		$t_user->set('lname', $lastname);
		$t_user->set('user_class', ($user_class == 'FULL') ? 0 : 1);
		$t_user->set('password', $password);
		$t_user->set('active', 1);
		$t_user->insert();
		if ($t_user->numErrors()) {
			CLIUtils::addError(_t("Account creation for user %1 failed: %2", $user_name, join("; ", $t_user->getErrors())));
			return false;
		}
		
		$t_user->addRoles($role_list);
		if ($t_user->numErrors()) {
			CLIUtils::addError(_t("Could not add roles to account for user %1: %2", $user_name, join("; ", $t_user->getErrors())));
		}
		
		if(is_array($group_list) && sizeof($group_list)) {
			$t_user->addToGroups($group_list);
			if ($t_user->numErrors()) {
				CLIUtils::addError(_t("Could not add groups to account for user %1: %2", $user_name, join("; ", $t_user->getErrors())));
			}
		}
		
		CLIUtils::addMessage(_t('Created account for user %1', $user_name), array('color' => 'bold_green'));
		if($auto_generate_password) {
			CLIUtils::addMessage(_t('Autogenerated password for user %1 is %2', $user_name, $password), array('color' => 'bold_green'));
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function add_accountParamList() {
		return array(
			"username|n=s" => _t("User name for new account."),
			"user|u=s" => _t("User name for new account."),
			"password|p=s" => _t("Password for new account"),
			"email|e=s" => _t("Email address for new account"),
			"firstname|f=s" => _t("First name of user"),
			"lastname|l=s" => _t("Last name of user"),
			"userclass|c=s" => _t("Class of account. User FULL for full login; PUBLIC for public-only login. Default is FULL."),
			"auto-generate-password|a=s" => _t("Generate new password for account."),
			"roles|r=s" => _t("Comma-separated list of roles to add to account"),
			"groups|g=s" => _t("Comma-separated list of groups to add to account"),
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function add_accountUtilityClass() {
		return _t('Access control');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function add_accountShortHelp() {
		return _t('Add a new user account');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function add_accountHelp() {
		return _t('Add a new user account.');
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public static function apply_acl_inheritance($po_opts=null) {
		if(!defined('__CA_DISABLE_ACL__')) { define('__CA_DISABLE_ACL__', true); }
		
		$db = new Db();
		
		$table = (string)$po_opts->getOption('table');
		
		$tables = caGetPrimaryTables();
		if($table && isset($tables[$table])) { $tables = [$table => $tables[$table]]; }
		
		foreach($tables as $table) {
			$qr = $table::findAsSearchResult('*');
			print CLIProgressBar::start($qr->numHits(), _t('Applying inheritance using %1', $qr->tableName()));
	
			while($qr->nextHit()) {
				if(!($t_instance = $qr->getInstance())) { continue; }
				if(!$t_instance->supportsACL()) { break; }
				ca_acl::applyACLInheritanceToChildrenFromRow($t_instance);
				
				if($table === 'ca_collections') { 
					ca_acl::applyACLInheritanceToRelatedFromRow($t_instance, 'ca_objects');
				}
				print CLIProgressBar::next(1, _t("Applying inheritance for %1", $t_instance->get('idno')));
			}
			print CLIProgressBar::finish();
			
			print CLIProgressBar::start(1, _t('Recreating missing ACL global entries'));
			ca_acl::setGlobalEntries($table, $db);
			print CLIProgressBar::finish();
			
		}
		
		print CLIProgressBar::start(1, _t('Removing redundant ACL entries'));
		ca_acl::removeRedundantACLEntries($db);
		print CLIProgressBar::finish();	
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function apply_acl_inheritanceParamList() {
		return array(
			"table|t=s" => _t("Only apply inheritance for table"),
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function apply_acl_inheritanceUtilityClass() {
		return _t('Access control');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function apply_acl_inheritanceShortHelp() {
		return _t('Apply inherited access control lists.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function apply_acl_inheritanceHelp() {
		return _t('Apply inherited access control lists.');
	}
	# -------------------------------------------------------
}
