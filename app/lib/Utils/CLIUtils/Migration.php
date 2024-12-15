<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Migration.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/System/Updater.php");

trait CLIUtilsMigration { 
	# -------------------------------------------------------
	/**
	 * Process queued tasks
	 */
	public static function update_from_1_7($po_opts=null) {
		// Check version
		if (($current_revision = ConfigurationCheck::getSchemaVersion()) >= __CollectiveAccess_Schema_Rev__) {
			CLIUtils::addMessage(_t("Database already at current revision (%1). No update is required.", $current_revision));
			return true;
		}
		
		if(!CLIUtils::confirm(_t("Are you sure you want to update your version 1.7 system (database revision %1)?\nNOTE: you MUST backup your database before applying this update!\n\nType 'y' to proceed or 'n' to cancel, then hit return", $current_revision),['confirmationCode' => 'y', 'color' => 'yellow'])) {
			return false;
		}
		
		$num_steps = 8;
		// Clear all caches
		$c = 1;
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Clearing caches", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::clear_caches();
		$c++;
		
		// Truncate existing search index
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Removing old search index", $c, $num_steps), ['color' => 'yellow']);
		$db = new Db();
		$db->query("TRUNCATE TABLE ca_sql_search_word_index");
		$db->query("TRUNCATE TABLE ca_sql_search_words");
		$c++;
		
		// Apply database updates
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Updating database schema", $c, $num_steps), ['color' => 'yellow']);
		self::update_database_schema(null, ['progressBar' => true, 'dontConfirm' => true]);
		$c++;
		
		// Clear all caches
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Clearing caches", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::clear_caches();
		$c++;
		
		// Update history tracking values
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Updating current history tracking values", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::reload_current_values_for_history_tracking_policies();
		$c++;
		
		// Update attribute sort values
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Updating attribute sort values", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::reload_attribute_sortable_values();
		$c++;
		
		// Update sortable values
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Updating label and identifier sort values", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::rebuild_sort_values();
		$c++;
		
		// Reindex
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("[Step %1/%2] Rebuilding search index", $c, $num_steps), ['color' => 'yellow']);
		CLIUtils::rebuild_search_index();
		$c++;
		
		// DONE!
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		CLIUtils::addMessage(_t("Update complete!", ['color' => 'green']));
		CLIUtils::addMessage(_t("\n\n------------------------------------------------------------------------------"));
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7ParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7UtilityClass() {
		return _t('Migration');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7ShortHelp() {
		return _t("Migrate a version 1.7.x system to version 2.0");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7Help() {
		return _t("Performs a multi-step process to update a 1.7.x database for use with CollectiveAccess version 2.0, including update of database schema, regeneration of sortable values and full rebuild of the search index. For larger systems this process may take significant time, up to several hours. The migration process is generally safe, and will not damage systems that are already updated. However it is strongly recommended that a full backup of the database be made before running this update.");
	}
	# -------------------------------------------------------
	/**
	 * Update database schema
	 */
	public static function update_database_schema($po_opts=null, ?array $options=null) {
		$config_check = new ConfigurationCheck();
		if (($current_revision = ConfigurationCheck::getSchemaVersion()) <= __CollectiveAccess_Schema_Rev__) {
			if(!caGetOption('dontConfirm', $options, false) && !CLIUtils::confirm(_t("Are you sure you want to update your CollectiveAccess database from revision %1 to %2?\nNOTE: you MUST backup your database before applying updates!\n\nType 'y' to proceed or 'n' to cancel, then hit return ", $current_revision + 1, __CollectiveAccess_Schema_Rev__), ['confirmationCode' => 'y', 'color' => 'yellow'])) {
				return false;
			}
			
			$messages = \System\Updater::performDatabaseSchemaUpdate();

			print CLIProgressBar::start(sizeof($messages), _t('Updating database'));
			foreach($messages as $message) {
				print CLIProgressBar::next(1, $message);
			}
			print CLIProgressBar::finish();
		} else {
			print CLIProgressBar::finish();
			CLIUtils::addMessage(_t("Database already at revision %1. No update is required.", __CollectiveAccess_Schema_Rev__), ['color' => 'red']);
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
		return _t('Migration');
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
}
