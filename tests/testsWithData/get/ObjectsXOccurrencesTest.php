<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ObjectsXOccurrencesTest.php
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
 * Class ObjectsXOccurrencesTest
 * Note: Requires testing profile!
 */
class ObjectsXOccurrencesTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_occurrence = null;
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
				'idno' => 'test_img'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_object_id);

		$vn_occurrence_id = $this->addTestRecord('ca_occurrences', array(
			'intrinsic_fields' => array(
				'type_id' => 'event',
				'idno' => 'foo',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Foo",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'depicts',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_occurrence_id);

		$vn_occurrence_id = $this->addTestRecord('ca_occurrences', array(
			'intrinsic_fields' => array(
				'type_id' => 'event',
				'idno' => 'bar',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Bar",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'used',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_occurrence_id);

		$this->opt_object = new ca_objects($vn_object_id);
	}
	# -------------------------------------------------------
	public function testInterstitialGet() {
		$va_rel_ids = $this->opt_object->get('ca_objects_x_occurrences.relation_id', array('returnAsArray' => true));
		$this->assertEquals(2, sizeof($va_rel_ids));

		$vn_rel_1 = array_shift($va_rel_ids);
		$t_rel = new ca_objects_x_occurrences($vn_rel_1);
		$this->assertEquals('Foo', $t_rel->get('ca_occurrences.preferred_labels'));
		$this->assertEquals('Foo (event)',
			$t_rel->get('ca_occurrences.preferred_labels', array('template' => '^ca_occurrences.preferred_labels (^ca_occurrences.type_id)'))
		);

		$vn_rel_2 = array_shift($va_rel_ids);
		$t_rel = new ca_objects_x_occurrences($vn_rel_2);
		$this->assertEquals('Bar', $t_rel->get('ca_occurrences.preferred_labels'));
		$this->assertEquals('Bar (event)',
			$t_rel->get('ca_occurrences.preferred_labels', array('template' => '^ca_occurrences.preferred_labels (^ca_occurrences.type_id)'))
		);
	}
	# -------------------------------------------------------
}
