<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker textwidth=80: */

/**
 * Linked list structure
 * 
 * This package implements a singly linked list structure. Each node
 * (Structures_LinkedList_SingleNode object) in the list
 * (Structures_LinkedList_Single) knows the the next node in the list.
 * Unlike an array, you can insert or delete nodes at arbitrary points
 * in the list.
 *
 * If your application normally traverses the linked list in a forward-only
 * direction, use the singly-linked list implemented by
 * {@link Structures_LinkedList_Single}. If, however, your application
 * needs to traverse the list backwards, or insert nodes into the list before
 * other nodes in the list, use the double-linked list implemented by
 * {@link Structures_LinkedList_Double} to give your application better
 * performance at the cost of a slightly larger memory footprint.
 *
 * Structures_LinkedList_Single implements the Iterator interface so control
 * structures like foreach($list as $node) and while($list->next()) work
 * as expected.
 *
 * To use this package, derive a child class from
 * Structures_LinkedList_SingleNode and add data to the object. Then use the
 * Structures_LinkedList_Single class to access the nodes.
 *
 * PHP version 5
 *
 * LICENSE:  Copyright 2006 Dan Scott
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category  Structures
 * @package   Structures_LinkedList_Single
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2006 Dan Scott
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @version   CVS: $Id: Single.php -1   $
 * @link      http://pear.php.net/package/Structures_LinkedList_Single
 * @example   single_link_example.php
 *
 * @todo Add some actual error conditions
 **/

require_once __CA_LIB_DIR__.'/core/Parsers/PEAR/Exception.php';

// {{{ class Structures_LinkedList_Single
/**
 * The Structures_LinkedList_Single class represents a linked list structure
 * composed of {@link Structures_LinkedList_SingleNode} objects.
 *
 * @category Structures
 * @package  Structures_LinkedList_Single
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @link     http://pear.php.net/package/Structures_LinkedList_Single
 */
class Structures_LinkedList_Single implements Iterator
{
    // {{{ properties
    /**
     * Current node in the linked list
     * @var Structures_LinkedList_SingleNode
     */
    protected $current;

    /**
     * Root node of the linked list
     * @var Structures_LinkedList_SingleNode
     */
    protected $root_node;

    /**
     * The linked list contains no nodes
     */
    const ERROR_EMPTY = -1;

    public static $messages = array(
        self::ERROR_EMPTY => 'No nodes in this linked list' 
    );
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Structures_LinkedList_Single constructor
     *
     * @param Structures_LinkedList_SingleNode $root root node for the
     * linked list
     */
    function __construct(Structures_LinkedList_SingleNode $root = null)
    {
        if ($root) {
            $this->root_node = $root;
            $this->current = $root;
        } else {
            $this->root_node = null;
            $this->current = null;
        }
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Structures_LinkedList_Single destructor
     *
     * If we do not destroy all of the references in the linked list,
     * we will quickly run out of memory for large / complex structures.
     *
     */
    function __destruct()
    {
        /*
         * Starting with root node, set last node = root_node
         *   get next node
         *   if next node exists, delete last node reference to next node
         */
        if (!$last_node = $this->root_node) {
            return;
        }
        while (($next_node = $last_node->next()) !== false) {
            $last_node->setNext(null);
            $temp_node = $last_node;
            $last_node = $next_node;
            unset($temp_node);
        }
        $this->current = null;
        $this->root_node = null;
        $last_node = null;
        $next_node = null;
    }
    // }}}

    // {{{ function current()
    /**
     * Returns the current node in the linked list
     *
     * @return Structures_LinkedList_SingleNode current node in the linked list
     */
    public function current()
    {
        return $this->current;
    }
    // }}}

    // {{{ function rewind()
    /**
     * Sets the pointer for the linked list to the root node
     *
     * @return Structures_LinkedList_SingleNode root node in the linked list
     */
    public function rewind()
    {
        if ($this->root_node) {
            $this->current = $this->root_node;
        } else {
            $this->current = null;
        }
        return $this->current;
    }
    // }}}

    // {{{ function end()
    /**
     * Sets the pointer for the linked list to the root node
     *
     * @return Structures_LinkedList_SingleNode root node in the linked list
     */
    public function end()
    {
        $this->current = $this->getTailNode();
        return $this->current;
    }
    // }}}

    // {{{ function key()
    /**
     * Stub for Iterator interface that simply returns the current node
     *
     * @return Structures_LinkedList_SingleNode current node in the linked list
     */
    public function key()
    {
        return $this->current;
    }
    // }}}

    // {{{ function valid()
    /**
     * Stub for Iterator interface that simply returns the current node
     *
     * @return Structures_LinkedList_SingleNode current node in the linked list
     */
    public function valid()
    {
        return $this->current();
    }
    // }}}

    // {{{ function next()
    /**
     * Sets the pointer for the linked list to the next node and
     * returns that node
     *
     * @return Structures_LinkedList_SingleNode next node in the linked list
     */
    public function next()
    {
        if (!$this->current) {
            return false;
        }
        $this->current = $this->current()->next();
        return $this->current;
    }
    // }}}

    // {{{ function previous()
    /**
     * Sets the pointer for the linked list to the previous node and
     * returns that node
     *
     * @return Structures_LinkedList_SingleNode previous node in the linked list
     */
    public function previous()
    {
        if (!$this->current) {
            return false;
        }
        $this->current = $this->_getPreviousNode();
        return $this->current;
    }
    // }}}

    // {{{ protected function getTailNode()
    /**
     * Returns the tail node of the linked list.
     *
     * This is an expensive operation!
     *
     * @return bool Success or failure
     **/
    protected function getTailNode()
    {
        $tail_node = $this->root_node;
        while (($y = $tail_node->next()) !== false) {
            $tail_node = $y;
        }
        return $tail_node;
    }
    // }}}

    // {{{ private function _getPreviousNode()
    /**
     * Returns the node prior to the current node in the linked list.
     *
     * This is an expensive operation for a singly linked list!
     *
     * @param Structures_LinkedList_SingleNode $node (Optional) Specific node 
     * for which we want to find the previous node
     *
     * @return Structures_LinkedList_SingleNode Previous node
     **/
    private function _getPreviousNode($node = null)
    {
        if (!$node) {
            $node = $this->current;
        }
        $prior_node = $this->root_node;
        while (($y = $prior_node->next()) !== false) {
            if ($y == $node) {
                return $prior_node;
            }
            $prior_node = $y;
        }
        return null;
    }
    // }}}

    // {{{ function appendNode()
    /**
     * Adds a {@link Structures_LinkedList_SingleNode} object to the end of
     * the linked list.
     *
     * @param Structures_LinkedList_SingleNode $new_node New node to append
     *
     * @return bool Success or failure
     **/
    public function appendNode(Structures_LinkedList_SingleNode $new_node)
    {
        if (!$this->root_node) {
            $this->__construct($new_node);
            return true;
        }

        // This is just a special case of insertNode()
        $this->insertNode($new_node, $this->getTailNode());

        return true;
    }
    // }}}

    // {{{ function insertNode()
    /**
     * Inserts a {@link Structures_LinkedList_SingleNode} object into the linked
     * list, based on a reference node that already exists in the list.
     *
     * @param Structures_LinkedList_SingleNode $new_node      New node to add to
     * the list
     * @param Structures_LinkedList_SingleNode $existing_node Reference
     * position node
     * @param bool                             $before        Insert new node
     * before or after the existing node
     *
     * @return bool Success or failure
     **/
    public function insertNode($new_node, $existing_node, $before = false)
    {
        if (!$this->root_node) {
            $this->__construct($new_node);
            return true;
        }

        // Now add the node according to the requested mode
        switch ($before) {

        case true:
            if ($existing_node === $this->root_node) {
                $this->root_node = $new_node;
            }
            $previous_node = $this->_getPreviousNode($existing_node);
            if ($previous_node) {
                $previous_node->setNext($new_node);
            }
            $new_node->setNext($existing_node);

            break;

        case false:
            $next_node = $existing_node->next();
            if ($next_node) {
                $new_node->setNext($next_node);
            }
            $existing_node->setNext($new_node);

            break;

        }

        return true;
    }
    // }}}

    // {{{ function prependNode()
    /**
     * Adds a {@link Structures_LinkedList_SingleNode} object to the start
     * of the linked list.
     *
     * @param Structures_LinkedList_SingleNode $new_node Node to prepend
     * to the list
     *
     * @return bool Success or failure
     **/
    public function prependNode(Structures_LinkedList_SingleNode $new_node)
    {
        if (!$this->root_node) {
            $this->__construct($new_node);
            return true;
        }

        // This is just a special case of insertNode()
        $this->insertNode($new_node, $this->root_node, true);

        return true;
    }
    // }}}

    // {{{ function deleteNode()
    /**
     * Deletes a {@link Structures_LinkedList_SingleNode} from the list.
     *
     * @param Structures_LinkedList_SingleNode $node Node to delete.
     *
     * @return null
     */
    public function deleteNode($node)
    {
        /* If this is the root node, and there are more nodes in the list,
         * make the next node the new root node before deleting this node.
         */
        if ($node === $this->root_node) {
            $this->root_node = $node->next();
        }
        
        /* If this is the current node, make the next node the current node.
         *
         * If that fails, null isn't such a bad place to be.
         */
        if ($node === $this->current) {
            if ($node->next()) {
                $this->current = $node->next();
            } else {
                $this->current = null;
            }
        }
        $node->__destruct();
    }
    // }}}

}
// }}}

// {{{ class Structures_LinkedList_SingleNode
/**
 * The Structures_LinkedList_SingleNode class represents a node in a
 * {@link Structures_LinkedList_Single} linked list structure.
 *
 * @category Structures
 * @package  Structures_LinkedList_Single
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @link     http://pear.php.net/package/Structures_LinkedList_Single
 */
class Structures_LinkedList_SingleNode
{
    // {{{ properties
    /**
     * Next node in the linked list
     * @var Structures_LinkedList_SingleNode
     */
    protected $next;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Structures_LinkedList_SingleNode constructor
     */
    public function __construct()
    {
        $this->next = null;
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Removes node from the list, adjusting the related nodes accordingly.
     *
     * This is a problem if the node is the root node for the list.
     * At this point, however, we do not have access to the list itself. Hmm.
     */
    public function __destruct()
    {
    }
    // }}}

    // {{{ function next()
    /**
     * Return the next node in the linked list
     *
     * @return Structures_LinkedList_SingleNode next node in the linked list
     */
    public function next()
    {
        if ($this->next) {
            return $this->next;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ function previous()
    /**
     * Return the previous node in the linked list
     *
     * Stub method for Structures_LinkedList_DoubleNode to override.
     *
     * @return Structures_LinkedList_SingleNode previous node in the linked list
     */
    public function previous()
    {
        return false;
    }
    // }}}

    // {{{ function setNext()
    /**
     * Sets the pointer for the next node in the linked list to the
     * specified node
     *
     * @param Structures_LinkedList_SingleNode $node new next node in
     * the linked list
     *
     * @return Structures_LinkedList_SingleNode new next node in the linked list
     */
    public function setNext($node = null)
    {
        $this->next = $node;
        return $this->next;
    }
    // }}}

    // {{{ function setPrevious()
    /**
     * Sets the pointer for the next node in the linked list to the
     * specified node
     *
     * Stub method for Structures_LinkedList_DoubleNode to override.
     *
     * @param Structures_LinkedList_SingleNode $node new next node in
     * the linked list
     *
     * @return Structures_LinkedList_SingleNode new next node in the linked list
     */
    public function setPrevious($node = null)
    {
        return false;
    }
    // }}}
}

// }}}

?>
