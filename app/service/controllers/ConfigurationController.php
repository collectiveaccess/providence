<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/ConfigurationController.php :
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/ConfigurationSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\ConfigurationSchema;


class ConfigurationController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	#
	static $config = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				// ------------------------------------------------------------
				// Tables
				// ------------------------------------------------------------
				'configurationFile' => [
					'type' => ConfigurationSchema::get('ConfigurationValueList'),
					'description' => _t('Get value from configuration file'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'file',
							'type' => Type::string(),
							'description' => _t('Configuration file'),
							'defaultValue' => 'app.conf'
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Configuration file key to return value for')
						],
						[
							'name' => 'keys',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Configuration file keys to return value for')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						if(!$u->canDoAction('can_access_configuration_files_via_graphql')) {
							throw new \ServiceException(_t('Access denied'));
						}
						
						if(!($config = Configuration::load(__CA_CONF_DIR__.'/'.$args['file']))) {
							throw new \ServiceException(_t('Invalid configuration file: %1', $args['file']));
						}
						
						$keys = is_array($args['keys']) && sizeof($args['keys']) ? $args['keys'] : [$args['key']];
						
						$values = [];
						foreach($keys as $key) {
							$key = trim($key);
							if(!strlen($key)) { continue; }
							$value = $config->getAssoc($key);
							$type = 'ASSOC';
							if(is_null($value)) {
								$value = $config->getList($key);
								$type = 'LIST';
								if(is_null($value)) {
									$value = $config->getScalar($key);
									$type = 'SCALAR';
								}
							}
							$values[] = [
								'key' => $key,
								'type'=> $type,
								'value' => json_encode($value)
							];
						}
						return ['file' => $args['file'], 'values' => $values];
					}
				],
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
			
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
}
