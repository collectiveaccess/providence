<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/TimestampSearchQueryTest.php
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

require_once(__CA_BASE_DIR__ . '/tests/testsWithData/AbstractSearchQueryTest.php');

/**
 * Class TimestampSearchQueryTest
 * Note: Requires testing profile!
 */
class TimestampSearchQueryTest extends AbstractSearchQueryTest {
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		// log in as admin so we can test user-specific change log search
		/** @var RequestHTTP $g_request */
		global $g_request;
		$g_request->doAuthentication(array(
			'user_name' => 'administrator', 'password' => 'dublincore',
			'dont_redirect_to_login' => true, 'no_headers' => true
		));

		// search subject table
		$this->setPrimaryTable('ca_objects');

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$this->assertGreaterThan(0, $vn_record_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'access' => 1,
				'status' => 4,
			),
		)));

		// search queries
		$this->setSearchQueries(array(
			'created:' . date('Y') => 1,
			'created.administrator:' . date('Y') => 1,
			'created.cataloguer:' . date('Y') => 0,
			'created:"1985-1986"' => 0,
			'created:"2000-2020"' => 1,
		));
	}
	# -------------------------------------------------------
}
