<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/AuthController.php :
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

require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/AuthSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQLServices\Schemas\AuthSchema;
use \Firebase\JWT\JWT;


class AuthController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function _default() {	
		$qt = new ObjectType([
			'name' => 'Auth',
			'fields' => [
				'login' => [
					'type' => AuthSchema::get('User'),
					'description' => _t('User login'),
					'args' => [
						[
							'name' => 'username',
							'type' => Type::string(),
							'description' => _t('User name'),
							'defaultValue' => null
						],
						[
							'name' => 'password',
							'type' => Type::string(),
							'description' => _t('User password'),
							'defaultValue' => null
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = new ca_users();
						if ($u->authenticate($args["username"], $args["password"], ['noPublicUsers' => true])) {
							$user_id = $u->get('ca_users.user_id');
						
							$data = [
								"id" => $user_id
							];
							return [
								'id' => $user_id,
								'jwt' => self::encodeJWT($data),
								'refresh' => self::encodeJWTRefresh($data),
								'user' => [
									'id' => $user_id,
									'username' => $u->get('ca_users.user_name'),
									'email' => $u->get('ca_users.email'),
									'fname' => $u->get('ca_users.fname'),
									'lname' => $u->get('ca_users.lname'),
									'userclass' => $u->get('ca_users.userclass')
								]
							];
						}
					
						return ['jwt' => null, 'user' => null];
					}
				],
				'refresh' => [
					'type' => AuthSchema::get('Refresh'),
					'description' => _t('Get new JWT access token using refresh token'),
					'args' => [
						[
							'name' => 'token',
							'type' => Type::string(),
							'description' => _t('Refresh token'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if($d = self::decodeJWT($args['token'])) {
							$id = $d->id;
							return [
								'jwt' => self::encodeJWT(['id' => $id])
							];
						}
						
						return ['jwt' => null];
					}
				],
				'validate' => [
					'type' => AuthSchema::get('UserInfo'),
					'description' => _t('User login validation'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT access token'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if ($d = self::authenticate($args['jwt'], ['returnAs' => 'array'])) {
							return $d;
						}
						
						return [];
					}
				],
			],
		]);
		
		return self::resolve($qt);
	}
	# -------------------------------------------------------
}