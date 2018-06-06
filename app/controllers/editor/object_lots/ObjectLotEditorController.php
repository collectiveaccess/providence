<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/collections/ObjectLotEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2015 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
 	require_once(__CA_LIB_DIR__."/BaseEditorController.php");
	require_once(__CA_LIB_DIR__.'/Parsers/ZipStream.php');
 
 	class ObjectLotEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_object_lots';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function info($pa_parameters) {
 			parent::info($pa_parameters);
 			return $this->render('widget_object_lot_info_html.php', true);
 		}
 		# -------------------------------------------------------
 		protected function _beforeDelete($pt_subject) {
			// Does this lot have any ca_object records associated?
			if ($vn_c = $pt_subject->numObjects()) {
				// Yes... we don't want the cascading delete of the lot to delete the objects
				// so we need to remove them from the lot *before* doing the lot deletion
				// Since we're going to be doing this in a transaction, if the lot delete fails out
				// unlinking of the lot's objects will be rolled back
				
				$pt_subject->removeAllObjects();
				
				if ($pt_subject->numErrors()) {
					foreach($pt_subject->errors() as $o_e) {
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);	
					}
				}
			}	
			return true;
		}
 		# -------------------------------------------------------
 		public function renumberObjects() {
 			if ((bool)$this->request->config->get('allow_automated_renumbering_of_objects_in_a_lot')) {
				$t_lot = new ca_object_lots($this->request->getParameter('lot_id', pInteger));
				if ($t_lot->getPrimaryKey()) {
					$t_lot->renumberObjects($this->opo_app_plugin_manager);
					
					if ($t_lot->numErrors()) {
						foreach($t_lot->getErrors() as $vs_error) {
							$this->notification->addNotification($vs_error, __NOTIFICATION_TYPE_ERROR__);
						}
					} else {
						$this->notification->addNotification(_t('Renumbered contents of lot'), __NOTIFICATION_TYPE_INFO__);
					}
				}
			}
 			
 			return $this->Edit();
 		}
 		# -------------------------------------------------------
 		public function getLotMedia() {
 			//if ((bool)$this->request->config->get('allow_download_of_all_object_media_in_a_lot')) {	// DO WE NEED TO REINSTATE THIS OPTIN?
 				set_time_limit(600); // allow a lot of time for this because the sets can be potentially large
				$t_lot = new ca_object_lots($this->request->getParameter('lot_id', pInteger));
				$o_media_metadata_conf = Configuration::load($t_lot->getAppConfig()->get('media_metadata'));
				if ($t_lot->getPrimaryKey()) {
					$va_object_ids = $t_lot->get('ca_objects.object_id', array('returnAsArray' => true, 'limit' => 100000));
					if(!is_array($va_object_ids) || !sizeof($va_object_ids)) {
						$this->notification->addNotification(_t('No media is available for download'), __NOTIFICATION_TYPE_ERROR__);
						$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_object_lots', $t_lot->getPrimaryKey()));
						return;
					}
					$qr_res = ca_objects::createResultSet($va_object_ids);
					$qr_res->filterNonPrimaryRepresentations(false);
					
					$va_paths = array();
					while($qr_res->nextHit()) {
						$va_original_paths = $qr_res->getMediaPaths('ca_object_representations.media', 'original');

						if(sizeof($va_original_paths)>0) {
							$va_paths[$qr_res->get('object_id')] = array(
								'idno' => $qr_res->get('idno'),
								'type_code' => caGetListItemIdno($qr_res->get('type_id')),
								'paths' => $va_original_paths,
								'representation_ids' => $qr_res->get('ca_object_representations.representation_id', array('returnAsArray' => true)),
								'representation_types' => $qr_res->get('ca_object_representations.type_id', array('returnAsArray' => true))
							);
						}
					}
					
					if (sizeof($va_paths) > 0){
						$o_zip = new ZipStream();
						
						foreach($va_paths as $vn_object_id => $va_path_info) {
							// make sure we don't download representations the user isn't allowed to read
							if(!caCanRead($this->request->user->getPrimaryKey(), 'ca_objects', $vn_object_id)){ continue; }
								
							$vn_c = 1;
							foreach($va_path_info['paths'] as $vn_i => $vs_media_path) {
								if (!file_exists($vs_media_path)) { continue; }

								if($o_media_metadata_conf->get('do_metadata_embedding_for_lot_media_download')) {
									if(!($vs_path = caEmbedMediaMetadataIntoFile($vs_media_path,
										'ca_objects', $vn_object_id, $va_path_info['type_code'],
										$va_path_info['representation_ids'][$vn_i], $va_path_info['representation_types'][$vn_i]
									))) {
										$vs_path = $vs_media_path;
									}
								} else {
									$vs_path = $vs_media_path;
								}

								$vs_filename = $va_path_info['idno'] ? $va_path_info['idno'] : $vn_object_id;
								$vs_filename .= "_{$vn_c}";
								
								if ($vs_ext = pathinfo($vs_media_path, PATHINFO_EXTENSION)) {
									$vs_filename .= ".{$vs_ext}";
								}
								$o_zip->addFile($vs_path, $vs_filename);
								
								$vn_c++;
							}
						}
						
						$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
 			
						// send files
						$o_view->setVar('zip_stream', $o_zip);
						$o_view->setVar('archive_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', ($vs_idno = $t_lot->get('idno_stub')) ? $vs_idno : $t_lot->getPrimaryKey()), 0, 20).'.zip');
						$this->response->addContent($o_view->render('download_file_binary.php'));
						return;
					} else {
						$this->notification->addNotification(_t('No files to download'), __NOTIFICATION_TYPE_ERROR__);
						$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_object_lots', $t_lot->getPrimaryKey()));
						return;
					}
				}
			//}
 			
 			return $this->Edit();
 		}
 		# -------------------------------------------------------
 	}
 ?>