<?php

/**
 * ImbPreTest.php
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * This file is part of tc-lib-barcode software library.
 */

namespace Test\Linear;

use PHPUnit\Framework\TestCase;
use Test\TestUtil;

/**
 * Barcode class test
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 */
class ImbPreTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Barcode\Barcode();
    }

    public function testGetGrid()
    {
        $testObj = $this->getTestObject();
        $bobj = $testObj->getBarcodeObj(
            'IMBPRE',
            'fatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdfatdf'
        );
        $grid = $bobj->getGrid();
        $expected = "101000001010000010100000101000001010000010100000101000001010"
            . "000010100000101000001010000010100000101000001010000010100000101000001\n"
            . "1010101010101010101010101010101010101010101010101010101010101010101"
            . "01010101010101010101010101010101010101010101010101010101010101\n"
            . "1000001010000010100000101000001010000010100000101000001010000010100"
            . "00010100000101000001010000010100000101000001010000010100000101\n";
        $this->assertEquals($expected, $grid);
    }

    public function testInvalidInput()
    {
        $this->bcExpectException('\Com\Tecnick\Barcode\Exception');
        $testObj = $this->getTestObject();
        $testObj->getBarcodeObj('IMBPRE', 'fatd');
    }
}
