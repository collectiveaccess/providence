<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Core\Data;

/**
 * Interface \Hoa\Core\Data\Datable.
 *
 * Polymorphic data interface (for transformation, ensures fun for other data
 * providers).
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
interface Datable
{
    /**
     * Transform data as an array.
     *
     * @return  array
     */
    public function toArray();
}

/**
 * Class \Hoa\Core\Data.
 *
 * Polymorphic data.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Data implements Datable, \ArrayAccess
{
    /**
     * Data as intuitive structure.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Temporize the branch name.
     *
     * @var string
     */
    protected $_temp = null;



    /**
     * Get a branch.
     *
     * @param   string  $name    Branch name.
     * @return  \Hoa\Core\Data
     */
    public function __get($name)
    {
        if (null !== $this->_temp) {
            return $this->offsetGet(0)->__get($name);
        }

        $this->_temp = $name;

        return $this;
    }

    /**
     * Set a branch.
     * Notice that it will always reach the (n+1)-th branch.
     *
     * @param   string  $name     Branch name.
     * @param   mixed   $value    Branch value (scalar or array value).
     * @return  \Hoa\Core\Data
     */
    public function __set($name, $value)
    {
        if (null !== $this->_temp) {
            return $this->offsetGet(0)->__set($name, $value);
        }

        $this->_temp = $name;

        return $this->offsetSet(null, $value);
    }

    /**
     * Check if the n-th branch exists.
     *
     * @param   mixed   $offset    Branch index.
     * @return  bool
     */
    public function offsetExists($offset)
    {
        if (null === $this->_temp || !is_int($offset)) {
            return false;
        }

        return true === array_key_exists($offset, $this->_data[$this->_temp]);
    }

    /**
     * Get the n-th branch.
     *
     * @param   mixed   $offset    Branch index. Could be null to
     *                             auto-increment.
     * @return  \Hoa\Core\Data
     */
    public function offsetGet($offset)
    {
        if (null === $this->_temp) {
            return;
        }

        $handle      = $this->_temp;
        $this->_temp = null;

        if (false === array_key_exists($handle, $this->_data)) {
            $this->_data[$handle] = [];

            if (null === $offset) {
                return $this->_data[$handle][] = new self();
            }

            return $this->_data[$handle][$offset] = new self();
        }

        if (null  === $offset ||
            false === array_key_exists($offset, $this->_data[$handle])) {
            return $this->_data[$handle][] = new self();
        }

        return $this->_data[$handle][$offset];
    }

    /**
     * Set the n-th branch.
     *
     * @param   mixed   $offset    Branch index. Could be null to
     *                             auto-increment.
     * @param   mixed   $value     Branche value (scalar, array or Datable
     *                             value).
     * @return  \Hoa\Core\Data
     */
    public function offsetSet($offset, $value)
    {
        if (null === $this->_temp) {
            return;
        }

        if ($value instanceof Datable) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            if (null === $offset) {
                $this->_data[$this->_temp][] = $value;
            } else {
                $this->_data[$this->_temp][$offset] = $value;
            }

            $this->_temp = null;

            return;
        }

        if (null === $offset) {
            $offset = 0;

            if (isset($this->_data[$this->_temp])) {
                foreach ($this->_data[$this->_temp] as $i => $_) {
                    if (is_int($i)) {
                        $offset = $i;
                    }
                }
            }

            foreach ($value as $k => $v) {
                if (is_int($k)) {
                    $temp = $this->_temp;
                    $this->offsetSet($k, $v);
                    $this->_temp = $temp;
                } else {
                    $temp = $this->_temp;
                    $this->offsetGet($offset)->__set($k, $v);
                    $this->_temp = $temp;
                }
            }
        } else {
            foreach ($value as $k => $v) {
                if (is_int($k)) {
                    continue;
                }

                $temp = $this->_temp;
                $this->offsetGet($offset)->__set($k, $v);
                $this->_temp = $temp;
            }
        }

        $this->_temp = null;

        return;
    }

    /**
     * Unset the n-th branch.
     *
     * @param   mixed   $offset    Branch index.
     * @return  \Hoa\Core\Data
     */
    public function offsetUnset($offset)
    {
        if (null === $this->_temp) {
            return;
        }

        if (null === $offset) {
            return;
        }

        unset($this->_data[$this->_temp][$offset]);
        $this->_temp = null;

        return;
    }

    /**
     * Transform data as an array.
     *
     * @return  array
     */
    public function toArray()
    {
        $out = [];

        foreach ($this->_data as $key => &$ii) {
            foreach ($ii as $i => &$value) {
                if (is_object($value)
                   && $value instanceof Datable) {
                    $out[$i][$key] = $value->toArray();
                } else {
                    $out[$i][$key] = &$value;
                }
            }
        }

        return $out;
    }
}

/**
 * Alias.
 */
class_alias('Hoa\Core\Data\Data', 'Hoa\Core\Data');
