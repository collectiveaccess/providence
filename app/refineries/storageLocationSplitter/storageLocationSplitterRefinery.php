<?php
/* ----------------------------------------------------------------------
 * storageLocationSplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 	require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');
 
	class storageLocationSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'storageLocationSplitter';
			$this->ops_title = _t('Storage location splitter');
			$this->ops_description = _t('Provides several storage location-related import functions: splitting of multiple locations in a string into individual values, mapping of type and relationship type for related locations, building location hierarchies and merging location data with names.');
			
			$this->opb_returns_multiple_values = true;
			
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
			$t_loc = new ca_storage_locations();
			$pa_options['defaultParentID'] = $t_loc->getHierarchyRootID();
			return caGenericImportSplitter('storageLocationSplitter', 'storageLocation', 'ca_storage_locations', $this, $pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options);
		}
		# -------------------------------------------------------	
		/**
		 * storageLocationSplitter returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['storageLocationSplitter'] = array(		
			'storageLocationSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
			),
			'storageLocationSplitter_hierarchicalDelimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Hierarchical delimiter'),
				'description' => _t('Sets the value of the delimiter to break hierarchical values on.')
			),
			'storageLocationSplitter_hierarchicalStorageLocationTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Hierarchical storage location types'),
				'description' => _t('A semicolon-delimited list of storage location types to apply to hierarchical storage locations extracted using the hierarchical delimiter.')
			),
			'storageLocationSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'storageLocationSplitter_storageLocationType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Storage location type'),
				'description' => _t('Accepts a constant list item idno from the list storage_location_types or a reference to the location in the data source where the type can be found.')
			),
			'storageLocationSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the storage location record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'storageLocationSplitter_parents' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Parents'),
				'description' => _t('Storage location parents to create, if required')
			),
			'storageLocationSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system')
			),
			'storageLocationSplitter_storageLocationTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Storage location type default'),
				'description' => _t('Sets the default storage location type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list storage_location_types')
			),
			'storageLocationSplitter_interstitial' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Interstitial attributes'),
				'description' => _t('Sets or maps metadata for the interstitial storage location <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'storageLocationSplitter_nonPreferredLabels' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Non-preferred labels'),
				'description' => _t('List of non-preferred labels to apply to storage locations.')
			)
		);
?>