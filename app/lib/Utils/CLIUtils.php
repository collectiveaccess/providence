<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2018 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */

 	require_once(__CA_LIB_DIR__.'/Utils/CLIBaseUtils.php');

	class CLIUtils extends CLIBaseUtils {
		# -------------------------------------------------------
		# CLI utility implementations
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

			if (!$po_opts->getOption('profile-name')) {
				CLIUtils::addError(_t("Missing required parameter: profile-name"));
				return false;
			}

			if ($pb_installing && !$po_opts->getOption('admin-email')) {
				CLIUtils::addError(_t("Missing required parameter: admin-email"));
				return false;
			}
			$vs_profile_directory = $po_opts->getOption('profile-directory');
			$vs_profile_directory = $vs_profile_directory ? $vs_profile_directory : __CA_BASE_DIR__ . '/install/profiles/xml';
			$t_total = new Timer();

			$vo_installer = new Installer(
				$vs_profile_directory,
				$po_opts->getOption('profile-name'),
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
				"quiet|q" => _t('Suppress progress messages.'),
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
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_search_index($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Search/SearchIndexer.php");
			ini_set('memory_limit', '4000m');
			set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */

			$o_si = new SearchIndexer();

			$va_tables = null;
			if ($vs_tables = (string)$po_opts->getOption('tables')) {
				$va_tables = preg_split("![;,]+!", $vs_tables);
			}
			$o_si->reindex($va_tables, array('showProgress' => true, 'interactiveProgressDisplay' => true));

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexParamList() {
			return array(
				"tables|t-s" => _t('Specific tables to reindex, separated by commas or semicolons. If omitted all tables will be reindexed.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexUtilityClass() {
			return _t('Search');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexHelp() {
			return _t("CollectiveAccess relies upon indices when searching your data. Indices are simply summaries of your data designed to speed query processing. The precise form and characteristics of the indices used will vary with the type of search engine you are using. They may be stored on disk, in a database or on another server, but their purpose is always the same: to make searches execute faster.

\tFor search results to be accurate the database and indices must be in sync. CollectiveAccess simultaneously updates both the database and indicies as you add, edit and delete data, keeping database and indices in agreement. Occasionally things get out of sync, however. If the basic and advanced searches are consistently returning unexpected results you can use this tool to rebuild the indices from the database and bring things back into alignment.

\tNote that depending upon the size of your database rebuilding can take from a few minutes to several hours. During the rebuilding process the system will remain usable but search functions may return incomplete results. Browse functions, which do not rely upon indices, will not be affected.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexShortHelp() {
			return _t("Rebuilds search indices. Use this if you suspect the indices are out of sync with the database.");
		}
		# -------------------------------------------------------
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_sort_values() {
			$o_db = new Db();

			foreach(array(
				'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
				'ca_occurrences', 'ca_collections', 'ca_storage_locations',
				'ca_object_representations', 'ca_representation_annotations',
				'ca_list_items'
			) as $vs_table) {
				require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
				$t_table = new $vs_table;
				$vs_pk = $t_table->primaryKey();
				$qr_res = $o_db->query("SELECT {$vs_pk} FROM {$vs_table}");

				if ($vs_label_table_name = $t_table->getLabelTableName()) {
					require_once(__CA_MODELS_DIR__."/".$vs_label_table_name.".php");
					$t_label = new $vs_label_table_name;
					$vs_label_pk = $t_label->primaryKey();
					$qr_labels = $o_db->query("SELECT {$vs_label_pk} FROM {$vs_label_table_name}");

					print CLIProgressBar::start($qr_labels->numRows(), _t('Processing %1', $t_label->getProperty('NAME_PLURAL')));
					while($qr_labels->nextRow()) {
						$vn_label_pk_val = $qr_labels->get($vs_label_pk);
						print CLIProgressBar::next();
						if ($t_label->load($vn_label_pk_val)) {
							$t_table->logChanges(false);
							$t_label->setMode(ACCESS_WRITE);
							$t_label->update();
						}
					}
					print CLIProgressBar::finish();
				}

				print CLIProgressBar::start($qr_res->numRows(), _t('Processing %1 identifiers', $t_table->getProperty('NAME_SINGULAR')));
				while($qr_res->nextRow()) {
					$vn_pk_val = $qr_res->get($vs_pk);
					print CLIProgressBar::next();
					if ($t_table->load($vn_pk_val)) {
						$t_table->logChanges(false);
						$t_table->setMode(ACCESS_WRITE);
						$t_table->update();
					}
				}
				print CLIProgressBar::finish();
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesHelp() {
			return _t("CollectiveAccess relies upon sort values when sorting values that should not sort alphabetically, such as titles with articles (eg. The Man Who Fell to Earth should sort as Man Who Fell to Earth, The) and alphanumeric identifiers (eg. 2011.001 and 2011.2 should sort next to each other with leading zeros in the first ignored).

\tSort values are derived from corresponding values in your database. The internal format of sort values can vary between versions of CollectiveAccess causing erroneous sorting behavior after an upgrade. If you notice values such as titles and identifiers are sorting incorrectly, you may need to reload sort values from your data.

\tNote that depending upon the size of your database reloading sort values can take from a few minutes to an hour or more. During the reloading process the system will remain usable but search and browse functions may return incorrectly sorted results.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesShortHelp() {
			return _t("Rebuilds values use to sort by title, name and identifier.");
		}

		# -------------------------------------------------------
		/**
		 * Remove media present in media directories but not referenced in database (aka. orphan media)
		 */
		public static function remove_unused_media($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			$vb_delete_opt = (bool)$po_opts->getOption('delete');
			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$qr_reps = $o_db->query("SELECT * FROM ca_object_representations");
			print CLIProgressBar::start($qr_reps->numRows(), _t('Loading valid file paths from database'))."\n";

			$va_paths = array();
			while($qr_reps->nextRow()) {
				print CLIProgressBar::next();
				$va_versions = $qr_reps->getMediaVersions('media');
				if (!is_array($va_versions)) { continue; }
				foreach($va_versions as $vs_version) {
					$va_paths[$qr_reps->getMediaPath('media', $vs_version)] = true;
				}
			}
			print CLIProgressBar::finish();

			print CLIProgressBar::start(1, _t('Reading file list'));
			$va_contents = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/media/'.__CA_APP_NAME__, true, false);
			print CLIProgressBar::next();
			print CLIProgressBar::finish();

			$vn_delete_count = 0;

			print CLIProgressBar::start(sizeof($va_contents), _t('Finding unused files'));
			$va_report = array();
			foreach($va_contents as $vs_path) {
				print CLIProgressBar::next();
				if (!preg_match('!_ca_object_representation!', $vs_path)) { continue; } // skip non object representation files
				if (!$va_paths[$vs_path]) {
					$vn_delete_count++;
					if ($vb_delete_opt) {
						unlink($vs_path);
					}
					$va_report[] = $vs_path;
				}
			}
			print CLIProgressBar::finish()."\n";

			CLIUtils::addMessage(_t('There are %1 files total', sizeof($va_contents)));

			if(sizeof($va_contents) > 0) {
				$vs_percent = sprintf("%2.1f", ($vn_delete_count/sizeof($va_contents)) * 100)."%";
			} else {
				$vs_percent = '0.0%';
			}

			if ($vn_delete_count == 1) {
				CLIUtils::addMessage($vb_delete_opt ? _t("%1 file (%2) was deleted", $vn_delete_count, $vs_percent) : _t("%1 file (%2) is unused", $vn_delete_count, $vs_percent));
			} else {
				CLIUtils::addMessage($vb_delete_opt ?  _t("%1 files (%2) were deleted", $vn_delete_count, $vs_percent) : _t("%1 files (%2) are unused", $vn_delete_count, $vs_percent));
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_unused_mediaParamList() {
			return array(
				"delete|d" => _t('Delete unused files. Default is false.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_unused_mediaUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_unused_mediaShortHelp() {
			return _t("Detects and, optionally, removes media present in the media directories but not referenced in the database.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_unused_mediaHelp() {
			return _t("Help text to come");
		}
		# -------------------------------------------------------
		/**
		 * Permanently remove object representations marked for deletion, deleting referenced files on disk and reclaiming disk space
		 */
		public static function remove_deleted_representations($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			$vb_delete_opt = (bool)$po_opts->getOption('delete');
			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);
			$va_paths = array();

			$qr_reps = $o_db->query("SELECT * FROM ca_object_representations WHERE deleted=1");

			if($vb_delete_opt) {
				print CLIProgressBar::start($qr_reps->numRows(), _t('Removing deleted representations from database'));
			} else {
				print CLIProgressBar::start($qr_reps->numRows(), _t('Loading deleted representations from database'));
			}

			while($qr_reps->nextRow()) {
				print CLIProgressBar::next();
				$va_versions = $qr_reps->getMediaVersions('media');
				if (!is_array($va_versions)) { continue; }
				foreach($va_versions as $vs_version) {
					$va_paths[$qr_reps->getMediaPath('media', $vs_version)] = true;
				}

				if($vb_delete_opt) {
					$t_rep->load($qr_reps->get('representation_id'));
					$t_rep->setMode(ACCESS_WRITE);
					$t_rep->removeAllLabels();
					$t_rep->delete(true, array('hard' => true));
				}
			}

			print CLIProgressBar::finish().PHP_EOL;

			if($vb_delete_opt && ($qr_reps->numRows() > 0)) {
				CLIUtils::addMessage(_t('Done!'), array('color' => 'green'));
			} elseif($qr_reps->numRows() == 0) {
				CLIUtils::addMessage(_t('There are no deleted representations to process!'), array('color' => 'green'));
			} else {
				CLIUtils::addMessage(_t("%1 files are referenced by %2 deleted records. Both the records and the files will be deleted if you re-run the script with the -d (--delete) option and the correct permissions.", sizeof($va_paths), $qr_reps->numRows()));
				CLIUtils::addMessage(_t("It is highly recommended to create a full backup before you do this!"), array('color' => 'bold_red'));
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_deleted_representationsParamList() {
			return array(
				"delete|d" => _t('Removes representations marked as deleted. Default is false. Note that the system user that runs this script has to be able to write/delete the referenced media files if you want them to be removed.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_deleted_representationsUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_deleted_representationsShortHelp() {
			return _t("Detects and, optionally, completely removes object representations marked as deleted in the database. Files referenced by these records are also removed.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_deleted_representationsHelp() {
			return _t("Detects and, optionally, completely removes object representations marked as deleted in the database. Files referenced by these records are also removed. This can be useful if there has been a lot of fluctuation in your representation stock and you want to free up disk space.");
		}
		# -------------------------------------------------------
		/**
		 * Permanently remove records marked as deleted
		 */
		public static function purge_deleted($po_opts=null) {
			CLIUtils::addMessage(_t("Are you sure you want to PERMANENTLY remove all deleted records? This cannot be undone.\n\nType 'y' to proceed or 'N' to cancel, then hit return ", $vn_current_revision, __CollectiveAccess_Schema_Rev__));
            flush();
            ob_flush();
            $confirmation  =  trim( fgets( STDIN ) );
            if ( $confirmation !== 'y' ) {
                // The user did not say 'y'.
                return false;
            }

			$va_tables = Datamodel::getTableNames();
			$o_db = new Db();
			$va_tables_to_process = array_filter(array_map("trim", preg_split('![ ,;]!', (string)$po_opts->getOption('tables'))), "strlen");

			$vn_t = 0;
			foreach($va_tables as $vs_table) {
				if(is_array($va_tables_to_process) && sizeof($va_tables_to_process) && !in_array($vs_table, $va_tables_to_process)) { continue; }
				if (!$t_instance = Datamodel::getInstanceByTableName($vs_table, true)) { continue; }
				if (!$t_instance->hasField('deleted')) { continue; }
			
				$t_instance->setMode(ACCESS_WRITE);

				$qr_del = $o_db->query("SELECT * FROM {$vs_table} WHERE deleted=1");
				if($qr_del->numRows() > 0) {
					print CLIProgressBar::start($qr_del->numRows(), _t('Removing deleted %1 from database', $t_instance->getProperty('NAME_PLURAL')));

					$vn_c = 0;
					while($qr_del->nextRow()) {
						print CLIProgressBar::next();
						$t_instance->load($qr_del->get($t_instance->primaryKey()));
						$t_instance->setMode(ACCESS_WRITE);
						$t_instance->removeAllLabels();
						$t_instance->delete(true, array('hard' => true));
						$vn_c++;
					}

					CLIUtils::addMessage(_t('Removed %1 %2', $vn_c, $t_instance->getProperty(($vn_c == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL')), array('color' => 'green'));
					print CLIProgressBar::finish();
					
					$vn_t += $vn_c;
				}
			}
			
			if ($vn_t > 0) {
				CLIUtils::addMessage(_t('Done!'), array('color' => 'green'));
			} else {
				CLIUtils::addMessage(_t('Nothing to delete!'), array('color' => 'red'));
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function purge_deletedParamList() {
			return array(
				"tables|t=s" => _t('List of tables for which to purge deleted records. List multiple tables names separated by commas. If no table list is provided all tables are purged.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function purge_deletedUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function purge_deletedShortHelp() {
			return _t("Completely and permanently removes records marked as deleted in the database.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function purge_deletedHelp() {
			return _t("Completely and permanently removes records marked as deleted in the database. Files referenced by these records are also removed. Note that this cannot be undone.");
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
		 * Process queued tasks
		 */
		public static function process_task_queue($po_opts=null) {
			require_once(__CA_LIB_DIR__."/TaskQueue.php");

			$vo_tq = new TaskQueue();

			if($po_opts->getOption("restart")) { $vo_tq->resetUnfinishedTasks(); }

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Processing queued tasks...")); }
			$vo_tq->processQueue();		// Process queued tasks

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Processing recurring tasks...")); }
			$vo_tq->runPeriodicTasks();	// Process recurring tasks implemented in plugins
			if (!$po_opts->getOption("quiet")) {  CLIUtils::addMessage(_t("Processing complete.")); }

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueParamList() {
			return array(
				"quiet|q" => _t("Run without outputting progress information."),
				"restart|r" => _t("Restart/reset unfinished tasks before queue processing. This option can be useful when the task queue script (or the whole machine) crashed and you have 'zombie' entries in your task queue. This option shouldn't interfere with any existing task queue processes that are actually running.")
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueUtilityClass() {
			return _t('Cron');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueShortHelp() {
			return _t("Process queued tasks.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueHelp() {
			return _t("Help text to come.");
		}
		# -------------------------------------------------------
		/**
		 * Reprocess media
		 */
		public static function reprocess_media($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$quiet = $po_opts->getOption('quiet');
			$pa_mimetypes = caGetOption('mimetypes', $po_opts, null, ['delimiter' => [',', ';']]);
			$pa_versions = caGetOption('versions', $po_opts, null, ['delimiter' => [',', ';']]);
			$pa_kinds = caGetOption('kinds', $po_opts, 'all', ['forceLowercase' => true, 'validValues' => ['all', 'ca_object_representations', 'ca_attributes', 'icons'], 'delimiter' => [',', ';']]);
			
			if (in_array('all', $pa_kinds) || in_array('ca_object_representations', $pa_kinds)) {
				if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
				if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }


				if ($vn_id = (int)$po_opts->getOption('id')) {
					$vn_start = $vn_id;
					$vn_end = $vn_id;
				}

				$va_ids = array();
				if ($vs_ids = (string)$po_opts->getOption('ids')) {
					if (sizeof($va_tmp = explode(",", $vs_ids))) {
						foreach($va_tmp as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					}
				}

				$vs_sql_where = null;
				$va_params = array();

				if (sizeof($va_ids)) {
					$vs_sql_where = "WHERE ca_object_representations.representation_id IN (?)";
					$va_params[] = $va_ids;
				} else {
					if (
						(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
					) {
						$vs_sql_where = "WHERE ca_object_representations.representation_id >= ?";
						$va_params[] = $vn_start;
						if ($vn_end) {
							$vs_sql_where .= " AND ca_object_representations.representation_id <= ?";
							$va_params[] = $vn_end;
						}
					}
				}

				$vs_sql_joins = '';
				if ($vs_object_ids = (string)$po_opts->getOption('object_ids')) {
					$va_object_ids = explode(",", $vs_object_ids);
					foreach($va_object_ids as $vn_i => $vn_object_id) {
						$va_object_ids[$vn_i] = (int)$vn_object_id;
					}
					
					$vs_sql_where = ($vs_sql_where ? "WHERE " : " AND ")."(ca_objects_x_object_representations.object_id IN (?))";
					$vs_sql_joins = "INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.representation_id = ca_object_representations.representation_id";
					$va_params[] = $va_object_ids;
				}

				$qr_reps = $o_db->query("
					SELECT representation_id, media
					FROM ca_object_representations
					{$vs_sql_joins}
					{$vs_sql_where}
					ORDER BY ca_object_representations.representation_id
				", $va_params);

				if (!$quiet) { print CLIProgressBar::start($qr_reps->numRows(), _t('Re-processing representation media')); }
				while($qr_reps->nextRow()) {
					$va_media_info = $qr_reps->getMediaInfo('media');
					$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

					if (!$quiet) { print CLIProgressBar::next(1, _t("Re-processing %1", ($vs_original_filename ? $vs_original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id')))); }
					$vs_mimetype = $qr_reps->getMediaInfo('media', 'original', 'MIMETYPE');
					if(is_array($pa_mimetypes) && sizeof($pa_mimetypes)) {
						$vb_mimetype_match = false;
						foreach($pa_mimetypes as $vs_mimetype_pattern) {
							if(!preg_match("!^".preg_quote($vs_mimetype_pattern)."!", $vs_mimetype)) {
								continue;
							}
							$vb_mimetype_match = true;
							break;
						}
						if (!$vb_mimetype_match) { continue; }
					}

					$t_rep->load($qr_reps->get('representation_id'));
					$t_rep->set('media', $qr_reps->getMediaPath('media', 'original'), array('original_filename' => $vs_original_filename));

					if (is_array($pa_versions) && sizeof($pa_versions)) {
						$t_rep->update(array('updateOnlyMediaVersions' =>$pa_versions));
					} else {
						$t_rep->update();
					}

					if ($t_rep->numErrors()) {
						CLIUtils::addError(_t("Error processing representation media: %1", join('; ', $t_rep->getErrors())));
					}
				}
				if (!$quiet) { print CLIProgressBar::finish(); }
			}

			if ((in_array('all', $pa_kinds)  || in_array('ca_attributes', $pa_kinds)) && (!$vn_start && !$vn_end)) {
				// get all Media elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

				if (is_array($va_elements) && sizeof($va_elements)) {
					if (is_array($va_element_ids = caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))) && sizeof($va_element_ids)) {
						$qr_c = $o_db->query("
							SELECT count(*) c
							FROM ca_attribute_values
							WHERE
								element_id in (?)
						", array($va_element_ids));
						if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }

						if (!$quiet) { print CLIProgressBar::start($vn_count, _t('Re-processing attribute media')); }
						foreach($va_elements as $vs_element_code => $va_element_info) {
							$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
							$va_vals = $qr_vals->getAllFieldValues('value_id');
							foreach($va_vals as $vn_value_id) {
								$t_attr_val = new ca_attribute_values($vn_value_id);
								if ($t_attr_val->getPrimaryKey()) {
									$t_attr_val->setMode(ACCESS_WRITE);
									$t_attr_val->useBlobAsMediaField(true);

									$va_media_info = $t_attr_val->getMediaInfo('value_blob');
									$vs_original_filename = is_array($va_media_info) ? $va_media_info['ORIGINAL_FILENAME'] : '';

									if (!$quiet) { print CLIProgressBar::next(1, _t("Re-processing %1", ($vs_original_filename ? $vs_original_filename." ({$vn_value_id})" : $vn_value_id))); }


									$t_attr_val->set('value_blob', $t_attr_val->getMediaPath('value_blob', 'original'), array('original_filename' => $vs_original_filename));

									$t_attr_val->update();
									if ($t_attr_val->numErrors()) {
										CLIUtils::addError(_t("Error processing attribute media: %1", join('; ', $t_attr_val->getErrors())));
									}
								}
							}
						}
						if (!$quiet) { print CLIProgressBar::finish(); }
					}
				}
			}
			
			if ((in_array('all', $pa_kinds)  || in_array('icons', $pa_kinds)) && (!$vn_start && !$vn_end)) {
				$icon_tables = ['ca_list_items', 'ca_storage_locations', 'ca_editor_uis', 'ca_editor_ui_screens', 'ca_tours', 'ca_tour_stops'];
				
				foreach($icon_tables as $icon_table) {
					if (!($t_instance = Datamodel::getInstance($icon_table, true))) { continue; }
					if (!$quiet) { print CLIProgressBar::start($icon_table::find('*', ['returnAs' => 'count']), _t('Re-processing icons')); }
					$qr_vals = $o_db->query("SELECT ".($pk = $t_instance->primaryKey())." FROM {$icon_table}");
					$ids = $qr_vals->getAllFieldValues($pk);
					foreach($ids as $id) {
						if ($t_instance->load($id)) {
							$t_instance->setMode(ACCESS_WRITE);

							$media_info = $t_instance->getMediaInfo($pk);

							if (!$quiet) { print CLIProgressBar::next(1, _t("Re-processing %1 from %2", $id, $icon_table)); }


							$t_instance->set('icon', ($p = $t_instance->getMediaPath('icon', 'original')) ? $p : $t_instance->getMediaPath('icon', 'iconlarge'));

							$t_instance->update();
							if ($t_instance->numErrors()) {
								CLIUtils::addError(_t("Error processing icon media: %1", join('; ', $t_instance->getErrors())));
							}
						}	
					}
					if (!$quiet) { print CLIProgressBar::finish(); }
				}
			}


			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaParamList() {
			return array(
				"mimetypes|m-s" => _t("Limit re-processing to specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
				"versions|v-s" => _t("Limit re-processing to specified versions. Separate multiple versions with commas."),
				"quiet|q" => _t('Suppress progress messages.'),
				"start_id|s-n" => _t('Representation id to start reloading at'),
				"end_id|e-n" => _t('Representation id to end reloading at'),
				"id|i-n" => _t('Representation id to reload'),
				"ids|l-s" => _t('Comma separated list of representation ids to reload'),
				"object_ids|o-s" => _t('Comma separated list of object ids to reload'),
				"kinds|k-s" => _t('Comma separated list of kind of media to reprocess. Valid kinds are ca_object_representations (object representations), ca_attributes (metadata elements) and icons (icon graphics on list items, storage locations, editors, editor screens, tours and tour stops). You may also specify "all" to reprocess all kinds of media. Default is "all"')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaShortHelp() {
			return _t("Re-process existing media using current media processing configuration.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaHelp() {
			return _t("CollectiveAccess generates derivatives for all uploaded media.");
		}
		# -------------------------------------------------------
		/**
		 * Reindex PDF media by content for in-PDF search
		 */
		public static function reindex_pdfs($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			if (!caPDFMinerInstalled()) {
				CLIUtils::addError(_t("Can't reindex PDFs: PDFMiner is not installed."));
				return false;
			}

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$va_versions = array("original");
			$va_kinds = ($vs_kinds = $po_opts->getOption("kinds")) ? explode(",", $vs_kinds) : array();

			if (!is_array($va_kinds) || !sizeof($va_kinds)) {
				$va_kinds = array('all');
			}
			$va_kinds = array_map('strtolower', $va_kinds);

			if ((in_array('all', $va_kinds) || in_array('ca_object_representations', $va_kinds)) && (!$vn_start && !$vn_end)) {
				if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
				if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }


				if ($vn_id = (int)$po_opts->getOption('id')) {
					$vn_start = $vn_id;
					$vn_end = $vn_id;
				}

				$va_ids = array();
				if ($vs_ids = (string)$po_opts->getOption('ids')) {
					if (sizeof($va_tmp = explode(",", $vs_ids))) {
						foreach($va_tmp as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					}
				}

				$vs_sql_where = null;
				$va_params = array();

				if (sizeof($va_ids)) {
					$vs_sql_where = "WHERE representation_id IN (?)";
					$va_params[] = $va_ids;
				} else {
					if (
						(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
					) {
						$vs_sql_where = "WHERE representation_id >= ?";
						$va_params[] = $vn_start;
						if ($vn_end) {
							$vs_sql_where .= " AND representation_id <= ?";
							$va_params[] = $vn_end;
						}
					}
				}

				if ($vs_sql_where) { $vs_sql_where .= " AND mimetype = 'application/pdf'"; } else { $vs_sql_where = " WHERE mimetype = 'application/pdf'"; }

				$qr_reps = $o_db->query("
					SELECT *
					FROM ca_object_representations
					{$vs_sql_where}
					ORDER BY representation_id
				", $va_params);

				print CLIProgressBar::start($qr_reps->numRows(), _t('Reindexing PDF representations'));

				$vn_rep_table_num = $t_rep->tableNum();
				while($qr_reps->nextRow()) {
					$va_media_info = $qr_reps->getMediaInfo('media');
					$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

					print CLIProgressBar::next(1, _t("Reindexing PDF %1", ($vs_original_filename ? $vs_original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id'))));

					$t_rep->load($qr_reps->get('representation_id'));

					$vn_rep_id = $t_rep->getPrimaryKey();

					$m = new Media();
					if(($m->read($vs_path = $t_rep->getMediaPath('media', 'original'))) && is_array($va_locs = $m->getExtractedTextLocations())) {
						MediaContentLocationIndexer::clear($vn_rep_table_num, $vn_rep_id);
						foreach($va_locs as $vs_content => $va_loc_list) {
							foreach($va_loc_list as $va_loc) {
								MediaContentLocationIndexer::index($vn_rep_table_num, $vn_rep_id, $vs_content, $va_loc['p'], $va_loc['x1'], $va_loc['y1'], $va_loc['x2'], $va_loc['y2']);
							}
						}
						MediaContentLocationIndexer::write();
					} else {
						//CLIUtils::addError(_t("[Warning] No content to reindex for PDF representation: %1", $vs_path));
					}
				}
				print CLIProgressBar::finish();
			}

			if (in_array('all', $va_kinds)  || in_array('ca_attributes', $va_kinds)) {
				// get all Media elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

				$qr_c = $o_db->query("
					SELECT count(*) c
					FROM ca_attribute_values
					WHERE
						element_id in (?)
				", array(caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))));
				if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }


				$t_attr_val = new ca_attribute_values();
				$vn_attr_table_num = $t_attr_val->tableNum();

				print CLIProgressBar::start($vn_count, _t('Reindexing metadata attribute media'));
				foreach($va_elements as $vs_element_code => $va_element_info) {
					$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
					$va_vals = $qr_vals->getAllFieldValues('value_id');
					foreach($va_vals as $vn_value_id) {
						$t_attr_val = new ca_attribute_values($vn_value_id);
						if ($t_attr_val->getPrimaryKey()) {
							$t_attr_val->setMode(ACCESS_WRITE);
							$t_attr_val->useBlobAsMediaField(true);

							$va_media_info = $t_attr_val->getMediaInfo('value_blob');
							$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

							if (!is_array($va_media_info) || ($va_media_info['MIMETYPE'] !== 'application/pdf')) { continue; }

							print CLIProgressBar::next(1, _t("Reindexing %1", ($vs_original_filename ? $vs_original_filename." ({$vn_value_id})" : $vn_value_id)));

							$m = new Media();
							if(($m->read($vs_path = $t_attr_val->getMediaPath('value_blob', 'original'))) && is_array($va_locs = $m->getExtractedTextLocations())) {
								MediaContentLocationIndexer::clear($vn_attr_table_num, $vn_attr_table_num);
								foreach($va_locs as $vs_content => $va_loc_list) {
									foreach($va_loc_list as $va_loc) {
										MediaContentLocationIndexer::index($vn_attr_table_num, $vn_value_id, $vs_content, $va_loc['p'], $va_loc['x1'], $va_loc['y1'], $va_loc['x2'], $va_loc['y2']);
									}
								}
								MediaContentLocationIndexer::write();
							} else {
								//CLIUtils::addError(_t("[Warning] No content to reindex for PDF in metadata attribute: %1", $vs_path));
							}
						}
					}
				}
				print CLIProgressBar::finish();
			}


			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsParamList() {
			return array(
				"start_id|s-n" => _t('Representation id to start reindexing at'),
				"end_id|e-n" => _t('Representation id to end reindexing at'),
				"id|i-n" => _t('Representation id to reindex'),
				"ids|l-s" => _t('Comma separated list of representation ids to reindex'),
				"kinds|k-s" => _t('Comma separated list of kind of media to reindex. Valid kinds are ca_object_representations (object representations), and ca_attributes (metadata elements). You may also specify "all" to reindex both kinds of media. Default is "all"')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsShortHelp() {
			return _t("Reindex PDF media for in-viewer content search.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsHelp() {
			return _t("The CollectiveAccess document viewer can search text within PDFs and highlight matches. To enable this feature PDF content must be analyzed and indexed. If your database predates the introduction of in-viewer PDF search in CollectiveAccess 1.4, or search is otherwise failing to work properly, you can use this command to analyze and index PDFs in the database.");
		}
		# -------------------------------------------------------
		/**
		 * Update database schema
		 */
		public static function update_database_schema($po_opts=null) {
			require_once(__CA_LIB_DIR__."/ConfigurationCheck.php");

			$o_config_check = new ConfigurationCheck();
			if (($vn_current_revision = ConfigurationCheck::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {
				CLIUtils::addMessage(_t("Are you sure you want to update your CollectiveAccess database from revision %1 to %2?\nNOTE: you should backup your database before applying updates!\n\nType 'y' to proceed or 'N' to cancel, then hit return ", $vn_current_revision, __CollectiveAccess_Schema_Rev__));
				flush();
				ob_flush();
				$confirmation  =  trim( fgets( STDIN ) );
				if ( $confirmation !== 'y' ) {
					// The user did not say 'y'.
					return false;
				}
				$va_messages = ConfigurationCheck::performDatabaseSchemaUpdate();

				print CLIProgressBar::start(sizeof($va_messages), _t('Updating database'));
				foreach($va_messages as $vs_message) {
					print CLIProgressBar::next(1, $vs_message);
				}
				print CLIProgressBar::finish();
			} else {
				print CLIProgressBar::finish();
				CLIUtils::addMessage(_t("Database already at revision %1. No update is required.", __CollectiveAccess_Schema_Rev__));
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function update_database_schemaParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function update_database_schemaUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function update_database_schemaShortHelp() {
			return _t("Update database schema to the current version.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function update_database_schemaHelp() {
			return _t("Updates database schema to current version.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mapping($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_importers.php");

			if (!($vs_file_path = $po_opts->getOption('file'))) {
				CLIUtils::addError(_t("You must specify a file"));
				return false;
			}
			if (!file_exists($vs_file_path)) {
				CLIUtils::addError(_t("File '%1' does not exist", $vs_file_path));
				return false;
			}
			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

			if (!($t_importer = ca_data_importers::loadImporterFromFile($vs_file_path, $va_errors, array('logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level)))) {
				CLIUtils::addError(_t("Could not import '%1': %2", $vs_file_path, join("; ", $va_errors)));
				return false;
			} else {
				if (is_array($va_errors) && (sizeof($va_errors)>0)) {
					CLIUtils::addMessage(CLIUtils::textWithColor(_t("There were warnings when adding mapping from file '%1': %2", $vs_file_path, join("; ", $va_errors)), 'yellow'));
				}

				CLIUtils::addMessage(_t("Created mapping %1 from %2", CLIUtils::textWithColor($t_importer->get('importer_code'), 'yellow'), $vs_file_path), array('color' => 'none'));
				return true;
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mappingParamList() {
			return array(
				"file|f=s" => _t('Excel XLSX file to load.'),
				"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mappingUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mappingShortHelp() {
			return _t("Load import mapping from Excel XLSX format file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mappingHelp() {
			return _t("Loads import mapping from Excel XLSX format file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_data($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_importers.php");

			if (!($vs_data_source = $po_opts->getOption('source'))) {
				CLIUtils::addError(_t('You must specify a data source for import'));
				return false;
			}
			if (!($vs_mapping = $po_opts->getOption('mapping'))) {
				CLIUtils::addError(_t('You must specify a mapping'));
				return false;
			}
			if (!($t_mapping = ca_data_importers::mappingExists($vs_mapping))) {
				CLIUtils::addError(_t('Mapping %1 does not exist', $vs_mapping));
				return false;
			}
			
			if (($vs_add_to_set = $po_opts->getOption('add-to-set')) && (!($t_set = ca_sets::find(['set_code' => $vs_add_to_set], ['returnAs' => 'firstModelInstance'])))) {
				CLIUtils::addError(_t('Set %1 does not exist', $vs_add_to_set));
				return false;
			}
			if ($t_set && ((int)$t_set->get('table_num') !== (int)$t_mapping->get('table_num'))) {
				CLIUtils::addError(_t('Set %1 does take items imported by mapping', $vs_add_to_set));
				return false;
			}

			$vb_import_all_datasets = (bool)$po_opts->getOption('import-all-datasets');
			$vb_direct = (bool)$po_opts->getOption('direct');
			$vb_no_search_indexing = (bool)$po_opts->getOption('no-search-indexing');
			$vb_use_temp_directory_for_logs_as_fallback = (bool)$po_opts->getOption('log-to-tmp-directory-as-fallback'); 

			$vs_format = $po_opts->getOption('format');
			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

			if ($vb_no_search_indexing) {
				define("__CA_DONT_DO_SEARCH_INDEXING__", true);
			}

			if (!ca_data_importers::importDataFromSource($vs_data_source, $vs_mapping, array('noTransaction' => $vb_direct, 'format' => $vs_format, 'showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level, 'logToTempDirectoryIfLogDirectoryIsNotWritable' => $vb_use_temp_directory_for_logs_as_fallback, 'addToSet' => $vs_add_to_set, 'importAllDatasets' => $vb_import_all_datasets))) {
				CLIUtils::addError(_t("Could not import source %1: %2", $vs_data_source, join("; ", ca_data_importers::getErrorList())));
				return false;
			} else {
				CLIUtils::addMessage(_t("Imported data from source %1", $vs_data_source));
				return true;
			}
		}
		/**
		* Helper function to get log levels
		*/
		private static function getLogLevel($po_opts){
			$vn_log_level = KLogger::INFO;
			switch($vs_log_level = $po_opts->getOption('log-level')) {
				case 'DEBUG':
					$vn_log_level = KLogger::DEBUG;
					break;
				case 'NOTICE':
					$vn_log_level = KLogger::NOTICE;
					break;
				case 'WARN':
					$vn_log_level = KLogger::WARN;
					break;
				case 'ERR':
					$vn_log_level = KLogger::ERR;
					break;
				case 'CRIT':
					$vn_log_level = KLogger::CRIT;
					break;
				case 'ALERT':
					$vn_log_level = KLogger::ALERT;
					break;
				default:
				case 'INFO':
					$vn_log_level = KLogger::INFO;
					break;
			}
			return $vn_log_level;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataParamList() {
			return array(
				"source|s=s" => _t('Data to import. For files provide the path; for database, OAI and other non-file sources provide a URL.'),
				"mapping|m=s" => _t('Mapping to import data with.'),
				"format|f-s" => _t('The format of the data to import. (Ex. XLSX, tab, CSV, mysql, OAI, Filemaker XML, ExcelXML, MARC). If omitted an attempt will be made to automatically identify the data format.'),
				"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
				"add-to-set|t-s" => _t('Optional identifier of set to add all imported items to.'),
				"dryrun" => _t('If set import is performed without data actually being saved to the database. This is useful for previewing an import for errors.'),
				"direct" => _t('If set import is performed without a transaction. This allows viewing of imported data during the import, which may be useful during debugging/development. It may also lead to data corruption and should only be used for testing.'),
				"import-all-datasets" => _t('When importing an Excel .xslx file, if set import will be performed on all worksheets in the file. By default, only the first worksheet is imported.'),
				"no-search-indexing" => _t('If set indexing of changes made during import is not done. This may significantly reduce import time, but will neccessitate a reindex of the entire database after the import.'),
				"log-to-tmp-directory-as-fallback" => _t('Use the system temporary directory for the import log if the application logging directory is not writable. Default report an error if the application log directory is not writeable.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataShortHelp() {
			return _t("Import data from many types of data sources.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataHelp() {
			return _t("Import data from many types of data sources including other CollectiveAccess systems, MySQL databases and Excel, delimited text, XML and MARC files.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_export_mapping($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_exporters.php");

			if (!($vs_file_path = $po_opts->getOption('file'))) {
				print _t("You must specify a file!")."\n";
				return false;
			}
			if (!file_exists($vs_file_path)) {
				print _t("File '%1' does not exist!", $vs_file_path)."\n";
				return false;
			}

			if (!($t_exporter = ca_data_exporters::loadExporterFromFile($vs_file_path,$va_errors))) {
				if(is_array($va_errors) && sizeof($va_errors)){
					foreach($va_errors as $vs_error){
						CLIUtils::addError($vs_error);
					}
				} else {
					CLIUtils::addError(_t("Could not import '%1'", $vs_file_path));
				}

				return false;
			} else {
				if(is_array($va_errors) && sizeof($va_errors)){
					foreach($va_errors as $vs_error){
						CLIUtils::addMessage(_t("Warning").":".$vs_error);
					}
				}
				print _t("Created mapping %1 from %2", CLIUtils::textWithColor($t_exporter->get('exporter_code'), 'yellow'), $vs_file_path)."\n";
				return true;
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_export_mappingParamList() {
			return array(
				"file|f=s" => _t('Excel XLSX file to load.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_export_mappingUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_export_mappingShortHelp() {
			return _t("Load export mapping from Excel XLSX format file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_export_mappingHelp() {
			return _t("Loads export mapping from Excel XLSX format file.");
		}
		# -------------------------------------------------------
		public static function export_data($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_exporters.php");

			$vs_search = $po_opts->getOption('search');
			$vs_id = $po_opts->getOption('id');
			$vb_rdf = (bool)$po_opts->getOption('rdf');

			if (!$vb_rdf && !$vs_search && !$vs_id) {
				print _t('You must specify either an idno or a search expression to select a record or record set for export or activate RDF mode.')."\n";
				return false;
			}
			if (!($vs_filename = $po_opts->getOption('file'))) {
				print _t('You must specify a file to write export output to.')."\n";
				return false;
			}

			if(@file_put_contents($vs_filename, "") === false){
				// probably a permission error
				print _t("Can't write to file %1. Check the permissions.",$vs_filename)."\n";
				return false;
			}

			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

			// RDF mode
			if($vb_rdf){
				if (!($vs_config = $po_opts->getOption('config'))) {
					print _t('You must specify a configuration file that contains the export definition for the RDF mode.')."\n";
					return false;
				}

				// test config syntax
				if(!Configuration::load($vs_config)){
					print _t('Syntax error in configuration file %s.',$vs_config)."\n";
					return false;
				}

				if(ca_data_exporters::exportRDFMode($vs_config, $vs_filename,array('showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level))){
					print _t("Exported data to %1", CLIUtils::textWithColor($vs_filename, 'yellow'));
					return true;
				} else {
					print _t("Could not run RDF mode export")."\n";
					return false;
				}
			}

			// Search or ID mode

			if (!($vs_mapping = $po_opts->getOption('mapping'))) {
				print _t('You must specify a mapping for export.')."\n";
				return false;
			}

			if (!(ca_data_exporters::loadExporterByCode($vs_mapping))) {
				print _t('Mapping %1 does not exist', $vs_mapping)."\n";
				return false;
			}

			if(sizeof($va_errors = ca_data_exporters::checkMapping($vs_mapping))>0){
				print _t("Mapping %1 has errors: %2",$vs_mapping,join("; ",$va_errors))."\n";
				return false;
			}

			if($vs_search){
				if(!ca_data_exporters::exportRecordsFromSearchExpression($vs_mapping, $vs_search, $vs_filename, array('showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level))){
					print _t("Could not export mapping %1", $vs_mapping)."\n";
					return false;
				} else {
					print _t("Exported data to %1", $vs_filename)."\n";
				}
			} else if($vs_id){
				if($vs_export = ca_data_exporters::exportRecord($vs_mapping, $vs_id, array('singleRecord' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level))){
					file_put_contents($vs_filename, $vs_export);
					print _t("Exported data to %1", CLIUtils::textWithColor($vs_filename, 'yellow'));
				} else {
					print _t("Could not export mapping %1", $vs_mapping)."\n";
					return false;
				}
			}
		}
		# -------------------------------------------------------
		public static function export_dataParamList() {
			return array(
				"search|s=s" => _t('Search expression that selects records to export.'),
				"id|i=s" => _t('Primary key identifier of single item to export.'),
				"file|f=s" => _t('Required. File to save export to.'),
				"mapping|m=s" => _t('Mapping to export data with.'),
				"log|l-s" => _t('Path to directory in which to log export details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Optional logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
				"rdf" => _t('Switches to RDF export mode. You can use this to assemble record-level exports across authorities with multiple mappings in a single export (usually an RDF graph). -s, -i and -m are ignored and -c is required.'),
				"config|c=s" => _t('Configuration file for RDF export mode.'),
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function export_dataUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		public static function export_dataShortHelp() {
			return _t("Export data to a CSV, MARC or XML file.");
		}
		# -------------------------------------------------------
		public static function export_dataHelp() {
			return _t("Export data to a CSV, MARC or XML file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previews($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
			if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }

			$vs_sql_where = null;
			$va_params = array();
			if (
				(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
			) {
				$vs_sql_where = "WHERE annotation_id >= ?";
				$va_params[] = $vn_start;
				if ($vn_end) {
					$vs_sql_where .= " AND annotation_id <= ?";
					$va_params[] = $vn_end;
				}
			}
			$qr_reps = $o_db->query("
				SELECT annotation_id
				FROM ca_representation_annotations
				{$vs_sql_where}
				ORDER BY annotation_id
			", $va_params);

			$vn_total = $qr_reps->numRows();
			print CLIProgressBar::start($vn_total, _t('Finding annotations'));
			$vn_c = 1;
			while($qr_reps->nextRow()) {
				$t_instance = new ca_representation_annotations($vn_id = $qr_reps->get('annotation_id'));
				print CLIProgressBar::next(1, _t('Annotation %1', $vn_id));
				$t_instance->setMode(ACCESS_WRITE);
				$t_instance->update(array('forcePreviewGeneration' => true));

				$vn_c++;
			}
			print CLIProgressBar::finish();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsParamList() {
			return array(
				"start_id|s-n" => _t('Annotation id to start reloading at'),
				"end_id|e-n" => _t('Annotation id to end reloading at')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsShortHelp() {
			return _t("Regenerates annotation preview media for some or all object representation annotations.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsHelp() {
			return _t("Regenerates annotation preview media for some or all object representation annotations.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_AAT($po_opts=null) {
			require_once(__CA_APP_DIR__.'/helpers/supportHelpers.php');

			if (!($vs_file_path = $po_opts->getOption('file'))) {
				CLIUtils::addError(_t("You must specify a file"));
				return false;
			}
			caLoadAAT($vs_file_path);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_AATParamList() {
			return array(
				"file|f=s" => _t('Path to AAT XML file.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_AATUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_AATShortHelp() {
			return _t("Load Getty Art & Architecture Thesaurus (AAT) into CollectiveAccess.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_AATHelp() {
			return _t("Loads the AAT from a Getty-provided XML file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_ULAN($po_opts=null) {
			require_once(__CA_APP_DIR__.'/helpers/supportHelpers.php');

			if (!($vs_file_path = $po_opts->getOption('directory'))) {
				CLIUtils::addError(_t("You must specify a data directory"));
				return false;
			}
			if (!file_exists($vs_config_path = $po_opts->getOption('configuration'))) {
				CLIUtils::addError(_t("You must specify a ULAN import configuration file"));
				return false;
			}
			caLoadULAN($vs_file_path, $vs_config_path);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_ULANParamList() {
			return array(
				"directory|d=s" => _t('Path to directory containing ULAN XML files.'),
				"configuration|c=s" => _t('Path to ULAN import configuration file.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_ULANUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_ULANShortHelp() {
			return _t("Load Getty Art & Architecture Thesaurus (AAT) into CollectiveAccess.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_ULANHelp() {
			return _t("Loads the AAT from a Getty-provided XML file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function replicate_data($po_opts=null) {
			require_once(__CA_LIB_DIR__.'/Sync/Replicator.php');

			$o_replicator = new Replicator();
			$o_replicator->replicate();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function replicate_dataParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function replicate_dataUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function replicate_dataShortHelp() {
			return _t("Replicate data from one CollectiveAccess system to another.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function replicate_dataHelp() {
			return _t("Replicates data in one CollectiveAccess instance based upon data in another instance, subject to configuration in replication.conf.");
		}
		# -------------------------------------------------------
		/**
		 * Fix file permissions
		 */
		public static function fix_permissions($po_opts=null) {
			// Guess web server user
			if (!($vs_user = $po_opts->getOption("user"))) {
				$vs_user = caDetermineWebServerUser();
				if (!$po_opts->getOption("quiet") && $vs_user) { CLIUtils::addMessage(_t("Determined web server user to be \"%1\"", $vs_user)); }
			}

			if (!$vs_user) {
				$vs_user = caGetProcessUserName();
				CLIUtils::addError(_t("Cannot determine web server user. Using %1 instead.", $vs_user));
			}

			if (!$vs_user) {
				CLIUtils::addError(_t("Cannot determine the user. Please specify one with the --user option."));
				return false;
			}

			if (!($vs_group = $po_opts->getOption("group"))) {
				$vs_group = caGetProcessGroupName();
				if (!$po_opts->getOption("quiet") && $vs_group) { CLIUtils::addMessage(_t("Determined web server group to be \"%1\"", $vs_group)); }
			}

			if (!$vs_group) {
				CLIUtils::addError(_t("Cannot determine the group. Please specify one with the --group option."));
				return false;
			}

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the temporary directory (app/tmp) for ownership by \"%1\"...", $vs_user)); }
			$va_files = caGetDirectoryContentsAsList($vs_path = __CA_APP_DIR__.'/tmp', true, false, false, true);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0770);
			}
			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the media directory (media) for ownership by \"%1\"...", $vs_user)); }
			$va_files = caGetDirectoryContentsAsList($vs_path = __CA_BASE_DIR__.'/media', true, false, false, true);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0775);
			}

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the HTMLPurifier definition cache directory (vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer) for ownership by \"%1\"...", $vs_user)); }
			$va_files = caGetDirectoryContentsAsList($vs_path = __CA_BASE_DIR__.'/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer', true, false, false, true);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0770);
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function fix_permissionsParamList() {
			return array(
				"user|u=s" => _t("Set ownership of directories to specifed user. If not set, an attempt will be made to determine the name of the web server user automatically. If the web server user cannot be determined the current user will be used."),
				"group|g=s" => _t("Set ownership of directories to specifed group. If not set, the current group will be used."),
				"quiet|q" => _t("Run without outputting progress information.")
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function fix_permissionsUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function fix_permissionsShortHelp() {
			return _t("Fix folder permissions. MUST BE RUN WHILE LOGGED IN WITH ADMINSTRATIVE/ROOT PERMISSIONS. You are currently logged in as %1 (uid %2)", caGetProcessUserName(), caGetProcessUserID());
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function fix_permissionsHelp() {
			return _t("CollectiveAccess must have both read and write access to the temporary storage directory (app/tmp), media directory (media) and HTMLPurifier definition cache (app/lib/Parsers/htmlpurifier/standalone/HTMLPurifier/DefinitionCache). A run-time error will be displayed if any of these locations is not accessible to the application. To change these permissions to allow CollectiveAccess to run normally run this command while logged in with administrative/root privileges. You are currently logged in as %1 (uid %2). You can specify which user will be given ownership of the directories using the --user option. If you do not specify a user, the web server user for your server will be automatically determined and used.", caGetProcessUserName(), caGetProcessUserID());
		}
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
			
			$t_user->setMode(ACCESS_WRITE);
			$t_user->set('password', $vs_password);
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
			return _t('Maintenance');
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
		 * Load metadata dictionary
		 */
		public static function load_metadata_dictionary_from_excel_file($po_opts=null) {
			require_once(__CA_MODELS_DIR__.'/ca_metadata_dictionary_entries.php');

			$t_entry = new ca_metadata_dictionary_entries();
			$o_db = $t_entry->getDb();
			$qr_res = $o_db->query("DELETE FROM ca_metadata_dictionary_rule_violations");
			$qr_res = $o_db->query("DELETE FROM ca_metadata_dictionary_rules");
			$qr_res = $o_db->query("DELETE FROM ca_metadata_dictionary_entries");

			if (!($ps_source = (string)$po_opts->getOption('file'))) {
				CLIUtils::addError(_t("You must specify a file"));
				return false;
			}
			if (!file_exists($ps_source) || !is_readable($ps_source)) {
				CLIUtils::addError(_t("You must specify a valid file"));
				return false;
			}

			try {
				$o_file = PHPExcel_IOFactory::load($ps_source);
			} catch (Exception $e) {
				CLIUtils::addError(_t("You must specify a valid Excel .xls or .xlsx file: %1", $e->getMessage()));
				return false;
			}
			$o_sheet = $o_file->getActiveSheet();
			$o_rows = $o_sheet->getRowIterator();

			$vn_add_count = 0;
			$vn_rule_count = 0;

			$o_rows->next(); // skip first line
			while ($o_rows->valid() && ($o_row = $o_rows->current())) {
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false);

				$vn_c = 0;
				$va_data = array();

				foreach ($o_cells as $o_cell) {
					$vm_val = $o_cell->getValue();
					if ($vm_val instanceof PHPExcel_RichText) {
						$vs_val = '';
						foreach($vm_val->getRichTextElements() as $vn_x => $o_item) {
							$o_font = $o_item->getFont();
							$vs_text = $o_item->getText();
							if ($o_font && $o_font->getBold()) {
								$vs_val .= "<strong>{$vs_text}</strong>";
							} elseif($o_font && $o_font->getItalic()) {
								$vs_val .= "<em>{$vs_text}</em>";
							} else {
								$vs_val .= $vs_text;
							}
						}
					} else {
						$vs_val = trim((string)$vm_val);
					}
					$va_data[$vn_c] = nl2br(preg_replace("![\n\r]{1}!", "\n\n", $vs_val));
					$vn_c++;

					if ($vn_c > 6) { break; }
				}
				$o_rows->next();

				// Insert entries
				$t_entry = new ca_metadata_dictionary_entries();
				$t_entry->set('bundle_name', $va_data[0]);
				$vn_add_count++;

				$t_entry->setMode(ACCESS_WRITE);
				$t_entry->setSetting('label', '');
				$t_entry->setSetting('definition', $va_data[2]);
				$t_entry->setSetting('mandatory', (bool)$va_data[1] ? 1 : 0);

				$va_tables = preg_split("![;,\|\r\n]{1}!", $va_data[3]);
				if(!is_array($va_tables)) { $va_tables = array(); }
				$va_tables = array_map('strip_tags', $va_tables);
				$va_tables = array_filter($va_tables,'strlen');
				
				$va_types = preg_split("![;,\|\r\n]{1}!", $va_data[4]);
				if(!is_array($va_types)) { $va_types = array(); }
				$va_types = array_map('strip_tags', $va_types);
				$va_types = array_filter($va_types,'strlen');

				$va_relationship_types = preg_split("![;,\|\r\n]{1}!", $va_data[5]);
				if (!is_array($va_relationship_types)) { $va_relationship_types = array(); }
				$va_relationship_types = array_map('strip_tags', $va_relationship_types);
				$va_relationship_types = array_filter($va_relationship_types,'strlen');

				$t_entry->setSetting('restrict_to', $va_tables);
				$t_entry->setSetting('restrict_to_types', $va_types);
				$t_entry->setSetting('restrict_to_relationship_types', $va_relationship_types);

				$vn_rc = ($t_entry->getPrimaryKey() > 0) ? $t_entry->update() : $t_entry->insert();

				if ($t_entry->numErrors()) {
					CLIUtils::addError(_t("Error while adding definition for %1: %2", $va_data[0], join("; ", $t_entry->getErrors())));
				}

				// Add rules
				if ($va_data[6]) {
					if (!is_array($va_rules = json_decode($va_data[6], true))) {
						CLIUtils::addError(_t('Could not decode rules for %1', $va_data[6]));
						continue;
					}
					foreach($va_rules as $va_rule) {
						$t_rule = new ca_metadata_dictionary_rules();
						$t_rule->setMode(ACCESS_WRITE);
						$t_rule->set('entry_id', $t_entry->getPrimaryKey());
						$t_rule->set('rule_code', (string)$va_rule['ruleCode']);
						$t_rule->set('rule_level', (string)$va_rule['ruleLevel']);
						$t_rule->set('expression', (string)$va_rule['expression']);
						$t_rule->setSetting('label', (string)$va_rule['label']);
						$t_rule->setSetting('description', (string)$va_rule['description']);
						$t_rule->setSetting('violationMessage', (string)$va_rule['violationMessage']);

						$t_rule->insert();
						if ($t_rule->numErrors()) {
							CLIUtils::addError(_t("Error while adding rule for %1: %2", $va_data[0], join("; ", $t_rule->getErrors())));
						} else {
							$vn_rule_count++;
						}
					}
				}
			}


			CLIUtils::addMessage(_t('Added %1 entries and %2 rules', $vn_add_count, $vn_rule_count), array('color' => 'bold_green'));
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_metadata_dictionary_from_excel_fileParamList() {
			return array(
				"file|f=s" => _t('Excel XLSX file to load.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_metadata_dictionary_from_excel_fileUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_metadata_dictionary_from_excel_fileShortHelp() {
			return _t('Load metadata dictionary entries from an Excel file');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_metadata_dictionary_from_excel_fileHelp() {
			return _t('Load metadata dictionary entries from an Excel file using the format described at http://docs.collectiveaccess.org/metadata_dictionary');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function validate_using_metadata_dictionary_rules($po_opts=null) {
			require_once(__CA_MODELS_DIR__.'/ca_metadata_dictionary_rules.php');
			require_once(__CA_MODELS_DIR__.'/ca_metadata_dictionary_rule_violations.php');

			$t_violation = new ca_metadata_dictionary_rule_violations();

			$va_rules = ca_metadata_dictionary_rules::getRules();

			print CLIProgressBar::start(sizeof($va_rules), _t('Evaluating'));

			$vn_total_rows = $vn_rule_num = 0;
			$vn_num_rules = sizeof($va_rules);
			foreach($va_rules as $va_rule) {
				$vn_rule_num++;
				$va_expression_tags = caGetTemplateTags($va_rule['expression']);

				$va_tmp = explode(".", $va_rule['bundle_name']);
				if (!($t_instance = Datamodel::getInstanceByTableName($va_tmp[0]))) {
					CLIUtils::addError(_t("Table for bundle %1 is not valid", $va_tmp[0]));
					continue;
				}

				$vs_bundle_name_proc = str_replace("{$vs_table_name}.", "", $va_rule['bundle_name']);
				$vn_table_num = $t_instance->tableNum();

				$qr_records = call_user_func_array(($vs_table_name = $t_instance->tableName())."::find", array(
					array('deleted' => 0),
					array('returnAs' => 'searchResult')
				));
				if (!$qr_records) { continue; }
				$vn_total_rows += $qr_records->numHits();

				CLIProgressBar::setTotal($vn_total_rows);

				$vn_count = 0;
				while($qr_records->nextHit()) {
					$vn_count++;

					print CLIProgressBar::next(1, _t("Rule %1 [%2/%3]: record %4", $va_rule['rule_settings']['label'], $vn_rule_num, $vn_num_rules, $vn_count));
					$t_violation->clear();
					$vn_id = $qr_records->getPrimaryKey();

					$vb_skip = !$t_instance->hasBundle($va_rule['bundle_name'], $qr_records->get('type_id'));

					if (!$vb_skip) {
						// create array of values present in rule
						$va_row = array($va_rule['bundle_name'] => $vs_val = $qr_records->get($va_rule['bundle_name']));
						foreach($va_expression_tags as $vs_tag) {
							$va_row[$vs_tag] = $qr_records->get($vs_tag);
						}
					}

					// is there a violation recorded for this rule and row?
					if ($t_found = ca_metadata_dictionary_rule_violations::find(array('rule_id' => $va_rule['rule_id'], 'row_id' => $vn_id, 'table_num' => $vn_table_num), array('returnAs' => 'firstModelInstance'))) {
						$t_violation = $t_found;
					}

					if (!$vb_skip && ExpressionParser::evaluate($va_rule['expression'], $va_row)) {
						// violation
						if ($t_violation->getPrimaryKey()) {
							$t_violation->setMode(ACCESS_WRITE);
							$t_violation->update();
						} else {
							$t_violation->setMode(ACCESS_WRITE);
							$t_violation->set('rule_id', $va_rule['rule_id']);
							$t_violation->set('table_num', $t_instance->tableNum());
							$t_violation->set('row_id', $qr_records->getPrimaryKey());
							$t_violation->insert();
						}
					} else {
						if ($t_violation->getPrimaryKey()) {
							$t_violation->delete(true);		// remove violation
						}
					}
				}
			}
			print CLIProgressBar::finish();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function validate_using_metadata_dictionary_rulesParamList() {
			return array(

			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function validate_using_metadata_dictionary_rulesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function validate_using_metadata_dictionary_rulesShortHelp() {
			return _t('Validate all records against metadata dictionary');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function validate_using_metadata_dictionary_rulesHelp() {
			return _t('Validate all records against rules in metadata dictionary.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_media_fixity($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");


			$quiet = $po_opts->getOption('quiet');
			
			$ps_email = caGetOption('email', $po_opts, null, ['castTo' => 'string']);
			if ($ps_email && !caCheckEmailAddress($ps_email)) {
				CLIUtils::addError(_t("Email address is invalid"));
				return false;
			}
			if (!($ps_file_path = strtolower((string)$po_opts->getOption('file'))) && !$ps_email) {
				CLIUtils::addError(_t("You must specify an output file or email address"));
				return false;
			}
			
			$ps_file_path = str_replace("%date", date("Y-m-d_h\hi\m"), $ps_file_path);
			
			switch($ps_format = caGetOption('format', $po_opts, 'text', ['forceLowercase' => true, 'validValues' => ['text', 'tab', 'csv'], 'castTo' => 'string'])) {
				case 'tab':
					$mimetype = 'text/tab-separated-values'; $extension = 'tab';
					break;
				case 'csv':
					$mimetype = 'text/csv'; $extension = 'csv';
					break;
				case 'text':
				default:
					$mimetype = 'text/plain'; $extension = 'txt';
					break;
			}
			
			
			$pa_versions = caGetOption('versions', $po_opts, null, ['delimiter' => [',', ';']]);
			$pa_kinds = caGetOption('kinds', $po_opts, 'all', ['forceLowercase' => true, 'validValues' => ['all', 'ca_object_representations', 'ca_attributes'], 'delimiter' => [',', ';']]);
			
			$o_db = new Db();
			$t_rep = new ca_object_representations();

			$vs_report_output = join(($ps_format == 'tab') ? "\t" : ",", array(_t('Type'), _t('Error'), _t('Name'), _t('ID'), _t('Version'), _t('File path'), _t('Expected MD5'), _t('Actual MD5')))."\n";

			$counts = [];

			if (in_array('all', $pa_kinds) || in_array('ca_object_representations', $pa_kinds)) {
				if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
				if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }


				if ($vn_id = (int)$po_opts->getOption('id')) {
					$vn_start = $vn_end = $vn_id;
				}

				$va_ids = array();
				if ($vs_ids = (string)$po_opts->getOption('ids')) {
					if (sizeof($va_tmp = explode(",", $vs_ids))) {
						foreach($va_tmp as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					}
				}

				$va_sql_wheres = array('o_r.deleted = 0');
				$va_params = array();
				$vs_sql_joins = '';

				if (sizeof($va_ids)) {
					$va_sql_wheres[] = "o_r.representation_id IN (?)";
					$va_params[] = $va_ids;
				} else {
					if (
						(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
					) {
						$va_sql_wheres[] = "o_r.representation_id >= ?";
						$va_params[] = $vn_start;
						if ($vn_end) {
							$va_sql_wheres[] = "o_r.representation_id <= ?";
							$va_params[] = $vn_end;
						}
					}
				}
				
				if ($vs_object_ids = (string)$po_opts->getOption('object_ids')) {
					$va_object_ids = explode(",", $vs_object_ids);
					foreach($va_object_ids as $vn_i => $vn_object_id) {
						$va_object_ids[$vn_i] = (int)$vn_object_id;
					}
					
					if (sizeof($va_object_ids)) { 
						$va_sql_wheres[] = "(oxor.object_id IN (?))";
						$vs_sql_joins = "INNER JOIN ca_objects_x_object_representations AS oxor ON oxor.representation_id = o_r.representation_id";
						$va_params[] = $va_object_ids;
					}
				}
				
				// Verify object representations
				$qr_reps = $o_db->query("
					SELECT o_r.representation_id, o_r.idno, o_r.media 
					FROM ca_object_representations o_r 
					{$vs_sql_joins}
					WHERE 
						".join(" AND ", $va_sql_wheres), $va_params
				);
				
				if (!$quiet) { print CLIProgressBar::start($vn_rep_count = $qr_reps->numRows(), _t('Checking object representations'))."\n"; }
				$vn_errors = 0;
				
				if ($qr_reps->numRows() > 0) {
                    $counts[] = _t('%1 media representations', $qr_reps->numRows());
                    while($qr_reps->nextRow()) {
                        $vn_representation_id = $qr_reps->get('representation_id');
                        if (!$quiet) { print CLIProgressBar::next(1, _t("Checking representation media %1", $vn_representation_id)); }

                        $va_media_versions = (is_array($pa_versions) && sizeof($pa_versions) > 0) ? $pa_versions : $qr_reps->getMediaVersions('media');
                        foreach($va_media_versions as $vs_version) {
                            if (!($vs_path = $qr_reps->getMediaPath('media', $vs_version))) { continue; }
                            if (!($vs_database_md5 = $qr_reps->getMediaInfo('media', $vs_version, 'MD5'))) { continue; }		// skip missing MD5s - indicates empty file
                            $vs_file_md5 = md5_file($vs_path);

                            if ($vs_database_md5 !== $vs_file_md5) {
                                $t_rep->load($vn_representation_id);

                                $vs_message = _t("[Object representation][MD5 mismatch] %1; version %2 [%3]", $t_rep->get("ca_objects.preferred_labels.name")." (". $t_rep->get("ca_objects.idno")."); representation_id={$vn_representation_id}", $vs_version, $vs_path);
                                switch($ps_format) {
                                    case 'text':
                                    default:
                                        $vs_report_output .= "{$vs_message}\n";
                                        break;
                                    case 'tab':
                                    case 'csv':
                                        $va_log = array(_t('Object representation'), ("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($t_rep->get("ca_objects.preferred_labels.name")." (". $t_rep->get("ca_objects.idno").")").'"', $vn_representation_id, $vs_version, $vs_path, $vs_database_md5, $vs_file_md5);
                                        $vs_report_output .= join(($ps_format == 'tab') ? "\t" : ",", $va_log)."\n";
                                        break;
                                }

                                CLIUtils::addError($vs_message);
                                $vn_errors++;
                            }
                        }
                    }
                }

				if (!$quiet) { 
					print CLIProgressBar::finish(); 				
					if ($vn_errors == 1) {
						CLIUtils::addMessage(_t('%1 error for %2 representations', $vn_errors, $vn_rep_count));
					} else {
						CLIUtils::addMessage(_t('%1 errors for %2 representations', $vn_errors, $vn_rep_count));
					}
				}
			}


			if (in_array('all', $pa_kinds) || in_array('ca_attributes', $pa_kinds)) {
				// get all media elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

				if (is_array($va_elements) && sizeof($va_elements)) {
					if (is_array($va_element_ids = caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))) && sizeof($va_element_ids)) {
						$qr_c = $o_db->query("
							SELECT count(*) c
							FROM ca_attribute_values
							WHERE
								element_id in (?)
						", array($va_element_ids));
						if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }

						if (!$quiet) { print CLIProgressBar::start($vn_count, _t('Checking attribute media')); }

						$vn_errors = 0;
						$c = 0;
						foreach($va_elements as $vs_element_code => $va_element_info) {
							$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
							$va_vals = $qr_vals->getAllFieldValues('value_id');
							foreach($va_vals as $vn_value_id) {
								$t_attr_val = new ca_attribute_values($vn_value_id);
								if ($t_attr_val->getPrimaryKey()) {
									$c++;
									$t_attr_val->setMode(ACCESS_WRITE);
									$t_attr_val->useBlobAsMediaField(true);


									if (!$quiet) { print CLIProgressBar::next(1, _t("Checking attribute media %1", $vn_value_id)); }

									$va_media_versions = (is_array($pa_versions) && sizeof($pa_versions) > 0) ? $pa_versions : $t_attr_val->getMediaVersions('value_blob');
									foreach($va_media_versions as $vs_version) {
										if (!($vs_path = $t_attr_val->getMediaPath('value_blob', $vs_version))) { continue; }

										if (!($vs_database_md5 = $t_attr_val->getMediaInfo('value_blob', $vs_version, 'MD5'))) { continue; }	// skip missing MD5s - indicates empty file
										$vs_file_md5 = md5_file($vs_path);

										if ($vs_database_md5 !== $vs_file_md5) {
											$t_attr = new ca_attributes($vn_attribute_id = $t_attr_val->get('attribute_id'));

											$vs_label = "attribute_id={$vn_attribute_id}; value_id={$vn_value_id}";
											if ($t_instance = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
												if ($t_instance->load($t_attr->get('row_id'))) {
													$vs_label = $t_instance->get($t_instance->tableName().'.preferred_labels');
													if ($vs_idno = $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
														$vs_label .= " ({$vs_label})";
													}
												}
											}

											$vs_message = _t("[Media attribute][MD5 mismatch] %1; value_id=%2; version %3 [%4]", $vs_label, $vn_value_id, $vs_version, $vs_path);

											switch($ps_format) {
												case 'text':
												default:
													$vs_report_output .= "{$vs_message}\n";
													break;
												case 'tab':
												case 'csv':
													$va_log = array(_t('Media attribute'), _t("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($vs_label).'"', $vn_value_id, $vs_version, $vs_path, $vs_database_md5, $vs_file_md5);
													$vs_report_output .= join(($ps_format == 'tab') ? "\t" : ",", $va_log);
													break;
											}

											CLIUtils::addError($vs_message);
											$vn_errors++;
										}
									}

								}
							}
						}
						
						if ((sizeof($va_elements) > 0) && ($c > 0)) {
						    $counts[] = _t('%1 media in %2 metadata elements', sizeof($va_elements), $c);
						}
						if (!$quiet) { 
							print CLIProgressBar::finish(); 
							if($vn_errors == 1) {
								CLIUtils::addMessage(_t('%1 error for %2 attributes', $vn_errors, $vn_rep_count));
							} else {
								CLIUtils::addMessage(_t('%1 errors for %2 attributes', $vn_errors, $vn_rep_count));
							}
						}
					}
				}

				// get all File elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(15)); // 15=file

				if (is_array($va_elements) && sizeof($va_elements)) {
					if (is_array($va_element_ids = caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))) && sizeof($va_element_ids)) {
						$qr_c = $o_db->query("
							SELECT count(*) c
							FROM ca_attribute_values
							WHERE
								element_id in (?)
						", array($va_element_ids));
						if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }

						if (!$quiet) { print CLIProgressBar::start($vn_count, _t('Checking attribute files')); }

						$vn_errors = 0;
						$c = 0;
						foreach($va_elements as $vs_element_code => $va_element_info) {
							$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
							$va_vals = $qr_vals->getAllFieldValues('value_id');
							foreach($va_vals as $vn_value_id) {
								$t_attr_val = new ca_attribute_values($vn_value_id);
								if ($t_attr_val->getPrimaryKey()) {
									$c++;
									
									$t_attr_val->setMode(ACCESS_WRITE);
									$t_attr_val->useBlobAsFileField(true);


									if (!$quiet) { print CLIProgressBar::next(1, _t("Checking attribute file %1", $vn_value_id)); }

									if (!($vs_path = $t_attr_val->getFilePath('value_blob'))) { continue; }

									if (!($vs_database_md5 = $t_attr_val->getFileInfo('value_blob', 'MD5'))) { continue; }	// skip missing MD5s - indicates empty file
									$vs_file_md5 = md5_file($vs_path);

									if ($vs_database_md5 !== $vs_file_md5) {
										$t_attr = new ca_attributes($vn_attribute_id = $t_attr_val->get('attribute_id'));

										$vs_label = "attribute_id={$vn_attribute_id}; value_id={$vn_value_id}";
										if ($t_instance = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
											if ($t_instance->load($t_attr->get('row_id'))) {
												$vs_label = $t_instance->get($t_instance->tableName().'.preferred_labels');
												if ($vs_idno = $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
													$vs_label .= " ({$vs_label})";
												}
											}
										}

										$vs_message = _t("[File attribute][MD5 mismatch] %1; value_id=%2; version %3 [%4]", $vs_label, $vn_value_id, $vs_version, $vs_path);

										switch($ps_format) {
											case 'text':
											default:
												$vs_report_output .= "{$vs_message}\n";
												break;
											case 'tab':
											case 'csv':
												$va_log = array(_t('File attribute'), _t("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($vs_label).'"', $vn_value_id, $vs_version, $vs_path, $vs_database_md5, $vs_file_md5);
												$vs_report_output .= join(($ps_format == 'tab') ? "\t" : ",", $va_log);
												break;
										}

										CLIUtils::addError($vs_message);
										$vn_errors++;
									}

								}
							}
						}
						
						if((sizeof($va_elements) > 0) && ($c > 0)) {
						    $counts[] = _t('%1 files in %2 metadata elements', $c, sizeof($va_elements));
						}
						if (!$quiet) { 
							print CLIProgressBar::finish();
							if ($vn_errors == 1) {
								CLIUtils::addMessage(_t('%1 error for %2 attributes', $vn_errors, $vn_rep_count));
							} else {
								CLIUtils::addMessage(_t('%1 errors for %2 attributes', $vn_errors, $vn_rep_count));
							}
						}
					}
				}
			}
			
			if ($ps_file_path) {
				file_put_contents($ps_file_path, $vs_report_output);
			}
			
			$o_request = new RequestHTTP(null, [
				'no_headers' => true,
				'simulateWith' => [
					'REQUEST_METHOD' => 'GET',
					'SCRIPT_NAME' => 'index.php'
				]
			]);
			if ($ps_email) {
				$vb_delete_file = false;
				if (!$ps_file_path) {
					$ps_file_path = caGetTempFileName('fixity', 'txt');
					file_put_contents($ps_file_path, $vs_report_output);
					$vb_delete_file = true;
				}
				
				$a = $o_request->config->get('app_display_name');
				$attachment = null;
				if($vn_errors > 0) {
					$attachment = [
						'path' => $ps_file_path,
						'name' => _t('%1_fixity_report_%2.%3', $a, date("Y-m-d_h\hi\m"), $extension),
						'mimetype' => $mimetyype
					];
				}
				
				if (sizeof($counts) > 0) {
                    if (!caSendMessageUsingView(
                        $o_request, 
                        [$ps_email], 
                        [__CA_ADMIN_EMAIL__], 
                        _t('[%1] Media fixity report for %2', $a, $d = caGetLocalizedDate()), 
                        'check_media_fixity_report.tpl', 
                        ['date' => $d, 'app_name' => $a, 'num_errors' => $vn_errors, 'counts' => caMakeCommaListWithConjunction($counts)], 
                        null, null, 
                        ['attachment' => $attachment])
                    ) {
                        global $g_last_email_error;
                        CLIUtils::addError(_t("Could not send email to %1: %2", $ps_email, $g_last_email_error));
                    }
                }
				if ($vb_delete_file) { @unlink($ps_file_path); }
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_media_fixityParamList() {
			return array(
				"versions|v-s" => _t("Limit checking to specified versions. Separate multiple versions with commas."),
				"file|o=s" => _t('Location to write report to. The placeholder %date may be included to date/time stamp the report.'),
				"format|f-s" => _t('Output format. (text|tab|csv)'),
				"email|m-s" => _t('Email address to send report to.'),
				"quiet|q" => _t('Suppress progress messages.'),
				"start_id|s-n" => _t('Representation id to start checking at'),
				"end_id|e-n" => _t('Representation id to end checking at'),
				"id|i-n" => _t('Representation id to check'),
				"ids|l-s" => _t('Comma separated list of representation ids to check'),
				"object_ids|x-s" => _t('Comma separated list of object ids to check'),
				"kinds|k-s" => _t('Comma separated list of kind of media to check. Valid kinds are ca_object_representations (object representations), and ca_attributes (metadata elements). You may also specify "all" to check both kinds of media. Default is "all"')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_media_fixityUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_media_fixityShortHelp() {
			return _t('Verify media fixity using database file signatures');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_media_fixityHelp() {
			return _t('Verifies that media files on disk are consistent with file signatures recorded in the database at time of upload.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function create_ngrams($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");

			$o_db = new Db();

			$pb_clear = ((bool)$po_opts->getOption('clear'));
			$pa_sizes = caGetOption('sizes', $po_opts, null, ['delimiter' => [',', ';']]);
			
			foreach($pa_sizes as $vn_i => $vn_size) {
				$vn_size = (int)$vn_size;
				if (!$vn_size || ($vn_size <= 0)) { unset($pa_sizes[$vn_i]); continue; }
				$pa_sizes[$vn_i] = $vn_size;
			}
			if(!is_array($pa_sizes) || !sizeof($pa_sizes)) { $pa_sizes = array(2,3,4); }

			$vs_insert_ngram_sql = "
				INSERT  INTO ca_sql_search_ngrams
				(word_id, ngram, seq)
				VALUES
			";

			if ($pb_clear) {
				$qr_res = $o_db->query("TRUNCATE TABLE ca_sql_search_ngrams");
			}

			//create ngrams
			$qr_res = $o_db->query("SELECT word_id, word FROM ca_sql_search_words");

			print CLIProgressBar::start($qr_res->numRows(), _t('Starting...'));

			$vn_c = 0;
			$vn_ngram_c = 0;
			while($qr_res->nextRow()) {
				print CLIProgressBar::next();
				$vn_word_id = $qr_res->get('word_id');
				$vs_word = $qr_res->get('word');
				print CLIProgressBar::next(1, _t('Processing %1', $vs_word));

				if (!$pb_clear) {
					$qr_chk = $o_db->query("SELECT word_id FROM ca_sql_search_ngrams WHERE word_id = ?", array($vn_word_id));
					if ($qr_chk->nextRow()) {
						continue;
					}
				}

				$vn_seq = 0;
				foreach($pa_sizes as $vn_size) {
					$va_ngrams = caNgrams((string)$vs_word, $vn_size);

					$va_ngram_buf = array();
					foreach($va_ngrams as $vs_ngram) {
						$va_ngram_buf[] = "({$vn_word_id},'{$vs_ngram}',{$vn_seq})";
						$vn_seq++;
						$vn_ngram_c++;
					}

					if (sizeof($va_ngram_buf)) {
						$o_db->query($vs_insert_ngram_sql."\n".join(",", $va_ngram_buf));
					}
				}
				$vn_c++;
			}
			print CLIProgressBar::finish();
			CLIUtils::addMessage(_t('Processed %1 words and created %2 ngrams', $vn_c, $vn_ngram_c));
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function create_ngramsParamList() {
			return array(
				"clear|c=s" => _t('Clear all existing ngrams. Default is false.'),
				"sizes|s=s" => _t('Comma-delimited list of ngram sizes to generate. Default is 4.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function create_ngramsUtilityClass() {
			return _t('Search');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function create_ngramsShortHelp() {
			return _t('Create ngrams from search indices to support spell correction of search terms.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function create_ngramsHelp() {
			return _t('Ngrams.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_indexing_queue($po_opts=null) {
			require_once(__CA_MODELS_DIR__.'/ca_search_indexing_queue.php');

			ca_search_indexing_queue::process();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_indexing_queueParamList() {
			return array(

			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_indexing_queueUtilityClass() {
			return _t('Search');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_indexing_queueShortHelp() {
			return _t('Process search indexing queue.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_indexing_queueHelp() {
			return _t('Process search indexing queue.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_locations($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_objects.php");

			$o_db = new Db();
			$t_object = new ca_objects();

			$qr_res = $o_db->query("SELECT object_id FROM ca_objects ORDER BY object_id");

			print CLIProgressBar::start($qr_res->numRows(), _t('Starting...'));

			$vn_c = 0;
			while($qr_res->nextRow()) {
				$vn_object_id = $qr_res->get('object_id');
				if($t_object->load($vn_object_id)) {
					print CLIProgressBar::next(1, _t('Processing %1', $t_object->getWithTemplate("^ca_objects.preferred_labels.name (^ca_objects.idno)")));
					$t_object->deriveCurrentLocationForBrowse();
				} else {
					print CLIProgressBar::next(1, _t('Cannot load object %1', $vn_object_id));
				}
				$vn_c++;
			}
			print CLIProgressBar::finish();
			CLIUtils::addMessage(_t('Processed %1 objects', $vn_c));
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_locationsParamList() {
			return array(

			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_locationsUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_locationsShortHelp() {
			return _t('Reloads current location values for all object records.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_locationsHelp() {
			return _t('CollectiveAccess supports browse on current locations of collection objects using values cached in the object records. From time to time these values may become out of date. Use this command to regenerate the cached values based upon the current state of the database.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_caches($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Configuration.php");
			$o_config = Configuration::load();

			$ps_cache = strtolower((string)$po_opts->getOption('cache'));
			if (!in_array($ps_cache, array('all', 'app', 'usermedia'))) { $ps_cache = 'all'; }

			if (in_array($ps_cache, array('all', 'app'))) {
				CLIUtils::addMessage(_t('Clearing application caches...'));
				if (is_writable($o_config->get('taskqueue_tmp_directory'))) {
					caRemoveDirectory($o_config->get('taskqueue_tmp_directory'), false);
				} else {
					CLIUtils::addError(_t('Skipping clearing of application cache because it is not writable'));
				}
			}
			if (in_array($ps_cache, array('all', 'usermedia'))) {
				if (($vs_tmp_directory = $o_config->get('ajax_media_upload_tmp_directory')) && (file_exists($vs_tmp_directory))) {
					if (is_writable($vs_tmp_directory)) {
						CLIUtils::addMessage(_t('Clearing user media cache in %1...', $vs_tmp_directory));
						caRemoveDirectory($vs_tmp_directory, false);
					} else {
						CLIUtils::addError(_t('Skipping clearing of user media cache because it is not writable'));
					}
				} else {
					if (!$vs_tmp_directory) {
						CLIUtils::addError(_t('Skipping clearing of user media cache because no cache directory is configured'));
					} else {
						CLIUtils::addError(_t('Skipping clearing of user media cache because the configured directory at %1 does not exist', $vs_tmp_directory));
					}
				}
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_cachesParamList() {
			return array(
				"cache|c=s" => _t('Which cache to clear. Use "app" for the application cache (include all user sessions); use "userMedia" for user-uploaded media; use "all" for all cached. Default is all.'),
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_cachesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_cachesShortHelp() {
			return _t('Clear application caches and tmp directories.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_cachesHelp() {
			return _t('CollectiveAccess stores often used values, processed configuration files, user-uploaded media and other types of data in application caches. You can clear these caches using this command.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_check($po_opts=null) {
			
			include_once(__CA_LIB_DIR__."/Search/SearchEngine.php");
			include_once(__CA_LIB_DIR__."/Media.php");
			include_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
			include_once(__CA_LIB_DIR__."/ConfigurationCheck.php");
			require_once(__CA_LIB_DIR__."/Configuration.php");
			
			// Media
			$t_media = new Media();
			$va_plugin_names = $t_media->getPluginNames();
			
			
			CLIUtils::addMessage(_t("Checking media processing plugins..."), array('color' => 'bold_blue'));
			foreach($va_plugin_names as $vs_plugin_name) {
				if ($va_plugin_status = $t_media->checkPluginStatus($vs_plugin_name)) {
					CLIUtils::addMessage("\t"._t("Found %1", $vs_plugin_name));
				}
			}
			CLIUtils::addMessage("\n"); 
		
			// Application plugins
			CLIUtils::addMessage(_t("Checking application plugins..."), array('color' => 'bold_blue'));
			$va_plugin_names = ApplicationPluginManager::getPluginNames();
			$va_plugins = array();
			foreach($va_plugin_names as $vs_plugin_name) {
				if ($va_plugin_status = ApplicationPluginManager::checkPluginStatus($vs_plugin_name)) {
					CLIUtils::addMessage("\t"._t("Found %1", $vs_plugin_name));
				}
			}
			CLIUtils::addMessage("\n");
		
			// Barcode generation
			CLIUtils::addMessage(_t("Checking for barcode dependencies..."), array('color' => 'bold_blue'));
			$vb_gd_is_available = caMediaPluginGDInstalled(true);
			CLIUtils::addMessage("\t("._t('GD is a graphics processing library required for all barcode generation.').")");
			if (!$vb_gd_is_available) {
				CLIUtils::addError("\t\t"._t('GD is not installed; barcode printing will not be possible.'));
			} else{
				CLIUtils::addMessage("\t\t"._t('GD is installed; barcode printing will be available.'));
			}
			CLIUtils::addMessage("\n");

			// General system configuration issues
			CLIUtils::addMessage(_t("Checking system configuration... this may take a while."), array('color' => 'bold_blue'));
			ConfigurationCheck::performExpensive();
			if(ConfigurationCheck::foundErrors()){
				CLIUtils::addMessage("\t"._t('Errors were found:'), array('color' => 'bold_red'));
				foreach(ConfigurationCheck::getErrors() as $vn_i => $vs_error) {
					CLIUtils::addError("\t\t[".($vn_i + 1)."] {$vs_error}");
				}
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_checkParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_checkUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_checkShortHelp() {
			return _t('Performs configuration check on CollectiveAccess installation.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_checkHelp() {
			return _t('CollectiveAccess requires certain PHP configuration options to be set and for file permissions in several directories to be web-server writable. This command will check these settings and file permissions and return warnings if configuration appears to be incorrect.');
		}
		# -------------------------------------------------------
		public static function reload_service_values($po_opts=null) {
			$va_infoservice_elements = ca_metadata_elements::getElementsAsList(
				false, null, null, true, false, false, array(__CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__)
			);

			$o_db = new Db();

			foreach($va_infoservice_elements as $va_element) {
				$qr_values = $o_db->query("
					SELECT * FROM ca_attribute_values
					WHERE element_id = ?
				", $va_element['element_id']);

				print CLIProgressBar::start($qr_values->numRows(), "Reloading values for element code ".$va_element['element_code']);
				$t_value = new ca_attribute_values();

				while($qr_values->nextRow()) {
					$o_val = new InformationServiceAttributeValue($qr_values->getRow());
					$vs_uri = $o_val->getUri();

					print CLIProgressBar::next(); // inc before first continuation point

					if(!$vs_uri || !strlen($vs_uri)) { continue; }
					if(!$t_value->load($qr_values->get('value_id'))) { continue; }

					$t_value->editValue($vs_uri);

					if($t_value->numErrors() > 0) {
						print _t('There were errors updating an attribute row: ') . join(' ', $t_value->getErrors());
					}
				}

				print CLIProgressBar::finish();
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_service_valuesParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_service_valuesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_service_valuesShortHelp() {
			return _t('Reload InformationService attribute values from referenced URLs.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_service_valuesHelp() {
			return _t('InformationService attribute values store all the data CollectiveAccess needs to operate locally while keeping a reference to the referenced record at the remote web service. That means that potential changes at the remote data source are not pulled in automatically. This script explicitly performs a lookup for all existing InformationService attribute values and updates the local copy of the data with the latest values.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function reload_ulan_records($po_opts=null) {
			require_once(__CA_MODELS_DIR__.'/ca_data_importers.php');

			if(!($vs_mapping = $po_opts->getOption('mapping'))) {
				CLIUtils::addError("\t\tNo mapping found. Please use the -m parameter to specify a ULAN mapping.");
				return false;
			}

			if (!(ca_data_importers::mappingExists($vs_mapping))) {
				CLIUtils::addError("\t\tMapping $vs_mapping does not exist");
				return false;
			}

			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

			$o_db = new Db();
			$qr_items = $o_db->query("
				SELECT DISTINCT source FROM ca_data_import_events WHERE type_code = 'ULAN'
			");

			$va_sources = array();

			while($qr_items->nextRow()) {
				$vs_source = $qr_items->get('source');
				if(!isURL($vs_source)) {
					continue;
				}

				if(!preg_match("/http\:\/\/vocab\.getty\.edu\/ulan\//", $vs_source)) {
					continue;
				}

				$va_sources[] = $vs_source;
			}

			ca_data_importers::importDataFromSource(join(',', $va_sources), $vs_mapping, array('format' => 'ULAN', 'showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level));

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_ulan_recordsParamList() {
			return array(
				"mapping|m=s" => _t('Which mapping to use to re-import the ULAN records.'),
				"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_ulan_recordsUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_ulan_recordsShortHelp() {
			return _t('Reload records imported from ULAN with the specified mapping.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_ulan_recordsHelp() {
			return _t('Reload records imported from ULAN with the specified mapping. This utility assumes that the mapping is set up with an existingRecordPolicy that ensures that existing records are matched properly. It will create duplicates if it does not match existing records so be sure to test your mapping first!');
		}
		# -------------------------------------------------------
		public static function reload_object_current_location_dates($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_movements.php");
			require_once(__CA_MODELS_DIR__."/ca_movements_x_objects.php");
			require_once(__CA_MODELS_DIR__."/ca_movements_x_storage_locations.php");
			require_once(__CA_MODELS_DIR__."/ca_objects_x_storage_locations.php");
			
			$o_config = Configuration::load();
			$o_db = new Db();
			
			// Reload movements-objects
			if ($vs_movement_storage_element = $o_config->get('movement_storage_location_date_element')) {
				$qr_movements = ca_movements::find(['deleted' => 0], ['returnAs' => 'searchResult']);
			
				print CLIProgressBar::start($qr_movements->numHits(), "Reloading movement dates");
				
				while($qr_movements->nextHit()) {
					if ($va_dates = $qr_movements->get("ca_movements.{$vs_movement_storage_element}", ['returnAsArray' => true, 'rawDate' => true])) {
						$va_date = array_shift($va_dates);
						
						// get movement-object relationships
						if (is_array($va_rel_ids = $qr_movements->get('ca_movements_x_objects.relation_id', ['returnAsArray' => true])) && sizeof($va_rel_ids)) {						
							$qr_res = $o_db->query(
								"UPDATE ca_movements_x_objects SET sdatetime = ?, edatetime = ? WHERE relation_id IN (?)", 
								array($va_date['start'], $va_date['end'], $va_rel_ids)
							);
						}
						// get movement-location relationships
						if (is_array($va_rel_ids = $qr_movements->get('ca_movements_x_storage_locations.relation_id', ['returnAsArray' => true])) && sizeof($va_rel_ids)) {						
							$qr_res = $o_db->query(
								"UPDATE ca_movements_x_storage_locations SET sdatetime = ?, edatetime = ? WHERE relation_id IN (?)", 
								array($va_date['start'], $va_date['end'], $va_rel_ids)
							);
							
							// check to see if archived storage locations are set in ca_movements_x_storage_locations.source_info
							// Databases created prior to the October 2015 location tracking changes won't have this
							$qr_rels = caMakeSearchResult('ca_movements_x_storage_locations', $va_rel_ids);
							while($qr_rels->nextHit()) {
								if (!is_array($va_source_info = $qr_rels->get('source_info')) || !isset($va_source_info['path'])) {
									$vn_rel_id = $qr_rels->get('ca_movements_x_storage_locations.relation_id');
									$qr_res = $o_db->query(
										"UPDATE ca_movements_x_storage_locations SET source_info = ? WHERE relation_id = ?", 
											array(caSerializeForDatabase(array(
												'path' => $qr_rels->get('ca_storage_locations.hierarchy.preferred_labels.name', array('returnAsArray' => true)),
												'ids' => $qr_rels->get('ca_storage_locations.hierarchy.location_id',  array('returnAsArray' => true))
											)), $vn_rel_id)
									);
								}
							}
						}
						print CLIProgressBar::next();
					}
				}
				
				print CLIProgressBar::finish();
			}
            
            $qr_loc_rels = ca_objects_x_storage_locations::find('*', ['returnAs' => 'searchResult']);
            print CLIProgressBar::start($qr_loc_rels->numHits(), "Reloading location dates");
            while($qr_loc_rels->nextHit()) {
                if (!$qr_loc_rels->get('ca_objects_x_storage_locations.effective_date')) {
                    if (($ts = $qr_loc_rels->get('ca_objects_x_storage_locations.lastModified')) && ($start_end = caDateToHistoricTimestamps($ts))) {
                        try {
                           $qr_res = $o_db->query(
                                "UPDATE ca_objects_x_storage_locations SET sdatetime = ?, edatetime = ? WHERE relation_id = ?", 
                                [$start_end[0], $start_end[1], $qr_loc_rels->get('ca_objects_x_storage_locations.relation_id')]
                            );
                        } catch (Exception $e) {
                            // noop
                        }
                    }
                }
                print CLIProgressBar::next();
            }
	
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_location_datesParamList() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_location_datesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_location_datesShortHelp() {
			return _t('Regenerate date/time stamps for movement and object-based location tracking.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_object_current_location_datesHelp() {
			return _t('Regenerate date/time stamps for movement and object-based location tracking.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_search_index($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			$o_db = new Db();
			
			CLIUtils::addMessage(_t("Preloading primary search index..."), array('color' => 'bold_blue'));
			$o_db->query("SELECT * FROM ca_sql_search_word_index", array(), array('resultMode' => MYSQLI_USE_RESULT));
			CLIUtils::addMessage(_t("Preloading index i_index_table_num..."), array('color' => 'bold_blue'));
			$o_db->query("SELECT * FROM ca_sql_search_word_index FORCE INDEX(i_index_table_num)", array(), array('resultMode' => MYSQLI_USE_RESULT));
			CLIUtils::addMessage(_t("Preloading index i_index_field_table_num..."), array('color' => 'bold_blue'));
			$o_db->query("SELECT * FROM ca_sql_search_word_index  FORCE INDEX(i_index_field_table_num)", array(), array('resultMode' => MYSQLI_USE_RESULT));
			CLIUtils::addMessage(_t("Preloading index i_index_field_num..."), array('color' => 'bold_blue'));
			$o_db->query("SELECT * FROM ca_sql_search_word_index  FORCE INDEX(i_index_field_num)", array(), array('resultMode' => MYSQLI_USE_RESULT));
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_search_indexParamList() {
			return array(
				
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_search_indexUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_search_indexShortHelp() {
			return _t('Preload SQLSearch index into MySQL in-memory cache.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_search_indexHelp() {
			return _t('Preload SQLSearch index into MySQL in-memory cache. This is only relevant if you are using the MySQL-based SQLSearch engine. Preloading can significantly improve performance on system with large search indices. Note that your MySQL installation must have a large enough buffer pool configured to hold the index. Loading may take several minutes.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_content($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			
			$o_config = Configuration::load();
			if(!(bool)$o_config->get('do_content_caching')) { 
				CLIUtils::addError(_t("Content caching is not enabled"));
				return;
			}
			$o_cache_config = Configuration::load(__CA_CONF_DIR__."/content_caching.conf");
			if(!is_array($va_exclude_from_precache = $o_cache_config->get('exclude_from_precache'))) { $va_exclude_from_precache = []; }
			
			$va_cached_actions = $o_cache_config->getAssoc('cached_actions');
			if(!is_array($va_cached_actions)) { 
				CLIUtils::addError(_t("No actions are configured for caching"));
				return;
			}
			
			$o_request = new RequestHTTP(null, [
				'no_headers' => true,
				'simulateWith' => [
					'REQUEST_METHOD' => 'GET',
					'SCRIPT_NAME' => 'index.php'
				]
			]);
			
			$vs_site_protocol = $o_config->get('site_protocol');
			if (!($vs_site_hostname = $o_config->get('site_hostname'))) {
				$vs_site_hostname = "localhost";
			}
			
			foreach($va_cached_actions as $vs_controller => $va_actions) {
			    if(in_array($vs_controller, $va_exclude_from_precache)) { continue; }
				switch($vs_controller) {
					case 'Browse':
					case 'Search':
						// preloading of cache not supported
						CLIUtils::addMessage(_t("Preloading from %1 is not supported", $vs_controller), array('color' => 'yellow'));
						break;
					case 'Detail':
						$va_tmp = explode("/", $vs_controller);
						$vs_controller = array_pop($va_tmp);
						$vs_module_path = join("/", $va_tmp);
						
						$o_detail_config = caGetDetailConfig();
						$va_detail_types = $o_detail_config->getAssoc('detailTypes');
						
						foreach($va_actions as $vs_action => $vn_ttl) {
							if (is_array($va_detail_types[$vs_action])) {
								$vs_table = $va_detail_types[$vs_action]['table'];
								$va_types = $va_detail_types[$vs_action]['restrictToTypes'];
								if (!file_exists(__CA_MODELS_DIR__."/{$vs_table}.php")) { continue; }
								require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
								
								if ($vs_url = caNavUrl($o_request, $vs_module_path, $vs_controller, $vs_action)) {
									// get ids
									$va_ids = call_user_func_array(array($vs_table, 'find'), array(['deleted' => 0], ['restrictToTypes' => $va_types, 'returnAs' => 'ids']));
								
									if (is_array($va_ids) && sizeof($va_ids)) {
										foreach($va_ids as $vn_id) {
											CLIUtils::addMessage(_t("Preloading from %1::%2 (%3)", $vs_controller, $vs_action, $vn_id), array('color' => 'bold_blue'));
											file_get_contents($vs_site_protocol."://".$vs_site_hostname.$vs_url."/$vn_id?noCache=1");
										}
									}
								}
							} else {
								CLIUtils::addMessage(_t("Preloading from %1::%2 failed because there is no detail configured for %2", $vs_controller, $vs_action), array('color' => 'yellow'));
							}
							
						}
						break;
					case 'splash':
					default:
					    $va_tmp = explode("/", $vs_controller);
						if ($vs_controller == 'splash') { $vs_controller = array_pop($va_tmp); }
						$vs_module_path = join("/", $va_tmp);
						foreach($va_actions as $vs_action => $vn_ttl) {
							if ($vs_url = caNavUrl($o_request, $vs_module_path, $vs_controller, $vs_action, array('noCache' => 1))) {
							
								CLIUtils::addMessage(_t("Preloading from %1::%2", $vs_controller, $vs_action), array('color' => 'bold_blue'));
								$vs_url = $vs_site_protocol."://".$vs_site_hostname.$vs_url;
							 	file_get_contents($vs_url);
							}
							
						}
					    break;
				}
			}
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_contentParamList() {
			return array(
				
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_contentUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_contentShortHelp() {
			return _t('Pre-generate content cache.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_contentHelp() {
			return _t('Pre-loads content cache by loading each cached page url. Pre-caching may take a while depending upon the quantity of content configured for caching.');
		}
		# -------------------------------------------------------
		/**
		 * Load metadata dictionary
		 */
		public static function load_chenhall_nomenclature($po_opts=null) {
			require_once(__CA_MODELS_DIR__.'/ca_lists.php');
			require_once(__CA_MODELS_DIR__.'/ca_locales.php');

			$t_list = new ca_lists();
			$o_db = $t_list->getDb();
			
			$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();

			if (!($ps_source = (string)$po_opts->getOption('file'))) {
				CLIUtils::addError(_t("You must specify a file"));
				return false;
			}
			if (!file_exists($ps_source) || !is_readable($ps_source)) {
				CLIUtils::addError(_t("You must specify a valid file"));
				return false;
			}
			
			if (!($ps_list_code = (string)$po_opts->getOption('list'))) {
				CLIUtils::addError(_t("You must specify a list"));
				return false;
			}
			
			$pb_update = (bool)$po_opts->getOption('update'); 	// "update" parameter; we only allow updating of a list if this is explicitly set
			$vb_is_update = false; // flag indicated if we're actually updating an existing list

			try {
				$o_file = PHPExcel_IOFactory::load($ps_source);
			} catch (Exception $e) {
				CLIUtils::addError(_t("You must specify a valid Excel .xls or .xlsx file: %1", $e->getMessage()));
				return false;
			}
			
			print CLIProgressBar::start($o_file->getActiveSheet()->getHighestRow(), _t('Loading non-preferred terms'));
			// Get non-preferred terms
			$o_file->setActiveSheetIndex(1);
			$o_sheet = $o_file->getActiveSheet();
			$o_rows = $o_sheet->getRowIterator();
			
			$o_rows->next();
				
			$va_non_preferred_terms = [];
			while ($o_rows->valid() && ($o_row = $o_rows->current())) {
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false);

				$vn_c = 0;
				$va_data = array();

				foreach ($o_cells as $o_cell) {
					$va_data[$vn_c] = trim((string)$o_cell->getValue());
					$vn_c++;

					if ($vn_c > 3) { break; }
				}
				
				$va_non_preferred_terms[$va_data[1]][] = $va_data[0];
				
				$o_rows->next();
				CLIProgressBar::next();
			}
			CLIProgressBar::finish();
			

			// get list
			
			print CLIProgressBar::start(1, _t('Creating list'));
			
			if (!($t_list = ca_lists::find(['list_code' => $ps_list_code], ['returnAs' => 'firstModelInstance']))) {
				$t_list = new ca_lists();
				$t_list->setMode(ACCESS_WRITE);
				$t_list->set('list_code', $ps_list_code);
				$t_list->set('is_system_list', 1);
				$t_list->set('is_hierarchical', 1);
				$t_list->set('use_as_vocabulary', 1);
				$t_list->insert();
				
				if ($t_list->numErrors()) {
					CLIUtils::addError(_t("Could not create list %1: %2", $ps_list_code, join("; ", $t_list->getErrors())));
					return false;
				}
				
				$t_list->addLabel(['name' => 'Chenhall Nomenclature'], $vn_locale_id, null, true);
				if ($t_list->numErrors()) {
					CLIUtils::addError(_t("Could not label list %1: %2", $ps_list_code, join("; ", $t_list->getErrors())));
					return false;
				}
				
			} elseif (($t_list->numItemsInList($ps_list_code) > 0)) {
				if ($pb_update) {
					$vb_is_update = true;
				} else {
					CLIUtils::addError(_t("List %1 is not empty. The Chenhall Nomenclature may only be imported into an empty list.", $ps_list_code));
					return false;
				}
			}
			CLIProgressBar::finish();
			
			
			$vn_list_id = $t_list->getPrimaryKey();

			// Get preferred terms
			
			$o_file->setActiveSheetIndex(0);
			$o_sheet = $o_file->getActiveSheet();
			$o_rows = $o_sheet->getRowIterator();
			$vn_add_count = 0;

			print CLIProgressBar::start($o_file->getActiveSheet()->getHighestRow(), _t('Loading preferred terms'));
			
			$o_rows->next(); // skip first line
			
			$va_parents = [];
			
			while ($o_rows->valid() && ($o_row = $o_rows->current())) {
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false);

				$vn_c = 0;
				$va_data = array();

				foreach ($o_cells as $o_cell) {
					$vm_val = $o_cell->getValue();
					if ($vm_val instanceof PHPExcel_RichText) {
						$vs_val = '';
						foreach($vm_val->getRichTextElements() as $vn_x => $o_item) {
							$o_font = $o_item->getFont();
							$vs_text = $o_item->getText();
							if ($o_font && $o_font->getBold()) {
								$vs_val .= "<strong>{$vs_text}</strong>";
							} elseif($o_font && $o_font->getItalic()) {
								$vs_val .= "<em>{$vs_text}</em>";
							} else {
								$vs_val .= $vs_text;
							}
						}
					} else {
						$vs_val = trim((string)$vm_val);
					}
					$va_data[$vn_c] = nl2br(preg_replace("![\n\r]{1}!", "\n\n", $vs_val));
					$vn_c++;

					if ($vn_c > 6) { break; }
				}
				$o_rows->next();

				
				$va_acc = [];
				foreach($va_data as $vn_col => $vs_term) {
					if(!$vs_term) { continue; }
					if($vn_col > 5) { break; }
					$va_acc[] = $vs_term;
				}
				$vs_term = array_pop($va_acc);
				$vs_key = md5(join("|", $va_acc));
				if (!($vn_parent_id = $va_parents[$vs_key])) {
					$vn_parent_id = $t_list->getRootListItemID();
				}
				
				$t_item = null;
				$vb_is_existing_item = false;
				if ($vb_is_update) {
					// look for existing list item
					if ($t_item = ca_list_items::find(['list_id' => $vn_list_id, 'idno' => mb_substr($vs_term, 0, 255)], ['returnAs' => 'firstModelInstance'])) {
						if (($t_item->get('ca_list_items.preferred_labels.name_plural') !== $vs_term) || ($t_item->get('ca_list_items.preferred_labels.description') !== $va_data[6])) {
							if(!$t_item->replaceLabel(['name_singular' => $vs_term, 'name_plural' => $vs_term, 'description' => $va_data[6]], $vn_locale_id, null, true)) {
								CLIUtils::addError(_t("Could not update term %1: %2", $vs_term, join("; ", $t_item->getErrors())));
							}
						}
						$vb_is_existing_item = true;
						
						if (!$t_item->removeAllLabels(__CA_LABEL_TYPE_NONPREFERRED__)) {
							CLIUtils::addError(_t("Could not remove nonpreferred labels for update for term %1: %2", $vs_term, join("; ", $t_item->getErrors())));
						}
						
						if ($vn_parent_id != $t_item->get('ca_list_items.parent_id')) {
							$t_item->setMode(ACCESS_WRITE);
							$t_item->set('parent_id', $vn_parent_id);
							if (!$t_item->update()) {
								CLIUtils::addError(_t("Could not update parent for term %1: %2", $vs_term, join("; ", $t_item->getErrors())));
							}
						}
					}
				}
				
				if (!$t_item) {
					if (!($t_item = $t_list->addItem($vs_term, true, false, $vn_parent_id, null, $vs_term, '', 0, 1))) {
						CLIUtils::addError(_t("Could not add term %1: %2", $vs_term, join("; ", $t_list->getErrors())));
						continue;
					}
				}
				if (!$vb_is_existing_item) {
					if (!$t_item->addLabel(['name_singular' => $vs_term, 'name_plural' => $vs_term, 'description' => $va_data[6]], $vn_locale_id, null, true)) {
						CLIUtils::addError(_t("Could not add term label %1: %2", $vs_term, join("; ", $t_list->getErrors())));
						continue;
					}
				}
				print CLIProgressBar::next(1, _t($vb_is_existing_item ? 'Updated preferred term %1' : 'Added preferred term %1', $vs_term));
				$va_parents[md5(join("|", array_merge($va_acc, [$vs_term])))] = $t_item->getPrimaryKey();
				
				if(is_array($va_non_preferred_terms[$vs_term])) {
					foreach($va_non_preferred_terms[$vs_term] as $vs_non_preferred_term) {
						if (!($t_item->addLabel(['name_singular' => $vs_non_preferred_term, 'name_plural' => $vs_non_preferred_term, 'description' => ''], $vn_locale_id, null, false))) {
							CLIUtils::addError(_t("Could not add non-preferred term %1 to %2: %3", $vs_non_preferred_term, $vs_term, join("; ", $t_list->getErrors())));
							continue;
						}
					}
				}
			}

			CLIProgressBar::finish();

			CLIUtils::addMessage(_t('Added %1 terms', $vn_add_count), array('color' => 'bold_green'));
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_chenhall_nomenclatureParamList() {
			return array(
				"file|f=s" => _t('Excel XLSX-format AASLH Chenhall Nomenclature file to load.'),
				"list|l=s" => _t('Code for list to load Chenhall Nomenclature into. If list with code does not exist it will be created.'),
				"update|u=s" => _t('Update an existing Chenhall installation.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_chenhall_nomenclatureUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_chenhall_nomenclatureShortHelp() {
			return _t('Load AASLH Chenhall Nomenclature from an Excel file');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_chenhall_nomenclatureHelp() {
			return _t('Loads Chenhall Nomenclature from Excel XLSX format file into the specified list. You can obtain a copy of the Nomenclature from the American Association of State and Local History (AASLH).');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_missing_guids($po_opts=null) {
			$o_db = new Db();

			foreach(Datamodel::getTableNames() as $vs_table) {
				$t_instance = Datamodel::getInstance($vs_table);
				if(
					($t_instance instanceof BundlableLabelableBaseModelWithAttributes) ||
					($t_instance instanceof BaseLabel) ||
					($t_instance instanceof ca_attribute_values) ||
					($t_instance instanceof ca_users) ||
					($t_instance instanceof ca_attributes) ||
					($t_instance->getProperty('LOG_CHANGES_TO_SELF') && method_exists($t_instance, 'getGUIDByPrimaryKey'))
				) {
					$qr_results = $o_db->query("SELECT ". $t_instance->primaryKey() . " FROM ". $t_instance->tableName());
					if($qr_results && ($qr_results->numRows() > 0)) {
						print CLIProgressBar::start($qr_results->numRows(), _t('Generating/verifying GUIDs for table %1', $t_instance->tableName()));
						while($qr_results->nextRow()) {
							print CLIProgressBar::next();
							$t_instance->getGUIDByPrimaryKey($qr_results->get($t_instance->primaryKey()));
						}
						print CLIProgressBar::finish();
					}
				}
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_missing_guidsParamList() {
			return array(

			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_missing_guidsUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_missing_guidsShortHelp() {
			return _t('Generate missing guids');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_missing_guidsHelp() {
			return _t('Generates guids for all records that don\'t have one yet. This can be useful if you plan on using the data synchronization/replication feature in the future. For more info see here: http://docs.collectiveaccess.org/wiki/Replication');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt $po_opts
		 * @return bool
		 */
		public static function remove_duplicate_records($po_opts=null) {
			$va_tables = null;
			if ($vs_tables = (string)$po_opts->getOption('tables')) {
				$va_tables = preg_split("![;,]+!", $vs_tables);
			} else {
				CLIUtils::addError(_t("The -t|--tables parameter is mandatory."));
				return false;
			}

			$vb_delete_opt = (bool)$po_opts->getOption('delete');

			foreach ($va_tables as $vs_t) {
				if (class_exists($vs_t) && method_exists($vs_t, 'listPotentialDupes')) {
					$va_dupes = $vs_t::listPotentialDupes();
					if (sizeof($va_dupes)) {
						CLIUtils::addMessage(_t('Table %1 has %2 records that have potential duplicates.', $vs_t, sizeof($va_dupes)), array('color' => 'red'));


						$t_instance = Datamodel::getInstance($vs_t);

						foreach ($va_dupes as $vs_sha2 => $va_keys) {
							CLIUtils::addMessage("\t" . _t('%1 records have the checksum %2', sizeof($va_keys), $vs_sha2));
							foreach ($va_keys as $vn_key) {
								$t_instance->load($vn_key);
								CLIUtils::addMessage("\t\t" . $t_instance->primaryKey() . ': ' . $t_instance->getPrimaryKey() . ' (' . $t_instance->getLabelForDisplay() . ')');
							}

							if ($vb_delete_opt) {
								$vn_entity_id = $vs_t::mergeRecords($va_keys);
								if ($vn_entity_id) {
									CLIUtils::addMessage("\t" . _t("Successfully consolidated them under id %1", $vn_entity_id), array('color' => 'green'));
								} else {
									CLIUtils::addMessage("\t" . _t("It seems like there was an error while deduplicating those records"), array('color' => 'bold_red'));
								}
							}

						}
					} else {
						CLIUtils::addMessage(_t('Table %1 does not seem to have any duplicates!', $vs_t), array('color' => 'bold_green'));
					}
				}
			}

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_duplicate_recordsParamList() {
			return array(
				"tables|t-s" => _t('Specific tables to deduplicate, separated by commas or semicolons. Mandatory.'),
				"delete|d" => _t('Delete duplicate records. Default is false.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_duplicate_recordsUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_duplicate_recordsShortHelp() {
			return _t('Show and, optionally, remove duplicate records');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function remove_duplicate_recordsHelp() {
			return _t('Lists and optionally removed duplicate records. For more info on how the algorithm works see here: http://docs.collectiveaccess.org/wiki/Deduplication');
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

			$vn_log_level = CLIUtils::getLogLevel($po_opts);

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
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function generate_new_system_guid($po_opts=null) {
			// generate system GUID -- used to identify systems in data sync protocol
			$o_vars = new ApplicationVars();
			$o_vars->setVar('system_guid', $vs_guid = caGenerateGUID());
			$o_vars->save();

			CLIUtils::addMessage(_t('New system GUID is %1', $vs_guid));
		}

		public static function generate_new_system_guidParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_new_system_guidUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_new_system_guidShortHelp() {
			return _t('Generates a new system GUID for this setup. Useful if you\'re using the sync/replication feature.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function generate_new_system_guidHelp() {
			return _t('This utility generates a new system GUID for the current system. This can be useful is you used a copy of another system to set it up and are now trying to sync/replicate data between the two. You may have to reset the system GUID for one of them in that case.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function check_url_reference_integrity($po_opts=null) {
			require_once(__CA_LIB_DIR__.'/Attributes/Values/UrlAttributeValue.php');

			$o_request = new RequestHTTP(null, [
				'no_headers' => true,
				'simulateWith' => [
					'REQUEST_METHOD' => 'GET',
					'SCRIPT_NAME' => 'index.php'
				]
			]);

			UrlAttributeValue::checkIntegrityForAllElements([
				'request' => $o_request,
				'notifyUsers' => $po_opts->getOption('users'),
				'notifyGroups' => $po_opts->getOption('groups')
			]);
		}
		# -------------------------------------------------------
		public static function check_url_reference_integrityParamList() {
			return [
				"users|u=s" => _t('User names to notify if there are errors. Multiple entries are delimited by comma or semicolon. Invalid or non-existing user named will be ignored. [Optional]'),
				"groups|g=s" => _t('Groups to notify if there are errors. They\'re identified by group code. Multiple entries are delimited by comma or semicolon. Invalid or non-existing groups will be ignored. [Optional]'),
			];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_url_reference_integrityUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_url_reference_integrityShortHelp() {
			return _t('Checks integrity for all URL references in the database.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_url_reference_integrityHelp() {
			return _t('This utility checks the integrity for all URL attribute references in the database. It does so by trying to hit each URL and reading a few bytes. It does not download the whole file.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function scan_site_page_templates($po_opts=null) {
			require_once(__CA_LIB_DIR__."/SitePageTemplateManager.php");
			
			CLIUtils::addMessage(_t("Scanning templates for tags"));
			$va_results = SitePageTemplateManager::scan();
			
			CLIUtils::addMessage(_t("Added %1 templates; updated %2 templates", $va_results['insert'],$va_results['update']));
			
			if (is_array($va_results['errors']) && sizeof($va_results['errors'])) {
				CLIUtils::addError(_t("Templates with errors: %1", join(", ", array_keys($va_results['errors']))));
			}
		}
		# -------------------------------------------------------
		public static function scan_site_page_templatesParamList() {
			return [
				"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.')
			];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function scan_site_page_templatesUtilityClass() {
			return _t('Content management');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function scan_site_page_templatesShortHelp() {
			return _t('Scan site page templates for tags.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function scan_site_page_templatesHelp() {
			return _t('Scan site page template for tags to build the content management editing user interface.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function check_metadata_alerts($po_opts=null) {
			require_once(__CA_MODELS_DIR__ . '/ca_metadata_alert_triggers.php');
			ca_metadata_alert_triggers::firePeriodicTriggers();
		}
		# -------------------------------------------------------
		public static function check_metadata_alertsParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_metadata_alertsUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_metadata_alertsShortHelp() {
			return _t('Checks periodic metadata alert triggers');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_metadata_alertsHelp() {
			return _t('This utility checks all periodic metadatadata alert triggers users have set up and, if they triggered, sends notifications to the recipients of these rules.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_simple_services($po_opts=null) {
			require_once(__CA_LIB_DIR__."/SitePageTemplateManager.php");
			
		
		    $o_app_conf = Configuration::load();
            $o_service_conf = Configuration::load(__CA_APP_DIR__.'/conf/services.conf');
            $va_endpoints = $o_service_conf->get('simple_api_endpoints');
            
            $ps_password = $vs_auth = null;
            if ($ps_username = $po_opts->getOption('username')) {
                $ps_password = $po_opts->getOption('password');
                
                $vs_auth = "{$ps_username}:{$ps_password}@";
                
            }

            foreach($va_endpoints as $vs_endpoint => $va_endpoint_info) {
                if ($va_precache_config = caGetOption('precache', $va_endpoint_info, null)) {
                    if (!($t_instance = Datamodel::getInstanceByTableName($vs_table = $va_endpoint_info['table'],true))) {
                        continue;
                    }
                    $vs_pk = $t_instance->primaryKey(true);
                            
                    switch($va_endpoint_info['type']) {
                        case 'search':
                        case 'refineablesearch':
                            if(isset($va_precache_config['searches']) && is_array($va_precache_config['searches'])) {
                                foreach($va_precache_config['searches'] as $vs_search) {
                                    if (sizeof($va_tags = caGetTemplateTags($vs_search, ['stripOptions' => true])) > 0) {
                                        $va_vals = [];
                                        foreach($va_tags as $vs_tag) {
                                            $va_tmp = explode('.', $vs_tag);
                                            if (!($t_tag = Datamodel::getInstanceByTableName($va_tmp[0],true))) {
                                                continue;
                                            }
                                            
                                            $qr_tag_vals = $va_tmp[0]::find('*', ['returnAs' => 'searchResult']);
                                            $va_tag_vals = $qr_tag_vals->getAllFieldValues($vs_tag);
                                          
                                            foreach($va_tag_vals as $vs_val) {
                                                $vs_search_proc = caProcessTemplate($vs_search, [$vs_tag => $vs_val]);
                                                file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}?noCache=1&q=".urlencode($vs_search_proc));
                                                CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1 for search %2", $vs_endpoint, $vs_search_proc));
                                            }
                                        }
                                    } else {
                                        file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}?noCache=1&q=".urlencode($vs_search));
                                        CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1 for search %2", $vs_endpoint, $vs_search));
                                    }
                                }
                            }
                            break;
                         case 'detail':
                           
                            if ($qr_res = $vs_table::find('*', ['returnAs' => 'searchResult'])) {
                                while($qr_res->nextHit()) {
                                    file_get_contents($vs_url = $o_app_conf->get('site_protocol')."://{$vs_auth}".$o_app_conf->get('site_hostname').'/'.$o_app_conf->get('ca_url_root')."/service.php/simple/{$vs_endpoint}/id/".$qr_res->get($vs_pk));
                                    CLIUtils::addMessage(_t("[".$t_instance->getProperty('NAME_PLURAL')."] Cached endpoint %1: %2", $vs_endpoint, $qr_res->get("{$vs_table}.preferred_labels")));
                                }
                            }
                            break;
                        // other service types are not cacheable
                    }
                }
            }
			
			CLIUtils::addMessage(_t("Added %1 templates; updated %2 templates"));
		}
		# -------------------------------------------------------
		public static function precache_simple_servicesParamList() {
			return [
				"username|u-s" => _t('Optional username to authenticate with.'),
				"password|p-s" => _t('Optional password to authenticate with.'),
			];
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_simple_servicesUtilityClass() {
			return _t('Performance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_simple_servicesShortHelp() {
			return _t('Pre-cache simple service responses.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function precache_simple_servicesHelp() {
			return _t('Pre-cache responses for appropriately configurated simple services. Caching can dramatically improve performance for services providing infrequently changing data.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_media($po_opts=null) {
			require_once(__CA_LIB_DIR__."/BatchProcessor.php");
			
			
			if (!caCheckMediaDirectoryPermissions()) {
			    CLIUtils::addError(_t('The media directory is not writeable by the current user. Try again, running the import as the web server user.'));
				return false;
			}

			if (!($vs_data_source = $po_opts->getOption('source'))) {
				CLIUtils::addError(_t('You must specify a directory to import media from'));
				return false;
			}
			if (!$vs_data_source) {
				CLIUtils::addError(_t('You must specify a source'));
				return false;
			}
			
			if (($vs_add_to_set = $po_opts->getOption('add-to-set')) && (!($t_set = ca_sets::find(['set_code' => $vs_add_to_set], ['returnAs' => 'firstModelInstance'])))) {
				CLIUtils::addError(_t('Set %1 does not exist', $vs_add_to_set));
				return false;
			}
			if ($t_set && ((int)$t_set->get('table_num') !== (int)$t_mapping->get('table_num'))) {
				CLIUtils::addError(_t('Set %1 does take items imported by mapping', $vs_add_to_set));
				return false;
			}
			
			$vn_user_id = null;
			if ($vs_user_name = $po_opts->getOption('username')) {
				if ($t_user = ca_users::find(['user_name' => $vs_user_name], ['returnAs' => 'firstModelInstance'])) {
				    $vn_user_id = $t_user->getPrimaryKey();
				} else {
				    CLIUtils::addError(_t('User name %1 is not valid', $vs_user_name));
				    return false;
				}
			} else {
			    CLIUtils::addError(_t('A user name to attribute the import to must be specified'));
				return false;
			}
			
			$vs_import_mode = $po_opts->getOption('import-mode');
			if (!in_array($vs_import_mode, ['TRY_TO_MATCH', 'ALWAYS_MATCH'])) {
				CLIUtils::addMessage(_t('Setting import mode to default value TRY_TO_MATCH'));
				$vs_import_mode = 'TRY_TO_MATCH';
			}
			$vs_match_mode = $po_opts->getOption('match-mode');
			if (!in_array($vs_match_mode, ['DIRECTORY_NAME', 'DIRECTORY_NAME', 'FILE_NAME'])) {
				CLIUtils::addMessage(_t('Setting match mode to default value FILE_NAME'));
				$vs_match_mode = 'FILE_NAME';
			}
			$vs_match_type = $po_opts->getOption('match-type');
			if (!in_array($vs_match_type, ['STARTS', 'ENDS', 'CONTAINS', 'EXACT'])) {
				CLIUtils::addMessage(_t('Setting match type to default value EXACT'));
				$vs_match_type = 'EXACT';
			}
			
			if (!($vs_import_target = $po_opts->getOption('import-target'))) {
			    $vs_import_target = 'ca_objects';
			    CLIUtils::addMessage(_t('Setting import target to default %1.', $vs_import_target));
			}
			$t_instance = Datamodel::getInstance($vs_import_target);
			if (!$t_instance || !is_subclass_of($t_instance, 'RepresentableBaseModel')) {
				CLIUtils::addMessage(_t('Import target %1 is invalid. Defaulting to ca_objects.', $vs_import_target));
				$vs_import_target = 'ca_objects';
			}
			if (!($t_instance = Datamodel::getInstanceByTableName($vs_import_target, true))) {
			    CLIUtils::addError(_t('Import target %1 is invalid.', $vs_import_target));
			    return false;
			}
			$vs_import_target_idno_mode = $po_opts->getOption('import-target-idno-mode');
			if (!in_array($vs_import_target_idno_mode, ['AUTO', 'FILENAME', 'FILENAME_NO_EXT', 'DIRECTORY_AND_FILENAME'])) {
				CLIUtils::addMessage(_t('Setting target identifier type to default value AUTO'));
				$vs_import_target_idno_mode = '';
			}
			$vs_representation_idno_mode = $po_opts->getOption('representation-idno-mode');
			if (!in_array($vs_representation_idno_mode, ['AUTO', 'FILENAME', 'FILENAME_NO_EXT', 'DIRECTORY_AND_FILENAME'])) {
				CLIUtils::addMessage(_t('Setting representation identifier type to default value AUTO'));
				$vs_representation_idno_mode = '';
			}
		
			$vn_type_id = null;
			if ($vs_import_target_type = $po_opts->getOption('import-target-type')) {
			    $vn_type_id = $t_instance->getTypeIDForCode($vs_import_target_type);
			}
			if (!$vn_type_id) {
			    $vn_type_id = array_shift($t_instance->getTypeList(['idsOnly' => true]));
			    CLIUtils::addMessage(_t('Setting target type to default %1', $t_instance->getTypeCodeForID($vn_type_id)));
			}
			
			$vn_rep_type_id = null;
			$t_rep = new ca_object_representations();
			if ($vs_rep_type = $po_opts->getOption('representation-type')) {
			    $vn_rep_type_id = $t_rep->getTypeIDForCode($vs_rep_type);
			}
			if (!$vn_rep_type_id) {
			    $vn_rep_type_id = array_shift($t_rep->getTypeList(['idsOnly' => true]));
			    CLIUtils::addMessage(_t('Setting representation type to default %1', $t_rep->getTypeCodeForID($vn_rep_type_id)));
			}
			
			$vn_access = null;
			if ($vs_import_target_access = $po_opts->getOption('import-target-access')) {
			    $vn_access = caGetListItemID('access_statuses', $vs_import_target_access);
			}
			if (!$vn_access) {
			    $vn_id = caGetDefaultItemID('access_statuses');
			    $vn_access = caGetListItemValueForID($vn_id);
			    CLIUtils::addMessage(_t('Setting target access to default %1', caGetListItemByIDForDisplay($vn_id)));
			}

			$vn_rep_access = null;
			if ($vs_rep_access = $po_opts->getOption('import-representation-access')) {
			    $vn_rep_access = caGetListItemID('access_statuses', $vs_import_target_access);
			}
			if (!$vn_rep_access) {
			    $vn_id = caGetDefaultItemID('access_statuses');
			    $vn_rep_access = caGetListItemValueForID($vn_id);
			    CLIUtils::addMessage(_t('Setting representation access to default %1', caGetListItemByIDForDisplay($vn_id)));
			}
			
			$vn_status = null;
			if ($vs_import_target_status = $po_opts->getOption('import-target-status')) {
			    $vn_status = caGetListItemID('workflow_statuses', $vs_import_target_status);
			}
			if (!$vn_status) {
			    $vn_id = caGetDefaultItemID('workflow_statuses');
			    $vn_status = caGetListItemValueForID($vn_id);
			    CLIUtils::addMessage(_t('Setting target status to default %1', caGetListItemByIDForDisplay($vn_id)));
			}

			$vn_rep_status = null;
			if ($vs_rep_status = $po_opts->getOption('import-representation-status')) {
			    $vn_rep_status = caGetListItemID('workflow_statuses', $vs_import_target_status);
			}
			if (!$vn_rep_status) {
			    $vn_id = caGetDefaultItemID('workflow_statuses');
			    $vn_rep_status = caGetListItemValueForID($vn_id);
			    CLIUtils::addMessage(_t('Setting representation status to default %1', caGetListItemByIDForDisplay($vn_id)));
			}
			
			$vn_mapping_id = null;
			if ($vs_mapping_code = $po_opts->getOption('representation-mapping')) {
			    if($t_mapping = ca_data_importers::mappingExists($vs_mapping_code)) {
			        if ($t_mapping->get('table_num') == $t_instance->tableNum()) {
			            $vn_mapping_id = $t_mapping->getPrimaryKey();
			        } else {
			             CLIUtils::addError(_t('Mapping %1 does not exist', $vs_mapping_code));
			        }
			    } else {
			        CLIUtils::addError(_t('Mapping %1 is not for target %2', $vs_mapping_code, $vs_import_target));
			    }
			}
			
			$vb_use_temp_directory_for_logs_as_fallback = (bool)$po_opts->getOption('log-to-tmp-directory-as-fallback'); 

			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

            $va_opts = [
                'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level, 'logToTempDirectoryIfLogDirectoryIsNotWritable' => $vb_use_temp_directory_for_logs_as_fallback, 
                'addToSet' => $vs_add_to_set,
                'importTarget' => $vs_import_target,
                'user_id' => $vn_user_id,
                'importFromDirectory' => $vs_data_source,
                'importMode' => $vs_import_mode,
                'matchMode' => $vs_match_mode,
                'matchType' => $vs_match_type,
                'allowDuplicateMedia' => (bool)$po_opts->getOption('allow-duplicate-media'),
                'includeSubDirectories' => (bool)$po_opts->getOption('include-subdirectories'),
                'deleteMediaOnImport' => (bool)$po_opts->getOption('delete-media-on-import'),
                
                'idno' => (string)$po_opts->getOption('import-target-idno'),
                'idnoMode' => $vs_import_target_idno_mode,
                'representationIdnoMode' => $vs_representation_idno_mode,
                'representation_idno' => (string)$po_opts->getOption('representation-idno'),
                
                $vs_import_target.'__mapping_id' => $vn_mapping_id,
                
                $vs_import_target.'_type_id' => $vn_type_id,
                'ca_object_representations_type_id' => $vn_rep_type_id,
                
                $vs_import_target.'_access' => $vn_access,
                'ca_object_representations_access' => $vn_rep_access,
                
                $vs_import_target.'_status' => $vn_status,
                'ca_object_representations_status' => $vn_rep_status,
                
                'progressCallback' => function($r, $c, $total, $message, $time, $memory, $notices, $errors) {
                    print CLIProgressBar::seek($c, $message);
                },
                'reportCallback' => function($r, $general, $notices, $errors) {
                    print CLIProgressBar::finish();
                }
            ];
            $va_counts = caGetDirectoryContentsCount($vs_data_source, (bool)$po_opts->getOption('include-subdirectories'));
            
            if ((bool)$po_opts->getOption('include-subdirectories')) {
                CLIUtils::addMessage(_t('Found %1 files in %2 directories', $va_counts['files'], $va_counts['directories']));
            } else {
                CLIUtils::addMessage(_t('Found %1 files', $va_counts['files']));
            }
            print CLIProgressBar::start($va_counts['files'], _t('Processing media'));
			if (!BatchProcessor::importMediaFromDirectory(null, $va_opts)) {
				CLIUtils::addError(_t("Could not import media from %1: %2", $vs_data_source, join("; ", BatchProcessor::getErrorList())));
				return false;
			} else {
				CLIUtils::addMessage(_t("Imported media from source %1", $vs_data_source));
				return true;
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_mediaParamList() {
		
			$access_status_values = caGetListItems('access_statuses', ['index' => 'item_value', 'value' => 'name_plural']);
			$access_status_default = caGetListItems('access_statuses', ['index' => 'item_value', 'value' => 'name_plural', 'defaultOnly' => true]);
			
			$access_status_list_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $access_status_values, array_keys($access_status_values)));
			$access_status_default_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $access_status_default, array_keys($access_status_default)));
			
			$workflow_status_values = caGetListItems('workflow_statuses', ['index' => 'item_value', 'value' => 'name_plural']);
			$workflow_status_default = caGetListItems('workflow_statuses', ['index' => 'item_value', 'value' => 'name_plural', 'defaultOnly' => true]);
			
			$workflow_status_list_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $workflow_status_values, array_keys($workflow_status_values)));
			$workflow_status_default_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $workflow_status_default, array_keys($workflow_status_default)));
			
			$object_type_values = caGetListItems('object_types', ['index' => 'item_value', 'value' => 'name_plural']);
			$object_type_default = caGetListItems('object_types', ['index' => 'item_value', 'value' => 'name_plural', 'defaultOnly' => true]);
			
			$object_type_list_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $object_type_values, array_keys($object_type_values)));
			$object_type_default_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $object_type_default, array_keys($object_type_default)));
			
			$representation_type_values = caGetListItems('object_representation_types', ['index' => 'item_value', 'value' => 'name_plural']);
			$representation_type_default = caGetListItems('object_representation_types', ['index' => 'item_value', 'value' => 'name_plural', 'defaultOnly' => true]);
			
			$representation_type_list_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $representation_type_values, array_keys($representation_type_values)));
			$representation_type_default_str = join(", ", array_map(function($v, $k) { return "{$k} ({$v})"; }, $representation_type_default, array_keys($representation_type_default)));
			
			return array(
				"source|s=s" => _t('Data to import. For files provide the path; for database, OAI and other non-file sources provide a URL.'),
				"username|u-s" => _t('User name of user to log import against.'),
				"log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log-level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
				"add-to-set|t-s" => _t('Optional identifier of set to add all imported items to.'),
				"log-to-tmp-directory-as-fallback|f-s" => _t('Use the system temporary directory for the import log if the application logging directory is not writable. Default report an error if the application log directory is not writeable.'),
				"include-subdirectories|i-s" => _t('Process media in sub-directories. Default is false.'),
				"match-type|mt-s" => _t('Sets how match between media and target record identifier is made. Valid values are: STARTS, ENDS, CONTAINS, EXACT. Default is EXACT.'),
				"match-mode|m-s" => _t('Determines how matches are made between media and records. Valid values are DIRECTORY_NAME, FILE_AND_DIRECTORY_NAMES, FILE_NAME. Set to DIRECTORY_NAME to match media directory names to target record identifiers; to FILE_AND_DIRECTORY_NAMES to match on both file and directory names; to FILE_NAME to match only on file names. Default is FILE_NAME.'),
				"import-mode" => _t('Determines if target records are created for media that do not match existing target records. Set to TRY_TO_MATCH to create new target records when no match is found. Set to ALWAYS_MATCH to only import media for existing records. Default is TRY_TO_MATCH.'),
				'allow-duplicate-media|du-s' => _t('Import media even if it already exists in CollectiveAccess. Default is false – skip import of duplicate media.'),
				'import-target|it-s' => _t('Table name of record to import media into. Should be a valid representation-taking table such as ca_objects, ca_entities, ca_occurrences, ca_places, etc. Default is ca_objects.'),
				'import-target-type|itt-s' => _t('Type to use for all newly created target records. Default is the first type in the target\'s type list.'),
				'import-target-idno|iti-s' => _t('Identifier to use for all newly created target records.'),
				'import-target-idno-mode|itim-s' => _t('Sets how identifiers of newly created target records are set. Valid values are AUTO, FILENAME, FILENAME_NO_EXT, DIRECTORY_AND_FILENAME. Set to AUTO to use an identifier calculated according to system numbering settings; set to FILENAME to use the file name as identifier; set to FILENAME_NO_EXT to use the file name stripped of extension as the identifier; use DIRECTORY_AND_FILENAME to set the identifer to the directory name and file name with extension. Default is AUTO.'),
				'import-target-access|ita-s' => _t('Set access for newly created target records. Possible values are %1. Default is %2.', $access_status_list_str, $access_status_default_str),
				'import-target-status|its-s' => _t('Set status for newly created target records. Possible values are %1. Default is %2.', $workflow_status_list_str, $workflow_status_default_str),
				'representation-type|rt-s' => _t('Type to use for all newly created representations. Possible values are %1. Default is %2.', $representation_type_list_str, $representation_type_default_str),
				'representation-idno|ri-s' => _t('Identifier to use for all newly created representation records.'),
				'representation-idno-mode|rim-s' => _t('Sets how identifiers of newly created representations are set. Valid values are AUTO, FILENAME, FILENAME_NO_EXT, DIRECTORY_AND_FILENAME. Set to AUTO to use an identifier calculated according to system numbering settings; set to FILENAME to use the file name as identifier; set to FILENAME_NO_EXT to use the file name stripped of extension as the identifier; use DIRECTORY_AND_FILENAME to set the identifer to the directory name and file name with extension. Default is AUTO.'),
				'representation-access|ra-s' => _t('Set access for newly created representations. Possible values are %1. Default is %2.', $access_status_list_str, $access_status_default_str),
				'representation-status|rs-s' => _t('Set status for newly created representations. Possible values are %1. Default is %2.', $workflow_status_list_str, $workflow_status_default_str),
				'representation-mapping|rm-s' => _t('Code for mapping to apply when importing media.'),
				'delete-media-on-import|dmoi-s' => _t('Remove media from directory after it has been successfully imported. Default is false.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_mediaUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_mediaShortHelp() {
			return _t("Import media.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_mediaHelp() {
			return _t("Import media from a directory or directory tree.");
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function regenerate_dependent_field_values($po_opts=null) {
			// Find containers with dependent fields
			$va_elements = ca_metadata_elements::getElementSetsWithSetting("isDependentValue", 1);
			
			
			$c = 0;
			foreach($va_elements as $va_element) {
			    $t_element = ca_metadata_elements::getInstance($va_element['element_code']);
			    $t_root = ca_metadata_elements::getInstance($va_element['hier_element_id']);
			    $vs_root_code = $t_root->get('element_code');
			    $vn_root_id = $t_root->get('element_id');
			    
			    CLIUtils::addMessage(_t('Processing %1.%2', $vs_root_code, $va_element['element_code']));
			    
			    // get type restrictions
			    $va_type_res_list = $t_element->getTypeRestrictions();
			    foreach($va_type_res_list as $va_type_res) {
			        if (!($t_instance = Datamodel::getInstanceByTableNum($va_type_res['table_num']))) { continue; }
			        $vs_table_name = $t_instance->tableName();
			        if ($va_type_res['type_id'] > 0) {
			            $qr_res = call_user_func("{$vs_table_name}::find", ["type_id" => (int)$va_type_res['type_id']], ['returnAs' => 'searchResult']);
			        } else {
			            $qr_res = call_user_func("{$vs_table_name}::find", ["*"], ['returnAs' => 'searchResult']);
			        }
			        
			        while($qr_res->nextHit()) {
			            $va_value_list = $qr_res->get("{$vs_table_name}.{$vs_root_code}", ["returnWithStructure" => true]);
			            foreach($va_value_list as $vn_row_id => $va_values_by_attribute_id) {
			                CLIUtils::addMessage(_t('Processing row %1 for %2.%3', $vn_row_id, $vs_root_code, $va_element['element_code']));
			                foreach($va_values_by_attribute_id as $vn_attr_id => $va_values) {
			                    $vs_processed_value = caProcessTemplate($va_element['settings']['dependentValueTemplate'], $va_values);
			                    
			                    $va_values[$va_element['element_code']] = $vs_processed_value;
			                    if (!$t_instance->load($vn_row_id)) { continue; }
			                    $t_instance->setMode(ACCESS_WRITE);
			                    $t_instance->editAttribute(
			                        $vn_attr_id, $vn_root_id, $va_values
			                    );
			                    $t_instance->update();
			                    $c++;
			                    if ($t_instance->numErrors() > 0) {
			                        CLIUtils::addError(_t("Could not update dependent value: %1", join("; ", $t_instance->getErrors())));
			                    }
			                }
			            }
			        }
			    }
			}
			
			CLIUtils::addMessage(_t("Regenerated %1 values", $c));
		}
		# -------------------------------------------------------
		public static function regenerate_dependent_field_valuesParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_dependent_field_valuesUtilityClass() {
            return _t('Maintenance');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_dependent_field_valuesShortHelp() {
			return _t('Regenerate template-generated values for fields that are dependent values.');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_dependent_field_valuesHelp() {
			return _t('Text fields that are dependent upon other fields are only refreshed on save and import. For dependent display templates using dimensions (length, width) formatting, changes in the dimensions.conf configuration files are not automatically applied to existing values. This utility will batch update all dependent values using the current system configuration.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function print_system_guid($po_opts=null) {
		    if (defined("__CA_SYSTEM_GUID__")) {
			    CLIUtils::addMessage(_t("System GUID is %1", __CA_SYSTEM_GUID__));
			} else {
			    CLIUtils::addError(_t("No system GUID is defined"));
			}
			return true;
		}
		# -------------------------------------------------------
		public static function print_system_guidParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function print_system_guidUtilityClass() {
			return _t('Import/Export');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function print_system_guidShortHelp() {
			return _t('Print system GUID value used for replication.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function print_system_guidHelp() {
			return _t('Print the system GUID value used for replication operations to the console. The system GUID uniquely identifies a CollectiveAccess installation.');
		}
		# -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function check_relationship_type_roots($po_opts=null) {
            require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
            require_once(__CA_MODELS_DIR__."/ca_locales.php");
            
			$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();	
			
			$va_tables = Datamodel::getTableNames();
		    
		    $c = 0;
			foreach($va_tables as $vs_table) {
				if (!preg_match('!_x_!', $vs_table)) { continue; }
				require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
				if (!($t_table = new $vs_table)) { continue; }
				if (!$t_table->hasField('type_id')) { continue; }
				$vs_pk = $t_table->primaryKey();
				$vn_table_num = $t_table->tableNum();
				
				// Create root ca_relationship_types row for table
				if (!$t_root = ca_relationship_types::find(['parent_id' => null, 'table_num' => $vn_table_num], ['returnAs' => 'firstModelInstance'])) {
				    $t_root = new ca_relationship_types();
					$t_root->logChanges(false);
					$t_root->setMode(ACCESS_WRITE);
					$t_root->set('table_num', $vn_table_num);
					$t_root->set('type_code', 'root_for_table_'.$vn_table_num);
					$t_root->set('rank', 1);
					$t_root->set('is_default', 0);
					$t_root->set('parent_id', null);
					$t_root->insert();
					
					if ($t_root->numErrors()) {
						 CLIUtils::addError(_t("Could not create root for relationship %1: %2", $vs_table, join('; ', $t_root->getErrors())));
						continue;
					}
					$t_root->addLabel(
						array(
							'typename' => 'Root for table '.$vn_table_num,
							'typename_reverse' => 'Root for table '.$vn_table_num
						), $vn_locale_id, null, true
					);
					if ($t_root->numErrors()) {
						CLIUtils::addError(_t("Could not add label to root for relationship %1: %2", $vs_table, join('; ', $t_root->getErrors())));
						continue;
					}
					CLIUtils::addMessage(_t('Added root for %1', $vs_table));
					$c++;
				}
			}
			
			CLIUtils::addMessage(($c == 1) ? _t("Recreated %1 root record", $c) : _t("Recreated %1 root records", $c));
		}
		# -------------------------------------------------------
		public static function check_relationship_type_rootsParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_relationship_type_rootsUtilityClass() {
            return _t('Maintenance');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_relationship_type_rootsShortHelp() {
			return _t('Check for and repair missing relationship type root records.');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_relationship_type_rootsHelp() {
			return _t('Each relationship has a hierarchy of relationship types defined. Each type hierarchy must have a root record. Root records are normally created during system installation, but may be missing due to accidental deletion or failure to create during system updates. This command will check for presence of require root records and recreate missing roots as required.');
        }
        # -------------------------------------------------------
		/**
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function clear_search_indexing_queue_lock_file($po_opts=null) {
            require_once(__CA_MODELS_DIR__."/ca_search_indexing_queue.php");
			
			if (ca_search_indexing_queue::lockExists()) {
			    if (ca_search_indexing_queue::lockCanBeRemoved()) {
			        ca_search_indexing_queue::lockRelease();
			        CLIUtils::addMessage(_t("Removed search indexing queue lock"));
			    } else {
			        CLIUtils::addMessage(_t("Insufficient privileges to remove search indexing queue. Try running caUtils under a user with privileges"));
			    }
			} else {
			    CLIUtils::addMessage(_t("Search indexing queue lock is not present"));
			}
		}
		# -------------------------------------------------------
		public static function clear_search_indexing_queue_lock_fileParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_search_indexing_queue_lock_fileUtilityClass() {
            return _t('Maintenance');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_search_indexing_queue_lock_fileShortHelp() {
			return _t('Remove search indexing queue lock file if present.');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function clear_search_indexing_queue_lock_fileHelp() {
			return _t('The search indexing queue is a task run periodically, usually via cron, to process pending indexing tasks. Simultaneous instances of the queue processor are prevented by means of a lock file. The lock file is created when the queue starts and deleted when it completed. While it is present new queue processing instances will refuse to start. In some cases, when a queue processing instance is killed or crashes, the lock file may not be removed and the queue will refuse to re-start. Lingering lock files may be removed using this command. Note that you must run caUtils under a user with privileges to delete the lock file.');
        }
		# -------------------------------------------------------
	}
