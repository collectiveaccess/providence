<?php
/** ---------------------------------------------------------------------
 * ExportFormatCTDA.php : defines ConnecTicut Digital Archive export format
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023-2024 Whirl-i-Gig
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Export/BaseExportFormat.php');

class ExportCTDA extends BaseExportFormat {
	# ------------------------------------------------------
	private static $row_index = 0;
	private static $headers_output = false;
	
	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'CTDA';
		$this->ops_element_description = _t('Values are column numbers (indexing starts at 1).');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($settings) {
		return 'csv';
	}
	# ------------------------------------------------------
	public function getContentType($settings) {
		return 'text/csv';
	}
	# ------------------------------------------------------
	public function setCounter(int $count) {
		self::$row_index = $count;
	}
	# ------------------------------------------------------
	public function getCounter() {
		return self::$row_index;
	}
	# ------------------------------------------------------
	private function _mediaClassToCTDAResourceType(?string $class) {
		$map = [
			'image' => 'Still image',
			'video' => 'Moving image',
			'audio' => 'Audio',
			'document' => 'Manuscript',
			'3d' => 'Multimedia',
			'vr' => 'Multimedia',
			'binary' => 'Binary'
		];
		if(isset($map[$class])) {
			return $map[$class];
		}
		return 'Mixed material';
	}
	# ------------------------------------------------------
	private function _mediaClassToCTDAModel(?string $class) {
		$map = [
			'image' => 'Image',
			'video' => 'Video',
			'audio' => 'Audio',
			'document' => 'Publication issue',
			'3d' => 'Digital document',
			'vr' => 'Digital document',
			'binary' => 'Binary'
		];
		if(isset($map[$class])) {
			return $map[$class];
		}
		return 'Mixed material';
	}
	# ------------------------------------------------------
	public function processExport($data, $options=[]){
		$ext_config = Configuration::load(__CA_CONF_DIR__.'/external_exports.conf');
		self::$row_index++;
		$group = self::$row_index;
		
		$headers = [
			"ID",
			"member_of",
			"member_of_existing_entity_id",
			"publish",
			"model",
			"held_by",
			"title",
			"rights_statement",
			"digital_file",
			"media_use",
			"digital_origin",
			"resource_type",
			"description",
			"local_identifier",
			"persons",
			"organizations",
			"subject",
			"temporal_subject",
			"geographic_subject",
			"origin_information",
			"record_information",
			"physical_description_note"
		];
		
		$acc = $row = [];
		for($c=0; $c < 22; $c++) {
			$row[$c] = null;
		}
		foreach($data as $item){
			$c = intval($item['element'])-1;
			$row[$c][] = $item['text'];
		}
		$row[0] = self::$row_index;
		ksort($row);
		$media = $row[8] ?? [];
		$media_prefix = $ext_config->get('ctda_media_prefix');
		$row[1] = $row[8] = null;
		foreach($row as $c => $data) {
			switch($c) {
				case 2:
					$row[$c] = $ext_config->get('ctda_entity_id');
					break;
				case 3:
					$row[$c] = ($row[$c][0] === 'public_access') ? 'Y': 'N';
					break;
				case 4:
					if(sizeof($media) > 1) {
						$row[$c] = 'Compound';
					} elseif(sizeof($media) === 1) {
						$row[$c] = $this->_mediaClassToCTDAModel(caGetMediaClass(Media::getMimetypeForExtension(pathinfo($media[0], PATHINFO_EXTENSION))));
					} else {
						$row[$c] = '';
					}
					break;
				case 8:
					if(sizeof($media ?? []) === 1) {
						$row[$c] = $media_prefix.pathinfo($media[0], PATHINFO_BASENAME);
					} else {
						$row[$c] = '';
					}
					break;
				case 11:
					if($media[0]) {
						$row[$c] = $this->_mediaClassToCTDAResourceType(caGetMediaClass(Media::getMimetypeForExtension(pathinfo($media[0], PATHINFO_EXTENSION))));
					} else {
						$row[$c] = '';
					}	
					break;
				case 16:
					$row[$c] = join('^^', array_map(function($v) {
						return "{$v}";
					}, $row[$c])).'|subject';
					break;
				case 19:
					$row[$c] = join('', array_map(function($v) {
						return "||{$v}";
					}, $row[$c]));
					break;
				case 20:
					if($row[$c][0] ?? null) {
						$row[$c] = '|||'.join('', $row[$c]);
					}
					break;
				default:
					$row[$c] = is_array($row[$c]) ? join(';', $row[$c]) : $row[$c];
					break;
			}
		}
		
		$acc[] = $row;
		
		if(sizeof($media) > 1) {
			foreach($media as $m) {
				self::$row_index++;
				$row[0] = self::$row_index;
				$row[1] = $group;
				$row[4] = $this->_mediaClassToCTDAModel(caGetMediaClass(Media::getMimetypeForExtension(pathinfo($m, PATHINFO_EXTENSION))));
				$row[8] = $media_prefix.pathinfo($m, PATHINFO_BASENAME);
				$row[11] = $this->_mediaClassToCTDAResourceType(caGetMediaClass(Media::getMimetypeForExtension(pathinfo($m, PATHINFO_EXTENSION))));
				$acc[] = $row;
			}
		}
		
		$r = fopen('php://temp/maxmemory:10485760', 'w');
		
		if(!self::$headers_output) {
			fputcsv($r, $headers);
			self::$headers_output = true;
		}
		foreach($acc as $i => $row) {
			foreach($row as $j => $rval) {
				$acc[$i][$j] = strip_tags($rval, ['b', 'i', 'u', 'strong', 'em', 'p', 'br']);
			}
			fputcsv($r, $acc[$i]);
		}
		rewind($r);
		$csv = stream_get_contents($r);
		fclose($r);
		
		return $csv;
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping){
		$errors = array();
		$top = $t_mapping->getTopLevelItems();

		foreach($top as $item){
			$t_item = new ca_data_exporter_items($item['item_id']);

			$element = $item['element'];
			if(!is_numeric($element)){
				//$errors[] = _t("Element %1 is not numeric",$element);
			}
			if(intval($element) <= 0){
				//$errors[] = _t("Element %1 is not a positive number",$element);	
			}

			if(sizeof($t_item->getHierarchyChildren())>0){
				$errors[] = _t("CTDA exports can't be hierarchical",$element);
			}
		}

		return $errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['CTDA'] = array(
	'CTDA_print_field_names' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => '"',
		'label' => _t('Print field names'),
		'description' => _t('Print names of output fields in first row of output.')
	),
);
