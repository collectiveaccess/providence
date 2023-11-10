--TEST--
marc_xml_006: test getFields() in XML
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php

$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

// Read MARC records from a stream (a file, in this case)
$marc_source = new File_MARCXML($dir . '/' . 'sandburg.xml');

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
    }
    else {
	print "Error -- \$a subfield appears more than once in this field!";
    }
}

// Retrieve all subject and genre fields
// Series statement fields start with a 6 (PCRE)
$subjects = $marc_record->getFields('^6', true);

// Iterate through all of the returned subject fields
foreach ($subjects as $field) {
    // print with File_MARC_Field_Data's magic __toString() method
    print "$field\n";
}

?>
--EXPECT--
Surname: Sandburg, Carl,
650  0 _aArithmetic
       _xJuvenile poetry.
650  0 _aChildren's poetry, American.
650  1 _aArithmetic
       _xPoetry.
650  1 _aAmerican poetry.
650  1 _aVisual perception.
