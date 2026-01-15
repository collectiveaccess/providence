<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Media.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2026 Whirl-i-Gig
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
	public static function reprocess_media($opts=null) {
		$o_db = new Db();
		$t_rep = new ca_object_representations();

		$quiet = $opts->getOption('quiet');
		$mimetypes = caGetOption('mimetypes', $opts, null, ['delimiter' => [',', ';']]);
		$skip_mimetypes = caGetOption('skip-mimetypes', $opts, null, ['delimiter' => [',', ';']]);
		$versions = caGetOption('versions', $opts, null, ['delimiter' => [',', ';']]);
		$kinds = caGetOption('kinds', $opts, 'ca_object_representations', ['forceLowercase' => true, 'validValues' => ['all', 'ca_object_representations', 'ca_attributes', 'icons'], 'delimiter' => [',', ';']]);
		
		$unprocessed = (bool)$opts->getOption('unprocessed');
		$oriented_only = (bool)$opts->getOption('oriented-only');
		if($extract_metadata_only = (bool)$opts->getOption('extract-metadata-only')) {
			$kinds = ['ca_object_representations'];
		}
	
		$log_options = [];
		$log_dir = $opts->getOption('log');
		$loglevel = $opts->getOption('log_level');
		if ($loglevel) {
			$log_options['logLevel'] = $loglevel;
		}
		$o_log = caGetLogger( $log_options, 'reprocess_media_log_directory' );

		if ($o_log) { $o_log->logDebug(_t("[reprocess-media] Start preparing to reprocess media")); }

		if (in_array('all', $kinds) || in_array('ca_object_representations', $kinds)) {
			if (!($start = (int)$opts->getOption('start_id'))) { $start = null; }
			if (!($end = (int)$opts->getOption('end_id'))) { $end = null; }


			if ($id = (int)$opts->getOption('id')) {
				$start = $id;
				$end = $id;
			}

			$ids = [];
			if ($opt_ids = (string)$opts->getOption('ids')) {
				if (sizeof($tmp = explode(",", $opt_ids))) {
					foreach($tmp as $id) {
						if ((int)$id > 0) {
							$ids[] = (int)$id;
						}
					}
				}
			}

			$sql_where = null;
			$params = [];

			if (sizeof($ids)) {
				$sql_where = "WHERE ca_object_representations.representation_id IN (?)";
				$params[] = $ids;
			} else {
				if (
					(($start > 0) && ($end > 0) && ($start <= $end)) || (($start > 0) && ($end == null))
				) {
					$sql_where = "WHERE ca_object_representations.representation_id >= ?";
					$params[] = $start;
					if ($end) {
						$sql_where .= " AND ca_object_representations.representation_id <= ?";
						$params[] = $end;
					}
				}
			}

			$sql_joins = '';
			if ($object_ids = (string)$opts->getOption('object_ids')) {
				$object_ids = explode(",", $object_ids);
				foreach($object_ids as $i => $object_id) {
					$object_ids[$i] = (int)$object_id;
				}
				
				$sql_where = ($sql_where ? "WHERE " : " AND ")."(ca_objects_x_object_representations.object_id IN (?))";
				$sql_joins = "INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.representation_id = ca_object_representations.representation_id";
				$params[] = $object_ids;
			}

			if ( $o_log ) {
				$o_log->logDebug( _t( "[reprocess-media] Running query for '$sql_joins' and '$sql_where' with params '"
									  . str_replace(array("\r", "\n"), '',var_export( $params, true ) . "'" )) );
			}

			$qr_c = $o_db->query("
				SELECT count(*) c
				FROM ca_object_representations
				{$sql_joins}
				{$sql_where}
			", $params);
			$total = null;
			if($qr_c && $qr_c->nextRow()) {
				$total = $qr_c->get('c');
			}
			if (!$quiet) { print CLIProgressBar::start($total, _t('Re-processing representation media')); }
			$c = 0; $inc = 50;
			do {
				$qr_reps = $o_db->query("
					SELECT ca_object_representations.representation_id, ca_object_representations.media, ca_object_representations.media_metadata
					FROM ca_object_representations
					{$sql_joins}
					{$sql_where}
					ORDER BY ca_object_representations.representation_id
					LIMIT {$c}, {$inc}
				", $params);
				$n = $qr_reps->numRows();
				
				while($qr_reps->nextRow()) {
					$media_info = $qr_reps->getMediaInfo('media');
					if(!is_array($media_info)) { print CLIProgressBar::next(1, "SKIPPED"); continue; }
					$media_metadata = caUnserializeForDatabase($qr_reps->get('ca_object_representations.media_metadata'));
					if($oriented_only && $media_metadata['EXIF']['IFD0']['Orientation'] == 1) { print CLIProgressBar::next(1, "SKIPPED"); continue; }
				
					$original_filename = $media_info['ORIGINAL_FILENAME'];

					if($unprocessed) {
						if(!sizeof(array_filter($media_info, function($v) {
							return isset($v['QUEUED']);
						}))) {
							if (!$quiet) { print CLIProgressBar::next(1, $message); }
							continue;
						}
					}


					if (!$quiet) {
						$message = _t("Re-processing %1", ($original_filename ? $original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id')));
						print CLIProgressBar::next(1, $message);
						if ($o_log) { $o_log->logDebug($message); }
					}
					$mimetype = $qr_reps->getMediaInfo('media', 'original', 'MIMETYPE');
					if(is_array($mimetypes) && sizeof($mimetypes)) {
						if(!caMimetypeIsValid($mimetype, $mimetypes)) { continue; }
					}
					if(is_array($skip_mimetypes) && sizeof($skip_mimetypes)) {
						if(caMimetypeIsValid($mimetype, $skip_mimetypes)) { continue; }
					}

					$t_rep->load($qr_reps->get('representation_id'));
					
					if($extract_metadata_only) {
						$t_rep->updateExtractedMediaMetadata();
					} else {
						$t_rep->set('media', $qr_reps->getMediaPath('media', 'original'), array('original_filename' => $original_filename));
	
						if (is_array($versions) && sizeof($versions)) {
							$t_rep->update(array('updateOnlyMediaVersions' =>$versions));
						} else {
							$t_rep->update();
						}
	
						if ($t_rep->numErrors()) {
							$message = _t("Error processing representation media: %1", join('; ', $t_rep->getErrors()));
							CLIUtils::addError($message);
							if ($o_log) { $o_log->logDebug($message); }
						}
					}
				}
				$c += $inc;
			} while($n > 0);
			if (!$quiet) { print CLIProgressBar::finish(); }
		}

		if ((in_array('all', $kinds)  || in_array('ca_attributes', $kinds)) && (!$start && !$end)) {
			// get all Media elements
			$elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

			if (is_array($elements) && sizeof($elements)) {
				if (is_array($element_ids = caExtractValuesFromArrayList($elements, 'element_id', array('preserveKeys' => false))) && sizeof($element_ids)) {
					$qr_c = $o_db->query("
						SELECT count(*) c
						FROM ca_attribute_values
						WHERE
							element_id in (?)
					", array($element_ids));
					if ($qr_c->nextRow()) { $count = $qr_c->get('c'); } else { $count = 0; }

					if (!$quiet) { print CLIProgressBar::start($count, _t('Re-processing attribute media')); }
					foreach($elements as $element_code => $element_info) {
						$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$element_info['element_id']);
						$vals = $qr_vals->getAllFieldValues('value_id');
						foreach($vals as $value_id) {
							$t_attr_val = new ca_attribute_values($value_id);
							if ($t_attr_val->getPrimaryKey()) {
								$t_attr_val->useBlobAsMediaField(true);

								$media_info = $t_attr_val->getMediaInfo('value_blob');
								$original_filename = is_array($media_info) ? $media_info['ORIGINAL_FILENAME'] : '';

								if (!$quiet) {
									$message = _t( "Re-processing %1",
										( $original_filename ? $original_filename . " ({$value_id})"
											: $value_id ) );
									print CLIProgressBar::next(1, $message );
									if ($o_log) { $o_log->logDebug($message); }
								}


								$t_attr_val->set('value_blob', $t_attr_val->getMediaPath('value_blob', 'original'), array('original_filename' => $original_filename));

								$t_attr_val->update();
								if ($t_attr_val->numErrors()) {
									$message = _t( "Error processing attribute media: %1",
										join( '; ', $t_attr_val->getErrors() ) );
									CLIUtils::addError( $message );
									if ($o_log) { $o_log->logDebug($message); }
								}
							}
						}
					}
					if (!$quiet) { print CLIProgressBar::finish(); }
				}
			}
		}
		
		if ((in_array('all', $kinds)  || in_array('icons', $kinds)) && (!$start && !$end)) {
			$icon_tables = ['ca_list_items', 'ca_storage_locations', 'ca_editor_uis', 'ca_editor_ui_screens', 'ca_tours', 'ca_tour_stops'];
			
			foreach($icon_tables as $icon_table) {
				if (!($t_instance = Datamodel::getInstance($icon_table, true))) { continue; }
				if (!$quiet) { print CLIProgressBar::start($icon_table::find('*', ['returnAs' => 'count']), _t('Re-processing icons')); }
				$qr_vals = $o_db->query("SELECT ".($pk = $t_instance->primaryKey())." FROM {$icon_table}");
				$ids = $qr_vals->getAllFieldValues($pk);
				foreach($ids as $id) {
					if ($t_instance->load($id)) {

						$media_info = $t_instance->getMediaInfo($pk);

						if (!$quiet) {
							$message = _t( "Re-processing %1 from %2", $id, $icon_table );
							print CLIProgressBar::next(1, $message );
							if ($o_log) { $o_log->logDebug($message); }
						}


						$t_instance->set('icon', ($p = $t_instance->getMediaPath('icon', 'original')) ? $p : $t_instance->getMediaPath('icon', 'iconlarge'));

						$t_instance->update();
						if ($t_instance->numErrors()) {
							$message = _t( "Error processing icon media: %1", join( '; ', $t_instance->getErrors() ) );
							CLIUtils::addError( $message );
							if ($o_log) { $o_log->logDebug($message); }
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
			"skip-mimetypes|x-s" => _t("Do not reprocess specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
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
			"oriented-only|y-i" => _t('Only reprocess image media having the EXIF orientation set to a value > 1'),
			"extract-metadata-only|z-i" => _t('Only extract embedded metadata from media. Do not reprocess.'),
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
	public static function reindex_pdfs($opts=null) {
		if (!caPDFMinerInstalled()) {
			CLIUtils::addError(_t("Can't reindex PDFs: PDFMiner is not installed."));
			return false;
		}

		$o_db = new Db();

		$t_rep = new ca_object_representations();

		$versions = array("original");
		$kinds = ($kinds = $opts->getOption("kinds")) ? explode(",", $kinds) : [];

		if (!is_array($kinds) || !sizeof($kinds)) {
			$kinds = array('all');
		}
		$kinds = array_map('strtolower', $kinds);

		if ((in_array('all', $kinds) || in_array('ca_object_representations', $kinds)) && (!$start && !$end)) {
			if (!($start = (int)$opts->getOption('start_id'))) { $start = null; }
			if (!($end = (int)$opts->getOption('end_id'))) { $end = null; }


			if ($id = (int)$opts->getOption('id')) {
				$start = $id;
				$end = $id;
			}

			$ids = [];
			if ($ids = (string)$opts->getOption('ids')) {
				if (sizeof($tmp = explode(",", $ids))) {
					foreach($tmp as $id) {
						if ((int)$id > 0) {
							$ids[] = (int)$id;
						}
					}
				}
			}

			$sql_where = null;
			$params = [];

			if (sizeof($ids)) {
				$sql_where = "WHERE representation_id IN (?)";
				$params[] = $ids;
			} else {
				if (
					(($start > 0) && ($end > 0) && ($start <= $end)) || (($start > 0) && ($end == null))
				) {
					$sql_where = "WHERE representation_id >= ?";
					$params[] = $start;
					if ($end) {
						$sql_where .= " AND representation_id <= ?";
						$params[] = $end;
					}
				}
			}

			if ($sql_where) { $sql_where .= " AND mimetype = 'application/pdf'"; } else { $sql_where = " WHERE mimetype = 'application/pdf'"; }

			$qr_reps = $o_db->query("
				SELECT representation_id, media
				FROM ca_object_representations
				{$sql_where}
				ORDER BY representation_id
			", $params);

			print CLIProgressBar::start($qr_reps->numRows(), _t('Reindexing PDF representations'));

			$rep_table_num = $t_rep->tableNum();
			while($qr_reps->nextRow()) {
				$media_info = $qr_reps->getMediaInfo('media');
				$original_filename = $media_info['ORIGINAL_FILENAME'];

				print CLIProgressBar::next(1, _t("Reindexing PDF %1", ($original_filename ? $original_filename." (".$qr_reps->get('representation_id').")" : $qr_reps->get('representation_id'))));

				$t_rep->load($qr_reps->get('representation_id'));

				$rep_id = $t_rep->getPrimaryKey();

				$m = new Media();
				if(($m->read($path = $t_rep->getMediaPath('media', 'original'))) && is_array($locs = $m->getExtractedTextLocations())) {
					MediaContentLocationIndexer::clear($rep_table_num, $rep_id);
					foreach($locs as $content => $loc_list) {
						foreach($loc_list as $loc) {
							MediaContentLocationIndexer::index($rep_table_num, $rep_id, $content, $loc['p'], $loc['x1'], $loc['y1'], $loc['x2'], $loc['y2']);
						}
					}
					MediaContentLocationIndexer::write();
				} else {
					//CLIUtils::addError(_t("[Warning] No content to reindex for PDF representation: %1", $path));
				}
			}
			print CLIProgressBar::finish();
		}

		if (in_array('all', $kinds)  || in_array('ca_attributes', $kinds)) {
			// get all Media elements
			$elements = ca_metadata_elements::getElementsAsList(false, null, null, true, false, true, array(16)); // 16=media

			$qr_c = $o_db->query("
				SELECT count(*) c
				FROM ca_attribute_values
				WHERE
					element_id in (?)
			", array(caExtractValuesFromArrayList($elements, 'element_id', array('preserveKeys' => false))));
			if ($qr_c->nextRow()) { $count = $qr_c->get('c'); } else { $count = 0; }


			$t_attr_val = new ca_attribute_values();
			$attr_table_num = $t_attr_val->tableNum();

			print CLIProgressBar::start($count, _t('Reindexing metadata attribute media'));
			foreach($elements as $element_code => $element_info) {
				$qr_vals = $o_db->query("SELECT value_id FROM ca_attribute_values WHERE element_id = ?", (int)$element_info['element_id']);
				$vals = $qr_vals->getAllFieldValues('value_id');
				foreach($vals as $value_id) {
					$t_attr_val = new ca_attribute_values($value_id);
					if ($t_attr_val->getPrimaryKey()) {
						$t_attr_val->useBlobAsMediaField(true);

						$media_info = $t_attr_val->getMediaInfo('value_blob');
						$original_filename = $media_info['ORIGINAL_FILENAME'];

						if (!is_array($media_info) || ($media_info['MIMETYPE'] !== 'application/pdf')) { continue; }

						print CLIProgressBar::next(1, _t("Reindexing %1", ($original_filename ? $original_filename." ({$value_id})" : $value_id)));

						$m = new Media();
						if(($m->read($path = $t_attr_val->getMediaPath('value_blob', 'original'))) && is_array($locs = $m->getExtractedTextLocations())) {
							MediaContentLocationIndexer::clear($attr_table_num, $attr_table_num);
							foreach($locs as $content => $loc_list) {
								foreach($loc_list as $loc) {
									MediaContentLocationIndexer::index($attr_table_num, $value_id, $content, $loc['p'], $loc['x1'], $loc['y1'], $loc['x2'], $loc['y2']);
								}
							}
							MediaContentLocationIndexer::write();
						} else {
							//CLIUtils::addError(_t("[Warning] No content to reindex for PDF in metadata attribute: %1", $path));
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
	public static function regenerate_annotation_previews($opts=null) {
		$o_db = new Db();
		$t_rep = new ca_object_representations();

		if (!($start = (int)$opts->getOption('start_id'))) { $start = null; }
		if (!($end = (int)$opts->getOption('end_id'))) { $end = null; }

		$sql_where = null;
		$params = [];
		if (
			(($start > 0) && ($end > 0) && ($start <= $end)) || (($start > 0) && ($end == null))
		) {
			$sql_where = "WHERE annotation_id >= ?";
			$params[] = $start;
			if ($end) {
				$sql_where .= " AND annotation_id <= ?";
				$params[] = $end;
			}
		}
		$qr_reps = $o_db->query("
			SELECT annotation_id
			FROM ca_representation_annotations
			{$sql_where}
			ORDER BY annotation_id
		", $params);

		$total = $qr_reps->numRows();
		print CLIProgressBar::start($total, _t('Finding annotations'));
		$c = 1;
		while($qr_reps->nextRow()) {
			$t_instance = new ca_representation_annotations($id = $qr_reps->get('annotation_id'));
			print CLIProgressBar::next(1, _t('Annotation %1', $id));
			$t_instance->update(array('forcePreviewGeneration' => true));

			$c++;
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
	/**
	 *
	 */
	public static function find_duplicate_media($opts=null) {
		if (!($filename = $opts->getOption('file'))) {
			print _t('You must specify a file to write report output to.')."\n";
			return false;
		}
		
		print CLIProgressBar::start(1, _t('Finding duplicates'));
		
		$o_db = new Db();
		
		$qr = $o_db->query("
			SELECT md5, count(*) c
			FROM ca_object_representations
			WHERE
				deleted = 0
			GROUP BY md5
			HAVING  c > 1
		");
		
		$dupe_md5s = $qr->getAllFieldValues('md5');
		
		print CLIProgressBar::start($n = sizeof($dupe_md5s), _t('Analyzing duplicates'));
		
		$fp = fopen($filename, "w");
		
		$lines = [];
		$headers = ['MD5', 'Count', 'Table', 'Idno'];
		
		fputcsv($fp, $headers);
		foreach($dupe_md5s as $md5) {
			if(is_array($reps = ca_object_representations::find(['md5' => $md5], ['returnAs' => 'modelInstances']))) {
				$line = [$md5, sizeof($reps)];
				
				$list = [];
				foreach($reps as $r) {
					$ref = caGetReferenceToExistingRepresentationMedia($r, ['returnAsArray' => true]);
					$list[] = $ref['idno'];
				}
				$line[] = Datamodel::getTableProperty($ref['table'], 'NAME_PLURAL');
				$line[] = join('; ', $list);
				
				fputcsv($fp, $line);
			}
			print CLIProgressBar::next(1, $md5);
		}
		
		fclose($fp);
		print CLIProgressBar::finish();
		CLIUtils::addMessage(($n == 1) ? _t('Found %1 duplicate', $n) : _t('Found %1 duplicates', $n));
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function find_duplicate_mediaParamList() {
		return [
			"file|f=s" => _t('Required. File to save export to.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function find_duplicate_mediaUtilityClass() {
		return _t('Media');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function find_duplicate_mediaShortHelp() {
		return _t("Lists media that have been uploaded more than once.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function find_duplicate_mediaHelp() {
		return _t("Lists media that have been uploaded more than once.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_media_class_values($opts=null) {
		$qr = ca_object_representations::findAsSearchResult('*');
		if(!$qr) {
			CLIUtils::addError(_t('No representations found'));
			return;
		}
		print CLIProgressBar::start($qr->numHits(), _t('Updating media classes'));
		while($qr->nextHit()) {
			$t_rep = $qr->getInstance();
			$t_rep->set('media_class', caGetMediaClass($t_rep->get('mimetype')));
			$t_rep->update();
			
			if($t_rep->numErrors() > 0) {
				CLIUtils::addError(_t('Could not update media class: %1', join('; ', $t_rep->getErrors())));
			}
			print CLIProgressBar::next(1, $t_rep->get('ca_object_representations.preferred_labels.name'));
		}
		
		print CLIProgressBar::finish();
		CLIUtils::addMessage(_t('Updated media class values'));
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_media_class_valuesParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_media_class_valuesUtilityClass() {
		return _t('Media');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_media_class_valuesShortHelp() {
		return _t("Sets media class values for object representations.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function update_media_class_valuesHelp() {
		return _t("Sets media class values for object representations. A media class indicates the general type of media (image, video, audio, etc.) and should be set for each uploaded representation on upload. When migrating from an older system these values may not be set. This command will ensure all representations with associated media are assigned a media class.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function transcribe($opts=null) {
		$quiet = $opts->getOption('quiet');
		$mimetypes = caGetOption('mimetypes', $opts, null, ['delimiter' => [',', ';']]);
		$skip_mimetypes = caGetOption('skip-mimetypes', $opts, null, ['delimiter' => [',', ';']]);
		
		if (!($start = (int)$opts->getOption('start_id'))) { $start = null; }
		if (!($end = (int)$opts->getOption('end_id'))) { $end = null; }

		if ($id = (int)$opts->getOption('id')) {
			$start = $id;
			$end = $id;
		}

		$ids = [];
		if ($ids = (string)$opts->getOption('ids')) {
			if (sizeof($tmp = explode(",", $ids))) {
				foreach($tmp as $id) {
					if ((int)$id > 0) {
						$ids[] = (int)$id;
					}
				}
			}
		}
		
		if (is_array($ids) && sizeof($ids)) {
			$criteria = ['representation_id' => ['IN', $ids]];
		} elseif (($start > 0) && ($end > 0) && ($start <= $end)) {
			$criteria = ['representation_id' => ['BETWEEN', [$start, $end]]];
		} elseif(($start > 0) && ($end == null)) {
			$criteria = ['representation_id' => ['>=', $start]];
		} else {
			$criteria = '*';
		}
		
		$o_tq = new TaskQueue();
		
		$qr = ca_object_representations::findAsSearchResult($criteria);
		if(!$qr) {
			CLIUtils::addMessage(_t('No media found'));
			return;
		}
		if(!$quiet) { print CLIProgressBar::start($qr->numHits(), _t('Transcribing media')); }
		while($qr->nextHit()) {
			$t_rep = $qr->getInstance();
			if(!($input_mimetype = $t_rep->get('mimetype'))) { continue; }
			if(caTranscribeAVMedia($input_mimetype) && ($t_rep->numCaptionFiles() == 0)) {
				if(is_array($mimetypes) && sizeof($mimetypes)) {
					if(!caMimetypeIsValid($input_mimetype, $mimetypes)) { continue; }
				}
				if(is_array($skip_mimetypes) && sizeof($skip_mimetypes)) {
					if(caMimetypeIsValid($input_mimetype, $skip_mimetypes)) { continue; }
				}
				$o_tq->addTask(
					'mediaTranscription',
					array(
						"TABLE" => $t_rep->tableName(), "FIELD" => 'media',
						"PK" => $t_rep->primaryKey(), "PK_VAL" => $t_rep->getPrimaryKey(),
					
						"INPUT_MIMETYPE" => $input_mimetype,
					
						"OPTIONS" => []
					),
					["priority" => 200, "entity_key" => md5(join("/", [$t_rep->tableName(), 'media', $t_rep->getPrimaryKey()])), "row_key" => join("/", array($t_rep->tableName(), $t_rep->getPrimaryKey())), 'user_id' => null]);	
			}
			
			if(!$quiet) { print CLIProgressBar::next(1, $t_rep->get('ca_object_representations.preferred_labels.name')); }
		}
		
		if(!$quiet) {
			print CLIProgressBar::finish();
			CLIUtils::addMessage(_t('Complete'));
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function transcribeParamList() {
		return [
			"mimetypes|m-s" => _t("Limit re-processing to specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
			"skip-mimetypes|x-s" => _t("Do not reprocess specified mimetype(s) or mimetype stubs. Separate multiple mimetypes with commas."),
			"start_id|s-n" => _t('Representation id to start reloading at'),
			"end_id|e-n" => _t('Representation id to end reloading at'),
			"id|i-n" => _t('Representation id to reload'),
			"ids|l-s" => _t('Comma separated list of representation ids to reload')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function transcribeUtilityClass() {
		return _t('Media');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function transcribeShortHelp() {
		return _t("Force Whisper-based transcription of audio/visual media.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function transcribeHelp() {
		return _t("Sets media class values for object representations. A media class indicates the general type of media (image, video, audio, etc.) and should be set for each uploaded representation on upload. When migrating from an older system these values may not be set. This command will ensure all representations with associated media are assigned a media class.");
	}
	# -------------------------------------------------------
}
