--TEST--
marc_lint_001: Full test of Lint suite
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
<?php include('tests/skipif_noispn.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_lint = new File_MARC_Lint();

print "Test records in camel.mrc\n";
$marc_file = new File_MARC($dir . '/' . 'camel.mrc');
while ($marc_record = $marc_file->next()) {
  $warnings = $marc_lint->checkRecord($marc_record);
  foreach ($warnings as $warning) {
    print $warning . "\n";
  }
}

print "\nTest from a constructed record\n";
$rec = new File_MARC_Record();
$rec->setLeader("00000nam  22002538a 4500");
$rec->appendField(
    new File_MARC_Data_Field(
        '041',
        array(
            new File_MARC_Subfield('a', 'end'),
            new File_MARC_Subfield('a', 'fren')
        ),
        "0", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '043',
        array(
            new File_MARC_Subfield('a', 'n-us-pn')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '082',
        array(
            new File_MARC_Subfield('a', '005.13/3'),
            // typo 'R' for 'W' and missing 'b' subfield
            new File_MARC_Subfield('R', 'all'),
            new File_MARC_Subfield('2', '21')
        ),
        "0", "4"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '082',
        array(
            new File_MARC_Subfield('a', '005.13'),
            new File_MARC_Subfield('b', 'Wall'),
            new File_MARC_Subfield('2', '14')
        ),
        "1", "4"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '100',
        array(
            new File_MARC_Subfield('a', 'Wall, Larry')
        ),
        "1", "4"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '110',
        array(
            new File_MARC_Subfield('a', "O'Reilly & Associates.")
        ),
        "1", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '245',
        array(
            new File_MARC_Subfield('a', 'Programming Perl / '),
            new File_MARC_Subfield('a', 'Big Book of Perl /'),
            new File_MARC_Subfield('c', 'Larry Wall, Tom Christiansen & Jon Orwant.')
        ),
        "9", "0"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '250',
        array(
            new File_MARC_Subfield('a', '3rd ed.')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '250',
        array(
            new File_MARC_Subfield('a', '3rd ed.')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '260',
        array(
            new File_MARC_Subfield('a', 'Cambridge, Mass. : '),
            new File_MARC_Subfield('b', "O'Reilly, "),
            new File_MARC_Subfield('r', '2000.')
        ),
        "", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '590',
        array(
            new File_MARC_Subfield('a', 'Personally signed by Larry.')
        ),
        "4", ""
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '650',
        array(
            new File_MARC_Subfield('a', 'Perl (Computer program language)'),
            new File_MARC_Subfield('0', '(DLC)sh 95010633')
        ),
        "", "0"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '856',
        array(
            new File_MARC_Subfield('u', 'http://www.perl.com/')
        ),
        "4", "3"
    )
);
$rec->appendField(
    new File_MARC_Data_Field(
        '886',
        array(
            new File_MARC_Subfield('4', 'Some foreign thing'),
            new File_MARC_Subfield('q', 'Another foreign thing')
        ),
        "0", ""
    )
);
$warnings = $marc_lint->checkRecord($rec);
foreach ($warnings as $warning) {
  print $warning . "\n";
}

?>
--EXPECT--
Test records in camel.mrc
100: Indicator 1 must be 0, 1 or 3 but it's "2"
007: Subfields are not allowed in fields lower than 010

Test from a constructed record
1XX: Only one 1XX tag is allowed, but I found 2 of them.
041: Subfield _a, end (end), is not valid.
041: Subfield _a must be evenly divisible by 3 or exactly three characters if ind2 is not 7, (fren).
043: Subfield _a, n-us-pn, is not valid.
082: Subfield _R is not allowed.
100: Indicator 2 must be blank but it's "4"
245: Indicator 1 must be 0 or 1 but it's "9"
245: Subfield _a is not repeatable.
260: Subfield _r is not allowed.
856: Indicator 2 must be blank, 0, 1, 2 or 8 but it's "3"
