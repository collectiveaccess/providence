<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ForumThread294947Test.php
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
 * Class ForumThread294947Test
 * Note: Requires testing profile!
 * @see http://www.collectiveaccess.org/support/forum/index.php?p=/discussion/294947/i-need-some-help-getting-data-in-a-report#latest
 */
class ForumThread294947Test extends BaseTestWithData {
	# -------------------------------------------------------
	protected $opn_location_id = null;
	protected $opn_object_id = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_test_object = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A test image",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_test_object);

		$vn_test_record = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'cabinet',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test cabinet",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_test_object,
						'type_id' => 'related',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_test_record);

		$this->opn_location_id = $vn_test_record;
		$this->opn_object_id = $vn_test_object;
	}
	# -------------------------------------------------------
	public function testGets() {
		$vo_result = caMakeSearchResult('ca_storage_locations', array($this->opn_location_id));

		while($vo_result->nextHit()) {
			$this->assertEquals(array($this->opn_object_id), $vo_result->get('ca_objects.object_id', array('returnAsArray' => true)));
			$this->assertEquals(array($this->opn_object_id), $vo_result->get('ca_objects.object_id', array('returnWithStructure' => true)));
			$this->assertEquals("{$this->opn_object_id}", $vo_result->get('ca_objects.object_id'));
		}
	}
	# -------------------------------------------------------
}
