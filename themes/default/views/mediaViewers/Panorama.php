<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/Panorama.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2026 Whirl-i-Gig
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
AssetLoadManager::register("panorama");

$files =  $this->getVar('files');
$urls = array_values(array_map(function($v) {
	return $v['original_url'];
}, $files));
?>
<div id="panoramaViewer"></div>
<script type="text/javascript">
	jQuery(document).ready(function() {
		const viewer = window.CI360;
		const container = document.getElementById('panoramaViewer');
		
		const config = {
		  imageListX: <?= json_encode($urls); ?>,
		  amountX: <?= sizeof($urls); ?>,
		  autoplay: false,
		  speed: 100,
		  dragSpeed: 150,
		  fullscreen: false,
		  zoomMax: 6,
		  inertia: true,
		  draggable: true,
		  keys: true
		};
		
		viewer.init(container, config);
	});
</script>
<?php
