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
trait CLIUtilsMigration { 
	# -------------------------------------------------------
	/**
	 * Process queued tasks
	 */
	public static function update_from_1_7($po_opts=null) {
		// Check version
		
		// Apply database updates
		self::update_database_schema(['progressBar' => true]);
		
		// Update history tracking values
		CLIUtils::reload_current_values_for_history_tracking_policies();
		
		// Update attribute sort values
		CLIUtils::reload_attribute_sortable_values();
		
		// Update sortable values
		CLIUtils::rebuild_sort_values();
		
		// Reindex
		CLIUtils::rebuild_search_index();
		
		// DONE!
		return true
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7ParamList() {
		return array(
			"xxx|r" => _t("xxx"),

		);
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
		return _t("Migration a version 1.7.x system to 2.0.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_from_1_7Help() {
		return _t("Help text to come.");
	}
	# -------------------------------------------------------
	/**
	 * Update database schema
	 */
	public static function update_database_schema($po_opts=null) {
		require_once(__CA_LIB_DIR__."/system/Updater.php");
		$config_check = new ConfigurationCheck();
		if (($current_revision = ConfigurationCheck::getSchemaVersion()) <= __CollectiveAccess_Schema_Rev__) {
			CLIUtils::addMessage(_t("Are you sure you want to update your CollectiveAccess database from revision %1 to %2?\nNOTE: you should backup your database before applying updates!\n\nType 'y' to proceed or 'N' to cancel, then hit return ", $current_revision, __CollectiveAccess_Schema_Rev__));
			flush();
			ob_flush();
			$confirmation  =  trim( fgets( STDIN ) );
			if ( $confirmation !== 'y' ) {
				// The user did not say 'y'.
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

