--TEST--
marc_008: Attempt to open a file that does not exist
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

try {
    $marc_file = new File_MARC('super_bogus_file');
}
catch (File_MARC_Exception $fme) {
    print $fme->getMessage();
}

?>
--EXPECTF--
Warning: fopen(super_bogus_file): failed to open stream: No such file or directory in %sMARC.php on line %d
Invalid input file "super_bogus_file"
