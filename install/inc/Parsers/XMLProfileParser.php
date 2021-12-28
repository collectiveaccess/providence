<?php
/* ----------------------------------------------------------------------
 * install/inc/SketchInstaller.php : install system from Excel-format system sketch
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

class XMLProfileParser extends BaseProfileParser {
	# --------------------------------------------------
	/**
	 *
	 */
	private $xml = null; 
	
	/**
	 * Base profile
	 */
	private $base = null; 
	
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
	public function __construct(?string $directory=null, ?string $profile=null) {
		if($profile) {
			$this->parse($directory, $profile);
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function parse(string $directory, string $profile) : array {
		if(!($profile_path = caGetProfilePath($directory, $profile))) {
			return null;
		}
		
		if(!$this->validateProfile($directory, $profile)) {
			throw new \Exception(_t('XML profile validation failed'));
		}
		if(!$this->loadProfile($directory, $profile)) {
			throw new \Exception(_t('Could not load XML profile'));
		}
		
		$this->directory = $directory;
		$this->profile_name = $profile;
		
		// Parse sections
		$this->processLocales();
		$this->processLists();
		$this->processRelationshipTypes();
		$this->processMetadataElementSets();
		$this->processUIs();
		
		return $this->data;
	}
	# --------------------------------------------------
	/**
	 * Return metadata (name, description) for a profile
	 *
	 * @param string $profile_path Path to an XML-format profile
	 *
	 * return array Array of data, or null if profile cannot be read.
	 */
	public function profileInfo(string $profile_path) : ?array {
		$reader = new \XMLReader();
		
		if (!@$reader->open($profile_path)) {
			return null;
		}

		$name = $description = $useForConfiguration = $locales = null;
		while(@$reader->read()) {
			if ($reader->nodeType === \XMLReader::ELEMENT) {
				switch($reader->name) {
					case 'profile':
						$useForConfiguration = $reader->getAttribute('useForConfiguration');
						break;
					case 'profileName':
						$name = $reader->readOuterXML();
						break;
					case 'profileDescription':
						$description = $reader->readOuterXML();
						break;
					case 'locale':
						$locale = $reader->getAttribute('lang').'_'.$reader->getAttribute('country');
						$locales[$locale] = [
							'lang' => $reader->getAttribute('lang'),
							'country' => $reader->getAttribute('country'),
							'locale' => $locale,
							'display' => $reader->readOuterXML()
						];
						break;
					case 'lists':
						break(2);
				}
			}
		}
		$reader->close();		

		return [
			'useForConfiguration' => $useForConfiguration,
			'display' => $name,
			'description' => $description,
			'locales' => $locales,
		];
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function loadProfile(string $directory, string $profile) : bool {
		$path = caGetProfilePath($directory, $profile);
		if(is_readable($path)) {
			$this->xml = @simplexml_load_file($path);
			
			if($base_profile = self::getAttribute($this->xml, "base")) {
				if($base_path = caGetProfilePath($directory, $base_profile)) {
					$this->base = @simplexml_load_file($base_path);
				}
			}
			
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------
	/**
	 * Validate profile
	 */
	private function validateProfile(string $directory, string $profile) {
		$profile_path = caGetProfilePath($directory, $profile);
		$base_path = caGetProfilePath($directory, 'base');
		$schema_path = caGetProfilePath($directory, 'profile.xsd');
		
		// simplexml doesn't support validation -> use DOMDocument
		$vo_profile = new \DOMDocument();
		@$vo_profile->load($profile_path);

		if($this->base) {
			$vo_base = new \DOMDocument();
			$vo_base->load($base_path);

			if($this->debug) {
				ob_start();
				$vb_return = $vo_profile->schemaValidate($schema_path) && $vo_base->schemaValidate($schema_path);
				$this->profile_debug .= ob_get_clean();
			} else {
				$vb_return = @$vo_profile->schemaValidate($schema_path) && @$vo_base->schemaValidate($schema_path);
			}
		} else {
			if($this->debug) {
				ob_start();
				$vb_return = $vo_profile->schemaValidate($schema_path);
				$this->profile_debug .= ob_get_clean();
			} else {
				$vb_return = @$vo_profile->schemaValidate($schema_path);
			}
		}

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
	public function processLocales(?array $options=null) : bool {
		$locale_list = [];
		if($this->base && $this->base->locales) { $locale_list[] = $this->base->locales->children(); }
		$locale_list[] = $this->xml->locales->children();
		
		$this->data['locales'] = [];
		foreach($locale_list as $locales) {
			foreach($locales as $locale) {
				$name = (string)$locale;
				$language = self::getAttribute($locale, "lang");
				$dialect = self::getAttribute($locale, "dialect");
				$country = self::getAttribute($locale, "country");
				$dont_use_for_cataloguing = (int)self::getAttribute($locale, "dontUseForCataloguing");
				$locale_code = $dialect ? $language."_".$country.'_'.$dialect : $language."_".$country;

			
				$this->data['locales'][$locale_code] = [
					'name' => $name,
					'language' => $language,
					'dialect' => $dialect,
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
	public function processLists($f_callback=null) {
		$lists_list = [];
		if($this->base && $this->base->lists) { $lists_list[] = $this->base->lists->children(); }
		$lists_list[] = $this->xml->lists->children();
	
		$this->data['lists'] = [];
		foreach($lists_list as $lists) {
			foreach($lists as $list) {
				$list_code = self::getAttribute($list, "code");
				$deleted = (bool)self::getAttribute($list, "deleted");
				$hierarchical = (bool)self::getAttribute($list, "hierarchical");
				$system = (bool)self::getAttribute($list, "system");
				$voc = (bool)self::getAttribute($list, "vocabulary");
				$def_sort = (int)self::getAttribute($list, "defaultSort");
			
				$labels = self::getLabelsFromXML($list->labels);

				$this->data['lists'][$list_code] = [
					'labels' => $labels,
					'code' => $list_code,
					'deleted' => $deleted,
					'hierarchical' => $hierarchical,
					'system' => $system,
					'vocabulary' => $voc,
					'defaultSort' => $def_sort,
					'items' => $list->items ? $this->processListItems($list->items) : []
				];
			}
		}
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processListItems($items) {
		$values = [];
		foreach($items->children() as $item) {
			$item_value = self::getAttribute($item, "value");
			$item_idno = self::getAttribute($item, "idno");
			$type = self::getAttribute($item, "type");
			$status = self::getAttribute($item, "status");
			$access = self::getAttribute($item, "access");
			$rank = self::getAttribute($item, "rank");
			$enabled = self::getAttribute($item, "enabled");
			$default = self::getAttribute($item, "default");
			$color = self::getAttribute($item, "color");

			if (!isset($item_value) || !strlen(trim($item_value))) {
				$item_value = $item_idno;
			}

			if (!isset($status)) { $status = 0; }
			if (!isset($access)) { $access = 0; }
			if (!isset($rank)) { $rank = 0; }

			$deleted = self::getAttribute($item, "deleted");
			
			$labels = self::getLabelsFromXML($item->labels);
		
			$settings = $item->settings ? $this->getSettingsFromXML($item->settings) : [];
				
			if (isset($item->items)) {
				$sub_items = $this->processListItems($item->items);
			}
			$values[$item_idno] = [
				'idno' => $item_idno,
				'value' => $item_value,
				'labels' => $labels,
				'settings' => $settings,
				'type' => $type,
				'status' => $status,
				'access' => $access,
				'rank' => $rank,
				'enabled' => $enabled,
				'default' => $default,
				'color' => $color,
				'deleted' => $deleted,
				'items' => $sub_items
			];
		}

		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processMetadataElementSets($f_callback=null) {
		$elements_list = [];
		if($this->base && $this->base->elementSets) { $elements_list[] = $this->base->elementSets->children(); }
		$elements_list[] = $this->xml->elementSets->children();
	
		$this->data['metadataElements'] = [];
		$acc = [];
		foreach($elements_list as $elements) {
			$acc = array_merge($acc, $this->processMetadataElements($elements));
		}
		$this->data['metadataElements'] = $acc;
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	protected function processMetadataElements($elements) {
		$element_list = [];
		foreach($elements as $element) {
			$element_code = self::getAttribute($element, "code");
			$deleted = (bool)self::getAttribute($element, "deleted");
			$datatype = (string)self::getAttribute($element, "datatype");
			$list = (string)self::getAttribute($element, "list");
			$documentation_url = (string)self::getAttribute($element, "documentationUrl");
	
			$labels = self::getLabelsFromXML($element->labels);
			$settings = $element->settings ? $this->getSettingsFromXML($element->settings) : [];
		
			if ($po_element->elements) {
				foreach($po_element->elements->children() as $vo_child) {
					$this->processMetadataElement($vo_child, $vn_element_id);
				}
			}
		
			$subelements = null;
			if($element->elements) {
				$subelements = $this->processMetadataElements($element->elements->children());
			}
			
			$type_restrictions = [];
			if($element->typeRestrictions) {
				foreach($element->typeRestrictions->children() as $restriction) {
					$restriction_code = self::getAttribute($restriction, "code");
    			    $table = (string)$restriction->table;
					$type = trim((string)$restriction->type);
					$include_subtypes = (bool)$restriction->includeSubtypes;
					$restriction_settings = $restriction->settings ? $this->getSettingsFromXML($restriction->settings) : [];

					$type_restrictions[] = [
						'code' => $restriction_code,
						'table' => $table,
						'type' => $type,
						'includeSubtypes' => $include_subtypes,
						'settings' => $restriction_settings
					];
				}
			}
			
			$labels = self::getLabelsFromXML($element->labels);
		
			$element_list[$element_code] = [
				'labels' => $labels,
				'code' => $element_code,
				'datatype' => $datatype,
				'list' => $list,
				'deleted' => $deleted,
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
	public function processRelationshipTypes() {
		$relationship_types_list = [];
		if($this->base && $this->base->relationshipTypes) { $relationship_types_list[] = $this->base->relationshipTypes->children(); }
		$relationship_types_list[] = $this->xml->relationshipTypes->children();

		$types_by_table = [];
		foreach($relationship_types_list as $relationship_types) {
			foreach($relationship_types as $rel_table_config) {
				$rel_table = self::getAttribute($rel_table_config, "name");
				$this->logStatus(_t('Processing relationship types for table %1', $rel_table));

				$types = $this->processRelationshipTypesForTable($rel_table_config->types);
			
				$types_by_table[self::getAttribute($rel_table_config, "name")] = $types;
			}
		}
		$this->data['relationshipTypes'] = $types_by_table;
		return true;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processRelationshipTypesForTable($relationship_types) {
		$type_list = [];
		foreach($relationship_types->children() as $type) {
			$type_code = self::getAttribute($type, "code");
			$default = self::getAttribute($type, "default");
			$rank = (int)self::getAttribute($type, "rank");
			$left_restriction = self::getAttribute($type, "typeRestrictionLeft");
			$right_restriction = self::getAttribute($type, "typeRestrictionRight");
			$include_subtypes_left = self::getAttribute($type, "includeSubtypesLeft");
			$include_subtypes_right = self::getAttribute($type, "includeSubtypesRight");
			

			$this->logStatus(_t('Processing relationship type with code %1', $type_code));

			$sub_types = [];
			if ($type->types) {
				$sub_types = $this->processRelationshipTypesForTable($type->types);
			}
			
			$labels = self::getLabelsFromXML($type->labels);
			
			$type_list[] = [
				'labels' => $labels,
				'code' => $type_code,
				'default' => $default,
				'rank' => $rank,
				'typeRestrictionLeft' => $left_restriction,
				'typeRestrictionRight' => $right_restriction,
				'includeSubtypesLeft' => $include_subtypes_left,
				'includeSubtypesRight' => $include_subtypes_right,
				'types' => $sub_types
			];
		}
		return $type_list;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function processUIs($f_callback=null) {
		$ui_list = [];
		if($this->base && $this->base->userInterfaces) { $lists_list[] = $this->base->userInterfaces->children(); }
		$ui_list[] = $this->xml->userInterfaces->children();
	
		$this->data['userInterfaces'] = [];
		foreach($ui_list as $uis) {
			foreach($uis as $ui) {
				$ui_code = self::getAttribute($ui, "code");
				$type = self::getAttribute($ui, "type");
				$color = self::getAttribute($ui, "color");
				$include_subtypes = self::getAttribute($ui, "includeSubtypes");
			
				$labels = self::getLabelsFromXML($ui->labels);
				
				$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($ui, "typeRestrictions"));
				if($ui->typeRestrictions) { 
					$type_restrictions = array_merge($type_restrictions, self::processTypeRestrictionLists($ui->typeRestrictions));
				}
				
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
					'screens' => $ui->screens ? $this->processUIScreens($ui->screens) : [],
					'userAccess' => $user_access,
					'groupAccess' => $group_access,
					'roleAccess' => $role_access
				];
			}
		}
		print_r($this->data['userInterfaces']);die;
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
			
			$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($screen, "typeRestrictions"));
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
			
			$type_restrictions = self::processTypeRestrictionStrings(self::getAttribute($bundle, "typeRestrictions"));
			
			$settings = $bundle->settings ? $this->getSettingsFromXML($bundle->settings) : [];
			
			$values[$code] = [
				'code' => $code,
				'bundle' => $bundle_name,
				'settings' => $settings,
				'includeSubtypes' => $include_subtypes,
				'type_restrictions' => $type_restrictions
			];
		}

		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function processTypeRestrictionStrings($type_restrictions) {
		return array_filter(preg_split("![ ,;\|]!", $type_restrictions), "strlen");
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function processTypeRestrictionLists($type_restrictions) {
		$values = [];
		if($type_restrictions) {
			foreach($type_restrictions->children() as $restriction) {
				$values[] = [
					'type' => self::getAttribute($restriction, 'type'),
					'includeSubtypes' => self::getAttribute($restriction, 'includeSubtypes')
				];
			}
		}
		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private static function processAccessRestrictions($type, $restrictions) {
		$values = [];
		if($restrictions) {
			foreach($restrictions->children() as $restriction) {
				$values[] = [
					$type => self::getAttribute($restriction, $type),
					'access' => self::getAttribute($restriction, 'access')
				];
			}
		}
		return $values;
	}
	# --------------------------------------------------
	/**
	 * 
	 *
	 * @param SimpleXMLElement $labels
	 * @param bool $force_preferred
	 *
	 * @return array
	 */
	protected static function getLabelsFromXML($labels, $force_preferred=false) {
		$values = [];
		foreach($labels->children() as $label) {
			$locale = self::getAttribute($label, "locale");
			$preferred = self::getAttribute($label, "preferred");
			
			if($force_preferred || (bool)$preferred || is_null($preferred)) {
				$preferred = true;
			} else {
				$preferred = false;
			}

			$value = ['locale' => $locale, 'preferred' => $preferred];
			foreach($label->children() as $field) {
				$value[$field->getName()] = (string) $field;
			}
			
			$values[] = $value;
		}
		return $values;
	}
	# --------------------------------------------------
	/**
	 *
	 */
	private function getSettingsFromXML($settings, $options=null) {
		$settings_values = [];
		
		if($settings) {
			foreach($settings->children() as $setting) {
				// some settings like 'label' or 'add_label' have 'locale' as sub-setting
				$locale = self::getAttribute($setting, "locale");
				if($locale && isset($this->data['locales'][$locale])) {
					$locale_id = $this->data['locales'][$locale];
				} else {
					$locale_id = null;
				}

				$setting_name = self::getAttribute($setting, "name");
				$setting_value = (string)$setting;
				
				if((strlen($setting_name)>0) && (strlen($setting_value)>0)) { // settings need at least name and value
					$settings_values[$setting_name][$locale ?? ''][] = $setting_value;
				}
			}
		}
		
		return $settings_values;
	}
	# --------------------------------------------------
	/**
	 * 
	 */
	protected static function getAttribute($node, $attr) {
		if(isset($node[$attr])) {
			return (string) $node[$attr];
		} else {
			return null;
		}
	}
	# --------------------------------------------------
}
