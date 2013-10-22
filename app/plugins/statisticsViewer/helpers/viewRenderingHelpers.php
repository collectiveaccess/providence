<?php
	/**
	 * Render the table
	 *
	 */
	function renderStatisticsTable($qr_result,$va_columns,$va_charting_rows,$va_total_columns,$width="100%") {
		// Function variables
		$cellpadding = intval($width/25); // base for 100% : 4 
		$cellspacing = intval($width/7); // base for 100% : 2
		$cellheight = round($width/30,0);  // base for 100% : 3
		$borderradius = round($width/100,1);  // base for 100% : 1
		if ($width== "100%") $fontheight = "1.2"; else $fontheight = "1";  // base for 100% : 1.2
		
		// Initializing the result variable
		$htmlResult = "";
		// Reset the pointer on the qr_result variable
		$qr_result->seek();

		if ($qr_result->numRows() ==0) {
			// If no result, display a message.
			$htmlResult .= "<table width=\"100%\"; height=\"100px\";><tr><td style=\"background:lightgray;text-align:center;-moz-border-radius: ".$borderradius."em;border-radius: ".$borderradius."em;\">";
			$htmlResult .="No result";
			$htmlResult .="</td></tr></table>";
			return $htmlResult;
		} 
		
		// If results, start the all shebang
		// Table begins here    
		$htmlResult .= "<table style=\"width:100%;text-align:center;\" cellpadding=\"".$cellpadding."px\" cellspacing=\"".$cellspacing."px\">\n";
		// Table head of columns    
		$htmlResult .= "<thead><tr style=\"height:".$cellheight."em\">";
		foreach ($va_columns as $va_column => $va_colum_label) {
			$htmlResult .= "<th style=\"background:lightgreen;height:".$cellheight."em;-moz-border-radius: ".$borderradius."em;border-radius: ".$borderradius."em;font-size:".$fontheight."em;\">$va_colum_label</th>";
		}
		$htmlResult .= "</tr></thead>\n";
		// Table contents  
		$htmlResult .= "<tbody>";
		while($qr_result->nextRow()) {
			$htmlResult .= "<tr>";
			foreach ($va_columns as $va_column => $va_colum_label) {
			    /* temp disabled : used to emphasis line retained in the graphics
			    if (in_array($va_colum_label, $va_charting_rows)) 
			    	print "<th scope=\"row\" style=\"background:#eeeeee;border:lightgray 1px solid;font-size:".$fontheight."em;\">".$qr_result->get($va_colum_label)."</th>";
			    else
			    */ 
			    $htmlResult .= "<td style=\"background:lightgray;height:".$cellheight."em;-moz-border-radius: ".$borderradius."em;border-radius: ".$borderradius."em;font-size:".$fontheight."em;\">"._t($qr_result->get($va_colum_label))."</td>";
			    if (in_array($va_colum_label, $va_total_columns)) $va_total_value["$va_colum_label"] = $va_total_value["$va_colum_label"] + $qr_result->get($va_colum_label);
			}
			$htmlResult .= "</tr>\n";
		 }		
		$htmlResult .= "</tbody>";
		// Total line with subtotal when needed
			$htmlResult .= "<tfoot>";
			foreach ($va_columns as $va_column => $va_colum_label) {
				if (in_array($va_colum_label, $va_total_columns)) {
					$htmlResult .= "<td style=\"background:lightblue;height:".$cellheight."em;-moz-border-radius: ".$borderradius."em;border-radius: ".$borderradius."em;font-size:".$fontheight."em;\">".$va_total_value["$va_colum_label"]."</td>";
				} else {
					$htmlResult .= "<td></td>";
				}
			}
			$htmlResult .= "</tfoot>";
		// Table ends hereafter
		$htmlResult .= " </table>";
		return $htmlResult;				
	}


	
	/**
	 * Render the drop-down options
	 *
	 */	
	function renderOptions($va_views_images_path,$va_positions="",$va_selectedPosition,$va_chart_types="",$va_selectedChartingType="line") {		
		// Initializing the result variable
		$htmlResult ="";
		// Preparing HTML for charting type selection via radio button
		if (is_array($va_chart_types) && ($va_selectedChartingType!="none")) {
			$charting_options="<div class=\"col\"><b>Charting format</b>";
			foreach($va_chart_types as $type => $settings) {
				if ($va_selectedChartingType == $type) $checked="checked=\"checked\""; else $checked="";
				$charting_options .= "<p><input type=\"radio\" name=\"selectedChartingType\" value=\"$type\" $checked><img src=\"$va_views_images_path/$type.png\" align=\"absmiddle\"/> $type</input></p>\n";
			}
			$charting_options.="</div><!-- end col -->\n";
		}
		// Preparing HTML for table/chart position via radio button
		if (is_array($va_positions) && ($va_selectedChartingType!="none")) {
			$position_options="<div class='col'><b>Table and/or chart</b>";
			foreach($va_positions as $position => $settings) {
				if ($va_selectedPosition == $position) $checked="checked=\"checked\""; else $checked="";
				$position_options .= "<p><input type=\"radio\" name=\"selectedPosition\" value=\"$position\" $checked><img src=\"$va_views_images_path/$position.png\" align=\"absmiddle\"/> ".$settings['label']."</input></p>\n";
			}
			$position_options.="</div><!-- end col -->\n";
		}

		// OK, what's next is a very stupid way of coding
		$htmlResult .= "
		<a href='#' id='showExports' onclick='jQuery(\"#layoutOptionsBox\").hide();  jQuery(\"#showLayoutOptions\").slideDown(1); jQuery(\"#exportsBox\").slideDown(250); jQuery(\"#showExports\").hide();  return false;'>Exports <img src=\"/providence/themes/default/graphics/arrows/arrow_right_gray.gif\" width=\"6\" height=\"7\" border=\"0\"></a>
		<a href='#' id='showLayoutOptions' onclick='jQuery(\"#exportsBox\").hide();  jQuery(\"#showExports\").slideDown(1); jQuery(\"#layoutOptionsBox\").slideDown(250); jQuery(\"#showLayoutOptions\").hide();  return false;'>" . _('Display Options') . "<img src=\"/providence/themes/default/graphics/arrows/arrow_right_gray.gif\" width=\"6\" height=\"7\" border=\"0\"></a>
 		<div id=\"layoutOptionsBox\" onLoad='jQuery(\"#layoutOptionsBox\").hide()'>
		<form action='".$_SERVER['REQUEST_URI']."' method='post' id='caSearchOptionsForm' target='_top' enctype='multipart/form-data'>
		<div class=\"bg\">
		<input type='hidden' name='_formName' value='caViewStatOptions'/>
		".
		$position_options.
		$charting_options."
		<div class=\"control-box-middle-content\"><a href='#' id='saveOptions' onclick='jQuery(\"#caSearchOptionsForm\").submit();'>Apply <img src=\"".$va_views_images_path."/arrow_right_gray.gif\" width=\"9\" height=\"10\" border=\"0\"></a></div>
				<a href='#' id='hideLayoutOptions' onclick='jQuery(\"#layoutOptionsBox\").slideUp(250); jQuery(\"#showLayoutOptions\").slideDown(1); return false;'><img src=\"".$va_views_images_path."/collapse.gif\" width=\"11\" height=\"11\" border=\"0\"></a>
		
				<div style='clear:both;height:1px;'>&nbsp;</div>
			</div><!-- end bg --></form>
		</div><!-- end layoutOptionsBox -->
		
		<div id=\"exportsBox\" onLoad='jQuery(\"#layoutOptionsBox\").hide()'>
		<div class=\"bg\">
		 	<div class=\"control-box-middle-content\"><a href='/providence/index.php/statisticsViewer/Statistics/ShowCSV/stat/objects/id/1' id='saveOptions' >CSV <img src=\"".$va_views_images_path."/arrow_right_gray.gif\" width=\"9\" height=\"10\" border=\"0\"></a></div></form>
				<a href='#' id='hideExports' onclick='jQuery(\"#exportsBox\").slideUp(250); jQuery(\"#showExports\").slideDown(1); return false;'><img src=\"".$va_views_images_path."/collapse.gif\" width=\"11\" height=\"11\" border=\"0\"></a>
				<div style='clear:both;height:1px;'>&nbsp;</div>
		</div><!-- end bg -->
		</div><!-- end exportsBox -->";
		return $htmlResult;	
	}
	
	/**
	 * HTML vardump : useful for debugging
	 * @param unknown_type $var
	 */
	function htmlvardump($var) {
		echo '<pre>'; // This is for correct handling of newlines
		ob_start();
		var_dump($var);
		$a=ob_get_contents();
		ob_end_clean();
		echo htmlspecialchars($a,ENT_QUOTES); // Escape every HTML special chars (especially > and < )
		echo '</pre>';		
	}
