<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Geocode.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace ElasticSearch\FieldTypes;

require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/GeocodeAttributeValue.php');

class Geocode extends GenericElement {
	public function __construct($ps_table_name, $ps_element_code, $pm_content) {
		parent::__construct($ps_table_name, $ps_element_code, $pm_content);
	}

	public function getDocumentFragment() {
		$vm_content = parent::getContent();
		if (is_array($vm_content)) { $vm_content = serialize($vm_content); }
		$va_return = array();

		$o_geocode_parser = new \GeocodeAttributeValue();

		$va_return[$this->getTableName().'.'.$this->getElementCode().'_text'] = $vm_content;
		if ($va_coords = $o_geocode_parser->parseValue($vm_content, array())) {
			if (isset($va_coords['value_longtext2']) && $va_coords['value_longtext2']) {
				$va_return[$this->getTableName().'.'.$this->getElementCode()] = explode(':', $va_coords['value_longtext2']);
			}
		}

		return $va_return;
	}
}
