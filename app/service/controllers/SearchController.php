<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/SearchController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2024 Whirl-i-Gig
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
							'name' => 'searches',
							'type' => Type::listOf(SearchSchema::get('SearchesInput')),
							'description' => _t('Searches to run')
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
							'name' => 'checkAccess',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Filter results by access values')
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
						[
							'name' => 'includeSubtypes',
							'type' => Type::boolean(),
							'description' => _t('Expand type restriction to include subtypes?'),
							'defaultValue' => true
						],
						[
							'name' => 'filterNonPrimaryRepresentations',
							'type' => Type::boolean(),
							'description' => 'Only return primary representation?'
						],
						[
							'name' => 'filterDeaccessioned',
							'type' => Type::boolean(),
							'description' => 'Remove deaccessioned records?',
							'defaultValue' => false
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$search = trim($args['search']);
						$searches = $args['searches'];
						
						if(!is_array($searches) || !sizeof($searches)) {
							$searches = [[
								'table' => trim($args['table']),
								'name' => trim($args['table']),
								'search' => $args['search'],
								'filterByAncestors' => $args['filterByAncestors'],
								'checkAccess' => $args['checkAccess'],
								'bundles' => $args['bundles'],
								'start' => $args['start'],
								'limit' => $args['limit'],
								'restrictToTypes' => $args['restrictToTypes'],
								'filterNonPrimaryRepresentations' => $args['filterNonPrimaryRepresentations'] ?? false,
								'filterDeaccessioned' => $args['filterDeaccessioned'] ?? false
							]];
						}
						$valid_tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						$results = [];
						
						$ftable = $fsearch = $fcount = $fresult = null;
						foreach($searches as $t) {
							$table = $t['table'] ?? null;
							$name = $t['name'] ?? $table;
							$search = $t['search'] ?? null;
							$check_access = $t['checkAccess'] ? \GraphQLServices\Helpers\filterAccessValues($t['checkAccess']) : null;
						
							if(!strlen($search)) { 
								throw new \ServiceException(_t('Search cannot be empty'));
							}
						
							if(!in_array($table, $valid_tables, true)) { 
								throw new \ServiceException(_t('Invalid table: %1', $table));
							}
						
							// Check user privs
							// TODO: add GraphQL-specific access check?
							if(!in_array($table, ['ca_list_items', 'ca_lists'], true) && !$u->canDoAction("can_search_{$table}")) {
								throw new \ServiceException(_t('Access denied for table: %1', $table));
							}
						
							$s = caGetSearchInstance($table);
						
							if($t['restrictToTypes'] && !is_array($t['restrictToTypes'])) {
								$t['restrictToTypes'] = [$t['restrictToTypes']];
							}
							if(is_array($t['restrictToTypes']) && sizeof($t['restrictToTypes'])) {
								$s->setTypeRestrictions($t['restrictToTypes'], ['includeSubtypes' => $args['includeSubtypes'] ?? true]);
							}
						
							$qr = $s->search($search, ['checkAccess' => $t['checkAccess'] ?? null, 'filterDeaccessionedRecords' => $t['filterDeaccessioned'] ?? false]);
							$rec = \Datamodel::getInstance($table, true);
						
							$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $t, []);

							$results[] = ['name' => $name, 'result' => $r = \GraphQLServices\Helpers\fetchDataForBundles($qr, $bundles, ['checkAccess' => $check_access, 'start' => $t['start'], 'limit' => $t['limit'], 'filterByAncestors' => $t['filterByAncestors'], 'filterNonPrimaryRepresentations' => $t['filterNonPrimaryRepresentations']]), 'count' => $qr->numHits()];
							if(is_null($ftable)) {
								// Stash details of first search for use in "flat" response
								$ftable = $table;
								$fsearch = $search;
								$fcount = $qr->numHits();
								$fresult = $r;
							}
						}
						return ['table' => $ftable, 'search' => $fsearch, 'count' => $fcount, 'results' => $results, 'result' => $fresult];
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
							'name' => 'finds',
							'type' => Type::listOf(SearchSchema::get('FindsInput')),
							'description' => _t('Finds to run')
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
							'name' => 'checkAccess',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Filter results by access values')
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
						[
							'name' => 'includeSubtypes',
							'type' => Type::boolean(),
							'description' => _t('Expand type restriction to include subtypes?'),
							'defaultValue' => true
						],
						[
							'name' => 'filterNonPrimaryRepresentations',
							'type' => Type::boolean(),
							'description' => 'Only return primary representation?'
						],
						[
							'name' => 'filterDeaccessioned',
							'type' => Type::boolean(),
							'description' => 'Remove deaccessioned records?',
							'defaultValue' => false
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$table = trim($args['table']);
						$finds = $args['finds'];
						
						if(!is_array($finds) || !sizeof($finds)) {
							$finds = [[
								'table' => trim($args['table']),
								'name' => trim($args['table']),
								'criteria' => $args['criteria'],
								'filterByAncestors' => $args['filterByAncestors'],
								'checkAccess' => $args['checkAccess'] ?? null,
								'bundles' => $args['bundles'],
								'start' => $args['start'],
								'limit' => $args['limit'],
								'restrictToTypes' => $args['restrictToTypes'],
								'includeSubtypes' => $args['includeSubtypes'] ?? true,
								'filterNonPrimaryRepresentations' => $args['filterNonPrimaryRepresentations'] ?? false,
								'filterDeaccessioned' => $args['filterDeaccessioned'] ?? false
							]];
						}
						
						$valid_tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						$results = [];
						
						$ftable = $fsearch = $fcount = $fresult = null;
						foreach($finds as $t) {
							$table = $t['table'] ?? null;
							$name = $t['name'] ?? $table;
							$check_access = $t['checkAccess'] ? \GraphQLServices\Helpers\filterAccessValues($t['checkAccess']) : null;
														
							if(!in_array($table, $valid_tables, true)) { 
								throw new \ServiceException(_t('Invalid table: %1', $table));
							}
						
							// Check user privs
							// TODO: add GraphQL-specific access check?
							if(!in_array($table, ['ca_list_items', 'ca_lists'], true) && !$u->canDoAction("can_search_{$table}")) {
								throw new \ServiceException(_t('Access denied for table: %1', $table));
							}
						
							if($t['restrictToTypes'] && !is_array($t['restrictToTypes'])) {
								$t['restrictToTypes'] = [$t['restrictToTypes']];
							}
						
							if(!($qr = $table::find(\GraphQLServices\Helpers\Search\convertCriteriaToFindSpec($t['criteria'], $table), [
								'returnAs' => 'searchResult', 
								'allowWildcards' => true, 
								'restrictToTypes' => $t['restrictToTypes'], 
								'includeSubtypes' => $t['includeSubtypes'],
								'checkAccess' => $check_access,
								'filterDeaccessionedRecords' => $filterDeaccessioned
							]))) {
								throw new \ServiceException(_t('No results for table: %1', $table));
							}
					
							$rec = \Datamodel::getInstance($table, true);
						
							$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $t, []);

							$results[] = ['name' => $name, 'result' => $r = \GraphQLServices\Helpers\fetchDataForBundles($qr, $bundles, [
								'checkAccess' => $check_access, 
								'start' => $t['start'], 
								'limit' => $t['limit'], 
								'filterByAncestors' => $t['filterByAncestors'], 
								'filterNonPrimaryRepresentations' => $t['filterNonPrimaryRepresentations']
							]), 'count' => sizeof($r)];
							if(is_null($ftable)) {
								// Stash details of first find for use in "flat" response
								$ftable = $table;
								$fsearch = $search;
								$fcount = sizeof($r);
								$fresult = $r;
							}
						}
						return ['table' => $ftable, 'search' => $fsearch, 'count' => $fcount, 'results' => $results, 'result' => $fresult];
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
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Type restrictions')
						],
						[
							'name' => 'includeSubtypes',
							'type' => Type::boolean(),
							'description' => _t('Expand type restriction to include subtypes?'),
							'defaultValue' => true
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
						
						$valid_tables = caFilterTableList(['ca_objects', 'ca_collections', 'ca_entities', 'ca_occurrences', 'ca_places', 'ca_list_items', 'ca_storage_locations', 'ca_loans', 'ca_object_lots', 'ca_movements', 'ca_object_representations']);
						
						if($args['restrictToTypes'] && !is_array($args['restrictToTypes'])) {
							$args['restrictToTypes'] = [$args['restrictToTypes']];
						}
						
						if(!in_array($table, $valid_tables, true)) { 
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
						switch($b = $bundle_bits[0]) {
							case 'idno':
								$ids = $table::getIDsForIdnos($values, [
									'restrictToTypes' => $args['restrictToTypes'] ?? null, 
									'includeSubtypes' => $args['includeSubtypes'] ?? true,
									'returnAll' => true
								]);
								if(!is_array($ids)) { break; }
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k) {return ['id' => $v[0], 'ids' => $v, 'idno' => $k, 'idnos' => [$k], 'value' => $k]; }, $ids, array_keys($ids));
								break;
							case 'preferred_labels':
							case 'nonpreferred_labels':
								$ids = $table::getIDsForlabels($values, ['mode' => ($b === 'preferred_labels') ? __CA_LABEL_TYPE_PREFERRED__ : __CA_LABEL_TYPE_NONPREFERRED__, 'returnIdnos' => true, 'restrictToTypes' => $args['restrictToTypes'], 'returnAll' => true, 'field' => $bundle_bits[1] ?? null]);
								
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k) use ($idnos) {return ['id' => $v[0]['id'], 'ids' => array_map(function($x) { return $x['id']; }, $v), 'idno' => $v[0]['idno'], 'idnos' => array_map(function($x) { return $x['idno']; }, $v), 'value' => $k]; }, $ids, array_keys($ids));
							
								break;
							default:
								$b = array_pop($bundle_bits);
								if(!$t->hasElement($b)) { 
									throw new \ServiceException(_t('Metadata element %1 does not exist', $b));
								}
								if ($u->getBundleAccessLevel($table, $b) < __CA_BUNDLE_ACCESS_READONLY__) {
									throw new \ServiceException(_t('Access denied to %1', $b));
								}
								$ids = $table::getIDsForAttributeValues($b, $values, ['returnIdnos' => true, 'restrictToTypes' => $args['restrictToTypes'], 'returnAll' => true]);
								
								foreach($values as $v) {
									if(!array_key_exists($v, $ids)) { $ids[$v] = null; }
								}
								$value_map = array_map(function($v, $k)  { return ['id' => $v[0]['id'], 'ids' => array_map(function($x) { return $x['id']; }, $v), 'idno' => $v[0]['idno'], 'idnos' => array_map(function($x) { return $x['idno']; }, $v), 'value' => $k]; }, $ids, array_keys($ids));

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
