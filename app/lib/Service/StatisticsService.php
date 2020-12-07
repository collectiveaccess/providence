<?php
/** ---------------------------------------------------------------------
 * app/lib/Service/StatisticsService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once( __CA_LIB_DIR__ . "/Service/BaseJSONService.php" );

class StatisticsService extends BaseJSONService {
	# -------------------------------------------------------
	/**
	 * Dispatch service call
	 *
	 * @param string      $ps_endpoint
	 * @param RequestHTTP $po_request
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function dispatch( $ps_endpoint, $po_request ) {

		$vs_cache_key = $po_request->getHash();

		if ( ! $po_request->getParameter( 'noCache', pInteger ) ) {
			if ( ExternalCache::contains( $vs_cache_key, "StatisticsAPI_{$ps_endpoint}" ) ) {
				return ExternalCache::fetch( $vs_cache_key, "StatisticsAPI_{$ps_endpoint}" );
			}
		}

		$vm_return = self::runStats( $po_request );

		$vn_ttl = defined( '__CA_SERVICE_API_CACHE_TTL__' ) ? __CA_SERVICE_API_CACHE_TTL__
			: 60 * 60; // save for an hour by default
		ExternalCache::save( $vs_cache_key, $vm_return, "StatisticsAPI_{$ps_endpoint}", $vn_ttl );

		return $vm_return;
	}
	# -------------------------------------------------------

	/**
	 * @param array       $pa_config
	 * @param RequestHTTP $po_request
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function runStats( $po_request ) {
		$config = Configuration::load( __CA_CONF_DIR__ . "/services.conf" );
		$ct     = time();
		$db     = new Db();

		$time_intervals = [
			'last day'      => ( $ti_one_day = $ct - ( 24 * 60 * 60 ) ),
			'last week'     => ( $ti_one_week = $ct - ( 24 * 60 * 60 * 7 ) ),
			'last 30 days'  => ( $ti_last_30_days = $ct - ( 24 * 60 * 60 * 30 ) ),
			'last 90 days'  => ( $ti_last_90_days = $ct - ( 24 * 60 * 60 * 90 ) ),
			'last 6 months' => ( $ti_last_6_months = $ct - ( 24 * 60 * 60 * 180 ) ),
			'last year'     => ( $ti_last_6_months = $ct - ( 24 * 60 * 60 * 365 ) ),
		];

		$access_statues = caGetListItems( 'access_statuses', [ 'index' => 'item_value' ] );
		$return         = [ 'created_on' => date( 'c' ), 'access_statuses' => array_flip( $access_statues ) ];
		$counts         = [];

		foreach (
			[
				'ca_objects',
				'ca_object_lots',
				'ca_object_representations',
				'ca_entities',
				'ca_occurrences',
				'ca_places',
				'ca_collections',
				'ca_storage_locations',
				'ca_loans',
				'ca_movements',
				'ca_list_items'
			] as $t
		) {
			Datamodel::getInstance( $t, true );
			$counts['totals'][ $t ] = $t::find( '*', [ 'returnAs' => 'count' ] );
			foreach ( $access_statues as $v => $l ) {
				$counts['by_status'][ $t ][ $l ] = $t::find( '*', [ 'checkAccess' => [ $v ], 'returnAs' => 'count' ] );
			}

			$t_instance = Datamodel::getInstance( $t, true );
			$type_list  = $t_instance->getTypeList();
			foreach ( $type_list as $type_id => $type_info ) {
				$counts['by_type'][ $t ][ $type_info['idno'] ] = $t::find( [ 'type_id' => $type_id ],
					[ 'returnAs' => 'count' ] );
			}

			if ( $s = caGetSearchInstance( $t ) ) {
				foreach ( $time_intervals as $ti_label => $ti ) {
					$r                                                    = $s->search( "created:\"" . _t( 'after %1',
							date( 'c', $ti ) ) . "\"" );
					$counts['by_interval']['created'][ $t ][ $ti_label ]  = $r->numHits();
					$r                                                    = $s->search( "modified:\"" . _t( 'after %1',
							date( 'c', $ti ) ) . "\"" );
					$counts['by_interval']['modified'][ $t ][ $ti_label ] = $r->numHits();
				}
			}
		}
		$return['records']           = [];
		$return['records']['counts'] = $counts;

		// last logins
		$return['logins'] = [];
		$counts           = [];

		$users         = ca_users::find( '*', [ 'returnAs' => 'modelInstances' ] );
		$exclude_users = $config->getList( 'exclude_logins' );

		$counts['by_class'] = [];
		$counts_by_interval = [];
		$last_login         = $last_login_user = null;
		$user_count         = 0;
		foreach ( $users as $user ) {
			if ( is_array(
				     $x = array_intersect( [ $user->get( 'user_name' ), $user->get( 'email' ) ], $exclude_users ) )
			     && sizeof( $x )
			) {
				continue;
			}
			$t = $user->getVar( 'last_login' );

			foreach ( $time_intervals as $ti_label => $ti ) {
				if ( $t > $ti ) {
					$counts_by_interval[ $ti_label ] ++;
				}
			}
			if ( ! $last_login || ( $t > $last_login ) ) {
				$last_login      = $t;
				$last_login_user = $user;
			}
			$counts['by_class'][ $user->getChoiceListValue( 'userclass', $user->get( 'userclass' ) ) ] ++;
			$user_count ++;
		}

		$counts['total']       = $user_count;
		$counts['by_interval'] = $counts_by_interval;

		$return['logins']['counts'] = $counts;

		if ( $last_login_user ) {
			$return['logins']['most_recent'] = [
				'last_login'            => date( 'c', $last_login ),
				'last_login_user_fname' => $last_login_user->get( 'fname' ),
				'last_login_user_lname' => $last_login_user->get( 'lname' ),
				'last_login_user_email' => $last_login_user->get( 'email' )
			];
		}

		$return['media'] = [
			'total_size'         => $size = caGetDirectoryTotalSize( __CA_BASE_DIR__ . '/media' ),
			'total_size_display' => caHumanFileSize( $size ),
			'file_count'         => caGetFileCountForDirectory( __CA_BASE_DIR__ . '/media' ),
			'by_format'          => []
		];

		if ( $qr
			= $db->query( "SELECT count(*) c, mimetype FROM ca_object_representations WHERE deleted = 0 GROUP BY mimetype" )
		) {
			while ( $qr->nextRow() ) {
				$return['media']['by_format'][ $qr->get( 'mimetype' ) ] = $qr->get( 'c' );
			}
		}


		return $return;
	}
	# -------------------------------------------------------
}
