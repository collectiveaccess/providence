<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/SchemaController.php :
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
require_once(__CA_APP_DIR__.'/service/schemas/SchemaSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/SchemaHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\SchemaSchema;
use GraphQLServices\Helpers\Schema;


class SchemaController extends \GraphQLServices\GraphQLServiceController {
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
				// Tables
				// ------------------------------------------------------------
				'tables' => [
					'type' => SchemaSchema::get('TableList'),
					'description' => _t('List of available tables'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$tables = caFilterTableList(\GraphQLServices\Helpers\Schema\primaryTables());
						
						$ret = array_map(function($v) {
							$t = Datamodel::getInstance($v, true);
							return [
								'name' => $t->getProperty('NAME_PLURAL'),
								'code' => $v,
								'types' => array_map(function($x) {
									return [
										'name' => $x['name_plural'],
										'code' => $x['idno']
									];
								}, $t->getTypeList())
							];
						}, $tables);
						
						return ['tables' => $ret];
					}
				],
				// ------------------------------------------------------------
				// Types
				// ------------------------------------------------------------
				'types' => [
					'type' => SchemaSchema::get('Table'),
					'description' => _t('List of available types for table'),
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
							'description' => _t('Table')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$table = $args['table'];
						
						if(!\GraphQLServices\Helpers\Schema\tableIsValid($table)) {
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
						$t = Datamodel::getInstance($table, true);
						return [
							'name' => $t->getProperty('NAME_PLURAL'),
							'code' => $table,
							'types' => array_map(function($x) {
								return [
									'name' => $x['name_plural'],
									'code' => $x['idno']
								];
							}, $t->getTypeList())
						];
					}
				],
				// ------------------------------------------------------------
				// Bundles
				// ------------------------------------------------------------
				'bundles' => [
					'type' => SchemaSchema::get('BundleList'),
					'description' => _t('List of available bundles'),
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
							'description' => _t('Table'),
							'defaultValue' => null
						],
						[
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Return bundles for a specific type. If omitted all bundles for the table are returned.'),
							'defaultValue' => null
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Return only specified bundles'),
							'defaultValue' => null
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$table = $args['table'];
						$type = $args['type'];
						$limit_to_bundles = $args['bundles'];
						if(!is_array($limit_to_bundles) || !sizeof($limit_to_bundles)) { $limit_to_bundles = null; }
						
						if(!\GraphQLServices\Helpers\Schema\tableIsValid($table)) {
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
						$t = Datamodel::getInstance($table, true);
						$bundles = $t->getBundleList(['includeBundleInfo' => true, 'rewriteKeys' => true]);
						
						$bundles = array_filter($bundles, function($v, $k) use ($limit_to_bundles) {
							if(is_array($limit_to_bundles) && !in_array($k, $limit_to_bundles, true)) { return false; }
							if(strtoupper($v['type']) === 'SPECIAL') { return false; }
							if((strtoupper($v['type']) === 'RELATED_TABLE') && !\Datamodel::tableExists($k)) { return false; }
							return true;
						}, ARRAY_FILTER_USE_BOTH);
						
						$bundles = array_map(function($code, $info) use ($t) {
							
							$desc = $t->getDisplayDescription(($table = $t->tableName()).'.'.$code);
							$info['type'] = strtoupper($info['type']);
							
							$tr = null;
							if($info['type'] === 'ATTRIBUTE') {
								
								// type restrictions
								$tr = \ca_metadata_elements::getTypeRestrictionsAsList($code, ['returnAll' => true]);
								$tr = isset($tr[$code][$table]) ? $tr[$code][$table] : null;
							}
							
							$dt = \GraphQLServices\Helpers\Schema\bundleDataType($t, $code);
							
							$subelements = null;
							if($dt === 'CONTAINER') {
								if(is_array($subelements = \ca_metadata_elements::getElementsForSet($code))) {
								
									array_shift($subelements); // get rid of root
									$subelements = array_filter($subelements, function($v) { return ($v['datatype'] !== 0); }); // filter containers
								
									$subelements = array_map(function($v) use ($t, $code) {
										return [
											'name' => $v['display_label'],
											'code' => $v['element_code'],
											'type' => 'ATTRIBUTE',
											'list' => caGetListCode(\ca_metadata_elements::getElementListID($v['element_code'])),
											'dataType' => \GraphQLServices\Helpers\Schema\bundleDataType($t, $t->tableName().'.'.$code.'.'.$v['element_code']),
											'description' => $t->getDisplayDescription(($table = $t->tableName()).'.'.$code.'.'.$v['element_code']),
											'settings' => \GraphQLServices\Helpers\Schema\formatSettings(\ca_metadata_elements::getElementSettingsForId($v['element_code'])),
										];
									}, $subelements);
								}
							}
							//print_R($subelements);
							return [
								'name' => $t->getDisplayLabel($t->tableName().'.'.$code),
								'code' => $code,
								'type' => $info['type'],
								'list' => caGetListCode(\ca_metadata_elements::getElementListID($code)),
								'dataType' => $dt,
								'description' => $desc,
								'typeRestrictions' => $tr,
								'settings' => \GraphQLServices\Helpers\Schema\formatSettings(\ca_metadata_elements::getElementSettingsForId($code)),
								'subelements' => $subelements
							];
						}, array_keys($bundles), $bundles);
						
						if($type) {
							$bundles = array_filter($bundles, function($v) use ($type) {
								if(is_array($v['typeRestrictions'])) {
									foreach($v['typeRestrictions'] as $tr) {
										if(is_null($tr['type'])) {
											// type-less = always include in list
											return true;
										} elseif($type === $tr['type']) {
											return true;
										}
									}
								}
								return false;
							});
						}
					
						return [
							'name' => $t->getProperty('NAME_PLURAL'),
							'code' => $table,
							'bundles' => $bundles
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
