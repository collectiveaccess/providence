<?php
/** ---------------------------------------------------------------------
 * app/lib/Configuration.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2025 Whirl-i-Gig
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
	 * @param string $file_path
	 * @param bool $dont_cache Don't use config file cached. [Default is false]
	 * @param bool $dont_cache_instance Don't attempt to cache config file Configuration instance. [Default is false]
	 * @param bool $dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 * @return Configuration
	 */
	static function load($file_path=__CA_APP_CONFIG__, $dont_cache=false, $dont_cache_instance=false, $dont_load_from_default_path=false) {
		if(!$file_path) { $file_path = __CA_APP_CONFIG__; }

		if(!MemoryCache::contains($file_path, 'ConfigurationInstances') || $dont_cache || $dont_cache_instance) {
			MemoryCache::save($file_path, new Configuration($file_path, true, $dont_cache, $dont_load_from_default_path), 'ConfigurationInstances');
		}

		return MemoryCache::fetch($file_path, 'ConfigurationInstances');
	}
	/* ---------------------------------------- */
	/**
	 * Load a configuration file. In addition to the parameters described below two global variables can also affect loading:
	 *
	 *		$g_ui_locale - if it contains the current locale code, this code will be used when computing the MD5 signature of the current configuration for caching purposes. By setting this to the current locale simultaneous caching of configurations for various locales (eg. config files with gettext-translated strings in them) is enabled.
	 *		$g_configuration_cache_suffix - any text it contains is used along with the configuration path and $g_ui_locale to compute the MD5 signature of the current configuration for caching purposes. By setting this to some value you can support simultaneous caching of configurations for several different modes. This is mainly used to support caching of theme-specific configurations. Since the theme can change based upon user agent, we need to potentially keep several computed configurations cached at the same time, one for each theme used.
	 *
	 * @param string $file_path Absolute path to configuration file to parse
	 * @param bool $die_on_error If true, request processing will halt with call to die() on error in parsing config file. [Default is false]
	 * @param bool $dont_cache If true, file will be parsed even if it's already cached. [Default is false]
	 * @param bool $dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 *
	 *
	 */
	public function __construct($file_path=__CA_APP_CONFIG__, $die_on_error=false, $dont_cache=false, $dont_load_from_default_path=false) {
		global $g_ui_locale, $g_configuration_cache_suffix;

		$this->ops_config_file_path = $file_path ? $file_path : __CA_APP_CONFIG__;	# path to configuration file
		
		$config_file_list = [];
		
		// cache key for on-disk caching
		$path_as_md5 = md5(($_SERVER['HTTP_HOST'] ?? '') . $this->ops_config_file_path.'/'.$g_ui_locale.(isset($g_configuration_cache_suffix) ? '/'.$g_configuration_cache_suffix : ''));

		#
		# Is configuration file already cached?
		#
		$config_path_components = explode("/", $this->ops_config_file_path);
		$config_filename = array_pop($config_path_components);

        $top_level_config_path = $this->ops_config_file_path;
		if (!$dont_load_from_default_path) {
			if (defined('__CA_LOCAL_CONFIG_DIRECTORY__') && !in_array($p = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.$config_filename, $config_file_list, true) && file_exists($p)){
				$config_file_list[] = $top_level_config_path = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.$config_filename;
			} 
			
			// Theme config overrides local config
			if (defined('__CA_DEFAULT_THEME_CONFIG_DIRECTORY__') && !in_array($p = __CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$config_filename, $config_file_list, true) && file_exists($p)) {
				$config_file_list[] = $top_level_config_path = __CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$config_filename;
			}
			
			// Appname-specific config overrides local config
			$appname_specific_path = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.pathinfo($config_filename, PATHINFO_FILENAME).'_'.__CA_APP_NAME__.'.'.pathinfo($config_filename, PATHINFO_EXTENSION);
			if (defined('__CA_LOCAL_CONFIG_DIRECTORY__') && !in_array($appname_specific_path, $config_file_list, true) && file_exists($appname_specific_path)) {
				$config_file_list[] = $top_level_config_path = $appname_specific_path;
			}
		}
		if(defined('__CA_CONF_DIR__') && !in_array($p = __CA_CONF_DIR__.'/'.$config_filename, $config_file_list, true) && file_exists($p)) { 
			$config_file_list[] = __CA_CONF_DIR__.'/'.$config_filename; 
		}
		$o_config = ($top_level_config_path === $this->ops_config_file_path) ? $this : Configuration::load($top_level_config_path, false, false, true);

		$filename = pathinfo($file_path, PATHINFO_BASENAME);
		
		$app_config = ($filename !== 'app.conf') ? Configuration::load(__CA_APP_CONFIG__) : $o_config;
		if (($inherit_config = $app_config->get(['allowThemeInheritance', 'allow_theme_inheritance'])) && !$dont_load_from_default_path) {
		    $i=0;
            while($inherit_from_theme = trim(trim($app_config->get(['inheritFrom', 'inherit_from'])), "/")) {
                $i++;
                $inherited_config_path = __CA_THEMES_DIR__."/{$inherit_from_theme}/conf/{$filename}";
                if (file_exists($inherited_config_path) && !in_array($inherited_config_path, $config_file_list) && ($inherited_config_path !== $this->ops_config_file_path)) {
                    array_unshift($config_file_list, $inherited_config_path);
                }
                if(!file_exists(__CA_THEMES_DIR__."/{$inherit_from_theme}/conf/app.conf")) { break; }
                $o_config = Configuration::load(__CA_THEMES_DIR__."/{$inherit_from_theme}/conf/app.conf", false, false, true);
                if ($i > 10) { break; } // max 10 levels
            }
		}
		if(file_exists($this->ops_config_file_path) && !in_array($this->ops_config_file_path, $config_file_list, true)) { 
			array_push($config_file_list, $this->ops_config_file_path); 
		}
		
		// try to figure out if we can get it from cache
		$mtime_keys = [];
		if((!defined('__CA_DISABLE_CONFIG_CACHING__') || !__CA_DISABLE_CONFIG_CACHING__) && !$dont_cache) {
			if($setup_has_changed = caSetupPhpHasChanged()) {
				self::clearCache();
			} elseif(ExternalCache::contains($path_as_md5, 'ConfigurationCache')) {	// try to load current file from cache
				self::$s_config_cache[$path_as_md5] = ExternalCache::fetch($path_as_md5, 'ConfigurationCache');
			}

			if(!$setup_has_changed && isset(self::$s_config_cache[$path_as_md5])) {	// file has been loaded from cache
				$cache_is_invalid = false;
				
				// Check file times to make sure cache is not stale
				foreach($config_file_list as $config_file_path) {
					$mtime_keys[] = $mtime_key = 'mtime_'.$path_as_md5.md5($config_file_path);
					$mtime = ExternalCache::fetch($mtime_key, 'ConfigurationCache');
					
				    $config_mtime = caGetFileMTime($config_file_path);
                    if($config_mtime != $mtime) { // config file has changed
                        self::$s_config_cache[$mtime_key] = $config_mtime;
                        $cache_is_invalid = true;
                        break;
                    }
				}

				if (!$cache_is_invalid) { // cache is ok
					$this->ops_config_settings = self::$s_config_cache[$path_as_md5];
					$this->ops_md5_path = md5($this->ops_config_file_path);
					return;
				}
			}

		}

		# File contents 
		$this->ops_config_settings = [];

		# try loading global.conf file
		if (sizeof($config_file_list) > 0) {
			foreach($config_file_list as $config_file_path) {
				if(!strlen(trim($config_file_path))) { continue; }
				$global_path = pathinfo($config_file_path, PATHINFO_DIRNAME).'/global.conf';
				if (file_exists($global_path)) { $this->loadFile($global_path, false); }
			}
		}
		
		//
		// Insert current user locale as constant into configuration.
		//
		$this->ops_config_settings['scalars']['LOCALE'] = $g_ui_locale;

		//
		// Load specified config file(s), overlaying each 
		//
		
        if (sizeof($config_file_list) > 0) {
            foreach(array_reverse($config_file_list) as $config_file_path) {
                if (file_exists($config_file_path)) {
                    $this->loadFile($config_file_path, false, false);
                }
            }
        }

		if($path_as_md5 && !$dont_cache) {
			self::$s_config_cache[$path_as_md5] = $this->ops_config_settings;
			// we loaded this cfg from file, so we have to write the
			// config cache to disk at least once on this request
			self::$s_have_to_write_config_cache = true;
			
			// Write file to cache
			ExternalCache::save($path_as_md5, self::$s_config_cache[$path_as_md5], 'ConfigurationCache', 3600 * 3600 * 30);
			
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
	 * Parses configuration file located at $file_path.
	 *
	 * @param $filepath - absolute path to configuration file to parse
	 * @param $die_on_error - if true, die() will be called on parse error halting request; default is false
	 * @param $num_lines_to_read - if set to a positive integer, will abort parsing after the first $num_lines_to_read lines of the config file are read. This is useful for reading in headers in config files without having to parse the entire file.
	 * @return boolean - returns true if parse succeeded, false if parse failed
	 */
	public function loadFile($filepath, $die_on_error=false, $num_lines_to_read=null) {
		$this->ops_md5_path = md5($filepath);
		$this->ops_error = "";
		$r_file = @fopen($filepath,"r", true);
		if (!$r_file) {
			$this->ops_error = "Couldn't open configuration file '".$filepath."'";
			if ($die_on_error) { $this->_dieOnError(); }
			return false;
		}

		$key = $scalar_value = $assoc_key = "";
		$in_quote = $state = 0;
		$escape_set = $quoted_item_is_closed = false;
		$assoc_pointer_stack = array();

		$token_history = array();
		$line_num = 0;
		$merge_mode = false;
		while (!feof($r_file)) {
			$line_num++;

			if (($num_lines_to_read > 0) && ($line_num > $num_lines_to_read)) { break; }
			$buffer = trim(fgets($r_file, 32000));
			if($in_quote) { $buffer .= "\n"; }

			# skip comments (start with '#') or blank lines
			if (strtolower(substr($buffer,0,7)) == '#!merge') { $merge_mode = true; }
			if (strtolower(substr($buffer,0,9)) == '#!replace') { $merge_mode = false; }
			if (!$buffer || (substr($buffer,0,1) === "#")) { continue; }

			$token_tmp = preg_split("/([={}\[\]\",\\\]){1}/", $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

			// eliminate blank tokens
			$tokens = array();
			$tok_count = sizeof($token_tmp);
			for($i = 0; $i < $tok_count; $i++) {
				if (strlen($token_tmp[$i])) {
					$tokens[] =& $token_tmp[$i];
				}
			}
			while (sizeof($tokens)) {
				$token = array_shift($tokens);

				$token_history[] = $token;
				if (sizeof($token_history) > 50) { array_shift($token_history); }
				switch($state) {
					# ------------------------------------
					# init
					case -1:
						$key = $assoc_key = $scalar_value = "";
						$in_quote = 0;
						$assoc_pointer_stack = array();

						$state = 0;

					# ------------------------------------
					# looking for key
					case 0:
						if ($token != "=") {
							$key .= $token;
						} else {
							$got_key = 1;
							$key = trim($key);

							$state = 10;
						}
						break;
					# ------------------------------------
					# determine type of value
					case 10:
						switch($token) {
							case '[':
								if(!is_array($this->ops_config_settings["lists"][$key] ?? null) || !$merge_mode) {
									$this->ops_config_settings["lists"][$key] = array();
								}
								$state = 30;
								break;
							case '{':
								if(!is_array($this->ops_config_settings["assoc"][$key] ?? null) || !$merge_mode) {
									$this->ops_config_settings["assoc"][$key] = array();
								}
								$assoc_pointer_stack[] =& $this->ops_config_settings["assoc"][$key];
								$state = 40;
								break;
							case '"':
								if($in_quote) {
									$in_quote = 0;
									$state = -1;
								} else {
									$scalar_value = '';
									$in_quote = 1;
									$state = 20;
								}
								break;
							default:
								// strip leading exclaimation in scalar to allow scalars to start with [ or {
								if (trim($token) == '!') {
									$token = array_shift($tokens);
								}
								if (!preg_match("/^[ \t]*$/", $token)) {
									$scalar_value .= $token;
									$state = 20;

									if(!$in_quote) {
										if (sizeof($tokens) == 0) {
											$this->ops_config_settings["scalars"][$key] = $this->_trimScalar($scalar_value);
											$state = -1;
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
						switch($token) {
							# -------------------
							case '"':
								if ($escape_set || (!$in_quote && strlen($scalar_value))) {	// Quote in interior of scalar - assume literal
									$scalar_value .= '"';
									if(sizeof($tokens) == 0) {
										$this->ops_config_settings["scalars"][$key] = $this->_trimScalar($scalar_value);
										$in_quote = 0;
										$escape_set = false;
										$state = -1;
									}
								} else {
									if (!$in_quote) {	// Quoted scalar
										$in_quote = 1;
									} else {
										// Accept quoted scalar
										$in_quote = 0;
										$escape_set = false;
										$state = -1;

										$this->ops_config_settings["scalars"][$key] = $scalar_value;
									}
								}
								$escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($escape_set) {
									$scalar_value .= $token;
									$escape_set = false;
								} else {
									$escape_set = true;
								}
								break;
							# -------------------
							default:
								if (preg_match("/[\r\n]/", $token) && !$in_quote) {	// Return ends scalar
									$this->ops_config_settings["scalars"][$key] = $this->_trimScalar($scalar_value);
									$in_quote = 0;
									$escape_set = false;
									$state = -1;
								} elseif ((sizeof($tokens) == 0) && !$in_quote) {
									$scalar_value .= $token;
									$this->ops_config_settings["scalars"][$key] = $this->_trimScalar($scalar_value);
									$in_quote = 0;
									$escape_set = false;
									$state = -1;
								} else { # keep going to next line
									$scalar_value .= rtrim($token, "\n");
									$state = 20;
								}
								break;
							# -------------------
						}
						break;
					# ------------------------------------
					# handle list values
					case 30:
					    if($quoted_item_is_closed && (!in_array(trim($token), [',', ']', ')']))) { break; }
						switch($token) {
							# -------------------
							case '"':
								if ($escape_set) {
									$scalar_value .= '"';
								} else {
									if (!$in_quote) {
										$in_quote = 1;
									} else {
										$in_quote = 0;
										$quoted_item_is_closed = true;
									}
								}
								$escape_set = false;
								break;
							# -------------------
							case ',':
								if ($in_quote || $escape_set) {
									$scalar_value .= ",";
								} else {
									if (strlen($item = trim($this->_interpolateScalar($this->_trimScalar($scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$key][] = $item;
									}
									$scalar_value = "";
									$quoted_item_is_closed = false;
								}
								$escape_set  = false;
								break;
							# -------------------
							case ']':
								if ($in_quote || $escape_set) {
									$scalar_value .= "]";
								} else {
									# accept list
									if (strlen($item = trim($this->_interpolateScalar($this->_trimScalar($scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$key][] = $item;
									}
									# initialize
									$state = -1;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($escape_set) {
									$scalar_value .= $token;
								} else {
									$escape_set = true;
								}
								break;
							# -------------------
							default:
								$scalar_value .= $token;
								$escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($tokens) == 0) && ($in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$key'";
							fclose($r_file);
							if ($die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------
					# handle associative array values
					# get associative key
					case 40:
					    if($quoted_item_is_closed && (!in_array(trim($token), [',', '=', '}', ')']))) { break; }
						switch($token) {
							# -------------------
							case '"':
								if ($escape_set) {
									$assoc_key .= '"';
								} else {
									if (!$in_quote) {
										$in_quote = 1;
									} else {
									    $quoted_item_is_closed = true;
										$in_quote = 0;
									}
								}
								$escape_set = false;
								break;
							# -------------------
							case '=':
								if ($in_quote || $escape_set) {
									$assoc_key .= "=";
								} else {
									if ((strlen($assoc_key = trim($this->_interpolateScalar($assoc_key)))) == '') {
										$this->ops_error = "Associative key must not be empty";
										fclose($r_file);

										if ($die_on_error) { $this->_dieOnError(); }
										return false;
									}
                                    
									$state = 50;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case ',':
								if ($in_quote || $escape_set) {
									$assoc_key .= ",";
								} else {
									if (strlen($assoc_key)) {
										$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][] = trim($assoc_key);
									}
									$assoc_key = "";
									$scalar_value = "";
									$state = 40;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case '}':
								if ($in_quote || $escape_set) {
									$assoc_key .= "}";
								} else {
									if (sizeof($assoc_pointer_stack) > 1) {
										if (strlen($assoc_key)) {
											$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][] = trim($assoc_key);
										}
										array_pop($assoc_pointer_stack);

										$state = 40;
									} else {
										if (strlen($assoc_key)) {
											$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][] = trim($assoc_key);
										}
										$state = -1;
									}
									$key = $assoc_key = $scalar_value = "";
									$in_quote = 0;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($escape_set) {
									$assoc_key .= $token;
								} else {
									$escape_set = true;
								}
								break;
							# -------------------
							default:
								if (preg_match("/^#/", trim($token))) {
									// comment
								} else {
									$escape_set = false;
									$assoc_key .= $token;
								}
								break;
							# -------------------
						}

						break;
					# ------------------------------------
					# handle associative value
					case 50:
					    if($quoted_item_is_closed && (!in_array(trim($token), [',', '{', '}', ',', ')']))) { break; }
						switch($token) {
							# -------------------
							case '"':
								if ($escape_set) {
									$scalar_value .= '"';
								} else {
									if (!$in_quote) {
									    if (preg_match("!^[ \t\n\r]+$!", $scalar_value)) { $scalar_value = ''; }
										$in_quote = 1;
									} else {
									    $quoted_item_is_closed = true;
										$in_quote = 0;
									}
								}
								$escape_set = false;
								break;
							# -------------------
							case ',':
								if ($in_quote || $escape_set) {
									$scalar_value .= ",";
								} else {
									if (strlen($assoc_key)) {
										$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][$assoc_key] = $this->_trimScalar($this->_interpolateScalar($scalar_value));
									}
									$assoc_key = "";
									$scalar_value = "";
									$state = 40;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							# open nested associative value
							case '{':
								if (!$in_quote && !$escape_set) {
									$i = sizeof($assoc_pointer_stack) - 1;
									if (!isset($assoc_pointer_stack[$i]) || !isset($assoc_pointer_stack[$i][$assoc_key]) || !is_array($assoc_pointer_stack[$i][$assoc_key]) || !$merge_mode) {
										$assoc_pointer_stack[$i][$assoc_key] = array();
									}
									$assoc_pointer_stack[] =& $assoc_pointer_stack[$i][$assoc_key];
									
									$state = 40;
									$key = $assoc_key = $scalar_value = "";
									$in_quote = 0;
									$quoted_item_is_closed = false;
								} else {
									$scalar_value .= $token;
								}
								$escape_set = false;
								break;
							# -------------------
							case '}':
								if ($in_quote || $escape_set) {
									$scalar_value .= "}";
								} else {
									if (sizeof($assoc_pointer_stack) > 1) {
										if (strlen($assoc_key)) {
											$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][$assoc_key] = $this->_trimScalar($this->_interpolateScalar($scalar_value));
										}
										array_pop($assoc_pointer_stack);

										$state = 40;
									} else {
										if (strlen($assoc_key)) {
											$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][$assoc_key] = $this->_trimScalar($this->_interpolateScalar($scalar_value));
										}
										$state = -1;
									}
									$key = $assoc_key = $scalar_value = "";
									$in_quote = 0;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							# open list
							case '[':
								if ($in_quote || $escape_set) {
									$scalar_value .= $token;
								} else {
									$i = sizeof($assoc_pointer_stack) - 1;
									if(!is_array($assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][$assoc_key] ?? null) || !$merge_mode) {
										$assoc_pointer_stack[$i][$assoc_key] = array();
									}
									$assoc_pointer_stack[] =& $assoc_pointer_stack[$i][$assoc_key];
									$state = 60;
									$in_quote = 0;
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($escape_set) {
									$scalar_value .= $token;
								} else {
									$escape_set = true;
								}
								break;
							# -------------------
							default:
								$scalar_value .= $token;
								$escape_set = false;
								break;
							# -------------------
						}
						break;
					# ------------------------------------
					# handle list values nested in assoc
					case 60:
					    if($quoted_item_is_closed && (!in_array(trim($token), [',', ']', ')']))) { break; }
						switch($token) {
							# -------------------
							case '"':
								if ($escape_set) {
									$scalar_value .= '"';
								} else {
									if (!$in_quote) {
										$in_quote = 1;
									} else {
										$in_quote = 0;
										$quoted_item_is_closed = true;
									}
								}
								$escape_set = false;
								break;
							# -------------------
							case ',':
								if ($in_quote || $escape_set) {
									$scalar_value .= ",";
								} else {
									if (strlen($item = trim($this->_interpolateScalar($this->_trimScalar($scalar_value)))) > 0) {
										$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][] = $item;
									}
									$scalar_value = "";
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case ']':
								if ($in_quote || $escape_set) {
									$scalar_value .= "]";
								} else {
									# accept list
									if (strlen($item = trim($this->_interpolateScalar($this->_trimScalar($scalar_value)))) > 0) {
										$assoc_pointer_stack[sizeof($assoc_pointer_stack) - 1][] = $item;
									}
									array_pop($assoc_pointer_stack);
									# initialize
									$state = 40;
									$assoc_key = '';
									$quoted_item_is_closed = false;
								}
								$escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($escape_set) {
									$scalar_value .= $token;
								} else {
									$escape_set = true;
								}
								break;
							# -------------------
							default:
								$scalar_value .= $token;
								$escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($tokens) == 0) && ($in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$key'";
							fclose($r_file);
							if ($die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------

				}
			}
			if ((($state == 10) || ($state == 20)) && !$in_quote) {
				$this->ops_config_settings["scalars"][$key] = "";
				$state = -1;
			}

			if(in_array($state, [10,20]) && $in_quote) {
				$scalar_value .= "\n";
			}

			if ($in_quote && !in_array($state, [10,20])) {
				switch($state) {
					case 30:
						// $this->ops_error = "Missing trailing quote in list '$key'<br/><strong>Last ".sizeof($token_history)." tokens were: </strong>".$this->_formatTokenHistory($token_history, array('outputAsHTML' => true));
 						//break;
						continue(2);	// allow multiline quoted entries
					case 40:
					case 50:
						//$this->ops_error = "Missing trailing quote in associative array '$key'<br/><strong>Last ".sizeof($token_history)." tokens were: </strong>".$this->_formatTokenHistory($token_history, array('outputAsHTML' => true));
						//break;
						continue(2);	// allow multiline quoted entries
					default:
						$this->ops_error = "Missing trailing quote in '$key' [Last token was '{$token}'; state was $state]<br/><strong>Last ".sizeof($token_history)." tokens were: </strong>".$this->_formatTokenHistory($token_history, array('outputAsHTML' => true));
						break;
				}
				fclose($r_file);

				if ($die_on_error) { $this->_dieOnError(); }
				return false;
			}
		}

		if ($state > 0) {
			$this->ops_error = "Syntax error in configuration file: missing { or } [state=$state]<br/><strong>Last ".sizeof($token_history)." tokens were: </strong>".$this->_formatTokenHistory($token_history, array('outputAsHTML' => true));
			fclose($r_file);

			if ($die_on_error) { $this->_dieOnError(); }
			return false;
		}

		// interpolate scalars
		if (isset($this->ops_config_settings["scalars"]) && is_array($this->ops_config_settings["scalars"])) {
			foreach($this->ops_config_settings["scalars"] as $key => $val) {
				$this->ops_config_settings["scalars"][$key] = $this->_interpolateScalar($val);
			}
		}
		fclose($r_file);

		return true;
	}
	/* ---------------------------------------- */
	private function _formatTokenHistory($pa_token_history, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$output = '';
		if (isset($pa_options['outputAsHTML']) && $pa_options['outputAsHTML']) {
			$output = "<pre>";
			for($i=1; $i <=sizeof($pa_token_history); $i++) {
				$output .= "\t[{$i}] ".$pa_token_history[$i-1]."\n";
			}
			$output .= "</pre>";
		} else {
			if (!isset($pa_options['delimiter'])) { $delimiter = ';'; } else { $delimiter = $pa_options['delimiter']; }
			$output = join($delimiter, $pa_token_history);
		}
		return $output;
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
	    
	    foreach($pm_key as $key) {
            if (isset(Configuration::$s_get_cache[$this->ops_md5_path][$key]) && Configuration::$s_get_cache[$this->ops_md5_path][$key]) { return Configuration::$s_get_cache[$this->ops_md5_path][$key]; }
            $this->ops_error = "";

            $tmp = $this->getScalar($key);
            if (!strlen($tmp)) {
                $tmp = $this->getList($key);
            }
            if (!is_array($tmp) && !strlen($tmp)) {
                if (is_array($tmp = $this->getAssoc($key))) { $assoc_exists = true; }
            }
            Configuration::$s_get_cache[$this->ops_md5_path][$key] = $tmp;
            
            if (!is_array($tmp) && !strlen($tmp)) { continue; }
            return $tmp;
        }
        return $assoc_exists ? [] : null;
	}
	/* ---------------------------------------- */
	/**
	 * Determine if specified key is present in the configuration file.
	 *
	 * @param string $key Name of configuration value.
	 *
	 * @return bool
	 */
	public function exists($key) {
		if (isset(Configuration::$s_get_cache[$this->ops_md5_path][$key])) { return true; }
		$this->ops_error = "";

		if (array_key_exists($key, $this->ops_config_settings["scalars"])) { return true; }
		if (array_key_exists($key, $this->ops_config_settings["lists"])) { return true; }
		if (array_key_exists($key, $this->ops_config_settings["assoc"])) { return true; }
		
		return false;
	}
	/* ---------------------------------------- */
	/**
	 * Get boolean configuration value
	 *
	 * @param string $key Name of configuration value to get. getBoolean() will look for the
	 * configuration value only as a scalar, and return boolean 'true' if the scalar value is
	 * either 'yes', 'true' or '1'.
	 *
	 * @return boolean
	 */
	public function getBoolean($key) {
		$tmp = strtolower($this->getScalar($key));
		if(($tmp == "yes") || ($tmp == "true") || ($tmp == "1")) {
			return true;
		} else {
			return false;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get scalar configuration value
	 *
	 * @param string $key Name of scalar configuration value to get. get() will look for the
	 * configuration value only as a scalar. Like-named list or associative array values are
	 * ignored.
	 *
	 * @return string
	 */
	public function getScalar($key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["scalars"][$key])) {
			return $this->ops_config_settings["scalars"][$key];
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
	 * @param string $key Name of list configuration value to get. get() will look for the
	 * configuration value only as a list. Like-named scalar or associative array values are
	 * ignored.
	 *
	 * @return array An indexed array
	 */
	public function getList($key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["lists"][$key])) {
			if (is_array($this->ops_config_settings["lists"][$key])) {
				return $this->ops_config_settings["lists"][$key];
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
	 * @param string $key Name of associative configuration value to get. get() will look for the
	 * configuration value only as an associative array. Like-named scalar or list values are
	 * ignored.
	 *
	 * @return array An associative array
	 */
	public function getAssoc($key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["assoc"][$key])) {
			if (is_array($this->ops_config_settings["assoc"][$key])) {
				return $this->ops_config_settings["assoc"][$key];
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
	private function _trimScalar(?string $scalar_value) : ?string {
		if (preg_match("/^[ ]+$/", $scalar_value)) {
			$scalar_value = " ";
		} else {
			$scalar_value = trim($scalar_value);
		}
		// perform constant var substitution
		if (preg_match("/^(__[A-Za-z0-9\_]+)(?=__)/", $scalar_value, $matches)) {
			if (defined($matches[1].'__')) {
				return str_replace($matches[1].'__', constant($matches[1].'__'), $scalar_value);
			}
		}
		return $scalar_value;
	}
	/* ---------------------------------------- */
	private function _dieOnError() {
		die("Error loading configuration file '".$this->ops_config_file_path."': ".$this->ops_error."\n");
	}
	/* ---------------------------------------- */
	private function _interpolateScalar(?string $text) : ?string {
		if (preg_match_all("/<([A-Za-z0-9_\-\.]+)>/", $text, $matches)) {
			foreach($matches[1] as $key) {
				if (($val = $this->getScalar($key)) !== false) {
					$text = preg_replace("/<$key>/", $val, $text);
				}
			}
		}

		// attempt translation if text is enclosed in _( and ) ... for example _t(translate me)
		// assumes translation function _t() is present; if not loaded will not attempt translation
		if (function_exists('_t') && preg_match("/(?<=\s|>|^)_\(([^\"\)]+)\)/", $text, $matches)) {
			$trans_text = $text;
			array_shift($matches);
			foreach($matches as $match) {
				$trans_text = str_replace("_({$match})", _t($match), $trans_text);
			}
			return $trans_text;
		}
		return $text;
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
