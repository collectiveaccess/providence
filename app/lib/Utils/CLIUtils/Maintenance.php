<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Maintenance.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2021 Whirl-i-Gig
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
 
	trait CLIUtilsMaintenance { 
		# -------------------------------------------------------
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_sort_values($po_opts=null) {
			$o_db = new Db();
			ini_set('memory_limit', '4000m');
			
			$tables = trim((string)$po_opts->getOption('table'));
			
			if($tables) {
				$tables = preg_split('![,;]+!', $tables);
			} else {
				$tables = [
					'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
					'ca_occurrences', 'ca_collections', 'ca_storage_locations',
					'ca_object_representations', 'ca_representation_annotations',
					'ca_list_items'
				];
			}
			
			foreach($tables as $table) {
				if(is_numeric($table)) { continue; }
				if(!($t_table = Datamodel::getInstance($table))) { continue; }
				$vs_pk = $t_table->primaryKey();
				$qr_res = $o_db->query("SELECT {$vs_pk} FROM {$table}");

				if ($vs_label_table_name = $t_table->getLabelTableName()) {
					require_once(__CA_MODELS_DIR__."/".$vs_label_table_name.".php");
					$t_label = new $vs_label_table_name;
					$vs_label_pk = $t_label->primaryKey();
					$qr_labels = $o_db->query("SELECT {$vs_label_pk} FROM {$vs_label_table_name}");

					print CLIProgressBar::start($qr_labels->numRows(), _t('Processing %1', $t_label->getProperty('NAME_PLURAL')));
					while($qr_labels->nextRow()) {
						$vn_label_pk_val = $qr_labels->get($vs_label_pk);
						
						CLIProgressBar::setMessage(_t("Memory: %1", caGetMemoryUsage()));
						print CLIProgressBar::next();
						if ($t_label->load($vn_label_pk_val)) {
							$t_table->logChanges(false);
							$t_label->update();
						}
					}
					print CLIProgressBar::finish();
				}

				print CLIProgressBar::start($qr_res->numRows(), _t('Processing %1 identifiers', $t_table->getProperty('NAME_SINGULAR')));
				while($qr_res->nextRow()) {
					$vn_pk_val = $qr_res->get($vs_pk);
					
					CLIProgressBar::setMessage(_t("Memory: %1", caGetMemoryUsage()));
					print CLIProgressBar::next();
					if ($t_table->load($vn_pk_val)) {
						$t_table->logChanges(false);
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
			return array(
				"table|t=s" => _t('Restrict rebuilding to a comma-separated list of table names.')
			);
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
			$vb_delete_opt = (bool)$po_opts->getOption('delete');
			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$qr_reps = $o_db->query("SELECT representation_id, media FROM ca_object_representations");
			print CLIProgressBar::start($qr_reps->numRows(), _t('Loading valid file paths from database'))."\n";

			$va_paths = array();
			while($qr_reps->nextRow()) {
				print CLIProgressBar::next();
				$va_versions = $qr_reps->getMediaVersions('media');
				if (!is_array($va_versions)) { continue; }
				
				$multifiles = $t_rep->getFileList($qr_reps->get('ca_object_representations.representation_id'), null, null, ['returnAllVersions' => true]);
				foreach($va_versions as $vs_version) {
					$va_paths[$qr_reps->getMediaPath('media', $vs_version)] = true;
					
					if(is_array($multifiles)) {
						foreach($multifiles as $mfinfo) {
							foreach($mfinfo as $mfk => $mf) {
								if(!preg_match("!_path$!", $mfk)) { continue; }
								$va_paths[$mf] = true;
							}
						}
					}
				}
			}
			print CLIProgressBar::finish();

			print CLIProgressBar::start(1, _t('Reading file list'));
			$va_contents = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/media/'.__CA_APP_NAME__, true, false);
			print CLIProgressBar::next();
			print CLIProgressBar::finish();

			$vn_delete_count = 0;

			print CLIProgressBar::start(sizeof($va_contents), _t('Finding unused files'));
	
			foreach($va_contents as $vs_path) {
				print CLIProgressBar::next();
				if (!preg_match('!_ca_object_representation!', $vs_path)) { continue; } // skip non object representation files
				if (!$va_paths[$vs_path]) {
					$vn_delete_count++;
					if ($vb_delete_opt) {
						@unlink($vs_path);
					}
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
			require_once(__CA_LIB_DIR__."/Logging/Downloadlog.php");
		
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
				if (!$t_instance = Datamodel::getInstanceByTableName($vs_table)) { continue; }
				if (!$t_instance->hasField('deleted')) { continue; }
			
				$t_instance->setMode(ACCESS_WRITE);
				$pk = $t_instance->primaryKey();

				$qr_del = $o_db->query("SELECT {$pk} FROM {$vs_table} WHERE deleted = 1");
				if($qr_del->numRows() > 0) {
					print CLIProgressBar::start($qr_del->numRows(), _t('Removing deleted %1 from database', $t_instance->getProperty('NAME_PLURAL')));

					$row_ids = $qr_del->getAllFieldValues($pk);
					
					if ($vs_table === 'ca_object_representations') {
						Downloadlog::purgeForRepresentation($row_ids);
					}
					$vn_c = 0;
					//while($qr_del->nextRow()) {
					foreach($row_ids as $row_id) {
						print CLIProgressBar::next();
						$t_instance->load($row_id);
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
		 * Update database schema
		 */
		public static function update_database_schema($po_opts=null) {
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
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function clear_search_indexing_queue_lock_file($po_opts=null) {
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
		/**
		 * Fix file permissions
		 */
		public static function fix_permissions($po_opts=null) {
			$config = Configuration::load();
			
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
			$va_files = caGetDirectoryContentsAsList(__CA_APP_DIR__.'/tmp', true, true, false, true, ['includeRoot' => true]);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0770);
			}
			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the media directory (media) for ownership by \"%1\"...", $vs_user)); }
			$media_root = $config->get("ca_media_root_dir");
			$va_files = caGetDirectoryContentsAsList($media_root, true, true, false, true, ['includeRoot' => true]);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0775);
			}

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the HTMLPurifier definition cache directory (vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer) for ownership by \"%1\"...", $vs_user)); }
			$va_files = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer', true, false, false, true);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0770);
			}
			
			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the log directory (app/log) for ownership by \"%1\"...", $vs_user)); }
			$va_files = caGetDirectoryContentsAsList(__CA_APP_DIR__.'/log', true, true, false, true, ['includeRoot' => true]);

			foreach($va_files as $vs_path) {
				chown($vs_path, $vs_user);
				chgrp($vs_path, $vs_group);
				chmod($vs_path, 0770);
			}
			
			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the user media upload directory (app/log) for ownership by \"%1\"...", $vs_user)); }
			$upload_root = $config->get("media_uploader_root_directory");
			$va_files = caGetDirectoryContentsAsList($upload_root, true, true, false, true, ['includeRoot' => true]);
			
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
				"group|g=s" => _t("Set ownership of directories to specifed group. If not set, the current group will be used.")
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
		 *
		 */
		public static function validate_using_metadata_dictionary_rules($po_opts=null) {
			$rules = ca_metadata_dictionary_rules::getRules();
			$tables = array_unique(array_map(function($v) { return $v['table_num']; }, $rules));
			print CLIProgressBar::start(sizeof($tables), _t('Evaluating'));

			$total_rows = 0;
			foreach($tables as $table_num) {
				if (!($t_instance = Datamodel::getInstanceByTableNum($table_num))) {
					CLIUtils::addError(_t("Table %1 is not valid", $table_num));
					continue;
				}

				$qr_records = call_user_func_array(($table_name = $t_instance->tableName())."::find", array(
					'*',
					array('returnAs' => 'searchResult')
				));
				if (!$qr_records) { continue; }
				$total_rows += $qr_records->numHits();

				CLIProgressBar::setTotal($total_rows);

				while($qr_records->nextHit()) {

					print CLIProgressBar::next(1, _t("Validating records in %1", $table_name));
					
					$t_instance = $qr_records->getInstance();
					$t_instance->validateUsingMetadataDictionaryRules();
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
					'SCRIPT_NAME' => __CA_URL_ROOT__.'/index.php'
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
		public static function clear_caches($po_opts=null) {
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
				PersistentCache::flush();
			}
			if (in_array($ps_cache, array('all', 'usermedia'))) {
				if (($vs_tmp_directory = $o_config->get('media_uploader_root_directory')) && (file_exists($vs_tmp_directory))) {
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
		public static function garbage_collection($po_opts=null) {
			$limit = (int)$po_opts->getOption('limit');
			$quiet = (bool)$po_opts->getOption('quiet');
			
			if(!$quiet) { CLIUtils::addMessage(_t('Performing garbage collection on application caches and temporary directories...')); }
			GarbageCollection::gc(['force' => true, 'limit' => $limit, 'showCLIProgress' => !$quiet]);
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function garbage_collectionParamList() {
			return [
				"limit|l=n" => _t('Maximum number of file cache files to analyze. Large file caches may take a long time to clear. Setting a limit will cap the time spent cleaning the cache and allow cleaning to be done in stages.')
			];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function garbage_collectionUtilityClass() {
			return _t('Maintenance');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function garbage_collectionShortHelp() {
			return _t('Remove stale files from application caches and temporary file locations.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function garbage_collectionHelp() {
			return _t('CollectiveAccess stores often used values, processed configuration files, user-uploaded media and other types of data in application caches. You can clean out old expired data from these locations using this command. If you want to completely clear the application caches of all data regardless of expiration date use the "clear-caches" command.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function do_configuration_check($po_opts=null) {
			include_once(__CA_LIB_DIR__."/Search/SearchEngine.php");
			
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
			if(!($vs_mapping = $po_opts->getOption('mapping'))) {
				CLIUtils::addError("\t\tNo mapping found. Please use the -m parameter to specify a ULAN mapping.");
				return false;
			}

			if (!(ca_data_importers::mappingExists($vs_mapping))) {
				CLIUtils::addError("\t\tMapping $vs_mapping does not exist");
				return false;
			}

			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = $po_opts->getOption('log-level');

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

			$t_importer = new ca_data_importers();
			$t_importer->importDataFromSource(join(',', $va_sources), $vs_mapping, array('format' => 'ULAN', 'showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level));

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
		/**
		 *
		 */
		public static function precache_search_index($po_opts=null) {
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
					'SCRIPT_NAME' => __CA_URL_ROOT__.'/index.php'
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
					'SCRIPT_NAME' => __CA_URL_ROOT__.'/index.php'
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
		public static function check_metadata_alerts($po_opts=null) {
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
			            $va_value_list = $qr_res->get("{$vs_table_name}.{$vs_root_code}", ['returnWithStructure' => true, 'convertCodesToDisplayText' => true]);
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
		public static function check_relationship_type_roots($po_opts=null) {
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
				
				if ($bad_roots = ca_relationship_types::find(['parent_id' => ['>', 0], 'table_num' => $vn_table_num, 'type_code' => ['IN', ['root_for_'.$vn_table_num, 'root_for_table_'.$vn_table_num]]], ['returnAs' => 'modelInstances'])) {
					foreach($bad_roots as $t_bad_root) { 
						$t_bad_root->delete(true);
					}
				}	
				
				if (
					($bad_roots = ca_relationship_types::find(['parent_id' => null, 'table_num' => $vn_table_num, 'type_code' => ['IN' , ['root_for_'.$vn_table_num, 'root_for_table_'.$vn_table_num]]], ['returnAs' => 'modelInstances']))
					&&
					(sizeof($bad_roots) > 1)
				) {
					$roots = sizeof($bad_roots);
					foreach($bad_roots as $t_bad_root) {
						if(!is_array($children = $t_bad_root->getHierarchyChildren(null, ['idsOnly' => true])) || !sizeof($children)) {
							$t_bad_root->delete(true);
							$roots--;
						}
						if($roots == 1) { break; }
					}
				}	
				
				// Create root ca_relationship_types row for table
				if (!$t_root = ca_relationship_types::find(['parent_id' => null, 'table_num' => $vn_table_num], ['returnAs' => 'firstModelInstance'])) {
				    $t_root = new ca_relationship_types();
					$t_root->logChanges(false);
					$t_root->set('table_num', $vn_table_num);
					$t_root->set('type_code', 'root_for_'.$vn_table_num);
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
				} elseif("root_for_{$vn_table_num}" !== $t_root->get('type_code')) {
					$t_root->logChanges(false);
					$t_root->set('type_code', 'root_for_'.$vn_table_num);
					$t_root->update();
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
		 *
		 */
		public static function reload_current_values_for_history_tracking_policies($po_opts=null) {
			$tables = ca_objects::getTablesWithHistoryTrackingPolicies();
			
            if(sizeof($tables) > 0) {
				$tables[0]::clearHistoryTrackingCurrentValues();
				foreach($tables as $table) {
					$c = 0;
					$t = Datamodel::getInstance($table, true);
					$qr = $table::find('*', ['returnAs' => 'searchResult']);
					print CLIProgressBar::start($qr->numHits(), _t('Starting...'));
					while($qr->nextHit()) {
						if ($t->load($qr->getPrimaryKey())) {
							print CLIProgressBar::next(1, _t('Processing %1', $t->getWithTemplate("^{$table}.preferred_labels (^{$table}.idno)")));
							if ($t->deriveHistoryTrackingCurrentValue()) {
								$c++;
							}
						}
					}
					print CLIProgressBar::finish();
					CLIUtils::addMessage(_t('Processed %1 %2', $c, Datamodel::getTableProperty($table, "NAME_PLURAL")));
				}
			} else {
				CLIUtils::addError(_t('No history tracking policies are configured'));
			}
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_current_values_for_history_tracking_policiesParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_current_values_for_history_tracking_policiesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_current_values_for_history_tracking_policiesShortHelp() {
			return _t('Reloads current values for all history tracking policies.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reload_current_values_for_history_tracking_policiesHelp() {
			return _t('CollectiveAccess supports tracking of location and use histories through history tracking policies. Current values for each record for each policy are cached to speed browses and display. From time to time these values may become out of date. Use this command to regenerate the cached values based upon the current state of the database.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_future_values_for_history_tracking_policies($po_opts=null) {
			Datamodel::getInstance('ca_history_tracking_current_values', true);
			$tables = ca_history_tracking_current_values::rowsWithFutureValues();
			
			foreach($tables as $table_num => $rows) {
				print CLIProgressBar::start(sizeof($rows), _t('Starting...'));
			
				$t = Datamodel::getInstance($table_num, true);
				$table = $t->tableName();
				foreach($rows as $row_id) {
					if ($t->load($row_id)) {
                        print CLIProgressBar::next(1, _t('Processing %1', $t->getWithTemplate("^{$table}.preferred_labels (^{$table}.idno)")));
                        if ($t->deriveHistoryTrackingCurrentValue()) {
                            $c++;
                        }
                    }
				}
				print CLIProgressBar::finish();
				CLIUtils::addMessage(_t('Processed %1 %2', $c, Datamodel::getTableProperty($table, "NAME_PLURAL")));
			}
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_future_values_for_history_tracking_policiesParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_future_values_for_history_tracking_policiesUtilityClass() {
			return _t('Maintenance');
		}

		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_future_values_for_history_tracking_policiesShortHelp() {
			return _t('Updates current values for all records with tracking policies for which values with upcoming data have been set.');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function check_future_values_for_history_tracking_policiesHelp() {
			return _t('CollectiveAccess supports tracking of location and use histories through history tracking policies. Current values for each record for each policy are cached to speed browses and display. For records with values having future dates, current values will change as those future dates become present dates. This command will update the cached current values to reflect the transition from future to current. It should be run periodically (at least once a day) to ensure the accuracy of the current value cache.');
		}
        # -------------------------------------------------------
		public static function reload_object_current_location_dates($po_opts=null) {
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
		 * @param Zend_Console_Getopt|null $po_opts
		 * @return bool
		 */
		public static function set_default_field_values($po_opts=null) {
			// Find containers with dependent fields
			$va_elements = ca_metadata_elements::getElementSetsWithSetting("default_text");
			
			
			$c = 0;
			foreach($va_elements as $va_element) {
			    print_R($va_element);
			    if (!strlen($default_value = trim($va_element['settings']['default_text']))) { continue; }
			    
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
			                foreach($va_values_by_attribute_id as $vn_attr_id => $va_values) {
			                    if ($va_values[$va_element['element_code']]) { continue; }
			                    CLIUtils::addMessage(_t('Processing row %1 for %2.%3', $vn_row_id, $vs_root_code, $va_element['element_code']));
			                    
			                    if (!$t_instance->load($vn_row_id)) { continue; }
			                    $t_instance->setMode(ACCESS_WRITE);
			                    
			                    if(!isset($va_values[$va_element['element_code']]) && ($va_element['element_code'] === $vs_root_code)) {
			                        $t_instance->addAttribute([
			                            $va_element['element_code'] => $default_value
			                        ], $va_element['element_code']);
			                    } else {
			                        $va_values[$va_element['element_code']] = $default_value;
                                    $t_instance->editAttribute(
                                        $vn_attr_id, $vn_root_id, $va_values
                                    );
                                }
			                    $t_instance->update();
			                    $c++;
			                    if ($t_instance->numErrors() > 0) {
			                        CLIUtils::addError(_t("Could not set default value: %1", join("; ", $t_instance->getErrors())));
			                    }
			                }
			            }
			        }
			    }
			}
			
			CLIUtils::addMessage(_t("Set default values on %1 records", $c));
		}
		# -------------------------------------------------------
		public static function set_default_field_valuesParamList() {
			return [];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function set_default_field_valuesUtilityClass() {
            return _t('Maintenance');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function set_default_field_valuesShortHelp() {
			return _t('Set default values on all fields where not value is set.');
        }
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function set_default_field_valuesHelp() {
			return _t('Sets configured default value on any field where no value has yet been set.');
		}
		# -------------------------------------------------------
    }
