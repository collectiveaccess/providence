<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/ZipFile.php : 
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
# ----------------------------------------------------------------------
#
# Based upon :
#
#  http://www.zend.com/codex.php?id=535&single=1
#  By Eric Mueller <eric@themepark.com>
#
#  http://www.zend.com/codex.php?id=470&single=1
#  by Denis125 <webmaster@atlant.ru>
#
#  a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
#  date and time of the compressed file
#
# Official ZIP file format: http://www.pkware.com/appnote.txt
#
# ** Changes from original class **
#
# (1) Changed addFile() to accept file paths in addition to string data
# (2) Class conserves memory by writing compressed data to disk during processing
#	  and minimizing swapping of full files in memory between variables. Can now 
#	  operate in a memory limited environment
# (3) Support for direct writing of output to disk and output, as well as a returned string
# (4) Fixed "data descriptor" header bug that prevents Macintosh Stuffit application from
#	  processing files created with this module
# (5) Added ability to return path to newly created zip file
# 
# ----------------------------------------------------------------------

# 
# Constants for use with output() method
# 
define("ZIPFILE_RETURN_HANDLE", 0);		# return handle to newly created zip file
define("ZIPFILE_RETURN_STRING", 1);		# return contents of zip file as in-memory string
define("ZIPFILE_PASSTHRU", 2);			# pass zip file to output buffer
define("ZIPFILE_FILEPATH", 3);			# return path to new zip file

class ZipFile {
	# ----------------------------------------------------------------------
	# --- Properties
	# ----------------------------------------------------------------------
    /**
     * Central directory
     *
     * @var  array    $ctrl_dir
     */
    var $ctrl_dir     = array();

    /**
     * End of central directory record
     *
     * @var  string   $eof_ctrl_dir
     */
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * Last offset position
     *
     * @var  integer  $old_offset
     */
    var $old_offset   = 0;
    
    
    #
    # total size, in bytes, of compressed data segment
    #
    var $datasize = 0;
    
    #
    # output debugging messages
    #
    var $debug = 0;

	#
	# handle to temporary output file
	#
	var $_tmp_data;
	
	#
	# pathname of temporary output file
	#
	var $_tmp_dath_path;
	
	#
	# "finished" flag; if true zip file is done (no more files can be added) and ready for output 
	#
	var $finished = 0;
	
	# ----------------------------------------------------------------------
	# --- Methods
	# ----------------------------------------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_tmp_data_path = tempnam("/tmp", "ZIP");
		$this->_tmp_data = fopen($this->_tmp_data_path, "w+");
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
    } // end of the 'unix2DosTime()' method

	# ----------------------------------------------------------------------
    /**
     * Adds "file" to archive
     *
     * @param string $data file contents (or file path)
     * @param string $name name of the file in the archive (may contains the path)
     * @param integer $time the current timestamp
     * @param array $pa_options An array of options. Supported options are:
     *		compression = a number between 0 and 9 where zero is no compression (fastest) and 9 is most compression (slowest). Default is to use PHP default compression level.
     *
     * @access public
     */
    public function addFile($data, $name, $time=0, $pa_options=null) {
    	if ($this->finished) {
    		return 0;	
    	}
    
    	if ($this->debug) { print "DEBUG (ZipFile.php); addFile() START: ".memory_get_usage()."<br>\n"; }
    	# PATCH - 14 November 2003
    	#
    	# Allows file paths to be added directly
    	# If $data parameter begins with a forward slash and ends
    	# with a letter, number or underscore and is 255 characters
    	# or less, it is treated as a file path
    	if ((strlen($data) <= 255) && ((preg_match("/^\/.*[A-Za-z0-9_]{1}$/", $data)) || (preg_match("/^[A-Za-z]{1}:.*[A-Za-z0-9_]{1}$/", $data)))) {
    		$path = $data;
    		if ($fp = fopen($path,"r")) {
    			$l = filesize($path);
    			$data = fread($fp,$l);
    		}
    	}
    	if ($this->debug) { print "DEBUG (ZipFile.php); addFile() AFTER LOAD FILE: ".memory_get_usage()."<br>\n"; }

        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hex      = $dtime[6] . $dtime[7] . $dtime[4] . $dtime[5] . $dtime[2] . $dtime[3] . $dtime[0] . $dtime[1];
        
        if(function_exists('hex2bin')) { // this is only available in PHP 5.4+
            $hexdtime = hex2bin($hex);
        } else {
            $hexdtime = pack("H*" , $hex);    
        }

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data,(isset($pa_options['compression']) && ((int)$pa_options['compression'] >= 0)) ? (int)$pa_options['compression'] : -1);

        $data = "";  

        $c_len   = strlen($zdata) - 6; // fix crc bug by triming first two chars and last four (actual trimming is done below)
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;
        
        fwrite($this->_tmp_data, $fr);
        $segsize = strlen($fr);

        // "file data" segment
        fwrite($this->_tmp_data, substr($zdata, 2, $c_len));
        $segsize += $c_len;
		$zdata = "";
		
        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        #$fr = pack('V', $crc);                  // crc32
        #$fr .= pack('V', $c_len);               // compressed filesize
        #$fr .= pack('V', $unc_len);             // uncompressed filesize

        #fwrite($this->_tmp_data, $fr);
        #$segsize += strlen($fr);
        $this->datasize += $segsize;
        
        $new_offset = $this->datasize;
		
        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
        
        return 1;
    } // end of the 'addFile()' method

	# ----------------------------------------------------------------------
	/**
	 * Output ZIP archive
	 *
	 * @param int $output Constant indicating where output should be sent. Constants are:
	 *		ZIPFILE_RETURN_HANDLE = return file handle for output
	 *		ZIPFILE_RETURN_STRING = return output as string; this will consume as much memory as the total output size and should only be used for small archives.
	 *		ZIPFILE_PASSTHRU = pass through compressed data to standard output
	 *		ZIPFILE_FILEPATH = return a path to the temporary file containing the ZIP archive output
	 *	The default is to return a file handle.
	 *
	 *	@return mixed The output is the form specified by $output.
	 */
	public function output($output=0) {
		if (!$this->finished) {
			$ctrldir = implode('', $this->ctrl_dir);
		
			fwrite($this->_tmp_data, $ctrldir);
			fwrite($this->_tmp_data, $this -> eof_ctrl_dir .
			pack('v', sizeof($this->ctrl_dir)) .  // total # of entries "on this disk"
			pack('v', sizeof($this->ctrl_dir)) .  // total # of entries overall
			pack('V', strlen($ctrldir)) .           // size of central dir
			pack('V', $this->datasize) .              // offset to start of central dir
			"\x00\x00");
			$this->finished = 1;
		}
		fseek($this->_tmp_data, 0);
		
		switch($output) {
			# --------------------------------------
			case ZIPFILE_RETURN_HANDLE:
				return $this->_tmp_data;
				break;
			# --------------------------------------
			case ZIPFILE_RETURN_STRING:
				return file_get_contents($this->_tmp_data_path);
				break;
			# --------------------------------------
			case ZIPFILE_PASSTHRU:
				//fpassthru($this->_tmp_data);
				while(!feof($this->_tmp_data) and (connection_status()==0)) {
					print(fread($this->_tmp_data, 1024*8));
					flush();
				}
				break;
			# --------------------------------------
			case ZIPFILE_FILEPATH:
				return $this->_tmp_data_path;
				break;
			# --------------------------------------
			default:
				die("Invalid output type: $output");
				break;
			# --------------------------------------
		}
	}
	# ----------------------------------------------------------------------
	/**
	 * Checks if archive is complete and can be output
	 *
	 * @return bool Returns true if file is complete
	 */
	public function isFinished() {
		return $this->finished;
	}
	# ----------------------------------------------------------------------
	public function __destruct() {
		if ($this->_tmp_data_path) {		// dispose of temporary file
			@unlink($this->_tmp_data_path);
		}
	}
	# ----------------------------------------------------------------------

} // end of the 'zipfile' class
?>