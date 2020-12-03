<?php

// Stash the current error_reporting value
$error_reporting = error_reporting();

// Disable warnings for the include_once() test
error_reporting(E_ALL & ~E_WARNING);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	// Composer
	require __DIR__ . '/bootstrap.php';

	if (!class_exists('Validate_ISPN')) {
		print("skip - Validate_ISPN package is not installed.");
	}
} else {
	// Pear
	if (!include_once('Validate/ISPN.php')) {
	    print("skip - Validate_ISPN package is not installed.");
	}
}

// Restore the error reporting to previous value
error_reporting($error_reporting);

?>
