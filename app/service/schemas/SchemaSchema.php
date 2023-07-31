<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/SchemaSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2023 Whirl-i-Gig
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

class SchemaSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$tableTypeType = new ObjectType([
				'name' => 'Type',
				'description' => 'A table type',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'ID of type'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of type'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for type'
					],
					'parent' => [
						'type' => Type::string(),
						'description' => 'Code for parent type'
					]
				]
			]),
			$tableType = new ObjectType([
				'name' => 'Table',
				'description' => 'A table',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of table'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for table'
					],
					'types' => [
						'type' => Type::listOf($tableTypeType),
						'description' => 'Types defined for table'
					],
				]
			]),
			$tableListType = new ObjectType([
				'name' => 'TableList',
				'description' => 'List of available tables',
				'fields' => [
					'tables' => [
						'type' => Type::listOf($tableType),
						'description' => 'Available tables'
					],
				]
			]),
			$typeRestrictionType = new ObjectType([
				'name' => 'TypeRestriction',
				'description' => 'A type restriction on a bundle',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of type bundle is restricted to'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type code of type bundle is restricted to'
					],
					'minAttributesPerRow' => [
						'type' => Type::int(),
						'description' => 'Minimum number of values allowed'
					],
					'maxAttributesPerRow' => [
						'type' => Type::int(),
						'description' => 'Maximum number of values allowed'
					],
					'minimumAttributeBundlesToDisplay' => [
						'type' => Type::int(),
						'description' => 'Minimum number of bundles to display in editing form'
					]
				]
			]),
			$settingsType = new ObjectType([
				'name' => 'Settings',
				'description' => 'Bundle settings',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of setting'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Value of setting'
					]
				]
			]),
			$subelementType = new ObjectType([
				'name' => 'SubElement',
				'description' => 'A sub-element in a container bundle',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of sub-element'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for sub-element'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Sub-element type (label, intrinsic, metadata element, special)'
					],
					'list' => [
						'type' => Type::string(),
						'description' => 'List used (list sub-elements only)'
					],
					'dataType' => [
						'type' => Type::string(),
						'description' => 'Data type (text, number, url, etc.)'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Sub-element description'
					],
					'settings' => [
						'type' => Type::listOf($settingsType),
						'description' => 'Sub-elemment settings'
					]
				]
			]),
			$bundleType = new ObjectType([
				'name' => 'Bundle',
				'description' => 'A bundle',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of bundle'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for bundle'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Bundle type (label, intrinsic, metadata element, special)'
					],
					'list' => [
						'type' => Type::string(),
						'description' => 'List used (list bundles only)'
					],
					'dataType' => [
						'type' => Type::string(),
						'description' => 'Data type (text, number, url, etc.)'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Bundle description'
					],
					'typeRestrictions' => [
						'type' => Type::listOf($typeRestrictionType),
						'description' => 'Type restrictions for bundle (metadata elements only)'
					],
					'settings' => [
						'type' => Type::listOf($settingsType),
						'description' => 'Bundle settings'
					],
					'subelements' => [
						'type' => Type::listOf($subelementType),
						'description' => 'Sub-elements'
					]
				]
			]),
			$bundleValidType = new ObjectType([
				'name' => 'BundleValid',
				'description' => 'Result of bundle validity check',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of bundle'
					],
					'isValid' => [
						'type' => Type::boolean(),
						'description' => 'Is valid?'
					]
				]
			]),
			$bundleValidityListType = new ObjectType([
				'name' => 'BundleValidityList',
				'description' => 'List of bundle validity checks',
				'fields' => [
					'bundles' => [
						'type' => Type::listOf($bundleValidType),
						'description' => 'Bundles'
					],
				]
			]),
			$bundleListType = new ObjectType([
				'name' => 'BundleList',
				'description' => 'List of available bundles',
				'fields' => [
					'bundles' => [
						'type' => Type::listOf($bundleType),
						'description' => 'Available bundles'
					],
				]
			]),
			$relationshipTypeType = new ObjectType([
				'name' => 'RelationshipType',
				'description' => 'A relationship type',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'ID of relationship type'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of relationship type'
					],
					'name_reverse' => [
						'type' => Type::string(),
						'description' => 'Reverse sense name of relationship type'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Description of relationship type'
					],
					'description_reverse' => [
						'type' => Type::string(),
						'description' => 'Description of reverse sense of relationship type'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for relationship type'
					],
					'rank' => [
						'type' => Type::int(),
						'description' => 'Sort rank for relationship type'
					],
					'locale' => [
						'type' => Type::string(),
						'description' => 'Locale for relationship type label'
					],
					'rank' => [
						'type' => Type::int(),
						'description' => 'Sort rank for relationship type'
					],
					'isDefault' => [
						'type' => Type::boolean(),
						'description' => 'Is default type'
					],
					'restrictToTypeLeft' => [
						'type' => Type::string(),
						'description' => 'Type restriction for left table'
					],
					'restrictToTypeRight' => [
						'type' => Type::string(),
						'description' => 'Type restriction for right table'
					],
					'includeSubtypesLeft' => [
						'type' => Type::boolean(),
						'description' => 'Include subtypes for left table type restriction'
					],
					'includeSubtypesRight' => [
						'type' => Type::boolean(),
						'description' => 'Include subtypes for right table type restriction'
					],	
				]
			]),
			$relationshipTypeListType = new ObjectType([
				'name' => 'RelationshipTypeList',
				'description' => 'List of relationship types',
				'fields' => [
					'table' => [
						'type' => Type::string(),
						'description' => 'Name of table'
					],
					'relatedTable' => [
						'type' => Type::string(),
						'description' => 'Name of related table'
					],
					'relationshipTable' => [
						'type' => Type::string(),
						'description' => 'Name of table containing relationships'
					],
					'types' => [
						'type' => Type::listOf($relationshipTypeType),
						'description' => 'Available relationship types'
					],
				]
			]),
		];
	}
	# -------------------------------------------------------
}