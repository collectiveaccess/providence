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

		// situation is as follows:
		// we have 2 objects, an image and a dataset
		// the image is related to homer (creator) and bart (publisher)
		// the dataset is just related to bart (creator)

		$vn_image_id = $this->addTestRecord('ca_objects', array(
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
		$this->assertGreaterThan(0, $vn_image_id);

		$vn_dataset_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'dataset',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test dataset",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_dataset_id);

		$vn_homer_id = $this->addTestRecord('ca_entities', array(
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
						'object_id' => $vn_image_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));
		$this->assertGreaterThan(0, $vn_homer_id);

		$vn_bart_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'bs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_image_id,
						'type_id' => 'publisher',
						'effective_date' => '2015',
						'source_info' => 'Me'
					),
					array(
						'object_id' => $vn_dataset_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));
		$this->assertGreaterThan(0, $vn_bart_id);;

		// search queries
		$this->setSearchQueries(array(
			// establish we're actually searching objects
			'My test image' => 1,
			'My test' => 2,

			// search on entity data
			'Homer J. Simpson' => 1,
			'"Homer J. Simpson"' => 1,
			'Bart Simpson' => 2,
			'"Bart Simpson"' => 2,
			'Simp*' => 2,

			'ca_entity_labels.displayname:"Homer J. Simpson"' => 1,
			'ca_entity_labels.displayname:"Bart Simpson"' => 2,
			'ca_entity_labels.displayname:"Homer"' => 1, // not a phrase search

			'ca_entity_labels.surname:"Simpson"' => 2,
			'ca_entity_labels.forename:"Homer"' => 1,
			'ca_entity_labels.forename:"Bart"' => 2,

			'ca_entities.idno:"hjs"' => 1,
			'ca_entity_labels.entity_id:'.$vn_homer_id => 1,
			'ca_entity_labels.entity_id:"'.$vn_homer_id.'"' => 1,
			'ca_entity_labels.entity_id:'.$vn_bart_id => 2,
			'ca_entity_labels.entity_id:"'.$vn_bart_id.'"' => 2,
			'entity_id:"'.$vn_homer_id.'"' => 1, 	// access point
			'entity_id:"'.$vn_bart_id.'"' => 2,		// access point

			'ca_entity_labels.displayname:"John Doe"' => 0, // nonexisting entity
			'John Doe' => 0,

			// rel type filters
			'ca_entity_labels.displayname/creator:"Homer J. Simpson"' => 1,
			// Bart is related to 2 objects, but only one of the rels is 'creator'
			'ca_entity_labels.displayname/creator:"Bart Simpson"' => 1,
			// doesn't exist
			'ca_entity_labels.displayname/creator:"John Doe"' => 0,
			// homer hasn't published anything
			'ca_entity_labels.displayname/publisher:"Homer J. Simpson"' => 0,
			// bart has though
			'ca_entity_labels.displayname/publisher:"Bart Simpson"' => 1,
			'ca_objects_x_entities.count:1' => 1,
			'ca_objects_x_entities.count:[1 to 10]' => 2,
		));
	}
	# -------------------------------------------------------
}
