<?php

/**
 * RgbTest.php
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
 * Rgb Color class test
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Color
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-color
 */
class RgbTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0.25,
                'green' => 0.50,
                'blue'  => 0.75,
                'alpha' => 0.85
            )
        );
    }

    public function testGetType()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getType();
        $this->assertEquals('RGB', $res);
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
        $this->assertEquals('#4080bfd9', $res);
    }

    public function testGetRgbHexColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getRgbHexColor();
        $this->assertEquals('#4080bf', $res);
    }

    public function testGetArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getArray();
        $this->assertEquals(
            array(
                'R' => 0.25,
                'G' => 0.50,
                'B' => 0.75,
                'A' => 0.85
            ),
            $res
        );
    }

    public function testGetNormalizedArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getNormalizedArray(255);
        $this->assertEquals(
            array(
                'R' => 64,
                'G' => 128,
                'B' => 191,
                'A' => 0.85
            ),
            $res
        );
    }

    public function testGetCssColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getCssColor();
        $this->assertEquals('rgba(25%,50%,75%,0.85)', $res);
    }

    public function testGetJsPdfColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getJsPdfColor();
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0.25,
                'green' => 0.50,
                'blue'  => 0.75,
                'alpha' => 0
            )
        );
        $res = $col->getJsPdfColor();
        $this->assertEquals('["T"]', $res);
    }

    public function testGetComponentsString()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getComponentsString();
        $this->assertEquals('0.250000 0.500000 0.750000', $res);
    }

    public function testGetPdfColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getPdfColor();
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);

        $res = $testObj->getPdfColor(false);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);

        $res = $testObj->getPdfColor(true);
        $this->assertEquals('0.250000 0.500000 0.750000 RG' . "\n", $res);
    }

    public function testToGrayArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toGrayArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'gray'  => 0.465,
                'alpha' => 0.85
            ),
            $res
        );
    }

    public function testToRgbArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toRgbArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'red'   => 0.25,
                'green' => 0.50,
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
        $this->bcAssertEqualsWithDelta(
            array(
                'hue'        => 0.583,
                'saturation' => 0.5,
                'lightness'  => 0.5,
                'alpha'      => 0.85
            ),
            $res
        );

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0,
                'green' => 0,
                'blue'  => 0,
                'alpha' => 1
            )
        );
        $res = $col->toHslArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'hue'        => 0,
                'saturation' => 0,
                'lightness'  => 0,
                'alpha'      => 1
            ),
            $res
        );

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0.1,
                'green' => 0.3,
                'blue'  => 0.2,
                'alpha' => 1
            )
        );
        $res = $col->toHslArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'hue'        => 0.416,
                'saturation' => 0.500,
                'lightness'  => 0.200,
                'alpha'      => 1
            ),
            $res
        );

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0.3,
                'green' => 0.2,
                'blue'  => 0.1,
                'alpha' => 1
            )
        );
        $res = $col->toHslArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'hue'        => 0.0833,
                'saturation' => 0.500,
                'lightness'  => 0.200,
                'alpha'      => 1
            ),
            $res
        );

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 1,
                'green' => 0.1,
                'blue'  => 0.9,
                'alpha' => 1
            )
        );
        $res = $col->toHslArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'hue'        => 0.852,
                'saturation' => 1,
                'lightness'  => 0.55,
                'alpha'      => 1
            ),
            $res
        );
    }

    public function testToCmykArray()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->toCmykArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'cyan'    => 0.666,
                'magenta' => 0.333,
                'yellow'  => 0,
                'key'     => 0.25,
                'alpha'   => 0.85
            ),
            $res
        );

        $col = new \Com\Tecnick\Color\Model\Rgb(
            array(
                'red'   => 0,
                'green' => 0,
                'blue'  => 0,
                'alpha' => 1
            )
        );
        $res = $col->toCmykArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'cyan'    => 0,
                'magenta' => 0,
                'yellow'  => 0,
                'key'     => 1,
                'alpha'   => 1
            ),
            $res
        );
    }

    public function testInvertColor()
    {
        $testObj = $this->getTestObject();
        $testObj->invertColor();
        $res = $testObj->toRgbArray();
        $this->bcAssertEqualsWithDelta(
            array(
                'red'   => 0.75,
                'green' => 0.50,
                'blue'  => 0.25,
                'alpha' => 0.85
            ),
            $res
        );
    }
}
