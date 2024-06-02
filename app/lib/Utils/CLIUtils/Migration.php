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
		
		// Reindex
		
		// Update attribute sort values
		
		// Update sortable values
		
		// DONE!
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
}

