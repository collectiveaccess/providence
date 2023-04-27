<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Replication.php : 
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

trait CLIUtilsReplication { 
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_data($po_opts=null) {
		require_once(__CA_LIB_DIR__.'/Sync/Replicator.php');

		$o_replicator = new Replicator();
		$o_replicator->replicate();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataParamList() {
		return array();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataShortHelp() {
		return _t("Replicate data from one CollectiveAccess system to another.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataHelp() {
		return _t("Replicates data in one CollectiveAccess instance based upon data in another instance, subject to configuration in replication.conf.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_change($po_opts=null) {
		require_once(__CA_LIB_DIR__.'/Sync/Replicator.php');

		if (!($source = $po_opts->getOption('source'))) {
			CLIUtils::addError(_t("You must specify a source"));
			return false;
		}
		$target = $po_opts->getOption('target');
		
		$guid = $po_opts->getOption('guid');	// TODO
		
		$table = $po_opts->getOption('table');
		$search = $po_opts->getOption('search');
		
		$guids = [];
		if($table && $search) {
			if(!($o_search = caGetSearchInstance($table))) {
				CLIUtils::addError(_t("Could not set up search for %1", $table));
				return false;
			}
			if($qr = $o_search->search($search)) {
				while($qr->nextHit()) {
					$guids[] = $qr->get("{$table}._guid");
				}
			} else {
				CLIUtils::addError(_t("Search failed"));
				return false;
			}
		} elseif($guid) {
			$guids = [$guid];
		} else {
			CLIUtils::addError(_t("You must specify a search or guid to replicate"));
			return false;
		}

		$o_replicator = new Replicator();
		
		foreach($guids as $guid) {
			print "Replicating {$guid}\n";
			$o_replicator->forceSyncOfLatest($source, $guid, ['targets' => [$target]]);
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeParamList() {
		return [
			"source|s=s" => _t('Source system'),
			"target|t=s" => _t('Target system'),
			"guid|g=s" => _t('GUID of record to replicate'),
			"table|b=s" => _t('Table of records to replicate'),
			"search|f=s" => _t('Search for records to replicate')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeShortHelp() {
		return _t("Force replication of last change to specific record");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeHelp() {
		return _t("To come.");
	}
	# -------------------------------------------------------
}
