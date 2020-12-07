<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/PluginConsumer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @subpackage Media
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\Plugins;

class PluginConsumer extends \BaseObject {
	# ----------------------------------------------------------
	/**
	 *
	 */
	public $DEBUG = false;

	/**
	 * Must be set by sub-class
	 */
	public static $name;

	/**
	 * Must be set by sub-class
	 */
	public static $plugin_prefix;


	/**
	 * Must be set by sub-class
	 */
	protected static $plugin_path;

	/**
	 *
	 */
	protected static $plugin_names;

	/**
	 *
	 */
	protected static $plugin_cache = [];

	/**
	 *
	 */
	protected static $exclusion_list = [];

	/**
	 *
	 */
	protected static $unregistered_plugin_cache = [];


	# ----------------------------------------------------------

	/**
	 * Get list of available plugin names.
	 *
	 * @param array $options Options include:
	 *                       limit = Return only specified plugins if valid. [Default is null; return all available
	 *                       plugins]
	 *
	 * @return array
	 */
	public function getPluginNames( array $options = null ) {
		$limit = caGetOption( 'limit', $options, [], [ 'castTo' => 'array' ] );
		if ( is_array( self::$plugin_names ) && ( sizeof( $limit ) === 0 ) ) {
			return self::$plugin_names;
		}

		self::$plugin_names = [];
		$dir                = opendir( self::$plugin_path );
		if ( ! $dir ) {
			throw new ApplicationException( _t( 'Cannot open plugin directory %1', self::$plugin_path ) );
		}

		$vb_binary_file_plugin_installed = false;
		while ( ( $plugin = readdir( $dir ) ) !== false ) {
			if ( ( in_array( $plugin, self::$exclusion_list, true ) )
			     || ( ( sizeof( $limit ) > 0 )
			          && ! in_array( preg_replace( '!\.php$!', '', $plugin ), $limit, true ) )
			) {
				continue;
			}
			if ( preg_match( "/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $plugin, $m ) ) {
				self::$plugin_names[] = $m[1];
			}
		}

		sort( self::$plugin_names );

		return self::$plugin_names;
	}
	# ----------------------------------------------------------

	/**
	 * Get instance of plugin.
	 *
	 * @param string $plugin_name
	 *
	 * return WLPlug Plugin instance, or null if plugin name is invalid.
	 */
	public function getPlugin( string $plugin_name ) {
		if ( ! ( $p = $this->getUnregisteredPlugin( $plugin_name ) ) ) {
			return null;
		}

		# register the plugin's capabilities
		if ( $vo_instance = $p->register() ) {
			if ( $this->DEBUG ) {
				print "[DEBUG:{self::$name}] LOADED {$plugin_name}<br>\n";
			}

			return self::$plugin_cache[ $plugin_name ] = $vo_instance;
		} else {
			if ( $this->DEBUG ) {
				print "[DEBUG:{self::$name}] DID NOT LOAD {$plugin_name}<br>\n";
			}
			self::$plugin_cache[ $plugin_name ] = false;

			return null;
		}
	}
	# ----------------------------------------------------------

	/**
	 * Instantiate plugin.
	 *
	 * @param string $plugin_name
	 *
	 * @return Plugin instance, or null if plugin name is invalid.
	 */
	private function getUnregisteredPlugin( string $plugin_name ) {
		if ( ! in_array( $plugin_name, $this->getPluginNames() ) ) {
			return null;
		}

		$plugin_dir = self::$plugin_path;

		# load the plugin
		if ( ! class_exists( self::$plugin_prefix . $plugin_name ) ) {
			if ( ! @require_once( "{$plugin_dir}/{$plugin_name}.php" ) ) {
				return null;
			}
		}
		$plugin_class = self::$plugin_prefix . $plugin_name;
		$p            = new $plugin_class();

		self::$unregistered_plugin_cache[ $plugin_name ] = $p;

		return $p;
	}
	# ----------------------------------------------------------
}
