<?php
/* ----------------------------------------------------------------------
 * app/plugins/ArtefactsCanada/controllers/ExportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ProgressBar.php');
require_once(__CA_LIB_DIR__.'/Parsers/ZipFile.php');
require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');


class ExportController extends ActionController {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $config;		// plugin configuration file

	# -------------------------------------------------------
	# Constructor
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		// Set view path for plugin views directory
		if (!is_array($pa_view_paths)) { $pa_view_paths = array(); }
		$pa_view_paths[] = __CA_APP_DIR__."/plugins/ArtefactsCanada/themes/".__CA_THEME__."/views";
		
		// Load plugin configuration file
		$this->config = Configuration::load(__CA_APP_DIR__.'/plugins/ArtefactsCanada/conf/artefactsCanada.conf');
		
		if (!$this->config->get('enabled')) {
			throw new ApplicationException(_t('Artefacts Canada export is not enabled'));
		}
		if (!($export_display_code = $this->config->get('export_display')) || (ca_bundle_displays::find(['display_code' => $export_display_code], ['returnAs' => 'count']) == 0)) {
			throw new ApplicationException(_t("Artefacts Canada export display '%1' does not exist", $export_display_code));
		}
		
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) {
			$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
			return;
		}
		
		// Load plugin stylesheet
		MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/ArtefactsCanada/themes/".__CA_THEME__."/css/ArtefactsCanada.css",'text/css');	
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Index() {
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) { return; }
		
		
		if ($this->config->get('delete_export_files_older_than') > 0) {
			// Clean up old files
			array_map(function($v) { 
				if(preg_match("!^artefacts_canada_export_!", pathinfo($v, PATHINFO_BASENAME))) { 
					$t = filemtime($v);
					if ((time()-$t) > $this->config->get('delete_export_files_older_than')) {
						unlink($v);
					}
				} 
				return;
			}, caGetDirectoryContentsAsList(__CA_APP_DIR__."/tmp", false));
		}
		
		$t_set = new ca_sets();
		$this->view->setVar('sets_list', $sets = caExtractValuesByUserLocale($t_set->getSets(['user_id' => $this->request->getUserID(), 'table' => 'ca_objects'])));
		
		$sets_list = [];
		foreach($sets as $set) {
		    if($set['item_count'] < 1) { continue; }
			$sets_list[$set['set_code']] = $set['name'].' '.(($set['item_count'] == 1) ? _t('(%1 item)', $set['item_count']) : _t('(%1 items)', $set['item_count']));
		}
		$this->view->setVar('sets_list_select', caHTMLSelect('set_code', array_flip($sets_list), []));
	
		$this->render("export_settings_html.php");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Run() {
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) { return; }
		
		$set_code = $this->request->getParameter('set_code', pString);
	
		if (!ca_sets::setExists($set_code, ['user_id' => $this->request->getUserID(), 'access' => __CA_SET_READ_ACCESS__])) { 
			throw new ApplicationException(_t('Set does not exist'));
		}
		$job_id = md5('U'.$this->request->getUserID()."_{$set_code}_".uniqid(rand(), true).'_'.microtime(true));
		
		$this->view->setVar('set_code', $set_code);
		$this->view->setVar('job_id', $job_id);
		
		$this->render("export_run_html.php");
	}
	# ------------------------------------------------------------------
	# Ajax
	# ------------------------------------------------------------------
	/**
	 * Ajax-invoked execution of export process. This is where the export is actually run.
	 */
	public function RunExport() {
	    set_time_limit(7200);
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) { return; }
		
		$set_code = $this->request->getParameter('set_code', pString);
		$job_id = $this->request->getParameter('job_id', pString);
	
		
		// Get set
		$t_set = ca_sets::find(['set_code' => $set_code], ['returnAs' => 'firstModelInstance']);
		if (!$t_set || !$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_READ_ACCESS__)) {
			throw new ApplicationException(_t('Invalid set %1', $set_code));
		}
		if ($t_set->get('ca_sets.table_num') != 57) { // 57=ca_objects
			throw new ApplicationException(_t('Only object sets may be exported'));
		}
		if (!is_array($items = $t_set->getItems(['returnRowIdsOnly' => true]))) { $items = []; }
		if(sizeof($items) == 0) { 
			throw new ApplicationException(_t('Set must not be empty'));
		}
		$items = array_keys($items);
		
		if (!($t_display = ca_bundle_displays::find(['display_code' => $display_code = $this->config->get('export_display')], ['returnAs' => 'firstModelInstance']))) {
			throw new ApplicationException(_t("Configured display '%1' is not available", $display_code));
		}
		$qr = caMakeSearchResult('ca_objects', $items);
		
		$placements = $t_display->getPlacements(['format' => 'simple']);

		// add headers
		$headers = array_merge(array_values(array_map(function($v) { 
			if (is_array($v['settings']) && is_array($v['settings']['label']) && sizeof($v['settings']['label'])) {
				if(isset($v['settings']['label'][__CA_DEFAULT_LOCALE__])) { return $v['settings']['label'][__CA_DEFAULT_LOCALE__]; }
				return array_shift($v['settings']['label']);
			}
			return $v['display']; 
		}, $placements)), [_t('Media')]);
		$headers = array_map(function($v) {
		    $v = preg_replace("![\r\n\t]+!u", " ", html_entity_decode($v, ENT_QUOTES));
		    $v = preg_replace("![“”]+!u", '"', $v);
		    $v = preg_replace("![‘’]+!u", "'", $v);
			$v = mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8'); //iconv('UTF-8', 'ISO-8859-1', $v);
			if (preg_match("![^A-Za-z0-9 .;\p{L}]+!u", $v)) {
				$v = ('"'.str_replace('"', '""', $v).'"');
			}
			return $v;
		; }, $headers);
		$rows[] = join("\t", $headers);
		
		$zip = new ZipFile();
		$seen_idnos = [];
		
		
		$o_progress = new ProgressBar('WebUI', $qr->numHits(), $job_id);
		$o_progress->start(_t('Processing'));
		while($qr->nextHit()) {
			$idno = $qr->get("ca_objects.idno");
			
			$row = [];
			foreach($placements as $placement_id => $placement_info) {
				$v = preg_replace("![\r\n\t]+!", " ", iconv('UTF-8', 'ISO-8859-1', html_entity_decode($t_display->getDisplayValue($qr, $placement_id, ['convert_codes_to_display_text' => true, 'convertLineBreaks' => false, 'timeOmit' => true]), ENT_QUOTES, 'UTF-8')));
				if (preg_match("![^A-Za-z0-9 .;]+!", $v)) {
					$v = '"'.str_replace('"', '""', $v).'"';
				}
				$row[] = $v;
			}
			
			$media_refs = [];
			if (is_array($media_list = $qr->get('ca_object_representations.media.original.path', ['returnAsArray' => true]))) {
				foreach($media_list as $media_path) {
				    if (!file_exists($media_path)) { continue; }
					$suffix = 0;
					do {
						$id = preg_replace("![^A-Za-z0-9\-\.]+!", "_", $idno.($suffix ? "-{$suffix}" : ""));
						$suffix++;
					} while(isset($seen_idnos[$id]));
					
					$zip->addFile($media_path, $media_refs[] = "{$id}.".pathinfo($media_path, PATHINFO_EXTENSION));
				}
				$seen_idnos[$id] = true;
			}
			$row[] = join(";", $media_refs);
			$row = array_map(function($v) { return preg_replace("![\t]+!", " ", $v); }, $row);
			
			$rows[] = join("\t", $row);
			$o_progress->next(_t('Processing %1', $idno));
		}
		
		$zip->addFile(join("\n", $rows), "artefacts_data.txt");
		
		$zip_path = $zip->output(ZIPFILE_FILEPATH);
		
		copy($zip_path, $new_path = __CA_APP_DIR__."/tmp/artefacts_canada_export_{$job_id}.zip");
		
		$links = [caNavLink($this->request, _t('Download Artefacts Canada data as ZIP file (%1)', caHumanFilesize(filesize($new_path))), '', '*', '*', 'Download', ['job_id' => $job_id, 'download' => 1])];
		
		// TODO: support SFTP here?
	
		$o_progress->finish();
		$job_info = $o_progress->getDataForJobID($job_id);
		
		$va_links = [];
		if(!is_array($job_info['data']['created'])) { $job_info['data']['created'] = []; }
		if(!is_array($job_info['data']['updated'])) { $job_info['data']['updated'] = []; }
		
		
		$this->view->setVar('info', array(
			'status' => $status,
			'job_id' => $job_id,
			'set_code' => $set_code,
			'message' => _t('Completed export: %1', $zip_path),
			'links' => $links
		));
		
		$this->render('export_run_json.php');
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function Download() {
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) { return; }
		$job_id = preg_replace("![^A-Za-z0-9_]+!", "_", $this->request->getParameter('job_id', pString));

		if(!file_exists(__CA_APP_DIR__."/tmp/artefacts_canada_export_{$job_id}.zip")) {
			throw new ApplicationException(_t('Download file is no longer available'));
		}
		
		$this->view->setVar('archive_path', __CA_APP_DIR__."/tmp/artefacts_canada_export_{$job_id}.zip");
		$this->view->setVar('archive_name', "artefacts_canada_export_{$job_id}.zip");
		$this->view->render('bundles/download_file_binary.php');
	}
	# ------------------------------------------------------------------
	/**
	 * Return via Ajax current status of running export job
	 */
	public function GetExportStatus() {
		if (!$this->request->user->canDoAction('can_export_artefacts_canada')) { return; }
		
		$job_id = $this->request->getParameter('job_id', pString);
		$o_progress = new ProgressBar('WebUI');
		$data = $o_progress->getDataForJobID($job_id);
		$data['elapsedTime'] = caFormatInterval(time()-$data['start']);
		
		$this->view->setVar('info', $data);
		$this->render('export_run_json.php');
	}
	# -------------------------------------------------------		
}
