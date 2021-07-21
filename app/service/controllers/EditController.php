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
						],
						[
							'name' => 'records',
							'type' => Type::listOf(EditSchema::get('Record')),
							'default' => null,
							'description' => _t('List of records to insert')
						],
						[
							'name' => 'insertMode',
							'type' => Type::string(),
							'default' => 'FLAT',
							'description' => _t('Insert mode: "FLAT" inserts each record separated; "HIERARCHICAL" creates a hierarchy from the list (if the specified table support hierarchies).')
						],
						[
							'name' => 'existingRecordPolicy',
							'type' => Type::string(),
							'default' => 'SKIP',
							'description' => _t('Policy if record with same identifier already exists. Values are: IGNORE (ignore existing records, REPLACE (delete existing and create new), MERGE (execute as edit), SKIP (do not perform add).')
						],
						[
							'name' => 'ignoreType',
							'type' => Type::boolean(),
							'default' => false,
							'description' => _t('Ignore record type when looking for existing records.')
						],
						[
							'name' => 'list',
							'type' => Type::string(),
							'default' => false,
							'description' => _t('List to add records to (when inserting list items.')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = $ids = $idnos = [];
						
						$table = $args['table'];
						$insert_mode = strtoupper($args['insertMode']);
						$erp = strtoupper($args['existingRecordPolicy']);
						$ignoreType = $args['ignoreType'];
						
						$idno_fld = \Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD');
						
						if(!$args['idno'] && $args['identifier']) { $args['idno'] = $args['identifier']; }
						
						$records = (is_array($args['records']) && sizeof($args['records'])) ? $args['records'] : [[
							'idno' => $args['idno'],
							'type' => $args['type'],
							'bundles' => $args['bundles']
						]];
						
						$c = 0;
						$last_id = null;
						foreach($records as $record) {
							if(!$record['idno'] && $record['identifier']) { $record['idno'] = $record['identifier']; }
							// Does record already exist?
							try {
								$instance = (in_array($erp, ['SKIP', 'REPLACE', 'MERGE'])) ? self::resolveIdentifier($table, $record['idno'], $ignoreType ? null : $record['type'], ['idnoOnly' => true, 'list' => $args['list']]) : null;
							} catch(\ServiceException $e) {
								$instance = null;	// No matching record
							}
							
							switch($erp) {
								case 'SKIP':
									if($instance) { 
										$last_id = $instance->getPrimaryKey(); 
										continue(2);
									}
									break;
								case 'REPLACE':
									if($instance && !$instance->delete(true)) {
										foreach($instance->errors() as $e) {
											$errors[] = [
												'code' => $e->getErrorNumber(),
												'message' => $e->getErrorDescription(),
												'bundle' => 'GENERAL'
											];
										}		
									}
									$instance = null;
									break;
								case 'MERGE':
									// NOOP
									break;
								case 'IGNORE':
									// NOOP
									break;
							}
							
							if ($instance) {
								$ret = true;
							} else {
								// Create new record
								$instance = new $table();
								$instance->set($idno_fld, $record['idno']);
								$instance->set('type_id', $record['type']);
								if($instance->hasField('list_id') && ($instance->primaryKey() !== 'list_id')) { 
									if(!$args['list']) { throw new \ServiceException(_t('List must be specified')); }
									$instance->set('list_id', $args['list']); 
								}
							
								if($insert_mode === 'HIERARCHICAL') {
									$instance->set('parent_id', $last_id);
								}
								$ret = $instance->insert(['validateAllIdnos' => true]);
							}
							if(!$ret) {
								foreach($instance->errors() as $e) {
									$errors[] = [
										'code' => $e->getErrorNumber(),
										'message' => $e->getErrorDescription(),
										'bundle' => 'GENERAL'
									];
								}	
							} else {
								$ids[] = $last_id = $instance->getPrimaryKey();
								$idnos[] = $instance->get($idno_fld);
								
								$ret = self::processBundles($instance, $record['bundles']);
								$errors += $ret['errors'];
								$warnings += $ret['warnings'];
							
								$c++;
							}
						}
						
						return ['table' => $table, 'id' => $ids, 'idno' => $idnos, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('Numeric database id value of record to edit.')
						],
						[
							'name' => 'idno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record to edit.')
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
						],
						[
							'name' => 'records',
							'type' => Type::listOf(EditSchema::get('Record')),
							'description' => _t('List of records to edit')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = $ids = $idnos = [];
						
						$table = $args['table'];
						$idno_fld = \Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD');
						
						$records = (is_array($args['records']) && sizeof($args['records'])) ? $args['records'] : [[
							'identifier' => $args['identifier'],
							'id' => $args['id'],
							'idno' => $args['idno'],
							'type' => $args['type'],
							'bundles' => $args['bundles'],
							'options' => $opts
						]];
						
						$c = 0;
						$opts = [];
						foreach($records as $record) {
							list($identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($record);
							if(!($instance = self::resolveIdentifier($table, $identifier, $record['type'], $opts))) {
								$errors[] = [
									'code' => 100,	// TODO: real number?
									'message' => _t('Invalid identifier'),
									'bundle' => 'GENERAL'
								];
							} else {
								$ids[] = $instance->getPrimaryKey();
								$idnos[] = $instance->get($idno_fld);
								
								$ret = self::processBundles($instance, $record['bundles']);
								$errors += $ret['errors'];
								$warnings += $ret['warnings'];
								$c++;
							}
						}
						
						return ['table' => $table, 'id' => $ids, 'idno' => $idnos, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('Numeric database id value of record to edit.')
						],
						[
							'name' => 'ids',
							'type' => Type::listOf(Type::int()),
							'description' => _t('Numeric database id value of record to edit.')
						],
						[
							'name' => 'idno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record to edit.')
						],
						[
							'name' => 'idnos',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Alphanumeric idno value of record to edit.')
						],
						[
							'name' => 'identifier',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value or numeric database id of record to delete.')
						],
						[
							'name' => 'identifiers',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Alphanumeric idno value or numeric database id of record to delete.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$table = $args['table'];
						
						$opts = [];
						$identifiers = [];
						
						if(isset($args['identifiers']) && is_array($args['identifiers']) && sizeof($args['identifiers'])) {
							$identifiers = $args['identifiers'];
						} elseif(isset($args['identifier']) && (strlen($args['identifier']) > 0)) {
							$identifiers[] = $args['identifier'];
						} elseif(isset($args['ids']) && is_array($args['ids']) && sizeof($args['ids'])) {
							$identifiers = $args['ids'];
							$opts['primaryKeyOnly'] = true;
						} elseif(isset($args['idnos']) && is_array($args['idnos']) && sizeof($args['idnos'])) {
							$identifiers = $args['idnos'];
							$opts['idnoOnly'] = true;
						} elseif(isset($args['id']) && ($args['id'] > 0)) {
							$identifiers[] = $args['id'];
							$opts['primaryKeyOnly'] = true;
						} elseif(isset($args['idno']) && (strlen($args['idno']) > 0)) {
							$identifiers[] = $args['idno'];
							$opts['idnoOnly'] = true;
						}
						
						$c = 0;
						foreach($identifiers as $identifier) {
							try {
								if(!($instance = self::resolveIdentifier($table, $identifier, null, $opts))) {
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
								} else {
									$c++;
								}
							} catch (ServiceException $e) {
								$errors[] = [
									'code' => 284,
									'message' => $e->getMessage(),
									'bundle' => $bundle_name
								];
							}
						}
						
						return ['table' => $table, 'id' => null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
							'name' => 'subjectId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'subjectIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value to use as relationship subject.')
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
							'name' => 'targetId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'targetIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record to use as relationship target.')
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
						
						list($subject_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'subject');
						if(!($subject = self::resolveIdentifier($subject, $subject_identifier, null, $opts))) {
							throw new \ServiceException(_t('Invalid subject identifier'));
						}
						
						// Check privs
						if (!$subject->isSaveable($u)) {
							throw new \ServiceException(_t('Cannot access subject'));
						}
						
						// effective_date set?
						$effective_date = \GraphQLServices\Helpers\Edit\extractValueFromBundles($bundles, ['effective_date']);
						
						$c = 0;
						list($target_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'target');
						if(!($rel = $subject->addRelationship($target, $target_identifier, $reltype, $effective_date, null, null, null, $opts))) {
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
							$c++;
						}
						
						return ['table' => is_object($rel) ? $rel->tableName() : null, 'id' => is_object($rel) ?  [$rel->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
							'name' => 'subjectId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'subjectIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value to use as relationship subject.')
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
							'name' => 'targetId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'targetIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record to use as relationship target.')
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
						
						list($subject_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'subject');
						if(!($s = self::resolveIdentifier($subject, $subject_identifier, $opts))) {
							throw new \ServiceException(_t('Subject does not exist'));
						}
						if (!$s->isSaveable($u)) {
							throw new \ServiceException(_t('Subject is not accessible'));
						}
						
						if(!($rel_id = $args['id'])) {		
							list($target_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'target');
							if(!($t = self::resolveIdentifier($target, $target_identifier, $opts))) {
								throw new \ServiceException(_t('Target does not exist'));
							}
							
							if ($rel = \GraphQLServices\Helpers\Edit\getRelationship($u, $s, $t, $rel_type)) {
								$rel_id = $rel->getPrimaryKey();
							}
						} 
						if(!$rel_id) {
							throw new \ServiceException(_t('Relationship does not exist'));
						}
						
						$c = 0;
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
							$c++;
						}
						
						return ['table' => is_object($rel) ? $rel->tableName() : null, 'id' => is_object($rel) ?  [$rel->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
							'name' => 'subjectId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'subjectIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value to use as relationship subject.')
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
							'name' => 'targetId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship target.')
						],
						[
							'name' => 'targetIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value of record to use as relationship target.')
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
						
						list($subject_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'subject');
						if(!($s = self::resolveIdentifier($subject, $subject_identifier, $opts))) {
							throw new \ServiceException(_t('Invalid subject identifier'));
						}
						if (!$s->isSaveable($u)) {
							throw new \ServiceException(_t('Subject is not accessible'));
						}
						
						if(!($rel_id = $args['id'])) {		
							$rel_type = $args['relationshipType'];
							
							list($target_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'target');
							if(!($t = self::resolveIdentifier($target, $target_identifier, $opts))) {
								throw new \ServiceException(_t('Invalid target identifier'));
							}
							
							if($rel = \GraphQLServices\Helpers\Edit\getRelationship($u, $s, $t, $rel_type)) {
								$rel_id = $rel->getPrimaryKey();
							}
						} 
						
						if (!$rel_id) {
							throw new \ServiceException(_t('Relationship does not exist'));
						}
						
						$c = 0;
						if(!$s->removeRelationship($target, $rel_id)) {							
							$errors[] = [
								'code' => 100,	// TODO: real number?
								'message' => _t('Could not delete relationship: %1', join('; ', $s->getErrors())),
								'bundle' => 'GENERAL'
							];
						} else {
							$c++;
						}
						
						return ['table' => is_object($s) ? $s->tableName() : null, 'id' => is_object($s) ?  [$s->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
					}
				],
				'deleteAllRelationships' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Delete all relationships on record to a target table. If one or more relationship types are specified then only relationships with those types will be removed.'),
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
							'name' => 'subjectId',
							'type' => Type::int(),
							'description' => _t('Numeric database id of record to use as relationship subject.')
						],
						[
							'name' => 'subjectIdno',
							'type' => Type::string(),
							'description' => _t('Alphanumeric idno value to use as relationship subject.')
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
							'name' => 'relationshipType',
							'type' => Type::string(),
							'description' => _t('Relationship type code.')
						],
						[
							'name' => 'relationshipTypes',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of relationship type codes.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = [];
						
						$rel_types = $s = $t = null;				
						$subject = $args['subject'];
						$target = $args['target'];
						
						
						list($subject_identifier, $opts) = \GraphQLServices\Helpers\Edit\resolveParams($args, 'subject');
						if(!($s = self::resolveIdentifier($subject, $subject_identifier, $opts))) {
							throw new \ServiceException(_t('Invalid subject identifier'));
						}
						if (!$s->isSaveable($u)) {
							throw new \ServiceException(_t('Subject is not accessible'));
						}
						
						if(isset($args['relationshipTypes']) && is_array($args['relationshipTypes']) && sizeof($$args['relationshipTypes'])) {
							$rel_types = $args['relationshipTypes'];
						} elseif(isset($args['relationshipType']) && $args['relationshipType']) {
							$rel_types = [$args['relationshipType']];
						} else {
							$rel_types = null;
						}
							
						$c = 0;
						if(is_array($rel_types) && sizeof($rel_types)) {
							foreach($rel_types as $rel_type) {
								if(!$s->removeRelationships($target, $rel_type)) {
									$errors[] = [
										'code' => 100,	// TODO: real number?
										'message' => _t('Could not delete relationships for relationship type %1: %2', $rel_type, join('; ', $s->getErrors())),
										'bundle' => 'GENERAL'
									];
									continue;
								} 
								$c++;
							}
						} else {
							if(!$s->removeRelationships($target)) {
								$errors[] = [
									'code' => 100,	// TODO: real number?
									'message' => _t('Could not delete relationships: %1', join('; ', $s->getErrors())),
									'bundle' => 'GENERAL'
								];
							} else {
								$c++;
							}
						}
						
						return ['table' => is_object($s) ? $s->tableName() : null, 'id' => is_object($s) ?  [$s->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'changed' => $c];
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
						if($label_instance->tableName() === 'ca_list_item_labels') {
							$label_values['name_plural'] = $b['value'];
						}
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
							if($rc = $instance->replaceAttribute($attr_values, $bundle_name, null, ['showRepeatCountErrors' => true])) {
								$rc = $instance->update();
							}
						} elseif(!$delete && !$id) {
							// Add
							if($rc = $instance->addAttribute($attr_values, $bundle_name, null, ['showRepeatCountErrors' => true])) {
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
