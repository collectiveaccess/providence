<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/SearchResultGetTest.php
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
 * Class SearchResultGetTest
 * Note: Requires testing profile!
 */
class SearchResultGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$i = 0;
		while($i < 10) {
			$vn_test_record = $this->addTestRecord('ca_objects', array(
				'intrinsic_fields' => array(
					'type_id' => 'moving_image',
				),
				'preferred_labels' => array(
					array(
						"locale" => "en_US",
						"name" => "My test moving image " . (string) $i,
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
			$i++;
		}
	}
	# -------------------------------------------------------
	public function testGets() {

		$o_search = caGetSearchInstance('ca_objects');
		$this->assertInstanceOf('SearchEngine', $o_search);

		$o_res = $o_search->search('*', array('sort' => 'ca_object_labels.name'));
		/** @var SearchResult $o_res */
		$this->assertInstanceOf('SearchResult', $o_res);
		$this->assertEquals(10, $o_res->numHits());

		$i=0;
		while($o_res->nextHit()) {
			$vs_label = $o_res->getWithTemplate('^ca_objects.preferred_labels');
			$this->assertGreaterThan(0, strlen($vs_label));
			$this->assertRegExp("/$i$/", $vs_label);

			$i++;
		}
	}
	# -------------------------------------------------------
}
