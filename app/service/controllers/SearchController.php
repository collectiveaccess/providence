<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/SearchController.php :
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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/SearchSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/SearchHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\SearchSchema;


class SearchController extends \GraphQLServices\GraphQLServiceController {
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
				// Search
				// ------------------------------------------------------------
				'search' => [
					'type' => SearchSchema::get('Result'),
					'description' => _t('Full-text search'),
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
							'description' => _t('Table to search')
						],
						[
							'name' => 'search',
							'type' => Type::string(),
							'description' => _t('Search expression')
						],
						[
							'name' => 'filterByAncestors',
							'type' => Type::listOf(SearchSchema::get('AncestorCriteriaList')),
							'description' => _t('Filter results by ancestors')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Bundles to return')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Start index'),
							'defaultValue' => 0
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return'),
							'defaultValue' => null
						],
						[
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Type restrictions')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$search = trim($args['search']);
						$table = trim($args['table']);
						
						if(!strlen($search)) { 
							throw new \ServiceException(_t('Search cannot be empty'));
						}
						
						$tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						if(!in_array($table, $tables, true)) { 
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
						// Check user privs
						// TODO: add GraphQL-specific access check?
						if(!in_array($table, ['ca_list_items', 'ca_lists'], true) && !$u->canDoAction("can_search_{$table}")) {
							throw new \ServiceException(_t('Access denied for table: %1', $table));
						}
						
						$s = caGetSearchInstance($table);
						
						if($args['restrictToTypes'] && !is_array($args['restrictToTypes'])) {
							$args['restrictToTypes'] = [$args['restrictToTypes']];
						}
						if(is_array($args['restrictToTypes']) && sizeof($args['restrictToTypes'])) {
							$s->setTypeRestrictions($args['restrictToTypes']);
						}
						
						$qr = $s->search($search);
						$rec = \Datamodel::getInstance($table, true);
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args, []);

						$data = \GraphQLServices\Helpers\fetchDataForBundles($qr, $bundles, ['start' => $args['start'], 'limit' => $args['limit'], 'filterByAncestors' => $args['filterByAncestors']]);
						
						return ['table' => $table, 'search' => $search, 'count' => sizeof($data), 'results' => $data];
					}
				],
				// ------------------------------------------------------------
				// Find
				// ------------------------------------------------------------
				'find' => [
					'type' => SearchSchema::get('Result'),
					'description' => _t('Find and return records using field-level values.'),
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
							'description' => _t('Table to search')
						],
						[
							'name' => 'criteria',
							'type' => Type::listOf(SearchSchema::get('Criterion')),
							'description' => _t('Search criteria')
						],
						[
							'name' => 'filterByAncestors',
							'type' => Type::listOf(SearchSchema::get('AncestorCriteriaList')),
							'description' => _t('Filter results by ancestors')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Bundles to return')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Start index'),
							'defaultValue' => 0
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return'),
							'defaultValue' => null
						],
						[
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Type restrictions')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$table = trim($args['table']);
						
						$tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						if(!in_array($table, $tables, true)) { 
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
						// Check user privs
						// TODO: add GraphQL-specific access check?
						if(!in_array($table, ['ca_list_items', 'ca_lists'], true) && !$u->canDoAction("can_search_{$table}")) {
							throw new \ServiceException(_t('Access denied for table: %1', $table));
						}
						
						if($args['restrictToTypes'] && !is_array($args['restrictToTypes'])) {
							$args['restrictToTypes'] = [$args['restrictToTypes']];
						}
						
						if(!($qr = $table::find(\GraphQLServices\Helpers\Search\convertCriteriaToFindSpec($args['criteria'], $table), ['returnAs' => 'searchResult', 'allowWildcards' => true, 'restrictToTypes' => $args['restrictToTypes']]))) {
							throw new \ServiceException(_t('No results for table: %1', $table));
						}
					
						$rec = \Datamodel::getInstance($table, true);
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args, []);

						$data = \GraphQLServices\Helpers\fetchDataForBundles($qr, $bundles, ['start' => $args['start'], 'limit' => $args['limit'], 'filterByAncestors' => $args['filterByAncestors']]);
						
						return ['table' => $table, 'search' => $search, 'count' => sizeof($data), 'results' => $data];
					}
				],
				// ------------------------------------------------------------
				// Find
				// ------------------------------------------------------------
				'exists' => [
					'type' => SearchSchema::get('ExistenceMap'),
					'description' => _t('Determine whether records exist based upon values in a bundle'),
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
							'description' => _t('Table to search')
						],
						[
							'name' => 'bundle',
							'type' => Type::string(),
							'description' => _t('Bundle to search')
						],
						[
							'name' => 'values',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of values')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$table = trim($args['table']);
						$bundle = trim($args['bundle']);
						$values = $args['values'];
						
						$tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						if(!in_array($table, $tables, true)) { 
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
						// Does user have access to this service?
						if (!$u->canDoAction('can_access_graphql_exists_search_service')) {
							throw new \ServiceException(_t('Access denied'));
						}
						
						// Check user privs
						//
						// NOTE: Currently does not enforce item-level or type-level access control
						// 		 However, bundle-level ACL is supported
						if(!in_array($table, ['ca_list_items', 'ca_lists'], true) && !$u->canDoAction("can_search_{$table}")) {
							throw new \ServiceException(_t('Access denied for table: %1', $table));
						}
						
						$bundle_bits = explode('.', $bundle);
						if((sizeof($bundle_bits) > 1) && \Datamodel::tableExists($bundle_bits[0])) {
							if($bundle_bits[0] !== $table) {
								throw new \ServiceException(_t('Bundle must be in current table %1', $table));
							}
							array_shift($bundle_bits);
						}
						
						$t = \Datamodel::getInstance($table, true);
						switch($bundle_bits[0]) {
							case 'idno':
								$ids = $table::getIDsForIdnos($values, ['returnAll' => true]);
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k) {return ['id' => $v[0], 'ids' => [$v], 'value' => $k]; }, $ids, array_keys($ids));
								break;
							case 'preferred_labels':
								$ids = $table::getIDsForlabels($values, ['returnAll' => true, 'field' => $bundle_bits[1] ?? null]);
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k) {return ['id' => $v[0], 'ids' => $v,'value' => $k]; }, $ids, array_keys($ids));
							
								break;
							default:
								$b = array_pop($bundle_bits);
								if(!$t->hasElement($b)) { 
									throw new \ServiceException(_t('Metadata element %1 does not exist', $b));
								}
								if ($u->getBundleAccessLevel($table, $b) < __CA_BUNDLE_ACCESS_READONLY__) {
									throw new \ServiceException(_t('Access denied to %1', $b));
								}
								$ids = $table::getIDsForAttributeValues($b, $values, ['returnAll' => true]);
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k)  { return ['id' => $v[0], 'ids' => $v, 'value' => $k]; }, $ids, array_keys($ids));

								break;
						}
						
						return ['table' => $table, 'bundle' => $bundle, 'map' => json_encode($ids), 'values' => $value_map];
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
