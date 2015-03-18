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

abstract class BaseTestWithData extends PHPUnit_Framework_TestCase {
	/**
	 * @var array list of records we created and their custom 'keys/identifiers' set in the original array
	 */
	private $opa_record_map = array();

	private $opo_request = null;
	# -------------------------------------------------------
	/**
	 * Inserts test data set by implementation
	 */
	public function setUp() {
		global $g_request;
		$vo_response = new ResponseHTTP();
		$g_request = $this->opo_request = new RequestHTTP($vo_response);

		define('__CA_APP_TYPE__', 'PROVIDENCE');
	}
	# -------------------------------------------------------
	/**
	 * @param string $ps_table Target table
	 * @param array $pa_data Test data. Format of a single record is similar to the JSON format used in the
	 * Web Service API, only that it's a PHP array, obviously. In fact, we use the same code to insert the data.
	 *
	 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
	 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
	 * @return int the primary key of the newly created record
	 */
	protected function addTestRecord($ps_table, $pa_data) {
		if(!is_array($pa_data)) { return false; }

		$o_itemservice = new ItemService($this->opo_request, $ps_table);
		$va_return = $o_itemservice->addItem($ps_table, $pa_data);
		if(!$va_return) {
			$this->assertTrue(false, 'Inserting test data failed');
		}

		$this->opa_record_map[$ps_table][] = $vn_return = array_shift($va_return);
		return $vn_return;
	}
	# -------------------------------------------------------
	protected function getRecordMap() {
		return $this->opa_record_map;
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
