<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/MediaAnalysisController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Service/BaseServiceController.php');


class MediaAnalysisController extends BaseServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($request, $response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function analyze() {
		$request = $this->getRequest();
		$t_instance = null;
		try {
			if(!$auth = $this->_setup()) { throw new ApplicationException(_t('Could not authenticate')); }
			$user_id = $auth['user_id'];
			$path = $auth['file_path'];
			
			$tq = new TaskQueue();
			
			$errors = $notices = $copied = [];
			$is_primary = true;
			
			$task_ids = [];
			if(is_array($_FILES) && is_array($_FILES['file'])) {
				$excluded_extensions = $request->getAppConfig()->getList('media_uploader_exclude_file_extensions');
				
				foreach($_FILES['file'] as $k => $v) {
					if(!is_array($v)) {
						$_FILES['file'][$k] = [$v];
					}
				}
			
				$entity_key = $this->request->getParameter('entity_id', pString);
				foreach($_FILES['file']['name'] as $i => $n) {
					$name = preg_replace("![^A-Z0-9_\-\.]+!i", "_", $n);
					$ext = pathinfo($name, PATHINFO_EXTENSION);
					
					$rpath = preg_replace("!^".__CA_BASE_DIR__."!i", "", "{$path}/{$name}");
					if(in_array($ext, $excluded_extensions, true)) {
						$errors[$rpath] = _t('File extension "%1" not allowed', $ext);
						continue;
					}
					$tmp_name = $_FILES['file']['tmp_name'][$i];
					if(!copy($tmp_name, "{$path}/{$name}")) {
						$errors[$rpath] = _t('Could not copy file "%1"', $name);
						continue;
					}
					$copied[$rpath] = filesize("{$path}/{$name}");
					
					$ret = $tq->addTask('mediaTranscription', ['SERVICE' => true, 'FILE' => "{$path}/{$name}", 'MODEL' => 'medium', 'OUTPUT_DIR' => $path], ['user_id' => $user_id, 
						'entity_key' => $entity_key ? $entity_key : $user_id.$rpath]);
					$task_ids["{$path}/{$name}"] = ['task_id' => $ret, 'entity_key' => md5($user_id.$rpath)];
				}
			}
			if(sizeof($copied) > 0) {
				$content = [
					'files' => $copied,
					'tasks' => $task_ids,
					'notices' => $notices,
					'errors' => $errors
				];
				$this->getView()->setVar('content', $content);
				$this->render('json/json.php');
			} else {
				$this->getView()->setVar('errors', $errors);
				$this->render('json/json_error.php');
			}
			
			\CA\Process\Background::run('taskQueue');
		} catch(Exception $e) {
			$this->getView()->setVar('errors', [$e->getMessage()]);
			$this->render('json/json_error.php');
			return;
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function tasks() {
		$request = $this->getRequest();
		$hostname = $request->getAppConfig()->get('site_host');
		$url_root = $request->getAppConfig()->get('ca_url_root');
		
		try {
			if(!$auth = $this->_setup()) { throw new ApplicationException(_t('Could not authenticate')); }
			$user_id = $auth['user_id'];
			$path = $auth['file_path'];
			
			$tq = new TaskQueue();
			
			$task_info = $tq->getTasksForUser($user_id, ['handlers' => ['mediaTranscription']]);
			
			foreach($task_info as $i => $ti_by_type) {
				foreach($ti_by_type as $j => $ti) {
					if((bool)($ti['parameters']['SERVICE'] ?? false)) {
						if(($output_dir = ($ti['parameters']['OUTPUT_DIR'] ?? null))) {
							$base_url = str_replace(__CA_BASE_DIR__, '', $output_dir);
							$ti['parameters']['FILE'] = str_replace($output_dir, '', $ti['parameters']['FILE']);
							
							$dir = pathinfo($ti['parameters']['FILE'], PATHINFO_DIRNAME);
							$fn = pathinfo($ti['parameters']['FILE'], PATHINFO_FILENAME);
							$ti['file'] = "{$hostname}{$url_root}{$base_url}{$ti['parameters']['FILE']}";
							if(file_exists("{$output_dir}/{$dir}/{$fn}.vtt")) {
								$ti['vtt'] = "{$hostname}{$url_root}{$base_url}{$dir}{$fn}.vtt";
							}
							if(file_exists("{$output_dir}/{$dir}/{$fn}.json")) {
								$ti['json'] = "{$hostname}{$url_root}{$base_url}{$dir}{$fn}.json";
							}
							
							unset($ti['parameters']);
							$task_info[$i][$j] = $ti;
							continue;
						}
					}
					unset($task_info[$i][$j]);
				}
			}
			
			$content = ['tasks' => $task_info];
			
			$this->getView()->setVar('content', $content);
			$this->render('json/json.php');
		} catch(Exception $e) {
			$this->getView()->setVar('errors', [$e->getMessage()]);
			$this->render('json/json_error.php');
			return;
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function results() {
		$request = $this->getRequest();
		
		try {
			if(!$auth = $this->_setup()) { throw new ApplicationException(_t('Could not authenticate')); }
			$user_id = $auth['user_id'];
			$path = $auth['file_path'];
			
			$task_id = $request->getParameter("task_id", pInteger);
			$entity_key = $request->getParameter("entity_key", pString);
			$format = $request->getParameter("format", pString);
			
			$tq = new TaskQueue();
			
			$task_info = $tq->getTaskInfo(['user_id' => $user_id, 'handlers' => ['mediaTranscription'], 'task_id' => $task_id, 'entity_key' => $entity_key]);
			
			if(is_array($task_info)) {
				switch($format) {
					case 'vtt':
						$path = pathinfo($task_info['parameters']['FILE'], PATHINFO_DIRNAME).'/'.pathinfo($task_info['parameters']['FILE'], PATHINFO_FILENAME).'.vtt';
						
						$this->getView()->setVar('file_path', $path);
						$this->getView()->setVar('download_name', pathinfo($path, PATHINFO_BASENAME));
						$this->getView()->setVar('mimetype', 'text/vtt');
						break;
					case 'json':
						$path = pathinfo($task_info['parameters']['FILE'], PATHINFO_DIRNAME).'/'.pathinfo($task_info['parameters']['FILE'], PATHINFO_FILENAME).'.json';
						
						$this->getView()->setVar('file_path', $path);
						$this->getView()->setVar('download_name', pathinfo($path, PATHINFO_BASENAME));
						$this->getView()->setVar('mimetype', 'text/json');
						break;
				}
			}
			
			
			// $this->getView()->setVar('content', $task_info);
// 			$this->render('json/json.php');
			
			$this->render('json/download_binary.php');
		} catch(Exception $e) {
			$this->getView()->setVar('errors', [$e->getMessage()]);
			$this->render('json/json_error.php');
			return;
		}
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	private function _setup(?array $options=null) : ?array{
		$request = $this->getRequest();
		if(intval((caGetOption('pretty', $options, $request->getParameter('pretty', pInteger)))) > 0) {
			$this->getView()->setVar('pretty_print', true);
		}

		if(!strlen($jwt = \GraphQLServices\GraphQLServiceController::getBearerToken())) {
			$jwt = caGetOption('jwt', $options, $request->getParameter('jwt', pString));
		}
		
		if(!($u = \GraphQLServices\GraphQLServiceController::authenticate($jwt, ['returnAs' => 'array', 'throw' => true]))) {
			return null;
		}
		
		$user_id = $u['id'];
		if(!($path = caGetMediaUploadPathForUser($user_id)) || !is_writable($path)) {
			throw new ApplicationException(_t('Upload path does not exist or is not writeable'));
		}
		return [
			'user_id' => $user_id,
			'file_path' => $path,
			'payload' => $jwt
		];
	}
	# -------------------------------------------------------
}
