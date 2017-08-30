<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/MediaViewers/UniversalViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
    require_once(__CA_LIB_DIR__."/ca/MediaContentLocationIndexer.php");
	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 
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
		public static function getViewerHTML($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				$o_view->setVar('identifier', $ps_identifier);
				
				$va_params = ['identifier' => $ps_identifier, 'context' => caGetOption('context', $pa_options, $po_request->getAction())];
				
				// Pass subject key when getting viewer data
				if ($pa_data['t_subject']) { $va_params[$pa_data['t_subject']->primaryKey()] = $pa_data['t_subject']->getPrimaryKey(); }
				
				$o_view->setVar('data_url', caNavUrl($po_request, '*', '*', 'GetMediaData', $va_params, ['absolute' => true]));
				$o_view->setVar('viewer', 'UniversalViewer');
				$o_view->setVar('width', caGetOption('width', $pa_data['display'], null));
				$o_view->setVar('height', caGetOption('height', $pa_data['display'], null));
			}
			
			return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerData($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				if ($t_instance = caGetOption('t_instance', $pa_data, null)) {
				
					$va_display = caGetOption('display', $pa_data, []);
					
					if(is_a($t_instance, "ca_object_representations")) {
						$vs_media_fld = 'media';
					} elseif(is_a($t_instance, "ca_attribute_values")) {
						$vs_media_fld = 'value_blob';
					} else {
						throw new ApplicationException(_t('Could not derive media dimensions'));
					}
					
					$pa_data['width'] = $t_instance->getMediaInfo($vs_media_fld, 'original', 'WIDTH');
					$pa_data['height'] = $t_instance->getMediaInfo($vs_media_fld, 'original', 'HEIGHT');
					
					$o_view->setVar('id', 'caMediaOverlayUniversalViewer_'.$t_instance->getPrimaryKey().'_'.($vs_display_type = caGetOption('display_type', $pa_data, caGetOption('display_version', $pa_data['display'], ''))));
				
					$vn_use_universal_viewer_for_image_list_length = caGetOption('use_universal_viewer_for_image_list_length_at_least', $pa_data['display'], null);
					if (((($vs_display_version = caGetOption('display_version', $pa_data['display'], 'tilepic')) == 'tilepic')) && !$vn_use_universal_viewer_for_image_list_length) {
						$pa_data['resources'] = $t_instance->getFileList();
					} elseif (is_a($t_instance, "ca_object_representations") && $pa_data['t_subject'] && $vn_use_universal_viewer_for_image_list_length) {
						$va_reps = $pa_data['t_subject']->getRepresentations(['small', $vs_display_version, 'original'], null, []);
						
						$t_rep = new ca_object_representations();
						$va_labels = $t_rep->getPreferredDisplayLabelsForIDs(caExtractArrayValuesFromArrayOfArrays($va_reps, 'representation_id'));
		
						foreach($va_reps as $va_rep) {
							$pa_data['resources'][] = [
								'title' => str_replace("["._t('BLANK')."]", "", $va_labels[$va_rep['representation_id']]),
								'representation_id' => $va_rep['representation_id'],
								'preview_url' => $va_rep['urls']['small'],
								'url' => $va_rep['urls'][$vs_display_version],
								'width' => $va_rep['info']['original']['WIDTH'],
								'height' => $va_rep['info']['original']['HEIGHT'],
								'noPages' => true
							];
						}
					} else {
						$pa_data['resources'][] = [
							'url' => $pa_data['t_instance']->getMediaUrl($vs_media_fld, $vs_display_version)
						];
					}
					
					$o_view->setVar('t_subject', $pa_data['t_subject']);
					$o_view->setVar('request', $po_request);
					$o_view->setVar('identifier', $ps_identifier);
					$o_view->setVar('data', $pa_data);
					
					return $o_view->render("UniversalViewerManifest.php");
				}
			}
			
			throw new ApplicationException(_t('Media manifest is not available'));
		}
		
		# -------------------------------------------------------
		/**
		 * Return word occurrence data for in-viewer search
		 *
		 * @param RequestHTTP $po_request
		 * @param string $ps_identifier Media identifier
		 * @param array $pa_data Viewer request data
		 * @param array $pa_options No options are currently supported
		 *
		 * @return sting IIIF search API manifest
		 * @throws ApplicationException
		 */
		public static function searchViewerData($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
		    if ($o_view = BaseMediaViewer::getView($po_request)) {
		        $ps_q = $po_request->getParameter('q', pString);
		        
                $va_identifier = caParseMediaIdentifier($ps_identifier);
                $t_rep = new ca_object_representations();
                if (!is_array($va_pages = $t_rep->getFileList($va_identifier['id'], 0, 1, ['original'])) || !sizeof($va_pages)) {
                    $pa_data['width'] = $pa_data['height'] = 0;
                    $va_locs = [];
                } else {
                    $va_page = array_shift($va_pages);
                    $pa_data['width'] = $va_page['original_width'];
                    $pa_data['height'] = $va_page['original_height'];
                    
                    switch($va_identifier['type']) {
                        case 'representation':
                        default:
                            $va_locs = MediaContentLocationIndexer::SearchWithinMedia($ps_q, 'ca_object_representations', $va_identifier['id'], 'media');
                            break;
                        case 'attribute':
                            $va_locs = MediaContentLocationIndexer::SearchWithinMedia($ps_q, 'ca_attribute_values', $va_identifier['id'], 'value_blob');
                            break;
                    }
                }
                
                $o_view->setVar('request', $po_request);
                $o_view->setVar('identifier', $ps_identifier);
                $o_view->setVar('data', $pa_data);
                
                $o_view->setVar('locations', $va_locs);
			    return $o_view->render("UniversalViewerSearchManifest.php");
			}
			
			throw new ApplicationException(_t('Media search is not available'));
		}
		# -------------------------------------------------------
		/**
		 * Return autocomplete matches for in-viewer search
		 *
		 * @param RequestHTTP $po_request
		 * @param string $ps_identifier Media identifier
		 * @param array $pa_data Viewer request data
		 * @param array $pa_options No options are currently supported
		 *
		 * @return sting IIIF autocomplete manifest
		 * @throws ApplicationException
		 */
		public static function autocomplete($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
		    if ($o_view = BaseMediaViewer::getView($po_request)) {
		        $ps_q = $po_request->getParameter('q', pString);
		        
                $va_identifier = caParseMediaIdentifier($ps_identifier);
                switch($va_identifier['type']) {
                    case 'representation':
                    default:
                        $va_matches = MediaContentLocationIndexer::autocomplete($ps_q, 'ca_object_representations', $va_identifier['id']);
                        break;
                    case 'attribute':
                        $va_matches = MediaContentLocationIndexer::autocomplete($ps_q, 'ca_attribute_values', $va_identifier['id']);
                        break;
                }
               
                $o_view->setVar('request', $po_request);
                $o_view->setVar('identifier', $ps_identifier);
                
                $o_view->setVar('matches', $va_matches);
			    return $o_view->render("UniversalViewerAutocomplete.php");
			}
			
			throw new ApplicationException(_t('Media search autocomplete is not available'));
		}
		# -------------------------------------------------------
	}