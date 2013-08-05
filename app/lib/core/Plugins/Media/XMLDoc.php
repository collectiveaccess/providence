<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/XMLDoc.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/**
 * Plugin for processing XML documents
 */
 
include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaXMLDoc Extends BaseMediaPlugin Implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	
	var $info = array(
		"IMPORT" => array(
			"text/xml" 					=> "xml"
		),
		
		"EXPORT" => array(
			"text/xml" 								=> "xml",
			"application/pdf"						=> "pdf",
			"text/html"								=> "html",
			"text/plain"							=> "txt",
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png"
		),
		
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing")
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'R',
			"height" 			=> 'R',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"typename"			=> 'W',
			"filesize" 			=> 'R',
			"quality"			=> 'W',
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "XMLDoc",
		
		"MULTIPAGE_CONVERSION" => false, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => false
	);
	
	var $typenames = array(
		"application/pdf" 				=> "PDF",
		"text/xml" 						=> "XML document",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "Plain text",
		"image/jpeg"					=> "JPEG",
		"image/png"						=> "PNG"
	);
	
	var $magick_names = array(
		"application/pdf" 				=> "PDF",
		"text/xml" 						=> "XML",
		"text/html" 					=> "HTML",
		"text/plain" 					=> "TXT"
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts and processes XML-format documents');
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		if ($this->register()) {
			$va_status['available'] = true;
		}
		
		return $va_status;
	}
	# ------------------------------------------------
	public function divineFileFormat($ps_filepath) {
		if ($ps_filepath == '') {
			return '';
		}
		
		if ($r_fp = @fopen($ps_filepath, "r")) {
			$vs_sig = fgets($r_fp, 10); 
			if (preg_match('!<\?xml!', $vs_sig)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'text/xml',
					"filesize" => filesize($ps_filepath),
					"typename" => "XML document"
				);
				return "text/xml";
			}
		}
		return '';
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
				//print "Invalid property";
				return '';
			}
		} else {
			return '';
		}
	}
	# ----------------------------------------------------------
	public function set($property, $value) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				switch($property) {
					default:
						if ($this->info["PROPERTIES"][$property] == 'W') {
							$this->properties[$property] = $value;
						} else {
							# read only
							return '';
						}
						break;
				}
			} else {
				# invalid property
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaXMLDoc->set()");
				return '';
			}
		} else {
			return '';
		}
		return true;
	}
	# ------------------------------------------------
	/**
	 * Returns text content for indexing, or empty string if plugin doesn't support text extraction
	 *
	 * @return String Extracted text
	 */
	public function getExtractedText() {
		$this->handle['content'] = '';
		$o_xml_config = Configuration::load($this->opo_config->get('xml_config'));
		
		if ((bool)$o_xml_config->get('xml_index_content_for_search')) {
			if ($r_fp = fopen($this->filepath, 'r')) {
				while(($vs_line = fgetss($r_fp, 4096)) !== false) {
					$this->handle['content'] .= $vs_line;
				}
				fclose($r_fp);
			}
		}
		return isset($this->handle['content']) ? $this->handle['content'] : '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return array();
	}
	# ------------------------------------------------
	public function read ($ps_filepath) {
		if (is_array($this->handle) && ($this->filepath == $ps_filepath)) {
			# noop
		} else {
			if (!file_exists($ps_filepath)) {
				$this->postError(3000, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaXMLDoc->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
			if (!($this->divineFileFormat($ps_filepath))) {
				$this->postError(3005, _t("File %1 is not an XML document", $ps_filepath), "WLPlugMediaXMLDoc->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
		}
		$o_xml_config = Configuration::load($this->opo_config->get('xml_config'));
		$vs_xml_resource_path = $o_xml_config->get('xml_resource_directory');
		
		$this->filepath = $ps_filepath;
		
		libxml_use_internal_errors(true);
		if ($o_xml = DOMDocument::load($ps_filepath, LIBXML_DTDVALID)) {
			// get schema
			$o_root = $o_xml->childNodes->item(0);
			
			
			if ($vb_require_schema = (bool)$o_xml_config->get('xml_do_validation')) {
				if (!($vs_schema_path = $o_root->getAttribute('xsi:noNamespaceSchemaLocation'))) {
					$vs_schema_path = $o_root->getAttribute('xsi:schemaLocation');
				}
				$vs_schema_path = preg_replace('![\.]+!', '.', $vs_schema_path);
				if (!$vs_schema_path) {
					$this->postError(3010, _t("No XML schema specified in XML file"), "WLPlugMediaXMLDoc->read()");
					$this->handle = $this->filepath = "";
					return false;
				}
				
				if (!file_exists($vs_xml_resource_path.'/schemas/'.$vs_schema_path)) {
					$this->postError(3015, _t("Specified XML schema is not installed"), "WLPlugMediaXMLDoc->read()");
					$this->handle = $this->filepath = "";
					return false;
				}
			
				// validate schema
				if (!$o_xml->schemaValidate($vs_xml_resource_path.'/schemas/'.$vs_schema_path)) {
					$va_xml_errors = libxml_get_errors(); 
					$va_xml_error_messages = array();
					foreach ($va_xml_errors as $va_xml_error) { 
						$va_xml_error_messages[] = _t('At line %1', $va_xml_error->line).': '.$va_xml_error->message;
					} 
					libxml_clear_errors();
					$this->postError(3020, _t("Validation against XML schema failed with errors:<br/>".join('; ', $va_xml_error_messages)), "WLPlugMediaXMLDoc->read()");
					$this->handle = $this->filepath = "";
					return false;
				} 
			}
			
			return true;
		} else {
			$this->postError(1651, _t("Could not open %1", $ps_filepath), "WLPlugMediaXMLDoc->read()");
			$this->handle = $this->filepath = "";
			return false;
		}
			
		return true;	
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugMediaXMLDoc->transform()");
			return false;
		}
		
		# get parameters for this operation
		$sparams = $this->info["TRANSFORMATIONS"][$operation];
		
		switch($operation) {
			# -----------------------
			case "SET":
				while(list($k, $v) = each($parameters)) {
					$this->set($k, $v);
				}
				break;
			# -----------------------
			case 'SCALE':
				$this->properties["version_width"] = $parameters["width"];
				$this->properties["version_height"] = $parameters["height"];
				# noop
				break;
			# -----------------------
		}
		return true;
	}
	# ----------------------------------------------------------
	public function write($ps_filepath, $ps_mimetype) {
		if (!$this->handle) { return false; }
		
		# is mimetype valid?
		if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaXMLDoc->write()");
			return false;
		} 
		
		# write the file
		if ($ps_mimetype == "text/xml") {
			if ( !copy($this->filepath, $ps_filepath.".xml") ) {
				$this->postError(1610, _t("Couldn't write file to %1", $ps_filepath), "WLPlugMediaXMLDoc->write()");
				return false;
			}
		} else {
			# use default media icons
			return __CA_MEDIA_DOCUMENT_DEFAULT_ICON__;
		}
		
		
		$this->properties["mimetype"] = $ps_mimetype;
		$this->properties["filesize"] = filesize($ps_filepath.".".$vs_ext);
		
		if (!($this->properties["width"] = $this->get("version_width"))) {
			$this->properties["width"] = $this->get("version_height");
		}
		if (!($this->properties["height"] = $this->get("version_height"))) {
			$this->properties["height"] = $this->get("version_width");
		}
		//$this->properties["typename"] = $this->typenames[$ps_mimetype];
		
		return $ps_filepath.".".$vs_ext;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($ps_filepath, $pa_options) {
		return null;
	}
	# ------------------------------------------------
	public function getOutputFormats() {
		return $this->info["EXPORT"];
	}
	# ------------------------------------------------
	public function getTransformations() {
		return $this->info["TRANSFORMATIONS"];
	}
	# ------------------------------------------------
	public function getProperties() {
		return $this->info["PROPERTIES"];
	}
	# ------------------------------------------------
	public function mimetype2extension($mimetype) {
		return $this->info["EXPORT"][$mimetype];
	}
	# ------------------------------------------------
	public function mimetype2typename($mimetype) {
		return $this->typenames[$mimetype];
	}
	# ------------------------------------------------
	public function extension2mimetype($extension) {
		reset($this->info["EXPORT"]);
		while(list($k, $v) = each($this->info["EXPORT"])) {
			if ($v === $extension) {
				return $k;
			}
		}
		return '';
	}
	# ------------------------------------------------
	public function reset() {
		return $this->init();
	}
	# ------------------------------------------------
	public function init() {
		$this->errors = array();
		$this->handle = $this->ohandle;
		$this->properties = array(
			"mimetype" => $this->ohandle["mimetype"],
			"filesize" => $this->ohandle["filesize"],
			"typename" => $this->ohandle["typename"]
		);
		
		$this->metadata = array();
	}
	# ------------------------------------------------
	public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		foreach(array(
			'name', 'url', 'viewer_width', 'viewer_height', 'idname',
			'viewer_base_url', 'width', 'height',
			'vspace', 'hspace', 'alt', 'title', 'usemap', 'align', 'border', 'class', 'style'
		) as $vs_k) {
			if (!isset($pa_options[$vs_k])) { $pa_options[$vs_k] = null; }
		}
		
		if(preg_match("/\.xml\$/", $ps_url)) {
			if (!$this->opo_config) { $this->opo_config = Configuration::load(); }
			$o_xml_config = Configuration::load($this->opo_config->get('xml_config'));
			$vs_xml_resource_path = $o_xml_config->get('xml_resource_directory');
			
			if (file_exists($vs_xml_resource_path.'/xsl/styl.xslt')) {
				$o_xsl = new DOMDocument();
				$o_xsl->load($vs_xml_resource_path.'/xsl/styl.xslt');
				
				$o_xml = new DOMDocument();
				$o_xml->load($ps_url);
				
				$o_xsl_proc = new XSLTProcessor();
				$o_xsl_proc->importStylesheet($o_xsl);
				return $o_xsl_proc->transformToXML($o_xml);
			} else {
				return "<a href='{$ps_url}'>"._t('View XML file')."</a>";
			}

		} else {
			if(preg_match("/\.pdf\$/", $ps_url)) {
				if ($pa_options['embed']) {
					$vn_viewer_width = intval($pa_options['viewer_width']);
					if ($vn_viewer_width < 100) { $vn_viewer_width = 400; }
					$vn_viewer_height = intval($pa_options['viewer_height']);
					if ($vn_viewer_height < 100) { $vn_viewer_height = 400; }
					return "<object data='{$ps_url}' type='application/pdf' width='{$vn_viewer_width}' height='{$vn_viewer_height}'><p><a href='$ps_url' target='_pdf'>"._t("View PDF file")."</a></p></object>";
				} else {
					return "<a href='$ps_url' target='_pdf'>"._t("View PDF file")."</a>";
				}
			} else {
				if (!is_array($pa_options)) { $pa_options = array(); }
				if (!is_array($pa_properties)) { $pa_properties = array(); }
				return caHTMLImage($ps_url, array_merge($pa_options, $pa_properties));
			}
		}
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>