<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/ItemController.php :
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
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\ItemSchema;
use GraphQLServices\Helpers\Item;

require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/ItemSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');
require_once(__CA_APP_DIR__.'/service/helpers/ItemHelpers.php');

class ItemController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	#
	static $config = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				// ------------------------------------------------------------
				'get' => [
					'type' => ItemSchema::get('Item'),
					'description' => _t('Get data for an item'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table name. (Eg. ca_objects)')
						],
						[
							'name' => 'identifier',
							'type' => Type::string(),
							'description' => _t('Record identifier. Either a integer primary key or alphanumeric idno value.')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Bundles to return.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$rec = self::resolveIdentifier($table = $args['table'], $args['identifier']);
						$rec_pk = $rec->primaryKey();
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args);
						$data = \GraphQLServices\Helpers\fetchDataForBundles($rec, $bundles, []);
						
						return ['table' => $rec->tableName(), 'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 'identifier' => $args['identifier'], 'id' => $rec->getPrimaryKey(), 'bundles' => $data];
					}
				],
				// ------------------------------------------------------------
				'getRelationships' => [
					'type' => ItemSchema::get('RelationshipList'),
					'description' => _t('Get data for an item'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table name. (Eg. ca_objects)')
						],
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('Numeric database id value of record.')
						],
						[
							'name' => 'idno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record.')
						],
						[
							'name' => 'identifier',
							'type' => Type::string(),
							'description' => _t('Record identifier. Either a numeric database id or alphanumeric idno value.')
						],
						[
							'name' => 'target',
							'type' => Type::string(),
							'description' => _t('Related table name. (Eg. ca_entities)')
						],
						[
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned records to specified types.')
						],
						[
							'name' => 'restrictToRelationshipTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned records to specified relationship types.')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Zero-based start index for returned relationships. If omitted starts from the first relationship.')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of relationships to return. If omitted all relationships are returned.')
						],
						[
							'name' => 'targets',
							'type' => Type::listOf(ItemSchema::get('TargetListInputItem')),
							'description' => _t('List of tables (with options) to return relationships for')
						],
						[
							'name' => 'checkAccess',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Filter results by access values')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Bundles to return.')
						],
						[
							'name' => 'includeMedia',
							'type' => Type::boolean(),
							'description' => 'Include representations linked to related items?'
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => 'If including representations, which versions to return'
						],
						[
							'name' => 'restrictMediaToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => 'If including representations, which restrict to specified types'
						],
						[
							'name' => 'resolveRelativeToRelated',
							'type' => Type::boolean(),
							'description' => _t('Resolve all bundles relative to related items, rather than the relationship.'),
							'defaultValue' => false
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$resolve_to_related = $args['resolveRelativeToRelated'];
						
						// TODO: add explicit parameter for idno and id (to handle case where numeric idnos are used) 
						$opts = [];
						list($identifier, $opts) = \GraphQLServices\Helpers\resolveParams($args);
						$rec = self::resolveIdentifier($table = $args['table'], $identifier, null, $opts);
						$rec_pk = $rec->primaryKey();
						
						$check_access = \GraphQLServices\Helpers\filterAccessValues($args['checkAccess']);
						
						$targets = [];
						if(is_array($args['targets'])) {
							$targets = $args['targets'];
						} elseif($target = $args['target']) {
							$targets[] = [
								'table' => $target,
								'restrictToTypes' => $args['restrictToTypes'] ?? null,
								'restrictToRelationshipTypes' => $args['restrictToRelationshipTypes'] ?? null,
								'bundles' => $args['bundles'] ?? [],
								'start' => $args['start'] ?? null,
								'limit' => $args['limit'] ?? null,
								'includeMedia' => $args['includeMedia'] ?? null,
								'mediaVersions' => $args['mediaVersions'] ?? null,
								'restrictMediaToTypes' => $args['restrictMediaToTypes'] ?? null
							];
						} else {
							throw new \ServiceException(_t('No target specified'));
						}
						
						$rels_by_target = [];
						foreach($targets as $t) {
							$rels_by_target[] = \GraphQLServices\Helpers\Item\processTarget($rec, $table, $t, ['resolveRelativeToRelated' => $resolve_to_related]);
						}
						return [
							'table' => $rec->tableName(), 
							'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 
							'identifier' => $args['identifier'], 
							'id' => $rec->getPrimaryKey(), 
							'targets' => $rels_by_target,
							'relationships' => $rels_by_target[0]['relationships'] ?? null
						];
					}
				]
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
			
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
}
