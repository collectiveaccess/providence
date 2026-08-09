<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ValueHistoryGetTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * Class HierarchyGetTest
 * Note: Requires testing profile!
 */
class ValueHistoryGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	protected $test_item = null;
	
	# -------------------------------------------------------
	protected function setUp() : void {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$test_item_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
				'idno' => 'MI.1'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test moving image",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "An alternate label",
				),
			),
			'attributes' => array(
				'description' => array(
					array(
						'description' => 'cordage'
					),
					array(
						'description' => 'blown'
					)
				),
			)
		));

		$this->assertGreaterThan(0, $test_item_id);
		$this->test_item = new ca_objects($test_item_id);
		
		foreach(['MV.2000', 'FM.3000', 'RA.342'] as $idno) {
			$this->test_item->set('idno', $idno);
			$this->test_item->update();
		}
		
		foreach(['Label 1', 'Label 2', 'Label 3'] as $label) {
			$this->test_item->replaceLabel(['name' => $label], 'en_US', null, true);
		}
		foreach(['Alternate Label 1', 'Alternate Label 2', 'Alternate Label 3'] as $label) {
			$this->test_item->replaceLabel(['name' => $label], 'en_US', null, false);
		}
		
		
		foreach(['Copper', 'Nickel', 'Cobalt'] as $description) {
			$this->test_item->replaceAttribute(['description' => $description], 'description');
			$this->test_item->update();
		}
	}
	# -------------------------------------------------------
	public function testIntrinsicValueHistoryAsString() {
		$history = $this->test_item->get('ca_objects.valuehistory.idno', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('RA.342;FM.3000;MV.2000;MI.1', $history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('FM.3000', $history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_2.idno', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('MV.2000', $history);
	
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno.log_datetime', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertIsNumeric($history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno.user_name', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('administrator', $history);
	}
	# -------------------------------------------------------
	public function testIntrinsicValueHistoryAsArray() {
		$history = $this->test_item->get('ca_objects.valuehistory.idno', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(4, sizeof($history));
		$this->assertEquals('RA.342', array_shift($history));
		$this->assertEquals('FM.3000', array_shift($history));
		$this->assertEquals('MV.2000', array_shift($history));
		$this->assertEquals('MI.1', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue.idno', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('FM.3000', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('FM.3000', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_2.idno', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('MV.2000', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno.log_datetime', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertIsNumeric($history[0]);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.idno.user_name', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('administrator', $history[0]);
		
		$history = $this->test_item->get('ca_objects.valuehistory.idno.user_name', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(4, sizeof($history));
		$this->assertEquals('administrator', $history[0]);
		$this->assertEquals('administrator', $history[1]);
		$this->assertEquals('administrator', $history[2]);
		$this->assertEquals('administrator', $history[3]);
	}
	# -------------------------------------------------------
	public function testIntrinsicValueHistoryWithStructure() {
		$history = $this->test_item->get('ca_objects.valuehistory.idno', ['returnAsArray' => true, 'returnWithStructure' => true]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$history = array_shift($history);
		
		$h = array_shift($history);
		$this->assertEquals(8, sizeof($h));
 		$this->assertEquals('RA.342', $h['idno']);
 		$this->assertEquals('administrator', $h['user_name']);
 		$this->assertIsNumeric($h['log_datetime']);
 		
		$h = array_shift($history);
		$this->assertEquals(8, sizeof($h));
 		$this->assertEquals('FM.3000', $h['idno']);
 		$this->assertEquals('administrator', $h['user_name']);
 		$this->assertIsNumeric($h['log_datetime']);
	}
	# -------------------------------------------------------
	public function testPreferredLabelsValueHistoryAsString() {
		foreach(['preferred_labels.name', 'preferred_labels'] as $f) {
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Label 3;Label 2;Label 1;My test moving image', $history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Label 2', $history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_2.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Label 1', $history);
		
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.log_datetime", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertIsNumeric($history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.user_name", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('administrator', $history);
		}
	}
	# -------------------------------------------------------
	public function testPreferredLabelsValueHistoryAsArray() {
		foreach(['preferred_labels.name', 'preferred_labels'] as $f) {
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(4, sizeof($history));
			$this->assertEquals('Label 3', array_shift($history));
			$this->assertEquals('Label 2', array_shift($history));
			$this->assertEquals('Label 1', array_shift($history));
			$this->assertEquals('My test moving image', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Label 2', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Label 2', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_2.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Label 1', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.log_datetime", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertIsNumeric($history[0]);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.user_name", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('administrator', $history[0]);
			
			$history = $this->test_item->get('ca_objects.valuehistory.preferred_labels.name.user_name', ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(4, sizeof($history));
			
			$this->assertEquals('administrator', $history[0]);
			$this->assertEquals('administrator', $history[1]);
			$this->assertEquals('administrator', $history[2]);
			$this->assertEquals('administrator', $history[3]);
		}
	}
	# -------------------------------------------------------
	public function testPreferredLabelsValueHistoryWithStructure() {
		foreach(['ca_objects.valuehistory.preferred_labels.name', 'ca_objects.valuehistory.preferred_labels'] as $f) {
			$history = $this->test_item->get($f, ['returnAsArray' => true, 'returnWithStructure' => true]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$history = array_shift($history);
			
			$h = array_shift($history);
			$this->assertEquals(8, sizeof($h));
			$this->assertEquals('Label 3', $h['name']);
			$this->assertEquals('administrator', $h['user_name']);
			$this->assertIsNumeric($h['log_datetime']);
			
			$h = array_shift($history);
			$this->assertEquals(8, sizeof($h));
			$this->assertEquals('Label 2', $h['name']);
			$this->assertEquals('administrator', $h['user_name']);
			$this->assertIsNumeric($h['log_datetime']);
		}
	}
	# -------------------------------------------------------
	public function testNonPreferredLabelsValueHistoryAsString() {
		foreach(['nonpreferred_labels.name', 'nonpreferred_labels'] as $f) {
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Alternate Label 3;Alternate Label 2;Alternate Label 1;An alternate label', $history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Alternate Label 2', $history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_2.{$f}", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('Alternate Label 1', $history);
		
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.log_datetime", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertIsNumeric($history);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.user_name", ['returnAsArray' => false, 'returnWithStructure' => false]);
			$this->assertTrue(is_string($history));
			$this->assertEquals('administrator', $history);
		}
	}
	# -------------------------------------------------------
	public function testNonPreferredLabelsValueHistoryAsArray() {
		foreach(['nonpreferred_labels.name', 'nonpreferred_labels'] as $f) {
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(4, sizeof($history));
			$this->assertEquals('Alternate Label 3', array_shift($history));
			$this->assertEquals('Alternate Label 2', array_shift($history));
			$this->assertEquals('Alternate Label 1', array_shift($history));
			$this->assertEquals('An alternate label', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Alternate Label 2', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Alternate Label 2', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_2.{$f}", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('Alternate Label 1', array_shift($history));
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.log_datetime", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertIsNumeric($history[0]);
			
			$history = $this->test_item->get("ca_objects.previousvalue_1.{$f}.user_name", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$this->assertEquals('administrator', $history[0]);
			
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}.user_name", ['returnAsArray' => true, 'returnWithStructure' => false]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(4, sizeof($history));
			
			$this->assertEquals('administrator', $history[0]);
			$this->assertEquals('administrator', $history[1]);
			$this->assertEquals('administrator', $history[2]);
			$this->assertEquals('administrator', $history[3]);
		}
	}
	# -------------------------------------------------------
	public function testNonPreferredLabelsValueHistoryWithStructure() {
		foreach(['nonpreferred_labels.name', 'nonpreferred_labels'] as $f) {
			$history = $this->test_item->get("ca_objects.valuehistory.{$f}", ['returnAsArray' => true, 'returnWithStructure' => true]);
			$this->assertTrue(is_array($history));
			$this->assertEquals(1, sizeof($history));
			$history = array_shift($history);
			$this->assertTrue(is_array($history));
			$this->assertEquals(4, sizeof($history));
			
			$h = array_shift($history);
			$this->assertEquals(8, sizeof($h));
			$this->assertEquals('Alternate Label 3', $h['name']);
			$this->assertEquals('administrator', $h['user_name']);
			$this->assertIsNumeric($h['log_datetime']);
			
			$h = array_shift($history);
			$this->assertEquals(8, sizeof($h));
			$this->assertEquals('Alternate Label 2', $h['name']);
			$this->assertEquals('administrator', $h['user_name']);
			$this->assertIsNumeric($h['log_datetime']);
		}
	}
	# -------------------------------------------------------
	public function testAttributeValueHistoryAsString() {
		$history = $this->test_item->get('ca_objects.valuehistory.description', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('Cobalt;Nickel;Copper;blown;cordage', $history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.description', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('Nickel', $history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_2.description', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('Copper', $history);
	
		$history = $this->test_item->get('ca_objects.previousvalue_1.description.log_datetime', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertIsNumeric($history);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.description.user_name', ['returnAsArray' => false, 'returnWithStructure' => false]);
		$this->assertTrue(is_string($history));
		$this->assertEquals('administrator', $history);
	}
	# -------------------------------------------------------
	public function testAttributeValueHistoryAsArray() {
		$history = $this->test_item->get('ca_objects.valuehistory.description', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(5, sizeof($history));
		$this->assertEquals('Cobalt', array_shift($history));
		$this->assertEquals('Nickel', array_shift($history));
		$this->assertEquals('Copper', array_shift($history));
		$this->assertEquals('blown', array_shift($history));
		$this->assertEquals('cordage', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue.description', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('Nickel', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.description', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('Nickel', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_2.description', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('Copper', array_shift($history));
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.description.log_datetime', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertIsNumeric($history[0]);
		
		$history = $this->test_item->get('ca_objects.previousvalue_1.description.user_name', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$this->assertEquals('administrator', $history[0]);
		
		$history = $this->test_item->get('ca_objects.valuehistory.description.user_name', ['returnAsArray' => true, 'returnWithStructure' => false]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(5, sizeof($history));
		$this->assertEquals('administrator', $history[0]);
		$this->assertEquals('administrator', $history[1]);
		$this->assertEquals('administrator', $history[2]);
		$this->assertEquals('administrator', $history[3]);
		$this->assertEquals('administrator', $history[4]);
	}
	# -------------------------------------------------------
	public function testAttributeValueHistoryWithStructure() {
		$history = $this->test_item->get('ca_objects.valuehistory.description', ['returnAsArray' => true, 'returnWithStructure' => true]);
		$this->assertTrue(is_array($history));
		$this->assertEquals(1, sizeof($history));
		$history = array_shift($history);
		
		$h = array_shift($history);
		$this->assertEquals(9, sizeof($h));
 		$this->assertEquals('Cobalt', $h['description']);
 		$this->assertEquals('administrator', $h['user_name']);
 		$this->assertIsNumeric($h['log_datetime']);
 		$this->assertIsNumeric($h['value_id']);
 		
		$h = array_shift($history);
		$this->assertEquals(9, sizeof($h));
 		$this->assertEquals('Nickel', $h['description']);
 		$this->assertEquals('administrator', $h['user_name']);
 		$this->assertIsNumeric($h['log_datetime']);
 		$this->assertIsNumeric($h['value_id']);
	}
	# -------------------------------------------------------
	protected function tearDown() : void {
		parent::tearDown();
	}
	# -------------------------------------------------------
}
