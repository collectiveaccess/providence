<?php
/* ----------------------------------------------------------------------
 * dateAccuracyJoinerRefinery.php : 
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
require_once(__CA_LIB_DIR__ . '/ca/Import/BaseRefinery.php');

class dateAccuracyJoinerRefinery extends BaseRefinery {
	# -------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'dateAccuracyJoiner';
		$this->ops_title = _t('Date Accuracy');
		$this->ops_description = _t('Modifies a date based on accuracy in another column.');
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
	public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options = null) {
		// Extract and normalise the original date value
		$vs_source_value = $pa_source_data[$pa_item['source']];
		$vo_normalised_date = DateTime::createFromFormat($pa_item['settings']['dateAccuracyJoiner_dateFormat'], $vs_source_value);

		if ($vo_normalised_date === false) {
			// Date does not match expected format
			switch ($pa_item['settings']['dateAccuracyJoiner_dateParseFailureReturnMode']) {
				case 'original':
					return $vs_source_value;
				case 'null':
				default:
					return null;
			}
		}
		$vs_normalised_date = $vo_normalised_date->format('Y-m-d');
		// Process according to accuracy value
		switch (strtolower(trim($pa_source_data[$pa_item['settings']['dateAccuracyJoiner_accuracyField']]))) {
			case strtolower($pa_item['settings']['dateAccuracyJoiner_accuracyValueDay']):
				// Date is accurate to the day, return normalised value unmodified
				return $vs_normalised_date;
			case strtolower($pa_item['settings']['dateAccuracyJoiner_accuracyValueMonth']):
				// Date is accurate to the month, strip off the last three characters (YYYY-MM-DD => YYYY-MM)
				return substr($vs_normalised_date, 0, -3);
			case strtolower($pa_item['settings']['dateAccuracyJoiner_accuracyValueYear']):
				// Date is accurate to the year, strip off the last six characters (YYYY-MM-DD => YYYY)
				return substr($vs_normalised_date, 0, -6);
			default:
				// Accuracy value is unknown
				switch ($pa_item['settings']['dateAccuracyJoiner_unknownAccuracyValueReturnMode']) {
					case 'null':
						return null;
					case 'original':
						return $vs_source_value;
					case 'normalised':
					default:
						return $vs_normalised_date;
				}
		}
	}
	# -------------------------------------------------------
	/**
	 * dateAccuracyJoiner returns a single transformed date value
	 *
	 * @return bool Always false
	 */
	public function returnsMultipleValues() {
		return false;
	}
	# -------------------------------------------------------
}

BaseRefinery::$s_refinery_settings['dateAccuracyJoiner'] = array(
	'dateAccuracyJoiner_accuracyField' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'label' => _t('Date Accuracy Field'),
		'description' => _t('The field in which the date accuracy is stored (case-insensitive values are defined by accuracyValueDay, accuracyValueMonth and accuracyValueYear settings)')
	),
	'dateAccuracyJoiner_dateFormat' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'Y-m-d',
		'label' => _t('Date Format'),
		'description' => _t('The format in which the input dates are expected')
	),
	'dateAccuracyJoiner_accuracyValueDay' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'day',
		'label' => _t('Date Accuracy Value: Day'),
		'description' => _t('The case-insensitive value in the accuracy field that indicates that the date is accurate to the day')
	),
	'dateAccuracyJoiner_accuracyValueMonth' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'month',
		'label' => _t('Date Accuracy Value: Month'),
		'description' => _t('The case-insensitive value in the accuracy field that indicates that the date is accurate to the month')
	),
	'dateAccuracyJoiner_accuracyValueYear' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'year',
		'label' => _t('Date Accuracy Value: Year'),
		'description' => _t('The case-insensitive value in the accuracy field that indicates that the date is accurate to the year')
	),
	'dateAccuracyJoiner_dateParseFailureReturnMode' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'null',
		'label' => _t('Date Parse Failure Return Mode'),
		'description' => _t('Defines what should be returned if the input date cannot be parsed, available values are "null" (the default) and "original"')
	),
	'dateAccuracyJoiner_unknownAccuracyValueReturnMode' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 10, 'height' => 1,
		'takesLocale' => false,
		'default' => 'normalised',
		'label' => _t('Unknown Accuracy Value Return Mode'),
		'description' => _t('Defines what should be returned if the value of the accuracy column is unknown, available values are "null", "original" and "normalised" (the default)')
	)
);
