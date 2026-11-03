<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate217.php :
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

class VersionUpdate217 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 217;
	protected $messages = [];
	# -------------------------------------------------------

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function applyDatabaseUpdate($options = null) {
		$t_role = new ca_user_roles();
		$roles = $t_role->getRoleList();
		foreach($roles as $role_id => $r) {
			if($t_role->load($role_id)) {
				$vars = $t_role->get('vars');
				foreach(($vars['type_access_settings'] ?? []) as $t => $ri) {
					if((int)$vars['type_access_settings'][$t] === __CA_BUNDLE_ACCESS_EDIT__) {
						$vars['type_access_settings'][$t] = __CA_BUNDLE_ACCESS_EDIT_DELETE__;
					}
				}
				$t_role->set('vars', $vars);
				$t_role->update();
			}
		}
		$ret = parent::applyDatabaseUpdate($options);
		return $ret;
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		return _t("Type-based access permissions have been updated.");
	}
	# -------------------------------------------------------
}
