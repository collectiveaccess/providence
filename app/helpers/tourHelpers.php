<?php
/** ---------------------------------------------------------------------
 * app/helpers/tourHelpers.php : miscellaneous functions
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
require_once(__CA_MODELS_DIR__.'/ca_tours.php');
require_once(__CA_MODELS_DIR__.'/ca_tour_labels.php');
require_once(__CA_MODELS_DIR__.'/ca_tour_stops.php');

	
	# ---------------------------------------
	/**
	 * Fetch tour_id for tour with specified idno or label
	 *
	 * @param string $ps_tour Tour code or tour label
	 * @return int tour_id of tour or null if no matching tour was found
	 */
	function caGetTourID($ps_tour) {
		$t_tour = new ca_tours();
		
		if (is_numeric($ps_tour)) {
			if ($t_tour->load((int)$ps_tour)) {
				return $t_tour->getPrimaryKey();
			}
		}
		
		if ($t_tour->load(array('tour_code' => $ps_tour))) {
			return $t_tour->getPrimaryKey();
		}
		
		$t_label = new ca_tour_labels();
		if ($t_label->load(array('name' => $ps_tour))) {
			return $t_label->get('tour_id');
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * Fetch stop_id for stop with specified idno in tour
	 *
	 * @param string $ps_tour_code Tour code
	 * @param string $ps_idno idno of stop to return stop_id for
	 * @return int item_id of list item or null if no matching item was found
	 */
	function caGetTourStopID($ps_tour_code, $ps_idno) {
		$vn_tour_id = caGetTourID($ps_tour_code);
		
		$t_stop = new ca_tour_stops();
		return $t_stop->load(array('tour_id' => $vn_tour_id, 'idno' => $ps_idno));
	}
	# ---------------------------------------
?>