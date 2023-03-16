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
							'type' => Type::listOf(ItemSchema::get('TargetListItem')),
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
							if(!\Datamodel::tableExists($t['table'])) { 
								throw new \ServiceException(_t('Invalid table'));
							}
							if(!($linking_table = \Datamodel::getLinkingTableName($table, $t['table']))) {
								throw new \ServiceException(_t('Cannot resolve relationship'));
							}
							
							$target_name = $t['name'] ?? $t['table'];
							
							$include_media = $t['includeMedia'] ?? false;
							$media_versions = $t['mediaVersions'] ?? ["thumbnail", "small", "medium", "large", "original"];
						
							$target_pk = \Datamodel::primaryKey($t['table']);
							$rels = $rec->getRelatedItems($t['table'], ['checkAccess' => $check_access, 'primaryIDs' => [$rec->getPrimaryKey()], 'restrictToTypes' => $t['restrictToTypes'], 'restrictToRelationshipTypes' => $t['restrictToRelationshipTypes']]);
					
							$rel_list = [];
							if (sizeof($rel_ids = array_map(function($v) use ($resolve_to_related, $target_pk, $t) { return $v[$resolve_to_related ? $target_pk : 'relation_id']; }, $rels)) > 0) {
								$rel_types = array_values(array_map(function($v) use ($t) {
									return [
										'relationship_typename' => $v['relationship_typename'],
										'relationship_typecode' => $v['relationship_type_code'],
										'relationship_type_id' => $v['relationship_type_id'],
									];
								}, $rels));
								
								$start = $t['start'] ?? 0;
								if($start < 0) { $start = 0; }
								if($start >= sizeof($rel_ids)) { $start = 0; }
								$limit = $t['limit'] ?? null;
								if($limit < 1) { $limit = null; }
								if(($start > 0) || ($limit > 0)) {
									$rel_ids = array_slice($rel_ids, $start, $limit);
									$rel_types = array_slice($rel_types, $start, $limit);
								}
								
								$qr = caMakeSearchResult($resolve_to_related ? $t['table'] : $linking_table, $rel_ids);
								while($qr->nextHit()) {
									$r = $qr->getInstance();
							
									$rel_type = array_shift($rel_types);
									$bundles = \GraphQLServices\Helpers\extractBundleNames($r, $t);
									$data = \GraphQLServices\Helpers\fetchDataForBundles($r, $bundles, ['primaryIDs' => [$rec->getPrimaryKey()]]);
							
									$media = [];
									if($include_media) {
										$m = $resolve_to_related ? $r : \Datamodel::getInstance($t['table'], false, $r->get($t['table'].'.'.$target_pk, ['primaryIDs' => [$rec->getPrimaryKey()]]));
										
										if(is_array($reps = $m->getRepresentations(array_merge($media_versions, ['original']), null, ['restrictToTypes' => $t['restrictMediaToTypes']]))) {
											foreach($reps as $rep_id => $rep_info) {
												$versions = [];
												foreach($rep_info['urls'] as $version => $url) {
													if(!in_array($version, $media_versions)) { continue; }
													$versions[] = [
														'version' => $version,
														'url' => $url,
														'tag' => $rep_info['urls'][$version],
														'width' => $rep_info['info'][$version]['WIDTH'],
														'height' => $rep_info['info'][$version]['HEIGHT'],
														'mimetype' => $rep_info['info'][$version]['MIMETYPE'],
														'filesize' => @filesize($rep_info['paths'][$version]),
														'duration' => $rep_info['info'][$version]['PROPERTIES']['duration'] ?? null,
													];
												}
												
												$media[] = [
													'id' => $rep_id,
													'idno' => $rep_info['idno'],
													'name' => $rep_info['label'],
													'type' => $rep_info['typename'],
													'mimetype' => $rep_info['mimetype'],
													'originalFilename' => $rep_info['original_filename'],
													'versions' => $versions,
													'isPrimary' => (bool)$rep_info['is_primary'],
													'width' => $rep_info['info']['original']['WIDTH'],
													'height' => $rep_info['info']['original']['HEIGHT'],
													'mimetype' => $rep_info['info']['original']['MIMETYPE'],
													'filesize' => @filesize($rep_info['paths']['original']),
													'duration' => $rep_info['info']['original']['PROPERTIES']['duration'] ?? null
												];
											}
										}
									}
								
									if(is_array($rel_ids = $r->get("{$linking_table}.relation_id", ['returnAsArray' => true]))) {
										foreach($rel_ids as $rel_id) {
											$rel_list[] = array_merge([
												'id' => $rel_id,
												'table' => $linking_table,
												'bundles' => $data,
												'media' => $media
											], $rel_type);
										}
									}
								}
							}
							$rels_by_target[] = [
								'name' => $target_name,
								'table' => $t['table'],
								'relationships' => $rel_list
							];
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
