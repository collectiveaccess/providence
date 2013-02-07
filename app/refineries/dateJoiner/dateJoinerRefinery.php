<?php
/* ----------------------------------------------------------------------
 * dateJoinerRefinery.php : 
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
 
	class dateJoinerRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'dateJoiner';
			$this->ops_title = _t('Date joiner');
			$this->ops_description = _t('Joins date fields');
			
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
			//$va_group_dest = explode(".", $pa_group['destination']);
			//$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			$va_item_dest = explode(".", $pa_item['destination']);
			$vs_item_terminal = array_pop($va_item_dest);
			$vs_group_terminal = array_pop($va_item_dest);
			
			$pm_value = preg_replace("![^\d\.A-Za-z\"\"’”]+!", "", $pm_value);
			
			$vs_date_expression = $pa_item['settings']['dateJoiner_dateExpression'];
			$vs_date_start = $pa_item['settings']['dateJoiner_dateStart'];
			$vs_date_end = $pa_item['settings']['dateJoiner_dateEnd'];
			
			$o_tep = new TimeExpressionParser();
			
			if ($vs_date_expression && ($vs_exp = BaseRefinery::parsePlaceholder($vs_date_expression, $pa_source_data, $pa_item))) {
				if ($o_tep->parse($vs_exp)) {
					return array(0 => array($vs_group_terminal => array($vs_item_terminal => $o_tep->getText())));
				}
			}
			
			$va_date = array();
			if ($vs_date_start = BaseRefinery::parsePlaceholder($vs_date_start, $pa_source_data, $pa_item)) { $va_date[] = $vs_date_start; }
			if ($vs_date_end = BaseRefinery::parsePlaceholder($vs_date_end, $pa_source_data, $pa_item)) { $va_date[] = $vs_date_end; }
			$vs_date_expression = join(" - ", $va_date);
			if ($vs_date_expression && ($vs_exp = BaseRefinery::parsePlaceholder($vs_date_expression, $pa_source_data, $pa_item))) {
				if ($o_tep->parse($vs_exp)) {
					return array(0 => array($vs_group_terminal => array($vs_item_terminal => $o_tep->getText())));
				}
			}
			
			return array();
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['dateJoiner'] = array(		
			'dateJoiner_dateExpression' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date expression'),
				'description' => _t('Date expression')
			),
			'dateJoiner_dateStart' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date start'),
				'description' => _t('Maps the date from the data source that is the beginning of the conjoined date range.')
			),
			'dateJoiner_dateEnd' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 8,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Date end'),
				'description' => _t('Maps the date from the data source that is the end of the conjoined date range.')
			)
		);
?>