<?php

	// TODO : Simplify all of these parameters to get a more readable code
	
	// loading the rendering helpers functions
	require_once(__CA_BASE_DIR__."/app/plugins/statisticsViewer/helpers/viewRenderingHelpers.php");	

	// local variables
	$va_informations = $this->getVar('informations');
	$va_sql = $this->getVar('sql');
	$va_parameters = $this->getVar('parameters');
	$va_charting_disabled = ($va_informations->charting == "none");
	
	// Getting display options from selected position options in conf file, in local variables to keep easier to reread
	$va_positions = $va_parameters['positions'];
	$va_selectedPosition = $va_parameters['selectedPosition'];
	$va_chart_width = $va_positions[$va_selectedPosition]["chartWidth"];
	$va_table_width = $va_positions[$va_selectedPosition]["tableWidth"]; 
	$va_firstElement = $va_positions[$va_selectedPosition]["firstElement"];
	$va_sidebyside = $va_positions[$va_selectedPosition]["side-by-side"];
	
	// Path to the icons for the drop-down options form
	$va_views_images_path = __CA_URL_ROOT__."/app/plugins/statisticsViewer/views/images";	
		
	// Content creation : CHART
  	if (($va_chart_width) && (!$va_charting_disabled)) {
  		
		// Chart object
		$charting_class = $va_parameters['ChartingLib'];
		require_once(__CA_BASE_DIR__."/app/plugins/statisticsViewer/lib/chart".$charting_class."Class.php");
  		
		// Create a chart object in the class defined by the config file
  		$chart = new $charting_class;
		$chart->loadParameter("columns",$va_informations->columns);
		$chart->loadParameter("width",$va_chart_width);
		$chart->loadParameter("format",$va_parameters['selectedChartingType']);
		$chart->loadParameter("charting_columns",$va_informations->charting_columns);
		$chart->loadParameter("chart_types",$va_parameters['ChartTypes']);
		$chart->loadParameter("title",$va_informations->title);

		// Test if all parameters are OK
		if($chart->checkRequiredParameters()) {
			// Render Chart
  			$chart->loadValues($va_sql['result']);
			$htmlChart = $chart->getHtml();
			// parsing the html result to complete with stat & id info if needed
			$htmlChart = str_ireplace("^stat", $va_informations->stat, $htmlChart);
			$htmlChart = str_ireplace("^id", $va_informations->id, $htmlChart);
			$htmlChart = str_ireplace("^width", $va_chart_width, $htmlChart);
			$htmlChart = str_ireplace("^type", $va_parameters['selectedChartingType'], $htmlChart);
		} else {
  			// Missing parameters, printing an error message
  			print _t("<p>Charting class defined in the conf file requires other parameters : ".$charting_class." - ".implode(", ",$chart->returnRequiredParameters())."</p>\n");
  		}

  	} else {
  		// No chart
  		$htmlChart="";
  	}
  	
  	// Content creation : TABLE
  	if ($va_table_width) {
  		$htmlTable .= renderStatisticsTable(
  						$va_sql['result'],
  						$va_informations->columns,
  						$va_informations->charting_columns,
  						$va_informations->total_columns);
  	}

	//If chart goes first
	if ($va_firstElement == "chart") {
		$first=$htmlChart;
		$second=$htmlTable; 
	} else {
		$first=$htmlTable;
		$second=$htmlChart;
	}

	// Final print
	// Printing statistics title & comment
	print "<h2>".$va_informations->title."</h2>";
	print "<p>".$va_informations->comment."</p>";
	
	// Include the CSS for the options drop-down form, then add the options drop-down form
	MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/statisticsViewer/css/statisticsViewer.css",'text/css');	
  	print renderOptions(
  			$va_views_images_path,
  			$va_parameters['positions'],
  			$va_parameters['selectedPosition'],
  			$va_parameters['ChartTypes'],
  			$va_parameters['selectedChartingType']);
  	  	
  	print "<div class=\"divide\"><!-- empty --></div>\n";
	
  	print ($va_sidebyside == "yes" ? "<div style=\"float:left;width:".$va_table_width."px;\">" :"<div>\n")
  		.$first."</div>\n"
  		.($va_sidebyside == "yes" ? "<div style=\"float:left;width:".$va_table_width."px;\">" :"<div>\n")
  		.$second."</div>";
	print "	<div class=\"editorBottomPadding\"><!-- empty --></div>\n" .
			"<div class=\"clear\"><!--empty--></div>";
?>