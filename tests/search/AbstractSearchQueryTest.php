<?php
/** ---------------------------------------------------------------------
 * tests/search/AbstractSearchQueryTest.php
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

abstract class AbstractSearchQueryTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var array Test data. Format of a single record is similar to the JSON format used in the
	 * Web Service API, only that it's a PHP array, obviously. In fact, we use the same code to insert the data.
	 * Overall format is:
	 * 		array(
	 * 			table => array(
	 * 				some custom record key you dreamed up => record data (see above),
	 * 				...
	 * 			),
	 * 			table2 => ...
	 * 		)
	 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
	 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
	 */
	protected $opa_test_data = array();

	/**
	 * @var string Primary search table
	 */
	protected $ops_primary_table = 'ca_objects';

	/**
	 * @var array list of search queries to execute, keyed by search query. The value is the expected number of results.
	 */
	protected $opa_search_queries = array();

	/**
	 * @var array list of records we created and their custom 'keys/identifiers' set in the original array
	 */
	private $opa_record_map = array();
	# -------------------------------------------------------
	/**
	 * Inserts test data set by implementation
	 */
	public function setUp() {
		if(!is_array($this->opa_test_data)) { return false; }
		$vo_response = new ResponseHTTP();
		$vo_request = new RequestHTTP($vo_response);

		foreach($this->opa_test_data as $vs_table => $va_records) {
			foreach($va_records as $vs_key => $va_data) {
				$o_itemservice = new ItemService($vo_request, $this->ops_primary_table);
				$va_return = $o_itemservice->addItem($vs_table, $va_data);
				if(!$va_return) {
					$this->assertTrue(false, 'Inserting test data for key '.$vs_key.' failed');
				}

				$this->opa_record_map[$vs_table][$vs_key] = array_shift($va_return);
			}
		}

		return true;
	}
	# -------------------------------------------------------
	protected function setPrimaryTable($ps_table) {
		$o_dm = Datamodel::load();

		if(!$o_dm->tableExists($ps_table)) {
			$this->assertTrue(false, 'Invalid table '.$ps_table);
		}

		$this->ops_primary_table = $ps_table;
	}
	# -------------------------------------------------------
	protected function setTestData($pa_data) {
		if(is_array($pa_data)) {
			$this->opa_test_data = $pa_data;
		} else {
			$this->assertTrue(false, 'Invalid test data');
		}
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
		$o_search = caGetSearchInstance($this->ops_primary_table);
		foreach($this->opa_search_queries as $vs_query => $vn_expected_num_results) {
			$o_result = $o_search->search($vs_query);
			$this->assertEquals($vn_expected_num_results, $o_result->numHits(), 'Must match the expected number of search results');
		}
	}
	# -------------------------------------------------------
	/**
	 * Delete all records we created. Not really needed in a Travis CI setting but whatever ...
	 */
	public function tearDown() {
		foreach($this->opa_record_map as $vs_table => $va_records) {
			$o_dm = Datamodel::load();
			$t_instance = $o_dm->getInstance($vs_table);
			foreach($va_records as $vn_id) {
				if($t_instance->load($vn_id)) {
					$t_instance->setMode(ACCESS_WRITE);
					$t_instance->delete(true, array('hard' => true));
				}
			}
		}
	}
	# -------------------------------------------------------
}
