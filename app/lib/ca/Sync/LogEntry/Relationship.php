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

use Hoa\Core\Exception\Exception;

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Base.php');

class Relationship extends Base {

	public function sanityCheck() {
		parent::sanityCheck();
		$va_snapshot = $this->getSnapshot();

		// check if type_code field is present (if needed, ca_objects_x_object_representatiosn is type-less for instance)
		if ($vs_type_field = $this->getModelInstance()->getProperty('RELATIONSHIP_TYPE_FIELDNAME')) {
			$vs_potential_code_field = str_replace('_id', '', $vs_type_field) . '_code';

			if (isset($va_snapshot[$vs_potential_code_field]) && ($vs_rel_type_code = $va_snapshot[$vs_potential_code_field])) {
				if (!($vn_rel_type_id = caGetRelationshipTypeID($this->getModelInstance()->tableNum(), $vs_rel_type_code))) {
					throw new InvalidLogEntryException(_t("Couldn't find relationship type with type code '%1'.", $vs_rel_type_code));
				}
			} else {
				if($this->isInsert()) {
					throw new InvalidLogEntryException(_t("No relationship type code found in relationship log entry."));
				}
			}
		}

		// check if left and right guid fields are present
		// left
		if ($this->isInsert() && ($vs_field = $this->getModelInstance()->getProperty('RELATIONSHIP_LEFT_FIELDNAME'))) {
			$this->verifyLeftOrRightFieldNameFromSnapshot($vs_field, true);
		}

		// right
		if ($vs_field = $this->getModelInstance()->getProperty('RELATIONSHIP_RIGHT_FIELDNAME')) {
			$this->verifyLeftOrRightFieldNameFromSnapshot($vs_field, false);
		}
	}

	public function apply(array $pa_options = array()) {
		$this->setIntrinsicsFromSnapshotInModelInstance();

		if($this->isInsert()) {
			$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
		} elseif($this->isUpdate()) {
			$this->getModelInstance()->update();
		} elseif($this->isDelete()) {
			$this->getModelInstance()->delete(false);
		}

		$this->checkModelInstanceForErrors();
	}

	public function setIntrinsicsFromSnapshotInModelInstance() {
		parent::setIntrinsicsFromSnapshotInModelInstance();
		$va_snapshot = $this->getSnapshot();

		if ($vs_type_field = $this->getModelInstance()->getProperty('RELATIONSHIP_TYPE_FIELDNAME')) {
			$vs_potential_code_field = str_replace('_id', '', $vs_type_field) . '_code';
			if (isset($va_snapshot[$vs_potential_code_field]) && ($vs_rel_type_code = $va_snapshot[$vs_potential_code_field])) {
				if ($vn_rel_type_id = caGetRelationshipTypeID($this->getModelInstance()->tableNum(), $vs_rel_type_code)) {
					$this->getModelInstance()->set($vs_type_field, $vn_rel_type_id);
				}
			}
		}

		// handle ca_foo_x_bar.foo_id and bar_id
		// left
		if ($vs_left_field = $this->getModelInstance()->getProperty('RELATIONSHIP_LEFT_FIELDNAME')) {
			$this->setLeftOrRightFieldNameFromSnapshot($vs_left_field, true);
		}

		// right
		if ($vs_right_field = $this->getModelInstance()->getProperty('RELATIONSHIP_RIGHT_FIELDNAME')) {
			$this->setLeftOrRightFieldNameFromSnapshot($vs_right_field, false);
		}
	}

	/**
	 * Helper function that sets ca_foo_x_bar.foo_id or bar_id from the snapshot
	 * @param $ps_field
	 * @param bool $pb_left
	 * @throws InvalidLogEntryException
	 */
	private function setLeftOrRightFieldNameFromSnapshot($ps_field, $pb_left=true) {
		$va_snapshot = $this->getSnapshot();
		if (isset($va_snapshot[$ps_field . '_guid']) && ($vs_reference_guid = $va_snapshot[$ps_field . '_guid'])) {
			/** @var \BundlableLabelableBaseModelWithAttributes $t_instance */
			$t_instance = $pb_left ? $this->getModelInstance()->getLeftTableInstance() : $this->getModelInstance()->getRightTableInstance();
			if(!$t_instance) { throw new InvalidLogEntryException('Could not get left or right table instance for relationship log entry'); }
			$t_instance->setTransaction($this->getTx());

			if ($t_instance->loadByGUID($vs_reference_guid)) {
				$this->getModelInstance()->set($ps_field, $t_instance->getPrimaryKey());
			} else {
				throw new InvalidLogEntryException('Could not find related record');
			}
		}
	}

	/**
	 * Helper function that varifies ca_foo_x_bar.foo_id or bar_id in snapshot
	 * @param $ps_field
	 * @param bool $pb_left
	 * @throws InvalidLogEntryException
	 */
	private function verifyLeftOrRightFieldNameFromSnapshot($ps_field, $pb_left=true) {
		$vs_property = $pb_left ? 'RELATIONSHIP_LEFT_FIELDNAME' : 'RELATIONSHIP_RIGHT_FIELDNAME';
		$va_snapshot = $this->getSnapshot();
		$o_dm = \Datamodel::load();

		if (isset($va_snapshot[$ps_field . '_guid']) && ($vs_reference_guid = $va_snapshot[$ps_field . '_guid'])) {
			/** @var \BaseRelationshipModel $t_instance */
			if($pb_left) {
				$t_instance = $o_dm->getInstanceByTableName($this->getModelInstance()->getLeftTableName(), true);
			} else {
				$t_instance = $o_dm->getInstanceByTableName($this->getModelInstance()->getRightTableName(), true);
			}

			$t_instance->setTransaction($this->getTx());
			if (!method_exists($t_instance, "loadByGUID") || !$t_instance->loadByGUID($vs_reference_guid)) {
				// TODO: confirm irrelevant is the way to go here
				throw new IrrelevantLogEntry("Could not load {$t_instance->tableName()} record by GUID {$vs_reference_guid} (referenced in {$vs_property} in a relationship record)");
			}
		} else {
			if($this->isInsert()) {
				// TODO: confirm irrelevant is the way to go here
				throw new IrrelevantLogEntry("No guid for {$vs_property} field found");
			}
		}
	}
}
