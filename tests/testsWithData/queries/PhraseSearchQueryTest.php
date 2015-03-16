<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/PhraseSearchQueryTest.php
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
 * Class PhraseSearchQueryTest
 * Note: Requires testing profile!
 */
class PhraseSearchQueryTest extends AbstractSearchQueryTest {
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
					"name" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.",
				),
			),
		)));

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Lorem ipsum dolor sit amet, adipiscing consectetur elit: Test",
				),
			),
		)));

		// search queries
		$this->setSearchQueries(array(
			// basics
			'"Lorem ipsum"' => 2,
			'"Lorem ipsum sit amet"' => 0,
			'"Lorem ipsum test"' => 0,
			'"No results here"' => 0,

			// word order
			'"consectetur adipiscing elit"' => 1,
			'"adipiscing consectetur elit"' => 1,
			'"adipiscing elit"' => 1,

			// punctuation
			'"elit. Pellentesque"' => 1,
			'"elit Pellentesque"' => 1,
			'"elit: Test"' => 1,
			'"elit Test"' => 1,

			// capitalization
			'"lorem ipsum"' => 2,
			'"Dolor Sit Amet"' => 2,
			'"DOLOR SIT AMET"' => 2,
			'"ELIT: TEST"' => 1,
			'"DOLOR SIT TEST"' => 0,
			'"DOLOR SIT test"' => 0,
		));
	}
	# -------------------------------------------------------
}
