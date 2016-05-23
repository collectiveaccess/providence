<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ObjectsXLocationsTest.php
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
 * Class ObjectsXLocationsTest
 * Note: Requires testing profile!
 */
class ObjectsXLocationsTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_objects
	 */
	private $opt_object = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'test'
			),
		));

		$this->assertGreaterThan(0, $vn_object_id);

		$vn_room_id = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'room',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A Room",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_room_id);

		$vn_cabinet_id = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'cabinet',
				'parent_id' => $vn_room_id,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My Cabinet",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'related',
						'effective_date' => 'January 28 1985'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_cabinet_id);

		$vn_drawer_id = $this->addTestRecord('ca_storage_locations', array(
			'intrinsic_fields' => array(
				'type_id' => 'shelf',
				'parent_id' => $vn_room_id,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My Shelf",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'related',
						'effective_date' => '2015'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_drawer_id);

		$this->opt_object = new ca_objects($vn_object_id);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_storage_locations.hierarchy.preferred_labels.name', array(
			'delimiter' => '; ',
			'hierarchyDelimiter' => ' > '
		));
		$this->assertEquals('A Room > My Cabinet; A Room > My Shelf', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_storage_locations', array(
			'showCurrentOnly' => true
		));
		$this->assertEquals('My Shelf', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_storage_locations.hierarchy.preferred_labels.name', array(
			'delimiter' => '; ',
			'hierarchyDelimiter' => ' > ',
			'showCurrentOnly' => true
		));
		$this->assertEquals('A Room > My Shelf', $vm_ret);

		$vm_ret = $this->opt_object->get("ca_objects_x_storage_locations.effective_date");
		$this->assertEquals('January 28 1985;2015', $vm_ret);

		$vm_ret = $this->opt_object->get("ca_objects_x_storage_locations.effective_date", array('getDirectDate' => true));
		$this->assertEquals($vm_ret, '1985.01280000000000000000;2015.01010000000000000000');

		// try legacy version of same option
		$vm_ret = $this->opt_object->get("ca_objects_x_storage_locations.effective_date", array('GET_DIRECT_DATE' => true));
		$this->assertEquals($vm_ret, '1985.01280000000000000000;2015.01010000000000000000');
	}
	# -------------------------------------------------------
}
