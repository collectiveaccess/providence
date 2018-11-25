<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/ImportExport.php : 
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
 
	trait CLIUtilsImportExport { 
		# -------------------------------------------------------
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

			$vb_direct = (bool)$po_opts->getOption('direct');
			$vb_no_search_indexing = (bool)$po_opts->getOption('no-search-indexing');
			$vb_use_temp_directory_for_logs_as_fallback = (bool)$po_opts->getOption('log-to-tmp-directory-as-fallback'); 

			$vs_format = $po_opts->getOption('format');
			$vs_log_dir = $po_opts->getOption('log');
			$vn_log_level = CLIUtils::getLogLevel($po_opts);

			if ($vb_no_search_indexing) {
				define("__CA_DONT_DO_SEARCH_INDEXING__", true);
			}

			if (!ca_data_importers::importDataFromSource($vs_data_source, $vs_mapping, array('noTransaction' => $vb_direct, 'format' => $vs_format, 'showCLIProgressBar' => true, 'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level, 'logToTempDirectoryIfLogDirectoryIsNotWritable' => $vb_use_temp_directory_for_logs_as_fallback, 'addToSet' => $vs_add_to_set))) {
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
    }
