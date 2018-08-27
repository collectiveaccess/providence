--TEST--
marc_lint_004: Tests check_245() called separately
--SKIPIF--
<?php include('skipif.inc'); ?>
<?php include('skipif_noispn.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
require 'File/MARC/Lint.php';

// Create test harness to allow direct calls to check methods:
class File_MARC_Lint_Test_Harness extends File_MARC_Lint
{
    public function check245($field)
    {
        return parent::check245($field);
    }

    // override warn method to echo instead of store in object:
    protected function warn($msg)
    {
        echo $msg . "\n";
    }
}

$marc_lint = new File_MARC_Lint_Test_Harness();

$testData = array(
    array(245, '0', '0', 'a', 'Subfield a.'),
    array(245, '0', '0', 'b', 'no subfield a.'),
    array(245, '0', '0', 'a', 'No period at end'),
    array(245, '0', '0', 'a', 'Other punctuation not followed by period!'),
    array(245, '0', '0', 'a', 'Other punctuation not followed by period?'),
    array(245, '0', '0', 'a', 'Precedes sub c', 'c', 'not preceded by space-slash.'),
    array(245, '0', '0', 'a', 'Precedes sub c/', 'c', 'not preceded by space-slash.'),
    array(245, '0', '0', 'a', 'Precedes sub c /', 'c', 'initials in sub c B. B.'),
    array(245, '0', '0', 'a', 'Precedes sub c /', 'c', 'initials in sub c B.B. (no warning).'),
    array(245, '0', '0', 'a', 'Precedes sub b', 'b', 'not preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b=', 'b', 'not preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b:', 'b', 'not preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b;', 'b', 'not preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b =', 'b', 'preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b :', 'b', 'preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub b ;', 'b', 'preceded by proper punctuation.'),
    array(245, '0', '0', 'a', 'Precedes sub h ', 'h', '[videorecording].'),
    array(245, '0', '0', 'a', 'Precedes sub h-- ', 'h', '[videorecording] :', 'b', 'with elipses dash before h.'),
    array(245, '0', '0', 'a', 'Precedes sub h-- ', 'h', 'videorecording :', 'b', 'without brackets around GMD.'),
    array(245, '0', '0', 'a', 'Precedes sub n.', 'n', 'Number 1.'),
    array(245, '0', '0', 'a', 'Precedes sub n', 'n', 'Number 2.'),
    array(245, '0', '0', 'a', 'Precedes sub n.', 'n', 'Number 3.', 'p', 'Sub n has period not comma.'),
    array(245, '0', '0', 'a', 'Precedes sub n.', 'n', 'Number 3,', 'p', 'Sub n has comma.'),
    array(245, '0', '0', 'a', 'Precedes sub p.', 'p', 'Sub a has period.'),
    array(245, '0', '0', 'a', 'Precedes sub p', 'p', 'Sub a has no period.'),
    array(245, '0', 'a', 'a', 'Invalid filing indicator.'),
    array(245, '0', '0', 'a', 'The article.'),
    array(245, '0', '4', 'a', 'The article.'),
    array(245, '0', '2', 'a', 'An article.'),
    array(245, '0', '0', 'a', "L'article."),
    array(245, '0', '2', 'a', 'A la mode.'),
    array(245, '0', '5', 'a', 'The "quoted article".'),
    array(245, '0', '5', 'a', 'The (parenthetical article).'),
    array(245, '0', '6', 'a', '(The) article in parentheses).'),
    array(245, '0', '9', 'a', "\"(The)\" 'article' in quotes and parentheses)."),
    array(245, '0', '5', 'a', '[The supplied title].')
);

foreach ($testData as $current) {
    $subfields = array();
    for ($i = 3; $i < count($current); $i+=2) {
        $subfields[] = new File_MARC_Subfield($current[$i], $current[$i+1]);
    }

    $field = new File_MARC_Data_Field(
        $current[0], $subfields, $current[1], $current[2]
    );
    $marc_lint->check245($field);
}

?>
--EXPECT--
245: Must have a subfield _a.
245: First subfield must be _a, but it is _b
245: Must end with . (period).
245: MARC21 allows ? or ! as final punctuation but LCRI 1.0C, Nov. 2003 (LCPS 1.7.1 for RDA records), requires period.
245: MARC21 allows ? or ! as final punctuation but LCRI 1.0C, Nov. 2003 (LCPS 1.7.1 for RDA records), requires period.
245: Subfield _c must be preceded by /
245: Subfield _c must be preceded by /
245: Subfield _c initials should not have a space.
245: Subfield _b should be preceded by space-colon, space-semicolon, or space-equals sign.
245: Subfield _b should be preceded by space-colon, space-semicolon, or space-equals sign.
245: Subfield _b should be preceded by space-colon, space-semicolon, or space-equals sign.
245: Subfield _b should be preceded by space-colon, space-semicolon, or space-equals sign.
245: Subfield _h should not be preceded by space.
245: Subfield _h must have matching square brackets, videorecording :.
245: Subfield _n must be preceded by . (period).
245: Subfield _p must be preceded by , (comma) when it follows subfield _n.
245: Subfield _p must be preceded by . (period) when it follows a subfield other than _n.
245: Non-filing indicator is non-numeric
245: First word, the, may be an article, check 2nd indicator (0).
245: First word, an, may be an article, check 2nd indicator (2).
245: First word, l, may be an article, check 2nd indicator (0).
245: First word, a, does not appear to be an article, check 2nd indicator (2).
