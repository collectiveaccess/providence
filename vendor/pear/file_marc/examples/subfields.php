<?php
require 'File/MARC.php';

// File_MARC offers the ability to add subfields at any point within
// an existing set of subfields

// First, create some subfields
$subfields[] = new File_MARC_Subfield('a', 'nothing');
$subfields[] = new File_MARC_Subfield('z', 'everything');

// Then, create a field including those subfields
$field = new File_MARC_Data_Field('100', $subfields, '0');

// Create some new subfields
$subfield1 = new File_MARC_Subfield('g', 'a little');
$subfield2 = new File_MARC_Subfield('k', 'a bit more');
$subfield3 = new File_MARC_Subfield('t', 'a lot');

// Append a new subfield to the existing set of subfields
// Expected order: a-z-g
$field->appendSubfield($subfield1);

// Insert a new subfield after the first subfield with code 'z'
// Expected order: a-z-k-g
$sf = $field->getSubfields('z');
// getSubfields() always returns an array; we just want the first subfield
if (count($sf) > 0) {
    $field->insertSubfield($subfield2, $sf[0]);
}

// Insert a new subfield prior to the first subfield with code 'z'
// Expected order: a-t-z-k-g
$sf = $field->getSubfield('z');
// getSubfield() simply returns the first matching subfield
if ($sf) {
    $field->insertSubfield($subfield3, $sf, true);
}

// let's see the results
print $field;
print "\n";

?>
