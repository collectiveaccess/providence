--TEST--
marc_lint_005: Tests check_020() called separately
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
    public function check020($field)
    {
        return parent::check020($field);
    }

    // override warn method to echo instead of store in object:
    protected function warn($msg)
    {
        echo $msg . "\n";
    }
}

$marc_lint = new File_MARC_Lint_Test_Harness();

$testData = array(
    array('a' => "154879473"), //too few digits
    array('a' => "1548794743"), //invalid checksum
    array('a' => "15487947443"), //11 digits
    array('a' => "15487947443324"), //14 digits
    array('a' => "9781548794743"), //13 digit valid
    array('a' => "9781548794745"), //13 digit invalid
    array('a' => "1548794740 (10 : good checksum)"), //10 digit valid with qualifier
    array('a' => "1548794745 (10 : bad checksum)"), //10 digit invalid with qualifier
    array('a' => "1-54879-474-0 (hyphens and good checksum)"), //10 digit invalid with hyphens and qualifier
    array('a' => "1-54879-474-5 (hyphens and bad checksum)"), //10 digit invalid with hyphens and qualifier
    array('a' => "1548794740(10 : unspaced qualifier)"), //10 valid without space before qualifier
    array('a' => "1548794745(10 : unspaced qualifier : bad checksum)"), //10 invalid without space before qualifier
    array('z' => "1548794743"), //subfield z
);

foreach ($testData as $current) {
    $subfields = array();
    foreach ($current as $key => $value) {
        $subfields[] = new File_MARC_Subfield($key, $value);
    }
    $field = new File_MARC_Data_Field('020', $subfields, '', '');
    $marc_lint->check020($field);
}

?>
--EXPECT--
020: Subfield a has the wrong number of digits, 154879473.
020: Subfield a has bad checksum, 1548794743.
020: Subfield a has the wrong number of digits, 15487947443.
020: Subfield a has the wrong number of digits, 15487947443324.
020: Subfield a has bad checksum (13 digit), 9781548794745.
020: Subfield a has bad checksum, 1548794745 (10 : bad checksum).
020: Subfield a may have invalid characters.
020: Subfield a may have invalid characters.
020: Subfield a has bad checksum, 1-54879-474-5 (hyphens and bad checksum).
020: Subfield a qualifier must be preceded by space, 1548794740(10 : unspaced qualifier).
020: Subfield a qualifier must be preceded by space, 1548794745(10 : unspaced qualifier : bad checksum).
020: Subfield a has bad checksum, 1548794745(10 : unspaced qualifier : bad checksum).
