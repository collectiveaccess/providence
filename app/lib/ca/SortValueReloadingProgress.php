<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/SortValueReloadingProgress.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  * Implements reloading of sortable values invoked via the web UI
  * This application dispatcher plugin ensures that the reloading starts
  * after the web UI page has been sent to the client
  */
 
 	require_once(__CA_LIB_DIR__.'/core/Controller/AppController/AppControllerPlugin.php');
 
	class SortValueReloadingProgress extends AppControllerPlugin {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function dispatchLoopShutdown() {	
		
			//
			// Force output to be sent - we need the client to have the page before
			// we start flushing progress bar updates
			//	
			$app = AppController::getInstance();
			$req = $app->getRequest();
			$resp = $app->getResponse();
			$resp->sendResponse();
			$resp->clearContent();
			
			//
			// Do reindexing
			//
			
			if ($req->isLoggedIn() && $req->user->canDoAction('can_do_search_reindex')) {
				set_time_limit(3600*8);
				$o_db = new Db();
				
				$t_timer = new Timer();
	
				$va_table_names = array(
					'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
					'ca_occurrences', 'ca_collections', 'ca_storage_locations',
					'ca_object_representations', 'ca_representation_annotations', 'ca_lists',
					'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops'
				);
				
				$vn_tc = 0;
				foreach($va_table_names as $vs_table) {
					require_once(__CA_MODELS_DIR__."/{$vs_table}.php");
					$t_table = new $vs_table;
					$vs_pk = $t_table->primaryKey();
					$qr_res = $o_db->query("SELECT {$vs_pk} FROM {$vs_table}");
					
					if ($vs_label_table_name = $t_table->getLabelTableName()) {
						require_once(__CA_MODELS_DIR__."/{$vs_label_table_name}.php");
						$va_table_names[] = $vs_label_table_name;
						
						$t_label = new $vs_label_table_name;
						$vs_label_pk = $t_label->primaryKey();
						$qr_labels = $o_db->query("SELECT {$vs_label_pk} FROM {$vs_label_table_name}");
						 
						$vn_c = 0;
						$vn_num_rows = $qr_labels->numRows();
						$vn_table_num = $t_label->tableNum();
						
						while($qr_labels->nextRow()) {
							$vn_label_pk_val = $qr_labels->get($vs_label_pk);
							
							if (!($vn_c % 100)) {
								caIncrementSortValueReloadProgress(
									$vn_c,
									$vn_num_rows,
									null, 
									null,
									$t_timer->getTime(2),
									memory_get_usage(true),
									$va_table_names,
									$vn_table_num,
									$t_label->getProperty('NAME_PLURAL'),
									$vn_tc+1
								);
							}
							
							if ($t_label->load($vn_label_pk_val)) {
								$t_label->setMode(ACCESS_WRITE);
								$t_label->update();
							}
							$vn_c++;
						}
						$vn_tc++;
					}
					
					$vn_table_num = $t_table->tableNum();
					$vn_num_rows = $qr_res->numRows();
					$vn_c  = 0;
					while($qr_res->nextRow()) {
						$vn_pk_val = $qr_res->get($vs_pk);
						
						if (!($vn_c % 100)) {
							caIncrementSortValueReloadProgress(
								$vn_c,
								$vn_num_rows,
								null, 
								null,
								$t_timer->getTime(2),
								memory_get_usage(true),
								$va_table_names,
								$vn_table_num,
								$t_table->getProperty('NAME_PLURAL'),
								$vn_tc+1
							);
						}
						
						if ($t_table->load($vn_pk_val)) {
							$t_table->setMode(ACCESS_WRITE);
							if ($vs_table == 'ca_object_representations') {
								$t_table->set('md5', $t_table->getMediaInfo('ca_object_representations.media', 'original', 'MD5'));
								$t_table->set('mimetype', $t_table->getMediaInfo('ca_object_representations.media', 'original', 'MIMETYPE'));
								
								$va_media_info = $t_table->getMediaInfo('ca_object_representations.media');
								$t_table->set('original_filename', $va_media_info['ORIGINAL_FILENAME']);
							}
							$t_table->update();
							
							if ($vs_table == 'ca_object_representations') {
								if (!$t_table->getPreferredLabelCount()) {
									$t_table->addLabel(
										array('name' => trim($va_media_info['ORIGINAL_FILENAME']) ? $va_media_info['ORIGINAL_FILENAME'] : _t('Representation')),
										$pn_locale_id,
										null,
										true
									);
								}
							}
						}
						
						$vn_c++;
					}
					
					$vn_tc++;
				}
				caIncrementSortValueReloadProgress(
					1,
					1,
					_t('Elapsed time: %1', caFormatInterval($t_timer->getTime(2))),
					_t('Index rebuild complete!'),
					$t_timer->getTime(2),
					memory_get_usage(true),
					$va_table_names,
					null,
					null,
					sizeof($va_table_names)
				);
			}
		}	
		# -------------------------------------------------------
	}
?>