<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate211.php :
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

class VersionUpdate211 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 211;
	protected $messages = [];
	
	private $err_count = 0;
	private $total = 0;
	# -------------------------------------------------------

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function applyDatabaseUpdate($options = null) {
		$ret = parent::applyDatabaseUpdate($options);
	
		// Remove old type restriction controls; add new controls
		// table_num 79 = ca_relationship_types
		$editors = ca_editor_uis::find(['editor_type' => 79], ['returnAs' => 'modelInstances']);
		if(is_array($editors)) {
			foreach($editors as $e) {
				$screens = $e->getScreens();
				foreach($screens as $screen) {
					$t_screen = null;
					
					$placements = $e->getScreenBundlePlacements($screen['screen_id']);
					if(!is_array($placements)) { continue; }
		
					foreach($placements as $placement) {
						if(in_array($placement['bundle_name'], ['sub_type_left_id', 'sub_type_right_id', 'include_subtypes_left', 'include_subtypes_right'], true)) {
							if(!$t_screen) { 
								$t_screen = new ca_editor_ui_screens($screen['screen_id']); 
								$this->total++;
								if(!$t_screen->addPlacementBefore('ca_relationship_type_restrictions', 'ca_relationship_type_restrictions', [], $placement['bundle_name'])) {
									$this->err_count++;
								}
							}
							if(!$t_screen->removePlacement($placement['placement_id'])) {
								$this->err_count++;
							}
						}
					}
				}
			}
		}	
		
		// Move existing restrictions to new table
		$rts = ca_relationship_types::find('*', ['returnAs' => 'arrays']);
		if(is_array($rts)) {
			foreach($rts as $rt) {
				if(($rt['sub_type_left_id'] > 0) || ($rt['sub_type_right_id'] > 0)) {
					$rtr = new ca_relationship_type_restrictions();
					$rtr->set([
						'type_id' => $rt['type_id'],
						'sub_type_left_id' => $rt['sub_type_left_id'],
						'sub_type_right_id' => $rt['sub_type_right_id'],
						'include_subtypes_left' => $rt['include_subtypes_left'],
						'include_subtypes_right' => $rt['include_subtypes_right']
					]);
					$rtr->insert();
				}
			}
		}
		
		return $ret;
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		if($this->err_count > 0) {
			return ($this->total == 1) ? 
				_t("Attempted to update relationship type editor, but encountered %2 errors. Editor configuration will need to be manually adjusted.", $this->total, $this->err_count)
				:
				_t("Attempted to update %1 relationship type editors, but encountered %2 errors. Editor configurations will need to be manually adjusted.", $this->total, $this->err_count);
		}
		return ($this->total == 1) ? _t("Updated relationship type editor with new type restriction controls.") : _t("Updated %1 relationship type editors with new type restriction controls.", $this->total);
	}
	# -------------------------------------------------------
}
