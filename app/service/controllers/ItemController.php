<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/ItemController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2023 Whirl-i-Gig
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
						
						list($identifier, $opts) = \GraphQLServices\Helpers\resolveParams($args);
						$rec = self::resolveIdentifier($table = $args['table'], $identifier, null, $opts);
						$rec_pk = $rec->primaryKey();
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args);
						$data = \GraphQLServices\Helpers\fetchDataForBundles($rec, $bundles, []);
						
						return ['table' => $rec->tableName(), 'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 'identifier' => $args['identifier'], 'id' => $rec->getPrimaryKey(), 'bundles' => $data];
					}
				],
				// ------------------------------------------------------------
				'getItems' => [
					'type' => ItemSchema::get('Item'),
					'description' => _t('Get data for several items'),
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
							'name' => 'ids',
							'type' => Type::listOf(Type::int()),
							'description' => _t('List of numeric database ids.')
						],
						[
							'name' => 'idnos',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of alphanumeric idno values.')
						],
						[
							'name' => 'identifiers',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of record identifier (either integer primary key or alphanumeric idno values).')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Bundles to return.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($identifier, $opts) = \GraphQLServices\Helpers\resolveParams($args);
						$rec = self::resolveIdentifier($table = $args['table'], $identifier, null, $opts);
						$rec_pk = $rec->primaryKey();
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args);
						$data = \GraphQLServices\Helpers\fetchDataForBundles($rec, $bundles, []);
						
						return ['table' => $rec->tableName(), 'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 'identifier' => $args['identifier'], 'id' => $rec->getPrimaryKey(), 'bundles' => $data];
					}
				],
				// ------------------------------------------------------------
				'getRelationships' => [
					'type' => ItemSchema::get('RelationshipList'),
					'description' => _t('Get relationships for an item'),
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
				],
				// ------------------------------------------------------------
				'getRelationshipsForItems' => [
					'type' => ItemSchema::get('RelationshipList'),
					'description' => _t('Get relationships for several item'),
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
							'name' => 'ids',
							'type' => Type::listOf(Type::int()),
							'description' => _t('List of numeric database ids.')
						],
						[
							'name' => 'idnos',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of alphanumeric idno values.')
						],
						[
							'name' => 'identifiers',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of record identifier (either integer primary key or alphanumeric idno values).')
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
				],
				// ------------------------------------------------------------
				'getMedia' => [
					'type' => ItemSchema::get('MediaList'),
					'description' => _t('Get list of media for an item'),
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
							'description' => _t('Media source (ca_object_representations or bundle in current record')
						],
						[
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned media to specified types.')
						],
						[
							'name' => 'restrictToRelationshipTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned media to specified relationship types.')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Zero-based start index for returned media. If omitted starts from the first media.')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of meedia to return. If omitted all media are returned.')
						],
						[
							'name' => 'checkAccess',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Filter media by access values')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media bundles to return (target=ca_object_representations only).')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => 'Media version to return'
						],
						[
							'name' => 'primaryOnly',
							'type' => Type::listOf(Type::string()),
							'description' => 'Return only primary medis'
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($identifier, $opts) = \GraphQLServices\Helpers\resolveParams($args);
						$rec = self::resolveIdentifier($table = $args['table'], $identifier, null, $opts);
						$rec_pk = $rec->primaryKey();
						
						$target = caGetOption('target', $opts, 'ca_object_representations');
						
						$start = caGetOption('start', $args, 0);
						$limit = caGetOption('limit', $args, null);
						
						$media_list = [];
						if($target === 'ca_object_representations') {
							$reps = $rec->getRelatedItems('ca_object_representations', [
								'returnAs' => 'array', 
								'filterNonPrimaryRepresentations' => false,
								'checkAccess' => caGetOption('checkAccess', $args, null), 
								'restrictToTypes' => caGetOption('restrictToTypes', $args, null), 
								'restrictToRelationshipTypes' => caGetOption('restrictToRelationshipTypes', $args, null)
							]);
							if(is_array($reps)) {
								if($start || $limit) {
									$reps = array_slice($reps, $start, $limit);
								}
								
								$qr_reps = caMakeSearchResult('ca_object_representations', array_map(function($v) {
									return $v['representation_id'];
								}, $reps));
								
								while($qr_reps->nextHit()) {
									$rinfo = array_shift($reps);
									$versions = [];
									$media_versions = caGetOption('mediaVersions', $args, $qr_reps->getMediaVersions('media'));
									foreach($media_versions as $media_version) {
										$media_info = $qr_reps->getMediaInfo('media', $media_version);
										$version = [
											'version' => $media_version,
											'url' => $qr_reps->getMediaUrl('media', $media_version),
											'tag' => $qr_reps->getMediaTag('media', $media_version),
											'mimetype' => $media_info['MIMETYPE'],
											'mediaclass' => caGetMediaClass($media_info['MIMETYPE']),
											'width' => $media_info['WIDTH'],
											'height' => $media_info['HEIGHT'],
											'duration' => $media_info['PROPERTIES']['duration'] ?? null,
											'filesize' => $media_info['PROPERTIES']['filesize'] ?? null,
											'md5' => $media_info['MD5']
										];
										
										$versions[] = $version;
									}
									
									$bundle_data = null;
									if(is_array($bundles = \GraphQLServices\Helpers\extractBundleNames($t_rep = $qr_reps->getInstance(), $args))) {
										$bundle_data = \GraphQLServices\Helpers\fetchDataForBundles($t_rep, $bundles, []);
									}
									$media_list[] = [
										'id' => $qr_reps->getPrimaryKey(),
										'idno' => $qr_reps->get('ca_object_representations.idno'),
										'type' => $qr_reps->get('ca_object_representations.type_id'),
										'name' => $qr_reps->get('ca_object_representations.preferred_labels.name'),
										'mimetype' => $qr_reps->get('ca_object_representations.mimetype'),
										'mediaclass' => $qr_reps->get('ca_object_representations.mediaclass'),
										'originalFilename' => $qr_reps->get('ca_object_representations.original_filename'),
										'md5' => $qr_reps->get('ca_object_representations.md5'),
										'versions' => $versions,
										'bundles' => $bundle_data,
										'isPrimary' => $rinfo['is_primary'] ?? false,
										'relationship_typename' => $rinfo['relationship_typename'] ?? null,
										'relationship_typecode' => $rinfo['relationship_type_code'] ?? null
									];
								}
							}
						} elseif($t_rec->elementExists($target)) {
						
						} else {
							throw new \ServiceException(_t('Invalid target specified'));
						}
						
						$bundles = \GraphQLServices\Helpers\extractBundleNames($rec, $args);
						$data = \GraphQLServices\Helpers\fetchDataForBundles($rec, $bundles, []);
						
						return ['table' => $rec->tableName(), 'idno'=> $rec->get($rec->getProperty('ID_NUMBERING_ID_FIELD')), 'id' => $rec->getPrimaryKey(), 'media' => $media_list];
					}
				],
				// ------------------------------------------------------------
				'getMediaForItems' => [
					'type' => ItemSchema::get('MediaList'),
					'description' => _t('Get lists of media for several items'),
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
							'name' => 'ids',
							'type' => Type::listOf(Type::int()),
							'description' => _t('List of numeric database ids.')
						],
						[
							'name' => 'idnos',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of alphanumeric idno values.')
						],
						[
							'name' => 'identifiers',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of record identifier (either integer primary key or alphanumeric idno values).')
						],
						[
							'name' => 'target',
							'type' => Type::string(),
							'description' => _t('Media source (ca_object_representations or bundle in current record')
						],
						[
							'name' => 'restrictToTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned media to specified types.')
						],
						[
							'name' => 'restrictToRelationshipTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Restrict returned media to specified relationship types.')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Zero-based start index for returned media. If omitted starts from the first media.')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of meedia to return. If omitted all media are returned.')
						],
						[
							'name' => 'checkAccess',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Filter media by access values')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media bundles to return (target=ca_object_representations only).')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => 'Media version to return'
						],
						[
							'name' => 'primaryOnly',
							'type' => Type::listOf(Type::string()),
							'description' => 'Return only primary medis'
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
					}
				],
				// ------------------------------------------------------------
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
