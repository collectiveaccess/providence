<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/HierarchyGetTest.php
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
 * Class HierarchyGetTest
 * Note: Requires testing profile!
 */
class HierarchyGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	protected $opt_child_object = null;
	
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	protected $opt_parent_object = null;
	
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	protected $opt_another_child_object = null;
	
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	protected $opt_third_child_object = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_test_parent = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test moving image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_test_parent);
		$this->opt_parent_object = new ca_objects($vn_test_parent);

		$vn_test_child = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'parent_id' => $vn_test_parent,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test still",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_test_child);
		$this->opt_child_object = new ca_objects($vn_test_child);

		// Another child to check if the 'children' modifier actually returns all children
		$vn_another_test_child = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'parent_id' => $vn_test_parent,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Another test still",
				),
			),
		));

		$this->opt_another_child_object = new ca_objects($vn_another_test_child);
		
		// Another child to check if the 'children' modifier actually returns all children by type
		$vn_third_test_child = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
				'parent_id' => $vn_test_parent,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A child movie",
				),
			),
		));
		$this->opt_third_child_object = new ca_objects($vn_third_test_child);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_child_object->get('ca_objects.preferred_labels');
		$this->assertEquals('My test still', $vm_ret);

		$vm_ret = $this->opt_child_object->get('ca_objects.parent.preferred_labels');
		$this->assertEquals('My test moving image', $vm_ret);
		
		$vm_ret = $this->opt_child_object->get('ca_objects.parent.preferred_labels', array('returnAsArray' => true));
		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(1, sizeof($vm_ret));
		$this->assertEquals('My test moving image', array_shift($vm_ret));
		
		$vm_ret = $this->opt_child_object->get('ca_objects.parent.preferred_labels', array('returnAsArray' => true));
		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(1, sizeof($vm_ret));
	//	$this->assertTrue(is_array($vm_ret = array_shift($vm_ret)));
		$this->assertArrayHasKey(0, $vm_ret);

		$this->assertContains('My test moving image', $vm_ret);
		$vm_ret = $this->opt_parent_object->get('ca_objects.children.preferred_labels');
		$this->assertEquals('My test still;Another test still;A child movie', $vm_ret);
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.children.preferred_labels', ['restrictToTypes' => ['image']]);
		$this->assertEquals('My test still;Another test still', $vm_ret);
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.children.preferred_labels', array('returnAsArray' => true));
		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(3, sizeof($vm_ret));
		$this->assertEquals('My test still', array_shift($vm_ret));
		$this->assertEquals('Another test still', array_shift($vm_ret));
		$this->assertEquals('A child movie', array_shift($vm_ret));
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.children.preferred_labels', array('returnWithStructure' => true, 'returnAllLocales' => true));

		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(3, sizeof($vm_ret));
		$this->assertTrue(is_array($vm_item = array_shift($vm_ret)));
		$this->assertArrayHasKey(1, $vm_item);
		$this->assertEquals(1, sizeof($vm_item[1]));

		$this->assertTrue(is_array($vm_subitem = array_shift($vm_item)));
		$this->assertTrue(is_array($vm_values = array_shift($vm_subitem)));
		$this->assertArrayHasKey('name', $vm_values);
		$this->assertEquals('My test still', $vm_values['name']);
		
		
		$vm_ret = $this->opt_child_object->get('ca_objects.hierarchy.preferred_labels', array('delimiter' => ' ➔ '));
		$this->assertEquals('My test moving image ➔ My test still', $vm_ret);
		
		$vm_ret = $this->opt_child_object->get('ca_objects.hierarchy.preferred_labels', array('delimiter' => ' ➔ ', 'hierarchyDirection' => 'desc'));
		$this->assertEquals('My test still ➔ My test moving image', $vm_ret);
		
		$vm_ret = $this->opt_child_object->get('ca_objects.hierarchy.preferred_labels', array('returnAsArray' => true));
		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(2, sizeof($vm_ret));
		$this->assertEquals('My test moving image', array_shift($vm_ret));
		$this->assertEquals('My test still', array_shift($vm_ret));
		
		$vm_ret = $this->opt_child_object->get('ca_objects.hierarchy.preferred_labels', array('returnWithStructure' => true, 'returnAllLocales' => true));
		
		$this->assertTrue(is_array($vm_ret));
 		$this->assertEquals(1, sizeof($vm_ret));
 		$this->assertTrue(is_array($vm_items = array_shift($vm_ret)));
 		$this->assertEquals(2, sizeof($vm_items));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_items)));
 		$this->assertArrayHasKey(1, $vm_item);
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals(1, sizeof($vm_item));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals(1, sizeof($vm_item));
 		$this->assertEquals('My test moving image', $vm_item['name']);
 		
 		$this->assertTrue(is_array($vm_item = array_shift($vm_items)));
 		$this->assertArrayHasKey(1, $vm_item);
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals(1, sizeof($vm_item));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals(1, sizeof($vm_item));
 		$this->assertEquals('My test still', $vm_item['name']);
		
		$vm_ret = $this->opt_child_object->get('ca_objects.siblings.preferred_labels', array('delimiter' => ', '));
		$this->assertEquals('My test still, Another test still, A child movie', $vm_ret);
		
		$vm_ret = $this->opt_child_object->get('ca_objects.siblings.preferred_labels', array('returnAsArray' => true));
		$this->assertTrue(is_array($vm_ret));
		$this->assertEquals(3, sizeof($vm_ret));
		$this->assertEquals('My test still', array_shift($vm_ret));
		$this->assertEquals('Another test still', array_shift($vm_ret));
		$this->assertEquals('A child movie', array_shift($vm_ret));
		
		$vm_ret = $this->opt_child_object->get('ca_objects.siblings.preferred_labels', array('returnWithStructure' => true, 'returnAllLocales' => true));
 		$this->assertTrue(is_array($vm_ret));

 		$this->assertEquals(3, sizeof($vm_ret));
 		$this->assertTrue(is_array($vm_items = array_shift($vm_ret)));
 		$this->assertEquals(1, sizeof($vm_items));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_items)));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals('My test still', $vm_item['name']);
 		
 		$this->assertTrue(is_array($vm_items = array_shift($vm_ret)));
 		$this->assertEquals(1, sizeof($vm_items));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_items)));
 		$this->assertTrue(is_array($vm_item = array_shift($vm_item)));
 		$this->assertEquals('Another test still', $vm_item['name']);
	}
	# -------------------------------------------------------
	public function testGetCounts() {
		$vm_ret = $this->opt_parent_object->get('ca_objects.children._count');
		$this->assertEquals(3, $vm_ret);
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.children._count', ['returnAsArray' => true]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals(3, $vm_ret[0]);
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.children', ['returnAsCount' => true]);
		$this->assertEquals(3, $vm_ret);
		
		$vm_ret = $this->opt_parent_object->get('ca_objects.siblings._count');
		$this->assertEquals(0, $vm_ret);
		
		$vm_ret = $this->opt_another_child_object->get('ca_objects.siblings._count');
		$this->assertEquals(3, $vm_ret);
		
		$vm_ret = $this->opt_another_child_object->get('ca_objects.parent._count');
		$this->assertEquals(1, $vm_ret);
	}
	# -------------------------------------------------------
	public function testChildrenRestrictToTypesDisplayTemplates() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='image' relativeTo='ca_objects.children'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_parent_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test still; Another test still', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='moving_image' relativeTo='ca_objects.children'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_parent_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('A child movie', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='image'>^ca_objects.children.preferred_labels.name</unit>", "ca_objects", array($this->opt_parent_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test still; Another test still', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='moving_image'>^ca_objects.children.preferred_labels.name</unit>", "ca_objects", array($this->opt_parent_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('A child movie', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testParentRestrictToTypesDisplayTemplates() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='moving_image' relativeTo='ca_objects.parent'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test moving image', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='image' relativeTo='ca_objects.parent'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testHierarchyRestrictToTypesDisplayTemplates() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='image' relativeTo='ca_objects.hierarchy'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		//print_R($vm_ret);
		$this->assertEquals('Another test still', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='moving_image' relativeTo='ca_objects.hierarchy'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test moving image', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testSiblingRestrictToTypesDisplayTemplates() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='image' relativeTo='ca_objects.siblings'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test still; Another test still', $vm_ret[0]);
		
		$vm_ret = DisplayTemplateParser::evaluate("<unit delimiter='; ' restrictToTypes='moving_image' relativeTo='ca_objects.siblings'>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opt_another_child_object->getPrimaryKey()), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('A child movie', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function tearDown() {

		// set parent id to null for both children to avoid FK conflicts during tearDown()

		if($this->opt_child_object instanceof ca_objects) {
			$this->opt_child_object->setMode(ACCESS_WRITE);
			$this->opt_child_object->set('parent_id', null);
			$this->opt_child_object->update();
		}

		if($this->opt_another_child_object instanceof ca_objects) {
			$this->opt_another_child_object->setMode(ACCESS_WRITE);
			$this->opt_another_child_object->set('parent_id', null);
			$this->opt_another_child_object->update();
		}
		
		if($this->opt_third_child_object instanceof ca_objects) {
			$this->opt_third_child_object->setMode(ACCESS_WRITE);
			$this->opt_third_child_object->set('parent_id', null);
			$this->opt_third_child_object->update();
		}

		parent::tearDown();
	}
	# -------------------------------------------------------
}
