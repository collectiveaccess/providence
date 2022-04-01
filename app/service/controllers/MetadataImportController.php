<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/MetadataImportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
require_once(__CA_APP_DIR__.'/service/schemas/MetadataImportSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/MetadataImportHelpers.php');
require_once(__CA_APP_DIR__.'/service/helpers/ServiceHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\MetadataImportSchema;
use GraphQLServices\Helpers\MetadataImport;
use GraphQLServices\Helpers;


class MetadataImportController extends \GraphQLServices\GraphQLServiceController {
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
				// List available importers
				// ------------------------------------------------------------
				'list' => [
					'type' => Type::listOf(MetadataImportSchema::get('Importer')),
					'description' => _t('List of available importers'),
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
						$importers = ca_data_importers::getImporters($table);
						
						$ret = array_values(array_map(function($v) {
							$formats = caGetOption('inputFormats', $v['settings'], []);
							$source = caGetOption('sourceUrl', $v['settings'], null);
						
							if(!($t_instance = \Datamodel::getInstance($v['table_num'], true))) { return null; }
							return [
								'id' => $v['importer_id'],
								'code' => $v['importer_code'],
								'name' => $v['label'],
								'table' => $t_instance->tableName(),
								'type' => $v['type'],
								'formats' => $formats,
								'source' => $source
							];
						}, $importers));
						return $ret;
					}
				],
				// ------------------------------------------------------------
				// List available importer settings
				// ------------------------------------------------------------
				'availableSettings' => [
					'type' => Type::listOf(MetadataImportSchema::get('ImporterSettingDefinition')),
					'description' => _t('List all available settings for importers'),
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
						
						$t_importer = new ca_data_importers();

						$settings = array_map(function($v, $c) {
							if(is_array($options = $v['options'] ?? null)) {
								$options = array_map(function($name, $value) {
									return ['name' => $name, 'value' => $value];
								}, $options, array_keys($options));
							}
						
							return [
								'name' => $v['label'],
								'code' => $c,
								'description' => $v['description'],
								'options' => $options
							];
						}, $data = $t_importer->getAvailableSettings(), array_keys($data));
						
						return $settings;
					}
				],
				// ------------------------------------------------------------
				// Importer form
				// ------------------------------------------------------------
				'importerForm' => [
					'type' => MetadataImportSchema::get('ImporterFormInfo'),
					'description' => _t('Get importer editing form data'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of importer to edit')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$id = $args['id'];
						
						$t_importer = new ca_data_importers($id);
						$fields = $t_importer->getFormFields();
						
						$required_fields = ['ca_data_importers.preferred_labels.name', 'ca_data_importers.importer_code', 'ca_data_importers.table_num'];
						
						$properties = $ui_schema = [];
						foreach([
								'ca_data_importers.preferred_labels.name', 'ca_data_importers.importer_code', 
								'ca_data_importers.table_num', 'ca_data_importers.settings'
							] as $code
						) {
							$types = \GraphQLServices\Helpers\fieldTypeToJsonFormTypes($t_importer, $code, []);

							foreach($types as $type) {
								$field = [
									'title' => $type['label'] ?? '???',
									'description' => $type['description'] ?? '',
									'type' => $type['type'] ?? '',
									'format' => $type['format'] ?? '',
									'uniqueItems' => $type['uniqueItems'] ?? false
								]; 		
								if($type['items']) { $field['items'] = $type['items']; }
								if($type['uiSchema']) { 
									$ui_schema[$code] = $type['uiSchema'];
								}
								foreach(['minLength', 'maxLength', 'enum', 'enumNames', 'minimum', 'maximum'] as $k) {
									if (isset($type[$k])) {
										$field[$k] = $type[$k];
									}
								}
							
								$properties[$type['code']] = $field;	
							}				
						}
						
						$values = \GraphQLServices\Helpers\fieldFormValues($t_importer);
						$form = [
							'title' => $t_importer->get('ca_data_importers.preferred_labels'),
							'description' => '',
							'required' => $required_fields,
							'properties' => json_encode($properties),
							'uiSchema' => json_encode($ui_schema),
							'values' => json_encode($values)
						];
						
						return $form;
					}
				]
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				// ------------------------------------------------------------
				// Add new importer
				// ------------------------------------------------------------
				'add' => [
					'type' => MetadataImportSchema::get('Importer'),
					'description' => _t('Add new importer'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'name',
							'type' => Type::string(),
							'description' => _t('Name of importer')
						],
						[
							'name' => 'code',
							'type' => Type::string(),
							'description' => _t('Code for importer')
						],
						[
							'name' => 'format',
							'type' => Type::string(),
							'description' => _t('Format importer accepts')
						],
						[
							'name' => 'formats',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of formats importer accepts')
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table importer targets')
						],
						[
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Table type importer targets')
						],
						[
							'name' => 'settings',
							'type' => Type::listOf(MetadataImportSchema::get('ImporterSetting')),
							'description' => _t('Importer settings')
						],
						
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						return self::_setImporter($args);
					}
				],
				'edit' => [
					'type' => MetadataImportSchema::get('Importer'),
					'description' => _t('Edit importer'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of importer to edit')
						],
						[
							'name' => 'name',
							'type' => Type::string(),
							'description' => _t('Name of importer')
						],
						[
							'name' => 'code',
							'type' => Type::string(),
							'description' => _t('Code for importer')
						],
						[
							'name' => 'format',
							'type' => Type::string(),
							'description' => _t('Format importer accepts')
						],
						[
							'name' => 'formats',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of formats importer accepts')
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table importer targets')
						],
						[
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Table type importer targets')
						],
						[
							'name' => 'settings',
							'type' => Type::listOf(MetadataImportSchema::get('ImporterSetting')),
							'description' => _t('Importer settings')
						],
						
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						return self::_setImporter($args);
					}
				],
				'delete' => [
					'type' => MetadataImportSchema::get('Importer'),
					'description' => _t('Edit importer'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of importer to edit')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						if(!$t_importer = ca_data_importers::findAsInstance(['importer_id' => $args['id']])) {
							return ['errors' => [
								_t('Importer with id %1 does not exist', $args['id'])
							]];
						}
						if(!$t_importer->delete(true)) {
							return ['errors' => [
								_t('Could not delete importer: %1', join('; ', $t_importer->getErrors()))
							]];
						}
						
						return [];
					}
				]
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _setImporter(array $args) : array {
		$errors = [];
		if(!strlen($args['code'])) { 
			$errors[] = _t('A code must be specified');
			return ['errors' => $errors];
		}
		if(!$args['name']) { $args['name'] = $args['code']; }
		
		$t_importer = new ca_data_importers();
		if($args['id'] > 0) {
			if(!$t_importer ->load($args['id'])) {
				$errors[] = _t('Mapping with id %1 does not exist', $args['id']);
				return ['errors' => $errors];
			}
		}
		
		$t_importer->set('importer_code', $args['code']);
		$t_importer->set('table_num', \Datamodel::getTableNum($args['table']));
		
		if($t_importer->isLoaded()) {
			if(!$t_importer->update()) {
				$errors[] = _t('Failed to update mapping: %1', join('; ', $t_importer->getErrors()));
				return ['errors' => $errors];
			}
		
			if(!$t_importer->replaceLabel(['name' => $args['name']], ca_locales::getDefaultCataloguingLocaleID(), null, true)) {
				$errors[] = _t('Failed to update label to mapping mapping: %1', join('; ', $t_importer->getErrors()));
				return ['errors' => $errors];
			}
		} else {
			if(!$t_importer->insert()) {
				$errors[] = _t('Failed to create mapping: %1', join('; ', $t_importer->getErrors()));
				return ['errors' => $errors];
			}
		
			if(!$t_importer->addLabel(['name' => $args['name']], ca_locales::getDefaultCataloguingLocaleID(), null, true)) {
				$errors[] = _t('Failed to add label to mapping mapping: %1', join('; ', $t_importer->getErrors()));
				return ['errors' => $errors];
			}
		}
		
		foreach($args['settings'] as $s) {
			if(!$t_importer->isValidSetting($s['code'])) {
				$errors[] = _t('%1 is not a valid setting', $s);
				continue;
			}
			$t_importer->setSetting($s['code'], $s['value']);
		}
		if((!is_array($args['formats']) || sizeof($args['formats'])) && $args['format']) {
			$args['formats'] = [$args['format']];
		}
		if(is_array($args['formats'])) {
			$t_importer->setSetting('inputFormats', $args['formats']);
		}
		if(strlen($args['type'])) {
			$t_importer->setSetting('type', $args['type']);
		}
		if(!$t_importer->update()) {
			$errors[] = _t('Could not save settings: %1', join('; ', $t_importer->getErrors()));
		}
		return [
			'id' => $t_importer->getPrimaryKey(),
			'name' => $t_importer->get('ca_data_importers.preferred_labels'),
			'code' => $t_importer->get('ca_data_importers.importer_code'),
			'table' => \Datamodel::getTableName($t_importer->get('table_num')),
			'type' => $t_importer->getSetting('type'),
			'formats' => $t_importer->getSetting('inputFormats'),
			'source' => null,
			'errors' => $errors
		];
	}
	# -------------------------------------------------------
}
