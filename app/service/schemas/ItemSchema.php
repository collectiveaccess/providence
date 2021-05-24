<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/ItemSchema.php :
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

class ItemSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$bundleSubValueType = new ObjectType([
				'name' => 'BundleSubValue',
				'description' => 'Sub-value for a bundle',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Sub-value name'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Sub-value code'
					],
					'dataType' => [
						'type' => Type::string(),
						'description' => 'Data type for sub-value'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Sub-value'
					]
				]
			]),
			$bundleValueType = new ObjectType([
				'name' => 'BundleValue',
				'description' => 'Value for a bundle',
				'fields' => [
					'locale' => [
						'type' => Type::string(),
						'description' => 'Locale for value (Eg. en_US)'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Value for bundle'
					],
					'subvalues' => [
						'type' => Type::listOf($bundleSubValueType),
						'description' => 'Value list for values with dataType=Container'
					]
				]
			]),
			$bundleValueListType = new ObjectType([
				'name' => 'BundleValueList',
				'description' => 'List of values for a bundle',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of bundle'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Bundle code'
					],
					'dataType' => [
						'type' => Type::string(),
						'description' => 'Data type for bundle'
					],
					'values' => [
						'type' => Type::listOf($bundleValueType),
						'description' => 'List of values for bundle'
					]
				]
			]),
			$itemType = new ObjectType([
				'name' => 'Item',
				'description' => 'A record',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'ID of item'
					],
					'table' => [
						'type' => Type::string(),
						'description' => 'Table of item'
					],
					'identifier' => [
						'type' => Type::string(),
						'description' => 'Item identifier'
					],
					'bundles' => [
						'type' => Type::listOf($bundleValueListType),
						'description' => ''
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}