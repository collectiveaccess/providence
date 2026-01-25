<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Maintenance.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2026 Whirl-i-Gig
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
	 * Rebuild sort values
	 */
	public static function rebuild_sort_values($opts=null) {
		$o_db = new Db();
		ini_set('memory_limit', '4096m');
		
		$tables = $opts ? trim((string)$opts->getOption('table')) : null;
		
		if($tables) {
			$tables = preg_split('![,;]+!', $tables);
		} else {
			$tables = [
				'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
				'ca_occurrences', 'ca_collections', 'ca_storage_locations',
				'ca_object_representations', 'ca_representation_annotations',
				'ca_list_items', 'ca_sets'
			];
		}
		
		foreach($tables as $table) {
			if(is_numeric($table)) { continue; }
			if(!($t_table = Datamodel::getInstance($table))) { continue; }
			
			$deleted_sql = ($t_table->hasField('deleted')) ? " WHERE t.deleted = 0" : "";
			$pk = $t_table->primaryKey();
			$qr_res = $o_db->query("SELECT t.{$pk} FROM {$table} t {$deleted_sql}");

			if ($label_table_name = $t_table->getLabelTableName()) {
				$t_label = new $label_table_name;
				$label_pk = $t_label->primaryKey();
				$qr_labels = $o_db->query("
					SELECT l.{$label_pk} 
					FROM {$label_table_name} l
					INNER JOIN {$table} AS t ON t.{$pk} = l.{$pk}
					{$deleted_sql}
				");
				
				$table_name_display = $t_label->getProperty('NAME_PLURAL');

				print CLIProgressBar::start($qr_labels->numRows(), _t('Processing %1', $t_label->getProperty('NAME_PLURAL')));
				while($qr_labels->nextRow()) {
					$label_pk_val = $qr_labels->get($label_pk);
					
					CLIProgressBar::setMessage(_t("[Sort: %1][Mem: %2]", $table_name_display, caGetMemoryUsage()));
					print CLIProgressBar::next();
					if ($t_label->load($label_pk_val)) {
						$t_table->logChanges(false);
						$t_label->update(['dontDoSearchIndexing' => true]);
					}
				}
				print CLIProgressBar::finish();
			}

			print CLIProgressBar::start($qr_res->numRows(), _t('Processing %1 identifiers', $t_table->getProperty('NAME_SINGULAR')));
			
			$table_name_display = $t_table->getProperty('NAME_PLURAL');
			while($qr_res->nextRow()) {
				$pk_val = $qr_res->get($pk);
				
				CLIProgressBar::setMessage(_t("[Sort: %1 identifiers][Mem: %2]", $table_name_display, caGetMemoryUsage()));
				print CLIProgressBar::next();
				if ($t_table->load($pk_val)) {
					$t_table->logChanges(false);
					$t_table->update(['dontDoSearchIndexing' => true]);
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
	public static function remove_unused_media($opts=null) {
		$delete_opt = (bool)$opts->getOption('delete');
		$o_db = new Db();

		$t_rep = new ca_object_representations();

		$qr_reps = $o_db->query("SELECT representation_id, media FROM ca_object_representations");
		print CLIProgressBar::start($qr_reps->numRows(), _t('Loading valid file paths from database'))."\n";

		$paths = array();
		while($qr_reps->nextRow()) {
			print CLIProgressBar::next();
			$versions = $qr_reps->getMediaVersions('media');
			if (!is_array($versions)) { continue; }
			
			$multifiles = $t_rep->getFileList($qr_reps->get('ca_object_representations.representation_id'), null, null, ['returnAllVersions' => true]);
			foreach($versions as $version) {
				$paths[$qr_reps->getMediaPath('media', $version)] = true;
				
				if(is_array($multifiles)) {
					foreach($multifiles as $mfinfo) {
						foreach($mfinfo as $mfk => $mf) {
							if(!preg_match("!_path$!", $mfk)) { continue; }
							$paths[$mf] = true;
						}
					}
				}
			}
		}
		print CLIProgressBar::finish();

		print CLIProgressBar::start(1, _t('Reading file list'));
		$contents = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/media/'.__CA_APP_NAME__, true, false);
		print CLIProgressBar::next();
		print CLIProgressBar::finish();

		$delete_count = 0;

		print CLIProgressBar::start(sizeof($contents), _t('Finding unused files'));

		foreach($contents as $path) {
			print CLIProgressBar::next();
			if (!preg_match('!_ca_object_representation!', $path)) { continue; } // skip non object representation files
			if (!$paths[$path]) {
				$delete_count++;
				if ($delete_opt) {
					@unlink($path);
				}
			}
		}
		print CLIProgressBar::finish()."\n";

		CLIUtils::addMessage(_t('There are %1 files total', sizeof($contents)));

		if(sizeof($contents) > 0) {
			$percent = sprintf("%2.1f", ($delete_count/sizeof($contents)) * 100)."%";
		} else {
			$percent = '0.0%';
		}

		if ($delete_count == 1) {
			CLIUtils::addMessage($delete_opt ? _t("%1 file (%2) was deleted", $delete_count, $percent) : _t("%1 file (%2) is unused", $delete_count, $percent));
		} else {
			CLIUtils::addMessage($delete_opt ?  _t("%1 files (%2) were deleted", $delete_count, $percent) : _t("%1 files (%2) are unused", $delete_count, $percent));
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
	public static function remove_deleted_representations($opts=null) {
		$delete_opt = (bool)$opts->getOption('delete');
		$o_db = new Db();

		$t_rep = new ca_object_representations();
		$paths = array();

		$qr_reps = $o_db->query("SELECT * FROM ca_object_representations WHERE deleted=1");

		if($delete_opt) {
			print CLIProgressBar::start($qr_reps->numRows(), _t('Removing deleted representations from database'));
		} else {
			print CLIProgressBar::start($qr_reps->numRows(), _t('Loading deleted representations from database'));
		}

		while($qr_reps->nextRow()) {
			print CLIProgressBar::next();
			$versions = $qr_reps->getMediaVersions('media');
			if (!is_array($versions)) { continue; }
			foreach($versions as $version) {
				$paths[$qr_reps->getMediaPath('media', $version)] = true;
			}

			if($delete_opt) {
				$t_rep->load($qr_reps->get('representation_id'));
				$t_rep->removeAllLabels();
				$t_rep->delete(true, array('hard' => true));
			}
		}

		print CLIProgressBar::finish().PHP_EOL;

		if($delete_opt && ($qr_reps->numRows() > 0)) {
			CLIUtils::addMessage(_t('Done!'), array('color' => 'green'));
		} elseif($qr_reps->numRows() == 0) {
			CLIUtils::addMessage(_t('There are no deleted representations to process!'), array('color' => 'green'));
		} else {
			CLIUtils::addMessage(_t("%1 files are referenced by %2 deleted records. Both the records and the files will be deleted if you re-run the script with the -d (--delete) option and the correct permissions.", sizeof($paths), $qr_reps->numRows()));
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
	public static function purge_deleted($opts=null) {
		require_once(__CA_LIB_DIR__."/Logging/Downloadlog.php");
	
		CLIUtils::addMessage(_t("Are you sure you want to PERMANENTLY remove all deleted records? This cannot be undone.\n\nType 'y' to proceed or 'N' to cancel, then hit return ", $current_revision, __CollectiveAccess_Schema_Rev__));
		flush();
		ob_flush();
		$confirmation  =  trim( fgets( STDIN ) );
		if ( $confirmation !== 'y' ) {
			// The user did not say 'y'.
			return false;
		}

		$tables = Datamodel::getTableNames();
		$o_db = new Db();
		$tables_to_process = array_filter(array_map("trim", preg_split('![ ,;]!', (string)$opts->getOption('tables'))), "strlen");

		$t = 0;
		foreach($tables as $table) {
			if(is_array($tables_to_process) && sizeof($tables_to_process) && !in_array($table, $tables_to_process)) { continue; }
			if (!$t_instance = Datamodel::getInstanceByTableName($table)) { continue; }
			if (!$t_instance->hasField('deleted')) { continue; }

			$pk = $t_instance->primaryKey();

			$qr_del = $o_db->query("SELECT {$pk} FROM {$table} WHERE deleted = 1");
			if($qr_del->numRows() > 0) {
				print CLIProgressBar::start($qr_del->numRows(), _t('Removing deleted %1 from database', $t_instance->getProperty('NAME_PLURAL')));

				$row_ids = $qr_del->getAllFieldValues($pk);
				
				if ($table === 'ca_object_representations') {
					Downloadlog::purgeForRepresentation($row_ids);
				}
				$c = 0;
				//while($qr_del->nextRow()) {
				foreach($row_ids as $row_id) {
					print CLIProgressBar::next();
					$t_instance->load($row_id);
					$t_instance->removeAllLabels();
					$t_instance->delete(true, array('hard' => true));
					$c++;
				}

				CLIUtils::addMessage(_t('Removed %1 %2', $c, $t_instance->getProperty(($c == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL')), array('color' => 'green'));
				print CLIProgressBar::finish();
				
				$t += $c;
			}
		}
		self::remove_unused_guids($opts);
		
		if ($t > 0) {
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function clear_search_indexing_queue_lock_file($opts=null) {
		if (ca_search_indexing_queue::lockExists()) {
			if (ca_search_indexing_queue::lockCanBeRemoved()) {
				ca_search_indexing_queue::lockRelease();
				CLIUtils::addMessage(_t("Removed search indexing queue lock"));
				return true;
			} else {
				CLIUtils::addMessage(_t("Insufficient privileges to remove search indexing queue lock file. Try running caUtils under a user with privileges"));
			}
		} else {
			CLIUtils::addMessage(_t("Search indexing queue lock is not present"));
		}
		return false;
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
	public static function fix_permissions($opts=null) {
		$config = Configuration::load();
		
		// Guess web server user
		if (!($user = $opts->getOption("user"))) {
			$user = caDetermineWebServerUser();
			if (!$opts->getOption("quiet") && $user) { CLIUtils::addMessage(_t("Determined web server user to be \"%1\"", $user)); }
		}

		if (!$user) {
			$user = caGetProcessUserName();
			CLIUtils::addError(_t("Cannot determine web server user. Using %1 instead.", $user));
		}

		if (!$user) {
			CLIUtils::addError(_t("Cannot determine the user. Please specify one with the --user option."));
			return false;
		}

		if (!($group = $opts->getOption("group"))) {
			$group = caGetProcessGroupName();
			if (!$opts->getOption("quiet") && $group) { CLIUtils::addMessage(_t("Determined web server group to be \"%1\"", $group)); }
		}

		if (!$group) {
			CLIUtils::addError(_t("Cannot determine the group. Please specify one with the --group option."));
			return false;
		}

		if (!$opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the temporary directory (\"%2\") for ownership by \"%1\"...", $user, __CA_TEMP_DIR__)); }
		$files = caGetDirectoryContentsAsList(__CA_TEMP_DIR__, true, true, false, true, ['includeRoot' => true]);

		foreach($files as $path) {
			chown($path, $user);
			chgrp($path, $group);
			chmod($path, 0770);
		}
		if (!$opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the media directory (media/appname) for ownership by \"%1\"...", $user)); }
		$media_root = $config->get("ca_media_root_dir");
		$files = caGetDirectoryContentsAsList($media_root, true, true, false, true, ['includeRoot' => true]);

		foreach($files as $path) {
			chown($path, $user);
			chgrp($path, $group);
			chmod($path, 0775);
		}

		if (!$opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the HTMLPurifier definition cache directory " . Configuration::load()->get('purify_serializer_path') . " for ownership by \"%1\"...", $user)); }
		$files = caGetDirectoryContentsAsList(Configuration::load()->get('purify_serializer_path'), true, false, false, true, ['includeRoot' => true]);

		foreach($files as $path) {
			chown($path, $user);
			chgrp($path, $group);
			chmod($path, 0770);
		}
		
		if (!$opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the log directory for ownership by \"%1\"...", $user)); }
		$files = caGetDirectoryContentsAsList(__CA_LOG_DIR__, true, true, false, true, ['includeRoot' => true]);

		foreach($files as $path) {
			chown($path, $user);
			chgrp($path, $group);
			chmod($path, 0770);
		}
		
		if (!$opts->getOption("quiet")) { CLIUtils::addMessage(_t("Fixing permissions for the user media upload directory (uploads) for ownership by \"%1\"...", $user)); }
		$upload_root = $config->get("media_uploader_root_directory");
		$files = caGetDirectoryContentsAsList($upload_root, true, true, false, true, ['includeRoot' => true]);
		
		foreach($files as $path) {
			chown($path, $user);
			chgrp($path, $group);
			chmod($path, 0770);
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
		return _t("CollectiveAccess must have both read and write access to the temporary storage directory (%3), media directory (media) and HTMLPurifier definition cache (app/lib/Parsers/htmlpurifier/standalone/HTMLPurifier/DefinitionCache). A run-time error will be displayed if any of these locations is not accessible to the application. To change these permissions to allow CollectiveAccess to run normally run this command while logged in with administrative/root privileges. You are currently logged in as %1 (uid %2). You can specify which user will be given ownership of the directories using the --user option. If you do not specify a user, the web server user for your server will be automatically determined and used.", caGetProcessUserName(), caGetProcessUserID(), __CA_TEMP_DIR__);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function validate_using_metadata_dictionary_rules($opts=null) {
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
	public static function check_media_fixity($opts=null) {
		$quiet = $opts->getOption('quiet');
		
		$email = caGetOption('email', $opts, null, ['castTo' => 'string']);
		if ($email && !caCheckEmailAddress($email)) {
			CLIUtils::addError(_t("Email address is invalid"));
			return false;
		}
		if (!($file_path = strtolower((string)$opts->getOption('file'))) && !$email) {
			CLIUtils::addError(_t("You must specify an output file or email address"));
			return false;
		}
		
		$file_path = str_replace("%date", date("Y-m-d_h\hi\m"), $file_path);
		
		switch($format = caGetOption('format', $opts, 'text', ['forceLowercase' => true, 'validValues' => ['text', 'tab', 'csv'], 'castTo' => 'string'])) {
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
		
		
		$versions = caGetOption('versions', $opts, null, ['delimiter' => [',', ';']]);
		$kinds = caGetOption('kinds', $opts, 'all', ['forceLowercase' => true, 'validValues' => ['all', 'ca_object_representations', 'ca_attributes'], 'delimiter' => [',', ';']]);
		
		$o_db = new Db();
		$t_rep = new ca_object_representations();

		$report_output = join(($format == 'tab') ? "\t" : ",", array(_t('Type'), _t('Error'), _t('Name'), _t('ID'), _t('Version'), _t('File path'), _t('Expected MD5'), _t('Actual MD5')))."\n";

		$counts = [];

		if (in_array('all', $kinds) || in_array('ca_object_representations', $kinds)) {
			if (!($start = (int)$opts->getOption('start_id'))) { $start = null; }
			if (!($end = (int)$opts->getOption('end_id'))) { $end = null; }


			if ($id = (int)$opts->getOption('id')) {
				$start = $end = $id;
			}

			$ids = array();
			if ($ids = (string)$opts->getOption('ids')) {
				if (sizeof($tmp = explode(",", $ids))) {
					foreach($tmp as $id) {
						if ((int)$id > 0) {
							$ids[] = (int)$id;
						}
					}
				}
			}

			$sql_wheres = array('o_r.deleted = 0');
			$params = array();
			$sql_joins = '';

			if (sizeof($ids)) {
				$sql_wheres[] = "o_r.representation_id IN (?)";
				$params[] = $ids;
			} else {
				if (
					(($start > 0) && ($end > 0) && ($start <= $end)) || (($start > 0) && ($end == null))
				) {
					$sql_wheres[] = "o_r.representation_id >= ?";
					$params[] = $start;
					if ($end) {
						$sql_wheres[] = "o_r.representation_id <= ?";
						$params[] = $end;
					}
				}
			}
			
			if ($object_ids = (string)$opts->getOption('object_ids')) {
				$object_ids = explode(",", $object_ids);
				foreach($object_ids as $i => $object_id) {
					$object_ids[$i] = (int)$object_id;
				}
				
				if (sizeof($object_ids)) { 
					$sql_wheres[] = "(oxor.object_id IN (?))";
					$sql_joins = "INNER JOIN ca_objects_x_object_representations AS oxor ON oxor.representation_id = o_r.representation_id";
					$params[] = $object_ids;
				}
			}
			
			// Verify object representations
			$qr_reps = $o_db->query("
				SELECT o_r.representation_id, o_r.idno, o_r.media 
				FROM ca_object_representations o_r 
				{$sql_joins}
				WHERE 
					".join(" AND ", $sql_wheres), $params
			);
			
			if (!$quiet) { print CLIProgressBar::start($rep_count = $qr_reps->numRows(), _t('Checking object representations'))."\n"; }
			$errors = 0;
			
			if ($qr_reps->numRows() > 0) {
				$counts[] = _t('%1 media representations', $qr_reps->numRows());
				while($qr_reps->nextRow()) {
					$representation_id = $qr_reps->get('representation_id');
					if (!$quiet) { print CLIProgressBar::next(1, _t("Checking representation media %1", $representation_id)); }

					$media_versions = (is_array($versions) && sizeof($versions) > 0) ? $versions : $qr_reps->getMediaVersions('media');
					foreach($media_versions as $version) {
						if (!($path = $qr_reps->getMediaPath('media', $version))) { continue; }
						if (!($database_md5 = $qr_reps->getMediaInfo('media', $version, 'MD5'))) { continue; }		// skip missing MD5s - indicates empty file
						$file_md5 = md5_file($path);

						if ($database_md5 !== $file_md5) {
							$t_rep->load($representation_id);

							$message = _t("[Object representation][MD5 mismatch] %1; version %2 [%3]", $t_rep->get("ca_objects.preferred_labels.name")." (". $t_rep->get("ca_objects.idno")."); representation_id={$representation_id}", $version, $path);
							switch($format) {
								case 'text':
								default:
									$report_output .= "{$message}\n";
									break;
								case 'tab':
								case 'csv':
									$log = array(_t('Object representation'), ("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($t_rep->get("ca_objects.preferred_labels.name")." (". $t_rep->get("ca_objects.idno").")").'"', $representation_id, $version, $path, $database_md5, $file_md5);
									$report_output .= join(($format == 'tab') ? "\t" : ",", $log)."\n";
									break;
							}

							CLIUtils::addError($message);
							$errors++;
						}
					}
				}
			}

			if (!$quiet) { 
				print CLIProgressBar::finish(); 				
				if ($errors == 1) {
					CLIUtils::addMessage(_t('%1 error for %2 representations', $errors, $rep_count));
				} else {
					CLIUtils::addMessage(_t('%1 errors for %2 representations', $errors, $rep_count));
				}
			}
		}


		if (in_array('all', $kinds) || in_array('ca_attributes', $kinds)) {
			// get all media elements
			$elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

			if (is_array($elements) && sizeof($elements)) {
				if (is_array($element_ids = caExtractValuesFromArrayList($elements, 'element_id', array('preserveKeys' => false))) && sizeof($element_ids)) {
					$qr_c = $o_db->query("
						SELECT count(*) c
						FROM ca_attribute_values
						WHERE
							element_id in (?)
					", array($element_ids));
					if ($qr_c->nextRow()) { $count = $qr_c->get('c'); } else { $count = 0; }

					if (!$quiet) { print CLIProgressBar::start($count, _t('Checking attribute media')); }

					$errors = 0;
					$c = 0;
					foreach($elements as $element_code => $element_info) {
						$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$element_info['element_id']);
						$vals = $qr_vals->getAllFieldValues('value_id');
						foreach($vals as $value_id) {
							$t_attr_val = new ca_attribute_values($value_id);
							if ($t_attr_val->getPrimaryKey()) {
								$c++;
								$t_attr_val->useBlobAsMediaField(true);


								if (!$quiet) { print CLIProgressBar::next(1, _t("Checking attribute media %1", $value_id)); }

								$media_versions = (is_array($versions) && sizeof($versions) > 0) ? $versions : $t_attr_val->getMediaVersions('value_blob');
								foreach($media_versions as $version) {
									if (!($path = $t_attr_val->getMediaPath('value_blob', $version))) { continue; }

									if (!($database_md5 = $t_attr_val->getMediaInfo('value_blob', $version, 'MD5'))) { continue; }	// skip missing MD5s - indicates empty file
									$file_md5 = md5_file($path);

									if ($database_md5 !== $file_md5) {
										$t_attr = new ca_attributes($attribute_id = $t_attr_val->get('attribute_id'));

										$label = "attribute_id={$attribute_id}; value_id={$value_id}";
										if ($t_instance = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
											if ($t_instance->load($t_attr->get('row_id'))) {
												$label = $t_instance->get($t_instance->tableName().'.preferred_labels');
												if ($idno = $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
													$label .= " ({$label})";
												}
											}
										}

										$message = _t("[Media attribute][MD5 mismatch] %1; value_id=%2; version %3 [%4]", $label, $value_id, $version, $path);

										switch($format) {
											case 'text':
											default:
												$report_output .= "{$message}\n";
												break;
											case 'tab':
											case 'csv':
												$log = array(_t('Media attribute'), _t("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($label).'"', $value_id, $version, $path, $database_md5, $file_md5);
												$report_output .= join(($format == 'tab') ? "\t" : ",", $log);
												break;
										}

										CLIUtils::addError($message);
										$errors++;
									}
								}

							}
						}
					}
					
					if ((sizeof($elements) > 0) && ($c > 0)) {
						$counts[] = _t('%1 media in %2 metadata elements', sizeof($elements), $c);
					}
					if (!$quiet) { 
						print CLIProgressBar::finish(); 
						if($errors == 1) {
							CLIUtils::addMessage(_t('%1 error for %2 attributes', $errors, $rep_count));
						} else {
							CLIUtils::addMessage(_t('%1 errors for %2 attributes', $errors, $rep_count));
						}
					}
				}
			}

			// get all File elements
			$elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(15)); // 15=file

			if (is_array($elements) && sizeof($elements)) {
				if (is_array($element_ids = caExtractValuesFromArrayList($elements, 'element_id', array('preserveKeys' => false))) && sizeof($element_ids)) {
					$qr_c = $o_db->query("
						SELECT count(*) c
						FROM ca_attribute_values
						WHERE
							element_id in (?)
					", array($element_ids));
					if ($qr_c->nextRow()) { $count = $qr_c->get('c'); } else { $count = 0; }

					if (!$quiet) { print CLIProgressBar::start($count, _t('Checking attribute files')); }

					$errors = 0;
					$c = 0;
					foreach($elements as $element_code => $element_info) {
						$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$element_info['element_id']);
						$vals = $qr_vals->getAllFieldValues('value_id');
						foreach($vals as $value_id) {
							$t_attr_val = new ca_attribute_values($value_id);
							if ($t_attr_val->getPrimaryKey()) {
								$c++;
								
								$t_attr_val->useBlobAsFileField(true);


								if (!$quiet) { print CLIProgressBar::next(1, _t("Checking attribute file %1", $value_id)); }

								if (!($path = $t_attr_val->getFilePath('value_blob'))) { continue; }

								if (!($database_md5 = $t_attr_val->getFileInfo('value_blob', 'MD5'))) { continue; }	// skip missing MD5s - indicates empty file
								$file_md5 = md5_file($path);

								if ($database_md5 !== $file_md5) {
									$t_attr = new ca_attributes($attribute_id = $t_attr_val->get('attribute_id'));

									$label = "attribute_id={$attribute_id}; value_id={$value_id}";
									if ($t_instance = Datamodel::getInstanceByTableNum($t_attr->get('table_num'), true)) {
										if ($t_instance->load($t_attr->get('row_id'))) {
											$label = $t_instance->get($t_instance->tableName().'.preferred_labels');
											if ($idno = $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))) {
												$label .= " ({$label})";
											}
										}
									}

									$message = _t("[File attribute][MD5 mismatch] %1; value_id=%2; version %3 [%4]", $label, $value_id, $version, $path);

									switch($format) {
										case 'text':
										default:
											$report_output .= "{$message}\n";
											break;
										case 'tab':
										case 'csv':
											$log = array(_t('File attribute'), _t("MD5 mismatch"), '"'.caEscapeForDelimitedOutput($label).'"', $value_id, $version, $path, $database_md5, $file_md5);
											$report_output .= join(($format == 'tab') ? "\t" : ",", $log);
											break;
									}

									CLIUtils::addError($message);
									$errors++;
								}

							}
						}
					}
					
					if((sizeof($elements) > 0) && ($c > 0)) {
						$counts[] = _t('%1 files in %2 metadata elements', $c, sizeof($elements));
					}
					if (!$quiet) { 
						print CLIProgressBar::finish();
						if ($errors == 1) {
							CLIUtils::addMessage(_t('%1 error for %2 attributes', $errors, $rep_count));
						} else {
							CLIUtils::addMessage(_t('%1 errors for %2 attributes', $errors, $rep_count));
						}
					}
				}
			}
		}
		
		if ($file_path) {
			file_put_contents($file_path, $report_output);
		}
		
		$o_request = new RequestHTTP(null, [
			'no_headers' => true,
			'simulateWith' => [
				'REQUEST_METHOD' => 'GET',
				'SCRIPT_NAME' => __CA_URL_ROOT__.'/index.php'
			]
		]);
		if ($email) {
			$delete_file = false;
			if (!$file_path) {
				$file_path = caGetTempFileName('fixity', 'txt');
				file_put_contents($file_path, $report_output);
				$delete_file = true;
			}
			
			$a = $o_request->config->get('app_display_name');
			$attachment = null;
			if($errors > 0) {
				$attachment = [
					'path' => $file_path,
					'name' => _t('%1_fixity_report_%2.%3', $a, date("Y-m-d_h\hi\m"), $extension),
					'mimetype' => $mimetype
				];
			}
			
			if (sizeof($counts) > 0) {
				if (!caSendMessageUsingView(
					$o_request, 
					[$email], 
					[__CA_ADMIN_EMAIL__], 
					_t('[%1] Media fixity report for %2', $a, $d = caGetLocalizedDate()), 
					'check_media_fixity_report.tpl', 
					['date' => $d, 'app_name' => $a, 'num_errors' => $errors, 'counts' => caMakeCommaListWithConjunction($counts)], 
					null, null, 
					['attachment' => $attachment])
				) {
					global $g_last_email_error;
					CLIUtils::addError(_t("Could not send email to %1: %2", $email, $g_last_email_error));
				}
			}
			if ($delete_file) { @unlink($file_path); }
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
	public static function clear_caches($opts=null) {
		$config = Configuration::load();

		$cache = strtolower($opts ? (string)$opts->getOption('cache') : 'all');
		if (!in_array($cache, ['all', 'app', 'usermedia'])) { $cache = 'all'; }

		if (in_array($cache, ['all', 'app'])) {
			CLIUtils::addMessage(_t('Clearing application caches...'));
			if (is_writable($config->get('taskqueue_tmp_directory'))) {
				$tempdir_info = stat($config->get('taskqueue_tmp_directory'));
				caRemoveDirectory($config->get('taskqueue_tmp_directory'), false);
				mkdir($config->get('purify_serializer_path'), $tempdir_info['mode']);
				chown($config->get('purify_serializer_path'), $tempdir_info['uid']);
				chgrp($config->get('purify_serializer_path'), $tempdir_info['gid']);
				clearstatcache();
			} else {
				CLIUtils::addError(_t('Skipping clearing of application cache because it is not writable'));
			}
			try {
				PersistentCache::flush();
			} catch(Exception $e) {
				// noop
			}
			ExternalCache::flush();
			MemoryCache::flush();
		}
		if (in_array($cache, ['all', 'usermedia'])) {
			if (($tmp_directory = $config->get('media_uploader_root_directory')) && (file_exists($tmp_directory))) {
				if (is_writable($tmp_directory)) {
					CLIUtils::addMessage(_t('Clearing user media cache in %1...', $tmp_directory));
					caRemoveDirectory($tmp_directory, false);
				} else {
					CLIUtils::addError(_t('Skipping clearing of user media cache because it is not writable'));
				}
			} else {
				if (!$tmp_directory) {
					CLIUtils::addError(_t('Skipping clearing of user media cache because no cache directory is configured'));
				} else {
					CLIUtils::addError(_t('Skipping clearing of user media cache because the configured directory at %1 does not exist', $tmp_directory));
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
	public static function garbage_collection($opts=null) {
		$limit = (int)$opts->getOption('limit');
		$quiet = (bool)$opts->getOption('quiet');
		
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
	public static function do_configuration_check($opts=null) {
		include_once(__CA_LIB_DIR__."/Search/SearchEngine.php");
		
		// Media
		$t_media = new Media();
		$plugin_names = $t_media->getPluginNames();
		
		
		CLIUtils::addMessage(_t("Checking media processing plugins..."), array('color' => 'bold_blue'));
		foreach($plugin_names as $plugin_name) {
			if ($plugin_status = $t_media->checkPluginStatus($plugin_name)) {
				CLIUtils::addMessage("\t"._t("Found %1", $plugin_name));
			}
		}
		CLIUtils::addMessage("\n"); 
	
		// Application plugins
		CLIUtils::addMessage(_t("Checking application plugins..."), array('color' => 'bold_blue'));
		$plugin_names = ApplicationPluginManager::getPluginNames();
		$plugins = array();
		foreach($plugin_names as $plugin_name) {
			if ($plugin_status = ApplicationPluginManager::checkPluginStatus($plugin_name)) {
				CLIUtils::addMessage("\t"._t("Found %1", $plugin_name));
			}
		}
		CLIUtils::addMessage("\n");
	
		// Barcode generation
		CLIUtils::addMessage(_t("Checking for barcode dependencies..."), array('color' => 'bold_blue'));
		$gd_is_available = caMediaPluginGDInstalled(true);
		CLIUtils::addMessage("\t("._t('GD is a graphics processing library required for all barcode generation.').")");
		if (!$gd_is_available) {
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
			foreach(ConfigurationCheck::getErrors() as $i => $error) {
				CLIUtils::addError("\t\t[".($i + 1)."] {$error}");
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
	public static function reload_service_values($opts=null) {
		$infoservice_elements = ca_metadata_elements::getElementsAsList(
			false, null, null, true, false, false, array(__CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__)
		);

		$o_db = new Db();

		foreach($infoservice_elements as $element) {
			$qr_values = $o_db->query("
				SELECT * FROM ca_attribute_values
				WHERE element_id = ?
			", $element['element_id']);

			print CLIProgressBar::start($qr_values->numRows(), "Reloading values for element code ".$element['element_code']);
			$t_value = new ca_attribute_values();

			while($qr_values->nextRow()) {
				$o_val = new InformationServiceAttributeValue($qr_values->getRow());
				$uri = $o_val->getUri();

				print CLIProgressBar::next(); // inc before first continuation point

				if(!$uri || !strlen($uri)) { continue; }
				if(!$t_value->load($qr_values->get('value_id'))) { continue; }

				$t_value->editValue($uri);

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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function reload_ulan_records($opts=null) {
		if(!($mapping = $opts->getOption('mapping'))) {
			CLIUtils::addError("\t\tNo mapping found. Please use the -m parameter to specify a ULAN mapping.");
			return false;
		}

		if (!(ca_data_importers::mappingExists($mapping))) {
			CLIUtils::addError("\t\tMapping $mapping does not exist");
			return false;
		}

		$log_dir = $opts->getOption('log');
		$log_level = $opts->getOption('log-level');

		$o_db = new Db();
		$qr_items = $o_db->query("
			SELECT DISTINCT source FROM ca_data_import_events WHERE type_code = 'ULAN'
		");

		$sources = array();

		while($qr_items->nextRow()) {
			$source = $qr_items->get('source');
			if(!isURL($source)) {
				continue;
			}

			if(!preg_match("/http\:\/\/vocab\.getty\.edu\/ulan\//", $source)) {
				continue;
			}

			$sources[] = $source;
		}

		$t_importer = new ca_data_importers();
		$t_importer->importDataFromSource(join(',', $sources), $mapping, array('format' => 'ULAN', 'showCLIProgressBar' => true, 'logDirectory' => $log_dir, 'logLevel' => $log_level));

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
	public static function precache_search_index($opts=null) {
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
		return _t('Preload SQLSearch index into MySQL in-memory cache. This is only relevant if you are using the MySQL-based SQLSearch engine. Preloading can significantly improve performance on systems with large search indices. Note that your MySQL installation must have a large enough buffer pool configured to hold the index. Loading may take several minutes.');
	}
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function precache_content($opts=null) {
		$config = Configuration::load();
		if(!(bool)$config->get('do_content_caching')) { 
			CLIUtils::addError(_t("Content caching is not enabled"));
			return;
		}
		$access = array_unique(array_merge($config->get('public_access_settings') ?? [], $config->get('privileged_access_settings') ?? []));
								
		$cache_config = Configuration::load('content_caching.conf');
		if(!is_array($exclude_from_precache = $cache_config->get('exclude_from_precache'))) { $exclude_from_precache = []; }
		
		$cached_actions = $cache_config->getAssoc('cached_actions');
		if(!is_array($cached_actions)) { 
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
		
		$site_protocol = $config->get('site_protocol');
		if (!($site_hostname = $config->get('site_hostname'))) {
			$site_hostname = "localhost";
		}
		
		foreach($cached_actions as $controller => $actions) {
			if(in_array($controller, $exclude_from_precache)) { continue; }
			switch($controller) {
				case 'Browse':
				case 'Search':
					// preloading of cache not supported
					CLIUtils::addMessage(_t("Preloading from %1 is not supported", $controller), array('color' => 'yellow'));
					break;
				case 'Detail':
					$tmp = explode("/", $controller);
					$controller = array_pop($tmp);
					$module_path = join("/", $tmp);
					
					$o_detail_config = caGetDetailConfig();
					$detail_types = $o_detail_config->getAssoc('detailTypes');
					
					foreach($actions as $action => $ttl) {
						if (is_array($detail_types[$action])) {
							$table = $detail_types[$action]['table'];
							$types = $detail_types[$action]['restrictToTypes'];
							if (!file_exists(__CA_MODELS_DIR__."/{$table}.php")) { continue; }
							require_once(__CA_MODELS_DIR__."/{$table}.php");
							
							if ($url = caNavUrl($o_request, $module_path, $controller, $action)) {
								// get ids
								$ids = call_user_func_array(array($table, 'find'), array(['deleted' => 0, 'access' => sizeof($access) ? ['in', $access] : null], ['restrictToTypes' => $types, 'returnAs' => 'ids']));
							
								if (is_array($ids) && sizeof($ids)) {
									foreach($ids as $id) {
										CLIUtils::addMessage(_t("Preloading from %1::%2 (%3)", $controller, $action, $id), array('color' => 'bold_blue'));
										file_get_contents($site_protocol."://".$site_hostname.$url."/$id?noCache=1");
									}
								}
							}
						} else {
							CLIUtils::addMessage(_t("Preloading from %1::%2 failed because there is no detail configured for %2", $controller, $action), array('color' => 'yellow'));
						}
						
					}
					break;
				case 'splash':
				default:
					$tmp = explode("/", $controller);
					if ($controller == 'splash') { $controller = array_pop($tmp); }
					$module_path = join("/", $tmp);
					foreach($actions as $action => $ttl) {
						if ($url = caNavUrl($o_request, $module_path, $controller, $action, array('noCache' => 1))) {
						
							CLIUtils::addMessage(_t("Preloading from %1::%2", $controller, $action), array('color' => 'bold_blue'));
							$url = $site_protocol."://".$site_hostname.$url;
							file_get_contents($url);
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
	public static function generate_missing_guids($opts=null) {
		$o_db = new Db();

		foreach(Datamodel::getTableNames() as $table) {
			$t_instance = Datamodel::getInstance($table);
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
	 *
	 */
	public static function remove_unused_guids($opts=null) {
		$o_db = new Db();

		$tables = Datamodel::getTableNames();
		
		print CLIProgressBar::start(sizeof($tables), _t('Removing unused GUIDs'));
		foreach($tables as $table) {
			if(in_array($table, ['ca_application_vars', 'ca_guids', 'ca_change_log', 'ca_change_log_subjects', 'ca_change_log_snapshots'])) { continue; }
			if(!($t_instance = Datamodel::getInstance($table))) { continue; }
			
			print CLIProgressBar::next(1, _t('Removing unused GUIDs for table %1', $t_instance->tableName()));
			if(!ca_guids::removeUnusedGUIDs($table)) {
				CLIUtils::addError(_t("Could not remove unused GUIDs for %1.", $table));
			}
		}
		print CLIProgressBar::finish();

		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function remove_unused_guidsParamList() {
		return array(

		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function remove_unused_guidsUtilityClass() {
		return _t('Maintenance');
	}

	# -------------------------------------------------------
	/**
	 *
	 */
	public static function remove_unused_guidsShortHelp() {
		return _t('Generate missing guids');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function remove_unused_guidsHelp() {
		return _t('Generates guids for all records that don\'t have one yet. This can be useful if you plan on using the data synchronization/replication feature in the future. For more info see here: http://docs.collectiveaccess.org/wiki/Replication');
	}
	# -------------------------------------------------------
	/**
	 * @param Zend_Console_Getopt $opts
	 * @return bool
	 */
	public static function remove_duplicate_records($opts=null) {
		$tables = null;
		if ($tables = (string)$opts->getOption('tables')) {
			$tables = preg_split("![;,]+!", $tables);
		} else {
			CLIUtils::addError(_t("The -t|--tables parameter is mandatory."));
			return false;
		}

		$delete_opt = (bool)$opts->getOption('delete');

		foreach ($tables as $t) {
			if (class_exists($t) && method_exists($t, 'listPotentialDupes')) {
				$dupes = $t::listPotentialDupes();
				if (sizeof($dupes)) {
					CLIUtils::addMessage(_t('Table %1 has %2 records that have potential duplicates.', $t, sizeof($dupes)), array('color' => 'red'));


					$t_instance = Datamodel::getInstance($t);

					foreach ($dupes as $sha2 => $keys) {
						CLIUtils::addMessage("\t" . _t('%1 records have the checksum %2', sizeof($keys), $sha2));
						foreach ($keys as $key) {
							$t_instance->load($key);
							CLIUtils::addMessage("\t\t" . $t_instance->primaryKey() . ': ' . $t_instance->getPrimaryKey() . ' (' . $t_instance->getLabelForDisplay() . ')');
						}

						if ($delete_opt) {
							$entity_id = $t::mergeRecords($keys);
							if ($entity_id) {
								CLIUtils::addMessage("\t" . _t("Successfully consolidated them under id %1", $entity_id), array('color' => 'green'));
							} else {
								CLIUtils::addMessage("\t" . _t("It seems like there was an error while deduplicating those records"), array('color' => 'bold_red'));
							}
						}

					}
				} else {
					CLIUtils::addMessage(_t('Table %1 does not seem to have any duplicates!', $t), array('color' => 'bold_green'));
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function generate_new_system_guid($opts=null) {
		// generate system GUID -- used to identify systems in data sync protocol
		$o_vars = new ApplicationVars();
		$o_vars->setVar('system_guid', $guid = caGenerateGUID());
		$o_vars->save();

		CLIUtils::addMessage(_t('New system GUID is %1', $guid));
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function check_url_reference_integrity($opts=null) {
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
			'notifyUsers' => $opts->getOption('users'),
			'notifyGroups' => $opts->getOption('groups')
		]);
		return true;
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function check_metadata_alerts($opts=null) {
		ca_metadata_alert_triggers::firePeriodicTriggers();
		return true;
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function regenerate_dependent_field_values($opts=null) {
		// Find containers with dependent fields
		$elements = ca_metadata_elements::getElementSetsWithSetting("isDependentValue", 1);
	
		$c = 0;
		$num_errors = 0;
		foreach($elements as $element) {
			$t_element = ca_metadata_elements::getInstance($element['element_code']);
			$t_root = ca_metadata_elements::getInstance($element['hier_element_id']);
			$root_code = $t_root->get('element_code');
			$root_id = $t_root->get('element_id');
			
			CLIUtils::addMessage(_t('Processing %1.%2', $root_code, $element['element_code']));
			
			// get type restrictions
			$type_res_list = $t_element->getTypeRestrictions();
			foreach($type_res_list as $type_res) {
				if (!($t_instance = Datamodel::getInstanceByTableNum($type_res['table_num']))) { continue; }
				$table_name = $t_instance->tableName();
				if ($type_res['type_id'] > 0) {
					$qr_res = call_user_func("{$table_name}::find", ["type_id" => (int)$type_res['type_id']], ['returnAs' => 'searchResult']);
				} else {
					$qr_res = call_user_func("{$table_name}::find", ["*"], ['returnAs' => 'searchResult']);
				}
				
				while($qr_res->nextHit()) {
					$value_list = $qr_res->get("{$table_name}.{$root_code}", ['returnWithStructure' => true, 'convertCodesToDisplayText' => true]);
					foreach($value_list as $row_id => $values_by_attribute_id) {
						CLIUtils::addMessage(_t('Processing row %1 for %2.%3', $row_id, $root_code, $element['element_code']));
						foreach($values_by_attribute_id as $attr_id => $values) {
							$processed_value = caProcessTemplate($element['settings']['dependentValueTemplate'], $values);
							$values[$element['element_code']] = $processed_value;
							if (!$t_instance->load($row_id)) { continue; }
							$t_instance->editAttribute(
								$attr_id, $root_id, $values
							);
							$t_instance->update();
							$c++;
							if ($t_instance->numErrors() > 0) {
								CLIUtils::addError(_t("Could not update dependent value: %1", join("; ", $t_instance->getErrors())));
								$num_errors++;
							}
						}
					}
				}
			}
		}
		
		CLIUtils::addMessage(_t("Regenerated %1 values", $c));
		return ($num_errors == 0);
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function check_relationship_type_roots($opts=null) {
		$locale_id = ca_locales::getDefaultCataloguingLocaleID();	
		
		$tables = Datamodel::getTableNames();
		
		$c = 0;
		$num_errors = 0;
		foreach($tables as $table) {
			if (!preg_match('!_x_!', $table)) { continue; }
			if (!($t_table = new $table)) { continue; }
			if (!$t_table->hasField('type_id')) { continue; }
			$pk = $t_table->primaryKey();
			$table_num = $t_table->tableNum();
			
			if ($bad_roots = ca_relationship_types::find(['parent_id' => ['>', 0], 'table_num' => $table_num, 'type_code' => ['IN', ['root_for_'.$table_num, 'root_for_table_'.$table_num]]], ['returnAs' => 'modelInstances'])) {
				foreach($bad_roots as $t_bad_root) { 
					$t_bad_root->delete(true);
				}
			}	
			
			if (
				($bad_roots = ca_relationship_types::find(['parent_id' => null, 'table_num' => $table_num, 'type_code' => ['IN' , ['root_for_'.$table_num, 'root_for_table_'.$table_num]]], ['returnAs' => 'modelInstances']))
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
			if (!$t_root = ca_relationship_types::find(['parent_id' => null, 'table_num' => $table_num], ['returnAs' => 'firstModelInstance'])) {
				$t_root = new ca_relationship_types();
				$t_root->logChanges(false);
				$t_root->set('table_num', $table_num);
				$t_root->set('type_code', 'root_for_'.$table_num);
				$t_root->set('rank', 1);
				$t_root->set('is_default', 0);
				$t_root->set('parent_id', null);
				$t_root->insert();
				
				if ($t_root->numErrors()) {
					CLIUtils::addError(_t("Could not create root for relationship %1: %2", $table, join('; ', $t_root->getErrors())));
					$num_errors++;
					continue;
				}
				$t_root->addLabel(
					array(
						'typename' => 'Root for table '.$table_num,
						'typename_reverse' => 'Root for table '.$table_num
					), $locale_id, null, true
				);
				if ($t_root->numErrors()) {
					CLIUtils::addError(_t("Could not add label to root for relationship %1: %2", $table, join('; ', $t_root->getErrors())));
					$num_errors++;
					continue;
				}
				CLIUtils::addMessage(_t('Added root for %1', $table));
				$c++;
			} elseif("root_for_{$table_num}" !== $t_root->get('type_code')) {
				$t_root->logChanges(false);
				$t_root->set('type_code', 'root_for_'.$table_num);
				$t_root->update();
			}
		}
		
		CLIUtils::addMessage(($c == 1) ? _t("Recreated %1 root record", $c) : _t("Recreated %1 root records", $c));
		return ($num_errors == 0);
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
	public static function reload_current_values_for_history_tracking_policies($opts=null) {
		$tables = ca_objects::getTablesWithHistoryTrackingPolicies();
		
		if(sizeof($tables) > 0) {
			$tables[0]::clearHistoryTrackingCurrentValues();
			foreach($tables as $table) {
				$c = 0;
				$t = Datamodel::getInstance($table, true);
				$qr = $table::find('*', ['returnAs' => 'searchResult']);
				print CLIProgressBar::start($qr->numHits(), _t('Starting...'));
				
				$table_name_display = $t->getProperty('NAME_PLURAL');
				
				while($qr->nextHit()) {
					if ($t->load($qr->getPrimaryKey())) {
						print CLIProgressBar::next(1, _t('[History: %1][Mem: %2] %3', $table_name_display, caGetMemoryUsage(), $t->getWithTemplate("^{$table}.preferred_labels (^{$table}.idno)")));
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
	public static function check_future_values_for_history_tracking_policies($opts=null) {
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
	public static function reload_object_current_location_dates($opts=null) {
		$config = Configuration::load();
		$o_db = new Db();
		
		// Reload movements-objects
		if ($movement_storage_element = $config->get('movement_storage_location_date_element')) {
			$qr_movements = ca_movements::find(['deleted' => 0], ['returnAs' => 'searchResult']);
		
			print CLIProgressBar::start($qr_movements->numHits(), "Reloading movement dates");
			
			while($qr_movements->nextHit()) {
				if ($dates = $qr_movements->get("ca_movements.{$movement_storage_element}", ['returnAsArray' => true, 'rawDate' => true])) {
					$date = array_shift($dates);
					
					// get movement-object relationships
					if (is_array($rel_ids = $qr_movements->get('ca_movements_x_objects.relation_id', ['returnAsArray' => true])) && sizeof($rel_ids)) {						
						$qr_res = $o_db->query(
							"UPDATE ca_movements_x_objects SET sdatetime = ?, edatetime = ? WHERE relation_id IN (?)", 
							array($date['start'], $date['end'], $rel_ids)
						);
					}
					// get movement-location relationships
					if (is_array($rel_ids = $qr_movements->get('ca_movements_x_storage_locations.relation_id', ['returnAsArray' => true])) && sizeof($rel_ids)) {						
						$qr_res = $o_db->query(
							"UPDATE ca_movements_x_storage_locations SET sdatetime = ?, edatetime = ? WHERE relation_id IN (?)", 
							array($date['start'], $date['end'], $rel_ids)
						);
						
						// check to see if archived storage locations are set in ca_movements_x_storage_locations.source_info
						// Databases created prior to the October 2015 location tracking changes won't have this
						$qr_rels = caMakeSearchResult('ca_movements_x_storage_locations', $rel_ids);
						while($qr_rels->nextHit()) {
							if (!is_array($source_info = $qr_rels->get('source_info')) || !isset($source_info['path'])) {
								$rel_id = $qr_rels->get('ca_movements_x_storage_locations.relation_id');
								$qr_res = $o_db->query(
									"UPDATE ca_movements_x_storage_locations SET source_info = ? WHERE relation_id = ?", 
										array(caSerializeForDatabase(array(
											'path' => $qr_rels->get('ca_storage_locations.hierarchy.preferred_labels.name', array('returnAsArray' => true)),
											'ids' => $qr_rels->get('ca_storage_locations.hierarchy.location_id',  array('returnAsArray' => true))
										)), $rel_id)
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
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function set_default_field_values($opts=null) {
		// Find containers with dependent fields
		$elements = ca_metadata_elements::getElementSetsWithSetting("default_text");
		
		
		$c = 0;
		foreach($elements as $element) {
			print_R($element);
			if (!strlen($default_value = trim($element['settings']['default_text']))) { continue; }
			
			$t_element = ca_metadata_elements::getInstance($element['element_code']);
			$t_root = ca_metadata_elements::getInstance($element['hier_element_id']);
			$root_code = $t_root->get('element_code');
			$root_id = $t_root->get('element_id');
			
			$num_errors = 0;
			
			CLIUtils::addMessage(_t('Processing %1.%2', $root_code, $element['element_code']));
			
			// get type restrictions
			$type_res_list = $t_element->getTypeRestrictions();
			foreach($type_res_list as $type_res) {
				if (!($t_instance = Datamodel::getInstanceByTableNum($type_res['table_num']))) { continue; }
				$table_name = $t_instance->tableName();
				if ($type_res['type_id'] > 0) {
					$qr_res = call_user_func("{$table_name}::find", ["type_id" => (int)$type_res['type_id']], ['returnAs' => 'searchResult']);
				} else {
					$qr_res = call_user_func("{$table_name}::find", ["*"], ['returnAs' => 'searchResult']);
				}
				
				while($qr_res->nextHit()) {
					$value_list = $qr_res->get("{$table_name}.{$root_code}", ["returnWithStructure" => true]);
					foreach($value_list as $row_id => $values_by_attribute_id) {
						foreach($values_by_attribute_id as $attr_id => $values) {
							if ($values[$element['element_code']]) { continue; }
							CLIUtils::addMessage(_t('Processing row %1 for %2.%3', $row_id, $root_code, $element['element_code']));
							
							if (!$t_instance->load($row_id)) { continue; }
							
							if(!isset($values[$element['element_code']]) && ($element['element_code'] === $root_code)) {
								$t_instance->addAttribute([
									$element['element_code'] => $default_value
								], $element['element_code']);
							} else {
								$values[$element['element_code']] = $default_value;
								$t_instance->editAttribute(
									$attr_id, $root_id, $values
								);
							}
							$t_instance->update();
							$c++;
							if ($t_instance->numErrors() > 0) {
								CLIUtils::addError(_t("Could not set default value: %1", join("; ", $t_instance->getErrors())));
								$num_errors++;
							}
						}
					}
				}
			}
		}
		
		CLIUtils::addMessage(_t("Set default values on %1 records", $c));
		return ($num_errors == 0);
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
	/**
	 * @param Zend_Console_Getopt|null $opts
	 * @return bool
	 */
	public static function reload_attribute_sortable_values($opts=null) {
		$o_db = new Db();
		
		$qr_res = $o_db->query("SELECT count(*) c FROM ca_attribute_values");
		$qr_res->nextRow();
		$count = $qr_res->get('c');
		
		$last_value_id = 0;
		
		print CLIProgressBar::start($count, _t('Processing'));
		do {
			$qr_res = $o_db->query("SELECT value_id, value_longtext1, value_decimal1, value_decimal2, element_id FROM ca_attribute_values WHERE value_id > ? ORDER BY value_id LIMIT 10000", [$last_value_id]);
		
			$c = 0;
			while($qr_res->nextRow()) {
				switch($dt = ca_metadata_elements::getElementDatatype($qr_res->get('element_id'))) {
					case __CA_ATTRIBUTE_VALUE_DATERANGE__:
						$v = $qr_res->get('value_longtext1');
						if(!$v) { $v = _t('undated'); }
						break;
					default:
						$v = $qr_res->get('value_longtext1');
						if(!$v) { 
							print CLIProgressBar::next();
							continue(2); 
						}
						break;
				}
				$value_id = $qr_res->get('value_id');
				if (strlen($v) > 0) {
					$sv = ca_metadata_elements::getSortableValueForElement($qr_res->get('element_id'), $v);
					$o_db->query("UPDATE ca_attribute_values SET value_sortable = ? WHERE value_id = ?", [$sv, $value_id]);
				}
				print CLIProgressBar::next();
				$c++;
				$last_value_id = $value_id;
			}
		} while($c > 0);
		print CLIProgressBar::finish();
		
		CLIUtils::addMessage(_t("Updated sortable values"));
		
		return true;
	}
	# -------------------------------------------------------
	public static function reload_attribute_sortable_valuesParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reload_attribute_sortable_valuesUtilityClass() {
		return _t('Maintenance');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reload_attribute_sortable_valuesShortHelp() {
		return _t('Reload attribute sortable values.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reload_attribute_sortable_valuesHelp() {
		return _t('To improve sorting performance an abbreviated sortable value is stored for all text-based metadata attributes (Ex. text, URL, LCSH and InformationService elements). This command regenerates and reloads sortable values from current data, which systems created prior to version 1.7.9 will lack.');
	}
	# -------------------------------------------------------
}
