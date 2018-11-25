<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Maintenance.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
			return [];
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
		public static function reload_current_values_for_history_tracking_policies($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");

            // TODO: implement new tracking system~
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

    }
