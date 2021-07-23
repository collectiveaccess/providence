<?php
/** ---------------------------------------------------------------------
 * tests/services/graphql/BaseGraphQLServiceTest.php
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


abstract class BaseGraphQLServiceTest extends TestCase {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected $ids_to_cleanup = [];
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function canRun() : bool {
		if(!defined('__CA_RUN_GRAPHQL_SERVICE_TESTS__') || !__CA_RUN_GRAPHQL_SERVICE_TESTS__) {
			$this->markTestSkipped(
				'Skipped GraphQL services tests because they are not configured to run.'
			);
			return false;
		}
		return true;
    }
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function client(string $endpoint, ?bool $do_auth=true) {
		if(!defined('__CA_SITE_PROTOCOL__') || !defined('__CA_SITE_HOSTNAME__') || !defined('__CA_URL_ROOT__')) {
			$this->markTestSkipped(
				'Skipped GraphQL services tests because required constants __CA_SITE_PROTOCOL__, __CA_SITE_HOSTNAME__ and/or __CA_URL_ROOT__ are not defined.'
			);
		}
		if(!$this->jwt && $do_auth) { $this->auth(); }
		
		return $this->client = \Softonic\GraphQL\ClientBuilder::build(__CA_SITE_PROTOCOL__.'://'.__CA_SITE_HOSTNAME__.__CA_URL_ROOT__.'/service/'.$endpoint, [
			'headers' => $do_auth ? ['Authorization' => 'Bearer ' . $this->jwt] : null
		]);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function auth() : bool {
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
		
		$client = $this->client('auth', false);
		$response = $client->query($query, [
			'username' => __CA_USERNAME__,	// Login information comes from setup-test.php
			'password' => __CA_PASSWORD__
		]);
		$errors = $response->getErrors();
		if(is_array($errors) && sizeof($errors)) {
			$this->markTestSkipped(
				'Skipped GraphQL services tests because required auth failed: '.join("; ".$errors)
			);
			return false;
		}
		$data = $response->getData();
		
		$this->jwt = $data['login']['jwt'];
		$this->jwt_refresh = $data['login']['refresh'];
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function cleanupRecords(string $table, $ids) : void {
		if(!is_array($ids)) {
			$ids = [$ids];
		}
		foreach($ids as $id) {
			$this->ids_to_cleanup[$table][$id] = true;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function tearDown() : void {
		foreach($this->ids_to_cleanup as $table => $ids) {
			foreach(array_keys($ids) as $id) {
				if($t = $table::find($id, ['returnAs' => 'firstModelInstance'])) {
					$t->delete(true);
				}
			}
		}
    }
	# -------------------------------------------------------
}
