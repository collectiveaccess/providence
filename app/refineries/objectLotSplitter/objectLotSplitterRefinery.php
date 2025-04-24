<?php
/* ----------------------------------------------------------------------
 * objectLotSplitterRefinery.php : 
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

class objectLotSplitterRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'objectLotSplitter';
		$this->ops_title = _t('Object lot splitter');
		$this->ops_description = _t('Provides several lot-related import functions: splitting of multiple lots in a string into individual values, mapping of type and relationship type for related lots, and merging lot data with lot names.');

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
		return caGenericImportSplitter('objectLotSplitter', 'objectLot', 'ca_object_lots', $this, $pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options);
	}
	# -------------------------------------------------------	
	/**
	 * objectLotSplitter returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return $this->opb_returns_multiple_values;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['objectLotSplitter'] = array(		
	'objectLotSplitter_delimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Delimiter'),
		'description' => _t('Sets the value of the delimiter to break on, separating data source values')
	),
	'objectLotSplitter_matchOn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Match on'),
		'description' => _t('List indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails')
	),
	'objectLotSplitter_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	),
	'objectLotSplitter_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'objectLotSplitter_dontCreate' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => false,
		'label' => _t('Do not create new records'),
		'description' => _t('If set splitter will only match on existing records and will not create new ones.')
	),
	'objectLotSplitter_relationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type'),
		'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
	),
	'objectLotSplitter_relationshipOrientation' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type orientation'),
		'description' => _t('Sets directionality of object lot-object lot relationships. Use LTOR for left-to-right directionality; RTOL for right-to-left directionality. LTOR is the default.')
	),
	'objectLotSplitter_extractRelationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Extract relationship type'),
		'description' => _t('If set splitter will attempt to extract relationship type from data. By default it will look for text enclosed in parens. Set to {} or [] or look for text enclosed with those brackets instead.')
	),
	'objectLotSplitter_objectLotType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Lot type'),
		'description' => _t('Accepts a constant list item idno from the list object_lot_types or a reference to the location in the data source where the type can be found.')
	),
	'objectLotSplitter_objectLotStatus' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Lot status'),
		'description' => _t('Accepts a constant list item idno from the list object_lot_statuses or a reference to the location in the data source where the status can be found.')
	),
	'objectLotSplitter_attributes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attributes'),
		'description' => _t('Sets or maps metadata for the lot record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'objectLotSplitter_attributeDelimiters' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attribute delimiters'),
		'description' => _t('Delimiters to use for each mapped attribute.')
	),
	'objectLotSplitter_relationshipTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type default'),
		'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
	),
	'objectLotSplitter_relationshipTypeDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type delimiter'),
		'description' => _t('If set splitter will use the relationship type value as a list of types and create a relationship for each.')
	),
	'objectLotSplitter_objectLotTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Lot type default'),
		'description' => _t('Sets the default lot type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list object_lot_types.')
	),
	'objectLotSplitter_objectLotStatusDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Lot status default'),
		'description' => _t('Sets the default lot status that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list object_lot_statuses.')
	),
	'objectLotSplitter_interstitial' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Interstitial attributes'),
		'description' => _t('Sets or maps metadata for the interstitial lot <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'objectLotSplitter_nonPreferredLabels' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Non-preferred labels'),
		'description' => _t('List of non-preferred labels to apply to lots.')
	),
	'objectLotSplitter_relationships' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationships'),
		'description' => _t('List of relationships to process.')
	),
	'objectLotSplitter_textTransform' => array(
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
	'objectLotSplitter_useRawValues' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Use raw values'),
		'description' => _t('If set splitter will use raw data values without processing such as replacement values, regular expressions, etc.')
	)
);
