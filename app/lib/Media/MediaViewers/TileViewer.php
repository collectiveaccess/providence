<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewers/TileViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */
 
	require_once(__CA_LIB_DIR__.'/Configuration.php');
	require_once(__CA_LIB_DIR__.'/Media/IMediaViewer.php');
	require_once(__CA_LIB_DIR__.'/Media/BaseMediaViewer.php');
 
	class TileViewer extends BaseMediaViewer implements IMediaViewer {
		# -------------------------------------------------------
		/**
		 *
		 */
		protected static $s_callbacks = [];
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerHTML($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				$o_view->setVar('identifier', $ps_identifier);
				$o_view->setVar('viewer', 'TileViewer');
				
				$t_instance = $pa_data['t_instance'];
				$t_subject = $pa_data['t_subject'];
				
				if (!$t_instance->hasMediaVersion('media', $vs_version = caGetOption('display_version', $pa_data['display'], 'tilepic'))) {
					if (!$t_instance->hasMediaVersion('media', $vs_version = caGetOption('alt_display_version', $pa_data['display'], 'tilepic'))) {
						$vs_version = 'original';
					}
				}
				
				$o_view->setVar('id', $vs_id = 'caMediaOverlayTileViewer_'.$t_instance->getPrimaryKey().'_'.($vs_display_type = caGetOption('display_type', $pa_data, caGetOption('display_version', $pa_data['display'], ''))));
				
				if (is_a($t_instance, "ca_object_representations")) {
					$va_viewer_opts = [
						'id' => $vs_id,
						'viewer_width' => caGetOption('viewer_width', $pa_data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $pa_data['display'], '100%'),
						'viewer_base_url' => $po_request->getBaseUrlPath(),
						'annotation_load_url' => caNavUrl($po_request, '*', '*', 'GetAnnotations', array('representation_id' => (int)$t_instance->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
						'annotation_save_url' => caNavUrl($po_request, '*', '*', 'SaveAnnotations', array('representation_id' => (int)$t_instance->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
						'download_url' => caNavUrl($po_request, '*', '*', 'DownloadMedia', array('representation_id' => (int)$t_instance->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
						'help_load_url' => caNavUrl($po_request, '*', '*', 'ViewerHelp', array()),
						'annotationEditorPanel' => 'caRepresentationAnnotationEditor',
						'read_only' => !$po_request->isLoggedIn(),
						'annotationEditorUrl' => caNavUrl($po_request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => (int)$t_instance->getPrimaryKey())),
						'captions' => $t_instance->getCaptionFileList(), 'progress_id' => 'caMediaOverlayProgress'
					];
					
					$vb_no_overlay = (caGetOption('no_overlay', $pa_data['display'], null) || caGetOption('noOverlay', $pa_options, null));
					if($vb_no_overlay){
						// HTML for tileviewer
						$o_view->setVar('viewerHTML', $t_instance->getMediaTag('media', $vs_version, $va_viewer_opts));
					}else{
						// HTML for tileviewer
						$o_view->setVar('viewerHTML', "<a href='#' class='zoomButton' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'Detail', 'GetMediaOverlay', array('context' => caGetOption('context', $pa_options, null), 'id' => (int)$t_subject->getPrimaryKey(), 'representation_id' => (int)$t_instance->getPrimaryKey(), 'overlay' => 1))."\"); return false;'>".$t_instance->getMediaTag('media', $vs_version, $va_viewer_opts)."</a>");
					}
				} elseif (is_a($t_instance, "ca_site_page_media")) {
					$va_viewer_opts = [
						'id' => $vs_id,
						'read_only' => true,
						'viewer_width' => caGetOption('viewer_width', $pa_data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $pa_data['display'], '100%'),
						'viewer_base_url' => $po_request->getBaseUrlPath(),
						'download_url' => caNavUrl($po_request, '*', '*', 'DownloadMedia', array('media_id' => (int)$t_instance->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
						'help_load_url' => caNavUrl($po_request, '*', '*', 'ViewerHelp', array())
					];
					
					// HTML for tileviewer
					$o_view->setVar('viewerHTML', $t_instance->getMediaTag('media', $vs_version, $va_viewer_opts));
				} else {
					$va_viewer_opts = [
						'id' => 'caMediaOverlayTileViewer',
						'viewer_width' => caGetOption('viewer_width', $pa_data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $pa_data['display'], '100%'),
						'read_only' => true,
						'viewer_base_url' => $po_request->getBaseUrlPath(),
						'download_url' => caNavUrl($po_request, '*', '*', 'DownloadMedia', array('value_id' => (int)$t_instance->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
						'help_load_url' => caNavUrl($po_request, '*', '*', 'ViewerHelp', array()),
						'read_only' => !$po_request->isLoggedIn(),
						'captions' => null, 'progress_id' => 'caMediaOverlayProgress'
					];
					
					$t_instance->useBlobAsMediaField(true);
					if (!$t_instance->hasMediaVersion('value_blob', $vs_version = caGetOption('display_version', $pa_data['display'], 'original'))) {
						if (!$t_instance->hasMediaVersion('value_blob', $vs_version = caGetOption('alt_display_version', $pa_data['display'], 'original'))) {
							$vs_version = 'original';
						}
					}
					
					// HTML for tileviewer
					$o_view->setVar('viewerHTML', $t_instance->getMediaTag('value_blob', $vs_version, $va_viewer_opts));
				}
				
					
				return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data, $pa_options);
			}
			
			return _t("Could not load viewer");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerData($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
			return _t("No data");
		}
		# -------------------------------------------------------
	}
