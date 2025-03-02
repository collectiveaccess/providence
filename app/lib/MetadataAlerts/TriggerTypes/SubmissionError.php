<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/SubmissionError.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * @subpackage MetadataAlerts
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\MetadataAlerts\TriggerTypes;

class SubmissionError extends Base {

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [];
	}

	public function getTriggerType() {
		return __CA_MD_ALERT_CHECK_TYPE_SUBMISSION_ERROR__;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	public function check(&$t_instance) {
		return true;
	}
	
	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @param array $additional_data
	 * @return string
	 */
	public function getEventKey($t_instance, ?array $additional_data=null) {
		return 'SubmissionWarning/'.$t_instance->tableName().'/'.$t_instance->getPrimaryKey().'/'.caGetOption('index', $additional_data, null);
	}
	
	/**
	 * Return true if trigger must be attached to a metadata element
	 *
	 * @return bool
	 */
	public function attachesToMetadataElement() : bool {
		return false;
	}
	
	/**
	 * Return additional filter values for specified metadata element. Return null for no filtering.
	 *
	 * @return string
	 */
	public function getElementFilters($element_id, $prefix_id, array $pa_options=[]) {
		return null;
	}
	
	/**
	 * List of elements to add to standard list of element. Return null if
	 * no elements are to be added.
	 *
	 * @return array
	 */
	public function getAdditionalElementList() {
		return null;
	}
}
