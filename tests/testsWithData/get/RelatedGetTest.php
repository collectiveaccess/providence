<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/RelatedGetTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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
 * Class RelatedGetTest
 * Note: Requires testing profile!
 */
class RelatedGetTest extends BaseTestWithData {
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
		$vn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_object_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
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
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Max",
					"middlename" => "",
					"surname" => "Power",
					"type_id" => "alt",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'bs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"middlename" => "",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'publisher',
						'effective_date' => '2014-2015',
						'source_info' => 'Homer'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'org',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "",
					"middlename" => "",
					"surname" => "ACME Inc.",
				),
			),
			'attributes' => array(
				'internal_notes' => array(
					array(
						'internal_notes' => 'Test notes'
					)
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'source',
						'effective_date' => '2013',
						'source_info' => 'Bart'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_related_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'dataset',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test dataset",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'related',
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_related_object_id);

		$this->opt_object = new ca_objects($vn_object_id);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities.preferred_labels', array('delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities.nonpreferred_labels');
		$this->assertEquals('Max Power', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('returnWithStructure' => true));
		$vm_ret2 = $this->opt_object->getRelatedItems('ca_entities');
		$this->assertSame($vm_ret, $vm_ret2);

		$vm_ret = $this->opt_object->get('ca_entities', array('restrictToRelationshipTypes' => array('creator')));
		$this->assertEquals('Homer J. Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('restrictToRelationshipTypes' => array('publisher')));
		$this->assertEquals('Bart Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('ind')));
		$this->assertEquals('Homer J. Simpson; Bart Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('org')));
		$this->assertEquals('ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('ind', 'org')));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'excludeRelationshipTypes' => array('creator', 'publisher')));
		$this->assertEquals('ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'excludeTypes' => array('ind')));
		$this->assertEquals('ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects_x_entities.source_info', array('delimiter' => '; '));
		$this->assertEquals('Me; Homer; Bart', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects_x_entities.effective_date', array('delimiter' => '; '));
		$this->assertEquals('2015; 2014 – 2015; 2013', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities.internal_notes');
		$this->assertEquals('Test notes', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_objects.related.preferred_labels');
		$this->assertEquals('My test dataset', $vm_ret);

		// <unit> with relativeTo to repeat template once per entity (this is different from the old pre-1.6 template behavior)
		$vm_ret = $this->opt_object->get('ca_entities', array('template' => '<unit relativeTo="ca_entities">^ca_entities.preferred_labels.displayname (^ca_entities.internal_notes)</unit>', 'delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson (); Bart Simpson (); ACME Inc. (Test notes)', $vm_ret);

		// Pre-1.6 test without units returns straight text get() output for each tag (this used to return the output in the previous test)
		$vm_ret = $this->opt_object->get('ca_entities', array('template' => '^ca_entities.preferred_labels.displayname (^ca_entities.internal_notes)', 'delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc. (Test notes)', $vm_ret);


		$vm_ret = $this->opt_object->get('ca_entities', array('template' => '^ca_entities.preferred_labels', 'delimiter' => '; ', 'returnAsLink' => true));
		$this->assertRegExp("/\<a href=[\"\'](.)+[\"\']>Homer J. Simpson\<\/a\>/", $vm_ret);
		$this->assertRegExp("/\<a href=[\"\'](.)+[\"\']>Bart Simpson\<\/a\>/", $vm_ret);
		$this->assertRegExp("/\<a href=[\"\'](.)+[\"\']>ACME Inc.\<\/a\>/", $vm_ret);


		$va_entity_relationships = $this->opt_object->get('ca_objects_x_entities.relation_id', array('returnAsArray' => true));
		$qr_entity_relationships = caMakeSearchResult('ca_objects_x_entities', $va_entity_relationships);

		$this->assertEquals(3, $qr_entity_relationships->numHits());

		while($qr_entity_relationships->nextHit()) {
			$this->assertEquals('0', $qr_entity_relationships->get('ca_objects.deleted'));
			$this->assertEquals('0', $qr_entity_relationships->get('ca_entities.deleted'));
		}

		// there are no related list items
		$vm_ret = $this->opt_object->get('ca_list_items', array('returnAsArray' => true));
		$this->assertEmpty($vm_ret);
	}
	# -------------------------------------------------------
	public function testGetCounts() {
		$vm_ret = $this->opt_object->get('ca_entities._count');
		$this->assertEquals(3, $vm_ret);
		
		$vm_ret = $this->opt_object->get('ca_entities._count', ['returnAsArray' => true]);
		$this->assertInternalType('array', $vm_ret);
		$this->assertCount(1, $vm_ret);
		$this->assertEquals(3, $vm_ret[0]);
		
		$vm_ret = $this->opt_object->get('ca_entities.idno', ['returnAsCount' => true]);
		$this->assertEquals(3, $vm_ret);
	}
	# -------------------------------------------------------
}
