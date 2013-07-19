<?php
/* ----------------------------------------------------------------------
 * loanSplitterRefinery.php : 
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
 
	class loanSplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'loanSplitter';
			$this->ops_title = _t('Loan splitter');
			$this->ops_description = _t('Provides several loan-related import functions: splitting of multiple loans in a string into individual values, mapping of type and relationship type for related loans, and merging loan data with loan names.');
			
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
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_loans = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['loanSplitter_delimiter']) {
					$va_loans = explode($vs_delimiter, $pm_value);
				} else {
					$va_loans = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_loans as $vn_i => $vs_loan) {
				if (!$vs_loan = trim($vs_loan)) { continue; }
				
				if($vs_terminal == 'name') {
					return $vs_loan;
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return array('name' => $vs_loan);	
				}
			
				// Set label
				$va_val = array('preferred_labels' => array('name' => $vs_loan));
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['loanSplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || $va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['loanSplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarning(_t('[loanSplitterRefinery] No relationship type is set for loan %1', $vs_loan));
				}
	
				// Set loan_type
				if (
					($vs_type_opt = $pa_item['settings']['loanSplitter_loanType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['loanSplitter_loanTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarning(_t('[loanSplitterRefinery] No loan type is set for loan %1', $vs_loan));
				}
			
				// Set attributes
				if (is_array($pa_item['settings']['loanSplitter_attributes'])) {
					$va_attr_vals = array();
					foreach($pa_item['settings']['loanSplitter_attributes'] as $vs_element_code => $va_attrs) {
						if(is_array($va_attrs)) {
							foreach($va_attrs as $vs_k => $vs_v) {
								// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
								// DataMigrationUtils::getLoanID(), which ca_data_importers::importDataFromSource() uses to create related loans
								// only supports non-repeating attribute values, so we join any values here and call it a day.
								$va_attr_vals[$vs_element_code][$vs_k] = (is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
							}
						} else {
							$va_attr_vals[$vs_element_code][$vs_element_code] = (is_array($vm_v = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
						}
					}
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * loanSplitter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['loanSplitter'] = array(		
			'loanSplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values')
			),
			'loanSplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.  Note for object data: if the relationship type matches that set as the hierarchy control, the object will be pulled in as a "child" element in the loan hierarchy.')
			),
			'loanSplitter_loanType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Loan type'),
				'description' => _t('Accepts a constant list item idno from the list loan_types or a reference to the location in the data source where the type can be found.')
			),
			'loanSplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the loan record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'loanSplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'loanSplitter_loanTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Loan type default'),
				'description' => _t('Sets the default loan type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list loan_types.')
			)
		);
?>