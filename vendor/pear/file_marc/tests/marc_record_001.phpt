--TEST--
marc_record_001: create a MARC record from scratch
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc = new File_MARC_Record();

$marc->appendField(new File_MARC_Data_Field('245', array(
        new File_MARC_Subfield('a', 'Main title: '),
        new File_MARC_Subfield('b', 'subtitle'),
        new File_MARC_Subfield('c', 'author')
    ), null, null
));

print $marc;

?>
--EXPECT--
LDR                         
245    _aMain title: 
       _bsubtitle
       _cauthor
