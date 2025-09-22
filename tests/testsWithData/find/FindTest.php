<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/FindTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
 * Class FindTest
 * Note: Requires testing profile!
 */
class FindTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * primary key ID of the first created object
	 * @var int
	 */
	private $opn_object_id = null;
	
	/**
	 * primary key ID of the second created object
	 * @var int
	 */
	private $opn_object_id2 = null;
	# -------------------------------------------------------
	protected function setUp() : void {
		require_once 'Zend/Registry.php';
		
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$this->assertGreaterThan(0, $this->opn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'TEST & STUFF',
				'acquisition_type_id' => 'gift',
				'extent' => 10
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Sound & Motion",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
			'attributes' => array(
				// simple text
				'internal_notes' => array(
					array(
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					)
				),

				// text in a container
				'external_link' => array(
					array(
						'url_source' => 'My URL source'
					)
				),

				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '10 in',
						'dimensions_weight' => '2 lbs',
						'dimensions_height' => null
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
				
				// DateRange
				'date' => array(
					array(
						'dates_value' => '6/1954',
						'dc_dates_types' => 'created'
					),
				),

				// Georeference
				// 'georeference' => array(
// 					array(
// 						'georeference' => '1600 Amphitheatre Parkway, Mountain View, CA',
// 					),
// 				),

				// coverageNotes
				'coverageNotes' => array(
					array(
						'coverageNotes' => 'Uncertain'
					),
				),
			)
		)));
		
		$this->assertGreaterThan(0, $this->opn_object_id2 = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'physical_object',
				'idno' => 'Another TEST',
				'extent' => 3
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A Walk on the Wild Side",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Lou Reed and the East Village",
				),
			),
			'attributes' => array(
				// simple text
				'internal_notes' => array(
					array(
						'internal_notes' => 'Holly came from Miami F.L.A.
Hitch-hiked her way across the U.S.A.
Plucked her eyebrows on the way
Shaved her legs and then he was a she
She said, hey babe, take a walk on the wild side,
Said, hey honey, take a walk on the wild side.'
					)
				),

				// text in a container
				'external_link' => array(
					array(
						'url_source' => 'Wikipedia'
					)
				),

				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '5 cm',
						'dimensions_height' => '5 in',
						'dimensions_weight' => '5 kg'
					)
				),

				// Integer
				'integer_test' => array(
					array(
						'integer_test' => 1,
					),
					array(
						'integer_test' => 500,
					)
				),
				
				
				// DateRange
				'date' => array(
					array(
						'dates_value' => '5/1/1975',
						'dc_dates_types' => 'created'
					),
				),

				// Currency
				'currency_test' => array(
					array(
						'currency_test' => '£50',
					),
				)
			)
		)));
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIdnoNoPurify() {
		$vm_ret = ca_objects::find(['idno' => 'TEST & STUFF'], ['returnAs' => 'ids', 'purify' => false]);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['idno' => 'TEST &amp; STUFF'], ['returnAs' => 'ids', 'purify' => false]);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByIdnoWithPurify() {
		$vm_ret = ca_objects::find(['idno' => 'TEST &amp; STUFF'], ['returnAs' => 'ids', 'purify' => true]);
		
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['idno' => 'TEST & STUFF'], ['returnAs' => 'ids', 'purify' => true]);
		
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelNoPurify() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound & Motion']], ['purify' => false, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound &amp; Motion']], ['purify' => false, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithPurify() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound &amp; Motion']], ['purifyWithFallback' => false, 'purify' => true, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound & Motion']], ['purifyWithFallback' => false, 'purify' => true, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithPurifyFallback() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound &amp; Motion']], ['purifyWithFallback' => true, 'purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound & Motion']], ['purifyWithFallback' => true, 'purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithOperators() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['=', 'Sound & Motion']]], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithOperatorsAndWildcards() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIntrinsicWithOperators() {	
		$vm_ret = ca_objects::find(['object_id' => ['>', 1]], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
		$vm_ret = ca_objects::find(['object_id' => ['<', 1]], ['purify' => false, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByAttribute() {	
		$vm_ret = ca_objects::find(['internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByAttributeWithOperators() {	
		$vm_ret = ca_objects::find(['internal_notes' => [['LIKE', '%ipsum%']]], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
		
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIntrinsicWithInOperator() {	
		$vm_ret = ca_objects::find(['idno' => ['IN', ['TEST &amp; STUFF', 'INVALID VALUE', 'Another TEST']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['idno' => ['IN', ['TEST & STUFF', 'INVALID VALUE', 'Another TEST']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['idno' => ['IN', ['TEST & STUFF', 'INVALID VALUE', 'Another TEST']]], ['purify' => false, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);	
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIntrinsicWithNotInOperator() {	
		$vm_ret = ca_objects::find(['idno' => ['NOT IN', ['INVALID VALUE', 'Another TEST']]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['idno' => ['NOT IN', ['TEST & STUFF']]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['idno' => ['NOT IN', ['INVALID VALUE']]], ['purify' => false, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);	
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIntrinsicWithBetweenOperator() {	
		$vm_ret = ca_objects::find(['extent' => ['BETWEEN', [1, 5]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['extent' => ['BETWEEN', [3, 100]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['extent' => ['BETWEEN', [8, 100]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['extent' => ['BETWEEN', [0, 2]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testBaseModelFindByAttributeWithBetweenOperator() {	
		$vm_ret = ca_objects::find(['integer_test' => ['BETWEEN', [400, 600]]], ['purify' => true, 'returnAs' => 'ids']);
	
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['integer_test' => ['BETWEEN', [0, 1000]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['integer_test' => ['BETWEEN', [5000, 100000]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithInOperator() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['IN', ['Sound & Motion']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['IN', ['Sound & Motion', 'INVALID VALUE', 'A Walk on the Wild Side']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithNotInOperator() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['NOT IN', ['Sound & Motion']]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);	
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['NOT IN', ['INVALID VALUE', 'A Walk on the Wild Side']]]], ['purify' => true, 'returnAs' => 'ids']);
		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByNonPreferredLabelWithInOperator() {	
		$vm_ret = ca_objects::find(['nonpreferred_labels' => ['name' => ['IN', ['My test image']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		
		$vm_ret = ca_objects::find(['nonpreferred_labels' => ['name' => ['IN', ['My test image', 'INVALID VALUE', 'Lou Reed and the East Village']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindByContainerSubAttributeWithOperators() {	
		$vm_ret = ca_objects::find(['external_link' => ['url_source' => [['IN', ['wikipedia']], ['=', 'wikipedia']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
			
		$vm_ret = ca_objects::find(['external_link' => ['url_source' => ['IN', ['wikipedia']]]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		
		$vm_ret = ca_objects::find(['external_link' => ['url_source' => ['LIKE', '%source']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindUsingWildcard() {	
		$vm_ret = ca_objects::find(['*'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find('*', ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testBaseModelFindByType() {	
		$vm_ret = ca_objects::find(['type_id' => 'image'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['type_id' => 'physical_object'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByType() {	
		$vm_ret = ca_objects::find(['type_id' => 'image', 'preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['type_id' => 'physical_object', 'preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testBaseModelFindByIntrinsicList() {	
		$vm_ret = ca_objects::find(['acquisition_type_id' => 'gift'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByIntrinsicList() {	
		$vm_ret = ca_objects::find(['acquisition_type_id' => 'gift', 'preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testBaseModelFindWithTypeRestrictions() {	
		$vm_ret = ca_objects::find(['object_id' => ['>', 0]], ['purify' => true, 'returnAs' => 'ids', 'restrictToTypes' => ['image']]);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['object_id' => ['>', 0]], ['purify' => true, 'returnAs' => 'ids', 'restrictToTypes' => ['physical_object']]);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindWithTypeRestrictions() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => true, 'returnAs' => 'ids', 'restrictToTypes' => ['image']]);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => ['LIKE', 'Sound%']]], ['purify' => true, 'returnAs' => 'ids', 'restrictToTypes' => ['physical_object']]);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindWithAllowWildcards() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound%']], ['allowWildcards' => true, 'purify' => true, 'returnAs' => 'ids', 'restrictToTypes' => ['image']]);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByIntrinsicListBooleanOr() {	
		$vm_ret = ca_objects::find(['acquisition_type_id' => 'gift', 'preferred_labels' => ['name' => ['LIKE', 'A walk%']]], ['boolean' => 'OR', 'purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindWithArrayOfValues() {	
		$vm_ret = ca_objects::find(['idno' => [['LIKE', 'TEST%'], 'Another TEST']], ['boolean' => 'OR', 'purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['idno' => ['TEST & STUFF', 'Another TEST']], ['boolean' => 'AND', 'purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByCurrencyValue() {	
		$vm_ret = ca_objects::find(['currency_test' => '$100'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['currency_test' => '€20'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByIntegerValue() {	
		$vm_ret = ca_objects::find(['integer_test' => '23'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['integer_test' => '534'], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testLabelableFindByDateRangeValue() {	
		$vm_ret = ca_objects::find(['date' => ['dates_value' => '1954']], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		$vm_ret = ca_objects::find(['date' => ['dates_value' => ['5/1975']]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindNull() {	
		$vm_ret = ca_objects::find(['parent_id' => null], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFindNullAttribute() {	
		$vm_ret = ca_objects::find(['coverageNotes' => null], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
 		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_height' => null]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
		
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_height' => '5in']], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_weight' => '5 kg']], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_length' => '5 cm', 'dimensions_height' => '5 in']], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id2, $vm_ret);
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_length' => '5 cm', 'dimensions_height' => '10 in']], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(0, $vm_ret);
		
		
		$vm_ret = ca_objects::find(['dimensions' => ['dimensions_length' => '10 in', 'dimensions_weight' => '2 lb', 'dimensions_height' => null]], ['purify' => true, 'returnAs' => 'ids']);

		$this->assertIsArray($vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains($this->opn_object_id, $vm_ret);
	}
	# -------------------------------------------------------
}
