<?php
/** ---------------------------------------------------------------------
 * tests/models/SchemaTest.php
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


class SchemaTest extends BaseGraphQLServiceTest {
	# -------------------------------------------------------
	protected function setUp() : void {
		parent::canRun();
		$this->client('schema');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function testCreateEntity(){
		$query = <<<'QUERY'
			query {
				types(table: "ca_objects") {
					name,
					code,
					types {
						name,
						code
					}
				}
			}
		QUERY;

		$response = $this->query($query);
		$data = $response->getData();
		
		$this->assertArrayHasKey('types', $data, 'Expected types array');
		$this->assertArrayHasKey('name', $data['types'], 'Expected name');
		$this->assertArrayHasKey('code', $data['types'], 'Expected code');
		$this->assertNotEmpty($data['types']['name'], 'Expected name');
		$this->assertNotEmpty($data['types']['code'], 'Expected code');
		$this->assertArrayHasKey('types', $data['types'], 'Expected types list');
		$this->assertIsArray($data['types']['types'], 'Expected types list');
		$this->assertCount(9, $data['types']['types']);
		$this->assertArrayHasKey('name', $data['types']['types'][0], 'Expected type name');
		$this->assertArrayHasKey('code', $data['types']['types'][0], 'Expected type code');
		$this->assertNotEmpty($data['types']['types'][0]['name'], 'Expected type name');
		$this->assertNotEmpty($data['types']['types'][0]['code'], 'Expected type code');
	}
	# -------------------------------------------------------
}
