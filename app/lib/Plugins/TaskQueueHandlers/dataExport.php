<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/dataExport.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 * @subpackage TaskQueue
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/ApplicationError.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_APP_DIR__."/helpers/exportHelpers.php");

class WLPlugTaskQueueHandlerdataExport Extends WLPlug Implements IWLPlugTaskQueueHandler {
	# --------------------------------------------------------------------------------
	
	public $error;

	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->error = new ApplicationError();
		$this->error->setErrorOutput(0);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns name/short description for this particular task queue plugin
	 *
	 * @return string Name - actually more of a short description - of this task queue plugin
	 */
	public function getHandlerName() {
		return _t("Background export");
	}
	# --------------------------------------------------------------------------------
	/**
	 * Extracts and returns printable parameters for the queue record passed in $rec
	 * This is used by utilties that present information on the task queue to show details of each queued task
	 * without having to know specifics about the type of task.
	 *
	 * @param array $rec A raw database record array for the queued task (eg. each key a field in ca_task_queue and the values are raw database data that has not been manipulated or unserialized)
	 * @return array An array of printable parameters for the task; array keys are parameter codes, values are arrays with two keys: 'label' = a printable parameter description'; 'value' is a printable parameter setting
	 */
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		$params = [];
		$config = Configuration::load(__CA_CONF_DIR__.'/find_navigation.conf');
		$find_types = $config->getAssoc('find_types_for_display');
		$is_summary = ($parameters["mode"] ?? null) === 'SUMMARY';
		$is_set = ($parameters["mode"] ?? null) === 'SETS';
	
		$params['outputType'] = array(
			'label' => _t('Output type'),
			'value' => mb_strtolower($parameters["mode"] ?? _t('Unknown'))
		);
		
		if(!$is_summary && !$is_set) {
			$params['findType'] = array(
				'label' => _t('Find type'),
				'value' => $find_types[$parameters["findType"]] ?? $parameters["findType"]
			);
		}
		$params['outputFormat'] = array(
			'label' => _t('Output format'),
			'value' => $parameters['format'] ?? _t('Unknown')
		);
		
		$params['contentType'] = array(
			'label' => _t('Content'),
			'value' => Datamodel::getTableProperty($parameters["table"], !$is_summary ? 'NAME_PLURAL' : 'NAME_SINGULAR')
		);
		
		
		$exp = $parameters["searchExpressionForDisplay"];
		
		$params['searchExpressionForDisplay'] = array(
			'label' => $is_summary ? _t('Subject') : _t('Query'),
			'value' => $exp
		);
		
		if(!$is_summary) {
			$params['resultCount'] = array(
				'label' => _t('Count'),
				'value' => sizeof($parameters["results"])
			);
		}
		
		return $params;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Method invoked when the task queue needs to actually execute the task. For mediaproc this means
	 * actually doing the processing of media!
	 *
	 * Return false on failure/error and sets the error property with an error description. Returns an array
	 * with processing details on success.
	 *
	 * @param array $parameters An unserialized parameters array for the current task (eg. unserialized data from ca_task_queue.parameters)
	 * @return array Returns false on error, or an array with processing details on success
	 */
	public function process($parameters) {
		$logger = caGetLogger();
		$resp = new ResponseHTTP();
		$req = new RequestHTTP($resp, array('simulateWith' => [
				'POST' => $parameters['request'],
				'SCRIPT_NAME' => join('/', array(__CA_URL_ROOT__, 'index.php')), 'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => join('/', array(__CA_URL_ROOT__, 'index.php', 'find')), 
				'PATH_INFO' => '/'.join('/', array('find')),
				'REMOTE_ADDR' => $parameters['ip_address'] ?? null,
				'HTTP_USER_AGENT' => 'dataExport',
				'user_id' => $parameters['user_id'] ?? null
			]
		));
		
		$t_download = ca_user_export_downloads::findAsInstance(['download_id' => $parameters['download_id'], 'user_id' => $parameters['user_id']]);
		
		$o_app = AppController::getInstance($req, $resp);
		
		if(!($user = ca_users::findAsInstance(['user_id' => $parameters['user_id']]))) {
			$logger->logError(_t("[TaskQueue::dataExport::process] Invalid user id; id was '%1'", $parameters['user_id'])); 
			$this->error->setError(551, _t("Invalid user_id; id was '%1'", $parameters['user_id']),"dataExport->process()");
			return false;
		}
		if(!($result = caMakeSearchResult($parameters['table'], $parameters['results'], ['sort' => $parameters['sort'], 'sortDirection' => $parameters['sortDirection']]))) {
			$logger->logError(_t("[TaskQueue::dataExport::process] Invalid table or id. Table was '%1''", $parameters['table'])); 
			$this->error->setError(551, _t("Invalid table or id. Table was '%1'", $parameters['table']),"dataExport->process()");
			return false;
		}
		
		if($t_download) {
			$t_download->set([
				'status' => 'PROCESSING'
			]);
			$t_download->update();
		}
		
		try {
			switch($mode = $parameters['mode']) {
				case 'EXPORT':
					$res = caExportResult($req, $result, $parameters['request']['export_format'], _t('Data_Export'), ['output' => 'FILE', 'checkAccess' => $parameters['request']['checkAccess'] ?? null]);
					if(is_array($res)) {
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Data export for %2', __CA_APP_DISPLAY_NAME__, strip_tags($parameters['searchExpressionForDisplay'])), 'data_export_result.tpl', $parameters, null, null, ['attachments' => [
							[
								'name' => "data_export.{$res['extension']}",
								'path' => $res['path'],
								'mimetype' => $res['mimetype']
							]]
						]);
					} else {
						$parameters['errors'] = _t('Output failed'); 
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Data export failed', __CA_APP_DISPLAY_NAME__), 'data_export_failure.tpl', $parameters, null, null, []);
					}
					break;
				case 'LABELS':
					$res = caExportAsLabels($req, $result, $parameters['request']['label_form'], _t('Labels'), _t('Labels'), ['output' => 'FILE', 'checkAccess' => $parameters['request']['checkAccess'] ?? null]);
					if(is_array($res)) {
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Labels for %2', __CA_APP_DISPLAY_NAME__, strip_tags($parameters['searchExpressionForDisplay'])), 'label_export_result.tpl', $parameters, null, null, ['attachments' => [
							[
								'name' => 'labels.pdf',		// labels are always PDF
								'path' => $res['path'],
								'mimetype' => $res['mimetype']
							]]
						]);
					} else {
						$parameters['errors'] = _t('Output failed'); 
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Label export failed', __CA_APP_DISPLAY_NAME__), 'label_export_failure.tpl', $parameters, null, null, []);
					}
					break;
				case 'SUMMARY':
					if(!$result->nextHit()) {
						$this->error->setError(551, _t("[TaskQueue::dataExport::process] Record does not exist", $mode),"dataExport->process()");
						$parameters['errors'] = _t('Record does not exist');
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Summary export failed', __CA_APP_DISPLAY_NAME__), 'summary_export_failure.tpl', $parameters, null, null, []);
						break;
					}
					$res = caExportSummary($req, $result->getInstance(), $parameters['request']['template'], (int)$parameters['request']['display_id'], _t('Download'), _t('Download'), ['output' => 'FILE', 'checkAccess' => $parameters['request']['checkAccess'] ?? null]);
					if(is_array($res)) {
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Summary for %2', __CA_APP_DISPLAY_NAME__, strip_tags($parameters['searchExpressionForDisplay'])), 'summary_export_result.tpl', $parameters, null, null, ['attachments' => [
							[
								'name' => "summary.{$res['extension']}",
								'path' => $res['path'],
								'mimetype' => $res['mimetype']
							]]
						]);
					} else {
						$parameters['errors'] = _t('Output failed');
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Summary export failed', __CA_APP_DISPLAY_NAME__), 'summary_export_failure.tpl', $parameters, null, null, []);
					}
					break;
				case 'SETS':
					$res = caExportResult($req, $result, $parameters['request']['export_format'], _t('Set_Export'), ['output' => 'FILE', 'checkAccess' => $parameters['request']['checkAccess'] ?? null]);
					if(is_array($res)) {
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Set export for %2', __CA_APP_DISPLAY_NAME__, strip_tags($parameters['searchExpressionForDisplay'])), 'set_export_result.tpl', $parameters, null, null, ['attachments' => [
							[
								'name' => "data_export.{$res['extension']}",
								'path' => $res['path'],
								'mimetype' => $res['mimetype']
							]]
						]);
					} else {
						$parameters['errors'] = _t('Output failed'); 
						caSendMessageUsingView($req, $user->get('email'), __CA_ADMIN_EMAIL__, _t('[%1] Set export failed', __CA_APP_DISPLAY_NAME__), 'set_export_failure.tpl', $parameters, null, null, []);
					}
					break;
				default:
					$logger->logError(_t("[TaskQueue::dataExport::process] Invalid mode %1", $mode)); 
					$this->error->setError(551, _t("[TaskQueue::dataExport::process] Invalid mode %1", $mode),"dataExport->process()");
					break;
			}
			
			if(is_array($res)) {
				if($t_download) {
					$md = $t_download->get('ca_user_export_downloads.metadata');
					$md['extension'] = Media::getExtensionForMimetype($res['mimetype']);
					$t_download->set([
						'generated_on' => _t('now'),
						'status' => 'COMPLETE',
						'export_file' => $res['path'],
						'metadata' => $md
					]);
					if(!$t_download->update()) {
						$md['error'] = join('; ', $t_download->getErrors());
						$t_download->clearMedia('export_file');
						$t_download->set([
							'generated_on' => _t('now'),
							'status' => 'ERROR',
							'metadata' => $md
						]);
						$t_download->update();
					}
				}
				
			} else {
				if($t_download) {
					$md = $t_download->get('ca_user_export_downloads.metadata');
					$md['error'] = $parameters['errors'] ?: _t('Unknown error');
					$t_download->set([
						'generated_on' => _t('now'),
						'status' => 'ERROR',
						'error_code' => 531,	
						'metadata' => $md
					]);
					$t_download->update();
				}
			}
		} catch(Exception $e) {
			if($t_download) {
				$md = $t_download->get('ca_user_export_downloads.metadata');
				$md['error'] = $e->getMessage();
					
				$t_download->set([
					'generated_on' => _t('now'),
					'status' => 'ERROR',
					'error_code' => 531,	
					'metadata' => $md
				]);
				$t_download->update();
			}
		}
		return false;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
	 * all task queue handlers must implement this
	 *
	 * Returns true on success, false on error
	 */
	public function cancel($pn_task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
