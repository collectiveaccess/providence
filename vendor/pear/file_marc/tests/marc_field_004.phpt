--TEST--
marc_field_004: Add subfields to an existing field (corner case)
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

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
print $field;
print "\n";

?>
--EXPECT--
100 0  _ga little
       _anothing
       _zeverything
