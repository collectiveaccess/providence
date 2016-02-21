<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/SimpleGetTest.php
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

/**
 * Class SimpleGetTest
 * Note: Requires testing profile!
 */
class SimpleGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;
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
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_objects.access', array('convertCodesToIdno' => true));
		$this->assertEquals('internal_only', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.status', array('convertCodesToIdno' => true));
		$this->assertEquals('new', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.type_id', array('convertCodesToDisplayText' => true));
		$this->assertEquals('Moving Image', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.preferred_labels');
		$this->assertEquals('My test moving image', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.duration');
		$this->assertEquals('0:23:28', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.access');
		$this->assertEquals('0', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.access', array('convertCodesToDisplayText' => true));
		$this->assertEquals('not accessible to public', $vm_ret);
		
		$o_tep = new TimeExpressionParser(); $vn_now = time();
		$vm_ret = $this->opt_object->get('ca_objects.lastModified');
		$this->assertTrue($o_tep->parse($vm_ret));
		$va_modified_unix = $o_tep->getUnixTimestamps();
		//$this->assertEquals($vn_now, $va_modified_unix['start'], 'lastModified timestamp cannot be more than 1 minute off', 60);
	}
	# -------------------------------------------------------
}
