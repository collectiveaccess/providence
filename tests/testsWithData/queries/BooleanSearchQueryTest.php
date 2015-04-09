<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/BooleanSearchQueryTest.php
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
 * Class BooleanSearchQueryTest
 * Note: Requires testing profile!
 */
class BooleanSearchQueryTest extends AbstractSearchQueryTest {
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
		)));


		// search queries
		$this->setSearchQueries(array(
			// establish that data was set correctly
			'ca_objects.type_id:image' => 1,
			'ca_object_labels.name:test' => 1,

			// AND
			'ca_objects.type_id:image AND ca_object_labels.name:test' => 1, // 1 and 1 = 1
			'ca_objects.type_id:image AND ca_object_labels.name:doesntexist' => 0, // 1 and 0 = 0
			'ca_objects.type_id:dataset AND ca_object_labels.name:test' => 0, // 0 and 1 = 0
			'ca_objects.type_id:dataset AND ca_object_labels.name:doesntexist' => 0, // 0 and 0 = 0

			// OR
			'ca_objects.type_id:image OR ca_object_labels.name:test' => 1, // 1 or 1 = 1
			'ca_objects.type_id:image OR ca_object_labels.name:doesntexist' => 1, // 1 or 0 = 1
			'ca_objects.type_id:dataset OR ca_object_labels.name:test' => 1, // 0 or 1 = 1
			'ca_objects.type_id:dataset OR ca_object_labels.name:doesntexist' => 0, // 0 or 0 = 0
		));
	}
	# -------------------------------------------------------
}
