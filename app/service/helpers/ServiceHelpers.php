<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/ServiceHelpers.php :
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
namespace GraphQLServices\Helpers;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

define('__CA_ITEM_SERVICE_RELATIONSHIP_TARGET_MAXIMUM_DEPTH__', 3);

/**
 * Ensure all checkAccess values are valid
 */
function filterAccessValues(?array $values) : ?array {
	if(!$values) { return null; }
	return array_unique(array_map(function($v) { return (int)$v; }, $values));
}

/**
 * Extract list of bundles to return from request args.
 *
 */
function extractBundleNames($rec, array $args) : array {
	$table = $args['table'];
	$bundles = $args['bundles'];
	if(!isset($bundles) || !is_array($bundles) || !sizeof($bundles)) {
		$intrinsics = array_map(function($v) use ($table) {
			return "{$table}.{$v}";
		}, array_keys($rec->getValuesForExport(['includeLabels' => false, 'includeAttributes' => false, 'includeRelationships' => false])));
		
		$elements = array_map(function($v) use ($table) {
			return "{$table}.{$v}";
		}, $rec->getApplicableElementCodes());
		
		$bundles = array_merge(["{$table}.preferred_labels", "{$table}.nonpreferred_labels"], $intrinsics, $elements);
	}
	return is_array($bundles) ? $bundles : [];
}

/**
 * Fetch data from instance or SearchResult for bundles. 
 * When $sresult is a model instance return array with data from the instance. When $sresult is a SearchResult instance 
 * an array of arrays with data from each row in the result is returned.
 *
 * @param SearchResult|Model $sresult
 * @param array $bundles
 * @param array $options Options include:
 *		start = 
 *		limit = 
 *		filterByAncestors = 
 *
 * @return array
 */
function fetchDataForBundles($sresult, array $bundles, array $options=null) : array {
	$start = caGetOption('start', $options, 0, ['castTo' => 'int']);
	$limit = caGetOption('limit', $options, null, ['castTo' => 'int']);
	
	$ancestor_filters = caGetOption('filterByAncestors', $options, null);
	
	$check_access = caGetOption('checkAccess', $options, null);
	
	$config = \Configuration::load();
	$base_url = $config->get('site_host').$config->get('ca_url_root');
		
	$data = [];
	if(isset($bundles) && is_array($bundles) && sizeof($bundles)) {
		$table = $sresult->tableName();
		
		$is_model = false;
		if(is_a($sresult, '\BaseModel')) {	// convert model instance to search result
			$sresult = \caMakeSearchResult($table, [$sresult->getPrimaryKey()]);
			$is_model = true;
		}
		
		$rec = \Datamodel::getInstance($table, true);
		$result_count = $sresult->numHits();
		
		if(($start > 0) && ($start < $result_count)) {
			$sresult->seek($start);
		} elseif(($start !== 0) && (($start >= $result_count) || ($start < 0))) {
			// out of bounds; return empty set
			return [];
		}
		while($sresult->nextHit()) {
			// ladder up hierarchy looking for matches
			if(is_array($ancestor_filters) && (sizeof($ancestor_filters) > 0)) {
				$ancestor_filter_list = $ancestor_filters;
				
				$id = $sresult->getPrimaryKey();
				
				$ancestor_ids = $rec->getHierarchyAncestors($id, ['idsOnly' => true, 'checkAccess' => $check_access, 'primaryIDs' => caGetOption('primaryIDs', $options, null)]);
				if(is_array($ancestor_ids) && sizeof($ancestor_ids)) {
					$qr_ancestors = caMakeSearchResult($table, $ancestor_ids);
					while($qr_ancestors->nextHit()) {
						$af = array_shift($ancestor_filter_list);	
						if(is_array($af['criteria'])) {
							foreach($af['criteria'] as $c) {
								$cv = $qr_ancestors->get($c['name'], ['checkAccess' => $check_access, 'primaryIDs' => caGetOption('primaryIDs', $options, null)]);
								if(!compare($c['operator'], $cv, $c['value'])) { continue(3); }
							}
						}
						
						if(!sizeof($ancestor_filter_list)) { break; }
					}
				}
		
			}
		
			$row = [];
			foreach($bundles as $f) {
				$pt = caParseTagOptions($f);
				$f = $pt['tag'];
				
				$is_template = (strpos($f, '^') !== false);	 // is display template if it has at least one caret
				
				$p = \SearchResult::parseFieldPathComponents($table, $f);
				
				$values = [];
				
				$d = $sresult->get($f, array_merge(['checkAccess' => $check_access, 'returnWithStructure' => true, 'useLocaleCodes' => true, 'returnAllLocales' => true, 'convertCodesToIdno' => true, 'includeValueIDs' => true], ['primaryIDs' => caGetOption('primaryIDs', $options, null)], $pt['options']));

				if(!$is_template && (!is_array($d) || (sizeof($d) === 0))) { continue; }
		
				if($is_template) {
					$row[] = [
							'name' => $f, 
							'code' => $f,
							'locale' => null,
							'dataType' => "Text",
							'values' => [
								[
									'value' => $sresult->getWithTemplate($f, ['checkAccess' => $check_access, 'primaryIDs' => caGetOption('primaryIDs', $options, null)]),
									'locale' => null,
									'subvalues' => null,
									'id' => null,
									'value_id' => null
								]
							]
						];
				} elseif($is_intrinsic = $rec->hasField($p['field_name'])) {
					// Intrinics
					if(strlen($v = $sresult->get($f, array_merge(['convertCodesToIdno' => true, 'checkAccess' => $check_access], $pt['options'])))) {
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'locale' => null,
							'dataType' => $table::intrinsicTypeToString((int)$sresult->getFieldInfo($p['field_name'], 'FIELD_TYPE')),
							'values' => [
								[
									'value' => $v,
									'locale' => null,
									'subvalues' => null,
									'id' => null,
									'value_id' => null
								]
							]
						];
					}
				} elseif(!$p['related'] && ($is_label = (in_array($p['field_name'], ['preferred_labels', 'nonpreferred_labels'], true)))) {
					$label = $rec->getLabelTableInstance();
					if(!$label) { continue; }
					foreach($d as $index => $by_locale) {
						$sub_values = [];
			
						$is_set = false;
						$keys = array_keys($by_locale);
						if (is_numeric($keys[0])) { $by_locale = array_shift($by_locale); }
						foreach($by_locale as $locale => $by_id) {
							foreach($by_id as $id => $sub_field_values) {
								foreach($sub_field_values as $sub_field => $sub_field_value) {
									if(in_array($sub_field, [$sresult_pk, 'locale_id', 'is_preferred', 'item_type_id', 'source_info'])) { continue; }
									if(is_string($sub_field_value) && strlen($sub_field_value)) { $is_set = true; }
									if(is_array($sub_field_value)) { 
										
									}
		
									$sub_values[] = [
										'name' => $rec->getDisplayLabel("{$p['table_name']}.{$p['field_name']}.{$p['subfield_name']}"),
										'code' => $sub_field,
										'value' => $sub_field_value,
										'dataType' => $label->getFieldInfo($sub_field, 'LIST_CODE') ? 'String' : $label::intrinsicTypeToString($label->getFieldInfo($sub_field, 'FIELD_TYPE')),
										'id' => null
									];
								}
							}
							if($is_set) {
								$values[] = [
									'locale' => $locale,
									'value' => $sresult->get($f, array_merge(['convertCodesToIdno' => true, 'checkAccess' => $check_access], $pt['options'])),
									'subvalues' => $sub_values,
									'id' => $id		// label_id
								];
							}
						}
					}
					if(sizeof($values) > 0) {
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'dataType' => $p['subfield_name'] ? $table::intrinsicTypeToString((int)$label->getFieldInfo($p['subfield_name'], 'FIELD_TYPE')) : 'Container',
							'values' => $values
						];
					}
				} elseif($rel = \Datamodel::getInstance($f, true)) {	// straight table name
					
					// relationships
					$map = [
						'row_id' => ['name' => $rel->primaryKey(), 'datatype' => 'Numeric'],
						'relation_id' => ['name' => _t('Relationship id'), 'datatype' => 'Numeric'], 
						'idno' => ['name' => $rel->getDisplayLabel("{$f}.".$rel->getProperty('ID_NUMBERING_ID_FIELD')), 'datatype' => 'String'], 
						'displayname' => ['name' => _t('Name'), 'datatype' => 'String'], 
						'relationship_type_code' => ['name' => _t('Relationship type code'), 'datatype' => 'String'], 
						'relationship_typename' => ['name' => _t('Relationship type name'), 'datatype' => 'String'],
						'rank' => ['name' => _t('Relationship rank'), 'datatype' => 'Numeric'],
						'table' => ['name' => _t('Relationshop table'), 'datatype' => 'String']
					];
					$linking_table = \Datamodel::getLinkingTableName($table, $f);
					foreach($d as $index => $rel) {
						$rel['table'] = $linking_table;
						$sub_values = [];
						foreach($map as $n => $rinfo) {
							$sub_values[] = [
								'id' => $rel['relation_id'],
								'name' => $rinfo['name'],
								'code' => $n,
								'value' => $rel[$n],
								'dataType' => is_numeric($rel[$n]) ? 'Numeric' : 'String'
							];
						}
						
						// Relationship level
						$values[] = [
							'id' => $rel['relation_id'],	// relation_id
							'value_id' => null,
							'locale' => \ca_locales::getDefaultCataloguingLocale(),
							'value' => $rel['displayname'],
							'subvalues' => $sub_values
						];
						
					}
					
					if(sizeof($values) > 0) {
						// Relationship list level
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'dataType' => "Container",
							'values' => $values,
							'id' => $id
						];
					}
				
				} else {
					// Metadata elements
					foreach($d as $index => $by_locale) {
						if(!is_array($by_locale)) { 
							$by_locale = [
								\ca_locales::getDefaultCataloguingLocale() => $by_locale
							];
						}
						foreach($by_locale as $locale => $by_id) {
							$sub_values = [];
							if(!is_array($by_id)) { $by_id = [[$by_id]]; }
			
							$is_set = false;
							foreach($by_id as $id => $sub_field_values) {
								$v = $sf =  null;
								
								$sub_values = [];
								foreach($sub_field_values as $sub_field => $sub_field_value) {
									$is_value_id = preg_match("!^(.*)_value_id$!", $sub_field, $m);
									//if($is_value_id && isset($sub_field_values[$m[1]])) { continue; }
									if(strlen($sub_field_value)) { $is_set = true; }
				
									$sf = $sub_field;
									
									if(!$is_value_id && !$v) {
										$v = $sub_field_value;
									} 
									
									// Sub-value level
									$sub_values[] = [
										'name' => $rec->getDisplayLabel("{$p['table_name']}.{$p['field_name']}.{$p['subfield_name']}"),
										'code' => $sub_field,
										'value' => $sub_field_value,
										'dataType' => \ca_metadata_elements::getElementDatatype($sub_field, ['returnAsString' => true]),
										'id' => $sub_field_values[$sub_field.'_value_id']
									];
								}
						
								if($is_set) {
									// Attribute level
									$values[] = [
										'id' => $id,	// attribute_id
										'value_id' => $sub_field_values[$sf.'_value_id'],
										'locale' => $locale,
										'value' => $v,
										'subvalues' => $sub_values
									];
								}
							}
						}
					}
					if(sizeof($values) > 0) {
						// Metadata element level
						$row[] = [
							'name' => $rec->getDisplayLabel("{$p['table_name']}.{$p['field_name']}.{$p['subfield_name']}"), 
							'code' => $f,
							'dataType' => \ca_metadata_elements::getElementDatatype("{$p['field_name']}", ['returnAsString' => true]),
							'values' => $values,
							'id' => $id
						];
					}
				}
			}
			
			if (!$is_model) {
				$row = [
					'id' => $sresult->getPrimaryKey(),
					'table' => $table,
					'idno' => $sresult->get(\Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD')),
					'bundles' => $row,
					'manifestUrl' => $base_url.'/service/IIIF/manifest/'.$table.':'.$sresult->getPrimaryKey()
				];
			}
			$data[] = $row;
			
			if(($limit > 0) && (sizeof($data) >= $limit)) {
				break;
			}
		}
	}
	return $is_model ? array_shift($data) : $data;
}

/**
 *
 */
function compare($op, $v1, $v2, array $options=null) : bool {
	$ci = caGetOption('caseInsensitive', $options, true);
	
	switch($op) {
		case 'LT':
		case '<':
			return ((float)$v1 < (float)$v2);
			break;
		case 'LTE':
		case '<=':
			return ((float)$v1 <= (float)$v2);
			break;
		case 'GT':
		case '>':
			return ((float)$v1 > (float)$v2);
			break;
		case 'GTE':
		case '>=':
			return ((float)$v1 >= (float)$v2);
			break;
		case 'LIKE':
			if($ci) {
				return (strpos(mb_strtolower(string($v2)), mb_strtolower(string($v1))) !== false);
			} else {
				return (strpos(string($v2), string($v1)) !== false);
			}
			break;
		case 'IN':
			if(is_array($v2)) {
				if($ci && !is_numeric($v1)) {
					return in_array(mb_strtolower($v1), array_map("mb_strtolower", $v2), true);
				}
				return in_array($v1, $v2, true);
			}
			break;
		case 'NOT_IN':
		case 'NOT IN':
			if(is_array($v2)) {
				if($ci && !is_numeric($v1)) {
					return !in_array(mb_strtolower($v1), array_map("mb_strtolower", $v2), true);
				}
				return !in_array($v1, $v2, true);
			}
			break;
		case 'BETWEEN':
			if(is_array($v2)) {
				return ((float)$v1 >= (float)$v2[0]) && ((float)$v1 <= (float)$v2[1]);
			}
			break;
		case 'NOT_EQ':
		case '<>':
		case '!=':
			if($ci) {
				return (mb_strtolower((string)$v1) !== mb_strtolower((string)$v2));
			}
			return ((string)$v1 !== (string)$v2);
			break;
		case 'EQ':
		case '=':
		default:
			if($ci) {
				return (mb_strtolower((string)$v1) === mb_strtolower((string)$v2));
			}
			return ((string)$v1 === (string)$v2);
			break;
	}
	return false;
}

/** 
 * Returns schema definition for item return structure, used by ItemService directly and 
 * by other services returning item data (such as Search)
 */
function itemSchemaDefinitions() {
	return [
		$bundleSubValueType = new ObjectType([
			'name' => 'BundleSubValue',
			'description' => 'Sub-value for a bundle',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'description' => 'Sub-value name'
				],
				'code' => [
					'type' => Type::string(),
					'description' => 'Sub-value code'
				],
				'dataType' => [
					'type' => Type::string(),
					'description' => 'Data type for sub-value'
				],
				'value' => [
					'type' => Type::string(),
					'description' => 'Sub-value'
				],
				'id' => [
					'type' => Type::int(),
					'description' => 'Internal value_id'
				]
			]
		]),
		$bundleValueType = new ObjectType([
			'name' => 'BundleValue',
			'description' => 'Value for a bundle',
			'fields' => [
				'locale' => [
					'type' => Type::string(),
					'description' => 'Locale for value (Eg. en_US)'
				],
				'value' => [
					'type' => Type::string(),
					'description' => 'Value for bundle'
				],
				'subvalues' => [
					'type' => Type::listOf($bundleSubValueType),
					'description' => 'Value list for values with dataType=Container'
				],
				'id' => [
					'type' => Type::int(),
					'description' => 'Internal attribute_id'
				],
				'value_id' => [
					'type' => Type::int(),
					'description' => 'Internal value_id'
				]
			]
		]),
		$bundleValueListType = new ObjectType([
			'name' => 'BundleValueList',
			'description' => 'List of values for a bundle',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'description' => 'Name of bundle'
				],
				'code' => [
					'type' => Type::string(),
					'description' => 'Bundle code'
				],
				'dataType' => [
					'type' => Type::string(),
					'description' => 'Data type for bundle'
				],
				'values' => [
					'type' => Type::listOf($bundleValueType),
					'description' => 'List of values for bundle'
				]
			]
		]),
		$mediaVersionType = new ObjectType([
			'name' => 'MediaVersionType',
			'description' => 'Data for specific version of a media item',
			'fields' => [
				'version' => [
					'type' => Type::string(),
					'description' => 'Media version'
				],
				'url' => [
					'type' => Type::string(),
					'description' => 'Media URL'
				],
				'tag' => [
					'type' => Type::string(),
					'description' => 'Media tag'
				],
				'mimetype' => [
					'type' => Type::string(),
					'description' => 'Media MIME type'
				],
				'mediaclass' => [
					'type' => Type::string(),
					'description' => 'Media class'
				],
				'width' => [
					'type' => Type::int(),
					'description' => 'Media width (in pixels)'
				],
				'height' => [
					'type' => Type::int(),
					'description' => 'Media height (in pixels)'
				],
				'duration' => [
					'type' => Type::float(),
					'description' => 'Media duration (in seconds)'
				],
				'filesize' => [
					'type' => Type::string(),
					'description' => 'Media filesize (in bytes)'
				],
				'md5' => [
					'type' => Type::string(),
					'description' => 'MD5 checksum'
				],
			]
		]),
		$mediaItemType = new ObjectType([
			'name' => 'MediaItem',
			'description' => 'Data for a media item',
			'fields' => [
				'id' => [
					'type' => Type::string(),
					'description' => 'Media representation id'
				],
				'idno' => [
					'type' => Type::string(),
					'description' => 'Media idno'
				],
				'name' => [
					'type' => Type::string(),
					'description' => 'Name of media'
				],
				'type' => [
					'type' => Type::string(),
					'description' => 'Type of media'
				],
				'versions' => [
					'type' => Type::listOf($mediaVersionType),
					'description' => 'Media versions'
				],
				'mimetype' => [
					'type' => Type::string(),
					'description' => 'Media MIME type'
				],
				'mediaclass' => [
					'type' => Type::string(),
					'description' => 'Media class'
				],
				'md5' => [
					'type' => Type::string(),
					'description' => 'MD5 checksum'
				],
				'originalFilename' => [
					'type' => Type::string(),
					'description' => 'Original filename of media'
				],
				'width' => [
					'type' => Type::int(),
					'description' => 'Media width (in pixels)'
				],
				'height' => [
					'type' => Type::int(),
					'description' => 'Media height (in pixels)'
				],
				'duration' => [
					'type' => Type::float(),
					'description' => 'Media duration (in seconds)'
				],
				'filesize' => [
					'type' => Type::string(),
					'description' => 'Media filesize (in bytes)'
				],
				'isPrimary' => [
					'type' => Type::boolean(),
					'description' => 'Is primary media?'
				],
				'relationship_type_id' => [
					'type' => Type::string(),
					'description' => 'Relationship type_id'
				],
				'relationship_typename' => [
					'type' => Type::string(),
					'description' => 'Relationship type name'
				],
				'relationship_typecode' => [
					'type' => Type::string(),
					'description' => 'Relationship type code'
				],
				'bundles' => [
					'type' => Type::listOf($bundleValueListType),
					'description' => 'Data for media'
				],
			]
		]),
		$itemType = new ObjectType([
			'name' => 'Item',
			'description' => 'A record',
			'fields' => [
				'id' => [
					'type' => Type::int(),
					'description' => 'ID of item'
				],
				'table' => [
					'type' => Type::string(),
					'description' => 'Table of item'
				],
				'idno' => [
					'type' => Type::string(),
					'description' => 'Item identifier'
				],
				'bundles' => [
					'type' => Type::listOf($bundleValueListType),
					'description' => 'Data for related item'
				],
				'media' => [
					'type' => Type::listOf($mediaItemType),
					'description' => 'Media for related item'
				],
				'manifestUrl' => [
					'type' => Type::string(),
					'description' => 'Media for related item'
				],
				'relationship_type_id' => [
					'type' => Type::string(),
					'description' => 'Relationship type_id'
				],
				'relationship_typename' => [
					'type' => Type::string(),
					'description' => 'Relationship type name'
				],
				'relationship_typecode' => [
					'type' => Type::string(),
					'description' => 'Relationship type code'
				],
			]
		]),
		$mediaListType = new ObjectType([
			'name' => 'MediaList',
			'description' => 'Media associated with record',
			'fields' => [
				'id' => [
					'type' => Type::int(),
					'description' => 'ID of media'
				],
				'table' => [
					'type' => Type::string(),
					'description' => 'Table of item'
				],
				'idno' => [
					'type' => Type::string(),
					'description' => 'Item identifier'
				],
				'media' => [
					'type' => Type::listOf($mediaItemType),
					'description' => 'Media for related item'
				],
			]
		]),
		$targetList = _targetListType(0, $bundleValueListType, $mediaItemType),
		$relationshipList = new ObjectType([
			'name' => 'RelationshipList',
			'description' => 'A list of relationships',
			'fields' => [
				'id' => [
					'type' => Type::int(),
					'description' => 'ID of item'
				],
				'table' => [
					'type' => Type::string(),
					'description' => 'Table of item'
				],
				'idno' => [
					'type' => Type::string(),
					'description' => 'Item identifier'
				],
				'targets' => [
					'type' => Type::listOf($targetList),
					'description' => ''
				],
				'relationships' => [
					'type' => Type::listOf($itemType),
					'description' => 'List of related records'
				]
			]
		]),
		$targetListItem = _targetListInputType(0)
	];
}

/**
 *
 */
function _targetListType(int $level=0, $bundleValueListType, $mediaItemType) : ObjectType {
	$fields = [
		'id' => [
			'type' => Type::int(),
			'description' => 'ID of item'
		],
		'table' => [
			'type' => Type::string(),
			'description' => 'Table of item'
		],
		'idno' => [
			'type' => Type::string(),
			'description' => 'Item identifier'
		],
		'bundles' => [
			'type' => Type::listOf($bundleValueListType),
			'description' => 'Data for related item'
		],
		'media' => [
			'type' => Type::listOf($mediaItemType),
			'description' => 'Media for related item'
		],
		'relationship_type_id' => [
			'type' => Type::string(),
			'description' => 'Relationship type_id'
		],
		'relationship_typename' => [
			'type' => Type::string(),
			'description' => 'Relationship type name'
		],
		'relationship_typecode' => [
			'type' => Type::string(),
			'description' => 'Relationship type code'
		]
	];
	if($level < __CA_ITEM_SERVICE_RELATIONSHIP_TARGET_MAXIMUM_DEPTH__) {
		$fields['targets'] = [
			'type' => Type::listOf(_targetListType($level + 1, $bundleValueListType, $mediaItemType)),
			'description' => 'Targets'
		];
	}
	$itemType = new ObjectType([
		'name' => str_repeat("Sub", $level + 1).'Item',
		'description' => 'A record',
		'fields' => $fields
	]);
	return new ObjectType([
		'name' => str_repeat("Sub", $level).'TargetList',
		'description' => 'A list of related items',
		'fields' => [
			'name' => [
				'type' => Type::string(),
				'description' => 'Name of target'
			],
			'table' => [
				'type' => Type::string(),
				'description' => 'Related table'
			],
			'relationships' => [
				'type' => Type::listOf($itemType),
				'description' => 'List of related records'
			]
		]
	]);
}

/**
 *
 */
function _targetListInputType(int $level=0) : InputObjectType {
	$fields = [
		'name' => [
			'type' => Type::string(),
			'description' => 'Name of target'
		],
		'table' => [
			'type' => Type::string(),
			'description' => 'Related table'
		],
		'restrictToTypes' => [
			'type' => Type::listOf(Type::string()),
			'description' => 'Restrict relationships to enumerated related record types'
		],
		'restrictToRelationshipTypes' => [
			'type' => Type::listOf(Type::string()),
			'description' => 'Restrict relationships to enumerated relationship types'
		],
		'start' => [
			'type' => Type::int(),
			'description' => 'Zero-based start index for returned relationships. If omitted starts from the first relationship.'
		],
		'limit' => [
			'type' => Type::int(),
			'description' => 'Maximum number of relationships to return. If omitted all relationships are returned.'
		],
		'bundles' => [
			'type' => Type::listOf(Type::string()),
			'description' => _t('Bundles to return.')
		],
		'includeMedia' => [
			'type' => Type::boolean(),
			'description' => 'Include representations linked to related items?'
		],
		'mediaVersions' => [
			'type' => Type::listOf(Type::string()),
			'description' => 'If including representations, which versions to return'
		],
		'mediaBundles' => [
			'type' => Type::listOf(Type::string()),
			'description' => _t('Media bundles to return.')
		],
		'restrictMediaToTypes' => [
			'type' => Type::listOf(Type::string()),
			'description' => 'If including representations, which restrict to specified types'
		]
	];
	if($level < __CA_ITEM_SERVICE_RELATIONSHIP_TARGET_MAXIMUM_DEPTH__) {
		$fields['targets'] = [
			'type' => Type::listOf(_targetListInputType($level + 1)),
			'description' => 'Targets'
		];
	}
	return new InputObjectType([
		'name' => str_repeat("Sub", $level).'TargetListInputItem',
		'description' => 'A list of targets (table + options) to return relationships for',
		'fields' => $fields
	]);
}

/**
 *
 */
function resolveParams(array $args, ?string $prefix=null) : array {
	$opts = [];
	
	$id_key = $prefix ? $prefix.'Id' : 'id';
	$idno_key = $prefix ? $prefix.'Idno' : 'idno';
	$identifier_key = $prefix ? $prefix.'Identifier' : 'identifier';
	
	$identifier = null;
	if(isset($args[$id_key]) && ($args[$id_key] > 0)) {
		$identifier = $args[$id_key];
		$opts['primaryKeyOnly'] = true;
	} elseif(isset($args[$idno_key]) && (strlen($args[$idno_key]) > 0)) {
		$identifier = $args[$idno_key];
		$opts['idnoOnly'] = true;
	} else {
		$identifier = $args[$identifier_key] ?? null;
	}
	return [$identifier, $opts];
}

/**
 *
 */
function resolveListParams(array $args, ?string $prefix=null) : array {
	$opts = [];
	
	$id_key = $prefix ? $prefix.'Ids' : 'ids';
	$idno_key = $prefix ? $prefix.'Idnos' : 'idnos';
	$identifier_key = $prefix ? $prefix.'Identifiers' : 'identifiers';
	
	$identifiers = null;
	if(isset($args[$id_key]) && is_array($args[$id_key])) {
		$identifiers = $args[$id_key];
		$opts['primaryKeyOnly'] = true;
	} elseif(isset($args[$idno_key]) && is_array($args[$idno_key])) {
		$identifiers = $args[$idno_key];
		$opts['idnoOnly'] = true;
	} elseif(isset($args[$identifier_key]) && is_array($args[$identifier_key])) {
		$identifiers = $args[$identifier_key] ?? null;
	}
	return [$identifiers, $opts];
}
