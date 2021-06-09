<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/SearchSchema.php :
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
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');

class SearchSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		$schema = \GraphQLServices\Helpers\itemSchemaDefinitions();
		
		$schema[] = $tableTypeType = new ObjectType([
			'name' => 'Result',
			'description' => 'Search result',
			'fields' => [
				'table' => [
					'type' => Type::string(),
					'description' => 'Table searched'
				],
				'search' => [
					'type' => Type::string(),
					'description' => 'Search expression'
				],
				'count' => [
					'type' => Type::int(),
					'description' => 'Number of items found'
				],
				'results' => [
					'type' => Type::listOf($schema[sizeof($schema)-1]),
					'description' => 'Items found'
				]
			]
		]);
		
		return $schema;
	}
	# -------------------------------------------------------
}