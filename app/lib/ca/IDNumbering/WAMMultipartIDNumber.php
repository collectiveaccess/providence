<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/WAMMultipartIDNumber.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 *
 * File created by Kehan Harman (www.gaiaresources.com.au) for specific Western Australian Museum requirements
 */

require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/MultipartIDNumber.php");

class WAMMultipartIDNumber extends MultipartIDNumber {
	/**
	 * Calls the parent method, and then for every value, adds the value again with the separators stripped.  If the
	 * separator is empty, this method does nothing different to the parent method.
	 *
	 * @param string|null $ps_value
	 *
	 * @return array
	 */
	public function getIndexValues($ps_value = null){
		$pa_index_values = parent::getIndexValues($ps_value);
		$vs_separator = $this->getSeparator();
		if ($vs_separator) {
			foreach ($pa_index_values as $vs_index_value) {
				if (strpos($vs_index_value, $vs_separator)) {
					$pa_index_values[] = str_replace($vs_separator, '', $vs_index_value);
				}
			}
		}
		return array_unique($pa_index_values);
	}
}
