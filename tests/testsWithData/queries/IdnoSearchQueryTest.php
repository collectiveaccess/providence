<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/IdnoSearchQueryTest.php
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
 * Class IdnoSearchQueryTest
 * Note: Requires testing profile!
 */
class IdnoSearchQueryTest extends AbstractSearchQueryTest {
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		// search subject table
		$this->setPrimaryTable('ca_objects');

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'D.99/2-38',
			),
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'D.99/2-39',
			),
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'D.99/0000001',
			),
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => '2016.1.15',
			),
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'CHS 34',
			),
		)));

		// search queries
		$this->setSearchQueries(array(
			'ca_objects.idno:"D.99/2-38"' => 1,
			'ca_objects.idno:"D.99/2-39"' => 1,
			'ca_objects.idno:"D.99/2-40"' => 0,
			'ca_objects.idno:"D.99/2-"' => 0,
			//'ca_objects.idno:D.99*' => 3, oops, this doesn't work in SqlSearch	
			'ca_objects.idno:2016*' => 1,

			'ca_objects.idno:"D.99"' => 3,
    		'ca_objects.idno:"D"' => 3,
    		'ca_objects.idno:"D.99/2"' => 2,

			'ca_objects.idno:"D.99/0000001"' => 1,
			'ca_objects.idno:"D.99/1"' => 1,

			'ca_objects.idno:"2016.1.15"' => 1,

			//'ca_objects.idno:"CHS 34"' => 1,
		));
	}
	# -------------------------------------------------------
}
