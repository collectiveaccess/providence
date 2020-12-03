--TEST--
marc_field_001: Exercise basic methods for File_MARC_Field class
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
$field = new File_MARC_Data_Field('100', $subfields, '0');

// test basic getter methods
print "Tag: " . $field->getTag() . "\n";
print "Get Ind1: " . $field->getIndicator(1) . "\n";
print "Get Ind2: " . $field->getIndicator(2) . "\n";

// test basic setter methods
print "Set Ind1: " . $field->setIndicator(1, '3') . "\n";

// test pretty print
print $field;
print "\n";

// test raw print
print $field->toRaw();
?>
--EXPECT--
Tag: 100
Get Ind1: 0
Get Ind2:  
Set Ind1: 3
100 3  _anothing
       _zeverything
3 anothingzeverything
