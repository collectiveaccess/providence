--TEST--
marc_list_001: Check File_MARC_List methods
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

$list = new File_MARC_List();

// Create fields
$field1 = new File_MARC_Control_Field( '001', '00001' );
$field2 = new File_MARC_Control_Field( '002', '00002' );
$field3 = new File_MARC_Control_Field( '003', '00003' );
$field4 = new File_MARC_Control_Field( '004', '00004' );

// Check appendNode
$list->appendNode( $field2 );
$list->appendNode( $field4 );
foreach ( $list as $el ) {
    echo $el->getPosition() . ': ' . $el . "\n";
}
echo $list->count() . " nodes\n";

// Check prependNode
echo "---\n";
$list->prependNode( $field1 );
foreach ( $list as $el ) {
    echo $el->getPosition() . ': ' . $el . "\n";
}
echo $list->count() . " nodes\n";

// Check insertNode
echo "---\n";
$list->insertNode( $field3, $field2 );
foreach ( $list as $el ) {
    echo $el->getPosition() . ': ' . $el . "\n";
}
echo $list->count() . " nodes\n";

// Check deleteNode
echo "---\n";
$list->deleteNode( $field2 );
foreach ( $list as $el ) {
    echo $el->getPosition() . ': ' . $el . "\n";
}
echo $list->count() . " nodes\n";

// Check key method
echo "---\n";
$list->rewind();
echo 'Key (field): ' . $list->key() . "\n";

$subfield = new File_MARC_Subfield( 'a', 'test' );
$list->prependNode( $subfield );
$list->rewind();
echo 'Key (subfield): ' . $list->key(). "\n";
?>
--EXPECT--
0: 002     00002
1: 004     00004
2 nodes
---
0: 001     00001
1: 002     00002
2: 004     00004
3 nodes
---
0: 001     00001
1: 002     00002
2: 003     00003
3: 004     00004
4 nodes
---
0: 001     00001
1: 003     00003
2: 004     00004
3 nodes
---
Key (field): 001
Key (subfield): a
