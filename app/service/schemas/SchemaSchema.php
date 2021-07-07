<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/SchemaSchema.php :
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
			$bundleListType = new ObjectType([
				'name' => 'BundleList',
				'description' => 'List of available bundles',
				'fields' => [
					'bundles' => [
						'type' => Type::listOf($bundleType),
						'description' => 'Available bundles'
					],
				]
			])
		];
	}
	# -------------------------------------------------------
}