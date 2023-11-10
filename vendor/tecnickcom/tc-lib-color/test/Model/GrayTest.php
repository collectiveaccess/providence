<?php

/**
 * GrayTest.php
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Color
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-color
 *
 * This file is part of tc-lib-color software library.
 */

namespace Test\Model;

use PHPUnit\Framework\TestCase;
use Test\TestUtil;

/**
 * Gray Color class test
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Color
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-color
 */
class GrayTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Color\Model\Gray(
            array(
                'gray'  => 0.75,
                'alpha' => 0.85
            )
        );
    }

    public function testGetType()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getType();
        $this->assertEquals('GRAY', $res);
    }

    public function testGetNormalizedValue()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getNormalizedValue(0.5, 255);
        $this->assertEquals(128, $res);
    }

    public function testGetHexValue()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getHexValue(0.5, 255);
        $this->assertEquals('80', $res);
    }

    public function testGetRgbaHexColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getRgbaHexColor();
        $this->assertEquals('#bfbfbfd9', $res);
    }

    public function testGetRgbHexColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getRgbHexColor();
        $this->assertEquals('#bfbfbf', $res);
    }

    public function testGetArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getArray();
        $this->assertEquals(array('G' => 0.75, 'A' => 0.85), $res);
    }

    public function testGetNormalizedArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getNormalizedArray(255);
        $this->assertEquals(array('G' => 191, 'A' => 0.85), $res);
    }

    public function testGetCssColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getCssColor();
        $this->assertEquals('rgba(75%,75%,75%,0.85)', $res);
    }

    public function testGetJsPdfColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getJsPdfColor();
        $this->assertEquals('["G",0.750000]', $res);

        $col = new \Com\Tecnick\Color\Model\Gray(
            array(
                'gray'    => 0.5,
                'alpha'   => 0
            )
        );
        $res = $col->getJsPdfColor();
        $this->assertEquals('["T"]', $res);
    }

    public function testGetComponentsString()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getComponentsString();
        $this->assertEquals('0.750000', $res);
    }

    public function testGetPdfColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getPdfColor();
        $this->assertEquals('0.750000 g' . "\n", $res);

        $res = $testObj->getPdfColor(false);
        $this->assertEquals('0.750000 g' . "\n", $res);

        $res = $testObj->getPdfColor(true);
        $this->assertEquals('0.750000 G' . "\n", $res);
    }

    public function testToGrayArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toGrayArray();
        $this->assertEquals(
            array(
                'gray'  => 0.75,
                'alpha' => 0.85
            ),
            $res
        );
    }

    public function testToRgbArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toRgbArray();
        $this->assertEquals(
            array(
                'red'   => 0.75,
                'green' => 0.75,
                'blue'  => 0.75,
                'alpha' => 0.85
            ),
            $res
        );
    }

    public function testToHslArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toHslArray();
        $this->assertEquals(
            array(
                'hue'        => 0,
                'saturation' => 0,
                'lightness'  => 0.75,
                'alpha'      => 0.85
            ),
            $res
        );
    }

    public function testToCmykArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toCmykArray();
        $this->assertEquals(
            array(
                'cyan'    => 0,
                'magenta' => 0,
                'yellow'  => 0,
                'key'     => 0.75,
                'alpha'   => 0.85
            ),
            $res
        );
    }

    public function testInvertColor()
    {
        $testObj = $this->getTestObject();
        $testObj->invertColor();
        $res = $testObj->toGrayArray();
        $this->assertEquals(
            array(
                'gray'  => 0.25,
                'alpha' => 0.85
            ),
            $res
        );
    }
}
