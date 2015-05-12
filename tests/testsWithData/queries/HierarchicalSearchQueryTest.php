<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/HierarchicalSearchQueryTest.php
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
 * Class HierarchicalSearchQueryTest
 * Note: Requires testing profile!
 */
class HierarchicalSearchQueryTest extends AbstractSearchQueryTest {
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		// search subject table
		$this->setPrimaryTable('ca_storage_locations');

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_building = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'building',
				'parent_id' => 1, // add directly under root
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Building",
				),
			),
		));

		$vn_floor = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'floor',
				'parent_id' => $vn_building,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Floor",
				),
			),
		));

		$vn_room = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'room',
				'parent_id' => $vn_floor,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Room",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_building);
		$this->assertGreaterThan(0, $vn_floor);
		$this->assertGreaterThan(0, $vn_room);

		// search queries
		$this->setSearchQueries(array(
			'Test' => 3,
			'ca_storage_location_labels.name:"Test"' => 3,
			'ca_storage_location_labels.name:"Building"' => 3, // hierarchical indexing in default indexing conf kicks in
			'ca_storage_location_labels.name:"Room"' => 1,
		));
	}
	# -------------------------------------------------------
}
