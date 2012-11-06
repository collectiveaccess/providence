<?php
/** ---------------------------------------------------------------------
 * app/helpers/listHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
require_once(__CA_MODELS_DIR__.'/ca_lists.php');


	# ---------------------------------------
	/**
	 * Fetch item_id for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get item_id for
	 * @return int item_id of list item or null if no matching item was found
	 */
	function caGetListItemID($ps_list_code, $ps_idno) {
		$t_list = new ca_lists();
		
		return $t_list->getItemIDFromList($ps_list_code, $ps_idno);
	}
	# ---------------------------------------
	/**
	 * Fetch display label in current locale for item with specified idno in list
	 *
	 * @param string $ps_list_code List code
	 * @param string $ps_idno idno of item to get label for
	 * @param bool $pb_return_plural If true, return plural version of label. Default is to return singular version of label.
	 * @return string The label of the list item, or null if no matching item was found
	 */
	function caGetListItemForDisplay($ps_list_code, $ps_idno, $pb_return_plural=false) {
		$t_list = new ca_lists();
		
		return $t_list->getItemFromListForDisplay($ps_list_code, $ps_idno, $pb_return_plural);
	}
	# ---------------------------------------
?>