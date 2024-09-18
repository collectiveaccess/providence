<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/StatusSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');

class StatusSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$EchoData = new ObjectType([
				'name' => 'EchoData',
				'description' => 'Data to echo to sender',
				'fields' => [
					[
						'name' => 'name',
						'type' => Type::string(),
						'description' => _t('Name of value')
					],
					[
						'name' => 'value',
						'type' => Type::string(),
						'description' => _t('Value')
					]
				]
			]),
			$InfoType = new ObjectType([
				'name' => 'Info',
				'description' => 'System information',
				'fields' => [
					'status' => [
						'type' => Type::string(),
						'description' => 'System status. "OK" for normal state.'
					],
					'datetime' => [
						'type' => Type::string(),
						'description' => 'Current date/time on server'
					],
					'timezone' => [
						'type' => Type::string(),
						'description' => 'Server timezone'
					],
					'apiversion' => [
						'type' => Type::string(),
						'description' => 'API version'
					],
					'echo' => [
						'type' => Type::listOf($EchoData),
						'description' => 'List echoed data'
					]
				]
			]),
			$EchoInputData = new InputObjectType([
				'name' => 'EchoInputData',
				'description' => 'Data to echo to sender',
				'fields' => [
					[
						'name' => 'name',
						'type' => Type::string(),
						'description' => _t('Name of value')
					],
					[
						'name' => 'value',
						'type' => Type::string(),
						'description' => _t('Value')
					]
				]
			]),
		];
	}
	# -------------------------------------------------------
}