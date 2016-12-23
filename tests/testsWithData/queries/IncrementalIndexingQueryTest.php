<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/IncrementalIndexingQueryTest.php
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

require_once(__CA_BASE_DIR__ . '/tests/testsWithData/AbstractSearchQueryTest.php');

/**
 * Class IncrementalIndexingQueryTest
 * Note: Requires testing profile!
 */
class IncrementalIndexingQueryTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_objects
	 */
	protected $opt_object = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$this->assertGreaterThan(0, $vn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'asdf'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "foo",
				),
			),
		)));

		$this->opt_object = new ca_objects($vn_object_id);
	}
	# -------------------------------------------------------
	public function testReplaceLabel() {
		$o_search = caGetSearchInstance('ca_objects');
		$o_result = $o_search->search('foo');
		$this->assertEquals(1, $o_result->numHits(), 'foo should be indexed');
		$o_result = $o_search->search('bar');
		$this->assertEquals(0, $o_result->numHits(), 'bar should not be indexed yet');

		$this->opt_object->removeAllLabels();
		$this->opt_object->addLabel(array(
			'name' => 'bar',
			'name_sort' => 'bar'
		), 1, null, true);

		$o_search = caGetSearchInstance('ca_objects');
		$o_result = $o_search->search('foo');
		$this->assertEquals(0, $o_result->numHits(), 'foo should not be indexed anymore');
		$o_result = $o_search->search('bar');
		$this->assertEquals(1, $o_result->numHits(), 'bar should now be indexed instead');
	}
	# -------------------------------------------------------
	public function testReplaceIdno() {
		$o_search = caGetSearchInstance('ca_objects');
		$o_result = $o_search->search('asdf');
		$this->assertEquals(1, $o_result->numHits(), 'asdf should be indexed');
		$o_result = $o_search->search('fdsa');
		$this->assertEquals(0, $o_result->numHits(), 'fdsa should not be indexed yet');

		$this->opt_object->set('idno', 'fdsa');
		$this->opt_object->setMode(ACCESS_WRITE);
		$this->opt_object->update();
		
		$this->opt_object = null;	// force search indexer to write on destruction of model instance
		
		$o_search = caGetSearchInstance('ca_objects');
		$o_result = $o_search->search('asdf');
		$this->assertEquals(0, $o_result->numHits(), 'asdf should not be indexed anymore');
		$o_result = $o_search->search('fdsa');
		$this->assertEquals(1, $o_result->numHits(), 'fdsa should now be indexed instead');
	}
	# -------------------------------------------------------
	public function testDelete() {
		$va_objects = array();

		$this->assertGreaterThan(0, $va_objects[] = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'dataset',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test dataset",
				),
			),
		)));

		$this->assertGreaterThan(0, $va_objects[] = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'physical_object',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test physical object",
				),
			),
		)));

		foreach($va_objects as $vn_object_id) {
			$t_object = new ca_objects($vn_object_id);
			$t_object->setMode(ACCESS_WRITE);
			$t_object->delete(true, array('hard' => true));
		}

		$o_search = caGetSearchInstance('ca_objects');
		$o_result = $o_search->search('dataset');
		$this->assertEquals(0, $o_result->numHits(), 'dataset should not be indexed anymore');
		$o_result = $o_search->search('physical');
		$this->assertEquals(0, $o_result->numHits(), 'physical object should not be indexed anymore');
	}
}
