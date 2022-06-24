--TEST--
marc_field_003: Add subfields to an existing field
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
$subfield2 = new File_MARC_Subfield('k', 'a bit more');
$subfield3 = new File_MARC_Subfield('t', 'a lot');
$subfield4 = new File_MARC_Subfield('0', 'first post');

// append a new subfield to the existing set of subfields
// expected order: a-z-g
$field->appendSubfield($subfield1);

// insert a new subfield after the first subfield with code 'z'
// expected order: a-z-k-g
$sf = $field->getSubfields('z');
// we might get an array back; in this case, we want the first subfield
if (is_array($sf)) {
  $field->insertSubfield($subfield2, $sf[0]);
}
else {
  $field->insertSubfield($subfield2, $sf);
}

// insert a new subfield prior to the first subfield with code 'z'
// expected order: a-t-z-k-g
$sf = $field->getSubfields('z');
// we might get an array back; in this case, we want the first subfield
if (is_array($sf)) {
  $field->insertSubfield($subfield3, $sf[0], true);
}
else {
  $field->insertSubfield($subfield3, $sf, true);
}

// insert a new subfield at the very start of the field
$field->prependSubfield($subfield4);

// let's see the results
print $field;
print "\n";

?>
--EXPECT--
100 0  _0first post
       _anothing
       _ta lot
       _zeverything
       _ka bit more
       _ga little
