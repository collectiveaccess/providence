<?php
	
	// local variables
	$va_informations = $this->getVar('informations');
	$va_sql = $this->getVar('sql');
	$va_parameters = $this->getVar('parameters');
	//$va_charting_disabled = ($va_informations->charting == "none");
	//$va_positions = $va_parameters['positions'];
	//$va_selectedPosition = $va_parameters['selectedPosition'];
	$va_chart_width = $va_parameters["width"];
			
	// Content creation : CHART
  		
	// Chart object
	$charting_class = $va_parameters['ChartingLib'];
	//$charting_class = "pChart";
	
	require_once(__CA_BASE_DIR__."/app/plugins/statisticsViewer/lib/chart".$charting_class."Class.php");
 		
	// Create a chart object in the class defined by the config file
	$chart = new $charting_class;
	$chart->loadParameter("columns",$va_informations->columns);
	$chart->loadParameter("width",$va_chart_width);
	$chart->loadParameter("format",$va_parameters['selectedChartingType']);
	$chart->loadParameter("charting_columns",$va_informations->charting_columns);
	$chart->loadParameter("chart_types",$va_parameters['ChartTypes']);
	// Test if all parameters are OK
	if($chart->checkRequiredParameters()) {
		// Render Chart
  		$chart->loadValues($va_sql['result']);
		$htmlChart = $chart->drawImage();
		exit();		
	} else {
		die("Missing parameters : ".implode(", ",$chart->returnRequiredParameters()));
	}
?>