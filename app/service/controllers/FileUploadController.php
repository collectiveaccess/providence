<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/FileUploadController.php :
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


class FileUploadController extends BaseServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function upload() {
		$request = $this->getRequest();
		$t_instance = null;
		try {
			if(intval($request->getParameter('pretty', pInteger))>0) {
				$this->getView()->setVar('pretty_print', true);
			}

			if(!strlen($jwt = \GraphQLServices\GraphQLServiceController::getBearerToken())) {
				$jwt = $request->getParameter('jwt', pString);
			}
			
			if(!($u = \GraphQLServices\GraphQLServiceController::authenticate($jwt, ['returnAs' => 'array', 'throw' => true]))) {
				return null;
			}
			
			$user_id = $u['id'];
			if(!($path = caGetMediaUploadPathForUser($user_id)) || !is_writable($path)) {
				throw new ApplicationException(_t('Upload path does not exist or is not writeable'));
			}
			
			if($table = $request->getParameter('table', pString)) {
				if(!($t_instance = Datamodel::getInstance($table))) {
					throw new ApplicationException(_t('Invalid table "%1"', $table));
				}
				$id = $request->getParameter('id', pInteger);
				if(!$t_instance->load($id)) {
					throw new ApplicationException(_t('Invalid id "%1"', $id));
				}
				if(!method_exists($t_instance, 'addRepresentation')) {
					throw new ApplicationException(_t('Table does not take representations'));
				}
				
				if(!$t_instance->isSaveable(new ca_users($u['id']), 'ca_object_representations')) {
					throw new ApplicationException(_t('Access denied'));
				}
				$type_id = $request->getParameter('type_id', pString);
				$locale_id = $request->getParameter('locale_id', pString);
				$status = $request->getParameter('status', pString);
				$access = $request->getParameter('access', pString);
				$idno = $request->getParameter('idno', pString);
				$label = $request->getParameter('label', pString);
			}
			
			$errors = $notices = $copied = [];
			$is_primary = true;
			if(is_array($_FILES) && is_array($_FILES['file'])) {
				$excluded_extensions = $request->getAppConfig()->getList('media_uploader_exclude_file_extensions');
				
				foreach($_FILES['file'] as $k => $v) {
					if(!is_array($v)) {
						$_FILES['file'][$k] = [$v];
					}
				}
			
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
					
					if($t_instance) {
						if($t_instance->addRepresentation("{$path}/{$name}", $type_id, $locale_id, $status, $access, $is_primary, ['idno' => $idno, 'preferred_labels' => $label], ['original_filename' => $n])) {
							$is_primary = false;
							$notices[$rpath] = _t('Added uploaded file as representation for %1 with id %2 (%3)', $table, $id, $t_instance->get('idno'));
						} else {
							$errors[$rpath] = _t('Could not create representation for file "%1": %2', $name, join('; ', $t_instance->getErrors()));
						}
					}
				}
			}
			if(sizeof($copied) > 0) {
				$content = [
					'fileCount' => sizeof($_FILES['file']['name']),
					'files' => $copied,
					'notices' => $notices,
					'errors' => $errors
				];
				$this->getView()->setVar('content', $content);
				$this->render('json/json.php');
			} else {
				$this->getView()->setVar('errors', $errors);
				$this->render('json/json_error.php');
			}
		} catch(Exception $e) {
			$this->getView()->setVar('errors', [$e->getMessage()]);
			$this->render('json/json_error.php');
			return;
		}
	}
	# -------------------------------------------------------
}
