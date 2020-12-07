<?php
/** ---------------------------------------------------------------------
 * app/lib/MediaUrl.php :
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

namespace CA;

/**
 *
 */
require_once( __CA_LIB_DIR__ . '/Plugins/PluginConsumer.php' );

class MediaUrl extends \CA\Plugins\PluginConsumer {
	# ----------------------------------------------------------
	# Properties
	# ----------------------------------------------------------


	# ----------------------------------------------------------
	# Methods
	# ----------------------------------------------------------
	/**
	 *
	 */
	public function __construct( $no_cache = false ) {
		if ( ! self::$plugin_path ) {
			self::$plugin_path = __CA_LIB_DIR__ . '/Plugins/MediaUrl';
		}
		self::$exclusion_list = [ 'BaseMediaUrlPlugin.php' ];

		self::$name          = 'MediaUrl';
		self::$plugin_prefix = '\\CA\\MediaUrl\\Plugins\\';
	}

	# ----------------------------------------------------------

	/**
	 * Validate url
	 *
	 * @param string $url
	 * @param array  $options Options include:
	 *                        format = Suggest preferred format to plugins that can return different formats for the
	 *                        same resource (Eg. GoogleDrive). The format is only a preference and may be ignored.
	 *                        [Default is NULL] limit = Limit processing to a specific plugin or list of plugins. Note
	 *                        that the name of the default file URL handler is "_File". [Default is null; all plugins
	 *                        are used]
	 *
	 * @return array|bool False if no plugin can process the url, or an array of information about the URL on success.
	 */
	function validate( $url, $options = null ) {
		$plugin_names = $this->getPluginNames( $options );
		foreach ( $plugin_names as $plugin_name ) {
			if ( ! ( $plugin_info = $this->getPlugin( $plugin_name ) ) ) {
				continue;
			}
			if ( $f = $plugin_info['INSTANCE']->parse( $url, $options ) ) {
				return $f;
			}
		}

		return false;
	}
	# ----------------------------------------------------------

	/**
	 * Fetch contents of URL
	 *
	 * @param string $url
	 * @param array  $options Options include:
	 *                        format = Suggest preferred format to plugins that can return different formats for the
	 *                        same resource (Eg. GoogleDrive). The format is only a preference and may be ignored.
	 *                        [Default is NULL] limit = Limit processing to a specific plugin or list of plugins. Note
	 *                        that the name of the default file URL handler is "_File". [Default is null; all plugins
	 *                        are used] returnAsString = Return fetched content as string rather than in a file.
	 *                        [Default is false]
	 *
	 * @return bool|array|string False is no plugin can process the url, an array of data including the path to a file
	 *                           containing the URL contents on success, or a string with file content is the
	 *                           returnAsString option is set.
	 */
	function fetch( $url, $options = null ) {
		$plugin_names = $this->getPluginNames();
		foreach ( $plugin_names as $plugin_name ) {
			if ( ! ( $plugin_info = $this->getPlugin( $plugin_name ) ) ) {
				continue;
			}

			try {
				if ( $f = $plugin_info['INSTANCE']->fetch( $url, $options ) ) {
					return $f;
				}
			} catch ( \UrlFetchException $e ) {
				return false;
			}
		}

		return false;
	}
	# ------------------------------------------------
}
