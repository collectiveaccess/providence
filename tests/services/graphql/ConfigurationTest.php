<?php
/** ---------------------------------------------------------------------
 * tests/models/ConfigurationTest.php
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


class ConfigurationTest extends BaseGraphQLServiceTest {
	# -------------------------------------------------------
	protected function setUp() : void {
		parent::canRun();
		$this->client('configuration');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function testCreateEntity(){
		$query = <<<'QUERY'
			query {
				configurationFile (file:"app.conf", keys: ["disable_gzip_on_controllers", "ca_loans_disable", "quicksearch_default_results"]) {
					file, values { key, type, value }
				}
			}
		QUERY;

		$response = $this->query($query);
		$data = $response->getData();
		
		$this->assertArrayHasKey('configurationFile', $data, 'Expected data array');
		$this->assertArrayHasKey('file', $data['configurationFile'], 'Expected file');
		$this->assertEquals('app.conf', $data['configurationFile']['file'], 'Expected file app.conf');
		$this->assertArrayHasKey('values', $data['configurationFile'], 'Expected values list');
		$this->assertIsArray($data['configurationFile']['values'], 'Expected values array');
		$this->assertCount(3, $data['configurationFile']['values'], 'Expected 3 values');
		
	 	$this->assertArrayHasKey('key', $data['configurationFile']['values'][0], 'Expected key');
	 	$this->assertArrayHasKey('type', $data['configurationFile']['values'][0], 'Expected type');
	 	$this->assertArrayHasKey('value', $data['configurationFile']['values'][0], 'Expected value');
	 	
	 	$this->assertEquals('disable_gzip_on_controllers', $data['configurationFile']['values'][0]['key'], 'Expected disable_gzip_on_controllers key');
	 	$this->assertEquals('ASSOC', $data['configurationFile']['values'][0]['type'], 'Expected ASSOC type');
	 	$this->assertStringStartsWith('{', $data['configurationFile']['values'][0]['value'], 'Expected value to start with {');
 		$this->assertIsArray(json_decode($data['configurationFile']['values'][0]['value'], true), 'Expected valid JSON');

		$this->assertEquals('ca_loans_disable', $data['configurationFile']['values'][1]['key'], 'Expected ca_loans_disable key');
	 	$this->assertEquals('SCALAR', $data['configurationFile']['values'][1]['type'], 'Expected SCALAR type');
	 	$this->assertEquals('0', $data['configurationFile']['values'][1]['value'], 'Expected zero value');
	 	
		$this->assertEquals('quicksearch_default_results', $data['configurationFile']['values'][2]['key'], 'Expected quicksearch_default_results key');
	 	$this->assertEquals('LIST', $data['configurationFile']['values'][2]['type'], 'Expected LIST type');
	 	$this->assertIsArray(json_decode($data['configurationFile']['values'][2]['value'], true), 'Expected array value');
	 	$this->assertGreaterThan(10, sizeof(json_decode($data['configurationFile']['values'][2]['value'], true)), 'Expected list to have at least 10 values');
	}
	# -------------------------------------------------------
}
