<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker textwidth=80: */

/**
 * Linked list structure
 * 
 * This package implements a doubly linked list structure. Each node
 * (Structures_LinkedList_DoubleNode object) in the list
 * (Structures_LinkedList_Double) knows the previous node and the next
 * node in the list. Unlike an array, you can insert or delete nodes at
 * arbitrary points in the list.
 *
 * If your application normally traverses the linked list in a forward-only
 * direction, use the singly-linked list implemented by
 * {@link Structures_LinkedList_Single}. If, however, your application
 * needs to traverse the list backwards, or insert nodes into the list before
 * other nodes in the list, use the double-linked list implemented by
 * {@link Structures_LinkedList_Double} to give your application better
 * performance at the cost of a slightly larger memory footprint.
 *
 * Structures_LinkedList_Double implements the Iterator interface so control
 * structures like foreach($list as $node) and while($list->next()) work
 * as expected.
 *
 * To use this package, derive a child class from
 * Structures_LinkedList_DoubleNode  and add data to the object. Then use the
 * Structures_LinkedList_Double class to access the nodes.
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
 * @package   Structures_LinkedList_Double
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2006 Dan Scott
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @version   CVS: $Id: Double.php -1   $
 * @link      http://pear.php.net/package/Structures_LinkedList
 * @example   double_link_example.php
 *
 * @todo Add some actual error conditions
 **/

require_once __CA_LIB_DIR__.'/core/Parsers/PEAR/Exception.php';
require_once __CA_LIB_DIR__.'/core/Parsers/Structures/LinkedList/Single.php';

// {{{ class Structures_LinkedList_Double
/**
 * The Structures_LinkedList_Double class represents a linked list structure
 * composed of {@link Structures_LinkedList_DoubleNode} objects.
 *
 * @category Structures
 * @package  Structures_LinkedList_Double
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @link     http://pear.php.net/package/Structures_LinkedList
 */
class Structures_LinkedList_Double extends Structures_LinkedList_Single implements Iterator
{
    // {{{ properties
    /**
     * Tail node of the linked list
     * @var Structures_LinkedList_DoubleNode
     */
    protected $tail_node;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Structures_LinkedList_Double constructor
     *
     * @param Structures_LinkedList_DoubleNode $root root node for the
     * linked list
     */
    function __construct(Structures_LinkedList_DoubleNode $root = null)
    {
        if ($root) {
            $this->tail_node = $root;
        } else {
            $this->tail_node = null;
        }
        parent::__construct($root);
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Structures_LinkedList_Double destructor
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
         *   if next node exists:
         *     delete last node's references to next node and previous node
         *     make the old next node the new last node
         */
        if (!$last_node = $this->root_node) {
            return;
        }
        while (($next_node = $last_node->next()) !== false) {
            $last_node->setNext(null);
            $last_node->setPrevious(null);
            $last_node = $next_node;
        }
        $this->current = null;
        $this->root_node = null;
        $this->tail_node = null;
        $last_node = null;
        $next_node = null;
    }
    // }}}

    // {{{ function end()
    /**
     * Sets the pointer for the linked list to its last node
     *
     * @return Structures_LinkedList_DoubleNode last node in the linked list
     */
    public function end()
    {
        if ($this->tail_node) {
            $this->current = $this->tail_node;
        } else {
            $this->current = null;
        }
        return $this->current;
    }
    // }}}

    // {{{ function previous()
    /**
     * Sets the pointer for the linked list to the previous node and
     * returns that node
     *
     * @return Structures_LinkedList_DoubleNode previous node in the linked list
     */
    public function previous()
    {
        if (!$this->current()->previous()) {
            return false;
        }
        $this->current = $this->current()->previous();
        return $this->current();
    }
    // }}}

    // {{{ function insertNode()
    /**
     * Inserts a {@link Structures_LinkedList_DoubleNode} object into the linked
     * list, based on a reference node that already exists in the list.
     *
     * @param Structures_LinkedList_DoubleNode $new_node      New node to add to the list
     * @param Structures_LinkedList_DoubleNode $existing_node Reference position node
     * @param bool                             $before        Insert new node before or after the existing node
     *
     * @return bool Success or failure
     **/
    public function insertNode($new_node, $existing_node, $before = false)
    {
        if (!$this->root_node) {
            $this->__construct($new_node);
        }

        // Now add the node according to the requested mode
        switch ($before) {

        case true:
            $previous_node = $existing_node->previous();
            if ($previous_node) {
                $previous_node->setNext($new_node);
                $new_node->setPrevious($previous_node);
            } else {
                // The existing node must be root node; make new node root
                $this->root_node = $new_node;
                $new_node->setPrevious();
            }
            $new_node->setNext($existing_node);
            $existing_node->setPrevious($new_node);

            break;

        case false:
            $new_node->setPrevious($existing_node);
            $next_node = $existing_node->next();
            if ($next_node) {
                $new_node->setNext($next_node);
                $next_node->setPrevious($new_node);
            } else {
                // The existing node must have been the tail node
                $this->tail_node = $new_node;
            }
            $existing_node->setNext($new_node);

            break;

        }

        return true;
    }
    // }}}

    // {{{ protected function getTailNode()
    /**
     * Returns the tail node of the linked list.
     *
     * This is a cheap operation for a doubly-linked list.
     *
     * @return bool Success or failure
     **/
    protected function getTailNode()
    {
        return $this->tail_node;
    }
    // }}}

    // {{{ function deleteNode()
    /**
     * Deletes a {@link Structures_LinkedList_DoubleNode} from the list.
     *
     * @param Structures_LinkedList_DoubleNode $node Node to delete.
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
        
        /* If this is the tail node, and there are more nodes in the list,
         * make the previous node the tail node before deleting this node
         */
        if ($node === $this->tail_node) {
            $this->tail_node = $node->previous();
        }

        /* If this is the current node, and there are other nodes in the list,
         * try making the previous node the current node so that next() works
         * as expected.
         *
         * If that fails, make the next node the current node.
         *
         * If that fails, null isn't such a bad place to be.
         */
        if ($node === $this->current) {
            if ($node->previous()) {
                $this->current = $node->previous();
            } elseif ($node->next()) {
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

// {{{ class Structures_LinkedList_DoubleNode
/**
 * The Structures_LinkedList_DoubleNode class represents a node in a
 * {@link Structures_LinkedList_Double} linked list structure.
 *
 * @category Structures
 * @package  Structures_LinkedList_Double
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @link     http://pear.php.net/package/Structures_LinkedList
 */
class Structures_LinkedList_DoubleNode extends Structures_LinkedList_SingleNode
{
    // {{{ properties
    /**
     * Previous node in the linked list
     * @var Structures_LinkedList_DoubleNode
     */
    protected $previous;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Structures_LinkedList_DoubleNode constructor
     */
    public function __construct()
    {
        $this->next = null;
        $this->previous = null;
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
        $next = $this->next();
        $previous = $this->previous();
        if ($previous && $next) {
            $previous->setNext($next);
            $next->setPrevious($previous);
        } elseif ($previous) {
            $previous->setNext();
        } elseif ($next) {
            $next->setPrevious();
        }
    }
    // }}}

    // {{{ function previous()
    /**
     * Return the previous node in the linked list
     *
     * @return Structures_LinkedList_DoubleNode previous node in the linked list
     */
    public function previous()
    {
        if ($this->previous) {
            return $this->previous;
        } else {
            return false;
        }
    }
    // }}}

    // {{{ function setPrevious()
    /**
     * Sets the pointer for the previous node in the linked list
     * to the specified node
     *
     * @param Structures_LinkedList_DoubleNode $node new previous node
     * in the linked list
     *
     * @return Structures_LinkedList_DoubleNode new previous node in
     * the linked list
     */
    public function setPrevious($node = null)
    {
        $this->previous = $node;
        return $this->previous;
    }
    // }}}

}
// }}}

?>
