<?php
/** ---------------------------------------------------------------------
 * app/lib/Attributes/Values/FloorPlanAttributeValue.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 
  /**
  *
  */
  define("__CA_ATTRIBUTE_VALUE_FLOORPLAN__", 31);
  
 require_once(__CA_LIB_DIR__.'/Configuration.php');
 require_once(__CA_LIB_DIR__.'/Attributes/Values/IAttributeValue.php');
 require_once(__CA_LIB_DIR__.'/Attributes/Values/AttributeValue.php');
 require_once(__CA_LIB_DIR__.'/Configuration.php');
 require_once(__CA_LIB_DIR__.'/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants

 require_once(__CA_APP_DIR__.'/helpers/gisHelpers.php');

 global $_ca_attribute_settings;
 
 $_ca_attribute_settings['FloorPlanAttributeValue'] = array(		// global
	'fieldWidth' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 60,
		'width' => 5, 'height' => 1,
		'label' => _t('Width of data entry field in user interface'),
		'description' => _t('Width, in characters, of the field when displayed in a user interface.')
	),
	'fieldHeight' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'default' => 1,
		'width' => 5, 'height' => 1,
		'label' => _t('Height of data entry field in user interface'),
		'description' => _t('Height, in characters, of the field when displayed in a user interface.')
	),
	'doesNotTakeLocale' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Does not use locale setting'),
		'description' => _t('Check this option if you don\'t want your FloorPlan values to be locale-specific. (The default is to not be.)')
	),
	'canBeUsedInSort' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used for sorting'),
		'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is not to be.)')
	),
	'canBeEmpty' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be empty'),
		'description' => _t('Check this option if you want to allow empty attribute values. This - of course - only makes sense if you bundle several elements in a container.')
	),
	'canBeUsedInDisplay' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 1,
		'width' => 1, 'height' => 1,
		'label' => _t('Can be used in display'),
		'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
	)
);

class FloorPlanAttributeValue extends AttributeValue implements IAttributeValue {
	# ------------------------------------------------------------------
 	private $ops_text_value;
 	# ------------------------------------------------------------------
 	public function __construct($pa_value_array=null) {
 		parent::__construct($pa_value_array);
 	}
 	# ------------------------------------------------------------------
 	public function loadTypeSpecificValueFromRow($pa_value_array) {
 		$this->ops_text_value = $pa_value_array['value_blob'];
 	}
 	# ------------------------------------------------------------------
 	/**
 	 * @param array $pa_options Options are:
 	 *		forDuplication = returns full text + FloorPlan URL suitable for setting a duplicate attribute. Used in BaseModelWithAttributes::copyAttributesTo()
 	 * @return string FloorPlan value
 	 */
	public function getDisplayValue($pa_options=null) {
		if(isset($pa_options['coordinates']) && $pa_options['coordinates']) {
			if (preg_match("!\[([^\]]+)!", $this->ops_text_value, $va_matches)) {
				$va_tmp = explode(',', $va_matches[1]);
				if ((sizeof($va_tmp) == 2) && (is_numeric($va_tmp[0])) && (is_numeric($va_tmp[1]))) {
					return array('latitude' => trim($va_tmp[0]), 'longitude' => trim($va_tmp[1]), 'path' => trim($va_matches[1]), 'label' => $this->ops_text_value);
				} else {
					return array('latitude' => null, 'longitude' => null, 'path' => null, 'label' => $this->ops_text_value);
				}
			} else {
				return array('latitude' => null, 'longitude' => null, 'path' => null, 'label' => $this->ops_text_value);
			}
		}
		
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	public function getTextValue(){
		return $this->ops_text_value;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 		$ps_value = trim(preg_replace("![\t\n\r]+!", ' ', $ps_value));
 		
		$vo_conf = Configuration::load();
		$vs_user = trim($vo_conf->get("FloorPlan_user"));

		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('canBeEmpty'));
		if (!$ps_value) {
 			if(!$va_settings["canBeEmpty"]){
				$this->postError(1970, _t('Entry was blank.'), 'FloorPlanAttributeValue->parseValue()');
				return false;
			}
			return array();
 		} elseif (is_array($va_data = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', preg_replace("/[\\\\]{2}/", "\\", $ps_value))))) {
			return array(
				'value_blob' => $ps_value
			);
		}
		return false;
	}
	# ------------------------------------------------------------------
	/**
	 * Return HTML form element for editing.
	 *
	 * @param array $pa_element_info An array of information about the metadata element being edited
	 * @param array $pa_options array Options include:
	 *
	 * @return string
	 */
	public function htmlFormElement($pa_element_info, $pa_options=null) {
		$o_config = Configuration::load();
 		
 		if (!($po_request = caGetOption('request', $pa_options, null))) { return _t('Floor plan is not supported outside of a request context'); }
 		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');


 		$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight'));
		
 		if (!(isset($pa_options['t_subject']) && is_object($pa_options['t_subject']))) {
 			return _t('Floor plan is not supported');
 		}
 		
		$t_subject = $pa_options['t_subject'];
		
		if (!(
			((($t_instance = $t_subject->getLeftTableInstance()) && $t_instance->hasField('floorplan') && ($t_target = $t_subject->getRightTableInstance()))
			||
			(($t_instance = $t_subject->getRightTableInstance()) && $t_instance->hasField('floorplan') && ($t_target = $t_subject->getLeftTableInstance())))
		)) {
			return _t('Floor plan is not supported');
		}
		
		$va_viewer_opts = [
			'id' => 'caMediaOverlayTileViewer',
			'viewer_width' => caGetOption('viewer_width', $pa_data['display'], '100%'), 'viewer_height' => caGetOption('viewer_height', $pa_data['display'], '100%'),
			'viewer_base_url' => $po_request->getBaseUrlPath(),
			'annotation_load_url' => '#{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
			'annotation_save_url' => '#{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
			'progress_id' => 'caMediaOverlayProgress'
		];
		
		// HTML for tileviewer
		$o_view->setVar('viewer', $t_instance->getMediaTag('floorplan', 'tilepic', $va_viewer_opts));
		$o_view->setVar('target_name', $vs_target_name = $t_instance->get('preferred_labels'));
		
		$vs_element = "<div style='width: 850px;'><a href='#' class=\"{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_trigger\">".$t_instance->getMediaTag('floorplan', 'preview')."</a>";
		$vs_element .= caHTMLHiddenInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				['value' => '{{'.$pa_element_info['element_id'].'}}', 'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}']
 			);
		
		$vs_element .= "<textarea style=\"display: none;\" id=\"{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_viewer\">
		".$o_view->render('floorplan_viewer.php')."
		</textarea>";
		
		if (!is_array($va_floor_plan_annotations = json_decode($t_subject->get($pa_element_info['element_code'])))) { $va_floor_plan_annotations = []; }
		$vn_num_annotations = sizeof($va_floor_plan_annotations);
		$vs_element .= "<div style=\"float:right; width: 50%;\" id=\"{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_info\">
			<div>
				<h2>"._t("Floor plan for <em>%1</em>", $vs_target_name)."</h2>
			</div>
			<div id=\"{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_stats\">
			".(($vn_num_annotations == 1) ? _t('%1 annotation on this floor plan', $vn_num_annotations) : _t('%1 annotations on this floor plan', $vn_num_annotations))."
			</div>
			<div style='margin-top: 10px'>
				<a href='#' class=\"{fieldNamePrefix}".$pa_element_info['element_id']."_{n}_trigger form-button\"><span class=\"form-button\">".caNavIcon(__CA_NAV_ICON_EDIT__, 2, ['style' => 'margin-right: 5px;'])." "._t('Edit floor plan')."</span></a>
			</div>
		</div></div>\n";
		
		$vs_element .= "<script type='text/javascript'>
	jQuery(document).ready(function() {
		var {fieldNamePrefix}".$pa_element_info['element_id']."{n}Floorplan = caUI.initFloorplan({
			'baseID': '{fieldNamePrefix}".$pa_element_info['element_id']."_{n}', 
			'elementID': ".$pa_element_info['element_id'].",
			'singularMessage': '".addslashes(_t('%1 annotation on this floor plan'))."',
			'pluralMessage': '".addslashes(_t('%1 annotations on this floor plan'))."'
		});
	});
</script>
		"; 
 		return $vs_element;
 	}
 	# ------------------------------------------------------------------
 	public function getAvailableSettings($pa_element_info=null) {
 		global $_ca_attribute_settings;

 		return $_ca_attribute_settings['FloorPlanAttributeValue'];
 	}
 	# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return 'value_blob';
		}
 	# ------------------------------------------------------------------
		/**
		 * Returns constant for FloorPlan attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_FLOORPLAN__;
		}
 		# ------------------------------------------------------------------
}