<?php

	/**
	 * Render the table
	 *
	 */
	function renderCSV($qr_result,$va_columns,$va_charting_rows,$va_total_columns) {
		$qr_result->seek();
		foreach ($va_columns as $va_column => $va_colum_label) {
			print "$va_colum_label;";
		}
		print "\n";
		while($qr_result->nextRow()) {
			foreach ($va_columns as $va_column => $va_colum_label) {
			    print $qr_result->get($va_colum_label).";";
			    if (in_array($va_colum_label, $va_total_columns)) $va_total_value["$va_colum_label"] = $va_total_value["$va_colum_label"] + $qr_result->get($va_colum_label);
			}
			print "\n";
		}		
		print "\n";
	}



	/** ---------------------------------------------------------------------
	 * main part 
	 * ---------------------------------------------------------------------- */

	// local variables
	$va_statistics 	= $this->getVar('statistics');
	$va_columns = explode(",",$va_statistics->columns);
	$va_total_columns = explode(",",$va_statistics->total_columns);

	header("Content-Type: application/csv-tab-delimited-table");
	header("Content-disposition: filename=table.csv");	

	renderCSV($qr_result,$va_columns,$va_charting_rows,$va_total_columns);
	exit();		
?>