<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/CLIUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__.'/core/Utils/CLIProgressBar.php');
	require_once(__CA_LIB_DIR__."/core/Zend/Console/Getopt.php");
 
	class CLIUtils {
		# -------------------------------------------------------
		/**
		 * ANSI foreground colors
		 */ 
		 private static $ansiForegroundColors = array(
			'black' => '0;30',
			'dark_gray' => '1;30',
			'red' => '0;31',
			'bold_red' => '1;31',
			'green' => '0;32',
			'bold_green' => '1;32',
			'brown' => '0;33',
			'yellow' => '1;33',
			'blue' => '0;34',
			'bold_blue' => '1;34',
			'purple' => '0;35',
			'bold_purple' => '1;35',
			'cyan' => '0;36',
			'bold_cyan' => '1;36',
			'white' => '1;37',
			'bold_gray' => '0;37',
		);

		/**
		 * ANSI background colors
		 */ 
		private static $ansiBackgroundColors = array(
			'black' => '40',
			'red' => '41',
			'magenta' => '45',
			'yellow' => '43',
			'green' => '42',
			'blue' => '44',
			'cyan' => '46',
			'light_gray' => '47',
		);
		# -------------------------------------------------------
		/**
		 * Determines if CLIUtils function should be presented as a command within caUtils
		 *
		 * @param string $ps_function_name The function to check
		 * @return bool True if the function is a caUtils command
		 */
		public static function isCommand($ps_function_name) {
			return (!in_array($ps_function_name, array('isCommand', 'textWithColor', 'textWithBackgroundColor')));
		}
		# -------------------------------------------------------
		/**
		 * Return text in ANSI color
		 *
		 * @param string $ps_string The string to output
		 * @param string $ps_color The color to output $ps_string in. Colors are defined in CLIUtils::ansiForegroundColors
		 * @return string The string with ANSI color codes. If $ps_color is invalid the original string will be returned without ANSI codes.
		 */
		public static function textWithColor($ps_string, $ps_color) {
			if (!isset(self::$ansiForegroundColors[$ps_color])) {
				return $ps_string;
			}
			return "\033[".self::$ansiForegroundColors[$ps_color]."m".$ps_string."\033[0m";
		}
		# -------------------------------------------------------
		/**
		 * Return text in ANSI color
		 *
		 * @param string $ps_string The string to output
		 * @param string $ps_color The background color to output $ps_string with. Colors are defined in CLIUtils::ansiBackgroundColors
		 * @return string The string with ANSI color codes. If $ps_color is invalid the original string will be returned without ANSI codes.
		 */
		public static function textWithBackgroundColor($ps_string, $ps_color) {
			if (!isset(self::$background[$color])) {
				return $ps_string;
			}

			return "\033[".self::$ansiBackgroundColors[$ps_color].'m'.$ps_string."\033[0m";
		}
		# -------------------------------------------------------
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_search_index() {
			require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
			ini_set('memory_limit', '4000m');
			set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
			
			$o_si = new SearchIndexer();
			$o_si->reindex(null, array('showProgress' => true, 'interactiveProgressDisplay' => true));
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexParamList() {
			return array(
			
			);
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
						$t_table->setMode(ACCESS_WRITE);
						$t_table->update();
					}
				}
				print CLIProgressBar::finish();
			}
			return trie;
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
			require_once(__CA_LIB_DIR__."/core/Db.php");
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
			$va_contents = caGetDirectoryContentsAsList(__CA_BASE_DIR__.'/media', true, false);
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
					if ($vb_delete) {
						unlink($vs_path);
					}
					$va_report[] = $vs_path;
				}
			}
			print CLIProgressBar::finish();
			
			print "\n"._t('There are %1 files total', sizeof($va_contents))."\n";
			$vs_percent = sprintf("%2.1f", ($vn_delete_count/sizeof($va_contents)) * 100)."%";
			
			if ($vn_delete_count == 1) {
				print ($vb_delete_opt ? _t("%1 file (%2) was deleted", $vn_delete_count, $vs_percent) : _t("%1 file (%2) is unused", $vn_delete_count, $vs_percent))."\n";
			} else {
				print ($vb_delete_opt ?  _t("%1 files (%2) were deleted", $vn_delete_count, $vs_percent) : _t("%1 files (%2) are unused", $vn_delete_count, $vs_percent))."\n";
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
		 * Export current system configuration as an XML installation profile
		 */
		public static function export_profile($po_opts=null) {
			require_once(__CA_LIB_DIR__."/ca/ConfigurationExporter.php");
	
			if(!class_exists("DOMDocument")){
				print _t("Sorry, the PHP DOM extension is required to export profiles.")."\n";
				return;
			}

			$vs_output = $po_opts->getOption("output");
			$va_output = explode("/", $vs_output);
			array_pop($va_output);
			if ($vs_output && (!is_dir(join("/", $va_output)))) {
				print _t("Sorry, cannot write profile to %1.", $vs_output)."\n";
				return;
			}
			
			$vs_profile = ConfigurationExporter::exportConfigurationAsXML($po_opts->getOption("name"), $po_opts->getOption("description"), $po_opts->getOption("base"), $po_opts->getOption("infoURL"));
			
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
				"output|o-s" => _t('File to output profile to. If omitted profile is printed to standard output. (Optional)')
			);
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
			require_once(__CA_LIB_DIR__."/core/TaskQueue.php");
	
			$vo_tq = new TaskQueue();
			
			if (!$po_opts->getOption("quiet")) { print _t("Processing queued tasks...")."\n"; }
			$vo_tq->processQueue();		// Process queued tasks
			
			if (!$po_opts->getOption("quiet")) { print _t("Processing recurring tasks...")."\n"; }
			$vo_tq->runPeriodicTasks();	// Process recurring tasks implemented in plugins
			if (!$po_opts->getOption("quiet")) { print _t("Processing complete.")."\n"; }
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueParamList() {
			return array(
				"quiet|q" => _t("Run without outputting progress information.")
			);
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
			define('__CollectiveAccess_IS_REPROCESSING_MEDIA__', 1);
			require_once(__CA_LIB_DIR__."/core/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
	
			$o_db = new Db();
	
			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);
	
			$va_mimetypes = explode(",", $po_opts->getOption("mimetypes"));
			$va_versions = explode(",", $po_opts->getOption("versions"));
	
			$qr_reps = $o_db->query("SELECT * FROM ca_object_representations ORDER BY representation_id");
			
			print CLIProgressBar::start($qr_reps->numRows(), _t('Re-processing media'));
			while($qr_reps->nextRow()) {
				$va_media_info = $qr_reps->getMediaInfo('media');
				$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];
				
				print CLIProgressBar::next(1, _t("Re-processing %1", ($vs_original_filename ? $vs_original_filename : $qr_reps->get('representation_id'))));
		
				$vs_mimetype = $qr_reps->getMediaInfo('media', 'original', 'MIMETYPE');
				if(sizeof($va_mimetypes)) {
					foreach($va_mimetypes as $vs_mimetype_pattern) {
						if(!preg_match("!^{$vs_mimetype_pattern}!", $vs_mimetype)) {
							continue(2);
						}
					}
				}
				
				$t_rep->load($qr_reps->get('representation_id'));
				$t_rep->set('media', $p =$qr_reps->getMediaPath('media', 'original'), array('original_filename' => $vs_original_filename));

				if (sizeof($va_versions)) {
					$t_rep->update(array('updateOnlyMediaVersions' =>$va_versions));
				} else {
					$t_rep->update();
				}
		
				if ($t_rep->numErrors()) {
					print CLIUtils::textWithColor(_t("[ERROR] Error processing media: %1", join('; ', $t_rep->getErrors())), "red")."\n";
				}
			}
			print CLIProgressBar::finish();
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaParamList() {
			return array(
				"mimetypes|m-s" => _t("Limit re-processing to specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
				"versions|v-s" => _t("Limit re-processing to specified versions. Separate multiple versions with commas.")
			);
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
			return _t("CollectiveAccess generates derivatives for all uploaded media. More here...");
		}
		# -------------------------------------------------------
		/**
		 * Update database schema
		 */
		public static function update_database_schema($po_opts=null) {
			require_once(__CA_LIB_DIR__."/ca/ConfigurationCheck.php");
	
			$o_config_check = new ConfigurationCheck();
			if (($vn_current_revision = ConfigurationCheck::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {
				print _t("Are you sure you want to update your CollectiveAccess database from revision %1 to %2?\nNOTE: you should backup your database before applying updates!\n\nType 'y' to proceed or 'N' to cancel, then hit return ", $vn_current_revision, __CollectiveAccess_Schema_Rev__)."\n";
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
				print _t("Database already at revision %1. No update is required.", __CollectiveAccess_Schema_Rev__)."\n";
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
		public static function update_database_schemaShortHelp() {
			return _t("Update database schema to the current version.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function update_database_schemaHelp() {
			return _t("Updates database schema to current version. More here...");
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public static function load_import_mapping($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_importers.php");
	
			if (!($vs_file_path = $po_opts->getOption('file'))) {
				print _t("You must specify a file!")."\n";
				return false;
			}
			if (!file_exists($vs_file_path)) {
				print _t("File '%1' does not exist!", $vs_file_path)."\n";
				return false;
			}
			
			if (!($t_importer = ca_data_importers::loadImporterFromFile($vs_file_path))) {
				print _t("Could not import '%1'", $vs_file_path)."\n";
				return false;
			} else {
				print _t("Created mapping %1 from %2", CLIUtils::textWithColor($t_importer->get('importer_code'), 'yellow'), $vs_file_path)."\n";
				return true;
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function load_import_mappingParamList() {
			return array(
				"file|f=s" => _t('Excel XLSX file to load.')
			);
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
			return _t("Loads import mapping from Excel XLSX format file. More here...");
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public static function import_data($po_opts=null) {
			require_once(__CA_MODELS_DIR__."/ca_data_importers.php");
	
			if (!($vs_data_source = $po_opts->getOption('source'))) {
				print _t('You must specify a data source for import')."\n";
				return false;
			}
			if (!$vs_data_source) {
				print _t('You must specify a source')."\n"."\n";
				return false;
			}
			if (!($vs_mapping = $po_opts->getOption('mapping'))) {
				print _t('You must specify a mapping')."\n";
				return false;
			}
			if (!(ca_data_importers::mappingExists($vs_mapping))) {
				print _t('Mapping %1 does not exist', $vs_mapping)."\n";
				return false;
			}
			
			$vs_format = $po_opts->getOption('format');
			
			if (!ca_data_importers::importDataFromSource($vs_data_source, $vs_mapping, array('format' => $vs_format, 'showCLIProgressBar' => true))) {
				print _t("Could not import source %1", $vs_data_source)."\n";
				return false;
			} else {
				print _t("Imported data from source %1", $vs_data_source)."\n";
				return true;
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataParamList() {
			return array(
				"source|s=s" => _t('Data to import. For files provide the path; for database, OAI and other non-file sources provide a URL.'),
				"mapping|m=s" => _t('Mapping to import data with.'),
				"format|f-s" => _t('The format of the data to import. (Ex. XLSX, tab, CSV, mysql, OAI, Filemaker XML, ExcelXML, MARC). If omitted an attempt will be made to automatically identify the data format.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataShortHelp() {
			return _t("Import data from an Excel XLSX, tab or comma delimited text or XML file.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function import_dataHelp() {
			return _t("Import data from an Excel XLSX, tab or comma delimited text or XML file. More here...");
		}
		# -------------------------------------------------------
	}
?>