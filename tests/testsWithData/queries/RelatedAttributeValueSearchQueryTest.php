<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/RelatedAttributeValueSearchQueryTest.php
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
require_once(__CA_LIB_DIR__."/Search/SearchIndexer.php");

/**
 * Class RelatedAttributeValueSearchQueryTest
 * Note: Requires testing profile!
 */
class RelatedAttributeValueSearchQueryTest extends AbstractSearchQueryTest {
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
		$vn_lot_id = $this->addTestRecord('ca_object_lots', array(
			'intrinsic_fields' => array(
				'type_id' => 'purchase',
				'idno_stub' => 'test_purchase',
				'lot_status_id' => 'accessioned'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A test purchase",
				),
			),
			'attributes' => array(
				'description' => array(
					array(
						'description' => 'Bacon ipsum dolor amet turkey brisket hamburger drumstick pork belly beef flank ham tongue'
					)
				),
				'acquisition_date' => array(
					array(
						'acquisition_date' => 'January 1985'
					)
				),
			)
		));

		$this->assertGreaterThan(0, $vn_lot_id);

		$this->assertGreaterThan(0, $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'lot_id' => $vn_lot_id,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			)
		)));


		// search queries
		$this->setSearchQueries(array(
			'My Test Image' => 1,

			'"Bacon ipsum"' => 1,
			'ca_object_lots.description:"Bacon ipsum"' => 1,
			'ca_object_lots.acquisition_date:"1985"' => 1,
			'ca_object_lots.acquisition_date:"1984"' => 0,

			'(ca_object_lots.description:"Bacon") AND (ca_object_lots.acquisition_date:1985)' => 1,
			'(ca_object_lots.description:bacon) AND (ca_object_lots.acquisition_date:1985)' => 1,
			'(ca_object_lots.acquisition_date:1985) AND (ca_object_lots.description:"Bacon")' => 1,
			'(ca_object_lots.acquisition_date:1985) AND (ca_object_lots.description:bacon)' => 1,

			'(ca_object_lots.description:"Bacon") OR (ca_object_lots.acquisition_date:1985)' => 1,
			'(ca_object_lots.description:bacon) OR (ca_object_lots.acquisition_date:1985)' => 1,
			'(ca_object_lots.acquisition_date:1985) OR (ca_object_lots.description:"Bacon")' => 1,
			'(ca_object_lots.acquisition_date:1985) OR (ca_object_lots.description:bacon)' => 1,

			'(ca_object_lots.type_id:1) AND (ca_object_lots.acquisition_date:1985)' => 0,
		));
	}
	# -------------------------------------------------------
}
