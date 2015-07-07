<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/ZipStream.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
  * Class to create and stream large ZIP archives via HTTP
  *
  * This is not a general purpose ZIP creation utility. For that you should
  * look at PHP ZipArchive and the like. This class is designed for the extreme
  * case where you need to create ZIP archives on-the-fly that are composed of very 
  * large files, on the order of several gigabytes or more. At these sizes standard ZIP creation libraries,
  * which require loading entire files into memory and/or writing of temporary files, quickly
  * become slow or exhaust available system resources. 
  *
  * This library is designed to read archived files in-place and stream the resulting ZIP archive directly 
  * to the HTTP client as it is created, loading only a small part of each file at any moment. The typical 
  * use case is delivery of multiple previously compressed files (JPEGs, smaller ZIP files, Etc.) in a single download, 
  * so default behavior is to not attempt data compression, which can be costly with large files.
  *
  * 
  *
  */
 class ZipStream {
	# ----------------------------------------------------------------------
	# --- Properties
	# ----------------------------------------------------------------------
    
    /**
     * List of files to add to archive
     */
	private $opa_file_list = array();
	
	
	# ----------------------------------------------------------------------
	# --- Methods
	# ----------------------------------------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}
	# ----------------------------------------------------------------------
	/**
	 * Initialize instance
	 */
	public function init() {
		$this->opa_file_list = array();	
	}
	# ----------------------------------------------------------------------
	/**
	 * Clear file list and start a new archive
	 */
	public function clear() {
		$this->init();
	}
	# ----------------------------------------------------------------------
    /**
     * Converts a Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    private function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
        	$timearray['year']    = 1980;
        	$timearray['mon']     = 1;
        	$timearray['mday']    = 1;
        	$timearray['hours']   = 0;
        	$timearray['minutes'] = 0;
        	$timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    }
	# ----------------------------------------------------------------------
    /**
     * Adds a file to the archive
     *
     * @param string $data file path
     * @param string $name name of the file in the archive (may contains the path)
     * @param array $pa_options An array of options. Supported options are:
     *		crc = Precomputed CRC to use for file. This can save time as the costliest operation for very large files in calculation of the CRC. CRC should be numeric. [Default is null]
     *		
     * @access public
     */
    public function addFile($ps_filepath, $ps_name=null, $pa_options=null) {
		if (!file_exists($ps_filepath)) { return null; }
		if (!$ps_name) { $ps_name = basename($ps_filepath); }
        $ps_name     = str_replace('\\', '/', $ps_name);
		
        $vs_dtime    = dechex($this->unix2DosTime(time()));
        
        $this->opa_file_list[$ps_name] = array(
        	'path' => $ps_filepath,
        	'name' => $ps_name,
        	'time' => $vs_dtime,
        	'size' => filesize($ps_filepath),
        	'crc' => isset($pa_options['crc']) ? $pa_options['crc'] : null
        ); 
        return sizeof($this->opa_file_list);
    }
    # ----------------------------------------------------------------------
    /**
     *
     */
    private function _stream($pa_options=null) {
    
    	$vb_dont_write = $pa_options['dontWrite'];
    	
    	$vb_need_zip64 = false;
    	if (isset($pa_options['useZIP64'])) {
    		$vb_need_zip64 = (bool)$pa_options['useZIP64'];
    	} else {
    		// Default is to use ZIP64 only if any single file or the archive
    		// as a whole is over ~4gigs (less a 100meg "ceiling")
    		$vn_total_size = 100*1024*1024;	// give some room for overhead
			$vn_size_limit = pow(2,32);
			foreach($this->opa_file_list as $vs_name => $va_file) {
				if ($va_file['size'] > $vn_size_limit) {
					$vb_need_zip64 = true; 
					break;
				}
				$vn_total_size += $va_file['size'];
				if ($vn_total_size > $vn_size_limit) {
					$vb_need_zip64 = true; 
					break;
				}
			}
		}
		
    	$r_out = $vb_dont_write ? fopen("/dev/null", "wb") : fopen("php://output", "wb");
    	
		if ($r_out === FALSE) {
			return null;
		}
		$vn_datasize = 0;
		$vn_old_offset = 0;
		$va_ctrl_dir = array();
		$vn_i = 0;
		
		$vs_create_version = "\x00\x00";
		$vs_extract_version = $vb_need_zip64 ? "\x2d\x00" : "\x14\x00";
		
    	foreach($this->opa_file_list as $vs_name => $va_file) {
    		$vs_filepath 	= $va_file['path'];
    		$vs_dtime 		= $va_file['time'];
    		$vn_i++;
    		
    		$r_in = fopen($vs_filepath, "rb");
			if ($r_in !== FALSE) {
				$vs_hex      = $vs_dtime[6] . $vs_dtime[7] . $vs_dtime[4] . $vs_dtime[5] . $vs_dtime[2] . $vs_dtime[3] . $vs_dtime[0] . $vs_dtime[1];
	
				if(function_exists('hex2bin')) { // this is only available in PHP 5.4+
					$vn_hexdtime = hex2bin($vs_hex);
				} else {
					$vn_hexdtime = pack("H*", $vs_hex);    
				}

				
				//
				// Local file header segment
				//
				$vs_header   = "\x50\x4b\x03\x04";
				$vs_header   .= $vs_extract_version;   // version needed to extract
				$vs_header   .= "\x08\x00";            // gen purpose bit flag
				$vs_header   .= "\x08\x00";            // compression method
				$vs_header   .= $vn_hexdtime;          // last mod time and date

				if (!($vs_crc = $va_file['crc'])) {
					$va_crc = unpack('N', pack('H*', hash_file('crc32b', $vs_filepath)));
					$vs_crc = $va_crc[1];
				}
				
				$vs_extended_info = '';
				if ($vb_need_zip64) {
					// header
					$vs_extended_info = "\x00\x01".pack("v", 28);
					$vs_extended_info .= pack("P", -1).pack("P", -1).pack("P", $vn_old_offset).pack("V", 0);	
				}
				
				$vs_header      .= pack('V', 0);		// crc
				$vs_header      .= $vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', 0);		// compressed size
				$vs_header      .= $vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', 0);		// uncompressed size
				
				$vs_header      .= pack('v', strlen($vs_name));    					// length of filename
				$vs_header      .= pack('v', strlen($vs_extended_info));     		// extra field length
				$vs_header      .= $vs_name;
				$vs_header 		.= $vs_extended_info;
		
				fwrite($r_out, $vs_header);			// write local file header
				
				$vn_segsize = strlen($vs_header);
				
				// add the deflate filter using compression level
				$r_fltr = stream_filter_append($r_in, "zlib.deflate", STREAM_FILTER_READ, array('level' => isset($pa_options['compression']) ? (int)$pa_options['compression'] : 0));
				
				// turn off the time limit
				if (!ini_get("safe_mode")) { set_time_limit(isset($pa_options['timeLimit']) ? (int)$pa_options['timeLimit'] : 0); }
				
				//
				// File content segment
				//
				$vs_content = TRUE;
				$vn_compressed_filesize = 0;
				while (($vs_content !== FALSE) && !feof($r_in)) {
					// deflate works best with buffers >32K
					$vs_content = fread($r_in, 64 * 1024);
					if ($vs_content !== FALSE) {
						$vn_content_length = strlen($vs_content);
						$vn_bytes_written = fwrite($r_out, $vs_content, $vn_content_length);
						ob_flush();
						flush();
						
						$vn_compressed_filesize += $vn_bytes_written;
						$vn_segsize += $vn_bytes_written;
					}
				}
				
				// remove the deflate filter
				stream_filter_remove($r_fltr);
				
				//
				// Data descriptor segment 
				//
				$vs_header  = "\x50\x4b\x07\x08";
				$vs_header .= pack('V', $vs_crc);							// crc32
				if ($vb_need_zip64) {
					$vs_header .= pack('P', $vn_compressed_filesize);		// compressed filesize
					$vs_header .= pack('P', $va_file['size']);				// uncompressed filesize
				} else {
					$vs_header .= pack('V', $vn_compressed_filesize);		// compressed filesize
					$vs_header .= pack('V', $va_file['size']);     			// uncompressed filesize
				}
				fwrite($r_out, $vs_header);
				$vn_segsize += strlen($vs_header);
				
			
				$vn_datasize += $vn_segsize;
	
				$vn_new_offset = $vn_datasize;
				
				$vs_extended_info = '';
				if ($vb_need_zip64) {
					// header with file sizes
					$vs_extended_info = "\x01\x00".pack("v", 28);
					$vs_extended_info .= pack("P", $va_file['size']).pack("P", $vn_compressed_filesize).pack("P", $vn_old_offset).pack("V", 0);	
				}
	
				//
				// Add to central directory record
				//
				$vs_central_directory_entry = "\x50\x4b\x01\x02";
				$vs_central_directory_entry .= $vs_create_version;               				 // version made by
				$vs_central_directory_entry .= $vs_extract_version;               				 // version needed to extract
				$vs_central_directory_entry .= "\x08\x00";               				 // gen purpose bit flag
				$vs_central_directory_entry .= "\x08\x00";               				 // compression method
				$vs_central_directory_entry .= $vn_hexdtime;                			 // last mod time & date
				$vs_central_directory_entry .= pack('V', $vs_crc);          			 // crc32
				$vs_central_directory_entry .= $vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', $vn_compressed_filesize);       // compressed filesize
				$vs_central_directory_entry .= $vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', $va_file['size']);       		 // uncompressed filesize
				$vs_central_directory_entry .= pack('v', strlen($vs_name) ); 			 // length of filename
				$vs_central_directory_entry .= pack('v', strlen($vs_extended_info));     // extra field length
				$vs_central_directory_entry .= pack('v', 0 );             		  	 	 // file comment length
				$vs_central_directory_entry .= pack('v', 0 );             				 // disk number start
				$vs_central_directory_entry .= pack('v', 0 );             				 // internal file attributes
				$vs_central_directory_entry .= pack('V', 32 );            				 // external file attributes - 'archive' bit set

				$vs_central_directory_entry .= $vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', $vn_old_offset); 				 // relative offset of local header
				
				$vs_central_directory_entry .= $vs_name;
				$vs_central_directory_entry .= $vs_extended_info;

				// optional extra field, file comment goes here
				// save to central directory
				$va_ctrl_dir[] = $vs_central_directory_entry;
				
				
				$vn_old_offset = $vn_new_offset;
			}
			fclose($r_in);
		}
		
		$vn_current_offset = $vn_new_offset;
		
		//
		// Add central directory
		//
		$vs_ctrl_dir = implode('', $va_ctrl_dir);
		$vn_current_offset += strlen($vs_ctrl_dir);
		fwrite($r_out, $vs_ctrl_dir);
		
		//
		// Add Zip64 end of central directory if required
		// 
		$vn_zip64_ctrl_dir_offset = $vn_current_offset;
		if ($vb_need_zip64) { 
			fwrite($r_out, $vs_zip64_ctrl_dir = "\x50\x4b\x06\x06" .
			pack('P', 44) .              // size of record (8)
		
			pack('v', "\x00\x00") .              // version created by (2)
			pack('v', $vs_extract_version) .              // version needed to extract (2)
			
			pack('V', 0) .              // number of this disk (4)
			pack('V', 0) .              // number of disk with start of central directory (4)
			pack('P', sizeof($va_ctrl_dir)) .  			// total # of entries on this disk
			pack('P', sizeof($va_ctrl_dir)) .  			// total # of entries overall
			pack('P', strlen($vs_ctrl_dir)) .  			// size of central dir
			pack('P', $vn_new_offset) .		// offset
			"");
		
		
			$vn_current_offset += strlen($vs_zip64_ctrl_dir);
		}
		
		//
		// Add Zip64 end of central directory locator if required
		//
		if ($vb_need_zip64) {
			fwrite($r_out, $vs_end_of_zip64_ctrl_dir = "\x50\x4b\x06\x07\x00\x00\x00\x00" .
			pack('P', $vn_zip64_ctrl_dir_offset) .              // offset to start of central dir
			"\x01\x00\x00\x00");
			$vn_current_offset += strlen($vs_end_of_zip64_ctrl_dir);
		}
		
		//
		// Add End of central directory record
		//
		fwrite($r_out, $vs_end_of_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00" .
		pack('v', sizeof($va_ctrl_dir)) .  // total # of entries "on this disk"
		pack('v', sizeof($va_ctrl_dir)) .  // total # of entries overall
		pack('V', strlen($vs_ctrl_dir)) .           // size of central dir
		($vb_need_zip64 ? "\xFF\xFF\xFF\xFF" : pack('V', $vn_new_offset)).
		"\x00\x00");
		$vn_current_offset += strlen($vs_end_of_ctrl_dir);
		
		ob_flush();
		flush();
		fclose($r_out);
    } 
	# ----------------------------------------------------------------------
	/**
	 * Output ZIP archive to client. Output is always directed to php://output. If you need the output in a file
	 * you can capture it from output, but you probably wouldn't be using this class if you needed a file, would you?
	 *
	 * @param array $pa_options An array of options. Supported options are:
	 *		compression = level of ZLIB compression to apply. Set to zero for no compression. [Default is 0]
	 *		timeLimit = number of seconds to set PHP execution time limit too. Set to zero to suspend time limit. [Default is 0]
	 *		useZIP64 = force use or disuse of ZIP64 format. [Default is to use ZIP64 only if archive is over 3.9gigs in size]
	 *		dontWrite = send output to /dev/null. Used for debugging purposes [Default is false]
	 */
	public function stream($pa_options=null) {
		$this->_stream($pa_options);	
		$this->init();
	}
	# ----------------------------------------------------------------------
}