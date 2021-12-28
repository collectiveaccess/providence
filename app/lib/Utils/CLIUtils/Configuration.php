<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Configuration.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
trait CLIUtilsConfiguration { 
	# -------------------------------------------------------
	/**
	 * Create a fresh installation of CollectiveAccess based on contents of setup.php.  This is essentially a CLI
	 * command wrapper for the installation process, as /install/inc/page2.php is a web wrapper.
	 * @param Zend_Console_Getopt $po_opts
	 * @param bool $pb_installing
	 * @return bool
	 */
	public static function install($po_opts=null, $pb_installing = true) {
		require_once(__CA_BASE_DIR__ . '/install/inc/Installer.php');

		define('__CollectiveAccess_Installer__', 1);

		if (!($profile = $po_opts->getOption('profile-name'))) {
			CLIUtils::addError(_t("Missing required parameter: profile-name"));
			return false;
		}

		if ($pb_installing && !$po_opts->getOption('admin-email')) {
			CLIUtils::addError(_t("Missing required parameter: admin-email"));
			return false;
		}
		$vs_profile_directory = $po_opts->getOption('profile-directory');
		$vs_profile_directory = $vs_profile_directory ? $vs_profile_directory : __CA_BASE_DIR__ . '/install/profiles';
		$t_total = new Timer();

		$vo_installer = new \Installer\Installer(
			$vs_profile_directory,
			$profile,
			$po_opts->getOption('admin-email'),
			$po_opts->getOption('overwrite'),
			$po_opts->getOption('debug')
		);

		$vb_quiet = $po_opts->getOption('quiet');

		// if profile validation against XSD failed, we already have an error here
		if($vo_installer->numErrors()){
			CLIUtils::addError(_t(
				"There were errors parsing the profile(s): %1",
				"\n * " . join("\n * ", $vo_installer->getErrors())
			));
			return false;
		}
		if($pb_installing){
			if (!$vb_quiet) { CLIUtils::addMessage(_t("Performing preinstall tasks")); }
			$vo_installer->performPreInstallTasks();

			if (!$vb_quiet) { CLIUtils::addMessage(_t("Loading schema")); }
			$vo_installer->loadSchema();

			if($vo_installer->numErrors()){
				CLIUtils::addError(_t(
					"There were errors loading the database schema: %1",
					"\n * " . join("\n * ", $vo_installer->getErrors())
				));
				return false;
			}
		}

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing locales")); }
		$vo_installer->processLocales();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing lists")); }
		$vo_installer->processLists();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing relationship types")); }
		$vo_installer->processRelationshipTypes();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing metadata elements")); }
		$vo_installer->processMetadataElements();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing metadata dictionary")); }
		$vo_installer->processMetadataDictionary();

		if(!$po_opts->getOption('skip-roles')){
			if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing access roles")); }
			$vo_installer->processRoles();
		}

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing user groups")); }
		$vo_installer->processGroups();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing user logins")); }
		$va_login_info = $vo_installer->processLogins($pb_installing);

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing user interfaces")); }
		$vo_installer->processUserInterfaces();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing displays")); }
		$vo_installer->processDisplays();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing search forms")); }
		$vo_installer->processSearchForms();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Setting up hierarchies")); }
		$vo_installer->processMiscHierarchicalSetup();
		
		if (!$vb_quiet) { CLIUtils::addMessage(_t("Processing metadata alerts")); }
		$vo_installer->processMetadataAlerts();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Performing post install tasks")); }
		$vo_installer->performPostInstallTasks();

		if (!$vb_quiet) { CLIUtils::addMessage(_t("Installation complete")); }

		$vs_time = _t("Installation took %1 seconds", $t_total->getTime(0));

		if($vo_installer->numErrors()){
			CLIUtils::addError(_t(
				"There were errors during installation: %1\n(%2)",
				"\n * " . join("\n * ", $vo_installer->getErrors()),
				$vs_time
			));
			return false;
		}
		if($pb_installing){
			CLIUtils::addMessage(_t(
				"Installation was successful!\n\nYou can now login with the following logins: %1\nMake a note of these passwords!",
				"\n * " . join(
					"\n * ",
					array_map(
						function ($username, $password) {
							return _t("username %1 and password %2", $username, $password);
						},
						array_keys($va_login_info),
						array_values($va_login_info)
					)
				)
			));
		} else {
			CLIUtils::addMessage(_t("Update of installation profile successful"));
		}

		CLIUtils::addMessage($vs_time);
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function installParamList() {
		return array(
			"profile-name|n=s" => _t('Name of the profile to install (filename in profiles directory, minus the .xml extension).'),
			"profile-directory|p=s" => _t('Directory to get profile. Default is: "%1". This directory must contain the profile.xsd schema so that the installer can validate the installation profile.', __CA_BASE_DIR__ . '/install/profiles/xml'),
			"admin-email|e=s" => _t('Email address of the system administrator (user@domain.tld).'),
			"overwrite" => _t('Flag must be set in order to overwrite an existing installation.  Also, the __CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ global must be set to a true value.'),
			"debug|d" => _t('Debug flag for installer.'),
			"skip-roles|s" => _t('Skip Roles. Default is false, but if you have many roles and access control enabled then install may take some time')
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function installUtilityClass() {
		return _t('Configuration');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function installHelp() {
		return _t("Performs a fresh installation of CollectiveAccess using the configured values in setup.php.

\tThe profile name and administrator email address must be given as per the web-based installer.

\tIf the database schema already exists, this operation will fail, unless the --overwrite flag is set, in which case all existing data will be deleted (use with caution!).");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function installShortHelp() {
		return _t("Performs a fresh installation of CollectiveAccess using the configured values in setup.php.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_installation_profileUtilityClass() {
		return _t('Configuration');
	}
	# -------------------------------------------------------
	public static function update_installation_profileParamList() {
		$va_params = self::installParamList();
		unset($va_params['overwrite']);
		unset($va_params['admin-email|e=s']);
		return $va_params;
	}
	# -------------------------------------------------------
	public static function update_installation_profile($po_opts=null) {
		self::install($po_opts, false);
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_installation_profileHelp() {
		return _t("Updates the configuration to match a supplied profile name.") ."\n".
		"\t" . _t("This function only creates new values and is useful if you want to append changes from one profile onto another.")."\n".
		"\t" . _t("Your new profile must exist in a directory that contains the profile.xsd schema and must validate against that schema in order for the update to apply successfully.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_installation_profileShortHelp() {
		return _t("Updates the installation profile to match a supplied profile name. Backup your database before you use this!");
	}
	
	
	# -------------------------------------------------------
	/**
	 * Export current system configuration as an XML installation profile
	 */
	public static function export_profile($po_opts=null) {
		require_once(__CA_LIB_DIR__."/ConfigurationExporter.php");

		if(!class_exists("DOMDocument")){
			CLIUtils::addError(_t("The PHP DOM extension is required to export profiles"));
			return false;
		}

		$vs_output = $po_opts->getOption("output");
		$va_output = explode("/", $vs_output);
		array_pop($va_output);
		if ($vs_output && (!is_dir(join("/", $va_output)))) {
			CLIUtils::addError(_t("Cannot write profile to '%1'", $vs_output));
			return false;
		}

		$vn_timestamp = null;
		if($po_opts->getOption("timestamp")) {
			$vn_timestamp = intval($po_opts->getOption("timestamp"));
		}

		$vs_profile = ConfigurationExporter::exportConfigurationAsXML($po_opts->getOption("name"), $po_opts->getOption("description"), $po_opts->getOption("base"), $po_opts->getOption("infoURL"), $vn_timestamp);

		if ($vs_output) {
			file_put_contents($vs_output, $vs_profile);
		} else {
			print $vs_profile;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_profileParamList() {
		return array(
			"base|b-s" => _t('File name of profile to use as base profile. Omit if you do not want to use a base profile. (Optional)'),
			"name|n=s" => _t('Name of the profile, used for "profileName" element.'),
			"infoURL|u-s" => _t('URL pointing to more information about the profile. (Optional)'),
			"description|d-s" => _t('Description of the profile, used for "profileDescription" element. (Optional)'),
			"output|o-s" => _t('File to output profile to. If omitted profile is printed to standard output. (Optional)'),
			"timestamp|t-s" => _t('Limit output to configuration changes made after this UNIX timestamp. (Optional)'),
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_profileUtilityClass() {
		return _t('Configuration');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_profileShortHelp() {
		return _t("Export current system configuration as an XML installation profile.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function export_profileHelp() {
		return _t("Help text to come.");
	}
	
	
	# -------------------------------------------------------
	/**
	 * @param Zend_Console_Getopt|null $po_opts
	 * @return bool
	 */
	public static function push_config_changes($po_opts=null) {
		require_once(__CA_LIB_DIR__.'/ConfigurationExporter.php');

		if (!($vs_targets = $po_opts->getOption('targets'))) {
			CLIUtils::addError(_t("Missing required parameter: targets. Try checking the help for this subcommand."));
			return false;
		}

		if (!($vs_user = $po_opts->getOption('username'))) {
			CLIUtils::addError(_t("Missing required parameter: username. Try checking the help for this subcommand."));
			return false;
		}

		if (!($vs_password = (string)$po_opts->getOption('password'))) {
			$vs_password = CLIUtils::_getPassword(_t('Password: '), true);
			print "\n\n";
		}

		$vn_timestamp = intval($po_opts->getOption('timestamp'));
		if (!($vs_log_dir = $po_opts->getOption('log'))) {
			$vs_log_dir = Configuration::load()->get('batch_metadata_import_log_directory');
		}

		$vn_log_level = $po_opts->getOption('log-level');

		$o_log = (is_writable($vs_log_dir)) ? new KLogger($vs_log_dir, $vn_log_level) : null;

		if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Start preparing to push config changes")); }

		$vn_timestamp = intval($vn_timestamp);

		$va_targets = preg_split('/[;|]/u', $vs_targets);

		$o_vars = new ApplicationVars();
		$va_timestamps = $o_vars->getVar('push-config-changes-timestamps');

		foreach($va_targets as $vs_target) {
			$vs_target = trim($vs_target);

			CLIUtils::addMessage(_t("Processing target %1", $vs_target));
			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Processing target %1", $vs_target)); }

			if(!isURL($vs_target)) {
				CLIUtils::addError(_t("The target '%1' doesn't seem to be in URL format", $vs_target));
				if ($o_log) { $o_log->logError(_t("[push-config-changes] The target '%1' doesn't seem to be in URL format", $vs_target)); }
				return false;
			}

			$vs_target = "{$vs_target}/service.php/model/updateConfig";

			if(isset($va_timestamps[$vs_target])) {
				$vn_target_timestamp = intval($va_timestamps[$vs_target]);
			} else {
				$vn_target_timestamp = $vn_timestamp ?: 0;
			}

			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Service endpoint is '%1'. Timestamp for diff config is %2", $vs_target, $vn_target_timestamp)); }

			$vs_config = ConfigurationExporter::exportConfigurationAsXML('', '', '', '', $vn_target_timestamp, true);
			$va_timestamps[$vs_target] = time();
			CLIUtils::addMessage(_t("Finished partial configuration export for target %1", $vs_target));

			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Configuration fragment for target '%1' is \n %2", $vs_target, $vs_config)); }

			$vo_handle = curl_init($vs_target);
			curl_setopt($vo_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($vo_handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($vo_handle, CURLOPT_TIMEOUT, 600);
			curl_setopt($vo_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($vo_handle, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($vo_handle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($vo_handle, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($vo_handle, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

			// basic auth
			curl_setopt($vo_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($vo_handle, CURLOPT_USERPWD, $vs_user.':'.$vs_password);

			// add config as request body
			curl_setopt($vo_handle, CURLOPT_POSTFIELDS, $vs_config);

			$vs_exec = curl_exec($vo_handle);
			$vn_code = curl_getinfo($vo_handle, CURLINFO_HTTP_CODE);
			curl_close($vo_handle);

			if($vn_code != 200) {
				CLIUtils::addError(_t("Pushing to target '%1' seems to have failed. HTTP response code was %2.", $vs_target, $vn_code));
				if ($o_log) { $o_log->logError(_t("[push-config-changes] Pushing to target '%1' seems to have failed. HTTP response code was %2. Enable debug logging mode to get more info below.", $vs_target, $vn_code)); }
			}

			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Target '%1' responded with 200 OK", $vs_target)); }

			$va_response = @json_decode($vs_exec, true);

			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Decoded response from target '%1' is '%2'", $vs_target, print_r($va_response, true))); }

			if(!isset($va_response['ok']) || !$va_response['ok']) {
				if(is_array($va_errors = $va_response['errors'])) {
					CLIUtils::addError(_t("Pushing to target '%1' seems to have failed. Response was not marked as okay. Errors were: %2", $vs_target, join(',', $va_errors)));
				} else {
					CLIUtils::addError(_t("Pushing to target '%1' seems to have failed. Response was not marked as okay. Raw response was: %2", $vs_target, $vs_exec));
				}
			}

			if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Finished processing target '%1'", $vs_target)); }
		}

		$o_vars->setVar('push-config-changes-timestamps', $va_timestamps);
		$o_vars->save();
		if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Saved sync timestamps are: %1", print_r($va_timestamps, true))); }

		CLIUtils::addMessage(_t("All done"));
		if ($o_log) { $o_log->logDebug(_t("[push-config-changes] Finished ...")); }
	}

	public static function push_config_changesParamList() {
		return [
			"targets|t=s" => _t('Comma- or semicolon separated list of target systems to push changes to. We assume the same service account exists on all of these systems'),
			"username|u=s" => _t('User name to use to log into the targets. We assume the same credentials can be used to log into all target systems.'),
			"password|p=s" => _t('Password to use to log into the targets. We assume the same credentials can be used to log into all target systems.'),
			"timestamp|s=s" => _t('Timestamp to use to filter the configuration changes that should be exported/pushed. Optional. The timestamp is only used for the very first push to that system. After that the master system will store the last push timestamp and use that instead. This parameter is a fixed offset/"starting point" of sorts.'),
			"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
			"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),

			// @todo some params that control excluding/including specific stuff?
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function push_config_changesUtilityClass() {
		return _t('Configuration');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function push_config_changesShortHelp() {
		return _t('Pushes configuration changes from this system out to other systems.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function push_config_changesHelp() {
		return _t('Pushes configuration changes from this system out to other systems.');
	}
}
