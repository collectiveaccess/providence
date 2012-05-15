Errors occurred when trying to access <code><?php print $this->getVar('referrer'); ?></code>:<br/>

<ul>
<?php
	foreach($this->getVar("error_messages") as $vs_message) {
		print "<li>$vs_message </li>\n";
	}
?>
</ul>