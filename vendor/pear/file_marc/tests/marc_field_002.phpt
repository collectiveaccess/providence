--TEST--
marc_field_002: Create fields with invalid indicators
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

// create some subfields
$subfields[] = new File_MARC_Subfield('a', 'nothing');
$subfields[] = new File_MARC_Subfield('z', 'everything');

// test constructor
try {
    $field = new File_MARC_Data_Field('100', $subfields, '$@');
}
catch (Exception $e) {
    print "Error: {$e->getMessage()}\n";
}
--EXPECT--
Error: Illegal indicator "$@" in field "100" forced to blank
