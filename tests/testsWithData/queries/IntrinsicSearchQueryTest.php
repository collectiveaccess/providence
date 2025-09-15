<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/IntrinsicSearchQueryTest.php
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
use PHPUnit\Framework\TestCase;

require_once(__CA_BASE_DIR__ . '/tests/testsWithData/AbstractSearchQueryTestClass.php');

/**
 * Class IntrinsicSearchQueryTest
 * Note: Requires testing profile!
 */
class IntrinsicSearchQueryTest extends AbstractSearchQueryTestClass {
	# -------------------------------------------------------
	protected function setUp() : void {
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
				'access' => 1,
				'status' => 4,
			),
		)));

		$vn_image_type_id = ca_lists::getItemID('object_types', 'image');

		// search queries
		$this->setSearchQueries(array(
			// type
			'ca_objects.type_id:"image"' => 1,
			'ca_objects.type_id:image' => 1,
			'ca_objects.type_id:"'.$vn_image_type_id.'"' => 1,
			'ca_objects.type_id:'.$vn_image_type_id => 1,
			'ca_objects.type_id:"'.($vn_image_type_id-1).'"' => 0,

			// status
			'ca_objects.status:4' => 1,
			'ca_objects.status:"4"' => 1,
			'ca_objects.status:44' => 0,
			'ca_objects.status:"44"' => 0,

			// access
			'ca_objects.access:1' => 1,
			'ca_objects.access:"1"' => 1,
			'ca_objects.access:0' => 0,
			'ca_objects.access:"0"' => 0,

			// Search for something that was not explicitly set in the model, but has a default value
			'ca_objects.is_deaccessioned:"0"' => 1,
			'ca_objects.is_deaccessioned:0' => 1,
		));
	}
	# -------------------------------------------------------
}
