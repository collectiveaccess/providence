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
require_once(__CA_APP_DIR__.'/service/schemas/SearchSchema.php');

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
					'idno' => [
						'type' => Type::string(),
						'description' => 'Identifier of record message pertains to'
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
			$EditWarningType = new ObjectType([
				'name' => 'EditWarning',
				'description' => 'Details of warning during edit.',
				'fields' => [
					'idno' => [
						'type' => Type::string(),
						'description' => 'Identifier of record message pertains to'
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
						'description' => 'Bundle where warning occurred'
					]
				]
			]),
			$EditInfoType = new ObjectType([
				'name' => 'EditInfo',
				'description' => 'Informational message during edit.',
				'fields' => [
					'idno' => [
						'type' => Type::string(),
						'description' => 'Identifier of record message pertains to'
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
						'description' => 'Bundle message pertains to'
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
					'info' => [	
						'type' => Type::listOf($EditInfoType),
						'description' => 'List of informational messages'
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
					'source' => [
						'type' => Type::string(),
						'description' => 'Source of value'
					],
				]
			]),
			$MatchRecordType = new InputObjectType([
				'name' => 'MatchRecord',
				'description' => '.',
				'fields' => [
					[
						'name' => 'search',
						'type' => Type::string(),
						'description' => _t('Search expression')
					],
					[
						'name' => 'criteria',
						'type' => Type::listOf(\GraphQLServices\Schemas\SearchSchema::get('Criterion')),
						'description' => _t('Search criteria')
					],
					[
						'name' => 'restrictToTypes',
						'type' => Type::listOf(Type::string()),
						'description' => _t('Type restrictions for search')
					],
				]
			]),
			$subjectRelationshipType = new InputObjectType([
				'name' => 'SubjectRelationship',
				'description' => 'Input for relationship added relative to a single subject',
				'fields' => [				
					[
						'name' => 'target',
						'type' => Type::string(),
						'description' => _t('Target table name. (Eg. ca_objects)')
					],
					[
						'name' => 'targetId',
						'type' => Type::int(),
						'description' => _t('Numeric database id of record to use as relationship target.')
					],
					[
						'name' => 'targetIdno',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value of record to use as relationship target')
					],
					[
						'name' => 'targetIdentifier',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship target.')
					],
					[
						'name' => 'relationshipType',
						'type' => Type::string(),
						'description' => _t('Relationship type code.')
					],
					[
						'name' => 'bundles',
						'type' => Type::listOf($EditBundleType),
						'description' => _t('Bundles to add')
					]
				]
			]),
			$RelationshipType = new InputObjectType([
				'name' => 'Relationship',
				'description' => 'Fully specified relationship',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'IDs of added/edited records'
					],				
					[
						'name' => 'subject',
						'type' => Type::string(),
						'description' => _t('Subject table name. (Eg. ca_objects)')
					],
					[
						'name' => 'subjectId',
						'type' => Type::int(),
						'description' => _t('Numeric database id of record to use as relationship subject.')
					],
					[
						'name' => 'subjectIdno',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value of record to use as relationship subject')
					],
					[
						'name' => 'subjectIdentifier',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship subject.')
					],
					[
						'name' => 'target',
						'type' => Type::string(),
						'description' => _t('Target table name. (Eg. ca_objects)')
					],
					[
						'name' => 'targetId',
						'type' => Type::int(),
						'description' => _t('Numeric database id of record to use as relationship target.')
					],
					[
						'name' => 'targetIdno',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value of record to use as relationship target')
					],
					[
						'name' => 'targetIdentifier',
						'type' => Type::string(),
						'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship target.')
					],
					[
						'name' => 'relationshipType',
						'type' => Type::string(),
						'description' => _t('Relationship type code.')
					],
					[
						'name' => 'bundles',
						'type' => Type::listOf($EditBundleType),
						'description' => _t('Bundles to add')
					]
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
					],
					[
						'name' => 'match',
						'type' => $MatchRecordType,
						'description' => _t('Match criteria')
					],
					[
						'name' => 'relationships',
						'type' => Type::listOf($RelationshipType),
						'description' => _t('List of relationship to create for new record')
					],
					[
						'name' => 'replaceRelationships',
						'type' => Type::boolean(),
						'description' => 'Set to 1 to indicate all relationships are to replaced with those specified in the current request. If not set relationships are merged with existing ones..'
					],
				]
			]),
		];
	}
	# -------------------------------------------------------
}