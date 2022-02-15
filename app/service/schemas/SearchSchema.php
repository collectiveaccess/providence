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
use GraphQL\Type\Definition\EnumType;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');

class SearchSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		$schema = \GraphQLServices\Helpers\itemSchemaDefinitions();
		
		$item = array_shift(array_filter($schema, function($v) { return $v->name === 'Item';}));
		
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
					'type' => Type::listOf($item),
					'description' => 'Items found'
				]
			]
		]);
		
		$schema[] = $bundleSearchOperatorType = new EnumType([
			'name' => 'BundleSearchOperator',
			'description' => 'Search operators',
			'values' => [
				'LT' => [
					'value' => '<',
					'description' => 'Less than'
				],
				'LTE' => [
					'value' => '<=',
					'description' => 'Less than or equal'
				],
				'GT' => [
					'value' => '>',
					'description' => 'Greater than'
				],
				'GTE' => [
					'value' => '>=',
					'description' => 'Greater than or equal'
				],
				'EQ' => [
					'value' => '=',
					'description' => 'Equal'
				],
				'NOT_EQ' => [
					'value' => '<>',
					'description' => 'Not equal'
				],
				'LIKE' => [
					'value' => 'LIKE',
					'description' => 'LIKE wildcard expression'
				],
				'IN' => [
					'value' => 'IN',
					'description' => 'IN list'
				],
				'NOT_IN' => [
					'value' => 'NOT IN',
					'description' => 'NOT IN list'
				],
				'BETWEEN' => [
					'value' => 'BETWEEN',
					'description' => 'BETWEEN values in list'
				]
			]
		]);
		
		$schema[] = $bundleSearchValueType = new InputObjectType([
			'name' => 'BundleSearchValue',
			'description' => '.',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'description' => 'Name of bundle value'
				],
				'operator' => [
					'type' => $bundleSearchOperatorType,
					'description' => 'Search operator',
					'defaultValue' => '='
				],
				'value' => [
					'type' => Type::string(),
					'description' => 'Search value'
				],
				'valueList' => [
					'type' => Type::listOf(Type::string()),
					'description' => 'List of search values'
				]
			]
		]);
		
		$schema[] = $criterionType = new InputObjectType([
			'name' => 'Criterion',
			'description' => 'Search criterion',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'description' => 'Bundle to search'
				],
				'operator' => [
					'type' => $bundleSearchOperatorType,
					'description' => 'Search operator',
					'defaultValue' => '='
				],
				'value' => [
					'type' => Type::string(),
					'description' => 'Value to search for'
				],
				'valueList' => [
					'type' => Type::listOf(Type::string()),
					'description' => 'List of search values'
				],
				'values' => [
					'type' => Type::listOf($bundleSearchValueType),
					'description' => 'Container sub-values'
				]
			]
		]);
		
		$schema[] = $ancestorCriteriaList = new InputObjectType([
			'name' => 'AncestorCriteriaList',
			'description' => 'Hierarchical filter criteria',
			'fields' => [
				'criteria' => [
					'type' => Type::listOf($criterionType),
					'description' => 'List of criteria'
				]
			]
		]);
		
		$schema[] = $valueMap  = new ObjectType([
			'name' => 'ValueMap',
			'description' => 'Mapping between value and database id',
			'fields' => [
				'value' => [
					'type' => Type::string(),
					'description' => 'Value'
				],
				'id' => [
					'type' => Type::int(),
					'description' => 'First matched ID'
				],
				'ids' => [
					'type' => Type::listOf(Type::int()),
					'description' => 'Full list of matched IDs'
				],
				'idno' => [
					'type' => Type::string(),
					'description' => 'First matched identifer'
				],
				'idnos' => [
					'type' => Type::listOf(Type::string()),
					'description' => 'Full list of matched identifers'
				],
			]
		]);
		
		$schema[] = $existenceMap  = new ObjectType([
			'name' => 'ExistenceMap',
			'description' => 'Map of existing records',
			'fields' => [
				'table' => [
					'type' => Type::string(),
					'description' => 'Table searched'
				],
				'bundle' => [
					'type' => Type::string(),
					'description' => 'Bundle searched'
				],
				'map' => [
					'type' => Type::string(),
					'description' => 'JSON-encoded table of values to existing ids'
				],
				'values' => [
					'type' => Type::listOf($valueMap),
					'description' => 'Value map'
				]
			]
		]);
		
		return $schema;
	}
	# -------------------------------------------------------
}