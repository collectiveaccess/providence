<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/AttributeGetTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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
 * Class AttributeGetTest
 * Note: Requires testing profile!
 */
class AttributeGetTest extends BaseTestWithData {
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
		$vn_test_record = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'attributes' => array(
				// simple text
				'internal_notes' => array(
					array(
						'locale' => 'en_US',
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					),
					array(
						'locale' => 'en_US',
						'internal_notes' => 'More meat.'
					),
					array(
						'locale' => 'de_DE',
						'internal_notes' => 'Bacon ipsum dolor amet venison bresaola short ribs turkey ham hock beef ribs.'
					),
				),

				// text in a container
				'external_link' => array(
					array(
						'url_source' => 'My URL source'
					),
					array(
						'url_source' => 'Another URL source'
					),
				),

				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '10 in',
						'dimensions_weight' => '2 lbs',
						'measurement_notes' => 'foo',
					),
				),

				// Date
				'date' => array(
					array(
						'dc_dates_types' => 'created',
						'dates_value' => 'today'
					)
				),

				// Integer
				'integer_test' => array(
					array(
						'integer_test' => 23,
					),
					array(
						'integer_test' => 1984,
					)
				),

				// Currency
				'currency_test' => array(
					array(
						'currency_test' => '$100',
					),
				),

				// Georeference
				'georeference' => array(
					array(
						'georeference' => '1600 Amphitheatre Parkway, Mountain View, CA',
					),
				),

				// InformationService/TGN
				'tgn' => array(
					array(
						'tgn' => 'http://vocab.getty.edu/tgn/7015849',
					),
				),

				// InformationService/Wikipedia
				'wikipedia' => array(
					array(
						'wikipedia' => 'http://en.wikipedia.org/wiki/Aaron_Burr'
					),
				),

				// InformationService/Container - Wikipedia and ULAN
				'informationservice' => array(
					array(
						'wiki' => 'http://en.wikipedia.org/wiki/Aaron_Burr',
						'ulan_container' => 'http://vocab.getty.edu/ulan/500024253'
					),
				),
			)
		));

		$this->assertGreaterThan(0, $vn_test_record);

		$this->opt_object = new ca_objects($vn_test_record);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_objects.date.dc_dates_types', array('returnIdno' => true));
		$this->assertEquals('created', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.type_id', array('convertCodesToDisplayText' => true));
		$this->assertEquals('Image', $vm_ret);

		// there are two internal notes but we assume that only the current UI locale is returned, unless we explicitly say otherwise
		$vm_ret = $this->opt_object->get('ca_objects.internal_notes');
		$this->assertEquals("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.;More meat.", $vm_ret);

		$vm_ret = $this->opt_object->get('internal_notes');
		$this->assertEquals("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.;More meat.", $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.external_link.url_source');
		$this->assertEquals("My URL source;Another URL source", $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.dimensions.dimensions_length');
		$this->assertEquals("10 in", $vm_ret);
		$vm_ret = $this->opt_object->get('ca_objects.dimensions.dimensions_weight');
		$this->assertEquals("2 lb", $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.integer_test', array('delimiter' => ' / '));
		$this->assertEquals("23 / 1984", $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.currency_test');
		$this->assertEquals("$ 100.00", $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.georeference');
		$this->assertRegExp("/^1600 Amphitheatre Parkway, Mountain View, CA \[[\d\.\,\-]+\]/", $vm_ret);

		// This is how we fetch the bundle preview for containers:
		$vs_template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /foo/'>^ca_objects.dimensions.dimensions_length</if></unit>";
		$vm_ret = $this->opt_object->getAttributesForDisplay('dimensions', $vs_template);
		$this->assertEquals('10 in', $vm_ret);

		$vs_template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /foo/'>^dimensions_length</if></unit>";
		$vm_ret = $this->opt_object->getAttributesForDisplay('dimensions', $vs_template);
		$this->assertEquals('10 in', $vm_ret);

		// shouldn't return anything because the expression is false
		$vs_template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /bar/'>^ca_objects.dimensions.dimensions_length</if></unit>";
		$vm_ret = $this->opt_object->getAttributesForDisplay('dimensions', $vs_template);
		$this->assertEmpty($vm_ret);

		// 'flat' informationservice attribues
		$this->assertEquals('Coney Island', $this->opt_object->get('ca_objects.tgn'));
		$this->assertContains('Aaron Burr', $this->opt_object->get('ca_objects.wikipedia'));
		// subfield notation for "extra info"
		$this->assertContains('Burr killed his political rival Alexander Hamilton', $this->opt_object->get('ca_objects.wikipedia.abstract'));
		$this->assertEquals('40.5667', $this->opt_object->get('ca_objects.tgn.lat'));

		// informationservice attributes in container
		$this->assertEquals('[500024253] Haring, Keith (Persons, Artists) - American painter, muralist, and cartoonist, 1958-1990', $this->opt_object->get('ca_objects.informationservice.ulan_container'));
		$this->assertContains('Aaron Burr', $this->opt_object->get('ca_objects.informationservice.wiki'));
		$this->assertContains('Burr killed his political rival Alexander Hamilton', $this->opt_object->get('ca_objects.informationservice.wiki.abstract'));
	}
	# -------------------------------------------------------
	public function testGetCounts() {
		$vm_ret = $this->opt_object->get('ca_objects.internal_notes._count');
		$this->assertEquals(2, $vm_ret);
		
		$vm_ret = $this->opt_object->get('ca_objects.internal_notes._count', ['returnAsArray' => true]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals(2, $vm_ret[0]);
		
		$vm_ret = $this->opt_object->get('ca_objects.internal_notes', ['returnAsCount' => true]);
		$this->assertEquals(2, $vm_ret);
	}
	# -------------------------------------------------------
}
