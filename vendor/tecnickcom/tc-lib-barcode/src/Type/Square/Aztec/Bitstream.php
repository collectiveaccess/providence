<?php

/**
 * Bitstream.php
 *
 * @since       2023-10-13
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2023-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * This file is part of tc-lib-barcode software library.
 */

namespace Com\Tecnick\Barcode\Type\Square\Aztec;

use Com\Tecnick\Barcode\Type\Square\Aztec\Data;
use Com\Tecnick\Barcode\Exception as BarcodeException;

/**
 * Com\Tecnick\Barcode\Type\Square\Aztec\Bitstream
 *
 * Bitstream for Aztec Barcode type class
 *
 * @since       2023-10-13
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2023-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Bitstream extends \Com\Tecnick\Barcode\Type\Square\Aztec\Layers
{
     /**
     * Performs the high-level encoding for the given code and ECI mode.
     *
     * @param string $code The code to encode.
     * @param int $eci The ECI mode to use.
     * @param string $hint The mode to use.
     */
    protected function highLevelEncoding($code, $eci = 0, $hint = 'A')
    {
        $this->addFLG($eci);
        $chars = array_values(unpack('C*', $code));
        $chrlen = count($chars);
        if ($hint == 'B') {
            $this->binaryEncode($chars, $chrlen);
            return;
        }
        $this->autoEncode($chars, $chrlen);
    }

    /**
     * Forced binary encoding for the given characters.
     *
     * @param array $chars  Integer ASCII values of the characters to encode.
     * @param int   $chrlen Lenght of the $chars array.
     */
    protected function binaryEncode($chars, $chrlen)
    {
        $bits = Data::MODE_BITS[Data::MODE_BINARY];
        $this->addShift(Data::MODE_BINARY);
        if ($chrlen > 62) {
            $this->addRawCwd(5, 0);
            $this->addRawCwd(11, ($chrlen - 31));
            for ($idx = 0; $idx < $chrlen; $idx++) {
                $this->addRawCwd($bits, $chars[$idx]);
            }
            return;
        }
        if ($chrlen > 31) {
            $this->addRawCwd(5, 31);
            for ($idx = 0; $idx < 31; $idx++) {
                $this->addRawCwd($bits, $chars[$idx]);
            }
            $this->addShift(Data::MODE_BINARY);
            $this->addRawCwd(5, ($chrlen - 31));
            for ($idx = 31; $idx < $chrlen; $idx++) {
                $this->addRawCwd($bits, $chars[$idx]);
            }
            return;
        }
        $this->addRawCwd(5, $chrlen);
        for ($idx = 0; $idx < $chrlen; $idx++) {
            $this->addRawCwd($bits, $chars[$idx]);
        }
    }

    /**
     * Automatic encoding for the given characters.
     *
     * @param array $chars  Integer ASCII values of the characters to encode.
     * @param int   $chrlen Lenght of the $chars array.
     */
    protected function autoEncode($chars, $chrlen)
    {
        $idx = 0;
        while ($idx < $chrlen) {
            if ($this->processBinaryChars($chars, $idx, $chrlen)) {
                continue;
            }
            if ($this->processPunctPairs($chars, $idx, $chrlen)) {
                continue;
            }
            $this->processModeChars($chars, $idx, $chrlen);
        }
    }

    /**
     * Process mode characters.
     *
     * @param array $chars The array of characters.
     * @param int $idx The current character index.
     * @param int $chrlen The total number of characters to process.
     */
    protected function processModeChars(&$chars, &$idx, $chrlen)
    {
        $ord = $chars[$idx];
        if ($this->isSameMode($this->encmode, $ord)) {
            $mode = $this->encmode;
        } else {
            $mode = $this->charMode($ord);
        }
        $nchr = $this->countModeChars($chars, $idx, $chrlen, $mode);
        if ($this->encmode != $mode) {
            if (($nchr == 1) && (!empty(Data::SHIFT_MAP[$this->encmode][$mode]))) {
                $this->addShift($mode);
            } else {
                $this->addLatch($mode);
            }
        }
        $this->mergeTmpCwd();
        $idx += $nchr;
    }

    /**
    * Count consecutive characters in the same mode.
    *
    * @param array $chars The array of characters.
    * @param int $idx The current character index.
    * @param int $chrlen The total number of characters to process.
    * @param int $mode The current mode.
    *
    * @return int
    */
    protected function countModeChars(&$chars, $idx, $chrlen, $mode)
    {
        $this->tmpCdws = array();
        $nbits = Data::MODE_BITS[$mode];
        $count = 0;
        do {
            $ord = $chars[$idx];
            if (
                (!$this->isSameMode($mode, $ord))
                || (($idx < ($chrlen - 1)) && ($this->punctPairMode($ord, $chars[($idx + 1)]) > 0))
            ) {
                return $count;
            }
            $this->tmpCdws[] = array($nbits, $this->charEnc($mode, $ord));
            $count++;
            $idx++;
        } while ($idx < $chrlen);
        return $count;
    }

    /**
     * Process consecutive binary characters.
     *
     * @param array $chars The array of characters.
     * @param int $idx The current character index.
     * @param int $chrlen The total number of characters to process.
     *
     * @return bool True if binary characters have been found and processed.
     */
    protected function processBinaryChars(&$chars, &$idx, $chrlen)
    {
        $binchrs = $this->countBinaryChars($chars, $idx, $chrlen);
        if ($binchrs == 0) {
            return false;
        }
        $encmode = $this->encmode;
        $this->addShift(Data::MODE_BINARY);
        if ($binchrs > 62) {
            $this->addRawCwd(5, 0);
            $this->addRawCwd(11, ($binchrs - 31));
            $this->mergeTmpCwdRaw();
            $idx += $binchrs;
            $this->encmode = $encmode;
            return true;
        }
        if ($binchrs > 31) {
            $nbits = Data::MODE_BITS[Data::MODE_BINARY];
            $this->addRawCwd(5, 31);
            for ($bcw = 0; $bcw < 31; $bcw++) {
                $this->addRawCwd($nbits, $this->tmpCdws[$bcw][1]);
            }
            $this->addShift(Data::MODE_BINARY);
            $this->addRawCwd(5, ($binchrs - 31));
            for ($bcw = 31; $bcw < $binchrs; $bcw++) {
                $this->addRawCwd($nbits, $this->tmpCdws[$bcw][1]);
            }
            $idx += $binchrs;
            $this->encmode = $encmode;
            return true;
        }
        $this->addRawCwd(5, $binchrs);
        $this->mergeTmpCwdRaw();
        $idx += $binchrs;
        $this->encmode = $encmode;
        return true;
    }

    /**
    * Count consecutive binary characters.
    *
    * @param array $chars The array of characters.
    * @param int $idx The current character index.
    * @param int $chrlen The total number of characters to process.
    *
    * @return int
    *
    * @SuppressWarnings(PHPMD.CyclomaticComplexity)
    */
    protected function countBinaryChars(&$chars, $idx, $chrlen)
    {
        $this->tmpCdws = array();
        $count = 0;
        $nbits = Data::MODE_BITS[Data::MODE_BINARY];
        while (($idx < $chrlen) && ($count < 2048)) {
            $ord = $chars[$idx];
            if ($this->charMode($ord) != Data::MODE_BINARY) {
                return $count;
            }
            $this->tmpCdws[] = array($nbits, $ord);
            $count++;
            $idx++;
        }
        return $count;
    }

    /**
     * Process consecutive special Punctuation Pairs.
     *
     * @param array $chars The array of characters.
     * @param int $idx The current character index.
     * @param int $chrlen The total number of characters to process.
     *
     * @return bool True if pair characters have been found and processed.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processPunctPairs(&$chars, &$idx, $chrlen)
    {

        $ppairs = $this->countPunctPairs($chars, $idx, $chrlen);
        if ($ppairs == 0) {
            return false;
        }
        switch ($this->encmode) {
            case Data::MODE_PUNCT:
                break;
            case Data::MODE_MIXED:
                $this->addLatch(Data::MODE_PUNCT);
                break;
            case Data::MODE_UPPER:
            case Data::MODE_LOWER:
                if ($ppairs > 1) {
                    $this->addLatch(Data::MODE_PUNCT);
                }
                break;
            case Data::MODE_DIGIT:
                $common = $this->countPunctAndDigitChars($chars, $idx, $chrlen);
                $clen = count($common);
                if (($clen > 0) && ($clen < 6)) {
                    $this->tmpCdws = $common;
                    $this->mergeTmpCwdRaw();
                    $idx += $clen;
                    return true;
                }
                if ($ppairs > 2) {
                    $this->addLatch(Data::MODE_PUNCT);
                }
                break;
        }
        $this->mergeTmpCwd(Data::MODE_PUNCT);
        $idx += ($ppairs * 2);
        return true;
    }

    /**
     * Count consecutive special Punctuation Pairs.
     *
     * @param array $chars The array of characters.
     * @param int $idx The current character index.
     * @param int $chrlen The total number of characters to process.
     *
     * @return int
     */
    protected function countPunctPairs(&$chars, $idx, $chrlen)
    {
        $this->tmpCdws = array();
        $pairs = 0;
        $maxidx = $chrlen - 1;
        while ($idx < $maxidx) {
            $pmode = $this->punctPairMode($chars[$idx], $chars[($idx + 1)]);
            if ($pmode == 0) {
                return $pairs;
            }
            $this->tmpCdws[] = array(5, $pmode);
            $pairs++;
            $idx += 2;
        }
        return $pairs;
    }

    /**
     * Counts the number of consecutive charcters that are in both PUNCT or DIGIT modes.
     * Returns the array with the codewords.
     *
     * @param array &$chars The string to count the characters in.
     * @param int   $idx    The starting index to count from.
     * @param int   $chrlen The length of the string to count.
     *
     * @return array
     */
    protected function countPunctAndDigitChars(&$chars, $idx, $chrlen)
    {
        $words = array();
        while ($idx < $chrlen) {
            $ord = $chars[$idx];
            if (!$this->isPunctAndDigitChar($ord)) {
                return $words;
            }
            $words[] = array(4, $this->charEnc(Data::MODE_DIGIT, $ord));
            $idx++;
        }
        return $words;
    }
}
