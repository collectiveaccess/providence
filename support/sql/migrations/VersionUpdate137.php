<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate137.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

/**
 *
 */

require_once(__CA_LIB_DIR__.'/BaseVersionUpdater.php');
require_once(__CA_MODELS_DIR__ . '/ca_editor_uis.php');
require_once(__CA_MODELS_DIR__ . '/ca_editor_ui_screens.php');


class VersionUpdate137 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 137;

	# -------------------------------------------------------
	/**
	 *
	 * @return array A list of tasks to execute after performing database update
	 */
	public function getPostupdateTasks() {
		return ['addMetadataAlertsEditor'];
	}
	# -------------------------------------------------------
	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		return _t("Successfully added editor for metadata alerts");
	}
	# -------------------------------------------------------
	public function addMetadataAlertsEditor() {
		// add a default editor for metadata alerts. it's in base.xml from v1.7 but for older systems we'll need to create it
		$t_ui = new ca_editor_uis();
		$t_ui->setMode(ACCESS_WRITE);
		$t_ui->set('user_id', null);
		$t_ui->set('is_system_ui', 1);
		$t_ui->set('editor_type', 238);
		$t_ui->set('editor_code', 'metadata_alert_rule_config_ui');
		$t_ui->set('color', '000000');
		$t_ui->insert();

		if ($t_ui->numErrors()) {
			return false;
		}

		$t_ui->addLabel(
			array('name' => 'Metadata alert rule editor'), 1, null, true
		);

		if ($t_ui->numErrors()) {
			return false;
		}

		$vn_ui_id = $t_ui->getPrimaryKey();

		$t_screen = new ca_editor_ui_screens();
		$t_screen->setMode(ACCESS_WRITE);
		$t_screen->set('ui_id', $vn_ui_id);
		$t_screen->set('idno', 'basic');
		$t_screen->set('rank', 1);
		$t_screen->set('is_default', 1);
		$t_screen->insert();

		if ($t_screen->numErrors()) {
			return false;
		}

		$t_screen->addLabel(
			array('name' => 'Basic'), 1, null, true
		);

		if ($t_screen->numErrors()) {
			return false;
		}

		// add bundles
		$vn_i = 1;

		$t_screen->addPlacement('preferred_labels', 'preferred_labels', array(
			'label' => [1 => 'Metadata alert rule name'],
			'add_label' => [1 => 'Add name']
		), $vn_i);
		$vn_i++;

		foreach(array('code', 'table_num', 'ca_metadata_alert_rule_type_restrictions') as $vs_bundle_name) {
			$t_screen->addPlacement($vs_bundle_name, $vs_bundle_name, array(), $vn_i);
			$vn_i++;
		}

		$t_screen->addPlacement('ca_users', 'ca_users', array(
			'label' => [1 => 'Recipient users'],
			'add_label' => [1 => 'Add user']
		), $vn_i);
		$vn_i++;

		$t_screen->addPlacement('ca_user_groups', 'ca_user_groups', array(
			'label' => [1 => 'Recipient user groups'],
			'add_label' => [1 => 'Add group']
		), $vn_i);
		$vn_i++;

		$t_screen->addPlacement('ca_metadata_alert_triggers', 'ca_metadata_alert_triggers', array(), $vn_i);

		return true;
	}
	# -------------------------------------------------------
}
