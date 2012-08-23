<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/TourService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_LIB_DIR__."/ca/LabelableBaseModelWithAttributes.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_tours.php");
require_once(__CA_MODELS_DIR__."/ca_tour_stops.php");

class TourService extends BaseService {
	# -------------------------------------------------------
	protected $opo_dm;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
		$this->opo_dm = Datamodel::load();
	}
	# -------------------------------------------------------
	/**
	 * Unified get function
	 *
	 * @param string $type [ca_tours, ca_tour_stops]
	 * @param int $item_id primary key
	 * @param array $bundles list of bundles to get
	 * For example:
	 * array("ca_objects.status","ca_entities.preferred_labels.displayname")
	 * @param array $options associative array of option arrays to pass through options to BaseModel::get() for each bundle.
	 * For example (corresponding to the example above):
	 * array(
	 *	"ca_objects.status" = array( "convertCodesToDisplayText" => true ),
	 *	"ca_entities.preferred_labels.displayname" = array( "delimiter" => ", " )
	 * )
	 * Possible options (keys) for each bundle are
	 * -BINARY: return field value as is
	 * -FILTER_HTML_SPECIAL_CHARS: convert all applicable chars to their html entities
	 * -DONT_PROCESS_GLOSSARY_TAGS: ?
	 * -CONVERT_HTML_BREAKS: similar to nl2br()
	 * -convertLineBreaks: same as CONVERT_HTML_BREAKS
	 * -GET_DIRECT_DATE: return raw date value from database if $ps_field adresses a date field, otherwise the value will be parsed using the TimeExpressionParser::getText() method
	 * -GET_DIRECT_TIME: return raw time value from database if $ps_field adresses a time field, otherwise the value will be parsed using the TimeExpressionParser::getText() method
	 * -TIMECODE_FORMAT: set return format for fields representing time ranges possible (string) values: COLON_DELIMITED, HOURS_MINUTES_SECONDS, RAW; data will be passed through floatval() by default
	 * -QUOTE: set return value into quotes
	 * -URL_ENCODE: value will be passed through urlencode()
	 * -ESCAPE_FOR_XML: convert <, >, &, ' and " characters for XML use
	 * -DONT_STRIP_SLASHES: if set to true, return value will not be passed through stripslashes()
	 * -template: formatting string to use for returned value; ^<fieldname> placeholder is used to represent field value in template
	 * -returnAsArray: if true, fields that can return multiple values [currently only <table_name>.children.<field>] will return values in an indexed array; default is false
	 * -returnAllLocales:
	 * -delimiter: if set, value is used as delimiter when fields that can return multiple fields are returned as strings; default is a single space
	 * -convertCodesToDisplayText: if set, id values refering to foreign keys are returned as preferred label text in the current locale
	 * @return array associative array of bundle contents
	 */
	public function get($type,$item_id,$bundles,$options){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id,true))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if(!is_array($bundles) && is_string($bundles)){
			return $t_subject_instance->get($bundles,$options); // hope this is safe
		} else {
			$va_return = array();
			foreach($bundles as $vs_bundle){
				if($this->_isBadBundle($vs_bundle)){
					continue;
				}
				if(isset($options[$vs_bundle])){
					$va_return[$vs_bundle] = $t_subject_instance->get($vs_bundle,$options[$vs_bundle]);
				} else {
					$va_return[$vs_bundle] = $t_subject_instance->get($vs_bundle);
				}
			}
			return $va_return;
		}
	}
	# -------------------------------------------------------
	/**
	 * Filter fields which should not be available for every service user
	 * @param string $ps_bundle field name
	 * @return boolean true if bundle should not be returned to user
	 */
	private function _isBadBundle($ps_bundle){
		if(stripos($ps_bundle, "ca_users")!==false){
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get data for specified item
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @return array associative array (field_name => field_value)
	 * @throws SoapFault
	 */
	public function getItem($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		return $t_subject_instance->getValuesForExport();
	}
	# -------------------------------------------------------
	/**
	 * Returns list of metdata element codes applicable to the specified type (type_id)
	 * @param string $type can be one of: "ca_tours", "ca_tour_stops"]
	 * @param int $type_id type identifier
	 * @param boolean $include_sub_element_codes determines whether to return only top-level element codes or not
	 * @return array list of applicable element codes
	 * @throws SoapFault
	 */
	public function getApplicableElementCodes($type, $type_id, $include_sub_element_codes){
		if(!($t_subject_instance = $this->getTableInstance($type,null,true))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		return $t_subject_instance->getApplicableElementCodes($type_id,$include_sub_element_codes);
	}
	# -------------------------------------------------------
	/**
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @return array
	 * @throws SoapFault
	 */
	public function getAttributes($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id,true))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		$va_attrs = $t_subject_instance->getAttributes();
		$t_element = new ca_metadata_elements();
		$va_return = array();
		$va_element_type_cfg = ca_metadata_elements::getAttributeTypes();
		foreach($va_attrs as $vo_attr){
			$va_attr = array();
			foreach($vo_attr->getValues() as $vo_value){
				$t_element->load($vo_value->getElementID());
				$va_attr[] = array(
					"value_id" => $vo_value->getValueID(),
					"display_value" => $vo_value->getDisplayValue(),
					"element_code" => $vo_value->getElementCode(),
					"element_id" => $vo_value->getElementID(),
					"attribute_info" => $t_subject_instance->getAttributeLabelAndDescription($vo_value->getElementCode()),
					"datatype" => $va_element_type_cfg[$t_element->get("datatype")]				);
			}
			$va_return[] = $va_attr;
		}
		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @param string $attribute_code_or_id
	 * @return array
	 * @throws SoapFault
	 */
	public function getAttributesByElement($type, $item_id, $attribute_code_or_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id,true))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		$t_locale = new ca_locales();

		$va_attrs = $t_subject_instance->getAttributesByElement($attribute_code_or_id);
		$va_return = array();
		foreach($va_attrs as $vo_attr){
			$va_attr = array();
			foreach($vo_attr->getValues() as $vo_value){
				$va_attr[] = array(
					"element_id" => $vo_value->getElementID(),
					"value_id" => $vo_value->getValueID(),
					"display_value" => $vo_value->getDisplayValue(),
					"locale" => $t_locale->localeIDToCode($vo_attr->getLocaleID()),
				);
			}
			$va_return[] = $va_attr;
		}
		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * Returns text of attributes in the user's currently selected locale, or else falls back to whatever locale is available
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @param string $attribute_code_or_id
	 * @param string $template
	 * @param array $options Supported options:
	 *	delimiter = text to use between attribute values; default is a single space
	 *	convertLinkBreaks = if true will convert line breaks to HTML <br/> tags for display in a web browser; default is false
	 * @return string
	 */
	public function getAttributesForDisplay($type, $item_id, $attribute_code_or_id, $template=null, $options=null){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id,true))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		return $t_subject_instance->getAttributesForDisplay($attribute_code_or_id, $ps_template=null, $pa_options=null);
	}
	# -------------------------------------------------------
	/**
	 * Get all labels attached to specified item
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @param string $mode can be either preferred, nonpreferred or all
	 * @return array
	 * @throws SoapFault
	 */
	public function getLabels($type, $item_id, $mode){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			if(!($vn_mode = $this->translateLabelMode($mode))){
				throw new SoapFault("Server", "Mode was invalid");
			}
			return $t_subject_instance->getLabels(null, $vn_mode);
		} else {
			throw new SoapFault("Server", "This item can't take labels");
		}
	}
	# -------------------------------------------------------
	/**
	 * Get label for display
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @return string
	 * @throws SoapFault
	 */
	public function getLabelForDisplay($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			return $t_subject_instance->getLabelForDisplay();
		} else {
			throw new SoapFault("Server", "This item can't take labels");
		}
	}
	# -------------------------------------------------------

	# -------------------------------------------------------
	/**
	 * Get all relationships between specified items and items of related_type
	 *
	 * @param string $type can be one of: ["ca_tours", "ca_tour_stops"]
	 * @param int $item_id primary key
	 * @param string $related_type can be one of: ["ca_objects", "ca_entities", "ca_places", "ca_occurrences", "ca_collections", "ca_list_items", "ca_object_representations", "ca_storage_locations", "ca_movements", "ca_loans", "ca_tours", "ca_tour_stops"]
	 * @param array options Supported options:
 	 * 		restrict_to_type = restricts returned items to those of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		restrictToType = synonym for restrict_to_type
 	 *		restrict_to_types = restricts returned items to those of the specified types; pass an array of list item_codes or item_ids
 	 *		restrictToTypes = synonym for restrict_to_types
 	 *		dont_include_subtypes_in_type_restriction = if set subtypes are not included when enforcing restrict_to_types. Note that restrict_to_relationship_types always includes subtypes in its restriction.
 	 *		dontIncludeSubtypesInTypeRestriction = synonym for dont_include_subtypes_in_type_restriction
 	 *		restrict_to_relationship_types = restricts returned items to those related to the current row by the specified relationship type(s). You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		restrictToRelationshipTypes = synonym for restrict_to_relationship_types
 	 *
 	 *		exclude_relationship_types = omits any items related to the current row with any of the specified types from the returned set of its. You can pass either an array of types or a single type. The types can be relationship type_code's or type_id's.
 	 *		excludeRelationshipTypes = synonym for exclude_relationship_types
 	 * 		exclude_type = excludes returned items of the specified type; only supports a single type which can be specified as a list item_code or item_id
 	 *		excludeType = synonym for exclude_type
 	 *		exclude_types = omits any items related to the current row that are of any of the specified types from the returned set of ids. You can pass either an array of types or a single type. The types can be type_code's or type_id's.
 	 *		excludeTypes = synonym for exclude_types
 	 *
 	 *		fields - array of fields (in table.fieldname format) to include in returned data
 	 *		return_non_preferred_labels - if set to true, non-preferred labels are included in returned data
 	 *		checkAccess - array of access values to filter results by; if defined only items with the specified access code(s) are returned
 	 *		returnLabelsAsArray - if set to true then all labels associated with row are returned in an array, otherwise only a text value in the current locale is returned; default is false - return single label in current locale
 	 * 		row_ids - array of primary key values to use when fetching related items; if omitted or set to a null value the 'row_id' option (single value) will be used; if row_id is also not set then the currently loaded primary key value will be used
 	 *		row_id - primary key value to use when fetching related items; if omitted or set to a false value (eg. null, false, 0) then the currently loaded primary key value is used [default]
 	 *		limit - number of items to limit return set to; default is 1000
 	 *		sort = optional array of bundles to sort returned values on. Currently only supported when getting related values via simple related <table_name> and <table_name>.related invokations. Eg. from a ca_objects results you can use the 'sort' option got get('ca_entities'), get('ca_entities.related') or get('ca_objects.related'). The bundle specifiers are fields with or without tablename. Only those fields returned for the related tables (intrinsics, label fields and attributes) are sortable.
	 *
	 * @return array
	 * @throws SoapFault
	 */
	public function getRelationships($type, $item_id, $related_type, $options = array()){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if(method_exists($t_subject_instance,"getRelatedItems")){
			return $t_subject_instance->getRelatedItems($related_type, $options);
		} else {
			throw new SoapFault("Server", "Invalid type");
		}
	}
	# -------------------------------------------------------
	/**
	 * Get valid relationship types between specified item types
	 *
	 * @param string $type
	 * @param int $sub_type_id
	 * @param string $related_type
	 * @param int $related_sub_type_id
	 * @return array
	 * @throws SoapFault
	 */
	public function getRelationshipTypes($type, $sub_type_id, $related_type, $related_sub_type_id){
		if(!($this->getTableInstance($type))){
			throw new SoapFault("Server", "Invalid type");
		}
		if(!($this->getTableInstance($related_type))){
			throw new SoapFault("Server", "Invalid related type");
		}
		$vs_rel_table = $this->getRelTableName($type, $related_type);
		$t_rel_type = new ca_relationship_types();
		require_once(__CA_MODELS_DIR__."/{$vs_rel_table}.php");
		$t_rel_table = new $vs_rel_table();

		if($t_rel_table->getLeftTableName() == $type){
			$vb_type_is_left = true;
		} else if($t_rel_table->getRightTableName() == $type){
			$vb_type_is_left = false;
		}

		$va_return = array();

		foreach($t_rel_type->getRelationshipInfo($vs_rel_table) as $va_rel){
			$vb_append = true;
			if($vb_type_is_left && is_int($sub_type_id) && $sub_type_id > 0){
				if($va_rel["sub_type_left_id"]!=$sub_type_id){
					$vb_append = false;
				}
			}
			if(!$vb_type_is_left && is_int($sub_type_id) && $sub_type_id > 0){
				if($va_rel["sub_type_right_id"]!=$sub_type_id){
					$vb_append = false;
				}
			}
			if($vb_type_is_left && is_int($related_sub_type_id) && $related_sub_type_id > 0){
				if($va_rel["sub_type_right_id"]!=$related_sub_type_id){
					$vb_append = false;
				}
			}
			if(!$vb_type_is_left && is_int($related_sub_type_id) && $related_sub_type_id > 0){
				if($va_rel["sub_type_left_id"]!=$related_sub_type_id){
					$vb_append = false;
				}
			}
			if($vb_append){
				$va_return[] = $va_rel;
			}
		}

		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * Gets list of all tours
	 *
	 * @return array
	 * @throws SoapFault
	 */
	public function getTours(){
		$t_tour = new ca_tours();
		return caExtractValuesByUserLocale($t_tour->getListOfTours());
	}
	# -------------------------------------------------------
	/**
	 * Gets info for specified tour
	 *
	 * @param int $tour_id
	 * @return array
	 * @throws SoapFault
	 */
	public function getTour($tour_id){
		$t_tour = new ca_tours();
		if(!$t_tour->load($tour_id)){
			throw new SoapFault("Server", "Invalid tour_id");
		}
		return $t_tour->getFieldValuesArray();
	}
	# -------------------------------------------------------
	/**
	 * Returns list of items in specified set
	 *
	 * @param int $set_id
	 * @param array $pa_options Optional array of options. Supported options are:
	 *	thumbnailVersions = A list of of a media versions to return with each item. Only used if the set content type is ca_objects.
	 *	thumbnailVersion = Same as 'thumbnailVersions' except it is a single value. (Maintained for compatibility with older code.)
	 *	limit = Limits the total number of records to be returned
	 *	checkAccess = An array of row-level access values to check set members for, often produced by the caGetUserAccessValues() helper. Set members with access values not in the list will be omitted. If this option is not set or left null no access checking is done.
	 *	returnRowIdsOnly = If true a simple array of row_ids (keys of the set members) for members of the set is returned rather than full item-level info for each set member.
	 *	returnItemIdsOnly = If true a simple array of item_ids (keys for the ca_set_items rows themselves) is returned rather than full item-level info for each set member.
	 *	returnItemAttributes = A list of attribute element codes for the ca_set_item record to return values for.
	 * @return array
	 * @throws SoapFault
	 */
	public function getTourStops($tour_id,$options){
		$t_tour = new ca_tours();
		if(!$t_tour->load($tour_id)){
			throw new SoapFault("Server", "Invalid tour_id");
		}
		return $t_tour->getStops();
	}
	# -------------------------------------------------------
	/**
	 * Returns the names of the datatypes for a given list of metadata elements
	 * 
	 * @param array $element_codes List of codes of the elements in question
	 * @return array Names of the datatypes, e.g. "List", "Text", etc.
	 */
	public function getElementTypesForCodes($element_codes){
		$vo_db = new Db();
		if(!is_array($element_codes)){
			throw new SoapFault("Server", "Invalid argument");
		}
		$va_return = array();
		foreach($element_codes as $vs_element_code){
			$qr_elements = $vo_db->query("SELECT datatype FROM ca_metadata_elements WHERE element_code=?",$vs_element_code);
			while($qr_elements->nextRow()){
				$va_return[$vs_element_code] = ca_metadata_elements::getAttributeNameForTypeCode(intval($qr_elements->get("datatype")));
			}
		}
		return $va_return;
	}
	# -------------------------------------------------------
	/**
	 * Get a list of available languages
	 *
	 * @param array $pa_options List of options. Possible keys:
	 *   - sort_field: field to sort on
	 *   - sort_direction: direction to sort on
	 *   - index_by_code: use code as array keys
	 *   - return_display_values: add language display value to result
	 *   - available_for_cataloguing_only: only return languages that are used for cataloguing
	 * @return array List of available language
	 */
	public function getLocaleList($pa_options = null){
		return ca_locales::getLocaleList($pa_options);
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getTableInstance($ps_type, $pn_type_id_to_load=null,$pb_check_bm_with_attributes=false){
		if(!in_array($ps_type, array("ca_tours", "ca_tour_stops"))){
			throw new SoapFault("Server", "Invalid type or item_id");
		} else {
			require_once(__CA_MODELS_DIR__."/{$ps_type}.php");
			$t_instance = new $ps_type();
			if($pn_type_id_to_load){
				if(!$t_instance->load($pn_type_id_to_load)){
					if(!$t_instance->load(array("idno" => $pn_type_id_to_load))){
						return false;
					}
				} 
				if($pb_check_bm_with_attributes){
					if($t_instance instanceof BaseModelWithAttributes){
						return $t_instance;
					} else {
						return false;
					}
				} else {
					return $t_instance;
				}
			} else {
				if($pb_check_bm_with_attributes){
					if($t_instance instanceof BaseModelWithAttributes){
						return $t_instance;
					} else {
						return false;
					}
				} else {
					return $t_instance;
				}
			}
		}
	}
	# -------------------------------------------------------
	private function getRelTableName($ps_left_table,$ps_right_table){
		$va_relationships = $this->opo_dm->getPath($ps_left_table, $ps_right_table);
		unset($va_relationships[$ps_left_table]);
		unset($va_relationships[$ps_right_table]);
		if(sizeof($va_relationships)==1){
			foreach($va_relationships as $vs_table_name => $vs_table_num){
				return $vs_table_name;
			}
		} else {
			throw new SoapFault("Server", "There is no applicable path from {$ps_left_table} to {$ps_right_table}");
		}
	}
	# -------------------------------------------------------
	private function translateLabelMode($ps_mode){
		switch($ps_mode){
			case "preferred":
				return __CA_LABEL_TYPE_PREFERRED__;
			case "nonpreferred":
				return __CA_LABEL_TYPE_NONPREFERRED__;
			case "all":
				return __CA_LABEL_TYPE_ANY__;
			default:
				return false;
		}
	}
	# -------------------------------------------------------
}
