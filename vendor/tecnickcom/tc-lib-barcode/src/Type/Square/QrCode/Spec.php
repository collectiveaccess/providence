<?php

/**
 * Spec.php
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2010-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * This file is part of tc-lib-barcode software library.
 */

namespace Com\Tecnick\Barcode\Type\Square\QrCode;

use Com\Tecnick\Barcode\Exception as BarcodeException;
use Com\Tecnick\Barcode\Type\Square\QrCode\Data;

/**
 * Com\Tecnick\Barcode\Type\Square\QrCode\Spec
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2010-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 */
class Spec extends \Com\Tecnick\Barcode\Type\Square\QrCode\SpecRs
{
    /**
     * Return maximum data code length (bytes) for the version.
     *
     * @param int $version Version
     * @param int $level   Error correction level
     *
     * @return int maximum size (bytes)
     */
    public function getDataLength($version, $level)
    {
        return (Data::CAPACITY[$version][Data::QRCAP_WORDS] - Data::CAPACITY[$version][Data::QRCAP_EC][$level]);
    }

    /**
     * Return maximum error correction code length (bytes) for the version.
     *
     * @param int $version Version
     * @param int $level   Error correction level
     *
     * @return int ECC size (bytes)
     */
    public function getECCLength($version, $level)
    {
        return Data::CAPACITY[$version][Data::QRCAP_EC][$level];
    }

    /**
     * Return the width of the symbol for the version.
     *
     * @param int $version Version
     *
     * @return int width
     */
    public function getWidth($version)
    {
        return Data::CAPACITY[$version][Data::QRCAP_WIDTH];
    }

    /**
     * Return the numer of remainder bits.
     *
     * @param int $version Version
     *
     * @return int number of remainder bits
     */
    public function getRemainder($version)
    {
        return Data::CAPACITY[$version][Data::QRCAP_REMINDER];
    }

    /**
     * Return the maximum length for the mode and version.
     *
     * @param int $mode    Encoding mode
     * @param int $version Version
     *
     * @return int the maximum length (bytes)
     */
    public function maximumWords($mode, $version)
    {
        if ($mode == Data::ENC_MODES['ST']) {
            return 3;
        }
        if ($version <= 9) {
            $lval = 0;
        } elseif ($version <= 26) {
            $lval = 1;
        } else {
            $lval = 2;
        }
        $bits = Data::LEN_TABLE_BITS[$mode][$lval];
        $words = (1 << $bits) - 1;
        if ($mode == Data::ENC_MODES['KJ']) {
            $words *= 2; // the number of bytes is required
        }
        return $words;
    }

    /**
     * Return an array of ECC specification.
     *
     * @param int   $version Version
     * @param int   $level   Error correction level
     * @param array $spec    Array of ECC specification contains as following:
     *                       {# of type1 blocks, # of data code, # of ecc code, # of type2 blocks, # of data code}
     *
     * @return array spec
     */
    public function getEccSpec($version, $level, $spec)
    {
        if (count($spec) < 5) {
            $spec = array(0, 0, 0, 0, 0);
        }
        $bv1 = Data::ECC_TABLE[$version][$level][0];
        $bv2 = Data::ECC_TABLE[$version][$level][1];
        $data = $this->getDataLength($version, $level);
        $ecc = $this->getECCLength($version, $level);
        if ($bv2 == 0) {
            $spec[0] = $bv1;
            $spec[1] = (int)($data / $bv1); /* @phpstan-ignore-line */
            $spec[2] = (int)($ecc / $bv1); /* @phpstan-ignore-line */
            $spec[3] = 0;
            $spec[4] = 0;
        } else {
            $spec[0] = $bv1;
            $spec[1] = (int)($data / ($bv1 + $bv2));
            $spec[2] = (int)($ecc  / ($bv1 + $bv2));
            $spec[3] = $bv2;
            $spec[4] = $spec[1] + 1;
        }
        return $spec;
    }

    /**
     * Return BCH encoded format information pattern.
     *
     * @param int $maskNo Mask number
     * @param int $level  Error correction level
     *
     * @return int
     */
    public function getFormatInfo($maskNo, $level)
    {
        if (
            ($maskNo < 0)
            || ($maskNo > 7)
            || ($level < 0)
            || ($level > 3)
        ) {
            return 0;
        }
        return Data::FORMAT_INFO[$level][$maskNo];
    }
}
