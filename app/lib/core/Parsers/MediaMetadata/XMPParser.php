<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/MediaMetadata/XMPMediaMetadata.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
 *
 * Portions derived from the PHP JPEG Metadata Toolkit (http://electronics.ozhiker.com)
 * Copyright 2004 Evan Hunter
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
 
require_once(__CA_LIB_DIR__."/core/Parsers/MediaMetadata/BaseMediaMetadataParser.php");
 
 /**
  * The XMPParser class parses files for embedded XMP-format metadata. It provides an API
  * for both extraction of XMP metadata as well as writing of metadata to an image file.
  *
  * Existing XMP metadata can be extracted after a parse as a SimpleXML object, or as XML-format text
  * 
  * XMP metadata for selected fields, defined in XMPParser::$opa_fields below, may be set. Note that 
  * any metadata you write using the parser overwrites all existing XMP metadata. Also note that for
  * improved compatibility with applications such as Adobe Photoshop, writing XMP data will remove
  * any IPTC-embedded data in the file. After a write of metadata, only the written metadata be be a part
  * of the file. All other data is removed.
  *
  * XMPParser currently supports only JPEG-format images. Support for PDFs and TIFFs may is planned.
  *
  * NOTE: This class incorporates code derived from the  PHP JPEG Metadata Toolkit (http://electronics.ozhiker.com)
  *            by Evan Hunter. It is Copyright Evan Hunter 2004, and licensed under the same GPL license as CollectiveAccess
  *
  */
class XMPParser extends BaseMediaMetadataParser {
	/**
	 * JPEG format segment codes and names
	 */
	static $s_jpeg_segment_names = array(
		0xC0 =>  "SOF0",  0xC1 =>  "SOF1",  0xC2 =>  "SOF2",  0xC3 =>  "SOF4",
		0xC5 =>  "SOF5",  0xC6 =>  "SOF6",  0xC7 =>  "SOF7",  0xC8 =>  "JPG",
		0xC9 =>  "SOF9",  0xCA =>  "SOF10", 0xCB =>  "SOF11", 0xCD =>  "SOF13",
		0xCE =>  "SOF14", 0xCF =>  "SOF15",
		0xC4 =>  "DHT",   0xCC =>  "DAC",
		
		0xD0 =>  "RST0",  0xD1 =>  "RST1",  0xD2 =>  "RST2",  0xD3 =>  "RST3",
		0xD4 =>  "RST4",  0xD5 =>  "RST5",  0xD6 =>  "RST6",  0xD7 =>  "RST7",
		
		0xD8 =>  "SOI",   0xD9 =>  "EOI",   0xDA =>  "SOS",   0xDB =>  "DQT",
		0xDC =>  "DNL",   0xDD =>  "DRI",   0xDE =>  "DHP",   0xDF =>  "EXP",
		
		0xE0 =>  "APP0",  0xE1 =>  "APP1",  0xE2 =>  "APP2",  0xE3 =>  "APP3",
		0xE4 =>  "APP4",  0xE5 =>  "APP5",  0xE6 =>  "APP6",  0xE7 =>  "APP7",
		0xE8 =>  "APP8",  0xE9 =>  "APP9",  0xEA =>  "APP10", 0xEB =>  "APP11",
		0xEC =>  "APP12", 0xED =>  "APP13", 0xEE =>  "APP14", 0xEF =>  "APP15",
		
		
		0xF0 =>  "JPG0",  0xF1 =>  "JPG1",  0xF2 =>  "JPG2",  0xF3 =>  "JPG3",
		0xF4 =>  "JPG4",  0xF5 =>  "JPG5",  0xF6 =>  "JPG6",  0xF7 =>  "JPG7",
		0xF8 =>  "JPG8",  0xF9 =>  "JPG9",  0xFA =>  "JPG10", 0xFB =>  "JPG11",
		0xFC =>  "JPG12", 0xFD =>  "JPG13",
		
		0xFE =>  "COM",   0x01 =>  "TEM",   0x02 =>  "RES",
	);
	# -------------------------------------------------------
	private $ops_filepath = null;					// path to parsed JPEG-format file
	
	private $opa_metadata = array();			//
	private $ops_header_data = null;
	private $opa_isset = array();
	# -------------------------------------------------------
	private $opa_fields = array();
	# -------------------------------------------------------
	/**
	 * XMP "fields" we support. Each of these maps to a specific element in the XMP RDF document 
	 * to be embedded into the JPEG file
	 */
	public function __construct() {
		$this->opa_fields = array(
			'Format' => array(
				'namespace' => 'dc',
				'tag' => 'format',
				'description' => _t('Mimetype of file')
			),
			'DateCreated' => array(
				'namespace' => 'photoshop',
				'tag' => 'DateCreated',
				'description' => _t('Creation date')
			),
			'RightsURL' => array(
				'namespace' => 'xmpRights',
				'tag' => 'WebStatement',
				'description' => _t('Copyright Information URL')
			),
			'Title' => array(
				'namespace' => 'dc',
				'tag' => 'title',
				'description' => _t('Document title')
			),
			'Description' => array(
				'namespace' => 'dc',
				'tag' => 'description',
				'description' => _t('Document description')
			),
			'Rights' => array(
				'namespace' => 'dc',
				'tag' => 'rights',
				'description' => _t('Copyright notice')
			),
			'CopyrightStatus' => array(
				'namespace' => 'xmpRights',
				'tag' => 'Marked',
				'description' => _t('Is under copyright?')
			),
			'Creator' => array(
				'namespace' => 'dc',
				'tag' => 'creator',
				'description' => _t('Document creator')
			),
			'Subjects' => array(
				'namespace' => 'dc',
				'tag' => 'subject',
				'description' => _t('Keywords')
			),
			'CreatorAddress' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiAdrExtadr',
				'description' => _t('Creator address')
			),
			'CreatorCity' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiAdrCity',
				'description' => _t('Creator city')
			),
			'CreatorStateRegion' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiAdrRegion',
				'description' => _t('Creator state/region')
			),
			'CreatorPostalCode' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiAdrPcode',
				'description' => _t('Creator postal code')
			),
			'CreatorCountry' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiAdrCtry',
				'description' => _t('Creator country')
			),
			'CreatorPhone' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiTelWork',
				'description' => _t('Creator phone')
			),
			'CreatorEmail' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiEmailWork',
				'description' => _t('Creator email')
			),
			'CreatorWebsite' => array(
				'namespace' => 'Iptc4xmpCore',
				'tag' => 'CiUrlWork',
				'description' => _t('Creator website')
			),
			'DescriptionWriter' => array(
				'namespace' => 'photoshop',
				'tag' => 'CaptionWriter',
				'description' => _t('Description writer')
			)
		);
		parent::__construct();
		
	}
	# -------------------------------------------------------
	/**
	 * Parses an image, extracting the JPEG header data and initializing various properties
	 *
	 * @param string $ps_filepath The path to the JPEG format
	 * @return bool Always returns true
	 */
	public function parse($ps_filepath) {
		$va_jpeg_header_data = $this->getJPEGHeaderData($ps_filepath);
		if (!is_array($va_jpeg_header_data)) { $va_jpeg_header_data = array(); }
		$this->ops_filepath = $ps_filepath;
		$this->opa_header_data = $va_jpeg_header_data;
		
		if (!sizeof($va_jpeg_header_data)) {
			$this->opo_old_metadata = null;
		} else {
			$this->opo_old_metadata = simplexml_load_string($this->getXMPData($va_jpeg_header_data));
		}
		if (is_object($this->opo_old_metadata)) {
			$va_namespaces = $this->opo_old_metadata->getNameSpaces(true);
			$this->opo_metadata = $this->opo_old_metadata;
			
			$va_data = array();
			foreach($this->opo_old_metadata->children($va_namespaces['rdf']) as $vn_i => $o_rdf) {
				foreach($o_rdf->children($va_namespaces['rdf']) as $vn_i => $o_rdf_desc) {
					foreach($va_namespaces as $vs_namespace => $vs_namespace_url) {
						foreach($o_rdf_desc->children($vs_namespace_url) as $vs_tag => $o_item) {
							$va_extracted_values = $o_item->xpath(".//rdf:li");
							$va_values = array();
							foreach($va_extracted_values as $o_value_list) {
								$va_values[] = (string)$o_value_list;
							}
							$va_data["{$vs_namespace}:{$vs_tag}"] = $va_values;
						}
					}
				}
			}
			
			foreach($this->opa_fields as $vs_field => $va_field_info) {
				$vs_namespace_and_tag = $va_field_info['namespace'].":".$va_field_info['tag'];
				if (isset($va_data[$vs_namespace_and_tag])) {
					$this->opa_metadata[$vs_field] = $va_data[$vs_namespace_and_tag];
				}
			}
			
		
			$this->opo_metadata->registerXPathNamespace("x", "adobe:ns:meta/");
			$this->opo_metadata->registerXPathNamespace("Iptc4xmpCore", "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
			$this->opo_metadata->registerXPathNamespace("rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
			
			$this->o_rdf = $this->opo_metadata ->xpath('//rdf:RDF');
			$this->o_rdf_desc = $this->opo_metadata ->xpath('//rdf:RDF/rdf:Description');
			$this->o_creator = null;
			$this->opa_isset = array();
		} else {
			$this->initMetadata();
		}
		return true;
	}
	# -------------------------------------------------------
	/** 
	 * Clears any parsed metadata for the currently parsed file
	 */
	public function initMetadata() {
		$vs_xmp_data = '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
		<rdf:Description rdf:about="" xmlns:xmp="http://ns.adobe.com/xap/1.0/" xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/" xmlns:stEvt="http://ns.adobe.com/xap/1.0/sType/ResourceEvent#" xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/" xmlns:crs="http://ns.adobe.com/camera-raw-settings/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xmpRights="http://ns.adobe.com/xap/1.0/rights/" xmlns:Iptc4xmpCore="http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/" xmp:CreatorTool="CollectiveAccess" xmp:ModifyDate="'.date("c").'" xmp:CreateDate="'.date("c").'" xmp:MetadataDate="'.date("c").'"> </rdf:Description></rdf:RDF></x:xmpmeta>';

		$this->opo_metadata = simplexml_load_string($vs_xmp_data);
		$this->opa_metadata = array();
		
		
		$this->opo_metadata->registerXPathNamespace("x", "adobe:ns:meta/");
		$this->opo_metadata->registerXPathNamespace("Iptc4xmpCore", "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
		$this->opo_metadata->registerXPathNamespace("rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		
		$this->o_rdf = $this->opo_metadata ->xpath('//rdf:RDF');
		$this->o_rdf_desc = $this->opo_metadata ->xpath('//rdf:RDF/rdf:Description');
		$this->o_creator = null;
		$this->opa_isset = array();
	}
	# -------------------------------------------------------
	/**
	 * Writes XMP metadata to the specified JPEG-format image, removing all existing XMP and IPTC data in the process.
	 * IPTC data is removed because applications such as Photoshop will use XMP in preference while others will use
	 * IPTC in preference. To avoid confusion only one standard is used here.
	 *
	 * @param string $ps_filepath Optional path to write output to. If not specified the location of the input file is used.
	 * @return bool Returns true on success, false on error
	 */
	public function write($ps_filepath=null) {
		// set all elements that aren't set already
		foreach($this->opa_fields as $vs_field => $va_field_info) {
			if (!isset($this->opa_isset[$vs_field])) { $this->set($vs_field, ''); }
		}
		$this->opa_header_data = $this->removeJPEGHeaderSegment('APP1', $this->opa_header_data);	// get rid of existing XMP
		$this->opa_header_data = $this->removeJPEGHeaderSegment('APP13', $this->opa_header_data);	// get rid of IPTC
		
		$vs_packet = '<?xpacket begin="ï»¿" id="W5M0MpCehiHzreSzNTczkc9d"?>'.str_replace('<?xml version="1.0"?>', '', $this->opo_metadata->asXML()).'

<?xpacket end="w"?>';

		$this->opa_header_data = $this->putXMPData($this->opa_header_data, $vs_packet); 
		return $this->putJPEGHeaderData($this->ops_filepath, ($ps_filepath ? $ps_filepath : $this->ops_filepath), $this->opa_header_data);
	}
	# -------------------------------------------------------
	/**
	 * Sets value of one of the fields listed in XMPParser::$opa_fields above. The field values are used to 
	 * populate an RDF-format XML document embedded in the image file when written out to disk.
	 *
	 * @param string $ps_field The name of a field, as listed in XMPParser::$opa_fields
	 * @param mixed $ps_value The value of the field
	 * @return bool True on success, false on error
	 */
	public function set($ps_field, $ps_value) {
		if(!isset($this->opa_fields[$ps_field])) { return false; }	// bad field
		$ps_value = caEscapeForXML($ps_value);
		
		$this->opa_isset[$ps_field] = true;
		
		switch($ps_field) {
			case 'Format':
				$this->o_rdf_desc[0]->addAttribute('dc:format', $ps_value, "http://purl.org/dc/elements/1.1/");
				break;
			case 'DateCreated':
				$this->o_rdf_desc[0]->addAttribute('photoshop:DateCreated', $ps_value, "http://ns.adobe.com/photoshop/1.0/");
				break;
			case 'DescriptionWriter':
				$this->o_rdf_desc[0]->addAttribute('photoshop:CaptionWriter', $ps_value, "http://ns.adobe.com/photoshop/1.0/");
				break;
			case 'RightsURL':
				$this->o_rdf_desc[0]->addAttribute('xmpRights:WebStatement', $ps_value, "http://ns.adobe.com/xap/1.0/rights/");
				break;
			case 'CopyrightStatus':
				if (!is_null($ps_value)) {
					$vs_value = (bool)$ps_value ? "True" : "False";
					$this->o_rdf_desc[0]->addAttribute('xmpRights:Marked', $vs_value, "http://ns.adobe.com/xap/1.0/rights/");
				} 
				break;
			case 'Title':
				$o_node =$this->o_rdf_desc[0]->addChild("dc:title", '', "http://purl.org/dc/elements/1.1/");
				$o_node =$o_node->addChild("rdf:Alt", '', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node =$o_node->addChild("rdf:li", $ps_value, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node->addAttribute('xml:lang', 'x-default', 'http://www.w3.org/XML/1998/namespace');
				break;
			case 'Description':
				$o_node = $this->o_rdf_desc[0]->addChild("dc:description", '', "http://purl.org/dc/elements/1.1/");
				$o_node =$o_node->addChild("rdf:Alt", '', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node =$o_node->addChild("rdf:li", $ps_value, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node->addAttribute('xml:lang', 'x-default', 'http://www.w3.org/XML/1998/namespace');
				break;
			case 'Rights':
				$o_node = $this->o_rdf_desc[0]->addChild("dc:rights", '', "http://purl.org/dc/elements/1.1/");
				$o_node =$o_node->addChild("rdf:Alt", '', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node =$o_node->addChild("rdf:li", $ps_value, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node->addAttribute('xml:lang', 'x-default', 'http://www.w3.org/XML/1998/namespace');
				break;
			case 'Creator':
				$o_node = $this->o_rdf_desc[0]->addChild("dc:creator", '', "http://purl.org/dc/elements/1.1/");
				$o_node =$o_node->addChild("rdf:Alt", '', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node =$o_node->addChild("rdf:li", $ps_value, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node->addAttribute('xml:lang', 'x-default', 'http://www.w3.org/XML/1998/namespace');
				break;
			case 'Subjects':
				$o_node = $this->o_rdf_desc[0]->addChild("dc:subject", '', "http://purl.org/dc/elements/1.1/");
				$o_node =$o_node->addChild("rdf:Alt", '', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node =$o_node->addChild("rdf:li", $ps_value, "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
				$o_node->addAttribute('xml:lang', 'x-default', 'http://www.w3.org/XML/1998/namespace');
				break;
			case 'CreatorAddress':
			case 'CreatorCity':
			case 'CreatorStateRegion':
			case 'CreatorPostalCode':
			case 'CreatorCountry':
			case 'CreatorPhone':
			case 'CreatorEmail':
			case 'CreatorWebsite':
				if (!$this->o_creator) {
					if (!($this->o_creator = $this->opo_metadata ->xpath('//Iptc4xmpCore:CreatorContactInfo'))) {
						$this->o_creator = $this->o_rdf_desc[0]->addChild("Iptc4xmpCore:CreatorContactInfo", '', "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
					}
				}
				
				switch($ps_field) {
					case 'CreatorAddress':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiAdrExtadr', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorCity':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiAdrCity', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorStateRegion':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiAdrRegion', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorPostalCode':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiAdrPcode', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorCountry':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiAdrCtry', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorPhone':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiTelWork', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorEmail':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiEmailWork', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
					case 'CreatorWebsite':
						$this->o_creator[0]->addAttribute('Iptc4xmpCore:CiUrlWork', $ps_value, "http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/");
						break;
				}
				break;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Gets value of one of the fields listed in XMPParser::$opa_fields above. 
	 *
	 * @param string $ps_field The name of a field, as listed in XMPParser::$opa_fields
	 * @param array $pa_options An array of options. May include:	
	 *		returnAsArray = if true an array of values is returned. Default is false, in which case a string of values concatenated with a delimiter is returned.
	 *		delimiter = text to insert between returned values when those values are returned as a string. Default is ";" (a single semicolon)
	 * @return mixed Value or null if no value is set or field is invalid. Value is a string unless returnAsArray setting is passed, in which case an array is returned.
	 */
	public function get($ps_field, $pa_options=null) {
		if(!isset($this->opa_fields[$ps_field])) { return null; }
		if(isset($this->opa_metadata[$ps_field])) {
			if (isset($pa_options['returnAsArray']) && $pa_options['returnAsArray']) {
				return $this->opa_metadata[$ps_field];
			}
			$vs_delimiter = isset($pa_options['delimiter']) ? $pa_options['delimiter'] : ";";
			return join($vs_delimiter, $this->opa_metadata[$ps_field]);
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Get all extracted XMP metadata as an array. Keys of arrays are fields listed in XMPParser::$opa_fields above. 
	 *
	 * @return array Extracted metadata values
	 */
	public function getMetadata() {
		return $this->opa_metadata;
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	/**
	  * Fetches and returns an array containing all JPEG headers in the specified JPEG image file
	  *
	  * @param string $ps_filename The path to the file to be read
	  * @return array The JPEG header as an array of segments
	  */
	private function getJPEGHeaderData($ps_filename) {

        // prevent refresh from aborting file operations and hosing file
        ignore_user_abort(true);


        // Attempt to open the jpeg file - the at symbol supresses the error message about
        // not being able to open files. The file_exists would have been used, but it
        // does not work with files fetched over http or ftp.
        $filehnd = @fopen($ps_filename, 'rb');

        // Check if the file opened successfully
        if ( ! $filehnd ) {
                // Could't open the file - exit
              //  echo "<p>Could not open file $ps_filename</p>\n";
                return false;
        }


        // Read the first two characters
        $data = $this->networkSafeFread($filehnd, 2);

        // Check that the first two characters are 0xFF 0xDA  (SOI - Start of image)
        if ($data != "\xFF\xD8") {
                // No SOI (FF D8) at start of file - This probably isn't a JPEG file - close file and return;
             //   echo "<p>This probably is not a JPEG file</p>\n";
                fclose($filehnd);
                return false;
        }


        // Read the third character
        $data = $this->networkSafeFread($filehnd, 2);

        // Check that the third character is 0xFF (Start of first segment header)
        if ($data{0} != "\xFF") {
                // NO FF found - close file and return - JPEG is probably corrupted
                fclose($filehnd);
                return false;
        }

        // Flag that we havent yet hit the compressed image data
        $hit_compressed_image_data = false;


        // Cycle through the file until, one of: 1) an EOI (End of image) marker is hit,
        //                                       2) we have hit the compressed image data (no more headers are allowed after data)
        //                                       3) or end of file is hit

        while ( ($data{1} != "\xD9") && (! $hit_compressed_image_data) && ( ! feof($filehnd))) {
                // Found a segment to look at.
                // Check that the segment marker is not a Restart marker - restart markers don't have size or data after them
                if (  ( ord($data{1}) < 0xD0) || ( ord($data{1}) > 0xD7)) {
                        // Segment isn't a Restart marker
                        // Read the next two bytes (size)
                        $sizestr = $this->networkSafeFread($filehnd, 2);

                        // convert the size bytes to an integer
                        $decodedsize = unpack ("nsize", $sizestr);

                        // Save the start position of the data
                        $segdatastart = ftell($filehnd);

                        // Read the segment data with length indicated by the previously read size
                        $segdata = $this->networkSafeFread($filehnd, $decodedsize['size'] - 2);


                        // Store the segment information in the output array
                        $headerdata[] = array(  "SegType" => ord($data{1}),
                                                "SegName" => XMPParser::$s_jpeg_segment_names[ ord($data{1}) ],
                                                "SegDataStart" => $segdatastart,
                                                "SegData" => $segdata);
                }

                // If this is a SOS (Start Of Scan) segment, then there is no more header data - the compressed image data follows
                if ($data{1} == "\xDA") {
                        // Flag that we have hit the compressed image data - exit loop as no more headers available.
                        $hit_compressed_image_data = true;
                }
                else {
                        // Not an SOS - Read the next two bytes - should be the segment marker for the next segment
                        $data = $this->networkSafeFread($filehnd, 2);

                        // Check that the first byte of the two is 0xFF as it should be for a marker
                        if ($data{0} != "\xFF") {
                                // NO FF found - close file and return - JPEG is probably corrupted
                                fclose($filehnd);
                                return false;
                        }
                }
        }

        // Close File
        fclose($filehnd);
        // Alow the user to abort from now on
        ignore_user_abort(false);

        // Return the header data retrieved
        return $headerdata;
	}
	# -------------------------------------------------------
	/**
	  * Replaces the JPEG header of the specified file with the specifed one and writes it out to a file.
	  *
	  * @param string $ps_old_filename Path to the file to insert the header into
	  * @param string $ps_new_filename Path where modified file should be written. Can be the same of $ps_od_filename if you wish to modify the file in-place.
	  * @param array $pa_jpeg_header_data The JPEG header data array
	  * @return array The JPEG header with the specified segment removed
	  */
	private function putJPEGHeaderData($ps_old_filename, $ps_new_filename, $pa_jpeg_header_data) {

        // Change: added check to ensure data exists, as of revision 1.10
        // Check if the data to be written exists
        if ($pa_jpeg_header_data == false) {
                // Data to be written not valid - abort
                return false;
        }

        // extract the compressed image data from the old file
        $compressed_image_data = $this->getJPEGImageData($ps_old_filename);

        // Check if the extraction worked
        if ( ($compressed_image_data === false) || ($compressed_image_data === NULL))
        {
                // Couldn't get image data from old file
                return false;
        }


        // Cycle through new headers
        foreach ($pa_jpeg_header_data as $segno => $segment) {
                // Check that this header is smaller than the maximum size
                if ( strlen($segment['SegData']) > 0xfffd)
                {
                        // Could't open the file - exit
                        //echo "<p>A Header is too large to fit in JPEG segment</p>\n";
                        return false;
                }
        }

        ignore_user_abort(true);    ## prevent refresh from aborting file operations and hosing file


        // Attempt to open the new jpeg file
        $newfilehnd = @fopen($ps_new_filename, 'wb');
        // Check if the file opened successfully
        if ( ! $newfilehnd ) {
                // Could't open the file - exit
               // echo "<p>Could not open file $ps_new_filename</p>\n";
                return false;
        }

        // Write SOI
        fwrite($newfilehnd, "\xFF\xD8");

        // Cycle through new headers, writing them to the new file
        foreach ($pa_jpeg_header_data as $segno => $segment) {

                // Write segment marker
                fwrite($newfilehnd, sprintf( "\xFF%c", $segment['SegType']));

                // Write segment size
                fwrite($newfilehnd, pack( "n", strlen($segment['SegData']) + 2));

                // Write segment data
                fwrite($newfilehnd, $segment['SegData']);
        }

        // Write the compressed image data
        fwrite($newfilehnd, $compressed_image_data);

        // Write EOI
        fwrite($newfilehnd, "\xFF\xD9");

        // Close File
        fclose($newfilehnd);

        // Alow the user to abort from now on
        ignore_user_abort(false);

        return true;
	}
	# -------------------------------------------------------
	/**
	  * Retrieves the compressed image data part of the JPEG file
	  *
	  * @param string $ps_filename Path to file to extract image data from
	  * @return string The image data
	  */
	private function getJPEGImageData($ps_filename) {

        // prevent refresh from aborting file operations and hosing file
        ignore_user_abort(true);

        // Attempt to open the jpeg file
        $filehnd = @fopen($ps_filename, 'rb');

        // Check if the file opened successfully
        if ( ! $filehnd ) {
                // Could't open the file - exit
                return false;
        }


        // Read the first two characters
        $data = $this->networkSafeFread($filehnd, 2);

        // Check that the first two characters are 0xFF 0xDA  (SOI - Start of image)
        if ($data != "\xFF\xD8") {
                // No SOI (FF D8) at start of file - close file and return;
                fclose($filehnd);
                return false;
        }



        // Read the third character
        $data = $this->networkSafeFread($filehnd, 2);

        // Check that the third character is 0xFF (Start of first segment header)
        if ($data{0} != "\xFF") {
                // NO FF found - close file and return
                fclose($filehnd);
                return;
        }

        // Flag that we havent yet hit the compressed image data
        $hit_compressed_image_data = false;


        // Cycle through the file until, one of: 1) an EOI (End of image) marker is hit,
        //                                       2) we have hit the compressed image data (no more headers are allowed after data)
        //                                       3) or end of file is hit

        while ( ($data{1} != "\xD9") && (! $hit_compressed_image_data) && ( ! feof($filehnd))) {
                // Found a segment to look at.
                // Check that the segment marker is not a Restart marker - restart markers don't have size or data after them
                if (  ( ord($data{1}) < 0xD0) || ( ord($data{1}) > 0xD7)) {
                        // Segment isn't a Restart marker
                        // Read the next two bytes (size)
                        $sizestr = $this->networkSafeFread($filehnd, 2);

                        // convert the size bytes to an integer
                        $decodedsize = unpack ("nsize", $sizestr);

                         // Read the segment data with length indicated by the previously read size
                        $segdata = $this->networkSafeFread($filehnd, $decodedsize['size'] - 2);
                }

                // If this is a SOS (Start Of Scan) segment, then there is no more header data - the compressed image data follows
                if ($data{1} == "\xDA") {
                        // Flag that we have hit the compressed image data - exit loop after reading the data
                        $hit_compressed_image_data = true;

                        // read the rest of the file in
                        // Can't use the filesize function to work out
                        // how much to read, as it won't work for files being read by http or ftp
                        // So instead read 1Mb at a time till EOF

                        $compressed_data = "";
                        do  {
                                $compressed_data .= $this->networkSafeFread($filehnd, 1048576);
                        } while( ! feof($filehnd));

                        // Strip off EOI and anything after
                        $EOI_pos = strpos($compressed_data, "\xFF\xD9");
                        $compressed_data = substr($compressed_data, 0, $EOI_pos);
                } else  {
                        // Not an SOS - Read the next two bytes - should be the segment marker for the next segment
                        $data = $this->networkSafeFread($filehnd, 2);

                        // Check that the first byte of the two is 0xFF as it should be for a marker
                        if ($data{0} != "\xFF")
                        {
                                // Problem - NO FF foundclose file and return";
                                fclose($filehnd);
                                return;
                        }
                }
        }

        // Close File
        fclose($filehnd);

        // Alow the user to abort from now on
        ignore_user_abort(false);


        // Return the compressed data if it was found
        if ($hit_compressed_image_data) {
                return $compressed_data;
        } else {
                return false;
        }
	}
	# -------------------------------------------------------
	/**
	  * Remove specified segment from a JPEG header
	  *
	  * @param string $ps_segment_name The name of the segment to remove (Eg. "APP13")
	  * @param array $pa_jpeg_header_data The JPEG's header data
	  * @return array The JPEG header with the specified segment removed
	  */
	private function removeJPEGHeaderSegment($ps_segment_name, $pa_jpeg_header_data) {
		$va_filtered_data = array();
		 foreach ($pa_jpeg_header_data as $vn_segno => $va_segment) {
			if ($va_segment['SegName'] == $ps_segment_name) { continue; }
			$va_filtered_data[] = $va_segment;
		 }
		 
		 return $va_filtered_data;
	}
	# -------------------------------------------------------
	/**
	  * Retrieves the XMP information  from an App1 JPEG segment and returns the raw XML text as 
	  * string. This includes the Resource Description Framework (RDF)  information and may include Dublin Core 
	  * Metadata Initiative (DCMI) information. Uses information supplied by the getJPEGHeaderData function.
	  *
	  * @param array $pa_jpeg_header_data The JPEG's header data
	  * @return string Extracted XML-format XMP data
	  */
	function getXMPData($pa_jpeg_header_data) {
        //Cycle through the header segments
        for( $i = 0; $i < count( $pa_jpeg_header_data ); $i++ ) {
                // If we find an APP1 header,
                if ( strcmp ( $pa_jpeg_header_data[$i]['SegName'], "APP1" ) == 0 ) {
                        // And if it has the Adobe XMP/RDF label (http://ns.adobe.com/xap/1.0/\x00) ,
                        if( strncmp ( $pa_jpeg_header_data[$i]['SegData'], "http://ns.adobe.com/xap/1.0/\x00", 29) == 0 ) {
                                // Found a XMP/RDF block
                                // Return the XMP text
                                $vs_xmp_data = substr ( $pa_jpeg_header_data[$i]['SegData'], 29 );

                                return $vs_xmp_data;
                        }
                }
        }
        return false;
	}
	# -------------------------------------------------------
	/**
	  * Adds or modifies the Extensible Metadata Platform (XMP) information
	  * in an App1 JPEG segment. If an XMP segment already exists, it is
	  * replaced, otherwise a new one is inserted, using the supplied data.
	  * Uses information supplied by the getJPEGHeaderData function.
	  *
	  * @param array $pa_jpeg_header_data The JPEG's header data
	  * @param string $ps_new_xmp_data XML-format XMP data to insert
	  * @return array The modified JPEG header
	  */
	private function putXMPData($pa_jpeg_header_data, $ps_new_xmp_data) {
        //Cycle through the header segments
        for($i = 0; $i < count($pa_jpeg_header_data); $i++) {
                // If we find an APP1 header,
                if ( strcmp ($pa_jpeg_header_data[$i]['SegName'], "APP1") == 0) {
                        // And if it has the Adobe XMP/RDF label (http://ns.adobe.com/xap/1.0/\x00) ,
                        if( strncmp ($pa_jpeg_header_data[$i]['SegData'], "http://ns.adobe.com/xap/1.0/\x00", 29) == 0) {
                                // Found a preexisting XMP/RDF block - Replace it with the new one and return.
                                $pa_jpeg_header_data[$i]['SegData'] = "http://ns.adobe.com/xap/1.0/\x00" . $ps_new_xmp_data;
                                return $pa_jpeg_header_data;
                        }
                }
        }

        // No pre-existing XMP/RDF found - insert a new one after any pre-existing APP0 or APP1 blocks
        // Change: changed to initialise $i properly as of revision 1.04
        $i = 0;
        // Loop until a block is found that isn't an APP0 or APP1
        while ( ($pa_jpeg_header_data[$i]['SegName'] == "APP0") || ($pa_jpeg_header_data[$i]['SegName'] == "APP1")) {
                $i++;
        }



        // Insert a new XMP/RDF APP1 segment at the specified point.
        // Change: changed to properly construct array element as of revision 1.04 - requires two array statements not one, requires insertion at $i, not $i - 1
        array_splice($pa_jpeg_header_data, $i, 0, array( array(       "SegType" => 0xE1,
                                                                        "SegName" => "APP1",
                                                                        "SegData" => "http://ns.adobe.com/xap/1.0/\x00" . $ps_new_xmp_data)));

        // Return the headers with the new segment inserted
        return $pa_jpeg_header_data;
	}
	
	# -------------------------------------------------------
	/**
	  * Retrieves data from a file. This function is required since
	  * the fread function will not always return the requested number
	  * of characters when reading from a network stream or pipe
	  *
	  * @param string $ps_segment_name The name of the segment to remove (Eg. "APP13")
	  * @param array $pa_jpeg_header_data The JPEG's header data
	  * @return array The JPEG header with the specified segment removed
	  */
	private function networkSafeFread($file_handle, $length) {
        // Create blank string to receive data
        $data = "";

        // Keep reading data from the file until either EOF occurs or we have
        // retrieved the requested number of bytes

        while ( ( !feof($file_handle)) && ( strlen($data) < $length)) {
                $data .= fread($file_handle, $length-strlen($data));
        }

        // return the data read
        return $data;
	}
	# -------------------------------------------------------
}
?>