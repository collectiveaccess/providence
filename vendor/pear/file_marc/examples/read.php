<?php

require 'File/MARC.php';

// Read MARC records from a stream (a file, in this case)
$marc_source = new File_MARC('example.mrc');

// Retrieve the first MARC record from the source
$marc_record = $marc_source->next();

// Retrieve a personal name field from the record
$names = $marc_record->getFields('100');

foreach ($names as $name_field) {
    // Now print the $a subfield
    switch ($name_field->getIndicator(1)) {
    case 0:
        print "Forename: ";
        break;

    case 1:
        print "Surname: ";
        break;

    case 2:
        print "Family name: ";
        break;
    }

    $name = $name_field->getSubfields('a');

    if (count($name) == 1) {
        print $name[0]->getData() . "\n";
    } else {
        print "Error -- \$a subfield appears more than once in this field!";
    }
}

print "\nPrint all series statement fields (4xx):\n";
// Retrieve all series statement fields
// Series statement fields start with a 4 (PCRE)
$subjects = $marc_record->getFields('^4', true);

// Iterate through all of the returned series statement fields
foreach ($subjects as $field) {
    // print with File_MARC_Field_Data's magic __toString() method
    print $field;
}

print "\n";

?>
