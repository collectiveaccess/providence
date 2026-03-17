<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate189.php :
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
 * @package CollectiveAccess
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__ . '/BaseVersionUpdater.php');

class VersionUpdate209 extends BaseVersionUpdater {
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 209;
	protected $messages = [];
	
	private $err_count = 0;
	private $total = 0;
	# -------------------------------------------------------

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function applyDatabaseUpdate($options = null) {
		$ret = parent::applyDatabaseUpdate($options);
		
		$qr = ca_object_lots::findAsSearchResult('*');
		$this->total = $qr->numHits();
		
		$this->err_count = 0;
		while($qr->nextHit()) {
			$l = $qr->getInstance();
			if(!$l) { 
				$this->err_count++;
				continue;
			}
			$l->set('hier_lot_id', $l->getPrimaryKey());
			if(!$l->update()) {
				$this->err_count++;
			}
			$l->rebuildHierarchicalIndex();
		}
		
		return $ret;
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage() {
		if($this->err_count > 0) {
			return _t("Attempted to update %1 lots for compatibility with hierarchies, but %2 failed.", $this->total, $this->err_count);
		}
		return _t("Updated %1 lots for compatibility with hierarchies.", $this->total);
	}
	# -------------------------------------------------------
}
