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
 * @version   CVS: $Id: List.php 268035 2008-10-30 17:01:14Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_List extends Structures_LinkedList_Double
/**
 * The File_MARC_List class extends the Structures_LinkedList_Double class
 * to override the key() method in a meaningful way for foreach() iterators.
 *
 * For the list of {@link File_MARC_Field} objects in a {@link File_MARC_Record}
 * object, the key() method returns the tag name of the field.
 * 
 * For the list of {@link File_MARC_Subfield} objects in a {@link
 * File_MARC_Data_Field} object, the key() method returns the code of
 * the subfield.
 *
 * <code>
 * // Iterate through the fields in a record with key=>value iteration
 * foreach ($record->getFields() as $tag=>$value) {
 *     print "$tag: ";
 *     if ($value instanceof File_MARC_Control_Field) {
 *         print $value->getData();
 *     }
 *     else {
 *         // Iterate through the subfields in this data field
 *         foreach ($value->getSubfields() as $code=>$subdata) {
 *             print "_$code";
 *         }
 *     }
 *     print "\n";
 * }
 * </code>
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_List extends Structures_LinkedList_Double
{
    // {{{ key()
    /**
     * Returns the tag for a {@link File_MARC_Field} object, or the code
     * for a {@link File_MARC_Subfield} object.
     *
     * This method enables you to use a foreach iterator to retrieve
     * the tag or code as the key for the iterator.
     *
     * @return string returns the tag or code
     */
    function key()
    {
        if ($this->current() instanceof File_MARC_Field) {
            return $this->current()->getTag();
        } elseif ($this->current() instanceof File_MARC_Subfield) {
            return $this->current()->getCode();
        }
        return false;
    }
    // }}}
}
// }}}

