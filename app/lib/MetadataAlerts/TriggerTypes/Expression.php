<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/Expression.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2033 Whirl-i-Gig
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

class Expression extends Base {

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [
			'expression' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => '670px', 'height' => 3,
				'default' => '',
				'label' => _t('Expression'),
				'suffix' => _t(''),
				'description' => _t('Expression to evaluate when record is saved.')
			]
		];
	}

	public function getTriggerType() {
		return __CA_MD_ALERT_CHECK_TYPE_SAVE__;
	}
	
	public function attachesToMetadataElement() : bool {
		return false;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	public function check(&$t_instance) {
		$values = $this->getTriggerValues() ?? [];
		if(!sizeof($values)) { return false; }
		
		$expression = $values['settings']['expression'] ?? null;
		if(!strlen($expression)) {
			throw new MetadataAlertExpressionException(_t('No expression specified for alert', ''));
		}
		$tags = caGetTemplateTags($expression) ?? [];
		$exp_values = [];
		foreach($tags as $t) {
			$exp_values[$t] = $t_instance->get($t);
		}
		
		try {
			if (\ExpressionParser::evaluate($expression, $exp_values)) {
				return true;
			}
		} catch(Exception $e) {
			// Invalid expression
			throw new MetadataAlertExpressionException(_t('Invalid expression specified for alert: %1', $e->getMessage()), $expression);
		}
		return false;
	}
}
