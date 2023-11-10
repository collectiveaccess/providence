<?php

/**
 * WebTest.php
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

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Web Color class test
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Color
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-color
 */
class WebTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Color\Web();
    }

    public function testGetMap()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getMap();
        $this->assertEquals(149, count($res));
    }

    public function testGetHexFromName()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getHexFromName('aliceblue');
        $this->assertEquals('f0f8ffff', $res);
        $res = $testObj->getHexFromName('color.yellowgreen');
        $this->assertEquals('9acd32ff', $res);
    }

    public function testGetHexFromNameInvalid()
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->getHexFromName('invalid');
    }

    public function testGetNameFromHex()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getNameFromHex('f0f8ffff');
        $this->assertEquals('aliceblue', $res);
        $res = $testObj->getNameFromHex('9acd32ff');
        $this->assertEquals('yellowgreen', $res);
    }

    public function testGetNameFromHexBad()
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->getNameFromHex('012345');
    }

    public function testExtractHexCode()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->extractHexCode('abc');
        $this->assertEquals('aabbccff', $res);
        $res = $testObj->extractHexCode('#abc');
        $this->assertEquals('aabbccff', $res);
        $res = $testObj->extractHexCode('abcd');
        $this->assertEquals('aabbccdd', $res);
        $res = $testObj->extractHexCode('#abcd');
        $this->assertEquals('aabbccdd', $res);
        $res = $testObj->extractHexCode('112233');
        $this->assertEquals('112233ff', $res);
        $res = $testObj->extractHexCode('#112233');
        $this->assertEquals('112233ff', $res);
        $res = $testObj->extractHexCode('11223344');
        $this->assertEquals('11223344', $res);
        $res = $testObj->extractHexCode('#11223344');
        $this->assertEquals('11223344', $res);
    }

    public function testExtractHexCodeBad()
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->extractHexCode('');
    }

    public function testGetRgbObjFromHex()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getRgbObjFromHex('#87ceebff');
        $this->assertEquals('#87ceebff', $res->getRgbaHexColor());
    }

    public function testGetRgbObjFromHexBad()
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->getRgbObjFromHex('xx');
    }

    public function testGetRgbObjFromName()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getRgbObjFromName('skyblue');
        $this->assertEquals('#87ceebff', $res->getRgbaHexColor());
    }

    public function testGetRgbObjFromNameBad()
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->getRgbObjFromName('xx');
    }

    public function testNormalizeValue()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->normalizeValue('50%', 50);
        $this->assertEquals(0.5, $res);
        $res = $testObj->normalizeValue(128, 255);
        $this->bcAssertEqualsWithDelta(0.5, $res);
    }

    public function testGetColorObj()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getColorObj('');
        $this->assertNull($res);
        $res = $testObj->getColorObj('t()');
        $this->assertNull($res);
        $res = $testObj->getColorObj('["T"]');
        $this->assertNull($res);
        $res = $testObj->getColorObj('transparent');
        $this->assertNull($res);
        $res = $testObj->getColorObj('color.transparent');
        $this->assertNull($res);
        $res = $testObj->getColorObj('royalblue');
        $this->assertEquals('#4169e1ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('#1a2b3c4d');
        $this->assertEquals('#1a2b3c4d', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('#1a2b3c');
        $this->assertEquals('#1a2b3cff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('#1234');
        $this->assertEquals('#11223344', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('#123');
        $this->assertEquals('#112233ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('["G",0.5]');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('["RGB",0.25,0.50,0.75]');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('["CMYK",0.666,0.333,0,0.25]');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('g(50%)');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('g(128)');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('rgb(25%,50%,75%)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('rgb(64,128,191)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('rgba(25%,50%,75%,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('rgba(64,128,191,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('hsl(210,50%,50%)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('hsla(210,50%,50%,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('cmyk(67%,33%,0,25%)');
        $this->assertEquals('#3f80bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('cmyk(67,33,0,25)');
        $this->assertEquals('#3f80bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('cmyka(67,33,0,25,0.85)');
        $this->assertEquals('#3f80bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObj('cmyka(67%,33%,0,25%,0.85)');
        $this->assertEquals('#3f80bfd9', $res->getRgbaHexColor());
    }

    public static function getBadColor()
    {
        return array(
            array('g(-)'),
            array('rgb(-)'),
            array('hsl(-)'),
            array('cmyk(-)'),
        );
    }
    /**
     * @dataProvider getBadColor
     */
    public function testGetColorObjBad($bad)
    {
        $this->bcExpectException('\Com\Tecnick\Color\Exception');
        $testObj = $this->getTestObject();
        $testObj->getColorObj($bad);
    }

    public function testGetRgbSquareDistance()
    {
        $testObj = $this->getTestObject();
        $cola = array('red' => 0, 'green' => 0, 'blue' => 0);
        $colb = array('red' => 1, 'green' => 1, 'blue' => 1);
        $dist = $testObj->getRgbSquareDistance($cola, $colb);
        $this->assertEquals(3, $dist);

        $cola = array('red' => 0.5, 'green' => 0.5, 'blue' => 0.5);
        $colb = array('red' => 0.5, 'green' => 0.5, 'blue' => 0.5);
        $dist = $testObj->getRgbSquareDistance($cola, $colb);
        $this->assertEquals(0, $dist);

        $cola = array('red' => 0.25, 'green' => 0.50, 'blue' => 0.75);
        $colb = array('red' => 0.50, 'green' => 0.75, 'blue' => 1.00);
        $dist = $testObj->getRgbSquareDistance($cola, $colb);
        $this->assertEquals(0.1875, $dist);
    }

    public function testGetClosestWebColor()
    {
        $testObj = $this->getTestObject();
        $col = array('red' => 1, 'green' => 0, 'blue' => 0);
        $color = $testObj->getClosestWebColor($col);
        $this->assertEquals('red', $color);

        $col = array('red' => 0, 'green' => 1, 'blue' => 0);
        $color = $testObj->getClosestWebColor($col);
        $this->assertEquals('lime', $color);

        $col = array('red' => 0, 'green' => 0, 'blue' => 1);
        $color = $testObj->getClosestWebColor($col);
        $this->assertEquals('blue', $color);

        $col = array('red' => 0.33, 'green' => 0.4, 'blue' => 0.18);
        $color = $testObj->getClosestWebColor($col);
        $this->assertEquals('darkolivegreen', $color);
    }
}
