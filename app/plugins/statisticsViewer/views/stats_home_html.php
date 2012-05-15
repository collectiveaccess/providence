<?php
$va_statistics 	= $this->getVar('statistics_listing');


$s_universe=$va_statistics ->universe;
print "<h2>".strtoupper($s_universe)."</h2>";
print _t("<h2>Statistics & charting plugin</h2>\n");

foreach($va_statistics->statistics_group as $s_group) {
	if ($s_group->title) print"<h3>".$s_group->title."</h3>";
	print "<table width=\"100%\">";
	foreach($s_group->statistic as $s_statistic) {
		print "<tr>\n";
		print "<td align=left style=\"background:#eeeeee;border:lightgray 1px solid;\">".$s_statistic->title."</td>\n";
		print "<td align=right style=\"background:#eeeeee;border:lightgray 1px solid;\"><img src=http://www.mybudget-online.com/images/chart_pie.png align=absmiddle><a href=".__CA_URL_ROOT__."/index.php/statisticsViewer/Statistics/ShowStat/stat/".$s_universe."/id/".$s_statistic->id.">launch</a></td>\n";
		print "<tr>\n";
	}
	print "</table>\n";
}

print "<br/><div class=\"clear\"><!--empty--></div>\n".
	  "<div class=\"editorBottomPadding\"><!-- empty --></div>\n" .
	  "<div class=\"clear\"><!--empty--></div>\n";

?>

