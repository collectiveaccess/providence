<?php

// Stash the current error_reporting value
$error_reporting = error_reporting();

// Disable warnings for the include_once() test
error_reporting(E_ALL & ~E_WARNING);
if (!include_once('Validate/ISPN.php')) {
    print("skip - Validate_ISPN package is not installed.");
}

// Restore the error reporting to previous value
error_reporting($error_reporting);

?>
