<?php
/** ---------------------------------------------------------------------
 * tests/models/EditTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
use PHPUnit\Framework\TestCase;
require_once(__CA_BASE_DIR__."/tests/services/graphql/BaseGraphQLServiceTest.php");


class EditTest extends BaseGraphQLServiceTest {
	# -------------------------------------------------------
	protected function setUp() : void {
		parent::canRun();
		$this->client('edit');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function testCreateEntity(){
		$query = <<<'QUERY'
		     mutation {
				add(
					table: "ca_entities",
					idno: "100000",
					type: "ind",
					bundles: [
						{ name: "preferred_labels", values: [
							{ name: "forename", value: "Forename" },
							{ name: "surname", value: "Surname" },
							{ name: "middlename", value: "Middle Name" },
							{ name: "prefix", value: "Prefix" },
							{ name: "suffix", value: "Suffix" },
							{ name: "other_forenames", value: "Other Forenames" },
							{ name: "displayname", value: "A display name" }
						]},
						{ name: "address", values: [
							{ name: "address1", value: "Address Line 1" },
							{ name: "address2", value: "Address Line 2" },
							{ name: "city", value: "City" },
							{ name: "state", value: "State/Province" },
							{ name: "postal_code", value: "Postal/Zip Code" },
							{ name: "country", value: "Country" },
							{ name: "type", value: "current"},
						]},
						{ name: "email", value: "Email 1" },
						{ name: "email", value: "Email 2" },
						{ name: "telephone", value: "Phone 1" },
						{ name: "telephone", value: "Phone 2" },
						{ name: "telephone", value: "Phone 3" },
						{ name: "biography", value: "Biography" }
					]
				) {
					id,
					table,
					idno,
					changed,
					errors { code, message, bundle },
					warnings { message, bundle }
				}
				}
		QUERY;

		$response = $this->query($query);
		$data = $response->getData();
		
		$this->assertArrayHasKey('add', $data, 'Expected data array');
		$this->assertArrayHasKey('id', $data['add'], 'Expected id');
		$this->assertIsArray($data['add']['id'], 'Expected id array');
		$this->assertIsInt($data['add']['id'][0], 'Expected id');
		
		$this->assertArrayHasKey('idno', $data['add'], 'Expected idno');
		$this->assertIsArray($data['add']['idno'], 'Expected idno array');
		$this->assertNotEmpty($data['add']['idno'][0], 'Expected idno');
		
		$this->assertArrayHasKey('table', $data['add'], 'Expected table');
		$this->assertEquals('ca_entities', $data['add']['table'], 'Expected table name to be ca_entities');
		
		$this->assertArrayHasKey('changed', $data['add'], 'Expected changed count');
		$this->assertEquals(1, $data['add']['changed'], 'Expected change count to be 1');
		$this->assertIsArray($data['add']['errors'], 'Expected error array');
		$this->assertIsArray($data['add']['warnings'], 'Expected warning array');
		$this->assertCount(0, $data['add']['errors'], 'Expected empty error array');
		$this->assertCount(0, $data['add']['warnings'], 'Expected empty warning array');
		
		$entity = ca_entities::findAsInstance(['entity_id' => $data['add']['id'][0]]);
		$this->assertIsObject($entity, 'Expected valid entity instance');
		$this->assertEquals($data['add']['id'][0], $entity->getPrimaryKey(), 'Expected valid entity with returned id');
	
		$this->assertEquals('A display name', $entity->get('ca_entities.preferred_labels.displayname'));
		$this->assertEquals('100000', $entity->get('ca_entities.idno'));
		$this->assertEquals('Biography', $entity->get('ca_entities.biography'));
		
		$numbers = $entity->get('ca_entities.telephone', ['returnAsArray' => true]);
		$this->assertCount(3, $numbers);
		$this->assertEquals('Phone 1', $numbers[0]);
		$this->assertEquals('Phone 2', $numbers[1]);
		$this->assertEquals('Phone 3', $numbers[2]);
		
		$this->cleanupRecords('ca_entities', $data['add']['id']);
	}
	# -------------------------------------------------------
}
