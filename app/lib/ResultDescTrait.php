<?php
/** ---------------------------------------------------------------------
 * app/lib/ResultDescTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 
 /**
  * Methods setting and getting result description information documenting how
  * rows in a search result set were matched.
  */
  
trait ResultDescTrait {
	# ------------------------------------------------------------------
	
	protected $result_desc = [];
	
	# ------------------------------------------------------------------
	/**
	 * Set raw data documenting how hits in the current result set were matched.
	 *
	 * @param array $result_desc
	 *
	 * @return bool 
	 */
	public function setRawResultDesc(?array $result_desc) :bool {
		if(!is_array($result_desc)) { return false; }
		$this->result_desc = $result_desc;
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 * Return raw data from engine documenting how hits in the current result set were matched.
	 * This data is generally not useful as-is for display or UI management, and must be resolved
	 * into references to matched tables and fiedls using SearchEngine::resolveResultDescData()
	 *
	 * @return array
	 */
	public function getRawResultDesc() : array {
		return $this->result_desc;
	}
	# ------------------------------------------------------------------
}
