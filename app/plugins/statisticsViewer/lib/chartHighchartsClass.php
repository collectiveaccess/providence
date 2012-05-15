<?php

// Requiring interface model for the class
require_once("chartClassesInterface.php");

// Defining prerequisite parameters for chart rendering
define("__CHART_CLASS_REQUIRED_PARAMETERS__","columns,width,format,charting_columns,chart_types");


// googleChartTools class
// Implements everything needed to render a chart from given values with Google Charts
//
class Highcharts implements chartClass {
	private $values;
	private $parameters = array();
	private $requiredParameters = array();
	
	public function __construct() {
		$this->requiredParameters = explode(",",__CHART_CLASS_REQUIRED_PARAMETERS__);
	}
	
	public function checkResultType() {
		return "html";
	}
	
	public function loadValues($values) {
    	$this->values=$values;
    	return true;
    }
    
    public function loadParameter($parameter, $parameter_value) {
    	$this->parameters[$parameter]=$parameter_value;
    	return true;
    }

    public function checkRequiredParameters() {
    	foreach ($this->requiredParameters as $requiredParameter) {
    		if (!isset($this->parameters[$requiredParameter])) {
    			return false;	
    		}
    	}
    	return true;
    }
    
    public function returnRequiredParameters(){
    	return $this->requiredParameters;
    }
    
    public function getHtml() {
    	$qr_result=$this->values;
	    $va_columns=$this->parameters["columns"];
	    $width=$this->parameters["width"];
	    $format=$this->parameters["format"];
	    $va_charting_columns=$this->parameters["charting_columns"];
	    $va_chart_types=$this->parameters["chart_types"];
	    $va_title=$this->parameters["title"];

	    $qr_result->seek();
		
		// Initializing the result variable
		$htmlResult = "";
 
		if ($qr_result->numRows() ==0) {
			//if no result nothing to do
			return false;
		} 
			
		// Loading chart format specifications
		// TODO : this coding part should be out of this function, sending values in 1 parameter 
		if(is_array($va_chart_types)) {
			foreach($va_chart_types as $type => $settings) {
				if ($type == $format) {
					$google_chart_type = $settings["googletype"];
					$message = $settings["message"];
				}	
			}
		}

		// Highchart js code
		$htmlResult = "
<script src=\"http://code.highcharts.com/highcharts.js\"></script>
<script src=\"http://code.highcharts.com/modules/exporting.js\"></script>



<div id=\"container\" style=\"min-width: 300px; height: 400px; margin: 0 auto\"></div>
<script type=\"text/javascript\">

(function($){ // encapsulate jQuery

var chart;
$(document).ready(function() {
	chart = new Highcharts.Chart({
		chart: {
			renderTo: 'container',
			plotBackgroundColor: null,
			plotBorderWidth: null,
			plotShadow: false
		},
		title: {
			text: '".$va_title."'
		},
		credits: {
            enabled: false
        },
		tooltip: {
			formatter: function() {
				^TOOLTIP
			}
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: false
				},
				showInLegend: true
			}
		},
		series: [{
			type: '^TYPE',
			name: '".$va_title."',
			data: [
^DATA
			]
		}]
	});
});

})(jQuery);
</script>";
		// Inserting the result rows
		$va_row_no=0;
		$data=""; 
		while($qr_result->nextRow()) {			
			$va_column_no=0;
			$data .= "[";
			foreach ($va_columns as $va_column => $va_column_label) { 
				// only render columns specified in XML field charting_columns 
				if (in_array($va_column_label,$va_charting_columns)) {
					// column no needs to be a string for 0
					if ($va_column_no == 0) 
						$data .= "'".$qr_result->get($va_column_label)."',";
					else 
						$data .= $qr_result->get($va_column_label);
					$va_column_no++;
				}				
			}
			$data .= "],\n";
			$va_row_no++;
		}

		switch ($format) {
			case "bar":
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ this.y;", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "bar", $htmlResult);
				break;
			case "column":
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ this.y;", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "column", $htmlResult);
				break;
			case "step":
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ this.y;", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "spline", $htmlResult);
				break;
			case "area":
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ this.y;", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "area", $htmlResult);
				break;
			case "pie":
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage*100)/100 +' %';", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "pie", $htmlResult);
				break;
			case "line":
			default:
				$htmlResult = str_ireplace("^TOOLTIP", "return '<b>'+ this.point.name +'</b>: '+ this.y;", $htmlResult); 
				$htmlResult = str_ireplace("^TYPE", "line", $htmlResult);
				break;
		}
		
		$htmlResult = str_ireplace("^DATA", $data, $htmlResult);
		
		return $htmlResult;
	}
    
	public function drawImage() {
		return FALSE;
	}
}