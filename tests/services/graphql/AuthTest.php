<?php
/** ---------------------------------------------------------------------
 * tests/models/AuthTest.php
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


class AuthTest extends BaseGraphQLServiceTest {
	# -------------------------------------------------------
	protected function setUp() : void {
		parent::canRun();
	}
	# -------------------------------------------------------
	/**
	 * Check JWT generation
	 */
	public function testGetJWT(){
		$query = <<<'QUERY'
		      query jwtAuth($username: String, $password: String){
				login(username: $username, password: $password) {
				  jwt,
				  refresh,
				  user {
					id,
					fname,
					lname,
					email
				  }
				}
				}
		QUERY;

		$client = $this->client('auth');
		
		$response = $client->query($query, [
			'username' => __CA_USERNAME__,	// Login information comes from setup-test.php
			'password' => __CA_PASSWORD__
		]);
		$this->assertIsObject($response, 'Expected response object');
		
		$errors = $response->getErrors();
		$this->assertIsArray($errors, 'Expected error array');
		$this->assertCount(0, $errors, 'Expected error array to be empty');
		
		$data = $response->getData();
		$this->assertIsArray($data, 'Expected data array');
		$this->assertArrayHasKey('login', $data, 'Expected data array');
		$this->assertArrayHasKey('jwt', $data['login'], 'Expected jwt key');
		$this->assertArrayHasKey('refresh', $data['login'], 'Expected jwt refresh key');
		$this->assertGreaterThan(30, strlen($data['login']['jwt']), 'Expected jwt key to be at least 30 characters in length');
		$this->assertGreaterThan(30, strlen($data['login']['refresh']), 'Expected jwt refresh key to be at least 30 characters in length');
		$this->assertArrayHasKey('user', $data['login'], 'Expected user information array');
		$this->assertEquals('CollectiveAccess', $data['login']['user']['fname']);
		$this->assertEquals('Administrator', $data['login']['user']['lname']);
		$this->assertEquals('info@collectiveaccess.org', $data['login']['user']['email']);
	}
	# -------------------------------------------------------
	/**
	 * Check JWT refresh
	 */
	public function testJWTRefresh(){
		$this->auth();
		$query = <<<'QUERY'
		      query($token: String) {
					refresh(token: $token)
					{
						jwt
					}
				}
		QUERY;

		$client = $this->client('auth');
		
		$response = $client->query($query, [
			'token' => $this->jwt
		]);
		$this->assertIsObject($response, 'Expected response object');
		
		$errors = $response->getErrors();
		$this->assertIsArray($errors, 'Expected error array');
		$this->assertCount(0, $errors, 'Expected error array to be empty');
		
		$data = $response->getData();
		$this->assertIsArray($data, 'Expected data array');
		$this->assertArrayHasKey('refresh', $data, 'Expected data array');
		$this->assertArrayHasKey('jwt', $data['refresh'], 'Expected jwt key');
		$this->assertGreaterThan(30, strlen($data['refresh']['jwt']), 'Expected jwt key to be at least 30 characters in length');
	}
	# -------------------------------------------------------
}
