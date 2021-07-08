<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/ConfigurationSchema.php :
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
namespace GraphQLServices\Schemas;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 

class ConfigurationSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$configurationValueType = new ObjectType([
				'name' => 'ConfigurationValue',
				'description' => '',
				'fields' => [
					'file' => [
						'type' => Type::string(),
						'description' => 'Name of file'
					],
					'key' => [
						'type' => Type::string(),
						'description' => 'Name of type'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type of value (assoc, list or scalar)'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'JSON-encoded value'
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}