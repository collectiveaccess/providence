<?php
/** ---------------------------------------------------------------------
 * tests/lib/MultipartDNumbering.php
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

require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');
require_once(__CA_LIB_DIR__.'/IDNumbering/MultipartIDNumber.php');

class MultipartDNumber extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_objects
	 */
	private $object1 = null;
	private $object2 = null;
	private $object3 = null;
	
	/**
	 * @var ca_entities
	 */
	private $entity1 = null;
	
	
	# -------------------------------------------------------
	protected function setUp() : void {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => '2021.%'
			),
			'attributes' => array(
				'preferred_labels' => [
					['name' => 'Hello World!', 'locale' => 'en_US']
				],
				'internal_notes' => [
					[
						'locale' => 'en_US',
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					]
				],
			)
		));

		$this->assertGreaterThan(0, $object_id);
		$this->object1 = new ca_objects($object_id);
		
		$object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'dataset',
				'idno' => 'A-2001-XX'
			),
			'attributes' => array(
				'preferred_labels' => [
					['name' => 'Hi there!', 'locale' => 'en_US']
				],
				'internal_notes' => [
					[
						'locale' => 'en_US',
						'internal_notes' => 'The wind in the willows.'
					]
				],
			)
		));

		$this->assertGreaterThan(0, $object_id);
		$this->object2 = new ca_objects($object_id);
		
		$object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
				'idno' => '2010~%~%'
			),
			'attributes' => array(
				'preferred_labels' => [
					['name' => 'Another greeting!', 'locale' => 'en_US']
				],
				'internal_notes' => [
					[
						'locale' => 'en_US',
						'internal_notes' => 'Drop the Dips'
					]
				],
			)
		));

		$this->assertGreaterThan(0, $object_id);
		$this->object3 = new ca_objects($object_id);
		
		$entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => '%'
			),
			'attributes' => array(
				'preferred_labels' => [
					['displayname' => 'George C. Tilyou', 'locale' => 'en_US']
				]
			)
		));

		$this->assertGreaterThan(0, $entity_id);
		$this->entity1 = new ca_entities($entity_id);
	}
	
	# -------------------------------------------------------
	
	// IDNO format: 2021.%
	public function testDefaultTypeSerial() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('image');
		
		$separator = $idno_plugin->getSeparator();
		$this->assertEquals('.', $separator, 'Separator was not expected value');

		$idno = $this->object1->get('ca_objects.idno');
		$this->assertMatchesRegularExpression('!^2021\.[\d]+$!', $idno, 'Generated identifier does not match expected format');
	}
	
	// IDNO format: A-2001-XX
	public function testTypedFree() {
		$idno_plugin = $this->object2->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('dataset');
		$separator = $idno_plugin->getSeparator();
		$this->assertEquals('-', $separator, 'Separator was not expected value');

		$idno = $this->object2->get('ca_objects.idno');
		$this->assertMatchesRegularExpression('!^A-2001-XX$!', $idno, 'Generated identifier does not match expected format and value');
	}
	
	// IDNO format: 2010~%~%
	public function testTypedMultiSerial() {
		$idno_plugin = $this->object3->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('moving_image');
		$separator = $idno_plugin->getSeparator();
		$this->assertEquals('~', $separator, 'Separator was not expected value');

		$idno = $this->object3->get('ca_objects.idno');
		$this->assertMatchesRegularExpression('!^2010~[\d]+~[\d]+$!', $idno, 'Generated identifier does not match expected format and value');
	}
	
	// IDNO format: %
	public function testEntityWithSingleSerial() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_entities');
		$idno_plugin->setType('ind');
		$separator = $idno_plugin->getSeparator();
		$this->assertEquals('.', $separator, 'Separator was not expected value');

		$idno = $this->entity1->get('ca_entities.idno');
		$this->assertMatchesRegularExpression('!^[\d]+$!', $idno, 'Generated identifier does not match expected format and value');
	}
	
	public function testFormatList() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$formats = $idno_plugin->getFormats();
		
		$this->assertCount(14, $formats);
		$this->assertContains('ca_objects', array_keys($formats), 'Does not contain expected formats');
		$this->assertContains('ca_entities', array_keys($formats), 'Does not contain expected formats');

	}
	
	public function testTypeList() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		
		$types = $idno_plugin->getTypes();
		
		$this->assertCount(6, $types);
		$this->assertContains('__default__', array_keys($types), 'Does not contain expected default object type');
		$this->assertContains('dataset', array_keys($types), 'Does not contain expected object type');
		$this->assertContains('moving_image', array_keys($types), 'Does not contain expected object type');
		
		$idno_plugin->setFormat('ca_entities');
		
		$types = $idno_plugin->getTypes();
		
		$this->assertCount(1, $types);
		$this->assertContains('__default__', array_keys($types), 'Does not contain expected default entity type');
	}
	
	public function testValidationForDefaultType() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('__default__');
		
		$ret = $idno_plugin->validateValue("TEST.VALUE");
	
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('year', $ret, "Array should include year error");
		$this->assertArrayHasKey('accession_number', $ret, "Array should include accession_number error");
		
		$ret = $idno_plugin->validateValue("1999.002");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(0, $ret, 'Should not contain errors for valid form');
		
		$ret = $idno_plugin->validateValue("1999.XXX");
	
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('accession_number', $ret, "Array should include accession_number error");
		
		$idno_plugin->setType('dataset');
		$ret = $idno_plugin->validateValue("1999.002");
		$this->assertIsArray($ret, 'Should return array with error messages');

		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('accession_type', $ret, "Array should include accession_type error");
		
		$ret = $idno_plugin->validateValue("1999-002-01");
		$this->assertIsArray($ret, 'Should return array with error messages');

		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('accession_type', $ret, "Array should include accession_type error");
		$this->assertArrayHasKey('year', $ret, "Array should include year error");
		
		$ret = $idno_plugin->validateValue("B-1999-01");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(0, $ret, 'Should not contain errors for valid form');
		
		
		$idno_plugin->setType('dataset');	
	}
	
	public function testValidationForDatasetType() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('dataset');
		$ret = $idno_plugin->validateValue("1999.002");
		$this->assertIsArray($ret, 'Should return array with error messages');

		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('accession_type', $ret, "Array should include accession_type error");
		
		$ret = $idno_plugin->validateValue("1999-002-01");
		$this->assertIsArray($ret, 'Should return array with error messages');

		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('accession_type', $ret, "Array should include accession_type error");
		$this->assertArrayHasKey('year', $ret, "Array should include year error");
		
		$ret = $idno_plugin->validateValue("B-1999-01");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(0, $ret, 'Should not contain errors for valid form');
	}
	
	public function testValidationForPhysicalObjectType() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('physical_object');
		
		$ret = $idno_plugin->validateValue("DATENUM-2009-005-002");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(0, $ret, 'Should not contain errors for valid form');
		
		$ret = $idno_plugin->validateValue("DATE-2009-005-002");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('const', $ret, "Array should include const error");
		
		$ret = $idno_plugin->validateValue("DATE-34-005-002");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('const', $ret, "Array should include const error");
		$this->assertArrayHasKey('year', $ret, "Array should include year error");
		
		$ret = $idno_plugin->validateValue("DATE-2002-14-002");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('const', $ret, "Array should include const error");
		$this->assertArrayHasKey('month', $ret, "Array should include month error");
		
		$ret = $idno_plugin->validateValue("DATE-2002-14-40");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(3, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('const', $ret, "Array should include const error");
		$this->assertArrayHasKey('month', $ret, "Array should include month error");
		$this->assertArrayHasKey('day', $ret, "Array should include day error");	
	}
	
	public function testValidationForServiceObjectType() {
		$idno_plugin = $this->entity1->getIDNoPlugInInstance();	
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('service');
		
		$ret = $idno_plugin->validateValue("BLAH.002.Meow123");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(0, $ret, 'Should not contain errors for valid form');
		
		$ret = $idno_plugin->validateValue("BLAH123.aaa.Meow $ 123");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('numeric', $ret, "Array should include numeric error");
		$this->assertArrayHasKey('alphanumeric', $ret, "Array should include alphanumeric error");
		
		$ret = $idno_plugin->validateValue("BLAH123.9 99.Meow $ 123");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(2, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('numeric', $ret, "Array should include numeric error");
		$this->assertArrayHasKey('alphanumeric', $ret, "Array should include alphanumeric error");
		
		$ret = $idno_plugin->validateValue("BLAH123.999.Meow$123");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('alphanumeric', $ret, "Array should include alphanumeric error");
		
		$ret = $idno_plugin->validateValue("BLAH123.999.Meow 123");
		$this->assertIsArray($ret, 'Should return array with error messages');
		$this->assertCount(1, $ret, 'Should contain errors for invalid form');
		$this->assertArrayHasKey('alphanumeric', $ret, "Array should include alphanumeric error");
	}
	
	public function testValidationForGetNextValue() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno = $this->object1->get('ca_objects.idno');

		$current_value = array_pop(explode('.', $idno));
		$next_value = $idno_plugin->getNextValue('accession_number');
		$this->assertEquals($next_value, $current_value + 1, 'Next-in-sequence value was not expected value');
		
		$new_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => '2021.%'
			),
			'attributes' => array(
				'preferred_labels' => [
					['name' => 'Hello World again!', 'locale' => 'en_US']
				]
			)
		));
		
		$this->assertGreaterThan(0, $new_object_id, 'Failed to create new object for testing of SERIAL incrementing');
		$new_object = new ca_objects($new_object_id);
		
		$current_value = array_pop(explode('.', $new_object->get('ca_objects.idno')));
		$next_value = $idno_plugin->getNextValue('accession_number');
		$this->assertEquals($next_value, $current_value + 1, 'Next-in-sequence value was not expected value after creating new object');
	}
	
	public function testGetSortableValues() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		
		$sortable = $idno_plugin->getSortableValue('2021.1');
		$this->assertEquals("                             1.2021", $sortable);	// padding to 30 width
		
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('moving_image');
		$sortable = $idno_plugin->getSortableValue('2021~5~4');
		$this->assertEquals("2021~                             5~                             4", $sortable);	// padding to 30 width
	}
	
	public function testGetIndexValues() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		
		$indexing_values = $idno_plugin->getIndexValues('A.100.10');
		$this->assertIsArray($indexing_values, 'Should return array with values');
		$this->assertCount(5, $indexing_values, 'Should contain 5 elements');
		$this->assertContains('A', $indexing_values, 'Should contain element');
		$this->assertContains('A.100', $indexing_values, 'Should contain element');
		$this->assertContains('A.100.10', $indexing_values, 'Should contain element');
		$this->assertContains('100', $indexing_values, 'Should contain element');
		$this->assertContains('10', $indexing_values, 'Should contain element');
		
		$indexing_values = $idno_plugin->getIndexValues('1999-001');	// not specified separator (should be ".") so value is treated as single element
		$this->assertIsArray($indexing_values, 'Should return array with values');
		$this->assertCount(3, $indexing_values, 'Should contain 3 elements');
		
		$this->assertContains('1999-001', $indexing_values, 'Should contain element');		// as single element
		$this->assertContains('1999', $indexing_values, 'Should contain element');			// as stub truncated on punctuation
		$this->assertContains('1999-1', $indexing_values, 'Should contain element');		// with numeric values normalized
		
		$indexing_values = $idno_plugin->getIndexValues('1999.001');	// treated as elements
		$this->assertIsArray($indexing_values, 'Should return array with values');
		$this->assertCount(4, $indexing_values, 'Should contain 4 elements');
		$this->assertContains('1999.001', $indexing_values, 'Should contain element');		
		$this->assertContains('1999.1', $indexing_values, 'Should contain element');			
		$this->assertContains('1999', $indexing_values, 'Should contain element');				
		$this->assertContains('001', $indexing_values, 'Should contain element');	
		
		$indexing_values = $idno_plugin->getIndexValues('1999.001a');	// treated as elements
		$this->assertIsArray($indexing_values, 'Should return array with values');
		$this->assertCount(6, $indexing_values, 'Should contain 4 elements');
		$this->assertContains('1999.001a', $indexing_values, 'Should contain element');		
		$this->assertContains('1999.1a', $indexing_values, 'Should contain element');			
		$this->assertContains('1999', $indexing_values, 'Should contain element');				
		$this->assertContains('1999.001', $indexing_values, 'Should contain element');			
		$this->assertContains('1999.1', $indexing_values, 'Should contain element');		
		$this->assertContains('001a', $indexing_values, 'Should contain element');		
		
		$indexing_values = $idno_plugin->getIndexValues('1999.001-a');	// treated as elements
		$this->assertIsArray($indexing_values, 'Should return array with values');
		$this->assertCount(5, $indexing_values, 'Should contain 4 elements');
		$this->assertContains('1999.001-a', $indexing_values, 'Should contain element');		
		$this->assertContains('1999.1-a', $indexing_values, 'Should contain element');			
		$this->assertContains('1999', $indexing_values, 'Should contain element');				
		$this->assertContains('1999.001', $indexing_values, 'Should contain element');		
		$this->assertContains('001-a', $indexing_values, 'Should contain element');		

	}
	
	public function testElementSortOrder() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('__default__');
		
		$sorted_elements = $idno_plugin->getElementOrderForSort();
		$this->assertIsArray($sorted_elements, 'Should return array with elements');
		$this->assertCount(2, $sorted_elements, 'Should contain two elements');
		$this->assertEquals('accession_number', $sorted_elements[0]);
		$this->assertEquals('year', $sorted_elements[1]);
	}
	
	public function testMakeTemplateFromValue() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('__default__');
		
		$template = $idno_plugin->makeTemplateFromValue("1999.1001");
		$this->assertIsString($template);
		$this->assertEquals("1999.%", $template);
		
		$idno_plugin->setType('moving_image');
		
		$template = $idno_plugin->makeTemplateFromValue("1999~1001~5");
		$this->assertIsString($template);
		$this->assertEquals("1999~%~%", $template);
	}
	
	public function testIsSerialFormat() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('__default__');
		
		$ret = $idno_plugin->isSerialFormat();
		$this->assertTrue($ret);
		
		$ret = $idno_plugin->isSerialFormat('ca_objects', 'dataset');
		$this->assertFalse($ret);
		
		$ret = $idno_plugin->isSerialFormat('ca_objects', '__default__');
		$this->assertTrue($ret);
	}
	
	public function testFormatIsExtensionOf() {
		$idno_plugin = $this->object1->getIDNoPlugInInstance();	
		$idno_plugin->setFormat('ca_objects');
		$idno_plugin->setType('dataset');
		
		$ret = $idno_plugin->formatIsExtensionOf('ca_objects', 'moving_image');
		$this->assertFalse($ret);
		
		$ret = $idno_plugin->isSerialFormat('ca_objects', 'software');
		$this->assertTrue($ret);
		
		$ret = $idno_plugin->formatIsExtensionOf('ca_objects', 'xxx');
		$this->assertFalse($ret);
	}
	
	# -------------------------------------------------------
}
