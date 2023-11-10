--TEST--
marc_subfield_002: Exercise setter and isEmpty() methods for File_MARC_Subfield class
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

function testEmpty($testSubfield) {
  print "Subfield is ";
  if ($testSubfield->isEmpty()) {
    print "empty.\n";
  }
  else {
    print "not empty.\n";
  }

}

$subfield = new File_MARC_Subfield('a', 'wasssup');

// test isEmpty() scenarios
testEmpty($subfield);

$subfield->setData(null);
testEmpty($subfield);

$subfield->setData('just hangin');
testEmpty($subfield);

// test setCode() scenarios
print "\nSet code to 'z'...";
if ($subfield->setCode('z')) {
  print "\n";
  print $subfield;
}

print "\nSet code to ''...";
if ($subfield->setCode('')) {
  print "\n";
  print $subfield;
}

print "\nSet code to null...";
if ($subfield->setCode(null)) {
  print "\n";
  print $subfield;
}

?>
--EXPECT--
Subfield is not empty.
Subfield is empty.
Subfield is not empty.

Set code to 'z'...
[z]: just hangin
Set code to ''...
Set code to null...
