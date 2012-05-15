<?php
// Requiring interface model for the class
require_once("chartClassesInterface.php");

// Defining prerequisite parameters for chart rendering
define("__CHART_CLASS_REQUIRED_PARAMETERS__","columns,width,format,charting_columns,chart_types");
define("__CA_STATISTICSVIEWER_CLASS_DIR__",__CA_APP_DIR__."/plugins/statisticsViewer/lib/pChart");

 /* pChart library inclusions */
include(__CA_STATISTICSVIEWER_CLASS_DIR__."/class/pData.class.php");
include(__CA_STATISTICSVIEWER_CLASS_DIR__."/class/pDraw.class.php");
include(__CA_STATISTICSVIEWER_CLASS_DIR__."/class/pPie.class.php");
include(__CA_STATISTICSVIEWER_CLASS_DIR__."/class/pImage.class.php"); 

/**
 * pChartTools class
 * Implements everything needed to render a chart from given values with pChart
 * @author gautier
 *
 */
class pChart implements chartClass {
	private $values;
	private $parameters = array();
	private $requiredParameters = array();
		
	public function __construct() {
		$this->requiredParameters = explode(",",__CHART_CLASS_REQUIRED_PARAMETERS__);
	}
	
	/* (non-PHPdoc)
	 * @see chartClass::checkResultType()
	 */
	public function checkResultType() {
		return "image";
	}
	
    /* (non-PHPdoc)
     * @see chartClass::loadValues()
     */
    public function loadValues($values) {
    	$this->values=$values;
    	return true;
    }
    
    /* (non-PHPdoc)
     * @see chartClass::loadParameter()
     */
    public function loadParameter($parameter, $parameter_value) {
    	$this->parameters[$parameter]=$parameter_value;
    	return true;
    }

    /* (non-PHPdoc)
     * @see chartClass::checkRequiredParameters()
     */
    public function checkRequiredParameters() {
    	foreach ($this->requiredParameters as $requiredParameter) {
    		if (!isset($this->parameters[$requiredParameter])) {
    			return false;	
    		}
    	}
    	return true;
    }
    
    /* (non-PHPdoc)
     * @see chartClass::returnRequiredParameters()
     */
    public function returnRequiredParameters(){
    	return $this->requiredParameters;
    }
    
    /* (non-PHPdoc)
     * @see chartClass::getHtml()
     */
    public function getHtml() {
    	$html = "<img src=\"".__CA_URL_ROOT__."/index.php/statisticsViewer/Statistics/ShowChartImage/stat/^stat/id/^id/type/^type/width/^width\">";
    	return $html;
    }
    
    /* (non-PHPdoc)
     * @see chartClass::drawImage()
     */
    public function drawImage() {
    	$qr_result=$this->values;
    	//var_dump($qr_result);die();
	    $va_columns=$this->parameters["columns"];
	    $width=$this->parameters["width"];
	    $format=$this->parameters["format"];
	    $va_charting_columns=$this->parameters["charting_columns"];
	    $va_chart_types=$this->parameters["chart_types"];
	 
		$qr_result->seek();
		
		if ($qr_result->numRows() ==0) {
			//if no result nothing to do
			return false;
		} 
			
		// Loading chart format specifications
		// TODO : this coding part should be out of this function, sending values in 1 parameter 
		if(is_array($va_chart_types)) {
			foreach($va_chart_types as $type => $settings) {
				if ($type == $format) {
					$chart_type = $settings["googletype"];
					$message = $settings["message"];
				}	
			}
		}
    	
		 /* Create and populate the pData object */
		 $MyData = new pData();   
		 		
		
		// Fulfillment of the results
		$va_row_no=0;
		$va_content=array(); 
		while($qr_result->nextRow()) {			
			$va_column_no=0;
			foreach ($va_columns as $va_column => $va_column_label) { 
				// only render columns specified in XML field charting_columns 
				if (in_array($va_column_label,$va_charting_columns)) {
					$va_content[$va_column_label][$va_row_no]=$qr_result->get($va_column_label);
					$va_column_no++;
				}				
			}
			$va_row_no++;
		}
		//var_dump($va_content);
		$va_row_no=0;
		foreach ($va_charting_columns as $va_column_label) {
			//print "MyData->addPoints(\"".implode("\",\"",$va_content[$va_column_label])."\",\"".$va_column_label."\");<br/>";
			$MyData->addPoints($va_content[$va_column_label],$va_column_label);
			if ($va_row_no == 0) 
				//print "MyData->setAbscissa(\"Labels\")<br/>";
				$MyData->setAbscissa($va_column_label);
			else
				//print "MyData->setSerieDescription(\"".$va_column_label."\",\"".$va_column_label."\");<br/>";
				$MyData->setSerieDescription($va_column_label,$va_column_label);
			$va_row_no++;
		}
		  		
		 /* Create the pChart object */
		$myPicture = new pImage($width,$width/2,$MyData);
		/* Set the common properties */ 
		$myPicture->setFontProperties(array("FontName"=>__CA_STATISTICSVIEWER_CLASS_DIR__."/fonts/verdana.ttf","FontSize"=>0.014*$width,"R"=>80,"G"=>80,"B"=>80));
		$RectangleSettings = array("R"=>200,"G"=>200,"B"=>200); 
		$myPicture->drawRectangle(1,1,$width-1,($width/2)-1,$RectangleSettings);
		$myPicture->setGraphArea($width/9,$width/10,$width*0.75,$width*0.4);
		// if not pie, draw the legend
		if ($format != "pie") $myPicture->drawLegend($width*0.8,$width*0.05,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
		
		switch ($format) {
			case "bar":
				$myPicture->drawScale(array("Pos"=>SCALE_POS_TOPBOTTOM,"DrawSubTicks"=>FALSE,"RemoveYAxis"=>TRUE,"GridAlpha"=>90,"DrawXLines"=>FALSE));
				$myPicture->drawBarChart();
				break;
			case "column":
				$myPicture->drawScale(array("DrawSubTicks"=>FALSE,"RemoveYAxis"=>TRUE,"GridAlpha"=>90,"DrawXLines"=>FALSE));
				$myPicture->drawBarChart();
				break;
			case "step":
				$myPicture->drawScale(array("DrawXLines"=>FALSE,"DrawYLines"=>ALL,"GridR"=>127,"GridG"=>127,"GridB"=>127));
				$myPicture->drawStepChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));
				break;
			case "area":
				$myPicture->drawScale(array("DrawXLines"=>FALSE,"DrawYLines"=>ALL,"GridR"=>127,"GridG"=>127,"GridB"=>127));
				$myPicture->drawAreaChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));
				break;
			case "pie":
				$PieChart = new pPie($myPicture,$MyData);
				$PieChart->draw2DPie(0.4*$width,0.25*$width,array("WriteValues"=>PIE_VALUE_PERCENTAGE,"ValueR"=>95,"ValueG"=>95,"ValueB"=>95,"ValuePadding"=>0.03*$width,"Radius"=>0.16*$width,"SecondPass"=>TRUE,"Border"=>TRUE,"Precision"=>0));
				$myPicture->setShadow(FALSE);
				$myPicture->setFontProperties(array("FontName"=>__CA_STATISTICSVIEWER_CLASS_DIR__."/fonts/verdana.ttf","FontSize"=>0.018*$width,"R"=>80,"G"=>80,"B"=>80));
				$PieChart->drawPieLegend(0.8*$width,0.05*$width,array("Style"=>LEGEND_NOBORDER,"BoxSize"=>$width/60,"FontR"=>0,"FontG"=>0,"FontB"=>0));
				break;
			case "line":
			default:
				$myPicture->drawScale(array("DrawXLines"=>FALSE,"DrawYLines"=>ALL,"GridR"=>127,"GridG"=>127,"GridB"=>127));
				$myPicture->drawLineChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));
				break;
		}
		
		/* Render the picture (choose the best way) */
		 $myPicture->autoOutput(); 
    }
}