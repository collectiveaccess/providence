<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker: */

/**
 * Parser for MARC records
 *
 * This package is based on the PHP MARC package, originally called "php-marc",
 * that is part of the Emilda Project (http://www.emilda.org). Christoffer
 * Landtman generously agreed to make the "php-marc" code available under the
 * GNU LGPL so it could be used as the basis of this PEAR package.
 * 
 * PHP version 5
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  File_Formats
 * @package   File_MARC
 * @author    Christoffer Landtman <landtman@realnode.com>
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2003-2008 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Subfield
/**
 * The File_MARC_Subfield class represents a single subfield in a MARC
 * record field.
 *
 * Represents a subfield within a MARC field and implements all management
 * functions related to a single subfield. This class also implements
 * the possibility of duplicate subfields within a single field, for example
 * 650 _z Test1 _z Test2.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Christoffer Landtman <landtman@realnode.com>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Subfield
{
    // {{{ properties
    /**
     * Subfield code, e.g. _a, _b
     * @var string
     */
    protected $code;

    /**
     * Data contained by the subfield
     * @var string
     */
    protected $data;

    /**
     * Position of the subfield
     * @var int
     */
    protected $position;

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * File_MARC_Subfield constructor
     *
     * Create a new subfield to represent the code and data
     *
     * @param string $code Subfield code
     * @param string $data Subfield data
     */
    function __construct($code, $data)
    {
        $this->code = $code;
        $this->data = $data;
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Destroys the subfield
     */
    function __destruct()
    {
        $this->code = null;
        $this->data = null;
        $this->position = null;
    }
    // }}}

    // {{{ Explicit destructor: function delete()
    /**
     * Destroys the subfield
     *
     * @return true
     */
    function delete()
    {
        $this->__destruct();
    }
    // }}}

    // {{{ getCode()
    /**
     * Return code of the subfield
     *
     * @return string Tag name
     */
    function getCode()
    {
        return (string)$this->code;
    }
    // }}}

    // {{{ getData()
    /**
     * Return data of the subfield
     *
     * @return string data
     */
    function getData()
    {
        return (string)$this->data;
    }
    // }}}

    // {{{ getPosition()
    /**
     * Return position of the subfield
     *
     * @return int data
     */
    function getPosition()
    {
        return $this->position;
    }
    // }}}

    // {{{ __toString()
    /**
     * Return string representation of subfield
     *
     * @return string String representation
     */
    public function __toString()
    {
        $pretty = '[' . $this->getCode() . ']: ' . $this->getData();
        return $pretty;
    }
    // }}}

    // {{{ toRaw()
    /**
     * Return the USMARC representation of the subfield
     *
     * @return string USMARC representation
     */
    function toRaw()
    {
        $result = File_MARC::SUBFIELD_INDICATOR.$this->code.$this->data;
        return (string)$result;
    }
    // }}}

    // {{{ setCode()
    /**
     * Sets code of the subfield
     *
     * @param string $code new code for the subfield
     *
     * @return string code 
     */
    function setCode($code)
    {
        if ($code) {
            // could check more stringently; m/[a-Z]/ or the likes
            $this->code = $code;
        } else {
            // code must be _something_; raise error
            return false;
        }
        return true;
    }
    // }}}

    // {{{ setData()
    /**
     * Sets data of the subfield
     *
     * @param string $data new data for the subfield
     *
     * @return string data
     */
    function setData($data)
    {
        $this->data = $data;
        return true;
    }
    // }}}

    // {{{ setPosition()
    /**
     * Sets position of the subfield
     *
     * @param string $pos new position of the subfield
     *
     * @return void
     */
    function setPosition($pos)
    {
        $this->position = $pos;
    }
    // }}}

    // {{{ isEmpty()
    /**
     * Checks whether the subfield is empty or not
     *
     * @return bool True or false
     */
    function isEmpty()
    {
        // There is data
        if (strlen($this->data)) {
            return false;
        }

        // There is no data
        return true;
    }
    // }}}
}
// }}}

