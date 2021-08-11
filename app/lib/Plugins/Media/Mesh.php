<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Media/Mesh.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2021 Whirl-i-Gig
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
 
include_once(__CA_LIB_DIR__."/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/Configuration.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");
include_once(__CA_LIB_DIR__."/Parsers/PlyToStl.php");

class WLPlugMediaMesh extends BaseMediaPlugin implements IWLPlugMedia {
	var $errors = [];
	
	var $filepath;
	var $handle;
	var $ohandle;
	var $properties;
	
	var $opo_config;
	
	var $ops_path_to_meshlab = null;
	
	var $info = [
		"IMPORT" => [
			"application/ply" 					=> "ply",
			"application/stl" 					=> "stl",
			"application/surf" 					=> "surf",
			"text/prs.wavefront-obj" 			=> "obj",
			"model/gltf+json"					=> "gltf"
		],
		
		"EXPORT" => [
			"application/ply" 						=> "ply",
			"application/stl" 						=> "stl",
			"application/surf" 						=> "surf",
			"text/prs.wavefront-obj" 				=> "obj", 
			"text/plain"							=> "txt",
			"image/jpeg"							=> "jpg",
			"image/png"								=> "png",
			"model/gltf+json"						=> "gltf"
		],
		
		"TRANSFORMATIONS" => [
			"SCALE" 			=> ["width", "height", "mode", "antialiasing"]
		],
		
		"PROPERTIES" => [
			"width" 			=> 'W',
			"height" 			=> 'W',
			"version_width" 	=> 'R', // width version icon should be output at (set by transform())
			"version_height" 	=> 'R',	// height version icon should be output at (set by transform())
			"mimetype" 			=> 'W',
			"typename"			=> 'W',
			"filesize" 			=> 'R',
			"quality"			=> 'W',
			
			'version'			=> 'W'	// required of all plug-ins
		],
		
		"NAME" => "3D",
		
		"MULTIPAGE_CONVERSION" => false, // if true, means plug-in support methods to transform and return all pages of a multipage document file (ex. a PDF)
		"NO_CONVERSION" => true
	];
	
	var $typenames = [
		"application/ply" 				=> "Polygon File Format",
		"application/stl" 				=> "Standard Tessellation Language File",
		"application/surf" 				=> "Surface Grid Format",
		"text/prs.wavefront-obj" 		=> "Wavefront OBJ",
		"model/gltf+json"				=> "GL Transmission Format"
	];
	
	var $magick_names = [
		"application/ply" 				=> "PLY",
		"application/stl" 				=> "STL",
		"application/surf" 				=> "SURF",
		"text/prs.wavefront-obj" 		=> "OBJ",
		"model/gltf+json"				=> "GLTF"
	];
	
	#
	# Alternative extensions for supported types
	#
	var $alternative_extensions = [];
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts files describing 3D models');

		$this->opo_config = Configuration::load();
	}
	# ------------------------------------------------
	# Tell WebLib what kinds of media this plug-in supports
	# for import and export
	public function register() {
		$this->opo_config = Configuration::load();
		$this->ops_path_to_meshlab = caMeshlabServerInstalled();
		
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	public function checkStatus() {
		$status = parent::checkStatus();
		
		if ($this->register()) {
			$status['available'] = true;
		}
		if (!caMeshlabServerInstalled()) {
			$status['warnings'][] = _t("MeshLab cannot be found: you will not be able to process 3D files; you can obtain MeshLab at https://www.meshlab.net/");
		} else {
			$status['notices'][] = _t("Found MeshLab");
		}

		return $status;
	}
	# ------------------------------------------------
	public function divineFileFormat($filepath) {
		if ($filepath == '') {
			return '';
		}

		$this->filepath = $filepath;

		// PLY and STL are basically a simple text files describing polygons
		// SURF is binary but with a plain text meta part at the beginning
		if ($r_fp = @fopen($filepath, "r")) {
			$sig = fgets($r_fp, 20); 
			if (preg_match('!^ply!', $sig)) {
				$this->properties = $this->handle = $this->ohandle = [
					"mimetype" => 'application/ply',
					"filesize" => filesize($filepath),
					"typename" => "Polygon File Format"
				];
				return "application/ply";
			}
			if (preg_match('!^solid!', $sig)) {
				$this->properties = $this->handle = $this->ohandle = [
					"mimetype" => 'application/stl',
					"filesize" => filesize($filepath),
					"typename" => "Standard Tessellation Language File"
				];
				return "application/stl";
			}
			if (preg_match('!\#\ HyperSurface!', $sig)) {
				$this->properties = $this->handle = $this->ohandle = [
					"mimetype" => 'application/surf',
					"filesize" => filesize($filepath),
					"typename" => "Surface Grid Format"
				];
				return "application/surf";
			}
			
			// binary STL?
			$section = file_get_contents($filepath, NULL, NULL, 0, 79);
			fseek($r_fp, 80);
			$data = fread($r_fp, 4);
			
			if (is_array($facets = @unpack("I", $data))) {
				$num_facets = array_shift($facets);
				if ((84 + ($num_facets * 50)) == ($filesize = filesize($filepath))) {
					$this->properties = $this->handle = $this->ohandle = [
						"mimetype" => 'application/stl',
						"filesize" => $filesize,
						"typename" => "Standard Tessellation Language File"
					];
					return "application/stl";
				}
			}
			
			// OBJ?
			if ($this->_parseOBJ($filepath)) {
				$this->properties = $this->handle = $this->ohandle = [
					"mimetype" => 'text/prs.wavefront-obj',
					"filesize" => filesize($filepath),
					"typename" => "Wavefront OBJ"
				];
				return "text/prs.wavefront-obj";
			}
			
			// GLTF?
			if(
				($json = json_decode(file_get_contents($filepath), true))
				&& 
				(sizeof(array_intersect(
					['asset', 'bufferViews', 'buffers', 'extensionsUsed', 'images', 
						'materials', 'meshes', 'nodes', 'samplers', 'scene', 'scenes'],
					array_keys($json)
				)) > 1)
			) {
				// TODO: analyze JSON data to confirm identify
				$this->properties = $this->handle = $this->ohandle = [
					"mimetype" => 'model/gltf+json',
					"filesize" => filesize($filepath),
					"typename" => "GLTF"
				];
				return "model/gltf+json";
			};
			
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
	 * Returns text content for indexing, or empty string if plugin doesn't support text extraction
	 *
	 * @return String Extracted text
	 */
	public function getExtractedText() {
		return '';
	}
	# ------------------------------------------------
	/**
	 * Returns array of extracted metadata, key'ed by metadata type or empty array if plugin doesn't support metadata extraction
	 *
	 * @return Array Extracted metadata
	 */
	public function getExtractedMetadata() {
		return [];
	}
	# ------------------------------------------------
	public function read ($filepath, $mimetype="", $options=null) {
		if (is_array($this->handle) && ($this->filepath == $filepath)) {
			# noop
		} else {
			if (!file_exists($filepath)) {
				$this->postError(3000, _t("File %1 does not exist", $filepath), "WLPlugMediaMesh->read()");
				$this->handle = $this->filepath = "";
				return false;
			}
			if (!($this->divineFileFormat($filepath))) {
				$this->postError(3005, _t("File %1 is not a 3D model", $filepath), "WLPlugMediaMesh->read()");
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
	public function write($filepath, $ps_mimetype) {
		if (!$this->handle) { return false; }

		$this->properties["width"] = $this->properties["version_width"];
		$this->properties["height"] = $this->properties["version_height"];
		
		# is mimetype valid?
		if (!($ext = $this->info["EXPORT"][$ps_mimetype])) {
			$this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaMesh->write()");
			return false;
		}

		switch($ps_mimetype) {
			case 'application/stl':
				# pretty restricted, but we can convert ply to stl!
				if(($this->properties['mimetype'] == 'application/ply')) {
					if(file_exists($this->filepath)){
						if ($this->ops_path_to_meshlab) {
							putenv("DISPLAY=:0");
							chdir('/usr/local/bin');
							caExec($this->ops_path_to_meshlab." -i ".caEscapeShellArg($this->filepath)." -o ".caEscapeShellArg($filepath).".stl 2>&1");
							return "{$filepath}.stl";	
						} elseif(PlyToStl::convert($this->filepath,$filepath.'.stl')){
							return "{$filepath}.stl";	
						} else {
							@unlink("{$filepath}.stl");
							$this->postError(1610, _t("Couldn't convert ply model to stl"), "WLPlugMediaMesh->write()");
							return false;
						}
					}
				}
				break;
		}
		
		# use default media icons
		return __CA_MEDIA_3D_DEFAULT_ICON__;
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	# This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
	public function &writePreviews($filepath, $options) {
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
		$this->errors = [];
		$this->handle = $this->ohandle;
		$this->properties = [
			"mimetype" => $this->ohandle["mimetype"],
			"filesize" => $this->ohandle["filesize"],
			"typename" => $this->ohandle["typename"]
		];
		
		$this->metadata = [];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function htmlTag($ps_url, $properties, $options=null, $pa_volume_info=null) {
		AssetLoadManager::register('3dmodels');
		if (!is_array($options)) { $options = []; }
		
		$width = 			caGetOption('viewer_width', $options, '100%');		
		$height = 			caGetOption('viewer_height', $options, '100%');  
		$id = 				caGetOption('id', $options, 'mesh_canvas'.md5(rand(0,99999).time()));
		$bgcolor = 			caGetOption('background_color', $options, '#00cc00'); 
		$default_color = 	caGetOption('default_color', $options, '#333333'); 
		
		if(in_array($properties['mimetype'], ["application/ply", "application/stl", "text/prs.wavefront-obj", "model/gltf+json"])){
			$texture = 				caGetOption('texture', $options, null);
			$texture_image_list = 	caGetOption('textureImages', $options, []);
			ob_start();
?>
		<div id="<?= $id; ?>"  class="online_3d_viewer"
			style="width: <?= $width; ?>; height: <?= $height; ?>;"
			model="<?= join(",", $texture_image_list).($texture ? ",{$texture}" : '').",{$ps_url}"; ?>"
			camera="3,1,2,3,0,0,0,1,0"
			backgroundcolor="<?= join(',', caHexColorToRGB($bgcolor)); ?>"
			defaultcolor="<?= join(',', caHexColorToRGB($default_color)); ?>">
		</div>
		
		<script type='text/javascript'>
			var viewerRef = null;
			jQuery(document).ready(function() {
				jQuery('canvas#os3dv_viewer').remove(); // remove existing viewers
				OV.Init3DViewerElements (function (viewers) {
					// noop
				});
			});
		</script>
<?php
			return ob_get_clean();
		} else {
			return caGetDefaultMediaIconTag(__CA_MEDIA_3D_DEFAULT_ICON__, $width, $height);
		}
	}
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function _parseOBJ($filepath) {
		if (!($r_rp = fopen($filepath, "r"))) { return false; }
		
		$c = 0;
		while((($line = trim(fgets($r_rp), "\n")) !== false) && ($c < 100)) {
			if ($line[0] === '#') { continue; }
			$toks = preg_split('![ ]+!', preg_replace("![\n\r\t]+!", "", $line));
			
			if (in_array($toks[0], ['v', 'vn']) && (sizeof($toks) >= 4) && is_numeric($toks[1]) && is_numeric($toks[2]) && is_numeric($toks[3])) {
				fclose($r_rp);
				return true;
			}
			if (($toks[0] === 'vt') && (sizeof($toks) >= 3) && is_numeric($toks[1]) && is_numeric($toks[2])) {
				fclose($r_rp);
				return true;
			}
			
			$c++;
		}
		fclose($r_rp);
		return false;
	}
	# ------------------------------------------------
	public function cleanup() {
		return;
	}
	# ------------------------------------------------
}
