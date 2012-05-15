<?php
$_SERVER['HTTP_HOST'] = $argv[2];
if(!file_exists('./setup.php')) {
	die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
}

require_once("./setup.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");

if(!$argv[1] || !file_exists($argv[1])){
	die("Couldn't load profile.\n");
}

// parse profile

$vo_config = Configuration::load();
$vo_config->loadFile($argv[1]);


// start building xml

$vo_dom = new DOMDocument('1.0', 'utf-8');

$vo_root = $vo_dom->createElement('profile');

$vo_root->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
$vo_root->setAttribute("xsi:noNamespaceSchemaLocation","profile.xsd");

// "profile" attributes

if(!$vo_config->get('profile_use_for_configuration')){
	$vo_root->setAttribute("useForConfiguration", 0);
} else {
	$vo_root->setAttribute("useForConfiguration", 1);
}

if($vo_config->get('profile_base')){
	$vo_root->setAttribute("base", $vo_config->get('profile_base'));
}

if($vo_config->get('profile_info_url')){
	$vo_root->setAttribute("infoUrl", $vo_config->get('profile_info_url'));
}

// basic elements

$vo_root->appendChild($vo_dom->createElement("profileName",xmlentities($vo_config->get('profile_name'))));
$vo_root->appendChild($vo_dom->createElement("profileDescription",xmlentities($vo_config->get('profile_description'))));


// locales

$vo_locales = $vo_dom->createElement("locales");

foreach($vo_config->getAssoc("locales") as $vs_locale_code => $vs_label){
	$vo_locale = $vo_dom->createElement("locale",xmlentities($vs_label));
	$va_tmp = explode("_",$vs_locale_code);
	if($va_tmp[0]){
		$vo_locale->setAttribute("lang", $va_tmp[0]);
	}
	if($va_tmp[1]){
		$vo_locale->setAttribute("country", $va_tmp[1]);
	}

	$vo_locales->appendChild($vo_locale);
}

$vo_root->appendChild($vo_locales);

// lists

$vo_lists = $vo_dom->createElement("lists");

foreach($vo_config->getAssoc("lists") as $vs_list_code => $va_info){
	$vo_list = $vo_dom->createElement("list");
	$vo_list->setAttribute("code", $vs_list_code);
	if($va_info['is_hierarchical']) {
		$vo_list->setAttribute("hierarchical", $va_info['is_hierarchical']);
	} else {
		$vo_list->setAttribute("hierarchical", 0);
	}
	if($va_info['is_system_list']) {
		$vo_list->setAttribute("system", $va_info['is_system_list']);
	} else {
		$vo_list->setAttribute("system", 0);
	}
	if($va_info['use_as_vocabulary']) {
		$vo_list->setAttribute("vocabulary", $va_info['use_as_vocabulary']);
	} else {
		$vo_list->setAttribute("vocabulary", 0);
	}
	if($va_info['default_sort']) {
		$vo_list->setAttribute("defaultSort", $va_info['default_sort']);
	}

	$vo_labels = $vo_dom->createElement("labels");
	foreach($va_info['preferred_labels'] as $vs_locale => $va_label_info) {
		$vo_label = $vo_dom->createElement("label");

		$vo_label->setAttribute("locale", $vs_locale);
		$vo_label->appendChild($vo_dom->createElement("name",xmlentities($va_label_info['name'])));

		$vo_labels->appendChild($vo_label);
	}

	$vo_list->appendChild($vo_labels);

	$vo_items = processListItems($va_info['items'],$vo_dom);
	$vo_list->appendChild($vo_items);

	$vo_lists->appendChild($vo_list);
}

$vo_root->appendChild($vo_lists);

// metadata elements

$vo_element_sets = $vo_dom->createElement("elementSets");
if(is_array($vo_config->getAssoc("element_sets"))){ // there are profiles without element_sets configured, believe it or not!
	foreach($vo_config->getAssoc("element_sets") as $vs_element_code => $va_info){

		$vo_element = processMetadataElement($vs_element_code, $va_info, $vo_dom);
		$vo_element_sets->appendChild($vo_element);

		if(is_array($va_info["type_restrictions"])){
			$vo_type_restrictions = $vo_dom->createElement("typeRestrictions");
			$vo_element->appendChild($vo_type_restrictions);
			foreach($va_info["type_restrictions"] as $vs_restriction_code => $va_restriction_info){
				$vo_restriction = $vo_dom->createElement("restriction");
				$vo_type_restrictions->appendChild($vo_restriction);
				$vo_restriction->setAttribute("code", $vs_restriction_code);
				$vo_restriction->appendChild($vo_dom->createElement("table",$va_restriction_info["table"]));
				if(strlen(trim($va_restriction_info["type"]))>0){
					$vo_restriction->appendChild($vo_dom->createElement("type",$va_restriction_info["type"]));
				}
				if(is_array($va_restriction_info["settings"])){
					$vo_settings = $vo_dom->createElement("settings");
					foreach($va_restriction_info["settings"] as $vs_setting => $vs_value){
						$vo_setting = $vo_dom->createElement("setting",$vs_value);
						$vo_setting->setAttribute("name", $vs_setting);
						$vo_settings->appendChild($vo_setting);
					}
					$vo_restriction->appendChild($vo_settings);
				}
			}
		}
	}
}

$vo_root->appendChild($vo_element_sets);

// uis

$vo_uis = $vo_dom->createElement("userInterfaces");

foreach($vo_config->getAssoc("uis") as $vs_ui_code => $va_info){
	$vo_ui = $vo_dom->createElement("userInterface");
	$vo_ui->setAttribute("code", $vs_ui_code);
	$vo_uis->appendChild($vo_ui);

	$vo_ui->setAttribute("type", trim($va_info["type"]));

	$vo_labels = $vo_dom->createElement("labels");
	foreach($va_info['preferred_labels'] as $vs_locale => $va_label_info) {
		$vo_label = $vo_dom->createElement("label");

		$vo_label->setAttribute("locale", $vs_locale);
		$vo_label->appendChild($vo_dom->createElement("name",xmlentities($va_label_info['name'])));

		$vo_labels->appendChild($vo_label);
	}

	$vo_ui->appendChild($vo_labels);

	$vo_screens = $vo_dom->createElement("screens");

	foreach($va_info["screens"] as $vs_screen_code => $va_screen_info){
		$vo_screen = $vo_dom->createElement("screen");
		$vo_screens->appendChild($vo_screen);

		$vo_screen->setAttribute("idno", $vs_screen_code);
		$vo_screen->setAttribute("default", $va_screen_info["is_default"]);

		$vo_labels = $vo_dom->createElement("labels");
		foreach($va_screen_info['preferred_labels'] as $vs_locale => $va_label_info) {
			$vo_label = $vo_dom->createElement("label");

			$vo_label->setAttribute("locale", $vs_locale);
			$vo_label->appendChild($vo_dom->createElement("name",xmlentities($va_label_info['name'])));

			$vo_labels->appendChild($vo_label);
		}

		$vo_screen->appendChild($vo_labels);

		if(is_array($va_screen_info["settings"])){
			foreach($va_screen_info['settings'] as $vs_setting => $vs_value) {
				if(trim($vs_setting) != "" && trim($vs_value) != ""){
					$vo_screen->setAttribute($vs_setting, $vs_value);
				}
			}
		}

		if(is_array($va_screen_info["type_restrictions"])){
			$vo_restrictions = $vo_dom->createElement("typeRestrictions");

			foreach($va_screen_info["type_restrictions"] as $vs_restriction_code => $va_restriction_info){
				$vo_restriction = $vo_dom->createElement("restriction");
				$vo_restrictions->appendChild($vo_restriction);
				$vo_restriction->setAttribute("code", $vs_restriction_code);
				$vo_restriction->setAttribute("type", $va_restriction_info["type"]);
				if(is_array($va_restriction_info["settings"])){
					foreach($va_restriction_info['settings'] as $vs_setting => $vs_value) {
						if(trim($vs_setting) != "" && trim($vs_value) != ""){
							$vo_restriction->setAttribute($vs_setting, $vs_value);
						}
					}
				}
			}

			$vo_screen->appendChild($vo_restrictions);
		}

		$vo_bundle_placements = $vo_dom->createElement("bundlePlacements");

		foreach($va_screen_info["bundles"] as $vs_placement_code => $va_placement_info){
			$vo_placement = $vo_dom->createElement("placement");
			$vo_bundle_placements->appendChild($vo_placement);
			$vo_placement->setAttribute("code", $vs_placement_code);
			$vo_placement->appendChild($vo_dom->createElement("bundle",$va_placement_info["bundle"]));

			if(sizeof($va_placement_info)>1){
				$vo_settings = $vo_dom->createElement("settings");
				foreach($va_placement_info as $vs_setting_name => $vm_setting_val){
					if($vs_setting_name == "bundle") continue;
					if(is_array($vm_setting_val)){
						foreach($vm_setting_val as $vs_key => $vs_val){ 
							$vo_setting = $vo_dom->createElement("setting",xmlentities($vs_val));
							$vo_setting->setAttribute("name", $vs_setting_name);
							$vo_setting->setAttribute("locale",$vs_key);
							$vo_settings->appendChild($vo_setting);
						}
					} else {
						$vo_setting = $vo_dom->createElement("setting",$vm_setting_val);
						$vo_setting->setAttribute("name", $vs_setting_name);
						$vo_settings->appendChild($vo_setting);
					}
				}
				$vo_placement->appendChild($vo_settings);
			}
		}

		$vo_screen->appendChild($vo_bundle_placements);
	}

	$vo_ui->appendChild($vo_screens);
	
}

$vo_root->appendChild($vo_uis);

// relationship types

$vo_rel_types = $vo_dom->createElement("relationshipTypes");

foreach($vo_config->getAssoc("relationship_types") as $vs_rel_table => $va_info){
	$vo_rel_table = $vo_dom->createElement("relationshipTable");
	$vo_rel_table->setAttribute("name", $vs_rel_table);
	$vo_rel_types->appendChild($vo_rel_table);
	if(is_array($va_info["types"])){
		$vo_types = $vo_dom->createElement("types");
		$vo_rel_table->appendChild($vo_types);
		foreach($va_info["types"] as $vs_type_code => $va_type_info){
			$vo_type = $vo_dom->createElement("type");
			$vo_types->appendChild($vo_type);
			$vo_type->setAttribute("code", $vs_type_code);
			$vo_type->setAttribute("default", isset($va_type_info["is_default"]) ? $va_type_info["is_default"] : 0);
			
			$vo_labels = $vo_dom->createElement("labels");
			foreach($va_type_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$vo_label = $vo_dom->createElement("label");

				$vo_label->setAttribute("locale", $vs_locale);
				$vo_label->appendChild($vo_dom->createElement("typename",xmlentities($va_label_info['typename'])));
				$vo_label->appendChild($vo_dom->createElement("typename_reverse",xmlentities($va_label_info['typename_reverse'])));

				$vo_labels->appendChild($vo_label);
			}

			$vo_type->appendChild($vo_labels);

			$vo_type->appendChild($vo_dom->createElement("subTypeLeft",$va_type_info["subtype_left"]));
			$vo_type->appendChild($vo_dom->createElement("subTypeRight",$va_type_info["subtype_right"]));
		}
	}
}

$vo_root->appendChild($vo_rel_types);

// roles

if(is_array($va_roles = $vo_config->getAssoc("roles"))){
	$vo_roles = $vo_dom->createElement("roles");
	foreach($va_roles as $vs_role_code => $va_role_info){
		$vo_role = $vo_dom->createElement("role");
		$vo_role->setAttribute("code", $vs_role_code);
		$vo_roles->appendChild($vo_role);
		$vo_role->appendChild($vo_dom->createElement("name",xmlentities($va_role_info["name"])));
		$vo_role->appendChild($vo_dom->createElement("description",xmlentities($va_role_info["description"])));
		if(is_array($va_role_info["actions"])){
			$vo_actions = $vo_dom->createElement("actions");
			foreach($va_role_info["actions"] as $vs_action){
				$vo_actions->appendChild($vo_dom->createElement("action",trim($vs_action)));
			}
			$vo_role->appendChild($vo_actions);
		}
	}
	$vo_root->appendChild($vo_roles);
}

// roles

if(is_array($va_groups = $vo_config->getAssoc("groups"))){
	$vo_groups = $vo_dom->createElement("groups");
	foreach($va_groups as $vs_group_code => $va_group_info){
		$vo_group = $vo_dom->createElement("group");
		$vo_group->setAttribute("code", $vs_group_code);
		$vo_groups->appendChild($vo_group);
		$vo_group->appendChild($vo_dom->createElement("name",xmlentities($va_group_info["name"])));
		$vo_group->appendChild($vo_dom->createElement("description",xmlentities($va_group_info["description"])));
		if(is_array($va_group_info["roles"])){
			$vo_roles = $vo_dom->createElement("roles");
			foreach($va_group_info["roles"] as $vs_role){
				$vo_roles->appendChild($vo_dom->createElement("role",trim($vs_role)));
			}
			$vo_group->appendChild($vo_roles);
		}
	}
	$vo_root->appendChild($vo_groups);
}

// generate XML output

$vo_dom->appendChild($vo_root);
$vo_dom->formatOutput = true;
print $vo_dom->saveXML();


/* helper functions */

function processListItems($pa_items,$po_dom){
	$vo_items = $po_dom->createElement("items");

	foreach($pa_items as $vs_item_code => $va_item_info) {
		$vo_item = $po_dom->createElement("item");
		$vo_item->setAttribute("idno", $vs_item_code);
		if (isset($va_item_info['status'])) {
			$vo_item->setAttribute("status", $va_item_info['status']);
		}
		if (isset($va_item_info['access'])) {
			$vo_item->setAttribute("access", $va_item_info['access']);
		}
		if (isset($va_item_info['rank'])) {
			$vo_item->setAttribute("rank", $va_item_info['rank']);
		}
		if (isset($va_item_info['is_enabled'])) {
			$vo_item->setAttribute("enabled", $va_item_info['is_enabled']);
		} else {
			$vo_item->setAttribute("enabled", 0);
		}
		if (isset($va_item_info['is_default'])) {
			$vo_item->setAttribute("default", $va_item_info['is_default']);
		} else {
			$vo_item->setAttribute("default", 0);
		}
		if (isset($va_item_info['item_value'])) {
			$vo_item->setAttribute("value", $va_item_info['item_value']);
		}
		if (isset($va_item_info['type'])) {
			$vo_item->setAttribute("type", $va_item_info['type']);
		}

		$vo_labels = $po_dom->createElement("labels");

		if(is_array($va_item_info['preferred_labels'])){
			foreach($va_item_info['preferred_labels'] as $vs_locale => $va_label_info) {
				$vo_label = $po_dom->createElement("label");
				$vo_label->setAttribute("locale", $vs_locale);
				$vo_label->setAttribute("preferred", 1);

				$vo_label->appendChild($po_dom->createElement("name_singular",xmlentities($va_label_info['name_singular'])));
				$vo_label->appendChild($po_dom->createElement("name_plural",xmlentities($va_label_info['name_plural'])));

				$vo_labels->appendChild($vo_label);
			}
		}

		$vo_item->appendChild($vo_labels);
		if (is_array($va_item_info['items'])) {
			$vo_childs = processListItems($va_item_info['items'], $po_dom);
			$vo_item->appendChild($vo_childs);
		}

		$vo_items->appendChild($vo_item);
	}

	return $vo_items;
}

function processMetadataElement($ps_code,$pa_info,$po_dom){
	$vo_element = $po_dom->createElement("metadataElement");

	$vo_element->setAttribute("code", $ps_code);
	$vo_element->setAttribute("datatype", $pa_info["datatype"]);

	if(strlen(trim($pa_info["list"]))>0){
		$vo_element->setAttribute("list", trim($pa_info["list"]));
	}

	if(is_array($pa_info['preferred_labels'])) {
		$vo_labels = $po_dom->createElement("labels");
		foreach($pa_info['preferred_labels'] as $vs_locale => $va_label_info) {
			$vo_label = $po_dom->createElement("label");

			$vo_label->setAttribute("locale", $vs_locale);
			if($va_label_info['name']){
				$vo_label->appendChild($po_dom->createElement("name",xmlentities($va_label_info['name'])));
			}

			if($va_label_info['description']){
				$vo_label->appendChild($po_dom->createElement("description",xmlentities($va_label_info['description'])));
			}

			$vo_labels->appendChild($vo_label);
		}
		$vo_element->appendChild($vo_labels);
	}

	if(strlen(trim($pa_info["documentation_url"]))>0){
		$vo_element->appendChild($po_dom->createElement("documentationUrl",$pa_info["documentation_url"]));
	}

	if(is_array($pa_info["settings"])){
		$vo_settings = $po_dom->createElement("settings");
		foreach($pa_info['settings'] as $vs_setting => $vs_value) {
			$vo_setting = $po_dom->createElement("setting",$vs_value);
			$vo_setting->setAttribute("name", $vs_setting);
			$vo_settings->appendChild($vo_setting);
		}
		$vo_element->appendChild($vo_settings);
	}

	if(is_array($pa_info['elements'])) {
		$vo_elements = $po_dom->createElement("elements");
		foreach($pa_info['elements'] as $vs_subelement_code => $va_subelement_info) {
			$vo_subelement = processMetadataElement($vs_subelement_code, $va_subelement_info, $po_dom);
			$vo_elements->appendChild($vo_subelement);
		}
		$vo_element->appendChild($vo_elements);
	}

	return $vo_element;
}

function xmlentities($ps_string){
	$ps_string = str_replace(">", "&gt;", $ps_string);
	$ps_string = str_replace("<", "&lt;", $ps_string);
	$ps_string = str_replace("", "&quot;", $ps_string);
	return str_replace("&", "&amp;", $ps_string);
}

?>
