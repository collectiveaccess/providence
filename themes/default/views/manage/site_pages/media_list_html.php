<?php
/* ----------------------------------------------------------------------
 * app/views/manage/site_pages/media_list_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
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
 
$va_media_list = $this->getVar('media_list');

?>
    <ul>
<?php
foreach($va_media_list as $va_item) {
?>
    <li class='mediaItem' data-idno='<?php print $va_item['idno']; ?>'>
        <div style='float:left;'><?php print $va_item['tags']['icon']; ?></div>
        <div>
            <em><?php print $va_item['title']; ?></em> (<?php print $va_item['idno']; ?>)<br/>
            <?php print $va_item['caption']; ?>
        </div><br style='clear:both;'/>
    </li>
<?php
}
?>
    </ul>