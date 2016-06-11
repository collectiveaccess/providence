<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/DisplayHelpersGetTest.php
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
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class DisplayHelpersGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;

	/**
	 * primary key ID of the last created entity
	 * @var int
	 */
	private $opn_entity_id = null;
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
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_object_id);
		$this->opt_object = new ca_objects($vn_object_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
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
		$this->opn_entity_id = $vn_entity_id;
	}
	# -------------------------------------------------------
	public function testExpressionTag() {
		// unit tag inside expression
		$this->assertEquals($this->opn_entity_id, caProcessTemplateForIDs(
			'<expression>max(<unit relativeTo="ca_entities" delimiter=",">^ca_entities.entity_id</unit>)</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// just a plain tag .. 'My test image' is 13 chars
		$this->assertEquals(13, caProcessTemplateForIDs(
			'<expression>length(^ca_objects.preferred_labels)</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// plain old scalars
		$this->assertEquals(9, caProcessTemplateForIDs(
			'<expression>5 + 4</expression>',
		'ca_objects', array($this->opt_object->getPrimaryKey())));

		// get entity names and their string lengths
		$this->assertEquals('Homer J. Simpson, 16; Bart Simpson, 12', caProcessTemplateForIDs(
			'<unit relativeTo="ca_entities">^ca_entities.preferred_labels, <expression>length(^ca_entities.preferred_labels)</expression></unit>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// scalars in ifdef (false)
		$this->assertEmpty(caProcessTemplateForIDs(
			'<ifdef code="ca_objects.description"><expression>5+9</expression></ifdef>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// scalars in ifdef (true)
		$this->assertEquals(9, caProcessTemplateForIDs(
			'<ifdef code="ca_entities"><expression>5+4</expression></ifdef>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// hacked up way to count number of relationships
		$this->assertEquals(2, caProcessTemplateForIDs(
			'<expression>sizeof(<unit relativeTo="ca_entities" delimiter=",">^ca_entities.entity_id</unit>)</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// no relationships exist for collections
		$this->assertEquals(0, caProcessTemplateForIDs(
			'<expression>sizeof(<unit relativeTo="ca_collections" delimiter=",">^ca_collections.collection_id</unit>)</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// no relationships exist for collections
		$this->assertEquals(0, caProcessTemplateForIDs(
			'<expression>sizeof(<unit relativeTo="ca_collections" delimiter=",">^ca_collections.collection_id</unit>)</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));

		// age calculation
		$this->assertEquals(41, caProcessTemplateForIDs(
			'<expression>age("23 June 1912", "7 June 1954")</expression>'
		, 'ca_objects', array($this->opt_object->getPrimaryKey())));
	}
	# -------------------------------------------------------
}
