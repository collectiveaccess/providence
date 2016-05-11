<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/DisplayTemplateParserTest.php
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
require_once(__CA_LIB_DIR__.'/core/Parsers/DisplayTemplateParser.php');

class DisplayTemplateParserTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;

	/**
	 * primary key ID of the first created entity
	 * @var int
	 */
	private $opn_entity_id1 = null;
	
	/**
	 * primary key ID of the last created entity
	 * @var int
	 */
	private $opn_entity_id2 = null;
	
	/**
	 * primary key ID of the first created object
	 * @var int
	 */
	private $opn_object_id = null;
	
	/**
	 * primary key ID of the last created object (the "related" object)
	 * @var int
	 */
	private $opn_rel_object_id = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_object_id = $this->opn_object_id = $this->addTestRecord('ca_objects', array(
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
			'attributes' => array(
				// simple text
				'internal_notes' => array(
					array(
						'locale' => 'en_US',
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					)
				),
				'description' => array(
					array(
						'locale' => 'en_US',
						'description' => 'First description'
					),
					array(
						'locale' => 'en_US',
						'description' => 'Second description'
					),
					array(
						'locale' => 'en_US',
						'description' => 'Third description'
					)
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
						'dimensions_length' => '10.1 in',
						'dimensions_weight' => '2 lbs',
						'measurement_notes' => 'foo',
					),
					array(
						'dimensions_length' => '5 in',
						'dimensions_weight' => '3 lbs',
						'measurement_notes' => 'meow',
					),
				)
			)
		));
		$this->assertGreaterThan(0, $vn_object_id);
		$this->opt_object = new ca_objects($vn_object_id);

		$vn_rel_object_id = $this->opn_rel_object_id = $this->addTestRecord('ca_objects', array(
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
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'related'
					)
				),
			),
			'attributes' => array(
				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '1 in',
						'measurement_notes' => 'test',
					),
				)
			)
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
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Max",
					"middlename" => "",
					"surname" => "Power",
					"type_id" => "alt",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$this->opn_entity_id1 = $vn_entity_id;

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'bs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"middlename" => "",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'publisher',
						'effective_date' => '2014-2015',
						'source_info' => 'Homer'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);
		$this->opn_entity_id2 = $vn_entity_id;
	}
	# -------------------------------------------------------
	public function testBasicFieldsSingleRow() {
		// Get fields for primary rows (single row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBasicFieldsWithIfDefSingleRow() {
		// Get fields for primary rows with <ifdef> (single row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifdef code='ca_objects.idno'> (^ca_objects.idno)</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBasicFieldsWithIfNotDefSingleRow() {
		// Get fields for primary rows with <ifnotdef> (single row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifnotdef code='ca_objects.idno'> (^ca_objects.idno)</ifnotdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBasicAttributesSingleRowWithIfDef() {
		// Get fields for primary rows (single row)
		$vm_ret = DisplayTemplateParser::evaluate("<ifdef code='ca_objects.description'>Description: ^ca_objects.description", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Description: First description;Second description;Third description', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBasicFieldsMultipleRows() {
		// Get fields for primary rows (multiple rows)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name (^ca_objects.idno)", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
		$this->assertEquals('Name: Another image (TEST.2)', $vm_ret[1]);
	}
	# -------------------------------------------------------
	public function testBasicFieldsWithIfDefMultipleRows() {
		// Get fields for primary rows with <ifdef> (multiple row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifdef code='ca_objects.idno'> (^ca_objects.idno)</ifdef>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
		$this->assertEquals('Name: Another image (TEST.2)', $vm_ret[1]);
	}
	# -------------------------------------------------------
	public function testBasicFieldsWithIfNotDefMultipleRows() {
		// Get fields for primary rows with <ifnotdef> (multiple row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifnotdef code='ca_objects.idno'> (^ca_objects.idno)</ifnotdef>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('Name: My test image', $vm_ret[0]);
		$this->assertEquals('Name: Another image', $vm_ret[1]);
	}
	# -------------------------------------------------------
	public function testBasicFormatWithRelated() {
		// Get related values
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => ^ca_entities.preferred_labels.displayname", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson;Bart Simpson', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testBasicFormatWithRelatedAndTagOpts() {
		// Get related values with tag-option delimiter
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => ^ca_entities.preferred_labels.displayname%delimiter=,_", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson, Bart Simpson', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValues() {
		// Get related values in <unit>
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', '>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan)</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989), Bart Simpson ()', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndRestrictToTypes() {
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', ' restrictToTypes='ind'>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan)</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989), Bart Simpson ()', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', ' restrictToTypes='org'>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan)</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => ', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndExcludeTypes() {
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', ' excludeTypes='org'>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan)</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989), Bart Simpson ()', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', ' excludeTypes='ind'>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan)</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => ', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndIfDef() {
		// Get related values in <unit> with <ifdef>
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', '>^ca_entities.preferred_labels.displayname<ifdef code='ca_entities.lifespan'> (^ca_entities.lifespan)</ifdef></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989), Bart Simpson', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndIfDefAndRestrictToRelationshipTypes() {
		// Get related values in <unit> with <ifdef> and restrictToRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' restrictToRelationshipTypes='creator' delimiter=', '>^ca_entities.preferred_labels.displayname<ifdef code='ca_entities.lifespan'> (^ca_entities.lifespan)</ifdef></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndIfDefAndMultRestrictToRelationshipTypes() {
		// Get related values in <unit> with <ifdef> and restrictToRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' restrictToRelationshipTypes='creator,publisher' delimiter=', '>^ca_entities.preferred_labels.displayname<ifdef code='ca_entities.lifespan'> (^ca_entities.lifespan)</ifdef></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989), Bart Simpson', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndIfDefAndExcludeRelationshipTypes() {
		// Get related values in <unit> with <ifdef> and excludeRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' excludeRelationshipTypes='creator' delimiter=', '>^ca_entities.preferred_labels.displayname<ifdef code='ca_entities.lifespan'> (^ca_entities.lifespan)</ifdef></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Bart Simpson', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitsWithRelatedValuesAndIfDefAndMultExcludeRelationshipTypes() {
		// Get related values in <unit> with <ifdef> and excludeRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' excludeRelationshipTypes='creator,publisher' delimiter=', '>^ca_entities.preferred_labels.displayname<ifdef code='ca_entities.lifespan'> (^ca_entities.lifespan)</ifdef></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('My test image (TEST.1) => ', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testNestedUnits() {
		// Get related values in <unit>
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_entities' delimiter=', '>^ca_entities.preferred_labels.displayname (^ca_entities.lifespan) <unit relativeTo='ca_objects'>[Back to ^ca_objects.preferred_labels.name]</unit></unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Homer J. Simpson (after December 17 1989) [Back to My test image], Bart Simpson () [Back to My test image]', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testNestedUnitsWithIfDef() {
		// Get related values in <unit> with <ifdef>
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name (^ca_objects.idno) => <unit relativeTo='ca_objects.related' delimiter=', '>^ca_objects.preferred_labels.name</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('My test image (TEST.1) => Another image', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithIfCount() {
		// <ifcount>
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code='ca_entities' min='0' max='2'>^ca_entities.preferred_labels.displayname</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Homer J. Simpson, Bart Simpson', $vm_ret[0]);

		// <ifcount>
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFormatsWithIfCountAndRestrictToRelationshipTypes() {
		// <ifcount> with restrictToRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount restrictToRelationshipTypes='publisher' code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=publisher</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("<ifcount restrictToRelationshipTypes='publisher,creator' code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=creator,publisher</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFormatsWithIfCountAndExcludeRelationshipTypes() {
		// <ifcount> with excludeRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount excludeRelationshipTypes='publisher' code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=publisher</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("<ifcount excludeRelationshipTypes='publisher,creator' code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=publisher</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', '));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFormatsWithIfCountAndIncludeBlanks() {
		// <ifcount>
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', ', 'includeBlankValuesInArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);

		// <ifcount> with restrictToRelationshipTypes
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount restrictToRelationshipTypes='publisher' code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=publisher</ifcount>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true, 'delimiter' => ', ', 'includeBlankValuesInArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFormatsWithCase() {
		$vm_ret = DisplayTemplateParser::evaluate("
			<case>
				<ifcount code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname</ifcount>
				<ifnotdef code='ca_objects.description'>Description was not set</ifnotdef>
				<ifdef code='ca_objects.idno'>Idno was ^ca_objects.idno</ifdef>
				<ifdef code='ca_objects.preferred_labels.name'>Label was ^ca_objects.preferred_labels.name</ifdef>
			</case>
		", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true, 'delimiter' => ', ', 'includeBlankValuesInArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Idno was TEST.1', trim($vm_ret[0]));		// <case> includes whitespace we need to get rid of for comparison


		$vm_ret = DisplayTemplateParser::evaluate("
			<case>
				<ifcount code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname</ifcount>
				<ifnotdef code='ca_objects.description'>Description was not set</ifnotdef>
				<ifdef code='ca_objects.preferred_labels.name'>Label was ^ca_objects.preferred_labels.name</ifdef>
				<ifdef code='ca_objects.idno'>Idno was ^ca_objects.idno</ifdef>
			</case>
		", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true, 'delimiter' => ', ', 'includeBlankValuesInArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Label was My test image', trim($vm_ret[0]));	// <case> includes whitespace we need to get rid of for comparison
	}
	# -------------------------------------------------------
	public function testFormatWithCaseDefault() {
		$vm_ret = DisplayTemplateParser::evaluate("
			<case>
				<ifcount code='ca_entities' min='1' max='1'>^ca_entities.preferred_labels.displayname</ifcount>
				<ifnotdef code='ca_objects.description'>Description was not set</ifnotdef>
				<unit>Default ^ca_objects.idno</unit>
			</case>
		", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true, 'delimiter' => ', ', 'includeBlankValuesInArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Default TEST.1', trim($vm_ret[0]));	// <case> includes whitespace we need to get rid of for comparison
	}
	# -------------------------------------------------------
	public function testFormatsWithHTML() {
		// Get fields for primary rows (single row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: <b>^ca_objects.preferred_labels.name</b> (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: <b>My test image</b> (TEST.1)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	//public function testFormatsWithSort() {
		// TODO: add sort and sortDirection options to parser
	//}
	# -------------------------------------------------------
	public function testFormatsWithSkipIfExpressionOption() {
		$vm_ret = DisplayTemplateParser::evaluate("Name: <b>^ca_objects.preferred_labels.name</b> (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('skipIfExpression' => '^ca_objects.description =~ /First/', 'returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("Name: <b>^ca_objects.preferred_labels.name</b> (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('skipIfExpression' => '^ca_objects.description =~ /NICHTS/', 'returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Name: <b>My test image</b> (TEST.1)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithRequireLinkTagsOption() {
		$vm_ret = DisplayTemplateParser::evaluate("URL: <l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains("editor/objects/ObjectEditor/Summary/object_id/{$this->opn_object_id}/rel/1\">My test image</a> (TEST.1)", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("URL: ^ca_objects.preferred_labels.name (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('requireLinkTags' => false, 'returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains("editor/objects/ObjectEditor/Summary/object_id/{$this->opn_object_id}/rel/1'>URL: My test image (TEST.1)</a>", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithPlaceholderPrefixOption() {
		$vm_ret = DisplayTemplateParser::evaluate("URL: <b>^url_source</b> (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('placeholderPrefix' => 'ca_objects.external_link', 'returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('URL: <b>My URL source;Another URL source</b> (TEST.1)', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithBetween() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_objects.dimensions'>^ca_objects.dimensions.dimensions_length <between>X</between> ^ca_objects.dimensions.dimensions_weight</unit>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('10.1 in X 2 lb; 5 in X 3 lb', $vm_ret[0]);
		$this->assertEquals('1 in', trim($vm_ret[1]));

		$vm_ret = DisplayTemplateParser::evaluate("<unit>^ca_objects.dimensions.dimensions_weight <between>X</between> ^ca_objects.dimensions.dimensions_length</unit>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('2 lb X 10.1 in; 3 lb X 5 in', $vm_ret[0]);
		$this->assertEquals('1 in', trim($vm_ret[1]));
	}
	# -------------------------------------------------------
	public function testFormatsWithMore() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit>^ca_objects.dimensions.dimensions_length <more>X</more> ^ca_objects.dimensions.dimensions_weight</unit>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('10.1 in X 2 lb; 5 in X 3 lb', $vm_ret[0]);
		$this->assertEquals('1 in', trim($vm_ret[1]));

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_objects'>^ca_objects.dimensions.dimensions_weight <more>X</more> ^ca_objects.dimensions.dimensions_length</unit>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertEquals('2 lb X 10.1 in; 3 lb X 5 in', $vm_ret[0]);
		$this->assertEquals('X 1 in', trim($vm_ret[1]));
	}
	# -------------------------------------------------------
	public function testFormatsWithIf() {
		$vm_ret = DisplayTemplateParser::evaluate("Description: <if rule='^ca_objects.description =~ /First/'>^ca_objects.description%delimiter=,_</if>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Description: First description, Second description, Third description', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("Description: <if rule='^ca_objects.description =~ /Fourth/'>^ca_objects.description%delimiter=,_</if>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Description: ', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithExpression() {
		$vm_ret = DisplayTemplateParser::evaluate("Expression: word count is <expression>wc(^ca_objects.description)</expression>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Expression: word count is 6', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithBareUnit() {
		$vm_ret = DisplayTemplateParser::evaluate("Here are the descriptions: <unit delimiter='... '>^ca_objects.description</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Here are the descriptions: First description... Second description... Third description', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithRepeatingAttributeUnit() {
		$vm_ret = DisplayTemplateParser::evaluate("Dimensions are: <unit delimiter='; ' relativeTo='ca_objects.dimensions'>^ca_objects.dimensions.dimensions_length / ^ca_objects.dimensions.dimensions_weight / ^ca_objects.dimensions.measurement_notes</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));

		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);

		$this->assertEquals('Dimensions are: 10.1 in / 2 lb / foo; 5 in / 3 lb / meow', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithTagOpts() {
		$vm_ret = DisplayTemplateParser::evaluate("Here are the descriptions: ^ca_objects.description%delimiter=,_", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Here are the descriptions: First description, Second description, Third description', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithPrimary() {
		$vm_ret = DisplayTemplateParser::evaluate("The current primary is ^primary. This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current primary is ca_objects. This has nothing to do with object TEST.1", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' '>The current primary is ^primary.</unit> This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current primary is ca_entities. The current primary is ca_entities. This has nothing to do with object TEST.1", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithCount() {
		$vm_ret = DisplayTemplateParser::evaluate("The current count is ^count. This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current count is 1. This has nothing to do with object TEST.1", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' '>The current count is ^count.</unit> This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current count is 2. The current count is 2. This has nothing to do with object TEST.1", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithIndex() {
		$vm_ret = DisplayTemplateParser::evaluate("This is row ^index of ^count", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("This is row 1 of 1", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' '>^ca_entities.preferred_labels.displayname is row ^index of ^count.</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson is row 1 of 2. Bart Simpson is row 2 of 2.", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFormatsWithDate() {
		$vs_date = date('d M Y'); // you execute this test right at the stroke of midnight it might fail...
		$vm_ret = DisplayTemplateParser::evaluate("The current date is ^DATE. This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current date is {$vs_date}. This has nothing to do with object TEST.1", $vm_ret[0]);

		$vs_date = date('M:d:Y'); // you execute this test right at the stroke of midnight it might fail...
		$vm_ret = DisplayTemplateParser::evaluate("The current date is ^DATE%format=M:d:Y. This has nothing to do with object ^ca_objects.idno", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("The current date is {$vs_date}. This has nothing to do with object TEST.1", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testIfDefWithOrCodes() {
		$vm_ret = DisplayTemplateParser::evaluate("<ifdef code='ca_objects.formatNotes|ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value was detected', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<ifdef code='ca_objects.formatNotes;ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("<ifdef code='ca_objects.preferred_labels.name;ca_objects.idno;ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value was detected', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<ifdef code='ca_objects.preferred_labels.name;ca_objects.idno;ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value was detected', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<ifnotdef code='ca_objects.preferred_labels.name;ca_objects.idno;ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("<ifnotdef code='ca_objects.formatNotes,ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);

		$vm_ret = DisplayTemplateParser::evaluate("<ifnotdef code='ca_objects.formatNotes|ca_objects.description'>Value was detected</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value was detected', $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testIfCountWithOrCodes() {
		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code='ca_objects.description|ca_objects.preferred_labels.name' min='1' max='4'>Value was detected</ifcount>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Value was detected', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code='ca_objects.formatNotes|ca_objects.preferred_labels.name' min='1' max='4'>Value was detected</ifcount>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(0, $vm_ret);
	}
	# -------------------------------------------------------
	public function testFormatsAsString() {
		// Get fields for primary rows with <ifnotdef> (multiple row)
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifnotdef code='ca_objects.idno'> (^ca_objects.idno)</ifnotdef>", "ca_objects", array($this->opn_object_id, $this->opn_rel_object_id), array('returnAsArray' => false));
		$this->assertInternalType('string', $vm_ret);
		$this->assertEquals('Name: My test image; Name: Another image', $vm_ret);
	}
	# -------------------------------------------------------
	public function testDirectivesNestedInStaticHTMLForDisplay() {
		// Relative to relationship table as is done for displays
		$va_relation_ids = $this->opt_object->get('ca_objects_x_entities.relation_id', ['returnAsArray' => true]);

		$vm_ret = DisplayTemplateParser::evaluate('<ul style="list-style-type:none"><li><unit relativeTo="ca_entities"><l>^ca_entities.preferred_labels.displayname</l> (^ca_entities.idno)</li></unit></ul>', "ca_objects_x_entities", $va_relation_ids, array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(2, $vm_ret);
		$this->assertInternalType('string', $vm_ret[0]);
		$this->assertInternalType('string', $vm_ret[1]);

		$this->assertContains("editor/entities/EntityEditor/Summary/entity_id/".$this->opn_entity_id1."/rel/1\">Homer J. Simpson</a> (hjs)</li></ul>", $vm_ret[0]);
		$this->assertContains("editor/entities/EntityEditor/Summary/entity_id/".$this->opn_entity_id2."/rel/1\">Bart Simpson</a> (bs)</li></ul>", $vm_ret[1]);
	}
	# -------------------------------------------------------
	public function testStringFormattingTagOpts() {
		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%toUpper=1", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("MY TEST IMAGE", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%toLower=1", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("my test image", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%toUpper&start=1&length=5", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Y TES", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=10&ellipsis", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("My test...", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=13&ellipsis", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("My test image", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=12&ellipsis=1", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("My test i...", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=11&ellipsis=1&toLower", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("my test ...", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=11&ellipsis=1&toLower&trim", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("my test...", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("^ca_objects.preferred_labels.name%length=11&ellipsis=0&toLower=1", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("my test ima", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testUnitStartLength() {
		if(false){
		// Repeating attribute
		$vm_ret = DisplayTemplateParser::evaluate("Here are the descriptions: <unit delimiter='... ' start='0' length='1'>^ca_objects.description</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Here are the descriptions: First description', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("Here are the descriptions: <unit delimiter='... ' start='1' length='2'>^ca_objects.description</unit><whenunitomits>omit=^omitcount</whenunitomits>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals('Here are the descriptions: Second description... Third description', $vm_ret[0]);

		// Related tables
		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='0' length='1'>^ca_entities.preferred_labels.displayname is row ^index of ^count.</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson is row 1 of 2.", $vm_ret[0]);
		}
		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='1' length='1'>^ca_entities.preferred_labels.displayname is row ^index of ^count.</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Bart Simpson is row 2 of 2.", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='0' length='2'>^ca_entities.preferred_labels.displayname is row ^index of ^count.</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson is row 1 of 2. Bart Simpson is row 2 of 2.", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='1' length='2'>^ca_entities.preferred_labels.displayname</unit><whenunitomits> and ^omitcount more</whenunitomits>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Bart Simpson and 1 more", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='0' length='2'>^ca_entities.preferred_labels.displayname</unit><whenunitomits> and ^omitcount more</whenunitomits>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson Bart Simpson", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter=' ' start='0' length='1'>^ca_entities.preferred_labels.displayname</unit><whenunitomits> and ^omitcount more</whenunitomits>; <unit relativeTo='ca_entities' delimiter=' ' start='1' length='2'>^ca_entities.preferred_labels.displayname</unit><whenunitomits> and ^omitcount more</whenunitomits>; <unit relativeTo='ca_entities' delimiter=' ' start='0' length='2'>^ca_entities.preferred_labels.displayname</unit><whenunitomits> and ^omitcount more</whenunitomits>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson and 1 more; Bart Simpson and 1 more; Homer J. Simpson Bart Simpson", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testTemplateWithNewLines() {

		$vm_ret = DisplayTemplateParser::evaluate('<ifcount code="ca_entities" min="1"><p><strong>People</strong><br/><unit delimiter="; " relativeTo="ca_entities"><a href="/index.php/MultiSearch/Index/search/ca_entities.entity_id:^ca_entities.entity_id">^ca_entities.preferred_labels.displayname</a></unit></p></ifcount>', "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);

		$this->assertContains("/MultiSearch/Index/search/ca_entities.entity_id:".$this->opn_entity_id1."\">Homer J. Simpson</a>", $vm_ret[0]);
		$this->assertContains("/MultiSearch/Index/search/ca_entities.entity_id:".$this->opn_entity_id2."\">Bart Simpson</a>", $vm_ret[0]);


		$vm_ret = DisplayTemplateParser::evaluate('<ifcount code="ca_entities.related" min="1">
				<ifcount code="ca_entities.related" min="1" max="1"><img src="left1.png"><strong>Related person</strong><img src="right1.png"><br/></ifcount>
				<ifcount code="ca_entities.related" min="2"><img src="left2.png"><strong>Related people</strong><img src="right2.png"><br/></ifcount>
				<unit relativeTo="ca_entities.related" delimiter="<br/>"><l>^ca_entities.preferred_labels.displayname</l></unit><br/><br/>
				</ifcount>', "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));

		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertContains("editor/entities/EntityEditor/Summary/entity_id/".$this->opn_entity_id1."/rel/1\">Homer J. Simpson</a>", $vm_ret[0]);
		$this->assertContains("editor/entities/EntityEditor/Summary/entity_id/".$this->opn_entity_id2."/rel/1\">Bart Simpson</a>", $vm_ret[0]);
		$this->assertContains('<img src="left2.png" /><strong>Related people</strong><img src="right2.png" />', $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<ifcount code=\"ca_entities\" min=\"1\">
			<script type='text/javascript'>
				jQuery(document).ready(function() {
					/*
					Carousel initialization
					*/
					$('.jcarousel')
						.jcarousel({
							// Options go here
						});

					/*
					 Prev control initialization
					 */
					$('#detailScrollButtonPrevious')
						.on('jcarouselcontrol:active', function() {
							$(this).removeClass('inactive');
						})
						.on('jcarouselcontrol:inactive', function() {
							$(this).addClass('inactive');
						})
						.jcarouselControl({
							// Options go here
							target: '-=1'
						});

					/*
					 Next control initialization
					 */
					$('#detailScrollButtonNext')
						.on('jcarouselcontrol:active', function() {
							$(this).removeClass('inactive');
						})
						.on('jcarouselcontrol:inactive', function() {
							$(this).addClass('inactive');
						})
						.jcarouselControl({
							// Options go here
							target: '+=1'
						});
				});
			</script></ifcount>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));

		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);

		$this->assertEquals("
			<script type=\"text/javascript\">
				jQuery(document).ready(function() {
					/*
					Carousel initialization
					*/
					$('.jcarousel')
						.jcarousel({
							// Options go here
						});

					/*
					 Prev control initialization
					 */
					$('#detailScrollButtonPrevious')
						.on('jcarouselcontrol:active', function() {
							$(this).removeClass('inactive');
						})
						.on('jcarouselcontrol:inactive', function() {
							$(this).addClass('inactive');
						})
						.jcarouselControl({
							// Options go here
							target: '-=1'
						});

					/*
					 Next control initialization
					 */
					$('#detailScrollButtonNext')
						.on('jcarouselcontrol:active', function() {
							$(this).removeClass('inactive');
						})
						.on('jcarouselcontrol:inactive', function() {
							$(this).addClass('inactive');
						})
						.jcarouselControl({
							// Options go here
							target: '+=1'
						});
				});
			</script>", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testAttributesWithHTML() {
		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter='<br/>'>^ca_entities.preferred_labels.displayname</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson<br/>Bart Simpson", $vm_ret[0]);

		$vm_ret = DisplayTemplateParser::evaluate("<unit relativeTo='ca_entities' delimiter='  <br/>  '>^ca_entities.preferred_labels.displayname</unit>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals("Homer J. Simpson  <br/>  Bart Simpson", $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testStartLength() {
		$this->assertEquals('5 in x 3 lb', trim(DisplayTemplateParser::evaluate("
		<unit relativeTo='ca_objects.dimensions' start='1' length='1'>
			^ca_objects.dimensions.dimensions_length x ^ca_objects.dimensions.dimensions_weight
		</unit>", 'ca_objects', array($this->opn_object_id))));
	}
	# -------------------------------------------------------
}
