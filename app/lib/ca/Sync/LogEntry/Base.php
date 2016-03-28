<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/Base.php
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

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Attribute.php');
require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Bundlable.php');
require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Relationship.php');
require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Label.php');


abstract class Base {

	/**
	 * The log entry from the remote/source system
	 * @var array
	 */
	private $opa_log;

	/**
	 * The system id of the remote/source system
	 * @var string
	 */
	private $ops_source_system_id;

	/**
	 * Log id (on the remote/source system) this object represents
	 * @var int
	 */
	private $opn_log_id;

	/**
	 * @var \Datamodel
	 */
	private $opo_datamodel;

	/**
	 * @var \BaseModel
	 */
	private $opt_instance;

	/**
	 * @var \Transaction
	 */
	private $opo_tx;

	/**
	 * Base constructor.
	 * @param string $ops_source_system_id
	 * @param int $opn_log_id
	 * @param array $opa_log
	 * @param \Transaction $po_tx
	 * @throws InvalidLogEntryException
	 * @throws IrrelevantLogEntry
	 */
	public function __construct($ops_source_system_id, $opn_log_id, array $opa_log, \Transaction $po_tx) {
		if(!is_array($opa_log)) {
			throw new InvalidLogEntryException('Log entry must be array');
		}

		if(!isset($opa_log['log_id'])) {
			throw new InvalidLogEntryException('Log entry does not have an id');
		}

		if(!is_numeric($opn_log_id)) {
			throw new InvalidLogEntryException('Log id is not numeric');
		}
		;
		if(strlen($ops_source_system_id) != 36) {
			throw new InvalidLogEntryException('source system GUID is not a valid guid. String length was: '. strlen($ops_source_system_id));
		}

		$this->opa_log = $opa_log;
		$this->ops_source_system_id = $ops_source_system_id;
		$this->opn_log_id = $opn_log_id;
		$this->opo_tx = $po_tx;

		$this->opo_datamodel = \Datamodel::load();

		$this->opt_instance = $this->getDatamodel()->getInstance($this->getTableNum());

		if(!($this->opt_instance instanceof \BaseModel)) {
			throw new InvalidLogEntryException('table num is invalid for this log entry');
		}

		// if this is not an insert log entry, load the specified row by GUID
		if($this->isUpdate() || $this->isDelete()) {
			// if we can't find the GUID and this is a delete() and this is an update, throw error
			// if we can't find it and this is a delete, we don't particularly care. yes, we can't delete a non-existing
			// record, but in terms of sync, that's a non-critical error.
			if((!$this->getModelInstance()->loadByGUID($this->getGUID())) && $this->isUpdate()) {
				throw new InvalidLogEntryException('mode was update but the given GUID could not be found');
			}
		}

		$this->opt_instance->setTransaction($this->getTx());
		$this->opt_instance->setMode(ACCESS_WRITE);

		if(!$this->isRelevant()) {
			throw new IrrelevantLogEntry();
		}
	}

	/**
	 * Applies this log entry to the local system.
	 * We don't dicate *how* implementations do this. It's advised to not cram all code into
	 * this function though. Breaking it out by insert/update/delete probably makes sense
	 *
	 * @param array $pa_options
	 * @return mixed
	 */
	abstract public function apply(array $pa_options = array());

	/**
	 * Can be used to weed out irrelevant log entries, for instance changes in configuration tables like
	 * ca_editor_uis and ca_editor_ui_screens. These should (arguably) be filtered on the source side too
	 * but it doesn't hurt to check here.
	 *
	 * Implementations can override this if they want to add/change logic, obviously.
	 *
	 * @return bool
	 */
	public function isRelevant() {
		$vs_t = $this->getModelInstance()->tableName();
		if(preg_match("/^ca\_editor\_ui/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_metadata\_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_relationship\_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_search\_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_bundle\_display/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_data_import/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca\_data_exporter/", $vs_t)) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getLog() {
		return $this->opa_log;
	}

	/**
	 * @return string
	 */
	public function getSourceSystemID() {
		return $this->ops_source_system_id;
	}

	/**
	 * @return int
	 */
	public function getLogId() {
		return $this->opn_log_id;
	}

	/**
	 * @return \Transaction
	 */
	public function getTx() {
		return $this->opo_tx;
	}

	/**
	 * Get record snapshot from log entry
	 * @return array|null
	 */
	public function getSnapshot() {
		if(isset($this->opa_log['snapshot']) && is_array($this->opa_log['snapshot'])) {
			return $this->opa_log['snapshot'];
		}

		return null;
	}

	/**
	 * Get a specific entry (e.g. 'type_id') from the log snapshot
	 * @param string $ps_entry the field name
	 * @return mixed|null
	 */
	public function getSnapshotEntry($ps_entry) {
		if($va_snapshot = $this->getSnapshot()) {
			if(isset($va_snapshot[$ps_entry])) {
				return $ps_entry;
			}
		}

		return null;
	}

	/**
	 * @return null|int
	 */
	public function getTableNum() {
		return isset($this->opa_log['logged_table_num']) ? $this->opa_log['logged_table_num'] : null;
	}

	/**
	 * @return null|int
	 */
	public function getRowID() {
		return isset($this->opa_log['logged_row_id']) ? $this->opa_log['logged_row_id'] : null;
	}

	/**
	 * @return null|array
	 */
	public function getSubjects() {
		return isset($this->opa_log['subjects']) ? $this->opa_log['subjects'] : null;
	}

	/**
	 * @return null|string
	 */
	public function getGUID() {
		return isset($this->opa_log['guid']) ? $this->opa_log['guid'] : null;
	}

	/**
	 * @return bool
	 */
	public function isInsert() {
		return (isset($this->opa_log['changetype']) && ($this->opa_log['changetype'] == 'I'));
	}

	/**
	 * @return bool
	 */
	public function isUpdate() {
		return (isset($this->opa_log['changetype']) && ($this->opa_log['changetype'] == 'U'));
	}

	/**
	 * @return bool
	 */
	public function isDelete() {
		return (isset($this->opa_log['changetype']) && ($this->opa_log['changetype'] == 'D'));
	}

	/**
	 * Get Datamodel
	 * @return \Datamodel
	 */
	public function getDatamodel() {
		return $this->opo_datamodel;
	}

	/**
	 * Get model instance for row referenced in change log entry
	 * @return \BaseModel|null
	 */
	public function getModelInstance() {
		return $this->opt_instance;
	}

	/**
	 * Set intrinsic fields from snapshot in given model instance
	 */
	public function setIntrinsicsFromSnapshotInModelInstance() {
		$va_snapshot = $this->getSnapshot();

		foreach($va_snapshot as $vs_field => $vm_val) {
			// skip non existing "fake" fields
			if(!$this->getModelInstance()->hasField($vs_field)) { continue; }

			// skip primary key
			if($this->getModelInstance()->primaryKey() == $vs_field) { continue; }

			// don't try to build hierarchy indexes by hand
			if($vs_field == $this->getModelInstance()->getProperty('HIERARCHY_LEFT_INDEX_FLD')) { continue; }
			if($vs_field == $this->getModelInstance()->getProperty('HIERARCHY_RIGHT_INDEX_FLD')) { continue; }

			// only do something if this is a valid field
			if($va_fld_info = $this->getModelInstance()->getFieldInfo($vs_field)) {

				// handle list reference fields, like status, access, item_status_id, or even type_id
				// in the source log, there should be fields like "type_code" or "access_code" that have
				// the codes of the list items we're looking for (corresponding to "type_id" and "access")
				// we assume they're the same in this system and try to set() them if they exist.
				$vs_potential_code_field = str_replace('_id', '', $vs_field) . '_code';
				if(isset($va_fld_info['LIST']) || isset($va_fld_info['LIST_CODE'])) {
					if(isset($va_snapshot[$vs_potential_code_field])) {
						$vs_code = $va_snapshot[$vs_potential_code_field];
						// already established one of them is set, a few lines above
						$vs_list = isset($va_fld_info['LIST']) ? $va_fld_info['LIST'] : $va_fld_info['LIST_CODE'];

						if($vn_item_id = caGetListItemID($vs_list, $vs_code)) {
							if(isset($va_fld_info['LIST'])) { // access, status -> set item value (0,1 etc.)
								$this->getModelInstance()->set($vs_field, caGetListItemValueForID($vn_item_id));
							} else { // type_id, source_id, etc. ...
								$this->getModelInstance()->set($vs_field, $vn_item_id);
							}
						} else {
							throw new InvalidLogEntryException(
								"Couldn't find list item id for idno '{$vs_code}' in list '{$vs_list}. Field was {$vs_field}"
							);
						}
					} else {
						throw new InvalidLogEntryException(
							"No corresponding code field '{$vs_potential_code_field}' found for list reference field '{$vs_field}'"
						);
					}

					continue;
				}

				// handle parent_ids -- have to translate GUID to primary key
				if($vs_field == $this->getModelInstance()->getProperty('HIERARCHY_PARENT_ID_FLD')) {
					if(isset($va_snapshot[$vs_field . '_guid']) && ($vs_parent_guid = $va_snapshot[$vs_field . '_guid'])) {
						$t_instance = $this->getModelInstance()->cloneRecord();
						if($t_instance->loadByGUID($vs_parent_guid)) {
							$this->getModelInstance()->set($vs_field, $t_instance->getPrimaryKey());
						} else {
							throw new InvalidLogEntryException("Could not load GUID {$vs_parent_guid} (referenced in HIERARCHY_PARENT_ID_FLD)");
						}
					} else {
						throw new InvalidLogEntryException("No guid for parent_id field found");
					}

					continue;
				}

				// plain old field like idno, extent, source_info etc.
				// model errors usually don't occurr on set(), so the implementations can still do whatever they want and possibly overwrite this
				$this->getModelInstance()->set($vs_field, $vm_val);
			}
		}
	}

	/**
	 * Check current model instance for errors and throw Exception if any
	 *
	 * @throws InvalidLogEntryException
	 */
	public function checkModelInstanceForErrors() {
		if(!($this->getModelInstance() instanceof \BaseModel)) {
			throw new InvalidLogEntryException('no model instance found');
		}

		if($this->getModelInstance()->numErrors() > 0) { // is this critical or not? hmm
			throw new InvalidLogEntryException(
				_t("There were errors processing record from log entry %1: %2",
					$this->getLogId(), join(' ', $this->getModelInstance()->getErrors()))
			);
		}
	}

	/**
	 * @param string $ps_source_system_id
	 * @param int $pn_log_id
	 * @param array $pa_log
	 * @param \Transaction $po_tx
	 * @return \CA\Sync\LogEntry\Base
	 * @throws InvalidLogEntryException
	 */
	public static function getInstance($ps_source_system_id, $pn_log_id, $pa_log, \Transaction $po_tx) {
		if(!is_array($pa_log) || !isset($pa_log['logged_table_num'])) {
			throw new InvalidLogEntryException('Invalid log entry');
		}

		$o_dm = \Datamodel::load();

		$t_instance = $o_dm->getInstance($pa_log['logged_table_num']);

		if($t_instance instanceof \BaseRelationshipModel) {
			return new Relationship($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \ca_attributes) {
			return new Attribute($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \ca_attribute_values) {
			return new AttributeValue($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \BaseLabel) {
			return new Label($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \BundlableLabelableBaseModelWithAttributes) {
			return new Bundlable($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		}

		throw new InvalidLogEntryException('Invalid table in log entry');
	}

}

/**
 * This should be handled as a non-recoverable error
 * Class InvalidLogEntryException
 * @package CA\Sync\LogEntry
 */
class InvalidLogEntryException extends \Exception {}

/**
 * More of a warning/notice. Something that should be logged but doesn't necessarily prevent import
 * Class LogEntryInconsistency
 * @package CA\Sync\LogEntry
 */
class LogEntryInconsistency extends \Exception {}

/**
 * Can be caught and discarded. This just means that the log entry is not relevant and is being skipped
 * @package CA\Sync\LogEntry
 */
class IrrelevantLogEntry extends \Exception {}
