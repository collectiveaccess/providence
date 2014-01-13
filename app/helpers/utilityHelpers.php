<?php
/** ---------------------------------------------------------------------
 * app/helpers/utilityHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2013 Whirl-i-Gig
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

# ----------------------------------------------------------------------
# String localization functions (getText)
# ----------------------------------------------------------------------
/**
 * Translates the string in $ps_key into the current locale
 * You interpolate values into the returned string by embedding numbered placeholders in $ps_key 
 * in the format %n (where n is a number). Each parameter passed after $ps_key corresponds to a 
 * placeholder (ex. the first parameter replaces %1, the second %2)
 */
 
global $ca_translation_cache;
$ca_translation_cache = array();
function _t($ps_key) {
	global $ca_translation_cache, $_;
	
	if (!isset($ca_translation_cache[$ps_key])) {
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
		$ca_translation_cache[$ps_key] = $vs_str;
	} else {
		$vs_str = $ca_translation_cache[$ps_key];
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
	global $ca_translation_cache, $_;
	
	if (!sizeof(func_get_args()) && isset($ca_translation_cache[$ps_key])) { print $ca_translation_cache[$ps_key]; return; }
	
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
	
	print $ca_translation_cache[$ps_key] = $vs_str;
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
	 * @return array An array of file paths.
	 */
	function &caGetDirectoryContentsAsList($dir, $pb_recursive=true, $pb_include_hidden_files=false, $pb_sort=false, $pb_include_directories=false) {
		$va_file_list = array();
		if(substr($dir, -1, 1) == "/"){
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		
		if($va_paths = scandir($dir, 0)) {
			foreach($va_paths as $item) {
				if ($item != "." && $item != ".." && ($pb_include_hidden_files || (!$pb_include_hidden_files && $item{0} !== '.'))) {
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
	function caZipDirectory($ps_directory, $ps_name, $ps_output_file) {
		$va_files_to_zip = caGetDirectoryContentsAsList($ps_directory);
		
		$o_zip = new ZipFile();
		foreach($va_files_to_zip as $vs_file) {
			$vs_name = str_replace($ps_directory, $ps_name, $vs_file);
			$o_zip->addFile($vs_file, $vs_name);
		}
		
		$vs_new_file = $o_zip->output(ZIPFILE_FILEPATH);
		copy($vs_new_file, $ps_output_file);
		unlink ($vs_new_file);
		
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
		if (!$opa_php_version) { $opa_php_version = caGetPHPVersion(); }
		
		if ($opa_php_version['versionInt'] >= 50203) {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set, false);
		} else {
			$ps_text = htmlspecialchars(stripslashes($ps_text), ENT_QUOTES, $vs_character_set);
		}
		return str_replace("&amp;#", "&#", $ps_text);
	}
	# ----------------------------------------
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
	 * not actually connect to the URL to confirm its validity. It only validates at text content for well-formedness.
	 *
	 * @param string $ps_url The URL to check
	 * @return boolean true if it appears to be valid URL, false if not
	 */
	function isURL($ps_url) {
		if (preg_match("!(http|ftp|https|rtmp|rtsp):\/\/[\w\-_]+(\.[\w\-_]+)*([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?!", $ps_url, $va_matches)) {
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
	 *		html - if true, then HTML formatted output will be returned; otherwise plain-text output is returned; default is false
	 *		print - if true output is printed to standard output; default is false
	 * @return string Stack trace output
	 */
	function caPrintStacktrace($pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$va_trace = debug_backtrace();
		
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
	 *		12⅔ ft (= 12.667 ft)
	 *		"Total is 12 3/4 lbs" (= "Total is 12.75 lbs")
	 *
	 * Both text fractions (ex. 3/4) and Unicode fraction glyphs (ex. ¾) may be used.
	 *
	 * @param string $ps_fractional_expression String including fractional expression to convert
	 * @param string $locale The locale of the string to use the right decimal separator
	 * @return string $ps_fractional_expression with fractions replaced with decimal equivalents
	 */
	function caConvertFractionalNumberToDecimal($ps_fractional_expression, $locale="en_US") {
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
			foreach(array(
				'½' => $sep.'5', '⅓' => $sep.'333',
				'⅔' => $sep.'667', '¼' => $sep.'25',
				'¾'	=> $sep.'75') as $vs_glyph => $vs_val
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
	 * @param string $locale The locale of the value
	 * @return float The converted value
	 */
	function caConvertLocaleSpecificFloat($ps_value, $locale = "en_US") {
		$vo_locale = new Zend_Locale($locale);

		try {
			return Zend_Locale_Format::getNumber($ps_value, array('locale' => $locale));
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
		$vo_locale = new Zend_Locale($locale);

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
	 * @param string $locale Which locale is to be used to determine the value
	 * @return string The separator
	 */
	function caGetDecimalSeparator($locale = "en_US") {
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
	 * then the dimension is considered to be expressed in pixesl. If non-integers are passed they will
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
	 * Sorts an array of arrays based upon one or more values in the second-level array.
	 * Top-level keys are preserved in the sort.
	 *
	 * @param array $pa_values The array to sort. It should be an array of arrays (aka. 2-dimensional)
	 * @param array $pa_sort_keys An array of keys in the second-level array to sort by
	 * @return array The sorted array
	*/
	function caSortArrayByKeyInValue($pa_values, $pa_sort_keys, $ps_sort_direction="ASC") {
		$va_sort_keys = array();
		foreach ($pa_sort_keys as $vs_field) {
			$va_tmp = explode('.', $vs_field);
			array_shift($va_tmp);
			$va_sort_keys[] = join(".", $va_tmp);
		}
		$va_sorted_by_key = array();
		foreach($pa_values as $vn_id => $va_data) {
			$va_key = array();
			foreach($va_sort_keys as $vs_sort_key) {
				$va_key[] = $va_data[$vs_sort_key];
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
	  *
	  */
	function caGetCacheObject($ps_prefix, $pn_lifetime=3600, $ps_cache_dir=null, $pn_cleaning_factor=100) {
		if (!$ps_cache_dir) { $ps_cache_dir = __CA_APP_DIR__.'/tmp'; }
		$va_frontend_options = array(
			'lifetime' => $pn_lifetime, 				/* cache lives 1 hour */
			'logging' => false,					/* do not use Zend_Log to log what happens */
			'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
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
					return 'application/pdf|application/postscript|text/xml|text/html|text/plain|application/msword';
				} else {
					return array('application/pdf', 'application/postscript', 'text/xml', 'text/html', 'text/plain', 'application/msword');
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
	  * @return string An MD5 cache key for the options array
	  */
	function caMakeCacheKeyFromOptions($pa_options) {
		if (!is_array($pa_options)) { return md5($pa_options); }
		foreach($pa_options as $vs_key => $vm_value) {
			if (is_object($vm_value)) { unset($pa_options[$vs_key]); }
		}
		
		return md5(print_R($pa_options, true));
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
		} catch (EXception $e) {
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
			return $va_date['start'];
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
	 * @param int $pn_max_length Maximum number of characters to return; if omitted defaults to 30 charactes
	 * @return string The truncated text
	 */
	function caTruncateStringWithEllipsis($ps_text, $pn_max_length=30) {
		if ($pn_max_length < 1) { $pn_max_length = 30; }
		if (mb_strlen($ps_text) > $pn_max_length) {
			$ps_text = mb_substr($ps_text, 0, ($pn_max_length - 3))."...";
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
	 * @param mixed $ps_option The option to extract
	 * @param array $pa_options The options array to extract values from
	 * @param mixed $pm_default An optional default value to return if $ps_option is not set in $pa_options 
	 * @param array $pa_parse_options Option parser options (cross your eyes now) include:
	 *		forceLowercase = transform option value to all lowercase [default=false]
	 *		forceUppercase = transform option value to all uppercase [default=false]
	 *		validValues = array of values that are possible for this option. If the option value is not in the list then the default is returned. If no default is set then the first value in the validValues list is returned. Note that by default all comparisons are case-insensitive. 
	 *		caseSensitive = do case sensitive comparisons when checking the option value against the validValues list [default=false]
	 * @return mixed
	 */
	function caGetOption($ps_option, $pa_options, $pm_default=null, $pa_parse_options=null) {
		$va_valid_values = null;
		$vb_case_insensitive = false;
		if (isset($pa_parse_options['validValues']) && is_array($pa_parse_options['validValues'])) {
			$va_valid_values = $pa_parse_options['validValues'];
			if (!isset($pa_parse_options['caseSensitive']) || !$pa_parse_options['caseSensitive']) {
				$va_valid_values = array_map(function($v) { return mb_strtolower($v); }, $va_valid_values);
				$vb_case_insensitive = true;
			}
		}
		$vm_val = (isset($pa_options[$ps_option]) && !is_null($pa_options[$ps_option])) ? $pa_options[$ps_option] : $pm_default;
		
		if(is_array($va_valid_values)) {
			if (!in_array($vb_case_insensitive ? mb_strtolower($vm_val) : $vm_val, $va_valid_values)) {
				$vm_val = $pm_default;
				if (!in_array($vb_case_insensitive ? mb_strtolower($vm_val) : $vm_val, $va_valid_values)) {
					$vm_val = array_shift($va_valid_values);
				}
			}
		}
		
		if (isset($pa_parse_options['forceLowercase']) && $pa_parse_options['forceLowercase']) {
			$vm_val = mb_strtolower($vm_val);
		} elseif (isset($pa_parse_options['forceUppercase']) && $pa_parse_options['forceUppercase']) {
			$vm_val = mb_strtoupper($vm_val);
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
	 * @param array $pa_options No options are currently supported
	 * @return array The sanitized array
	 */
	function caSanitizeArray($pa_array, $pa_options=null) {
		if (!is_array($pa_array)) { return array(); }
		foreach($pa_array as $vn_k => $vm_v) {
			if (is_array($vm_v)) {
				$pa_array[$vn_k] = caSanitizeArray($vm_v);
			} else {
				if (!preg_match("!^[\p{L}\p{N}\p{P}]+!", $vm_v)) {
					unset($pa_array[$vn_k]);
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
	
	# ----------------------------------------------------------------
	/**
	 *
	 */
	function caWriteServerConfigHints() {
		if (file_exists(__CA_APP_DIR__."/tmp/server_config_hints.txt")) { return false; }
		return @file_put_contents(__CA_APP_DIR__."/tmp/server_config_hints.txt", serialize(
			array(
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
				'HTTP_HOST' => $_SERVER['HTTP_HOST'],
				'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT']
			)
		));
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
	 *
	 *
	 */
	function caMakeSearchResult($ps_table, $pa_ids) {
		$o_dm = Datamodel::load();
		if ($t_instance = $o_dm->getInstanceByTableName($ps_table, true)) {
			return $t_instance->makeSearchResult($ps_table, $pa_ids);
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
	 *
	 */
	function caIsAssociativeArray($pa_array) {
	  return (bool)count(array_filter(array_keys($pa_array), 'is_string'));
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
?>