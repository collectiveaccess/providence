<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/DedupTest.php
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
 * Class DedupTest
 * Note: Requires testing profile!
 */
class DedupTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;

	/** @var ca_entities */
	private $opt_entity_1 = null;
	/** @var ca_entities */
	private $opt_entity_2 = null;
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
				'idno' => 'TEST.1'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_object_id);

		$vn_rel_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'TEST.2'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Another image",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_rel_object_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
				'lifespan' => '12/17/1989 -'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
			'attributes' => array(
				'address' => array(
					array(
						'address1' => 'Foo Bar',
						'city' => 'Springfield',
					)
				),
				'internal_notes' => array(
					array(
						'locale' => 'en_US',
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					)
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					),
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);
		$this->opt_entity_1 = new ca_entities($vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
				'lifespan' => '12/17/1989 -'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
			'attributes' => array(
				'address' => array(
					array(
						'address1' => 'Foo Bar',
						'city' => 'Springfield',
					)
				),
				'internal_notes' => array(
					array(
						'locale' => 'en_US',
						'internal_notes' => 'Bacon'
					),
					array(
						'locale' => 'en_US',
						'internal_notes' => 'Steak'
					),
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					),
					array(
						'object_id' => $vn_rel_object_id,
						'type_id' => 'publisher',
						'effective_date' => '2014',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);
		$this->opt_entity_2 = new ca_entities($vn_entity_id);

		// this record looks the same but is not actually a dupe -- has no lifespan!
		// we don't really care about this record so it's not assigned to a class property
		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

	}
	# -------------------------------------------------------
	public function testDupe() {
		$va_dupe_list = ca_entities::listPotentialDupes();
		$this->assertEquals(1, sizeof($va_dupe_list), 'We expect two of the entered entities to be duplicates');

		$this->assertEquals(1, sizeof($this->opt_entity_1->getRelatedItems('ca_objects')));
		$this->assertEquals(2, sizeof($this->opt_entity_2->getRelatedItems('ca_objects')));

		$this->assertEquals(2, sizeof($this->opt_entity_1->getAttributes(['noCache' => true])));
		$this->assertEquals(3, sizeof($this->opt_entity_2->getAttributes(['noCache' => true])));

		foreach($va_dupe_list as $va_dupes) {
			if(sizeof($va_dupes) > 1) {
				$vn_entity_id = ca_entities::mergeRecords($va_dupes);
			}
			//$this->assertGreaterThan(0, $vn_entity_id);
		}

		// one relationship was effectively a dupe so it should still be there. only one should have been moved
		$this->assertEquals(2, sizeof($this->opt_entity_1->getRelatedItems('ca_objects')));
		$this->assertEquals(1, sizeof($this->opt_entity_2->getRelatedItems('ca_objects')));

		// of the 5 total attributes, one is a dupe
		$this->assertEquals(4, sizeof($this->opt_entity_1->getAttributes(['noCache' => true])));
		$this->assertEquals(0, sizeof($this->opt_entity_2->getAttributes(['noCache' => true])));
	}
	# -------------------------------------------------------
	/**
	 * @expectedException Exception
	 */
	public function testInvalidMergeListString() {
		ca_entities::mergeRecords(['foo']);
	}
	# -------------------------------------------------------
	/**
	 * @expectedException Exception
	 */
	public function testInvalidMergeListInvalidID() {
		ca_entities::mergeRecords([time()]);
	}
	# -------------------------------------------------------
}
