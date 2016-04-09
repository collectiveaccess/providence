<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/EntitySearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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

class EntitySearchResult extends BaseSearchResult {
	
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_entities';
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
	 *
	 * @param IWLPlugSearchEngineResult $po_engine_result
	 * @param array $pa_tables
	 * @param array $pa_options Options are those taken by SearchResult::init():
	 *		filterNonPrimaryRepresentations = If set only primary representations are returned. This can improve performance somewhat in most cases. Default is true.
	 */
	public function init($po_engine_result, $pa_tables, $pa_options=null) {
		parent::init($po_engine_result, $pa_tables, $pa_options);
		
		if (!isset($pa_options['filterNonPrimaryRepresentations'])) { $pa_options['filterNonPrimaryRepresentations'] = true; }
		if ($pa_options['filterNonPrimaryRepresentations']) {
			$va_criteria = array('ca_object_representations_x_entities.is_primary = 1', 'ca_object_representations.deleted = 0');
		} else {
			$va_criteria = array('ca_object_representations.deleted = 0');
		}
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access', 'ca_object_representations.md5', 'ca_object_representations.mimetype', 'ca_object_representations.original_filename'),
			'joinTables' => array('ca_object_representations_x_entities'),
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
			$va_criteria = array('ca_object_representations_x_entities.is_primary = 1', 'ca_object_representations.deleted = 0');
		} else {
			$va_criteria = array('ca_object_representations.deleted = 0');
		}
		$this->opa_tables['ca_object_representations'] = array(
			'fieldList' => array('ca_object_representations.media', 'ca_object_representations.representation_id', 'ca_object_representations.access', 'ca_object_representations.md5', 'ca_object_representations.mimetype', 'ca_object_representations.original_filename'),
			'joinTables' => array('ca_object_representations_x_entities'),
			'criteria' => $va_criteria
		);
		
		return true;
	}
	# -------------------------------------
}
?>