<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/CheckAccessGetWithTemplateTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * Class CheckAccessGetWithTemplateTest
 * Note: Requires testing profile!
 */
class CheckAccessGetWithTemplateTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_objects
	 */
	protected $opt_object;
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
				'type_id' => 'moving_image',
				'access' => 1,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test moving image"
				),
			)
		));

		$this->assertGreaterThan(0, $vn_object_id);
		$this->opt_object = new ca_objects($vn_object_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
				'lifespan' => '12/17/1989 -',
				'access' => 1,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Max",
					"middlename" => "",
					"surname" => "Power",
					"type_id" => "alt",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'bs',
				'access' => 0,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"middlename" => "",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'publisher',
						'effective_date' => '2014-2015',
						'source_info' => 'Homer'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);
	}
	# -------------------------------------------------------
	public function testGets() {

		$this->assertEquals(
			'Homer J. Simpson; Bart Simpson',
			$this->opt_object->getWithTemplate('<unit relativeTo="ca_entities">^ca_entities.preferred_labels</unit>')
		);

		$this->assertEquals(
			'Homer J. Simpson',
			$this->opt_object->getWithTemplate('<unit relativeTo="ca_entities">^ca_entities.preferred_labels</unit>', array('checkAccess' => array('1')))
		);

	}
	# -------------------------------------------------------
}
