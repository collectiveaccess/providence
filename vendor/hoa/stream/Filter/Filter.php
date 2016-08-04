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

namespace Hoa\Stream\Filter;

use Hoa\Core;
use Hoa\Stream;

/**
 * Class \Hoa\Stream\Filter.
 *
 * Proposes some methods to handle filter.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
abstract class Filter extends Stream
{
    /**
     * Overwrite filter if already exists.
     *
     * @const bool
     */
    const OVERWRITE        = true;

    /**
     * Do not overwrite filter if already exists.
     *
     * @const bool
     */
    const DO_NOT_OVERWRITE = false;

    /**
     * Filter should only be applied when reading.
     *
     * @const int
     */
    const READ             = STREAM_FILTER_READ;

    /**
     * Filter should only be applied when writing.
     *
     * @const int
     */
    const WRITE            = STREAM_FILTER_WRITE;

    /**
     * Filter should be applied when reading and writing.
     *
     * @const int
     */
    const READ_AND_WRITE   = STREAM_FILTER_ALL;

    /**
     * Filters resources register.
     *
     * @var array
     */
    protected static $_resources = [];



    /**
     * Register a stream filter.
     *
     * @param   string  $name         Filter name.
     * @param   mixed   $class        Class name or instance.
     * @param   bool    $overwrite    Overwrite filter if already exists or
     *                                not. Given by self::*OVERWRITE constants.
     * @return  bool
     * @throws  \Hoa\Stream\Filter\Exception
     */
    public static function register(
        $name,
        $class,
        $overwrite = self::DO_NOT_OVERWRITE
    ) {
        if ($overwrite === self::DO_NOT_OVERWRITE &&
            true       === self::isRegistered($name)) {
            throw new Exception('Filter %s is already registered.', 0, $name);
        }

        if (empty($name)) {
            throw new Exception('Filter name cannot be empty.', 1);
        }

        if (is_object($class)) {
            $class = get_class($class);
        }

        return stream_filter_register($name, $class);
    }

    /**
     * Append a filter to the list of filters.
     *
     * @param   mixed       $stream        Stream which received the filter.
     *                                     Should be resource or an object
     *                                     \Hoa\Stream.
     * @param   string      $name          Filter name.
     * @param   int         $mode          self::READ, self::WRITE or
     *                                     self::READ_AND_WRITE.
     * @param   mixed       $parameters    Parameters.
     * @return  resource
     */
    public static function append(
        $stream,
        $name,
        $mode = self::READ, $parameters = null
    ) {
        if ($stream instanceof Stream) {
            $stream = $stream->getStream();
        }

        if (null === $parameters) {
            return self::$_resources[$name] = stream_filter_append(
                $stream,
                $name,
                $mode
            );
        }

        return self::$_resources[$name] = stream_filter_append(
            $stream,
            $name,
            $mode,
            $parameters
        );
    }

    /**
     * Prepend a filter to the list of filters.
     *
     * @param   mixed       $stream        Stream which received the filter.
     *                                     Should be resource or an object
     *                                     \Hoa\Stream.
     * @param   string      $name          Filter name.
     * @param   int         $mode          self::READ, self::WRITE or
     *                                     self::READ_AND_WRITE.
     * @param   mixed       $parameters    Parameters.
     * @return  resource
     */
    public static function prepend(
        $stream,
        $name,
        $mode = self::READ, $parameters = null
    ) {
        if ($stream instanceof Stream) {
            $stream = $stream->getStream();
        }

        if (null === $parameters) {
            return self::$_resources[$name] = stream_filter_prepend(
                $stream,
                $name,
                $mode
            );
        }

        return self::$_resources[$name] = stream_filter_prepend(
            $stream,
            $name,
            $mode,
            $parameters
        );
    }

    /**
     * Delete a filter.
     *
     * @param   mixed   $streamFilter    Stream filter resource or name.
     * @return  bool
     * @throws  \Hoa\Stream\Filter\Exception
     */
    public static function remove($streamFilter)
    {
        if (!is_resource($streamFilter)) {
            if (isset(self::$_resources[$streamFilter])) {
                $streamFilter = self::$_resources[$streamFilter];
            } else {
                throw new Exception(
                    'Cannot remove the stream filter %s because no resource was ' .
                    'found with this name.',
                    2,
                    $streamFilter
                );
            }
        }

        return stream_filter_remove($streamFilter);
    }

    /**
     * Check if a filter is already registered or not.
     *
     * @param   string  $name    Filter name.
     * @return  bool
     */
    public static function isRegistered($name)
    {
        return in_array($name, self::getRegistered());
    }

    /**
     * Get all registered filer names.
     *
     * @return  array
     */
    public static function getRegistered()
    {
        return stream_get_filters();
    }
}

/**
 * Flex entity.
 */
Core\Consistency::flexEntity('Hoa\Stream\Filter\Filter');
