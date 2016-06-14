<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
?>
<div class="uv" data-locale="en-GB:English (GB)" data-uri="<?php print $vs_data_url; ?>" data-collectionindex="0" data-manifestindex="0" data-sequenceindex="0" data-canvasindex="<?php print $vn_page; ?>" style="width:100%; height:100%; background-color: #000" data-config="<?php print $this->request->getAssetsUrlPath(); ?>/universalviewer/config.json"</div>
<script type="text/javascript" id="embedUV" src="<?php print $this->request->getAssetsUrlPath(); ?>/universalviewer/dist/uv/lib/embed.js"></script>