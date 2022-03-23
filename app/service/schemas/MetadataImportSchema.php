<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/MetadataImportSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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

class MetadataImportSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$importerType = new ObjectType([
				'name' => 'Importer',
				'description' => 'Information about an importer',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'ID of importer'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of importer'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for importer'
					],
					'table' => [
						'type' => Type::string(),
						'description' => 'Table importer targets'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type in table importer targets'
					],
					'formats' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Data formats importer accepts'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Source of importer'
					],
					'errors' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'List of errors during add or update of importer'
					],
				]
			]),
			$importerSettingDefinitionOptionType = new ObjectType([
				'name' => 'ImporterSettingDefinitionOption',
				'description' => 'Definition of an importer setting',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Option name'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Option value'
					]
				]
			]),
			$importerSettingDefinitionType = new ObjectType([
				'name' => 'ImporterSettingDefinition',
				'description' => 'Definition of an importer setting',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of setting'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for setting'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Description of setting'
					],
					'options' => [
						'type' => Type::listOf($importerSettingDefinitionOptionType),
						'description' => 'Description of setting'
					],
				]
			]),
			$importerSettingType = new InputObjectType([
				'name' => 'ImporterSetting',
				'fields' => [
					'code' => [
						'type' => Type::string(),
						'description' => 'Settings code'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Value of setting'
					]
				]
			]),
		];
	}
	# -------------------------------------------------------
}