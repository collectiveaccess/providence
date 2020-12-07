<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/BaseExternalExportFormatPlugin.php : base class for external export plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2020 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package    CollectiveAccess
 * @subpackage ExternalExport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */
include_once( __CA_LIB_DIR__ . "/Plugins/WLPlug.php" );
include_once( __CA_LIB_DIR__ . "/Plugins/IWLPlugExternalExportFormat.php" );

abstract class BaseExternalExportFormatPlugin Extends WLPlug {
	# ------------------------------------------------
	// properties for this plugin instance
	protected $properties = array();

	// plugin info
	protected $info
		= [
			"NAME"       => "?",
			"PROPERTIES" => [
				'id' => 'W'
			]
		];

	# ------------------------------------------------

	/**
	 *
	 */
	public function __construct() {

	}
	# ------------------------------------------------

	/**
	 *
	 */
	public function register() {
		$this->opo_config = Configuration::load();

		$this->info["INSTANCE"] = $this;

		return $this->info;
	}
	# ------------------------------------------------

	/**
	 * Returns status of plugin. Normally this is overriden by the plugin subclass
	 *
	 * @return array - status info array; 'available' key determines if the plugin should be loaded or not
	 */
	public function checkStatus() {
		$va_status = parent::checkStatus();

		if ( $this->register() ) {
			$va_status['available'] = true;
		}

		return $va_status;
	}
	# ------------------------------------------------

	/**
	 * Process export. This *must* be overriden
	 */
	abstract public function process( $t_instance, $target_info, $options = null );
	# ------------------------------------------------

	/**
	 *
	 */
	public function init() {

		return;
	}
	# ------------------------------------------------

	/**
	 *
	 */
	public function cleanup() {
		return;
	}
	# ------------------------------------------------

	/**
	 *
	 */
	public function getAvailableSettings() {
		return [];
	}
	# ------------------------------------------------

	/**
	 * Generate file name for export using configured specification
	 *
	 * @param mixed $spec Formatting specification for filename. Can be either a template or an rray with two keys:
	 *                    "delimiter" and "components". Components is a list of display templates to be strung together
	 *                    with the delimiter.
	 * @param array $data An array of data to substitute into the file name spec.
	 *
	 * @string The processed file name
	 */
	static public function processExportFilename( $spec, $data, $instance = null ) {
		if ( is_array( $spec ) ) {
			$delimiter = caGetOption( 'delimiter', $spec, '.' );
			if ( ! is_array( $components = caGetOption( 'components', $spec, null ) ) ) {
				$components = [ $components ];
			}

			if ( $instance ) {
				$tags = array_reduce( $components, function ( $c, $v ) {
					return array_merge( $c, caGetTemplateTags( $v ) );
				}, [] );
				foreach ( $tags as $t ) {
					if ( ! ( $v = $instance->get( $t ) ) ) {
						continue;
					}
					$data[ str_replace( "^", "", $t ) ] = $v;
				}
			}

			return join( $delimiter, array_map( function ( $v ) use ( $data ) {
				return caProcessTemplate( $v, $data );
			}, $components ) );
		} else {
			// simple template
			if ( $instance ) {
				$tags = caGetTemplateTags( $spec );
				foreach ( $tags as $t ) {
					if ( ! ( $v = $instance->get( $t ) ) ) {
						continue;
					}
					$data[ str_replace( "^", "", $t ) ] = $v;
				}
			}

			return caProcessTemplate( $spec, $data );
		}
	}
	# ------------------------------------------------
	# Helpers
	# ------------------------------------------------
	/**
	 *
	 */
	public function _processFiles( $t_instance, $content_spec, $options = null ) {
		$file_list      = [];
		$file_mimetypes = [];
		$total_filesize = 0;

		if ( $relative_to = caGetOption( 'relativeTo', $content_spec, null ) ) {
			// TODO: support children, parent, hierarchy
			$instance_list = $t_instance->getRelatedItems( $relative_to, [ 'returnAs' => 'modelInstances' ] );
		} else {
			$instance_list = [ $t_instance ];
		}

		$file_list_template = caGetOption( 'file_list_template', $target_options, '' );

		$restrict_to_types     = caGetOption( 'restrictToTypes', $content_spec, null );
		$restrict_to_mimetypes = caGetOption( 'restrictToMimeTypes', $content_spec, null );

		$media_index = caGetOption( 'mediaIndex', $options, null );

		foreach ( $instance_list as $t ) {
			if ( is_array( $restrict_to_types ) && sizeof( $restrict_to_types )
			     && ! in_array( $t->getTypeCode(), $restrict_to_types )
			) {
				continue;
			}
			foreach ( $content_spec['files'] as $get_spec => $export_filename_spec ) {
				$pathless_spec = preg_replace( '!\.path$!', '', $get_spec );
				if ( ! preg_match( "!\.path$!", $get_spec ) ) {
					$get_spec .= ".path";
				}

				$filenames      = $t->get( "{$pathless_spec}.filename",
					[ 'returnAsArray' => true, 'filterNonPrimaryRepresentations' => false ] );
				$mimetypes      = $t->get( "{$pathless_spec}.mimetype",
					[ 'returnAsArray' => true, 'filterNonPrimaryRepresentations' => false ] );
				$file_mod_times = $t->get( "{$pathless_spec}.fileModificationTime",
					[ 'returnAsArray' => true, 'filterNonPrimaryRepresentations' => false ] );

				$files = $t->get( $get_spec, [ 'returnAsArray' => true, 'filterNonPrimaryRepresentations' => false ] );

				$seen_files = [];
				foreach ( $files as $i => $f ) {
					if ( ! is_null( $media_index ) && ( (int) $i !== (int) $media_index ) ) {
						continue;
					}
					$m = $mimetypes[ $i ];
					$t = $file_mod_times[ $i ];
					if ( is_array( $restrict_to_mimetypes ) && sizeof( $restrict_to_mimetypes )
					     && ! sizeof( array_filter( $restrict_to_mimetypes, function ( $v ) use ( $m ) {
							return caCompareMimetypes( $m, $v );
						} ) )
					) {
						continue;
					}

					$extension         = pathinfo( $f, PATHINFO_EXTENSION );
					$original_basename = pathinfo( $filenames[ $i ], PATHINFO_FILENAME );
					$basename          = pathinfo( $f, PATHINFO_FILENAME );

					$e = $export_filename = self::processExportFilename( $export_filename_spec, [
						'extension'         => $extension,
						'original_filename' => $original_basename ? "{$original_basename}.{$extension}"
							: "media_{$i}.{$extension}",
						'original_basename' => $original_basename,
						'filename'          => "{$basename}.{$extension}",
						"basename"          => $basename,
					] );

					$file_mimetypes[ $m ] = true;

					// Detect and rename duplicate file names
					$c = 1;
					while ( isset( $seen_files[ $e ] ) && $seen_files[ $e ] ) {
						$e = pathinfo( $export_filename, PATHINFO_FILENAME ) . "-{$c}.{$extension}";
						$c ++;
					}
					$total_filesize += ( $fs = filesize( $f ) );

					$seen_files[ $export_filename ] = true;

					$d = [
						'path'                 => $f,
						'name'                 => $e,
						'filemodtime'          => $t,
						'filename'             => $original_basename ? "{$original_basename}.{$extension}"
							: "{$basename}.{$extension}",
						'filesize_in_bytes'    => $fs,
						'filesize_for_display' => caHumanFilesize( $fs ),
						'mimetype'             => $m
					];
					if ( $file_list_template && ! isset( $file_list[ $export_filename ] ) ) {
						$file_list_template_proc = caProcessTemplate( $file_list_template, [
							'filename'             => $original_basename ? "{$original_basename}.{$extension}"
								: "{$basename}.{$extension}",
							'filesize_in_bytes'    => $fs,
							'filesize_for_display' => caHumanFilesize( $fs ),
							'mimetype'             => $m
						], [ 'skipTagsWithoutValues' => true ] );

						$file_list[ $export_filename ]
							= $t_instance->getWithTemplate( caProcessTemplate( $file_list_template, $d,
							[ 'skipTagsWithoutValues' => true ] ) );
					}
					$d['file_list'] = $t_instance->getWithTemplate( $file_list_template_proc );

					$file_list[ $export_filename ] = $d;
				}
			}
		}

		return [ 'fileList' => $file_list, 'totalFileSize' => $total_filesize, 'fileMimeTypes' => $file_mimetypes ];
	}
	# ------------------------------------------------
}
