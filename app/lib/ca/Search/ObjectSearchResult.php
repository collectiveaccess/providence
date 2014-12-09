<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/ObjectSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_objects';
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct($po_engine_result=null, $pa_tables=null) {
		parent::__construct($po_engine_result=null, $pa_tables=null);
	}
	# -------------------------------------
	/**
	 * Override init to set ca_representations join params
	 *
	 * @param IWLPlugSearchEngineResult $po_engine_result
	 * @param array $pa_tables
	 * @param array $pa_options Options are:
	 *		filterNonPrimaryRepresentations = If set only primary representations are returned. This can improve performance somewhat in most cases. Default is true.
	 */
	public function init($po_engine_result, $pa_tables, $pa_options=null) {
		parent::init($po_engine_result, $pa_tables);
		
		if (!isset($pa_options['filterNonPrimaryRepresentations'])) { $pa_options['filterNonPrimaryRepresentations'] = true; }
		if ($pa_options['filterNonPrimaryRepresentations']) {
			$va_criteria = array('ca_objects_x_object_representations.is_primary = 1', 'ca_object_representations.deleted = 0');
		} else {
			$va_criteria = array('ca_object_representations.deleted = 0');
		}
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access', 'ca_object_representations.md5', 'ca_object_representations.mimetype', 'ca_object_representations.original_filename'),
			'joinTables' => array('ca_objects_x_object_representations'),
			'criteria' => $va_criteria
		);
	}
	# -------------------------------------
	/**
	 * Set if non-primary representations are filtered from returned results
	 *
	 * @param bool $pb_filter IF true non primary representations will be filtered from returned results
	 * @return bool Always returns true
	 */
	public function filterNonPrimaryRepresentations($pb_filter) {
		if ($pb_filter) {
			$va_criteria = array('ca_objects_x_object_representations.is_primary = 1', 'ca_object_representations.deleted = 0');
		} else {
			$va_criteria = array('ca_object_representations.deleted = 0');
		}
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access', 'ca_object_representations.md5', 'ca_object_representations.mimetype', 'ca_object_representations.original_filename'),
			'joinTables' => array('ca_objects_x_object_representations'),
			'criteria' => $va_criteria
		);
		
		return true;
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
	public function getTagForPrimaryRepresentation($ps_version, $pa_options=null) {
		return $this->getMediaTag('ca_object_representations.media', $ps_version, $pa_options);
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
		return parent::getMediaTag($ps_field, $ps_version, $pa_options);
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
		return parent::getMediaUrl($ps_field, $ps_version, $pa_options);
	}
	# -------------------------------------
	/**
	 *
	 */
	public function getMediaInfo($ps_field, $ps_version=null, $ps_key=null, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaInfo($ps_field, $ps_version, $ps_key, $pa_options);
	}
	# ------------------------------------------------------
 	/**
 	 * Returns number of representations attached to the current row of the specified class. 
 	 *
 	 * @param string $ps_class The class of representation to return a count for. Valid classes are "image", "audio", "video" and "document"
 	 * @param array $pa_options Optional array of options. Options are:
	 *						checkAccess = array of access values to restrict returned values to.
	 *
 	 * @return int Number of representations
 	 */
 	public function numberOfRepresentationsOfClass($ps_class, $pa_options=null) {
 		return sizeof($this->representationsOfClass($ps_class, $pa_options));
 	}
 	# ------------------------------------------------------
 	/**
 	 * Returns number of representations attached to the current row with the specified mimetype. 
 	 *
 	 * @param string $ps_mimetype The mimetype to return a count for. 
 	 * @param array $pa_options Optional array of options. Options are:
	 *						checkAccess = array of access values to restrict returned values to.
 	 *
 	 * @return int Number of representations
 	 */
 	public function numberOfRepresentationsWithMimeType($ps_mimetype, $pa_options=null) {
 		return sizeof($this->representationsWithMimeType($ps_mimetype, $pa_options));
 	}
	# -------------------------------------
	/**
 	 * Returns representation_ids for representations of the specified row attached to the current object. 
 	 *
 	 * @param string $ps_class The class of representation to return information for. Valid classes are "image", "audio", "video" and "document"
 	 * @param array $pa_options Optional array of options. Options are:
	 *						checkAccess = array of access values to restrict returned values to.
 	 *
 	 * @return array A list of representation_ids
 	 */
	public function representationsOfClass($ps_class, $pa_options=null) {
		if (!isset($this->opa_class_cache[$this->get('object_id')])) {
			$this->_prefetchMimeTypes($pa_options);
		}
		
		return isset($this->opa_class_cache[$this->get('object_id')][$ps_class]) ? $this->opa_class_cache[$this->get('object_id')][$ps_class] : array();
	}
	# -------------------------------------
	/**
 	 * Returns representation_ids of representations attached to the current row with the specified mimetype. 
 	 *
 	 * @param string $ps_mimetype The mimetype to return representations for. 
 	 * @param array $pa_options Optional array of options. Options are:
	 *						checkAccess = array of access values to restrict returned values to.
 	 *
 	 * @return array An array with information about matching representations, in the same format as that returned by ca_objects::getRepresentations()
 	 */
	public function representationsWithMimeType($ps_mimetype, $pa_options=null) {
		if (!isset($this->opa_mimetype_cache[$this->get('object_id')])) {
			$this->_prefetchMimeTypes($pa_options);
		}
		
		return isset($this->opa_mimetype_cache[$this->get('object_id')][$ps_mimetype]) ? $this->opa_mimetype_cache[$this->get('object_id')][$ps_mimetype] : array();
	}
	# -------------------------------------
	/**
	 * Prefetch MIME types and media classes for representations attached to objects in this result set
	 */
	private function _prefetchMimeTypes($pa_options) {
		$o_db = new Db();
		
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		$vs_sql_access = '';
		if (is_array($va_check_access) && sizeof($va_check_access)) { $vs_sql_access = ' AND caor.access IN ('.join(",", $va_check_access).')'; }

		$va_ids = $this->getRowIDsToPrefetch('ca_objects', $this->currentIndex(), 5);
		$qr_res = $o_db->query("
			SELECT oxor.object_id, caor.representation_id, caor.mimetype
			FROM ca_object_representations caor
			INNER JOIN ca_objects_x_object_representations AS oxor ON caor.representation_id = oxor.representation_id
			WHERE
				oxor.object_id IN (".join(",", $va_ids).") {$vs_sql_access}
		");
		
		while($qr_res->nextRow()) {
			$this->opa_mimetype_cache[(int)$qr_res->get('object_id')][$qr_res->get('mimetype')][] = (int)$qr_res->get('representation_id');
			$this->opa_class_cache[(int)$qr_res->get('object_id')][caGetMediaClass($qr_res->get('mimetype'))][] = (int)$qr_res->get('representation_id');
		}
		return true;
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