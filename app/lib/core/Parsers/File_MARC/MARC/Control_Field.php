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
 * @version   CVS: $Id: Control_Field.php 301737 2010-07-31 04:14:44Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Control_Field extends File_MARC_Field
/**
 * The File_MARC_Control_Field class represents a single control field
 * in a MARC record.
 *
 * A MARC control field consists of a tag name and control data.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Christoffer Landtman <landtman@realnode.com>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Control_Field extends File_MARC_Field
{

    // {{{ Properties
    /**
     * Value of field, if field is a Control field
     * @var string
     */
    protected $data;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Field init function
     *
     * Create a new {@link File_MARC_Control_Field} object from passed arguments
     *
     * @param string $tag  tag
     * @param string $data control field data
     * @param string $ind1 placeholder for class strictness
     * @param string $ind2 placeholder for class strictness
     */
    function __construct($tag, $data, $ind1 = null, $ind2 = null) 
    {
        $this->data = $data;
        parent::__construct($tag);

    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Destroys the control field
     */
    function __destruct()
    {
        $this->data = null;
        parent::__destruct();
    }
    // }}}

    // {{{ Explicit destructor: function delete()
    /**
     * Destroys the control field
     *
     * @return true
     */
    function delete()
    {
        $this->__destruct();
    }
    // }}}

    // {{{ getData()
    /**
     * Get control field data
     *
     * @return string returns data in control field
     */
    function getData()
    {
        return (string)$this->data;
    }
    // }}}

    // {{{ isEmpty()
    /**
     * Is empty
     *
     * Checks if the field contains data
     *
     * @return bool Returns true if the field is empty, otherwise false
     */
    function isEmpty()
    {
        return ($this->data) ? false : true;
    }
    // }}}

    // {{{ setData()
    /**
     * Set control field data
     *
     * @param string $data data for the control field
     *
     * @return bool returns the new data in the control field
     */
    function setData($data)
    {
        $this->data = $data;
        return $this->getData();
    }
    // }}}

    // {{{ __toString()
    /**
     * Return as a formatted string
     *
     * Return the control field as a formatted string for pretty printing
     *
     * @return string Formatted output of control Field
     */
    function __toString()
    {
        return sprintf("%3s     %s", $this->tag, $this->data);
    }
    // }}}

    // {{{ toRaw()
    /**
     * Return as raw MARC
     *
     * Return the control field formatted in Raw MARC for saving into MARC files
     *
     * @return string Raw MARC
     */
    function toRaw()
    {
        return (string)$this->data.File_MARC::END_OF_FIELD;
    }
    // }}}

}
// }}}

