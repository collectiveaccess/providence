--TEST--
marc_lint_002: Tests check041() and check043() called separately
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
<?php include('tests/skipif_noispn.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

// Create test harness to allow direct calls to check methods:
class File_MARC_Lint_Test_Harness extends File_MARC_Lint
{
    public function check041($field)
    {
        return parent::check041($field);
    }

    public function check043($field)
    {
        return parent::check043($field);
    }

    // override warn method to echo instead of store in object:
    protected function warn($msg)
    {
        echo $msg . "\n";
    }
}

$marc_lint = new File_MARC_Lint_Test_Harness();

$field = new File_MARC_Data_Field(
    '041',
    array(
        new File_MARC_Subfield('a', 'end'),             // invalid
        new File_MARC_Subfield('a', 'span'),            // too long
        new File_MARC_Subfield('h', 'far')              // obsolete
    ),
    "0", ""
);
$marc_lint->check041($field);

$field = new File_MARC_Data_Field(
    '041',
    array(
        new File_MARC_Subfield('a', 'endorviwo'),       // invalid
        new File_MARC_Subfield('a', 'spanowpalasba')    // too long and invalid
    ),
    "1", ""
);
$marc_lint->check041($field);

$field = new File_MARC_Data_Field(
    '043',
    array(
        new File_MARC_Subfield('a', 'n-----'),          // 6 chars vs. 7
        new File_MARC_Subfield('a', 'n-us----'),        // 8 chars vs. 7
        new File_MARC_Subfield('a', 'n-ma-us'),         // invalid code
        new File_MARC_Subfield('a', 'e-ur-ai')          // obsolete code
    ),
    "", ""
);
$marc_lint->check043($field);

?>
--EXPECT--
041: Subfield _a, end (end), is not valid.
041: Subfield _a must be evenly divisible by 3 or exactly three characters if ind2 is not 7, (span).
041: Subfield _h, far, may be obsolete.
041: Subfield _a, endorviwo (end), is not valid.
041: Subfield _a, endorviwo (orv), is not valid.
041: Subfield _a, endorviwo (iwo), is not valid.
041: Subfield _a must be evenly divisible by 3 or exactly three characters if ind2 is not 7, (spanowpalasba).
043: Subfield _a must be exactly 7 characters, n-----
043: Subfield _a must be exactly 7 characters, n-us----
043: Subfield _a, n-ma-us, is not valid.
043: Subfield _a, e-ur-ai, may be obsolete.
