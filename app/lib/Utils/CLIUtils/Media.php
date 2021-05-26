<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Media.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2019 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
	trait CLIUtilsMedia { 
		# -------------------------------------------------------
		/**
		 * Reprocess media
		 */
		public static function reprocess_media($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$quiet = $po_opts->getOption('quiet');
			$pa_mimetypes = caGetOption('mimetypes', $po_opts, null, ['delimiter' => [',', ';']]);
			$pa_versions = caGetOption('versions', $po_opts, null, ['delimiter' => [',', ';']]);
			$pa_kinds = caGetOption('kinds', $po_opts, 'all', ['forceLowercase' => true, 'validValues' => ['all', 'ca_object_representations', 'ca_attributes', 'icons'], 'delimiter' => [',', ';']]);
			
			$unprocessed = (bool)$po_opts->getOption('unprocessed');

			$va_log_options = array();
			$vs_log_dir = $po_opts->getOption('log');
			if ($vs_log_dir){
				$va_log_options = array( 'logDirectory' => $vs_log_dir );
			}
			$vs_loglevel = $po_opts->getOption('log_level');
			if ($vs_loglevel) {
				$va_log_options['logLevel'] = $vs_loglevel;
			}
			$o_log = caGetLogger( $va_log_options, 'reprocess_media_log_directory' );

			if ($o_log) { $o_log->logDebug(_t("[reprocess-media] Start preparing to reprocess media")); }

			if (in_array('all', $pa_kinds) || in_array('ca_object_representations', $pa_kinds)) {
				if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
				if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }


				if ($vn_id = (int)$po_opts->getOption('id')) {
					$vn_start = $vn_id;
					$vn_end = $vn_id;
				}

				$va_ids = array();
				if ($vs_ids = (string)$po_opts->getOption('ids')) {
					if (sizeof($va_tmp = explode(",", $vs_ids))) {
						foreach($va_tmp as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					}
				}

				$vs_sql_where = null;
				$va_params = array();

				if (sizeof($va_ids)) {
					$vs_sql_where = "WHERE ca_object_representations.representation_id IN (?)";
					$va_params[] = $va_ids;
				} else {
					if (
						(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
					) {
						$vs_sql_where = "WHERE ca_object_representations.representation_id >= ?";
						$va_params[] = $vn_start;
						if ($vn_end) {
							$vs_sql_where .= " AND ca_object_representations.representation_id <= ?";
							$va_params[] = $vn_end;
						}
					}
				}

				$vs_sql_joins = '';
				if ($vs_object_ids = (string)$po_opts->getOption('object_ids')) {
					$va_object_ids = explode(",", $vs_object_ids);
					foreach($va_object_ids as $vn_i => $vn_object_id) {
						$va_object_ids[$vn_i] = (int)$vn_object_id;
					}
					
					$vs_sql_where = ($vs_sql_where ? "WHERE " : " AND ")."(ca_objects_x_object_representations.object_id IN (?))";
					$vs_sql_joins = "INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.representation_id = ca_object_representations.representation_id";
					$va_params[] = $va_object_ids;
				}

				if ( $o_log ) {
					$o_log->logDebug( _t( "[reprocess-media] Running query for '$vs_sql_joins' and '$vs_sql_where' with params '"
					                      . str_replace(array("\r", "\n"), '',var_export( $va_params, true ) . "'" )) );
				}

				$qr_reps = $o_db->query("
					SELECT ca_object_representations.representation_id, ca_object_representations.media
					FROM ca_object_representations
					{$vs_sql_joins}
					{$vs_sql_where}
					ORDER BY ca_object_representations.representation_id
				", $va_params);

				if (!$quiet) { print CLIProgressBar::start($qr_reps->numRows(), _t('Re-processing representation media')); }
				while($qr_reps->nextRow()) {
					$va_media_info = $qr_reps->getMediaInfo('media');
					if(!is_array($va_media_info)) { continue; }
					$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

					if($unprocessed) {
						if(!sizeof(array_filter($va_media_info, function($v) {
							return isset($v['QUEUED']);
						}))) {
							if (!$quiet) { print CLIProgressBar::next(1, $vs_message); }
							continue;
						}
					}


					if (!$quiet) {
						$vs_message = _t("Re-processing %1", ($vs_original_filename ? $vs_original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id')));
						print CLIProgressBar::next(1, $vs_message);
						if ($o_log) { $o_log->logDebug($vs_message); }
					}
					$vs_mimetype = $qr_reps->getMediaInfo('media', 'original', 'MIMETYPE');
					if(is_array($pa_mimetypes) && sizeof($pa_mimetypes)) {
						$vb_mimetype_match = false;
						foreach($pa_mimetypes as $vs_mimetype_pattern) {
							if(!preg_match("!^".preg_quote($vs_mimetype_pattern)."!", $vs_mimetype)) {
								continue;
							}
							$vb_mimetype_match = true;
							break;
						}
						if (!$vb_mimetype_match) { continue; }
					}

					$t_rep->load($qr_reps->get('representation_id'));
					$t_rep->set('media', $qr_reps->getMediaPath('media', 'original'), array('original_filename' => $vs_original_filename));

					if (is_array($pa_versions) && sizeof($pa_versions)) {
						$t_rep->update(array('updateOnlyMediaVersions' =>$pa_versions));
					} else {
						$t_rep->update();
					}

					if ($t_rep->numErrors()) {
						$vs_message = _t("Error processing representation media: %1", join('; ', $t_rep->getErrors()));
						CLIUtils::addError($vs_message);
						if ($o_log) { $o_log->logDebug($vs_message); }
					}
				}
				if (!$quiet) { print CLIProgressBar::finish(); }
			}

			if ((in_array('all', $pa_kinds)  || in_array('ca_attributes', $pa_kinds)) && (!$vn_start && !$vn_end)) {
				// get all Media elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

				if (is_array($va_elements) && sizeof($va_elements)) {
					if (is_array($va_element_ids = caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))) && sizeof($va_element_ids)) {
						$qr_c = $o_db->query("
							SELECT count(*) c
							FROM ca_attribute_values
							WHERE
								element_id in (?)
						", array($va_element_ids));
						if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }

						if (!$quiet) { print CLIProgressBar::start($vn_count, _t('Re-processing attribute media')); }
						foreach($va_elements as $vs_element_code => $va_element_info) {
							$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
							$va_vals = $qr_vals->getAllFieldValues('value_id');
							foreach($va_vals as $vn_value_id) {
								$t_attr_val = new ca_attribute_values($vn_value_id);
								if ($t_attr_val->getPrimaryKey()) {
									$t_attr_val->setMode(ACCESS_WRITE);
									$t_attr_val->useBlobAsMediaField(true);

									$va_media_info = $t_attr_val->getMediaInfo('value_blob');
									$vs_original_filename = is_array($va_media_info) ? $va_media_info['ORIGINAL_FILENAME'] : '';

									if (!$quiet) {
										$vs_message = _t( "Re-processing %1",
											( $vs_original_filename ? $vs_original_filename . " ({$vn_value_id})"
												: $vn_value_id ) );
										print CLIProgressBar::next(1, $vs_message );
										if ($o_log) { $o_log->logDebug($vs_message); }
									}


									$t_attr_val->set('value_blob', $t_attr_val->getMediaPath('value_blob', 'original'), array('original_filename' => $vs_original_filename));

									$t_attr_val->update();
									if ($t_attr_val->numErrors()) {
										$vs_message = _t( "Error processing attribute media: %1",
											join( '; ', $t_attr_val->getErrors() ) );
										CLIUtils::addError( $vs_message );
										if ($o_log) { $o_log->logDebug($vs_message); }
									}
								}
							}
						}
						if (!$quiet) { print CLIProgressBar::finish(); }
					}
				}
			}
			
			if ((in_array('all', $pa_kinds)  || in_array('icons', $pa_kinds)) && (!$vn_start && !$vn_end)) {
				$icon_tables = ['ca_list_items', 'ca_storage_locations', 'ca_editor_uis', 'ca_editor_ui_screens', 'ca_tours', 'ca_tour_stops'];
				
				foreach($icon_tables as $icon_table) {
					if (!($t_instance = Datamodel::getInstance($icon_table, true))) { continue; }
					if (!$quiet) { print CLIProgressBar::start($icon_table::find('*', ['returnAs' => 'count']), _t('Re-processing icons')); }
					$qr_vals = $o_db->query("SELECT ".($pk = $t_instance->primaryKey())." FROM {$icon_table}");
					$ids = $qr_vals->getAllFieldValues($pk);
					foreach($ids as $id) {
						if ($t_instance->load($id)) {
							$t_instance->setMode(ACCESS_WRITE);

							$media_info = $t_instance->getMediaInfo($pk);

							if (!$quiet) {
								$vs_message = _t( "Re-processing %1 from %2", $id, $icon_table );
								print CLIProgressBar::next(1, $vs_message );
								if ($o_log) { $o_log->logDebug($vs_message); }
							}


							$t_instance->set('icon', ($p = $t_instance->getMediaPath('icon', 'original')) ? $p : $t_instance->getMediaPath('icon', 'iconlarge'));

							$t_instance->update();
							if ($t_instance->numErrors()) {
								$vs_message = _t( "Error processing icon media: %1", join( '; ', $t_instance->getErrors() ) );
								CLIUtils::addError( $vs_message );
								if ($o_log) { $o_log->logDebug($vs_message); }
							}
						}	
					}
					if (!$quiet) { print CLIProgressBar::finish(); }
				}
			}


			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaParamList() {
			return array(
				"mimetypes|m-s" => _t("Limit re-processing to specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
				"versions|v-s" => _t("Limit re-processing to specified versions. Separate multiple versions with commas."),
				"log|L-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
				"log_level|d-s" => _t('Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'),
				"start_id|s-n" => _t('Representation id to start reloading at'),
				"end_id|e-n" => _t('Representation id to end reloading at'),
				"id|i-n" => _t('Representation id to reload'),
				"ids|l-s" => _t('Comma separated list of representation ids to reload'),
				"object_ids|o-s" => _t('Comma separated list of object ids to reload'),
				"kinds|k-s" => _t('Comma separated list of kind of media to reprocess. Valid kinds are ca_object_representations (object representations), ca_attributes (metadata elements) and icons (icon graphics on list items, storage locations, editors, editor screens, tours and tour stops). You may also specify "all" to reprocess all kinds of media. Default is "all"'),
				"unprocessed|u" => _t('Reprocess all unprocessed media'),
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaShortHelp() {
			return _t("Re-process existing media using current media processing configuration.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reprocess_mediaHelp() {
			return _t("CollectiveAccess generates derivatives for all uploaded media.");
		}
		# -------------------------------------------------------
		/**
		 * Reindex PDF media by content for in-PDF search
		 */
		public static function reindex_pdfs($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

			if (!caPDFMinerInstalled()) {
				CLIUtils::addError(_t("Can't reindex PDFs: PDFMiner is not installed."));
				return false;
			}

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			$va_versions = array("original");
			$va_kinds = ($vs_kinds = $po_opts->getOption("kinds")) ? explode(",", $vs_kinds) : array();

			if (!is_array($va_kinds) || !sizeof($va_kinds)) {
				$va_kinds = array('all');
			}
			$va_kinds = array_map('strtolower', $va_kinds);

			if ((in_array('all', $va_kinds) || in_array('ca_object_representations', $va_kinds)) && (!$vn_start && !$vn_end)) {
				if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
				if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }


				if ($vn_id = (int)$po_opts->getOption('id')) {
					$vn_start = $vn_id;
					$vn_end = $vn_id;
				}

				$va_ids = array();
				if ($vs_ids = (string)$po_opts->getOption('ids')) {
					if (sizeof($va_tmp = explode(",", $vs_ids))) {
						foreach($va_tmp as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					}
				}

				$vs_sql_where = null;
				$va_params = array();

				if (sizeof($va_ids)) {
					$vs_sql_where = "WHERE representation_id IN (?)";
					$va_params[] = $va_ids;
				} else {
					if (
						(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
					) {
						$vs_sql_where = "WHERE representation_id >= ?";
						$va_params[] = $vn_start;
						if ($vn_end) {
							$vs_sql_where .= " AND representation_id <= ?";
							$va_params[] = $vn_end;
						}
					}
				}

				if ($vs_sql_where) { $vs_sql_where .= " AND mimetype = 'application/pdf'"; } else { $vs_sql_where = " WHERE mimetype = 'application/pdf'"; }

				$qr_reps = $o_db->query("
					SELECT representation_id, media_id
					FROM ca_object_representations
					{$vs_sql_where}
					ORDER BY representation_id
				", $va_params);

				print CLIProgressBar::start($qr_reps->numRows(), _t('Reindexing PDF representations'));

				$vn_rep_table_num = $t_rep->tableNum();
				while($qr_reps->nextRow()) {
					$va_media_info = $qr_reps->getMediaInfo('media');
					$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

					print CLIProgressBar::next(1, _t("Reindexing PDF %1", ($vs_original_filename ? $vs_original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id'))));

					$t_rep->load($qr_reps->get('representation_id'));

					$vn_rep_id = $t_rep->getPrimaryKey();

					$m = new Media();
					if(($m->read($vs_path = $t_rep->getMediaPath('media', 'original'))) && is_array($va_locs = $m->getExtractedTextLocations())) {
						MediaContentLocationIndexer::clear($vn_rep_table_num, $vn_rep_id);
						foreach($va_locs as $vs_content => $va_loc_list) {
							foreach($va_loc_list as $va_loc) {
								MediaContentLocationIndexer::index($vn_rep_table_num, $vn_rep_id, $vs_content, $va_loc['p'], $va_loc['x1'], $va_loc['y1'], $va_loc['x2'], $va_loc['y2']);
							}
						}
						MediaContentLocationIndexer::write();
					} else {
						//CLIUtils::addError(_t("[Warning] No content to reindex for PDF representation: %1", $vs_path));
					}
				}
				print CLIProgressBar::finish();
			}

			if (in_array('all', $va_kinds)  || in_array('ca_attributes', $va_kinds)) {
				// get all Media elements
				$va_elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

				$qr_c = $o_db->query("
					SELECT count(*) c
					FROM ca_attribute_values
					WHERE
						element_id in (?)
				", array(caExtractValuesFromArrayList($va_elements, 'element_id', array('preserveKeys' => false))));
				if ($qr_c->nextRow()) { $vn_count = $qr_c->get('c'); } else { $vn_count = 0; }


				$t_attr_val = new ca_attribute_values();
				$vn_attr_table_num = $t_attr_val->tableNum();

				print CLIProgressBar::start($vn_count, _t('Reindexing metadata attribute media'));
				foreach($va_elements as $vs_element_code => $va_element_info) {
					$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$va_element_info['element_id']);
					$va_vals = $qr_vals->getAllFieldValues('value_id');
					foreach($va_vals as $vn_value_id) {
						$t_attr_val = new ca_attribute_values($vn_value_id);
						if ($t_attr_val->getPrimaryKey()) {
							$t_attr_val->setMode(ACCESS_WRITE);
							$t_attr_val->useBlobAsMediaField(true);

							$va_media_info = $t_attr_val->getMediaInfo('value_blob');
							$vs_original_filename = $va_media_info['ORIGINAL_FILENAME'];

							if (!is_array($va_media_info) || ($va_media_info['MIMETYPE'] !== 'application/pdf')) { continue; }

							print CLIProgressBar::next(1, _t("Reindexing %1", ($vs_original_filename ? $vs_original_filename." ({$vn_value_id})" : $vn_value_id)));

							$m = new Media();
							if(($m->read($vs_path = $t_attr_val->getMediaPath('value_blob', 'original'))) && is_array($va_locs = $m->getExtractedTextLocations())) {
								MediaContentLocationIndexer::clear($vn_attr_table_num, $vn_attr_table_num);
								foreach($va_locs as $vs_content => $va_loc_list) {
									foreach($va_loc_list as $va_loc) {
										MediaContentLocationIndexer::index($vn_attr_table_num, $vn_value_id, $vs_content, $va_loc['p'], $va_loc['x1'], $va_loc['y1'], $va_loc['x2'], $va_loc['y2']);
									}
								}
								MediaContentLocationIndexer::write();
							} else {
								//CLIUtils::addError(_t("[Warning] No content to reindex for PDF in metadata attribute: %1", $vs_path));
							}
						}
					}
				}
				print CLIProgressBar::finish();
			}


			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsParamList() {
			return array(
				"start_id|s-n" => _t('Representation id to start reindexing at'),
				"end_id|e-n" => _t('Representation id to end reindexing at'),
				"id|i-n" => _t('Representation id to reindex'),
				"ids|l-s" => _t('Comma separated list of representation ids to reindex'),
				"kinds|k-s" => _t('Comma separated list of kind of media to reindex. Valid kinds are ca_object_representations (object representations), and ca_attributes (metadata elements). You may also specify "all" to reindex both kinds of media. Default is "all"')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsShortHelp() {
			return _t("Reindex PDF media for in-viewer content search.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function reindex_pdfsHelp() {
			return _t("The CollectiveAccess document viewer can search text within PDFs and highlight matches. To enable this feature PDF content must be analyzed and indexed. If your database predates the introduction of in-viewer PDF search in CollectiveAccess 1.4, or search is otherwise failing to work properly, you can use this command to analyze and index PDFs in the database.");
		}
		
		
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previews($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Db.php");
			require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");

			$o_db = new Db();

			$t_rep = new ca_object_representations();
			$t_rep->setMode(ACCESS_WRITE);

			if (!($vn_start = (int)$po_opts->getOption('start_id'))) { $vn_start = null; }
			if (!($vn_end = (int)$po_opts->getOption('end_id'))) { $vn_end = null; }

			$vs_sql_where = null;
			$va_params = array();
			if (
				(($vn_start > 0) && ($vn_end > 0) && ($vn_start <= $vn_end)) || (($vn_start > 0) && ($vn_end == null))
			) {
				$vs_sql_where = "WHERE annotation_id >= ?";
				$va_params[] = $vn_start;
				if ($vn_end) {
					$vs_sql_where .= " AND annotation_id <= ?";
					$va_params[] = $vn_end;
				}
			}
			$qr_reps = $o_db->query("
				SELECT annotation_id
				FROM ca_representation_annotations
				{$vs_sql_where}
				ORDER BY annotation_id
			", $va_params);

			$vn_total = $qr_reps->numRows();
			print CLIProgressBar::start($vn_total, _t('Finding annotations'));
			$vn_c = 1;
			while($qr_reps->nextRow()) {
				$t_instance = new ca_representation_annotations($vn_id = $qr_reps->get('annotation_id'));
				print CLIProgressBar::next(1, _t('Annotation %1', $vn_id));
				$t_instance->setMode(ACCESS_WRITE);
				$t_instance->update(array('forcePreviewGeneration' => true));

				$vn_c++;
			}
			print CLIProgressBar::finish();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsParamList() {
			return array(
				"start_id|s-n" => _t('Annotation id to start reloading at'),
				"end_id|e-n" => _t('Annotation id to end reloading at')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsUtilityClass() {
			return _t('Media');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsShortHelp() {
			return _t("Regenerates annotation preview media for some or all object representation annotations.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function regenerate_annotation_previewsHelp() {
			return _t("Regenerates annotation preview media for some or all object representation annotations.");
		}
		# -------------------------------------------------------
    }
