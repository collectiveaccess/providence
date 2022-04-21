<?php
/* ----------------------------------------------------------------------
 * default/views/mailTemplates/library_checkout_receipt.tpl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
$checkouts = $this->getVar('checkouts') ?? [];
$reservations = $this->getVar('reservations') ?? [];

if(sizeof($checkouts)) { 
?>
<p>The following items were borrowed on <?= $this->getVar('checkout_date'); ?> and will be due on the dates specified below: </p>

<ul><?= join("\n", array_map(function($v) { return $v['_display']; }, $checkouts)); ?></ul>

<p>Please return them on or before the listed dates. Thank you!</p>
<?php
}
if(sizeof($reservations)) {
?>
<p>The following items were reserved on <?= $this->getVar('checkout_date'); ?>: </p>

<ul><?= join("\n", array_map(function($v) { return $v['_display']; }, $checkouts)); ?></ul>

You will be notified when these items become available.
<?php
}
