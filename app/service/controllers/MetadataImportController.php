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
require_once(__CA_APP_DIR__.'/service/helpers/ErrorHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\MetadataImportSchema;
use GraphQLServices\Helpers\MetadataImport;
use GraphQLServices\Helpers;
use GraphQLServices\Helpers\Error;


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
					},
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
					},
				],
				// ------------------------------------------------------------
				// List mappings for importer
				// ------------------------------------------------------------
				'listMappings' => [
					'type' => MetadataImportSchema::get('ImporterMappingListInfo'),
					'description' => _t('Return mappings and contextual information for mappings in an importer'),
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
							'description' => _t('ID of importer')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$t_importer = new ca_data_importers($args['id']);
						if(!$t_importer->isLoaded()) {
							throw new \ServiceException(_t('Importer does not exist'));
						}
						$items = $t_importer->getItems();
						
						$ret = $mappings = [];
						
						foreach($items as $item) {
							$tmp = explode(':', $item['source']);
							if($item['mapping_type'] === 'C') {	// detect constants
								$mapping_type = 'CONSTANT';
							} else {
								$mapping_type = 'MAPPING';
							}
							
							$settings = $item['settings'];
							$refineries = $replacement_values = [];
							if(is_array($settings['refineries'])) {
								foreach($settings['refineries'] as $r) {
									if(!trim($r)) { continue; }
									$rsettings = [];
									foreach($settings as $k => $v) {
										if(preg_match("!^{$r}_(.*)$!", $k, $m)) {
											$rsettings[] = ['name' => $m[1], 'value' => is_array($v) ? json_encode($v) : $v];
											unset($settings[$k]);
										}
									}
									$refineries[] = [
										'refinery' => $r,
										'options' => $rsettings
									];
								}
								unset($settings['refineries']);
							}
							
							if(is_array($settings['original_values'])) {
								foreach($settings['original_values'] as $i => $ov) {
									if(!strlen($ov)) { continue; }
									$rv = $settings['replacement_values'][$i] ?? null;
									$replacement_values[] = [
										'original' => $ov,
										'replacement' => $rv
									];
								}
							}
							unset($settings['original_values']);
							unset($settings['replacement_values']);
							
							$m = [
								'id' => $item['item_id'],
								'type' => $mapping_type,
								'group_id' => $item['group_id'],
								'source' => $item['source'],
								'destination' => $item['destination'],
								'options' => $settings,
								'replacement_values' => $replacement_values,
								'refineries' => $refineries
							];
							
							$mappings[] = $m;
						}
						
						return ['mappings' => $mappings];
					},
				],
				// ------------------------------------------------------------
				// Get mapping settings (options) form
				// ------------------------------------------------------------
				'mappingSettings' => [
					'type' => MetadataImportSchema::get('ImporterMapping'),
					'description' => _t('Return mappings and contextual information for mappings in an importer'),
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
							'description' => _t('ID of importer')
						],
						[
							'name' => 'mapping_id',
							'type' => Type::int(),
							'description' => _t('ID of mapping')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$t_importer = new ca_data_importers();

						// $settings = array_map(function($v, $c) {
// 							if(is_array($options = $v['options'] ?? null)) {
// 								$options = array_map(function($name, $value) {
// 									return ['name' => $name, 'value' => $value];
// 								}, $options, array_keys($options));
// 							}
// 						
// 							return [
// 								'name' => $v['label'],
// 								'code' => $c,
// 								'description' => $v['description'],
// 								'options' => $options
// 							];
// 						}, $data = $t_importer->getAvailableSettings(), array_keys($data));
						
						return $settings;
					},
				],
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
							'type' => Type::int(),
							'description' => _t('Table type importer target')
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
				// ------------------------------------------------------------
				// Edit importer
				// ------------------------------------------------------------
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
							'type' => Type::int(),
							'description' => _t('Table type importer table number')
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
				// ------------------------------------------------------------
				// Delete importer
				// ------------------------------------------------------------
				'delete' => [
					'type' => MetadataImportSchema::get('ImporterResult'),
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
						
						$errors = [];
						if(!$t_importer = ca_data_importers::findAsInstance(['importer_id' => $args['id']])) {
							$errors[] = Error\error($args['id'], 750, _t('Importer with id %1 does not exist', $args['id']), 'GENERAL');
						}
						if(!$t_importer->delete(true)) {
							foreach($t_importer->getErrors() as $e) {
								$errors[] = Error\error($args['id'], $e->getErrorNumber(), $e->getErrorDescription());
							}
						}
						
						return ['id' => null, 'errors' => $errors, 'warnings' => [], 'info' => []];
					}
				],
				// ------------------------------------------------------------
				// Editing mappings
				// ------------------------------------------------------------
				'editMappings' => [
					'type' => MetadataImportSchema::get('ImporterResult'),
					'description' => _t('Edit mapping'),
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
							'name' => 'mappings',
							'type' => Type::listOf(MetadataImportSchema::get('ImporterMappingInput')),
							'description' => _t('ID of importer to edit')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = $info = [];
						
						$ids = [];
						if(!$t_importer = ca_data_importers::findAsInstance(['importer_id' => $args['id']])) {
							$errors[] = Error\error($args['id'], 750, _t('Importer with id %1 does not exist', $args['id']), 'GENERAL');
						} else {
							foreach($args['mappings'] as $i => $mapping) {
								$group = caGetOption('group', $mapping, "group_{$i}");
								$source = $mapping['source'];
								$destination = $mapping['destination'];
								$mapping_type = $mapping['type'];
								
								switch(strtoupper($mapping_type)) {
									case 'CONSTANT':
										$mapping_type = 'C';
										break;
									case 'MAPPING':
									default:
										$mapping_type = 'M';
										break;
									
								}
							
								if(!$source || !$destination) {
									$warnings[] = Error\warning($args['id'], 750, _t('No source or destination specified for mapping'), 'MAPPING');
									continue;
								}
							
								$t_group = $t_importer->getGroup($group);
							
								$settings = [];
							
								if(is_array($mapping['options'])) {
									$settings['options'] = $mapping['options'];
								}
								if(is_array($mapping['replacement_values'])) {
									$settings['original_values'] = array_map(
										function($v) { return $v['original']; },
										$mapping['replacement_values']
									);
									$settings['replacement_values'] = array_map(
										function($v) { return $v['replacement']; },
										$mapping['replacement_values']
									);
								}
							
								if(is_array($mapping['refineries'])) {
									$settings['refineries'] = $mapping['refineries'];
								}
							
								$settings = \ca_data_importers::mergeItemSettings($settings);
								
								if (isset($mapping['id']) && $mapping['id']) {
									if($t_item = $t_group->editItem($mapping['id'], $mapping_type, $source, $destination, $settings, ['returnInstance' => true])) {
										$info[] = Error\info($t_item->getPrimaryKey(), 'EDIT', _t('Edited mapping for source %1 and destination %2 with item_id %3', $source, $destination, $t_item->getPrimaryKey()), 'MAPPING');
									}								
								} else {
									if($t_item = $t_group->addItem($mapping_type, $source, $destination, $settings, ['returnInstance' => true])) {
										$info[] = Error\info($t_item->getPrimaryKey(), 'ADD', _t('Added mapping for source %1 and destination %2 with item_id %3', $source, $destination, $t_item->getPrimaryKey()), 'MAPPING');
									}
								}
								$ids[] = $t_group->getPrimaryKey();
								
							}
						}
						
						return ['id' => $ids, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info];
					}
				],
				// ------------------------------------------------------------
				// Delete mapping
				// ------------------------------------------------------------
				'deleteMapping' => [
					'type' => MetadataImportSchema::get('ImporterResult'),
					'description' => _t('Delete mapping'),
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
							'description' => _t('ID of importer to delete')
						],
						[
							'name' => 'mapping_id',
							'type' => Type::int(),
							'description' => _t('ID of mapping to delete')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = [];
						
						if (!is_a($t_importer = self::_loadImporter($args['id']), 'ca_data_importers')) {
							$errors[] = $t_importer;
						} elseif(!$t_importer->removeItem($args['mapping_id'])) {
							if($t_importer->numErrors() > 0) {
								foreach($t_importer->getErrors() as $e) {
									$errors[] = Error\error($args['mapping_id'], $e->getErrorNumber(), $e->getErrorMessage, 'REMOVE_MAPPING');
								}
							} else {
								$errors[] = Error\error($args['mapping_id'], 750, _t('Could not remove mapping'), 'REMOVE_MAPPING');
							}
						}
						
						return ['id' => null, 'errors' => $errors, 'warnings' => [], 'info' => []];
					}
				],
				'reorderMappings' => [
					'type' => MetadataImportSchema::get('ImporterResult'),
					'description' => _t('Reorder mappings'),
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
							'description' => _t('ID of importer to reorder'),
							'defaultValue' => null
						],
						[
							'name' => 'data',
							'type' => MetadataImportSchema::get('ImporterReorderInputType'),
							'description' => _t('Reorder values for mapping')
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						$errors = [];
						if (!is_a($t_importer = self::_loadImporter($args['id']), 'ca_data_importers')) {
							$errors[] = $t_importer;
						} else {

							$sorted_id_str = $args['data']['sorted_ids'];
							$sorted_id_arr = preg_split('![&;,]!', $sorted_id_str);
							$sorted_id_int_arr = array_filter(array_map(function($v) { return (int)$v; }, $sorted_id_arr), function($v) { return ($v > 0); });
						
							$errors = $t_importer->reorderItems($sorted_id_int_arr);
							
							return ['id' => [$args['id']], 'errors' => $errors, 'warnings' => [], 'info' => []];
						}
					}
				],
			],
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _loadImporter(int $id) {
		if(!$t_importer = ca_data_importers::findAsInstance(['importer_id' => $id])) {
			return Error\error($id, 750, _t('Importer with id %1 does not exist', $args['id']), 'LOAD');
		}
		return $t_importer;
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
