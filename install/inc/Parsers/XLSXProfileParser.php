<?php
/* ----------------------------------------------------------------------
 * install/inc/Parsers/XLSXProfileParser.php : install system from Excel-format system sketch
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
	
	# --------------------------------------------------
	/**
	 *
	 */
	public function parse(string $directory, string $profile) : array {
		$this->settings = null;
		if(!($profile_path = caGetProfilePath($directory, $profile))) {
			return null;
		}
		
		if(!$this->validateProfile($directory, $profile)) {
			throw new \Exception(_t('XLSX profile validation failed'));
		}
		if(!$this->loadProfile($directory, $profile)) {
			throw new \Exception(_t('Could not load XLSX profile'));
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
// 		$this->processUIs();
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
	 *
	 */
	public function loadProfile(string $directory, string $profile) : bool {
		$path = caGetProfilePath($directory, $profile);
		if(is_readable($path)) {
			try {
				if ($this->xlsx = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)) {
					return true;
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
					if(preg_match("!^ui_(.*)$!", strtolower($s), $m)) {
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
	 * Validate profile
	 */
	private function validateProfile(string $directory, string $profile) {
		$profile_path = caGetProfilePath($directory, $profile);
		$base_path = caGetProfilePath($directory, 'base');
		
		$vb_return = true; // TODO: do actual validation
		if($vb_return) {
			$this->logStatus(_t('Successfully validated profile %1', $this->profile_name));
		} else {
			$this->logStatus(_t('Validation failed for profile %1', $this->profile_name));
		}

		return $vb_return;
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
			for($line=2; $line <= $hrow; $line++) {
				$name = strtolower($sheet->getCellByColumnAndRow(1, $line)->getValue());
				$language = strtolower($sheet->getCellByColumnAndRow(2, $line)->getValue());
				$country = strtolower($sheet->getCellByColumnAndRow(3, $line)->getValue());
				$dont_use_for_cataloguing = strtolower($sheet->getCellByColumnAndRow(4, $line)->getValue());
				
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
				
				$this->data['lists'][$code] = [
					'labels' => [[
						'name' => $name,
						'locale' => $this->settings['locale'],
						'preferred' => 1
					]],
					'code' => caTextToSnake($code),
					'hierarchical' => ($info['start'] !== $info['end']),
					'system' => false,
					'vocabulary' => false,
					'defaultSort' => 0,
					'items' => $this->processListItems($sheet, $info, 2, $info['start'])
				];
			}
		} 
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processListItems($sheet, array $info, int $row, int $col) : array {
		$values = [];
		
		$hrow = $sheet->getHighestRow(); 
		for($r=$row; $r <= $hrow; $r++) {
			$val = trim($sheet->getCellByColumnAndRow($col, $r)->getValue());
			$next_val = trim($sheet->getCellByColumnAndRow($col, $r+1)->getValue());
			if(!strlen($val)) { continue; }
			
			$idno = caTextToSnake($val);
			$name = caCamelOrSnakeToText($val, ['ucFirst' => true]);
			
			$labels = [[
				'name_singular' => $name,
				'name_plural' => $name,
				'preferred' => 1,
				'locale' => $this->settings['locale']
			]];
			
			$sub_items = [];
			if (!$next_val && ($col < $info['end']) && ($subval = trim($sheet->getCellByColumnAndRow($col+1, $r+1)->getValue()))) {
 				$sub_items = $this->processListItems($sheet, $info, $row+1, $col+1);
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
			$row = 2;
			
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
				['name' => $element['name'], 'description' => $element['description'], 'locale' => $this->settings['locale'], 'preferred' => 1]
			];
			$settings = [];
			
			foreach(preg_split('![,;\n]+!', $element['render']) as $render_setting) {
				switch(strtolower($render_setting)) {
					case 'suggest existing values';
						$settings['suggestExistingValues'][''] = [
							1
						];
						break;
					case 'drop-down list':
					case 'drop down list':
						$settings['render'][''] = [
							'select'
						];
						break;
					case 'rich text':
						$settings['usewysiwygeditor'][''] = [
							1
						];
						break;
					case 'checklist':
						$settings['render'][''] = [
							'checklist'
						];
						break;
					case 'month dd yyyy':
					case 'mdy':
					
						break;
					case 'cad':
						$settings['dollarCurrency'][''] = [
							'CAD'
						];
						break;
				}
			}
		
			$subelements = null;
			if (is_array($element['elements']) && sizeof($element['elements'])) {
				$subelements = $this->processMetadataElements($element['elements']);
			}
		
			$type_restrictions = [];
			if(!$element['restrict_to']) { $element['restrict_to'] = 'ca_objects'; } // TODO: remove - for testing only
			if($element['restrict_to']) {
				$restrictions = preg_split('![;,\n]+!', $element['restrict_to']);
				foreach($restrictions as $rx => $restriction) {
					$tmp = explode('/', $restriction);
    			    $table = $tmp[0];
					$type = $tmp[1] ?? null;
					
					$min_repeats = self::parseBool($element['mandatory']) ? 1 : 0;
					$max_repeats = self::parseBool($element['repeatable']) ? 1000 : 0;

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
					// TODO: generate warning
					//print "WARNING: No table for $rel_table\n";
					unset($rel_tables[$rel_table]);
				}
			}
			
			$r = 1;
			foreach($rel_tables as $table_code => $info) {
				$name = caCamelOrSnakeToText($info['code'], ['ucFirst' => true]);
				
				$this->data['relationshipTypes'][$info['table']] = $this->processRelationshipTypesForTable($sheet, $info, 2, $info['start']);
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
	public function processUIs(int $sheet_num) : bool {
		$sheet = $this->xlsx->getSheet($sheet_num);
		
		if($sheet) {
			$hrow = $sheet->getHighestRow(); 
			
			for($r=$row; $r <= $hrow; $r++) {
				$xxx = trim($sheet->getCellByColumnAndRow(1, $r)->getValue());
			}
		}
		
		
		foreach($ui_list as $uis) {
			foreach($uis as $ui) {
				$ui_code = self::getAttribute($ui, "code");
				$type = self::getAttribute($ui, "type");
				$color = self::getAttribute($ui, "color");
				$include_subtypes = self::getAttribute($ui, "includeSubtypes");
			
				$labels = self::getLabelsFromXML($ui->labels);
				
				$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($ui, "typeRestrictions"), $include_subtypes);
				if($ui->typeRestrictions) { 
					$type_restrictions = array_merge($type_restrictions, self::processTypeRestrictionLists($ui->typeRestrictions));
				}
				$settings = $ui->settings ? $this->getSettingsFromXML($ui->settings) : [];
				
				$user_access = self::processAccessRestrictions('user', $ui->userAccess);
				$group_access = self::processAccessRestrictions('group', $ui->groupAccess);
				$role_access = self::processAccessRestrictions('role', $ui->userAccess);
			
				$this->data['userInterfaces'][$ui_code] = [
					'labels' => $labels,
					'code' => $ui_code,
					'type' => $type,
					'color' => $color,
					'typeRestrictions' => $type_restrictions,
					'includeSubtypes' => $include_subtypes,
					'settings' => $settings,
					'screens' => $ui->screens ? $this->processUIScreens($ui->screens) : [],
					'userAccess' => $user_access,
					'groupAccess' => $group_access,
					'roleAccess' => $role_access
				];
			}
		}
		
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processUIScreens($screens) {
		$values = [];
		foreach($screens->children() as $screen) {
			$idno = self::getAttribute($screen, "idno");
			$default = self::getAttribute($screen, "default");
			$deleted = self::getAttribute($screen, "deleted");
			$color = self::getAttribute($screen, "color");
			$include_subtypes = self::getAttribute($ui, "includeSubtypes");
			
			$labels = self::getLabelsFromXML($screen->labels);
		
			$settings = $screen->settings ? $this->getSettingsFromXML($screen->settings) : [];
			
			$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($screen, "typeRestrictions"), $include_subtypes);
			if($screen->typeRestrictions) { 
				$type_restrictions = array_merge($type_restrictions, self::processTypeRestrictionLists($screen->typeRestrictions));
			}
			
			$user_access = self::processAccessRestrictions('user', $screen->userAccess);
			$group_access = self::processAccessRestrictions('group', $screen->groupAccess);
			$role_access = self::processAccessRestrictions('role', $screen->userAccess);
			
			$values[$idno] = [
				'idno' => $idno,
				'default' => $default,
				'labels' => $labels,
				'settings' => $settings,
				'color' => $color,
				'deleted' => $deleted,
				'typeRestrictions' => $type_restrictions,
				'includeSubtypes' => $include_subtypes,
				'bundles' => $screen->bundlePlacements ? $this->processUIBundles($screen->bundlePlacements) : [],
				'userAccess' => $user_access,
				'groupAccess' => $group_access,
				'roleAccess' => $role_access
			];
		}

		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processUIBundles($bundles) {
		$values = [];
		foreach($bundles->children() as $bundle) {
			$code = self::getAttribute($bundle, "code");
			$bundle_name = trim((string)$bundle->bundle);
			$include_subtypes = self::getAttribute($ui, "includeSubtypes");
			
			$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($bundle, "typeRestrictions"), $include_subtypes);
			
			$settings = $bundle->settings ? $this->getSettingsFromXML($bundle->settings) : [];
			
			$values[$code] = [
				'code' => $code,
				'bundle' => $bundle_name,
				'settings' => $settings,
				'includeSubtypes' => $include_subtypes,
				'typeRestrictions' => $type_restrictions
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
			for($r=2; $r <= $hrow; $r++) {
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
	private static function tableNameFromString(string $string) : ?string {
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
		return null;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function relTableNameFromString(string $string) : ?string {
		if ($t_rel = \Datamodel::getInstance($string, true)) {
			if ($t_rel->isRelationship()) { 
				return $string;
			}
			return null;
		}
		
		$tmp = explode('_', $string);
		$table1 = self::tableNameFromString($tmp[0]);
		$table2 = self::tableNameFromString($tmp[1]);
		if(!$table1 || !$table2) { return null; }
		
		$path = \Datamodel::getPath($table1, $table2);
		if(!is_array($path) || (sizeof($path) < 2)) { return null; }
		
		$path = array_keys($path);
		return \Datamodel::isRelationship($path[1]) ? $path[1] : null;
	}
	# --------------------------------------------------
}
