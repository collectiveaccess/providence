<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/LengthAttributeValueSearchQueryTest.php
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
class LengthAttributeValueSearchQueryTest extends AbstractSearchQueryTest {
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
				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '10 in',
						'dimensions_width' => '30 cm',
						'dimensions_height' => '12.34cm'
					)
				),
			)
		)));

		// search queries
		$this->setSearchQueries(array(
			'ca_objects.dimensions_height:"12.35 cm"' => 0,
			'ca_objects.dimensions_height:"12.34 cm"' => 1,

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
		));
	}
	# -------------------------------------------------------
}
