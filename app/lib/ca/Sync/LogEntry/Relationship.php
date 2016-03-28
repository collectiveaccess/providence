<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/Relationship.php
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
 * @subpackage Sync
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\Sync\LogEntry;

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Base.php');

class Relationship extends Base {

	public function apply(array $pa_options = array()) {
		$this->setIntrinsicsFromSnapshotInModelInstance();

		if($this->isInsert()) {
			$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
		} elseif($this->isUpdate()) {
			$this->getModelInstance()->update();
		} elseif($this->isDelete()) {
			$this->getModelInstance()->delete();
		}

		$this->checkModelInstanceForErrors();
	}

	public function setIntrinsicsFromSnapshotInModelInstance() {
		parent::setIntrinsicsFromSnapshotInModelInstance();
		$va_snapshot = $this->getSnapshot();

		foreach($va_snapshot as $vs_field => $vm_val) {
			// handle ca_foo_x_bar.type_id
			if ($vs_field = $this->getModelInstance()->getProperty('RELATIONSHIP_TYPE_FIELDNAME')) {
				$vs_potential_code_field = str_replace('_id', '', $vs_field) . '_code';
				if (isset($va_snapshot[$vs_potential_code_field]) && ($vs_rel_type_code = $va_snapshot[$vs_potential_code_field])) {
					if ($vn_rel_type_id = caGetRelationshipTypeID($vs_rel_type_code)) {
						$this->getModelInstance()->set($vs_field, $vn_rel_type_id);
					} else {
						throw new LogEntryInconsistency("Could find relationship type with type code '{$vs_rel_type_code}'");
					}
				} else {
					throw new LogEntryInconsistency("No relationship type code found");
				}
			}

			// handle ca_foo_x_bar.foo_id and bar_id
			// left
			if ($vs_field == $this->getModelInstance()->getProperty('RELATIONSHIP_LEFT_FIELDNAME')) {
				$this->setLeftOrRightFieldNameFromSnapshot($vs_field, true);
			}

			// right
			if ($vs_field == $this->getModelInstance()->getProperty('RELATIONSHIP_RIGHT_FIELDNAME')) {
				$this->setLeftOrRightFieldNameFromSnapshot($vs_field, false);
			}
		}
	}

	/**
	 * Helper function that sets ca_foo_x_bar.foo_id or bar_id from the snapshot
	 * @param $ps_field
	 * @param bool $pb_left
	 * @throws LogEntryInconsistency
	 */
	private function setLeftOrRightFieldNameFromSnapshot($ps_field, $pb_left=true) {
		$vs_property = $pb_left ? 'RELATIONSHIP_LEFT_FIELDNAME' : 'RELATIONSHIP_RIGHT_FIELDNAME';
		$va_snapshot = $this->getSnapshot();

		if (isset($va_snapshot[$ps_field . '_guid']) && ($vs_reference_guid = $va_snapshot[$ps_field . '_guid'])) {
			/** @var \BundlableLabelableBaseModelWithAttributes $t_instance */
			$t_instance = $pb_left ? $this->getModelInstance()->getLeftTableInstance() : $this->getModelInstance()->getRightTableInstance();

			if ($t_instance->loadByGUID($vs_reference_guid)) {
				$this->getModelInstance()->set($ps_field, $t_instance->getPrimaryKey());
			} else {
				throw new LogEntryInconsistency("Could not load GUID {$vs_reference_guid} (referenced in {$vs_property})");
			}
		} else {
			throw new LogEntryInconsistency("No guid for {$vs_property} field found");
		}
	}

}
