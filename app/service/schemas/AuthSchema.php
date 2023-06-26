<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/AuthSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
namespace GraphQLServices\Schemas;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\AuthSchema;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 

class AuthSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	public static function load() {
		return [
			$userType = new ObjectType([
					'name' => 'UserInfo',
					'description' => 'Service user login',
					'fields' => [
						'id' => [
							'type' => Type::int(),
							'description' => 'Unique user identifier'
						],
						'username' => [
							'type' => Type::string(),
							'description' => 'User name'
						],
						'email' => [
							'type' => Type::string(),
							'description' => 'User mail'
						],
						'fname' => [
							'type' => Type::string(),
							'description' => 'User first name'
						],
						'lname' => [
							'type' => Type::string(),
							'description' => 'User last name'
						],
						'userclass' => [
							'type' => Type::string(),
							'description' => 'User class'
						]
					]
			]),
			new ObjectType([
				'name' => 'User',
				'description' => 'Service user login',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Unique user identifier'
					],
					'user' => [
						'type' => $userType,
						'description' => 'User information'
					],
					'jwt' => [
						'type' => Type::string(),
						'description' => 'JSON Web Token (JWT) for access'
					],
					'refresh' => [
						'type' => Type::string(),
						'description' => 'JSON Web Token (JWT) for refresh'
					]
				]
			]),
			new ObjectType([
				'name' => 'Refresh',
				'description' => 'Refresh JWT token',
				'fields' => [
					'jwt' => [
						'type' => Type::string(),
						'description' => 'JSON Web Token (JWT) for access'
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}