<?php
/** ---------------------------------------------------------------------
 * app/lib/SearchResultsRepresentableTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 
 /**
  * Methods for relationship models that include an is_primary flag
  */
  
 
trait SearchResultsRepresentableTrait {
	# -------------------------------------
	static $s_table_names = [];
	
	static $s_mimetype_cache = [];
	static $s_class_cache = [];
	
	# -------------------------------------
	/**
	 * Override init to set ca_representations join params
	 *
	 * @param IWLPlugSearchEngineResult $engine_result
	 * @param array $tables
	 * @param array $options Options are those taken by SearchResult::init():
	 *		filterNonPrimaryRepresentations = If set only primary representations are returned. This can improve performance somewhat in most cases. Default is true.
	 */
	public function init($engine_result, $tables, $options=null) {
		parent::init($engine_result, $tables, $options);
		
		$this->filterNonPrimaryRepresentations(caGetOption('filterNonPrimaryRepresentations', $options, true));
	}
	# -------------------------------------
	/**
	 *
	 */
	private function _getLinkingTableName() {
		$t = $this->opo_subject_instance->tableName();
		if(isset(self::$s_table_names[$t])) { return self::$s_table_names[$t]; }
		
		$path = Datamodel::getPath($t, 'ca_object_representations');
	    if(!is_array($path) || (sizeof($path) !== 3)) { return false; }
	    $path = array_keys($path);
		return self::$s_table_names[$t] = $path[1];
	}
	# -------------------------------------
	/**
	 * Set if non-primary representations are filtered from returned results
	 *
	 * @param bool $filter IF true non primary representations will be filtered from returned results
	 * @return bool Always returns true
	 */
	public function filterNonPrimaryRepresentations($filter) {
	   if(!($table = $this->_getLinkingTableName())) { return false; }
		
		if ($filter) {
			$criteria = ["{$table}.is_primary = 1", 'ca_object_representations.deleted = 0'];
		} else {
			$criteria = ['ca_object_representations.deleted = 0'];
		}
		$this->opa_tables['ca_object_representations'] = [
			'fieldList' => ['ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access', 'ca_object_representations.md5', 'ca_object_representations.mimetype', 'ca_object_representations.original_filename'],
			'joinTables' => [$table],
			'criteria' => $criteria
		];
		
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
		$object_id = (int)$this->get('object_id');
		if (!isset(self::$s_class_cache[$object_id])) {
			$this->_prefetchMimeTypes($pa_options);
		}
		
		return isset(self::$s_class_cache[$object_id][$ps_class]) ? self::$s_class_cache[$object_id][$ps_class] : [];
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
		$object_id = (int)$this->get('object_id');
		if (!isset(self::$s_mimetype_cache[$object_id])) {
			$this->_prefetchMimeTypes($pa_options);
		}
		
		return isset(self::$s_mimetype_cache[$object_id][$ps_mimetype]) ? self::$s_mimetype_cache[$object_id][$ps_mimetype] : [];
	}
	# -------------------------------------
	/**
	 * Prefetch MIME types and media classes for representations attached to objects in this result set
	 */
	private function _prefetchMimeTypes($pa_options) {
		if(!($table = $this->_getLinkingTableName())) { return false; }
		
		$o_db = new Db();
		
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		$vs_sql_access = '';
		if (is_array($va_check_access) && sizeof($va_check_access)) { $vs_sql_access = ' AND caor.access IN ('.join(",", $va_check_access).')'; }

		$va_ids = $this->getRowIDsToPrefetch('ca_objects', $this->currentIndex(), 5);
		$qr_res = $o_db->query("
			SELECT oxor.object_id, caor.representation_id, caor.mimetype
			FROM ca_object_representations caor
			INNER JOIN {$table} AS oxor ON caor.representation_id = oxor.representation_id
			WHERE
				oxor.object_id IN (".join(",", $va_ids).") {$vs_sql_access}
		");
		
		while($qr_res->nextRow()) {
			$object_id = (int)$qr_res->get('object_id');
			$representation_id = (int)$qr_res->get('representation_id');
			$mimetype = $qr_res->get('mimetype');
			self::$s_mimetype_cache[$object_id][$mimetype][] = $representation_id;
			self::$s_class_cache[$object_id][caGetMediaClass($mimetype)][] = $representation_id;
		}
		return true;
	}
	# -------------------------------------
	/**
	 * Indicates if current user has rights to view media
	 *
	 * @param array $pa_access_values List of access values which media must have if user is to be able to view
	 * @return boolean True if user has access, false if not
	 */
	 private function _haveAccessToRepresentation($pa_access_values) {
	 	if (!is_array($pa_access_values)) { $pa_access_values = []; }
	 	if (!sizeof($pa_access_values)) { return true; }
	 	if (!sizeof(array_intersect($this->get('ca_object_representations.access', array('returnAsArray' => true)), $pa_access_values))) {
			return false;	
		}
		return true;
	}
	# -------------------------------------
}