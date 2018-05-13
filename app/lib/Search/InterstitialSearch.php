<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/InterstitialSearch.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2015 Whirl-i-Gig
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

include_once(__CA_LIB_DIR__."/Search/BaseSearch.php");
include_once(__CA_LIB_DIR__."/Search/InterstitialSearchResult.php");

class InterstitialSearch extends BaseSearch {
	# ----------------------------------------------------------------------
	/**
	 * Which table does this class represent?
	 */
	protected $ops_tablename;
	protected $ops_primary_key;

	# ----------------------------------------------------------------------
	public function __construct($ps_table) {
		$this->ops_tablename = $ps_table;
		$this->opn_tablenum = Datamodel::getTableNum($ps_table);
		
		$this->ops_primary_key = Datamodel::primaryKey($ps_table);
		parent::__construct();
	}
	# ----------------------------------------------------------------------
	public function &search($ps_search, $pa_options=null) {
		return parent::doSearch($ps_search, new InterstitialSearchResult($this->ops_tablename), $pa_options);
	}
	# ----------------------------------------------------------------------
}