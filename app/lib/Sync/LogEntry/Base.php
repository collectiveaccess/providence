<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/LogEntry/Base.php
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

require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Attribute.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/AttributeValue.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Bundlable.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Relationship.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Label.php');
require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Representation.php');

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

		$this->opt_instance = \Datamodel::getInstance($this->getTableNum());

		if(!($this->opt_instance instanceof \BaseModel)) {
			throw new InvalidLogEntryException('table num is invalid for this log entry');
		}

		if(!$this->isRelevant()) {
			throw new IrrelevantLogEntry();
		}

		$this->opt_instance->setTransaction($this->getTx());

		// if this is not an insert log entry, load the specified row by GUID
		if($this->isUpdate() || $this->isDelete()) {
			// if we can't find the GUID and this is an update, throw error
			if((!$this->getModelInstance()->loadByGUID($this->getGUID())) && $this->isUpdate()) {
				throw new IrrelevantLogEntry('Mode was update but the given GUID "'.$this->getGUID().'" could not be found for table num ' . $this->getTableNum());
			}

			// if we can't find it and this is a delete, we don't particularly care. yes, we can't delete a non-existing
			// record, but in terms of sync, that's a non-critical error.
			if((!$this->getModelInstance()->loadByGUID($this->getGUID())) && $this->isDelete()) {
				throw new IrrelevantLogEntry('Mode was delete but the given GUID "'.$this->getGUID().'" could not be found for table num ' . $this->getTableNum());
			}
		}

		$this->opt_instance->setMode(ACCESS_WRITE);
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
		
		if (!method_exists($this->getModelInstance(), "loadByGUID")) { return false; }
		if(preg_match("/^ca_locales/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_editor_ui/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_metadata_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_relationship_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_search_/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_bundle_display/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_data_import/", $vs_t)) {
			return false;
		}
		if(preg_match("/^ca_data_exporter/", $vs_t)) {
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
		
			// Init unset value fields to null; this allows blanking of a field value to be replicated
			if (
				($this->opt_instance->tableName() == 'ca_attribute_values')
				&&
				!isset($this->opa_log['snapshot']['item_id']) &&
				!isset($this->opa_log['snapshot']['value_longtext1']) &&
				!isset($this->opa_log['snapshot']['value_longtext2']) &&
				!isset($this->opa_log['snapshot']['value_blob']) &&
				!isset($this->opa_log['snapshot']['value_decimal1']) &&
				!isset($this->opa_log['snapshot']['value_decimal2']) &&
				!isset($this->opa_log['snapshot']['value_integer1'])
			) {
				foreach (['item_id', 'value_longtext1', 'value_longtext2', 'value_blob', 'value_decimal1', 'value_decimal2', 'value_integer1'] as $vs_f) {
					if(!isset($this->opa_log['snapshot'][$vs_f])) { $this->opa_log['snapshot'][$vs_f] = null; }
				}
			}
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
	 * Get model instance for row referenced in change log entry
	 * @return \BaseModel|null
	 */
	public function getModelInstance() {
		return $this->opt_instance;
	}

	/**
	 * Checks if all the bits and pieces are in place for this log entry. This is meant to be called
	 * before apply() and, ideally, before setIntrinsicsFromSnapshotInModelInstance()
	 * @return mixed
	 * @throws InvalidLogEntryException
	 */
	public function sanityCheck() {
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

						if(strlen($vs_code) && ($vs_code !== 'null') && !($vn_item_id = caGetListItemID($vs_list, $vs_code, ['includeDeleted' => true]))) {
							throw new InvalidLogEntryException(
								"Couldn't find list item id for idno '{$vs_code}' in list '{$vs_list}'. Field was {$vs_field}"
							);
						}
					} else {
						throw new InvalidLogEntryException(
							"No corresponding code field '{$vs_potential_code_field}' found for list reference field '{$vs_field}'"
						);
					}

					continue;
				}

				// check parent_id field
				if(($vs_field == $this->getModelInstance()->getProperty('HIERARCHY_PARENT_ID_FLD')) && (intval($va_snapshot[$vs_field]) != 1)) {
					if(isset($va_snapshot[$vs_field . '_guid']) && ($vs_parent_guid = $va_snapshot[$vs_field . '_guid'])) {
						if(($vs_idno = $va_snapshot[$this->getModelInstance()->getProperty('ID_NUMBERING_ID_FIELD')]) && !preg_match("/Root node for /", $vs_idno)) {
							$t_instance = $this->getModelInstance()->cloneRecord();
							$t_instance->setTransaction($this->getTx());
							if(!$t_instance->loadByGUID($vs_parent_guid) && !(intval($va_snapshot[$vs_field]) == 1)) {
								throw new InvalidLogEntryException(_t("Could not load GUID %1 (referenced in HIERARCHY_PARENT_ID_FLD)", $vs_parent_guid));
							}
						}
					} else {
						throw new InvalidLogEntryException("No parent_guid field found");
					}
				}
			}
		}
	}

	/**
	 * Set intrinsic fields from snapshot in given model instance
	 */
	public function setIntrinsicsFromSnapshotInModelInstance() {
		$va_snapshot = $this->getSnapshot();

		$va_many_to_one_rels = \Datamodel::getManyToOneRelations($this->getModelInstance()->tableName());
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
			
				// handle media in intrinsics
				// was checksum? -> clean up stashed file
				if (($va_fld_info['FIELD_TYPE'] == FT_MEDIA) && (strlen($va_snapshot[$vs_field]) == 32) && preg_match("/^[a-f0-9]+$/", $va_snapshot[$vs_field])) {
					$o_app_vars = new \ApplicationVars();
					$va_files = $o_app_vars->getVar('pushMediaFiles');
					
					if(isset($va_files[$va_snapshot[$vs_field]])) {
						$vm_val = $va_files[$va_snapshot[$vs_field]];
						$this->getModelInstance()->set($vs_field, $vm_val);
					}
					
					continue;
				}

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
								$this->getModelInstance()->set($vs_field, caGetListItemValueForID($vn_item_id), ['allowSettingOfTypeID' => true]);
							} else { // type_id, source_id, etc. ...
								$this->getModelInstance()->set($vs_field, $vn_item_id, ['allowSettingOfTypeID' => true]);
							}
						} elseif(strlen($vs_code) && ($vs_code !== 'null')) {
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

				// handle parent_ids -- have to translate GUID to primary key, unless it's 1
				if($vs_field == $this->getModelInstance()->getProperty('HIERARCHY_PARENT_ID_FLD')) {
					if(intval($va_snapshot[$vs_field]) == 1) {
						$this->getModelInstance()->set($vs_field, 1);
					} else {
						if(isset($va_snapshot[$vs_field . '_guid']) && ($vs_parent_guid = $va_snapshot[$vs_field . '_guid'])) {
							$t_instance = $this->getModelInstance()->cloneRecord();
							$t_instance->setTransaction($this->getTx());
							if($t_instance->loadByGUID($vs_parent_guid)) {
								$this->getModelInstance()->set($vs_field, $t_instance->getPrimaryKey());
							} else {
								if(($vs_idno = $va_snapshot[$this->getModelInstance()->getProperty('ID_NUMBERING_ID_FIELD')]) && preg_match("/Root node for /", $vs_idno)) {
									throw new IrrelevantLogEntry();
								}

								throw new InvalidLogEntryException("Could not load GUID {$vs_parent_guid} (referenced in HIERARCHY_PARENT_ID_FLD)");
							}
						} else {
							throw new InvalidLogEntryException("No guid for parent_id field found");
						}
					}

					continue;
				}
				
				// handle table_num/row_id based polymorphic relationships
				if (($vs_field == 'row_id') && isset($va_snapshot['row_guid']) && ($t_rel_item = \Datamodel::getInstanceByTableNum($va_snapshot['table_num'], true))) {
					if($t_rel_item->loadByGUID($va_snapshot['row_guid'])) {
						$this->getModelInstance()->set($vs_field, $t_rel_item->getPrimaryKey());
						continue;
					}
				}
				
				// handle many-to-ones relationships (Eg. ca_set_items.set_id => ca_sets.set_id)
				if (isset($va_many_to_one_rels[$vs_field]) && ($t_rel_item = \Datamodel::getInstanceByTableName($va_many_to_one_rels[$vs_field]['one_table'], true)) && ($t_rel_item instanceof \BundlableLabelableBaseModelWithAttributes)) {
					$t_rel_item->setTransaction($this->getTx());
					if($t_rel_item->loadByGUID($va_snapshot[$vs_field.'_guid'])) {
						$this->getModelInstance()->set($vs_field, $t_rel_item->getPrimaryKey());
						continue;
					} else {
						if (!in_array($vs_field, ['type_id', 'locale_id', 'item_id', 'lot_id'])) {	// let auto-resolved fields fall through
							throw new IrrelevantLogEntry(_t("%1 guid value '%2' is not defined on this system for %3: %4", $vs_field, $va_snapshot[$vs_field.'_guid'], $t_rel_item->tableName(), print_R($va_snapshot, true)));
						}
					}
				}

				if(($this->getModelInstance() instanceof \ca_representation_annotations) && ($vs_field == 'representation_id')) {
					if(isset($va_snapshot[$vs_field . '_guid']) && ($vs_rep_guid = $va_snapshot[$vs_field . '_guid'])) {
						$t_rep = new \ca_object_representations();
						$t_rep->setTransaction($this->getTx());
						if($t_rep->loadByGUID($vs_rep_guid)) {
							$this->getModelInstance()->set($vs_field, $t_rep->getPrimaryKey());
						} else {
							throw new InvalidLogEntryException("Could not load GUID {$vs_rep_guid} (referenced in representation_id)");
						}
					}
				}

				// plain old field like idno, extent, source_info etc.
				// model errors usually don't occur on set(), so the implementations
				// can still do whatever they want and possibly overwrite this
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
			if (($this->getModelInstance()->numErrors() == 1) && ($o_error = $this->getModelInstance()->errors[0]) && ($o_error->getErrorNumber() == 251)) {
				throw new IrrelevantLogEntry(_t("Log entry has already been applied"));
			}
		
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
	 * @throws IrrelevantLogEntry
	 */
	public static function getInstance($ps_source_system_id, $pn_log_id, $pa_log, \Transaction $po_tx) {
		if(!is_array($pa_log) || !isset($pa_log['logged_table_num'])) {
			throw new InvalidLogEntryException('Invalid log entry');
		}

		$t_instance = \Datamodel::getInstance($pa_log['logged_table_num']);

		if($t_instance instanceof \BaseRelationshipModel) {
			return new Relationship($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \ca_attributes) {
			return new Attribute($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \ca_attribute_values) {
			return new AttributeValue($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \BaseLabel) {
			return new Label($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \ca_object_representations) {
			return new Representation($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \BundlableLabelableBaseModelWithAttributes) {
			return new Bundlable($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		} elseif($t_instance instanceof \BaseModel) {
			return new Bundlable($ps_source_system_id, $pn_log_id, $pa_log, $po_tx);
		}

		throw new IrrelevantLogEntry();
	}

}

/**
 * This should be handled as a non-recoverable error
 * Class InvalidLogEntryException
 * @package CA\Sync\LogEntry
 */
class InvalidLogEntryException extends \Exception {}

/**
 * Can be caught and discarded. This just means that the log entry is not relevant and is being skipped
 * @package CA\Sync\LogEntry
 */
class IrrelevantLogEntry extends \Exception {}
