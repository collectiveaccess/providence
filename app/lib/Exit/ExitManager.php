<?php
/** ---------------------------------------------------------------------
 * app/lib/Exit/ExitManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * @subpackage Exit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace Exit;

class ExitManager {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $format = 'XML';
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(?string $format='XML') {
		$this->setFormat($format);
	}
	# -------------------------------------------------------
	/**
	 * Returns list of tables to be exported
	 *
	 */
	public function getExportTableNames(?array $options=null) : array {
		$tables = caGetPrimaryTables(true, [
			'ca_relationship_types'
		], ['returnAllTables' => true]);
		
		return $tables;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function setFormat(string $format) : bool {
		$format = strtoupper($format);
		if(!in_array($format, ['XML', 'CSV'], true)) { return false; }
		$this->format = $format;
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getFormat() : string {
		return $this->format;
	}
	# -------------------------------------------------------
}
