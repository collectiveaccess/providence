<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8Result.php :
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

include_once( __CA_LIB_DIR__ . '/Datamodel.php' );
include_once( __CA_LIB_DIR__ . '/Plugins/WLPlug.php' );
include_once( __CA_LIB_DIR__ . '/Plugins/IWLPlugSearchEngineResult.php' );

class WLPlugSearchEngineElastic8Result extends WLPlug implements IWLPlugSearchEngineResult {
	# -------------------------------------------------------
	private $hits;
	private $current_row;
	private $subject_tablenum;
	private $subject_primary_key;
	private $subject_instance;
	private $subject_table_name;

	# -------------------------------------------------------
	public function __construct( $pa_hits, $pn_table_num ) {
		parent::__construct();

		$this->subject_tablenum = $pn_table_num;
		$this->setHits( $pa_hits );
	}

	# -------------------------------------------------------
	public function setHits( $hits ) {
		$this->hits = $hits;
		$this->current_row = - 1;

		if ( sizeof( $this->hits ) ) {
			$this->subject_instance = Datamodel::getInstanceByTableNum( $this->subject_tablenum, true );
			$this->subject_primary_key = $this->subject_instance->primaryKey();
			$this->subject_table_name = $this->subject_instance->tableName();
		}
	}

	# -------------------------------------------------------
	public function getHits() {
		return $this->hits;
	}

	# -------------------------------------------------------
	public function numHits() {
		return is_array( $this->hits ) ? sizeof( $this->hits ) : 0;
	}

	# -------------------------------------------------------
	public function nextHit() {
		if ( $this->current_row < sizeof( $this->hits ) - 1 ) {
			$this->current_row ++;

			return true;
		}

		return false;
	}

	# -------------------------------------------------------
	public function currentRow() {
		return $this->current_row;
	}

	# -------------------------------------------------------
	public function get( $ps_field, $pa_options = null ) {
		// the only thing get() pulls directly from the index is the primary key ...
		// everything else is handled in SearchResult::get() using prefetched database queries.
		if ( $ps_field == $this->subject_primary_key
			|| $ps_field == $this->subject_table_name . '.' . $this->subject_primary_key
		) {
			return $this->hits[ $this->current_row ]['_id'];
		}

		return false;
	}

	# -------------------------------------------------------
	public function getPrimaryKeyValues( $limit = null ) {
		if ( ! $limit ) {
			$limit = null;
		}
		if ( ! is_array( $this->hits ) ) {
			return array();
		}
		// primary key
		$ids = array();

		$c = 0;
		foreach ( $this->hits as $i => $row ) {
			$ids[] = $row['_id'];
			$c ++;
			if ( ! is_null( $limit ) && ( $c >= $limit ) ) {
				break;
			}
		}

		return $ids;
	}

	# -------------------------------------------------------
	public function seek( $pn_index ) {
		if ( ( $pn_index >= 0 ) && ( $pn_index < sizeof( $this->hits ) ) ) {
			$this->current_row = $pn_index - 1;

			return true;
		}

		return false;
	}
	# -------------------------------------------------------
}
