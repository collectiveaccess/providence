--TEST--
marc_lint_003: Tests for field 880 and for subfield 6
--SKIPIF--
<?php include('skipif.inc'); ?>
<?php include('skipif_noispn.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
require 'File/MARC/Lint.php';
$marc_lint = new File_MARC_Lint();

$rec = new File_MARC_Record();
$rec->setLeader("00000nam  22002538a 4500");
$rec->appendField(
    new File_MARC_Control_Field(
        '001', 'ttt07000001 '
    )
);
$rec->appendField(
    new File_MARC_Control_Field(
        '003', 'TEST '
    )
);
$rec->appendField(
    new File_MARC_Control_Field(
        '008', '070520s2007    ilu           000 0 eng d'
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '040',
        array(
            new File_MARC_Subfield('a', 'TEST'),
            new File_MARC_Subfield('c', 'TEST')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '050',
        array(
            new File_MARC_Subfield('a', 'RZ999'),
            new File_MARC_Subfield('b', '.J66 2007')
        ),
        "", "4"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '082',
        array(
            new File_MARC_Subfield('a', '615.8/9'),
            new File_MARC_Subfield('2', '22')
        ),
        "0", "4"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '100',
        array(
            new File_MARC_Subfield('a', 'Jones, John')
        ),
        "1", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '245',
        array(
            new File_MARC_Subfield('6', '880-02'),
            new File_MARC_Subfield('a', 'Test 880.')
        ),
        "1", "0"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '260',
        array(
            new File_MARC_Subfield('a', 'Mount Morris, Ill. :'),
            new File_MARC_Subfield('b', "B. Baldus,"),
            new File_MARC_Subfield('c', '2007.')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '300',
        array(
            new File_MARC_Subfield('a', '1 v. ;'),
            new File_MARC_Subfield('c', '23 cm.')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '880',
        array(
            new File_MARC_Subfield('6', '245-02/$1'),
            new File_MARC_Subfield('a', '<Title in CJK script>.')
        ),
        "1", "0"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '880',
        array(
            new File_MARC_Subfield('6', '245-02/$1'),
            new File_MARC_Subfield('a', 'Illegal duplicate field.')
        ),
        "1", "0"
    )
);
$warnings = $marc_lint->checkRecord($rec);
foreach ($warnings as $warning) {
  print $warning . "\n";
}

?>
--EXPECT--
245: Field is not repeatable.
