<?php
/** ---------------------------------------------------------------------
 * app/lib/SortValueReloadingProgress.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2026 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/Controller/AppController/AppControllerPlugin.php');

class SortValueReloadingProgress extends AppControllerPlugin {
	# -------------------------------------------------------
	public function dispatchLoopShutdown() {	
		global $g_ui_locale_id;
		
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

			$table_names = [
				'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
				'ca_occurrences', 'ca_collections', 'ca_storage_locations',
				'ca_object_representations', 'ca_representation_annotations', 'ca_lists',
				'ca_list_items', 'ca_loans', 'ca_movements', 'ca_tours', 'ca_tour_stops'
			];
			
			$tc = 0;
			foreach($table_names as $table) {
				$t_table = new $table;
				$pk = $t_table->primaryKey();
				$deleted_sql = ($t_table->hasField('deleted')) ? " WHERE t.deleted = 0" : "";
				$qr_res = $o_db->query("SELECT t.{$pk} FROM {$table} t {$deleted_sql}");
				
				if ($label_table_name = $t_table->getLabelTableName()) {
					$table_names[] = $label_table_name;
					
					$t_label = new $label_table_name;
					$label_pk = $t_label->primaryKey();
					$qr_labels = $o_db->query("
						SELECT l.{$label_pk} 
						FROM {$label_table_name} l
						INNER JOIN {$table} AS t ON t.{$pk} = l.{$pk}
						{$deleted_sql}
					");
					 
					$c = 0;
					$num_rows = $qr_labels->numRows();
					$table_num = $t_label->tableNum();
					
					while($qr_labels->nextRow()) {
						$label_pk_val = $qr_labels->get($label_pk);
						
						if (!($c % 100)) {
							caIncrementSortValueReloadProgress(
								$c,
								$num_rows,
								null, 
								null,
								$t_timer->getTime(2),
								memory_get_usage(true),
								$table_names,
								$table_num,
								$t_label->getProperty('NAME_PLURAL'),
								$tc+1
							);
						}
						
						if ($t_label->load($label_pk_val)) {						
							$t_label->logChanges(false);
							$t_label->update(['dontDoSearchIndexing' => true]);
						}
						$c++;
					}
					$tc++;
				}
				
				$table_num = $t_table->tableNum();
				$num_rows = $qr_res->numRows();
				$c  = 0;
				while($qr_res->nextRow()) {
					$pk_val = $qr_res->get($pk);
					
					if (!($c % 100)) {
						caIncrementSortValueReloadProgress(
							$c,
							$num_rows,
							null, 
							null,
							$t_timer->getTime(2),
							memory_get_usage(true),
							$table_names,
							$table_num,
							$t_table->getProperty('NAME_PLURAL'),
							$tc+1
						);
					}
					
					if ($t_table->load($pk_val)) {
						if ($table == 'ca_object_representations') {
							$t_table->set('md5', $t_table->getMediaInfo('ca_object_representations.media', 'original', 'MD5'));
							$t_table->set('mimetype', $t_table->getMediaInfo('ca_object_representations.media', 'original', 'MIMETYPE'));
							
							$media_info = $t_table->getMediaInfo('ca_object_representations.media');
							$t_table->set('original_filename', $media_info['ORIGINAL_FILENAME']);
						}
						$t_table->logChanges(false);
						$t_table->update(['dontDoSearchIndexing' => true]);
						
						if ($table == 'ca_object_representations') {
							if (!$t_table->getPreferredLabelCount()) {
								$t_table->addLabel(
									['name' => trim($media_info['ORIGINAL_FILENAME']) ? $media_info['ORIGINAL_FILENAME'] : _t('Representation')],
									$g_ui_locale_id,
									null,
									true
								);
							}
						}
					}
					
					$c++;
				}
				
				$tc++;
			}
			caIncrementSortValueReloadProgress(
				1,
				1,
				_t('Elapsed time: %1', caFormatInterval($t_timer->getTime(2))),
				_t('Index rebuild complete!'),
				$t_timer->getTime(2),
				memory_get_usage(true),
				$table_names,
				null,
				null,
				sizeof($table_names)
			);
		}
	}	
	# -------------------------------------------------------
}
