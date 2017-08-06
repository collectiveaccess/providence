<?php
/** ---------------------------------------------------------------------
 * app/helpers/utilityHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2016 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/ZipFile.php');
require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
require_once(__CA_LIB_DIR__.'/core/Utils/Encoding.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Measure/Length.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/ganon.php');

# ----------------------------------------------------------------------
# String localization functions (getText)
# ----------------------------------------------------------------------
/**
 * Translates the string in $ps_key into the current locale
 * You interpolate values into the returned string by embedding numbered placeholders in $ps_key 
 * in the format %n (where n is a number). Each parameter passed after $ps_key corresponds to a 
 * placeholder (ex. the first parameter replaces %1, the second %2)
 */

MemoryCache::flush('translation');

function _t($ps_key) {
	if(!$ps_key) { return ''; }
	global $_;

	if(!MemoryCache::contains($ps_key, 'translation')) {
		if (is_array($_)) {
			$vs_str = $ps_key;
			foreach($_ as $o_locale) {
				if ($o_locale->isTranslated($ps_key)) {
					$vs_str = $o_locale->_($ps_key);
					break;
				}
			}
		} else {
			if (!is_object($_)) { 
				$vs_str = $ps_key;
			} else {
				$vs_str = $_->_($ps_key);
			} 
		}
		MemoryCache::save($ps_key, $vs_str, 'translation');
	} else {
		$vs_str = MemoryCache::fetch($ps_key, 'translation');
	}
	
	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", $va_args[$vn_i], $vs_str);
		}
	}
	return $vs_str;
}

/**
 * The same as _t(), but rather than returning the translated string, it prints it
 **/
function _p($ps_key) {
	if(!$ps_key) { return; }
	global $_;
	
	if (!sizeof(func_get_args()) && MemoryCache::contains($ps_key, 'translation')) {
		print MemoryCache::fetch($ps_key, 'translation'); return;
	}
	
	if (is_array($_)) {
		$vs_str = $ps_key;
		foreach($_ as $o_locale) {
			if ($o_locale->isTranslated($ps_key)) {
				$vs_str = $o_locale->_($ps_key);
				break;
			}
		}
	} else {
		if (!is_object($_)) { 
			$vs_str = $ps_key;
		} else {
			$vs_str = $_->_($ps_key);
		} 
	}
	
	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", $va_args[$vn_i], $vs_str);
		}
	}

	MemoryCache::save($ps_key, $vs_str, 'translation');
	print $vs_str;
	return;
}
# ----------------------------------------------------------------------
# Define parameter type constants for getParameter()
# ----------------------------------------------------------------------
if(!defined("pInteger")) { define("pInteger", 1); }
if(!defined("pFloat")) { define("pFloat", 2); }
if(!defined("pString")) { define("pString", 3); }
if(!defined("pArray")) { define("pArray", 4); }

# OS family constants
define('OS_POSIX', 0);
define('OS_WIN32', 1);


# ----------------------------------------
# --- XML
# ----------------------------------------
function caEscapeForXML($ps_text) {
	$ps_text = caMakeProperUTF8ForXML($ps_text);
	$ps_text = str_replace("&", "&amp;", $ps_text);
	$ps_text = str_replace("<", "&lt;", $ps_text);
	$ps_text = str_replace(">", "&gt;", $ps_text);
	$ps_text = str_replace("'", "&apos;", $ps_text);
	
	return str_replace("\"", "&quot;", $ps_text);
}
# ----------------------------------------
function caMakeProperUTF8ForXML($ps_text){
	// remove/convert invalid bytes
	$ps_text = mb_convert_encoding($ps_text, 'UTF-8', 'UTF-8');
	
	// strip invalid PCDATA characters for XML
	$vs_return = "";
	if (empty($ps_text)) {
		return $vs_return;
	}
	 
	$vn_length = strlen($ps_text);
	for ($i=0; $i < $vn_length; $i++) {
		$vn_current = ord($ps_text{$i});
		if (($vn_current == 0x9) ||
			($vn_current == 0xA) ||
			($vn_current == 0xD) ||
			(($vn_current >= 0x20) && ($vn_current <= 0xD7FF)) ||
			(($vn_current >= 0xE000) && ($vn_current <= 0xFFFD)) ||
			(($vn_current >= 0x10000) && ($vn_current <= 0x10FFFF)))
		{
			$vs_return .= chr($vn_current);
		} else {
			$vs_return .= " ";
		}
	}
	return $vs_return;
}
# ----------------------------------------
# --- Files
# ----------------------------------------
function caFileIsIncludable($ps_file) {
	$va_paths = explode(PATH_SEPARATOR, get_include_path());
	
	foreach ($va_paths as $vs_path) {
		$vs_fullpath = $vs_path.DIRECTORY_SEPARATOR.$ps_file;
 
		if (file_exists($vs_fullpath)) {
			return $vs_fullpath;
		}
    }
 
    return false;
}

# ----------------------------------------
# File and directory copying
# ----------------------------------------
	function caCopyDirectory($fromDir,$toDir,$chmod=0755,$verbose=false,$replace_existing=true) {
		$errors=array();
		$messages=array();
		
		if (!file_exists($toDir)) {
			mkdir($toDir, $chmod);
		}
		if (!is_writable($toDir)) {
			$errors[]='target '.$toDir.' is not writable';
		}
		if (!is_dir($toDir)) {
			$errors[]='target '.$toDir.' is not a directory';
		}
		if (!is_dir($fromDir)) {
			$errors[]='source '.$fromDir.' is not a directory';
		}
		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					echo '<strong>Error</strong>: '.$err.'<br />';
				}
			}
			return false;
		}
		
		$exceptions=array('.','..');
		
		$handle=opendir($fromDir);
		while (false!==($item=readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				// cleanup for trailing slashes in directories destinations
				$from=str_replace('//','/',$fromDir.'/'.$item);
				$to=str_replace('//','/',$toDir.'/'.$item);
		
				if (is_file($from))  {
					if (!((!$replace_existing) && file_exists($to))) { 
						if (@copy($from,$to))  {
							chmod($to,$chmod);
							touch($to,filemtime($from)); // to track last modified time
							$messages[]='File copied from '.$from.' to '.$to;
						} else {
							$errors[]='cannot copy file from '.$from.' to '.$to;
						}
					}
				}
				if (is_dir($from))  {
					if (@mkdir($to))  {
						chmod($to,$chmod);
						$messages[]='Directory created: '.$to;
					} else {
						$errors[]='cannot create directory '.$to;
					}
					caCopyDirectory($from,$to,$chmod,$verbose,$replace_existing);
				}
			}
		}
		closedir($handle);
		
		if ($verbose) {
			foreach($errors as $err) {
				echo '<strong>Error</strong>: '.$err."<br/>\n";
			}
			foreach($messages as $msg) {
				echo $msg."<br/>\n";
			}
		}
		return true;
	}
	# ----------------------------------------
	/**
	 * Removes directory $dir and recursively all content within. This means all files and subdirectories within the specified directory will be removed without any question!
	 *
	 * @param string $dir The path to the directory you wish to remove
	 * @param bool $pb_delete_dir By default caRemoveDirectory() will remove the specified directory after delete everything within it. Setting this to false will retain the directory after removing everything inside of it, effectively "cleaning" the directory.
	 * @return bool Always returns true
	 */
	function caRemoveDirectory($dir, $pb_delete_dir=true) {
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		if ($handle = opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					if (is_dir("{$dir}/{$item}")) { caRemoveDirectory("{$dir}/{$item}", true);  }
					else { @unlink("{$dir}/{$item}"); }
				}
			}
			closedir($handle);
			if ($pb_delete_dir) {
				@rmdir($dir);
			}
		} else {
			return false;
		}
		
		return true;
	}
	# ----------------------------------------
	/**
	 * Returns a list of files for the directory $dir and all sub-directories. Optionally can be restricted to return only files that are in $dir (no sub-directories).
	 *
	 * @param string $dir The path to the directory you wish to get the contents list for
	 * @param bool $pb_recursive Optional. By default caGetDirectoryContentsAsList() will recurse through all sub-directories of $dir; set this to false to only consider files that are in $dir itself.
	 * @param bool $pb_include_hidden_files Optional. By default caGetDirectoryContentsAsList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @param bool $pb_sort Optional. If set paths are returns sorted alphabetically. Default is false.
	 * @param bool $pb_include_directories. If set paths to directories are included. Default is false (only files are returned).
	 * @param array $pa_options Additional options, including:
	 *		modifiedSince = Only return files and directories modified after a Unix timestamp [Default=null]
	 * @return array An array of file paths.
	 */
	function &caGetDirectoryContentsAsList($dir, $pb_recursive=true, $pb_include_hidden_files=false, $pb_sort=false, $pb_include_directories=false, $pa_options=null) {
		$va_file_list = array();
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		
		if($va_paths = scandir($dir, 0)) {
			foreach($va_paths as $item) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
					if (
						(isset($pa_option['modifiedSince']) && ($pa_option['modifiedSince'] > 0))
						&&
						(is_array($va_stat = @stat("{$dir}/{$item}")))
						&&
						($va_stat['mtime'] < $pa_option['modifiedSince'])	
					) { continue; }
				
					$vb_is_dir = is_dir("{$dir}/{$item}");
					if ($pb_include_directories && $vb_is_dir) {
						$va_file_list["{$dir}/{$item}"] = true;
					}
					if ($pb_recursive && $vb_is_dir) { 
						$va_file_list = array_merge($va_file_list, array_flip(caGetDirectoryContentsAsList("{$dir}/{$item}", true, $pb_include_hidden_files, false, $pb_include_directories)));
					} else { 
						if (!$vb_is_dir) { 
							$va_file_list["{$dir}/{$item}"] = true;
						}
					}
				}
			}
		}
		
		if ($pb_sort) {
			ksort($va_file_list);
		}
		return array_keys($va_file_list);
	}
	# ----------------------------------------
	/**
	 * Returns counts of files and directories for the directory $dir and, optionally, all sub-directories. 
	 *
	 * @param string $dir The path to the directory you wish to get the contents list for
	 * @param bool $pb_recursive Optional. By default caGetDirectoryContentsAsList() will recurse through all sub-directories of $dir; set this to false to only consider files that are in $dir itself.
	 * @param bool $pb_include_hidden_files Optional. By default caGetDirectoryContentsAsList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @return array An array of counts with two keys: 'directories' and 'files'
	 */
	function caGetDirectoryContentsCount($dir, $pb_recursive=true, $pb_include_hidden_files=false) {
		$vn_file_count = 0;
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		
		$va_counts = array(
			'directories' => 0, 'files' => 0
		);
		if ($handle = @opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
					$vb_is_dir = is_dir("{$dir}/{$item}");
					if ($vb_is_dir) {
						$va_counts['directories']++;
					}
					if ($pb_recursive && $vb_is_dir) { 
						$va_recursive_counts = caGetDirectoryContentsCount("{$dir}/{$item}", true, $pb_include_hidden_files);
						$va_counts['files'] += $va_recursive_counts['files'];
						$va_counts['directories'] += $va_recursive_counts['directories'];
					} else { 
						if (!$vb_is_dir) { 
							$va_counts['files']++;
						} 
					}
				}
			}
			closedir($handle);
		}
		
		return $va_counts;
	}
	# ----------------------------------------
	/**
	 * Returns a list of directories from all directories under $dir as an array of directory paths with associated file counts. 
	 *
	 * @param string $dir The path to the directory you wish to get the contents list for
	 * @param bool $pb_include_root Optional. By default caGetSubDirectoryList() omits the root directory ($dir) and any files in it. Set this to true to include the root directory if it contains files.
	 * @param bool $pb_include_hidden_files Optional. By default caGetSubDirectoryList() does not consider hidden files (files starting with a '.') when calculating file counts. Set this to true to include hidden files in counts. Note that the special UNIX '.' and '..' directory entries are *never* counted as files.
	 * @return array An array with directory paths as keys and file counts as values. The array is sorted alphabetically.
	 */
	function &caGetSubDirectoryList($dir, $pb_include_root=false, $pb_include_hidden_files=false) {
		$va_dir_list = array();
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		if ($pb_include_root) {
			$va_dir_list[$dir] = 0;
		}
		$vn_file_count = 0;
		if ($handle = @opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
					if (is_dir("{$dir}/{$item}")) {
						$va_dir_list = array_merge($va_dir_list, caGetSubDirectoryList("{$dir}/{$item}", true, $pb_include_hidden_files));
					}  else {
						$vn_file_count++;
					}
				}
			}
			closedir($handle);
		}
		
		if ($pb_include_root) {
			$va_dir_list[$dir] = $vn_file_count;
		}
		
		ksort($va_dir_list);
		return $va_dir_list;
	}
	# ----------------------------------------
	/**
	 * Checks if a given directory is empty (i.e. doesn't have any subdirectories or files in it)
	 * @param string $vs_dir The directory to check
	 * @return bool false if it's not a readable directory or if it's not empty, otherwise true
	 */
	function caDirectoryIsEmpty($vs_dir) {
		if(!is_readable($vs_dir) || !is_dir($vs_dir)) { return false; }

		try {
			$o_iterator = new \FilesystemIterator($vs_dir);
			return !$o_iterator->valid();
		} catch (Exception $e) {
			return false;
		}
	}
	# ----------------------------------------
	function caZipDirectory($ps_directory, $ps_name, $ps_output_file) {
		$va_files_to_zip = caGetDirectoryContentsAsList($ps_directory);

		$vs_tmp_name = caGetTempFileName('caZipDirectory', 'zip');
		$o_phar = new PharData($vs_tmp_name, null, null, Phar::ZIP);
		foreach($va_files_to_zip as $vs_file) {
			$vs_name = str_replace($ps_directory, $ps_name, $vs_file);
			$o_phar->addFile($vs_file, $vs_name);
		}

		copy($vs_tmp_name, $ps_output_file);
		unlink($vs_tmp_name);

		return true;
	}
	# ----------------------------------------
	function caIsArchive($ps_filename){
		// what once was the PHAR extension is built in since PHP 5.3
		// can actually handle zip and tar.gz (and probably a lot more)
		if(!class_exists("PharData")) return false; 
		$list = @scandir('phar://'.$ps_filename);
	
		return (bool)$list;
	}
	# ----------------------------------------
	/**
	 * Detemines if a given path is valid by validating it against a regular expression and running it through file_exists
	 * @param $ps_path
	 * @return bool
	 */
	function caIsValidFilePath($ps_path) {
		// strip quotes from path if present since they'll cause file_exists() to fail
		$ps_path = preg_replace("!^\"!", "", $ps_path);
		$ps_path = preg_replace("!\"$!", "", $ps_path);
		if (!$ps_path || (preg_match("/[^\/A-Za-z0-9\.:\ _\(\)\\\-]+/", $ps_path))) { return false; }

		if(!ini_get('open_basedir') && !@is_readable($ps_path)) { // open_basedir and is_readable() have some weird interactions
			return false;
		}

		return true;
	}
	# ----------------------------------------
	/**
	 * Returns constant indicating class of operating system that system is running on
	 *
	 * @return int Returns constant OS_WIN32 for windows, OS_POSIX for Posix (Eg. Linux, MacOS)
	 */
	function caGetOSFamily() {
		switch(strtoupper(substr(PHP_OS, 0, 3))	) {
			case 'WIN':
				return OS_WIN32;
				break;
			default:
				return OS_POSIX;
				break;
		}
	}
	# ----------------------------------------
	/**
	 * Returns true if running on a Windows system
	 *
	 * @return bool
	 */
	function caIsWindows() {
		return (caGetOSFamily() === OS_WIN32);
	}
	# ----------------------------------------
	/**
	 * Returns true if running on a POSIX system
	 *
	 * @return bool
	 */
	function caIsPOSIX() {
		return (caGetOSFamily() === OS_POSIX);
	}
	# ----------------------------------------
	function caGetPHPVersion() {
		$vs_version = phpversion();
		$va_tmp = explode('.', $vs_version);

		$vn_i = 0;
		$vn_major = $vn_minor = $vn_revision = 0;
		foreach($va_tmp as $vs_element) {
			if (is_numeric($vs_element)) {
				switch($vn_i) {
					case 0:
						$vn_major = intval($vs_element);
						break;
					case 1:
						$vn_minor = intval($vs_element);
						break;
					case 2:
						$vn_revision = intval($vs_element);
						break;
				}
				
				$vn_i++;
			}
		}
		
		return(array(
			'version' => join('.', array($vn_major, $vn_minor, $vn_revision)), 
			'major' => $vn_major, 
			'minor' => $vn_minor, 
			'revision' => $vn_revision,
			'versionInt' => ($vn_major * 10000) + ($vn_minor * 100) + ($vn_revision)
		));
	}
	# ----------------------------------------
	function caEscapeHTML($ps_text, $vs_character_set='utf-8') {
		$opa_php_version = caGetPHPVersion();
		
		if ($opa_php_version['versionInt'] >= 50203) {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set, false);
		} else {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set);
		}
		return str_replace("&amp;#", "&#", $ps_text);
	}
	# ----------------------------------------
	function caEscapeForBundlePreview($ps_text, $pn_limit=100) {
		$ps_text = caSanitizeStringForJsonEncode($ps_text);
		if(mb_strlen($ps_text) > $pn_limit) {
			$ps_text = mb_substr($ps_text, 0, $pn_limit) . " ...";
		}

		if($ps_text = json_encode(html_entity_decode($ps_text, ENT_QUOTES | ENT_HTML5))) {
			return $ps_text;
		} else {
			return '""';
		}
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caEscapeSearchForURL($ps_search) {
		return rawurlencode(str_replace('/', '&#47;', $ps_search)); // encode slashes as html entities to avoid Apache considering it a directory separator
	}
	# ----------------------------------------
	function caSanitizeStringForJsonEncode($ps_text) {
		// Remove invalid UTF-8
		mb_substitute_character(0xFFFD);
		$ps_text = mb_convert_encoding($ps_text, 'UTF-8', 'UTF-8');

		return strip_tags($ps_text);

		// @see http://php.net/manual/en/regexp.reference.unicode.php
		//return preg_replace("/[^\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{N}\p{P}\p{Zp}\p{Zs}\p{S}]|➔/", '', strip_tags($ps_text));
	}
	# ----------------------------------------
	/**
	 * Return text with quotes escaped for use in a tab or comma-delimited file
	 *
	 * @param string $ps_text
	 * @return string
	 */
	function caEscapeForDelimitedOutput($ps_text) {
		return '"'.str_replace("\"", "\"\"", $ps_text).'"';
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caGetTempDirPath() {
		if (function_exists('sys_get_temp_dir')) {
			return sys_get_temp_dir();
		}

		if (!empty($_ENV['TMP'])) {
			return realpath($_ENV['TMP']);
		} else {
			if (!empty($_ENV['TMPDIR'])) {
    		 	return realpath($_ENV['TMPDIR']);
   			} else {
				if (!empty($_ENV['TEMP'])) {
					return realpath( $_ENV['TEMP'] );
				} else {
					$vs_tmp = tempnam( md5(uniqid(rand(), TRUE)), '' );
					if ($vs_tmp)  {
						$vs_tmp_dir = realpath(dirname($vs_tmp));
						unlink($vs_tmp);
						return $vs_tmp_dir;
					} else {
						return "/tmp";
					}
				}
			}
		}
	}
	# ----------------------------------------
	function caGetTempFileName($ps_prefix, $ps_extension = null) {
		$vs_tmp = tempnam(caGetTempDirPath(), $ps_prefix);
		@unlink($vs_tmp);

		if($ps_extension && strlen($ps_extension)>0) {
			$vs_tmp = $vs_tmp.'.'.$ps_extension;
		}

		return $vs_tmp;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caMakeGetFilePath($ps_prefix=null, $ps_extension=null) {
 		$vs_path = caGetTempDirPath();

		do {
			$vs_file_path = $vs_path.DIRECTORY_SEPARATOR.$ps_prefix.mt_rand().getmypid().($ps_extension ? ".{$ps_extension}" : "");
		} while (file_exists($vs_file_path));            

		return $vs_file_path;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caQuoteList($pa_list) {
		if (!is_array($pa_list)) { return array(); }
		$va_quoted_list = array();
		foreach($pa_list as $ps_list) {
			$va_quoted_list[] = "'".addslashes($ps_list)."'";
		}
		return $va_quoted_list;
	}
	# ----------------------------------------
	function caSerializeForDatabase($ps_data, $pb_compress=false) {
		if ($pb_compress && function_exists('gzcompress')) {
			return gzcompress(serialize($ps_data));
		} else {
			return base64_encode(serialize($ps_data));
		}
	}
	# ----------------------------------------
	function caUnserializeForDatabase($ps_data) {
		if (is_array($ps_data)) { return $ps_data; }
		if (function_exists('gzuncompress') && ($ps_uncompressed_data = @gzuncompress($ps_data))) {
			return unserialize($ps_uncompressed_data);
		}
		return unserialize(base64_decode($ps_data));
	}
	# ----------------------------------------
	/**
	 * 
	 */
	function caWinExec($ps_cmd, &$pa_output, &$pn_return_val) {
		$va_descr = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		
		$va_env=array('placeholder' => '   ');		// do we need this?
		$r_proc = proc_open($ps_cmd,$va_descr,$va_pipes,null,$va_env,array('bypass_shell'=>TRUE));
		
		if (!is_resource($r_proc)) {
			$pa_output = array();
			$pn_return_val = -1;
			return false;
		} else {
			// Write to app w/ $pipes[0] here...
			fclose($va_pipes[0]);
			
			// Retrieve & close stdout(1) & stderr(2)
			$output=preg_replace("![\n\r]+!", "", stream_get_contents($va_pipes[1]));
			$error=stream_get_contents($va_pipes[2]);
			
			$pa_output = array($output);
			if ($error) {
				$pa_output[] = $error;
			}
			fclose($va_pipes[1]);
			fclose($va_pipes[2]);
			
			// It is important that you close any pipes before calling
			// proc_close in order to avoid a deadlock
			$pn_return_val = proc_close($r_proc);
			return true;
		}
	}
	# ----------------------------------------
	/**
	 * Check if mod_rewrite web server module is available 
	 *
	 * @return bool
	 */
	$g_mod_write_is_available = null;
	function caModRewriteIsAvailable() {
		global $g_mod_write_is_available;
		if (is_bool($g_mod_write_is_available)) { return $g_mod_write_is_available; }
		if (function_exists('apache_get_modules')) {
			return $g_mod_write_is_available = (bool)in_array('mod_rewrite', apache_get_modules());
		} else {
			return $g_mod_write_is_available = (bool)((getenv('HTTP_MOD_REWRITE') == 'On') ? true : false);
		}
	}
	# ----------------------------------------
	function caConvertHTMLBreaks($ps_text) {
		# check for tags before converting breaks
		preg_match_all("/<[A-Za-z0-9]+/", $ps_text, $va_tags);
		$va_ok_tags = array("<b", "<i", "<u", "<strong", "<em", "<strike", "<sub", "<sup", "<a", "<img", "<span");

		$vb_convert_breaks = true;
		foreach($va_tags[0] as $vs_tag) {
			if (!in_array($vs_tag, $va_ok_tags)) {
				$vb_convert_breaks = false;
				break;
			}
		}

		if ($vb_convert_breaks) {
			$ps_text = preg_replace("/(\n|\r\n){2}/","<p/>",$ps_text);
			$ps_text = ereg_replace("\n","<br/>",$ps_text);
		}
		
		return $ps_text;
	}
	# ----------------------------------------
	/**
	 * Returns list of ngrams for $ps_word; $pn_n is the length of the ngram
	 */
	function caNgrams($str, $size = 5, $clean = true) {
  		$arrNgrams = array();
  		if ($clean) {
	  		$str = strtolower(preg_replace("/[^A-Za-z0-9]/",'',$str));
		}
		for ($i = 0; $i < (strlen($str)-$size+1); $i++) {
			$potential_ngram = substr($str, $i, $size);
			if (strlen($potential_ngram) > 1) {
				$arrNgrams[] = $potential_ngram;
			}
		}
		
		if ($clean) {
			$arrNgrams = array_unique($arrNgrams);
		}
		return($arrNgrams);
	}
	# ---------------------------------------
	/**
	 * Returns memory used by current request, either in bytes (integer) or in megabytes for display (string)
	 * 
	 * If $pb_dont_include_base_usage is set to true (default) then usage is counted from a base level 
	 * as defined in the __CA_BASE_MEMORY_USAGE__ constant. This constant should be set early in the request immediately 
	 * after all core includes() are performed.
	 *
	 * If $pb_dont_include_base_usage is set to false then this function returns the same value as the PHP memory_get_usage() built-in
	 * with the "real memory" parameter set.
	 *
	 * If the $pb_format_for_display is set (default = true), then the memory usage is returned as megabytes in a string (ex. 9.75M)
	 * If it is not set then an integer representing the number of bytes used is returned (ex. 10223616)
	 */
	function caGetMemoryUsage($pb_dont_include_base_usage=true, $pb_format_for_display=true) {
		$vn_base_use = defined("__CA_BASE_MEMORY_USAGE__") ? intval(__CA_BASE_MEMORY_USAGE__) : 0;
		
		$vn_usage = ($pb_dont_include_base_usage) ? memory_get_usage(true) - $vn_base_use : memory_get_usage(true);
		
		if ($pb_format_for_display) {
			return sprintf("%3.2f", ($vn_usage/(1024 * 1024)))."M";
		} else {
			return $vn_usage;
		}
	}
	# ---------------------------------------
	/**
	 * Checks URL for apparent well-formedness. Return true if it looks like a valid URL, false if not. This function does
	 * not actually connect to the URL to confirm its validity. It only validates text content for well-formedness.
	 * By default will return true if a url is anywhere in the $ps_url parameter. Set the 'strict' option if you want to 
	 * only return true for strings that are valid urls without any extra text.
	 *
	 * @param string $ps_url The URL to check
	 * @param array $pa_options Options include:
	 *		strict = only consider text a valid url if text contains only the url [Default is false]
	 * @return boolean true if it appears to be valid URL, false if not
	 */
	function isURL($ps_url, $pa_options=null) {
	
		if (
			caGetOption('strict', $pa_options, false)
			?
				preg_match("!^(http|ftp|https|rtmp|rtsp|mysql):\/\/[\w\-_]+(\.[\w\-_]+)*([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?$!", $ps_url, $va_matches)
				:
				preg_match("!(http|ftp|https|rtmp|rtsp|mysql):\/\/[\w\-_]+(\.[\w\-_]+)*([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?!", $ps_url, $va_matches)
			) {
			return array(
				'protocol' => $va_matches[1],
				'url' => $ps_url
			);
		}
		return false;
	}
	# ---------------------------------------
	/**
	 * Helper function for use with usort() that returns an array of strings sorted by length
	 */
	function caLengthSortHelper($a,$b){ return strlen($b)-strlen($a); }
	# ---------------------------------------
	/**
	 *
	 */
	function caConvertLineBreaks($ps_text) {
		$vs_text = $ps_text;
		
		# check for tags before converting breaks
		preg_match_all("/<[A-Za-z0-9]+/", $vs_text, $va_tags);
		$va_ok_tags = array("<b", "<i", "<u", "<strong", "<em", "<strike", "<sub", "<sup", "<a", "<img", "<span");

		$vb_convert_breaks = true;
		foreach($va_tags[0] as $vs_tag) {
			if (!in_array($vs_tag, $va_ok_tags)) {
				$vb_convert_breaks = false;
				break;
			}
		}

		if ($vb_convert_breaks) {
			$vs_text = preg_replace("/(\n|\r\n){2}/","<p/>",$vs_text);
			$vs_text = ereg_replace("\n","<br/>",$vs_text);
		}
		
		return $vs_text;
	}
	# ---------------------------------------
	/**
	 * Prints stack trace from point of invokation
	 *
	 * @param array $pa_options Optional array of options. Support options are:
	 *		html = if true, then HTML formatted output will be returned; otherwise plain-text output is returned. [Default is false]
	 *		print = if true output is printed to standard output. [Default is false]
	 *		skip = number of calls to skip from the top of the stack. [Default is 0]
	 * @return string Stack trace output
	 */
	function caPrintStacktrace($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_trace = debug_backtrace();
		
		if (isset($pa_options['skip']) && ($pa_options['skip'] > 0)) {
			$va_trace = array_slice($va_trace, $pa_options['skip']);
		}
		
		$va_buf = array();
		foreach($va_trace as $va_line) {
			if(isset($pa_options['html']) && $pa_options['html']) {
				$va_buf[] = array($va_line['file'], $va_line['class'], $va_line['function'], $va_line['line']);
			} else {
				$va_buf[] = $va_line['file'].':'.($va_line['class'] ? $va_line['class'].':' : '').$va_line['function'].'@'.$va_line['line']."<br/>\n";
			}
		}
		
		if(isset($pa_options['html']) && $pa_options['html']) {
			// TODO: make nicer looking HTML output
			$vs_output = "<table>\n<tr><th>File</th><th>Class</th><th>Function</th><th>Line</th></tr>\n";
			foreach($va_buf as $va_line) {
				$vs_output .= "<tr><td>".join('</td><td>', $va_line)."</td></tr>\n";
			}
			$vs_output .= "</table>\n";
		} else {
			$vs_output = join("\n", $va_buf);
		}
		
		if(isset($pa_options['print']) && $pa_options['print']) {
			print "<pre>{$vs_output}</pre>";
		}
		
		return $vs_output;
	}
	# ---------------------------------------
	/**
	 * Converts expression with fractional expression to decimal equivalent. 
	 * Only fractional numbers are converted to decimal. The surrounding text will be
	 * left unchanged.
	 *
	 * Examples of valid expressions are:
	 *		12 1/2" (= 12.5")
	 *		12 ⅔ ft (= 12.667 ft)
	 *		"Total is 12 3/4 lbs" (= "Total is 12.75 lbs")
	 *
	 * Both text fractions (ex. 3/4) and Unicode fraction glyphs (ex. ¾) may be used.
	 *
	 * @param string $ps_fractional_expression String including fractional expression to convert
	 * @param string $locale The locale of the string to use the right decimal separator
	 * @return string $ps_fractional_expression with fractions replaced with decimal equivalents
	 */
	function caConvertFractionalNumberToDecimal($ps_fractional_expression, $locale="en_US") {
		$ps_fractional_expression = preg_replace("![\n\r\t ]+!", " ", $ps_fractional_expression);
		// convert ascii fractions (eg. 1/2) to decimal
		if (preg_match('!^([\d]*)[ ]*([\d]+)/([\d]+)!', $ps_fractional_expression, $va_matches)) {
			if ((float)$va_matches[2] > 0) {
				$vn_val = ((float)$va_matches[2])/((float)$va_matches[3]);
			} else {
				$vn_val = '';
			}
			$vn_val = sprintf("%4.3f", ((float)$va_matches[1] + $vn_val));
			
			$vn_val = caConvertFloatToLocale($vn_val, $locale);
			$ps_fractional_expression = str_replace($va_matches[0], $vn_val, $ps_fractional_expression);
		} else {
			$sep = caGetDecimalSeparator($locale);
			// replace unicode fractions with decimal equivalents
			foreach([
				'½' => $sep.'5', '⅓' => $sep.'333', '¼' => $sep.'25', '⅛' => $sep.'125',
				'⅔' => $sep.'667', 
				'¾'	=> $sep.'75', '⅜' => $sep.'375', '⅝' => $sep.'625', '⅞' => $sep.'875', '⅒' => $sep.'1'] as $vs_glyph => $vs_val
			) {
				$ps_fractional_expression = preg_replace('![ ]*'.$vs_glyph.'!u', $vs_val, $ps_fractional_expression);	
			}
		}
		
		return $ps_fractional_expression;
	}	
	# ---------------------------------------
	/**
	 * Returns list of values 
	 */
	function caExtractArrayValuesFromArrayOfArrays($pa_array, $ps_key, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_extracted_values = array();
		
		foreach($pa_array as $vm_i => $va_values) {
			if (!isset($va_values[$ps_key])) { continue; }
			$va_extracted_values[] = $va_values[$ps_key];
		}
		
		if (isset($pa_options['removeDuplicates'])) { 
			$va_extracted_values = array_flip(array_flip($va_extracted_values));
		}
		
		return $va_extracted_values;
	}
	# ---------------------------------------
	/**
	 * Takes locale-formatted float (eg. 54,33) and converts it to the "standard"
	 * format needed for calculations (eg 54.33)
	 *
	 * @param string $ps_value The value to convert
	 * @param string $ps_locale The locale of the value
	 * @return float The converted value
	 */
	function caConvertLocaleSpecificFloat($ps_value, $ps_locale = "en_US") {
		try {
			return Zend_Locale_Format::getNumber($ps_value, array('locale' => $ps_locale));
		} catch (Zend_Locale_Exception $e) { // happens when you enter 54.33 but 54,33 is expected in the current locale
			return floatval($ps_value);
		}
	}
	# ---------------------------------------
	/**
	 * Takes a standard formatted float (eg. 54.33) and converts it to the locale
	 * format needed for display (eg 54,33)
	 *
	 * @param string $pn_value The value to convert
	 * @param string $locale Which locale is to be used to return the value
	 * @return float The converted value
	 */
	function caConvertFloatToLocale($pn_value, $locale = "en_US") {
		try {
			return Zend_Locale_Format::toNumber($pn_value, array('locale' => $locale));
		} catch (Zend_Locale_Exception $e) {
			return $pn_value;
		}
	}
	# ---------------------------------------
	/**
	 * Get the decimal separator
	 *
	 * @param string $locale Which locale is to be used to determine the value.
	 * 		If not set, fall back to UI locale. If UI locale is not set, fall back to "en_US"
	 * @return string The separator
	 */
	function caGetDecimalSeparator($locale = null) {
		if(!$locale) {
			global $g_ui_locale;
			$locale = $g_ui_locale;
			if(!$locale) { $locale = 'en_US'; }
		}

		$va_symbols = Zend_Locale_Data::getList($locale,'symbols');
		if(isset($va_symbols['decimal'])){
			return $va_symbols['decimal'];
		} else {
			return '.';
		}
	}
	# ---------------------------------------
	/**
	 * Formats any number of seconds into a readable string
	 *
	 * @param int Seconds to format
	 * @param int Number of divisors to return, ie (3) gives '1 Year, 3 Days, 9 Hours' whereas (2) gives '1 Year, 3 Days'
	 * @param string Seperator to use between divisors
	 * @return string Formatted interval
	*/
	function caFormatInterval($pn_seconds, $pn_precision = -1, $ps_separator = ', ') {
		$va_divisors = Array(
			31536000 => array('singular' => _t('year'), 'plural' => _t('years'), 'divisor' => 31536000),
			2628000 => array('singular' => _t('month'), 'plural' => _t('months'), 'divisor' => 2628000),
			86400 => array('singular' => _t('day'), 'plural' => _t('days'), 'divisor' => 86400),
			3600 => array('singular' => _t('hour'), 'plural' => _t('hours'), 'divisor' => 3600),
			60 => array('singular' => _t('minute'), 'plural' => _t('minutes'), 'divisor' => 60),
			1 => array('singular' => _t('second'), 'plural' => _t('seconds'), 'divisor' => 1)
		);
	
		krsort($va_divisors);
	
		$va_out = array();
		
		foreach($va_divisors as $vn_divisor => $va_info) {
			// If there is at least 1 of the divisor's time period
			if($vn_value = floor($pn_seconds / $vn_divisor)) {
				// Add the formatted value - divisor pair to the output array.
				// Omits the plural for a singular value.
				if($vn_value == 1) {
					$va_out[] = "{$vn_value} ".$va_info['singular'];
				} else {
					$va_out[] = "{$vn_value} ".$va_info['plural'];
				}
	
				// Stop looping if we've hit the precision limit
				$pn_precision--;
				if($pn_precision == 0) {
					break;
				}
			}
	
			// Strip this divisor from the total seconds
			$pn_seconds %= $vn_divisor;
		}
	
		if (!sizeof($va_out)) {
			$va_out[] = "0 ".$va_info['plural'];
		}
		return implode($ps_separator, $va_out);
	}
	# ---------------------------------------
	/**
	 * Parses string for form element dimension. If a simple integer is passed then it is considered
	 * to be expressed as the number of characters to display. If an integer suffixed with 'px' is passed
	 * then the dimension is considered to be expressed in pixels. If non-integers are passed they will
	 * be cast to integers.
	 *
	 * An array is always returned, with two keys: 
	 *		dimension = the integer value of the dimension
	 *		type = either 'pixels' or 'characters'
	 *
	 * @param string $ps_dimension
	 * @return array An array describing the parsed value or null if no value was passed
	*/
	function caParseFormElementDimension($ps_dimension) {
		$ps_dimension = trim($ps_dimension);
		if (!$ps_dimension) { return null; }
		
		if (preg_match('!^([\d]+)[ ]*(px)$!', $ps_dimension, $va_matches)) {
			return array(
				'dimension' => (int)$va_matches[1],
				'type' => 'pixels'
			);
		}
		
		return array(
			'dimension' => (int)$ps_dimension,
			'type' => 'characters'
		);
	}
	# ---------------------------------------
	/**
	 * Parses string for element dimension. If a simple integer is passed then it is considered
	 * to be expressed as pixels. If an integer suffixed with 'px' is passed. Percentages are parsed as relative dimensions.
	 * then the dimension is considered to be expressed in pixels. If non-integers are passed they will
	 * be cast to integers.
	 *
	 * An array is always returned, with three keys: 
	 *		dimension = the integer value of the dimension
	 *		expression = CSS dimension (eg. 500px or 100%)
	 *		type = either 'pixels' or 'relative'
	 *
	 * @param string $ps_dimension
	 * @param array $pa_options Options include:
	 *		returnAsString = return normalized dimension expression as string rather than an array of values. [Default is false]
	 *		default = dimension expression to use if $ps_dimension is empty. [Default is null]
	 * @return array An array describing the parsed value or null if no value was passed
	*/
	function caParseElementDimension($ps_dimension, $pa_options=null) {
		if (!($ps_dimension = trim($ps_dimension))) { $ps_dimension = caGetOption('default', $pa_options, null); }
		if (!$ps_dimension) { return null; }
		
		$va_val = null;
		if (preg_match('!^([\d]+)[ ]*px$!', $ps_dimension, $va_matches)) {
			$va_val = array(
				'dimension' => (int)$va_matches[1],
				'expression' => $ps_dimension,
				'type' => 'pixels'
			);
		}
		
		if (preg_match('!^([\d\.]+)[ ]*%$!', $ps_dimension, $va_matches)) {
			$va_val = array(
				'dimension' => (int)$va_matches[1],
				'expression' => $ps_dimension,
				'type' => 'relative'
			);
		}
		
		if(!$va_val && $ps_dimension) {
			$va_val = array(
				'dimension' => (int)$ps_dimension,
				'expression' => "{$ps_dimension}px",
				'type' => 'pixels'
			);
		}
		if (!$va_val) { return null; }
		return caGetOption('returnAsString', $pa_options, false) ? $va_val['expression'] : $va_val;
	}
	# ---------------------------------------
	/**
	 * Sorts an array of arrays based upon one or more values in the second-level array.
	 * Top-level keys are preserved in the sort.
	 *
	 * @param array $pa_values The array to sort. It should be an array of arrays (aka. 2-dimensional)
	 * @param array $pa_sort_keys An array of keys in the second-level array to sort by
	 * @param array $pa_options Options include:
	 * 		dontRemoveKeyPrefixes = By default keys that are period-delimited will have the prefix before the first period removed (this is to ease sorting by field names). Set to true to disable this behavior. [Default is false]
	 * @return array The sorted array
	*/
	function caSortArrayByKeyInValue($pa_values, $pa_sort_keys, $ps_sort_direction="ASC", $pa_options=null) {
		$va_sort_keys = array();
		if (caGetOption('dontRemoveKeyPrefixes', $pa_options, false)) {
			foreach ($pa_sort_keys as $vs_field) {
				$va_tmp = explode('.', $vs_field);
				if (sizeof($va_tmp) > 1) { array_shift($va_tmp); }
				$va_sort_keys[] = join(".", $va_tmp);
			}
		} else {
			$va_sort_keys = $pa_sort_keys;
		}
		$va_sorted_by_key = array();
		foreach($pa_values as $vn_id => $va_data) {
			if (!is_array($va_data)) { continue; }
			$va_key = array();
			foreach($va_sort_keys as $vs_sort_key) {
				$va_key[] = isset($va_data[$vs_sort_key.'_sort_']) ? $va_data[$vs_sort_key.'_sort_'] : $va_data[$vs_sort_key];  // an alternative sort-specific value for a key may be present with the suffix "_sort_"; when present we use this in preference to the key value
			}
			$va_sorted_by_key[join('/', $va_key)][$vn_id] = $va_data;
		}
		ksort($va_sorted_by_key);
		if (strtolower($ps_sort_direction) == 'desc') {
			$va_sorted_by_key = array_reverse($va_sorted_by_key);
		}
		
		$pa_values = array();
		foreach($va_sorted_by_key as $vs_key => $va_data) {
			foreach($va_data as $vn_id => $va_values) {
				$pa_values[$vn_id] = $va_values;
			}
		}
		
		return $pa_values;
	}
	# ---------------------------------------
	/**
	 *
	 *
	 * @param array $pa_array 
	 * @return array The sorted array
	*/
	function caTranslateArrayKeys($pa_array) {
		if (!is_array($pa_array)) { return null; }
		$pa_trans_array = array();
		foreach($pa_array as $vs_key => $vm_val) {
			if (is_array($vm_val)) {
				$pa_trans_array[$vs_key] = caTranslateArrayKeys($vm_val);
			} else {
				$pa_trans_array[$vs_key] = _t($vm_val);
			}
		}
		return $pa_trans_array;
	}
	# ---------------------------------------
	/**
	 * Make first character upper-case in UTF-8 safe manner.
	 * Basically an implementation of the missing PHP mb_ucfirst() function
	 *
	 * @param string $ps_string The string to process
	 */
	function caUcFirstUTF8Safe($ps_string, $pb_capitalize_all_words=false) {
		if ($pb_capitalize_all_words) {
			$va_words = preg_split('![ ]+!', $ps_string);
		} else {
			$va_words = array($ps_string);
		}
		
		$va_proc_words = array();
		foreach($va_words as $vs_string) {
			$vn_strlen = mb_strlen($vs_string, 'UTF-8');
			$vs_first_char = mb_substr($vs_string, 0, 1, 'UTF-8');
			$va_proc_words[] = mb_strtoupper($vs_first_char, 'UTF-8').mb_substr($vs_string, 1, $vn_strlen - 1, 'UTF-8');
		}
		return join(' ', $va_proc_words);
	}
	# ---------------------------------------
	/**
	 * Strips off any leading punctuation leaving letters or numbers as the first character(s)
	 *
	 * @param string $ps_string The string to process
	 */
	function caStripLeadingPunctuation($ps_string) {
		return preg_replace('!^[^A-Za-z0-9]+!u', '', trim($ps_string));
	}
	# ---------------------------------------
	/**
	 * Remove all HTML tags and their contents 
	 *
	 * @param string $ps_string The string to process
	 * @return string $ps_string with HTML tags and associated content removed
	 */
	function caStripTagsAndContent($ps_string) {
		$o_doc = str_get_dom($ps_string);	
		foreach($o_doc("*") as $o_node) {
			if ($o_node->tag != '~text~') {
				$o_node->delete();
			}
		}
		$vs_proc_string = $o_doc->html();
		$vs_proc_string = str_replace("<~root~>", "", $vs_proc_string);
		$vs_proc_string = str_replace("</~root~>", "", $vs_proc_string);
		return trim($vs_proc_string);
	}
	# ---------------------------------------
	/**
	  *
	  */
	function caGetCacheObject($ps_prefix, $pn_lifetime=86400, $ps_cache_dir=null, $pn_cleaning_factor=100) {
		if (!$ps_cache_dir) { $ps_cache_dir = __CA_APP_DIR__.'/tmp'; }
		$va_frontend_options = array(
			'cache_id_prefix' => $ps_prefix,
			'lifetime' => $pn_lifetime, 		
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => false,			/* immediate read after write is enabled (we don't write often) */
			'automatic_cleaning_factor' => $pn_cleaning_factor, 	/* automatic cache cleaning */
			'automatic_serialization' => true	/* we store arrays, so we have to enable that */
		);
		
		$va_backend_options = array(
			'cache_dir' =>  $ps_cache_dir,		/* where to store cache data? */
			'file_locking' => true,				/* cache corruption avoidance */
			'read_control' => false,			/* no read control */
			'file_name_prefix' => $ps_prefix,	/* prefix of cache files */
			'cache_file_perm' => 0700			/* permissions of cache files */
		);


		try {
			return Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
		} catch (exception $e) {
			return null;
		}
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns the media class to which a MIME type belongs, or null if the MIME type does not belong to a class. Possible classes are 'image', 'video', 'audio' and 'document'
	 *
	 * @param string $ps_mimetype A media MIME type
	 *
	 * @return string The media class that includes the specified MIME type, or null if the MIME type does not belong to a class. Returned classes are 'image', 'video', 'audio' and 'document'
	 */
	function caGetMediaClass($ps_mimetype) {
		$va_tmp = explode("/", $ps_mimetype);
		
		switch($va_tmp[0]) {
			case 'image':
				return 'image';
				break;
			case 'video':
				return 'video';
				break;
			case 'audio':
				return 'audio';
				break;
			default:
				switch($ps_mimetype) {
					case 'application/pdf':
					case 'application/postscript':
					case 'text/xml':
					case 'text/html':
					case 'text/plain':
					case 'application/msword':
						return 'document';
						break;
					case 'x-world/x-qtvr':
					case 'application/x-shockwave-flash':
						return 'video';
						break;
				}
				break;
		}
		return null;
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Returns all MIME types contained in the specified media class. Note that inclusive type groups will be returned as a general group type.
	 * Eg. for class 'images' the inclusive MIME type 'image/*' is returned rather than a list of specific image types such as 'image/jpeg', 'image/tiff', etc.
	 *
	 * @param string $ps_class The media class to return MIME types for. Valid classes are 'image', 'video', 'audio' and 'document'
	 * @param array $pa_options Options include
	 *		returnAsRegex = if set a string suitable for use as a regular expression for matching of MIME types is returned instead of an array
	 *
	 * @return mixed An array of MIME types for the class, or a regular expression (string) if returnAsRegex option is set
	 */
	function caGetMimetypesForClass($ps_class, $pa_options=null) {
		$vb_return_as_regex = (isset($pa_options['returnAsRegex']) && $pa_options['returnAsRegex']) ? true : false;
		switch($ps_class) {
			case 'image':
				if ($vb_return_as_regex) {
					return 'image/.*';
				} else {
					return array('image/*');
				}
				break;
			case 'video':
				if ($vb_return_as_regex) {
					return 'video/.*|x-world/x-qtvr|application/x-shockwave-flash';
				} else {
					return array('video/*', 'x-world/x-qtvr', 'application/x-shockwave-flash');
				}
				break;
			case 'audio':
				if ($vb_return_as_regex) {
					return 'audio/.*';
				} else {
					return array('audio/*');
				}
				break;
			case 'document':
				if ($vb_return_as_regex) {
					return 'application/pdf|application/postscript|text/xml|text/html|text/plain|application/msword|officedocument';
				} else {
					return array('application/pdf', 'application/postscript', 'text/xml', 'text/html', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
				}
				break;
		}
		return null;
	}
	# ---------------------------------------
	/**
	  * Creates an md5-based cached key from an array of options
	  *
	  * @param array $pa_options An options array
	  * @param string $ps_additional_text Additional text to add to key
	  * @return string An MD5 cache key for the options array
	  */
	function caMakeCacheKeyFromOptions($pa_options, $ps_additional_text=null) {
		if (!is_array($pa_options)) { return md5($pa_options.$ps_additional_text); }
		foreach($pa_options as $vs_key => $vm_value) {
			if (is_object($vm_value)) { unset($pa_options[$vs_key]); }
		}
		
		return md5(print_R($pa_options, true).$ps_additional_text);
	}
	# ---------------------------------------
	/**
	  * Returns passed value or default (as defined in $g_default_display_value global) if passed value is blank
	  *
	  * @param string $ps_text The text value to return if not blank
	  * @return string The text or default value if text is blank
	  */
	function caReturnDefaultIfBlank($ps_text) {
		global $g_default_display_value;
		
		return trim($ps_text) ? $ps_text : $g_default_display_value;
	}
	# ---------------------------------------
	/**
	  * Formats JSON-encoded data into a format more easily read by semi-sentient life forms
	  *
	  * @param string $ps_json The JSON-encoded data
	  * @return string The JSON-encoded data formatted for ease of reading. Other than spacing and indentation, the returned data is unchanged from the input.
	  */
	function caFormatJson($json) {
		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;
	
		for ($i=0; $i<=$strLen; $i++) {
	
			// Grab the next character in the string.
			$char = substr($json, $i, 1);
	
			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;
			
			// If this character is the end of an element, 
			// output a new line and indent the next line.
			} else if(($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}
			
			// Add the character to the result string.
			$result .= $char;
	
			// If the last character was the beginning of an element, 
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos ++;
				}
				
				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}
			
			$prevChar = $char;
		}
	
		return $result.$newLine;
	}
	# ---------------------------------------
	function caFormatXML($ps_xml){  
		require_once(__CA_LIB_DIR__.'/core/Parsers/XMLFormatter.php');

		$va_options = array(
			"paddingString" => " ",
			"paddingMultiplier" => 2,
			"wordwrapCData" => false,
		);

		$vr_input = fopen('data://text/plain,'.$ps_xml, 'r');
		$vr_output = fopen('php://temp', 'w+');

		$vo_formatter = new XML_Formatter($vr_input, $vr_output, $va_options);

		try {
			$vo_formatter->format();
			rewind($vr_output);
			return stream_get_contents($vr_output)."\n";
		} catch (Exception $e) {
			return false;
		}
	}
	# ---------------------------------------
	/**
	  * Parses natural language date and returns pair of Unix timestamps defining date/time range
	  *
	  * @param string $ps_date_expression A valid date/time expression as described in http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	  * @return array The start and end timestamps for the parsed date/time range. Array contains values key'ed under 0 and 1 and 'start' and 'end'; null is returned if expression cannot be parsed.
	  */
	function caDateToUnixTimestamps($ps_date_expression) {
		// don't mangle unix timestamps
		if(preg_match('/^[0-9]{10}$/', $ps_date_expression)) {
			return array('start' => (int) $ps_date_expression, 'end' => (int) $ps_date_expression);
		}

		$o_tep = new TimeExpressionParser();
		if ($o_tep->parse($ps_date_expression)) {
			return $o_tep->getUnixTimestamps();
		}
		return null;
	}
	# ---------------------------------------
	/**
	  * Parses natural language date and returns a Unix timestamp 
	  *
	  * @param string $ps_date_expression A valid date/time expression as described in http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	  * @return int A Unix timestamp for the date expression or null if expression cannot be parsed.
	  */
	function caDateToUnixTimestamp($ps_date_expression) {
		$o_tep = new TimeExpressionParser();
		if ($o_tep->parse($ps_date_expression)) {
			$va_date = $o_tep->getUnixTimestamps();
			return isset($va_date['start']) ? $va_date['start'] : null;
		}
		return null;
	}
	# ---------------------------------------
	/**
	  * Parses natural language date and returns pair of historic timestamps defining date/time range
	  *
	  * @param string $ps_date_expression A valid date/time expression as described in http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	  * @return array The start and end timestamps for the parsed date/time range. Array contains values key'ed under 0 and 1 and 'start' and 'end'; null is returned if expression cannot be parsed.
	  */
	function caDateToHistoricTimestamps($ps_date_expression) {
		$o_tep = new TimeExpressionParser();
		if ($o_tep->parse($ps_date_expression)) {
			return $o_tep->getHistoricTimestamps();
		}
		return null;
	}
	# ---------------------------------------
	/**
	  * Parses natural language date and returns an historic timestamp
	  *
	  * @param string $ps_date_expression A valid date/time expression as described in http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	  * @return float An historic timestamp for the date expression or null if expression cannot be parsed.
	  */
	function caDateToHistoricTimestamp($ps_date_expression) {
		$o_tep = new TimeExpressionParser();
		if ($o_tep->parse($ps_date_expression)) {
			$va_date = $o_tep->getHistoricTimestamps();
			return isset($va_date['start']) ? $va_date['start'] : null;
		}
		return null;
	}
	# ---------------------------------------
	/**
	  * Determine if date expression can be parsed 
	  *
	  * @param string $ps_date_expression A date/time expression as described in http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	  * @return bool True if expression can be parsed
	  */
	function caIsValidDate($ps_date_expression) {
		$o_tep = new TimeExpressionParser();
		return $o_tep->parse($ps_date_expression);
	}
	# ---------------------------------------
	/**
	  * Converts Unix timestamp to historic date timestamp
	  *
	  * @param int $pn_timestamp A Unix-format timestamp
	  * @return float Equivalent value as floating point historic timestamp value, or null if Unix timestamp was not valid.
	  */
	function caUnixTimestampToHistoricTimestamps($pn_timestamp) {
		$o_tep = new TimeExpressionParser();
		return $o_tep->unixToHistoricTimestamp($pn_timestamp);
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caSeemsUTF8($str){
		$length = strlen($str);
		for ($i=0; $i < $length; $i++) {
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; # 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}
	# ---------------------------------------
	/**
	 * Recursively encode array (or string) as UTF8 text
	 *
	 * @param mixed $pm_input Array or string to encode
	 * @return mixed Encoded array or string
	 */
	function caEncodeUTF8Deep(&$pm_input) {
		if (is_string($pm_input)) {
			$pm_input = Encoding::toUTF8($pm_input);
		} else if (is_array($pm_input)) {
			foreach ($pm_input as &$vm_value) {
				caEncodeUTF8Deep($vm_value);
			}

			unset($vm_value);
		} else if (is_object($pm_input)) {
			$va_keys = array_keys(get_object_vars($pm_input));

			foreach ($va_keys as $vs_key) {
				caEncodeUTF8Deep($pm_input->$vs_key);
			}
		}
		return $pm_input;
	}
	# ---------------------------------------
	/**
	 * Converts all entities in string
	 *
	 * @param string $ps_string Text that might have accent characters
	 * @param int $pn_quotes
	 * @param string $ps_charset
	 *
	 * @return string
	 */
	function caDecodeAllEntities($ps_string, $pn_quotes = ENT_COMPAT, $ps_charset = 'UTF-8') {
  		return html_entity_decode(preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/', 'caConvertEntity', html_entity_decode($ps_string)), $pn_quotes, $ps_charset); 
	}
	# ---------------------------------------
	/**
	 * Helper function for decode_entities_full().
	 *
	 * This contains the full HTML 4 Recommendation listing of entities, so the default to discard  
	 * entities not in the table is generally good. Pass false to the second argument to return 
	 * the faulty entity unmodified, if you're ill or something.
	 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
	 */
	function caConvertEntity($matches, $destroy = true) {
	  static $table = array('quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;','OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;','Yuml' => '&#376;','circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;','lrm' => '&#8206;','rlm' => '&#8207;','ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;','rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;','Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;','fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;','Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;','Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;','Xi' => '&#926;','Omicron' => '&#927;','Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;','Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;','theta' => '&#952;','iota' => '&#953;','kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;','pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;','tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;','psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;','hellip' => '&#8230;','prime' => '&#8242;','Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;','alefsym' => '&#8501;','larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;','rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;','isin' => '&#8712;','notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;','prop' => '&#8733;','infin' => '&#8734;','ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;','there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;','ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;','sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;','otimes' => '&#8855;','perp' => '&#8869;','sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;','loz' => '&#9674;','spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => '&#160;','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;','curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;','not' => '&#172;','shy' => '&#173;','reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;','acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;','cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;','frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;','Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;','Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;','Ecirc' => '&#202;','Euml' => '&#203;','Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;','Oacute' => '&#211;','Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;','Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;','auml' => '&#228;','aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;','igrave' => '&#236;','iacute' => '&#237;','icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;','ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;','divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;','uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;'
						   );
	  if (isset($table[$matches[1]])) return $table[$matches[1]];
	  // else 
	  return $destroy ? '' : $matches[0];
	}
	# ---------------------------------------
	/**
	 * Converts all accent characters to ASCII characters.
	 *
	 * If there are no accent characters, then the string given is just returned.
	 *
	 * @param string $string Text that might have accent characters
	 * @return string Filtered string with replaced "nice" characters.
	 */
	function caRemoveAccents($string) {
		if ( !preg_match('/[\x80-\xff]/', $string) )
			return $string;
	
		if (caSeemsUTF8($string)) {
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => '');
	
			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);
	
			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
	
			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}
	
		return $string;
	}
	# ---------------------------------------
	/**
	 * Escape argument for use with exec()
	 *
	 * @param string parameter value
	 * @return string escaped parameter value, surrounded with single quotes and ready for use
	 */
	function caEscapeShellArg($ps_text) {
		return escapeshellarg($ps_text);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 *
	 *
	 * @param string $ps_date_expression Start of date range, as Unix timestamp
	 * @param array $pa_options All options supported by TimeExpressionParser::getText() are supported
	 *
	 * @return array
	 */
	function caGetISODates($ps_date_expression, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$o_tep = new TimeExpressionParser();
		
		if (!$o_tep->parse($ps_date_expression)) { return null; }
		
		return array(
			'start' => $o_tep->getText(array_merge($pa_options, array('start_as_iso8601' => true))),
			'end' => $o_tep->getText(array_merge($pa_options, array('end_as_iso8601' => true)))
		);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Format time expression parser expression using date().
	 * @see http://php.net/manual/en/function.date.php
	 * @see http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	 *
	 * @param string $ps_date_expression valid TEP expression
	 * @param string $ps_format date() format string
	 * @return null|string
	 */
	function caFormatDate($ps_date_expression, $ps_format = 'c') {
		$va_unix_timestamps = caDateToUnixTimestamps($ps_date_expression);
		if(!is_numeric($va_unix_timestamps['start'])) { return null; }

		return date($ps_format, (int) $va_unix_timestamps['start']);
	}
	# ------------------------------------------------------------------------------------------------
	/**
	 * Format time expression parser expression using gmdate().
	 * @see http://php.net/manual/en/function.gmdate.php
	 * @see http://docs.collectiveaccess.org/wiki/Date_and_Time_Formats
	 *
	 * @param string $ps_date_expression valid TEP expression
	 * @param string $ps_format gmdate() format string
	 * @return null|string
	 */
	function caFormatGMDate($ps_date_expression, $ps_format = 'c') {
		$va_unix_timestamps = caDateToUnixTimestamps($ps_date_expression);
		if(!is_numeric($va_unix_timestamps['start'])) { return null; }

		return gmdate($ps_format, (int) $va_unix_timestamps['start']);
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caLogEvent($ps_code, $ps_message, $ps_source=null) {
		$t_log = new EventLog();
		return $t_log->log(array('CODE' => $ps_code, 'MESSAGE' => $ps_message, 'SOURCE' => $ps_source));
	}
	# ---------------------------------------
	/**
	 * Truncates text to a maximum length, including an ellipsis ("...")
	 *
	 * @param string $ps_text Text to (possibly) truncate
	 * @param int $pn_max_length Maximum number of characters to return; if omitted defaults to 30 characters
	 * @param string $ps_orientation Side of string to based truncation from. "start" will truncate $pn_max_length characters from the beginning; "end" $pn_max_length characters from the end. [Default="start"]
	 * @return string The truncated text
	 */
	function caTruncateStringWithEllipsis($ps_text, $pn_max_length=30, $ps_side="start") {
		if ($pn_max_length < 1) { $pn_max_length = 30; }
		if (mb_strlen($ps_text) > $pn_max_length) {
			if (strtolower($ps_side == 'end')) {
				$vs_txt = mb_substr($ps_text, mb_strlen($ps_text) - $pn_max_length + 3, null, 'UTF-8');
				if (preg_match("!<[^>]*$!", $vs_txt, $va_matches)) {
					$vs_txt = preg_replace("!{$va_matches[0]}$!", '', $vs_txt);
				}
				$ps_text = "...{$vs_txt}";
			} else {
				$vs_txt = mb_substr($ps_text, 0, ($pn_max_length - 3), 'UTF-8');
				if (preg_match("!(<[^>]*)$!", $vs_txt, $va_matches)) {
					$vs_txt = preg_replace("!{$va_matches[0]}$!", '', $vs_txt);
				}
				$ps_text = "{$vs_txt}...";
			}
		}
		return $ps_text;
	}
	# ---------------------------------------
	/**
	 * Determines if current request was from from command line
	 *
	 * @return boolean True if request wasrun from command line, false if not
	 */
	function caIsRunFromCLI() {
		if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
			return true;
		} else {
			return false;
		}
	}
	# ---------------------------------------
	/**
	 * Determines if current request was via service.php
	 *
	 * @return boolean true if request addressed service.php, false if not
	 */
	function caIsServiceRequest() {
		if(isset($_SERVER['SCRIPT_NAME']) && ($_SERVER['SCRIPT_NAME'] == '/service.php')){
			return true;
		} else {
			return false;
		}
	}
	# ---------------------------------------
	/**
	 * Extract specified option from an options array. 
	 * An options array is simply an associative array where keys are option names and values are option values.
	 * caGetOption() provides a simple interface to grab values, force default values for non-existent settings and enforce simple validation rules.
	 *
	 * @param mixed $pm_option The option to extract. If an array is provided then each option is tried, in order, until a non-false value is found.
	 * @param array $pa_options The options array to extract values from. An instance of Zend_Console_Getopt may also be passed, allowing processing of command line options.
	 * @param mixed $pm_default An optional default value to return if $ps_option is not set in $pa_options 
	 * @param array $pa_parse_options Option parser options (cross your eyes now) include:
	 *		forceLowercase = transform option value to all lowercase [default=false]
	 *		forceUppercase = transform option value to all uppercase [default=false]
	 *		validValues = array of values that are possible for this option. If the option value is not in the list then the default is returned. If no default is set then the first value in the validValues list is returned. Note that by default all comparisons are case-insensitive. 
	 *		caseSensitive = do case sensitive comparisons when checking the option value against the validValues list [default=false]
	 *		castTo = array|int|string|float|bool
	 *		delimiter = A delimiter, or array of delimiters, to break a string option value on. When this option is set an array will always be returned. [Default is null]
	 * @return mixed
	 */
	function caGetOption($pm_option, $pa_options, $pm_default=null, $pa_parse_options=null) {
		if (is_object($pa_options) && is_a($pa_options, 'Zend_Console_Getopt')) {
			$pa_options = array($pm_option => $pa_options->getOption($pm_option));
		}
		$va_valid_values = null;
		$vb_case_insensitive = false;
		if (isset($pa_parse_options['validValues']) && is_array($pa_parse_options['validValues'])) {
			$va_valid_values = $pa_parse_options['validValues'];
			if (!isset($pa_parse_options['caseSensitive']) || !$pa_parse_options['caseSensitive']) {
				$va_valid_values = array_map(function($v) { return mb_strtolower($v); }, $va_valid_values);
				$vb_case_insensitive = true;
			}
		}
		
		if (is_array($pm_option)) { 
			$vm_val = null;
			foreach($pm_option as $ps_option) {
				if (isset($pa_options[$ps_option]) && !is_null($pa_options[$ps_option])) {
					$vm_val = $pa_options[$ps_option];
					break;
				}
			}
			if (is_null($vm_val)) { $vm_val = $pm_default; }
		} else {
			$vm_val = (isset($pa_options[$pm_option]) && !is_null($pa_options[$pm_option])) ? $pa_options[$pm_option] : $pm_default;
		}
		
		if (
			((is_string($vm_val) && !isset($pa_parse_options['castTo'])) || (isset($pa_parse_options['castTo']) && ($pa_parse_options['castTo'] == 'string')))
			&&
			(!isset($pa_parse_options['delimiter']) || !($va_delimiter = $pa_parse_options['delimiter']))
			&& 
			(is_array($va_valid_values))
		) {
			if (!in_array($vb_case_insensitive ? mb_strtolower($vm_val) : $vm_val, $va_valid_values)) {
				$vm_val = $pm_default;
				if (!in_array($vb_case_insensitive ? mb_strtolower($vm_val) : $vm_val, $va_valid_values)) {
					$vm_val = array_shift($va_valid_values);
				}
			}
		}
		
		if (isset($pa_parse_options['forceLowercase']) && $pa_parse_options['forceLowercase']) {
			$vm_val = is_array($vm_val) ? array_map('mb_strtolower', $vm_val) : mb_strtolower($vm_val);
		} elseif (isset($pa_parse_options['forceUppercase']) && $pa_parse_options['forceUppercase']) {
			$vm_val = is_array($vm_val) ? array_map('mb_strtoupper', $vm_val) : mb_strtoupper($vm_val);
		}
		
		$vs_cast_to = (isset($pa_parse_options['castTo']) && ($pa_parse_options['castTo'])) ? strtolower($pa_parse_options['castTo']) : '';
		switch($vs_cast_to) {
			case 'int':
			case 'integer':
				$vm_val = (int)$vm_val;
				break;
			case 'float':
			case 'decimal':
				$vm_val = (float)$vm_val;
				break;
			case 'string':
				$vm_val = (string)$vm_val;
				break;
			case 'bool':
			case 'boolean':
				$vm_val = (bool)$vm_val;
				break;
			case 'array':
				if(!is_array($vm_val)) {
					if (strlen($vm_val)) {
						$vm_val = array($vm_val);
					} else {
						$vm_val = array();
					}
				}
				break;
		}
		
		if (is_string($vm_val) && (isset($pa_parse_options['delimiter']) && ($va_delimiter = $pa_parse_options['delimiter']))) {
			if (!is_array($va_delimiter)) { $va_delimiter = array($va_delimiter); }
			
			$va_split_vals = preg_split('![ ]*('.join('|', $va_delimiter).')[ ]*!', $vm_val);
			$va_split_vals = array_filter($va_split_vals, "strlen");
			
			if (is_array($va_valid_values)) {
				$va_filtered_vals = [];
				foreach($va_split_vals as $vm_val) {
					if (in_array($vb_case_insensitive ? mb_strtolower($vm_val) : $vm_val, $va_valid_values)) {
						$va_filtered_vals[] = $vm_val;
					}
				
					if (!sizeof($va_filtered_vals) && $pm_default) { $va_filtered_vals[] = $pm_default; }
					$va_split_vals = $va_filtered_vals;
				}
			}
			
			return $va_split_vals;
		}
		
		return $vm_val;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_options
	 * @param array $pa_defaults
	 * @return array
	 */
	function caGetOptions($pa_options, $pa_defaults) {
		$va_proc_options = is_array($pa_options) ? $pa_options : array();
		
		foreach($pa_defaults as $vs_opt => $vs_opt_default_val) {
			if (!isset($va_proc_options[$vs_opt])) { $va_proc_options[$vs_opt] = $vs_opt_default_val; }
		}
		return $va_proc_options;
	}
	# ---------------------------------------
	/**
	 * Removes from supplied array values that begin with binary (non-character) data. 
	 * Arrays may be of any depth. 
	 *
	 * Note that function is of limited use outside of the case it was designed for: to remove binary entries from extracted EXIF metadata arrays.
	 *
	 * @param array $pa_array The array to sanitize
	 * @param array $pa_options
	 *        allowStdClass = stdClass object array values are allowed. This is useful for arrays that are about to be passed to json_encode [Default=false]
	 *		  removeNonCharacterData = remove non-character data from all array value. This option leaves all character data in-place [Default=false]
	 * @return array The sanitized array
	 */
	function caSanitizeArray($pa_array, $pa_options=null) {
		if (!is_array($pa_array)) { return array(); }
		$vb_allow_stdclass = caGetOption('allowStdClass', $pa_options, false);
		$vb_remove_noncharacter_data = caGetOption('removeNonCharacterData', $pa_options, false);

		foreach($pa_array as $vn_k => $vm_v) {
			if (is_array($vm_v)) {
				$pa_array[$vn_k] = caSanitizeArray($vm_v, $pa_options);
			} else {
				if($vb_allow_stdclass && is_object($vm_v) && (get_class($vm_v) == 'stdClass')){
					continue;
				}

				if ((!preg_match("!^\X+$!", $vm_v)) || (!mb_detect_encoding($vm_v))) {
					unset($pa_array[$vn_k]);
					continue;
				}
				
				if ($vb_remove_noncharacter_data) {
					$pa_array[$vn_k] = caSanitizeStringForJsonEncode($pa_array[$vn_k]);
				}
			}
		}
		return $pa_array;
	}
	# ---------------------------------------
	/**
	 * Process all string values in an array with HTMLPurifier. Arrays may be of any depth. If a string is passed it will be purified and returned.
	 *
	 * @param array $pm_array The array or string to purify
	 * @param array $pa_options Array of options:
	 *		purifier = HTMLPurifier instance to use for processing. If null a new instance will be used. [Default is null]
	 * @return array The purified array
	 */
	function caPurifyArray($pa_array, $pa_options=null) {
		if (!is_array($pa_array)) { return array(); }

		if (!(($o_purifier = caGetOption('purifier', $pa_options, null)) instanceof HTMLPurifier)) {
			$o_purifier = new HTMLPurifier();	
		}	
		
		if (!is_array($pa_array)) { return $o_purifier->purify($pa_array); }	
		
		foreach($pa_array as $vn_k => $vm_v) {
			if (is_array($vm_v)) {
				$pa_array[$vn_k] = caPurifyArray($vm_v, $pa_options);
			} else {
				if (!is_null($vm_v)) {
					$pa_array[$vn_k] = $o_purifier->purify($vm_v);
				}
			}
		}
		return $pa_array;
	}
	# ---------------------------------------
	/**
	 * Returns a regexp string to check if a string is a valid roman number
	 *
	 * @return string The PCRE regexp
	 */
	function caRomanNumeralsRegexp() {
		return "M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})";
	}
	
	# ---------------------------------------
	/**
	 * Detects if a string is a valid roman number
	 *
	 * @param string $pa_string The string to analyze
	 * @return boolean True if string is a roman number, false otherwise
	 */
	function caIsRomanNumerals($pa_string) {
		if ($pa_string === NULL) return false;
		$pattern = "/^".caRomanNumeralsRegexp()."$/";
		return preg_match($pattern, $pa_string);
	}
	# ---------------------------------------
	/**
	 * Converts an arabic int to a roman number
	 * 
	 * Source : http://www.go4expert.com/forums/showthread.php?t=4948
	 *
	 * @param $input_arabic_numeral The int to convert
	 * @return string Roman number resulting from the conversion
	 */
	function caArabicRoman($num) {
		// Make sure that we only use the integer portion of the value
		$n = intval($num);
		$result = '';
		
		// Declare a lookup array that we will use to traverse the number:
		$lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
				'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
				'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
		
		foreach ($lookup as $roman => $value)
		{
			// Determine the number of matches
			$matches = intval($n / $value);
		
			// Store that many characters
			$result .= str_repeat($roman, $matches);
		
			// Substract that from the number
			$n = $n % $value;
		}
		
		// The Roman numeral should be built, return it
		return $result;
	}
	# ---------------------------------------
	/**
	 * Converts a roman number to arabic numerals
	 *
	 * Source : pear/Numbers/Roman.php
	 *
	 * @param string $roman The string to convert
	 * @return mixed int if converted, false if no valid roman number supplied   
	 */
	function caRomanArabic($roman) {
		$conv = array(
            array("letter" => 'I', "number" => 1),
            array("letter" => 'V', "number" => 5),
            array("letter" => 'X', "number" => 10),
            array("letter" => 'L', "number" => 50),
            array("letter" => 'C', "number" => 100),
            array("letter" => 'D', "number" => 500),
            array("letter" => 'M', "number" => 1000),
            array("letter" => 0,   "number" => 0)
        );
        $arabic = 0;
        $state  = 0;
        $sidx   = 0;
        $len    = strlen($roman) - 1;
        while ($len >= 0) {
            $i = 0;
            $sidx = $len;
            while ($conv[$i]['number'] > 0) {
                if (strtoupper($roman[$sidx]) == $conv[$i]['letter']) {
                    if ($state > $conv[$i]['number']) {
                        $arabic -= $conv[$i]['number'];
                    } else {
                        $arabic += $conv[$i]['number'];
                        $state   = $conv[$i]['number'];
                    }
                }
                $i++;
            }
            $len--;
        }
        return($arabic);
	}
	# ----------------------------------------
	/**
	 * Generic debug function for shiny variable output
	 * @param mixed $vm_data content to print
	 * @param string $vs_label optional label to prefix the output with
	 * @param boolean $print_r Flag to switch between print_r() and var_export() for data conversion to string. 
	 * 		Set $print_r to TRUE when dealing with a recursive data structure as var_export() will generate an error.
	 */
	function caDebug($vm_data, $vs_label = null, $print_r = false) {
		if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
			if(caIsRunFromCLI()){
				// simply dump stuff on command line
				if($vs_label) { print $vs_label.":\n"; }
				if($print_r) {
					print_r($vm_data);
				} else {
					var_export($vm_data);
				}
				print "\n";
				return;
			} else if (caIsServiceRequest()){
				$vs_data = ($print_r ? print_r($vm_data, TRUE) : var_export($vm_data, TRUE));
				if($vs_label){
					$vs_string = '<debugLabel>' . $vs_label . '</debugLabel>' . "\n";
				} else {
					$vs_string = "";
				}
				$vs_string .= '<debug>' . $vs_data . '</debug>';
				$vs_string .= "\n\n";
			} else {
				$vs_string = htmlspecialchars(($print_r ? print_r($vm_data, TRUE) : var_export($vm_data, TRUE)), ENT_QUOTES, 'UTF-8');
				$vs_string = '<pre>' . $vs_string . '</pre>';
				$vs_string = trim($vs_label ? "<div class='debugLabel'>$vs_label:</div> $vs_string" : $vs_string);
				$vs_string = '<div class="debug">'. $vs_string . '</div>';
			}

			global $g_response;
			if(is_object($g_response)){
				$g_response->addContent($vs_string,'default');
			} else {
				// on the off chance that someone wants to debug something that happens before 
				// the response object is generated (like config checks), print content
				// to output buffer to avoid headers already sent warning. The output is sent
				// when someone (e.g. View.php) starts a new buffer.
				ob_start();
				print $vs_string;
			}
		}
	}
	# ----------------------------------------
	/**
	 * Return search result instance for given table and id list
	 * @param string $ps_table the table name
	 * @param array $pa_ids a list of primary key values
	 * @param null|array $pa_options @see BundlableLabelableBaseModelWithAttributes::makeSearchResult
	 * @return null|SearchResult
	 */
	function caMakeSearchResult($ps_table, $pa_ids, $pa_options=null) {
		$o_dm = Datamodel::load();
		if ($t_instance = $o_dm->getInstanceByTableName('ca_objects', true)) {	// get an instance of a model inherits from BundlableLabelableBaseModelWithAttributes; doesn't matter which one
			return $t_instance->makeSearchResult($ps_table, $pa_ids, $pa_options);
		}
		return null;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caExtractValuesFromArrayList($pa_array, $ps_key, $pa_options=null) {
		$vb_preserve_keys = (isset($pa_options['preserveKeys'])) ? (bool)$pa_options['preserveKeys'] : true;
		$vb_include_blanks = (isset($pa_options['includeBlanks'])) ? (bool)$pa_options['includeBlanks'] : false;
		
		$va_extracted_values = array();
		foreach($pa_array as $vs_k => $va_v) {
			if (!$vb_include_blanks && (!isset($va_v[$ps_key]) ||(strlen($va_v[$ps_key]) == 0))) { continue; }
			if ($vb_preserve_keys) {
				$va_extracted_values[$vs_k] = $va_v[$ps_key];
			} else {
				$va_extracted_values[] = $va_v[$ps_key];
			}
		}
		
		return $va_extracted_values;
	}
	# ----------------------------------------
	/**
	 * Creates new array with all keys forced to lowercase.
	 *
	 * @param array $pa_array
	 * @param array $pa_options No options are supported (yet)
	 *
	 * @return array
	 */
	function caMakeArrayKeysLowercase($pa_array, $pa_options=null) {
		if (!is_array($pa_array)) { return $pa_array; }
		$va_new_array = array();
		foreach($pa_array as $vs_k => $vm_v) {
			$vs_k_lc = strtolower($vs_k);
			if (is_array($vm_v)) {
				$va_new_array[$vs_k_lc] = caMakeArrayKeysLowercase($vm_v, $pa_options);
			} else {
				$va_new_array[$vs_k_lc] = $vm_v;
			}
		}
		return $va_new_array;
	}
	# ----------------------------------------
	/**
	 * Check if array is associative (text or mixed indices)
	 *
	 * @param array $pa_array
	 *
	 * @return bool
	 */
	function caIsAssociativeArray($pa_array) {
	  return (bool)count(array_filter(array_keys($pa_array), 'is_string'));
	}
	# ----------------------------------------
	/**
	 * Check if array is indexed (numeric indices)
	 *
	 * @param array $pa_array
	 *
	 * @return bool
	 */
	function caIsIndexedArray($pa_array) {
		return (is_array($pa_array) && !caIsAssociativeArray($pa_array));
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caGetProcessUserID() {
	  if (function_exists("posix_geteuid")) {
	  	return posix_geteuid();
	  }
	  return null;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caGetProcessUserName() {
	  if (function_exists("posix_getpwuid")) {
	  	if (is_array($va_user = posix_getpwuid(caGetProcessUserID()))) {
	  		return $va_user['name'];
	  	}
	  }
	  return null;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caGetProcessGroupID() {
	  if (function_exists("posix_getegid")) {
	  	return posix_geteuid();
	  }
	  return null;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caGetProcessGroupName() {
	  if (function_exists("posix_getgrgid")) {
	  	if (is_array($va_group = posix_getgrgid(caGetProcessGroupID()))) {
	  		return $va_group['name'];
	  	}
	  }
	  return null;
	}
	# ----------------------------------------
	/**
	 *
	 */
	function caDetermineWebServerUser() {
	  if (!caIsRunFromCLI() && ($vs_user = caGetProcessUserName())) {	// we're running on the web server
	  	return $vs_user;
	  }
	  
	  if(function_exists("posix_getpwnam")) {
		  // Not running in web server so try to guess
		  foreach(array('apache', 'www-data', 'www', 'httpd', 'nobody') as $vs_possible_user) {
			if (posix_getpwnam($vs_possible_user)) {
				return $vs_possible_user;
			}
		  }
	  }
	  
	  return null;
	}
	# ----------------------------------------
	/**
	 * Convert currency value to another currency.
	 *
	 * @param $ps_value string Currency value with specifier (Ex. $500, USD 500, ¥1200, CAD 750)
	 * @param $ps_to string Specifier of currency to convert value to (Ex. USD, CAD, EUR)
	 * @param $pa_options array Options are:
	 *		numericValue = return floating point numeric value only, without currency specifier. Default is false.
	 *
	 * @return string Converted value with currency specifier, unless numericValue option is set. Returns null if value could not be converted.
	 */
	function caConvertCurrencyValue($ps_value, $ps_to, $pa_options=null) {
		require_once(__CA_LIB_DIR__."/core/Plugins/CurrencyConversion/EuroBank.php");
		if ((!$ps_value) || is_numeric($ps_value)) return null;
		try {
			return WLPlugCurrencyConversionEuroBank::convert($ps_value, $ps_to, $pa_options);
		} catch (Exception $e) {
			return null;
		}
	}
	# ----------------------------------------
	/**
	 * Returns list of currencies for which conversion can be done.
	 *
	 * @return array List of three character currency codes, or null if conversion is not available.
	 */
	function caAvailableCurrenciesForConversion() {
		require_once(__CA_LIB_DIR__."/core/Plugins/CurrencyConversion/EuroBank.php");
		
		try {
			$va_currency_list = WLPlugCurrencyConversionEuroBank::getCurrencyList();
			sort($va_currency_list);
			return $va_currency_list;
		} catch (Exception $e) {
			return null;
		}
	}
	# ----------------------------------------
	/**
	 * Get symbol for currency if available. "USD" will return "$", for example, while
	 * "CAD" will return "CAD"
	 *
	 * @param $ps_value string Currency specifier (Ex. USD, EUR, CAD)
	 *
	 * @return string Symbol (Ex. $, £, ¥) or currency specifier if no symbol is available
	 */
	function caGetCurrencySymbol($ps_value) {
		$o_config = Configuration::load();
		$vs_dollars_are_this = strtolower($o_config->get('default_dollar_currency'));
		switch(strtolower($ps_value)) {
			case $vs_dollars_are_this:
				return '$';
			case 'eur':
				return '€';
			case 'gbp':
				return '£';
			case 'jpy':
				return '¥';
		}
		return $ps_value;
	}
	# ----------------------------------------
	/**
	 * Parse currency value and return array with value and currency type.
	 *
	 * @param string $ps_value
	 * @return array 
	 */
	function caParseCurrencyValue($ps_value) {
		// it's either "<something><decimal>" ($1000) or "<decimal><something>" (1000 EUR) or just "<decimal>" with an implicit <something>
		
		// either
		if (preg_match("!^([^\d]+)([\d\.\,]+)$!", trim($ps_value), $va_matches)) {
			$vs_decimal_value = $va_matches[2];
			$vs_currency_specifier = trim($va_matches[1]);
		// or 1
		} else if (preg_match("!^([\d\.\,]+)([^\d]+)$!", trim($ps_value), $va_matches)) {
			$vs_decimal_value = $va_matches[1];
			$vs_currency_specifier = trim($va_matches[2]);
		// or 2
		} else if (preg_match("!(^[\d\,\.]+$)!", trim($ps_value), $va_matches)) {
			$vs_decimal_value = $va_matches[1];
			$vs_currency_specifier = null;
		}
		
		if ($vs_currency_specifier || ($vs_decimal_value > 0)) {
			return ['currency' => $vs_currency_specifier, 'value' => $vs_decimal_value];
		}
		return null;
 	}
	# ----------------------------------------
	/**
	 * 
	 *
	 * @return array 
	 */
	function caParseTagOptions($ps_tag, $pa_options=null) {
		$vs_tag_proc = $ps_tag;
		$va_opts = array();
		if (sizeof($va_tmp = explode('%', $ps_tag)) > 1) {
			$vs_tag_proc = array_shift($va_tmp);
			$va_params_raw = explode("&", join("%", $va_tmp));
		
			foreach($va_params_raw as $vs_param_raw) {
				$va_tmp = explode('=', $vs_param_raw);
				$va_opts[$va_tmp[0]] = $va_tmp[1];
			}
		}
		
		return array('tag' => $vs_tag_proc, 'options' => $va_opts);
	}
	# ----------------------------------------
	/**
	 * Scales width and height to fit target bounding box while preserving aspect ratio
	 *
	 * @param int $pn_original_width
	 * @param int $pn_original_height
	 * @param int $pn_target_width
	 * @param int $pn_target_height
	 * @param array $pa_options No options are supported (yet)
	 *
	 * @return array Array with "width" and "height" keys for scaled dimensions
	 */
	function caFitImageDimensions($pn_original_width, $pn_original_height, $pn_target_width, $pn_target_height, $pa_options=null) {
		$pn_original_width = preg_replace('![^\d]+!', '', $pn_original_width);
		$pn_original_height = preg_replace('![^\d]+!', '', $pn_original_height);
		if ($pn_original_width > $pn_original_height) {
			$vn_scale_factor = $pn_target_width/$pn_original_width;
			$pn_target_height = $vn_scale_factor * $pn_original_height;
		} else {
			$vn_scale_factor = $pn_target_height/$pn_original_height;
			$pn_target_width = $vn_scale_factor * $pn_original_width;
		}
		return array('width' => (int)$pn_target_width, 'height' => (int)$pn_target_height);
	}
	# ----------------------------------------
	/**
	 * Returns true if the date expression includes the current date/time
	 *
	 * @param string $ps_date_expression
	 * @return bool
	 */
	function caIsCurrentDate($ps_date_expression) {
		if ($va_date = caDateToHistoricTimestamps($ps_date_expression)) {
			$va_now = caDateToHistoricTimestamps(_t('now'));
			if (
				(($va_date['start'] <= $va_now['start'])
				&&
				($va_date['end'] >= $va_now['start']))
			) {
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns true if the date expression ends after the current date/time. 
	 * Only the end point of the expression is considered. 
	 *
	 * @param string $ps_date_expression
	 * @return bool
	 */
	function caDateEndsInFuture($ps_date_expression) {
		if ($va_date = caDateToHistoricTimestamps($ps_date_expression)) {
			$va_now = caDateToHistoricTimestamps(_t('now'));
			if (
				($va_date['end'] >= $va_now['end'])
			) {
				return true;
			}
		}
		return false;
	}
	# ----------------------------------------
	function caHumanFilesize($bytes, $decimals = 2) {
		$size = array('B','KiB','MiB','GiB','TiB');
		$factor = floor((strlen($bytes) - 1) / 3);

		return sprintf("%.{$decimals}f", $bytes/pow(1024, $factor)).@$size[$factor];
	}
	# ----------------------------------------
	/**
	 * Upload a local file to a GitHub repository
	 * @param string $ps_user GitHub username
	 * @param string $ps_token access token. Global account password can be used here but it's recommended to create a personal access token instead.
	 * @param string $ps_owner The repository owner
	 * @param string $ps_repo repository name
	 * @param string $ps_git_path path for the file destination inside the repository, e.g. "/exports/from_collectiveaccess/export.xml."
	 * @param string $ps_local_filepath file to upload as absolute local path. Note that the file must be loaded in memory to be committed to GitHub.
	 * @param string $ps_branch branch to commit to. defaults to 'master'
	 * @param bool $pb_update_on_conflict Determines what happens if file already exists in GitHub repository.
	 * 		true means the file is updated in place for. false means we abort. default is true
	 * @param string $ps_commit_msg commit message
	 * @return bool success state
	 */
	function caUploadFileToGitHub($ps_user, $ps_token, $ps_owner, $ps_repo, $ps_git_path, $ps_local_filepath, $ps_branch = 'master', $pb_update_on_conflict=true, $ps_commit_msg = null) {
		// check mandatory params
		if(!$ps_user || !$ps_token || !$ps_owner || !$ps_repo || !$ps_git_path || !$ps_local_filepath) {
			caLogEvent('DEBG', "Invalid parameters for GitHub file upload. Check your configuration!", 'caUploadFileToGitHub');
			return false;
		}

		if(!$ps_commit_msg) {
			$ps_commit_msg = 'Commit created by CollectiveAccess on '.date('c');
		}


		$o_client = new \Github\Client();
		$o_client->authenticate($ps_user, $ps_token);

		$vs_content = @file_get_contents($ps_local_filepath);

		try {
			$o_client->repositories()->contents()->create($ps_owner, $ps_repo, $ps_git_path, $vs_content, $ps_commit_msg, $ps_branch);
		} catch (Github\Exception\RuntimeException $e) {
			switch($e->getCode()) {
				case 401:
					caLogEvent('DEBG', "Could not authenticate with GitHub. Error message was: ".$e->getMessage()." - Code was: ".$e->getCode(), 'caUploadFileToGitHub');
					break;
				case 422:
					if($pb_update_on_conflict) {
						try {
							$va_content = $o_client->repositories()->contents()->show($ps_owner, $ps_repo, $ps_git_path);
							if(isset($va_content['sha'])) {
								$o_client->repositories()->contents()->update($ps_owner, $ps_repo, $ps_git_path, $vs_content, $ps_commit_msg, $va_content['sha'], $ps_branch);
							}
							return true; // overwrite was successful if there was no exception in above statement
						} catch (Github\Exception\RuntimeException $ex) {
							caLogEvent('DEBG', "Could not update exiting file in GitHub. Error message was: ".$ex->getMessage()." - Code was: ".$ex->getCode(), 'caUploadFileToGitHub');
							break;
						}
					} else {
						caLogEvent('DEBG', "Could not upload file to GitHub. It looks like a file already exists at {$ps_git_path}.", 'caUploadFileToGitHub');
					}
					break;
				default:
					caLogEvent('DEBG', "Could not upload file to GitHub. A generic error occurred. Error message was: ".$e->getMessage()." - Code was: ".$e->getCode(), 'caUploadFileToGitHub');
					break;
			}
			return false;
		} catch (Github\Exception\ValidationFailedException $e) {
			caLogEvent('DEBG', "Could not upload file to GitHub. The parameter validation failed. Error message was: ".$e->getMessage()." - Code was: ".$e->getCode(), 'caUploadFileToGitHub');
			return false;
		} catch (Exception $e) {
			caLogEvent('DEBG', "Could not upload file to GitHub. A generic error occurred. Error message was: ".$e->getMessage()." - Code was: ".$e->getCode(), 'caUploadFileToGitHub');
			return false;
		}

		return true;
	}
	# ----------------------------------------
	/**
 	 * Query external web service and return whatever body it returns as string
 	 * @param string $ps_url URL of the web service to query
	 * @return string
	 * @throws \Exception
 	 */
	function caQueryExternalWebservice($ps_url) {
		if(!isURL($ps_url)) { return false; }
		$o_conf = Configuration::load();

		$vo_curl = curl_init();
		curl_setopt($vo_curl, CURLOPT_URL, $ps_url);

		if($vs_proxy = $o_conf->get('web_services_proxy_url')){ /* proxy server is configured */
			curl_setopt($vo_curl, CURLOPT_PROXY, $vs_proxy);
		}

		curl_setopt($vo_curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($vo_curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($vo_curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($vo_curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($vo_curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($vo_curl, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($vo_curl, CURLOPT_TIMEOUT, 120);
		curl_setopt($vo_curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($vo_curl, CURLOPT_USERAGENT, 'CollectiveAccess web service lookup');

		$vs_content = curl_exec($vo_curl);

		if(curl_getinfo($vo_curl, CURLINFO_HTTP_CODE) !== 200) {
			throw new \Exception(_t('An error occurred while querying an external webservice'));
		}

		curl_close($vo_curl);
		return $vs_content;
	}
	# ----------------------------------------
	/**
	 * Convert <br> tags to newlines
	 * 
	 * @param string $ps_text
	 * @return string
	 */
	function br2nl($ps_text) {
		return preg_replace('#<br\s*/?>#i', "\n", $ps_text);
	}
	# ----------------------------------------
	/**
	 * Parse generic dimension (weight or length)
	 * @param string $ps_value value to parse
	 * @param null|array $pa_options options array
	 * @return bool|null|Zend_Measure_Length|Zend_Measure_Weight
	 */
	function caParseDimension($ps_value, $pa_options=null) {
		try {
			if ($vo_length = caParseLengthDimension($ps_value, $pa_options)) {
				return $vo_length;
			}
		} catch (Exception $e) {
			// noop
		}
		
		try {
			if ($vo_weight = caParseWeightDimension($ps_value, $pa_options)) {
				return $vo_weight;
			}
		} catch (Exception $e) {
			return null;
		}
		
		return null;
	}
	# ----------------------------------------
	/**
	 * Get length unit type as Zend constant, e.g. 'ft.' = Zend_Measure_Length::FEET
	 * @param string $ps_unit
	 * @param array $pa_options Options include:
	 *		short = return short name for unit (ex. for feet, return "ft", for millimeter return "mm")
	 * @return null|string
	 */
	function caGetLengthUnitType($ps_unit, $pa_options=null) {
		$vb_return_short = caGetOption('short', $pa_options, false);
		switch(strtolower(str_replace(".", "", $ps_unit))) {
			case "'":
			case "’":
			case 'ft':
			case 'feet':
			case 'foot':
				return $vb_return_short ? 'ft' : Zend_Measure_Length::FEET;
				break;
			case '"':
			case "”":
			case 'in':
			case 'inch':
			case 'inches':
				return $vb_return_short ? 'in' :  Zend_Measure_Length::INCH;
				break;
			case 'm':
			case 'm.':
			case 'meter':
			case 'meters':
			case 'metre':
			case 'metres':
			case 'mt':
				return $vb_return_short ? 'm' :  Zend_Measure_Length::METER;
				break;
			case 'cm':
			case 'centimeter':
			case 'centimeters':
			case 'centimetre':
			case 'centimetres':
				return $vb_return_short ? 'cm' : Zend_Measure_Length::CENTIMETER;
				break;
			case 'mm':
			case 'millimeter':
			case 'millimeters':
			case 'millimetre':
			case 'millimetres':
				return $vb_return_short ? 'mm' :  Zend_Measure_Length::MILLIMETER;
				break;
			case 'point':
			case 'pt':
			case 'p':
				return $vb_return_short ? 'pt' :  Zend_Measure_Length::POINT;
				break;
			case 'mile':
			case 'miles':
			case 'mi':
				return $vb_return_short ? 'mi' :  Zend_Measure_Length::MILE;
				break;
			case 'km':
			case 'k':
			case 'kilometer':
			case 'kilometers':
			case 'kilometre':
			case 'kilometres':
				return $vb_return_short ? 'km' :  Zend_Measure_Length::KILOMETER;
				break;
			default:	
				return null;
				break;
		}
	}
	# ----------------------------------------
	/**
	 * Parse length dimension
	 * @param string $ps_value
	 * @param null|array $pa_options
	 * @return bool|null|Zend_Measure_Length
	 * @throws Exception
	 */
	function caParseLengthDimension($ps_value, $pa_options=null) {
		global $g_ui_locale;
		$vs_locale = caGetOption('locale', $pa_options, $g_ui_locale);

		$pa_values = array(caConvertFractionalNumberToDecimal(trim($ps_value), $vs_locale));
		
		$vo_parsed_measurement = null;
		while($vs_expression = array_shift($pa_values)) {
			// parse units of measurement
			if (preg_match("!^([\d\.\,/ ]+)[ ]*([^\d ]+)!", $vs_expression, $va_matches)) {
				$vs_value = trim($va_matches[1]);
				$va_values = explode(" ", $vs_value);
				$vs_unit_expression = strtolower(trim($va_matches[2]));
				if ($vs_expression = trim(str_replace($va_matches[0], '', $vs_expression))) {
					array_unshift($pa_values, $vs_expression);
				}
				
				$vs_value  = 0;
				foreach($va_values as $vs_v) {
					$vs_value += caConvertLocaleSpecificFloat(trim($vs_v), $vs_locale);
				}

				if (!($vs_units = caGetLengthUnitType($vs_unit_expression))) {
					throw new Exception(_t('%1 is not a valid unit of length [%2]', $va_matches[2], $ps_value));
				}
			
				try {
					$o_tmp = new Zend_Measure_Length($vs_value, $vs_units, $vs_locale);
				} catch (Exception $e) {
					throw new Exception(_t('Not a valid measurement'));
				}
				if ($o_tmp->getValue() < 0) {
					// length can't be negative in our universe
					throw new Exception(_t('Must not be less than zero'));
					return false;
				}
				
				if ($vo_parsed_measurement) {
					$vo_parsed_measurement = $vo_parsed_measurement->add($o_tmp);
				} else {
					$vo_parsed_measurement = $o_tmp;
				}
			}
		}
		
		if (!$vo_parsed_measurement) { 
			throw new Exception(_t('Not a valid measurement [%1]', $ps_value));
		}
		
		return $vo_parsed_measurement;
	}
	# ----------------------------------------
	/**
	 * Parse weight dimension
	 * @param string $ps_value value to parse
	 * @param null|array $pa_options options array
	 * @return bool|null|Zend_Measure_Weight
	 * @throws Exception
	 */
	function caParseWeightDimension($ps_value, $pa_options=null) {
		global $g_ui_locale;
		$vs_locale = caGetOption('locale', $pa_options, $g_ui_locale);
	
		$pa_values = array(caConvertFractionalNumberToDecimal(trim($ps_value), $vs_locale));
		
		$vo_parsed_measurement = null;
		while($vs_expression = array_shift($pa_values)) {
			// parse units of measurement
			if (preg_match("!^([\d\.\,/ ]+)[ ]*([^\d ]+)!", $vs_expression, $va_matches)) {
				$vs_value = trim($va_matches[1]);
				$va_values = explode(" ", $vs_value);
				if ($vs_expression = trim(str_replace($va_matches[0], '', $vs_expression))) {
					array_unshift($pa_values, $vs_expression);
				}
				
				$vs_value  = 0;
				foreach($va_values as $vs_v) {
					$vs_value += caConvertLocaleSpecificFloat(trim($vs_v), $vs_locale);
				}

				switch(strtolower($va_matches[2])) {
 					case "lbs":
 					case 'lbs.':
 					case 'lb':
 					case 'lb.':
 					case 'pound':
 					case 'pounds':
 						$vs_units = Zend_Measure_Weight::POUND;
 						break;
 					case 'kg':
 					case 'kg.':
 					case 'kilo':
 					case 'kilos':
 					case 'kilogram':
 					case 'kilograms':
 						$vs_units = Zend_Measure_Weight::KILOGRAM;
 						break;
 					case 'g':
 					case 'g.':
 					case 'gr':
 					case 'gr.':
 					case 'gram':
 					case 'grams':
 						$vs_units = Zend_Measure_Weight::GRAM;
 						break;
 					case 'mg':
 					case 'mg.':
 					case 'milligram':
 					case 'milligrams':
 						$vs_units = Zend_Measure_Weight::MILLIGRAM;
 						break;
 					case 'oz':
 					case 'oz.':
 					case 'ounce':
 					case 'ounces':
 						$vs_units = Zend_Measure_Weight::OUNCE;
 						break;
 					case 'ton':
 					case 'tons':
 					case 'tonne':
 					case 'tonnes':
 					case 't':
 					case 't.':
 						$vs_units = Zend_Measure_Weight::TON;
 						break;
 					case 'stone':
 						$vs_units = Zend_Measure_Weight::STONE;
 						break;
 					default:
 						throw new Exception(_t('Not a valid unit of weight [%2]', $ps_value));
 						break;
 				}
			
				try {
					$o_tmp = new Zend_Measure_Weight($vs_value, $vs_units, $vs_locale);
				} catch (Exception $e) {
					throw new Exception(_t('Not a valid measurement'));
				}
				if ($o_tmp->getValue() < 0) {
					// weight can't be negative in our universe
					throw new Exception(_t('Must not be less than zero'));
				}
				
				if ($vo_parsed_measurement) {
					$vo_parsed_measurement = $vo_parsed_measurement->add($o_tmp);
				} else {
					$vo_parsed_measurement = $o_tmp;
				}
			}
		}
		
		if (!$vo_parsed_measurement) { 
			throw new Exception(_t('Not a valid measurement [%1]', $ps_value));
		}
		
		return $vo_parsed_measurement;
	}
	# ----------------------------------------
	/**
	 * Parses and normalizes length exprssions in the form <dimension1> <delimiter> <dimension2> <delimiter> <dimension3> ... (Ex. 4" x 5")
	 * into an array of normalized dimension string. When no units are specified default units are specified (Ex. 4x6 is returned as ["4 in", "6 in"]).
	 * When units are specified for some, but not all, quantities then the first specified unit in the expression in applied to all unit-less quantities 
	 * (Ex. 4x6cm is returned as ["4 cm", "6 cm"] no matter what default units are set to). When units are specified that are always used for the quantity they
	 * apply to (Ex. 4 x 6cm x 8" is returned as ["4 cm", "6 cm", "8 in"])
	 *
	 * @param string $ps_expression Expression to parse
	 * @param null|array $pa_options Options include:
	 *		delimiter = Delimiter string between dimensions. Delimiter will be processed case-insensitively. [Default is 'x']
	 *		units = Units to use as default for quantities that lack a specification. [Default is inches]
	 *		returnExtractedMeasurements = return an array of arrays, each of which includes the numeric quantity, units and display string as separate values. [Default is false]
	 * @return array An array of parsed and normalized length dimensions, parseable by caParseLengthDimension() or Zend_Measure
	 */
	function caParseLengthExpression($ps_expression, $pa_options=null) {
		$va_extracted_measurements = [];
		$vs_specified_units = $vs_extracted_units = null;
		
		$ps_units = caGetOption('units', $pa_options, 'in');
		$pb_return_extracted_measurements = caGetOption('returnExtractedMeasurements', $pa_options, false);
		
		if ($ps_delimiter = caGetOption('delimiter', $pa_options, 'x')) {
			$va_measurements = explode(strtolower($ps_delimiter), strtolower($ps_expression));
		} else {
			$ps_delimiter = '';
			$va_measurements = array($pm_value);
		}
		
		foreach($va_measurements as $vn_i => $vs_measurement) {
			$vs_measurement = trim(preg_replace("![ ]+!", " ", $vs_measurement));
			
			$vs_extracted_units = $vs_measurement_units = null;
			try {
				if (!($vo_parsed_measurement = caParseLengthDimension($vs_measurement))) {
					throw new Exception("Missing or invalid dimensions");
				} else {
					$vs_measurement = trim($vo_parsed_measurement->toString());
					$vs_extracted_units = caGetLengthUnitType($vo_parsed_measurement->getType(), ['short' => true]);
					if (!$vs_specified_units) { $vs_specified_units = $vs_extracted_units; }
				}
			} catch(Exception $e) {
				if (preg_match("!^([\d\.]+)!", $vs_measurement, $va_matches)) {
					$vs_measurement = $va_matches[0]." {$ps_units}";
				} else {
					continue;
				}
			}
			$va_extracted_measurements[] = ['quantity' => preg_replace("![^\d\.]+!", "", $vs_measurement), 'string' => $vs_measurement, 'units' => $vs_extracted_units];
		}
		if ($pb_return_extracted_measurements) { return $va_extracted_measurements; }
		
		$vn_set_count = 0;
		
		$va_return = [];
		foreach($va_extracted_measurements as $vn_i => $va_measurement) {
			
			if ($va_measurement['units']) {
				$vs_measurement = $va_measurement['quantity']." ".$va_measurement['units'];
			} elseif ($vs_specified_units) {
				$vs_measurement = $va_measurement['quantity']." {$vs_specified_units}";
			} else {
				$vs_measurement = $va_measurement['quantity']." {$ps_units}";
			}
			$va_return[] = $vs_measurement;
		}
		
		return $va_return;
	}
	# ----------------------------------------
	/**
	 * Generate a GUID 
	 */
	function caGenerateGUID(){
		if (function_exists("openssl_random_pseudo_bytes")) {
			$vs_data = openssl_random_pseudo_bytes(16);
		} else {
			$vs_data = '';
			for($i=0; $i < 16; $i++) {
				$vs_data .= chr(mt_rand(0, 255));
			}
		}
		$vs_data[6] = chr(ord($vs_data[6]) & 0x0f | 0x40); // set version to 0100
		$vs_data[8] = chr(ord($vs_data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($vs_data), 4));
	}
	# ----------------------------------------
	/**
	 * Push a value to a fixed length stack
	 * @param $pm_val
	 * @param $pa_stack
	 * @param $pn_stack_max_len
	 * @return array the stack
	 */
	function caPushToStack($pm_val, $pa_stack, $pn_stack_max_len) {
		array_push($pa_stack, $pm_val);
		if(sizeof($pa_stack) > $pn_stack_max_len) {
			$pa_stack = array_slice($pa_stack, (sizeof($pa_stack) - $pn_stack_max_len));
		}
		return $pa_stack;
	}
	# ----------------------------------------
	/**
	 * Simple helper to figure out if there is a value in the initial values array
	 * (the one that we feed to the initialize bundle javascript)
	 * @param string $ps_id_prefix the id prefix for the field in question, used to figure out what format the array has
	 * @param array|string $pa_initial_values
	 * @return bool
	 */
	function caInitialValuesArrayHasValue($ps_id_prefix, $pa_initial_values=array()) {
		// intrinsic
		if (is_string($pa_initial_values)) {
			return (strlen($pa_initial_values) > 0);
		}

		// attributes
		if (preg_match("/attribute/", $ps_id_prefix)) {
			foreach ($pa_initial_values as $va_val) {
				foreach ($va_val as $vs_subfield => $vs_subfield_val) {
					if ($vs_subfield === 'locale_id') {
						continue;
					}
					if ($vs_subfield_val) {
						return true;
					}
				}
			}
		} elseif (preg_match("/Labels$/", $ps_id_prefix)) { // labels
			return (sizeof($pa_initial_values) > 0);
		} elseif (preg_match("/\_rel$/", $ps_id_prefix)) {
			return (sizeof($pa_initial_values) > 0);
		}

		return false;
	}
	# ----------------------------------------
	/** 
	 * Determine if CURL functions are available
	 *
	 * @return bool
	 */
	function caCurlIsAvailable() {
		if ((bool)ini_get('safe_mode')) { return false; }

		return function_exists('curl_init');
	}
	# ----------------------------------------
	/**
	 * Returns the maximum depth of an array
	 *
	 * @param array $pa_array
	 * @return int
	 */
	function caArrayDepth($pa_array) {
		$vn_max_indentation = 1;

		$va_array_str = print_r($pa_array, true);
		$va_lines = explode("\n", $va_array_str);

		foreach ($va_lines as $vs_line) {
			$vn_indentation = (strlen($vs_line) - strlen(ltrim($vs_line))) / 4;

			if ($vn_indentation > $vn_max_indentation) {
				$vn_max_indentation = $vn_indentation;
			}
		}

		return ceil(($vn_max_indentation - 1) / 2) + 1;
	}
	# ----------------------------------------
	/**
	 * Concatenate array of get parameters as string suitable for a URL
	 * @param array $pa_params associative array of GET parameters
	 * @return null|string
	 */
	function caConcatGetParams($pa_params) {
		if(!is_array($pa_params)) { return null; }

		$va_return = array();
		foreach($pa_params as $vs_k => $vs_v) {
			$va_return[] = $vs_k.'='.$vs_v;
		}

		return join('&', $va_return);
	}
	# ----------------------------------------
	/**
	 * Get modified timestamp for given file
	 * @param string $ps_file absolute file path
	 * @return bool|int timestamp, false on error
	 */
	function caGetFileMTime($ps_file) {
		$va_stat = @stat($ps_file);
		if(!is_array($va_stat) || !isset($va_stat['mtime'])) { return false; }
		return $va_stat['mtime'];
	}
	# ----------------------------------------
	/**
	 * Check if setup.php has changed since we last cached the mtime.
	 * If it has, cache the new mtime
	 * @return bool
	 */
	function caSetupPhpHasChanged() {
		$vn_setup_mtime = caGetFileMTime(__CA_BASE_DIR__.'/setup.php');

		if(
			!CompositeCache::contains('setup_php_mtime')
			||
			($vn_setup_mtime != CompositeCache::fetch('setup_php_mtime'))
		) {
			CompositeCache::save('setup_php_mtime', $vn_setup_mtime, 'default', 0);
			return true;
		}

		return false;
	}
	# ----------------------------------------
	/**
	 * Display-and-die
	 *
	 * @param mixed $pm_val Value to dump
	 * @param array $pa_options Option include:
	 *		live = Don't die [default is to false]
	 *
	 */
	function dd($pm_val, $pa_options=null) {
		print "<pre>".print_r($pm_val, true)."</pre>\n";
		if (!caGetOption('live', $pa_options, false)) { die; }
	}
	# ----------------------------------------
	/**
	 * Output content in HTML <pre> tags
	 *
	 * @param mixed $pm_val Value to dump
	 *
	 */
	function pre($pm_val) {
		dd($pm_val, array('live' => true));
	}
	# ----------------------------------------
	/**
	 * Flatten a multi-dimensional array
	 *
	 * @param array $pa_array The multidimensional array
	 * @param array $pa_options Options include:
	 *		unique = return only unique values [Default is false]
	 *
	 * @return array A one-dimensional array
	 */
	function caFlattenArray(array $pa_array, array $pa_options=null) {
		$va_return = array();
		array_walk_recursive($pa_array, function($a) use (&$va_return) { $va_return[] = $a; });
		
		if(caGetOption('unique', $pa_options, false)) { $va_return = array_unique($va_return); }
		return $va_return;
	}
	# ----------------------------------------
	/**
	 * Converts a (float) measurement in inches to fractions, up to the given denominator, e.g. 1/16th
	 * @see http://stackoverflow.com/questions/13811108/function-to-round-mm-to-the-nearest-32nd-of-an-inch
	 *
	 * @param float $pn_inches_as_float
	 * @param int $pn_denom
	 * @param bool $pb_reduce
	 * @return string
	 */
	function caLengthToFractions($pn_inches_as_float, $pn_denom, $pb_reduce = true) {
		$o_config = Configuration::load();
		
		$pn_inches_as_float = (float)preg_replace("![^\d\.]+!", "", $pn_inches_as_float);	// remove commas and such; also remove "-" as dimensions can't be negative
		$num = round($pn_inches_as_float * $pn_denom);
		$int = (int)($num / $pn_denom);
		$num %= $pn_denom;

		if (!$num) {
			return "{$int} in";
		}

		if ($pb_reduce) {
			// Use Euclid's algorithm to find the GCD.
			$a = $num < 0 ? -$num : $num;
			$b = $pn_denom;
			while ($b) {
				$t = $b;
				$b = $a % $t;
				$a = $t;
			}

			$num /= $a;
			$pn_denom /= $a;
		}

		if ($int) {
			// Suppress minus sign in numerator; keep it only in the integer part.
			if ($num < 0) {
				$num *= -1;
			}
			
			if ($o_config->get('use_unicode_fractions_for_measurements')) {
				if (($num === 1) && ($pn_denom == 4)) {
					$frac = "¼";
				} elseif (($num === 1) && ($pn_denom == 2)) {
					$frac = "½";
				} elseif (($num === 1) && ($pn_denom == 3)) {
					$frac = "⅓";
				} elseif (($num === 1) && ($pn_denom == 4)) {
					$frac = "¼";
				} elseif (($num === 1) && ($pn_denom == 8)) {
					$frac = "⅛";
				} elseif (($num === 2) && ($pn_denom == 3)) {
					$frac = "⅔";
				} elseif (($num === 3) && ($pn_denom == 4)) {
					$frac = "¾";
				} elseif (($num === 3) && ($pn_denom == 8)) {
					$frac = "⅜";
				} elseif (($num === 5) && ($pn_denom == 8)) {
					$frac = "⅝";
				} elseif (($num === 7) && ($pn_denom == 8)) {
					$frac = "⅞";
				} elseif (($num === 1) && ($pn_denom == 10)) {
					$frac = "⅒";
				} else {
					$frac = "{$num}/{$pn_denom}";
				}
			} else {
				$frac = "{$num}/{$pn_denom}";
			}
			
			return "$int $frac in";
		}

		return "$num/$pn_denom in";
	}
	# ----------------------------------------
	/**
	 * Convert text into string suitable for sorting, by moving articles to end of string, etc.
	 *
	 * @param string $ps_text Text to convert to sortable value
	 * @param array $pa_options Options include:
	 *		locale = Locale settings to use. If omitted current default locale is used. [Default is current locale]
	 *		omitArticle = Omit leading definite and indefinited articles, rather than moving them to the end of the text [Default is true]
	 *
	 * @return string Converted text. If locale cannot be found $ps_text is returned unchanged.
	 */
	function caSortableValue($ps_text, $pa_options=null) {
		global $g_ui_locale;
		$ps_locale = caGetOption('locale', $pa_options, $g_ui_locale);
		if (!$ps_locale) { return $ps_text; }
		
		$pb_omit_article = caGetOption('omitArticle', $pa_options, true);
		
		$o_locale_settings = TimeExpressionParser::getSettingsForLanguage($ps_locale);
		
		$vs_display_value = trim(preg_replace('![^\p{L}0-9 ]+!u', ' ', $ps_text));
		
		// Move articles to end of string
		$va_articles = caGetArticlesForLocale($ps_locale);
		
		foreach($va_articles as $vs_article) {
			if (preg_match('!^('.$vs_article.')[ ]+!i', $vs_display_value, $va_matches)) {
				$vs_display_value = trim(str_replace($va_matches[1], '', $vs_display_value).($pb_omit_article ? '' : ', '.$va_matches[1]));
				break;
			}
		}
		
		// Left-pad numbers
		if (preg_match("![\d]+!", $vs_display_value, $va_matches)) {
			for($i=0; $i<sizeof($va_matches); $i++) {
				$vs_padded = str_pad($va_matches[$i], 15, 0, STR_PAD_LEFT);
				$vs_display_value = str_replace($va_matches[$i], $vs_padded, $vs_display_value);
			}
		}
		return $vs_display_value;
	}
	# ----------------------------------------
	/**
	 * Get list of (enabled) primary tables as table_num => table_name mappings
	 * @return array
	 */
	function caGetPrimaryTables() {
		$o_conf = Configuration::load();
		$va_ret = [];
		foreach([
			'ca_objects' => 57,
			'ca_object_lots' => 51,
			'ca_entities' => 20,
			'ca_places' => 72,
			'ca_occurrences' => 67,
			'ca_collections' => 13,
			'ca_storage_locations' => 89,
			'ca_object_representations' => 56,
			'ca_loans' => 133,
			'ca_movements' => 137,
			'ca_list_items' => 33,
			'ca_tours' => 153,
			'ca_tour_stops' => 155
		] as $vs_table_name => $vn_table_num) {
			if(!$o_conf->get($vs_table_name.'_disable')) {
				$va_ret[$vn_table_num] = $vs_table_name;
			}
		}
		return $va_ret;
	}
	# ----------------------------------------
	/**
	 * Get CA primary tables (objects, entities, etc.) for HTML select, i.e. as Display Name => Table Num mapping
	 * @return array
	 */
	function caGetPrimaryTablesForHTMLSelect() {
		$va_tables = caGetPrimaryTables();
		$o_dm = Datamodel::load();
		$va_ret = [];
		foreach($va_tables as $vn_table_num => $vs_table) {
			$va_ret[$o_dm->getInstance($vn_table_num, true)->getProperty('NAME_PLURAL')] = $vn_table_num;
		}
		return $va_ret;
	}
	# ----------------------------------------
	/**
	 * 
	 * @return array
	 */
	function caNormalizeValueArray($pa_values, $pa_options=null) {
		$va_values_proc = [];
		
		$o_purifier = null;
		if($pb_purify = caGetOption('purify', $pa_options, false)) {
			if (!(($o_purifier = caGetOption('purifier', $pa_options, null)) instanceof HTMLPurifier)) {
				$o_purifier = new HTMLPurifier();	
			}	
		}
		
		foreach($pa_values as $vs_key => $vm_val) {
			if (is_array($vm_val)) {
				if(isset($vm_val[0]) && !is_array($vm_val[0]) && caIsValidSqlOperator($vm_val[0], ['nullable' => true, 'isList' => true])) {
					$vm_val = [$vm_val];
				}
				foreach($vm_val as $vs_key2 => $vm_list_vals) {
					if (is_array($vm_list_vals) && !is_array($vm_list_vals[0]) && caIsValidSqlOperator($vm_list_vals[0], ['nullable' => true, 'isList' => true])) {
						$vm_list_vals = [$vm_list_vals];
					}
					if (!is_array($vm_list_vals)) { $vm_list_vals = [$vm_list_vals]; }
					
					foreach($vm_list_vals as $vm_list_val) {
						
						if(!is_array($vm_list_val)) { $vm_list_val = ['=', $vm_list_val]; }
						if (caIsValidSqlOperator($vm_list_val[0], ['nullable' => true, 'isList' => true])) {
							if (is_array($vm_list_val[1]) && $o_purifier) { 
								$va_vals_proc = [];
								foreach($vm_list_val[1] as $vm_sublist_val) {
									$va_vals_proc[] = !is_null($vm_sublist_val) ? $o_purifier->purify($vm_sublist_val) : $vm_sublist_val;
								}
							
								if (!is_numeric($vs_key2)) { 
									$va_values_proc[$vs_key][$vs_key2][] = [$vm_list_val[0], $va_vals_proc];
								} else {
									$va_values_proc[$vs_key][] = [$vm_list_val[0], $va_vals_proc];
								}
							} else {
								if (!is_numeric($vs_key2)) { 
									$va_values_proc[$vs_key][$vs_key2][] = [$vm_list_val[0], $o_purifier && !is_null($vm_list_val[1]) ? $o_purifier->purify($vm_list_val[1]) : $vm_list_val[1]];
								} else {
									$va_values_proc[$vs_key][] = [$vm_list_val[0], $o_purifier && !is_null($vm_list_val[1]) ? $o_purifier->purify($vm_list_val[1]) : $vm_list_val[1]];
								}
							}
						} else {
							$va_values_proc[$vs_key][$vs_key2][] = caNormalizeValueArray($vm_list_val, $pa_options);
						}
					}
				}
			} else {
				$va_values_proc[$vs_key][] = ['=', $o_purifier && !is_null($vm_val) ? $o_purifier->purify($vm_val) : $vm_val];
			}
		}
		return $va_values_proc;
	}
	# ----------------------------------------
	/**
	 * 
	 * @return bool
	 */
	function caIsValidSqlOperator($ps_op, $pa_options=null) {
		$ps_type = caGetOption('type', $pa_options, null, ['forceLowercase' => true]);
		$pb_nullable = caGetOption('nullable', $pa_options, false);
		$pb_is_list = caGetOption('isList', $pa_options, false);
		
		switch(strtolower($ps_op)) {
			case '>':
			case '<':
			case '>=':
			case '<=':
				return (!$ps_type || ($ps_type == 'numeric')) ? true : false;
				break;
			case '=':
			case '<>':
			case '!=':
				return true;
				break;
			case 'like':
				return (!$ps_type || ($ps_type == 'string')) ? true : false;
				break;
			case 'is':
				return ($pb_nullable) ? true : false;
				break;
			case 'in':
				return ($pb_is_list) ? true : false;
				break;
		}
		return false;
	}
	# ----------------------------------------
	/**
	 * Find and return tag-like strings in a template. All tags are assumed to begin with
	 * a caret ("^") and end with a space or EOL. Tags may contain spaces within quoted areas. 
	 *
	 * @param string $ps_template The template to parse
	 * @param array $pa_options No options are supported.
	 * @return array A list of identified tags
	 */
	function caExtractTagsFromTemplate($ps_template, $pa_options=null) {
		$va_tags = [];
		
		$vb_in_tag = $vb_in_single_quote = $vb_in_double_quote = $vb_have_seen_param_delimiter = $vb_is_ca_get_ref = false;
		$vs_tag = '';
		$vs_last_char = null;
		
		for($i=0; $i < mb_strlen($ps_template); $i++) {
			switch($vs_char = mb_substr($ps_template, $i, 1)) {
				case '^':
					if ($vb_in_tag) {
						if ($vs_tag = trim($vs_tag)){ $va_tags[] = $vs_tag; }
					}
					$vb_in_tag = true;
					$vs_tag = '';
					$vb_in_single_quote = $vb_in_double_quote = $vb_have_seen_param_delimiter = $vb_is_ca_get_ref = false;
					break;
				case '%':
					if($vb_in_tag) {
						$vb_have_seen_param_delimiter = true;
						$vs_tag .= $vs_char;
					}
					break;
				case ' ':
				case ',':
				case '<':
					if (!$vb_in_single_quote && !$vb_in_double_quote && (!$vb_have_seen_param_delimiter || (!in_array($vs_char, [','])))) {
						if ($vs_tag = trim($vs_tag)) { $va_tags[] = $vs_tag; }
						$vs_tag = '';
						$vb_in_tag = $vb_in_single_quote = $vb_in_double_quote = $vb_is_ca_get_ref = false;
					} else {
						$vs_tag .= $vs_char;
					}
					break;
				case '"':
					if ($vb_in_tag && !$vb_in_double_quote && ($vs_last_char == '=')) {
						$vb_in_double_quote = true;
						$vs_tag .= $vs_char;
					} elseif($vb_in_tag && $vb_in_double_quote) {
						$vs_tag .= $vs_char;
						$vb_in_double_quote = false;
					} elseif($vb_in_tag) {
						if ($vs_tag = trim($vs_tag)) { $va_tags[] = $vs_tag; }
						$vs_tag = '';
						$vb_in_tag = $vb_in_single_quote = $vb_in_double_quote = $vb_is_ca_get_ref = false;
					}
					break;
				case "'":
					$vb_in_single_quote = !$vb_in_single_quote;
					$vs_tag .= $vs_char;
					break;
				default:
					if ($vb_in_tag) {
						if ((!$vb_is_ca_get_ref) && preg_match("!^ca_[a-z]+\.$!", $vs_tag)) {
							$vb_is_ca_get_ref = true;
						}
						if ($vb_is_ca_get_ref && !$vb_have_seen_param_delimiter && (!preg_match("![A-Za-z0-9_\-\.]!", $vs_char))) {
							$va_tags[] = $vs_tag;
							$vs_tag = '';
							$vb_in_tag = $vb_in_single_quote = $vb_in_double_quote = $vb_is_ca_get_ref = false;
						} else {
							$vs_tag .= $vs_char;
						}
					}
					break;
			}
			$vs_last_char = $vs_char;
		}

		if ($vb_in_tag) {
			if ($vs_tag = trim($vs_tag)) { $va_tags[] = $vs_tag; }
		}
		
		foreach($va_tags as $vn_i => $vs_tag) {
			if ((($p = strpos($vs_tag, "~")) !== false) && ($p < (mb_strlen($vs_tag) - 1))) { continue; }	// don't clip trailing characters when there's a tag directive specified (eg. a tilde that is not at the end of the tag)
			
			$vb_is_ca_tag = (substr($vs_tag, 0, 3) == 'ca_');
			
			if ($vb_is_ca_tag && (strpos($vs_tag, '%') === false)) {
				// ca_* tags that don't have modifiers always end whenever a non-alphanumeric character is encountered
				$vs_tag = preg_replace("![^0-9\p{L}_]+$!u", "", $vs_tag);
			} elseif(preg_match("!^([\d]+)[^0-9\p{L}_]+!", $vs_tag, $va_matches)) {
				// tags beginning with numbers followed by non-alphanumeric characters are truncated to number-only tags
				$vs_tag = $va_matches[1];
			}
			
			$va_tags[$vn_i] = rtrim($vs_tag, ")/.,%");	// remove trailing slashes, periods and percent signs as they're potentially valid tag characters that are never meant to be at the end
		}
		return $va_tags;
	}
	# ----------------------------------------
