<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/InterstitialSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

include_once(__CA_LIB_DIR__."/Search/BaseSearchResult.php");

class InterstitialSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name;
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct($ps_table) {
		$this->ops_table_name = $ps_table;
		parent::__construct();
	}
	# -------------------------------------
	/**
	 * Set if non-primary representations are filtered from returned results
	 *
	 * @param bool $filter IF true non primary representations will be filtered from returned results
	 * @return bool Always returns true
	 */
	public function filterNonPrimaryRepresentations($filter) {
		if ($t_instance = Datamodel::getFieldNum($table = $this->ops_table_name, 'is_primary') > 0) {
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
			if($filter) {
				$this->opa_tables[$table] = [
					'fieldList' => ["{$table}.relation_id", "{$table}.is_primary"],
					'joinTables' => [],
					'criteria' => ["{$table}.is_primary = 1"]
				];
			}
		}
	}
	# -------------------------------------
}
