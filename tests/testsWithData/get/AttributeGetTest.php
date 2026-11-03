<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/AttributeGetTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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
 use PHPUnit\Framework\TestCase;

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
	protected function setUp() : void {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$test_record = $this->addTestRecord('ca_objects', array(
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
					array(
						'dimensions_length' => '5 ft',
						'dimensions_weight' => '10 gm.',
						'measurement_notes' => 'meow',
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
						'tgn' => 'https://vocab.getty.edu/tgn/7015849',
					),
				),

				// InformationService/Wikipedia
				'wikipedia' => array(
					array(
						'wikipedia' => 'https://en.wikipedia.org/wiki/Aaron_Burr'
					),
				),

 				// InformationService/Container - Wikipedia and ULAN
				'informationservice' => array(
					array(
						'wiki' => 'https://en.wikipedia.org/wiki/Aaron_Burr',
						'ulan_container' => 'https://vocab.getty.edu/ulan/500024253'
					),
				),
			)
		));

		$this->assertGreaterThan(0, $test_record);

		$this->opt_object = new ca_objects($test_record);
	}
	# -------------------------------------------------------
	public function testGetListAsIdno() {
		$ret = $this->opt_object->get('ca_objects.date.dc_dates_types', array('returnIdno' => true));
		$this->assertEquals('created', $ret);
	}
	# -------------------------------------------------------
	public function testGetTypeForDisplay() {
		$ret = $this->opt_object->get('ca_objects.type_id', array('convertCodesToDisplayText' => true));
		$this->assertEquals('Image', $ret);
	}

	# -------------------------------------------------------
	public function testGetText() {
		// there are two internal notes but we assume that only the current UI locale is returned, unless we explicitly say otherwise
		$ret = $this->opt_object->get('ca_objects.internal_notes');
		$this->assertEquals("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.;More meat.", $ret);

		$ret = $this->opt_object->get('internal_notes');
		$this->assertEquals("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.;More meat.", $ret);
	}
	# -------------------------------------------------------
	public function testGetUrlInContainer() {
		$ret = $this->opt_object->get('ca_objects.external_link.url_source');
		$this->assertEquals("My URL source;Another URL source", $ret);
	}
	# -------------------------------------------------------
	public function testGetLength() {
		$ret = $this->opt_object->get('ca_objects.dimensions.dimensions_length', ['returnAsArray' => true]);
		$this->assertEquals("10 in", $ret[0]);
		$this->assertEquals("5 ft", $ret[1]);
	}
	# -------------------------------------------------------
	public function testGetWeight() {
		$ret = $this->opt_object->get('ca_objects.dimensions.dimensions_weight', ['returnAsArray' => true]);
		$this->assertEquals("2 lb", $ret[0]);
		$this->assertEquals("10 g", $ret[1]);
	}
	# -------------------------------------------------------
	public function testGetInteger() {
		$ret = $this->opt_object->get('ca_objects.integer_test', array('delimiter' => ' / '));
		$this->assertEquals("23 / 1984", $ret);
	}
	# -------------------------------------------------------
	public function testGetCurrency() {
		$ret = $this->opt_object->get('ca_objects.currency_test');
		$this->assertEquals("$100.00", $ret);
	}
	# -------------------------------------------------------
	public function testGetAttriburesForDisplay() {
		// This is how we fetch the bundle preview for containers:
		$template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /foo/'>^ca_objects.dimensions.dimensions_length</if></unit>";
		$ret = $this->opt_object->getAttributesForDisplay('dimensions', $template);
		$this->assertEquals('10 in', $ret);

		$template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /foo/'>^dimensions_length</if></unit>";
		$ret = $this->opt_object->getAttributesForDisplay('dimensions', $template);
		$this->assertEquals('10 in', $ret);

		// shouldn't return anything because the expression is false
		$template = "<unit relativeTo='ca_objects.dimensions'><if rule='^measurement_notes =~ /bar/'>^ca_objects.dimensions.dimensions_length</if></unit>";
		$ret = $this->opt_object->getAttributesForDisplay('dimensions', $template);
		$this->assertEmpty($ret);
	}
	# -------------------------------------------------------
	public function testGetTGNTextValue() {
		// 'flat' informationservice attribues
		$this->assertEquals('Coney Island', $this->opt_object->get('ca_objects.tgn'));
	}
	# -------------------------------------------------------
	public function testGetTGNExtendedInfo() {
		// subfield notation for "extra info"
		$this->assertEquals('40.5667', $this->opt_object->get('ca_objects.tgn.lat'));
	}
	# -------------------------------------------------------
	public function testGetULANInContainer() {
		// informationservice attributes in container
		$this->assertEquals('[500024253] Haring, Keith (Persons) - American painter, muralist, and cartoonist, 1958-1990', $this->opt_object->get('ca_objects.informationservice.ulan_container'));
	}
	# -------------------------------------------------------
	public function testGetWikipedia() {
		$this->assertStringContainsString('Aaron Burr', $this->opt_object->get('ca_objects.wikipedia'));
		$this->assertStringContainsString('Aaron Burr', $this->opt_object->get('ca_objects.informationservice.wiki'));
        
        $expected_abstract_wikipedia_burr = 'Aaron Burr Jr.';
        $this->assertStringContainsString($expected_abstract_wikipedia_burr, $this->opt_object->get('ca_objects.wikipedia.abstract'));
		$this->assertStringContainsString($expected_abstract_wikipedia_burr, $this->opt_object->get('ca_objects.informationservice.wiki.abstract'));
	}
	# -------------------------------------------------------
	public function testGetCounts() {
		$ret = $this->opt_object->get('ca_objects.internal_notes._count');
		$this->assertEquals(2, $ret);
		
		$ret = $this->opt_object->get('ca_objects.internal_notes._count', ['returnAsArray' => true]);
		$this->assertIsArray($ret);
		$this->assertCount(1, $ret);
		$this->assertEquals(2, $ret[0]);
		
		$ret = $this->opt_object->get('ca_objects.internal_notes', ['returnAsCount' => true]);
		$this->assertEquals(2, $ret);
	}
	# -------------------------------------------------------
}
