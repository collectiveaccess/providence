<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ConfigurationExporter.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage Configuration
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
require_once(__CA_MODELS_DIR__."/ca_lists.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_editor_ui_bundle_placements.php");
require_once(__CA_MODELS_DIR__."/ca_user_roles.php");
require_once(__CA_MODELS_DIR__."/ca_user_groups.php");
require_once(__CA_MODELS_DIR__."/ca_search_forms.php");
require_once(__CA_MODELS_DIR__."/ca_search_form_placements.php");



final class ConfigurationExporter {
	# -------------------------------------------------------
	private $opo_config;
	private $opo_dm;
	private $opo_db;
	private $opt_locale;
	private $opo_dom;
	# -------------------------------------------------------

	# -------------------------------------------------------
	public function __construct() {
		$this->opo_config = Configuration::load();
		$this->opo_db = new Db();
		$this->opo_dm = Datamodel::load();
		
		$this->opo_dom = new DOMDocument('1.0', 'utf-8');
		$this->opo_dom->preserveWhiteSpace = false;
		$this->opo_dom->formatOutput = true;
		
		$this->opt_locale = new ca_locales();
	}
	# -------------------------------------------------------
	/**
	 * Export current configuration as XML profile
	 * @param type $ps_name Name of the profile, used for "profileName" element
	 * @param type $ps_description Description of the profile, used for "profileDescription" element
	 * @param type $ps_base Base profile
	 * @param type $ps_info_url Info URL for the profile
	 * @param type $pa_options Associative array of options. Possible keys
	 * @return type string profile as XML string
	 */
	public static function exportConfigurationAsXML($ps_name="",$ps_description="",$ps_base="",$ps_info_url="",$pa_options=null) {
		$o_exporter = new ConfigurationExporter();
		
		$vo_root = $o_exporter->getDOM()->createElement('profile');
		$o_exporter->getDOM()->appendChild($vo_root);

		// "profile" attributes

		$vo_root->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
		$vo_root->setAttribute("xsi:noNamespaceSchemaLocation","profile.xsd");
		$vo_root->setAttribute("useForConfiguration", 1);
		
		if(strlen($ps_base)>0){
			$vo_root->setAttribute("base", $ps_base);
		}
		
		$vo_root->setAttribute("infoUrl", $ps_info_url);

		if(strlen($ps_name)>0){
			$vo_root->appendChild($o_exporter->getDOM()->createElement("profileName",$ps_name));
		} else {
			$vo_root->appendChild($o_exporter->getDOM()->createElement("profileName",__CA_APP_NAME__));
		}
		
		if(strlen($ps_description)>0){
			$vo_root->appendChild($o_exporter->getDOM()->createElement("profileDescription",$ps_description));
		} else {
			$vo_root->appendChild($o_exporter->getDOM()->createElement("profileDescription"));
		}
		
		$vo_root->appendChild($o_exporter->getLocalesAsDOM());
		$vo_root->appendChild($o_exporter->getListsAsDOM());
		$vo_root->appendChild($o_exporter->getElementsAsDOM());
		$vo_root->appendChild($o_exporter->getUIsAsDOM());
		$vo_root->appendChild($o_exporter->getRelationshipTypesAsDOM());
		$vo_root->appendChild($o_exporter->getRolesAsDOM());
		$vo_root->appendChild($o_exporter->getGroupsAsDOM());
		
		/* hack to import string XML to existing document, have to rewrite display exporter at some point */
		
		$vo_fragment = $o_exporter->getDOM()->createDocumentFragment();
		$vo_fragment->appendXML($o_exporter->getDisplaysAsXML());
		$o_exporter->getDOM()->getElementsByTagName('profile')->item(0)->appendChild($vo_fragment);
		
		$vo_root->appendChild($o_exporter->getSearchFormsAsDOM());
		
		/* hack for decent formatting */
		$vs_string = $o_exporter->getDOM()->saveXML();
		
		$vo_dom = new DOMDocument('1.0', 'utf-8');
		$vo_dom->preserveWhiteSpace = false;
		$vo_dom->loadXML($vs_string);
		$vo_dom->formatOutput = true;
		return $vo_dom->saveXML();
	}
	# -------------------------------------------------------
	public function getDOM(){
		return $this->opo_dom;
	}
	# -------------------------------------------------------
	public function getLocalesAsDOM(){
		// locales

		$qr_locales = $this->opo_db->query("SELECT * FROM ca_locales ORDER BY locale_id");

		$vo_locales = $this->opo_dom->createElement("locales");

		while($qr_locales->nextRow()){	
			$vo_locale = $this->opo_dom->createElement("locale",caEscapeForXML($qr_locales->get("name")));
			$vo_locale->setAttribute("lang", $qr_locales->get("language"));
			$vo_locale->setAttribute("country", $qr_locales->get("country"));

			if($qr_locales->get("dialect")){
				$vo_locale->setAttribute("dialect", $qr_locales->get("dialect"));
			}

			if($qr_locales->get("dont_use_for_cataloguing")){
				$vo_locale->setAttribute("dontUseForCataloguing", $qr_locales->get("dont_use_for_cataloguing"));
			}

			$vo_locales->appendChild($vo_locale);
		}
		
		return $vo_locales;
	}
	# -------------------------------------------------------
	public function getListsAsDOM(){
		$qr_lists = $this->opo_db->query("SELECT * FROM ca_lists ORDER BY list_id");

		$vo_lists = $this->opo_dom->createElement("lists");

		$t_list = new ca_lists();

		while($qr_lists->nextRow()){
			$vo_list = $this->opo_dom->createElement("list");
			$vo_list->setAttribute("code", $this->makeIDNO($qr_lists->get("list_code")));
			$vo_list->setAttribute("hierarchical", $qr_lists->get("is_hierarchical"));
			$vo_list->setAttribute("system", $qr_lists->get("is_system_list"));
			$vo_list->setAttribute("vocabulary", $qr_lists->get("use_as_vocabulary"));
			if($qr_lists->get("default_sort")){
				$vo_list->setAttribute("defaultSort", $qr_lists->get("default_sort"));
			}
			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_list_labels = $this->opo_db->query("SELECT * FROM ca_list_labels WHERE list_id=?",$qr_lists->get("list_id"));

			// label-less lists are ignored
			if($qr_list_labels->numRows() == 0){
				continue;
			} else {
				while($qr_list_labels->nextRow()) {
					$vo_label = $this->opo_dom->createElement("label");

					$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_list_labels->get("locale_id")));
					$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_list_labels->get("name"))));

					$vo_labels->appendChild($vo_label);
				}
			}

			$vo_list->appendChild($vo_labels);

			$vo_items = $this->getListItemsAsDOM($t_list->getRootItemIDForList($qr_lists->get("list_code")));
			if($vo_items){
				$vo_list->appendChild($vo_items);
			}

			$vo_lists->appendChild($vo_list);
		}
		
		return $vo_lists;
	}
	# -------------------------------------------------------
	private function getListItemsAsDOM($pn_parent_id){
		$qr_items = $this->opo_db->query("SELECT * FROM ca_list_items WHERE parent_id=? AND deleted=0",$pn_parent_id);

		if(!($qr_items->numRows()>0)){
			return false;
		}

		$vo_items = $this->opo_dom->createElement("items");

		while($qr_items->nextRow()){
			$vo_item = $this->opo_dom->createElement("item");

			$vo_item->setAttribute("idno", $this->makeIDNO($qr_items->get("idno")));
			$vo_item->setAttribute("enabled", $qr_items->get("is_enabled"));
			$vo_item->setAttribute("default", $qr_items->get("is_default"));
			if(is_numeric($vn_value = $qr_items->get("item_value"))){
				$vo_item->setAttribute("value", $vn_value);
			}

			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_list_item_labels = $this->opo_db->query("SELECT * FROM ca_list_item_labels WHERE item_id=?",$qr_items->get("item_id"));
			if($qr_list_item_labels->numRows() > 0){
				while($qr_list_item_labels->nextRow()) {
					$vo_label = $this->opo_dom->createElement("label");

					$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_list_item_labels->get("locale_id")));
					$vo_label->setAttribute("preferred", $qr_list_item_labels->get("is_preferred"));
					$vo_label->appendChild($this->opo_dom->createElement("name_singular",caEscapeForXML($qr_list_item_labels->get("name_singular"))));
					$vo_label->appendChild($this->opo_dom->createElement("name_plural",caEscapeForXML($qr_list_item_labels->get("name_plural"))));

					$vo_labels->appendChild($vo_label);
				}
			} else { // fallback if list item has no labels: add idno label in en_US
				$vo_label = $this->opo_dom->createElement("label");
				$vo_label->setAttribute("preferred", "1");
				$vo_label->setAttribute("locale", "en_US");
				$vo_label->appendChild($this->opo_dom->createElement("name_singular",caEscapeForXML($this->makeIDNO($qr_items->get("idno")))));
				$vo_label->appendChild($this->opo_dom->createElement("name_plural",caEscapeForXML($this->makeIDNO($qr_items->get("idno")))));
				$vo_labels->appendChild($vo_label);
			}

			$vo_item->appendChild($vo_labels);

			if($vo_sub_items = $this->getListItemsAsDOM($qr_items->get("item_id"))){
				$vo_item->appendChild($vo_sub_items);
			}

			$vo_items->appendChild($vo_item);
		}

		return $vo_items;
	}
	# -------------------------------------------------------
	public function getElementsAsDOM(){
		$t_list = new ca_lists();
		
		$vo_elements = $this->opo_dom->createElement("elementSets");

		$qr_elements = $this->opo_db->query("SELECT * FROM ca_metadata_elements WHERE parent_id IS NULL ORDER BY element_id");

		$t_element = new ca_metadata_elements();

		while($qr_elements->nextRow()){
			$vo_element = $this->opo_dom->createElement("metadataElement");

			$vo_element->setAttribute("code", $this->makeIDNO($qr_elements->get("element_code")));
			$vo_element->setAttribute("datatype",  ca_metadata_elements::getAttributeNameForTypeCode($qr_elements->get("datatype")));
			if($qr_elements->get("list_id")){
				$t_list->load($qr_elements->get("list_id"));
				$vo_element->setAttribute("list", $t_list->get("list_code"));
			}

			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_element_labels = $this->opo_db->query("SELECT * FROM ca_metadata_element_labels WHERE element_id=?",$qr_elements->get("element_id"));
			while($qr_element_labels->nextRow()) {
				$vo_label = $this->opo_dom->createElement("label");

				$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_element_labels->get("locale_id")));
				$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_element_labels->get("name"))));
				if(strlen(trim($qr_element_labels->get("description")))>0){
					$vo_label->appendChild($this->opo_dom->createElement("description",caEscapeForXML($qr_element_labels->get("description"))));
				}		

				$vo_labels->appendChild($vo_label);
			}

			$vo_element->appendChild($vo_labels);

			$t_element->load($qr_elements->get("element_id"));
			$va_settings = $t_element->getSettings();
			$va_available_settings = $t_element->getAvailableSettings();

			if(is_array($va_settings)){
				$vo_settings = $this->opo_dom->createElement("settings");
				$vb_append_settings_element = false;
				foreach($t_element->getSettings() as $vs_setting => $vs_value){
					if($t_element->isValidSetting($vs_setting) && ($vs_value != $va_available_settings[$vs_setting]["default"])){
						$vo_setting = $this->opo_dom->createElement("setting",$vs_value);
						$vo_setting->setAttribute("name", $vs_setting);
						$vo_settings->appendChild($vo_setting);
						$vb_append_settings_element = true;
					}		
				}

				if($vb_append_settings_element){
					$vo_element->appendChild($vo_settings);
				}
			}

			$vo_sub_elements = $this->getElementAsDOM($qr_elements->get("element_id"));
			if($vo_sub_elements){
				$vo_element->appendChild($vo_sub_elements);
			}

			$vo_restrictions = $this->opo_dom->createElement("typeRestrictions");

			foreach($t_element->getTypeRestrictions() as $va_restriction){
				$t_restriction = new ca_metadata_type_restrictions($va_restriction["restriction_id"]);
				$vo_restriction = $this->opo_dom->createElement("restriction");
				$vo_table = $this->opo_dom->createElement("table",$this->opo_dm->getTableName($t_restriction->get("table_num")));
				$vo_restriction->appendChild($vo_table);

				if($t_restriction->get("type_id")){
					$t_instance = $this->opo_dm->getInstanceByTableNum($t_restriction->get("table_num"));
					$vs_type_code = $t_instance->getTypeListCode();

					$va_item = $t_list->getItemFromListByItemID($vs_type_code, $t_restriction->get("type_id"));
					$vo_type = $this->opo_dom->createElement("type",$va_item["idno"]);

					$vo_restriction->appendChild($vo_type);
				}
							
				if (isset($va_restriction['include_subtypes']) && (bool)$va_restriction['include_subtypes']) {
					$vo_include_subtypes = $this->opo_dom->createElement('includeSubtypes', '1');
					$vo_restriction->appendChild($vo_include_subtypes);
				}

				if(is_array($va_restriction_settings = $t_restriction->getSettings())){
					$vo_settings = $this->opo_dom->createElement("settings");
					
					foreach($va_restriction_settings as $vs_setting => $vs_value){
						$vo_setting = $this->opo_dom->createElement("setting",$vs_value);
						$vo_setting->setAttribute("name", $vs_setting);
						$vo_settings->appendChild($vo_setting);
					}

					$vo_restriction->appendChild($vo_settings);
				}
				
				$vo_restrictions->appendChild($vo_restriction);
			}

			$vo_element->appendChild($vo_restrictions);

			$vo_elements->appendChild($vo_element);
		}
		
		return $vo_elements;
	}
	# -------------------------------------------------------
	private function getElementAsDOM($pn_parent_id){
		$t_element = new ca_metadata_elements();
		$t_list = new ca_lists();

		$qr_elements = $this->opo_db->query("SELECT * FROM ca_metadata_elements WHERE parent_id = ? ORDER BY element_id",$pn_parent_id);
		if(!$qr_elements->numRows()){
			return null;
		}

		$vo_elements = $this->opo_dom->createElement("elements");

		while($qr_elements->nextRow()){
			$vo_element = $this->opo_dom->createElement("metadataElement");

			$vo_element->setAttribute("code", $this->makeIDNO($qr_elements->get("element_code")));
			$vo_element->setAttribute("datatype",  ca_metadata_elements::getAttributeNameForTypeCode($qr_elements->get("datatype")));
			if($qr_elements->get("list_id")){
				$t_list->load($qr_elements->get("list_id"));
				$vo_element->setAttribute("list", $t_list->get("list_code"));
			}

			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_element_labels = $this->opo_db->query("SELECT * FROM ca_metadata_element_labels WHERE element_id=?",$qr_elements->get("element_id"));
			while($qr_element_labels->nextRow()) {
				$vo_label = $this->opo_dom->createElement("label");

				$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_element_labels->get("locale_id")));
				$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_element_labels->get("name"))));
				if(strlen(trim($qr_element_labels->get("description")))>0){
					$vo_label->appendChild($this->opo_dom->createElement("description",caEscapeForXML($qr_element_labels->get("description"))));
				}		

				$vo_labels->appendChild($vo_label);
			}

			$vo_element->appendChild($vo_labels);

			$t_element->load($qr_elements->get("element_id"));

			$vo_settings = $this->opo_dom->createElement("settings");
			if(is_array($va_settings = $t_element->getSettings())){
				foreach($va_settings as $vs_setting => $va_values){
					if(is_null($va_values)) { continue; }
					if(!is_array($va_values)) { $va_values = array($va_values); }
					foreach($va_values as $vs_value){
						$vo_setting = $this->opo_dom->createElement("setting",$vs_value);
						$vo_setting->setAttribute("name", $vs_setting);
						$vo_settings->appendChild($vo_setting);
					}
				}
			}
			$vo_element->appendChild($vo_settings);
			
			$vo_sub_elements = $this->getElementAsDOM($qr_elements->get("element_id"));
			if($vo_sub_elements){
				$vo_element->appendChild($vo_sub_elements);
			}

			$vo_elements->appendChild($vo_element);
		}

		return $vo_elements;
	}
	# -------------------------------------------------------
	public function getUIsAsDOM(){
		$t_list = new ca_lists();
		
		$vo_uis = $this->opo_dom->createElement("userInterfaces");
		
		$qr_uis = $this->opo_db->query("SELECT * FROM ca_editor_uis ORDER BY ui_id");
		
		while($qr_uis->nextRow()){
			$vo_ui = $this->opo_dom->createElement("userInterface");
			
			$vs_type = $this->opo_dm->getTableName($qr_uis->get("editor_type"));
			
			if(strlen($vs_code = $qr_uis->get("editor_code")) > 0){
				$vo_ui->setAttribute("code", $this->makeIDNO($vs_code));
			} else {
				$vo_ui->setAttribute("code", "standard_{$vs_type}_ui");
			}
			
			$vo_ui->setAttribute("type", $vs_type);
			
			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_ui_labels = $this->opo_db->query("SELECT * FROM ca_editor_ui_labels WHERE ui_id=?",$qr_uis->get("ui_id"));
			if($qr_ui_labels->numRows() > 0){
				while($qr_ui_labels->nextRow()) {
					if($vs_locale = $this->opt_locale->localeIDToCode($qr_ui_labels->get("locale_id"))){
						$vo_label = $this->opo_dom->createElement("label");
						$vo_label->setAttribute("locale", $vs_locale);
						$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_ui_labels->get("name"))));
						$vo_labels->appendChild($vo_label);
					}
				}
			} else {
				$vo_label = $this->opo_dom->createElement("label");
				$vo_label->setAttribute("locale", "en_US");
				$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($vs_code)));
				$vo_labels->appendChild($vo_label);
			}

			$vo_ui->appendChild($vo_labels);
			
			$vo_screens = $this->opo_dom->createElement("screens");
			$qr_screens = $this->opo_db->query("SELECT * FROM ca_editor_ui_screens WHERE parent_id IS NOT NULL AND ui_id=? ORDER BY screen_id",$qr_uis->get("ui_id"));
			
			while($qr_screens->nextRow()){
				$t_screen = new ca_editor_ui_screens($qr_screens->get("screen_id"));
				
				$vo_screen = $this->opo_dom->createElement("screen");
				if($vs_idno = $qr_screens->get("idno")){
					$vo_screen->setAttribute("idno", $this->makeIDNO($vs_idno));
				}
				
				$vo_screen->setAttribute("default", $qr_screens->get("is_default"));
				
				$vo_labels = $this->opo_dom->createElement("labels");
				$qr_screen_labels = $this->opo_db->query("SELECT * FROM ca_editor_ui_screen_labels WHERE screen_id=?",$qr_screens->get("screen_id"));
				if($qr_ui_labels->numRows() > 0){
					while($qr_screen_labels->nextRow()) {
						if($vs_locale = $this->opt_locale->localeIDToCode($qr_screen_labels->get("locale_id"))){
							$vo_label = $this->opo_dom->createElement("label");
							$vo_label->setAttribute("locale", $vs_locale);
							$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_screen_labels->get("name"))));
							if(strlen(trim($qr_screen_labels->get("description")))>0){
								$vo_label->appendChild($this->opo_dom->createElement("description",caEscapeForXML($qr_screen_labels->get("description"))));
							}
							$vo_labels->appendChild($vo_label);
						}
					}
				} else {
					$vo_label = $this->opo_dom->createElement("label");
					$vo_label->setAttribute("locale", "en_US");
					$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($vs_code)));
					$vo_labels->appendChild($vo_label);
				}

				$vo_screen->appendChild($vo_labels);
				
				if(is_array($t_screen->getTypeRestrictions()) && sizeof($t_screen->getTypeRestrictions())>0){
					$vo_type_restrictions = $this->opo_dom->createElement("typeRestrictions");

					foreach($t_screen->getTypeRestrictions() as $va_restriction){
						$vo_type_restriction = $this->opo_dom->createElement("restriction");

						$t_instance = $this->opo_dm->getInstanceByTableNum($va_restriction["table_num"]);
						$vs_type_code = $t_instance->getTypeListCode();
						$va_item = $t_list->getItemFromListByItemID($vs_type_code, $va_restriction["type_id"]);

						$vo_type_restriction->setAttribute("type", $va_item["idno"]);

						$vo_type_restrictions->appendChild($vo_type_restriction);
					}

					$vo_screen->appendChild($vo_type_restrictions);
				}
				
				$vo_placements = $this->opo_dom->createElement("bundlePlacements");
				$va_placements = $t_screen->getPlacementsInScreen();

				if(is_array($va_placements)){

					foreach($va_placements as $va_placement){
						$vo_placement = $this->opo_dom->createElement("placement");
						$vo_placements->appendChild($vo_placement);
						
						$vo_placement->setAttribute("code", $this->makeIDNO($va_placement["placement_code"]));
						$vo_placement->appendChild($this->opo_dom->createElement("bundle",caEscapeForXML($va_placement["bundle"])));
						
						if(is_array($va_placement["settings"])){
							$vo_settings = $this->opo_dom->createElement("settings");
							foreach($va_placement["settings"] as $vs_setting => $va_values){
								if(is_null($va_values)) { continue; }
								if(!is_array($va_values)){ $va_values = array($va_values); }
								
								// account for legacy settings
								if($vs_setting=="restrict_to_type") $vs_setting = "restrict_to_types";
								
								foreach($va_values as $vs_key => $vs_value){
									switch($vs_setting) {
										case 'restrict_to_types':
											$t_item = new ca_list_items($vs_value);
											if ($t_item->getPrimaryKey()) {
												$vs_value = $t_item->get('idno');
											}
											break;
										case 'restrict_to_lists':
											$t_list = new ca_lists($vs_value);
											if ($t_list->getPrimaryKey()) {
												$vs_value = $t_list->get('list_code');
											}
											break;
										case 'restrict_to_relationship_types':
											$t_rel_type = new ca_relationship_types($vs_value);
											if ($t_rel_type->getPrimaryKey()) {
												$vs_value = $t_rel_type->get('type_code');
											}
											break;
									}
									if(strlen($vs_value)>0){
										if($vs_value === 0 || $vs_value === "0"){ // caExcapeForXML mangles zero values for some reason -> catch them here.
											$vs_setting_val = $vs_value;
										} else {
											$vs_setting_val = caEscapeForXML($vs_value);
										}
										$vo_setting = @$this->opo_dom->createElement("setting", $vs_setting_val);
										
										$vo_setting->setAttribute("name", $vs_setting);
										if($vs_setting=="label" || $vs_setting=="add_label" || $vs_setting=="description"){
											if(preg_match("/^[a-z]{2,3}\_[A-Z]{2,3}$/",$vs_key)){
												$vo_setting->setAttribute("locale", $vs_key);
											} else {
												continue;
											}
										}
										$vo_settings->appendChild($vo_setting);
									}
								}
								
								
							}

							$vo_placement->appendChild($vo_settings);
						}
						
					}
				}
				
				$vo_screen->appendChild($vo_placements);
				
				$vo_screens->appendChild($vo_screen);
			}
			
			$vo_ui->appendChild($vo_screens);
			
			$vo_uis->appendChild($vo_ui);
		}
		
		
		return $vo_uis;
	}
	# -------------------------------------------------------
	public function getRelationshipTypesAsDOM(){
		$vo_rel_types = $this->opo_dom->createElement("relationshipTypes");
		
		$qr_tables = $this->opo_db->query("SELECT DISTINCT table_num FROM ca_relationship_types ORDER BY type_id");
		
		while($qr_tables->nextRow()){
			$vo_table = $this->opo_dom->createElement("relationshipTable");
			$vo_table->setAttribute("name", $this->opo_dm->getTableName($qr_tables->get("table_num")));
			
			$qr_root = $this->opo_db->query("
				SELECT type_id FROM ca_relationship_types
				WHERE table_num=?
				AND parent_id IS NULL
			",$qr_tables->get("table_num"));
			if($qr_root->nextRow()) $vn_parent = $qr_root->get("type_id");
			
			if($vo_types = $this->getRelationshipTypesForParentAsDOM($vn_parent)){
				$vo_table->appendChild($vo_types);
				$vo_rel_types->appendChild($vo_table);
			}
		}
		
		return $vo_rel_types;
	}
	# -------------------------------------------------------
	private function getRelationshipTypesForParentAsDOM($pn_parent_id){
		$t_list = new ca_lists();
		
		$vo_types = $this->opo_dom->createElement("types");
		
		$qr_types = $this->opo_db->query("SELECT * FROM ca_relationship_types WHERE parent_id=?",$pn_parent_id);
		if(!$qr_types->numRows()) return false;
		
		while($qr_types->nextRow()){
			$vo_type = $this->opo_dom->createElement("type");
			
			if(preg_match("/root\_for\_[0-9]{1,3}/",$qr_types->get("type_code"))){ // ignore legacy root records
				continue;
			}
			
			$vo_type->setAttribute("code", $this->makeIDNO($qr_types->get("type_code")));
			
			$vo_type->setAttribute("default", $qr_types->get("is_default"));
			
			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_type_labels = $this->opo_db->query("SELECT * FROM ca_relationship_type_labels WHERE type_id=?",$qr_types->get("type_id"));
			while($qr_type_labels->nextRow()) {
				$vo_label = $this->opo_dom->createElement("label");

				$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_type_labels->get("locale_id")));
				$vo_label->appendChild($this->opo_dom->createElement("typename",caEscapeForXML($qr_type_labels->get("typename"))));
				$vo_label->appendChild($this->opo_dom->createElement("typename_reverse",caEscapeForXML($qr_type_labels->get("typename_reverse"))));

				$vo_labels->appendChild($vo_label);
			}

			$vo_type->appendChild($vo_labels);
			
			// restrictions (left side)
			$t_instance = $this->opo_dm->getInstanceByTableNum($qr_types->get("table_num"));
			
			if($qr_types->get("sub_type_left_id")){
				$vs_left_table = $t_instance->getLeftTableName();
				$t_left_instance = $this->opo_dm->getInstanceByTableNum($vs_left_table);

				$vs_type_code = $t_left_instance->getTypeListCode();
				$va_item = $t_list->getItemFromListByItemID($vs_type_code, $qr_types->get("sub_type_left_id"));

				$vo_type->appendChild($this->opo_dom->createElement("subTypeLeft",$va_item["idno"]));
			}
			
			// restrictions (right side)
			
			if($qr_types->get("sub_type_right_id")){
				$vs_right_table = $t_instance->getRightTableName();
				$t_right_instance = $this->opo_dm->getInstanceByTableNum($vs_right_table);

				$vs_type_code = $t_right_instance->getTypeListCode();
				$va_item = $t_list->getItemFromListByItemID($vs_type_code, $qr_types->get("sub_type_right_id"));

				$vo_type->appendChild($this->opo_dom->createElement("subTypeRight",$va_item["idno"]));
			}
			
			// subtypes
			
			if($vo_subtypes = $this->getRelationshipTypesForParentAsDOM($qr_types->get("type_id"))){
				$vo_types->appendChild($vo_subtypes);
			}
			
			$vo_types->appendChild($vo_type);
		}
		
		return $vo_types;
	}
	# -------------------------------------------------------
	public function getRolesAsDOM(){
		$t_role = new ca_user_roles();
		
		$vo_roles = $this->opo_dom->createElement("roles");
		
		$qr_roles = $this->opo_db->query("SELECT * FROM ca_user_roles");
		
		while($qr_roles->nextRow()){
			$t_role->load($qr_roles->get("role_id"));
			
			$vo_role = $this->opo_dom->createElement("role");
			$vo_role->setAttribute("code", $this->makeIDNO($t_role->get("code")));
			
			$vo_role->appendChild($this->opo_dom->createElement("name", $t_role->get("name")));
			$vo_role->appendChild($this->opo_dom->createElement("description", $t_role->get("description")));
			
			if(is_array($va_actions = $t_role->getRoleActions())){
				$vo_actions = $this->opo_dom->createElement("actions");
				foreach($va_actions as $vs_action){
					$vo_actions->appendChild($this->opo_dom->createElement("action", $vs_action));
				}
				$vo_role->appendChild($vo_actions);
			}
			
			$vo_roles->appendChild($vo_role);
		}
		
		return $vo_roles;
	}
	# -------------------------------------------------------
	public function getGroupsAsDOM(){
		$t_group = new ca_user_groups();
		
		$vo_groups = $this->opo_dom->createElement("groups");
		
		$qr_groups = $this->opo_db->query("SELECT * FROM ca_user_groups WHERE parent_id IS NOT NULL");
		
		while($qr_groups->nextRow()){
			$t_group->load($qr_groups->get("group_id"));
			
			$vo_group = $this->opo_dom->createElement("group");
			$vo_group->setAttribute("code", $this->makeIDNO($t_group->get("code")));
			
			$vo_group->appendChild($this->opo_dom->createElement("name", caEscapeForXML($t_group->get("name"))));
			$vo_group->appendChild($this->opo_dom->createElement("description", caEscapeForXML($t_group->get("description"))));
			
			if(is_array($va_roles = $t_group->getGroupRoles())){
				$vo_roles = $this->opo_dom->createElement("roles");
				foreach($va_roles as $va_role){
					$vo_roles->appendChild($this->opo_dom->createElement("role", $this->makeIDNO($va_role["code"])));
				}
				$vo_group->appendChild($vo_roles);
			}
			
			$vo_groups->appendChild($vo_group);
		}
		
		return $vo_groups;
	}
	# -------------------------------------------------------
	public function getSearchFormsAsDOM(){
		$vo_forms = $this->opo_dom->createElement("searchForms");
		
		$qr_forms = $this->opo_db->query("SELECT * FROM ca_search_forms");
		
		while($qr_forms->nextRow()){
			$t_form = new ca_search_forms($qr_forms->get("form_id"));
			
			$vo_form = $this->opo_dom->createElement("searchForm");
			$vo_form->setAttribute("code", $this->makeIDNO($qr_forms->get("form_code")));
			$vo_form->setAttribute("type", $this->opo_dm->getTableName($qr_forms->get("table_num")));
			$vo_form->setAttribute("system", $qr_forms->get("is_system"));
			
			$vo_labels = $this->opo_dom->createElement("labels");
			$qr_form_labels = $this->opo_db->query("SELECT * FROM ca_search_form_labels WHERE form_id=?",$qr_forms->get("form_id"));
			while($qr_form_labels->nextRow()) {
				$vo_label = $this->opo_dom->createElement("label");

				$vo_label->setAttribute("locale", $this->opt_locale->localeIDToCode($qr_form_labels->get("locale_id")));
				$vo_label->appendChild($this->opo_dom->createElement("name",caEscapeForXML($qr_form_labels->get("name"))));

				$vo_labels->appendChild($vo_label);
			}

			$vo_form->appendChild($vo_labels);
			
			if(is_array($t_form->getSettings())){
				$vo_settings = $this->opo_dom->createElement("settings");
				foreach($t_form->getSettings() as $vs_setting => $va_value){
					if(is_array($va_value)){
						foreach($va_value as $vs_value){
							if(!is_array($vs_value)){ // ignore legacy search form settings which usually have nested arrays
								$vo_setting = $this->opo_dom->createElement("setting",$vs_value);
								$vo_setting->setAttribute("name", $vs_setting);
								$vo_settings->appendChild($vo_setting);
							}
						}
					} else {
						$vo_setting = $this->opo_dom->createElement("setting",$va_value);
						$vo_setting->setAttribute("name", $vs_setting);
						$vo_settings->appendChild($vo_setting);			
					}
				}

				$vo_form->appendChild($vo_settings);
			}
			
			$vo_placements = $this->opo_dom->createElement("bundlePlacements");
			$qr_placements = $this->opo_db->query("SELECT * FROM ca_search_form_placements WHERE form_id=? ORDER BY placement_id",$qr_forms->get("form_id"));
			while($qr_placements->nextRow()){
				$vo_placement = $this->opo_dom->createElement("placement");
				$vo_placement->setAttribute("code", "p".$qr_placements->get('placement_id'));

				$vo_placements->appendChild($vo_placement);
				$vo_placement->appendChild($this->opo_dom->createElement("bundle",caEscapeForXML($qr_placements->get("bundle_name"))));

				$t_placement = new ca_search_form_placements($qr_placements->get("placement_id"));

				if(is_array($t_placement->getSettings())){
					$vo_settings = $this->opo_dom->createElement("settings");

					foreach($t_placement->getSettings() as $vs_setting => $va_values){
						if(is_array($va_values)){
							foreach($va_values as $vs_key => $vs_value){
								$vo_setting = $this->opo_dom->createElement("setting",$vs_value);
								$vo_setting->setAttribute("name", $vs_setting);
								if($vs_setting=="label" || $vs_setting=="add_label"){
									$vo_setting->setAttribute("locale", $vs_key);
								}
								$vo_settings->appendChild($vo_setting);
							}
						} else {
							$vo_setting = $this->opo_dom->createElement("setting",$va_values);
							$vo_setting->setAttribute("name", $vs_setting);
							$vo_settings->appendChild($vo_setting);
						}
					}

					$vo_placement->appendChild($vo_settings);
				}

			}

			$vo_form->appendChild($vo_placements);
			
			$vo_forms->appendChild($vo_form);
		}
		
		return $vo_forms;
	}
	# -------------------------------------------------------
	public function getDisplaysAsXML($pa_options=null) {
		$t_display = new ca_bundle_displays();
		$o_dm = Datamodel::load();
		$this->opt_locale = new ca_locales();
		
		$va_displays = $t_display->getBundleDisplays();
		
		$vs_buf = "<displays>\n";
		foreach($va_displays as $vn_i => $va_display_by_locale) {
			$va_locales = array_keys($va_display_by_locale);
			$va_info = $va_display_by_locale[$va_locales[0]];
			
			if (!$t_display->load($va_info['display_id'])) { continue; }
			
			$vs_buf .= "\t<display code='".($va_info['display_code'] && preg_match('!^[A-Za-z0-9_]+$!', $va_info['display_code']) ? $va_info['display_code'] : 'display_'.$va_info['display_id'])."' type='".$o_dm->getTableName($va_info['table_num'])."' system='".$t_display->get('is_system')."'>\n";
			$vs_buf .= "\t\t<labels>\n";
			foreach($va_display_by_locale as $vn_locale_id => $va_display_info) {
				if(strlen($this->opt_locale->localeIDToCode($vn_locale_id))>0){
					$vs_buf .= "\t\t\t<label locale='".$this->opt_locale->localeIDToCode($vn_locale_id)."'><name>".caEscapeForXML($va_display_info['name'])."</name></label>\n";
				}
			}
			$vs_buf .= "\t\t</labels>\n";
			
			
			$va_placements = $t_display->getPlacements();
			//print_R(($va_placements));
			
			$vs_buf .= "<bundlePlacements>\n";
			foreach($va_placements as $vn_placement_id => $va_placement_info) {
				$vs_buf .= "\t\t<placement code='".preg_replace("![^A-Za-z0-9_]+!", "_", $va_placement_info['bundle_name'])."'><bundle>".$va_placement_info['bundle_name']."</bundle>\n";
				$va_settings = caUnserializeForDatabase($va_placement_info['settings']);
				if(is_array($va_settings)) { 
					$vs_buf .= "<settings>\n";
					foreach($va_settings as $vs_setting => $vm_value) {
						switch($vs_setting) {
							case 'label':
								//restrict_to_relationship_types
								if(is_array($vm_value)) {
									foreach($vm_value as $vn_locale_id => $vm_locale_specific_value) {
										$vs_buf .= "<setting name='label' locale='".$this->opt_locale->localeIDToCode($vn_locale_id)."'>".caEscapeForXML($vm_locale_specific_value)."</setting>\n";
									}
								}
							
								break;
							default:
								if (is_array($vm_value)) {
									foreach($vm_value as $vn_i => $vn_val) {
										$vs_buf .= "\t\t\t\t<setting name='{$vs_setting}'>".caEscapeForXML($vn_val)."</setting>\n";
									}
								} else {
									$vs_buf .= "\t\t\t\t<setting name='{$vs_setting}'>".caEscapeForXML($vm_value)."</setting>\n";
								}
								break;
						}
					}
					$vs_buf .= "</settings>\n";
				}
				$vs_buf .= "\t\t</placement>\n";
			}
			$vs_buf .= "</bundlePlacements>\n";
			
			$vs_buf .= "\t</display>\n";
		}
		$vs_buf .= "</displays>\n";
		
		//print_R($va_displays);
		
		return $vs_buf;
	}
	# -------------------------------------------------------
	// Utilities
	# -------------------------------------------------------
	private function makeIDNO($ps_idno){
		if(strlen($ps_idno)>0){
			return substr(preg_replace("/[^_a-zA-Z0-9]/","_",$ps_idno),0,30);
		} else {
			return "default";
		}
	}
	# -------------------------------------------------------
}
?>
