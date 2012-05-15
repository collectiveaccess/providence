<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/ObjectSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class ObjectSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of labels table for this type of search subject (eg. for ca_objects, the label table is ca_object_labels)
	 */
	protected $ops_label_table_name = 'ca_object_labels';
	# -------------------------------------
	/**
	 * Name of field in labels table to use for display for this type of search subject (eg. for ca_objects, the label display field is 'name')
	 */
	protected $ops_label_display_field = 'name';
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
	# -------------------------------------
	/**
	 * Override init to set ca_representations join params
	 */
	public function init($pn_subject_table_num, $po_engine_result, $pa_tables) {
		parent::init($pn_subject_table_num, $po_engine_result, $pa_tables);
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access'),
			'joinTables' => array('ca_objects_x_object_representations'),
			'criteria' => array('ca_objects_x_object_representations.is_primary = 1', 'ca_object_representations.deleted = 0')
		);
	}
	# -------------------------------------
	/**
	 *
	 */
	public function get($ps_field, $pa_options=null) {
		if (substr($ps_field, 0, 25) === 'ca_object_representations') {
			$va_tmp = explode('.', $ps_field);
			if ($va_tmp[1] !== 'access') {
				$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
				if (!$this->_haveAccessToRepresentation($va_check_access)) {
					return null;
				}
			}
		}
		return parent::get($ps_field, $pa_options);
	}
	# -------------------------------------
	/**
	 *
	 */
	public function getMediaTag($ps_field, $ps_version, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaTag($ps_field, $ps_version, $pa_option);
	}
	# -------------------------------------
	/**
	 *
	 */
	public function getMediaUrl($ps_field, $ps_version, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaUrl($ps_field, $ps_version, $pa_option);
	}
	# -------------------------------------
	/**
	 *
	 */
	public function getMediaInfo($ps_field, $ps_version, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaInfo($ps_field, $ps_version, $pa_option);
	}
	# -------------------------------------
	/**
	 *
	 */
	 private function _haveAccessToRepresentation($pa_access_values) {
	 	if (!is_array($pa_access_values)) { $pa_access_values = array(); }
	 	if (!sizeof($pa_access_values)) { return true; }
	 	if (!in_array($this->get('ca_object_representations.access'), $pa_access_values)) {
			return false;	
		}
		return true;
	 }
	 # -------------------------------------
}
?>