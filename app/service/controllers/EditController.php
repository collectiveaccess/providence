<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/EditController.php :
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
require_once(__CA_APP_DIR__.'/service/schemas/EditSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/EditHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\EditSchema;
use GraphQLServices\Helpers\Edit;


class EditController extends \GraphQLServices\GraphQLServiceController {
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
				
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				// ------------------------------------------------------------
				'add' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Add a new record'),
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
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Type code for new record. (Eg. ca_objects)')
						],
						[
							'name' => 'idno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value for new record.')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(EditSchema::get('Bundle')),
							'description' => _t('Bundles to add')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$table = $args['table'];
						$bundles = $args['bundles'];
						
						// Create new record
						$instance = new $table();
						$instance->set('idno', $args['idno']);
						$instance->set('type_id', $args['type']);
						if(!$instance->insert()) {
							foreach($instance->errors() as $e) {
								$errors[] = [
									'code' => $e->getErrorNumber(),
									'message' => $e->getErrorDescription(),
									'bundle' => 'GENERAL'
								];
							}	
						} else {
							$ret = self::processBundles($instance, $bundles);
							$errors += $ret['errors'];
							$warnings += $ret['warnings'];
						}
						
						return ['table' => $table, 'id' => $instance->getPrimaryKey(), 'idno' => $instance->get('idno'), 'errors' => $errors, 'warnings' => $warnings];
					}
				],
				'edit' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Edit an existing record'),
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
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Type code for new record. (Eg. ca_objects)')
						],
						[
							'name' => 'identifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to edit.')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(EditSchema::get('Bundle')),
							'description' => _t('Bundles to add')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$table = $args['table'];
						$bundles = $args['bundles'];
						
						// Load record
						if(!($instance = self::resolveIdentifier($table, $args['identifier']))) {
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Invalid identifier'),
								'bundle' => 'GENERAL'
							];
						} else {
							$ret = self::processBundles($instance, $bundles);
						}
						
						return ['table' => $table, 'id' => $instance->getPrimaryKey(), 'idno' => $instance->get('idno'), 'errors' => $ret['errors'], 'warnings' => $ret['warnings']];
					}
				],
				'delete' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Delete an existing record'),
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
							'description' => _t('Alphanumeric idno value or numeric database id of record to delete.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$table = $args['table'];
						
						// Delete record
						if(!($instance = self::resolveIdentifier($table, $args['identifier']))) {
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Invalid identifier'),
								'bundle' => 'GENERAL'
							];
						} elseif(!($rc = $instance->delete(true))) {
							foreach($instance->errors() as $e) {
								$errors[] = [
									'code' => $e->getErrorNumber(),
									'message' => $e->getErrorDescription(),
									'bundle' => $bundle_name
								];
							}
						}
						
						return ['table' => $table, 'id' => null, 'idno' => null, 'errors' => $ret['errors'], 'warnings' => $ret['warnings']];
					}
				],
				//
				// Relationships
				//
				'addRelationship' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Add a relationship between two records'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'subject',
							'type' => Type::string(),
							'description' => _t('Subject table name. (Eg. ca_objects)')
						],
						[
							'name' => 'subjectIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'target',
							'type' => Type::string(),
							'description' => _t('Target table name. (Eg. ca_objects)')
						],
						[
							'name' => 'targetIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'relationshipType',
							'type' => Type::string(),
							'description' => _t('Relationship type code.')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(EditSchema::get('Bundle')),
							'description' => _t('Bundles to add')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$subject = $args['subject'];
						$target = $args['target'];
						
						$reltype = $args['relationshipType'];
						$bundles = caGetOption('bundles', $args, [], ['castTo' => 'array']);
						
						if(!($subject = self::resolveIdentifier($subject, $args['subjectIdentifier']))) {
							throw new \ServiceException(_t('Invalid subject identifier'));
						}
						
						// Check privs
						if (!$subject->isSaveable($u)) {
							throw new \ServiceException(_t('Cannot access subject'));
						}
						
						// effective_date set?
						$effective_date = \GraphQLServices\Helpers\Edit\extractValueFromBundles($bundles, ['effective_date']);
						
						if(!($rel = $subject->addRelationship($target, $args['targetIdentifier'], $reltype, $effective_date))) {
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Could not create relationship: %1', join('; ', $subject->getErrors())),
								'bundle' => 'GENERAL'
							];
						} elseif(sizeof($bundles) > 0) {
							//  Add interstitial data
							if (is_array($ret = self::processBundles($rel, $bundles))) {
								$errors += $ret['errors'];
								$warnings += $ret['warnings'];
							}
						}
						
						return ['table' => is_object($rel) ? $rel->tableName() : null, 'id' => is_object($rel) ?  $rel->getPrimaryKey() : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings];
					}
				],
				'editRelationship' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Edit a relationship between two records'),
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
							'defaultValue' => null,
							'description' => _t('Relationship id')
						],
						[
							'name' => 'subject',
							'type' => Type::string(),
							'description' => _t('Subject table name. (Eg. ca_objects)')
						],
						[
							'name' => 'subjectIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'target',
							'type' => Type::string(),
							'description' => _t('Target table name. (Eg. ca_objects)')
						],
						[
							'name' => 'targetIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'relationshipType',
							'type' => Type::string(),
							'description' => _t('Relationship type code.')
						],
						[
							'name' => 'bundles',
							'type' => Type::listOf(EditSchema::get('Bundle')),
							'description' => _t('Bundles to edit')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$s = $t = null;				
						$subject = $args['subject'];
						$target = $args['target'];
						$target_identifier = $args['targetIdentifier'];
						$rel_type = $args['relationshipType'];
						$bundles = caGetOption('bundles', $args, [], ['castTo' => 'array']);
						
						// effective_date set?
						$effective_date = \GraphQLServices\Helpers\Edit\extractValueFromBundles($bundles, ['effective_date']);
						
						// rel type set?
						$new_rel_type = \GraphQLServices\Helpers\Edit\extractValueFromBundles($bundles, ['relationship_type']);
						
						if(!($s = self::resolveIdentifier($subject, $args['subjectIdentifier']))) {
							throw new \ServiceException(_t('Subject does not exist'));
						}
						if (!$s->isSaveable($u)) {
							throw new \ServiceException(_t('Subject is not accessible'));
						}
						
						if(!($rel_id = $args['id'])) {		
							if(!($t = self::resolveIdentifier($target, $args['targetIdentifier']))) {
								throw new \ServiceException(_t('Target does not exist'));
							}
							
							if ($rel = \GraphQLServices\Helpers\Edit\getRelationship($u, $s, $t, $rel_type)) {
								$rel_id = $rel->getPrimaryKey();
							}
						} 
						if(!$rel_id) {
							throw new \ServiceException(_t('Relationship does not exist'));
						}
						
						if(!($rel = $s->editRelationship($target, $rel_id, $target_identifier, $new_rel_type, $effective_date))) {
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Could not edit relationship: %1', join('; ', $s->getErrors())),
								'bundle' => 'GENERAL'
							];		
						} elseif(sizeof($bundles) > 0) {
							//  Edit interstitial data
							if (is_array($ret = self::processBundles($rel, $bundles))) {
								$errors += $ret['errors'];
								$warnings += $ret['warnings'];
							}
						}
						
						return ['table' => is_object($rel) ? $rel->tableName() : null, 'id' => is_object($rel) ?  $rel->getPrimaryKey() : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings];
					}
				],
				'deleteRelationship' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Delete relationship'),
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
							'defaultValue' => null,
							'description' => _t('Relationship id')
						],
						[
							'name' => 'subject',
							'type' => Type::string(),
							'description' => _t('Subject table name. (Eg. ca_objects)')
						],
						[
							'name' => 'subjectIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'target',
							'type' => Type::string(),
							'description' => _t('Target table name. (Eg. ca_objects)')
						],
						[
							'name' => 'targetIdentifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'relationshipType',
							'type' => Type::string(),
							'description' => _t('Relationship type code.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$rel_type = $s = $t = null;				
						$subject = $args['subject'];
						$target = $args['target'];
						
						
						if(!($s = self::resolveIdentifier($subject, $args['subjectIdentifier']))) {
							throw new \ServiceException(_t('Invalid subject identifier'));
						}
						if (!$s->isSaveable($u)) {
							throw new \ServiceException(_t('Subject is not accessible'));
						}
						
						if(!($rel_id = $args['id'])) {		
							$rel_type = $args['relationshipType'];
							
							if(!($t = self::resolveIdentifier($target, $args['targetIdentifier']))) {
								throw new \ServiceException(_t('Invalid target identifier'));
							}
							
							if($rel = \GraphQLServices\Helpers\Edit\getRelationship($u, $s, $t, $rel_type)) {
								$rel_id = $rel->getPrimaryKey();
							}
						} 
						
						if (!$rel_id) {
							throw new \ServiceException(_t('Relationship does not exist'));
						}
						
						if(!$s->removeRelationship($target, $rel_id)) {							
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Could not delete relationship: %1', join('; ', $s->getErrors())),
								'bundle' => 'GENERAL'
							];
						}
						
						return ['table' => is_object($s) ? $s->tableName() : null, 'id' => is_object($s) ?  $s->getPrimaryKey() : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings];
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
	private static function processBundles(\BaseModel $instance, array $bundles) : array {
		$label_instance = $instance->getLabelTableInstance();
		
		$errors = $warnings = [];
		foreach($bundles as $b) {
			$id = $b['id'] ?? null;
			$delete = isset($b['delete']) ? (bool)$b['delete'] : false;
			$replace = isset($b['replace']) ? (bool)$b['replace'] : false;
			$bundle_name = $b['name'];
			
			if(!strlen($bundle_name)) { continue; }
			
			switch($bundle_name) {
				# -----------------------------------
				case 'effective_date':
				case 'relationship_type':
					// noop - handled by services
					break;
				# -----------------------------------
				case 'preferred_labels':
				case 'nonpreferred_labels':
					$label_values = [];
					
					if (isset($b['values']) && is_array($b['values']) && sizeof($b['values'])) {
						$label_fields = $instance->getLabelUIFields();
						
						foreach($b['values'] as $val) {
							if(in_array($val['name'], $label_fields)) { $label_values[$val['name']] = $val['value']; }
						}
					} elseif(isset($b['value'])) {
						$label_values[$label_instance->getDisplayField()] = $b['value'];
					}
					$locale = caGetOption('locale', $b, ca_locales::getDefaultCataloguingLocale());
					$locale_id = caGetOption('locale', $b, ca_locales::codeToID($locale));
					
					$type_id = caGetOption('type_id', $b, null);
					
					if(!$delete && $id) {
						// Edit
						$rc = $instance->editLabel($id, $label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
					} elseif($replace && !$id) {
						$rc = $instance->replaceLabel($label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
					} elseif(!$delete && !$id) {
						// Add
						$rc = $instance->addLabel($label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
					} elseif($delete && $id) {
						// Delete
						$rc = $instance->removeLabel($id);
					} elseif($delete && !$id) {
						// Delete all
						$rc = $instance->removeAllLabels(($bundle_name === 'preferred_labels') ? __CA_LABEL_TYPE_PREFERRED__ : __CA_LABEL_TYPE_NONPREFERRED__);
					} else {
						// invalid operation
						$warnings[] = warning($bundle_name, _t('Invalid operation %1 on %2', ($delete ? _t('delete') : $id ? 'edit' : 'add')));	
					}
					
					foreach($instance->errors() as $e) {
						$errors[] = [
							'code' => $e->getErrorNumber(),
							'message' => $e->getErrorDescription(),
							'bundle' => $bundle_name
						];
					}
					break;
				# -----------------------------------
				default:
					if($instance->hasField($bundle_name)) {
						$instance->set($bundle_name, $delete ? null : ((is_array($b['values']) && sizeof($b['values'])) ? array_shift($b['values']) : $b['value'] ?? null), ['allowSettingOfTypeID' => true]);
						$rc = $instance->update();
					} else {
						 // attribute
						$attr_values = [];
						if (isset($b['values']) && is_array($b['values']) && sizeof($b['values'])) {
							foreach($b['values'] as $val) {
								$attr_values[$val['name']] = $val['value'];
							}
						} elseif(isset($b['value'])) {
							$attr_values[$bundle_name] = $b['value'];
						}
						
						$locale = caGetOption('locale', $b, ca_locales::getDefaultCataloguingLocale());
						$locale_id = caGetOption('locale', $b, ca_locales::codeToID($locale));
						
						$attr_values['locale_id'] = $locale_id;
				
						if(!$delete && $id) {
							// Edit
							if($rc = $instance->editAttribute($id, $bundle_name, $attr_values)) {
								$rc = $instance->update();
							}
						} elseif($replace && !$id) {
							if($rc = $instance->replaceAttribute($attr_values, $bundle_name)) {
								$rc = $instance->update();
							}
						} elseif(!$delete && !$id) {
							// Add
							if($rc = $instance->addAttribute($attr_values, $bundle_name)) {
								$rc = $instance->update();
							}
						} elseif($delete && $id) {
							// Delete
							if($rc = $instance->removeAttribute($id)) {
								$rc = $instance->update();
							}
						} elseif($delete && !$id) {
							// Delete all
							if($rc = $instance->removeAttributes($bundle_name)) {
								$rc = $instance->update();
							}
						} else {
							// invalid operation
							$warnings[] = warning($bundle_name, _t('Invalid operation %1 on %2', ($delete ? _t('delete') : $id ? 'edit' : 'add')));
						}
					}
				
					foreach($instance->errors() as $e) {
						$errors[] = [
							'code' => $e->getErrorNumber(),
							'message' => $e->getErrorDescription(),
							'bundle' => $bundle_name
						];
					}
					break;
				# -----------------------------------
			}
		}
		return ['errors' => $errors, 'warnings' => $warnings];
	}
	# -------------------------------------------------------
}
