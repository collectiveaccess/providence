<?php
/** ---------------------------------------------------------------------
 * app/lib/System/Updater.php : 
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
 * @subpackage Configuration
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace System;

require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/Db/Transaction.php");
require_once(__CA_LIB_DIR__.'/GenericVersionUpdater.php');


final class Updater {
	# -------------------------------------------------------
	/**
	 *
	 * @param array $options Options include:
	 *		progressBar = output progress bar? [Default is false]
	 */
	public static function performDatabaseSchemaUpdate(?array $options=null) {
		$messages = [];
		if (($schema_revision = \ConfigurationCheck::getSchemaVersion()) < __CollectiveAccess_Schema_Rev__) {			
			
			for($i = ($schema_revision + 1); $i <= __CollectiveAccess_Schema_Rev__; $i++) {
				if (!($o_updater = self::getVersionUpdateInstance($i))) {
					$o_updater = new \GenericVersionUpdater($i);
				}
				
				$methods_that_errored = [];
				
				// pre-update tasks
				foreach($o_updater->getPreupdateTasks() as $preupdate_method) {
					if (!$o_updater->$preupdate_method()) {
						//$messages["error_{$i}_{$preupdate_method}_preupdate"] = _t("Pre-update task '{$preupdate_method}' failed");
						$methods_that_errored[] = $preupdate_method;
					}
				}
				
				if (is_array($new_messages = $o_updater->applyDatabaseUpdate())) {
					$messages = $messages + $new_messages;
				} else {
					$messages["error_{$i}_sql_fail"] = _t('Could not apply database update for migration %1', $i);
				}
				// post-update tasks
				foreach($o_updater->getPostupdateTasks() as $postupdate_method) {
					if (!$o_updater->$postupdate_method()) {
						//$messages["error_{$i}_{$postupdate_method}_postupdate"] = _t("Post-update task '{$postupdate_method}' failed");
						$methods_that_errored[] = $postupdate_method;
					}
				}
				
				if ($message = $o_updater->getPostupdateMessage()) {
					$messages[(sizeof($methods_that_errored) ? "error" : "info")."_{$i}_{$postupdate_method}_postupdate_message"] = _t("For migration %1", $i).": {$message}";
				} else {
					if (sizeof($methods_that_errored)) {
						$messages["error_{$i}_{$postupdate_method}_postupdate_message"] = _t("For migration %1", $i).": "._t("The following tasks did not complete: %1", join(', ', $methods_that_errored));
					} else {
						$messages["info_{$i}_postupdate"] = _t("Applied migration %1", $i);
					}
				}
			}
		}
		
		// Clean cache
		caRemoveDirectory(__CA_APP_DIR__.'/tmp', false);
		
		return $messages;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getVersionUpdateInstance($pn_version) {
		$classname = "VersionUpdate{$pn_version}";
		if (file_exists(__CA_BASE_DIR__."/support/sql/migrations/{$classname}.php")) {
			require_once(__CA_BASE_DIR__."/support/sql/migrations/{$classname}.php");
			return new $classname();
		}
		return null;
	}
	# -------------------------------------------------------
}
