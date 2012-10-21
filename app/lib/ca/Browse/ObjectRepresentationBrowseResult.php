<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/ObjectRepresentationBrowseResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class ObjectRepresentationBrowseResult extends BaseSearchResult {
	
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_object_representations';
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
	public function init($po_engine_result, $pa_tables, $pa_options=null) {
		parent::init($po_engine_result, $pa_tables);
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access'),
			'joinTables' => array('ca_objects_x_object_representations')
		);
	}
	# -------------------------------------
	/**
	 * Hooks get() to impose representation-level access control
	 *
	 * @param string $ps_field The bundle to fetch value(s) for
	 * @param array optional list of get() options
	 * @return mixed A return value - either a scalar or array
	 */
	public function get($ps_field, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::get($ps_field, $pa_options);
	}
	# -------------------------------------
	/**
	 * Returns an HTML tag for a media version in a media field. 
	 *
	 * @param string $ps_field The field to fetch media from
	 * @param string $ps_version The media version to fetch
	 * @param array $pa_options Optional array of options. Options are passed through to the media plugin htmlTag() method for rendering. In addition 
	 *					getMediaTag() also implements the following options:
	 *						page = the page to return a tag for in a multipage document.
	 *						index = if media repeats, indicates the position of the value to return. The index is zero-based.
	 *						checkAccess = array of access values to restrict returned values to.
	 */
	public function getMediaTag($ps_field, $ps_version, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaTag($ps_field, $ps_version, isset($pa_options['index']) ? $pa_options['index'] : 0, $pa_options);
	}
	# -------------------------------------
	/**
	 * Returns a URL for a media version in a media field. 
	 *
	 * @param string $ps_field The field to fetch media from
	 * @param string $ps_version The media version to fetch
	 * @param array $pa_options Optional array of options. Options are: 
	 *						index = if media repeats, indicates the position of the value to return. The index is zero-based.
	 *						checkAccess = array of access values to restrict returned values to.
	 */
	public function getMediaUrl($ps_field, $ps_version, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaUrl($ps_field, $ps_version, isset($pa_options['index']) ? $pa_options['index'] : 0, $pa_options);
	}
	# -------------------------------------
	/**
	 * Returns information for a media version in a media field. If key is specified then a specific element of the media info list
	 * is returned, otherwise the entire list is returned as an associative array.
	 *
	 * @param string $ps_field The field to fetch media from
	 * @param string $ps_version The media version to fetch
	 * @param string $ps_key The name of the info element to return. (Ex. 'HEIGHT' returns the media height; 'MD5' returns the md5 hash for the media). If omitted the entire array will be returned.
	 * @param array $pa_options Optional array of options. Options are:
	 *						index = if media repeats, indicates the position of the value to return. The index is zero-based.
	 *						checkAccess = array of access values to restrict returned values to.
	 */
	public function getMediaInfo($ps_field, $ps_version, $ps_key=null, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);
		$va_check_access = isset($pa_options['checkAccess']) ? $pa_options['checkAccess'] : null;
		
		if (($va_tmp[0] === 'ca_object_representations') && ($va_tmp[1] !== 'access')) {
			if (!$this->_haveAccessToRepresentation($va_check_access)) {
				return null;
			}
		}
		return parent::getMediaInfo($ps_field, $ps_version, $ps_key, isset($pa_options['index']) ? $pa_options['index'] : 0, $pa_options);
	}
	# -------------------------------------
	/**
	 * Indicates if current user has rights to view media
	 *
	 * @param array $pa_access_values List of access values which media must have if user is to be able to view
	 * @return boolean True if user has access, false if not
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