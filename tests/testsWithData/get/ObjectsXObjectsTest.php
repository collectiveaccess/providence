<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/ObjectsXObjectsTest.php
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
 * Class ObjectsXObjectsTest
 * Note: Requires testing profile!
 */
class ObjectsXObjectsTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object_left = null;
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object_right = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_object_left = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'test_img'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Image",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_object_left);

		$vn_object_right = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'dataset',
				'idno' => 'test_dataset'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Test Dataset",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_left,
						'type_id' => 'related',
						'effective_date' => 'January 28 1985',
						'direction' => 'rtol'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_object_right);

		$this->opt_object_left = new ca_objects($vn_object_left);
		$this->opt_object_right = new ca_objects($vn_object_right);
	}
	# -------------------------------------------------------
	public function testInterstitialTemplateProcessing() {

		// should only be one
		$vn_relation_id = $this->opt_object_left->get('ca_objects_x_objects.relation_id');
		$this->assertTrue(is_numeric($vn_relation_id));

		$va_opts = array(
			'resolveLinksUsing' => 'ca_objects',
			'primaryIDs' =>
				array (
					'ca_objects' => array ($this->opt_object_left->getPrimaryKey()),
				),
		);

		// we're reading from the left side, so the right side pop up

		$this->assertEquals('Test Dataset', caProcessTemplateForIDs(
			'^ca_objects.preferred_labels',
			'ca_objects_x_objects', array($vn_relation_id), $va_opts
		));

		$this->assertEquals('is related to', caProcessTemplateForIDs(
			'^relationship_typename',
			'ca_objects_x_objects', array($vn_relation_id), $va_opts
		));

		$this->assertEquals('is related to', caProcessTemplateForIDs(
			'<unit relativeTo="ca_objects_x_objects">^relationship_typename</unit>',
			'ca_objects', array($this->opt_object_left->getPrimaryKey()), $va_opts
		));

	}
	# -------------------------------------------------------
}
