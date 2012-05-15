<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/maintenance/search_reindex_status_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
	JavascriptLoadManager::register("sortableUI");
?>
<h1><?php print _t('Reload sort values'); ?></h1>


<div class="searchReindexTableProgressGroup">
	<div id="searchReindexTableStatus" class="searchReindexStatus"> </div>
	<div id="progressbarTables"></div>
</div>

<div class="searchReindexRowProgressGroup">
	<div id="searchReindexRowStatus" class="searchReindexStatus"> </div>
	<div id="progressbarRows"></div>
	<div class="searchReindexElapsedTime" id="searchReindexElapsedTime"></div><br style="clear: both;"/>
</div>
	
<script type="text/javascript">
		jQuery('#progressbarTables').progressbar({
			value: 0
		});
		jQuery('#progressbarRows').progressbar({
			value: 0
		});
</script>


<?php
	function caIncrementSortValueReloadProgress($pn_rows_complete, $pn_total_rows, $ps_row_message, $ps_table_message, $pn_elapsed_time, $pn_memory_used, $pa_table_list, $pn_table_num, $ps_display_name, $pn_tables_processed) {
		$pn_percentage = ($pn_rows_complete/$pn_total_rows) * 100;
		if (is_null($ps_row_message)) {
			$ps_row_message = _t('Reloading %1/%2', $pn_rows_complete, $pn_total_rows);
		}
		
		if (is_null($ps_table_message)) {
			$ps_table_message = _t('Reloading sort values for %1 (%2/%3)', $ps_display_name, $pn_tables_processed, sizeof($pa_table_list));
		}
		$pn_table_percentage = ($pn_tables_processed / sizeof($pa_table_list)) * 100;
		
		print "<script type='text/javascript'>";
		print "jQuery('#progressbarTables').progressbar('value',{$pn_table_percentage}); jQuery('#searchReindexTableStatus').html('{$ps_table_message}');";
		print "jQuery('#progressbarRows').progressbar('value',{$pn_percentage}); jQuery('#searchReindexRowStatus').html('{$ps_row_message}');";
		print "jQuery('#searchReindexElapsedTime').html('".caFormatInterval($pn_elapsed_time)."/".sprintf("%4.2f mb", ($pn_memory_used/ 1048576))."');"; 
		print "</script>";
		caFlushOutput();
	}
	
	$app = AppController::getInstance();
	$app->registerPlugin(new SortValueReloadingProgress());
?>