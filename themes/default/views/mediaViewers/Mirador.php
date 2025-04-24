<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/Mirador.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2024 Whirl-i-Gig
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
$data_url = $this->getVar('data_url');
$page = (int)$this->getVar('page');
$id = 'mirador_'.preg_replace("/[^A-Za-z0-9]+/", "_", $this->getVar('identifier'));

$width = caParseElementDimension($this->getVar('width') ? $this->getVar('width') : $this->getVar('viewer_width'), ['returnAsString' => true, 'default' => '100%']);
$height = caParseElementDimension($this->getVar('height') ? $this->getVar('height') : $this->getVar('viewer_height'), ['returnAsString' => true, 'default' => '100%']);
?>
<link rel="stylesheet" type="text/css" href="<?= $this->request->getAssetsUrlPath(); ?>/mirador/css/mirador-combined.css"/>	
<script type="text/javascript" src="<?php print $this->request->getAssetsUrlPath(); ?>/mirador/mirador.js"></script>
<script type="text/javascript">
    jQuery(document).ready(function() {
      Mirador({
        "id": "<?= $id; ?>", 
        "layout": "1x1", 
        "mainMenuSettings" : {
          "show" : false
        },
        "data": [
          { "manifestUri": "<?= $data_url; ?>"}
        ],
        "windowObjects": [{
        	"loadedManifest" : "<?= $data_url; ?>",
        	"viewType" : "ImageView",
        	"displayLayout": false,
			"bottomPanel" : true,
			"bottomPanelVisible": false,
			"sidePanel" : false,
			"metadataView": false,
			"annotationLayer" : false,
			"annotationCreation": false,
			"overlay" : false,
			"canvasControls": {
				"annotations": {
					"annotationLayer": false
				}
			}
        }],
		"buildPath": '<?= __CA_URL_ROOT__."/assets/mirador/"; ?>'
      });
      jQuery(".mirador-icon-metadata-view, .mirador-osd-annotation-controls").hide();
    });
  </script>
  <div id="<?= $id; ?>" style="width: <?= $width; ?>; height: <?= !$this->getVar('hideOverlayControls') ? "calc({$height} - 24px)" : $height; ?>;">
  
  </div>
