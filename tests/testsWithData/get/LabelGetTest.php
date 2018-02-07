<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/LabelGetTest.php
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
 * Class LabelGetTest
 * Note: Requires testing profile!
 */
class LabelGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
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
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
				array(
					"locale" => "de_DE",
					"name" => "Testbild",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Alternative title for test image",
				),
				array(
					"locale" => "en_US",
					"name" => "Even more alternative title for test image",
				),
				array(
					"locale" => "de_DE",
					"name" => "Alternativer Titel für Testbild",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_test_record);

		$this->opt_object = new ca_objects($vn_test_record);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_objects.type_id', array('convertCodesToDisplayText' => true));
		$this->assertEquals('Image', $vm_ret);

		// it should get the en_US title here because at this point this is our "UI locale"
		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels');
		$this->assertEquals('My test image', $vm_ret);
		
		// it should get the en_US title here because at this point this is our "UI locale"
		$vm_ret = $this->opt_object->get('ca_object_labels.name');
		$this->assertEquals('My test image', $vm_ret);

		// extract de_DE locale from array
		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels', array('returnWithStructure' => true, 'returnAllLocales' => true));
		$this->assertInternalType('array', $vm_ret);

		$va_vals = array_shift(array_shift(caExtractValuesByLocale(array('preferred' => array('de_DE')),$vm_ret)));
		$this->assertEquals('Testbild', $va_vals['name']);

		// it should get the en_US title here because at this point this is our "UI locale"
		$vm_ret = $this->opt_object->get('ca_objects.nonpreferred_labels');
		$this->assertEquals('Alternative title for test image;Even more alternative title for test image', $vm_ret);

		// extract de_DE locale from array
		$vm_ret = $this->opt_object->get('ca_objects.nonpreferred_labels', array('returnWithStructure' => true, 'returnAllLocales' => true));
		$this->assertInternalType('array', $vm_ret);

		$va_vals = array_shift(array_shift(caExtractValuesByLocale(array('preferred' => array('de_DE')),$vm_ret)));
		$this->assertEquals('Alternativer Titel für Testbild', $va_vals['name']);
	}
	# -------------------------------------------------------
	public function testGetCounts() {
		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels._count');
		$this->assertEquals(1, $vm_ret);
		
		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels._count', ['returnAsArray' => true]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals(1, $vm_ret[0]);
		
		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels', ['returnAsCount' => true]);
		$this->assertEquals(1, $vm_ret);
		
		$vm_ret = $this->opt_object->get('ca_objects.nonpreferred_labels._count');
		$this->assertEquals(2, $vm_ret);
		
		$vm_ret = $this->opt_object->get('ca_objects.nonpreferred_labels._count', ['returnAsArray' => true]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals(2, $vm_ret[0]);
		
		$vm_ret = $this->opt_object->get('ca_objects.nonpreferred_labels', ['returnAsCount' => true]);
		$this->assertEquals(2, $vm_ret);
	}
	# -------------------------------------------------------
}
