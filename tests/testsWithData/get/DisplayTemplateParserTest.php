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
	 * primary key ID of the last created entity
	 * @var int
	 */
	private $opn_entity_id = null;
	
	private $opn_object_id = null;
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
		));
		$this->assertGreaterThan(0, $vn_rel_object_id);

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
		
		// Get fields for primary rows
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name (^ca_objects.idno)", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
		
		// Get fields for primary rows with <ifdef>
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifdef code='ca_objects.idno'> (^ca_objects.idno)</ifdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image (TEST.1)', $vm_ret[0]);
		
		// Get fields for primary rows with <ifnotdef>
		$vm_ret = DisplayTemplateParser::evaluate("Name: ^ca_objects.preferred_labels.name<ifnotdef code='ca_objects.idno'> (^ca_objects.idno)</ifnotdef>", "ca_objects", array($this->opn_object_id), array('returnAsArray' => true));
		$this->assertInternalType('array', $vm_ret);
		$this->assertEquals('Name: My test image', $vm_ret[0]);

	}
	# -------------------------------------------------------
}
