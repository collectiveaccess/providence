<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/GraphQLServiceController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
namespace GraphQLServices;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type; 
use GraphQL\Error\DebugFlag;
use GraphQL\Error\FormattedError;
use \Firebase\JWT\JWT;

require_once(__CA_LIB_DIR__.'/Service/BaseServiceController.php');

class GraphQLServiceController extends \BaseServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected static $schemas;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function resolve($queryType, $mutationType=null){
		if (strtolower($_SERVER['REQUEST_METHOD']) === 'options') {
			http_response_code(200);
			return;	
		}
		$schema = new Schema([
			'query' => $queryType, 'mutation' => $mutationType
		]);

		$rawInput = file_get_contents('php://input');
		$input = json_decode($rawInput, true);
		$query = $input['query'];
		$variableValues = isset($input['variables']) ? $input['variables'] : null;

		try {
			$rootValue = ['prefix' => ''];
			
			// TODO: make debug mode configurable
			$debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
			
			$errorFormatter = function(\GraphQL\Error\Error $error) {
				$formattedError =  FormattedError::createFromException($error);
				if($error->getMessage() === 'Expired token') {
					$formattedError['expiredToken'] = true;
				}
				return $formattedError;
			};
			
			$result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues)->setErrorFormatter($errorFormatter);
			$output = $result->toArray($debug);
		} catch (\Exception $e) {
			$output = [
				'errors' => [
					[
						'message' => $e->getMessage()
					]
				]
			];
			http_response_code(500);
		} catch (\TypeError $e) {
			$output = [
				'errors' => [
					[
						'message' => _t('An invalid parameter type error occurred. Are your arguments correct?')
					]
				]
			];
			http_response_code(500);
		}
		if($result->errors) {
			if(isset($output['errors'][0]['expiredToken']) && $output['errors'][0]['expiredToken']) {
				http_response_code(401);
			} else {
				http_response_code(500);
			}
		}
		
		if(intval($this->request->getParameter("pretty",pInteger))>0){
			$this->view->setVar("pretty_print",true);
		}
					
		$this->view->setVar('content', $output);
		$this->view->setVar('raw', true);	// don't set 'ok' parameter
		$this->render("json.php");
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function encodeJWT(array $data, array $options=null) {
		if(!is_array($options)) { $options = []; }
		
		$config = \Configuration::load();
		$key = $config->get('graphql_services_jwt_token_key');
		$exp_offset = caGetOption('refresh', $options, false) ? 
			(int)$config->get('graphql_services_jwt_access_token_lifetime') 
			: 
			(int)$config->get('graphql_services_jwt_refresh_token_lifetime');
			
		if ($exp_offset <= 0) { $exp_offset = 900; }
		
		return \Session::encodeJWT($data, $key, array_merge($options, ['lifetime' => $exp_offset]));
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function encodeJWTRefresh(array $data) {
		return self::encodeJWT($data, ['refresh' => true]);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function decodeJWT(string $jwt) {
		$key = \Configuration::load()->get('graphql_services_jwt_token_key');
		
		return \Session::decodeJWT($jwt, $key);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function authenticate(string $jwt, array $options=null) {
		if ($d = self::decodeJWT($jwt)) {
			if ($u = \ca_users::find(['user_id' => (int)$d->id, 'active' => 1, 'userclass' => ['<>', 255]], ['returnAs' => 'firstModelInstance'])) {
				if (caGetOption('returnAs', $options, null) === 'array') {
					return [
						'id' => $u->getPrimaryKey(),
						'username' => $u->get('ca_users.user_name'),
						'email' => $u->get('ca_users.email'),
						'fname' => $u->get('ca_users.fname'),
						'lname' => $u->get('ca_users.lname'),
						'userclass' => $u->get('ca_users.userclass')
					];
				}
				return $u;
			}
		}
		return false;
	}
	# -------------------------------------------------------
	/** 
	 * Get header Authorization
	 * 
	 */
	protected static function getAuthorizationHeaders(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		} elseif(isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif(function_exists('apache_request_headers')) {
			$rheaders = apache_request_headers();
			$rheaders = array_combine(array_map('ucwords', array_keys($rheaders)), array_values($rheaders));
			
			if (isset($rheaders['Authorization'])) {
				$headers = trim($rheaders['Authorization']);
			}
		}
		return $headers;
	}
	# -------------------------------------------------------
	/**
	 * Get access token from header
	 * 
	 */
	protected static function getBearerToken() {
		$headers = self::getAuthorizationHeaders();
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $m)) {
				return $m[1];
			}
		}
		return null;
	}
	# -------------------------------------------------------
}
