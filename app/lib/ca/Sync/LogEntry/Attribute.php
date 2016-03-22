<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/Attribute.php
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
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');

class Attribute extends Base {

	public function apply() {
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

			if($vs_field == 'element_id') {
				if (isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])) {
					if ($vn_element_id = \ca_metadata_elements::getElementID($vs_element_code)) {
						$this->getModelInstance()->set('element_id', $vn_element_id);
					} else {
						throw new LogEntryInconsistency("Could find element with code '{$vs_element_code}'");
					}
				} else {
					throw new LogEntryInconsistency("No element code");
				}
			} elseif($vs_field == 'row_id') {
				if (isset($va_snapshot['row_guid']) && ($vs_row_guid = $va_snapshot['row_guid'])) {
					if($va_guid_info = \ca_guids::getInfoForGUID($vs_row_guid)) {
						$this->getModelInstance()->set('row_id', $va_guid_info['row_id']);
						$this->getModelInstance()->set('table_num', $va_guid_info['table_num']);
					} else {
						throw new LogEntryInconsistency("Couldnt find record for guid $vs_row_guid");
					}
				} else {
					throw new LogEntryInconsistency("No table_num/row_guid in snapshot but row id is set");
				}
			}
		}
	}

}
