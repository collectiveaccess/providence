<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Media/MeshTextured.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * Created by ideesculture
 * Plugin for processing 3D OBJ Files zipped inside a .OBJ.ZIP container
 */

include_once(__CA_LIB_DIR__."/core/Plugins/Media/BaseMediaPlugin.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMedia.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_APP_DIR__."/helpers/mediaPluginHelpers.php");

class WLPlugMediaMeshTextured Extends BaseMediaPlugin Implements IWLPlugMedia {
    var $errors = array();

    var $filepath;
    var $handle;
    var $ohandle;
    var $properties;

    var $opo_config;
    var $opo_external_app_config;
    var $opo_search_config;
    var $ops_ghostscript_path;

    var $ops_imagemagick_path;
    var $ops_graphicsmagick_path;

    var $info = array(
        "IMPORT" => array(
            "application/objzip"                    => "obj.zip"
        ),

        "EXPORT" => array(
            "application/objzip"                => "obj.zip",
            "image/jpeg"						=> "jpg",
            "image/png"							=> "png",
            "application/3dutf8"				=> "3dutf8",
        ),

        "TRANSFORMATIONS" => array(
            "SCALE" 			=> array("width", "height", "mode", "antialiasing"),
            "SET" 				=> array("property", "value")
        ),

        "PROPERTIES" => array(
            "width" 			=> 'W', # in pixels
            "height" 			=> 'W', # in pixels
            "version_width" 	=> 'R', // width version icon should be output at (set by transform())
            "version_height" 	=> 'R',	// height version icon should be output at (set by transform())
            "mimetype" 			=> 'W',
            "quality"			=> 'W',
            "pages"				=> 'R',
            "page"				=> 'W', # page to output as JPEG or TIFF
            "resolution"		=> 'W', # resolution of graphic in pixels per inch
            "filesize" 			=> 'R',
            "antialiasing"		=> 'W', # amount of antialiasing to apply to final output; 0 means none, 1 means lots; a good value is 0.5
            "crop"				=> 'W', # if set to geometry value (eg. 72x72) image will be cropped to those dimensions; set by transform() to support fill_box SCALE mode
            "scaling_correction"=> 'W',	# percent scaling required to correct sizing of image output by Ghostscript (Ghostscript does not do fractional resolutions)
            "target_width"		=> 'W',
            "target_height"		=> 'W',

            "colors"			=> 'W', # number of colors in output PNG-format image; default is 256

            'version'			=> 'W'	// required of all plug-ins
        ),

        "NAME" => "OBJ.ZIP",

        "MULTIPAGE_CONVERSION"  => false,
        "NO_CONVERSION"         => false
    );

    var $typenames = array(
        "application/objzip"                => "3D OBJ container",
        "image/jpeg"						=> "JPEG",
        "image/png"							=> "PNG",
        "application/3dutf8"                => "3DUTF8"
    );

    var $magick_names = array(
        "image/jpeg" 		    => "JPEG",
        "image/png" 		    => "PNG",
        "application/objzip"    => "OBJ.ZIP",
        "application/3dutf8"    => "3DUTF8"
    );

    # ------------------------------------------------
    public function __construct() {
        $this->description = _t('Accepts OBJ files with materials & textures (zipped with OBJ.ZIP extension)');
    }
    # ------------------------------------------------
    # Tell WebLib what kinds of media this plug-in supports
    # for import and export
    public function register() {
        $this->opo_config = Configuration::load();

        $this->opo_search_config = Configuration::load($this->opo_config->get('search_config'));
        $this->opo_external_app_config = Configuration::load($this->opo_config->get('external_applications'));

        $this->ops_imagemagick_path = $this->opo_external_app_config->get('imagemagick_path');
        $this->ops_graphicsmagick_path = $this->opo_external_app_config->get('graphicsmagick_app');
        $this->ops_blender_path = $this->opo_external_app_config->get('blender_app');
        $this->ops_obj2utf8x_path = $this->opo_external_app_config->get('obj2utf8x_app');

        $this->info["INSTANCE"] = $this;
        return $this->info;
    }
    # ------------------------------------------------
    public function checkStatus() {
        $va_status = parent::checkStatus();

        if ($this->register()) {
            $va_status['available'] = true;

            if (!caBlenderInstalled($this->ops_blender_path)) {
                $va_status['warnings'][] = _t("Blender is not available. Image previews can't be rendered.");
            } 
            if(!caObj2utf8xInstalled($this->ops_obj2utf8x_path)) {
                $va_status['errors'][] = _t("obj2utf8x is not available. You won't be able to display OBJ files directly in CollectiveAccess. ");
            }

        }

        return $va_status;
    }
    # ------------------------------------------------
    public function divineFileFormat($ps_filepath) {
        if ($ps_filepath == '') {
            return '';
        }

        $this->filepath = $ps_filepath;

        if ($r_fp = @fopen($ps_filepath, "r")) {

            $zip = new ZipArchive() ;
            if ($zip->open($this->filepath) !== true) {
                $this->filepath = null;
                return '';
            } else {
                 for($i = 0; $i < $zip->numFiles; $i++) 
                 {   
                    // Verifying inside the archive if we have a file with an OBJ extension and not starting with a dot (hidden files on mac)
                    $filename = $zip->getNameIndex($i);
                    $info = pathinfo($filename);
                    if (($info["extension"] == "obj") && (substr($info["filename"],0,1) != ".")) {
                        $this->properties = $this->handle = $this->ohandle = array(
                            "mimetype" => 'application/objzip',
                            "filesize" => filesize($ps_filepath),
                            "typename" => "3D OBJ container"
                        );                        
                        return "application/objzip";
                    } 
                 } 
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
                    case 'quality':
                        if (($value < 1) || ($value > 100)) {
                            $this->postError(1650, _t("Quality property must be between 1 and 100"), "WLPlugMediaMeshTextured->set()");
                            return '';
                        }
                        $this->properties["quality"] = $value;
                        break;
                    case 'antialiasing':
                        if (($value < 0) || ($value > 100)) {
                            $this->postError(1650, _t("Antialiasing property must be between 0 and 100"), "WLPlugMediaMeshTextured->set()");
                            return '';
                        }
                        $this->properties["antialiasing"] = $value;
                        break;
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
                $this->postError(1650, _t("Can't set property %1", $property), "WLPlugMediaMeshTextured->set()");
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
        return $this->metadata;
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
        if (!(($this->handle) && ($$ps_filepath === $this->filepath))) {
            if (!file_exists($ps_filepath)) {
                $this->postError(1650, _t("File %1 does not exist", $ps_filepath), "WLPlugMediaMeshTextured->read()");
                $this->handle = "";
                $this->filepath = "";
                return false;
            }
            if (!($this->divineFileFormat($ps_filepath))) {
                $this->postError(1650, _t("File %1 is not an .OBJ.ZIP", $ps_filepath), "WLPlugMediaMeshTextured->read()");
                $this->handle = "";
                $this->filepath = "";
                return false;
            }
            $this->filepath = $ps_filepath;

            // Hardcode width/height for preview generation
            $this->set('width', 960);
            $this->set('height', 540);
            $this->set('resolution', 72);

            $this->ohandle = $this->handle = $this->properties;

            return true;
        } else {
            // image already loaded by previous call (probably divineFileFormat())
            return 1;
        }
    }
    # ----------------------------------------------------------
    public function transform($ps_operation, $pa_parameters) {
        if (!$this->handle) { return false; }

        if (!($this->info["TRANSFORMATIONS"][$ps_operation])) {
            # invalid transformation
            $this->postError(1655, _t("Invalid transformation %1", $ps_operation), "WLPlugMediaMeshTextured->transform()");
            return false;
        }

        # get parameters for this operation
        $sparams = $this->info["TRANSFORMATIONS"][$ps_operation];

        $this->properties["version_width"] = $w = $pa_parameters["width"];
        $this->properties["version_height"] = $h = $pa_parameters["height"];
        $cw = $this->get("width");
        $ch = $this->get("height");
        switch($ps_operation) {
            # -----------------------
            case "SET":
                while(list($k, $v) = each($pa_parameters)) {
                    $this->set($k, $v);
                }
                break;
            # -----------------------
            case "SCALE":
                $vn_width_ratio = $w/$cw;
                $vn_height_ratio = $h/$ch;
                $vn_orig_resolution = $this->get("resolution");
                switch($pa_parameters["mode"]) {
                    # ----------------
                    case "width":
                        $vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
                        $vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
                        break;
                    # ----------------
                    case "height":
                        $vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
                        $vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
                        break;
                    # ----------------
                    case "fill_box":
                        if ($vn_width_ratio < $vn_height_ratio) {
                            $vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
                            $vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
                        } else {
                            $vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
                            $vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
                        }
                        $this->set("crop",$w."x".$h);
                        break;
                    # ----------------
                    case "bounding_box":
                    default:
                        if ($vn_width_ratio > $vn_height_ratio) {
                            $vn_resolution = ceil($vn_orig_resolution * $vn_height_ratio);
                            $vn_scaling_correction = $h/ceil($vn_resolution * ($ch/$vn_orig_resolution));
                        } else {
                            $vn_resolution = ceil($vn_orig_resolution * $vn_width_ratio);
                            $vn_scaling_correction = $w/ceil($vn_resolution * ($cw/$vn_orig_resolution));
                        }
                        break;
                    # ----------------
                }

                $this->properties["scaling_correction"] = $vn_scaling_correction;

                $this->properties["resolution"] = $vn_resolution;
                $this->properties["width"] = ceil($vn_resolution * ($cw/$vn_orig_resolution));
                $this->properties["height"] = ceil($vn_resolution * ($ch/$vn_orig_resolution));
                $this->properties["target_width"] = $w;
                $this->properties["target_height"] = $h;
                $this->properties["antialiasing"] = ($pa_parameters["antialiasing"]) ? 1 : 0;
                break;
            # -----------------------
        }
        return true;
    }
    # ----------------------------------------------------------
    /**
     * @param array $pa_options Options include:
     *		dontUseDefaultIcons = If set to true, write will fail rather than use default icons when preview can't be generated. Default is false – to use default icons.
     *
     */
    public function write($ps_filepath, $ps_mimetype, $pa_options=null) {

        if (!$this->handle) { return false; }

        $vb_dont_allow_default_icons = (isset($pa_options['dontUseDefaultIcons']) && $pa_options['dontUseDefaultIcons']) ? true : false;

        // is mimetype valid?
        if (!($vs_ext = $this->info["EXPORT"][$ps_mimetype])) {
            $this->postError(1610, _t("Can't convert file to %1", $ps_mimetype), "WLPlugMediaMeshTextured->write()");
            return false;
        }

        // write the file
        if ($ps_mimetype == "application/objzip") {
            //var_dump($this->filepath);
            //die();
            if ( !copy($this->filepath, $ps_filepath.".obj.zip") ) {
                $this->postError(1610, _t("Couldn't write file to '%1'", $ps_filepath), "WLPlugMediaMeshTextured->write()");
                return false;
            }
        } else {

            // not an obj.zip file, so a preview image or a 3D UTF8 conversion for three.js display
            $vb_processed_preview = false;

            $this->opo_config = Configuration::load();
            $vs_external_app_config_path = $this->opo_config->get('external_applications');
            $this->opo_external_app_config = Configuration::load($vs_external_app_config_path);

            $pathinfo = pathinfo($this->filepath);
            $vs_tempdir = $pathinfo["dirname"]."/".$pathinfo["filename"];

            $zip = new ZipArchive() ;
            if ($zip->open($this->filepath) !== true) {
                $this->postError(1610, _t("Impossible to open the archive %1", $ps_filepath), "WLPlugMediaMeshTextured->write()");
                return false;
            }
            // creating dir where content will be unzipped
            if (!is_dir($vs_tempdir)) {
                if (!mkdir($vs_tempdir)) {
                    die('Failed to create folders...');
                }
            }
            // unzipping everything inside the zip at a root folder, to avoid messing with subfolders. See contrib at http://php.net/manual/fr/ziparchive.extractto.php
            for($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $stat = $zip->statIndex($i); 
                $fileinfo = pathinfo($filename);
                $extension = strtolower($fileinfo['extension']);
                // filtering : only files having some size, not starting with a dot, and in the allowed extension are extracted
                if (($stat["size"]>0) && (substr($fileinfo['basename'],0,1)!= ".") && in_array($extension, array('jpg','jpeg','png','mtl','obj'))) {
                    copy("zip://".$this->filepath."#".$filename, $vs_tempdir.DIRECTORY_SEPARATOR.$fileinfo['basename']);
                }
            }                   

            $zip->close();                   
            @chmod($vs_tempdir, 0777);

            // determining basename and path of the obj file
            $files = scandir($vs_tempdir);
            foreach($files as $file) {
                if ((is_file($vs_tempdir."/".$file)) && (substr($file, 0, 1) != '.')) {
                    if(pathinfo($vs_tempdir."/".$file, PATHINFO_EXTENSION) == "obj") {
                        $objbasename = $vs_tempdir."/".pathinfo($vs_tempdir."/".$file, PATHINFO_BASENAME);
                    }
                }
            }

            switch($ps_mimetype) {
                // creating 3D utf8 file to display with three.js
                case 'application/3dutf8':
                    $this->ops_obj2utf8x_path = $this->opo_external_app_config->get('obj2utf8x_app');

                    if(is_file($this->ops_obj2utf8x_path)){
                        exec("mkdir ".$ps_filepath);
                        if(is_dir($ps_filepath)){
                            $temphandle = fopen($ps_filepath.'.3dutf8', 'w+');
                            fwrite($temphandle, '');
                            fclose($temphandle);

                            $vs_destname = basename($ps_filepath);
                            $vs_destpath = $ps_filepath . '/'. $vs_destname;

                            // recopying textures to target path, they are needed for 3D utf8 viewer
                            $files = glob($vs_tempdir."/*.{jpg,gif,png}", GLOB_BRACE);
                            foreach($files as $file){
                                $file_to_go = str_replace($vs_tempdir,$ps_filepath,$file);
                                copy($file, $file_to_go);
                            }

                            // obj to uf8 conversion
                            $convert_command = $this->ops_obj2utf8x_path.' '.$objbasename.' '.$vs_destname.'.utf8mesh '.$vs_destname.'.js';
                            $this->log($ps_filepath, $render_command);
                            exec('cd '.$vs_tempdir.' && '.$convert_command);
                            copy($vs_tempdir.'/'.$vs_destname.'.utf8mesh', $vs_destpath.'.utf8mesh');
                            copy($vs_tempdir.'/'.$vs_destname.'.js', $vs_destpath.'.js');

                            $ps_filepath = $vs_destpath;
                            $vs_ext = 'js';
                            $vb_processed_preview = true;
                        }
                    }
                    break;
                // creating preview files
                case 'image/jpeg':
                case 'image/png':
                    // Tester si blender configuré
                    $this->ops_Blender_path = $this->opo_external_app_config->get('blender_app');

                    $handle = new Imagick();

                    // RENDERING
                    // If we don't already have a rendered version of the OBJ file, let's use our blender python script in support/misc/render3d
                    $render_command="";
                    if(!is_file($objbasename.".png")) {
                        // Test if Blender is available
                        if(is_file($this->ops_Blender_path)) {
                            $render_command = $this->ops_Blender_path.'  -b -P '.__CA_BASE_DIR__."/support/misc/render3d/renderobj.py -- ".$objbasename;
                        }
                        $this->log($ps_filepath, $render_command);
                        if($render_command) exec($render_command);
                    }
                    if(is_file($objbasename.".png")) {
                        $vo_imagick = new Imagick($objbasename.".png");
                        $vo_imagick->resizeImage($this->properties["version_width"],$this->properties["version_height"],Imagick::FILTER_LANCZOS,1);
                        $vo_imagick->writeimage($ps_filepath.".".$vs_ext);
                        $vo_imagick->destroy();
                        $vb_processed_preview = true;
                    }
                    break;
                default:
                    //die("Unsupported output type in PDF plug-in: $ps_mimetype [this shouldn't happen]");
                    break;
            }

            if (!$vb_processed_preview) {
                return __CA_MEDIA_3D_DEFAULT_ICON__;
            }
        }

        $this->properties["mimetype"] = $ps_mimetype;
        $this->properties["filesize"] = filesize($ps_filepath.".".$vs_ext);
        $this->properties["typename"] = $this->typenames[$ps_mimetype];

        return $ps_filepath.".".$vs_ext;
    }
    # ------------------------------------------------
    /**
     * Options:
     *		width
     *		height
     *		numberOfPages
     *		pageInterval
     *		startAtPage
     *		outputDirectory
     *		force = ignore setting of "document_preview_generate_pages" app.conf directive and generate previews no matter what
     */
    # This method must be implemented for plug-ins that can output preview frames for videos or pages for documents
    public function &writePreviews($ps_filepath, $pa_options) {
        return '';
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
    public function mimetype2extension($ps_mimetype) {
        return $this->info["EXPORT"][$ps_mimetype];
    }
    # ------------------------------------------------
    public function mimetype2typename($ps_mimetype) {
        return $this->typenames[$ps_mimetype];
    }
    # ------------------------------------------------
    public function extension2mimetype($ps_extension) {
        reset($this->info["EXPORT"]);
        while(list($k, $v) = each($this->info["EXPORT"])) {
            if ($v === $ps_extension) {
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
        /*$this->properties = array(
            "width" => 960,
            "height" => 540,
            "mimetype" => $this->ohandle["mimetype"],
            "quality" => 75,
            "pages" => $this->ohandle["pages"],
            "page" => 1,
            "resolution" => 72,
            "filesize" => $this->ohandle["filesize"],
            "typename" => "OBJ.ZIP"
        );*/
        $this->properties = array(
            "width" => 960,
            "height" => 540,
            "resolution" => 72,
            "mimetype" => $this->ohandle["mimetype"],
            "filesize" => $this->ohandle["filesize"],
            "typename" => $this->ohandle["typename"]
        );        
    }
    # ------------------------------------------------
    public function htmlTag($ps_url, $pa_properties, $pa_options=null, $pa_volume_info=null) {
        if (!is_array($pa_options)) { $pa_options = array(); }

        AssetLoadManager::register("meshviewer");
        $url = dirname($ps_url)."/".basename($ps_url,".3dutf8")."/".basename($ps_url,".3dutf8").".js";
        ob_start(); ?>

        <div id="mainViewer">

            <pre style="color:white;">
            </pre>

            <div id="viewer"></div>
            <div id="progress"></div>
            <div id="timer"></div>
            <div id="weight"></div>

            <div id="buttons">
                <div id="face-buttons">
                <!--
                    <div class="buttons-header">VIEW</div>
                    <div class="buttons-detail">
                        <div id="face-buttons-table">
                            <div class="face-button" id="face-button-1"></div>
                            <div class="face-button" id="face-button-2" onclick="javascript:showTop()"></div>
                            <div class="face-button" id="face-button-3"></div>
                            <div class="clearfix"></div>
                            <div class="face-button" id="face-button-4" onclick="javascript:showLeft()"></div>
                            <div class="face-button" id="face-button-5" onclick="javascript:showFront()"></div>
                            <div class="face-button" id="face-button-6" onclick="javascript:showRight()"></div>
                            <div class="clearfix"></div>
                            <div class="face-button" id="face-button-7"></div>
                            <div class="face-button" id="face-button-8" onclick="javascript:showBottom()"></div>
                            <div class="face-button" id="face-button-9" onclick="javascript:showBack()"></div>
                            <div class="clearfix"></div>
                        </div>
                    </div>-->
                </div>
                <div id="advanced-buttons">
                    <div class="buttons-header">ADVANCED</div>
                    <div class="buttons-detail">
                        <p>ROTATE</p>
                        <div id="rotate-buttons">
                            <div id="sphere-button-1" class="sphere-button" onclick="javascript:rotateLeft()"></div>
                            <div id="sphere-button-2" class="sphere-button" onclick="javascript:rotateRight()"></div>
                            <div class="clearfix"></div>
                        </div>
                        <!--
                        <div id="pan-buttons">
                            <p>PAN</p>
                            <img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/square_empty_icon&24.png" /><img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/sq_br_up_icon&24.png" onclick="javascript:translateUp()"/><br/>
                            <img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/sq_br_prev_icon&24.png" onclick="javascript:translateLeft()"/><img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/square_shape_icon&24.png" onclick="javascript:translateReset()"/><img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/sq_br_next_icon&24.png" onclick="javascript:translateRight()"/><br/>
                            <img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/square_empty_icon&24.png" /><img src="<?php print __CA_URL_ROOT__;?>/js/meshviewer/icons/24/sq_br_down_icon&24.png" onclick="javascript:translateDown()"/><br/>
                        </div>
                        -->
                        <div id="zoom-buttons">
                            <P>ZOOM</P>
                            <img src="<?php print __CA_URL_ROOT__;?>/assets/meshviewer/icons/24/sq_minus_icon&24.png" onclick="javascript:zoomOut()"/>
                            <img src="<?php print __CA_URL_ROOT__;?>/assets/meshviewer/icons/24/sq_plus_icon&24.png" onclick="javascript:zoomIn()" /><br/>
                        </div>
                        <div id="advanced-toggler-buttons">
                            <P>TOGGLERS</P>
                            <a href="javascript:scene.add(axes);">AXIS ON</a>
                            <a href="javascript:scene.remove(axes);">AXIS OFF</a><br/>
                            <!--
                            <a href="javascript:scene.add(boundingbox);">BBOX ON</a>
                            <a href="javascript:scene.remove(boundingbox);">BBOX OFF</a><br/>
                            <a href="javascript:addPlinth();">PLINTH ON</a>
                            <a href="javascript:removePlinth();">PLINTH OFF</a><br/>
                            <a href="javascript:rotateOn();">ROTATE ON</a>
                            <a href="javascript:rotateOff();">ROTATE OFF</a>
                            -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
                meshviewer({'meshFile' : '<?php print $url; ?>','mtlFile' : '', 'container':'#viewer', 'format':'utf8'});
        </script>
        <?php
        $return = ob_get_contents();

        ob_end_clean();
        return $return;

    }
    # ------------------------------------------------
    #
    # ------------------------------------------------
    public function cleanup() {
        return;
    }
    # ------------------------------------------------

    private function log($ps_file, $log = null) {
        $file = pathinfo($ps_file, PATHINFO_DIRNAME)."/".pathinfo($ps_file, PATHINFO_FILENAME).".log";
        $current = fopen($file, 'a+');
        fwrite($current, $log.'\n');
        //var_dump($log);die();
        fclose($current);
        return true;
    }
}
# ----------------------------------------------------------------------
?>