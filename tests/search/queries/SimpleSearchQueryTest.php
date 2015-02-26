<?php
/** ---------------------------------------------------------------------
 * tests/search/queries/SimpleSearchQueryTest.php
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

require_once(__CA_BASE_DIR__ . '/tests/search/AbstractSearchQueryTest.php');

/**
 * Class SimpleSearchQueryTest
 * Note: Requires dublincore profile!
 */
class SimpleSearchQueryTest extends AbstractSearchQueryTest {
	# -------------------------------------------------------
	public function setUp() {
		// search subject table
		$this->setPrimaryTable('ca_objects');

		// test data
		$this->setTestData(array(
			'ca_objects' => array(
				'test' => array(
					'intrinsic_fields' => array(
						'type_id' => 'image',
					),
					'preferred_labels' => array(
						array(
							"locale" => "en_US",
							"name" => "My Test Image",
						),
					),
				),
			),
		));

		// search queries
		$this->setSearchQueries(array(
			'My Test Image' => 1,
			'test' => 1,
			'ca_objects.type_id:image' => 1,
			'asdf' => 0,
		));

		// don't forget to call parent so that data actually is inserted
		parent::setUp();
	}
	# -------------------------------------------------------
}
