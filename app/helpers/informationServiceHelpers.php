<?php
/** ---------------------------------------------------------------------
 * app/helpers/informationServiceHelpers.php : miscellaneous system functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
# ---------------------------------------
/** 
 * 
 */
function caGetInformationServiceMirrorListInformation(ca_metadata_elements $t_element) : ?array {
	$element_type = $t_element->get('datatype');
	if($element_type !== __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__) { return null; }
	
	if($use_mirror_list = (bool)$t_element->getSetting('useMirrorList')) {
		$mirror_list = $t_element->getSetting('mirrorToList');
		$mirror_access = $t_element->getSetting('mirrorToListAccess');
		
		return [
			'list' => $mirror_list,
			'access' => $mirror_access
		];
	}
	
	return null;
}
# ---------------------------------------
