<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/VideoProcessingTest.php
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
 * Class VideoProcessingTest
 * Note: Requires testing profile!
 */
class VideoProcessingTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var ca_object_representations
	 */
	private $opt_mp4_rep = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_mp4_rep = $this->addTestRecord('ca_object_representations', array(
			'intrinsic_fields' => array(
				'type_id' => 'front',
				'media' => 'http://mirrors.creativecommons.org/getcreative/Creative_Commons_-_Get_Creative.mov'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test rep",
				),
			)
		));

		$this->assertGreaterThan(0, $vn_mp4_rep);

		$this->opt_mp4_rep = new ca_object_representations($vn_mp4_rep);
	}
	# -------------------------------------------------------
	public function testMedia() {
		$va_media_info = $this->opt_mp4_rep->get('ca_object_representations.media');

		$this->assertEquals('video/mp4', $va_media_info['INPUT']['MIMETYPE']);
		$this->assertEquals(
			'http://mirrors.creativecommons.org/getcreative/Creative_Commons_-_Get_Creative.mov',
			$va_media_info['INPUT']['FETCHED_FROM']
		);
	}
	# -------------------------------------------------------
}
