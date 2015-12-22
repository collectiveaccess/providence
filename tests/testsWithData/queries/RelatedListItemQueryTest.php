<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/RelatedListItemQueryTest.php
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
 * Class RelatedListItemQueryTest
 * Note: Requires testing profile!
 */
class RelatedListItemQueryTest extends AbstractSearchQueryTest {
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

		$vn_test_vocab_item_id = caGetListItemID('test_vocab', 'test');
		$this->assertGreaterThan(0, $vn_test_vocab_item_id);

		$vn_image_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Just an image",
				),
			),
			'related' => array(
				'ca_list_items' => array(
					array(
						'item_id' => $vn_test_vocab_item_id,
						'type_id' => 'depicts'
					)
				),
			),
		));
		$this->assertGreaterThan(0, $vn_image_id);

		// search queries
		$this->setSearchQueries(array(
			// establish we're actually searching objects
			'Just an image' => 1,

			// search on list item data
			'Foo' => 1,
			'"Foo"' => 1,
			'Bar' => 1,
			'"Bar"' => 1,

			'ca_list_item_labels.name_singular:"Foo"' => 1,
			'ca_list_item_labels.name_singular:"Bar"' => 1,

			// doesn't exist
			'Baz' => 0,
			'"Baz"' => 0,
			'ca_list_item_labels.name_singular:"Baz"' => 0,
		));
	}
	# -------------------------------------------------------
}
