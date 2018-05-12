<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/LogEntry/Attribute.php
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

require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Base.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');

class Attribute extends Base {

	public function isRelevant() {
		if((!$this->getModelInstance()->loadByGUID($this->getGUID())) && $this->isUpdate()) {
			return false;
		}

		return parent::isRelevant();
	}

	public function sanityCheck() {
		parent::sanityCheck();
		$va_snapshot = $this->getSnapshot();

		if (isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])) {
			if (!($vn_element_id = \ca_metadata_elements::getElementID($vs_element_code))) {
				throw new InvalidLogEntryException(_t("Could not find element with code '%1' on target system.", $vs_element_code));
			}
		} else {
			throw new InvalidLogEntryException(_t("We have an attribute log entry without element code."));
		}

		if($this->isInsert()) {
			if (!isset($va_snapshot['row_guid']) || !($va_snapshot['row_guid'])) {
				throw new InvalidLogEntryException(_t("Couldn't find row_guid for insert attribute log entry."));
			}
		}

		if($this->isUpdate() || $this->isDelete()) {
			if (!isset($va_snapshot['attribute_guid']) || !($va_snapshot['attribute_guid'])) {
				throw new InvalidLogEntryException(_t("Couldn't find attribute_guid for update attribute log entry."));
			}

			$vs_attribute_guid = $va_snapshot['attribute_guid'];
			if(!($va_guid_info = \ca_guids::getInfoForGUID($vs_attribute_guid))) {
				throw new InvalidLogEntryException(_t("Couldnt find ca_attributes record for guid %1.", $vs_row_guid));
			}
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

		if (isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])) {
			if ($vn_element_id = \ca_metadata_elements::getElementID($vs_element_code)) {
				$this->getModelInstance()->set('element_id', $vn_element_id);
			}
		}

		if (isset($va_snapshot['row_guid']) && ($vs_row_guid = $va_snapshot['row_guid'])) {
			if ($va_guid_info = \ca_guids::getInfoForGUID($vs_row_guid)) {
				$this->getModelInstance()->set('row_id', $va_guid_info['row_id']);
				$this->getModelInstance()->set('table_num', $va_guid_info['table_num']);
			}
		}
	}

}
