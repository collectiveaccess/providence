<?php

// set_include_path('./vendor/pear/pear_exception' . PATH_SEPARATOR . './tests' . PATH_SEPARATOR . get_include_path());

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	// Composer
	require __DIR__ . '/../vendor/autoload.php';
} else {
	// Pear
	require 'File/MARC.php';
	require 'File/MARCXML.php';
}
