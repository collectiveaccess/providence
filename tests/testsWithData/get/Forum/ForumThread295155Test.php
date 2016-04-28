<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ForumThread295155Test.php
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

/**
 * Class ForumThread295155Test
 * Note: Requires testing profile!
 * @see http://www.collectiveaccess.org/support/forum/index.php?p=/discussion/294947/i-need-some-help-getting-data-in-a-report#latest
 */
class ForumThread295155Test extends BaseTestWithData {
	# -------------------------------------------------------
	protected $opn_object_id = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_test_object = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "A test image",
				),
			),
			'attributes' => array(
				// Date
				'date' => array(
					array(
						'dc_dates_types' => 'created',
						'dates_value' => 'today'
					)
				),
			)
		));
		$this->assertGreaterThan(0, $vn_test_object);


		$this->opn_object_id = $vn_test_object;
	}
	# -------------------------------------------------------
	public function testGets() {
		// @see http://www.collectiveaccess.org/support/forum/index.php?p=/discussion/295155/if-rule-now-crashes-print-templates#latest

		$vo_result = caMakeSearchResult('ca_objects', array($this->opn_object_id));
		while($vo_result->nextHit()) {
			$this->assertEquals('Foo', $vo_result->getWithTemplate("<if rule='^ca_objects.date.dc_dates_types=~ /Date created/'>Foo</if>"));
			$this->assertEquals('', $vo_result->getWithTemplate("<if rule='^ca_objects.date.dc_dates_types=~ /Date copyrighted/'>Foo</if>"));
		}
	}
	# -------------------------------------------------------
}
