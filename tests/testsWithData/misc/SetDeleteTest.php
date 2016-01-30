<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/SimpleGetTest.php
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

require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');

/**
 * Class SimpleGetTest
 * Note: Requires testing profile!
 */
class SetDeleteTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_sets
	 */
	private $opt_set = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();


		for($i=0; $i<50; $i++) {
			$vn_test_record = $this->addTestRecord('ca_objects', array(
				'intrinsic_fields' => array(
					'type_id' => 'image',
					'idno' => $i
				),
			));

			$this->assertGreaterThan(0, $vn_test_record);
		}

		$vn_set_id = $this->addTestRecord('ca_sets', array(
			'intrinsic_fields' => array(
				'set_code' => 'batch_insert_delete_test',
				'table_num' => 57, // objects
				'type_id' => 'user'
			),
		));

		$this->assertGreaterThan(0, $vn_set_id);
		$this->opt_set = new ca_sets($vn_set_id);
	}
	# -------------------------------------------------------
	public function testCreateDeleteSet() {
		$o_db = new Db();
		$qr_objects = $o_db->query('select object_id from ca_objects where deleted=0');
		$va_object_ids = $qr_objects->getAllFieldValues('object_id');

		//$t = new Timer();
		$this->opt_set->addItems($va_object_ids);
		//var_dump($t->getTime());

		$this->assertSame(sizeof($va_object_ids), $this->opt_set->getItemCount());

		$this->opt_set->setMode(ACCESS_WRITE);
		//$t = new Timer();
		$this->opt_set->delete(true);
		//var_dump($t->getTime());
	}
	# -------------------------------------------------------
}
