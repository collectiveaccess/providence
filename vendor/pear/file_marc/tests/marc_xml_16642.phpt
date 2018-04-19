--TEST--
marc_xml_16642: Fix bug 16642: ensure tag and subfield values are returned as strings
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARCXML.php';
// Retrieve a set of MARC records from a file
$marc_file = new File_MARCXML($dir . '/' . 'onerecord.xml');
// Iterate through the retrieved records
while ($record = $marc_file->next()) {
   foreach ($record->getFields() as $tag => $subfields) {
       // Skip everything except for 650 fields
       if ($tag == '650') {
           print "Subject:";
           foreach ($subfields->getSubfields() as $code => $value) {
               print " $value";
           }
           print "\n";
       }
   }
}
?>
--EXPECT--
Subject: [a]: Arithmetic [x]: Juvenile poetry.
Subject: [a]: Children's poetry, American.
Subject: [a]: Arithmetic [x]: Poetry.
Subject: [a]: American poetry.
Subject: [a]: Visual perception.
