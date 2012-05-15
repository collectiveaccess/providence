<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/DbResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2011 Whirl-i-Gig
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

include_once(__CA_LIB_DIR__."/core/Db/DbBase.php");

include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/File/FileInfoCoder.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimecodeParser.php");

/**
 * Database abstraction results class (supercedes ancient Db_Sql class)
 */
class DbResult extends DbBase {

	/**
	 * Instance of db driver
	 *
	 * @access private
	 */
	var $opo_db;

	/**
	 * MySQL result set id
	 *
	 * @access private
	 */
	var $opr_res;

	/**
	 * Content of current row
	 *
	 * @var array
	 */
	var $opa_current_row;

	/**
	 * Number of current row, numbering starts at zero
	 *
	 * @var int
	 */
	var $opn_current_row;

	/**
	 * caches unserialized field data for each row; saves a potential decompression and unserialize
	 *
	 * @access private
	 */
	private $opa_unserialized_cache;


	/**
	 * Datamodel instance
	 *
	 * @access private
	 */
	private $opo_datamodel;

	/**
	 * Cache for fieldInfo() results
	 *
	 * @access private
	 */
	static $s_field_info_cache;

	/**
	 * Constructor
	 *
	 * @param mixed $po_db instance of the db driver you are using
	 * @param mixed $pr_res SQL result set resource
	 */
	function __construct(&$po_db, $pr_res) {

		$this->opo_datamodel = Datamodel::load();
		if (!isset($GLOBALS["_DbResult_time_expression_parser"]) || !$GLOBALS["_DbResult_time_expression_parser"]) { $GLOBALS["_DbResult_time_expression_parser"] = new TimeExpressionParser(); }
		if (!isset($GLOBALS["_DbResult_timecodeparser"]) || !$GLOBALS["_DbResult_timecodeparser"]) { $GLOBALS["_DbResult_timecodeparser"] = new TimecodeParser(); }

		if (!isset($GLOBALS["_DbResult_mediainfocoder"]) || !$GLOBALS["_DbResult_mediainfocoder"]) { $GLOBALS["_DbResult_mediainfocoder"] = MediaInfoCoder::load(); }
		if (!isset($GLOBALS["_DbResult_fileinfocoder"]) || !$GLOBALS["_DbResult_fileinfocoder"]) { $GLOBALS["_DbResult_fileinfocoder"] = FileInfoCoder::load(); }

		$this->opo_db =& $po_db;
		$this->opr_res = $pr_res;
		$this->opn_current_row = 0;
	}

	/**
	 * Move the pointer to the next row of the result set.
	 *
	 * @return bool true on success, false if there is no next row
	 */
	function nextRow() {
		if ($this->opa_current_row = $this->opo_db->nextRow($this, $this->opr_res)) {
			$this->opn_current_row++;
			$this->opa_unserialized_cache = array();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the value of a field in the current row.
	 * Possible keys in the options array:
	 * binary, unserialize, convertHTMLBreaks, urlEncode, filterHTMLSpecialCharacters, escapeForXML, stripSlashes
	 *
	 * @param string $ps_field field name
	 * @param array $pa_options associative array of options, keys are names of the options, values are boolean.
	 * @return mixed
	 */
	function get($ps_field, $pa_options=null) {

		$va_field = isset(DbResult::$s_field_info_cache[$ps_field]) ? DbResult::$s_field_info_cache[$ps_field] : $this->getFieldInfo($ps_field);

		if (!isset($this->opa_current_row[$va_field["field"]])) {
			return null;
		}

		$vs_val = isset($this->opa_current_row[$va_field["field"]]) ? $this->opa_current_row[$va_field["field"]] : null;

		if (isset($pa_options["binary"]) && $pa_options["binary"]) {
			return $vs_val;
		}
		if (isset($pa_options["unserialize"]) && $pa_options["unserialize"]) {
			if (!isset($this->opa_unserialized_cache[$va_field["field"]]) || !($vm_data = $this->opa_unserialized_cache[$va_field["field"]])) {
				$vm_data = caUnserializeForDatabase($vs_val);
				$this->opa_unserialized_cache[$va_field["field"]] =& $vm_data;
			}
			return $vm_data;
		}

		if (isset($pa_options["convertHTMLBreaks"]) && ($pa_options["convertHTMLBreaks"])) {
			# check for tags before converting breaks
			preg_match_all("/<[A-Za-z0-9]+/", $vs_val, $va_tags);
			$va_ok_tags = array("<b", "<i", "<u", "<strong", "<em", "<strike", "<sub", "<sup", "<a", "<img", "<span");

			$vb_convert_breaks = true;
			foreach($va_tags[0] as $vs_tag) {
				if (!in_array($vs_tag, $va_ok_tags)) {
					$vb_convert_breaks = false;
					break;
				}
			}

			if ($vb_convert_breaks) {
				$vs_val = preg_replace("/(\n|\r\n){2}/","<p/>",$vs_val);
				$vs_val = ereg_replace("\n","<br/>",$vs_val);
			}
		}
		if (isset($pa_options["urlEncode"]) && ($pa_options["urlEncode"])) {
			$vs_val = urlEncode($vs_val);
		}

		if (isset($pa_options["filterHTMLSpecialCharacters"]) && ($pa_options["filterHTMLSpecialCharacters"])) {
			$vs_val = htmlentities(html_entity_decode($vs_val));
		}

		if (isset($pa_options["escapeForXML"]) && $pa_options["escapeForXML"]) {
			$vs_val = escapeForXML($vs_val);
		}

		if (get_magic_quotes_gpc() || $pa_options["stripSlashes"]) {
			$vs_val = stripSlashes($vs_val);
		}

		return $vs_val;
	}

	/**
	 * If you want to adress certain fields by their number (starting at zero) instead of the name, use this method.
	 *
	 * @see DbResult::get()
	 * @param int $pn_i
	 * @param array $pa_options
	 * @return mixed
	 */
	function &getFieldAtIndex($pn_i, $pa_options=null) {
		$va_keys = array_keys($this->opa_current_row);
		if (($pn_i >= 0) && ($pn_i < sizeof($va_keys))) {
			return $this->get($va_keys[$pn_i], $pa_options);
		}
		return null;
	}

	/**
	 * Fetches the current row
	 *
	 * @return array An associative array with a fieldname => value mapping
	 */
	function &getRow() {
		return $this->opa_current_row;
	}

	/**
	 * How many rows does our DbResult have?
	 *
	 * @return int number of rows in the resultset
	 */
	function numRows() {
		if (!$this->opo_db->supports($this, "numrows")) { return false; }
		return $this->opo_db->numRows($this, $this->opr_res);
	}

	/**
	 * Move the pointer to a certain position in the result set.
	 *
	 * @param int Position where the pointer should move to; default is 0.
	 * @return bool Success or not
	 */
	function seek($pn_pos=0) {
		$this->clearErrors();
		$this->opo_db->seek($this, $this->opr_res, $pn_pos);

		if ($this->numErrors()) {
			$this->opo_db->seek($this, $this->opr_res, 0);
			$this->opn_current_row = 0;
    		return false;
		}
		$this->opn_current_row = $pn_pos;
    	return true;
	}
	# ---------------------------------------------------------------------------
	/**
	  * Return all rows from the result set as a list. Each item in the list is an array with keys set to field names and values set to field values
	  * 
	  * @return array List of arrays containing row data
	  */
	public function getAllRows() {
		$this->seek(0);
		$va_rows = array();
		while($this->nextRow()) {
			$va_rows[] = $this->getRow();
		}
		
		return $va_rows;
	}
	# ---------------------------------------------------------------------------
	/**
	  * Returns a list of values for the specified field from all rows in the result set. If you need to extract all values from single field in a result set this method provides a convenient means to do so.
	  *
	  * @param string $ps_field Name of field to fetch
	  * @return array List of values for the specified fields
	  */
	public function getAllFieldValues($ps_field) {
		$this->seek(0);
		$va_values = array();
		while($this->nextRow()) {
			$va_values[] = $this->get($ps_field);
		}
		
		return $va_values;
	}
	# ---------------------------------------------------------------------------
	# Support for Weblib field types
	# ---------------------------------------------------------------------------
	/**
	 * Fetches an array containing the table name, the field name and a reference to
	 * an instance of an object representation of that table.
	 * This method sets the DbResult_instance_cache and the object reference returned
	 * in the array points to that cache. The instance is useful primarily for getting information
	 * about fields in a result set such as the label text, validation rules and intended data type
	 *
	 * @access private
	 * @param string tablename.fieldname, e.g. objects.title
	 * @return array
	 */
	function getFieldInfo($ps_field) {
		if (isset(DbResult::$s_field_info_cache[$ps_field])) { return DbResult::$s_field_info_cache[$ps_field]; }
		$va_tmp = explode(".", $ps_field);
		switch(sizeof($va_tmp)) {
			case 1:		// query field name (no table specified, in other words)
				return DbResult::$s_field_info_cache[$ps_field] = array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			case 2:		// table.field format fieldname
				$o_instance = $this->opo_datamodel->getInstanceByTableName($va_tmp[0], true);

				if ($o_instance) {
					return DbResult::$s_field_info_cache[$ps_field] = array("table" => $va_tmp[0], "field" => $va_tmp[1], "instance" => $o_instance);
				}
				return DbResult::$s_field_info_cache[$ps_field] = array("table" => null, "field" => $ps_field, "instance" => null);
				break;
			default:	// invalid field name
				return DbResult::$s_field_info_cache[$ps_field] = false;
				break;
		}
	}
	
	/**
	 * Returns an associative array containing media details for the specified field, if the field
	 * is defined as FT_MEDIA in the table's class definition. If the media version parameter is
	 * specified then details are returned for the version only, otherwise details for all available
	 * versions are returned. If the infokey parameter is passed then the specified information for
	 * the version is returned, otherwise all information is returned.
	 * 
	 * When only the tablename.fieldname parameter is passed the returned array will contain the following keys:
	 *	ORIGINAL_FILENAME => original name of uploaded file 
	 *  INPUT => an array of information about the originally uploaded file, including the following keys:
	 *				MIMETYPE => mimetype of uploaded file
	 *				WIDTH => width in pixels of uploaded file
	 *				HEIGHT => height in pixels of uploaded file
	 *				MD5 => md5 hash for uploaded file
	 *				FILESIZE => size in bytes of uploaded file
	 *  
	 * In addition to the keys above there will be a key for each available version derived from the originally
	 * uploaded file. Each version key is associated with an array containing the following keys:
	 *  VOLUME => the logical volume the derived file is stored on; corresponds to a name defined in the media_volumes configuration file
	 *  MIMETYPE => the mimetype of the derived file
	 *  WIDTH => the width, in pixels, of the derived file
	 *  HEIGHT => the height, in pixels, of the derived file
	 *  PROPERTIES => an associative array of format-specific information about the media; the format of this array varies by media processing plug-in
	 *  FILENAME => the file name of the derived file, including the file extension
	 *  HASH => a series of nested numeric directories, based upon the primary key of the row the media file is part of, that the derived file is contained within. The directory nesting ensures that no single directory in the file system ever has too many files
	 *  MAGIC => a random number prepended to the file name to prevent browser caching of outdated media and to prevent easy "sucking" of media off of a logical volume by making it hard to guess file names
	 *  EXTENSION => the file extension used for the derived file
	 *  MD5 => md5 hash for derived file
	 *
	 * Note that while you can derive the path to a derived file by putting path information from the volume together with the hash, magic
	 * and file name, you are much better off using the getMediaPath() method
	 *
	 * @see DbResult::getMediaPath()
	 * @see DbResult::getMediaUrl()
	 * @see DbResult::getMediaTag()
	 * @see DbResult::getMediaVersions()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @param string infokey, e.g. MIMETYPE
	 * @return array
	 */
	function getMediaInfo($ps_field, $ps_version=null, $ps_key=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaInfo($this->get($va_field["field"], array("unserialize" => true)), $ps_version, $ps_key);
	}
	
	/**
	 * Returns the absolute path to the specified version in a media field. If the field is not declared as
	 * FT_MEDIA in the table's class definition or the version is invalid then the returned string will be empty.
	 *
	 * @see DbResult::getMediaUrl()
	 * @see DbResult::getMediaTag()
	 * @see DbResult::getMediaVersions()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @return string
	 */
	function getMediaPath($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaPath($this->get($va_field["field"], array("unserialize" => true)), $ps_version);
	}
	
	/**
	 * Returns the absolute URL to the specified version in a media field. If the field is not declared as
	 * FT_MEDIA in the table's class definition or the version is invalid then the returned string will be empty.
	 *
	 * @see DbResult::getMediaUrl()
	 * @see DbResult::getMediaTag()
	 * @see DbResult::getMediaVersions()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @return string
	 */
	function getMediaUrl($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaUrl($this->get($va_field["field"], array("unserialize" => true)), $ps_version);
	}
	
	/**
	 * Returns an HTML tag for the specified version in a media field. For images this will be an <img> tag, for andio/video this will usually
	 * be some kind of HTML embedding tag, but can vary from media type to media type. 
	 *
	 * An optional options array may be passed specifying opp. The options vary by media type but usually include the following basic HTML attributes:
	 *
	 *  idname => the HTML id attribute
	 *  style => the HTML style attribute
	 *  class => the HTML CSS classname to apply to the tag
	 *  border => for images, the border to apply; default is 0
	 *  alt => alternate text to attach to an image 
	 *  title => title text to attach to an image
	 *  usemap => specifies imagemap to overlay on an image
	 *
	 * If the field is not declared as FT_MEDIA in the table's class definition or the version is invalid then the returned string will be empty.
	 *
	 * @see DbResult::getMediaUrl()
	 * @see DbResult::getMediaTag()
	 * @see DbResult::getMediaVersions()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @param array options 
	 * @return string
	 */
	function getMediaTag($ps_field, $ps_version, $pa_options=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaTag($this->get($va_field["field"], array("unserialize" => true)), $ps_version, $pa_options);
	}
	
	/**
	 * Returns an array list of all versions available in the specified field.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @return array
	 */
	function getMediaVersions($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaVersions($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * Returns true if the specified media field contains the version.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @return boolean
	 */
	function hasMediaVersion($ps_field, $ps_version) {
		if (!is_array($va_tmp = $this->getMediaVersions($ps_field))) {
			return false;
		}
		return in_array($ps_version, $va_tmp);
	}
	
	/**
	 * Returns true if the specified media field actually contains media information and is not blank.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @return boolean
	 */
	function hasMedia($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->hasMedia($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * The returns the number of external servers the specified media field version is configured to be mirrored to. If the version is not
	 * configured for mirroring, then returns zero.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @return boolean
	 */
	function mediaIsMirrored($ps_field, $ps_version) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->mediaIsMirrored($this->get($va_field["field"], array("unserialize" => true)), $ps_version);
	}
	
	/**
	 * Returns current status of mirroring for a given media field verion. Possible return values are
	 *
	 * PARTIAL => mirroring is in progress
	 * FAIL => mirroring failed
	 * SUCCESS => mirroring completed successfully
	 *
	 * A result of undefined indicates that mirroring has not yet begun.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media
	 * @param string media version, e.g. thumbnail
	 * @param string unique name of mirror, e.g. streaming_server
	 * @return boolean
	 */
	function getMediaMirrorStatus($ps_field, $ps_version, $ps_mirror=null) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_mediainfocoder"]->getMediaMirrorStatus($this->get($va_field["field"], array("unserialize" => true)), $ps_version, $ps_mirror);
	}
	
	/**
	 * Returns an associative array containing details for the specified file, if the field
	 * is defined as FT_FILE in the table's class definition. 
	 * The returned array will contain the following keys:
	 *
	 * FILE => always set to 1
	 * ORIGINAL_FILENAME => original name of uploaded file
	 * VOLUME => the logical volume the uploaded file is stored on; corresponds to a name defined in the file_volumes configuration file
	 * MIMETYPE => he mimetype of the uploaded file
	 * FILENAME => the file name of the uploaded file, including the file extension
	 * HASH => a series of nested numeric directories, based upon the primary key of the row the file is part of, that the uploaded file is contained within. The directory nesting ensures that no single directory in the file system ever has too many files
	 * MAGIC => a random number prepended to the file name to prevent browser caching of outdated media and to prevent easy "sucking" of media off of a logical volume by making it hard to guess file names
	 * PROPERTIES => an associative array of format-specific information about the file; for unrecognized formats this will be minimal; the format of this array varies by file processing plug-in
	 * DANGEROUS => will evaluate to true if the file is considered "dangerous" (can be executed on the server); an additional non-executable file extension is automatically added to such files 
	 * CONVERSIONS => an array list of format conversions performed upon upload; not all files can be converted other formats, and conversions are never guaranteed to succeed in any event (this is different from FT_MEDIA fields where conversions are guaranteed to succeed otherwise the uploaded file is rejected) Each item in the CONVERSIONS list is an associative array with the following keys:
	 *			MIMETYPE => the mimetype of the converted file
	 *			FILENAME => the filename of the converted file (the volume and hash are the same as the original file)
	 *			PROPERTIES => an associative array of information about the converted file, including these keys:
	 *					filesize => size of file in bytes
	 *					extension => file extension of file
	 *					format_name => short display-able name of format file conversion is in
	 *					long_format_name => more descriptive name of format
	 * MD5 => md5 hash for the uploaded file
	 *
	 *
	 * @see DbResult::getFileUrl()
	 * @see DbResult::getFilePath()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @return array
	 */
	function getFileInfo($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileInfo($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * Returns the absolute path to the file contained in the specified file field. If the field is not declared as
	 * FT_FILE in the table's class definition or the field is empty then the returned string will be empty.
	 *
	 * @see DbResult::getFileUrl()
	 * @see DbResult::getFileInfo()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @return string
	 */
	function getFilePath($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFilePath($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * Returns the absolute URL to the file contained in the specified file field. If the field is not declared as
	 * FT_FILE in the table's class definition or the field is empty then the returned string will be empty.
	 *
	 * @see DbResult::getFilePath()
	 * @see DbResult::getFileInfo()
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @return string
	 */
	function getFileUrl($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileUrl($this->get($va_field["field"], array("unserialize" => true)));
	}

	/**
	 * Returns true if the specified file field actually contains file information and is not blank.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @return boolean
	 */
	function hasFile($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->hasFile($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * Returns an array list of available conversions based upon the uploaded file. Each item in the list contains the following keys:
	 *
	 *  filesize => size of file in bytes
	 *	extension => file extension of file
	 *	format_name => short display-able name of format file conversion is in
	 *	long_format_name => more descriptive name of format
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @return array
	 */
	function getFileConversions($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversions($this->get($va_field["field"], array("unserialize" => true)));
	}
	
	/**
	 * Returns absolute path to converted file with the desired mimetype, or undefined if no such file exists
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @param string mimetype of desired conversion 
	 * @return boolean
	 */
	function getFileConversionPath($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionPath($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
	}
	
	/**
	 * Returns absolute URL to converted file with the desired mimetype, or undefined if no such file exists
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @param string mimetype of desired conversion
	 * @return boolean
	 */
	function getFileConversionUrl($ps_field, $ps_mimetype) {
		$va_field = $this->getFieldInfo($ps_field);
		return $GLOBALS["_DbResult_fileinfocoder"]->getFileConversionUrl($this->get($va_field["field"], array("unserialize" => true)), $ps_mimetype);
	}
	
	/**
	 * Converts a date field into text for display. Note that dates are stored as either traditional Unix timestamps
	 * or as "historic" dates in a floating point format. Date ranges are stored as a pair of fields, single dates in a single
	 * field. This method automatically deals with Unix timestamps vs. historic dates, but care must be taken when dealing with
	 * date ranges. The field name for a single date is simply the field name, of course. But for ranges, the field name you
	 * pass in the first parameter is the *virtual* field for the range name defined in the table's class definition. This virtual field
	 * is composed of the two fields used to store the range data. So, in other words, for date ranges the field name you pass to this method
	 * will *not* exist in the database, but rather will be defined in the table class definition and point to two fields that do in fact exist
	 * in the database.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_documents.file_info
	 * @param array options; valid options include
	 *		getRawDate => returns raw numeric date data as set in the database; for single dates this is a number; for date ranges this will be an array including the start and end dates
	 * @return string
	 */
	function getDate($ps_field, $pa_options=null) {

		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if (!in_array($vn_field_type = $va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE"), array(FT_DATE, FT_TIME, FT_DATETIME, FT_TIMESTAMP, FT_HISTORIC_DATETIME, FT_HISTORIC_DATERANGE, FT_DATERANGE))) {
				return false;
			}

			$vn_val = $this->get($va_field["field"], array("binary" => true));
			$GLOBALS["_DbResult_time_expression_parser"]->init();	// get rid of any lingering date-i-ness
			switch($vn_field_type) {
				case (FT_DATE):
				case (FT_TIME):
				case (FT_DATETIME):
				case (FT_TIMESTAMP):
				case (FT_HISTORIC_DATETIME):
					if ($pa_options["getRawDate"]) {
						return $vn_val;
					} else {
						if ($vn_field_type == FT_HISTORIC_DATETIME) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($vn_val, $vn_val);
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($vn_val, $vn_val);
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					}
					break;
				case (FT_DATERANGE):
				case (FT_HISTORIC_DATERANGE):
					$vs_start_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"START");
					$vs_end_field_name = 	$va_field["instance"]->getFieldInfo($va_field["field"],"END");

					if (!$pa_options["getRawDate"]) {
						if ($vn_field_type == FT_HISTORIC_DATERANGE) {
							$GLOBALS["_DbResult_time_expression_parser"]->setHistoricTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						} else {
							$GLOBALS["_DbResult_time_expression_parser"]->setUnixTimestamps($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
						}
						return $GLOBALS["_DbResult_time_expression_parser"]->getText();
					} else {
						return array($this->get($vs_start_field_name, array("binary" => true)), $this->get($vs_end_field_name, array("binary" => true)));
					}
					break;
			}
		}
	}
	
	/**
	 * For fields defined at FT_TIMECODE in the table class definition, this method will return a timecode string
	 * (timecode being a specification of elapsed hours, minutes, seconds as used in time-based cataloguing). By
	 * default this is just the number of seconds, but it can be formatted in more useful formats using the format 
	 * parameter.
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_clips.start_time
	 * @param string optional format specification for timecode; valid formats are:
	 * 		RAW => integer display of number of seconds [default]
	 *		COLON_DELIMITED => timecode returned in format hh:mm:ss (hh=hours, mm=minutes, ss=seconds; ex. 2:10:15)
	 *		HOURS_MINUTES_SECONDS => timecode returned in format Xh Xm Xs (where X=number; ex. 2h 10m 15s)
	 * @return string
	 */
	function getTimecode($ps_field, $ps_format=null) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_TIMECODE) {
				return false;
			}
		}

		if (is_numeric($vn_tc = $this->get($va_field["field"]))) {
			$GLOBALS["_DbResult_timecodeparser"]->setParsedValueInSeconds($vn_tc);
			return $GLOBALS["_DbResult_timecodeparser"]->getText($ps_format);
		} else {
			return false;
		}
	}
	
	/**
	 * For text and numeric fields with a static choice list defined with a BOUNDS_CHOICE_LIST setting in the table class definition
	 * this method will convert the value of the field into the display value of the choice list. For example, if you have a choice list
	 * with values 0, 1 and 2 and corresponding display options ('New', 'First edit', 'Complete'), then if the value in the database
	 * is 2, this function will return 'Complete'
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. objects.status
	 * @return string
	 */
	function getChoiceListValue($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if(is_object($va_field["instance"])) {
			if (is_array($va_field["instance"]->getFieldInfo($va_field["field"], "BOUNDS_CHOICE_LIST"))) {
				return $va_field["instance"]->getChoiceListValue($va_field["field"], $this->get($va_field["field"]));
			} else {
				// no choice list; return actual field value
				return $this->get($va_field["field"]);
			}
		} else {
			return false;
		}
	}
	
	/**
	 * For fields defined at FT_VARS (container for serialized PHP vars) in the table's class definition, this method
	 * will unserialize the variables and return to you a PHP data structure ready for use. 
	 *
	 * @access public
	 * @param string tablename.fieldname, e.g. object_representations.media_metadata
	 * @return mixed
	 */
	function getVars($ps_field) {
		$va_field = $this->getFieldInfo($ps_field);
		if (is_object($va_field["instance"])) {
			if ($va_field["instance"]->getFieldInfo($va_field["field"], "FIELD_TYPE") != FT_VARS) {
				return false;
			}
		}
		return $this->get($va_field["field"], array("unserialize" => true));
	}

	/**
	 * Free result memory
	 */
	function free() {
		$this->opo_db->free($this, $this->opr_res);
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		//print "DESTRUCT Result set\n";
		$this->free();
		unset($this->opo_db);
	}
}
?>