<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/LabelTemplateTest.php
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
 * Class LabelTemplateTest
 * Note: Requires testing profile!
 */
class LabelTemplateTest extends BaseTestWithData {
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
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				)
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Alternate title for test image",
					"type_id" => 'alt'
				),
				array(
					"locale" => "en_US",
					"name" => "Use for title for test image",
					"type_id" => 'uf'
				)
			),
		));

		$this->assertGreaterThan(0, $vn_test_record);

		$this->opt_object = new ca_objects($vn_test_record);
	}
	# -------------------------------------------------------
	public function testGets() {
		$this->assertEquals(
			'Alternate title for test image (alternate)<br />Use for title for test image (use for)',
			$this->opt_object->getWithTemplate(
				'<unit relativeTo="ca_objects.nonpreferred_labels" delimiter="<br />">^ca_objects.nonpreferred_labels.name (^ca_objects.nonpreferred_labels.type_id)</unit>'
			)
		);
	}
	# -------------------------------------------------------
}
