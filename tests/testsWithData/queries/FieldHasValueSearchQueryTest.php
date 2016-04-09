<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/FieldHasValueSearchQueryTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * Class SimpleSearchQueryTest
 * Note: Requires testing profile!
 */
class FieldHasValueSearchQueryTest extends AbstractSearchQueryTest {
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
		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
			'attributes' => array(
				// simple text attr
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
						'dimensions_weight' => '2 lbs'
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

				// coverageNotes
				'coverageNotes' => array(
					array(
						'coverageNotes' => '', // add blank value for [BLANK] search test
					),
				),
			)
		)));

		// search queries
		$this->setSearchQueries(array(
			'My Test Image' => 1,

			// plain text
			'Lorem ipsum' => 1,

			// SET values
			'ca_object_labels.name:"[SET]"' => 1,
			'ca_objects.coverageNotes:"[SET]"' => 0,		// actually has a blank value
			'ca_objects.description:"[SET]"' => 0,			// has no value at all
			//'ca_objects.currency_test:"[SET]"' => 1,
			'ca_objects.integer_test:"[SET]"' => 1,
			'ca_objects.dimensions_weight:"[SET]"' => 1,
			'ca_objects.dimensions_length:"[SET]"' => 1,
			'ca_objects.url_source:"[SET]"' => 1,
			'ca_objects.internal_notes:"[SET]"' => 1,

			// Same thing without quotes
			// These are being replaces with a phrase search for "[SET]"
			// in SearchEngine.php, but we should keep these tests around
			// just in case somebody decides to remove that from the
			// SearchEngine :-)
			'ca_object_labels.name:[SET]' => 1,
			'ca_objects.coverageNotes:[SET]' => 0,		// actually has a blank value
			'ca_objects.description:[SET]' => 0,		// has no value at all
			//'ca_objects.currency_test:[SET]' => 1,
			'ca_objects.integer_test:[SET]' => 1,
			'ca_objects.dimensions_weight:[SET]' => 1,
			'ca_objects.dimensions_length:[SET]' => 1,
			'ca_objects.url_source:[SET]' => 1,
			'ca_objects.internal_notes:[SET]' => 1
		));
	}
	# -------------------------------------------------------
}
