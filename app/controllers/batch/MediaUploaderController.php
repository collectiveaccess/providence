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
 			
 			// Can user batch import media?
 			if (!$po_request->user->canDoAction('can_batch_import_media')) {
 				$po_response->setRedirect($po_request->config->get('error_display_url').'/n/3410?r='.urlencode($po_request->getFullUrlPath()));
 				return;
 			}
 			
 			AssetLoadManager::register('react');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates a form for specification of media import settings. The form is rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function Index($pa_values=null, $pa_options=null) {
 			AssetLoadManager::register("directoryBrowser");

 			$this->render('mediauploader/index_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * tus resume-able file upload API endpoint (see https://tus.io and https://github.com/ankitpokhrel/tus-php)
 		 */
 		public function tus(){
            $key = $this->request->getParameter('key', pString);
 		    // TODO: check if user has upload privs

 		    // Create user directory if it doesn't already exist
 		    $user_dir_path = MediaUploadManager::getMediaPathForUser($this->request->user->getUserID());
 		    if (!file_exists($user_dir_path)) { mkdir($user_dir_path); }

			// Start up server
            $server = new \TusPhp\Tus\Server('redis');  // TODO: make cache type configurable

           // $server->middleware()->add(MediaUploaderHandler::class);
            $server->setApiPath('/batch/MediaUploader/tus')->setUploadDir($user_dir_path);

            $server->event()->addListener('tus-server.upload.progress', function (\TusPhp\Events\TusEvent $event) {
                $fileMeta = $event->getFile()->details();
                $request  = $event->getRequest();
                $response = $event->getResponse();
                $key = $fileMeta['metadata']['sessionKey'];

                // ...
                if ($session = MediaUploadManager::findSession($key)) {
                    $session->set('last_activity_on', _t('now'));
                    $progress_data = $session->get('progress');
                    $progress_data[$fileMeta['file_path']]['totalSizeInBytes'] = $fileMeta['size'];
                    $progress_data[$fileMeta['file_path']]['progressInBytes'] = $fileMeta['offset'];
                    $progress_data[$fileMeta['file_path']]['complete'] = false;
                    $session->set('progress', $progress_data);
                    $session->update();
                }
            });
            $server->event()->addListener('tus-server.upload.complete', function (\TusPhp\Events\TusEvent $event) {
                $fileMeta = $event->getFile()->details();
                $request  = $event->getRequest();
                $response = $event->getResponse();
                $key = $fileMeta['metadata']['sessionKey'];

                // ...
                if ($session = MediaUploadManager::findSession($key)) {
                    $session->set('last_activity_on', _t('now'));
                    $session->set('progress_files', (int)$session->set('progress_files') + 1);
                     $progress_data = $session->get('progress');
                        $progress_data[$fileMeta['file_path']]['totalSizeInBytes'] = $fileMeta['size'];
                        $progress_data[$fileMeta['file_path']]['progressInBytes'] = $fileMeta['size'];
                        $progress_data[$fileMeta['file_path']]['complete'] = true;
                        $session->set('progress', $progress_data);
                        $session->update();
                    $session->update();
                }

				// If subdirectory
				$rel_path = $fileMeta['metadata']['relativePath'];
                if(isset($rel_path) && strlen($rel_path) && file_exists($fileMeta['file_path'])) {
                    $path = pathinfo($fileMeta['file_path'], PATHINFO_DIRNAME);
                    $name = pathinfo($fileMeta['file_path'], PATHINFO_BASENAME);

                    $rel_path_proc = [];
                    foreach(preg_split('!/!', $rel_path) as $rel_path_dir) {
                        if(strlen($rel_path_dir = preg_replace('![^A-Za-z0-9\-_]+!', '_', $rel_path_dir))) {
                            $rel_path_proc[] = $rel_path_dir;
                            mkdir("{$path}/".join("/", $rel_path_proc));
                        }
                    }
                    @rename($fileMeta['file_path'], "{$path}/".join("/", $rel_path_proc)."/{$name}");
                }

            });
            $response = $server->serve();
            $response->send();
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
 		    if ($size < 1) {
                $errors[] = _t('Invalid size');
            }
            if(sizeof($errors) === 0) {
				$session = MediaUploadManager::newSession($user_id, $num_files, $size);
				$this->view->setVar('response', ['ok' => 1, 'key' => $session->get('session_key')]);
			} else {
				$this->view->setVar('response', ['ok' => 0, 'errors' => $errors]);
			}
			$this->render('mediauploader/response_json.php');
 		}
 		# -------------------------------------------------------
        /**
         * Get recent uploads
         */
        public function recent(){
 		    $user_id = $this->request->getUserID();

 		    $recent = MediaUploadManager::getRecent(['user' => $user_id]);
 		    $this->view->setVar('response', ['ok' => 0, 'recent' => $recent]);
 		    $this->render('mediauploader/response_json.php');
        }
 		# -------------------------------------------------------
        /**
         * Mark upload session as complete
         */
        public function complete(){
            $user_id = $this->request->getUserID();
            $key = $this->request->getParameter('key', pString);

            $errors = [];

			if (!($session = MediaUploadManager::findSession($key, $user_id))) {
				 $errors[] = _t('Invalid key');
			}
            if(sizeof($errors) === 0) {
                $session->set('completed_on', _t('now'));
                $session->update();
                $this->view->setVar('response', ['ok' => 1, 'key' => $session->get('session_key'), 'completed_on' => $session->get('completed_on')]);
            } else {
                $this->view->setVar('response', ['ok' => 0, 'errors' => $errors]);
            }
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
 			
			//$this->view->setVar('screen', $this->request->getActionExtra());						// name of screen
			//$this->view->setVar('result_context', $this->getResultContext());
			
 			return $this->render('mediauploader/widget_media_uploader_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
