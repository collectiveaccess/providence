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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/ItemSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\ItemSchema;


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
							'description' => _t('Bundles to return')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$rec = self::resolveIdentifier($table = $args['table'], $args['identifier']);
						$rec_pk = $rec->primaryKey();
						
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

						$data = [];
						if(isset($bundles) && is_array($bundles) && sizeof($bundles)) {
							foreach($bundles as $f) {
								$fbits = explode('.', $f);
								$values = [];
								$d = $rec->get($f, ['returnWithStructure' => true, 'useLocaleCodes' => true, 'convertCodesToIdno' => true]);
							
								if(!is_array($d) || (sizeof($d) === 0)) { continue; }
								
								if($is_intrinsic = $rec->hasField($fbits[1])) {
									if(strlen($v = $rec->get($f, ['convertCodesToIdno' => true]))) {
										$data[] = [
											'name' => $rec->getDisplayLabel($f), 
											'code' => $f,
											'locale' => null,
											'dataType' => $table::intrinsicTypeToString((int)$rec->getFieldInfo($fbits[1], 'FIELD_TYPE')),
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
												if(in_array($sub_field, [$rec_pk, 'locale_id', 'is_preferred', 'item_type_id', 'source_info'])) { continue; }
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
												'locale' => ca_locales::localeIDToCode($locale_id),
												'value' => $rec->get($f, ['convertCodesToIdno' => true]),
												'subvalues' => $sub_values
											];
										}
									}
									if(sizeof($values) > 0) {
										$data[] = [
											'name' => $rec->getDisplayLabel($f), 
											'code' => $f,
											'locale' => ca_locales::localeIDToCode($locale_id),
											'dataType' => ca_metadata_elements::getElementDatatype($fbits[1], ['returnAsString' => true]),
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
													'dataType' => ca_metadata_elements::getElementDatatype($sub_field, ['returnAsString' => true]),
												];
											}
										}
									
										if($is_set) {
											$values[] = [
												'locale' => ca_locales::localeIDToCode($locale_id),
												'value' => $rec->get($f, ['convertCodesToIdno' => true]),
												'subvalues' => $sub_values
											];
										}
									}
									if(sizeof($values) > 0) {
										$data[] = [
											'name' => $rec->getDisplayLabel($f), 
											'code' => $f,
											'locale' => ca_locales::localeIDToCode($locale_id),
											'dataType' => ca_metadata_elements::getElementDatatype($fbits[1], ['returnAsString' => true]),
											'values' => $values
										];
									}
								}
							}
						}
						
						return ['table' => $rec->tableName(), 'identifier' => $args['identifier'], 'id' => $rec->getPrimaryKey(), 'bundles' => $data];
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
