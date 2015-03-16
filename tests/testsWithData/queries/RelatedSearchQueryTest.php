<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/RelatedSearchQueryTest.php
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

require_once(__CA_BASE_DIR__ . '/tests/testsWithData/AbstractSearchQueryTest.php');

/**
 * Class RelatedSearchQueryTest
 * Note: Requires testing profile!
 */
class RelatedSearchQueryTest extends AbstractSearchQueryTest {
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		// search subject table
		$this->setPrimaryTable('ca_objects');

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

		// search queries
		$this->setSearchQueries(array(
			// establish we're actually searching objects
			'My test image' => 1,

			// search on entity data
			'Homer J. Simpson' => 1,
			'"Homer J. Simpson"' => 1,
			'Simp*' => 1,
			'ca_entity_labels.displayname:"Homer J. Simpson"' => 1,
			'ca_entity_labels.displayname:"Homer"' => 1,
			'ca_entity_labels.forename:"Homer"' => 1,
			'ca_entity_labels.surname:"Simpson"' => 1,
			'ca_entity_labels.idno:"Homer J. Simpson"' => 1,
			'ca_entity_labels.entity_id:'.$vn_entity_id => 1,
			'ca_entity_labels.entity_id:"'.$vn_entity_id.'"' => 1,
			'entity_id:"'.$vn_entity_id.'"' => 1, // access point

			'ca_entity_labels.displayname:"John Doe"' => 0,
			'John Doe' => 0,

			// filter on rel type
			'ca_entities.preferred_labels.displayname/creator:"Homer J. Simpson"' => 1,
			'ca_entities.preferred_labels.displayname/creator:"John Doe"' => 0,
			'ca_entities.preferred_labels.displayname/publisher:"Homer J. Simpson"' => 0, // existing rel type
			// @todo 'ca_entities.preferred_labels.displayname/character:"Homer J. Simpson"' => 0, // nonexisting rel type

		));
	}
	# -------------------------------------------------------
}
