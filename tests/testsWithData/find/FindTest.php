<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/queries/FindTest.php
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
 * Class FindTest
 * Note: Requires testing profile!
 */
class FindTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * primary key ID of the first created object
	 * @var int
	 */
	private $opn_object_id = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that request is set up correctly
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$this->assertGreaterThan(0, $this->opn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
				'idno' => 'TEST & STUFF'
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "Sound & Motion",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
			'attributes' => array(
				// simple text
				'internal_notes' => array(
					array(
						'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
					)
				),

				// text in a container
				'external_link' => array(
					array(
						'url_source' => 'My URL source'
					)
				),

				// Length
				'dimensions' => array(
					array(
						'dimensions_length' => '10 in',
						'dimensions_weight' => '2 lbs'
					)
				),

				// Integer
				'integer_test' => array(
					array(
						'integer_test' => 23,
					),
					array(
						'integer_test' => 1984,
					)
				),

				// Currency
				'currency_test' => array(
					array(
						'currency_test' => '$100',
					),
				),

				// Georeference
				'georeference' => array(
					array(
						'georeference' => '1600 Amphitheatre Parkway, Mountain View, CA',
					),
				),

				// coverageNotes
				'coverageNotes' => array(
					array(
						'coverageNotes' => ''
					),
				),
			)
		)));
	}
	# -------------------------------------------------------
	public function testFindByIdnoNoPurify() {
		$vm_ret = ca_objects::find(['idno' => 'TEST &amp; STUFF'], ['returnAs' => 'ids', 'purify' => false]);
		
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByIdnoWithPurify() {
		$vm_ret = ca_objects::find(['idno' => 'TEST & STUFF'], ['returnAs' => 'ids', 'purify' => true]);
		
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelNoPurify() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound &amp; Motion']], ['purify' => false, 'returnAs' => 'ids']);
	
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
	public function testFindByPreferredLabelWithPurify() {	
		$vm_ret = ca_objects::find(['preferred_labels' => ['name' => 'Sound & Motion']], ['purifyWithFallback' => false, 'purify' => true, 'returnAs' => 'ids']);
	
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals($this->opn_object_id, $vm_ret[0]);
	}
	# -------------------------------------------------------
}
