<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewers/ThreeJS.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2021 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Media/IMediaViewer.php');
require_once(__CA_LIB_DIR__.'/Media/BaseMediaViewer.php');

class ThreeJS extends BaseMediaViewer implements IMediaViewer {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected static $s_callbacks = [];
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getViewerHTML($request, $identifier, $data=null, $options=null) {
		if ($o_view = BaseMediaViewer::getView($request)) {
			$o_view->setVar('identifier', $identifier);
			$o_view->setVar('viewer', 'ThreeJS');
			
			$t_instance = $data['t_instance'];
			$t_subject = $data['t_subject'];
			
			$o_view->setVar('id', 'caMediaOverlayThreeJS_'.$t_instance->getPrimaryKey().'_'.($display_type = caGetOption('display_type', $data, caGetOption('display_version', $data['display'], ''))));
			
			$viewer_opts = ['background_color' => caGetOption('background_color', $data['display'], null)];
			
			if (is_a($t_instance, "ca_object_representations")) {
				if (is_array($textures = $t_instance->getSidecarFileList(null, ['text/prs.wavefront-mtl']))) {
					foreach($textures as $sid => $sf) {
						$viewer_opts['texture'] = caNavUrl($request, '*', '*', 'getMediaSidecarData', ['sidecar_id' => $sid], ['absolute' => true]).'/filename/'.$sf['original_filename'];
						break;
					}
				}
				if(is_array($texture_image_list = $t_instance->getSidecarFileList(null, ['image/*', 'application/octet-stream']))) {
					$viewer_opts['textureImages'] = array_map(function($v) use ($request) { 
						return caNavUrl($request, '*', '*', 'GetMediaSidecarData', ['sidecar_id' => $v['sidecar_id'], 'filename' => $v['original_filename']]);
					}, $texture_image_list);
				}
				
				$viewer_opts = array_merge($viewer_opts, [
					'id' => $id, 'viewer_width' => caGetOption('viewer_width', $data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $data['display'], '100%')
				]);
				
				if (!$t_instance->hasMediaVersion('media', $version = caGetOption('display_version', $data['display'], 'original'))) {
					if (!$t_instance->hasMediaVersion('media', $version = caGetOption('alt_display_version', $data['display'], 'original'))) {
						$version = 'original';
					}
				}
				
				// HTML for 3d viewer
				$o_view->setVar('viewerHTML', $t_instance->getMediaTag('media', $version, $viewer_opts));
			} elseif (is_a($t_instance, "ca_site_page_media")) {
				$viewer_opts = array_merge($viewer_opts, [
					'id' => $id, 'viewer_width' => caGetOption('viewer_width', $data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $data['display'], '100%')
				]);
				
				if (!$t_instance->hasMediaVersion('media', $version = caGetOption('display_version', $data['display'], 'original'))) {
					if (!$t_instance->hasMediaVersion('media', $version = caGetOption('alt_display_version', $data['display'], 'original'))) {
						$version = 'original';
					}
				}
				
				// HTML for tileviewer
				$o_view->setVar('viewerHTML', $t_instance->getMediaTag('media', $version, $viewer_opts));
			} else {
				$viewer_opts = array_merge($viewer_opts, [
					'id' => 'caMediaOverlayThreeJS', 'viewer_width' => caGetOption('viewer_width', $data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $data['display'], '100%')
				]);
				
				$t_instance->useBlobAsMediaField(true);
				if (!$t_instance->hasMediaVersion('value_blob', $version = caGetOption('display_version', $data['display'], 'original'))) {
					if (!$t_instance->hasMediaVersion('value_blob', $version = caGetOption('alt_display_version', $data['display'], 'original'))) {
						$version = 'original';
					}
				}
				
				// HTML for 3d viewer
				$o_view->setVar('viewerHTML', $t_instance->getMediaTag('value_blob', $version, $viewer_opts));
			}
			
				
			return BaseMediaViewer::prepareViewerHTML($request, $o_view, $data, $options);
		}
		
		return _t("Could not load viewer");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getViewerData($request, $identifier, $data=null, $options=null) {
		return _t("No data");
	}
	# -------------------------------------------------------
}
