--TEST--
marc_023: test extended Record interface
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

$marc_file = new File_MARC($dir . '/' . 'example.mrc', File_MARC::SOURCE_FILE, MyRecord::class);

$rec = $marc_file->next();
print get_class($rec) . "\n";
print $rec->myNewMethod() . "\n";

?>
--EXPECT--
MyRecord
NB
