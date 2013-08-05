<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Mesh.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * Plugin for processing 3D object files
 */
 
include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/core/Parsers/PlyToStl.php");

class WLPlugMediaMesh extends BaseMediaPlugin implements IWLPlugMedia {
	var $errors = array();
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	
	var $info = array(
		"IMPORT" => array(
			"application/ply" 					=> "ply",
			"application/stl" 					=> "stl",
			"application/surf" 					=> "surf",
		),
		
		"EXPORT" => array(
			"application/ply" 						=> "ply",
			"application/stl" 						=> "stl",
			"application/surf" 						=> "surf",
			"text/plain"							=> "txt",
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png"
		),
		
		"TRANSFORMATIONS" => array(
			"SCALE" 			=> array("width", "height", "mode", "antialiasing")
		),
		
		"PROPERTIES" => array(
			"width" 			=> 'W',
			"height" 			=> 'W',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"typename"			=> 'W',
			"filesize" 			=> 'R',
			"quality"			=> 'W',
			
			'version'			=> 'W'	// required of all plug-ins
		),
		
		"NAME" => "3D",
		
		"MULTIPAGE_CONVERSION" => false, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => true
	);
	
	var $typenames = array(
		"application/ply" 				=> "Polygon File Format",
		"application/stl" 				=> "Standard Tessellation Language File",
		"application/surf" 				=> "Surface Grid Format",
	);
	
	var $magick_names = array(
		"application/ply" 				=> "PLY",
		"application/stl" 				=> "STL",
		"application/surf" 				=> "SURF",
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts files describing 3D models');

		$this->opo_config = Configuration::load();
		$vs_external_app_config_path = $this->opo_config->get('external_applications');
		$this->opo_external_app_config = Configuration::load($vs_external_app_config_path);
		$this->ops_python_path = $this->opo_external_app_config->get('python_app');
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

		$this->filepath = $ps_filepath;

		// PLY and STL are basically a simple text files describing polygons
		// SURF is binary but with a plain text meta part at the beginning
		if ($r_fp = @fopen($ps_filepath, "r")) {
			$vs_sig = fgets($r_fp, 20); 
			if (preg_match('!^ply!', $vs_sig)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'application/ply',
					"filesize" => filesize($ps_filepath),
					"typename" => "Polygon File Format"
				);
				return "application/ply";
			}
			if (preg_match('!^solid!', $vs_sig)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'application/stl',
					"filesize" => filesize($ps_filepath),
					"typename" => "Standard Tessellation Language File"
				);
				return "application/stl";
			}
			if (preg_match('!\#\ HyperSurface!', $vs_sig)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'application/surf',
					"filesize" => filesize($ps_filepath),
					"typename" => "Surface Grid Format"
				);
				return "application/surf";
			}
		}

		$this->filepath = null;
		return '';
	}
	# ----------------------------------------------------------
	public function get($property) {
		if ($this->handle) {
			if ($this->info["PROPERTIES"][$property]) {
				return $this->properties[$property];
			} else {
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
				$this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaMesh->set()");
				return '';
			}
		} else {
			return '';
		}
		return true;
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
				$this->postError(3000, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaMesh->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
			if (!($this->divineFileFormat($ps_filepath))) {
				$this->postError(3005, _t("File %1 is not a 3D model", $ps_filepath), "WLPlugMediaMesh->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
		}
			
		return true;	
	}
	# ----------------------------------------------------------
	public function transform($operation, $parameters) {
		if (!$this->handle) { return false; }
		
		if (!($this->info["TRANSFORMATIONS"][$operation])) {
			# invalid transformation
			$this->postError(1655, _t("Invalid transformation %1", $operation), "WLPlugMediaMesh->transform()");
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

		$this->properties["width"] = $this->properties["version_width"];
		$this->properties["height"] = $this->properties["version_height"];
		
		# is mimetype valid?
		if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaMesh->write()");
			return false;
		}

		# pretty restricted, but we can convert ply to stl!
		if(($this->properties['mimetype'] == 'application/ply') && ($ps_mimetype == 'application/stl')){
			if(file_exists($this->filepath)){
				if(PlyToStl::convert($this->filepath,$ps_filepath.'.stl')){
					return $ps_filepath.'.stl';	
				} else {
					@unlink($ps_filepath.'.stl');
					$this->postError(1610, _t("Couldn't convert ply model to stl"), "WLPlugMediaMesh->write()");
					return false;
				}
			}
		}
		
		# use default media icons
		return __CA_MEDIA_3D_DEFAULT_ICON__;
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
		JavascriptLoadManager::register('3dmodels');

		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vn_width = $pa_options["viewer_width"] ? $pa_options["viewer_width"] : 820;
		$vn_height = $pa_options["viewer_height"] ? $pa_options["viewer_height"] : 520;

		$vs_id = $pa_options["id"] ? $pa_options["id"] : "mesh_canvas";

		if(in_array($pa_properties['mimetype'], array("application/stl"))){
			ob_start();
?>
<canvas id="<?php print $vs_id; ?>" style="border: 1px solid;" width="<?php print $vn_width; ?>" height="<?php print $vn_height; ?>" ></canvas>
<script type="text/javascript">
	var canvas = document.getElementById('<?php print $vs_id; ?>');
	var viewer = new JSC3D.Viewer(canvas);
	viewer.setParameter('SceneUrl', '<?php print $ps_url; ?>');
	viewer.setParameter('RenderMode', 'flat');
	viewer.init();
	viewer.update();
</script>
<?php
			return ob_get_clean();
		} else {
			return caGetDefaultMediaIconTag(__CA_MEDIA_3D_DEFAULT_ICON__,$vn_width,$vn_height);
		}
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
?>