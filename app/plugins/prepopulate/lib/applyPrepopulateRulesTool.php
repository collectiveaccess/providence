<?php
/** ---------------------------------------------------------------------
 * app/plugins/prepopulate/lib/applyPrepopulateRulesTool.php :
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
 * @subpackage AppPlugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once( __CA_LIB_DIR__ . '/Utils/BaseApplicationTool.php' );
require_once( __CA_LIB_DIR__ . '/ModelSettings.php' );
require_once( __CA_LIB_DIR__ . '/Db.php' );
require_once( __CA_MODELS_DIR__ . "/ca_storage_locations.php" );
require_once( __CA_MODELS_DIR__ . "/ca_objects.php" );
require_once( __CA_MODELS_DIR__ . "/ca_entities.php" );
require_once( __CA_MODELS_DIR__ . "/ca_collections.php" );
require_once( __CA_MODELS_DIR__ . "/ca_occurrences.php" );
require_once( __CA_MODELS_DIR__ . "/ca_users.php" );
require_once( __CA_MODELS_DIR__ . "/ca_lists.php" );
require_once( __CA_MODELS_DIR__ . "/ca_locales.php" );
require_once( __CA_MODELS_DIR__ . "/ca_data_import_events.php" );
require_once( __CA_LIB_DIR__ . '/Utils/DataMigrationUtils.php' );

class applyPrepopulateRulesTool extends BaseApplicationTool {
	# -------------------------------------------------------

	/**
	 * Settings delegate - implements methods for setting, getting and using settings
	 */
	public $SETTINGS;

	/**
	 * Name of tool. Usuall the same as the class name. Must be unique to the tool
	 */
	protected $ops_tool_name = 'Prepopulate apply rules tool';

	/**
	 * Identifier for tool. Usually the same as the class name. Must be unique to the tool.
	 */
	protected $ops_tool_id = 'applyPrepopulateRulesTool';

	/**
	 * Description of tool for display
	 */
	protected $ops_description = 'Apply configured prepopulate rules to all records';
	# -------------------------------------------------------

	/**
	 * Set up tool and settings specifications
	 */
	public function __construct( $pa_settings = null, $ps_mode = 'CLI' ) {
		$this->opa_available_settings = array(
			// 'transcript_directory' => array(
			// 					'formatType' => FT_TEXT,
			// 					'displayType' => DT_FILE_BROWSER,
			// 					'width' => 100, 'height' => 1,
			// 					'takesLocale' => false,
			// 					'default' => '1',
			// 					'label' => _t('Transcript directory'),
			// 					'description' => _t('Directory containing transcripts to import.')
			// 				),
			// 				'audio_directory' => array(
			// 					'formatType' => FT_TEXT,
			// 					'displayType' => DT_FILE_BROWSER,
			// 					'width' => 100, 'height' => 1,
			// 					'takesLocale' => false,
			// 					'default' => '1',
			// 					'label' => _t('Audio directory'),
			// 					'description' => _t('Directory containing digital audio to import.')
			// 				)
		);
		$this->prepopulateInstance    = caGetOption( 'prepopulateInstance', $pa_settings, null );

		parent::__construct( $pa_settings, $ps_mode, __CA_APP_DIR__ . '/plugins/prepopulate/conf/prepopulate.conf' );
	}
	# -------------------------------------------------------
	# Commands
	# -------------------------------------------------------
	/**
	 *
	 */
	public function commandApply_Prepopulate_Rules() {
		$o_conf = $this->getToolConfig();

		$rules = $o_conf->get( 'prepopulate_rules' );

		if ( ! $rules || ( ! is_array( $rules ) ) || ( sizeof( $rules ) < 1 ) ) {
			return false;
		}

		$tables = array_unique( array_values( array_map( function ( $v ) {
			return $v['table'];
		}, $rules ) ) );
		if ( ! is_array( $tables ) || ( sizeof( $tables ) < 1 ) ) {
			return false;
		}

		foreach ( $tables as $t ) {
			if ( $qr = $t::find( '*', [ 'returnAs' => 'searchResult' ] ) ) {
				print CLIProgressBar::start( $qr->numHits(), _t( 'Processing %1', $t ) );
				while ( $qr->nextHit() ) {
					print CLIProgressBar::next( 1, $qr->get( "{$t}.preferred_labels" ) );
					if ( ! $this->prepopulateInstance->prepopulateFields( $qr->getInstance() ) ) {
						print "ERROR\n";
					}
				}
				print CLIProgressBar::finish( "Done" );
			}
		}

		return true;
	}
	# -------------------------------------------------------
	# Help
	# -------------------------------------------------------
	/**
	 * Return short help text about a tool command
	 *
	 * @return string
	 */
	public function getShortHelpText( $ps_command ) {
		switch ( $ps_command ) {
			case 'Apply_Prepopulate_Rules':
			default:
				return _t( 'xxx.' );
		}

		return _t( 'No help available for %1', $ps_command );
	}
	# -------------------------------------------------------

	/**
	 * Return full help text about a tool command
	 *
	 * @return string
	 */
	public function getHelpText( $ps_command ) {
		switch ( $ps_command ) {
			case 'Apply_Prepopulate_Rules':
			default:
				return _t( 'yyy.' );
		}

		return _t( 'No help available for %1', $ps_command );
	}
	# -------------------------------------------------------
}

?>
