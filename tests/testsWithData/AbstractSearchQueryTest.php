<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/AbstractSearchQueryTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/ca/Service/ItemService.php');
require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');

abstract class AbstractSearchQueryTest extends BaseTestWithData {

	/**
	 * @var string Primary search table
	 */
	protected $ops_primary_table = 'ca_objects';

	/**
	 * @var array list of search queries to execute, keyed by search query. The value is the expected number of results.
	 */
	protected $opa_search_queries = array();

	# -------------------------------------------------------
	protected function setPrimaryTable($ps_table) {
		$o_dm = Datamodel::load();

		if(!$o_dm->tableExists($ps_table)) {
			$this->assertTrue(false, 'Invalid table '.$ps_table);
		}

		$this->ops_primary_table = $ps_table;
	}
	# -------------------------------------------------------
	protected function setSearchQueries($pa_queries) {
		if(is_array($pa_queries)) {
			$this->opa_search_queries = $pa_queries;
		} else {
			$this->assertTrue(false, 'Invalid search query data');
		}
	}
	# -------------------------------------------------------
	/**
	 * Run all the search queries set by the implementation and check if the number of hits fits!
	 */
	public function testSearchQueries() {
		if(!is_array($this->opa_search_queries)) { $this->assertTrue(false, 'no queries set up!'); }
		foreach($this->opa_search_queries as $vs_query => $vn_expected_num_results) {
			$o_search = caGetSearchInstance($this->ops_primary_table);
			$o_result = $o_search->search($vs_query);
			$this->assertEquals($vn_expected_num_results, $o_result->numHits(), 'Must match the expected number of search results. Query was: '.$vs_query);
		}
	}
	# -------------------------------------------------------
}
