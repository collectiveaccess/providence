<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker: */

/**
 * Lint for MARC records
 *
 * This module is adapted from the MARC::Lint CPAN module for Perl, maintained by
 * Bryan Baldus <eijabb@cpan.org> and available at http://search.cpan.org/~eijabb/
 *
 * Current MARC::Lint version used as basis for this module: 1.47
 *
 * PHP version 5
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  File_Formats
 * @package   File_MARC
 * @author    Demian Katz <demian.katz@villanova.edu>
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2003-2013 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id: Record.php 308146 2011-02-08 20:36:20Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */
require_once 'File/MARC/Lint/CodeData.php';
require_once 'Validate/ISPN.php';

// {{{ class File_MARC_Lint
/**
 * Class for testing validity of MARC records against MARC21 standard.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Lint
{

    // {{{ properties
    /**
     * Rules used for testing records
     * @var array
     */
    protected $rules;

    /**
     * A File_MARC_Lint_CodeData object for validating codes
     * @var File_MARC_Lint_CodeData
     */
    protected $data;

    /**
     * Warnings generated during analysis
     * @var array
     */
    protected $warnings = array();

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Start function
     *
     * Set up rules for testing MARC records.
     *
     * @return true
     */
    public function __construct()
    {
        $this->parseRules();
        $this->data = new File_MARC_Lint_CodeData();
    }
    // }}}

    // {{{ getWarnings()
    /**
     * Check the provided MARC record and return an array of warning messages.
     *
     * @param File_MARC_Record $marc Record to check
     *
     * @return array
     */
    public function checkRecord($marc)
    {
        // Reset warnings:
        $this->warnings = array();

        // Fail if we didn't get a valid object:
        if (!is_a($marc, 'File_MARC_Record')) {
            $this->warn('Must pass a File_MARC_Record object to checkRecord');
        } else {
            $this->checkDuplicate1xx($marc);
            $this->checkMissing245($marc);
            $this->standardFieldChecks($marc);
        }

        return $this->warnings;
    }
    // }}}

    // {{{ warn()
    /**
     * Add a warning.
     *
     * @param string $warning Warning to add
     *
     * @return void
     */
    protected function warn($warning)
    {
        $this->warnings[] = $warning;
    }
    // }}}

    // {{{ checkDuplicate1xx()
    /**
     * Check for multiple 1xx fields.
     *
     * @param File_MARC_Record $marc Record to check
     *
     * @return void
     */
    protected function checkDuplicate1xx($marc)
    {
        $result = $marc->getFields('1[0-9][0-9]', true);
        $count = count($result);
        if ($count > 1) {
            $this->warn(
                "1XX: Only one 1XX tag is allowed, but I found $count of them."
            );
        }
    }
    // }}}

    // {{{ checkMissing245()
    /**
     * Check for missing 245 field.
     *
     * @param File_MARC_Record $marc Record to check
     *
     * @return void
     */
    protected function checkMissing245($marc)
    {
        $result = $marc->getFields('245');
        if (count($result) == 0) {
            $this->warn('245: No 245 tag.');
        }
    }
    // }}}

    // {{{ standardFieldChecks()
    /**
     * Check all fields against the standard rules encoded in the class.
     *
     * @param File_MARC_Record $marc Record to check
     *
     * @return void
     */
    protected function standardFieldChecks($marc)
    {
        $fieldsSeen = array();
        foreach ($marc->getFields() as $current) {
            $tagNo = $current->getTag();
            // if 880 field, inherit rules from tagno in subfield _6
            if ($tagNo == 880) {
                if ($sub6 = $current->getSubfield(6)) {
                    $tagNo = substr($sub6->getData(), 0, 3);
                    $tagrules = isset($this->rules[$tagNo])
                        ? $this->rules[$tagNo] : null;
                    // 880 is repeatable, but its linked field may not be
                    if (isset($tagrules['repeatable'])
                        && $tagrules['repeatable'] == 'NR'
                        && isset($fieldsSeen['880.'.$tagNo])
                    ) {
                        $this->warn("$tagNo: Field is not repeatable.");
                    }
                    $fieldsSeen['880.'.$tagNo] = isset($fieldsSeen['880.'.$tagNo])
                        ? $fieldsSeen['880.'.$tagNo] + 1 : 1;
                } else {
                    $this->warn("880: No subfield 6.");
                    $tagRules = null;
                }
            } else {
                // Default case -- not an 880 field:
                $tagrules = isset($this->rules[$tagNo])
                    ? $this->rules[$tagNo] : null;
                if (isset($tagrules['repeatable']) && $tagrules['repeatable'] == 'NR'
                    && isset($fieldsSeen[$tagNo])
                ) {
                    $this->warn("$tagNo: Field is not repeatable.");
                }
                $fieldsSeen[$tagNo] = isset($fieldsSeen[$tagNo])
                    ? $fieldsSeen[$tagNo] + 1 : 1;
            }

            // Treat data fields differently from control fields:
            if (intval(ltrim($tagNo, '0')) >= 10) {
                if (!empty($tagrules)) {
                    $this->checkIndicators($tagNo, $current, $tagrules);
                    $this->checkSubfields($tagNo, $current, $tagrules);
                }
            } else {
                // Control field:
                if (strstr($current->toRaw(), chr(hexdec('1F')))) {
                    $this->warn(
                        "$tagNo: Subfields are not allowed in fields lower than 010"
                    );
                }
            }

            // Check to see if a checkxxx() function exists, and call it on the
            // field if it does
            $method = 'check' . $tagNo;
            if (method_exists($this, $method)) {
                $this->$method($current);
            }
        }
    }
    // }}}

    // {{{ checkIndicators()
    /**
     * Check the indicators for the provided field.
     *
     * @param string          $tagNo Tag number being checked
     * @param File_MARC_Field $field Field to check
     * @param array           $rules Rules to use for checking
     *
     * @return void
     */
    protected function checkIndicators($tagNo, $field, $rules)
    {
        for ($i = 1; $i <= 2; $i++) {
            $ind = $field->getIndicator($i);
            if ($ind === false || $ind == ' ') {
                $ind = 'b';
            }
            if (!strstr($rules['ind' . $i]['values'], $ind)) {
                // Make indicator blank value human-readable for message:
                if ($ind == 'b') {
                    $ind = 'blank';
                }
                $this->warn(
                    "$tagNo: Indicator $i must be "
                    . $rules['ind' . $i]['hr_values']
                    . " but it's \"$ind\""
                );
            }
        }
    }
    // }}}

    // {{{ checkSubfields()
    /**
     * Check the subfields for the provided field.
     *
     * @param string          $tagNo Tag number being checked
     * @param File_MARC_Field $field Field to check
     * @param array           $rules Rules to use for checking
     *
     * @return void
     */
    protected function checkSubfields($tagNo, $field, $rules)
    {
        $subSeen = array();

        foreach ($field->getSubfields() as $current) {
            $code = $current->getCode();
            $data = $current->getData();

            $subrules = isset($rules['sub' . $code])
                ? $rules['sub' . $code] : null;

            if (empty($subrules)) {
                $this->warn("$tagNo: Subfield _$code is not allowed.");
            } elseif ($subrules['repeatable'] == 'NR' && isset($subSeen[$code])) {
                $this->warn("$tagNo: Subfield _$code is not repeatable.");
            }

            if (preg_match('/\r|\t|\n/', $data)) {
                $this->warn(
                    "$tagNo: Subfield _$code has an invalid control character"
                );
            }

            $subSeen[$code] = isset($subSeen[$code]) ? $subSeen[$code]++ : 1;
        }
    }
    // }}}

    // {{{ check020()
    /**
     * Looks at 020$a and reports errors if the check digit is wrong.
     * Looks at 020$z and validates number if hyphens are present.
     *
     * @param File_MARC_Field $field Field to check
     *
     * @return void
     */
    protected function check020($field)
    {
        foreach ($field->getSubfields() as $current) {
            $data = $current->getData();
            // remove any hyphens
            $isbn = str_replace('-', '', $data);
            // remove nondigits
            $isbn = preg_replace('/^\D*(\d{9,12}[X\d])\b.*$/', '$1', $isbn);

            if ($current->getCode() == 'a') {
                if ((substr($data, 0, strlen($isbn)) != $isbn)) {
                    $this->warn("020: Subfield a may have invalid characters.");
                }

                // report error if no space precedes a qualifier in subfield a
                if (preg_match('/\(/', $data) && !preg_match('/[X0-9] \(/', $data)) {
                    $this->warn(
                        "020: Subfield a qualifier must be preceded by space, $data."
                    );
                }

                // report error if unable to find 10-13 digit string of digits in
                // subfield 'a'
                if (!preg_match('/(?:^\d{10}$)|(?:^\d{13}$)|(?:^\d{9}X$)/', $isbn)) {
                    $this->warn(
                        "020: Subfield a has the wrong number of digits, $data."
                    );
                } else {
                    if (strlen($isbn) == 10) {
                        if (!Validate_ISPN::isbn10($isbn)) {
                            $this->warn("020: Subfield a has bad checksum, $data.");
                        }
                    } else if (strlen($isbn) == 13) {
                        if (!Validate_ISPN::isbn13($isbn)) {
                            $this->warn(
                                "020: Subfield a has bad checksum (13 digit), $data."
                            );
                        }
                    }
                }
            } else if ($current->getCode() == 'z') {
                // look for valid isbn in 020$z
                if (preg_match('/^ISBN/', $data)
                    || preg_match('/^\d*\-\d+/', $data)
                ) {
                    // ##################################################
                    // ## Turned on for now--Comment to unimplement  ####
                    // ##################################################
                    if ((strlen($isbn) == 10)
                        && (Validate_ISPN::isbn10($isbn) == 1)
                    ) {
                        $this->warn("020:  Subfield z is numerically valid.");
                    }
                }
            }
        }
    }
    // }}}

    // {{{ check041()
    /**
     * Warns if subfields are not evenly divisible by 3 unless second indicator is 7
     * (future implementation would ensure that each subfield is exactly 3 characters
     * unless ind2 is 7--since subfields are now repeatable. This is not implemented
     * here due to the large number of records needing to be corrected.). Validates
     * against the MARC Code List for Languages (<http://www.loc.gov/marc/>).
     *
     * @param File_MARC_Field $field Field to check
     *
     * @return void
     */
    protected function check041($field)
    {
        // warn if length of each subfield is not divisible by 3 unless ind2 is 7
        if ($field->getIndicator(2) != '7') {
            foreach ($field->getSubfields() as $sub) {
                $code = $sub->getCode();
                $data = $sub->getData();
                if (strlen($data) % 3 != 0) {
                    $this->warn(
                        "041: Subfield _$code must be evenly divisible by 3 or "
                        . "exactly three characters if ind2 is not 7, ($data)."
                    );
                } else {
                    for ($i = 0; $i < strlen($data); $i += 3) {
                        $chk = substr($data, $i, 3);
                        if (!in_array($chk, $this->data->languageCodes)) {
                            $obs = $this->data->obsoleteLanguageCodes;
                            if (in_array($chk, $obs)) {
                                $this->warn(
                                    "041: Subfield _$code, $data, may be obsolete."
                                );
                            } else {
                                $this->warn(
                                    "041: Subfield _$code, $data ($chk),"
                                    . " is not valid."
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    // }}}

    // {{{ check043()
    /**
     * Warns if each subfield a is not exactly 7 characters. Validates each code
     * against the MARC code list for Geographic Areas (<http://www.loc.gov/marc/>).
     *
     * @param File_MARC_Field $field Field to check
     *
     * @return void
     */
    protected function check043($field)
    {
        foreach ($field->getSubfields('a') as $suba) {
            // warn if length of subfield a is not exactly 7
            $data = $suba->getData();
            if (strlen($data) != 7) {
                $this->warn("043: Subfield _a must be exactly 7 characters, $data");
            } else if (!in_array($data, $this->data->geogAreaCodes)) {
                if (in_array($data, $this->data->obsoleteGeogAreaCodes)) {
                    $this->warn("043: Subfield _a, $data, may be obsolete.");
                } else {
                    $this->warn("043: Subfield _a, $data, is not valid.");
                }
            }
        }
    }
    // }}}

    // {{{ check245()
    /**
     * -Makes sure $a exists (and is first subfield).
     * -Warns if last character of field is not a period
     * --Follows LCRI 1.0C, Nov. 2003 rather than MARC21 rule
     * -Verifies that $c is preceded by / (space-/)
     * -Verifies that initials in $c are not spaced
     * -Verifies that $b is preceded by :;= (space-colon, space-semicolon,
     *  space-equals)
     * -Verifies that $h is not preceded by space unless it is dash-space
     * -Verifies that data of $h is enclosed in square brackets
     * -Verifies that $n is preceded by . (period)
     * --As part of that, looks for no-space period, or dash-space-period
     *  (for replaced elipses)
     * -Verifies that $p is preceded by , (no-space-comma) when following $n and
     *  . (period) when following other subfields.
     * -Performs rudimentary article check of 245 2nd indicator vs. 1st word of
     *  245$a (for manual verification).
     *
     * Article checking is done by internal checkArticle method, which should work
     * for 130, 240, 245, 440, 630, 730, and 830.
     *
     * @param File_MARC_Field $field Field to check
     *
     * @return void
     */
    protected function check245($field)
    {
        if (count($field->getSubfields('a')) == 0) {
            $this->warn("245: Must have a subfield _a.");
        }

        // Convert subfields to array and set flags indicating which subfields are
        // present while we're at it.
        $tmp = $field->getSubfields();
        $hasSubfields = $subfields = array();
        foreach ($tmp as $current) {
            $subfields[] = $current;
            $hasSubfields[$current->getCode()] = true;
        }

        // 245 must end in period (may want to make this less restrictive by allowing
        // trailing spaces)
        // do 2 checks--for final punctuation (MARC21 rule), and for period
        // (LCRI 1.0C, Nov. 2003)
        $lastChar = substr($subfields[count($subfields)-1]->getData(), -1);
        if (!in_array($lastChar, array('.', '?', '!'))) {
            $this->warn("245: Must end with . (period).");
        } else if ($lastChar != '.') {
            $this->warn(
                "245: MARC21 allows ? or ! as final punctuation but LCRI 1.0C, Nov."
                . " 2003 (LCPS 1.7.1 for RDA records), requires period."
            );
        }

        // Check for first subfield
        // subfield a should be first subfield (or 2nd if subfield '6' is present)
        if (isset($hasSubfields['6'])) {
            // make sure there are at least 2 subfields
            if (count($subfields) < 2) {
                $this->warn("245: May have too few subfields.");
            } else {
                $first = $subfields[0]->getCode();
                $second = $subfields[1]->getCode();
                if ($first != '6') {
                    $this->warn("245: First subfield must be _6, but it is $first");
                }
                if ($second != 'a') {
                    $this->warn(
                        "245: First subfield after subfield _6 must be _a, but it "
                        . "is _$second"
                    );
                }
            }
        } else {
            // 1st subfield must be 'a'
            $first = $subfields[0]->getCode();
            if ($first != 'a') {
                $this->warn("245: First subfield must be _a, but it is _$first");
            }
        }
        // End check for first subfield

        // subfield c, if present, must be preceded by /
        // also look for space between initials
        if (isset($hasSubfields['c'])) {
            foreach ($subfields as $i => $current) {
                // 245 subfield c must be preceded by / (space-/)
                if ($current->getCode() == 'c') {
                    if ($i > 0
                        && !preg_match('/\s\/$/', $subfields[$i-1]->getData())
                    ) {
                        $this->warn("245: Subfield _c must be preceded by /");
                    }
                    // 245 subfield c initials should not have space
                    if (preg_match('/\b\w\. \b\w\./', $current->getData())) {
                        $this->warn(
                            "245: Subfield _c initials should not have a space."
                        );
                    }
                    break;
                }
            }
        }

        // each subfield b, if present, should be preceded by :;= (colon, semicolon,
        // or equals sign)
        if (isset($hasSubfields['b'])) {
            // 245 subfield b should be preceded by space-:;= (colon, semicolon, or
            // equals sign)
            foreach ($subfields as $i => $current) {
                if ($current->getCode() == 'b' && $i > 0
                    && !preg_match('/ [:;=]$/', $subfields[$i-1]->getData())
                ) {
                    $this->warn(
                        "245: Subfield _b should be preceded by space-colon, "
                        . "space-semicolon, or space-equals sign."
                    );
                }
            }
        }

        // each subfield h, if present, should be preceded by non-space
        if (isset($hasSubfields['h'])) {
            // 245 subfield h should not be preceded by space
            foreach ($subfields as $i => $current) {
                // report error if subfield 'h' is preceded by space (unless
                // dash-space)
                if ($current->getCode() == 'h') {
                    $prev = $subfields[$i-1]->getData();
                    if ($i > 0 && !preg_match('/(\S$)|(\-\- $)/', $prev)) {
                        $this->warn(
                            "245: Subfield _h should not be preceded by space."
                        );
                    }
                    // report error if subfield 'h' does not start with open square
                    // bracket with a matching close bracket; could have check
                    // against list of valid values here
                    $data = $current->getData();
                    if (!preg_match('/^\[\w*\s*\w*\]/', $data)) {
                        $this->warn(
                            "245: Subfield _h must have matching square brackets,"
                            . " $data."
                        );
                    }
                }
            }
        }

        // each subfield n, if present, must be preceded by . (period)
        if (isset($hasSubfields['n'])) {
            // 245 subfield n must be preceded by . (period)
            foreach ($subfields as $i => $current) {
                // report error if subfield 'n' is not preceded by non-space-period
                // or dash-space-period
                if ($current->getCode() == 'n' && $i > 0) {
                    $prev = $subfields[$i-1]->getData();
                    if (!preg_match('/(\S\.$)|(\-\- \.$)/', $prev)) {
                        $this->warn(
                            "245: Subfield _n must be preceded by . (period)."
                        );
                    }
                }
            }
        }

        // each subfield p, if present, must be preceded by a , (no-space-comma)
        // if it follows subfield n, or by . (no-space-period or
        // dash-space-period) following other subfields
        if (isset($hasSubfields['p'])) {
            // 245 subfield p must be preceded by . (period) or , (comma)
            foreach ($subfields as $i => $current) {
                if ($current->getCode() == 'p' && $i > 0) {
                    $prev = $subfields[$i-1];
                    // case for subfield 'n' being field before this one (allows
                    // dash-space-comma)
                    if ($prev->getCode() == 'n'
                        && !preg_match('/(\S,$)|(\-\- ,$)/', $prev->getData())
                    ) {
                        $this->warn(
                            "245: Subfield _p must be preceded by , (comma) "
                            . "when it follows subfield _n."
                        );
                    } else if ($prev->getCode() != 'n'
                        && !preg_match('/(\S\.$)|(\-\- \.$)/', $prev->getData())
                    ) {
                        $this->warn(
                            "245: Subfield _p must be preceded by . (period)"
                            . " when it follows a subfield other than _n."
                        );
                    }
                }
            }
        }

        // check for invalid 2nd indicator
        $this->checkArticle($field);
    }
    // }}}

    // {{{ checkArticle()
    /**
     * Check of articles is based on code from Ian Hamilton. This version is more
     * limited in that it focuses on English, Spanish, French, Italian and German
     * articles. Certain possible articles have been removed if they are valid
     * English non-articles. This version also disregards 008_language/041 codes
     * and just uses the list of articles to provide warnings/suggestions.
     *
     * source for articles = <http://www.loc.gov/marc/bibliographic/bdapp-e.html>
     *
     * Should work with fields 130, 240, 245, 440, 630, 730, and 830. Reports error
     * if another field is passed in.
     *
     * @param File_MARC_Field $field Field to check
     *
     * @return void
     */
    protected function checkArticle($field)
    {
        // add articles here as needed
        // Some omitted due to similarity with valid words (e.g. the German 'die').
        static $article = array(
            'a' => 'eng glg hun por',
            'an' => 'eng',
            'das' => 'ger',
            'dem' => 'ger',
            'der' => 'ger',
            'ein' => 'ger',
            'eine' => 'ger',
            'einem' => 'ger',
            'einen' => 'ger',
            'einer' => 'ger',
            'eines' => 'ger',
            'el' => 'spa',
            'en' => 'cat dan nor swe',
            'gl' => 'ita',
            'gli' => 'ita',
            'il' => 'ita mlt',
            'l' => 'cat fre ita mlt',
            'la' => 'cat fre ita spa',
            'las' => 'spa',
            'le' => 'fre ita',
            'les' => 'cat fre',
            'lo' => 'ita spa',
            'los' => 'spa',
            'os' => 'por',
            'the' => 'eng',
            'um' => 'por',
            'uma' => 'por',
            'un' => 'cat spa fre ita',
            'una' => 'cat spa ita',
            'une' => 'fre',
            'uno' => 'ita',
        );

        // add exceptions here as needed
        // may want to make keys lowercase
        static $exceptions = array(
            'A & E',
            'A & ',
            'A-',
            'A+',
            'A is ',
            'A isn\'t ',
            'A l\'',
            'A la ',
            'A posteriori',
            'A priori',
            'A to ',
            'El Nino',
            'El Salvador',
            'L is ',
            'L-',
            'La Salle',
            'Las Vegas',
            'Lo mein',
            'Los Alamos',
            'Los Angeles',
        );

        // get tagno to determine which indicator to check and for reporting
        $tagNo = $field->getTag();
        // retrieve tagno from subfield 6 if 880 field
        if ($tagNo == '880' && ($sub6 = $field->getSubfield('6'))) {
            $tagNo = substr($sub6->getData(), 0, 3);
        }

        // $ind holds nonfiling character indicator value
        $ind = '';
        // $first_or_second holds which indicator is for nonfiling char value
        $first_or_second = '';
        if (in_array($tagNo, array(130, 630, 730))) {
            $ind = $field->getIndicator(1);
            $first_or_second = '1st';
        } else if (in_array($tagNo, array(240, 245, 440, 830))) {
            $ind = $field->getIndicator(2);
            $first_or_second = '2nd';
        } else {
            $this->warn(
                'Internal error: ' . $tagNo
                . " is not a valid field for article checking\n"
            );
            return;
        }

        if (!is_numeric($ind)) {
            $this->warn($tagNo . ": Non-filing indicator is non-numeric");
            return;
        }

        // get subfield 'a' of the title field
        $titleField = $field->getSubfield('a');
        $title = $titleField ? $titleField->getData() : '';

        $char1_notalphanum = 0;
        // check for apostrophe, quote, bracket,  or parenthesis, before first word
        // remove if found and add to non-word counter
        while (preg_match('/^["\'\[\(*]/', $title)) {
            $char1_notalphanum++;
            $title = preg_replace('/^["\'\[\(*]/', '', $title);
        }
        // split title into first word + rest on space, parens, bracket, apostrophe,
        // quote, or hyphen
        preg_match('/^([^ \(\)\[\]\'"\-]+)([ \(\)\[\]\'"\-])?(.*)/i', $title, $hits);
        $firstword = isset($hits[1]) ? $hits[1] : '';
        $separator = isset($hits[2]) ? $hits[2] : '';
        $etc = isset($hits[3]) ? $hits[3] : '';

        // get length of first word plus the number of chars removed above plus one
        // for the separator
        $nonfilingchars = strlen($firstword) + $char1_notalphanum + 1;

        // check to see if first word is an exception
        $isan_exception = false;
        foreach ($exceptions as $current) {
            if (substr($title, 0, strlen($current)) == $current) {
                $isan_exception = true;
                break;
            }
        }

        // lowercase chars of $firstword for comparison with article list
        $firstword = strtolower($firstword);

        // see if first word is in the list of articles and not an exception
        $isan_article = !$isan_exception && isset($article[$firstword]);

        // if article then $nonfilingchars should match $ind
        if ($isan_article) {
            // account for quotes, apostrophes, parens, or brackets before 2nd word
            if (strlen($separator) && preg_match('/^[ \(\)\[\]\'"\-]+/', $etc)) {
                while (preg_match('/^[ "\'\[\]\(\)*]/', $etc)) {
                    $nonfilingchars++;
                    $etc = preg_replace('/^[ "\'\[\]\(\)*]/', '', $etc);
                }
            }
            if ($nonfilingchars != $ind) {
                $this->warn(
                    $tagNo . ": First word, $firstword, may be an article, check "
                    . "$first_or_second indicator ($ind)."
                );
            }
        } else {
            // not an article so warn if $ind is not 0
            if ($ind != '0') {
                $this->warn(
                    $tagNo . ": First word, $firstword, does not appear to be an "
                    . "article, check $first_or_second indicator ($ind)."
                );
            }
        }
    }
    // }}}

    // {{{ parseRules()
    /**
     * Support method for constructor to load MARC rules.
     *
     * @return void
     */
    protected function parseRules()
    {
        // Break apart the rule data on line breaks:
        $lines = explode("\n", $this->getRawRules());

        // Each group of data is split by a blank line -- process one group
        // at a time:
        $currentGroup = array();
        foreach ($lines as $currentLine) {
            if (empty($currentLine) && !empty($currentGroup)) {
                $this->processRuleGroup($currentGroup);
                $currentGroup = array();
            } else {
                $currentGroup[] = preg_replace("/\s+/", " ", $currentLine);
            }
        }

        // Still have unprocessed data after the loop?  Handle it now:
        if (!empty($currentGroup)) {
            $this->processRuleGroup($currentGroup);
        }
    }
    // }}}

    // {{{ processRuleGroup()
    /**
     * Support method for parseRules() -- process one group of lines representing
     * a single tag.
     *
     * @param array $rules Rule lines to process
     *
     * @return void
     */
    protected function processRuleGroup($rules)
    {
        // The first line is guaranteed to exist and gives us some basic info:
        list($tag, $repeatable, $description) = explode(' ', $rules[0]);
        $this->rules[$tag] = array(
            'repeatable' => $repeatable,
            'desc' => $description
        );

        // We may or may not have additional details:
        for ($i = 1; $i < count($rules); $i++) {
            list($key, $value, $lineDesc) = explode(' ', $rules[$i]);
            if (substr($key, 0, 3) == 'ind') {
                // Expand ranges:
                $value = str_replace('0-9', '0123456789', $value);
                $this->rules[$tag][$key] = array(
                    'values' => $value,
                    'hr_values' => $this->getHumanReadableIndicatorValues($value),
                    'desc'=> $lineDesc
                );
            } else {
                if (strlen($key) <= 1) {
                    $this->rules[$tag]['sub' . $key] = array(
                        'repeatable' => $value,
                        'desc' => $lineDesc
                    );
                } elseif (strstr($key, '-')) {
                    list($startKey, $endKey) = explode('-', $key);
                    for ($key = $startKey; $key <= $endKey; $key++) {
                        $this->rules[$tag]['sub' . $key] = array(
                            'repeatable' => $value,
                            'desc' => $lineDesc
                        );
                    }
                }
            }
        }
    }
    // }}}

    // {{{ getHumanReadableIndicatorValues()
    /**
     * Turn a set of indicator rules into a human-readable list.
     *
     * @param string $rules Indicator rules
     *
     * @return string
     */
    protected function getHumanReadableIndicatorValues($rules)
    {
        // No processing needed for blank rule:
        if ($rules == 'blank') {
            return $rules;
        }

        // Create string:
        $string = '';
        $length = strlen($rules);
        for ($i = 0; $i < $length; $i++) {
            $current = substr($rules, $i, 1);
            if ($current == 'b') {
                $current = 'blank';
            }
            $string .= $current;
            if ($length - $i == 2) {
                $string .= ' or ';
            } else if ($length - $i > 2) {
                $string .= ', ';
            }
        }

        return $string;
    }
    // }}}

    // {{{ getRawRules()
    /**
     * Support method for parseRules() -- get the raw rules from MARC::Lint.
     *
     * @return string
     */
    protected function getRawRules()
    {
        // When updating rules, don't forget to escape the dollar signs in the text!
        // It would be simpler to change from HEREDOC to NOWDOC syntax, but that
        // would raise the requirements to PHP 5.3.
        // @codingStandardsIgnoreStart
        return <<<RULES
001     NR      CONTROL NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
        NR      Undefined

002     NR      LOCALLY DEFINED (UNOFFICIAL)
ind1    blank   Undefined
ind2    blank   Undefined
        NR      Undefined

003     NR      CONTROL NUMBER IDENTIFIER
ind1    blank   Undefined
ind2    blank   Undefined
        NR      Undefined

005     NR      DATE AND TIME OF LATEST TRANSACTION
ind1    blank   Undefined
ind2    blank   Undefined
        NR      Undefined

006     R       FIXED-LENGTH DATA ELEMENTS--ADDITIONAL MATERIAL CHARACTERISTICS--GENERAL INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
        R       Undefined

007     R       PHYSICAL DESCRIPTION FIXED FIELD--GENERAL INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
        R       Undefined

008     NR      FIXED-LENGTH DATA ELEMENTS--GENERAL INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
        NR      Undefined

010     NR      LIBRARY OF CONGRESS CONTROL NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      LC control number
b       R       NUCMC control number
z       R       Canceled/invalid LC control number
8       R       Field link and sequence number

013     R       PATENT CONTROL INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Number
b       NR      Country
c       NR      Type of number
d       R       Date
e       R       Status
f       R       Party to document
6       NR      Linkage
8       R       Field link and sequence number

015     R       NATIONAL BIBLIOGRAPHY NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       R       National bibliography number
z       R       Canceled/Invalid national bibliography number
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

016     R       NATIONAL BIBLIOGRAPHIC AGENCY CONTROL NUMBER
ind1    b7      National bibliographic agency
ind2    blank   Undefined
a       NR      Record control number
z       R       Canceled or invalid record control number
2       NR      Source
8       R       Field link and sequence number

017     R       COPYRIGHT OR LEGAL DEPOSIT NUMBER
ind1    blank   Undefined
ind2    b8      Undefined
a       R       Copyright or legal deposit number
b       NR      Assigning agency
d       NR      Date
i       NR      Display text
z       R       Canceled/invalid copyright or legal deposit number
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

018     NR      COPYRIGHT ARTICLE-FEE CODE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Copyright article-fee code
6       NR      Linkage
8       R       Field link and sequence number

020     R       INTERNATIONAL STANDARD BOOK NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      International Standard Book Number
c       NR      Terms of availability
z       R       Canceled/invalid ISBN
6       NR      Linkage
8       R       Field link and sequence number

022     R       INTERNATIONAL STANDARD SERIAL NUMBER
ind1    b01     Level of international interest
ind2    blank   Undefined
a       NR      International Standard Serial Number
l       NR      ISSN-L
m       R       Canceled ISSN-L
y       R       Incorrect ISSN
z       R       Canceled ISSN
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

024     R       OTHER STANDARD IDENTIFIER
ind1    0123478    Type of standard number or code
ind2    b01     Difference indicator
a       NR      Standard number or code
c       NR      Terms of availability
d       NR      Additional codes following the standard number or code
z       R       Canceled/invalid standard number or code
2       NR      Source of number or code
6       NR      Linkage
8       R       Field link and sequence number

025     R       OVERSEAS ACQUISITION NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Overseas acquisition number
8       R       Field link and sequence number

026     R       FINGERPRINT IDENTIFIER
ind1    blank   Undefined
ind2    blank   Undefined
a       R       First and second groups of characters
b       R       Third and fourth groups of characters
c       NR      Date
d       R       Number of volume or part
e       NR      Unparsed fingerprint
2       NR      Source
5       R       Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

027     R       STANDARD TECHNICAL REPORT NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Standard technical report number
z       R       Canceled/invalid number
6       NR      Linkage
8       R       Field link and sequence number

028     R       PUBLISHER NUMBER
ind1    012345  Type of publisher number
ind2    0123    Note/added entry controller
a       NR      Publisher number
b       NR      Source
q       R       Qualifying information
6       NR      Linkage
8       R       Field link and sequence number

030     R       CODEN DESIGNATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      CODEN
z       R       Canceled/invalid CODEN
6       NR      Linkage
8       R       Field link and sequence number

031     R       MUSICAL INCIPITS INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Number of work
b       NR      Number of movement
c       NR      Number of excerpt
d       R       Caption or heading
e       NR      Role
g       NR      Clef
m       NR      Voice/instrument
n       NR      Key signature
o       NR      Time signature
p       NR      Musical notation
q       R       General note
r       NR      Key or mode
s       R       Coded validity note
t       R       Text incipit
u       R       Uniform Resource Identifier
y       R       Link text
z       R       Public note
2       NR      System code
6       NR      Linkage
8       R       Field link and sequence number

032     R       POSTAL REGISTRATION NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Postal registration number
b       NR      Source (agency assigning number)
6       NR      Linkage
8       R       Field link and sequence number

033     R       DATE/TIME AND PLACE OF AN EVENT
ind1    b012    Type of date in subfield \$a
ind2    b012    Type of event
a       R       Formatted date/time
b       R       Geographic classification area code
c       R       Geographic classification subarea code
p       R       Place of event
0       R       Record control number
2       R       Source of term
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

034     R       CODED CARTOGRAPHIC MATHEMATICAL DATA
ind1    013     Type of scale
ind2    b01     Type of ring
a       NR      Category of scale
b       R       Constant ratio linear horizontal scale
c       R       Constant ratio linear vertical scale
d       NR      Coordinates--westernmost longitude
e       NR      Coordinates--easternmost longitude
f       NR      Coordinates--northernmost latitude
g       NR      Coordinates--southernmost latitude
h       R       Angular scale
j       NR      Declination--northern limit
k       NR      Declination--southern limit
m       NR      Right ascension--eastern limit
n       NR      Right ascension--western limit
p       NR      Equinox
r       NR      Distance from earth
s       R       G-ring latitude
t       R       G-ring longitude
x       NR      Beginning date
y       NR      Ending date
z       NR      Name of extraterrestrial body
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

035     R       SYSTEM CONTROL NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      System control number
z       R       Canceled/invalid control number
6       NR      Linkage
8       R       Field link and sequence number

036     NR      ORIGINAL STUDY NUMBER FOR COMPUTER DATA FILES
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Original study number
b       NR      Source (agency assigning number)
6       NR      Linkage
8       R       Field link and sequence number

037     R       SOURCE OF ACQUISITION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Stock number
b       NR      Source of stock number/acquisition
c       R       Terms of availability
f       R       Form of issue
g       R       Additional format characteristics
n       R       Note
6       NR      Linkage
8       R       Field link and sequence number

038     NR      RECORD CONTENT LICENSOR
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Record content licensor
6       NR      Linkage
8       R       Field link and sequence number

040     NR      CATALOGING SOURCE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Original cataloging agency
b       NR      Language of cataloging
c       NR      Transcribing agency
d       R       Modifying agency
e       R       Description conventions
6       NR      Linkage
8       R       Field link and sequence number

041     R       LANGUAGE CODE
ind1    b01      Translation indication
ind2    b7      Source of code
a       R       Language code of text/sound track or separate title
b       R       Language code of summary or abstract
d       R       Language code of sung or spoken text
e       R       Language code of librettos
f       R       Language code of table of contents
g       R       Language code of accompanying material other than librettos
h       R       Language code of original
j       R       Language code of subtitles or captions
k       R       Language code of intermediate translations
m       R       Language code of original accompanying materials other than librettos
n       R       Language code of original libretto
2       NR      Source of code
6       NR      Linkage
8       R       Field link and sequence number

042     NR      AUTHENTICATION CODE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Authentication code

043     NR      GEOGRAPHIC AREA CODE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Geographic area code
b       R       Local GAC code
c       R       ISO code
0       R       Authority record control number or standard number
2       R       Source of local code
6       NR      Linkage
8       R       Field link and sequence number

044     NR      COUNTRY OF PUBLISHING/PRODUCING ENTITY CODE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       MARC country code
b       R       Local subentity code
c       R       ISO country code
2       R       Source of local subentity code
6       NR      Linkage
8       R       Field link and sequence number

045     NR      TIME PERIOD OF CONTENT
ind1    b012    Type of time period in subfield \$b or \$c
ind2    blank   Undefined
a       R       Time period code
b       R       Formatted 9999 B.C. through C.E. time period
c       R       Formatted pre-9999 B.C. time period
6       NR      Linkage
8       R       Field link and sequence number

046     R       SPECIAL CODED DATES
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Type of date code
b       NR      Date 1 (B.C. date)
c       NR      Date 1 (C.E. date)
d       NR      Date 2 (B.C. date)
e       NR      Date 2 (C.E. date)
j       NR      Date resource modified
k       NR      Beginning or single date created
l       NR      Ending date created
m       NR      Beginning of date valid
n       NR      End of date valid
o       NR      Single or starting date for aggregated content
p       NR      Ending date for aggregated content
2       NR      Source of date
6       NR      Linkage
8       R       Field link and sequence number

047     R       FORM OF MUSICAL COMPOSITION CODE
ind1    blank   Undefined
ind2    b7      Source of code
a       R       Form of musical composition code
2       NR      Source of code
8       R       Field link and sequence number

048     R       NUMBER OF MUSICAL INSTRUMENTS OR VOICES CODE
ind1    blank   Undefined
ind2    b7      Source specified in subfield \$2
a       R       Performer or ensemble
b       R       Soloist
2       NR      Source of code
8       R       Field link and sequence number

050     R       LIBRARY OF CONGRESS CALL NUMBER
ind1    b01     Existence in LC collection
ind2    04      Source of call number
a       R       Classification number
b       NR      Item number
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

051     R       LIBRARY OF CONGRESS COPY, ISSUE, OFFPRINT STATEMENT
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Classification number
b       NR      Item number
c       NR      Copy information
8       R       Field link and sequence number

052     R       GEOGRAPHIC CLASSIFICATION
ind1    b17     Code source
ind2    blank   Undefined
a       NR      Geographic classification area code
b       R       Geographic classification subarea code
d       R       Populated place name
2       NR      Code source
6       NR      Linkage
8       R       Field link and sequence number

055     R       CLASSIFICATION NUMBERS ASSIGNED IN CANADA
ind1    b01     Existence in LAC collection
ind2    0123456789   Type, completeness, source of class/call number
a       NR      Classification number
b       NR      Item number
2       NR      Source of call/class number
8       R       Field link and sequence number

060     R       NATIONAL LIBRARY OF MEDICINE CALL NUMBER
ind1    b01     Existence in NLM collection
ind2    04      Source of call number
a       R       Classification number
b       NR      Item number
8       R       Field link and sequence number

061     R       NATIONAL LIBRARY OF MEDICINE COPY STATEMENT
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Classification number
b       NR      Item number
c       NR      Copy information
8       R       Field link and sequence number

066     NR      CHARACTER SETS PRESENT
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Primary G0 character set
b       NR      Primary G1 character set
c       R       Alternate G0 or G1 character set

070     R       NATIONAL AGRICULTURAL LIBRARY CALL NUMBER
ind1    01      Existence in NAL collection
ind2    blank   Undefined
a       R       Classification number
b       NR      Item number
8       R       Field link and sequence number

071     R       NATIONAL AGRICULTURAL LIBRARY COPY STATEMENT
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Classification number
b       NR      Item number
c       NR      Copy information
8       R       Field link and sequence number

072     R       SUBJECT CATEGORY CODE
ind1    blank   Undefined
ind2    07      Source specified in subfield \$2
a       NR      Subject category code
x       R       Subject category code subdivision
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

074     R       GPO ITEM NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      GPO item number
z       R       Canceled/invalid GPO item number
8       R       Field link and sequence number

080     R       UNIVERSAL DECIMAL CLASSIFICATION NUMBER
ind1    01      Type of edition
ind2    blank   Undefined
a       NR      Universal Decimal Classification number
b       NR      Item number
x       R       Common auxiliary subdivision
2       NR      Edition identifier
6       NR      Linkage
8       R       Field link and sequence number

082     R       DEWEY DECIMAL CLASSIFICATION NUMBER
ind1    017     Type of edition
ind2    b04     Source of classification number
a       R       Classification number
b       NR      Item number
m       NR      Standard or optional designation
q       NR      Assigning agency
2       NR      Edition number
6       NR      Linkage
8       R       Field link and sequence number

083     R       ADDITIONAL DEWEY DECIMAL CLASSIFICATION NUMBER
ind1    017     Type of edition
ind2    blank   Undefined
a       R       Classification number
c       R       Classification number--Ending number of span
m       NR      Standard or optional designation
q       NR      Assigning agency
y       R       Table sequence number for internal subarrangement or add table
z       R       Table identification
2       NR      Edition number
6       NR      Linkage
8       R       Field link and sequence number

084     R       OTHER CLASSIFICATION NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Classification number
b       NR      Item number
q       NR      Assigning agency
2       NR      Source of number
6       NR      Linkage
8       R       Field link and sequence number

085     R       SYNTHESIZED CLASSIFICATION NUMBER COMPONENTS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Number where instructions are found-single number or beginning number of span
b       R       Base number
c       R       Classification number-ending number of span
f       R       Facet designator
r       R       Root number
s       R       Digits added from classification number in schedule or external table
t       R       Digits added from internal subarrangement or add table
u       R       Number being analyzed
v       R       Number in internal subarrangement or add table where instructions are found
w       R       Table identification-Internal subarrangement or add table
y       R       Table sequence number for internal subarrangement or add table
z       R       Table identification
6       NR      Linkage
8       R       Field link and sequence number

086     R       GOVERNMENT DOCUMENT CLASSIFICATION NUMBER
ind1    b01     Number source
ind2    blank   Undefined
a       NR      Classification number
z       R       Canceled/invalid classification number
2       NR      Number source
6       NR      Linkage
8       R       Field link and sequence number

088     R       REPORT NUMBER
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Report number
z       R       Canceled/invalid report number
6       NR      Linkage
8       R       Field link and sequence number

100     NR      MAIN ENTRY--PERSONAL NAME
ind1    013     Type of personal name entry element
ind2    blank   Undefined
a       NR      Personal name
b       NR      Numeration
c       R       Titles and other words associated with a name
d       NR      Dates associated with a name
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
j       R       Attribution qualifier
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section of a work
p       R       Name of part/section of a work
q       NR      Fuller form of name
t       NR      Title of a work
u       NR      Affiliation
0       R       Authority record control number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

110     NR      MAIN ENTRY--CORPORATE NAME
ind1    012     Type of corporate name entry element
ind2    blank   Undefined
a       NR      Corporate name or jurisdiction name as entry element
b       R       Subordinate unit
c       NR      Location of meeting
d       R       Date of meeting or treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
t       NR      Title of a work
u       NR      Affiliation
0       R       Authority record control number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

111     NR      MAIN ENTRY--MEETING NAME
ind1    012     Type of meeting name entry element
ind2    blank   Undefined
a       NR      Meeting name or jurisdiction name as entry element
c       NR      Location of meeting
d       NR      Date of meeting
e       R       Subordinate unit
f       NR      Date of a work
g       NR      Miscellaneous information
j       R       Relator term
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
q       NR      Name of meeting following jurisdiction name entry element
t       NR      Title of a work
u       NR      Affiliation
0       R       Authority record control number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

130     NR      MAIN ENTRY--UNIFORM TITLE
ind1    0-9     Nonfiling characters
ind2    blank   Undefined
a       NR      Uniform title
d       R       Date of treaty signing
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
0       R       Authority record control number
6       NR      Linkage
8       R       Field link and sequence number

210     R       ABBREVIATED TITLE
ind1    01      Title added entry
ind2    b0      Type
a       NR      Abbreviated title
b       NR      Qualifying information
2       R       Source
6       NR      Linkage
8       R       Field link and sequence number

222     R       KEY TITLE
ind1    blank   Specifies whether variant title and/or added entry is required
ind2    0-9     Nonfiling characters
a       NR      Key title
b       NR      Qualifying information
6       NR      Linkage
8       R       Field link and sequence number

240     NR      UNIFORM TITLE
ind1    01    Uniform title printed or displayed
ind2    0-9    Nonfiling characters
a       NR      Uniform title
d       R       Date of treaty signing
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
0       R       Authority record control number
6       NR      Linkage
8       R       Field link and sequence number

242     R       TRANSLATION OF TITLE BY CATALOGING AGENCY
ind1    01    Title added entry
ind2    0-9    Nonfiling characters
a       NR      Title
b       NR      Remainder of title
c       NR      Statement of responsibility, etc.
h       NR      Medium
n       R       Number of part/section of a work
p       R       Name of part/section of a work
y       NR      Language code of translated title
6       NR      Linkage
8       R       Field link and sequence number

243     NR      COLLECTIVE UNIFORM TITLE
ind1    01    Uniform title printed or displayed
ind2    0-9    Nonfiling characters
a       NR      Uniform title
d       R       Date of treaty signing
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
6       NR      Linkage
8       R       Field link and sequence number

245     NR      TITLE STATEMENT
ind1    01    Title added entry
ind2    0-9    Nonfiling characters
a       NR      Title
b       NR      Remainder of title
c       NR      Statement of responsibility, etc.
f       NR      Inclusive dates
g       NR      Bulk dates
h       NR      Medium
k       R       Form
n       R       Number of part/section of a work
p       R       Name of part/section of a work
s       NR      Version
6       NR      Linkage
8       R       Field link and sequence number

246     R       VARYING FORM OF TITLE
ind1    0123    Note/added entry controller
ind2    b012345678    Type of title
a       NR      Title proper/short title
b       NR      Remainder of title
f       NR      Date or sequential designation
g       NR      Miscellaneous information
h       NR      Medium
i       NR      Display text
n       R       Number of part/section of a work
p       R       Name of part/section of a work
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

247     R       FORMER TITLE
ind1    01      Title added entry
ind2    01      Note controller
a       NR      Title
b       NR      Remainder of title
f       NR      Date or sequential designation
g       NR      Miscellaneous information
h       NR      Medium
n       R       Number of part/section of a work
p       R       Name of part/section of a work
x       NR      International Standard Serial Number
6       NR      Linkage
8       R       Field link and sequence number

250     R       EDITION STATEMENT
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Edition statement
b       NR      Remainder of edition statement
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

254     NR      MUSICAL PRESENTATION STATEMENT
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Musical presentation statement
6       NR      Linkage
8       R       Field link and sequence number

255     R       CARTOGRAPHIC MATHEMATICAL DATA
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Statement of scale
b       NR      Statement of projection
c       NR      Statement of coordinates
d       NR      Statement of zone
e       NR      Statement of equinox
f       NR      Outer G-ring coordinate pairs
g       NR      Exclusion G-ring coordinate pairs
6       NR      Linkage
8       R       Field link and sequence number

256     NR      COMPUTER FILE CHARACTERISTICS
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Computer file characteristics
6       NR      Linkage
8       R       Field link and sequence number

257     R       COUNTRY OF PRODUCING ENTITY
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Country of producing entity
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

258     R       PHILATELIC ISSUE DATE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Issuing jurisdiction
b       NR      Denomination
6       NR      Linkage
8       R       Field link and sequence number

260     R       PUBLICATION, DISTRIBUTION, ETC. (IMPRINT)
ind1    b23     Sequence of publishing statements
ind2    blank   Undefined
a       R       Place of publication, distribution, etc.
b       R       Name of publisher, distributor, etc.
c       R       Date of publication, distribution, etc.
d       NR      Plate or publisher's number for music (Pre-AACR 2)
e       R       Place of manufacture
f       R       Manufacturer
g       R       Date of manufacture
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

261     NR      IMPRINT STATEMENT FOR FILMS (Pre-AACR 1 Revised)
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Producing company
b       R       Releasing company (primary distributor)
d       R       Date of production, release, etc.
e       R       Contractual producer
f       R       Place of production, release, etc.
6       NR      Linkage
8       R       Field link and sequence number

262     NR      IMPRINT STATEMENT FOR SOUND RECORDINGS (Pre-AACR 2)
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Place of production, release, etc.
b       NR      Publisher or trade name
c       NR      Date of production, release, etc.
k       NR      Serial identification
l       NR      Matrix and/or take number
6       NR      Linkage
8       R       Field link and sequence number

263     NR      PROJECTED PUBLICATION DATE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Projected publication date
6       NR      Linkage
8       R       Field link and sequence number

264     R       PRODUCTION, PUBLICATION, DISTRIBUTION, MANUFACTURE, AND COPYRIGHT NOTICE
ind1    b23     Sequence of statements
ind2    01234   Function of entity
a       R       Place of production, publication, distribution, manufacture
b       R       Name of producer, publisher, distributor, manufacturer
c       R       Date of production, publication, distribution, manufacture, or copyright notice
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number (R)

270     R       ADDRESS
ind1    b12     Level
ind2    b07     Type of address
a       R       Address
b       NR      City
c       NR      State or province
d       NR      Country
e       NR      Postal code
f       NR      Terms preceding attention name
g       NR      Attention name
h       NR      Attention position
i       NR      Type of address
j       R       Specialized telephone number
k       R       Telephone number
l       R       Fax number
m       R       Electronic mail address
n       R       TDD or TTY number
p       R       Contact person
q       R       Title of contact person
r       R       Hours
z       R       Public note
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

300     R       PHYSICAL DESCRIPTION
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Extent
b       NR      Other physical details
c       R       Dimensions
e       NR      Accompanying material
f       R       Type of unit
g       R       Size of unit
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

306     NR      PLAYING TIME
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Playing time
6       NR      Linkage
8       R       Field link and sequence number

307     R       HOURS, ETC.
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Hours
b       NR      Additional information
6       NR      Linkage
8       R       Field link and sequence number

310     NR      CURRENT PUBLICATION FREQUENCY
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Current publication frequency
b       NR      Date of current publication frequency
6       NR      Linkage
8       R       Field link and sequence number

321     R       FORMER PUBLICATION FREQUENCY
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Former publication frequency
b       NR      Dates of former publication frequency
6       NR      Linkage
8       R       Field link and sequence number

336     R       CONTENT TYPE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Content type term
b       R       Content type code
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

337     R       MEDIA TYPE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Media type term
b       R       Media type code
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

338     R       CARRIER TYPE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Carrier type term
b       R       Carrier type code
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

340     R       PHYSICAL MEDIUM
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Material base and configuration
b       R       Dimensions
c       R       Materials applied to surface
d       R       Information recording technique
e       R       Support
f       R       Production rate/ratio
h       R       Location within medium
i       R       Technical specifications of medium
j       R       Generation
k       R       Layout
m       R       Book format
n       R       Font size
o       R       Polarity
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

342     R       GEOSPATIAL REFERENCE DATA
ind1    01      Geospatial reference dimension
ind2    012345678    Geospatial reference method
a       NR      Name
b       NR      Coordinate or distance units
c       NR      Latitude resolution
d       NR      Longitude resolution
e       R       Standard parallel or oblique line latitude
f       R       Oblique line longitude
g       NR      Longitude of central meridian or projection center
h       NR      Latitude of projection origin or projection center
i       NR      False easting
j       NR      False northing
k       NR      Scale factor
l       NR      Height of perspective point above surface
m       NR      Azimuthal angle
o       NR      Landsat number and path number
p       NR      Zone identifier
q       NR      Ellipsoid name
r       NR      Semi-major axis
s       NR      Denominator of flattening ratio
t       NR      Vertical resolution
u       NR      Vertical encoding method
v       NR      Local planar, local, or other projection or grid description
w       NR      Local planar or local georeference information
2       NR      Reference method used
6       NR      Linkage
8       R       Field link and sequence number

343     R       PLANAR COORDINATE DATA
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Planar coordinate encoding method
b       NR      Planar distance units
c       NR      Abscissa resolution
d       NR      Ordinate resolution
e       NR      Distance resolution
f       NR      Bearing resolution
g       NR      Bearing units
h       NR      Bearing reference direction
i       NR      Bearing reference meridian
6       NR      Linkage
8       R       Field link and sequence number

344     R       SOUND CHARACTERISTICS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Type of recording
b       R       Recording medium
c       R       Playing speed
d       R       Groove characteristic
e       R       Track configuration
f       R       Tape configuration
g       R       Configuration of playback channels
h       R       Special playback characteristics
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

345     R       PROJECTION CHARACTERISTICS OF MOVING IMAGE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Presentation format
b       R       Projection speed
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

346     R       VIDEO CHARACTERISTICS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Video format
b       R       Broadcast standard
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

347     R       DIGITAL FILE CHARACTERISTICS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       File type
b       R       Encoding format
c       R       File size
d       R       Resolution
e       R       Regional encoding
f       R       Transmission speed
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

351     R       ORGANIZATION AND ARRANGEMENT OF MATERIALS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Organization
b       R       Arrangement
c       NR      Hierarchical level
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

352     R       DIGITAL GRAPHIC REPRESENTATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Direct reference method
b       R       Object type
c       R       Object count
d       NR      Row count
e       NR      Column count
f       NR      Vertical count
g       NR      VPF topology level
i       NR      Indirect reference description
q       R       Format of the digital image
6       NR      Linkage
8       R       Field link and sequence number

355     R       SECURITY CLASSIFICATION CONTROL
ind1    0123458    Controlled element
ind2    blank   Undefined
a       NR      Security classification
b       R       Handling instructions
c       R       External dissemination information
d       NR      Downgrading or declassification event
e       NR      Classification system
f       NR      Country of origin code
g       NR      Downgrading date
h       NR      Declassification date
j       R       Authorization
6       NR      Linkage
8       R       Field link and sequence number

357     NR      ORIGINATOR DISSEMINATION CONTROL
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Originator control term
b       R       Originating agency
c       R       Authorized recipients of material
g       R       Other restrictions
6       NR      Linkage
8       R       Field link and sequence number

362     R       DATES OF PUBLICATION AND/OR SEQUENTIAL DESIGNATION
ind1    01      Format of date
ind2    blank   Undefined
a       NR      Dates of publication and/or sequential designation
z       NR      Source of information
6       NR      Linkage
8       R       Field link and sequence number

363     R       NORMALIZED DATE AND SEQUENTIAL DESIGNATION
ind1    b01      Start/End designator
ind2    b01      State of issuanceUndefined
a       NR      First level of enumeration
b       NR      Second level of enumeration
c       NR      Third level of enumeration
d       NR      Fourth level of enumeration
e       NR      Fifth level of enumeration
f       NR      Sixth level of enumeration
g       NR      Alternative numbering scheme, first level of enumeration
h       NR      Alternative numbering scheme, second level of enumeration
i       NR      First level of chronology
j       NR      Second level of chronology
k       NR      Third level of chronology
l       NR      Fourth level of chronology
m       NR      Alternative numbering scheme, chronology
u       NR      First level textual designation
v       NR      First level of chronology, issuance
x       R       Nonpublic note
z       R       Public note
6       NR      Linkage
8       NR      Field link and sequence number

365     R       TRADE PRICE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Price type code
b       NR      Price amount
c       NR      Currency code
d       NR      Unit of pricing
e       NR      Price note
f       NR      Price effective from
g       NR      Price effective until
h       NR      Tax rate 1
i       NR      Tax rate 2
j       NR      ISO country code
k       NR      MARC country code
m       NR      Identification of pricing entity
2       NR      Source of price type code
6       NR      Linkage
8       R       Field link and sequence number

366     R       TRADE AVAILABILITY INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Publishers' compressed title identification
b       NR      Detailed date of publication
c       NR      Availability status code
d       NR      Expected next availability date
e       NR      Note
f       NR      Publishers' discount category
g       NR      Date made out of print
j       NR      ISO country code
k       NR      MARC country code
m       NR      Identification of agency
2       NR      Source of availability status code
6       NR      Linkage
8       R       Field link and sequence number

377     R       ASSOCIATED LANGUAGE
ind1    blank   Undefined
ind2    b7      Undefined
a       R       Language code
l       R       Language term
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

380     R       FORM OF WORK
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Form of work
0       R       Record control number
2       NR      Source of term
6       NR      Linkage
8       R       Field link and sequence number

381     R       OTHER DISTINGUISHING CHARACTERISTICS OF WORK OR EXPRESSION
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Other distinguishing characteristic
u       R       Uniform Resource Identifier
v       R       Source of information
0       R       Record control number
2       NR      Source of term
6       NR      Linkage
8       R       Field link and sequence number

382     R       MEDIUM OF PERFORMANCE
ind1    b01     Undefined
ind2    b01     Undefined
a       R       Medium of performance
b       R       Soloist
d       R       Doubling instrument
n       R       Number of performers of the same medium
p       R       Alternative medium of performance
s       R       Total number of performers
v       R       Note
0       R       Record control number
2       NR      Source of term
6       NR      Linkage
8       R       Field link and sequence number

383     R       NUMERIC DESIGNATION OF MUSICAL WORK
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Serial number
b       R       Opus number
c       R       Thematic index number
d       NR      Thematic index code
e       NR      Publisher associated with opus number
2       NR      Source
6       NR      Linkage
8       R       Field link and sequence number

384     NR      KEY
ind1    b01     Key type
ind2    blank   Undefined
a       NR      Key
6       NR      Linkage
8       R       Field link and sequence number

385     R       AUDIENCE CHARACTERISTICS
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Audience term
b       R       Audience code
m       NR      Demographic group term
n       NR      Demographic group code
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

386 - CREATOR/CONTRIBUTOR CHARACTERISTICS (R)
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Creator/contributor term
b       R       Creator/contributor code
m       NR      Demographic group term
n       NR      Demographic group code
0       R       Authority record control number or standard number
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

400     R       SERIES STATEMENT/ADDED ENTRY--PERSONAL NAME
ind1    013     Type of personal name entry element
ind2    01      Pronoun represents main entry
a       NR      Personal name
b       NR      Numeration
c       R       Titles and other words associated with a name
d       NR      Dates associated with a name
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section of a work
p       R       Name of part/section of a work
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume number/sequential designation
x       NR      International Standard Serial Number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

410     R       SERIES STATEMENT/ADDED ENTRY--CORPORATE NAME
ind1    012     Type of corporate name entry element
ind2    01      Pronoun represents main entry
a       NR      Corporate name or jurisdiction name as entry element
b       R       Subordinate unit
c       NR      Location of meeting
d       R       Date of meeting or treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume number/sequential designation
x       NR      International Standard Serial Number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

411     R       SERIES STATEMENT/ADDED ENTRY--MEETING NAME
ind1    012     Type of meeting name entry element
ind2    01      Pronoun represents main entry
a       NR      Meeting name or jurisdiction name as entry element
c       NR      Location of meeting
d       NR      Date of meeting
e       R       Subordinate unit
f       NR      Date of a work
g       NR      Miscellaneous information
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
q       NR      Name of meeting following jurisdiction name entry element
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume number/sequential designation
x       NR      International Standard Serial Number
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

440     R       SERIES STATEMENT/ADDED ENTRY--TITLE [OBSOLETE]
ind1    blank   Undefined
ind2    0-9     Nonfiling characters
a       NR      Title
n       R       Number of part/section of a work
p       R       Name of part/section of a work
v       NR      Volume number/sequential designation
x       NR      International Standard Serial Number
w       R       Bibliographic record control number
0       R       Authority record control number
6       NR      Linkage
8       R       Field link and sequence number

490     R       SERIES STATEMENT
ind1    01      Specifies whether series is traced
ind2    blank   Undefined
a       R       Series statement
l       NR      Library of Congress call number
v       R       Volume number/sequential designation
x       R       International Standard Serial Number
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

500     R       GENERAL NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      General note
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

501     R       WITH NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      With note
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

502     R       DISSERTATION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Dissertation note
b       NR      Degree type
c       NR      Name of granting institution
d       NR      Year of degree granted
g       R       Miscellaneous information
o       R       Dissertation identifier
6       NR      Linkage
8       R       Field link and sequence number

504     R       BIBLIOGRAPHY, ETC. NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Bibliography, etc. note
b       NR      Number of references
6       NR      Linkage
8       R       Field link and sequence number

505     R       FORMATTED CONTENTS NOTE
ind1    0128    Display constant controller
ind2    b0      Level of content designation
a       NR      Formatted contents note
g       R       Miscellaneous information
r       R       Statement of responsibility
t       R       Title
u       R       Uniform Resource Identifier
6       NR      Linkage
8       R       Field link and sequence number

506     R       RESTRICTIONS ON ACCESS NOTE
ind1    b01     Restriction
ind2    blank   Undefined
a       NR      Terms governing access
b       R       Jurisdiction
c       R       Physical access provisions
d       R       Authorized users
e       R       Authorization
f       R       Standard terminology for access restiction
u       R       Uniform Resource Identifier
2       NR      Source of term
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

507     NR      SCALE NOTE FOR GRAPHIC MATERIAL
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Representative fraction of scale note
b       NR      Remainder of scale note
6       NR      Linkage
8       R       Field link and sequence number

508     R       CREATION/PRODUCTION CREDITS NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Creation/production credits note
6       NR      Linkage
8       R       Field link and sequence number

510     R       CITATION/REFERENCES NOTE
ind1    01234   Coverage/location in source
ind2    blank   Undefined
a       NR      Name of source
b       NR      Coverage of source
c       NR      Location within source
u       R       Uniform Resource Identifier
x       NR      International Standard Serial Number
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

511     R       PARTICIPANT OR PERFORMER NOTE
ind1    01      Display constant controller
ind2    blank   Undefined
a       NR      Participant or performer note
6       NR      Linkage
8       R       Field link and sequence number

513     R       TYPE OF REPORT AND PERIOD COVERED NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Type of report
b       NR      Period covered
6       NR      Linkage
8       R       Field link and sequence number

514     NR      DATA QUALITY NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Attribute accuracy report
b       R       Attribute accuracy value
c       R       Attribute accuracy explanation
d       NR      Logical consistency report
e       NR      Completeness report
f       NR      Horizontal position accuracy report
g       R       Horizontal position accuracy value
h       R       Horizontal position accuracy explanation
i       NR      Vertical positional accuracy report
j       R       Vertical positional accuracy value
k       R       Vertical positional accuracy explanation
m       NR      Cloud cover
u       R       Uniform Resource Identifier
z       R       Display note
6       NR      Linkage
8       R       Field link and sequence number

515     R       NUMBERING PECULIARITIES NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Numbering peculiarities note
6       NR      Linkage
8       R       Field link and sequence number

516     R       TYPE OF COMPUTER FILE OR DATA NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Type of computer file or data note
6       NR      Linkage
8       R       Field link and sequence number

518     R       DATE/TIME AND PLACE OF AN EVENT NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Date/time and place of an event note
d       R       Date of event
o       R       Other event information
p       R       Place of event
0       R       Record control number
2       R       Source of term
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

520     R       SUMMARY, ETC.
ind1    b012348    Display constant controller
ind2    blank   Undefined
a       NR      Summary, etc. note
b       NR      Expansion of summary note
c       NR      Assigning agency
u       R       Uniform Resource Identifier
2       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

521     R       TARGET AUDIENCE NOTE
ind1    b012348    Display constant controller
ind2    blank   Undefined
a       R       Target audience note
b       NR      Source
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

522     R       GEOGRAPHIC COVERAGE NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Geographic coverage note
6       NR      Linkage
8       R       Field link and sequence number

524     R       PREFERRED CITATION OF DESCRIBED MATERIALS NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Preferred citation of described materials note
2       NR      Source of schema used
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

525     R       SUPPLEMENT NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Supplement note
6       NR      Linkage
8       R       Field link and sequence number

526     R       STUDY PROGRAM INFORMATION NOTE
ind1    08      Display constant controller
ind2    blank   Undefined
a       NR      Program name
b       NR      Interest level
c       NR      Reading level
d       NR      Title point value
i       NR      Display text
x       R       Nonpublic note
z       R       Public note
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

530     R       ADDITIONAL PHYSICAL FORM AVAILABLE NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Additional physical form available note
b       NR      Availability source
c       NR      Availability conditions
d       NR      Order number
u       R       Uniform Resource Identifier
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

533     R       REPRODUCTION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Type of reproduction
b       R       Place of reproduction
c       R       Agency responsible for reproduction
d       NR      Date of reproduction
e       NR      Physical description of reproduction
f       R       Series statement of reproduction
m       R       Dates and/or sequential designation of issues reproduced
n       R       Note about reproduction
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
7       NR      Fixed-length data elements of reproduction
8       R       Field link and sequence number

534     R       ORIGINAL VERSION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Main entry of original
b       NR      Edition statement of original
c       NR      Publication, distribution, etc. of original
e       NR      Physical description, etc. of original
f       R       Series statement of original
k       R       Key title of original
l       NR      Location of original
m       NR      Material specific details
n       R       Note about original
o       R       Other resource identifier
p       NR      Introductory phrase
t       NR      Title statement of original
x       R       International Standard Serial Number
z       R       International Standard Book Number
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

535     R       LOCATION OF ORIGINALS/DUPLICATES NOTE
ind1    12      Additional information about custodian
ind2    blank   Undefined
a       NR      Custodian
b       R       Postal address
c       R       Country
d       R       Telecommunications address
g       NR      Repository location code
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

536     R       FUNDING INFORMATION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Text of note
b       R       Contract number
c       R       Grant number
d       R       Undifferentiated number
e       R       Program element number
f       R       Project number
g       R       Task number
h       R       Work unit number
6       NR      Linkage
8       R       Field link and sequence number

538     R       SYSTEM DETAILS NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      System details note
i       NR      Display text
u       R       Uniform Resource Identifier
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

540     R       TERMS GOVERNING USE AND REPRODUCTION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Terms governing use and reproduction
b       NR      Jurisdiction
c       NR      Authorization
d       NR      Authorized users
u       R       Uniform Resource Identifier
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

541     R       IMMEDIATE SOURCE OF ACQUISITION NOTE
ind1    b01     Undefined
ind2    blank   Undefined
a       NR      Source of acquisition
b       NR      Address
c       NR      Method of acquisition
d       NR      Date of acquisition
e       NR      Accession number
f       NR      Owner
h       NR      Purchase price
n       R       Extent
o       R       Type of unit
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

542     R       INFORMATION RELATING TO COPYRIGHT STATUS
ind1    b01     Relationship
ind2    blank   Undefined
a       NR      Personal creator
b       NR      Personal creator death date
c       NR      Corporate creator
d       R       Copyright holder
e       R       Copyright holder contact information
f       R       Copyright statement
g       NR      Copyright date
h       R       Copyright renewal date
i       NR      Publication date
j       NR      Creation date
k       R       Publisher
l       NR      Copyright status
m       NR      Publication status
n       R       Note
o       NR      Research date
q       NR      Assigning agency
r       NR      Jurisdiction of copyright assessment
s       NR      Source of information
u       R       Uniform Resource Identifier
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

544     R       LOCATION OF OTHER ARCHIVAL MATERIALS NOTE
ind1    b01     Relationship
ind2    blank   Undefined
a       R       Custodian
b       R       Address
c       R       Country
d       R       Title
e       R       Provenance
n       R       Note
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

545     R       BIOGRAPHICAL OR HISTORICAL DATA
ind1    b01     Type of data
ind2    blank   Undefined
a       NR      Biographical or historical note
b       NR      Expansion
u       R       Uniform Resource Identifier
6       NR      Linkage
8       R       Field link and sequence number

546     R       LANGUAGE NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Language note
b       R       Information code or alphabet
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

547     R       FORMER TITLE COMPLEXITY NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Former title complexity note
6       NR      Linkage
8       R       Field link and sequence number

550     R       ISSUING BODY NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Issuing body note
6       NR      Linkage
8       R       Field link and sequence number

552     R       ENTITY AND ATTRIBUTE INFORMATION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Entity type label
b       NR      Entity type definition and source
c       NR      Attribute label
d       NR      Attribute definition and source
e       R       Enumerated domain value
f       R       Enumerated domain value definition and source
g       NR      Range domain minimum and maximum
h       NR      Codeset name and source
i       NR      Unrepresentable domain
j       NR      Attribute units of measurement and resolution
k       NR      Beginning date and ending date of attribute values
l       NR      Attribute value accuracy
m       NR      Attribute value accuracy explanation
n       NR      Attribute measurement frequency
o       R       Entity and attribute overview
p       R       Entity and attribute detail citation
u       R       Uniform Resource Identifier
z       R       Display note
6       NR      Linkage
8       R       Field link and sequence number

555     R       CUMULATIVE INDEX/FINDING AIDS NOTE
ind1    b08     Display constant controller
ind2    blank   Undefined
a       NR      Cumulative index/finding aids note
b       R       Availability source
c       NR      Degree of control
d       NR      Bibliographic reference
u       R       Uniform Resource Identifier
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

556     R       INFORMATION ABOUT DOCUMENTATION NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Information about documentation note
z       R       International Standard Book Number
6       NR      Linkage
8       R       Field link and sequence number

561     R       OWNERSHIP AND CUSTODIAL HISTORY
ind1    b01     Undefined
ind2    blank   Undefined
a       NR      History
u       R       Uniform Resource Identifier
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

562     R       COPY AND VERSION IDENTIFICATION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Identifying markings
b       R       Copy identification
c       R       Version identification
d       R       Presentation format
e       R       Number of copies
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

563     R       BINDING INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Binding note
u       R       Uniform Resource Identifier
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

565     R       CASE FILE CHARACTERISTICS NOTE
ind1    b08     Display constant controller
ind2    blank   Undefined
a       NR      Number of cases/variables
b       R       Name of variable
c       R       Unit of analysis
d       R       Universe of data
e       R       Filing scheme or code
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

567     R       METHODOLOGY NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Methodology note
6       NR      Linkage
8       R       Field link and sequence number

580     R       LINKING ENTRY COMPLEXITY NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Linking entry complexity note
6       NR      Linkage
8       R       Field link and sequence number

581     R       PUBLICATIONS ABOUT DESCRIBED MATERIALS NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Publications about described materials note
z       R       International Standard Book Number
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

583     R       ACTION NOTE
ind1    b01     Undefined
ind2    blank   Undefined
a       NR      Action
b       R       Action identification
c       R       Time/date of action
d       R       Action interval
e       R       Contingency for action
f       R       Authorization
h       R       Jurisdiction
i       R       Method of action
j       R       Site of action
k       R       Action agent
l       R       Status
n       R       Extent
o       R       Type of unit
u       R       Uniform Resource Identifier
x       R       Nonpublic note
z       R       Public note
2       NR      Source of term
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

584     R       ACCUMULATION AND FREQUENCY OF USE NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Accumulation
b       R       Frequency of use
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

585     R       EXHIBITIONS NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Exhibitions note
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

586     R       AWARDS NOTE
ind1    b8      Display constant controller
ind2    blank   Undefined
a       NR      Awards note
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

588     R       SOURCE OF DESCRIPTION NOTE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Source of description note
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

600     R       SUBJECT ADDED ENTRY--PERSONAL NAME
ind1    013     Type of personal name entry element
ind2    01234567    Thesaurus
a       NR      Personal name
b       NR      Numeration
c       R       Titles and other words associated with a name
d       NR      Dates associated with a name
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
j       R       Attribution qualifier
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
q       NR      Fuller form of name
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

610     R       SUBJECT ADDED ENTRY--CORPORATE NAME
ind1    012     Type of corporate name entry element
ind2    01234567    Thesaurus
a       NR      Corporate name or jurisdiction name as entry element
b       R       Subordinate unit
c       NR      Location of meeting
d       R       Date of meeting or treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section/meeting
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

611     R       SUBJECT ADDED ENTRY--MEETING NAME
ind1    012     Type of meeting name entry element
ind2    01234567    Thesaurus
a       NR      Meeting name or jurisdiction name as entry element
c       NR      Location of meeting
d       NR      Date of meeting
e       R       Subordinate unit
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
j       R       Relator term
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
q       NR      Name of meeting following jurisdiction name entry element
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

630     R       SUBJECT ADDED ENTRY--UNIFORM TITLE
ind1    0-9     Nonfiling characters
ind2    01234567    Thesaurus
a       NR      Uniform title
d       R       Date of treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

648     R       SUBJECT ADDED ENTRY--CHRONOLOGICAL TERM
ind1    b01     Type of date or time period
ind2    01234567    Thesaurus
a       NR      Chronological term
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

650     R       SUBJECT ADDED ENTRY--TOPICAL TERM
ind1    b012    Level of subject
ind2    01234567    Thesaurus
a       NR      Topical term or geographic name as entry element
b       NR      Topical term following geographic name as entry element
c       NR      Location of event
d       NR      Active dates
e       NR      Relator term
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

651     R       SUBJECT ADDED ENTRY--GEOGRAPHIC NAME
ind1    blank   Undefined
ind2    01234567    Thesaurus
a       NR      Geographic name
e       R       Relator term
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

653     R       INDEX TERM--UNCONTROLLED
ind1    b012    Level of index term
ind2    b0123456   Type of term or name
a       R       Uncontrolled term
6       NR      Linkage
8       R       Field link and sequence number

654     R       SUBJECT ADDED ENTRY--FACETED TOPICAL TERMS
ind1    b012    Level of subject
ind2    blank   Undefined
a       R       Focus term
b       R       Non-focus term
c       R       Facet/hierarchy designation
e       R       Relator term
v       R       Form subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

655     R       INDEX TERM--GENRE/FORM
ind1    b0      Type of heading
ind2    01234567    Thesaurus
a       NR      Genre/form data or focus term
b       R       Non-focus term
c       R       Facet/hierarchy designation
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of term
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

656     R       INDEX TERM--OCCUPATION
ind1    blank   Undefined
ind2    7       Source of term
a       NR      Occupation
k       NR      Form
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of term
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

657     R       INDEX TERM--FUNCTION
ind1    blank   Undefined
ind2    7       Source of term
a       NR      Function
v       R       Form subdivision
x       R       General subdivision
y       R       Chronological subdivision
z       R       Geographic subdivision
0       R       Authority record control number
2       NR      Source of term
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

658     R       INDEX TERM--CURRICULUM OBJECTIVE
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Main curriculum objective
b       R       Subordinate curriculum objective
c       NR      Curriculum code
d       NR      Correlation factor
2       NR      Source of term or code
6       NR      Linkage
8       R       Field link and sequence number

662     R       SUBJECT ADDED ENTRY--HIERARCHICAL PLACE NAME
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Country or larger entity
b       NR      First-order political jurisdiction
c       R       Intermediate political jurisdiction
d       NR      City
e       R       Relator term
f       R       City subsection
g       R       Other nonjurisdictional geographic region and feature
h       R       Extraterrestrial area
0       R       Authority record control number
2       NR      Source of heading or term
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

700     R       ADDED ENTRY--PERSONAL NAME
ind1    013     Type of personal name entry element
ind2    b2      Type of added entry
a       NR      Personal name
b       NR      Numeration
c       R       Titles and other words associated with a name
d       NR      Dates associated with a name
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
i       R       Relationship information
j       R       Attribution qualifier
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
q       NR      Fuller form of name
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

710     R       ADDED ENTRY--CORPORATE NAME
ind1    012     Type of corporate name entry element
ind2    b2      Type of added entry
a       NR      Corporate name or jurisdiction name as entry element
b       R       Subordinate unit
c       NR      Location of meeting
d       R       Date of meeting or treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
i       R       Relationship information
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section/meeting
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

711     R       ADDED ENTRY--MEETING NAME
ind1    012     Type of meeting name entry element
ind2    b2      Type of added entry
a       NR      Meeting name or jurisdiction name as entry element
c       NR      Location of meeting
d       NR      Date of meeting
e       R       Subordinate unit
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
i       R       Relationship information
j       R       Relator term
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
q       NR      Name of meeting following jurisdiction name entry element
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

720     R       ADDED ENTRY--UNCONTROLLED NAME
ind1    b12     Type of name
ind2    blank   Undefined
a       NR      Name
e       R       Relator term
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

730     R       ADDED ENTRY--UNIFORM TITLE
ind1    0-9     Nonfiling characters
ind2    b2      Type of added entry
a       NR      Uniform title
d       R       Date of treaty signing
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
i       R       Relationship information
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

740     R       ADDED ENTRY--UNCONTROLLED RELATED/ANALYTICAL TITLE
ind1    0-9     Nonfiling characters
ind2    b2      Type of added entry
a       NR      Uncontrolled related/analytical title
h       NR      Medium
n       R       Number of part/section of a work
p       R       Name of part/section of a work
5       NR      Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

751     R       ADDED ENTRY--GEOGRAPHIC NAME
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Geographic name
e       R       Relator term
0       R       Authority record control number
2       NR      Source of heading or term
3       NR      Materials specified
4       R       Relator code
6       NR      Linkage
8       R       Field link and sequence number

752     R       ADDED ENTRY--HIERARCHICAL PLACE NAME
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Country or larger entity
b       NR      First-order political jurisdiction
c       NR      Intermediate political jurisdiction
d       NR      City
f       R       City subsection
g       R       Other nonjurisdictional geographic region and feature
h       R       Extraterrestrial area
0       R       Authority record control number
2       NR      Source of heading or term
6       NR      Linkage
8       R       Field link and sequence number

753     R       SYSTEM DETAILS ACCESS TO COMPUTER FILES
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Make and model of machine
b       NR      Programming language
c       NR      Operating system
6       NR      Linkage
8       R       Field link and sequence number

754     R       ADDED ENTRY--TAXONOMIC IDENTIFICATION
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Taxonomic name
c       R       Taxonomic category
d       R       Common or alternative name
x       R       Non-public note
z       R       Public note
0       R       Authority record control number
2       NR      Source of taxonomic identification
6       NR      Linkage
8       R       Field link and sequence number

760     R       MAIN SERIES ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
s       NR      Uniform title
t       NR      Title
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

762     R       SUBSERIES ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
s       NR      Uniform title
t       NR      Title
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

765     R       ORIGINAL LANGUAGE ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

767     R       TRANSLATION ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

770     R       SUPPLEMENT/SPECIAL ISSUE ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

772     R       SUPPLEMENT PARENT ENTRY
ind1    01      Note controller
ind2    b08     Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Stan dard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

773     R       HOST ITEM ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
p       NR      Abbreviated title
q       NR      Enumeration and first page
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
3       NR      Materials specified
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

774     R       CONSTITUENT UNIT ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

775     R       OTHER EDITION ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
e       NR      Language code
f       NR      Country code
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

776     R       ADDITIONAL PHYSICAL FORM ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

777     R       ISSUED WITH ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
s       NR      Uniform title
t       NR      Title
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

780     R       PRECEDING ENTRY
ind1    01      Note controller
ind2    01234567    Type of relationship
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

785     R       SUCCEEDING ENTRY
ind1    01      Note controller
ind2    012345678    Type of relationship
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standa rd Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

786     R       DATA SOURCE ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
j       NR      Period of content
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
p       NR      Abbreviated title
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
v       NR      Source Contribution
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

787     R       OTHER RELATIONSHIP ENTRY
ind1    01      Note controller
ind2    b8      Display constant controller
a       NR      Main entry heading
b       NR      Edition
c       NR      Qualifying information
d       NR      Place, publisher, and date of publication
g       R       Related parts
h       NR      Physical description
i       R       Relationship information
k       R       Series data for related item
m       NR      Material-specific details
n       R       Note
o       R       Other item identifier
r       R       Report number
s       NR      Uniform title
t       NR      Title
u       NR      Standard Technical Report Number
w       R       Record control number
x       NR      International Standard Serial Number
y       NR      CODEN designation
z       R       International Standard Book Number
4       R       Relationship code
6       NR      Linkage
7       NR      Control subfield
8       R       Field link and sequence number

800     R       SERIES ADDED ENTRY--PERSONAL NAME
ind1    013     Type of personal name entry element
ind2    blank   Undefined
a       NR      Personal name
b       NR      Numeration
c       R       Titles and other words associated with a name
d       NR      Dates associated with a name
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
j       R       Attribution qualifier
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
q       NR      Fuller form of name
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume/sequential designation
w       R       Bibliographic record control number
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       R       Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

810     R       SERIES ADDED ENTRY--CORPORATE NAME
ind1    012     Type of corporate name entry element
ind2    blank   Undefined
a       NR      Corporate name or jurisdiction name as entry element
b       R       Subordinate unit
c       NR      Location of meeting
d       R       Date of meeting or treaty signing
e       R       Relator term
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section/meeting
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume/sequential designation
w       R       Bibliographic record control number
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       R       Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

811     R       SERIES ADDED ENTRY--MEETING NAME
ind1    012     Type of meeting name entry element
ind2    blank   Undefined
a       NR      Meeting name or jurisdiction name as entry element
c       NR      Location of meeting
d       NR      Date of meeting
e       R       Subordinate unit
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
j       R       Relator term
k       R       Form subheading
l       NR      Language of a work
n       R       Number of part/section/meeting
p       R       Name of part/section of a work
q       NR      Name of meeting following jurisdiction name entry element
s       NR      Version
t       NR      Title of a work
u       NR      Affiliation
v       NR      Volume/sequential designation
w       R       Bibliographic record control number
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
4       R       Relator code
5       R       Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

830     R       SERIES ADDED ENTRY--UNIFORM TITLE
ind1    blank   Undefined
ind2    0-9     Nonfiling characters
a       NR      Uniform title
d       R       Date of treaty signing
f       NR      Date of a work
g       NR      Miscellaneous information
h       NR      Medium
k       R       Form subheading
l       NR      Language of a work
m       R       Medium of performance for music
n       R       Number of part/section of a work
o       NR      Arranged statement for music
p       R       Name of part/section of a work
r       NR      Key for music
s       NR      Version
t       NR      Title of a work
v       NR      Volume/sequential designation
w       R       Bibliographic record control number
x       NR      International Standard Serial Number
0       R       Authority record control number
3       NR      Materials specified
5       R       Institution to which field applies
6       NR      Linkage
8       R       Field link and sequence number

841     NR      HOLDINGS CODED DATA VALUES

842     NR      TEXTUAL PHYSICAL FORM DESIGNATOR

843     R       REPRODUCTION NOTE

844     NR      NAME OF UNIT

845     R       TERMS GOVERNING USE AND REPRODUCTION NOTE

850     R       HOLDING INSTITUTION
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Holding institution
8       R       Field link and sequence number

852     R       LOCATION
ind1    b012345678    Shelving scheme
ind2    b012    Shelving order
a       NR      Location
b       R       Sublocation or collection
c       R       Shelving location
d       R       Former shelving location
e       R       Address
f       R       Coded location qualifier
g       R       Non-coded location qualifier
h       NR      Classification part
i       R       Item part
j       NR      Shelving control number
k       R       Call number prefix
l       NR      Shelving form of title
m       R       Call number suffix
n       NR      Country code
p       NR      Piece designation
q       NR      Piece physical condition
s       R       Copyright article-fee code
t       NR      Copy number
u       R       Uniform Resource Identifier
x       R       Nonpublic note
z       R       Public note
2       NR      Source of classification or shelving scheme
3       NR      Materials specified
6       NR      Linkage
8       NR      Sequence number

853     R       CAPTIONS AND PATTERN--BASIC BIBLIOGRAPHIC UNIT

854     R       CAPTIONS AND PATTERN--SUPPLEMENTARY MATERIAL

855     R       CAPTIONS AND PATTERN--INDEXES

856     R       ELECTRONIC LOCATION AND ACCESS
ind1    b012347    Access method
ind2    b0128   Relationship
a       R       Host name
b       R       Access number
c       R       Compression information
d       R       Path
f       R       Electronic name
h       NR      Processor of request
i       R       Instruction
j       NR      Bits per second
k       NR      Password
l       NR      Logon
m       R       Contact for access assistance
n       NR      Name of location of host
o       NR      Operating system
p       NR      Port
q       NR      Electronic format type
r       NR      Settings
s       R       File size
t       R       Terminal emulation
u       R       Uniform Resource Identifier
v       R       Hours access method available
w       R       Record control number
x       R       Nonpublic note
y       R       Link text
z       R       Public note
2       NR      Access method
3       NR      Materials specified
6       NR      Linkage
8       R       Field link and sequence number

863     R       ENUMERATION AND CHRONOLOGY--BASIC BIBLIOGRAPHIC UNIT

864     R       ENUMERATION AND CHRONOLOGY--SUPPLEMENTARY MATERIAL

865     R       ENUMERATION AND CHRONOLOGY--INDEXES

866     R       TEXTUAL HOLDINGS--BASIC BIBLIOGRAPHIC UNIT

867     R       TEXTUAL HOLDINGS--SUPPLEMENTARY MATERIAL

868     R       TEXTUAL HOLDINGS--INDEXES

876     R       ITEM INFORMATION--BASIC BIBLIOGRAPHIC UNIT

877     R       ITEM INFORMATION--SUPPLEMENTARY MATERIAL

878     R       ITEM INFORMATION--INDEXES

880     R       ALTERNATE GRAPHIC REPRESENTATION
ind1            Same as associated field
ind2            Same as associated field
6       NR      Linkage

882     NR      REPLACEMENT RECORD INFORMATION
ind1    blank   Undefined
ind2    blank   Undefined
a       R       Replacement title
i       R       Explanatory text
w       R       Replacement bibliographic record control number
6       NR      Linkage
8       R       Field link and sequence number

883     R       MACHINE-GENERATED METADATA PROVENANCE
ind1    b01     Type of field
ind2    blank   Undefined
a       NR      Generation process
c       NR      Confidence value
d       NR      Generation date
q       NR      Generation agency
x       NR      Validity end date
u       NR      Uniform Resource Identifier
w       R       Bibliographic record control number
0       R       Authority record control number or standard number
8       R       Field link and sequence number

886     R       FOREIGN MARC INFORMATION FIELD
ind1    012     Type of field
ind2    blank   Undefined
a       NR      Tag of the foreign MARC field
b       NR      Content of the foreign MARC field
c-z     NR      Foreign MARC subfield
0-1     NR      Foreign MARC subfield
2       NR      Source of data
4       NR      Source of data
3-9     NR      Source of data

887     R       NON-MARC INFORMATION FIELD
ind1    blank   Undefined
ind2    blank   Undefined
a       NR      Content of non-MARC field
2       NR      Source of data
RULES;
        // @codingStandardsIgnoreEnd
    }
    // }}}
}
// }}}

