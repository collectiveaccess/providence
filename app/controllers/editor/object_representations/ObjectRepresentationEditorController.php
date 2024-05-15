<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/object_representations/ObjectRepresentationEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/Media.php");
require_once(__CA_LIB_DIR__."/Media/MediaProcessingSettings.php");
require_once(__CA_LIB_DIR__."/BaseEditorController.php");

require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");

class ObjectRepresentationEditorController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_object_representations';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	# AJAX handlers
	# -------------------------------------------------------
	/**
	 * Return representation annotation editor
	 *
	 * Expects the following request parameters: 
	 *		representation_id = the id of the ca_object_representations record for which to edit annotations
	 *
	 *	Optional request parameters:
	 *		none (yet)
	 */ 
	public function GetAnnotationEditor() {
		list($pn_representation_id, $t_rep) = $this->_initView();
		
		// Get player
		$va_display_info = caGetMediaDisplayInfo('annotation_editor', $t_rep->getMediaInfo("media", "original", "MIMETYPE"));
		$this->view->setVar('player', $t_rep->getMediaTag('media', $va_display_info['display_version'], array('viewer_width' => $vn_player_width = $va_display_info['viewer_width'], 'viewer_height' => $vn_player_height = $va_display_info['viewer_height'], 'id' => 'caAnnoEditorMediaPlayer', 'class' => 'caAnnoEditorMediaPlayer'.((true) ? ' caAnnoEditorAudioMediaPlayer': ' caAnnoEditorVideoMediaPlayer'))));
		$this->view->setVar('player_width', $vn_player_width);
		$this->view->setVar('player_height', $vn_player_height);
		
		$va_rep_props = $t_rep->getMediaInfo('media', 'original');
		$vn_timecode_offset = isset($va_rep_props['PROPERTIES']['timecode_offset']) ? (float)$va_rep_props['PROPERTIES']['timecode_offset'] : 0;
		$this->view->setVar('timecode_offset', $vn_timecode_offset);
		
		$result_context = new ResultContext($this->request, 'ca_objects', ResultContext::getLastFind($this->request, 'ca_objects'));
		$result_desc = $result_context->getResultDesc() ?? [];
		$object_ids = $t_rep->get('ca_objects.object_id', ['returnAsArray' => true]);
		
		$default_annotation_id = null;
		if(is_array($object_ids) && sizeof($object_ids) && isset($result_desc[$object_ids[0]])) {
			$desclist = $result_desc[$object_ids[0]]['desc'] ?? [];
			foreach($desclist as $desc) {
				if($desc['table'] === 'ca_representation_annotations') {
					$default_annotation_id = $desc['field_row_id'];
					break;
				}
				if($desc['table'] === 'ca_representation_annotation_labels') {
					$l = new ca_representation_annotation_labels($desc['field_row_id']);
					$default_annotation_id = $l->get('annotation_id');
					break;
				}
			}
		}
		
		$this->view->setVar('default_annotation_id', $default_annotation_id);
		
		// Get # clips
		$this->view->setVar('annotation_count', (int)$t_rep->getAnnotationCount());
		
		if(!is_array($va_annotations = $t_rep->getAnnotations())) { $va_annotations = array(); }
		$this->view->setVar('annotation_map', array_values($va_annotations));
		
		return $this->render('ajax_representation_annotation_editor_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Return representation image center editor
	 *
	 * Expects the following request parameters: 
	 *		representation_id = the id of the ca_object_representations record for which to edit annotations
	 *
	 *	Optional request parameters:
	 *		none (yet)
	 */ 
	public function GetImageCenterEditor() {
		list($pn_representation_id, $t_rep) = $this->_initView();
		
		$va_display_info = caGetMediaDisplayInfo('image_center_editor', $t_rep->getMediaInfo("media", "original", "MIMETYPE"));
		$this->view->setVar('image', $t_rep->getMediaTag('media', $va_display_info['display_version'], array('id' => 'caImageCenterEditorImage', 'class' => 'caImageCenterEditor')));
		$va_media_info = $t_rep->getMediaInfo('media', $va_display_info['display_version']);
		
		$this->view->setVar('image_width', caGetOption('WIDTH', $va_media_info, null));
		$this->view->setVar('image_height', caGetOption('HEIGHT', $va_media_info, null));
		
		$va_center = $t_rep->getMediaCenter('media');
		
		$this->view->setVar('center_x', $va_center['x']);
		$this->view->setVar('center_y', $va_center['y']);
		
		$this->view->setVar('image_info', $va_media_info);
		
		
		return $this->render('ajax_representation_image_center_editor_html.php');
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function GetAnnotationList() {
		list($pn_representation_id, $t_rep) = $this->_initView();
		$vn_start = $this->request->getParameter('s', pInteger);
		$vn_max = $this->request->getParameter('n', pInteger);
		
		$this->view->setVar('annotation_count', $vn_total =(int)$t_rep->getAnnotationCount());
		$this->view->setVar('annotation_list', array('start' => $vn_start, 'max' => $vn_max, 'total' => $vn_total, 'list' => array_values($t_rep->getAnnotations(array('start' => $vn_start, 'max' => $vn_max)))));
		
		return $this->render('ajax_representation_annotation_list_json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function DownloadCaptionFile() {
		list($pn_representation_id, $t_rep) = $this->_initView();
		
		$pn_caption_id = $this->request->getParameter('caption_id', pString);
		
		$this->view->setVar('representation_id', $pn_representation_id);
		$this->view->setVar('caption_id', $pn_caption_id);
		$this->view->setVar('t_object_representation', $t_rep);
		
		$t_caption = new ca_object_representation_captions($pn_caption_id);
		if (!$t_caption->getPrimaryKey() || ((int)$t_caption->get('representation_id') !== (int)$pn_representation_id)) {
			die(_t("Invalid caption file"));
		}
		
		$t_locale = new ca_locales();
		$vn_locale_id = $t_caption->get('locale_id');
		$vs_locale = $t_locale->localeIDToCode($vn_locale_id);
		$this->view->setVar('file_path', $t_caption->getFilePath('caption_file'));
		$va_info = $t_caption->getFileInfo("caption_file");
		
		$this->view->setVar('download_name', caGetRepresentationDownloadFileName('ca_object_representations', ['idno' => $t_rep->get('idno'), 'index' => null, 'version' => 'captions', 'extension' => 'vtt', 'original_filename' => $va_info['ORIGINAL_FILENAME'], 'representation_id' => $pn_representation_id]));				
		
		return $this->render('caption_download_binary.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function DownloadSidecarFile() {
		list($pn_representation_id, $t_rep) = $this->_initView();
		
		$pn_sidecar_id = $this->request->getParameter('sidecar_id', pString);
		
		$this->view->setVar('representation_id', $pn_representation_id);
		$this->view->setVar('sidecar_id', $pn_sidecar_id);
		$this->view->setVar('t_object_representation', $t_rep);
		
		$t_sidecar = new ca_object_representation_sidecars($pn_sidecar_id);
		if (!$t_sidecar->getPrimaryKey() || ((int)$t_sidecar->get('representation_id') !== (int)$pn_representation_id)) {
			die(_t("Invalid sidecar file"));
		}
		
		$info = $t_sidecar->getFileInfo('sidecar_file');
		$this->view->setVar('file_path', $path = $t_sidecar->getFilePath('sidecar_file'));
		$va_info = $t_sidecar->getFileInfo("sidecar_file");
		
		$this->view->setVar('download_name', caGetRepresentationDownloadFileName('ca_object_representations', ['idno' => $t_rep->get('idno'), 'index' => null, 'version' => 'sidecars', 'extension' => pathinfo($path, PATHINFO_EXTENSION), 'original_filename' => $va_info['ORIGINAL_FILENAME'], 'representation_id' => $pn_representation_id]));				
		
		return $this->render('sidecar_download_binary.php');
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		AssetLoadManager::register('panel');
		parent::info($pa_parameters);
		$vn_representation_id = (isset($pa_parameters['representation_id'])) ? $pa_parameters['representation_id'] : null;
	
		if ($vn_representation_id) {
			// find object editor screen with media bundle
			$t_ui = ca_editor_uis::loadDefaultUI('ca_objects', $this->request);
			$this->view->setVar('object_editor_screen', $t_ui->getScreenWithBundle('ca_object_representations', $this->request));
		}
		
		return $this->render('widget_object_representation_info_html.php', true);
	}
	# -------------------------------------------------------
}
