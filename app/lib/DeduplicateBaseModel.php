<?php
/** ---------------------------------------------------------------------
 * app/lib/DeduplicateBaseModel.php :
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */


trait DeduplicateBaseModel {
	# -----------------------------------------------------
	/**
	 * Implementations can override this to add more criteria for hashing, e.g. hierarchy path components or what have you
	 * @return array
	 */
	public function getAdditionalChecksumComponents() {
		return array();
	}
	# -------------------------------------------------------
	/**
	 * Get identifying checksum for this row
	 * @return bool|string
	 */
	public function getChecksum() {
		if(!$this->getPrimaryKey()) { return false; }
		$va_hash_components = array();

		if(($vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD')) && !($this->getAppConfig()->get($this->tableName() . '_dont_use_idno_in_checksums'))) {
			$va_hash_components[] = $this->get($vs_idno_fld);
		}

		if($vs_type_id_fld = $this->getProperty('ATTRIBUTE_TYPE_ID_FLD')) {
			$va_hash_components[] = $this->getTypeCode();
		}

		if(method_exists($this, 'getPreferredLabelCount') && ($this->getPreferredLabelCount() > 0)) {
			$va_hash_components[] = $this->get($this->tableName(). '.preferred_labels', array('returnAllLocales' => true));
		}

		if(method_exists($this, 'getNonPreferredLabelCount') && ($this->getNonPreferredLabelCount() > 0)) {
			$va_hash_components[] = $this->get($this->tableName(). '.nonpreferred_labels', array('returnAllLocales' => true));
		}

		if($vs_parent_id_fld = $this->getProperty('HIERARCHY_PARENT_ID_FLD')) {
			if($vn_parent_id = $this->get($vs_parent_id_fld)) {
				$va_hash_components[] = self::getChecksumForRecord($vn_parent_id);
			}
		}

		if($vs_source_id_fld = $this->getProperty('SOURCE_ID_FLD')) {
			if($vs_source_idno = $this->get($vs_source_id_fld, array('convertCodesToIdno' => true))) {
				$va_hash_components[] = $vs_source_idno;
			}
		}

		$va_hash_components[] = $this->getAdditionalChecksumComponents();

		return hash('sha256', serialize($va_hash_components));
	}
	# -------------------------------------------------------
	public static function getChecksumForRecord($pn_record_id) {
		$vs_table = get_called_class();
		/** @var BundlableLabelableBaseModelWithAttributes $t_instance */
		$t_instance = new $vs_table;

		$t_instance->load($pn_record_id);
		return $t_instance->getChecksum();
	}
	# -------------------------------------------------------
	/**
	 * Return potential duplicates in this class, as determined by their checksum
	 * @return array
	 */
	public static function listPotentialDupes() {
		/** @var BundlableLabelableBaseModelWithAttributes $t_instance */
		$t_instance = Datamodel::getInstance(get_called_class());
		$vs_deleted_sql = '';
		if($t_instance->hasField('deleted')) {
			$vs_deleted_sql = "WHERE deleted = 0";
		}

		$qr_records = $t_instance->getDb()->query("
			SELECT {$t_instance->primaryKey()} FROM {$t_instance->tableName()} {$vs_deleted_sql} ORDER BY {$t_instance->primaryKey()}
		");

		$va_checksums = array();
		while($qr_records->nextRow()) {
			$va_checksums[$t_instance->getChecksumForRecord($qr_records->get($t_instance->primaryKey()))][] =
				$qr_records->get($t_instance->primaryKey());
		}

		// remove non-dupes
		foreach($va_checksums as $vs_hash => $va_list) {
			if(sizeof($va_list) < 2) {
				unset($va_checksums[$vs_hash]);
			}
		}

		return $va_checksums;
	}
	# -------------------------------------------------------
	/**
	 * Merge an arbitrary number of records into one. Note that this will delete all records but the first one in the array.
	 * @param array $pa_records
	 * @return bool|int The primary key of the remaining (merged) record
	 * @throws DeduplicateException
	 */
	public static function mergeRecords($pa_records) {
		if(!is_array($pa_records)) { return false; }
		/** @var BundlableLabelableBaseModelWithAttributes $t_main_record */
		$t_main_record = Datamodel::getInstance(get_called_class(), false);
		if(!$t_main_record->load(array_shift($pa_records))) {
			throw new DeduplicateException('Could not load main record for deduplication');
		}
		$t_main_record->setMode(ACCESS_WRITE);

		/** @var BundlableLabelableBaseModelWithAttributes $t_other_record */
		$t_other_record = Datamodel::getInstance(get_called_class(), false);

		foreach($pa_records as $vn_record_id) {
			if(!is_numeric($vn_record_id)) { throw new DeduplicateException('One of the identifiers in the deduplication list is not numeric: ' . $vn_record_id); }

			if(!$t_other_record->load(array_shift($pa_records))) {
				throw new DeduplicateException('Could not load other record for deduplication');
			}

			self::mergeRelationships($t_main_record, $t_other_record);
			self::mergeAttributes($t_main_record, $t_other_record);

			$t_other_record->setMode(ACCESS_WRITE);
			$t_other_record->delete(true);
		}

		return $t_main_record->getPrimaryKey();
	}
	# -------------------------------------------------------
	/**
	 * Merge relationships for two given table instances
	 * @param BundlableLabelableBaseModelWithAttributes $t_main
	 * @param BundlableLabelableBaseModelWithAttributes $t_dupe
	 * @throws DeduplicateException
	 */
	protected static function mergeRelationships($t_main, $t_dupe) {
		//@todo check if this makes sense, or generate it dynamically.
		//@todo took this from the moveRelationships part of BaseEditorController
		$va_tables = array(
			'ca_objects', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections',
			'ca_storage_locations', 'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours',
			'ca_tour_stops', 'ca_object_representations', 'ca_list_items'
		);

		// leverage moveRelationships() code which in theory shouldn't create dupes, right? right!?
		foreach($va_tables as $vs_table) {
			$t_dupe->moveRelationships($vs_table, $t_main->getPrimaryKey());
		}

		// update existing metadata attributes to use remapped value
		$t_dupe->moveAuthorityElementReferences($t_main->getPrimaryKey());
	}
	# -------------------------------------------------------
	/**
	 * Merge attributes for two given table instances
	 * @param BundlableLabelableBaseModelWithAttributes $t_main
	 * @param BundlableLabelableBaseModelWithAttributes $t_dupe
	 */
	protected static function mergeAttributes($t_main, $t_dupe) {
		$t_main->setMode(ACCESS_WRITE);
		$t_dupe->setMode(ACCESS_WRITE);
		$va_move_codes = [];

		foreach($t_dupe->getApplicableElementCodes() as $vs_element_code) { // dupe and main applicable codes are the same
			// pairwise comparison of attributes

			$va_dupe_attributes = $va_main_attributes = [];

			foreach($t_dupe->getAttributesByElement($vs_element_code) as $o_dupe_attribute) {
				/** @var Attribute $o_dupe_attribute */
				$va_dupe_attributes[$o_dupe_attribute->getAttributeID()] = md5(serialize($o_dupe_attribute->getDisplayValues()));
			}

			foreach($t_main->getAttributesByElement($vs_element_code) as $o_main_attribute) {
				/** @var Attribute $o_main_attribute */
				$va_main_attributes[$o_main_attribute->getAttributeID()] = md5(serialize($o_main_attribute->getDisplayValues()));
			}

			// nuke dupe attributes that are also in main
			foreach($va_main_attributes as $vn_id => $vs_md5) {
				while($vn_k = array_search($vs_md5, $va_dupe_attributes)) {
					unset($va_dupe_attributes[$vn_k]);
					$t_dupe->removeAttribute($vn_k);
				}
			}
			$t_dupe->update(); // commit nuke

			// mark this attribute for moving
			if(sizeof($va_dupe_attributes)) {
				$va_move_codes[] = $vs_element_code;
			}
		}

		if(sizeof($va_move_codes)) {
			$t_dupe->moveAttributes($t_main->getPrimaryKey(), $va_move_codes);
		}
	}
	# -------------------------------------------------------
}

class DeduplicateException extends Exception {}
