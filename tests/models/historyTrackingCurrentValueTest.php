<?php
/** ---------------------------------------------------------------------
 * tests/models/historyTrackingCurrentValueTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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

require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_LIB_DIR__.'/Service/ItemService.php');
require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');

class historyTrackingCurrentValueTest extends BaseTestWithData {
	protected $image_id;
	protected $shelf1_id;
	protected $shelf2_id;
	protected $shelf3_id;

	protected function setUp() : void {
		parent::setUp();
		
		$this->image_id = $this->addTestRecord('ca_objects', [
			'intrinsic_fields' => [
				'type_id' => 'image',
			],
			'preferred_labels' => [
				[
					"locale" => "en_US",
					"name" => "My test image",
				]
			]
		]);
		$this->assertGreaterThan(0, $this->image_id);

		$this->shelf1_id = $this->addTestRecord('ca_storage_locations', [
			'intrinsic_fields' => [
				'type_id' => 'shelf',
				'idno' => '100',
			],
			'preferred_labels' => [
				[
					"locale" => "en_US",
					"name" => "Shelf 1",
				]
			]
		]);
		$this->assertGreaterThan(0, $this->shelf1_id);
		

		$this->shelf2_id = $this->addTestRecord('ca_storage_locations', [
			'intrinsic_fields' => [
				'type_id' => 'shelf',
				'idno' => '200',
			],
			'preferred_labels' => [
				[
					"locale" => "en_US",
					"name" => "Shelf 2",
				]
			]
		]);
		$this->assertGreaterThan(0, $this->shelf2_id);
		

		$this->shelf3_id = $this->addTestRecord('ca_storage_locations', [
			'intrinsic_fields' => [
				'type_id' => 'shelf',
				'idno' => '200',
			],
			'preferred_labels' => [
				[
					"locale" => "en_US",
					"name" => "Shelf 3",
				]
			]
		]);
		$this->assertGreaterThan(0, $this->shelf3_id);
	}

	/**
	 * 
	 */
	public function testDefaultCurrentValuePolicyUsingSimpleHistory() {
		$object = ca_objects::findAsInstance(['object_id' => $this->image_id]);		
		$rel1 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf1_id, 'related', '5/2020');
		
		$history = $object->getHistory();
		$this->assertIsArray($history);
		$this->assertCount(1, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
		$rel2 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf2_id, 'related', '10/2002');
		$history = $object->getHistory();
		
		$this->assertIsArray($history);
		$this->assertCount(2, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
		$rel3 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf3_id, 'related', '7/7/2021');
		$history = $object->getHistory();
		
		$this->assertIsArray($history);
		$this->assertCount(3, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 3', $by_time[0]['display']);
	}
	
	/**
	 * 
	 */
	public function testCurrentValuePolicyWithMovementsUsingSimpleHistory() {
		$this->_testCurrentValuePolicyUsingSimpleHistory("current_location");
	}
	
	/**
	 * 
	 */
	public function testAltCurrentValuePolicyUsingSimpleHistory() {
		$this->_testCurrentValuePolicyUsingSimpleHistory("alt_current_location");
	}
	
	/**
	 * 
	 */
	public function testNonExistentPolicyName() {
		$object = ca_objects::findAsInstance(['object_id' => $this->image_id]);		
		$rel1 = $object->addRelationship('ca_storage_locations', $this->shelf1_id, 'related', '5/2020');
		
		
		$history = $object->getHistory(['policy' => 'BAD_POLICY_NAME']);
		$this->assertIsArray($history, "Expected array returned for non-existent policy name");
		$this->assertCount(0, $history, "Expected no entries for non-existent policy name");
		
		$history = $object->getHistory(['policy' => 'current_location']);
		$this->assertIsArray($history, "Expected array returned for \"current_location\" policy name");
		$this->assertCount(1, $history, "Expected 1 entry for \"current_location\" policy");
	}
	
	/**
	 * 
	 */
	public function testFutureCurrentValue() {
		$object = ca_objects::findAsInstance(['object_id' => $this->image_id]);		
		$rel1 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf1_id, 'related', '5/2020');
		
		$history = $object->getHistory(['currentOnly' => true]);
		$this->assertIsArray($history);
		$this->assertCount(1, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
		$rel2 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf2_id, 'related', '10/1/2029');
		$history = $object->getHistory(['currentOnly' => true]);
		$this->assertIsArray($history);
		$this->assertCount(1, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
	}
	
	/**
	 * 
	 */
	private function _testCurrentValuePolicyUsingSimpleHistory(string $policy) {
		$object = ca_objects::findAsInstance(['object_id' => $this->image_id]);		
		$rel1 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf1_id, 'related', '5/2020');
		
		$history = $object->getHistory(['policy' => $policy]);
		$this->assertIsArray($history);
		$this->assertCount(1, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
		$rel2 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf2_id, 'related', '10/2002');
		$history = $object->getHistory(['policy' => $policy]);
		
		$this->assertIsArray($history);
		$this->assertCount(2, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 1', $by_time[0]['display']);
		
		$rel3 = $this->addTestRelationship($object, 'ca_storage_locations', $this->shelf3_id, 'related', '7/7/2021');
		$history = $object->getHistory(['policy' => $policy]);
		
		$this->assertIsArray($history);
		$this->assertCount(3, $history);
		$by_time = array_shift($history);
		$this->assertIsArray($by_time);
		$this->assertCount(1, $by_time);
		$this->assertArrayHasKey('display', $by_time[0]);
		$this->assertEquals('Shelf 3', $by_time[0]['display']);
	}
	
	protected function tearDown() : void {
		parent::tearDown();
	}
}
