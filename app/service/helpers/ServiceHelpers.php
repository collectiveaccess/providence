<?php
namespace GraphQLServices\Helpers;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Extract list of bundles to return from request args.
 *
 */
function extractBundleNames(\BaseModel $rec, array $args) : array {
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
 *
 * @return array
 */
function fetchDataForBundles($sresult, array $bundles, array $options=null) : array {
	$start = caGetOption('start', $options, 0);
	$limit = caGetOption('limit', $options, null);
	
	$data = [];
	if(isset($bundles) && is_array($bundles) && sizeof($bundles)) {
		$table = $sresult->tableName();
		
		$is_model = false;
		if(is_a($sresult, '\BaseModel')) {	// convert model instance to search result
			$sresult = \caMakeSearchResult($table, [$sresult->getPrimaryKey()]);
			$is_model = true;
		}
		
		$rec = \Datamodel::getInstance($table, true);
		
		if(($start > 0) && ($start < $sresult->numHits())) {
			$sresult->seek($start);
		}
		while($sresult->nextHit()) {
			$row = [];
			foreach($bundles as $f) {
				$fbits = explode('.', $f);
				$values = [];
				$d = $sresult->get($f, ['returnWithStructure' => true, 'useLocaleCodes' => true, 'convertCodesToIdno' => true]);
	
				if(!is_array($d) || (sizeof($d) === 0)) { continue; }
		
				if($is_intrinsic = $rec->hasField($fbits[1])) {
					if(strlen($v = $sresult->get($f, ['convertCodesToIdno' => true]))) {
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'locale' => null,
							'dataType' => $table::intrinsicTypeToString((int)$sresult->getFieldInfo($fbits[1], 'FIELD_TYPE')),
							'values' => [
								['locale' => null,
								'value' => $v,
								'subvalues' => null]
							]
						];
					}
				} elseif($is_label = (in_array($fbits[1], ['preferred_labels', 'nonpreferred_labels'], true))) {
					$label = $rec->getLabelTableInstance();
					foreach($d as $locale_id => $by_locale) {
						$sub_values = [];
			
						$is_set = false;
						foreach($by_locale as $bundle_index => $sub_field_values) {
							foreach($sub_field_values as $sub_field => $sub_field_value) {
								if(in_array($sub_field, [$sresult_pk, 'locale_id', 'is_preferred', 'item_type_id', 'source_info'])) { continue; }
								if(strlen($sub_field_value)) { $is_set = true; }
				
								$sub_values[] = [
									'name' => $rec->getDisplayLabel("{$table}.{$f}.{$sub_field}"),
									'code' => $sub_field,
									'value' => $sub_field_value,
									'dataType' => $label->getFieldInfo($sub_field, 'LIST_CODE') ? 'String' : $label::intrinsicTypeToString($label->getFieldInfo($sub_field, 'FIELD_TYPE'))
								];
							}
						}
			
						if($is_set) {
							$values[] = [
								'locale' => \ca_locales::localeIDToCode($locale_id),
								'value' => $sresult->get($f, ['convertCodesToIdno' => true]),
								'subvalues' => $sub_values
							];
						}
					}
					if(sizeof($values) > 0) {
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'locale' => \ca_locales::localeIDToCode($locale_id),
							'dataType' => \ca_metadata_elements::getElementDatatype($fbits[1], ['returnAsString' => true]),
							'values' => $values
						];
					}
				} else {
					foreach($d as $locale_id => $by_locale) {
						$sub_values = [];
			
						$is_set = false;
						foreach($by_locale as $bundle_index => $sub_field_values) {
							foreach($sub_field_values as $sub_field => $sub_field_value) {
								if(strlen($sub_field_value)) { $is_set = true; }
				
								$sub_values[] = [
									'name' => $rec->getDisplayLabel("{$table}.{$f}.{$sub_field}"),
									'code' => $sub_field,
									'value' => $sub_field_value,
									'dataType' => \ca_metadata_elements::getElementDatatype($sub_field, ['returnAsString' => true]),
								];
							}
						}
			
						if($is_set) {
							$values[] = [
								'locale' => \ca_locales::localeIDToCode($locale_id),
								'value' => $sresult->get($f, ['convertCodesToIdno' => true]),
								'subvalues' => $sub_values
							];
						}
					}
					if(sizeof($values) > 0) {
						$row[] = [
							'name' => $rec->getDisplayLabel($f), 
							'code' => $f,
							'locale' => \ca_locales::localeIDToCode($locale_id),
							'dataType' => \ca_metadata_elements::getElementDatatype($fbits[1], ['returnAsString' => true]),
							'values' => $values
						];
					}
				}
			}
			
			if (!$is_model) {
				$row = [
					'id' => $sresult->getPrimaryKey(),
					'table' => $table,
					'idno' => $sresult->get('idno'),
					'bundles' => $row
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
					'description' => ''
				]
			]
		])
	];
}
