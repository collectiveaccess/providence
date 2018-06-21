<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/EntityReferencesForSetsTemplateTest.php
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
require_once(__CA_LIB_DIR__.'/Parsers/DisplayTemplateParser.php');

class EntityReferencesForSetsTemplateTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_sets
	 */
	private $opt_set = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		
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
			)
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_set_id = $this->addTestRecord('ca_sets', array(
			'intrinsic_fields' => array(
				'type_id' => 'user',
				'set_code' => 'TEST'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test set",
				),
			),
			'attributes' => array(
				'entity_reference' => array(
					array(
						'locale' => 'en_US',
						'entity_reference' => $vn_entity_id
					),
				)
			)
		));
		$this->assertGreaterThan(0, $vn_set_id);
		$this->opt_set = new ca_sets($vn_set_id);

	}
	# -------------------------------------------------------
	public function testTest() {
		// establish everything went ok
		$this->assertEquals('TEST', $this->opt_set->get('set_code'));
		// should return primary label
		$this->assertEquals('Homer J. Simpson', $this->opt_set->get('ca_sets.entity_reference', ['output' => 'text']));
		$this->assertEquals('Homer J. Simpson', $this->opt_set->getWithTemplate('^ca_sets.entity_reference'));
	}
	# -------------------------------------------------------
}
