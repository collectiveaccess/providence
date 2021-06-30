<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/EditSchema.php :
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

class EditSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$EditErrorType = new ObjectType([
				'name' => 'EditError',
				'description' => 'Details of error during edit.',
				'fields' => [
					'code' => [
						'type' => Type::int(),
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
			$EditWarningType = new ObjectType([
				'name' => 'EditWarning',
				'description' => 'Details of warning during edit.',
				'fields' => [
					'message' => [
						'type' => Type::string(),
						'description' => 'Warning message'
					],
					'bundle' => [
						'type' => Type::string(),
						'description' => 'Bundle where warning occurred'
					]
				]
			]),
			$EditResultType = new ObjectType([
				'name' => 'EditResult',
				'description' => 'Result of multiple record add or edit',
				'fields' => [
					'id' => [
						'type' => Type::listOf(Type::int()),
						'description' => 'IDs of added/edited records'
					],
					'table' => [
						'type' => Type::string(),
						'description' => 'Table of Edit'
					],
					'idno' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Identifiers of added/edited records'
					],
					'labels' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Preferred labels for new records'
					],
					'errors' => [	
						'type' => Type::listOf($EditErrorType),
						'description' => 'List of errors'
					],
					'warnings' => [	
						'type' => Type::listOf($EditWarningType),
						'description' => 'List of warnings'
					],
					'changed' => [
						'type' => Type::int(),
						'description' => 'Number of records affected by edit'
					]
				]
			]),
			// Bundles
			$EditBundleValueType = new InputObjectType([
				'name' => 'BundleValue',
				'description' => '.',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of bundle value'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Bundle value'
					]
				]
			]),
			$EditBundleType = new InputObjectType([
				'name' => 'Bundle',
				'description' => '.',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Bundle name (Eg. ca_objects.preferred_labels; ca_objects.description)'
					],
					'locale' => [
						'type' => Type::string(),
						'description' => 'Locale'
					],
					'id' => [
						'type' => Type::int(),
						'description' => 'ID for existing bundle value to edit or delete. Omitted for newly created bundles.'
					],
					'delete' => [
						'type' => Type::boolean(),
						'description' => 'Set to 1 to indicate value referenced by id parameter is to be removed.'
					],
					'replace' => [
						'type' => Type::boolean(),
						'description' => 'Set to 1 to indicate value is to replace the first existing value, or else added.'
					],
					'value' => [
						'type' => Type::string(),
						'description' => 'Value to set bundle to, when bundle has a simple, single value structure. Can be used in place of the values list for single value bundles.'
					],
					'values' => [
						'type' => Type::listOf($EditBundleValueType),
						'description' => 'Bundle values to set.'
					],
					'type_id' => [
						'type' => Type::string(),
						'description' => 'Optional type code for non-preferred labels'
					],
				]
			]),
			$RecordType = new InputObjectType([
				'name' => 'Record',
				'description' => '.',
				'fields' => [
					[
						'name' => 'type',
						'type' => Type::string(),
						'description' => _t('Type code for new record. (Eg. ca_objects)')
					],
					[
						'name' => 'id',
						'type' => Type::int(),
						'description' => _t('Integer id value for existing record.')
					],
					[
						'name' => 'idno',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value for new record.')
					],
					[
						'name' => 'identifier',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno or integer id value for existing record.')
					],
					[
						'name' => 'bundles',
						'type' => Type::listOf($EditBundleType),
						'description' => _t('Bundles to add')
					]
				]
			]),
		];
	}
	# -------------------------------------------------------
}


# Bundles:
// 		name: 'nonpreferred_labels',
// 		locale: 'en_US',
// 		id: null,  // only for edits
// 		delete: false, // only for edits; used to replace or remove value
// 		value: "This is a desc" // can be used for simple single-value attributes in lieu of values block
// 		values: [
// 			{ 'name': 'forename', 'value': 'Seth' },
// 			{ 'name': 'surname', 'value': 'Kaufman' },
// 			{ 'name': 'type', 'value': 'UF' }
// 		]


