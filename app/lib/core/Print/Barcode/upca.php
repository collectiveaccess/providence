<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */

/**
 * Barcode_upca class
 *
 * Renders UPC-A barcodes
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
 * @author     Jeffrey K. Brown <jkb@darkfantastic.net>
 * @author     Didier Fournout <didier.fournout@nyc.fr>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: upca.php,v 1.5 2006/12/13 19:33:03 cweiske Exp $
 * @link       http://pear.php.net/package/Barcode
 */

#
# MODIFIED for use with PHPWeblib2
# Mods (c) 2008 Whirl-i-Gig
#


/**
 * Barcode_upca class
 *
 * Package which provides a method to create UPC-A barcode using GD library.
 *
 * Slightly Modified ean13.php to get upca.php I needed a way to print
 * UPC-A bar codes on a PHP page.  The Barcode class seemed like
 * the best way to do it, so I modified ean13 to print in the UPC-A format.
 * Checked the bar code tables against some documentation below (no errors)
 * and validated the changes with my trusty cue-cat.
 * http://www.indiana.edu/~atmat/units/barcodes/bar_t4.htm
 *
 * @category   Image
 * @package    Barcode
 * @author     Jeffrey K. Brown <jkb@darkfantastic.net>
 * @author     Didier Fournout <didier.fournout@nyc.fr>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Barcode
 */
class Barcode_upca extends Barcode
{

	var $_printText = false; // print number below barcode?
	
    /**
     * Barcode type
     * @var string
     */
    var $_type = 'upca';

    /**
     * Barcode height
     *
     * @var integer
     */
    var $_barcodeheight = 50;

    /**
     * Font use to display text
     *
     * @var integer
     */
    var $_font = 2;  // gd internal small font

    /**
     * Bar width
     *
     * @var integer
     */
    var $_barwidth = 1;


    /**
     * Number set
     * @var array
     */
    var $_number_set = array(
           '0' => array(
                    'A' => array(0,0,0,1,1,0,1),
                    'B' => array(0,1,0,0,1,1,1),
                    'C' => array(1,1,1,0,0,1,0)
                        ),
           '1' => array(
                    'A' => array(0,0,1,1,0,0,1),
                    'B' => array(0,1,1,0,0,1,1),
                    'C' => array(1,1,0,0,1,1,0)
                        ),
           '2' => array(
                    'A' => array(0,0,1,0,0,1,1),
                    'B' => array(0,0,1,1,0,1,1),
                    'C' => array(1,1,0,1,1,0,0)
                        ),
           '3' => array(
                    'A' => array(0,1,1,1,1,0,1),
                    'B' => array(0,1,0,0,0,0,1),
                    'C' => array(1,0,0,0,0,1,0)
                        ),
           '4' => array(
                    'A' => array(0,1,0,0,0,1,1),
                    'B' => array(0,0,1,1,1,0,1),
                    'C' => array(1,0,1,1,1,0,0)
                        ),
           '5' => array(
                    'A' => array(0,1,1,0,0,0,1),
                    'B' => array(0,1,1,1,0,0,1),
                    'C' => array(1,0,0,1,1,1,0)
                        ),
           '6' => array(
                    'A' => array(0,1,0,1,1,1,1),
                    'B' => array(0,0,0,0,1,0,1),
                    'C' => array(1,0,1,0,0,0,0)
                        ),
           '7' => array(
                    'A' => array(0,1,1,1,0,1,1),
                    'B' => array(0,0,1,0,0,0,1),
                    'C' => array(1,0,0,0,1,0,0)
                        ),
           '8' => array(
                    'A' => array(0,1,1,0,1,1,1),
                    'B' => array(0,0,0,1,0,0,1),
                    'C' => array(1,0,0,1,0,0,0)
                        ),
           '9' => array(
                    'A' => array(0,0,0,1,0,1,1),
                    'B' => array(0,0,1,0,1,1,1),
                    'C' => array(1,1,1,0,1,0,0)
                        )
        );


    var $_number_set_left_coding = array(
           '0' => array('A','A','A','A','A','A'),
           '1' => array('A','A','B','A','B','B'),
           '2' => array('A','A','B','B','A','B'),
           '3' => array('A','A','B','B','B','A'),
           '4' => array('A','B','A','A','B','B'),
           '5' => array('A','B','B','A','A','B'),
           '6' => array('A','B','B','B','A','A'),
           '7' => array('A','B','A','B','A','B'),
           '8' => array('A','B','A','B','B','A'),
           '9' => array('A','B','B','A','B','A')
        );



    /**
     * Draws a UPC-A image barcode
     *
     * @param   string $text     A text that should be in the image barcode
     * @param   string $imgtype  The image type that will be generated
     *
     * @return  image            The corresponding Interleaved 2 of 5 image barcode
     *
     * @access  public
     *
     * @author  Jeffrey K. Brown <jkb@darkfantastic.net>
     * @author  Didier Fournout <didier.fournout@nyc.fr>
     *
     */
    function &draw($text, $imgtype = 'png')
    {
        $error = false;
        if ((is_numeric($text)==false) || (strlen($text)!=12)) {
            $barcodewidth= (12 * 7 * $this->_barwidth) + 3 + 5 + 3 + 2 * (imagefontwidth($this->_font)+1);
            $error = true;
        } else {
            // Calculate the barcode width
            $barcodewidth = (strlen($text)) * (7 * $this->_barwidth)
                + 3 // left
                + 5 // center
                + 3 // right
                + imagefontwidth($this->_font)+1
                + imagefontwidth($this->_font)+1   // check digit's padding
                ;
        }

        $barcodelongheight = (int) (imagefontheight($this->_font)/2)+$this->_barcodeheight;

        // Create the image
        $img = ImageCreate($barcodewidth, $barcodelongheight+ imagefontheight($this->_font)+1);

        // Alocate the black and white colors
        $black = ImageColorAllocate($img, 0, 0, 0);
        $white = ImageColorAllocate($img, 255, 255, 255);

        // Fill image with white color
        imagefill($img, 0, 0, $white);

        if ($error) {
            $imgerror = ImageCreate($barcodewidth, $barcodelongheight+imagefontheight($this->_font)+1);
            $red      = ImageColorAllocate($imgerror, 255, 0, 0);
            $black    = ImageColorAllocate($imgerror, 0, 0, 0);
            imagefill($imgerror, 0, 0, $red);

			
            if ($this->_printText) {
				imagestring(
					$imgerror,
					$this->_font,
					$barcodewidth / 2 - (10/2 * imagefontwidth($this->_font)),
					$this->_barcodeheight / 2,
					'Code Error',
					$black
				);
			}
        }

        // get the first digit which is the key for creating the first 6 bars
        $key = substr($text,0,1);

        // Initiate x position
        $xpos = 0;

        // print first digit
        imagestring($img, $this->_font, $xpos, $this->_barcodeheight, $key, $black);
        $xpos= imagefontwidth($this->_font) + 1;



        // Draws the left guard pattern (bar-space-bar)
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;
        // space
        $xpos += $this->_barwidth;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;

        $set_array = $this->_number_set_left_coding[$key];



        foreach ($this->_number_set['0'][$set_array[0]] as $bar) {
            if ($bar) {
                imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
            }
            $xpos += $this->_barwidth;
        }



        // Draw left $text contents
        for ($idx = 1; $idx < 6; $idx ++) {
            $value=substr($text,$idx,1);
            imagestring ($img, $this->_font, $xpos+1, $this->_barcodeheight, $value, $black);

            //foreach ($this->_number_set[$value][$set_array[$idx-1]] as $bar) {

            foreach ($this->_number_set[$value][$set_array[$idx]] as $bar) {
                if ($bar) {
                    imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $this->_barcodeheight, $black);
                }
                $xpos += $this->_barwidth;
            }
        }


        // Draws the center pattern (space-bar-space-bar-space)
        // space
        $xpos += $this->_barwidth;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;
        // space
        $xpos += $this->_barwidth;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;
        // space
        $xpos += $this->_barwidth;


        // Draw right $text contents
        for ($idx = 6; $idx < 11; $idx ++) {
            $value=substr($text,$idx,1);
            imagestring ($img, $this->_font, $xpos+1, $this->_barcodeheight, $value, $black);
            foreach ($this->_number_set[$value]['C'] as $bar) {
                if ($bar) {
                    imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $this->_barcodeheight, $black);
                }
                $xpos += $this->_barwidth;
            }
        }



        $value = substr($text,11,1);
        foreach ($this->_number_set[$value]['C'] as $bar) {
            if ($bar) {
                imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
            }
            $xpos += $this->_barwidth;
        }



        // Draws the right guard pattern (bar-space-bar)
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;
        // space
        $xpos += $this->_barwidth;
        // bar
        imagefilledrectangle($img, $xpos, 0, $xpos + $this->_barwidth - 1, $barcodelongheight, $black);
        $xpos += $this->_barwidth;


        // Print Check Digit
        imagestring($img, $this->_font, $xpos+1, $this->_barcodeheight, $value, $black);

        if ($error) {
            return $imgerror;
        } else {
            return $img;
        }
    } // function create

} // class
?>