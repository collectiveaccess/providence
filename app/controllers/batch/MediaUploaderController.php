<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUploaderController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
 
 	class MediaUploaderController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			AssetLoadManager::register('react');
 			
 			if(!$po_request->config->get('media_uploader_enabled')) { 
 				throw new ApplicationException(_t('Media uploader is not enabled'));
 			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates a form for specification of media import settings. The form is rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function index($pa_values=null, $pa_options=null) {
 			AssetLoadManager::register("directoryBrowser");

 			$this->render('mediauploader/index_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates admin console
 		 *
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function admin($pa_options=null) {
        	// Check that user has privs to use uploader admin console
 		    $this->request->getUser()->canDoAction('is_media_uploader_administrator', ['throwException' => true]);
 		    
 			AssetLoadManager::register("directoryBrowser");

 			$this->render('mediauploader/admin_html.php');
 		}
 		# -------------------------------------------------------
 		# Services
 		# -------------------------------------------------------
 		/**
 		 * tus resume-able file upload API endpoint (see https://tus.io and https://github.com/ankitpokhrel/tus-php)
 		 */
 		public function tus(){
 			$config = Configuration::load();
            $user_id = $this->request->getUserID();

 		    $server = MediaUploadManager::getTUSServer($user_id);
            try {
            	$response = $server->serve();
            	
            	# Force protocol for urls returned by service to configured value if needed. This
            	# can be necessary when running behind a proxy that doesn't set 
				# X-Forwarded-Host and X-Forwarded-Proto headers
            	if(($force_proto_to = strtolower($config->get('media_uploader_force_protocol_to'))) && in_array($force_proto_to, ['http', 'https'], true)) {
            		$location = preg_replace('!^[A-Za-z]+:!', "{$force_proto_to}:", $response->headers->get('location'));
            		$response->headers->set('location', $location);
            	}
           		$response->send();
           		exit;
           	} catch(Exception $e) {
           		// Delete all files
           		$request = $server->getRequest();
           		$key = $request->header('x-session-key');

				if ($session = MediaUploadManager::findSession($key, $user_id)) {
					if(is_array($progress_data = $session->get('progress'))) {
						foreach(array_keys($progress_data) as $f) {
							@unlink($f);
						}	
					}
				}
           	
           		// Return error
           		AppController::getInstance()->removeAllPlugins();
           		http_response_code(401);
           		header("Tus-Resumable: 1.0.0");
           		$this->view->setVar('response', ['error' => $e->getMessage(), 'global' => true, 'state' => 'quota']);
           		$this->render('mediauploader/response_json.php');
           		return;
           	}
 		}
 		# -------------------------------------------------------
 		/**
 		 * Create upload session
 		 */
 		public function session(){
 		    $user_id = $this->request->getUserID();
 		    $num_files = $this->request->getParameter('n', pInteger);
 		    $size = $this->request->getParameter('size', pInteger);

 		    $errors = [];
 		    if ($num_files < 1) {
 		        $errors[] = _t('Invalid file count');
 		    }
 		    $max_num_files = (int)$this->request->config->get('media_uploader_max_files_per_session');
 		    if (($max_num_files > 0) && ($num_files > $max_num_files)) {
 		    	$errors[] = _t('A maximum of %1 files may be uploaded at once. Try again with fewer files.', $max_num_files);
 		    }
 		    if ($size < 1) {
                $errors[] = _t('Invalid size');
            }
            if(sizeof($errors) === 0) {
				$session = MediaUploadManager::newSession($user_id, $num_files, $size);
				$this->view->setVar('response', array_merge(['ok' => 1, 'key' => $session->get('session_key')], caGetUserMediaStorageUsageStats($user_id)));
			} else {
				$this->view->setVar('response', ['ok' => 0, 'errors' => $errors]);
			}
			$this->render('mediauploader/response_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Get current storage stats
 		 */
 		public function storage(){
 		    $user_id = $this->request->getUserID();
			$this->view->setVar('response', array_merge(['ok' => 1], caGetUserMediaStorageUsageStats($user_id)));
			
			$this->render('mediauploader/response_json.php');
 		}
 		# -------------------------------------------------------
        /**
         * Get recent uploads
         */
        public function recent(){
 		    $user_id = $this->request->getUserID();

 		    $recent = MediaUploadManager::getRecent(['user' => $user_id, 'limit' => 9]);
 		    $this->view->setVar('response', array_merge(['ok' => 0, 'recent' => $recent], caGetUserMediaStorageUsageStats($user_id)));
 		    $this->render('mediauploader/response_json.php');
        }
        # -------------------------------------------------------
        /**
         * Mark upload session as cancelled
         */
        public function cancel(){
            $key = $this->request->getParameter('key', pString);
            $user_id = $this->request->getUserID();

            $errors = [];
			try {
				$session = MediaUploadManager::cancelSession($key, $user_id);
				
				$this->view->setVar('response', array_merge(
					[
						'ok' => 1, 
						'key' => $session->get('session_key'), 
						'completed_on' => $session->get('completed_on'),
						'cancelled' => 1
					], 
					caGetUserMediaStorageUsageStats($user_id)
				));
			} catch(Exception $e) {
				$this->view->setVar('response', ['ok' => 0, 'errors' => [$e->getMessage()]]);
			}
            $this->render('mediauploader/response_json.php');
        }
 		# -------------------------------------------------------
        /**
         * Mark upload session as complete
         */
        public function complete(){
            $key = $this->request->getParameter('key', pString);
            $user_id = $this->request->getUserID();

            $errors = [];
			try {
				$session = MediaUploadManager::completeSession($key, $user_id);
				
				$this->view->setVar('response', array_merge(
					[
						'ok' => 1, 
						'key' => $session->get('session_key'), 
						'completed_on' => $session->get('completed_on')
					], 
					caGetUserMediaStorageUsageStats($user_id)
				));
			} catch(Exception $e) {
				$this->view->setVar('response', ['ok' => 0, 'errors' => [$e->getMessage()]]);
			}
            $this->render('mediauploader/response_json.php');
        }
        # -------------------------------------------------------
        /**
         * Log data for admin console
         */
        public function logdata(){
        	// Check that user has privs to use uploader admin console
 		    $this->request->getUser()->canDoAction('is_media_uploader_administrator', ['throwException' => true]);
        	
        	$user = $this->request->getParameter('user', pString);
        	$status = $this->request->getParameter('status', pString);
        	$date = $this->request->getParameter('date', pString);

			if(strlen($date) || strlen($status) || strlen($user)) {
				// Handling of filtered query goes here
				$recent = MediaUploadManager::getLog(['date' => $date, 'status' => $status, 'user' => $user]);
			} else {
				// No params, so show recent uploads by default
 		    	$recent = MediaUploadManager::getLog([]);
 		    }
 		    $this->view->setVar('response', ['ok' => 0, 'userList' => array_values(MediaUploadManager::getUserList()), 'data' => $recent]);
 		    $this->render('mediauploader/response_json.php');
        }
		# ------------------------------------------------------------------
 		# Sidebar info handler
 		# ------------------------------------------------------------------
 		/**
 		 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
 		 *
 		 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
 		 */
 		public function info($pa_parameters) {
 			return $this->render('mediauploader/widget_media_uploader_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
