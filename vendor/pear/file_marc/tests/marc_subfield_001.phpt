--TEST--
marc_subfield_001: Exercise basic methods for File_MARC_Subfield class
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

// test constructor
$subfield = new File_MARC_Subfield('a', 'wasssup');

// test get methods
print "Code: " . $subfield->getCode() . "\n";
print "Data: " . $subfield->getData() . "\n";

// test __toString implementation
print $subfield;
print "\n";

// test raw output implementation
print $subfield->toRaw() . "\n";

// test isEmpty()
if ($subfield->isEmpty()) {
    print "Subfield is empty\n";
}
else {
    print "Subfield is not empty\n";
}
?>
--EXPECT--
Code: a
Data: wasssup
[a]: wasssup
awasssup
Subfield is not empty
