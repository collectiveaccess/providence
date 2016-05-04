<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/SyncableBaseModel.php : common functions for syncable models
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

require_once(__CA_MODELS_DIR__ . '/ca_guids.php');

trait SyncableBaseModel {
	# -------------------------------------------------------
	/**
	 * Set GUID for this record.
	 * @param null $pa_options
	 */
	public function setGUID($pa_options=null) {
		if(!$this->getPrimaryKey()) { return; }

		/** @var ca_guids $t_guid */
		$t_guid = $this->getAppDatamodel()->getInstance('ca_guids');
		$t_guid->setTransaction($this->getTransaction());

		$t_guid->setMode(ACCESS_WRITE);
		$t_guid->set('table_num', $this->tableNum());
		$t_guid->set('row_id', $this->getPrimaryKey());
		$t_guid->set('guid', caGetOption('setGUIDTo', $pa_options, caGenerateGUID()));

		if($t_guid->getPrimaryKey()) {
			$t_guid->update();
		} else {
			$t_guid->insert();
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove GUID for this record
	 * @param $pn_primary_key
	 */
	public function removeGUID($pn_primary_key) {
		$t_guid = $this->getAppDatamodel()->getInstance('ca_guids');
		if($t_guid->load(array('table_num' => $this->tableNum(), 'row_id' => $pn_primary_key))) {
			$t_guid->setMode(ACCESS_WRITE);
			$t_guid->delete();
		}
	}
	# -----------------------------------------------------
	// guid utilities
	# -----------------------------------------------------
	/**
	 * Get GUID for current row
	 * @return bool|null|string
	 */
	public function getGUID() {
		if($this->getPrimaryKey()) {
			return ca_guids::getForRow($this->getPrimaryKey(), $this->tableNum());
		}

		return null;
	}
	# -----------------------------------------------------
	/**
	 * Load by GUID
	 * @param string $ps_guid
	 * @return bool|null
	 */
	public function loadByGUID($ps_guid) {
		$va_info = ca_guids::getInfoForGUID($ps_guid);

		if($va_info['table_num'] == $this->tableNum()) {
			return $this->load($va_info['row_id']);
		}

		return null;
	}
	# -----------------------------------------------------
	/**
	 * Get loaded BaseModel instance by GUID
	 * @param string $ps_guid
	 * @return null|BaseModel
	 */
	public static function getInstanceByGUID($ps_guid) {
		$vs_table = get_called_class();
		$t_instance = new $vs_table;

		if($t_instance->loadByGUID($ps_guid)) {
			return $t_instance;
		}

		return null;
	}
	# -----------------------------------------------------
	/**
	 * Get primary key for given GUID
	 * @param string $ps_guid
	 * @return int|null
	 */
	public static function getPrimaryKeyByGUID($ps_guid) {
		$vs_table = get_called_class();
		$t_instance = new $vs_table;

		if($t_instance->loadByGUID($ps_guid)) {
			return $t_instance->getPrimaryKey();
		}

		return null;
	}
	# -----------------------------------------------------
	/**
	 * Get guid by primary key
	 * @param int $pn_primary_key
	 * @return bool|string
	 */
	public static function getGUIDByPrimaryKey($pn_primary_key) {
		return ca_guids::getForRow(Datamodel::load()->getTableNum(get_called_class()), $pn_primary_key);
	}
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

		if($vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD')) {
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

		return md5(serialize($va_hash_components));
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
}
