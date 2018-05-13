<?php
/** ---------------------------------------------------------------------
 * app/lib/SyncableBaseModel.php : common functions for syncable models
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
		$vs_guid = caGetOption('setGUIDTo', $pa_options, caGenerateGUID());

		/** @var ca_guids $t_guid */
		$t_guid = Datamodel::getInstance('ca_guids');
		$t_guid->setTransaction($this->getTransaction());

		$t_guid->load(array('guid' => $vs_guid));

		$t_guid->setMode(ACCESS_WRITE);
		$t_guid->set('table_num', $this->tableNum());
		$t_guid->set('row_id', $this->getPrimaryKey());
		$t_guid->set('guid', $vs_guid);

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
		$t_guid = Datamodel::getInstance('ca_guids');
		if($t_guid->load(array('table_num' => $this->tableNum(), 'row_id' => $pn_primary_key))) {
			if($this->inTransaction()) {
				$t_guid->setTransaction($this->getTransaction());
			}
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
			$va_options = [];
			if($this->inTransaction()) {
				$va_options['transaction'] = $this->getTransaction();
			}
			return ca_guids::getForRow($this->getPrimaryKey(), $this->tableNum(), $va_options);
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
		return ca_guids::getForRow(Datamodel::getTableNum(get_called_class()), $pn_primary_key);
	}
	# -----------------------------------------------------
	/**
	 * Get loaded BaseModel instance by GUID
	 * @param string $ps_guid
	 * @return null|BaseModel
	 */
	public static function GUIDToInstance($ps_guid) {

		$o_db = new Db();
		$qr_res = $o_db->query("SELECT * FROM ca_guids WHERE guid = ?", [$ps_guid]);
		
		if($qr_res->nextRow()) {
			if (($t_instance = Datamodel::getInstanceByTableNum($qr_res->get('table_num'))) && ($t_instance->load($qr_res->get('row_id')))) {
				return $t_instance;
			}
		}
		return null;
	}
	# -----------------------------------------------------
}
