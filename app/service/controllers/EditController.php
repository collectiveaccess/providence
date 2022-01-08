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
require_once(__CA_APP_DIR__.'/service/helpers/ErrorHelpers.php');
require_once(__CA_APP_DIR__.'/service/helpers/SearchHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\EditSchema;
use GraphQLServices\Helpers\Edit;
use GraphQLServices\Helpers\Error;


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
							'name' => 'relationships',
							'type' => Type::listOf(EditSchema::get('SubjectRelationship')),
							'default' => null,
							'description' => _t('List of relationship to create for new record')
						],
						[
							'name' => 'replaceRelationships',
							'type' => Type::boolean(),
							'default' => false,
							'description' => 'Set to 1 to indicate all relationships are to replaced with those specified in the current request. If not set relationships are merged with existing ones.'
						],
						[
							'name' => 'insertMode',
							'type' => Type::string(),
							'default' => 'FLAT',
							'description' => _t('Insert mode: "FLAT" inserts each record separated; "HIERARCHICAL" creates a hierarchy from the list (if the specified table support hierarchies).')
						],
						[
							'name' => 'matchOn',
							'type' => Type::listOf(Type::string()),
							'default' => ['idno'],
							'description' => _t('List of fields to test for existance of record. Values can be "idno" or "preferred_labels".')
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
							'name' => 'ignoreParent',
							'type' => Type::boolean(),
							'default' => false,
							'description' => _t('Ignore record parent when looking for existing records in HIERARCHICAL insert mode.')
						],
						[
							'name' => 'match',
							'type' => EditSchema::get('MatchRecord'),
							'description' => _t('Find criteria')
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
						
						$errors = $warnings = $info =  $ids = $idnos = [];
						
						$table = $args['table'];
						if(!\Datamodel::tableExists($table)) {
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						$insert_mode = strtoupper($args['insertMode']);
						$erp = strtoupper($args['existingRecordPolicy']);
						$match_on = (is_array($args['matchOn']) && sizeof($args['matchOn'])) ? $args['matchOn'] : ['idno'];
						$ignore_type = $args['ignoreType'];
						$ignore_parent = $args['ignoreParent'];
						
						$idno_fld = \Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD');
						
						if(!$args['idno'] && $args['identifier']) { $args['idno'] = $args['identifier']; }
						
						$records = (is_array($args['records']) && sizeof($args['records'])) ? $args['records'] : [[
							'idno' => $args['idno'],
							'type' => $args['type'],
							'bundles' => $args['bundles'],
							'match' => $args['match'],
							'relationships' => $args['relationships'],
							'replaceRelationships' => $args['replaceRelationships']
						]];
						
						$c = 0;
						$last_id = null;
						foreach($records as $record) {
							$instance = null;
							
							if(!$record['idno'] && $record['identifier']) { $record['idno'] = $record['identifier']; }
							
							// Force matching to preferred labels if using serial idnos in hierarchjcal mode and 
							// matchOn is set to identifier; we need tp do this since serial idnos can't be used
							// for matching when they don't yet exist
							if(($insert_mode === 'HIERARCHICAL') && (strpos($record['idno'],'%') !== false)) {
								if(!sizeof($match_on = array_filter($match_on, function($v) { return $v !== 'idno'; }))) {
									$ignore_parent = false;
									$match_on = ['preferred_labels'];
								}
							}
							
							// Does record already exist?
							try {
								if(in_array($erp, ['SKIP', 'REPLACE', 'MERGE'])) {
									if(is_array($f = $record['match'])) {										
										if($f['restrictToTypes'] && !is_array($f['restrictToTypes'])) {
											$f['restrictToTypes'] = [$f['restrictToTypes']];
										}
										if(isset($f['search'])) {
											$s = caGetSearchInstance($table);
						
											if(is_array($f['restrictToTypes']) && sizeof($f['restrictToTypes'])) {
												$s->setTypeRestrictions($f['restrictToTypes']);
											}
				
											if(($qr = $s->search($f['search'])) && $qr->nextHit()) {
												$instance = $qr->getInstance();
												$info[] = Error\info($record['idno'], 'MATCH', _t('Record found for search on (%1)', $f['search']), 'GENERAL');
											} else {
												$info[] = Error\info($record['idno'], 'NO_MATCH', _t('No record found for search on (%1)', $f['search']), 'GENERAL');
											}
										} elseif(isset($f['criteria'])) {
											if(($qr = $table::find(\GraphQLServices\Helpers\Search\convertCriteriaToFindSpec($f['criteria'], $table), ['returnAs' => 'searchResult', 'allowWildcards' => true, 'restrictToTypes' => $f['restrictToTypes']])) && $qr->nextHit()) {
												$info[] = Error\info($record['idno'], 'MATCH', _t('Record found for search using criteria (%1)', json_encode($f['criteria'])), 'GENERAL');
												$instance = $qr->getInstance();
											} else {
												$info[] = Error\info($record['idno'], 'NO_MATCH', _t('No record found for search using criteria (%1)', json_encode($f['criteria'])), 'GENERAL');
											}
										}
									} else {
										foreach($match_on as $m) {
											try {
												switch($m) {
													case 'idno':
														if($instance = (in_array($erp, ['SKIP', 'REPLACE', 'MERGE'])) ? self::resolveIdentifier($table, $record['idno'], $ignore_type ? null : $record['type'], ['idnoOnly' => true, 'list' => $args['list'], 'parent_id' => (!$ignore_parent && ($insert_mode === 'HIERARCHICAL')) ? $last_id : null]) : null) {
															$info[] = Error\info($record['idno'], 'MATCH', _t('Record found for match on (%1)', $m), 'GENERAL');
															break(2);
														}
														break;
													case 'preferred_labels':
														$label_values = Edit\extractLabelValueFromBundles($table, $record['bundles']);
														if($instance = self::resolveLabel($table, $label_values, $ignore_type ? null : $record['type'], ['list' => $args['list'], 'parent_id' => (!$ignore_parent && ($insert_mode === 'HIERARCHICAL')) ? $last_id : null])) {
															$info[] = Error\info($record['idno'], 'MATCH', _t('Record found for match on (%1)', $m), 'GENERAL');
															break(2);
														}
														break;
													default:
														if ($m) {
															$b = array_shift(array_filter($record['bundles'], function($v) use ($m) {
																return ($v['name'] === $m);
															}));
															$criteria = [$m => $b['value']];
															if(!$ignore_parent && ($insert_mode === 'HIERARCHICAL') && $last_id) { $criteria['parent_id'] = $last_id; }
															if($instance = $table::findAsInstance($criteria)) {
																$info[] = Error\info($b['value'], 'MATCH', _t('Record found for match on (%1) with values(%2)', $m, $b['value']), 'GENERAL');
																break(2);
															}
														}
														break;
												}
											} catch(\ServiceException $e) {
												// No matching record
											}
										}
										if(!$instance) {
											$info[] = Error\info($record['idno'], 'NO_MATCH', _t('No matching record found for match on (%1)', join(', ', $match_on)), 'GENERAL');
										}
									}
								}
								
							} catch(\ServiceException $e) {
								$instance = null;	// No matching record
							}
							
							switch($erp) {
								case 'SKIP':
									if($instance) { 
										$ids[] = $last_id = $instance->getPrimaryKey(); 
										$info[] = Error\info($record['idno'], 'SKIP', _t('Skipped record because it already exists and ERP is set to SKIP'), 'GENERAL');
										continue(2);
									}
									break;
								case 'REPLACE':
									if($instance) {
										if(!$instance->delete(true)) {
											foreach($instance->errors() as $e) {
												$errors[] = Error\error($record['idno'], Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), 'GENERAL');
											}		
										} else {
											$info[] = Error\info($record['idno'], 'REPLACE', _t('Deleted existing record because ERP is set to REPLACE'), 'GENERAL');
										}
									}
									$instance = null;
									break;
								case 'MERGE':
									// NOOP
									if($instance) {
										$info[] = Error\info($record['idno'], 'MERGE', _t('Will merge new data with existing record because ERP is set to MERGE'), 'GENERAL');
									}
									break;
								case 'IGNORE':
									// NOOP
									if($instance) {
										$info[] = Error\info($record['idno'], 'IGNORE', _t('Existing record was found but will be ignored because ERP is set to IGNORE'), 'GENERAL');
									}
									break;
							}
							
							if ($instance) {
								$ret = true;
							} else {
								// Create new record
								$instance = new $table();
								$instance->set('type_id', $record['type']);	// set type first so idno is interpreted properly
								$instance->setIdnoWithTemplate($record['idno']);
								if($instance->hasField('list_id') && ($instance->primaryKey() !== 'list_id')) { 
									if(!$args['list']) { 
										// If it's not set all insert will fail, so return generic (no idno) error and bail
										$errors[] = Error\error(null, 'PARAMETER_ERROR', _t('List must be specified'), 'GENERAL');
										break;
									}
									$instance->set('list_id', $args['list']); 
								}
							
								if($insert_mode === 'HIERARCHICAL') {
									$instance->set('parent_id', $last_id);
								}
								$ret = $instance->insert(['validateAllIdnos' => true]);
							}
							if(!$ret) {
								foreach($instance->errors() as $e) {
									$errors[] = Error\error($record['idno'], Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), 'GENERAL');
								}	
							} else {
								$ids[] = $last_id = $instance->getPrimaryKey();
								$idnos[] = $instance->get($idno_fld);
								
								$ret = self::processBundles($instance, $record['bundles']);
								$errors = array_merge($errors, $ret['errors']);
								$warnings = array_merge($warnings, $ret['warnings']);
								$info = array_merge($info, $ret['info']);
								if(isset($record['relationships']) && is_array($record['relationships']) && sizeof($record['relationships'])) {
									$ret = self::processRelationships($instance, $record['relationships'], ['replace' => $record['replaceRelationships']]);
									$errors = array_merge($errors, $ret['errors']);
									$warnings = array_merge($warnings, $ret['warnings']);
									$info = array_merge($info, $ret['info']);
								}
							
								$c++;
							}
						}
						
						return ['table' => $table, 'id' => $ids, 'idno' => $idnos, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
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
						],
						[
							'name' => 'relationships',
							'type' => Type::listOf(EditSchema::get('SubjectRelationship')),
							'default' => null,
							'description' => _t('List of relationship to create for new record')
						],
						[
							'name' => 'replaceRelationships',
							'type' => Type::boolean(),
							'default' => false,
							'description' => 'Set to 1 to indicate all relationships are to replaced with those specified in the current request. If not set relationships are merged with existing ones..'
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$errors = $warnings = $info = $ids = $idnos = [];
						
						$table = $args['table'];
						if(!\Datamodel::tableExists($table)) {
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						$idno_fld = \Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD');
						
						$records = (is_array($args['records']) && sizeof($args['records'])) ? $args['records'] : [[
							'identifier' => $args['identifier'],
							'id' => $args['id'],
							'idno' => $args['idno'],
							'type' => $args['type'],
							'bundles' => $args['bundles'],
							'relationships' => $args['relationships'],
							'replaceRelationships' => $args['replaceRelationships'],
							'options' => []
						]];
						
						$c = 0;
						$opts = [];
						foreach($records as $record) {
							list($identifier, $opts) = Edit\resolveParams($record);
							if(!($instance = self::resolveIdentifier($table, $identifier, $record['type'], $opts))) {
								$errors[] = Error\error($record['idno'], 'INVALID_IDENTIFIER', _t('Invalid identifier'), 'GENERAL');
							} else {
								$ids[] = $instance->getPrimaryKey();
								$idnos[] = $instance->get($idno_fld);
								
								$ret = self::processBundles($instance, $record['bundles']);
								$errors = array_merge($errors, $ret['errors']);
								$warnings = array_merge($warnings, $ret['warnings']);
								$info = array_merge($info, $ret['info']);
								
								if(isset($record['relationships']) && is_array($record['relationships']) && sizeof($record['relationships'])) {
									$ret = self::processRelationships($instance, $record['relationships'], ['replace' => $record['replaceRelationships']]);
									$errors = array_merge($errors, $ret['errors']);
									$warnings = array_merge($warnings, $ret['warnings']);
									$info = array_merge($info, $ret['info']);
								}
								
								$c++;
							}
						}
						
						return ['table' => $table, 'id' => $ids, 'idno' => $idnos, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
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
						
						$errors = $warnings = $info = [];
						
						$table = $args['table'];
						if(!\Datamodel::tableExists($table)) {
							throw new \ServiceException(_t('Invalid table: %1', $table));
						}
						
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
									$errors[] = Error\error($identifier, 'INVALID_IDENTIFIER', _t('Invalid identifier'), 'GENERAL'); 
								} elseif(!($rc = $instance->delete(true))) {
									foreach($instance->errors() as $e) {
										$errors[] = Error\error($identifier, Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), null);
									}
								} else {
									$c++;
								}
							} catch (ServiceException $e) {
								$errors[] = Error\error($identifier, 'INVALID_IDENTIFIER', $e->getErrorDescription(), 'GENERAL'); 
							}
						}
						
						return ['table' => $table, 'id' => null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
					}
				],
				'truncate' => [
					'type' => EditSchema::get('EditResult'),
					'description' => _t('Truncate a table, removing all records or records created from a date range.'),
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
							'name' => 'date',
							'type' => Type::string(),
							'description' => _t('Limit truncation to rows with modification dates within the specified range. Date can be any parseable date expression.')
						],
						[
							'name' => 'type',
							'type' => Type::string(),
							'description' => _t('Type code to limit truncation of records to.')
						],
						[
							'name' => 'types',
							'type' => Type::listOf(Type::string()),
							'description' => _t('Type codes to limit truncation of records to.')
						],
						[
							'name' => 'fast',
							'type' => Type::boolean(),
							'description' => _t('Delete records quickly, bypassing log and search index updates.')
						],
						[
							'name' => 'list',
							'type' => Type::string(),
							'description' => _t('Delete all items from specified list. Implies table = ca_list_items. If set table option is ignored.')
						],
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						if(!$u->canDoAction('can_truncate_tables_via_graphql')) {
							throw new \ServiceException(_t('Access denied'));
						}
						
						$errors = $warnings = $info = [];
						
						if($list = $args['list']) {
							$table = 'ca_list_items';
							$qr = $table::find(['list_id' => $list], ['modified' => $args['date'], 'restrictToTypes' => (isset($args['types']) && is_array($args['types']) && sizeof($args['types'])) ? $args['types'] : ($args['type'] ?? [$args['type']]), 'returnAs' => 'searchResult']);
						} else {
							$table = $args['table'];
							if(!\Datamodel::tableExists($table)) {
								throw new \ServiceException(_t('Invalid table: %1', $table));
							}
							$qr = $table::find('*', ['modified' => $args['date'], 'restrictToTypes' => (isset($args['types']) && is_array($args['types']) && sizeof($args['types'])) ? $args['types'] : ($args['type'] ?? [$args['type']]), 'returnAs' => 'searchResult']);
						}
						$c = 0;
						if($qr && ($qr->numHits() > 0)) {
							$instance = Datamodel::getInstance($table, true);
							$pk = $instance->primaryKey();
							
							$hier_root_restriction_sql = in_array($instance->getHierarchyType(), [__CA_HIER_TYPE_SIMPLE_MONO__, __CA_HIER_TYPE_MULTI_MONO__]);
							
							if((bool)$args['fast'] && Datamodel::getFieldNum($table, 'deleted')) {
								$db = new Db();
										
								try {
									if($args['date'] || $args['types'] || $list) {
										$db->query("UPDATE {$table} SET deleted = 1 WHERE {$pk} IN (?)".($hier_root_restriction_sql ? " AND parent_id IS NOT NULL" : ''), [$qr->getAllFieldValues("{$table}.{$pk}")]);
									} else {
										$db->query("UPDATE {$table} SET deleted = 1".($hier_root_restriction_sql ? " WHERE parent_id IS NOT NULL" : ''));
									}
									$c = $qr->numHits();
								} catch (\DatabaseException $e) {
									$errors[] = Error\error($identifier, Error\toGraphQLError($e->getNumber()), $e->getMessage(), null);
								}						
							} else {
								while($qr->nextHit()) {
									$instance = $qr->getInstance();
									if($hier_root_restriction_sql && !$instance->get('parent_id')) { continue; }
									if(!$instance->delete(true)) {
										foreach($instance->errors() as $e) {
											$errors[] = Error\error(null, Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), null);
										}
									} else {
										$c++;
									}
								}
							}
							if($table === 'ca_list_items') {
								ExternalCache::flush('listItems');
							}
						}
						
						return ['table' => $table, 'id' => null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
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
						
						return array_shift(self::processFullyQualifiedRelationships($u,  [$args], []));
					}
				],
				'addRelationships' => [
					'type' => Type::listOf(EditSchema::get('EditResult')),
					'description' => _t('Add multiple relationships between records'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'relationships',
							'type' => Type::listOf(EditSchema::get('Relationship')),
							'description' => _t('List of fully specified relationships')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						return self::processFullyQualifiedRelationships($u,  $args['relationships'], []);
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
						
						return array_shift(self::processFullyQualifiedRelationships($u,  [$args], []));
					}
				],
				'editRelationships' => [
					'type' => Type::listOf(EditSchema::get('EditResult')),
					'description' => _t('Add multiple relationships between records'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'relationships',
							'type' => Type::listOf(EditSchema::get('Relationship')),
							'description' => _t('List of fully specified relationships')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						return self::processFullyQualifiedRelationships($u,  $args['relationships'], []);
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
						
						return array_shift(self::processFullyQualifiedRelationshipDeletes($u, [$args]));
					}
				],
				'deleteRelationships' => [
					'type' => Type::listOf(EditSchema::get('EditResult')),
					'description' => _t('Add multiple relationships between records'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'relationships',
							'type' => Type::listOf(EditSchema::get('Relationship')),
							'description' => _t('List of fully specified relationships')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						return self::processFullyQualifiedRelationshipDeletes($u,  $args['relationships'], []);
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
						
						$errors = $warnings = $info = [];
						
						$rel_types = $s = $t = null;				
						$subject = $args['subject'];
						$target = $args['target'];
						
						
						list($subject_identifier, $opts) = Edit\resolveParams($args, 'subject');
						if(!($s = self::resolveIdentifier($subject, $subject_identifier, $opts))) {
							$errors[] = Error\error("{$subject_identifier}", 'INVALID_IDENTIFIER', _t('Invalid subject identifier'), 'GENERAL');
						} else {
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
										foreach($s->errors() as $e) {
											$errors[] = Error\error($subject_identifier, Error\toGraphQLError($e->getErrorNumber()), _t('Could not delete relationships for relationship type %1: %2', $rel_type, $s->getErrorMessage()), 'GENERAL'); 
										}
										continue;
									} 
									$c++;
								}
							} else {
								if(!$s->removeRelationships($target)) {
									foreach($s->errors() as $e) {
										$errors[] = Error\error($subject_identifier, Error\toGraphQLError($e->getErrorNumber()), _t('Could not delete relationships: %1', $s->getErrorMessage()), 'GENERAL'); 
									}
								} else {
									$c++;
								}
							}
						}
						
						return ['table' => is_object($s) ? $s->tableName() : null, 'id' => is_object($s) ?  [$s->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
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
		$errors = $warnings = $info = [];
		$idno = $instance->get($instance->tableName().'.'.$instance->getProperty('ID_NUMBERING_ID_FIELD'));
		
		foreach($bundles as $b) {
			$id = $b['id'] ?? null;
			$delete = isset($b['delete']) ? (bool)$b['delete'] : false;
			$replace = isset($b['replace']) ? (bool)$b['replace'] : false;
			$bundle_name = $b['name'];
			
			if(!strlen($bundle_name)) { continue; }
			
			$pref_label_set = false;
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
					
					$label_values = Edit\extractLabelValueFromBundles($instance->tableName(), [$b]);
					
					$locale = caGetOption('locale', $b, ca_locales::getDefaultCataloguingLocale());
					$locale_id = caGetOption('locale', $b, ca_locales::codeToID($locale));
					
					$type_id = caGetOption('type_id', $b, null);
					
					if(!$delete && $id) {
						// Edit
						$rc = $instance->editLabel($id, $label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
						if($bundle_name === 'preferred_labels') { $pref_label_set = true; }
					} elseif($replace && !$id) {
						$rc = $instance->replaceLabel($label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
						if($bundle_name === 'preferred_labels') { $pref_label_set = true; }
					} elseif(!$delete && !$id) {
						// Add
						$rc = $instance->addLabel($label_values, $locale_id, $type_id, ($bundle_name === 'preferred_labels'));
						if($bundle_name === 'preferred_labels') { $pref_label_set = true; }
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
						$errors[] = Error\error($idno, Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), $bundle_name);
					}
					break;
				# -----------------------------------
				default:
					if($instance->hasField($bundle_name)) {
						$v = $delete ? null : ((is_array($b['values']) && sizeof($b['values'])) ? array_shift($b['values']) : $b['value'] ?? null);
						if(is_array($v)) { $v = $v['value'] ?? null; }
						$instance->set($bundle_name, $v, ['allowSettingOfTypeID' => true]);
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
						$errors[] = Error\error($idno, Error\toGraphQLError($e->getErrorNumber()), $e->getErrorDescription(), $bundle_name);
					}
					break;
				# -----------------------------------
			}
		}
		
		if(method_exists($instance, 'addLabel') && ($instance->getLabelCount(true) == 0)) {
			$locale_id = ca_locales::getDefaultCataloguingLocaleID();
			$rc = $instance->addLabel([$instance->getLabelDisplayField() => "[".caGetBlankLabelText($instance->tableName())."]"], $locale_id, null, true);
		}
		return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
	}
	# -------------------------------------------------------
	/**
	 * Process relationship data relative to a subject 
	 *
	 * @param BaseModel $instance A subject instance
	 * @param array $relationships A list of relationships, defined relative to the subject
	 * @param array $options Options include:
	 *		replace = replace existing relationships with those in $relationships. [Default is false]
	 *
	 * @return array
	 *
	 * TODO: 
	 *		1. Special handling for self-relations? (Eg. direction)
	 *		2. Options to control when relationship is edited vs. recreated
	 *
	 */
	private static function processFullyQualifiedRelationships(\ca_users $u, array $relationships, ?array $options=null) : array {
		$ret = [];
		foreach($relationships as $r) {
			$errors = $warnings = $info = [];
		
			$subject = $r['subject'];
			$target = $r['target'];
			
			$reltype = $r['relationshipType'];
			$bundles = caGetOption('bundles', $r, [], ['castTo' => 'array']);
			
			// effective_date set?
			$effective_date = Edit\extractValueFromBundles($bundles, ['effective_date']);
			
			$rel_id = $r['id'] ?? null;
			if($rel_id) {
				if($ri = Edit\getRelationshipById($u, $subject, $target, $rel_id)) {
					$rel = $ri['subject']->editRelationship($ri['target']->tableName(), $ri['rel']->getPrimaryKey(), $ri['target']->getPrimaryKey(), $reltype, $effective_date);
				
					$subject_identifier = $ri['subject']->getPrimaryKey();
					$target_identifier = $ri['target']->getPrimaryKey();
					foreach($ri['subject']->errors() as $e) {
						$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", Error\toGraphQLError($e->getErrorNumber()), _t('Could not edit relationship: %1', $e->getErrorMessage()), 'GENERAL'); 
					}
				} else {
					$errors[] = Error\error("relationship", Error\toGraphQLError(1100), _t('Could not find relationship with relation_id %1', $rel_id), 'GENERAL'); 
				}
			} else {
				list($subject_identifier, $opts) = Edit\resolveParams($r, 'subject');
				if(!($subject = self::resolveIdentifier($subject, $subject_identifier, null, $opts))) {
					$errors[] = Error\error("{$subject_identifier}", 'INVALID_IDENTIFIER', _t('Invalid subject identifier'), 'GENERAL');
				} else {
					// Check privs
					if (!$subject->isSaveable($u)) {
						throw new \ServiceException(_t('Subject is not accessible'));
					}
		
		
					$c = 0;
					list($target_identifier, $opts) = Edit\resolveParams($r, 'target');
					
					if(is_array($rel_ids = $subject->relationshipExists($target, $target_identifier, $r['relationshipType'])) && sizeof($rel_ids)) {		
						$rel_id = array_shift($rel_ids);
						$rel = $subject->editRelationship($target, $rel_id, $target_identifier, $reltype, $effective_date);
					} else {
						$rel = $subject->addRelationship($target, $target_identifier, $reltype, $effective_date, null, null, null, $opts);
					}
					if(!$rel) {
				
						foreach($subject->errors() as $e) {
							$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", Error\toGraphQLError($e->getErrorNumber()), _t('Could not create relationship: %1', $e->getErrorMessage()), 'GENERAL');
						}
					} elseif(sizeof($bundles) > 0) {
						//  Add interstitial data
						if (is_array($ret = self::processBundles($rel, $bundles))) {
							$errors = array_merge($errors, $ret['errors']);
							$warnings = array_merge($warnings, $ret['warnings']);
							$info = array_merge($info, $ret['info']);
						}
						$c++;
					}
				}
			}
			$ret[] = ['table' => is_object($rel) ? $rel->tableName() : null, 'id' => is_object($rel) ?  [$rel->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];	
		}
		return $ret;
	}
	# -------------------------------------------------------
	/**
	 * Delete relationships
	 *
	 * @param BaseModel $instance A subject instance
	 * @param array $relationships A list of relationships to delete
	 * @param array $options No options are currently supported
	 *
	 * @return array
	 */
	private static function processFullyQualifiedRelationshipDeletes(\ca_users $u, array $relationships, ?array $options=null) : array {
		$ret = [];
		foreach($relationships as $r) {
			$errors = $warnings = $info = [];
						
			$rel_type = $s = $t = null;				
			$subject = $r['subject'];
			$target = $r['target'];
		
			$rel_id = $r['id'] ?? null;
			if($rel_id) {
				if($ri = Edit\getRelationshipById($u, $subject, $target, $rel_id)) {
					$rel = $ri['subject']->removeRelationship($ri['target']->tableName(), $ri['rel']->getPrimaryKey());
				
					$subject_identifier = $ri['subject']->getPrimaryKey();
					$target_identifier = $ri['target']->getPrimaryKey();
					foreach($ri['subject']->errors() as $e) {
						$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", Error\toGraphQLError($e->getErrorNumber()), _t('Could not edit relationship: %1', $e->getErrorMessage()), 'GENERAL'); 
					}
				} else {
					$errors[] = Error\error("relationship", Error\toGraphQLError(1100), _t('Could not find relationship with relation_id %1', $rel_id), 'GENERAL'); 
				}
			} else {
				list($subject_identifier, $opts) = Edit\resolveParams($r, 'subject');
				if(!($s = self::resolveIdentifier($subject, $subject_identifier, $opts))) {
					$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", 'INVALID_IDENTIFIER', _t('Invalid subject identifier'), 'GENERAL');
				} else {
					if (!$s->isSaveable($u)) {
						throw new \ServiceException(_t('Subject is not accessible'));
					}
		
					if(!($rel_id = $r['id'])) {		
						$rel_type = $r['relationshipType'];
			
						list($target_identifier, $opts) = Edit\resolveParams($r, 'target');
						if(!($t = self::resolveIdentifier($target, $target_identifier, $opts))) {
							$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", 'INVALID_IDENTIFIER', _t('Invalid target identifier'), 'GENERAL');
						} else {
							if($rel = Edit\getRelationship($u, $s, $t, $rel_type)) {
								$rel_id = $rel->getPrimaryKey();
							}
						}
					} 
		
					if (!$rel_id) {
						$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", 'INVALID_IDENTIFIER', _t('Relationship does not exist'), 'GENERAL');
					} else {
						$c = 0;
						if(!$s->removeRelationship($target, $rel_id)) {			
							foreach($s->errors() as $e) {				
								$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", Error\toGraphQLError($e->getErrorNumber()), _t('Could not delete relationship: %1', $s->getErrorMessage()), 'GENERAL');
							}
						} else {
							$c++;
						}
					}
				}
			}
		
			$ret[] = ['table' => is_object($s) ? $s->tableName() : null, 'id' => is_object($s) ?  [$s->getPrimaryKey()] : null, 'idno' => null, 'errors' => $errors, 'warnings' => $warnings, 'info' => $info, 'changed' => $c];
		}
		return $ret;
	}
	# -------------------------------------------------------
	/**
	 * Process relationship data relative to a subject 
	 *
	 * @param BaseModel $instance A subject instance
	 * @param array $relationships A list of relationships, defined relative to the subject
	 * @param array $options Options include:
	 *		replace = replace existing relationships with those in $relationships. [Default is false]
	 *
	 * @return array
	 *
	 * TODO: 
	 *		1. Special handling for self-relations? (Eg. direction)
	 *		2. Options to control when relationship is edited vs. recreated
	 *
	 */
	private static function processRelationships(\BaseModel $instance, array $relationships, ?array $options=null) : array {
		$replace = caGetOption('replace', $options, false);
		$errors = $warnings = $info = [];
		
		$subject_identifier = $instance->get($instance->tableName().'.'.$instance->getProperty('ID_NUMBERING_ID_FIELD'));
		
		if($replace) {
			foreach(array_unique(array_map(function($v) { return $v['target']; }, $relationships)) as $t) {
				$instance->removeRelationships($t);
				$info[] = Error\info("{$subject_identifier}", 'RELS_REMOVED', _t('Relationships were removed because REPLACE is set'), 'GENERAL');
			}
		}
		
		foreach($relationships as $r) {
			$effective_date = (isset($r['bundles']) && is_array($r['bundles'])) ? Edit\extractValueFromBundles($r['bundles'], ['effective_date']) : null;
						
			$c = 0;
			$target = $r['target'];
			list($target_identifier, $opts) = Edit\resolveParams($r, 'target');
			
			if(is_array($rel_ids = $instance->relationshipExists($target, $target_identifier, $r['relationshipType'])) && sizeof($rel_ids)) {
				$info[] = Error\info("{$subject_identifier}::{$target_identifier}", 'REL_EXISTS', _t('Existing relationship was found for type (%1) and will be used', $r['relationshipType']), 'GENERAL');
				$rel_id = array_shift($rel_ids);
				$rel = $instance->editRelationship($target, $rel_id, $target_identifier, $r['relationshipType'], $effective_date, null, null, null, $opts);
			} else {
				$info[] = Error\info("{$subject_identifier}::{$target_identifier}", 'REL_CREATED', _t('New relationship was created for type (%1)', $r['relationshipType']), 'GENERAL');
				$rel = $instance->addRelationship($target, $target_identifier, $r['relationshipType'], $effective_date, null, null, null, $opts);
			}
			if(!$rel) {
				foreach($instance->errors() as $e) {
					$errors[] = Error\error("{$subject_identifier}::{$target_identifier}", Error\toGraphQLError($e->getErrorNumber()), 
						is_array($rel_ids) ? 
							_t('Could not edit relationship: %1', $e->getErrorMessage()) 
							: 
							_t('Could not create relationship: %1', $e->getErrorMessage()),
						'GENERAL'
					); 
				}
			} elseif(isset($r['bundles']) && is_array($r['bundles']) && (sizeof($r['bundles']) > 0)) {
				//  Add interstitial data
				if (is_array($ret = self::processBundles($rel, $r['bundles']))) {
					$errors += $ret['errors'];
					$warnings += $ret['warnings'];
					$info += $ret['info'];
				}
				$c++;
			}
		}
		return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
	}
	# -------------------------------------------------------
}
