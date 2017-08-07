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
 * @version   CVS: $Id: Data_Field.php 301737 2010-07-31 04:14:44Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Data_Field extends File_MARC_Field
/**
 * The File_MARC_Data_Field class represents a single field in a MARC record.
 *
 * A MARC data field consists of a tag name, two indicators which may be null,
 * and zero or more subfields represented by {@link File_MARC_Subfield} objects.
 * Subfields are held within a linked list structure.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Christoffer Landtman <landtman@realnode.com>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Data_Field extends File_MARC_Field
{

    // {{{ properties
    /**
     * Value of the first indicator
     * @var string
     */
    protected $ind1;

    /**
     * Value of the second indicator
     * @var string
     */
    protected $ind2;

    /**
     * Linked list of subfields
     * @var File_MARC_List
     */
    protected $subfields;

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * {@link File_MARC_Data_Field} constructor
     *
     * Create a new {@link File_MARC_Data_Field} object. The only required
     * parameter is a tag. This enables programs to build up new fields
     * programmatically.
     *
     * <code>
     * // Example: Create a new data field
     *
     * // We can optionally create an array of subfields first
     * $subfields[] = new File_MARC_Subfield('a', 'Scott, Daniel.');
     *
     * // Create the new 100 field complete with a _a subfield and an indicator
     * $new_field = new File_MARC_Data_Field('100', $subfields, 0, null);
     * </code>
     *
     * @param string $tag       tag
     * @param array  $subfields array of {@link File_MARC_Subfield} objects
     * @param string $ind1      first indicator
     * @param string $ind2      second indicator
     */
    function __construct($tag, array $subfields = null, $ind1 = null, $ind2 = null) 
    {
        $this->subfields = new File_MARC_List();

        parent::__construct($tag);

        $this->ind1 = $this->_validateIndicator($ind1);
        $this->ind2 = $this->_validateIndicator($ind2);

        // we'll let users add subfields after if they so desire
        if ($subfields) {
            $this->addSubfields($subfields);
        }
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Destroys the data field
     */
    function __destruct()
    {
        $this->subfields = null;
        $this->ind1 = null;
        $this->ind2 = null;
        parent::__destruct();
    }
    // }}}

    // {{{ Explicit destructor: function delete()
    /**
     * Destroys the data field
     *
     * @return true
     */
    function delete()
    {
        $this->__destruct();
    }
    // }}}

    // {{{ protected function _validateIndicator()
    /**
     * Validates an indicator field
     *
     * Validates the value passed in for an indicator. This routine ensures
     * that an indicator is a single character. If the indicator value is null,
     * then this method returns a single character.
     *
     * If the indicator value contains more than a single character, this
     * throws an exception.
     *
     * @param string $indicator Value of the indicator to be validated
     *
     * @return string Returns the indicator, or space if the indicator was null
     */
    private function _validateIndicator($indicator)
    {
        if ($indicator == null) {
            $indicator = ' ';
        } elseif (strlen($indicator) > 1) {
            $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_INDICATOR], array("tag" => $this->getTag(), "indicator" => $indicator));
            throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INVALID_INDICATOR);
        }
        return $indicator;
    }
    // }}}

    // {{{ appendSubfield()
    /**
     * Appends subfield to subfield list
     *
     * Adds a File_MARC_Subfield object to the end of the existing list
     * of subfields.
     *
     * @param File_MARC_Subfield $new_subfield The subfield to add
     *
     * @return File_MARC_Subfield the new File_MARC_Subfield object
     */
    function appendSubfield(File_MARC_Subfield $new_subfield)
    {
        /* Append as the last field in the record */
        $this->subfields->appendNode($new_subfield);
        return $new_subfield;
    }
    // }}}

    // {{{ prependSubfield()
    /**
     * Prepends subfield to subfield liss 
     *
     * Adds a File_MARC_Subfield object to the  start of the existing list
     * of subfields.
     *
     * @param File_MARC_Subfield $new_subfield The subfield to add
     *
     * @return File_MARC_Subfield the new File_MARC_Subfield object
     */
    function prependSubfield(File_MARC_Subfield $new_subfield)
    {
        $this->subfields->prependNode($new_subfield);
        return $new_subfield;
    }
    // }}}

    // {{{ insertSubfield()
    /**
     * Inserts a field in the MARC record relative to an existing field
     *
     * Inserts a {@link File_MARC_Subfield} object before or after an existing
     * subfield.
     *
     * @param File_MARC_Subfield $new_field      The subfield to add
     * @param File_MARC_Subfield $existing_field The target subfield
     * @param bool               $before         Insert the subfield before the existing subfield if true; after the existing subfield if false
     *
     * @return File_MARC_Subfield                The new subfield
     */
    function insertSubfield(File_MARC_Subfield $new_field, File_MARC_Subfield $existing_field, $before = false)
    {
        switch ($before) {
        /* Insert before the specified subfield in the record */
        case true:
            $this->subfields->insertNode($new_field, $existing_field, true);
            break;

        /* Insert after the specified subfield in the record */
        case false:
            $this->subfields->insertNode($new_field, $existing_field);
            break;

        default: 
             $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INSERTSUBFIELD_MODE], array("mode" => $mode));
             throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INSERTSUBFIELD_MODE);
            return false;
        }
        return $new_field;
    }
    // }}}

    // {{{ addSubfields()
    /**
     * Adds an array of subfields to a {@link File_MARC_Data_Field} object
     *
     * Appends subfields to existing subfields in the order in which
     * they appear in the array. For finer grained control of the subfield
     * order, use {@link appendSubfield()}, {@link prependSubfield()},
     * or {@link insertSubfield()} to add each subfield individually.
     *
     * @param array $subfields array of {@link File_MARC_Subfield} objects
     *
     * @return int returns the number of subfields that were added
     */
    function addSubfields(array $subfields)
    {
        /*
         * Just in case someone passes in a single File_MARC_Subfield
         * instead of an array
         */
        if ($subfields instanceof File_MARC_Subfield) {
            $this->appendSubfield($subfields);
            return 1;
        }

        // Add subfields
        $cnt = 0;
        foreach ($subfields as $subfield) {
            $this->appendSubfield($subfield);
            $cnt++;
        }

        return $cnt;
    }
    // }}}

    // {{{ deleteSubfield()
    /**
     * Delete a subfield from the field.
     *
     * @param File_MARC_Subfield $subfield The subfield to delete
     *
     * @return bool                         Success or failure
     */
    function deleteSubfield(File_MARC_Subfield $subfield)
    {
        if ($this->subfields->deleteNode($subfield)) {
            return true;
        }
        return false;
    }
    // }}}

    // {{{ getIndicator()
    /**
     * Get the value of an indicator
     *
     * @param int $ind number of the indicator (1 or 2)
     *
     * @return string returns indicator value if it exists, otherwise false
     */
    function getIndicator($ind)
    {
        if ($ind == 1) {
            return (string)$this->ind1;
        } elseif ($ind == 2) {
            return (string)$this->ind2;
        } else {
             $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_INDICATOR_REQUEST], array("indicator" => $indicator));
             throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INVALID_INDICATOR_REQUEST);
        }
        return false;
    }
    // }}}

    // {{{ setIndicator()
    /**
     * Set the value of an indicator
     *
     * @param int    $ind   number of the indicator (1 or 2)
     * @param string $value value of the indicator
     *
     * @return string       returns indicator value if it exists, otherwise false
     */
    function setIndicator($ind, $value)
    {
        switch ($ind) {

        case 1:
            $this->ind1 = $this->_validateIndicator($value);
            break;

        case 2:
            $this->ind2 = $this->_validateIndicator($value);
            break;

        default:
             $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_INDICATOR_REQUEST], array("indicator" => $ind));
             throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INVALID_INDICATOR_REQUEST);
            return false;
        }

        return $this->getIndicator($ind);
    }
    // }}}

    // {{{ getSubfield()
    /**
     * Returns the first subfield that matches a requested code.
     *
     * @param string $code subfield code for which the
     * {@link File_MARC_Subfield} is retrieved
     *
     * @return File_MARC_Subfield returns the first subfield that matches
     * $code, or false if no codes match $code
     */
    function getSubfield($code = null)
    {
        // iterate merrily through the subfields looking for the requested code
        foreach ($this->subfields as $sf) {
            if ($sf->getCode() == $code) {
                return $sf;
            }
        }

        // No matches were found
        return false;
    }
    // }}}

    // {{{ getSubfields()
    /**
     * Returns an array of subfields that match a requested code,
     * or a {@link File_MARC_List} that contains all of the subfields
     * if the requested code is null.
     *
     * @param string $code subfield code for which the
     * {@link File_MARC_Subfield} is retrieved
     *
     * @return File_MARC_List|array returns a linked list of all subfields
     * if $code is null, an array of {@link File_MARC_Subfield} objects if
     * one or more subfields match, or false if no codes match $code
     */
    function getSubfields($code = null)
    {
        $results = array();

        // return all subfields if no specific subfields were requested
        if ($code === null) {
            $results = $this->subfields;
            return $results;
        }

        // iterate merrily through the subfields looking for the requested code
        foreach ($this->subfields as $sf) {
            if ($sf->getCode() == $code) {
                $results[] = $sf;
            }
        }
        return $results;
    }
    // }}}

    // {{{ isEmpty()
    /**
     * Checks if the field is empty.
     *
     * Checks if the field is empty. If the field has at least one subfield
     * with data, it is not empty.
     *
     * @return bool Returns true if the field is empty, otherwise false
     */
    function isEmpty()
    {
        // If $this->subfields is null, we must have deleted it
        if (!$this->subfields) {
            return true;
        }

        // Iterate through the subfields looking for some data
        foreach ($this->subfields as $subfield) {
            // Check if subfield has data
            if (!$subfield->isEmpty()) {
                return false;
            }
        }
        // It is empty
        return true;
    }
    // }}}

    /**
     * ========== OUTPUT METHODS ==========
     */

    // {{{ __toString()
    /**
     * Return Field formatted
     *
     * Return Field as a formatted string.
     *
     * @return string Formatted output of Field
     */
    function __toString()
    {
        // Variables
        $lines = array();
        // Process tag and indicators
        $pre = sprintf("%3s %1s%1s", $this->tag, $this->ind1, $this->ind2);

        // Process subfields
        foreach ($this->subfields as $subfield) {
            $lines[] = sprintf("%6s _%1s%s", $pre, $subfield->getCode(), $subfield->getData());
            $pre = "";
        }

        return join("\n", $lines);
    }
    // }}}

    // {{{ toRaw()
    /**
     * Return Field in Raw MARC
     *
     * Return the Field formatted in Raw MARC for saving into MARC files
     *
     * @return string Raw MARC
     */
    function toRaw()
    {
        $subfields = array();
        foreach ($this->subfields as $subfield) {
            if (!$subfield->isEmpty()) {
                $subfields[] = $subfield->toRaw();
            }
        }
        return (string)$this->ind1.$this->ind2.implode("", $subfields).File_MARC::END_OF_FIELD;
    }
    // }}}
}
// }}}

