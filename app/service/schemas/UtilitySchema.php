<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/UtilitySchema.php :
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
use GraphQL\Type\Definition\EnumType;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 

class UtilitySchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$dateParseResult = new ObjectType([
				'name' => 'DateParseResult',
				'description' => 'Parsed date expression',
				'fields' => [
					'start' => [
						'type' => Type::float(),
						'description' => 'Start of date interval'
					],
					'end' => [
						'type' => Type::float(),
						'description' => 'End of date interval'
					],
					'text' => [
						'type' => Type::string(),
						'description' => 'Normalized text representation of date expression'
					]
				]
			]),
			$entityNameParseResult = new ObjectType([
				'name' => 'EntityNameParseResult',
				'description' => 'Parsed entity name',
				'fields' => [
					'forename' => [
						'type' => Type::string(),
						'description' => 'Parsed forename'
					],
					'surname' => [
						'type' => Type::string(),
						'description' => 'Parsed surname'
					],
					'middlename' => [
						'type' => Type::string(),
						'description' => 'Parsed middle name'
					],
					'displayname' => [
						'type' => Type::string(),
						'description' => 'Normalized name'
					],
					'suffix' => [
						'type' => Type::string(),
						'description' => 'Parsed suffix'
					],
					'prefix' => [
						'type' => Type::string(),
						'description' => 'Parsed prefix'
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}