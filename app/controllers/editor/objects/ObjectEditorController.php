<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/ObjectEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_objects.php"); 
 	require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
 	require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");
 	require_once(__CA_LIB_DIR__."/core/Media.php");
 	require_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
	require_once(__CA_LIB_DIR__."/ca/MediaContentLocationIndexer.php");
 	
 
 	class ObjectEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_objects';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			JavascriptLoadManager::register('panel');
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values=null, $pa_options=null) {
 			$va_values = array();
 			
 			if ($vn_lot_id = $this->request->getParameter('lot_id', pInteger)) {
 				$t_lot = new ca_object_lots($vn_lot_id);
 				
 				if ($t_lot->getPrimaryKey()) {
					$va_values['lot_id'] = $vn_lot_id;
					$va_values['idno'] = $t_lot->get('idno_stub');
				}
 			}
 			
 			return parent::Edit($va_values, $pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function postSave($t_object, $pb_is_insert) {
 			if ( $this->request->config->get('ca_objects_x_collections_hierarchy_enabled') && ($vs_coll_rel_type = $this->request->config->get('ca_objects_x_collections_hierarchy_relationship_type')) && ($pn_collection_id = $this->request->getParameter('collection_id', pInteger))) {
 				if (!($t_object->addRelationship('ca_collections', $pn_collection_id, $vs_coll_rel_type))) {
 					$this->notification->addNotification(_t("Could not add parent collection to object", __NOTIFICATION_TYPE_ERROR__));
 				}
 			}
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
 		/**
 		 * Returns content for overlay containing details for object representation
 		 *
 		 * Expects the following request parameters: 
 		 *		object_id = the id of the ca_objects record to display
 		 *		representation_id = the id of the ca_object_representations record to display; the representation must belong to the specified object
 		 *
 		 *	Optional request parameters:
 		 *		version = The version of the representation to display. If omitted the display version configured in media_display.conf is used
 		 *
 		 */ 
 		public function GetRepresentationInfo() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
 		
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			if(!$vn_object_id) { 
 				if (is_array($va_object_ids = $t_rep->get('ca_objects.object_id', array('returnAsArray' => true))) && sizeof($va_object_ids)) {
 					$vn_object_id = array_shift($va_object_ids);
 				} else {
 					$this->postError(1100, _t('Invalid object/representation'), 'ObjectEditorController->GetRepresentationInfo');
 					return;
 				}
 			}
 			$va_opts = array('display' => 'media_overlay', 'object_id' => $vn_object_id, 'containerID' => 'caMediaPanelContentArea');
 			if (strlen($vs_use_book_viewer = $this->request->getParameter('use_book_viewer', pInteger))) { $va_opts['use_book_viewer'] = (bool)$vs_use_book_viewer; }
 
 			$this->response->addContent($t_rep->getRepresentationViewerHTMLBundle($this->request, $va_opts));
 		}
 		# -------------------------------------------------------
 		/**
 		 * xxx
 		 *
 		 * Expects the following request parameters: 
 		 *		object_id = the id of the ca_objects record to display
 		 *		representation_id = the id of the ca_object_representations record to display; the representation must belong to the specified object
 		 *
 		 *	Optional request parameters:
 		 *		version = The version of the representation to display. If omitted the display version configured in media_display.conf is used
 		 *
 		 */ 
 		public function GetRepresentationEditor() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
 			$pb_reload 	= (bool)$this->request->getParameter('reload', pInteger);
 			
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			if(!$vn_object_id) { 
 				if (is_array($va_object_ids = $t_rep->get('ca_objects.object_id', array('returnAsArray' => true))) && sizeof($va_object_ids)) {
 					$vn_object_id = array_shift($va_object_ids);
 				} else {
 					$this->postError(1100, _t('Invalid object/representation'), 'ObjectEditorController->GetRepresentationEditor');
 					return;
 				}
 			}
 			
 			$va_opts = array('display' => 'media_editor', 'object_id' => $vn_object_id, 'containerID' => 'caMediaPanelContentArea', 'mediaEditor' => true, 'noControls' => $pb_reload);
 			if (strlen($vs_use_book_viewer = $this->request->getParameter('use_book_viewer', pInteger))) { $va_opts['use_book_viewer'] = (bool)$vs_use_book_viewer; }
 
 			$this->response->addContent($t_rep->getRepresentationViewerHTMLBundle($this->request, $va_opts));
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function GetAnnotations() {
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			$va_annotations_raw = $t_rep->getAnnotations();
 			$va_annotations = array();
 			
 			foreach($va_annotations_raw as $vn_annotation_id => $va_annotation) {
 				$va_annotations[] = array(
 					'annotation_id' => $va_annotation['annotation_id'],
 					'x' => (float)$va_annotation['x'],
 					'y' => (float)$va_annotation['y'],
 					'w' => (float)$va_annotation['w'],
 					'h' => (float)$va_annotation['h'],
 					'tx' => (float)$va_annotation['tx'],
 					'ty' => (float)$va_annotation['ty'],
 					'tw' => (float)$va_annotation['tw'],
 					'th' => (float)$va_annotation['th'],
 					'points' => $va_annotation['points'],
 					'label' => (string)$va_annotation['label'],
 					'description' => (string)$va_annotation['description'],
 					'type' => (string)$va_annotation['type'],
 					'options' => $va_annotation['options']
 				);
 			}
 			
 			$this->view->setVar('annotations', $va_annotations);
 			$this->render('ajax_representation_annotations_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function SaveAnnotations() {
 			global $g_ui_locale_id;
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			$pa_annotations = $this->request->getParameter('save', pArray);
 		
 			$va_annotation_ids = array();
 			if (is_array($pa_annotations)) {
 				foreach($pa_annotations as $vn_i => $va_annotation) {
 					$vs_label = (isset($va_annotation['label']) && ($va_annotation['label'])) ? $va_annotation['label'] : '';
 					if (isset($va_annotation['annotation_id']) && ($vn_annotation_id = $va_annotation['annotation_id'])) {
 						// edit existing annotation
 						$t_rep->editAnnotation($vn_annotation_id, $g_ui_locale_id, $va_annotation, 0, 0);
 						$va_annotation_ids[$va_annotation['index']] = $vn_annotation_id;
 					} else {
 						// new annotation
 						$va_annotation_ids[$va_annotation['index']] = $t_rep->addAnnotation($vs_label, $g_ui_locale_id, $this->request->getUserID(), $va_annotation, 0, 0);
 					}
 				}
 			}
 			$va_annotations = array(
 				'error' => $t_rep->numErrors() ? join("; ", $t_rep->getErrors()) : null,
 				'annotation_ids' => $va_annotation_ids
 			);
 			
 			$pa_annotations = $this->request->getParameter('delete', pArray);
 			
 			if (is_array($pa_annotations)) {
 				foreach($pa_annotations as $vn_to_delete_annotation_id) {
 					$t_rep->removeAnnotation($vn_to_delete_annotation_id);
 				}
 			}
 			
 			
 			$this->view->setVar('annotations', $va_annotations);
 			$this->render('ajax_representation_annotations_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function ViewerHelp() {
 			$this->render('viewer_help_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function ProcessMedia() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
 			$ps_op 					= $this->request->getParameter('op', pString);
 			$pn_angle 				= $this->request->getParameter('angle', pInteger);
 			$pb_revert 				= (bool)$this->request->getParameter('revert', pInteger);
 			
 			$t_rep = new ca_object_representations($pn_representation_id);
 			if (!$t_rep->getPrimaryKey()) { 
 				$va_response = array(
 					'action' => 'process', 'status' => 20, 'message' => _t('Invalid representation_id')
 				);
 			} else {
				if ($t_rep->applyMediaTransformation('media', $ps_op, array('angle' => $pn_angle), array('revert' => $pb_revert))) {
					$va_response = array(
						'action' => 'process', 'status' => 0, 'message' => 'OK', 'op' => $ps_op, 'angle' => $pn_angle
					);
				} else {
					$va_response = array(
						'action' => 'process', 'status' => 10, 'message' => _t('Transformation failed')
					);
				}
			}
 			
 			$this->view->setVar('response', $va_response);
 			$this->render('object_representation_process_media_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function RevertMedia() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id 	= $this->request->getParameter('representation_id', pInteger);
 			if(!$vn_object_id) { $vn_object_id = 0; }
 			$t_rep = new ca_object_representations($pn_representation_id);
 			if ($t_rep->removeMediaTransformations('media')) {
 				$va_response = array(
 					'action' => 'revert', 'status' => 0
 				);
 			} else {
 				$va_response = array(
 					'action' => 'revert', 'status' => 10
 				);
 			}
 			$this->view->setVar('response', $va_response);
 			$this->render('object_representation_process_media_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Download all media attached to specified object (not necessarily open for editing)
 		 * Includes all representation media attached to the specified object + any media attached to oter
 		 * objects in the same object hierarchy as the specified object. Used by the book viewer interfacce to 
 		 * initiate a download.
 		 */ 
 		public function DownloadMedia($pa_options=null) {
 			$pn_object_id = $this->request->getParameter('object_id', pInteger);
 			$t_object = new ca_objects($pn_object_id);
 			if (!($vn_object_id = $t_object->getPrimaryKey())) { return; }
 			
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			if (!$ps_version) { $ps_version = 'original'; }
 			$this->view->setVar('version', $ps_version);
 			
 			$va_ancestor_ids = $t_object->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true));
			if ($vn_parent_id = array_pop($va_ancestor_ids)) {
				$t_object->load($vn_parent_id);
				array_unshift($va_ancestor_ids, $vn_parent_id);
			}
			
			$va_child_ids = $t_object->getHierarchyChildren(null, array('idsOnly' => true));
			
			foreach($va_ancestor_ids as $vn_id) {
				array_unshift($va_child_ids, $vn_id);
			}
			
			$vn_c = 1;
			$va_file_names = array();
			$va_file_paths = array();
			
			foreach($va_child_ids as $vn_object_id) {
				$t_object = new ca_objects($vn_object_id);
				if (!$t_object->getPrimaryKey()) { continue; }
				
				$va_reps = $t_object->getRepresentations(array($ps_version));
				$vs_idno = $t_object->get('idno');
				
				foreach($va_reps as $vn_representation_id => $va_rep) {
					if ($pn_representation_id && ($pn_representation_id != $vn_representation_id)) { continue; }
					$va_rep_info = $va_rep['info'][$ps_version];
					$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $vs_idno);
					switch($this->request->user->getPreference('downloaded_file_naming')) {
						case 'idno':
							$vs_file_name = $vs_idno_proc.'_'.$vn_c.'.'.$va_rep_info['EXTENSION'];
							break;
						case 'idno_and_version':
							$vs_file_name = $vs_idno_proc.'_'.$ps_version.'_'.$vn_c.'.'.$va_rep_info['EXTENSION'];
							break;
						case 'idno_and_rep_id_and_version':
							$vs_file_name = $vs_idno_proc.'_representation_'.$vn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION'];
							break;
						case 'original_name':
						default:
							if ($va_rep['info']['original_filename']) {
								$va_tmp = explode('.', $va_rep['info']['original_filename']);
								if (sizeof($va_tmp) > 1) { 
									if (strlen($vs_ext = array_pop($va_tmp)) < 3) {
										$va_tmp[] = $vs_ext;
									}
								}
								$vs_file_name = join('_', $va_tmp); 					
							} else {
								$vs_file_name = $vs_idno_proc.'_representation_'.$vn_representation_id.'_'.$ps_version;
							}
							
							if (isset($va_file_names[$vs_file_name.'.'.$va_rep_info['EXTENSION']])) {
								$vs_file_name.= "_{$vn_c}";
							}
							$vs_file_name .= '.'.$va_rep_info['EXTENSION'];
							break;
					} 
					
					$va_file_names[$vs_file_name] = true;
					$this->view->setVar('version_download_name', $vs_file_name);
				
					//
					// Perform metadata embedding
					$t_rep = new ca_object_representations($va_rep['representation_id']);
					if (!($vs_path = caEmbedMetadataIntoRepresentation($t_object, $t_rep, $ps_version))) {
						$vs_path = $t_rep->getMediaPath('media', $ps_version);
					}
					$va_file_paths[$vs_path] = $vs_file_name;
					
					$vn_c++;
				}
			}
			
			if (sizeof($va_file_paths) > 1) {
				if (!($vn_limit = ini_get('max_execution_time'))) { $vn_limit = 30; }
				set_time_limit($vn_limit * 2);
				$o_zip = new ZipFile();
				foreach($va_file_paths as $vs_path => $vs_name) {
					$o_zip->addFile($vs_path, $vs_name, null, array('compression' => 0));	// don't try to compress
				}
				$this->view->setVar('archive_path', $vs_path = $o_zip->output(ZIPFILE_FILEPATH));
				$this->view->setVar('archive_name', preg_replace('![^A-Za-z0-9\.\-]+!', '_', $t_object->get('idno')).'.zip');
				
 				$vn_rc = $this->render('object_download_media_binary.php');
				
 				if ($vs_path) { unlink($vs_path); }
			} else {
				foreach($va_file_paths as $vs_path => $vs_name) {
					$this->view->setVar('archive_path', $vs_path);
					$this->view->setVar('archive_name', $vs_name);
				}
 				$vn_rc = $this->render('object_download_media_binary.php');
			}
			
 			return $vn_rc;
 		}
 		# -------------------------------------------------------
 		/**
 		 * Download single representation from currently open object
 		 */ 
 		public function DownloadRepresentation() {
 			list($vn_object_id, $t_object) = $this->_initView();
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_version = $this->request->getParameter('version', pString);
 			
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			$this->view->setVar('t_object_representation', $t_rep);
 			
 			$va_versions = $t_rep->getMediaVersions('media');
 			
 			if (!in_array($ps_version, $va_versions)) { $ps_version = $va_versions[0]; }
 			$this->view->setVar('version', $ps_version);
 			
 			$va_rep_info = $t_rep->getMediaInfo('media', $ps_version);
 			$this->view->setVar('version_info', $va_rep_info);
 			
 			$va_info = $t_rep->getMediaInfo('media');
 			$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $t_object->get('idno'));
 			switch($this->request->user->getPreference('downloaded_file_naming')) {
 				case 'idno':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'.'.$va_rep_info['EXTENSION']);
					break;
 				case 'idno_and_version':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					break;
 				case 'idno_and_rep_id_and_version':
 					$this->view->setVar('version_download_name', $vs_idno_proc.'_representation_'.$pn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					break;
 				case 'original_name':
 				default:
 					if ($va_info['ORIGINAL_FILENAME']) {
						$va_tmp = explode('.', $va_info['ORIGINAL_FILENAME']);
						if (sizeof($va_tmp) > 1) { 
							if (strlen($vs_ext = array_pop($va_tmp)) < 3) {
								$va_tmp[] = $vs_ext;
							}
						}
						$this->view->setVar('version_download_name', join('_', $va_tmp).'.'.$va_rep_info['EXTENSION']);					
 					} else {
 						$this->view->setVar('version_download_name', $vs_idno_proc.'_representation_'.$pn_representation_id.'_'.$ps_version.'.'.$va_rep_info['EXTENSION']);
 					}
 					break;
 			} 
 			
 			//
 			// Perform metadata embedding
 			if ($vs_path = caEmbedMetadataIntoRepresentation($t_object, $t_rep, $ps_version)) {
 				$this->view->setVar('version_path', $vs_path);
 			} else {
 				$this->view->setVar('version_path', $t_rep->getMediaPath('media', $ps_version));
 			}
 			$vn_rc = $this->render('object_representation_download_binary.php');
 			if ($vs_path) { unlink($vs_path); }
 			exit;
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function GetPageListAsJSON() {
 			$pn_object_id = $this->request->getParameter('object_id', pInteger);
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_content_mode = $this->request->getParameter('content_mode', pString);
 			
 			$this->view->setVar('object_id', $pn_object_id);
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$this->view->setVar('content_mode', $ps_content_mode);
 			
 			$va_page_list_cache = $this->request->session->getVar('caDocumentViewerPageListCache');
 			
 			$va_pages = $va_page_list_cache[$pn_object_id.'/'.$pn_representation_id];
 			if (!isset($va_pages)) {
 				// Page cache not set?
 				$this->postError(1100, _t('Invalid object/representation'), 'ObjectEditorController->GetPage');
 				return;
 			}
 			
 			$va_section_cache = $this->request->session->getVar('caDocumentViewerSectionCache');
 			$this->view->setVar('pages', $va_pages);
 			$this->view->setVar('sections', $va_section_cache[$pn_object_id.'/'.$pn_representation_id]);
 			
 			$this->view->setVar('is_searchable', MediaContentLocationIndexer::hasIndexing('ca_object_representations', $pn_representation_id));
 			
 			$this->render('object_representation_page_list_json.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function SearchWithinMedia() {
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_q = $this->request->getParameter('q', pString);
 			
 			$va_results = MediaContentLocationIndexer::SearchWithinMedia($ps_q, 'ca_object_representations', $pn_representation_id, 'media');
 			$this->view->setVar('results', $va_results);
 			
 			$this->render('object_representation_within_media_search_results_json.php');
		}
		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function MediaReplicationControls($pt_representation=null) {
 			if ($pt_representation) {
 				$pn_representation_id = $pt_representation->getPrimaryKey();
 				$t_rep = $pt_representation;
 			} else {
 				$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 				$t_rep = new ca_object_representations($pn_representation_id);
 			}
 			$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$this->view->setVar('t_representation', $t_rep);
 		
 			$this->render('object_representation_media_replication_controls_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function StartMediaReplication() {
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_target = $this->request->getParameter('target', pString);
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$this->view->setVar('t_representation', $t_rep);
 			$this->view->setVar('selected_target', $ps_target);
 			
 			$t_rep->replicateMedia('media', $ps_target);
 			
 			$this->MediaReplicationControls($t_rep);
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */ 
 		public function RemoveMediaReplication() {
 			$pn_representation_id = $this->request->getParameter('representation_id', pInteger);
 			$ps_target = $this->request->getParameter('target', pString);
 			$ps_key = urldecode($this->request->getParameter('key', pString));
 			$t_rep = new ca_object_representations($pn_representation_id);
 			
 			$this->view->setVar('target_list', $t_rep->getAvailableMediaReplicationTargetsAsHTMLFormElement('target', 'media'));
 			$this->view->setVar('representation_id', $pn_representation_id);
 			$this->view->setVar('t_representation', $t_rep);
 			$this->view->setVar('selected_target', $ps_target);
 			
 			$t_rep->removeMediaReplication('media', $ps_target, $ps_key);
 			
 			$this->MediaReplicationControls($t_rep);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_object_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>