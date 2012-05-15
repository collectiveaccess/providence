<?php

// Requiring interface model for the class
require_once("chartClassesInterface.php");

// Defining prerequisite parameters for chart rendering
define("__CHART_CLASS_REQUIRED_PARAMETERS__","columns,width,format,charting_columns,chart_types");


// googleChartTools class
// Implements everything needed to render a chart from given values with Google Charts
//
class googleChartTools implements chartClass {
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

		// Google Chart initialization
		$htmlResult = "<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>\n<script type=\"text/javascript\">google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});google.setOnLoadCallback(drawChart);\nfunction drawChart() {\nvar data = new google.visualization.DataTable();\n";
			  
		// Inserting the column labels
		$va_column_no=0;		
		foreach ($va_columns as $va_column_label) {
			if (in_array($va_column_label,$va_charting_columns)) {
				// The first column is to render as a label (string) when the other are rendered as value (number)
				$htmlResult .= "data.addColumn(".($va_column_no == 0 ? "'string'" : "'number'").", '".$va_column_label."');\n";
				$va_column_no++;
			}
		}
		$va_column_label="";
		
		// Declaring the number of resulting rows
		$htmlResult .= "data.addRows(".$qr_result->numRows().");\n";
		
		// Inserting the result rows
		$va_row_no=0; 
		while($qr_result->nextRow()) {			
			$va_column_no=0;
			foreach ($va_columns as $va_column => $va_column_label) { 
				// only render columns specified in XML field charting_columns 
				if (in_array($va_column_label,$va_charting_columns)) {
					// column no needs to be a string for 0
					if($va_row_no == 0) $va_row_no="0"; else $va_row_no = (string) $va_row_no;  
					$htmlResult .= "\tdata.setValue(".$va_row_no.", ".$va_column_no.", ";
					if ($va_column_no == 0 )
						$htmlResult .= "'"._t($qr_result->get($va_column_label))."'";
					else
						$htmlResult .= _t($qr_result->get($va_column_label));
					$htmlResult .= ");\n";
					$va_column_no++;
				}				
			}
			$va_row_no++;
		}

		// Chart type call, chart div insertion
		$htmlResult .= "var chart = new google.visualization.".$google_chart_type."(document.getElementById('chart_div'));\n" .
			  "chart.draw(data, {width: ".$width.", height: 300, title: '".$va_title."'});}" .
			  "</script>\n" .
			  "<div id=\"chart_div\" style=\"align:left;border:1px solid lightgray;\"></div>"; 
		if ($message) $htmlResult .= $message;
		return $htmlResult;
	}
    
	public function drawImage() {
		return FALSE;
	}
}