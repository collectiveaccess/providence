<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewers/Mirador.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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
	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 
	class Mirador extends BaseMediaViewer implements IMediaViewer {
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
				if ($pa_data['t_subject']) { 
				    $va_params[$pa_data['t_subject']->primaryKey()] = $pa_data['t_subject']->getPrimaryKey(); 
				    
				    $o_view->setVar('index', 0);
				    if ($pa_data['t_instance']) {
                        $vn_rep_id = $pa_data['t_instance']->getPrimaryKey();
                        if (is_array($va_rep_ids = Mirador::getViewerRepresentationIDList($po_request, $ps_identifier, $pa_data, $pa_options))) {
                            $o_view->setVar('index', array_search($vn_rep_id, $va_rep_ids));
                        }
                    }
				}
				
				$o_view->setVar('data_url', caNavUrl($po_request, '*', '*', 'GetMediaData', $va_params, ['absolute' => true]));
				$o_view->setVar('viewer', 'Mirador');
				$o_view->setVar('width', caGetOption('width', $pa_data['display'], null));
				$o_view->setVar('height', caGetOption('height', $pa_data['display'], null));
				$o_view->setVar('data', $pa_data);
			}
			
			return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerRepresentationIDList($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
            if ($t_instance = caGetOption('t_instance', $pa_data, null)) {
            
                $va_display = caGetOption('display', $pa_data, []);
                
                if(is_a($t_instance, "ca_object_representations") || is_a($t_instance, "ca_site_page_media")) {
                    $vs_media_fld = 'media';
                } elseif(is_a($t_instance, "ca_attribute_values")) {
                    $vs_media_fld = 'value_blob';
                } else {
                    throw new ApplicationException(_t('Could not derive media dimensions'));
                }
                
                $vn_use_mirador_for_image_list_length = caGetOption('use_mirador_for_image_list_length_at_least', $pa_data['display'], null);
                if (is_a($t_instance, "ca_object_representations") && caGetOption('expand_hierarchically', $pa_data['display'], null) && $pa_data['t_subject'] && $pa_data['t_subject']->isHierarchical() && (is_array($va_ids = $pa_data['t_subject']->getHierarchy(null, ['idsOnly' => true, 'sort' => 'idno_sort']))) && sizeof($va_ids)) {  
                    $vn_root_id = $pa_data['t_subject']->getHierarchyRootID();
                    $va_ids = array_filter($va_ids, function($v) use ($vn_root_id) { return $v != $vn_root_id; });
                    return array_values(array_map(function($v) { return $v['representation_id']; }, $pa_data['t_subject']->getPrimaryMediaForIDs($va_ids, ['small'])));
                } elseif (is_a($t_instance, "ca_object_representations") && $pa_data['t_subject'] && $vn_use_mirador_for_image_list_length && ($va_reps = $pa_data['t_subject']->getRepresentations(['small'], null, [])) && (sizeof($va_reps) >= $vn_use_mirador_for_image_list_length)) {
                    return array_values(array_map(function($v) { return $v['representation_id']; }, $va_reps));
                }
            }
            return null;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function getViewerData($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
			if ($o_view = BaseMediaViewer::getView($po_request)) {
				if ($t_instance = caGetOption('t_instance', $pa_data, null)) {
				    $t_subject = caGetOption('t_subject', $pa_data, null);
				
					$va_display = caGetOption('display', $pa_data, []);
					
					if(is_a($t_instance, "ca_object_representations") || is_a($t_instance, "ca_site_page_media")) {
						$vs_media_fld = 'media';
					} elseif(is_a($t_instance, "ca_attribute_values")) {
						$vs_media_fld = 'value_blob';
					} else {
						throw new ApplicationException(_t('Could not derive media dimensions'));
					}		
									
					$va_labels = [];
					
					$pa_data['width'] = $t_instance->getMediaInfo($vs_media_fld, 'original', 'WIDTH');
					$pa_data['height'] = $t_instance->getMediaInfo($vs_media_fld, 'original', 'HEIGHT');
					
					$o_view->setVar('id', 'caMediaOverlayMirador_'.$t_instance->getPrimaryKey().'_'.($vs_display_type = caGetOption('display_type', $pa_data, caGetOption('display_version', $pa_data['display'], ''))));
				
					$vn_use_mirador_for_image_list_length = caGetOption('use_mirador_for_image_list_length_at_least', $pa_data['display'], null);
					if (((($vs_display_version = caGetOption('display_version', $pa_data['display'], 'tilepic')) == 'tilepic')) && !$vn_use_mirador_for_image_list_length) {
						$pa_data['resources'] = $t_instance->getFileList();
					} elseif (is_a($t_instance, "ca_object_representations") && $pa_data['t_subject'] && $vn_use_mirador_for_image_list_length && ($va_reps = $pa_data['t_subject']->getRepresentations(['small', $vs_display_version, 'original'], null, []))) {
						$t_rep = new ca_object_representations();
						
						if (is_a($t_instance, "ca_object_representations") && caGetOption('expand_hierarchically', $pa_data['display'], null) && $pa_data['t_subject'] && $pa_data['t_subject']->isHierarchical() && (is_array($va_ids = $pa_data['t_subject']->getHierarchy(null, ['idsOnly' => true, 'sort' => 'idno_sort']))) && (sizeof($va_ids) > 1)) {  
                            $vn_root_id = $pa_data['t_subject']->getHierarchyRootID();
                            $va_ids = array_filter($va_ids, function($v) use ($vn_root_id) { return $v != $vn_root_id; });
                            $va_reps = $pa_data['t_subject']->getPrimaryMediaForIDs($va_ids, ['small', $vs_display_version, 'original']);
                        }
						
						if(sizeof($va_reps) < $vn_use_mirador_for_image_list_length) {
						    $pa_data['resources'][] = [
                                'url' => $pa_data['t_instance']->getMediaUrl($vs_media_fld, $vs_display_version)
                            ];
						} else {
                            $va_labels = $t_rep->getPreferredDisplayLabelsForIDs(caExtractArrayValuesFromArrayOfArrays($va_reps, 'representation_id'));
        
                            foreach($va_reps as $va_rep) {
                                $pa_data['resources'][] = [
                                    'title' => str_replace("[".caGetBlankLabelText()."]", "", $va_labels[$va_rep['representation_id']]),
                                    'representation_id' => $va_rep['representation_id'],
                                    'preview_url' => $va_rep['urls']['small'],
                                    'url' => $va_rep['urls'][$vs_display_version],
                                    'width' => $va_rep['info']['original']['WIDTH'],
                                    'height' => $va_rep['info']['original']['HEIGHT'],
                                    'noPages' => true
                                ];
                            }
                        }
					} else {
						$pa_data['resources'][] = [
							'url' => $pa_data['t_instance']->getMediaUrl($vs_media_fld, $vs_display_version)
						];
					}
					
					
					$o_view->setVar('t_subject', $pa_data['t_subject']);
					$o_view->setVar('t_instance', $t_instance);
					$o_view->setVar('request', $po_request);
					$o_view->setVar('identifier', $ps_identifier);
					$o_view->setVar('data', $pa_data);
					
					return $o_view->render("MiradorManifest.php");
				}
			}
			
			throw new ApplicationException(_t('Media manifest is not available'));
		}
		# -------------------------------------------------------
	}
