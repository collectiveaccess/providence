<?php
/** ---------------------------------------------------------------------
 * tests/lib/Service/SimpleServiceTest.php
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

require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');
require_once(__CA_LIB_DIR__.'/Service/SimpleService.php');

/**
 * Class SimpleServiceTest
 * Note: Requires testing profile!
 */
class SimpleServiceTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_image = null;
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_moving_image = null;

	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_img = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_img);

		$this->opt_image = new ca_objects($vn_img);

		$vn_moving_img = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test moving image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_moving_img);

		$this->opt_moving_image = new ca_objects($vn_moving_img);
	}
	# -------------------------------------------------------
	public function testDetailDispatch() {
		global $g_request;
		$g_request->setParameter('id', $this->opt_image->getPrimaryKey(), 'GET');

		$va_ret = SimpleService::dispatch('testDetail', $g_request);

		$this->assertEquals($this->opt_image->getPrimaryKey(), $va_ret['object_id']);
		$this->assertEquals('My test image', $va_ret['display_label']);
	}
	# -------------------------------------------------------
	/**
	 * @expectedException Exception
	 */
	public function testDetailDispatchWithInvalidType() {
		global $g_request;
		$g_request->setParameter('id', $this->opt_moving_image->getPrimaryKey(), 'GET');

		SimpleService::dispatch('testDetail', $g_request);
	}
	# -------------------------------------------------------
	public function testSearchDispatch() {
		global $g_request;
		$g_request->setParameter('q', 'test', 'GET');

		$va_ret = SimpleService::dispatch('testSearch', $g_request);

		$this->assertEquals(2, sizeof($va_ret));

		foreach($va_ret as $va_result) {
			$this->assertArrayHasKey('display_label', $va_result);
			$this->assertArrayHasKey('object_id', $va_result);
		}
	}
	# -------------------------------------------------------
	public function testSearchDispatchWithRestriction() {
		global $g_request;
		$g_request->setParameter('q', 'test', 'GET');

		$va_ret = SimpleService::dispatch('testSearchWithRestriction', $g_request);

		$this->assertEquals(1, sizeof($va_ret));

		foreach($va_ret as $va_result) {
			$this->assertArrayHasKey('display_label', $va_result);
			$this->assertArrayHasKey('object_id', $va_result);
		}
	}
	# -------------------------------------------------------
}

