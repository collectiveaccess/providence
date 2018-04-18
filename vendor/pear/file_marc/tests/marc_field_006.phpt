--TEST--
marc_field_006: Test methods getSubfield and getSubfields
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

// create some subfields
$subfields = array();
$subfields[] = new File_MARC_Subfield('3', 'number');
$subfields[] = new File_MARC_Subfield('a', 'character');
$subfields[] = new File_MARC_Subfield('z', 'another character');

// create a field
$field = new File_MARC_Data_Field('100', $subfields);

// let's see the results
$results[] = $field->getSubfield( '[a-z]', true );
$results = array_merge( $results, $field->getSubfields( 'a' ) );
$results = array_merge( $results, $field->getSubfields( '[a-z]', true ) );
$results = array_merge( $results, $field->getSubfields( '[0-9]', true ) );
$results = array_merge( $results, $field->getSubfields( '.', true ) );

foreach ($results AS $sf) {
    print $sf;
    print "\n";
}

// Check for empty fields
if ($field->getSubfield( 'd|4|n', true ) === false) {
    print "Nice! False as result for getSubfield.\n";
}

$subfields_result = $field->getSubfields( 'd|4|n', true );
if ( is_array( $subfields_result ) && count( $subfields_result ) === 0 ) {
    print "Nice! Empty array as result for getSubfields.\n";
}
?>
--EXPECT--
[a]: character
[a]: character
[a]: character
[z]: another character
[3]: number
[3]: number
[a]: character
[z]: another character
Nice! False as result for getSubfield.
Nice! Empty array as result for getSubfields.
