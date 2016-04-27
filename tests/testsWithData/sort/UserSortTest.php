<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/UserSortTest.php
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

require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');
require_once(__CA_MODELS_DIR__.'/ca_user_sorts.php');

/**
 * Class UserSortTest
 * Note: Requires testing profile!
 */
class UserSortTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;

	/**
	 * @var ca_user_sorts
	 */
	private $opt_user_sorts = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_test_record = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
				'access' => 0,
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test moving image",
				),
			),
			'attributes' => array(
				'duration' => array(
					array(
						'duration' => '00:23:28'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_test_record);

		$this->opt_object = new ca_objects($vn_test_record);
	}
	# -------------------------------------------------------
	public function testUserSort() {
		// add new sort
		$this->opt_user_sorts = new ca_user_sorts();
		$this->opt_user_sorts->setMode(ACCESS_WRITE);

		$this->opt_user_sorts->set('table_num', 57);
		$this->opt_user_sorts->set('user_id', 1);
		$this->opt_user_sorts->set('name', 'Test sort');
		$this->opt_user_sorts->insert();

		$this->assertGreaterThan(0, $this->opt_user_sorts->getPrimaryKey());

		$this->opt_user_sorts->addSortBundle('ca_object_labels.name_sort');
		$this->opt_user_sorts->addSortBundle('ca_objects.idno_sort');

		$this->assertEquals(2, sizeof($this->opt_user_sorts->getSortBundleNames()));
	}
	# -------------------------------------------------------
	public function tearDown() {
		if($this->opt_user_sorts instanceof ca_user_sorts) {
			if($this->opt_user_sorts->getPrimaryKey() > 0) {
				$this->opt_user_sorts->setMode(ACCESS_WRITE);
				//$this->opt_user_sorts->delete(true, array('hard' => true));
			}
		}
		parent::tearDown();
	}
}
