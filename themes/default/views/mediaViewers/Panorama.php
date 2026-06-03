<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/Panorama.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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

$width = caParseElementDimension($this->getVar('width') ? $this->getVar('width') : $this->getVar('viewer_width'), ['returnAsString' => true, 'default' => '100%']);
$height = caParseElementDimension($this->getVar('height') ? $this->getVar('height') : $this->getVar('viewer_height'), ['returnAsString' => true, 'default' => '100%']);
$id = 'panorama_'.preg_replace("/[^A-Za-z0-9]+/", "_", $this->getVar('identifier'));

$options = $this->getVar('options');
$file_urls = $this->getVar('fileUrls');
?>
<div id="<?= $id; ?>" class="panoramaViewer" style="width: <?= $$width; ?>; height: <?= $height; ?>;"></div>
<script type="text/javascript">
	jQuery(document).ready(function() {
		const viewer = window.CI360;
		const container = document.getElementById('<?= $id; ?>');
		
		const config = {
		  imageListX: <?= json_encode($file_urls); ?>,
		  amountX: <?= sizeof($file_urls); ?>,
		  autoplay: <?= json_encode(caGetOption('autoplay', $options, false, ['castTo' => 'bool'])); ?>,
		  speed: <?= json_encode(caGetOption('speed', $options, 100)); ?>,
		  dragSpeed: <?= json_encode(caGetOption('dragSpeed', $options, 150)); ?>,
		  fullscreen: <?= json_encode(caGetOption('fullscreen', $options, false, ['castTo' => 'bool'])); ?>,
		  zoomMax: <?= json_encode(caGetOption('zoomMax', $options, 8)); ?>,
		  inertia: <?= json_encode(caGetOption('inertia', $options, true, ['castTo' => 'bool'])); ?>,
		  draggable: <?= json_encode(caGetOption('draggable', $options, false, ['castTo' => 'bool'])); ?>,
		  keys: <?= json_encode(caGetOption('keys', $options, false, ['castTo' => 'bool'])); ?>
		};
		
		viewer.init(container, config);
	});
</script>
<?php
