<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/Mesh.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2017 Whirl-i-Gig
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
			"text/prs.wavefront-obj" 			=> "obj"
		),
		
		"EXPORT" => array(
			"application/ply" 						=> "ply",
			"application/ctm" 						=> "ctm",
			"application/stl" 						=> "stl",
			"application/surf" 						=> "surf",
			"text/prs.wavefront-obj" 				=> "obj", 
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
		"text/prs.wavefront-obj" 		=> "Wavefront OBJ",
		"application/ctm" 				=> "CTM"
	);
	
	var $magick_names = array(
		"application/ply" 				=> "PLY",
		"application/stl" 				=> "STL",
		"application/surf" 				=> "SURF",
		"text/prs.wavefront-obj" 		=> "OBJ",
		"application/ctm" 				=> "CTM"
	);
	
	# ------------------------------------------------
	public function __construct() {
		$this->description = _t('Accepts files describing 3D models');

		$this->opo_config = Configuration::load();
		$this->opo_external_app_config = Configuration::load(__CA_CONF_DIR__."/external_applications.conf");
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
			
			// binary STL?
			$vs_section = file_get_contents($ps_filepath, NULL, NULL, 0, 79);
			fseek($r_fp, 80);
			$vs_data = fread($r_fp, 4);
			
			if (is_array($va_facets = @unpack("I", $vs_data))) {
				$vn_num_facets = array_shift($va_facets);
				if ((84 + ($vn_num_facets * 50)) == ($vn_filesize = filesize($ps_filepath))) {
					$this->properties = $this->handle = $this->ohandle = array(
						"mimetype" => 'application/stl',
						"filesize" => $vn_filesize,
						"typename" => "Standard Tessellation Language File"
					);
					return "application/stl";
				}
			}
			
			// OBJ?
			if ($this->_parseOBJ($ps_filepath)) {
				$this->properties = $this->handle = $this->ohandle = array(
					"mimetype" => 'text/prs.wavefront-obj',
					"filesize" => filesize($ps_filepath),
					"typename" => "Wavefront OBJ"
				);
				return "text/prs.wavefront-obj";
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

		switch($ps_mimetype) {
			case 'application/ctm':
				if(file_exists($this->filepath) && caOpenCTMInstalled()){
					exec(caGetExternalApplicationPath('openctm').' '.caEscapeShellArg($this->filepath)." ".caEscapeShellArg($ps_filepath).".ctm --method MG2 --level 9 2>&1", $va_output);
					return "{$ps_filepath}.ctm";	
				} else {
					@unlink("{$ps_filepath}.ctm");
					//$this->postError(1610, _t("Couldn't convert %1 model to ctm", $this->properties['mimetype']), "WLPlugMediaMesh->write()");
					//return false;
				}
				break;
			default:
				# pretty restricted, but we can convert ply to stl!
				if(($this->properties['mimetype'] == 'application/ply') && ($ps_mimetype == 'application/stl')){
					if(file_exists($this->filepath)){
						if (caMeshlabServerInstalled()) {
							putenv("DISPLAY=:0");
							chdir('/usr/local/bin');
							exec(caGetExternalApplicationPath('meshlabserver')." -i ".caEscapeShellArg($this->filepath)." -o ".caEscapeShellArg($ps_filepath).".stl 2>&1", $va_output);
							return "{$ps_filepath}.stl";	
						} elseif(PlyToStl::convert($this->filepath,$ps_filepath.'.stl')){
							return "{$ps_filepath}.stl";	
						} else {
							@unlink("{$ps_filepath}.stl");
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
		AssetLoadManager::register('3dmodels');

		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$vn_width = (isset($pa_options["viewer_width"]) && ($pa_options["viewer_width"] > 0)) ? $pa_options["viewer_width"] : 820;
		$vn_height = (isset($pa_options["viewer_height"]) && ($pa_options["viewer_height"] > 0)) ? $pa_options["viewer_height"] : 520;

		$vs_id = (isset($pa_options["id"]) && $pa_options["id"]) ? $pa_options["id"] : "mesh_canvas";
		
		$vs_bgcolor = (isset($pa_options["background_color"]) && $pa_options["background_color"]) ? preg_replace("![^A-Fa-f0-9]+!", "", $pa_options["background_color"]) : "CCCCCC";
		
		$vs_progress_id = (isset($pa_options["progress_id"]) && $pa_options["progress_id"]) ? $pa_options["progress_id"] : "caMediaOverlayProgress";
		
		$vn_progress_total_filesize = (isset($pa_options["progress_total_filesize"]) && ($pa_options["progress_total_filesize"] > 0)) ? $pa_options["progress_total_filesize"] : 0;
		

		if(in_array($pa_properties['mimetype'], array("application/ply", "application/stl", "application/ctm", "text/prs.wavefront-obj"))){
			ob_start();
?>
		<div id="viewer"></div>
<script type="text/javascript">
			var container, stats;
			var camera, cameraTarget, scene, renderer;
			var total_filesize = <?php print $vn_progress_total_filesize; ?>;
			
			init();
			animate();
			
			function init() {
				container = document.getElementById('viewer');

				camera = new THREE.PerspectiveCamera( 35, window.innerWidth / window.innerHeight, 1, 150 );
				camera.position.set( 3, 0.15, 3 );

				cameraTarget = new THREE.Vector3( 0, -0.25, 0 );

				scene = new THREE.Scene();
				scene.add(camera);
				
				// ASCII file

<?php
	switch($pa_properties['mimetype']) {
		case 'application/stl':
?>
				var loader = new THREE.STLLoader();
<?php
				break;
		case 'application/ply':
?>
				var loader = new THREE.PLYLoader();
<?php
				break;
		case 'application/ctm':
?>
				var loader = new THREE.CTMLoader();
<?php
				break;
		case 'text/prs.wavefront-obj':
?>
				var loader = new THREE.OBJLoader();
<?php
				break;
	}
?>
				function postLoad ( event ) {
					var geometry = event;
					if(!geometry.center) { geometry = event.content; }
					
					geometry.center();
					var material = new THREE.MeshPhongMaterial( { ambient: 0xFFFFCC, color: 0xFFFFCC, specular: 0x111111, shininess: 200, side: THREE.DoubleSide } );
					var mesh = new THREE.Mesh( geometry, material );
					
					if ((mesh.geometry.type == 'Geometry') && (!mesh.geometry.faces || (mesh.geometry.faces.length == 0))) {
						material = new THREE.PointCloudMaterial({ vertexColors: true, size: 0.01 });
						mesh = new THREE.PointCloud( geometry, material );
					}
					
					var boundingBox = mesh.geometry.boundingBox.clone();
					
					var s = 3/Math.abs(boundingBox.max.x);
					mesh.position.set( 0, 0, 0 );
					mesh.scale.set( 0.25* s, 0.25 * s, 0.25 *s);
					
					mesh.castShadow = false;
					mesh.receiveShadow = false;

					scene.add( mesh );
				
					var light = new THREE.HemisphereLight( 0xffffbb, 0x080820, 0.7 );
					scene.add( light );
				
					jQuery('#<?php print $vs_progress_id; ?> div').html("Loaded model");
					setTimeout(function() {
						jQuery('#<?php print $vs_progress_id; ?>').fadeOut(500);
					}, 3000);

				}
				
				function loadProgressMonitor( event ) {
						jQuery('#<?php print $vs_progress_id; ?>').show();
						var msg = "Loaded " + caUI.utils.formatFilesize(event.loaded/5.2, true);
						if(total_filesize > 0) {
							msg += " (" + Math.ceil((event.loaded/total_filesize) * 100) + "%)";
						}
						jQuery('#<?php print $vs_progress_id; ?> div').html(msg);
				}
				
				if (loader.addEventListener) {
					loader.addEventListener( 'load', postLoad);
				
				
					loader.addEventListener( 'progress', loadProgressMonitor);
				}
				loader.load( '<?php print $ps_url; ?>' , postLoad, loadProgressMonitor);

				// Lights
				scene.add( new THREE.AmbientLight( 0x777777 ) );
				
				// renderer
				if (Detector.webgl) {
					renderer = new THREE.WebGLRenderer( { antialias: true, alpha: false } );
				} else {
					renderer = new THREE.CanvasRenderer( { antialias: false, alpha: false } );
				}
				renderer.setSize( window.innerWidth, window.innerHeight );

				renderer.gammaInput = true;
				renderer.gammaOutput = true;
				renderer.physicallyBasedShading = true;

				renderer.shadowMapEnabled = true;
				renderer.shadowMapCullFace = THREE.CullFaceBack;

				controls = new THREE.TrackballControls( camera, renderer.domElement );
				controls.rotateSpeed = 0.5;
				controls.zoomSpeed = 0.5;
				controls.panSpeed = 0.2;
 
				controls.noZoom = false;
				controls.noPan = false;
 
				controls.staticMoving = false;
				controls.dynamicDampingFactor = 0.3;
 
				controls.minDistance = 1.5;
				controls.maxDistance = 100;
				
				renderer.setClearColor( 0x<?php print $vs_bgcolor; ?>, 1 );
 
				controls.keys = [ 16, 17, 18 ]; // [ rotateKey, zoomKey, panKey ]

				container.appendChild( renderer.domElement );

				window.addEventListener( 'resize', onWindowResize, false );

			}

			function addShadowedLight( x, y, z, color, intensity ) {

				var directionalLight = new THREE.DirectionalLight( color, intensity );
				directionalLight.position.set( x, y, z )
				scene.add( directionalLight );

				directionalLight.castShadow = true;
				directionalLight.shadowCameraVisible = false;

				var d = 1;
				directionalLight.shadowCameraLeft = -d;
				directionalLight.shadowCameraRight = d;
				directionalLight.shadowCameraTop = d;
				directionalLight.shadowCameraBottom = -d;

				directionalLight.shadowCameraNear = 1;
				directionalLight.shadowCameraFar = 4;

				directionalLight.shadowMapWidth = 1024;
				directionalLight.shadowMapHeight = 1024;

				directionalLight.shadowBias = -0.005;
				directionalLight.shadowDarkness = 0.15;
				return directionalLight;
			}

			function onWindowResize() {
				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

			}

			function animate() {
				requestAnimationFrame( animate );
				render();
			}

			function render() {
				controls.update(); 
				camera.lookAt( cameraTarget );
				renderer.render( scene, camera );
			}
</script>
<?php
			return ob_get_clean();
		} else {
			return caGetDefaultMediaIconTag(__CA_MEDIA_3D_DEFAULT_ICON__,$vn_width,$vn_height);
		}
	}
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function _parseOBJ($ps_filepath) {
		if (!($r_rp = fopen($ps_filepath, "r"))) { return false; }
		
		$vn_c = 0;
		while((($vs_line = trim(fgets($r_rp), "\n")) !== false) && ($vn_c > 100)) {
			if ($vs_line[0] === '#') { continue; }
			
			$va_toks = preg_split('![ ]+!', $vs_line);
			if (in_array($va_toks[0], ['v', 'vn']) && (sizeof($va_toks) >= 4) && is_numeric($va_toks[1]) && is_numeric($va_toks[2]) && is_numeric($va_toks[3])) {
				fclose($r_rp);
				return true;
			}
			if (($va_toks[0] === 'vt') && (sizeof($va_toks) >= 3) && is_numeric($va_toks[1]) && is_numeric($va_toks[2])) {
				fclose($r_rp);
				return true;
			}
			
			$vn_c++;
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