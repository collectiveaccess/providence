<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */

/**
 * Barcode_postnet class
 *
 * Renders PostNet barcodes
 *
 * PHP versions 4
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Image
 * @package    Barcode
 * @author     Josef "Jeff" Sipek <jeffpc@optonline.net>
 * @copyright  2005 Josef "Jeff" Sipek
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: postnet.php,v 1.3 2006/12/13 19:29:30 cweiske Exp $
 * @link       http://pear.php.net/package/Barcode
 */

 /*
  * Note:
  *
  * The generated barcode must fit the following criteria to be useable
  * by the USPS scanners:
  *
  * When printed, the dimensions should be:
  *
  *     tall bar:       1/10 inches     = 2.54 mm
  *  short bar:      1/20 inches     = 1.27 mm
  *  density:        22 bars/inch    = 8.66 bars/cm
  */

//require_once 'Image/Barcode.php';


/**
 * Barcode_postnet class
 *
 * Package which provides a method to create PostNet barcode using GD library.
 *
 * @category   Image
 * @package    Barcode
 * @author     Josef "Jeff" Sipek <jeffpc@optonline.net>
 * @copyright  2005 Josef "Jeff" Sipek
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: postnet.php,v 1.3 2006/12/13 19:29:30 cweiske Exp $
 * @link       http://pear.php.net/package/Barcode
 */
class Barcode_postnet extends Barcode
{
    /**
     * Barcode type
     * @var string
     */
    var $_type = 'postnet';

    /**
     * Bar short height
     *
     * @var integer
     */
    var $_barshortheight = 7;

    /**
     * Bar tall height
     *
     * @var integer
     */
    var $_bartallheight = 15;

    /**
     * Bar width / scaling factor
     *
     * @var integer
     */
    var $_barwidth = 2;

    /**
     * Coding map
     * @var array
     */
    var $_coding_map = array(
           '0' => '11000',
           '1' => '00011',
           '2' => '00101',
           '3' => '00110',
           '4' => '01001',
           '5' => '01010',
           '6' => '01100',
           '7' => '10001',
           '8' => '10010',
           '9' => '10100'
        );

    /**
     * Draws a PostNet image barcode
     *
     * @param  string $text     A text that should be in the image barcode
     * @param  string $imgtype  The image type that will be generated
     *
     * @return image            The corresponding Interleaved 2 of 5 image barcode
     *
     * @access public
     *
     * @author Josef "Jeff" Sipek <jeffpc@optonline.net>
     * @since  Barcode 0.3
     */

    function draw($text, $imgtype = 'png')
    {
        $text = trim($text);

        if (!preg_match('/[0-9]/', $text)) {
            return;
        }

        // Calculate the barcode width
        $barcodewidth = (strlen($text)) * 2 * 5 * $this->_barwidth + $this->_barwidth*3;

        // Create the image
        $img = ImageCreate($barcodewidth, $this->_bartallheight);

        // Alocate the black and white colors
        $black = ImageColorAllocate($img, 0, 0, 0);
        $white = ImageColorAllocate($img, 255, 255, 255);

        // Fill image with white color
        imagefill($img, 0, 0, $white);

        // Initiate x position
        $xpos = 0;

        // Draws the leader
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $this->_bartallheight, $black);
        $xpos += 2*$this->_barwidth;

        // Draw $text contents
        for ($idx = 0; $idx < strlen($text); $idx++) {
            $char  = substr($text, $idx, 1);

            for ($baridx = 0; $baridx < 5; $baridx++) {
                $elementheight = (substr($this->_coding_map[$char], $baridx, 1)) ?  0 : $this->_barshortheight;
                imagefilledrectangle($img, $xpos, $elementheight, $xpos + $this->_barwidth - 1, $this->_bartallheight, $black);
                $xpos += 2*$this->_barwidth;
            }
        }

        // Draws the trailer
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $this->_bartallheight, $black);
        $xpos += 2*$this->_barwidth;

        return $img;
    } // function create

} // class
?>