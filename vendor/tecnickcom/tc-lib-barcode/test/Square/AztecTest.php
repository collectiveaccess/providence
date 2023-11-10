<?php

/**
 * AztecTest.php
 *
 * @since       2023-10-20
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2023-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * This file is part of tc-lib-barcode software library.
 */

namespace Test\Square;

use PHPUnit\Framework\TestCase;
use Test\TestUtil;

/**
 * AZTEC Barcode class test
 *
 * @since       2023-10-20
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2023-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 */
class AztecTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Barcode\Barcode();
    }

    public function testInvalidInput()
    {
        $this->bcExpectException('\Com\Tecnick\Barcode\Exception');
        $testObj = $this->getTestObject();
        $testObj->getBarcodeObj('AZTEC', '');
    }

    public function testCapacityException()
    {
        $this->bcExpectException('\Com\Tecnick\Barcode\Exception');
        $testObj = $this->getTestObject();
        $code = str_pad('', 2000, '0123456789');
        $testObj->getBarcodeObj('AZTEC,100,B,F,3', $code);
    }

    /**
     * @dataProvider getGridDataProvider
     */
    public function testGetGrid($options, $code, $expected)
    {
        $testObj = $this->getTestObject();
        $bobj = $testObj->getBarcodeObj('AZTEC' . $options, $code);
        $grid = $bobj->getGrid();
        $this->assertEquals($expected, md5($grid));
    }

    public static function getGridDataProvider()
    {
        return array(
            array(',100,A,A,0', 'A', 'c48da49052f674edc66fa02e52334b17'),
            array('', ' ABCDEFGHIJKLMNOPQRSTUVWXYZ', '74f1e68830f0c635cd01167245743098'),
            array('', ' abcdefghijklmnopqrstuvwxyz', '100ebf910c88922b0ccee88256ba0c81'),
            array('', ' ,.0123456789', 'ee2a70b7c88a9e0956b1896983e93f91'),
            array('', "\r" . '!"#$%&\'()*+,-./:;<=>?[]{}', '6965459e50f7c3029de42ef5dc5c1fdf'),
            array('', chr(1) . chr(2) . chr(3) . chr(4) . chr(5)
                . chr(6) . chr(7) . chr(8) . chr(9) . chr(10)
                . chr(11) . chr(12) . chr(13) . chr(27) . chr(28)
                . chr(29) . chr(30) . chr(31) . chr(64) . chr(92)
                . chr(94) . chr(95) . chr(96) . chr(124) . chr(126)
                . chr(127), 'b8961abf38519b529f7dc6a20e8f3e59'),
            array('', 'AaB0C#D' . chr(126), '9b1f2af28b8d9d222de93dfe6a09a047'),
            array('', 'aAb0c#d' . chr(126), 'f4c58cabbdb5d94fa0cc1c31d510936a'),
            array('', '#A$a%0&' . chr(126), 'a17634a1db6372efbf8ea25a303c38f8'),
            array('', chr(1) . 'A' . chr(1) . 'a' . chr(1) . '0' . chr(1) . '#', 'c1a585888c7a1eb424ff98bbf7b32d46'),
            array('', 'PUNCT pairs , . : ' . "\r\n", '35281793cc5247b291abb8e3fe5ed853'),
            array('', 'ABCDEabcdeABCDE012345ABCDE?[]{}ABCDE'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5), '4ae19b80469a1afff8e490f5afaa8b73'),
            array('', 'abcdeABCDEabcde012345abcde?[]{}abcde'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5), 'b0158bfe19c6fe20042128d59e40ca3b'),
            array('', '?[]{}ABCDE?[]{}abcde?[]{}012345?[]{}'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5), '71ba0ed8c308c93af6af7cd23a76355a'),
            array('', chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . 'ABCDE'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . 'abcde'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . '012345'
                . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . '?[]{}', 'f31e14be0b2c1f903e77af11e6c901b0'),
            array('', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit,'
                . ' sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
                . ' Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris'
                . ' nisi ut aliquip ex ea commodo consequat.'
                . ' Duis aute irure dolor in reprehenderit in voluptate velit esse'
                . ' cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat'
                . ' cupidatat non proident,' .
                ' sunt in culpa qui officia deserunt mollit anim id est laborum.', 'bb2b103d59e035a581fed0619090f89c'),
            array('', chr(128) . chr(129) . chr(130) . chr(131) . chr(132), 'da92b009c1f4430e2f62c76c5f708121'),
            array('', chr(128) . chr(129) . chr(130) . chr(131) . chr(132)
                . chr(133) . chr(134) . chr(135) . chr(136) . chr(137)
                . chr(138) . chr(139) . chr(140) . chr(141) . chr(142)
                . chr(143) . chr(144) . chr(145) . chr(146) . chr(147)
                . chr(148) . chr(149) . chr(150) . chr(151) . chr(152)
                . chr(153) . chr(154) . chr(155) . chr(156) . chr(157)
                . chr(158) . chr(159) . chr(160), 'f3dfdda6d6fdbd747c86f042fc649193'),
            array('', chr(128) . chr(129) . chr(130) . chr(131) . chr(132)
                . chr(133) . chr(134) . chr(135) . chr(136) . chr(137)
                . chr(138) . chr(139) . chr(140) . chr(141) . chr(142)
                . chr(143) . chr(144) . chr(145) . chr(146) . chr(147)
                . chr(148) . chr(149) . chr(150) . chr(151) . chr(152)
                . chr(153) . chr(154) . chr(155) . chr(156) . chr(157)
                . chr(158) . chr(159) . chr(160) . chr(161) . chr(162)
                . chr(163) . chr(164) . chr(165) . chr(166) . chr(167)
                . chr(168) . chr(169) . chr(170) . chr(171) . chr(172)
                . chr(173) . chr(174) . chr(175) . chr(176) . chr(177)
                . chr(178) . chr(179) . chr(180) . chr(181) . chr(182)
                . chr(183) . chr(184) . chr(185) . chr(186) . chr(187)
                . chr(188) . chr(189) . chr(190), 'ee473dc76e160671f3d1991777894323'),
            array('', '012345: : : : : : : : ', 'b7ae80e65d754dc17fe116aaddd33c24'),
            array('', '012345. , ', '92b442e5f1b33be91c576eddc12bcca7'),
            array('', '012345. , . , . , . , ', '598fd97d8a28b1514cd0bf88369c68e9'),
            array('', '~~~~~~. , ', 'c40fc61717a7e802d7458897197227b1'),
            array('', '******. , ', 'abbe0bdfdc10ad059ad2c415e79dab31'),
            array('', chr(188) . chr(189) . chr(190) . '. , ', 'c9ae209e0c6d03014753363affffee8b')
        );
    }

    /**
     * @dataProvider getStringDataProvider
     */
    public function testStrings($code)
    {
        $testObj = $this->getTestObject();
        $bobj = $testObj->getBarcodeObj('AZTEC,50,B,F', $code);
        $this->assertNotNull($bobj);
    }

    public static function getStringDataProvider()
    {
        return \Test\TestStrings::$data;
    }
}
