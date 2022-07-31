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

require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/ItemSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');

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
							'name' => 'identifier',
							'type' => Type::string(),
							'description' => _t('Record identifier. Either a integer primary key or alphanumeric idno value.')
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
						$rec = self::resolveIdentifier($table = $args['table'], $args['identifier']);
						$rec_pk = $rec->primaryKey();
						
						$check_access = \GraphQLServices\Helpers\filterAccessValues($args['checkAccess']);
						
						$target = $args['target'];
						if(!\Datamodel::tableExists($target)) { 
							throw new \ServiceException(_t('Invalid target'));
						}
						if(!($linking_table = \Datamodel::getLinkingTableName($table, $target))) {
							throw new \ServiceException(_t('Cannot resolve relationship'));
						}
						
						$target_pk = \Datamodel::primaryKey($target);
						$rels = $rec->getRelatedItems($target, ['checkAccess' => $check_access, 'restrictToTypes' => $args['restrictToTypes'], 'restrictToRelationshipTypes' => $args['restrictToRelationshipTypes']]);
					
						$rel_list = [];
						if (sizeof($rel_ids = array_map(function($v) use ($resolve_to_related, $target_pk) { return $v[$resolve_to_related ? $target_pk : 'relation_id']; }, $rels)) > 0) {
							$rel_types = array_values(array_map(function($v) {
								return [
									'relationship_typename' => $v['relationship_typename'],
									'relationship_typecode' => $v['relationship_type_code'],
									'relationship_type_id' => $v['relationship_type_id']
								];
							}, $rels));
							$qr = caMakeSearchResult($resolve_to_related ? $target : $linking_table, $rel_ids);
							while($qr->nextHit()) {
								$r = $qr->getInstance();
							
								$bundles = \GraphQLServices\Helpers\extractBundleNames($r, $args);
								$data = \GraphQLServices\Helpers\fetchDataForBundles($r, $bundles, []);
								
								$rel_type = array_shift($rel_types);
								
								if(is_array($rel_ids = $r->get("{$linking_table}.relation_id", ['returnAsArray' => true]))) {
									foreach($rel_ids as $rel_id) {
										$rel_list[] = array_merge([
											'id' => $rel_id,
											'table' => $linking_table,
											'bundles' => $data
										], $rel_type);
									}
								}
							}
						}
						return ['table' => $rec->tableName(), 'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 'identifier' => $args['identifier'], 'id' => $rec->getPrimaryKey(), 'relationships' => $rel_list];
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
