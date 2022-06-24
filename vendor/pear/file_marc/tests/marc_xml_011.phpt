--TEST--
marc_xml_010: iterate and pretty print a MARC record
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

class MyRecord extends File_MARC_Record {
  public function myNewMethod() {
    return $this->getField('040')->getSubfield('a')->getData();
  }
}

$marc_file = new File_MARCXML($dir . '/' . 'repeated_subfields.xml', File_MARCXML::SOURCE_FILE, '', false, MyRecord::class);

$rec = $marc_file->next();
print get_class($rec) . "\n";
print $rec->myNewMethod() . "\n";

--EXPECT--
MyRecord
DLC
