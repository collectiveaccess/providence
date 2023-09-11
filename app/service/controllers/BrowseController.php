<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/BrowseController.php :
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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/BrowseSchema.php');
require_once(__CA_APP_DIR__.'/helpers/browseHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/themeHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\BrowseSchema;


class BrowseController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	private static $browse_conf;
	
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
		
		if(!self::$browse_conf) { 
            self::$browse_conf = caGetBrowseConfig();
        }
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				'facet' => [
					'type' => BrowseSchema::get('BrowseFacet'),
					'description' => _t('Information about specific facet'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'facet',
							'type' => Type::string(),
							'description' => _t('Name of facet')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return facet values starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of facet values to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
						$facet = $args['facet'];
						
						$user_access_values = caGetUserAccessValues();
						$facet_values = $browse->getFacet($facet, ['start' => $args['start'] ?? null, 'limit' => $args['limit'] ?? null, 'checkAccess' => $user_access_values]);
						
						if(!is_array($facet_values)) {
							throw new \ServiceException(_t('Facets %1 is not defined for table %2', $facet, $browse_info['table']));
						}
						
						$facet_info = $browse->getInfoForFacet($facet, ['checkAccess' => $user_access_values]);
						$data_spec = caGetOption('data', $facet_info, null);
						$facet_table = caGetOption('table', $facet_info, null);
						$instance = Datamodel::getInstance($facet_table, true);
						$ret = array_map(function($v) use ($data_spec, $instance, $user_access_values) {
							$display_data = [];
							if(is_array($data_spec) && sizeof($data_spec) && $instance && $instance->load($v['id'])) {
								foreach($data_spec as $n => $t) {
									$display_data[] = [
										'name' => $n,
										'value' => $instance->getWithTemplate($t, ['checkAccess' => $user_access_values])
									];
								}
							}
							return [
								'id' => $v['id'],
								'value' => $v['label'],
								'sortableValue' => $v['label_sort_'],
								'contentCount' => $v['content_count'],
								'childCount' => $v['child_count'],
								'displayData' => $display_data
							];
						}, $facet_values);
						
						if(!($facet_info = self::facetInfo($browse_info['table'], $facet))) {
							throw new \ServiceException(_t('No facets defined for table '.$browse_info['table']));
						}
						return [
							'name' => $facet,
							'type' => caGetOption('type', $facet_info, null),
							'description' => caGetOption('description', $facet_info, null),
							'values' => $ret
						];
					}
				],
				'facets' => [
					'type' => BrowseSchema::get('BrowseFacetList'),
					'description' => _t('List of available facets'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return facet values starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of facet values to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
						$user_access_values = caGetUserAccessValues();
						$facets = $browse->getInfoForAvailableFacets(['checkAccess' => $user_access_values]);
						
						$ret = array_map(function($f, $n) use ($browse, $args) { 
							$facet_values = $browse->getFacet($n, ['start' => $args['start'] ?? null, 'limit' => $args['limit'] ?? null, 'checkAccess' => $user_access_values]);
						
							$vret = array_map(function($v) {
								return [
									'id' => $v['id'],
									'value' => $v['label'],
									'sortableValue' => $v['label_sort_'],
									'contentCount' => $v['content_count'],
									'childCount' => $v['child_count'],
								];
							}, $facet_values);
							return [
								'name' => $n,
								'labelSingular' => caGetOption('label_singular', $f, $n),
								'labelPlural' => caGetOption('label_plural', $f, $n),
								'description' => caGetOption('description', $f, null),
								'type' => caGetOption('type', $f, null),
								'values' => $vret
							];
						}, $facets, array_keys($facets));
						return [
							'key' => $args['key'],
							'facets' => $ret
						];
					}
				],
				'result' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Return browse result for key'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'sort',
							'type' => Type::string(),
							'description' => _t('Sort fields')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return records starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media version to return')
						]
						
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
						
						if($browse->numCriteria() == 0) {
							$browse->addCriteria("_search", ["*"]);
							$browse->execute(['checkAccess' => caGetUserAccessValues()]);	
						}
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'filters' => [
					'type' => BrowseSchema::get('BrowseFilterList'),
					'description' => _t('List of applied filters'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
						
						return [
							'key' => $args['key'],
							'filters' => self::getFiltersForResponse($browse, $browse_info)
						];
					}
				],
				
				// -----------------
				'activity' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Return browse result with recent activity'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'defaultValue' => 0,
							'description' => _t('Return records starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'defaultValue' => 10,
							'description' => _t('Maximum number of records to return')
						],
						[
							'name' => 'facet',
							'type' => Type::string(),
							'description' => _t('Facet name')
						],
						[
							'name' => 'value',
							'type' => Type::string(),
							'description' => _t('Filter value')
						],
						[
							'name' => 'values',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Filter values')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media version to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
						
						$t = Datamodel::getInstance($browse_info['table'], true);
						$args['sort'] = $t->primaryKey(true).':desc'; // always sort descending
						
						
						if(isset($args['facet'])) {						
							$facet = $args['facet'];
							$value = $args['value'] ?? null;
							$values = $args['values'] ?? null;
							$browse->addCriteria($facet, is_array($values) ? $values : [$value]);
						} elseif(isset($args['baseCriteria'])) {
							foreach($args['baseCriteria'] as $facet => $values) {
								$browse->addCriteria($facet, is_array($values) ? $values : [$value]);
							}
						} else {
							throw new \ServiceException(_t('No criteria specified'));
						}
						
						$browse->execute(['checkAccess' => caGetUserAccessValues()]);	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				// -----------------
				'activityFacet' => [
					'type' => BrowseSchema::get('BrowseFacet'),
					'description' => _t('Information about specific facet'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'facet',
							'type' => Type::string(),
							'description' => _t('Name of facet')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return facet values starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of facet values to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args, ['baseCriteria' => ['modified:"after '.date('Y-m-d').'"']]);
						$facet = $args['facet'];
						
						
						
						$user_access_values = caGetUserAccessValues();
						$facet_values = $browse->getFacet($facet, ['start' => $args['start'] ?? null, 'limit' => $args['limit'] ?? null, 'checkAccess' => $user_access_values]);
						
						if(!is_array($facet_values)) {
							throw new \ServiceException(_t('Facets %1 is not defined for table %2', $facet, $browse_info['table']));
						}
						
						$facet_info = $browse->getInfoForFacet($facet, ['checkAccess' => $user_access_values]);
						$data_spec = caGetOption('data', $facet_info, null);
						$facet_table = caGetOption('table', $facet_info, null);
						$instance = Datamodel::getInstance($facet_table, true);
						$ret = array_map(function($v) use ($data_spec, $instance, $user_access_values) {
							$display_data = [];
							if(is_array($data_spec) && sizeof($data_spec) && $instance && $instance->load($v['id'])) {
								foreach($data_spec as $n => $t) {
									$display_data[] = [
										'name' => $n,
										'value' => $instance->getWithTemplate($t, ['checkAccess' => $user_access_values])
									];
								}
							}
							return [
								'id' => $v['id'],
								'value' => $v['label'],
								'sortableValue' => $v['label_sort_'],
								'contentCount' => $v['content_count'],
								'childCount' => $v['child_count'],
								'displayData' => $display_data
							];
						}, $facet_values);
						
						if(!($facet_info = self::facetInfo($browse_info['table'], $facet))) {
							throw new \ServiceException(_t('No facets defined for table '.$browse_info['table']));
						}
						return [
							'name' => $facet,
							'type' => caGetOption('type', $facet_info, null),
							'description' => caGetOption('description', $facet_info, null),
							'values' => $ret
						];
					}
				],
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				'addFilterValue' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Add filter value to browse.'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'facet',
							'type' => Type::string(),
							'description' => _t('Facet name')
						],
						[
							'name' => 'value',
							'type' => Type::string(),
							'description' => _t('Filter value')
						],
						[
							'name' => 'values',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Filter values')
						],
						[
							'name' => 'sort',
							'type' => Type::string(),
							'description' => _t('Sort fields')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return records starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media version to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
					
						$facet = $args['facet'];
						$value = $args['value'] ?? null;
						$values = $args['values'] ?? null;
						
						$browse->addCriteria($facet, is_array($values) ? $values : [$value]);
						
						$user_access_values = caGetUserAccessValues();
						$browse->execute(['checkAccess' => $user_access_values]);	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'removeFilterValue' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Remove filter value from browse. If value is omitted all values for the specified facet are removed.'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'facet',
							'type' => Type::string(),
							'description' => _t('Facet name')
						],
						[
							'name' => 'values',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Filter value')
						],
						[
							'name' => 'value',
							'type' => Type::string(),
							'description' => _t('Filter value')
						],
						[
							'name' => 'sort',
							'type' => Type::string(),
							'description' => _t('Sort fields')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return records starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media version to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						list($browse_info, $browse) = self::browseParams($args);
					
						$facet = $args['facet'];
						if(!is_array($values = $args['values']) || !sizeof($values)) {
							if($value = $args['value']) { $values = [$value]; }
						}
						if(!is_array($values)) {
							throw new \ServiceException(_t('No values passed'));
						}
						$browse->removeCriteria($facet, $values);
						
						$user_access_values = caGetUserAccessValues();
						$browse->execute(['checkAccess' => $user_access_values]);	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'removeAllFilterValues' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Remove all filters from browse, resetting to start state.'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'browseType',
							'type' => Type::string(),
							'description' => _t('Browse type')
						],
						[
							'name' => 'key',
							'type' => Type::string(),
							'description' => _t('Browse key')
						],
						[
							'name' => 'sort',
							'type' => Type::string(),
							'description' => _t('Sort fields')
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Return records starting at index')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of records to return')
						],
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Media version to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$args['key'] = '';
						list($browse_info, $browse) = self::browseParams($args);
						
						//$browse->removeAllCriteria();
						
						$user_access_values = caGetUserAccessValues();
						$browse->execute(['checkAccess' => $user_access_values]);	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				]
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
	/**
	 * Fetch browse information and browse engine instance based upon browseType and key args
	 *
	 * @param array $args Request aruguments
	 * @param array $options Options include:
	 *		execute = Call execute() on browse instance before returning. [Default is true]
	 *		baseCriteria = 
	 */
	private static function browseParams(array $args, array $options=null) {
		$browse_type = trim($args['browseType']);
		if(!($browse_info = caGetInfoForBrowseType($browse_type))) { 
			throw new \ServiceException(_t('Invalid browse type '.$browse_type));
		}
		if(!($browse = caGetBrowseInstance($table = $browse_info['table']))) { 
			throw new \ServiceException(_t('Invalid browse table '.$table));
		}
		
		if ($key = trim($args['key'])) {
			$browse->reload($key);
		}
		$restrict_to_types = caGetOption('restrictToTypes', $browse_info, null);
		if (is_array($restrict_to_types) && sizeof($restrict_to_types)) { $browse->setTypeRestrictions($restrict_to_types); }
		
		if($criteria = caGetOption('baseCriteria', $browse_info, null)) {
			foreach($criteria as $k => $c) {
				$browse->addCriteria($k, is_array($c) ? $c : [$c]);
			}
		}	
		$user_access_values = caGetUserAccessValues();
		if (caGetOption('execute', $options, true)) { $browse->execute(['checkAccess' => $user_access_values]); }
		
		return [
			$browse_info, $browse
		];
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * @param string $table
	 * @para, string $facet
	 */
	private static function facetInfo(string $table, string $facet) {
		 $info = self::$browse_conf->getAssoc($table);
		 if(!is_array($info)) { return null; }
		 
		 $facets = caGetOption('facets', $info, null);
		 if(!is_array($facets)) { return null; }
		 
		 return isset($facets[$facet]) ? $facets[$facet] : null;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getResultsForResponse(BrowseEngine $browse, SearchResult $qr, array $browse_info, array $args) {
		$ret = [];
		
		$table = $browse_info['table'];
		
		$start = caGetOption('start', $args, 0);
		$limit = caGetOption('limit', $args, null);
		$media_versions = caGetOption('mediaVersions', $args, null);
		if(is_array($media_versions) && !sizeof($media_versions)) { $media_versions = null; }
		
		$i = 0;
		
		$user_access_values = caGetUserAccessValues();
		
		// TODO: make caGetDisplayImagesForAuthorityItems() more efficient
		$qr->seek($start);
		$m = caGetDisplayImagesForAuthorityItems($table, $qr->getAllFieldValues($qr->primaryKey()), ['return' => 'data', 'versions' => ['small', 'medium', 'large', 'iiif', 'original', 'h264_hi', 'mp3', 'compressed'], 'useRelatedObjectRepresentations' => ($table !== 'ca_objects')]);
		$m = array_map(function($versions) use ($media_versions) {
		    $acc = [];
			foreach ($versions as $v => $info) {
				if(is_array($media_versions) && !in_array($v, $media_versions, true)) { continue; }
				if(!strlen($info['url'])) { continue; }
				$acc[$v] = [
					'version' => $v,
					'url' => $info['url'],
					'width' => $info['width'],
					'height' => $info['height'],
					'mimetype' => $info['mimetype'],
				];
			}
			return $acc;
		}, $m);
		$qr->seek($start);
		while($qr->nextHit()) {
			$id = $qr->getPrimaryKey();
			
			$data = [];
			
			// TODO: only execute this if 'data' is in the query
			if(is_array($browse_info['additionalData'])) {
				foreach($browse_info['additionalData'] as $k => $f) {
					if (strpos($f, '^') !== false) {
						$v = $qr->getWithTemplate($f, ['checkAccess' => $user_access_values]);
					} else {
						$t = caParseTagOptions($f);
						$v = $qr->get($t['tag'], array_merge($t['options'], ['checkAccess' => $user_access_values]));
					}
					$data[] = ['name' => $k, 'value' => $v];
				}
			}
			
			
			$viewer_class = caGetMediaClass($m[$id]['original']['mimetype']);
			
			switch($viewer_class) {
				case 'image':
					$viewer_url = $m[$id]['iiif']['url'];
					break;
				case 'video':
					$viewer_url = isset($m[$id]['h264_hi']) ? $m[$id]['h264_hi']['url'] : $m[$id]['original']['url'];
					break;
				case 'audio':
					$viewer_url = isset($m[$id]['mp3']) ? $m[$id]['mp3']['url'] : $m[$id]['original']['url'];
					break;
				case 'document':
					$viewer_url = isset($m[$id]['compressed']) ? $m[$id]['compressed']['url'] : $m[$id]['original']['url'];
					break;
				default:
					$viewer_url = $m[$id]['original']['url'];
					break;
			}
			unset($m[$id]['iiif']);
			$ret[] = [
				'id' => $id,
				'title' => $qr->get("{$table}.preferred_labels", ['checkAccess' => $user_access_values]),
				'viewerUrl' => $viewer_url,
				'viewerClass' => $viewer_class,
				'identifier' => $qr->get("{$table}.idno", ['checkAccess' => $user_access_values]),
				'rank' => $i,
				'media' => is_array($m[$id]) ? array_values($m[$id]) : null,
				'data' => $data
			];
			$i++;
			if(($limit > 0) && ($i >= $limit)) { break; }
		}
		
		return $ret;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getMutationResponse(BrowseEngine $browse, array $browse_info, array $args) {
		list($sort, $sort_direction) = self::processSortSpec($args['sort']);
		
		$user_access_values = caGetUserAccessValues();
		if(!($qr = $browse->getResults(['sort' => $sort, 'sort_direction' => $sort_direction, 'checkAccess' => $user_access_values]))) { 
			throw new \ServiceException(_t('Browse execution failed'));
		}
		
		// Get available sorts
		$sorts = caGetOption('sortBy', $browse_info, [], ['castTo' => 'array']);
		$sort_directions = caGetOption('sortDirection', $browse_info, [], ['castTo' => 'array']);
		
		$available_sorts = [];
		foreach($sorts as $n => $f) {
			if($d = caGetOption($f, $sort_directions, null)) { $d = ":{$d}"; }
			$available_sorts[] = ['name' => $n, 'value' => "{$f}{$d}"];
		}
		
		$n = $qr->numHits();
		return [
			'key' => $browse->getBrowseID(),
			'created' => date('c'),
			'content_type' => $browse_info['table'],
			'content_type_display' => mb_strtolower($browse_info[($n == 1) ? 'labelSingular' : 'labelPlural']),
			'item_count' => $n,
			'items' => self::getResultsForResponse($browse, $qr, $browse_info, $args),
			'facets' => [],	// TODO: return facet list here?
			'filters' => self::getFiltersForResponse($browse, $browse_info),
			'available_sorts' => $available_sorts
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function getFiltersForResponse(BrowseEngine $browse, array $browse_info) {
		$filters = $browse->getCriteriaWithLabels();
					
		if(!is_array($filters)) { 
			throw new \ServiceException(_t('No filters returned'));
		}
		$filters = array_map(function($i, $f) {
			return [
				'facet' => $f,
				'values' => array_map(function($l, $id) {
						return [
							'id' => $id,
							'value' => $l
						];
					}, $i, array_keys($i))
			];
		}, $filters, array_keys($filters));
		
		return $filters;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function processSortSpec($sort_spec) {
		if($sort_spec) {
			$tmp = explode(';', $sort_spec);
			$sort = join(';', array_map(function($v) { 
				$t = explode(':', $v);
				return $t[0];
			}, $tmp));
			$sort_direction = join(';', array_map(function($v) { 
				$t = explode(':', $v);
				$d = (sizeof($t) > 1) ? strtoupper($t[1]) : 'ASC';
				if(!in_array($d, ['ASC' , 'DESC'], true)) { $d = 'ASC'; }
				return $d;
			}, $tmp));
			
			return [$sort, $sort_direction];
		}
		
		return [null, null];
	}
	# -------------------------------------------------------
}
