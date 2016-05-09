<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaViewers/UniversalViewer.php :
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
 
	class UniversalViewer extends BaseMediaViewer implements IMediaViewer {
		# -------------------------------------------------------
		/**
		 *
		 */
		protected static $s_callbacks = ['getViewerManifest'];
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerHTML($po_request, $ps_identifier, $pa_data=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				$o_view->setVar('identifier', $ps_identifier);
				$o_view->setVar('data_url', caNavUrl($po_request, '*', '*', 'GetMediaData', ['identifier' => $ps_identifier], ['absolute' => true]));
				$o_view->setVar('viewer', 'UniversalViewer');
			}
			
			return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerData($po_request, $ps_identifier, $pa_data=null) {
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