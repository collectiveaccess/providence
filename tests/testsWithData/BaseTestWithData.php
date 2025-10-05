<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/AbstractSearchQueryTestClass.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__.'/Service/ItemService.php');
require_once(__CA_LIB_DIR__.'/Search/SearchIndexer.php');
require_once(__CA_MODELS_DIR__.'/ca_search_indexing_queue.php');

abstract class BaseTestWithData extends TestCase {
	/**
	 * @var array list of records we created and their custom 'keys/identifiers' set in the original array
	 */
	private $opa_record_map = array();

	/**
	 * @var null|RequestHTTP
	 */
	private $opo_request = null;

	/**
	 * Quick switch to turn off side effect checks and record deletion after the test.
	 * Can be useful for search testing in some cases where you actually want to look
	 * at the indexing after the test ran.
	 * @var bool
	 */
	private $opb_care_about_side_effects = true;

	static $opa_valid_tables = array('ca_objects', 'ca_entities', 'ca_occurrences', 'ca_movements', 'ca_loans', 'ca_object_lots', 'ca_storage_locations', 'ca_places', 'ca_item_comments', 'ca_sets');
	# -------------------------------------------------------
	/**
	 * Inserts test data set by implementation
	 */
	protected function setUp() : void {
		global $g_request, $AUTH_CURRENT_USER_ID;
		$vo_response = new ResponseHTTP();
		$g_request = $this->opo_request = new RequestHTTP($vo_response);
		
		// Set user_id to ensure change log contains user info used by some tests
		$AUTH_CURRENT_USER_ID = 1;	// 1=administrator
		define('__CA_APP_TYPE__', 'PROVIDENCE');

		// make sure there are no side-effects caused by lingering records
		if($this->opb_care_about_side_effects) {
			$this->checkRecordCounts();
		}
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
			$this->assertTrue(false, 'Inserting test data failed. API errors are: ' . join(' ', $o_itemservice->getErrors()));
		}

		$this->opa_record_map[$ps_table][] = $vn_return = array_shift($va_return);

		return $vn_return;
	}
	# -------------------------------------------------------
	/**
	 * @param BaseModel $instance Target instance
	 * @param array $pa_data Test data. Format of a single record is similar to the JSON format used in the
	 * Web Service API, only that it's a PHP array, obviously. In fact, we use the same code to insert the data.
	 *
	 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
	 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
	 * @return int the primary key of the newly created record
	 */
	protected function addTestRelationship($instance, $table, $id, $relationship_type, $effective_date=null) {
		$return = $instance->addRelationship($table, $id, $relationship_type, $effective_date);
		if(!$return) {
			$this->assertTrue(false, 'Inserting test data failed. API errors are: ' . join(' ', $instance->getErrors()));
		}

		$this->opa_record_map[$instance->tableName()][] = $return;

		return $return;
	}
	# -------------------------------------------------------
	protected function getRecordMap() {
		return $this->opa_record_map;
	}
	# -------------------------------------------------------
	protected function setRecordMapEntry($ps_table, $pn_id) {
		$this->opa_record_map[$ps_table][] = $pn_id;
	}
	# -------------------------------------------------------
	/**
	 * Delete all records we created for this test to avoid side effects with other tests
	 */
	protected function tearDown() : void {
		if($this->opb_care_about_side_effects) {
			foreach($this->opa_record_map as $vs_table => &$va_records) {
				$t_instance = Datamodel::getInstance($vs_table);
				// delete in reverse order so that we can properly
				// catch potential hierarchical relationships
				rsort($va_records);
				foreach($va_records as $vn_id) {
					if($t_instance->load($vn_id)) {
						$t_instance->setMode(ACCESS_WRITE);
						$t_instance->delete(true, array('hard' => true));
					}
				}
			}

			// check record counts again (make sure there are no lingering records)
			$this->checkRecordCounts();
		}
	}
	# -------------------------------------------------------
	private function checkRecordCounts() {
		// ensure there are no lingering records
		$o_db = new Db();
		foreach(self::$opa_valid_tables as $vs_table) {
			$qr_rows = $o_db->query("SELECT count(*) AS c FROM {$vs_table}");
			$qr_rows->nextRow();

			// these two are allowed to have hierarchy roots
			if(in_array($vs_table, array('ca_storage_locations', 'ca_places'))) {
				$vn_allowed_records = 1;
			} else {
				$vn_allowed_records = 0;
			}

			$this->assertEquals($vn_allowed_records, $qr_rows->get('c'), "Table {$vs_table} should be empty to avoid side effects between tests");
		}
	}
	# -------------------------------------------------------
}
