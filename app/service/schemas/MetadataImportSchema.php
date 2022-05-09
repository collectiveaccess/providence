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
use GraphQL\Type\Definition\EnumType;
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
					],
					'values' => [
						'type' =>  Type::listOf(Type::string()),
						'description' => 'List of option values'
					]
				]
			]),
			$importerSettingDefinitionOptionInputType = new InputObjectType([
				'name' => 'ImporterSettingDefinitionInputOption',
				'description' => 'Definition of an importer setting',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Option name'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Option value'
					],
					'values' => [
						'type' =>  Type::listOf(Type::string()),
						'description' => 'List of option values'
					]
				]
			]),
			$importerMappingReplacementValueType = new ObjectType([
				'name' => 'ImporterMappingReplacementValue',
				'description' => 'Definition of an importer mapping original/replacement value pair',
				'fields' => [
					'original' => [
						'type' => Type::string(),
						'description' => 'Original value'
					],
					'replacement' => [
						'type' => Type::string(),
						'description' => 'Replacement value'
					]
				]
			]),
			$importerMappingReplacementValueInputType = new InputObjectType([
				'name' => 'ImporterMappingReplacementInputValue',
				'description' => 'Definition of an importer mapping original/replacement value pair',
				'fields' => [
					'original' => [
						'type' => Type::string(),
						'description' => 'Original value'
					],
					'replacement' => [
						'type' => Type::string(),
						'description' => 'Replacement value'
					]
				]
			]),
			$importerMappingRefineryType = new ObjectType([
				'name' => 'ImporterMappingRefinery',
				'description' => 'Definition of an importer mapping refinery specification',
				'fields' => [
					'refinery' => [
						'type' => Type::string(),
						'description' => 'Refinery code'
					],
					'options' => [
						'type' => Type::listOf($importerSettingDefinitionOptionType),
						'description' => 'Refinery options'
					]
				]
			]),
			$importerMappingRefineryInputType = new InputObjectType([
				'name' => 'ImporterMappingInputRefinery',
				'description' => 'Definition of an importer mapping refinery specification',
				'fields' => [
					'refinery' => [
						'type' => Type::string(),
						'description' => 'Refinery code'
					],
					'options' => [
						'type' => Type::listOf($importerSettingDefinitionOptionInputType),
						'description' => 'Refinery options'
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
					],
					'values' => [
						'type' =>  Type::listOf(Type::string()),
						'description' => 'List of setting values'
					]
				]
			]),
			$importerFormInfoType = new ObjectType([
				'name' => 'ImporterFormInfo',
				'description' => 'Information for importer form',
				'fields' => [
					'title' => [
						'type' => Type::string(),
						'description' => 'Title of form for display'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Description of form'
					],
					'required' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Required fields'
					],
					'properties' => [
						'type' => Type::string(),
						'description' => 'Information about each field in the form, serialized as JSON'
					],
					'uiSchema' => [
						'type' => Type::string(),
						'description' => 'UI display configuration about each field in the form, serialized as JSON'
					],
					'values' => [
						'type' => Type::string(),
						'description' => 'Form values, serialized as JSON'
					],
				]
			]),
			$importerMappingTypeEnum = new EnumType([
				'name' => 'ImporterMappingType',
				'description' => 'Mapping types',
				'values' => [
					'MAPPING' => [
						'value' => 'MAPPING',
						'description' => 'Mapping'
					],
					'CONSTANT' => [
						'value' => 'CONSTANT',
						'description' => 'Constant value'
					],
				]
			]),
			$importerMappingType = new ObjectType([
				'name' => 'ImporterMapping',
				'description' => 'Information for importer mapping',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Mapping ID'
					],
					'type' => [
						'type' => $importerMappingTypeEnum,
						'description' => 'Mapping type'
					],
					'group_id' => [
						'type' => Type::int(),
						'description' => 'Group ID'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Data source reference'
					],
					'destination' => [
						'type' => Type::string(),
						'description' => 'CollectiveAccess destination'
					],
					'options' => [
						'type' => Type::listOf($importerSettingDefinitionOptionType),
						'description' => 'Mapping options'
					],
					'replacement_values' => [
						'type' => Type::listOf($importerMappingReplacementValueType),
						'description' => 'Original and replacement values'
					],
					'refineries' => [
						'type' => Type::listOf($importerMappingRefineryType),
						'description' => 'Refineries'
					],
				]
			]),
			$importerMappingListType = new ObjectType([
				'name' => 'ImporterMappingListInfo',
				'description' => 'List of importer mappings',
				'fields' => [
					'mappings' => [
						'type' => Type::listOf($importerMappingType),
						'description' => 'List of mappings'
					],
				]
			]),
			$importerMappingType = new InputObjectType([
				'name' => 'ImporterMappingInput',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Mapping ID'
					],
					'type' => [
						'type' => $importerMappingTypeEnum,
						'description' => 'Mapping type'
					],
					'group' => [
						'type' => Type::string(),
						'description' => 'Group code or ID'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Data source reference'
					],
					'destination' => [
						'type' => Type::string(),
						'description' => 'CollectiveAccess destination'
					],
					'options' => [
						'type' => Type::listOf($importerSettingDefinitionOptionInputType),
						'description' => 'Mapping options'
					],
					'replacement_values' => [
						'type' => Type::listOf($importerMappingReplacementValueInputType),
						'description' => 'Original and replacement values'
					],
					'refineries' => [
						'type' => Type::listOf($importerMappingRefineryInputType),
						'description' => 'Refineries'
					]
				]
			]),
			$importerErrorType = new ObjectType([
				'name' => 'ImporterError',
				'description' => 'Details of error during edit.',
				'fields' => [
					'id' => [
						'type' => Type::string(),
						'description' => 'Identifier of importer or mapping message pertains to'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Error code'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Error message'
					],
					'bundle' => [
						'type' => Type::string(),
						'description' => 'Bundle where error occurred'
					]
				]
			]),
			$importerWarningType = new ObjectType([
				'name' => 'ImporterWarning',
				'description' => 'Details of warning during edit.',
				'fields' => [
					'id' => [
						'type' => Type::string(),
						'description' => 'Identifier of importer or mapping message pertains to'
					],
					'code' => [
						'type' => Type::int(),
						'description' => 'Warning code'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Warning message'
					],
					'bundle' => [
						'type' => Type::string(),
						'description' => 'Bundle where error occurred'
					]
				]
			]),
			$importerInfoType = new ObjectType([
				'name' => 'ImporterInfo',
				'description' => 'Informational message during edit.',
				'fields' => [
					'id' => [
						'type' => Type::string(),
						'description' => 'Identifier of importer or mapping message pertains to'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Informational message code'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Informational message'
					],
					'bundle' => [
						'type' => Type::string(),
						'description' => 'Bundle where error occurred'
					]
				]
			]),
			$importerResultType = new ObjectType([
				'name' => 'ImporterResult',
				'description' => 'Result of multiple record add or edit',
				'fields' => [
					'id' => [
						'type' => Type::listOf(Type::int()),
						'description' => 'IDs of added/edited records'
					],
					'errors' => [	
						'type' => Type::listOf($importerErrorType),
						'description' => 'List of errors'
					],
					'warnings' => [	
						'type' => Type::listOf($importerWarningType),
						'description' => 'List of warnings'
					],
					'info' => [	
						'type' => Type::listOf($importerInfoType),
						'description' => 'List of informational messages'
					]
				]
			]),
			$importerReorderInputType = new InputObjectType([
				'name' => 'ImporterReorderInputType',
				'fields' => [
					'sorted_ids' => [
						'type' => Type::string(),
						'description' => 'Sorted importer item item_ids'
					]
				]
			]),
			
		];
	}
	# -------------------------------------------------------
}