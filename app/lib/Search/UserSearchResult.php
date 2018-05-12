<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/UserSearchResult.php :
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class UserSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_users';
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
	
	# -------------------------------------------------------
	/**
	 * Returns label(s) from current row ready for display (ie. in the current users locale)
	 *
	 * @param bool $pb_has_preferred_flag If set then only preferred label is returned, otherwise all labels for the users locale are returned. Default is true.
	 * @return array List of labels ready for display
	 */
	public function getDisplayLabels($pb_has_preferred_flag=true) {
		return array(1 => "_dummy");
	}
	# -------------------------------------
}