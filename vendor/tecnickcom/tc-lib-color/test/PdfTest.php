<?php

/**
 * PdfTest.php
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
 * Pdf Color class test
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Color
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-color
 */
class PdfTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Color\Pdf();
    }

    public function testGetJsMap()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getJsMap();
        $this->assertEquals(12, count($res));
    }

    public function testGetJsColorString()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getJsColorString('t()');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('["T"]');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('transparent');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('color.transparent');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('magenta');
        $this->assertEquals('color.magenta', $res);
        $res = $testObj->getJsColorString('#1a2b3c4d');
        $this->assertEquals('["RGB",0.101961,0.168627,0.235294]', $res);
        $res = $testObj->getJsColorString('#1a2b3c');
        $this->assertEquals('["RGB",0.101961,0.168627,0.235294]', $res);
        $res = $testObj->getJsColorString('#1234');
        $this->assertEquals('["RGB",0.066667,0.133333,0.200000]', $res);
        $res = $testObj->getJsColorString('#123');
        $this->assertEquals('["RGB",0.066667,0.133333,0.200000]', $res);
        $res = $testObj->getJsColorString('["G",0.5]');
        $this->assertEquals('["G",0.500000]', $res);
        $res = $testObj->getJsColorString('["RGB",0.25,0.50,0.75]');
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);
        $res = $testObj->getJsColorString('["CMYK",0.666,0.333,0,0.25]');
        $this->assertEquals('["CMYK",0.666000,0.333000,0.000000,0.250000]', $res);
        $res = $testObj->getJsColorString('g(50%)');
        $this->assertEquals('["G",0.500000]', $res);
        $res = $testObj->getJsColorString('g(128)');
        $this->assertEquals('["G",0.501961]', $res);
        $res = $testObj->getJsColorString('rgb(25%,50%,75%)');
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);
        $res = $testObj->getJsColorString('rgb(64,128,191)');
        $this->assertEquals('["RGB",0.250980,0.501961,0.749020]', $res);
        $res = $testObj->getJsColorString('rgba(25%,50%,75%,0.85)');
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);
        $res = $testObj->getJsColorString('rgba(64,128,191,0.85)');
        $this->assertEquals('["RGB",0.250980,0.501961,0.749020]', $res);
        $res = $testObj->getJsColorString('hsl(210,50%,50%)');
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);
        $res = $testObj->getJsColorString('hsla(210,50%,50%,0.85)');
        $this->assertEquals('["RGB",0.250000,0.500000,0.750000]', $res);
        $res = $testObj->getJsColorString('cmyk(67%,33%,0,25%)');
        $this->assertEquals('["CMYK",0.670000,0.330000,0.000000,0.250000]', $res);
        $res = $testObj->getJsColorString('cmyk(67,33,0,25)');
        $this->assertEquals('["CMYK",0.670000,0.330000,0.000000,0.250000]', $res);
        $res = $testObj->getJsColorString('cmyka(67,33,0,25,0.85)');
        $this->assertEquals('["CMYK",0.670000,0.330000,0.000000,0.250000]', $res);
        $res = $testObj->getJsColorString('cmyka(67%,33%,0,25%,0.85)');
        $this->assertEquals('["CMYK",0.670000,0.330000,0.000000,0.250000]', $res);
        $res = $testObj->getJsColorString('g(-)');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('rgb(-)');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('hsl(-)');
        $this->assertEquals('color.transparent', $res);
        $res = $testObj->getJsColorString('cmyk(-)');
        $this->assertEquals('color.transparent', $res);
    }

    public function testGetColorObject()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getColorObject('');
        $this->assertNull($res);
        $res = $testObj->getColorObject('[*');
        $this->assertNull($res);
        $res = $testObj->getColorObject('t()');
        $this->assertNull($res);
        $res = $testObj->getColorObject('["T"]');
        $this->assertNull($res);
        $res = $testObj->getColorObject('transparent');
        $this->assertNull($res);
        $res = $testObj->getColorObject('color.transparent');
        $this->assertNull($res);
        $res = $testObj->getColorObject('#1a2b3c4d');
        $this->assertEquals('#1a2b3c4d', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('#1a2b3c');
        $this->assertEquals('#1a2b3cff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('#1234');
        $this->assertEquals('#11223344', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('#123');
        $this->assertEquals('#112233ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('["G",0.5]');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('["RGB",0.25,0.50,0.75]');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('["CMYK",0.666,0.333,0,0.25]');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('g(50%)');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('g(128)');
        $this->assertEquals('#808080ff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('rgb(25%,50%,75%)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('rgb(64,128,191)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('rgba(25%,50%,75%,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('rgba(64,128,191,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('hsl(210,50%,50%)');
        $this->assertEquals('#4080bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('hsla(210,50%,50%,0.85)');
        $this->assertEquals('#4080bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('cmyk(67%,33%,0,25%)');
        $this->assertEquals('#3f80bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('cmyk(67,33,0,25)');
        $this->assertEquals('#3f80bfff', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('cmyka(67,33,0,25,0.85)');
        $this->assertEquals('#3f80bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('cmyka(67%,33%,0,25%,0.85)');
        $this->assertEquals('#3f80bfd9', $res->getRgbaHexColor());
        $res = $testObj->getColorObject('none');
        $this->assertEquals('0.000000 0.000000 0.000000 0.000000 k' . "\n", $res->getPdfColor());
        $res = $testObj->getColorObject('all');
        $this->assertEquals('1.000000 1.000000 1.000000 1.000000 k' . "\n", $res->getPdfColor());
        $res = $testObj->getColorObject('["G"]');
        $this->assertNull($res);
        $res = $testObj->getColorObject('["RGB"]');
        $this->assertNull($res);
        $res = $testObj->getColorObject('["CMYK"]');
        $this->assertNull($res);
        $res = $testObj->getColorObject('g(-)');
        $this->assertNull($res);
        $res = $testObj->getColorObject('rgb(-)');
        $this->assertNull($res);
        $res = $testObj->getColorObject('hsl(-)');
        $this->assertNull($res);
        $res = $testObj->getColorObject('cmyk(-)');
        $this->assertNull($res);
    }

    public function testGetPdfColor()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getPdfColor('magenta', false, 1);
        $this->assertEquals('/CS1 cs 1.000000 scn' . "\n", $res);
        $res = $testObj->getPdfColor('magenta', true, 1);
        $this->assertEquals('/CS1 CS 1.000000 SCN' . "\n", $res);
        $res = $testObj->getPdfColor('magenta', false, 0.5);
        $this->assertEquals('/CS1 cs 0.500000 scn' . "\n", $res);
        $res = $testObj->getPdfColor('magenta', true, 0.5);
        $this->assertEquals('/CS1 CS 0.500000 SCN' . "\n", $res);

        $res = $testObj->getPdfColor('t()', false, 1);
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('["T"]', false, 1);
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('transparent', false, 1);
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('color.transparent', false, 1);
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('magenta', false, 1);
        $this->assertEquals('/CS1 cs 1.000000 scn' . "\n", $res);
        $res = $testObj->getPdfColor('#1a2b3c4d', false, 1);
        $this->assertEquals('0.101961 0.168627 0.235294 rg' . "\n", $res);
        $res = $testObj->getPdfColor('#1a2b3c', false, 1);
        $this->assertEquals('0.101961 0.168627 0.235294 rg' . "\n", $res);
        $res = $testObj->getPdfColor('#1234', false, 1);
        $this->assertEquals('0.066667 0.133333 0.200000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('#123', false, 1);
        $this->assertEquals('0.066667 0.133333 0.200000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('["G",0.5]', false, 1);
        $this->assertEquals('0.500000 g' . "\n", $res);
        $res = $testObj->getPdfColor('["RGB",0.25,0.50,0.75]', false, 1);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('["CMYK",0.666,0.333,0,0.25]', false, 1);
        $this->assertEquals('0.666000 0.333000 0.000000 0.250000 k' . "\n", $res);
        $res = $testObj->getPdfColor('g(50%)', false, 1);
        $this->assertEquals('0.500000 g' . "\n", $res);
        $res = $testObj->getPdfColor('g(128)', false, 1);
        $this->assertEquals('0.501961 g' . "\n", $res);
        $res = $testObj->getPdfColor('rgb(25%,50%,75%)', false, 1);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('rgb(64,128,191)', false, 1);
        $this->assertEquals('0.250980 0.501961 0.749020 rg' . "\n", $res);
        $res = $testObj->getPdfColor('rgba(25%,50%,75%,0.85)', false, 1);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('rgba(64,128,191,0.85)', false, 1);
        $this->assertEquals('0.250980 0.501961 0.749020 rg' . "\n", $res);
        $res = $testObj->getPdfColor('hsl(210,50%,50%)', false, 1);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('hsla(210,50%,50%,0.85)', false, 1);
        $this->assertEquals('0.250000 0.500000 0.750000 rg' . "\n", $res);
        $res = $testObj->getPdfColor('cmyk(67%,33%,0,25%)', false, 1);
        $this->assertEquals('0.670000 0.330000 0.000000 0.250000 k' . "\n", $res);
        $res = $testObj->getPdfColor('cmyk(67,33,0,25)', false, 1);
        $this->assertEquals('0.670000 0.330000 0.000000 0.250000 k' . "\n", $res);
        $res = $testObj->getPdfColor('cmyka(67,33,0,25,0.85)', false, 1);
        $this->assertEquals('0.670000 0.330000 0.000000 0.250000 k' . "\n", $res);
        $res = $testObj->getPdfColor('cmyka(67%,33%,0,25%,0.85)', false, 1);
        $this->assertEquals('0.670000 0.330000 0.000000 0.250000 k' . "\n", $res);
        $res = $testObj->getPdfColor('g(-)');
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('rgb(-)');
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('hsl(-)');
        $this->assertEquals('', $res);
        $res = $testObj->getPdfColor('cmyk(-)');
        $this->assertEquals('', $res);
    }

    public function testGetPdfRgbComponents()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getPdfRgbComponents('');
        $this->assertEquals('', $res);

        $res = $testObj->getPdfRgbComponents('red');
        $this->assertEquals('1.000000 0.000000 0.000000', $res);

        $res = $testObj->getPdfRgbComponents('#00ff00');
        $this->assertEquals('0.000000 1.000000 0.000000', $res);

        $res = $testObj->getPdfRgbComponents('rgb(0,0,255)');
        $this->assertEquals('0.000000 0.000000 1.000000', $res);
    }
}
