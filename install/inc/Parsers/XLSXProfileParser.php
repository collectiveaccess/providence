<?php
/* ----------------------------------------------------------------------
 * install/inc/Parsers/XLSXProfileParser.php : install system from Excel-format system sketch
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2022 Whirl-i-Gig
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
namespace Installer\Parsers;

require_once(__CA_BASE_DIR__."/install/inc/Parsers/BaseProfileParser.php");
require_once(__CA_BASE_DIR__."/install/inc/Parsers/XMLProfileParser.php");

class XLSXProfileParser extends BaseProfileParser {
	# --------------------------------------------------
	/**
	 *
	 */
	private $xlsx = null; 
	
	/**
	 * Base profile
	 */
	private $base = null; 
	
	/**
	 *
	 */
	private $settings = null;
	
	/**
	 *
	 */
	protected $data = [];
	
	/**
	 *
	 */
	private $directory; 
	
	/**
	 *
	 */
	private $profile_name; 
	
	/**
	 * Parser format
	 */
	var $format = 'XLSX';
	
	# --------------------------------------------------
	/**
	 * Parse a profile
	 *
	 * @param string $directory Directory containing profile
	 * @param string $profile Name (with or without extension) of profile to parse
	 *
	 * @return array Parsed profile data
	 */
	public function parse(string $directory, string $profile) : array {
		$this->settings = null;
		if(!($profile_path = caGetProfilePath($directory, $profile))) {
			return null;
		}
		
		if(!$this->validateProfile($directory, $profile)) {	// validation loads profile
			throw new \Exception(_t('XLSX profile validation failed'));
		}
		
		$this->directory = $directory;
		$this->profile_name = $profile;
					
		$this->data = [];
		
		// Load XML base profile
		$this->settings = $this->_getProfileInfo($this->xlsx);
		if($base_profile = caGetOption('base', $this->settings, 'base.xml')) {
			$base_parser = new \Installer\Parsers\XMLProfileParser();
			$this->data = $base_parser->parse(__CA_BASE_DIR__.'/install/profiles/xml', $base_profile);
		}
				
		// Build list of worksheets to process
		$sheet_map = $this->_getSheetMap();
		// Parse sections
		if(isset($sheet_map['locales'])) { $this->processLocales($sheet_map['locales']); }
 		if(isset($sheet_map['lists'])) { $this->processLists($sheet_map['lists']); }
 		if(isset($sheet_map['relationshipTypes'])) { $this->processRelationshipTypes($sheet_map['relationshipTypes']); }
 		if(isset($sheet_map['metadataElements'])) { $this->processMetadataElementSets($sheet_map['metadataElements']); }
 		if(isset($sheet_map['uis'])) { $this->processUIs($sheet_map['uis']); }
 		if(isset($sheet_map['logins'])) { $this->processLogins($sheet_map['logins']); }
		
		return $this->data;
	}
	# --------------------------------------------------
	/**
	 * Return metadata (name, description) for a profile
	 *
	 * @param string $profile_path Path to an XLSX-format profile
	 *
	 * return array Array of data, or null if profile cannot be read.
	 */
	public function profileInfo(string $profile_path) : ?array {
		try {
			return $this->_getProfileInfo(\PhpOffice\PhpSpreadsheet\IOFactory::load($profile_path));
		} catch (\Exception $e) {
			return null;
		}
		
		return null;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function _getProfileInfo($xlsx) : array {
		if (!($sheet = $xlsx->getSheetByName('Settings'))) { 
			$sheet = $xlsx->getSheetByName('settings');
		}
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 

			$settings = [];
			for($line=1; $line <= $hrow; $line++) {
				$n = strtolower($sheet->getCellByColumnAndRow(1, $line)->getValue());
				$v = $sheet->getCellByColumnAndRow(2, $line)->getValue();
			
				switch($n) {
					case 'name':
					case 'base':
					case 'locale':
					case 'description':
						$settings[$n] = $v;
						break;
				}
			}
		} 
		return [
			'useForConfiguration' => 1,
			'display' => caGetOption('name', $settings, pathinfo($profile_path, PATHINFO_FILENAME)),
			'description' => caGetOption('description', $settings, ''),
			'base' => caGetOption('base', $settings, 'base'),
			'locale' => caGetOption('locale', $settings, 'en_US'),
		];
	}
	# --------------------------------------------------
	/**
	 * Validate profile. For XLSX validation consists of loading the profile and identifying sheets with content.
	 * Validation will fail if the file is not a valid XLSX file or no sheets with content are found.

	 * @param string $directory path to a directory containing profiles
	 * @param string $profile Name of the profile, with or without file extension
	 *
	 * @return bool
	 */
	public function validateProfile(string $directory, string $profile) : bool {
		$path = caGetProfilePath($directory, $profile);
		
		if(is_readable($path)) {
			try {
				if ($this->xlsx = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)) {
					$sheet_map = $this->_getSheetMap();
					return (is_array($sheet_map) && sizeof($sheet_map));
				}
			} catch(\Exception $e) {
				return false;
			}
		}
		return false;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function _getSheetMap() {
		$sheet_names = $this->xlsx->getSheetNames();
		
		$sheet_map = [
			'settings' => null,
			'locales' => null,
			'lists' => null,
			'metadataElements' => null,
			'relationshipTypes' => null,
			'uis' => null,
			'logins' => null,
			'locales' => null
		];
		foreach($sheet_names as $i => $s) {
			switch(strtolower($s)) {
				case 'settings':
					$sheet_map['settings'] = $i;
					break;
				case 'locales':
					$sheet_map['locales'] = $i;
					break;
				case 'logins':
					$sheet_map['logins'] = $i;
					break;
				case 'metadata elements':
				case 'metadata_elements':
				case 'metadata':
				case 'elements':
					$sheet_map['metadataElements'] = $i;
					break;
				case 'lists':
					$sheet_map['lists'] = $i;
					break;
				case 'relationship types':
				case 'relationship_types':
				case 'rel types':
				case 'rel_types':
					$sheet_map['relationshipTypes'] = $i;
					break;
				default:
					if(preg_match("!^ui_([A-Za-z0-9\-\_ ]+)!", strtolower($s), $m)) {
						if(!is_array($sheet_map['uis'])) { $sheet_map['uis'] = []; }
						$sheet_map['uis'][$m[1]] = $i;
					}
					break;
			}
		}
		
		return $sheet_map;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLocales(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 

			$settings = [];
			for($line=3; $line <= $hrow; $line++) {
				$name = strtolower($sheet->getCellByColumnAndRow(1, $line)->getValue());
				$language = strtolower($sheet->getCellByColumnAndRow(2, $line)->getValue());
				$country = strtoupper($sheet->getCellByColumnAndRow(3, $line)->getValue());
				$dont_use_for_cataloguing = strtolower($sheet->getCellByColumnAndRow(4, $line)->getValue());
				
				if(!$language || !$country) { continue; }
				$locale_code = "{$language}_{$country}";
				$this->data['locales'][$locale_code] = [
					'name' => $name,
					'language' => $language,
					'dialect' => null,
					'country' => $country,
					'dontUseForCataloguing' => $dont_use_for_cataloguing
				];
			}
		} 
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLists(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 
			
			// Get list codes and column boundaries
			$list_codes = [];
			
			$cur_code = null;
			$i = 1;
			foreach($sheet->getRowIterator() as $row) {
				$ci = $row->getCellIterator();
				$ci->setIterateOnlyExistingCells(false); // This loops through all cells,
				foreach ($ci as $cell) {
					$code = trim($cell->getValue());
					
					if ($code) {
						$cur_code = $code;
						$list_codes[$cur_code] = [
							'code' => $cur_code,
							'start' => $i,
							'end' => $i
						];
					} elseif($cur_code) {
						$list_codes[$cur_code]['end'] = $i;
					}
					$i++;
				}
				break;
			}
			
			foreach($list_codes as $code => $info) {
				$name = caCamelOrSnakeToText($code, ['ucFirst' => true]);
				$code_proc = caTextToSnake($code);
				
				$this->data['lists'][$code] = [
					'labels' => [[
						'name' => $name,
						'locale' => $this->settings['locale'],
						'preferred' => 1
					]],
					'code' => $code_proc,
					'hierarchical' => ($info['start'] !== $info['end']),
					'system' => false,
					'vocabulary' => false,
					'defaultSort' => 0,
					'items' => $this->processListItems($sheet, $info, 3, $info['start'], $code_proc)
				];
			}
		} 
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processListItems($sheet, array $info, int $row, int $col, string $list_code) : array {
		$values = [];
		
		$hrow = $sheet->getHighestRow(); 
		$i = 0;
		for($r=$row; $r <= $hrow; $r++) {
			$val = trim($sheet->getCellByColumnAndRow($col, $r)->getValue());
			$next_val = trim($sheet->getCellByColumnAndRow($col, $r+1)->getValue());
			if(!strlen($val)) { continue; }
			
			$idno = caTextToSnake($val);
			
			$labels = [[
				'name_singular' => $val,
				'name_plural' => $val,
				'preferred' => 1,
				'locale' => $this->settings['locale']
			]];
			
			$sub_items = [];
			if (!$next_val && ($col < $info['end']) && ($subval = trim($sheet->getCellByColumnAndRow($col+1, $r+1)->getValue()))) {
 				$sub_items = $this->processListItems($sheet, $info, $row+1, $col+1, $list_code);
 			}
 			
 			if(in_array($list_code, ['access_statuses', 'workflow_statuses'])) {
 				$idno = $val = $i;
 			}
 			
			$values[$idno] = [
				'idno' => $idno,
				'value' => $val,
				'labels' => $labels,
				'settings' => [],
				'type' => null,
				'status' => 0,
				'access' => 0,
				'rank' => $col * $r,
				'enabled' => 1,
				'default' => 0,
				'color' => '',
				'items' => $sub_items
			];
			$i++;
		}
		
		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMetadataElementSets(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		$element_list = [];
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 
			//Element Name	Sub-element Name	Schema	Element Code	Data Type	Data Format	Source List	Restrict to Types	Display Order	Mandatory	Repeatable	Public	Description	Notes										
			$row = 3;
			
			$elements = [];
			$parent_element_code = null;
			for($r=$row; $r <= $hrow; $r++) {
				$element_name = trim($sheet->getCellByColumnAndRow(1, $r)->getValue());
				$subelement_name = trim($sheet->getCellByColumnAndRow(2, $r)->getValue());
				$element_code = trim($sheet->getCellByColumnAndRow(4, $r)->getValue());
				
				if(!$element_name || !$element_code) { continue; }
				
				$e = [
					'name' => $subelement_name ? $subelement_name : $element_name,
					'code' =>  $element_code,
					'schema' =>  trim($sheet->getCellByColumnAndRow(3, $r)->getValue()),
					'datatype' =>  trim($sheet->getCellByColumnAndRow(5, $r)->getValue()),	// TODO: validate codes
					'render' => trim($sheet->getCellByColumnAndRow(6, $r)->getValue()),
					'list' =>  trim($sheet->getCellByColumnAndRow(7, $r)->getValue()),
					'restrict_to' =>  trim($sheet->getCellByColumnAndRow(8, $r)->getValue()),
					'display_order' =>  trim($sheet->getCellByColumnAndRow(9, $r)->getValue()),
					'mandatory' =>  trim($sheet->getCellByColumnAndRow(10, $r)->getValue()),
					'repeatable' =>  trim($sheet->getCellByColumnAndRow(11, $r)->getValue()),
					'public' =>  trim($sheet->getCellByColumnAndRow(12, $r)->getValue()),
					'description' =>  trim($sheet->getCellByColumnAndRow(13, $r)->getValue()),
					'notes' =>  trim($sheet->getCellByColumnAndRow(14, $r)->getValue())
				];
				
				if(!$subelement_name) { 
					$parent_element_code = $element_code;
				}
				if (!isset($elements[$parent_element_code])) {
					$elements[$parent_element_code] = $e;
				} else {
					$elements[$parent_element_code]['elements'][$element_code] = $e;
				}
			}
			
			$this->data['metadataElements'] = $this->processMetadataElements($elements);
		}
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processMetadataElements($elements) {
		$element_list = [];
		foreach($elements as $element) {
	
			$labels = [
				[
					'name' => $element['name'], 'description' => $element['description'], 
					'locale' => $this->settings['locale'], 'preferred' => 1
				]
			];
			$settings = [];
			
			foreach(preg_split('![,;\n]+!', $element['render']) as $render_setting) {
				$lines = preg_split("![\n\r]+!", $render_setting);
				foreach($lines as $line) {
					$l = explode('=', $line);
					$setting = self::rewriteSettingName(array_shift($l));
					$value = join('=', $l);
					
					$settings[$setting][''][] = $value;
				}
			}
		
			$subelements = null;
			if (is_array($element['elements']) && sizeof($element['elements'])) {
				$subelements = $this->processMetadataElements($element['elements']);
			}
		
			$type_restrictions = [];
			if(!$element['restrict_to']) { 
				$this->warning('processMetadataElements', _t('No type restriction specified for metadata element %1', $element['name']));
			}
			if($element['restrict_to']) {
				$restrictions = preg_split('![;,\n]+!', $element['restrict_to']);
				foreach($restrictions as $rx => $restriction) {
					$tmp = explode('/', $restriction);
    			    $table = self::tableNameFromString($tmp[0]);
					$type = $tmp[1] ?? null;
					
					$min_repeats = is_numeric($element['mandatory']) ? (int)$element['mandatory'] : (self::parseBool($element['mandatory']) ? 1 : 0);
					$max_repeats = is_numeric($element['repeatable']) ? (int)$element['repeatable'] : (self::parseBool($element['repeatable']) ? 1000 : 1);

					$type_restrictions[] = [
						'code' => "res{$rx}",
						'table' => $table,
						'type' => $type,
						'includeSubtypes' => 0,
						'settings' => [
							'minAttributesPerRow' => ['' => [$min_repeats]],
							'maxAttributesPerRow' => ['' => [$max_repeats]],
							'minimumAttributeBundlesToDisplay' => ['' => [1]]
						]
					];
				}
			}
		
			$element_list[$element['code']] = [
				'labels' => $labels,
				'code' => $element['code'],
				'datatype' => $element['datatype'],
				'list' => $element['list'],
				'deleted' => false,
				'documentationUrl' => $documentation_url,
				'settings' => $settings,
				'elements' => $subelements,
				'typeRestrictions' => $type_restrictions
			];
		}
		
		return $element_list;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processRelationshipTypes(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 
			
			// Get relationship tables and column boundaries
			$rel_tables = [];
			
			$cur_rel_table = null;
			$i = 1;
			foreach($sheet->getRowIterator() as $row) {
				$ci = $row->getCellIterator();
				$ci->setIterateOnlyExistingCells(false); // This loops through all cells,
				foreach ($ci as $cell) {
					$rel_table = trim($cell->getValue());
					
					if ($rel_table) {
						$cur_rel_table = $rel_table;
						$rel_tables[$cur_rel_table] = [
							'table' => $cur_rel_table,
							'start' => $i,
							'end' => $i
						];
					} elseif($cur_rel_table) {
						$rel_tables[$cur_rel_table]['end'] = $i;
					}
					$i++;
				}
				break;
			}
			
			foreach($rel_tables as $rel_table => $info) {
				if($rt = self::relTableNameFromString($info['table'])) {
					$rel_tables[$rel_table]['table'] = $rt;
				} else {
					$this->warning('processRelationshipTypes', _t('Could not generate relationship table name from profile heading value %1', $info['table']));
					unset($rel_tables[$rel_table]);
				}
			}
			
			$r = 1;
			foreach($rel_tables as $table_code => $info) {
				$this->data['relationshipTypes'][$info['table']] = $this->processRelationshipTypesForTable($sheet, $info, 3, $info['start']);
				$r++;
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processRelationshipTypesForTable($sheet, array $info, int $row, int $col) : array {
		$values = [];
		
		$hrow = $sheet->getHighestRow(); 
		for($r=$row; $r <= $hrow; $r++) {
			$val = trim($sheet->getCellByColumnAndRow($col, $r)->getValue());
			$next_val = trim($sheet->getCellByColumnAndRow($col, $r+1)->getValue());
			if(!strlen($val)) { continue; }
			
			$idno = caTextToSnake($val);
			$name = caCamelOrSnakeToText($val, ['ucFirst' => true]);
			
			$name_tmp = preg_split("![ ]*/[ ]*!", $name);
			
			$labels = [[
				'typename' => $name_tmp[0],
				'typename_reverse' => $name_tmp[1] ?? $name_tmp[0],
				'locale' => $this->settings['locale']
			]];
			
			$sub_types = [];
			if (!$next_val && ($col < $info['end']) && ($subval = trim($sheet->getCellByColumnAndRow($col+1, $r+1)->getValue()))) {
 				$sub_types = $this->processRelationshipTypesForTable($sheet, $info, $row+1, $col+1);
 			}
			$values[$idno] = [
				'code' => $idno,
				'labels' => $labels,
				'settings' => [],
				'rank' => $col * $r,
				'types' => $sub_types
			];
		}
		
		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processUIs(array $sheet_nums) : bool {
		foreach($sheet_nums as $ui_spec => $sheet_num) {
			$sheet = $this->xlsx->getSheet($sheet_num);
			$tmp = explode('_', $ui_spec);
			if(!($table = self::tableNameFromString($tmp[0]))) { 
				$this->warning('processUIs', _t('Could not generate user interface table from worksheet name %1', $ui_spec));
				continue;
			}
			array_shift($tmp);
			
			$type_res = join('_', $tmp);	
		
			if($sheet) {
				$hrow = $sheet->getHighestRow(); 
			
				$by_screen = [];
				for($r=3; $r <= $hrow; $r++) {
					$screen = trim($sheet->getCellByColumnAndRow(1, $r)->getValue());
					if(!$screen) { continue; }
					
					// Skip if label if blank (allows inclusion of sub-elements for documentation purposes)
					if(!($label = trim($sheet->getCellByColumnAndRow(2, $r)->getValue()))) { continue; }
					
					$by_screen[$screen][] = [
						'label' => $label,
						'code' => trim($sheet->getCellByColumnAndRow(3, $r)->getValue()),
						'relationship' => trim($sheet->getCellByColumnAndRow(4, $r)->getValue()),
						'relationship_type' => trim($sheet->getCellByColumnAndRow(5, $r)->getValue()),
						'type_res' => trim($sheet->getCellByColumnAndRow(6, $r)->getValue()),
						'description' => trim($sheet->getCellByColumnAndRow(7, $r)->getValue()),
						'settings' => trim($sheet->getCellByColumnAndRow(8, $r)->getValue()),
						'notes' => trim($sheet->getCellByColumnAndRow(9, $r)->getValue()),
					];
				}
				
				$this->data['userInterfaces'][$ui_spec] = [
					'labels' => [[
						'name' => caCamelOrSnakeToText($ui_spec), 'locale' => $this->settings['locale']
					]],
					'code' => caTextToSnake($ui_spec),
					'type' => $table,
					'color' => '000000',
					'typeRestrictions' => [['type' => caTextToSnake($type_res), 'includeSubtypes' => 1]],
					'includeSubtypes' => true,
					'settings' => [],
					'screens' => $this->processUIScreens($table, $by_screen),
					'userAccess' => [],
					'groupAccess' => [],
					'roleAccess' => []
				];
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processUIScreens(string $table, array $screens) {
		$values = [];
		$default = true;
		foreach($screens as $idno => $bundles) {
			$settings = [];
			$type_restrictions = [];
			
			$values[$idno] = [
				'idno' => caTextToSnake($idno),
				'default' => $default,
				'labels' => [[
					'name' => $idno, 'locale' => $this->settings['locale']
				]],
				'settings' => $settings,
				'color' => '000000',
				'typeRestrictions' => $type_restrictions,
				'includeSubtypes' => true,
				'bundles' => $this->processUIBundles($table, $bundles),
				'userAccess' => [],
				'groupAccess' => [],
				'roleAccess' => []
			];
			
			$default = false;
		}

		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processUIBundles(string $table, array $bundles) {
		$values = [];
		foreach($bundles as $bundle) {
			$code = $bundle["code"];
			
			$label = $bundle["label"];
			$description = $bundle["description"];
			$relationship = $bundle["relationship"];
			$relationship_type = $bundle["relationship_type"];
			$type_res = $bundle["type_res"];
			
			$rel_table = null;
		
			if(!$code && !$relationship) { continue; }
			
			$settings = [];
			if($type_res) {
				$settings['restrict_to_types'][] = array_map(function($v) { return caTextToSnake($v); }, preg_split("![;,\n]+!", $type_res));
			}
			if($relationship && ($rel_table = self::tableNameFromString($relationship)) && $relationship_type) {
				$settings['restrict_to_relationship_types'][] = array_map(function($v) { return caTextToSnake($v); }, preg_split("![;,\n]+!", $relationship_type));
			}
			if($label) {
				$settings['label'][$this->settings['locale']] = [$label];
			}
			if($description) {
				$settings['description'][$this->settings['locale']] = [$description];
			}
			
			foreach(preg_split('![,;\n]+!', $element['settings']) as $render_setting) {
				$lines = preg_split("![\n\r]+!", $render_setting);
				foreach($lines as $line) {
					$l = explode('=', $line);
					$setting = self::rewriteSettingName(array_shift($l));
					$value = join('=', $l);
					
					$settings[$setting][''][] = $value;
				}
			}
			
			if(!isset($settings['add_label']) && $relationship && $rel_table && $label) {
				$settings['add_label'][''][] = _t('Add %1', $label);
			}
			
			$base_key = $key = $code ? $code : $relationship;
			
			$i = 0;
			while(isset($values[$key])) {
				$i++;
				$key = "{$base_key}_{$i}";
			}
			
			$bundle_type = null;
			$bundle_list = \Datamodel::getInstance($table, true)->getBundleList(['includeBundleInfo' => true]);
			
			if($rel_table) {
				$bundle_type = 'relationship';
			} elseif(isset($bundle_list[$code])) {
				$bundle_type = $bundle_list[$code]['type'];	
			}
			
			switch($bundle_type) {
				case 'relationship':
					$bundle = $rel_table;
					break;
				case 'special':
					$bundle = $code;
					break;
				default:
					$bundle = "{$table}.{$code}";
					break;
			}
			
			$values[$key] = [
				'code' => $key,
				'bundle' => $bundle,
				'settings' => $settings,
				'includeSubtypes' => true,
				'typeRestrictions' => []
			];
		}

		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processLogins(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 
			for($r=3; $r <= $hrow; $r++) {
				$user_name = trim($sheet->getCellByColumnAndRow(1, $r)->getValue());
				$password = trim($sheet->getCellByColumnAndRow(2, $r)->getValue());
				$fname = trim($sheet->getCellByColumnAndRow(3, $r)->getValue());
				$lname = trim($sheet->getCellByColumnAndRow(4, $r)->getValue());
				$email = trim($sheet->getCellByColumnAndRow(5, $r)->getValue());
				$roles = trim($sheet->getCellByColumnAndRow(6, $r)->getValue());
				$groups = trim($sheet->getCellByColumnAndRow(7, $r)->getValue());
				if(!strlen($user_name)) { continue; }
				
				$this->data['logins'][$user_name] = [
					'user_name' => $user_name,
					'password' => $password,
					'fname' => $fname,
					'lname' => $lname,
					'email' => $email,
					'roles' => $roles ? preg_split('![,; ]+!', $roles) : [],
					'groups' => $groups ? preg_split('![,; ]+!', $groups) : []
				];
			}
		}
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function parseBool(string $string) : bool {
		switch(strtolower($string)) {
			case 'y':
			case 'yes':
			case 1:
				return true;
			case 'n':
			case 'no':
			case 0:
				return false;
		}
		return null;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function tableNameFromString(string $string, ?bool $search_relationships=true) : ?string {
		if(\Datamodel::tableExists($string)) { return $string; }
		switch(strtolower($string)) {
			case 'entity':
			case 'entities':
			case 'ca_entities':
				return 'ca_entities';
			case 'objects':
			case 'object':
			case 'ca_entities':
				return 'ca_objects';
			case 'object_lot':
			case 'object_lots':
			case 'accession':
			case 'accessions':
			case 'ca_object_lots':
				return 'ca_object_lots';
			case 'collection':
			case 'collections':
			case 'ca_collections':
				return 'ca_collections';
			case 'place':
			case 'places':
			case 'ca_places':
				return 'ca_places';
			case 'occurrence':
			case 'occurrences':
			case 'ca_occurrences':
				return 'ca_occurrences';
			case 'loan':
			case 'loans':
			case 'ca_loans':
				return 'ca_loans';
			case 'movement':
			case 'movements':
			case 'ca_movements':
				return 'ca_movements';
			case 'representation':
			case 'representations':
			case 'object_representation';
			case 'object_representations';
			case 'rep':
			case 'reps':
			case 'ca_object_representations':
				return 'ca_object_representations';
			case 'list_item':
			case 'list_items':
			case 'item';
			case 'items';
			case 'vocabulary':
			case 'vocabularies':
			case 'ca_list_items':
				return 'ca_list_items';
			case 'storage_location':
			case 'storage_locations':
			case 'location';
			case 'locations';
			case 'storage':
			case 'loc':
			case 'locs':
			case 'ca_storage_locations':
				return 'ca_storage_locations';
			case 'tour':
			case 'tours':
			case 'ca_tours':
				return 'ca_tours';
			case 'tour_stop':
			case 'tour_stops':
			case 'stop':
			case 'stops':
			case 'ca_tour_stops':
				return 'ca_tour_stops';
		}
		return $search_relationships ? self::relTableNameFromString($string) : false;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function relTableNameFromString(string $string) : ?string {
		$tmp = preg_split('!'.(preg_match('!_x_!i', $string) ? '_x_' : '_').'!i', $string);
		if(sizeof($tmp) < 2) { return null; }
		$table1 = self::tableNameFromString($tmp[0], false);
		$table2 = self::tableNameFromString($tmp[1], false);
		if(!$table1 || !$table2) { return null; }
		
		$path = \Datamodel::getPath($table1, $table2);
		if(!is_array($path) || (sizeof($path) < 2)) { return null; }
		
		$path = array_keys($path);
		return self::isRelationship($path[1]) ? $path[1] : null;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function isRelationship(string $table) : bool {
		if (\Datamodel::tableExists($table)) {
			if (preg_match("!^ca_[a-z_]+?_x_[a-z_]+!", $table)) { 
				return true;
			}
		}
		return false;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function rewriteSettingName(string $setting) : string {
		switch(strtolower($setting)) {
			case 'width':
				return 'fieldWidth';
			case 'height':
				case 'width':
				return 'fieldHeight';
		}
		return $setting;
	}
	# --------------------------------------------------
}
