<?php
/* ----------------------------------------------------------------------
 * themes/default/views/editor/generic/ajax_media_attribute_list_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 
$va_media_list  = $this->getVar('media_list');
$va_media       = $this->getVar('media');
$va_text        = $this->getVar('text');

?>
<ul>
<?php
    if (sizeof($va_media_list) > 0) {
        foreach($va_media_list as $vn_attr_id => $va_item) {
?>
        <li class='mediaItem' data-id='<?php print $va_item[$va_media[0]]['value_id']; ?>'>
            <div style='float:left;'><?php print $va_item[$va_media[0]]['tags']['icon']; ?></div>
            <div>
<?php
            foreach($va_text as $vs_k) {
?>
                <?php print $va_item[$vs_k]; ?><br/>
<?php
            }
?>
            </div><br style='clear:both;'/>
        </li>
<?php
        }
    } else {
?>
        <h2><?php print _t('No media available'); ?></h2>
<?php
    }
?>
</ul>

<div style="display: none" id="camediacontentTextTemplate"><?php
    print join("\n", array_map(function($v) { return "^{$v}"; }, array_merge($va_media, $va_text)))."\n";
?></div>