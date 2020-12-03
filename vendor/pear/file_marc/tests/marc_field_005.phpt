--TEST--
marc_field_005: Test method getContents
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

// create some subfields
$subfields[] = new File_MARC_Subfield('a', 'nothing');
$subfields[] = new File_MARC_Subfield('z', 'everything');

// create a field
$field = new File_MARC_Data_Field('100', $subfields, '0');

// create some new subfields
$subfield1 = new File_MARC_Subfield('g', 'a little');

// test the fieldpost corner case by inserting prior to the first subfield
$sf = $field->getSubfields('a');
// we might get an array back; in this case, we want the first subfield
if (is_array($sf)) {
  $field->insertSubfield($subfield1, $sf[0], true);
}
else {
  $field->insertSubfield($subfield1, $sf, true);
}

// let's see the results
print $field->getContents();
print "\n";
print $field->getContents('###');
print "\n";

?>
--EXPECT--
a littlenothingeverything
a little###nothing###everything
