<?php
/** ---------------------------------------------------------------------
 * app/lib/Browse/BaseBrowse.php : Base class for ca_* browses
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__."/Browse/BrowseEngine.php");
  
class BaseBrowse extends BrowseEngine {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($subject_table_name_or_num, ?string $browse_id=null, ?string $context=null) {
		parent::__construct($subject_table_name_or_num, $browse_id, $context);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function execute($options=null) {
		return parent::execute($options);
	}
	# -------------------------------------------------------
}
