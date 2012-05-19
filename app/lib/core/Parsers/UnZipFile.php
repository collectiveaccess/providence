<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/UnZipFile.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2012 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

class UnZipFile {
	# --------------------------------------------------------
	private $ops_filepath;
	private $opo_zip;
	
	private $opn_read_packet_size = 256000;
	# --------------------------------------------------------
	/** 
	 *
	 */
	public function UnZipFile($ps_filepath="") {
		if ($ps_filepath) {
			$this->load($ps_filepath);
		}
	}
	# --------------------------------------------------------
	/** 
	 *
	 */
	public function load($ps_filepath) {
		if (!function_exists('zip_open')) {
			return null;
		} else {
			if ($this->opo_zip) {
				zip_close($this->opo_zip);
			}
			
			$this->opo_zip = zip_open($ps_filepath);
			if (is_resource($this->opo_zip)) {
				return $this->opo_zip;
			} else {
				$this->opo_zip = false;
				return false;
			}
		}
	}
	# --------------------------------------------------------
	/**
	 *
	 */
	public function extract($ps_destpath, $ps_file_to_expand=null) {
		if (!$this->opo_zip) {
			return false;
		} else {
			if (!preg_match("/\/$/", $ps_destpath)) {
				$ps_destpath .= "/";
			}
			
			if (!function_exists('zip_open')) {
				return null;
			} else {
				while($r_zip_entry = zip_read($this->opo_zip)) {
					if (zip_entry_open($this->opo_zip, $r_zip_entry, "r")) {
						
						$vs_filepath = zip_entry_name($r_zip_entry);
						if ($ps_file_to_expand && ($vs_filepath != $ps_file_to_expand)) { continue; }
						
						$vs_filesize = zip_entry_filesize($r_zip_entry);
						
						if ($vs_filesize == 0) {
							$va_dirs = explode("/", $vs_filepath);
							$vn_num_dirs = sizeof($va_dirs);
							for($vn_i=1; $vn_i <= $vn_num_dirs; $vn_i++) {
								$va_dirpath = array_slice($va_dirs,0,$vn_i);
								if (!file_exists($ps_destpath.join("/", $va_dirpath))) {
									@mkdir($ps_destpath.join("/", $va_dirpath));
								}
							}
						} else {
							
							$vn_out = 0;
							$vn_buf_size = 0;
							if ($r_fp = fopen($ps_destpath.$vs_filepath, "w+")) {
								while($vn_out < $vs_filesize) {
									$vn_buf_size = $vs_filesize - $vn_out;
									if ($vn_buf_size > $this->opn_read_packet_size) {
										$vn_buf_size = $this->opn_read_packet_size;
									}
									if (!fwrite($r_fp, zip_entry_read($r_zip_entry, $vn_buf_size))) {
										break;
									}
								}
								fclose($r_fp);
							}
						}
					
						zip_entry_close($r_zip_entry);
					}
				}
			}
		}
		return true;
	}
	# --------------------------------------------------------
	/**
	 *
	 */
	public function getFileList() {
		if (!$this->opo_zip) {
			return false;
		} else {
			if (!function_exists('zip_open')) {
				return null;
			} else {
				$va_files = array();
				while($r_zip_entry = zip_read($this->opo_zip)) {
					if (zip_entry_open($this->opo_zip, $r_zip_entry, "r")) {
						
						$vs_filepath = zip_entry_name($r_zip_entry);
						
						$vs_filesize = zip_entry_filesize($r_zip_entry);
						$va_files[$vs_filepath] = $vs_filesize;
						
						zip_entry_close($r_zip_entry);
					}
				}
				return $va_files;
			}
		}
		return null;
	}
	# --------------------------------------------------------
	/**
	 *
	 */
	public function zipFileErrMsg($errno) {
		// using constant name as a string to make this function PHP4 compatible
		$zipFileFunctionsErrors = array(
			'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
			'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
			'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed', 
			'ZIPARCHIVE::ER_SEEK' => 'Seek error',
			'ZIPARCHIVE::ER_READ' => 'Read error',
			'ZIPARCHIVE::ER_WRITE' => 'Write error',
			'ZIPARCHIVE::ER_CRC' => 'CRC error',
			'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
			'ZIPARCHIVE::ER_NOENT' => 'No such file.',
			'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
			'ZIPARCHIVE::ER_OPEN' => 'Can\'t open file', 
			'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.', 
			'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
			'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure', 
			'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
			'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.', 
			'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
			'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
			'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
			'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
			'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent', 
			'ZIPARCHIVE::ER_REMOVE' => 'Can\'t remove file',
			'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted'
		);
		$errmsg = 'unknown';
		foreach ($zipFileFunctionsErrors as $constName => $errorMessage) {
			if (defined($constName) and constant($constName) === $errno) {
				return 'Zip File Function error: '.$errorMessage;
			}
		}
		return 'Zip File Function error: unknown';
	}
	# --------------------------------------------------------
}
?>