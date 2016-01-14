<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/LoansXLoansTest.php
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
 * Class LoansXLoansTest
 * Note: Requires testing profile!
 */
class LoansXLoansTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_loan_in = null;
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_loan_out = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_loan_in = $this->addTestRecord('ca_loans', array(
			'intrinsic_fields' => array(
				'type_id' => 'in',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "New Loan In",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_loan_in);

		$vn_loan_out = $this->addTestRecord('ca_loans', array(
			'intrinsic_fields' => array(
				'type_id' => 'out',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "New Loan Out",
				),
			),
			'related' => array(
				'ca_loans' => array(
					array(
						'object_id' => $vn_loan_in,
						'type_id' => 'related',
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_loan_out);

		$this->opt_loan_in = new ca_loans($vn_loan_in);
		$this->opt_loan_out = new ca_loans($vn_loan_out);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_loan_in->get('ca_loans.related');
		$this->assertEquals('New Loan Out', $vm_ret);

		$va_items = $this->opt_loan_in->getRelatedItems('ca_loans');
		$vn_relation_id = array_shift(caExtractArrayValuesFromArrayOfArrays($va_items, 'relation_id'));

		// The relationship we created is Loan Out <-> Loan In, so evaluating with loan in as primary ID should give us the loan out
		$va_opts = array(
			'resolveLinksUsing' => 'ca_loans',
			'primaryIDs' =>
				array (
					'ca_loans' => array ($this->opt_loan_in->getPrimaryKey()),
  				),
		);

		$vm_ret = caProcessTemplateForIDs("^ca_loans.preferred_labels", 'ca_loans_x_loans', array($vn_relation_id), $va_opts);
		$this->assertEquals('New Loan Out', $vm_ret);

		// Now for the other side ...
		$va_opts = array(
			'resolveLinksUsing' => 'ca_loans',
			'primaryIDs' =>
				array (
					'ca_loans' => array ($this->opt_loan_out->getPrimaryKey()),
				),
		);

		$vm_ret = caProcessTemplateForIDs("^ca_loans.preferred_labels", 'ca_loans_x_loans', array($vn_relation_id), $va_opts);
		$this->assertEquals('New Loan In', $vm_ret);
	}
	# -------------------------------------------------------
}
