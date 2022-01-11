<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/SubmissionController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2022 Whirl-i-Gig
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
require_once(__CA_APP_DIR__.'/service/schemas/SubmissionSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\SubmissionSchema;


class SubmissionController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	#
	static $config = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
		
		if(!self::$config) { 
			self::$config = Configuration::load(__CA_CONF_DIR__.'/Submission.conf'); 
		}
	}
	
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				// ------------------------------------------------------------
				// Sessions
				// ------------------------------------------------------------
				'sessionList' => [
					'type' => SubmissionSchema::get('SubmissionSessionList'),
					'description' => _t('List of sessions for current user'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'date',
							'type' => Type::string(),
							'description' => _t('Filter by date')
						],
						[
							'name' => 'user_id',
							'type' => Type::int(),
							'description' => _t('Filter by user')
						],
						[
							'name' => 'status',
							'type' => Type::string(),
							'description' => _t('Filter by status')
						]
					],
					'resolve' => function ($rootValue, $args) {							
						global $g_request;
						if(!$args['jwt']) {
							throw new \ServiceException(_t('No JWT'));
						}
						$u = self::authenticate($args['jwt']);
						$user_id = $u->getPrimaryKey();
						
						$log_entries = MediaUploadManager::getLog(['source' => 'FORM', 'user' => $args['user_id'], 'status' => $args['status'], 'date' => $args['date']]);
						
						$processed_log = [];
						foreach($log_entries as $l) {
							$warnings = array_map(function($filename, $warnings) {
								return [
									'filename' => $filename,
									'message' => join("; ", $warnings)
								];
							}, array_keys($l['warnings']), $l['warnings']);
						
							$errors = array_map(function($filename, $errors) {
								return [
									'filename' => $filename,
									'message' => join("; ", $errors)
								];
							}, array_keys($l['errors']), $l['errors']);

							$search_url = caSearchUrl($g_request, $l['table'], 'mediaUploadSession:'.$l['session_key'], false, null, ['absolute' => true]);
							$processed_log[] = [
								'label' => $l['label'],
								'sessionKey' => $l['session_key'],
								'createdOn' => $l['created_on'],
								'lastActivityOn' => $l['created_on'],
								'completedOn' => $l['completed_on'],
								'status' => $l['status'],
								'statusDisplay' => $l['status_display'],
								'source' => $l['source'],
								'user_id' => $l['user']['user_id'],
								'username' => $l['user']['user_name'],
								'user' => trim($l['user']['fname'].' '.$l['user']['lname']),
								'email' => $l['user']['email'],
								'files' => $l['num_files'],
								'totalBytes' => $l['total_bytes'],
								'receivedBytes' => $l['received_bytes'],
								'totalSize' => $l['total_display'],
								'receivedSize' => $l['received_display'],
								'warnings' => $warnings,
								'errors' => $errors,
								'filesImported' => $l['files_imported'],
								'searchUrl' => $search_url
							];
						}
						return ['sessions' => $processed_log];
					}
				],
				//
				//
				'getSession' => [
					'type' => SubmissionSchema::get('SubmissionSessionData'),
					'description' => _t('Return data for existing session'),
					'args' => [
						[
							'name' => 'sessionKey',
							'type' => Type::string(),
							'description' => _t('Session key')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if(!$args['jwt']) {
							throw new \ServiceException(_t('No JWT'));
						}
						$u = self::authenticate($args['jwt']);
						$user_id = $u->getPrimaryKey();
						$session_key = $args['sessionKey'];
						
						$s = $this->_getSession($session_key);
						if(!$s) {
							throw new \ServiceException(_t('Invalid session key'));
						}
						
						if(!is_array($log_entries = MediaUploadManager::getLog(['sessionKey' => $session_key, 'user' => $user_id])) || !sizeof($log_entries)) {
							throw new \ServiceException(_t('Invalid session key'));
						}
						
						$fields = [
							'label' => 'label', 'session_key' => 'sessionKey', 'user_id' => 'user_id',
							'metadata' => 'formData', 'num_files' => 'files', 'total_bytes' => 'totalBytes',
							'filesUploaded' => 'filesUploaded', 'source' => 'source',
							'received_bytes' => 'receivedBytes', 'total_display' => 'totalSize', 'received_display' => 'receivedSize'
						];
						
						$data = array_shift($log_entries);
						foreach($fields as $f => $k) {
							$v = isset($data[$f]) ? $data[$f] : $s->get($f);
							unset($data[$f]);
							switch($k) {
								case 'formData':
									$v = json_encode(caUnserializeForDatabase($v), true);
									break;
								case 'filesUploaded':
									$file_list = [];
									
									$files = $s->getFileList();
									if(is_array($files)) {
										foreach($files as $path => $file_info) {
											$file_list[] = [
												'path' => $path,
												'name' => pathInfo($path, PATHINFO_FILENAME),
												'complete' => (bool)$file_info['completed_on'],
												'totalBytes' => $file_info['total_bytes'],
												'receivedBytes' => $file_info['bytes_received'],
												'totalSize' => caHumanFilesize($file_info['total_bytes']),
												'receivedSize' => caHumanFilesize($file_info['bytes_received'])
											];
										}
									}
									$data[$k] = $file_list;
									continue(2);
							}
							$data[$k] = $v;
						}
						
						return $data;
					}
				],
				//
				//
				
				'getSessionFilterValues' => [
					'type' => SubmissionSchema::get('SubmissionSessionFilterValues'),
					'description' => _t('Get list of session filter values'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if(!$args['jwt']) {
							throw new \ServiceException(_t('No JWT'));
						}
						$u = self::authenticate($args['jwt']);
						
						return ['users' => \MediaUploadManager::getUserList(), 'statuses' => \MediaUploadManager::getStatusList()];
					}
				],
				// ------------------------------------------------------------
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				'updateSessionStatus' => [
					'type' => SubmissionSchema::get('SubmissionSessionUpdateResult'),
					'description' => _t('Return data for existing session'),
					'args' => [
						[
							'name' => 'sessionKey',
							'type' => Type::string(),
							'description' => _t('Session key')
						],
						[
							'name' => 'status',
							'type' => Type::string(),
							'description' => _t('Session status')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if(!$args['jwt']) {
							throw new \ServiceException(_t('No JWT'));
						}
						$u = self::authenticate($args['jwt']);
						$session_key = $args['sessionKey'];
						$status = $args['status'];
						if(!in_array($status, ['ACCEPTED', 'REJECTED'])) {
							throw new \ServiceException(_t('Invalid status: %1', $status));
						}
						
						if(!($s = $this->_getSession($session_key))) {
							throw new \ServiceException(_t('Invalid session key: %1', $session_key));
						}
					
						$s->set('status', $status);
						if ($s->update()) {
							return ['updated' => 1];
						} else {
							throw new \ServiceException(_t('Could not update session: %1', join('; ', $s->getErrors())));
						}
						
					}
				],
				'deleteSession' => [
					'type' => SubmissionSchema::get('SubmissionSessionDeleteResult'),
					'description' => _t('Delete session'),
					'args' => [
						[
							'name' => 'sessionKey',
							'type' => Type::string(),
							'description' => _t('Session key')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if(!$args['jwt']) {
							throw new \ServiceException(_t('No JWT'));
						}
						$u = self::authenticate($args['jwt']);
						$user_id = $u->getPrimaryKey();
						$session_key = $args['sessionKey'];
						
						$s = $this->_getSession($session_key);
						
						if ($s->delete(true)) {
							return ['deleted' => 1];
						} else {
							throw new \ServiceException(_t('Could not delete session: %1', join('; ', $s->getErrors())));
						}
					}
				],
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function _getSession($session_key) {
		if(!$session_key) {
			throw new \ServiceException(_t('Empty session key'));
		}
		if(!($s = ca_media_upload_sessions::find(['session_key' => $session_key], ['returnAs' => 'firstModelInstance']))) {
			throw new \ServiceException(_t('Invalid session key'));
		}
		
		return $s;
	}
	# -------------------------------------------------------
}
