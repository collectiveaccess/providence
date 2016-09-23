<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/current_sort_html.php :
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
 * ----------------------------------------------------------------------
 */

/** @var ResultContext $vo_result_context */
$vo_result_context 			= $this->getVar('result_context');
/** @var SearchResult $vo_result */
$vo_result					= $this->getVar('result');

$va_current_sort = caGetSortForDisplay($vo_result->getResultTableName(), $vo_result_context->getCurrentSort());
if(is_array($va_current_sort) && (sizeof($va_current_sort) > 0)) {
?>
	<h3 class='currentSort'><?php print _t("Current sort"); ?>:
		<div>
<?php
		print join(', ', $va_current_sort)
?>
		</div>
	</h3>
<?php
}
