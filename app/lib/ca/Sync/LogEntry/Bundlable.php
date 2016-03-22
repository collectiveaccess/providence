<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/Bundlable.php
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

class Bundlable extends Base {

	public function apply() {
		if($this->isInsert()) {
			$this->applyInsert();
		} elseif($this->isUpdate()) {
			$this->applyUpdate();
		} elseif($this->isDelete()) {
			$this->applyDelete();
		}
	}

	private function applyInsert() {
		if($this->getModelInstance()->getPrimaryKey()) {
			throw new InvalidLogEntryException('operation is insert but model instance has primary key.');
		}

		$this->setIntrinsicsFromSnapshotInModelInstance();
		$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
		$this->checkModelInstanceForErrors();
	}

	private function applyUpdate() {
		if(!$this->getModelInstance()->getPrimaryKey()) {
			throw new InvalidLogEntryException('operation is update but model instance does not have a primary key.');
		}

		$this->setIntrinsicsFromSnapshotInModelInstance();
		$this->getModelInstance()->update();
		$this->checkModelInstanceForErrors();
	}

	private function applyDelete() {
		if(!$this->getModelInstance()->getPrimaryKey()) {
			throw new LogEntryInconsistency('operation is delete but model instance does not have a primary key.');
		}

		$this->getModelInstance()->delete();
		$this->checkModelInstanceForErrors();
	}
}
