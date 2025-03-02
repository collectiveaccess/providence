<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate189.php :
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
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__ . '/BaseVersionUpdater.php');

class VersionUpdate199 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 199;
	protected $messages = [];
	# -------------------------------------------------------

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function applyDatabaseUpdate($options = null) {
		$ret = parent::applyDatabaseUpdate($options);
		$db	 = new Db();
		$db->query("TRUNCATE TABLE ca_sql_search_word_index");
		$db->query("TRUNCATE TABLE ca_sql_search_words");
		
		return $ret;
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		return _t("The search indexing format has changed. You must reindex your system now.");
	}
	# -------------------------------------------------------
}
