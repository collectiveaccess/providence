<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/ModelController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ca/Service/BaseServiceController.php');
require_once(__CA_LIB_DIR__.'/ca/Service/ModelService.php');
require_once(__CA_LIB_DIR__.'/ca/ConfigurationExporter.php');

require_once(__CA_BASE_DIR__.'/install/inc/Installer.php');

class ModelController extends BaseServiceController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	public function exportConfig() {
		$vn_timestamp = null;
		if($vs_after = $this->opo_request->getParameter('modifiedAfter', pString)) {
			if(is_numeric($vs_after) && (strlen($vs_after) == 10)) {
				$vn_timestamp = (int) $vs_after;
			} else {
				$o_tep = new TimeExpressionParser();
				if($o_tep->parse($vs_after)) {
					$va_timestamps = $o_tep->getUnixTimestamps();
					$vn_timestamp = $va_timestamps['start'];
				}
			}
		}

		$this->getView()->setVar('content', ConfigurationExporter::exportConfigurationAsXML('', '', '', '', $vn_timestamp));
		$this->render('xml.php');
	}
	# -------------------------------------------------------
	public function __call($ps_table, $pa_args) {
		$vo_service = new ModelService($this->getRequest(), $ps_table);
		$va_content = $vo_service->dispatch();

		if(intval($this->getRequest()->getParameter("pretty", pInteger))>0){
			$this->getView()->setVar("pretty_print",true);
		}

		if($vo_service->hasErrors()){
			$this->getView()->setVar("errors", $vo_service->getErrors());
			$this->render("json_error.php");
		} else {
			$this->getView()->setVar("content", $va_content);
			$this->render("json.php");
		}
	}
	# -------------------------------------------------------
	public function updateConfig() {
		$vs_post_data = $this->getRequest()->getRawPostData();
		require_once(__CA_LIB_DIR__.'/core/Logging/KLogger/KLogger.php');
		// @todo make this configurable or get from app.conf?
		$o_log = new KLogger(__CA_BASE_DIR__ . '/app/log', KLogger::DEBUG);
		try {
			$o_log->logInfo(_t('Got incoming updateConfig request from %1', $this->getRequest()->getClientIP()));
			$o_log->logInfo(_t('Raw payload is %1', $vs_post_data));
			$o_log->logInfo(_t('Running installer now ...'));

			$o_installer = Installer::getFromString($vs_post_data, '', false, true);

			if($va_errors = $o_installer->getErrors()) {
				$this->getView()->setVar('errors', $va_errors);
				$this->render('json_error.php');
				return;
			}

			$o_installer->processLocales();
			$o_installer->processLists();
			$o_installer->processRelationshipTypes();
			$o_installer->processMetadataElements();
			$o_installer->processMetadataDictionary();
			$o_installer->processRoles();
			$o_installer->processGroups();
			$o_installer->processUserInterfaces();
			$o_installer->processDisplays();
			$o_installer->processSearchForms();

			if($va_errors = $o_installer->getErrors()) {
				$this->getView()->setVar('errors', $va_errors);
				$o_log->logInfo(_t('It seems there was at least one error while running Installer for updateConfig: %1', join(',', $va_errors)));
				$this->render('json_error.php');
				return;
			}

			$o_log->logInfo(_t('Done ...'));

			$this->render('json.php');
		} catch(Exception $e) {
			$o_log->logInfo(_t('It seems there was an error while running Installer for updateConfig: %1', $e->getMessage()));
			$this->getView()->setVar('errors', [$e->getCode() => $e->getMessage()]);
			$this->render('json_error.php');
		}
	}
	# -------------------------------------------------------
}
