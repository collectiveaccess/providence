<?php
/* ----------------------------------------------------------------------
 * placeSplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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

class placeSplitterRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'placeSplitter';
		$this->ops_title = _t('Place splitter');
		$this->ops_description = _t('Provides several place-related import functions: splitting of multiple places in a string into individual values, mapping of type and relationship type for related places, building place hierarchies and merging place data with names.');
		
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
		$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
		
		// Set place hierarchy
		if ($vs_hierarchy = $pa_item['settings']['placeSplitter_placeHierarchy']) {
			$vn_hierarchy_id = caGetListItemID('place_hierarchies', $vs_hierarchy);
		} else {
			// Default to first place hierarchy
			$t_list = new ca_lists();
			$va_hierarchy_ids = $t_list->getItemsForList('place_hierarchies', array('idsOnly' => true, 'omitRoot' => true));
			$vn_hierarchy_id = array_shift($va_hierarchy_ids);
		}
		if (!$vn_hierarchy_id) {
			if ($o_log) { $o_log->logError(_t('[placeSplitterRefinery] No place hierarchies are defined')); }
			return array();
		}
		$pa_options['hierarchyID'] = $vn_hierarchy_id;
		
		$t_place = new ca_places();
		if ($t_place->load(array('parent_id' => null, 'hierarchy_id' => $vn_hierarchy_id))) {
			$pa_options['defaultParentID'] = $t_place->getPrimaryKey();
		}
	
		return caGenericImportSplitter('placeSplitter', 'place', 'ca_places', $this, $pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options);
	}
	# -------------------------------------------------------	
	/**
	 * placeSplitter returns multiple values
	 *
	 * @return bool
	 */
	public function returnsMultipleValues() {
		return $this->opb_returns_multiple_values;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['placeSplitter'] = array(		
	'placeSplitter_delimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Delimiter'),
		'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
	),
	'placeSplitter_matchOn' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Match on'),
		'description' => _t('List indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails')
	),
	'placeSplitter_ignoreType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore type when trying to match row'),
		'description' => _t('Ignore type when trying to match row.')
	),
	'placeSplitter_ignoreParent' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Ignore parent when trying to match row'),
		'description' => _t('Ignore parent when trying to match row.')
	),
	'placeSplitter_dontCreate' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => false,
		'label' => _t('Do not create new records'),
		'description' => _t('If set splitter will only match on existing records and will not create new ones.')
	),
	'placeSplitter_relationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type'),
		'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
	),
	'placeSplitter_relationshipOrientation' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type orientation'),
		'description' => _t('Sets directionality of place-place relationships. Use LTOR for left-to-right directionality; RTOL for right-to-left directionality. LTOR is the default.')
	),
	'placeSplitter_extractRelationshipType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Extract relationship type'),
		'description' => _t('If set splitter will attempt to extract relationship type from data. By default it will look for text enclosed in parens. Set to {} or [] or look for text enclosed with those brackets instead.')
	),
	'placeSplitter_relationshipTypeDelimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type delimiter'),
		'description' => _t('If set splitter will use the relationship type value as a list of types and create a relationship for each.')
	),
	'placeSplitter_placeType' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Place type'),
		'description' => _t('Accepts a constant list item idno from the list place_types or a reference to the location in the data source where the type can be found.')
	),
	'placeSplitter_attributes' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attributes'),
		'description' => _t('Sets or maps metadata for the place record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'placeSplitter_attributeDelimiters' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Attribute delimiters'),
		'description' => _t('Delimiters to use for each mapped attribute.')
	),
	'placeSplitter_parents' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Parents'),
		'description' => _t('Place parents to create, if required')
	),
	'placeSplitter_hierarchy' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchy'),
		'description' => _t('Place hierarchy to create, if required.')
	),
	'placeSplitter_placeHierarchy' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Hierarchy'),
		'description' => _t('Identifier of the place hierarchy to add places under.')
	),
	'placeSplitter_relationshipTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationship type default'),
		'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system')
	),
	'placeSplitter_placeTypeDefault' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Place type default'),
		'description' => _t('Sets the default place type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list place_types')
	),
	'placeSplitter_skipIfValue' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Skip if value'),
		'description' => _t('Skip if imported value is in the specified list of values.')
	),
	'placeSplitter_skipIfNotValue' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Skip if value'),
		'description' => _t('Skip if imported value is in the specified list of values.')
	),
	'placeSplitter_interstitial' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Interstitial attributes'),
		'description' => _t('Sets or maps metadata for the interstitial place <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
	),
	'placeSplitter_nonPreferredLabels' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Non-preferred labels'),
		'description' => _t('List of non-preferred labels to apply to places.')
	),
	'placeSplitter_relationships' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Relationships'),
		'description' => _t('List of relationships to process.')
	),
	'placeSplitter_textTransform' => array(
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
	'placeSplitter_useRawValues' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Use raw values'),
		'description' => _t('If set splitter will use raw data values without processing such as replacement values, regular expressions, etc.')
	)
);
