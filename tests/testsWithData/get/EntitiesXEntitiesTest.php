<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/EntitiesXEntitiesTest.php
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
 * Class EntitiesXEntitiesTest
 * Note: Requires testing profile!
 */
class EntitiesXEntitiesTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_homer = null;
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_bart = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_homer_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
		));

		$this->assertGreaterThan(0, $vn_homer_id);

		$vn_bart_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'bs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_entities' => array(
					array(
						'entity_id' => $vn_homer_id,
						'type_id' => 'related',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_bart_id);


		$this->opt_homer = new ca_entities($vn_homer_id);
		$this->opt_bart = new ca_entities($vn_bart_id);
	}
	# -------------------------------------------------------
	public function testInterstitialTemplateProcessing() {

		// should only be one
		$vn_relation_id = $this->opt_homer->get('ca_entities_x_entities.relation_id');
		$this->assertTrue(is_numeric($vn_relation_id));

		$va_opts = array(
			'resolveLinksUsing' => 'ca_entities',
			'primaryIDs' =>
				array (
					'ca_entities' => array($this->opt_bart->getPrimaryKey()),
				),
		);

		// we're reading from Bart, so Homer should pop up

		$this->assertEquals('Homer J. Simpson', caProcessTemplateForIDs(
			'^ca_entities.preferred_labels',
			'ca_entities_x_entities', array($vn_relation_id), $va_opts
		));

		// Try getting the rel type from the relationship record
		// We don't need $va_opts to do that, by the way!

		$this->assertEquals('is related to', caProcessTemplateForIDs(
			'^relationship_typename',
			'ca_entities_x_entities', array($vn_relation_id)
		));

		// Try getting the rel type from the Homer record
		// We don't need $va_opts to do that either

		$this->assertEquals('is related to', caProcessTemplateForIDs(
			'<unit relativeTo="ca_entities_x_entities">^relationship_typename</unit>',
			'ca_entities', array($this->opt_homer->getPrimaryKey())
		));
	}
	# -------------------------------------------------------
}
