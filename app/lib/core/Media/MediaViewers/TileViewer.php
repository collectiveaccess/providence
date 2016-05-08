<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaViewers/TileViewer.php :
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
 
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/Media/IMediaViewer.php');
	require_once(__CA_LIB_DIR__.'/core/Media/BaseMediaViewer.php');
 
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
		public static function getViewerHTML($po_request, $ps_identifier, $pa_data=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				$o_view->setVar('identifier', $ps_identifier);
				$o_view->setVar('viewer', 'TileViewer');
				
				$t_rep = $pa_data['t_instance'];
				$t_subject = $pa_data['t_subject'];
				$va_viewer_opts = [
					'id' => 'caMediaOverlayTileViewer',
					'viewer_base_url' => $po_request->getBaseUrlPath(),
					'annotation_load_url' => caNavUrl($po_request, '*', '*', 'GetAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
					'annotation_save_url' => caNavUrl($po_request, '*', '*', 'SaveAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
					'download_url' => caNavUrl($po_request, '*', '*', 'DownloadMedia', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
					'help_load_url' => caNavUrl($po_request, '*', '*', 'ViewerHelp', array()),
					'annotationEditorPanel' => 'caRepresentationAnnotationEditor',
					'annotationEditorUrl' => caNavUrl($po_request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => (int)$t_rep->getPrimaryKey())),
					'captions' => $t_rep->getCaptionFileList(), 'progress_id' => 'caMediaOverlayProgress'
				];
				
				// HTML for tileviewer
				$o_view->setVar('viewerHTML', $t_rep->getMediaTag('media', 'tilepic', $va_viewer_opts));
					
				return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data);
			}
			
			return _t("Could not load viewer");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerManifest($po_request, $ps_identifier, $pa_data=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				$o_view->setVar('identifier', $ps_identifier);
				$o_view->setVar('data', $pa_data);
				return $o_view->render("UniversalViewerManifest.php");
			}
			
			// TODO: better error
			return _t("Manifest not available");
		}
		# -------------------------------------------------------
	}