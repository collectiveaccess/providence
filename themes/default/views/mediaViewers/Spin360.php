<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/Spin360.php :
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
 
	$va_images = $this->getVar('images');
	$t_instance = $this->getVar('t_instance');
	$va_data = $this->getVar('data');
	$vs_identifier = $this->getVar('identifier');
	$vs_id = $this->getVar('id');
	$vs_class = preg_replace("![^A-Za-z0-9]+!", "_", $vs_identifier);
	
	$t_subject = caGetOption('t_subject', $va_data, null);
	
	$vn_num_images = sizeof($va_images);
?>
<div class="threesixty <?php print $vs_class; ?>" id="<?php print $vs_id; ?>">
    <div class="spinner">
        <span>0%</span>
    </div>
    <ol class="threesixty_images" id="<?php print $vs_id.'_images'; ?>"></ol>
</div>
<script type="text/javascript">
	jQuery('#<?php print $vs_id; ?>').ThreeSixty({
        totalFrames: <?php print $vn_num_images; ?>,
        endFrame: <?php print $vn_num_images; ?>, 
        framerate: <?php print floor($vn_num_images/3); ?>, 
        currentFrame: 1, 
        imgList: '#<?php print $vs_id; ?>_images', // selector for image list
        progress: '#<?php print $vs_id; ?> .spinner', // selector to show the loading progress
        imagePath:'<?php print caNavUrl($this->request, '*', '*', 'GetMediaData', ['context' => $this->request->getAction(), 'id' => $t_subject->getPrimaryKey(), 'identifier' => $vs_identifier]); ?>:', 
        filePrefix: '',
        ext: '',
        width: "<?php print caGetOption('viewer_width', $va_data['display'], '800'); ?>",
        height: "<?php print caGetOption('viewer_height', $va_data['display'], '800'); ?>",
        navigation: true,
        disableSpin: false
    });
</script>