<?php
print _t("Errors occurred when trying to access")." <code>".$this->getVar('referrer')."</code>:<br/>";
?>

<ul>
<?php
	foreach($this->getVar("error_messages") as $vs_message) {
		print "<li>$vs_message </li>\n";
	}
?>
</ul>