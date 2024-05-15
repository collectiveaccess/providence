<?php
/** ---------------------------------------------------------------------
 * app/lib/Configuration.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2020 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */


/**
 * Parses and provides access to application configuration files.
 *
 * A configuration file can contain any number of key-value pairs. Keys are simple
 * alphanumeric text expressions. Values may be one of three types:
 *
 * - Scalar: a string or number. Strings are always unquoted and may contain any character.
 * - List: a list of strings or numbers separated by commas and enclosed in square brackets ("[" and "]"). A string must be enclosed in double quotes if it contains a comma. You may not place the double quote character in a list item. Lists are retrievable as indexed PHP arrays. Lists may not be nested.
 * - Associative array: a list of key-value pairs. Both keys and values must be enclosed in double quotes if they contain commas. Neither may contain double quotes. Associative arrays are enclosed with curly brackets ("{" and "}"). Separate keys from values with "=" Separate key-value pairs from each other using commas. Values may be strings, numbers or nested associative arrays. Associative arrays may be nested to any depth.
 *
 * Keys are always separated from values by "=" You may place as many spaces as you like on either side of the "=" character.
 *
 * Both lists and associative array may span as many lines as necessary.
 *
 * @example docs/Configuration1.example Example configuration file
 */
class Configuration {
	/**
	 * Contains parsed configuration values
	 *
	 * @access private
	 */
	private $ops_config_settings;

	/**
	 * Error message
	 *
	 * @access private
	 */
	private $ops_error="";		#  error message - blank if no error

	/**
	 * Absolute path to configuration file
	 *
	 * @access private
	 */
	private $ops_config_file_path;

	/**
	 * Display debugging info
	 *
	 * @access private
	 */
	private $opb_debug = false;
	
	/**
	 * MD5 hash for current configuration file path
	 *
	 * @access private
	 */
	private $ops_md5_path;

	static $s_get_cache;
	static $s_config_cache = null;
	static $s_have_to_write_config_cache = false;
	

	/* ---------------------------------------- */
	/**
	 * Load a configuration file
	 *
	 * @param string $ps_file_path
	 * @param bool $pb_dont_cache Don't use config file cached. [Default is false]
	 * @param bool $pb_dont_cache_instance Don't attempt to cache config file Configuration instance. [Default is false]
	 * @param bool $pb_dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 * @return Configuration
	 */
	static function load($ps_file_path=__CA_APP_CONFIG__, $pb_dont_cache=false, $pb_dont_cache_instance=false, $pb_dont_load_from_default_path=false) {
		if(!$ps_file_path) { $ps_file_path = __CA_APP_CONFIG__; }

		if(!MemoryCache::contains($ps_file_path, 'ConfigurationInstances') || $pb_dont_cache || $pb_dont_cache_instance) {
			MemoryCache::save($ps_file_path, new Configuration($ps_file_path, true, $pb_dont_cache, $pb_dont_load_from_default_path), 'ConfigurationInstances');
		}

		return MemoryCache::fetch($ps_file_path, 'ConfigurationInstances');
	}
	/* ---------------------------------------- */
	/**
	 * Load a configuration file. In addition to the parameters described below two global variables can also affect loading:
	 *
	 *		$g_ui_locale - if it contains the current locale code, this code will be used when computing the MD5 signature of the current configuration for caching purposes. By setting this to the current locale simultaneous caching of configurations for various locales (eg. config files with gettext-translated strings in them) is enabled.
	 *		$g_configuration_cache_suffix - any text it contains is used along with the configuration path and $g_ui_locale to compute the MD5 signature of the current configuration for caching purposes. By setting this to some value you can support simultaneous caching of configurations for several different modes. This is mainly used to support caching of theme-specific configurations. Since the theme can change based upon user agent, we need to potentially keep several computed configurations cached at the same time, one for each theme used.
	 *
	 * @param string $ps_file_path Absolute path to configuration file to parse
	 * @param bool $pb_die_on_error If true, request processing will halt with call to die() on error in parsing config file. [Default is false]
	 * @param bool $pb_dont_cache If true, file will be parsed even if it's already cached. [Default is false]
	 * @param bool $pb_dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 *
	 *
	 */
	public function __construct($ps_file_path=__CA_APP_CONFIG__, $pb_die_on_error=false, $pb_dont_cache=false, $pb_dont_load_from_default_path=false) {
		global $g_ui_locale, $g_configuration_cache_suffix;

		$this->ops_config_file_path = $ps_file_path ? $ps_file_path : __CA_APP_CONFIG__;	# path to configuration file
		
		$va_config_file_list = [];
		
		// cache key for on-disk caching
		$vs_path_as_md5 = md5(($_SERVER['HTTP_HOST'] ?? '') . $this->ops_config_file_path.'/'.$g_ui_locale.(isset($g_configuration_cache_suffix) ? '/'.$g_configuration_cache_suffix : ''));

		#
		# Is configuration file already cached?
		#
		$va_config_path_components = explode("/", $this->ops_config_file_path);
		$vs_config_filename = array_pop($va_config_path_components);


        $vs_top_level_config_path = $this->ops_config_file_path;
		if (!$pb_dont_load_from_default_path) {
			if (defined('__CA_LOCAL_CONFIG_DIRECTORY__') && file_exists(__CA_LOCAL_CONFIG_DIRECTORY__.'/'.$vs_config_filename)) {
				$va_config_file_list[] = $vs_top_level_config_path = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.$vs_config_filename;
			} 
			
			// Theme config overrides local config
			if (defined('__CA_DEFAULT_THEME_CONFIG_DIRECTORY__') && file_exists(__CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$vs_config_filename)) {
				$va_config_file_list[] = $vs_top_level_config_path = __CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$vs_config_filename;
			}
			
			// Appname-specific config overrides local config
			$appname_specific_path = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.pathinfo($vs_config_filename, PATHINFO_FILENAME).'_'.__CA_APP_NAME__.'.'.pathinfo($vs_config_filename, PATHINFO_EXTENSION);
			if (defined('__CA_LOCAL_CONFIG_DIRECTORY__') && file_exists($appname_specific_path)) {
				$va_config_file_list[] = $vs_top_level_config_path = $appname_specific_path;
			}
		}
		$o_config = ($vs_top_level_config_path === $this->ops_config_file_path) ? $this : Configuration::load($vs_top_level_config_path, false, false, true);

		$vs_filename = pathinfo($ps_file_path, PATHINFO_BASENAME);
		
		$app_config = ($vs_filename !== 'app.conf') ? Configuration::load(__CA_APP_CONFIG__) : $o_config;
		if (($vb_inherit_config = $app_config->get(['allowThemeInheritance', 'allow_theme_inheritance'])) && !$pb_dont_load_from_default_path) {
		    $i=0;
            while($vs_inherit_from_theme = trim(trim($app_config->get(['inheritFrom', 'inherit_from'])), "/")) {
                $i++;
                $vs_inherited_config_path = __CA_THEMES_DIR__."/{$vs_inherit_from_theme}/conf/{$vs_filename}";
                if (file_exists($vs_inherited_config_path) && !in_array($vs_inherited_config_path, $va_config_file_list) && ($vs_inherited_config_path !== $this->ops_config_file_path)) {
                    array_unshift($va_config_file_list, $vs_inherited_config_path);
                }
                if(!file_exists(__CA_THEMES_DIR__."/{$vs_inherit_from_theme}/conf/app.conf")) { break; }
                $o_config = Configuration::load(__CA_THEMES_DIR__."/{$vs_inherit_from_theme}/conf/app.conf", false, false, true);
                if ($i > 10) { break; } // max 10 levels
            }
		}
		array_unshift($va_config_file_list, $this->ops_config_file_path);

		// try to figure out if we can get it from cache
		$mtime_keys = [];
		if((!defined('__CA_DISABLE_CONFIG_CACHING__') || !__CA_DISABLE_CONFIG_CACHING__) && !$pb_dont_cache) {
			if($vb_setup_has_changed = caSetupPhpHasChanged()) {
				self::clearCache();
			} elseif(ExternalCache::contains($vs_path_as_md5, 'ConfigurationCache')) {	// try to load current file from cache
				self::$s_config_cache[$vs_path_as_md5] = ExternalCache::fetch($vs_path_as_md5, 'ConfigurationCache');
			}

			if(!$vb_setup_has_changed && isset(self::$s_config_cache[$vs_path_as_md5])) {	// file has been loaded from cache
				$vb_cache_is_invalid = false;
				
				// Check file times to make sure cache is not stale
				foreach($va_config_file_list as $vs_config_file_path) {
					$mtime_keys[] = $mtime_key = 'mtime_'.$vs_path_as_md5.md5($vs_config_file_path);
					$mtime = ExternalCache::fetch($mtime_key, 'ConfigurationCache');
					
				    $vs_config_mtime = caGetFileMTime($vs_config_file_path);
                    if($vs_config_mtime != $mtime) { // config file has changed
                        self::$s_config_cache[$mtime_key] = $vs_config_mtime;
                        $vb_cache_is_invalid = true;
                        break;
                    }
				}

				if (!$vb_cache_is_invalid) { // cache is ok
					$this->ops_config_settings = self::$s_config_cache[$vs_path_as_md5];
					$this->ops_md5_path = md5($this->ops_config_file_path);
					return;
				}
			}

		}

		# File contents 
		$this->ops_config_settings = [];

		# try loading global.conf file
		$vs_global_path = join("/", $va_config_path_components).'/global.conf';
		if (file_exists($vs_global_path)) { $this->loadFile($vs_global_path, false); }

		//
		// Insert current user locale as constant into configuration.
		//
		$this->ops_config_settings['scalars']['LOCALE'] = $g_ui_locale;

		//
		// Load specified config file(s), overlaying each 
		//
		$vs_config_file_path = array_shift($va_config_file_list);
		if (file_exists($vs_config_file_path) && $this->loadFile($vs_config_file_path, false, false)) {
			$this->ops_config_settings["ops_config_file_path"] = $vs_config_file_path;
		}
		
		
        if (sizeof($va_config_file_list) > 0) {
            foreach($va_config_file_list as $vs_config_file_path) {
                if (file_exists($vs_config_file_path)) {
                    $this->loadFile($vs_config_file_path, false, false, true);
                }
            }
        }

		if($vs_path_as_md5 && !$pb_dont_cache) {
			self::$s_config_cache[$vs_path_as_md5] = $this->ops_config_settings;
			// we loaded this cfg from file, so we have to write the
			// config cache to disk at least once on this request
			self::$s_have_to_write_config_cache = true;
			
			// Write file to cache
			ExternalCache::save($vs_path_as_md5, self::$s_config_cache[$vs_path_as_md5], 'ConfigurationCache', 3600 * 3600 * 30);
			
			// Write mtimes to cache for each component file (local, theme, stock)
			if (is_array($mtime_keys) && sizeof($mtime_keys)) {
				foreach($mtime_keys as $k) {
					ExternalCache::save($k, self::$s_config_cache[$k] ?? null, 'ConfigurationCache', 3600 * 3600 * 30);
				}
			}
		}
	}
	/* ---------------------------------------- */
	/**
	 * Parses configuration file located at $ps_file_path.
	 *
	 * @param $ps_filepath - absolute path to configuration file to parse
	 * @param $pb_die_on_error - if true, die() will be called on parse error halting request; default is false
	 * @param $pn_num_lines_to_read - if set to a positive integer, will abort parsing after the first $pn_num_lines_to_read lines of the config file are read. This is useful for reading in headers in config files without having to parse the entire file.
	 * @return boolean - returns true if parse succeeded, false if parse failed
	 */
	public function loadFile($ps_filepath, $pb_die_on_error=false, $pn_num_lines_to_read=null) {
		$this->ops_md5_path = md5($ps_filepath);
		$this->ops_error = "";
		$r_file = @fopen($ps_filepath,"r", true);
		if (!$r_file) {
			$this->ops_error = "Couldn't open configuration file '".$ps_filepath."'";
			if ($pb_die_on_error) { $this->_dieOnError(); }
			return false;
		}

		$vs_key = $vs_scalar_value = $vs_assoc_key = "";
		$vn_in_quote = $vn_state = 0;
		$vb_escape_set = $vb_quoted_item_is_closed = false;
		$va_assoc_pointer_stack = array();

		$va_token_history = array();
		$vn_line_num = 0;
		$vb_merge_mode = false;
		while (!feof($r_file)) {
			$vn_line_num++;

			if (($pn_num_lines_to_read > 0) && ($vn_line_num > $pn_num_lines_to_read)) { break; }
			$vs_buffer = trim(fgets($r_file, 32000));
			if($vn_in_quote) { $vs_buffer .= "\n"; }

			# skip comments (start with '#') or blank lines
			if (strtolower(substr($vs_buffer,0,7)) == '#!merge') { $vb_merge_mode = true; }
			if (strtolower(substr($vs_buffer,0,9)) == '#!replace') { $vb_merge_mode = false; }
			if (!$vs_buffer || (substr($vs_buffer,0,1) === "#")) { continue; }

			$va_token_tmp = preg_split("/([={}\[\]\",\\\]){1}/", $vs_buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

			// eliminate blank tokens
			$va_tokens = array();
			$vn_tok_count = sizeof($va_token_tmp);
			for($vn_i = 0; $vn_i < $vn_tok_count; $vn_i++) {
				if (strlen($va_token_tmp[$vn_i])) {
					$va_tokens[] =& $va_token_tmp[$vn_i];
				}
			}
			while (sizeof($va_tokens)) {
				$vs_token = array_shift($va_tokens);

				$va_token_history[] = $vs_token;
				if (sizeof($va_token_history) > 50) { array_shift($va_token_history); }
				switch($vn_state) {
					# ------------------------------------
					# init
					case -1:
						$vs_key = $vs_assoc_key = $vs_scalar_value = "";
						$vn_in_quote = 0;
						$va_assoc_pointer_stack = array();

						$vn_state = 0;

					# ------------------------------------
					# looking for key
					case 0:
						if ($vs_token != "=") {
							$vs_key .= $vs_token;
						} else {
							$vn_got_key = 1;
							$vs_key = trim($vs_key);

							$vn_state = 10;
						}
						break;
					# ------------------------------------
					# determine type of value
					case 10:
						switch($vs_token) {
							case '[':
								if(!is_array($this->ops_config_settings["lists"][$vs_key] ?? null) || !$vb_merge_mode) {
									$this->ops_config_settings["lists"][$vs_key] = array();
								}
								$vn_state = 30;
								break;
							case '{':
								if(!is_array($this->ops_config_settings["assoc"][$vs_key] ?? null) || !$vb_merge_mode) {
									$this->ops_config_settings["assoc"][$vs_key] = array();
								}
								$va_assoc_pointer_stack[] =& $this->ops_config_settings["assoc"][$vs_key];
								$vn_state = 40;
								break;
							case '"':
								if($vn_in_quote) {
									$vn_in_quote = 0;
									$vn_state = -1;
								} else {
									$vs_scalar_value = '';
									$vn_in_quote = 1;
									$vn_state = 20;
								}
								break;
							default:
								// strip leading exclaimation in scalar to allow scalars to start with [ or {
								if (trim($vs_token) == '!') {
									$vs_token = array_shift($va_tokens);
								}
								if (!preg_match("/^[ \t]*$/", $vs_token)) {
									$vs_scalar_value .= $vs_token;
									$vn_state = 20;

									if(!$vn_in_quote) {
										if (sizeof($va_tokens) == 0) {
											$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
											$vn_state = -1;
										}
									}
								}
								break;
						}
						break;
					# ------------------------------------
					# handle scalar values
					case 20:
						// end quote? -> accept scalar
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set || (!$vn_in_quote && strlen($vs_scalar_value))) {	// Quote in interior of scalar - assume literal
									$vs_scalar_value .= '"';
									if(sizeof($va_tokens) == 0) {
										$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
										$vn_in_quote = 0;
										$vb_escape_set = false;
										$vn_state = -1;
									}
								} else {
									if (!$vn_in_quote) {	// Quoted scalar
										$vn_in_quote = 1;
									} else {
										// Accept quoted scalar
										$vn_in_quote = 0;
										$vb_escape_set = false;
										$vn_state = -1;

										$this->ops_config_settings["scalars"][$vs_key] = $vs_scalar_value;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
									$vb_escape_set = false;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								if (preg_match("/[\r\n]/", $vs_token) && !$vn_in_quote) {	// Return ends scalar
									$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
									$vn_in_quote = 0;
									$vb_escape_set = false;
									$vn_state = -1;
								} elseif ((sizeof($va_tokens) == 0) && !$vn_in_quote) {
									$vs_scalar_value .= $vs_token;
									$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
									$vn_in_quote = 0;
									$vb_escape_set = false;
									$vn_state = -1;
								} else { # keep going to next line
									$vs_scalar_value .= $vs_token;
									$vn_state = 20;
								}
								break;
							# -------------------
						}
						break;
					# ------------------------------------
					# handle list values
					case 30:
					    if($vb_quoted_item_is_closed && (!in_array(trim($vs_token), [',', ']', ')']))) { break; }
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
										$vb_quoted_item_is_closed = true;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$vs_key][] = $vs_item;
									}
									$vs_scalar_value = "";
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set  = false;
								break;
							# -------------------
							case ']':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "]";
								} else {
									# accept list
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$vs_key][] = $vs_item;
									}
									# initialize
									$vn_state = -1;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($va_tokens) == 0) && ($vn_in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$vs_key'";
							fclose($r_file);
							if ($pb_die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------
					# handle associative array values
					# get associative key
					case 40:
					    if($vb_quoted_item_is_closed && (!in_array(trim($vs_token), [',', '=', '}', ')']))) { break; }
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_assoc_key .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
									    $vb_quoted_item_is_closed = true;
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '=':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_assoc_key .= "=";
								} else {
									if (($vs_assoc_key = trim($this->_interpolateScalar($vs_assoc_key))) == '') {
										$this->ops_error = "Associative key must not be empty";
										fclose($r_file);

										if ($pb_die_on_error) { $this->_dieOnError(); }
										return false;
									}
                                    
									$vn_state = 50;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_assoc_key .= ",";
								} else {
									if ($vs_assoc_key) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
									}
									$vs_assoc_key = "";
									$vs_scalar_value = "";
									$vn_state = 40;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '}':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_assoc_key .= "}";
								} else {
									if (sizeof($va_assoc_pointer_stack) > 1) {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
										}
										array_pop($va_assoc_pointer_stack);

										$vn_state = 40;
									} else {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
										}
										$vn_state = -1;
									}
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_assoc_key .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								if (preg_match("/^#/", trim($vs_token))) {
									// comment
								} else {
									$vb_escape_set = false;
									$vs_assoc_key .= $vs_token;
								}
								break;
							# -------------------
						}

						break;
					# ------------------------------------
					# handle associative value
					case 50:
					    if($vb_quoted_item_is_closed && (!in_array(trim($vs_token), [',', '{', '}', ',', ')']))) { break; }
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
									    if (preg_match("!^[ \t\n\r]+$!", $vs_scalar_value)) { $vs_scalar_value = ''; }
										$vn_in_quote = 1;
									} else {
									    $vb_quoted_item_is_closed = true;
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if ($vs_assoc_key) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
									}
									$vs_assoc_key = "";
									$vs_scalar_value = "";
									$vn_state = 40;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							# open nested associative value
							case '{':
								if (!$vn_in_quote && !$vb_escape_set) {
									$i = sizeof($va_assoc_pointer_stack) - 1;
									if (!isset($va_assoc_pointer_stack[$i]) || !isset($va_assoc_pointer_stack[$i][$vs_assoc_key]) || !is_array($va_assoc_pointer_stack[$i][$vs_assoc_key]) || !$vb_merge_mode) {
										$va_assoc_pointer_stack[$i][$vs_assoc_key] = array();
									}
									$va_assoc_pointer_stack[] =& $va_assoc_pointer_stack[$i][$vs_assoc_key];
									
									$vn_state = 40;
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
									$vb_quoted_item_is_closed = false;
								} else {
									$vs_scalar_value .= $vs_token;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '}':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "}";
								} else {
									if (sizeof($va_assoc_pointer_stack) > 1) {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
										}
										array_pop($va_assoc_pointer_stack);

										$vn_state = 40;
									} else {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
										}
										$vn_state = -1;
									}
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							# open list
							case '[':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$i = sizeof($va_assoc_pointer_stack) - 1;
									if(!is_array($va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] ?? null) || !$vb_merge_mode) {
										$va_assoc_pointer_stack[$i][$vs_assoc_key] = array();
									}
									$va_assoc_pointer_stack[] =& $va_assoc_pointer_stack[$i][$vs_assoc_key];
									$vn_state = 60;
									$vn_in_quote = 0;
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						break;
					# ------------------------------------
					# handle list values nested in assoc
					case 60:
					    if($vb_quoted_item_is_closed && (!in_array(trim($vs_token), [',', ']', ')']))) { break; }
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
										$vb_quoted_item_is_closed = true;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = $vs_item;
									}
									$vs_scalar_value = "";
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ']':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "]";
								} else {
									# accept list
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = $vs_item;
									}
									array_pop($va_assoc_pointer_stack);
									# initialize
									$vn_state = 40;
									$vs_assoc_key = '';
									$vb_quoted_item_is_closed = false;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($va_tokens) == 0) && ($vn_in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$vs_key'";
							fclose($r_file);
							if ($pb_die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------

				}
			}
			if ((($vn_state == 10) || ($vn_state == 20)) && !$vn_in_quote) {
				$this->ops_config_settings["scalars"][$vs_key] = "";
				$vn_state = -1;
			}

			if(in_array($vn_state, [10,20]) && $vn_in_quote) {
				$vs_scalar_value .= "\n";
			}

			if ($vn_in_quote && !in_array($vn_state, [10,20])) {
				switch($vn_state) {
					case 30:
						// $this->ops_error = "Missing trailing quote in list '$vs_key'<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
 						//break;
						continue(2);	// allow multiline quoted entries
					case 40:
					case 50:
						//$this->ops_error = "Missing trailing quote in associative array '$vs_key'<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
						//break;
						continue(2);	// allow multiline quoted entries
					default:
						$this->ops_error = "Missing trailing quote in '$vs_key' [Last token was '{$vs_token}'; state was $vn_state]<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
						break;
				}
				fclose($r_file);

				if ($pb_die_on_error) { $this->_dieOnError(); }
				return false;
			}
		}

		if ($vn_state > 0) {
			$this->ops_error = "Syntax error in configuration file: missing { or } [state=$vn_state]<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
			fclose($r_file);

			if ($pb_die_on_error) { $this->_dieOnError(); }
			return false;
		}

		// interpolate scalars
		if (isset($this->ops_config_settings["scalars"]) && is_array($this->ops_config_settings["scalars"])) {
			foreach($this->ops_config_settings["scalars"] as $vs_key => $vs_val) {
				$this->ops_config_settings["scalars"][$vs_key] = $this->_interpolateScalar($vs_val);
			}
		}
		fclose($r_file);

		return true;
	}
	/* ---------------------------------------- */
	private function _formatTokenHistory($pa_token_history, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_output = '';
		if (isset($pa_options['outputAsHTML']) && $pa_options['outputAsHTML']) {
			$vs_output = "<pre>";
			for($vn_i=1; $vn_i <=sizeof($pa_token_history); $vn_i++) {
				$vs_output .= "\t[{$vn_i}] ".$pa_token_history[$vn_i-1]."\n";
			}
			$vs_output .= "</pre>";
		} else {
			if (!isset($pa_options['delimiter'])) { $vs_delimiter = ';'; } else { $vs_delimiter = $pa_options['delimiter']; }
			$vs_output = join($vs_delimiter, $pa_token_history);
		}
		return $vs_output;
	}
	/* ---------------------------------------- */
	/**
	 * Get configuration value
	 *
	 * @param mixed $pm_key Name of configuration key to fetch. get() will look for the
	 * key first as a scalar, then as a list and finally as an associative array.
	 * The first value found is returned. If an array of values are passed get() will try 
	 * each key in turn until a value is found.
	 *
	 * @return mixed A string, indexed array (list) or associative array, depending upon what
	 * kind of configuration value was found. If no value is found null is returned.
	 */
	public function get($pm_key) {
	    $assoc_exists = false;
	    if (!is_array($pm_key)) { $pm_key = [$pm_key]; }
	    
	    foreach($pm_key as $ps_key) {
            if (isset(Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]) && Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]) { return Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]; }
            $this->ops_error = "";

            $vs_tmp = $this->getScalar($ps_key);
            if (!strlen($vs_tmp)) {
                $vs_tmp = $this->getList($ps_key);
            }
            if (!is_array($vs_tmp) && !strlen($vs_tmp)) {
                if (is_array($vs_tmp = $this->getAssoc($ps_key))) { $assoc_exists = true; }
            }
            Configuration::$s_get_cache[$this->ops_md5_path][$ps_key] = $vs_tmp;
            
            if (!is_array($vs_tmp) && !strlen($vs_tmp)) { continue; }
            return $vs_tmp;
        }
        return $assoc_exists ? [] : null;
	}
	/* ---------------------------------------- */
	/**
	 * Determine if specified key is present in the configuration file.
	 *
	 * @param string $ps_key Name of configuration value.
	 *
	 * @return bool
	 */
	public function exists($ps_key) {
		if (isset(Configuration::$s_get_cache[$this->ops_md5_path][$ps_key])) { return true; }
		$this->ops_error = "";

		if (array_key_exists($ps_key, $this->ops_config_settings["scalars"])) { return true; }
		if (array_key_exists($ps_key, $this->ops_config_settings["lists"])) { return true; }
		if (array_key_exists($ps_key, $this->ops_config_settings["assoc"])) { return true; }
		
		return false;
	}
	/* ---------------------------------------- */
	/**
	 * Get boolean configuration value
	 *
	 * @param string $ps_key Name of configuration value to get. getBoolean() will look for the
	 * configuration value only as a scalar, and return boolean 'true' if the scalar value is
	 * either 'yes', 'true' or '1'.
	 *
	 * @return boolean
	 */
	public function getBoolean($ps_key) {
		$vs_tmp = strtolower($this->getScalar($ps_key));
		if(($vs_tmp == "yes") || ($vs_tmp == "true") || ($vs_tmp == "1")) {
			return true;
		} else {
			return false;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get scalar configuration value
	 *
	 * @param string $ps_key Name of scalar configuration value to get. get() will look for the
	 * configuration value only as a scalar. Like-named list or associative array values are
	 * ignored.
	 *
	 * @return string
	 */
	public function getScalar($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["scalars"][$ps_key])) {
			return $this->ops_config_settings["scalars"][$ps_key];
		} else {
			return false;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for scalar values
	 *
	 *
	 * @return array List of all possible keys for scalar values
	 */
	public function getScalarKeys() {
		$this->ops_error = "";
		return array_keys($this->ops_config_settings["scalars"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for list values
	 *
	 *
	 * @return array List of all possible keys for list values
	 */
	public function getListKeys() {
		$this->ops_error = "";
		return array_keys($this->ops_config_settings["lists"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for associative values
	 *
	 *
	 * @return array List of all possible keys for associative values
	 */
	public function getAssocKeys() {
		$this->ops_error = "";
		return @array_keys($this->ops_config_settings["assoc"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get list configuration value
	 *
	 * @param string $ps_key Name of list configuration value to get. get() will look for the
	 * configuration value only as a list. Like-named scalar or associative array values are
	 * ignored.
	 *
	 * @return array An indexed array
	 */
	public function getList($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["lists"][$ps_key])) {
			if (is_array($this->ops_config_settings["lists"][$ps_key])) {
				return $this->ops_config_settings["lists"][$ps_key];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get associative configuration value
	 *
	 * @param string $ps_key Name of associative configuration value to get. get() will look for the
	 * configuration value only as an associative array. Like-named scalar or list values are
	 * ignored.
	 *
	 * @return array An associative array
	 */
	public function getAssoc($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["assoc"][$ps_key])) {
			if (is_array($this->ops_config_settings["assoc"][$ps_key])) {
				return $this->ops_config_settings["assoc"][$ps_key];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Return currently loaded configuration file as JSON
	 *
	 * @return string
	 */
	public function toJson() {
		$config = array_merge(
			is_array($this->ops_config_settings["scalars"]) ? $this->ops_config_settings["scalars"] : [], 
			is_array($this->ops_config_settings["lists"]) ? $this->ops_config_settings["lists"] : [], 
			is_array($this->ops_config_settings["assoc"]) ? $this->ops_config_settings["assoc"] : []
		);	
		return caFormatJson(json_encode($config));
	}
	/* ---------------------------------------- */
	/**
	 * Validate currently loaded configuration file against schema
	 *
	 * @return Opis\JsonSchema\ValidationResult Returns null if schema could not be loaded, either because it is invalid or does not exist.
	 */
	public function validate() {
		$f = pathinfo($this->ops_config_file_path, PATHINFO_BASENAME);
		
		$v = new \Opis\JsonSchema\Validator();	
		$loader = new \Opis\JsonSchema\Loaders\File("https://collectiveaccess.org", [__CA_LIB_DIR__."/Configuration/".ucfirst(strtolower(__CA_APP_TYPE__))."/schemas"]);
		if (!($schema = $loader->loadSchema("https://collectiveaccess.org/{$f}.schema.json"))) { return null; } 	// no schema loaded

		$result = $v->schemaValidation(json_decode($this->toJson()), $schema);
		
		return $result;
	}
	/* ---------------------------------------- */
	/**
	 * Find out if there was an error processing the configuration file
	 *
	 * @return bool Returns true if error occurred, false if not
	 */
	public function isError() {
		return ($this->ops_error) ? true : false;
	}
	/* ---------------------------------------- */
	/**
	 * Get error message
	 *
	 * @return string Returns user-displayable error message
	 */
	public function getError() {
		return $this->ops_error;
	}
	/* ---------------------------------------- */
	private function _trimScalar($ps_scalar_value) {
		if (preg_match("/^[ ]+$/", $ps_scalar_value)) {
			$ps_scalar_value = " ";
		} else {
			$ps_scalar_value = trim($ps_scalar_value);
		}
		// perform constant var substitution
		if (preg_match("/^(__[A-Za-z0-9\_]+)(?=__)/", $ps_scalar_value, $va_matches)) {
			if (defined($va_matches[1].'__')) {
				return str_replace($va_matches[1].'__', constant($va_matches[1].'__'), $ps_scalar_value);
			}
		}
		return $ps_scalar_value;
	}
	/* ---------------------------------------- */
	private function _dieOnError() {
		die("Error loading configuration file '".$this->ops_config_file_path."': ".$this->ops_error."\n");
	}
	/* ---------------------------------------- */
	private function _interpolateScalar($ps_text) {
		if (preg_match_all("/<([A-Za-z0-9_\-\.]+)>/", $ps_text, $va_matches)) {
			foreach($va_matches[1] as $vs_key) {
				if (($vs_val = $this->getScalar($vs_key)) !== false) {
					$ps_text = preg_replace("/<$vs_key>/", $vs_val, $ps_text);
				}
			}
		}

		// attempt translation if text is enclosed in _( and ) ... for example _t(translate me)
		// assumes translation function _t() is present; if not loaded will not attempt translation
		if (function_exists('_t') && preg_match("/(?<=\s|>|^)_\(([^\"\)]+)\)/", $ps_text, $va_matches)) {
			$vs_trans_text = $ps_text;
			array_shift($va_matches);
			foreach($va_matches as $vs_match) {
				$vs_trans_text = str_replace("_({$vs_match})", _t($vs_match), $vs_trans_text);
			}
			return $vs_trans_text;
		}
		return $ps_text;
	}
	/* ---------------------------------------- */
	/**
	 * Remove all cached configuration
	 */
	public static function clearCache() {
		ExternalCache::flush('ConfigurationCache');
		self::$s_config_cache = null;
	}
	/* ---------------------------------------- */
	/**
	 * Destructor: Save config cache to disk/external provider
	 */
	public function __destruct() {
		if(isset(Configuration::$s_have_to_write_config_cache) && Configuration::$s_have_to_write_config_cache) {
			foreach(self::$s_config_cache as $k => $v) {
				ExternalCache::save($k, $v, 'ConfigurationCache', 3600 * 3600 * 30);
			}
			self::$s_have_to_write_config_cache = false;
		}
	}
	# ---------------------------------------------------------------------------
}
