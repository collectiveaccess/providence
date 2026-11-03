<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate218.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__ . '/BaseVersionUpdater.php');

class VersionUpdate218 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 218;
	protected $messages = [];
	# -------------------------------------------------------
	/**
	 * @inheritDoc
	 *
	 * @return bool
	 */
	public function applyDatabaseUpdate($options = null) {
		$db = new Db();
		try {
			$db->query("ALTER TABLE ca_editor_uis ADD COLUMN settings longtext not null");
		} catch(Exception $e) {
			// noop
		}
		try {
			$db->query("ALTER TABLE ca_editor_ui_screens ADD COLUMN settings longtext not null");
		} catch(Exception $e) {
			// noop
		}
		return parent::applyDatabaseUpdate($options);
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		return _t("Verified presence of editor user interface settings field.");
	}
	# -------------------------------------------------------
}
