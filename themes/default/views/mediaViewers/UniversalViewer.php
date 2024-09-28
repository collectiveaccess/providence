<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2019 Whirl-i-Gig
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
 
	$vs_data_url = $this->getVar('data_url');
	$vn_page = (int)$this->getVar('page');
	
	$vs_width = caParseElementDimension($this->getVar('width'), ['returnAsString' => true, 'default' => '100%']);
	$vs_height = caParseElementDimension($this->getVar('height'), ['returnAsString' => true, 'default' => '100%']);
	
	// For UniversalViewer config file. By default use stock config in UV assets directory, but 
	// if one is defined in either the current theme or the "default" theme then use that one instead.
	$config_path = $this->request->getAssetsUrlPath()."/universalviewer/config.json";
	if (file_exists($this->request->getThemeDirectoryPath()."/views/mediaViewers/universalviewer.config.json")) {
	    $config_path = $this->request->getThemeUrlPath()."/views/mediaViewers/universalviewer.config.json";
	} elseif (file_exists($this->request->getDefaultThemeDirectoryPath()."/views/mediaViewers/universalviewer.config.json")) {
	    $config_path = $this->request->getDefaultThemeUrlPath()."/views/mediaViewers/universalviewer.config.json";
	}
?>
<div class="uv" data-locale="en-GB:English (GB)" data-uri="<?= $vs_data_url; ?>" data-collectionindex="0" data-manifestindex="0" data-sequenceindex="0" data-canvasindex="0" style="width:<?= $vs_width; ?>; height:<?= $vs_height; ?>; background-color: #000;" data-config="<?= $this->request->getAssetsUrlPath(); ?>/universalviewer/config.json"></div>
<script type="text/javascript" id="embedUV" src="<?= $this->request->getAssetsUrlPath(); ?>/universalviewer/dist/uv/lib/embed.js"></script>
