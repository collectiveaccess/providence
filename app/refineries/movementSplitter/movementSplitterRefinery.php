<?php
/* ----------------------------------------------------------------------
 * movementSplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');

class movementSplitterRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'movementSplitter';
		$this->ops_title = _t('Movement splitter');
		$this->ops_description = _t('Provides several movement-related import functions: splitting of multiple movements in a string into individual values, mapping of type and relationship type for related movements, and merging movement data with movement names.');
		
		$this->opb_returns_multiple_values = true;
		$this->opb_supports_relationships = true;
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => true,
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
		return caGenericImportSplitter('movementSplitter', 'movement', 'ca_movements', $this, $pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options);
	}
	# -------------------------------------------------------	
	/**
	 * movementSplitter returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return $this->opb_returns_multiple_values;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['movementSplitter'] = array(		
	'movementSplitter_delimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Delimiter'),
		'description' => _t('Sets the value of the delimiter to break on, separating data source values')
	),
	'movementSplitter_matchOn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Match on'),
		'description' => _t('List indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails')
	),
	'movementSplitter_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	),
	'movementSplitter_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'movementSplitter_dontCreate' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => false,
		'label' => _t('Do not create new records'),
		'description' => _t('If set splitter will only match on existing records and will not create new ones.')
	),
	'movementSplitter_relationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type'),
		'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.  Note for object data: if the relationship type matches that set as the hierarchy control, the object will be pulled in as a "child" element in the movement hierarchy.')
	),
	'movementSplitter_relationshipOrientation' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type orientation'),
		'description' => _t('Sets directionality of movement-movement relationships. Use LTOR for left-to-right directionality; RTOL for right-to-left directionality. LTOR is the default.')
	),
	'movementSplitter_extractRelationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Extract relationship type'),
		'description' => _t('If set splitter will attempt to extract relationship type from data. By default it will look for text enclosed in parens. Set to {} or [] or look for text enclosed with those brackets instead.')
	),
	'movementSplitter_relationshipTypeDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type delimiter'),
		'description' => _t('If set splitter will use the relationship type value as a list of types and create a relationship for each.')
	),
	'movementSplitter_movementType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('movement type'),
		'description' => _t('Accepts a constant list item idno from the list movement_types or a reference to the location in the data source where the type can be found.')
	),
	'movementSplitter_attributes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attributes'),
		'description' => _t('Sets or maps metadata for the movement record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'movementSplitter_attributeDelimiters' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attribute delimiters'),
		'description' => _t('Delimiters to use for each mapped attribute.')
	),
	'movementSplitter_parents' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Parents'),
		'description' => _t('movement parents to create, if required')
	),
	'movementSplitter_relationshipTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type default'),
		'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
	),
	'movementSplitter_movementTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('movement type default'),
		'description' => _t('Sets the default movement type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list movement_types.')
	),
	'movementSplitter_interstitial' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Interstitial attributes'),
		'description' => _t('Sets or maps metadata for the interstitial movement <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'movementSplitter_nonPreferredLabels' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Non-preferred labels'),
		'description' => _t('List of non-preferred labels to apply to movements.')
	),
	'movementSplitter_relationships' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationships'),
		'description' => _t('List of relationships to process.')
	),
	'movementSplitter_textTransform' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'options' => [
			'Upper case' => 'toUpperCase',
			'Lower case' => 'toLowerCase',
			'Upper case first character only' => 'upperCaseFirst',
		],
		'label' => _t('Text transformation'),
		'description' => _t('Transform text case.')
	),
	'movementSplitter_useRawValues' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Use raw values'),
		'description' => _t('If set splitter will use raw data values without processing such as replacement values, regular expressions, etc.')
	)
);
