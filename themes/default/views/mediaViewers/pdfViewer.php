<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/pdfViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2026 Whirl-i-Gig
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
$t_subject = $this->getVar('t_subject');
$vn_page = (int)$this->getVar('page');

$vs_width = caParseElementDimension($this->getVar('width'), ['returnAsString' => true, 'default' => '100%']);
$vs_height = caParseElementDimension($this->getVar('height'), ['returnAsString' => true, 'default' => '100%']);

$url = $this->getVar('display_media_url') ? $this->getVar('display_media_url') : $this->getVar('original_media_url');
?>
<div id="pdfMediaViewer" style="width: <?= $vs_width; ?>; height: <?= $vs_height; ?>"></div>
 
<script type="module">
  import EmbedPDF from '<?= __CA_URL_ROOT__.'/assets/embedpdf/embedpdf.js'; ?>'
 
  EmbedPDF.init({
    type: 'container',
    target: document.getElementById('pdfMediaViewer'),
    src: <?= json_encode($url); ?>,
    theme: { preference: 'system' },
    disabledCategories: ['annotation', 'print', 'form', 'redaction', 'insert', 'document', 'panel-comment'],
    scroll: {
		defaultStrategy: <?= json_encode($this->getVar('scroll_mode')); ?>, 
		defaultPageGap: 20          
	}
  });
</script>