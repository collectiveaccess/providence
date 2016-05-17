<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/SimpleSearchQueryTest.php
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
 * Class DateSearchQueryTest
 * Note: Requires testing profile!
 */
class DateSearchQueryTest extends AbstractSearchQueryTest {
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
				// straight date
				'coverageDates' => array(
					array(
						'coverageDates' => '01/28/1985 @ 10am'
					)
				),

				// Date in a container
				'date' => array(
					array(
						'dates_value' => '1840-1850'
					)
				)
			)
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Another test image",
				),
			),
			'attributes' => array(
				'coverageDates' => array(
					array(
						'coverageDates' => '1986'
					)
				)
			)
		)));

		// search queries
		$this->setSearchQueries(array(
			// full text
			'1985' => 1, '1986' => 1,

			// basic stuff
			'ca_objects.coverageDates:"1985-1986"' => 2,
			'ca_objects.coverageDates:"1984-1985"' => 1,
			'ca_objects.coverageDates:"1984 - 1985"' => 1,
			'ca_objects.coverageDates:"1985"' => 1,
			'ca_objects.coverageDates:"01/1985"' => 1,
			'ca_objects.coverageDates:"01-28-1985"' => 1,
			'ca_objects.coverageDates:"01.28.1985"' => 1,
			'ca_objects.coverageDates:"28-JAN-1985"' => 1,
			'ca_objects.coverageDates:"28-JAN-85"' => 1,

			// ranges in weird 'special' notations
			'ca_objects.coverageDates:"1980s"' => 2,
			'ca_objects.coverageDates:"Winter 1985"' => 1,
			'ca_objects.coverageDates:"Spring 2015"' => 0,
			'ca_objects.coverageDates:"circa 1985"' => 1,
			'ca_objects.coverageDates:"20th century"' => 2,
			'ca_objects.coverageDates:"198-"' => 2,
			'ca_objects.coverageDates:"1990\'s"' => 0,
			'ca_objects.coverageDates:"1980\'s"' => 2,

			// precise ranges in different notations
			'ca_objects.coverageDates:"Between June 5, 2007 and June 15 2007"' => 0,
			'ca_objects.coverageDates:"Between January 27, 1985 and January 29 1985"' => 1,
			'ca_objects.coverageDates:"Between January 01, 1985 and December 31 1986"' => 2,

			'ca_objects.coverageDates:"June 5, 2007 - June 15, 2007"' => 0,
			'ca_objects.coverageDates:"January 27, 1985 - January 29, 1985"' => 1,
			'ca_objects.coverageDates:"January 1, 1985 - December 31, 1986"' => 2,

			'ca_objects.coverageDates:"From 6/5/2007 to 6/15/2007"' => 0,
			'ca_objects.coverageDates:"From 1/27/1985 to 1/29/1985"' => 1,
			'ca_objects.coverageDates:"From 1/1/1985 to 12/31/1986"' => 2,

			// times
			'ca_objects.coverageDates:"1/28/1985 @ 8am - 1/28/1985 @ 9am"' => 0,
			'ca_objects.coverageDates:"1/28/1985 @ 9am - 1/28/1985 @ 11am"' => 1,
			'ca_objects.coverageDates:"1/28/1986 @ 8am - 1/28/1986 @ 9am"' => 1,

			// # qualifier
			'ca_objects.coverageDates:"#1986"' => 1,

			// >, >=, <, <= qualifiers
			'ca_objects.coverageDates:">=1985"' => 2,
			'ca_objects.coverageDates:">1985"' => 1,
			'ca_objects.coverageDates:"<1986"' => 1,
			'ca_objects.coverageDates:"<=1986"' => 2,

			// in container
			'ca_objects.date.dates_value:"1845"' => 1,
			'ca_objects.date.dates_value:1845' => 1,

			// these are valid dates for data entry but apparently they don't work that well in combination with the search
			//'ca_objects.coverageDates:"01/28/1985 @ 4:43:03a.m. - 01/28/1985 @ 4:43:03p.m."' => 1,
			//'ca_objects.coverageDates:"01/28/1985 @ 07:43:03 - 01/28/1985 @ 11:43:03"' => 1,
			//'ca_objects.coverageDates:"01/28/1986 @ 18:43:03 - 01/28/1986 @ 19:43:03"' => 1,
		));
	}
	# -------------------------------------------------------
}
