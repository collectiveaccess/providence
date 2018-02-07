<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/AttributeValueSearchQueryTest.php
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
 * Class SimpleSearchQueryTest
 * Note: Requires testing profile!
 */
class AttributeValueSearchQueryTest extends AbstractSearchQueryTest {
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

				// Georeference
				// 'georeference' => array(
// 					array(
// 						'georeference' => '1600 Amphitheatre Parkway, Mountain View, CA',
// 					),
// 				),

				// coverageNotes
				'coverageNotes' => array(
					array(
						'coverageNotes' => '', // add blank value for [BLANK] search test
					),
				),

				// Date in a container
				'date' => array(
					array(
						'dates_value' => '1985'
					)
				)
			)
		)));

		// search queries
		$this->setSearchQueries(array(
			'My Test Image' => 1,

			// plain text
			'Lorem ipsum' => 1,
			'ca_objects.internal_notes:"Lorem ipsum"' => 1,
			'ca_objects.internal_notes:"Test Image"' => 0,

			// container text
			'My URL source' => 1,
			'ca_objects.url_source:"My URL source"' => 1,
			'ca_objects.url_source:"Lorem impsum"' => 0,

			// length
			'ca_objects.dimensions_length:[9in to 11in]' => 1,
			'ca_objects.dimensions_length:[0in to 10in]' => 1,
			'ca_objects.dimensions_length:[0in to 9.99in]' => 0,
			'ca_objects.dimensions_length:10in' => 1,
			'ca_objects.dimensions_length:[25cm to 30cm]' => 1,
			'ca_objects.dimensions_length:[25cm to 11in]' => 1,
			'ca_objects.dimensions_length:[25cm to 9in]' => 0, // 25cm > 9in
			'ca_objects.dimensions_length:25.4cm' => 1,
			'ca_objects.dimensions_length:25.3cm' => 0,
			'ca_objects.dimensions_length:[0.5ft to 1ft]' => 1,
			'ca_objects.dimensions_length:[1ft to 2ft]' => 0,

			// it's not inconceivable that someone enters something like this!?
			//'ca_objects.dimensions_length:"25cm to 30 cm"' => 1, // turns out SqlSearch can't to this

			// weight
			'ca_objects.dimensions_weight:2lbs' => 1,
			'ca_objects.dimensions_weight:[1lbs to 2lbs]' => 1,
			'ca_objects.dimensions_weight:[0.8kg to 0.9kg]' => 0,
			'ca_objects.dimensions_weight:[0.8kg to 1kg]' => 1,
			'ca_objects.dimensions_weight:[800g to 1kg]' => 1,
			'ca_objects.dimensions_weight:[0.90kg to 0.91kg]' => 1,

			// Integer (numbers)
			'ca_objects.integer_test:[22 to 23]' => 1,
			'ca_objects.integer_test:23' => 1,
			'ca_objects.integer_test:[23 to 1984]' => 1,
			'ca_objects.integer_test:[0 to 9]' => 0,
			'ca_objects.integer_test:1984' => 1,
			'ca_objects.integer_test:23 AND ca_objects.integer_test:1984' => 1,
			'ca_objects.integer_test:24 AND ca_objects.integer_test:1984' => 0,

			// Currency
			'ca_objects.currency_test:$100' => 1,
			'ca_objects.currency_test:[$99.99 to $100.01]' => 1,
			'ca_objects.currency_test:EUR100' => 0,
			'ca_objects.currency_test:USD100' => 1,
			'ca_objects.currency_test:CAD100' => 0,
			// multiterm phrase query
			'ca_objects.currency_test:"100 EUR"' => 0,

			// Georeference
			// 'ca_objects.georeference:[36.4,-123.5 to 38.5,-121.9]' => 1, // actual lucene range search
// 			'ca_objects.georeference:[36.4,-121.9 to 38.5,-123.5]' => 1, // order shouldn't matter
// 			'ca_objects.georeference:[38.5,-121.9 to 36.4,-123.5]' => 1, // order shouldn't matter
// 			'ca_objects.georeference:[40.0,-121.9 to 40.1,-123.5]' => 0,
// 			'ca_objects.georeference:[38.5,-124.0 to 36.4,-123.5]' => 0,
// 
// 			'ca_objects.georeference:"[37.4224879,-122.08422 ~ 5km]"' => 1, // special range query embedded in a lucene phrase query
// 			'ca_objects.georeference:"[40.0,-125.0 ~ 5km]"' => 0,
// 			'ca_objects.georeference:"[36.4,-123.5 to 38.5,-121.9]"' => 1, // special range query embedded in a lucene phrase query
// 			'ca_objects.georeference:"[36.4,-121.9 to 38.5,-123.5]"' => 1, // order shouldn't matter
// 			'ca_objects.georeference:"[38.5,-121.9 to 36.4,-123.5]"' => 1, // order shouldn't matter
// 			'ca_objects.georeference:"[40.0,-121.9 to 40.1,-123.5]"' => 0,
// 			'ca_objects.georeference:"[38.5,-124.0 to 36.4,-123.5]"' => 0,

			// Blank values
			'ca_objects.coverageNotes:"[BLANK]"' => 1,			// actually has a blank value
			'ca_objects.description:"[BLANK]"' => 1,			// has no value at all
			'ca_objects.georeference:"[BLANK]"' => 0,
			'ca_objects.currency_test:"[BLANK]"' => 0,
			'ca_objects.integer_test:"[BLANK]"' => 0,
			'ca_objects.dimensions_weight:"[BLANK]"' => 0,
			'ca_objects.dimensions_length:"[BLANK]"' => 0,
			'ca_objects.url_source:"[BLANK]"' => 0,
			'ca_objects.internal_notes:"[BLANK]"' => 0,

			// Same thing without quotes
			// These are being replaces with a phrase search for "[BLANK]"
			// in SearchEngine.php, but we should keep these tests around
			// just in case somebody decides to remove that from the
			// SearchEngine :-)
			'ca_objects.coverageNotes:[BLANK]' => 1,		// actually has a blank value
			'ca_objects.description:[BLANK]' => 1,			// has no value at all
			'ca_objects.georeference:[BLANK]' => 0,
			'ca_objects.currency_test:[BLANK]' => 0,
			'ca_objects.integer_test:[BLANK]' => 0,
			'ca_objects.dimensions_weight:[BLANK]' => 0,
			'ca_objects.dimensions_length:[BLANK]' => 0,
			'ca_objects.url_source:[BLANK]' => 0,
			'ca_objects.internal_notes:[BLANK]' => 0,

			// dates in containers
			'ca_objects.dates_value:1985' => 1,
			'ca_objects.date.dates_value:1985' => 1, // for container advanced searches
			'ca_objects.dates_value:1986' => 0,
			'ca_objects.date.dates_value:1986' => 0, // for container advanced searches

			// same as phrases
			'ca_objects.dates_value:"1985"' => 1,
			'ca_objects.date.dates_value:"1985"' => 1, // for container advanced searches
			'ca_objects.dates_value:"1986"' => 0,
			'ca_objects.date.dates_value:"1986"' => 0, // for container advanced searches
		));
	}
	# -------------------------------------------------------
}
