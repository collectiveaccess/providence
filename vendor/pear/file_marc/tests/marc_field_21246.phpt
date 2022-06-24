--TEST--
marc_field_21246: Delete multiple subfields
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

$field = new File_MARC_Data_Field(650, [
    new File_MARC_Subfield('9', 'test1'),
    new File_MARC_Subfield('9', 'test2'),
    new File_MARC_Subfield('0', 'test3'),
    new File_MARC_Subfield('9', 'test4'),
  ]
);
echo "--- Before: ---\n$field\n\n";
foreach ($field->getSubfields('9') as $subfield) {
  echo "Deleting subfield: $subfield\n";
  $field->deleteSubfield($subfield);
}
echo "\n--- After: ---\n$field\n\n";
?>
--EXPECT--
--- Before: ---
650    _9test1
       _9test2
       _0test3
       _9test4

Deleting subfield: [9]: test1
Deleting subfield: [9]: test2
Deleting subfield: [9]: test4

--- After: ---
650    _0test3
