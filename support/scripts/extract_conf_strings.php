<?php
$output_path = $argv[1];

$extracted_strings = [];
for($i=2; $i < sizeof($argv); $i++) {
	if(!file_exists($argv[$i])) { continue; }

	$r = fopen($argv[$i], "r");

	while($line = fgets($r)) {
		$strings = preg_match_all("!_\([\"]{0,1}([^\"\)]+)[\"]{0,1}\)!", $line, $m);
	
		$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
			return preg_match("![A-Za-z0-9]+!", $v);
		}));
	}
}
$extracted_strings = array_unique($extracted_strings);


$out = fopen($output_path, "w");

foreach($extracted_strings as $s) {
	fputs($out, "msgid \"{$s}\"\n");
	fputs($out, "msgstr \"\"\n\n");
}
