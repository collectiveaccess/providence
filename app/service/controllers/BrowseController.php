<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/BrowseController.php :
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
require_once(__CA_APP_DIR__.'/service/schemas/BrowseSchema.php');

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
						]
					],
					'resolve' => function ($rootValue, $args) {
						list($browse_info, $browse) = self::browseParams($args);
						$facet = $args['facet'];
						
						$facet_values = $browse->getFacet($facet);
						
						$ret = array_map(function($v) {
							return [
								'id' => $v['id'],
								'value' => $v['label'],
								'sortableValue' => $v['label_sort_'],
								'contentCount' => $v['content_count'],
								'childCount' => $v['child_count'],
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
						list($browse_info, $browse) = self::browseParams($args);
						$facets = $browse->getInfoForAvailableFacets();
						
						$ret = array_map(function($f, $n) use ($browse) { 
							$facet_values = $browse->getFacet($n);
						
							$ret = array_map(function($v) {
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
								'values' => $ret
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
						]
					],
					'resolve' => function ($rootValue, $args) {
						list($browse_info, $browse) = self::browseParams($args);
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'filters' => [
					'type' => BrowseSchema::get('BrowseFilterList'),
					'description' => _t('List of applied filters'),
					'args' => [
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
						list($browse_info, $browse) = self::browseParams($args);
						
						return [
							'key' => $args['key'],
							'filters' => self::getFiltersForResponse($browse, $browse_info)
						];
					}
				],
			],
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				'addFilterValue' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Add filter value to browse.'),
					'args' => [
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
						]
					],
					'resolve' => function ($rootValue, $args) {
						list($browse_info, $browse) = self::browseParams($args);
					
						$facet = $args['facet'];
						$value = $args['value'] ?? null;
						$values = $args['values'] ?? null;
						
						$browse->addCriteria($facet, is_array($values) ? $values : [$value]);
						$browse->execute();	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'removeFilterValue' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Remove filter value from browse. If value is omitted all values for the specified facet are removed.'),
					'args' => [
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
						]
					],
					'resolve' => function ($rootValue, $args) {
						list($browse_info, $browse) = self::browseParams($args);
					
						$facet = $args['facet'];
						if(!is_array($values = $args['values']) || !sizeof($values)) {
							if($value = $args['value']) { $values = [$value]; }
						}
						if(!is_array($values)) {
							throw new \ServiceException(_t('No values passed'));
						}
						$browse->removeCriteria($facet, $values);
						$browse->execute();	
						
						return self::getMutationResponse($browse, $browse_info, $args);
					}
				],
				'removeAllFilterValues' => [
					'type' => BrowseSchema::get('BrowseResult'),
					'description' => _t('Remove all filters from browse, resetting to start state.'),
					'args' => [
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
						]
					],
					'resolve' => function ($rootValue, $args) {
						list($browse_info, $browse) = self::browseParams($args);
						
						$browse->removeAllCriteria();
						$browse->execute();	
						
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
	 *		dontExecute = Call execute() on browse instance before returning. [Default is true]
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
		if (caGetOption('execute', $options, true)) { $browse->execute(); }
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
						
		$i = 0;
		
		$media_versions = [];
		
		// TODO: make caGetDisplayImagesForAuthorityItems() more efficient
		$qr->seek($start);
		$m = caGetDisplayImagesForAuthorityItems($table, $qr->getAllFieldValues($qr->primaryKey()), ['return' => 'data', 'versions' => ['small', 'medium', 'large'], 'useRelatedObjectRepresentations' => ($table !== 'ca_objects')]);
		
		$m = array_map(function($versions) {
		    $acc = [];
			foreach ($versions as $v => $info) {
				$acc[] = [
					'version' => $v,
					'url' => $info['url'],
					'tag' => $info['tag'],
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
			$ret[] = [
				'id' => $id,
				'title' => $qr->get("{$table}.preferred_labels"),
				'detailUrl' => caDetailUrl($table, $id),
				'identifier' => $qr->get("{$table}.idno"),
				'rank' => $i,
				'media' => $m[$id],
				'data' => []
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
		if(!($qr = $browse->getResults(['sort' => $sort, 'sort_direction' => $sort_direction]))) { 
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
			'facets' => [],
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
