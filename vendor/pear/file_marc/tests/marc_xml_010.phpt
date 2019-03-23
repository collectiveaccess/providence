--TEST--
marc_xml_010: iterate and pretty print a MARC record
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARCXML.php';
$marc_file = new File_MARCXML($dir . '/' . 'repeated_subfields.xml');

while ($marc_record = $marc_file->next()) {
    $subject = $marc_record->getFields('650');
    if (!$subject) {
        next;
    }
    foreach ($subject as $key => $line) {
        if ($line->getSubfield('a')) {
            // get subject
            $array[$key]['subject'] = $line->getSubfield('a')->getData();
            if ($line->getSubfield('b')) {
                $array[$key]['subsubject'] = $line->getSubfield('b')->getData();
            }
            // get subject hierarchy level
            if ($name = $line->getSubfields('x')) {
                foreach ($name as $value) {
                    $array[$key]['level'][] = $value->getData();
                }
            } // end if subfield x
        } // end if subfield a
    } // end foreach
    var_dump($array);
}
--EXPECT--
array(5) {
  [0]=>
  array(2) {
    ["subject"]=>
    string(10) "Arithmetic"
    ["level"]=>
    array(1) {
      [0]=>
      string(16) "Juvenile poetry."
    }
  }
  [1]=>
  array(2) {
    ["subject"]=>
    string(27) "Children's poetry, American"
    ["level"]=>
    array(1) {
      [0]=>
      string(7) "Oregon."
    }
  }
  [2]=>
  array(3) {
    ["subject"]=>
    string(10) "Arithmetic"
    ["subsubject"]=>
    string(11) "Really hard"
    ["level"]=>
    array(2) {
      [0]=>
      string(6) "Poetry"
      [1]=>
      string(13) "Unbelievable."
    }
  }
  [3]=>
  array(1) {
    ["subject"]=>
    string(16) "American poetry."
  }
  [4]=>
  array(1) {
    ["subject"]=>
    string(18) "Visual perception."
  }
}
