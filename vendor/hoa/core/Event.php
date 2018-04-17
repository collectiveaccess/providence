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

namespace Hoa\Core\Event;

use Hoa\Core;

/**
 * Interface \Hoa\Core\Event\Source.
 *
 * Each object which is observable must implement this interface.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
interface Source
{
}

/**
 * Class \Hoa\Core\Event\Bucket.
 *
 * This class is the object which is transmit through event channels.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Bucket
{
    /**
     * Source object.
     *
     * @var \Hoa\Core\Event\Source
     */
    protected $_source = null;

    /**
     * Data.
     *
     * @var mixed
     */
    protected $_data   = null;



    /**
     * Set data.
     *
     * @param   mixed   $data    Data.
     * @return  void
     */
    public function __construct($data = null)
    {
        $this->setData($data);

        return;
    }

    /**
     * Send this object on the event channel.
     *
     * @param   string                  $eventId    Event ID.
     * @param   \Hoa\Core\Event\Source  $source     Source.
     * @return  void
     * @throws  \Hoa\Core\Exception
     */
    public function send($eventId, Source $source)
    {
        return Event::notify($eventId, $source, $this);
    }

    /**
     * Set source.
     *
     * @param   \Hoa\Core\Event\Source  $source    Source.
     * @return  \Hoa\Core\Event\Source
     */
    public function setSource(Source $source)
    {
        $old           = $this->_source;
        $this->_source = $source;

        return $old;
    }

    /**
     * Get source.
     *
     * @return  \Hoa\Core\Event\Source
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Set data.
     *
     * @param   mixed   $data    Data.
     * @return  mixed
     */
    public function setData($data)
    {
        $old         = $this->_data;
        $this->_data = $data;

        return $old;
    }

    /**
     * Get data.
     *
     * @return  mixed
     */
    public function getData()
    {
        return $this->_data;
    }
}

/**
 * Class \Hoa\Core\Event.
 *
 * Events are asynchronous at registration, anonymous at use (until we
 * receive a bucket) and useful to largely spread data through components
 * without any known connection between them.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Event
{
    /**
     * Static register of all observable objects, i.e. \Hoa\Core\Event\Source
     * object, i.e. object that can send event.
     *
     * @var array
     */
    private static $_register = [];

    /**
     * Callables, i.e. observer objects.
     *
     * @var array
     */
    protected $_callable      = [];



    /**
     * Privatize the constructor.
     *
     * @return  void
     */
    private function __construct()
    {
        return;
    }

    /**
     * Manage multiton of events, with the principle of asynchronous
     * attachements.
     *
     * @param   string  $eventId    Event ID.
     * @return  \Hoa\Core\Event
     */
    public static function getEvent($eventId)
    {
        if (!isset(self::$_register[$eventId][0])) {
            self::$_register[$eventId] = [
                0 => new self(),
                1 => null
            ];
        }

        return self::$_register[$eventId][0];
    }

    /**
     * Declare a new object in the observable collection.
     * Note: Hoa's libraries use hoa://Event/AnID for their observable objects;
     *
     * @param   string                  $eventId    Event ID.
     * @param   \Hoa\Core\Event\Source  $source     Observable object.
     * @return  void
     * @throws  \Hoa\Core\Exception
     */
    public static function register($eventId, $source)
    {
        if (true === self::eventExists($eventId)) {
            throw new Core\Exception(
                'Cannot redeclare an event with the same ID, i.e. the event ' .
                'ID %s already exists.',
                0,
                $eventId
            );
        }

        if (is_object($source) && !($source instanceof Source)) {
            throw new Core\Exception(
                'The source must implement \Hoa\Core\Event\Source ' .
                'interface; given %s.',
                1,
                get_class($source)
            );
        } else {
            $reflection = new \ReflectionClass($source);

            if (false === $reflection->implementsInterface('\Hoa\Core\Event\Source')) {
                throw new Core\Exception(
                    'The source must implement \Hoa\Core\Event\Source ' .
                    'interface; given %s.',
                    2,
                    $source
                );
            }
        }

        if (!isset(self::$_register[$eventId][0])) {
            self::$_register[$eventId][0] = new self();
        }

        self::$_register[$eventId][1] = $source;

        return;
    }

    /**
     * Undeclare an object in the observable collection.
     *
     * @param   string  $eventId    Event ID.
     * @param   bool    $hard       If false, just delete the source, else,
     *                              delete source and attached callables.
     * @return  void
     */
    public static function unregister($eventId, $hard = false)
    {
        if (false !== $hard) {
            unset(self::$_register[$eventId]);
        } else {
            self::$_register[$eventId][1] = null;
        }

        return;
    }

    /**
     * Attach an object to an event.
     * It can be a callable or an accepted callable form (please, see the
     * \Hoa\Core\Consistency\Xcallable class).
     *
     * @param   mixed   $callable    Callable.
     * @return  \Hoa\Core\Event
     */
    public function attach($callable)
    {
        $callable                              = xcallable($callable);
        $this->_callable[$callable->getHash()] = $callable;

        return $this;
    }

    /**
     * Detach an object to an event.
     * Please see $this->attach() method.
     *
     * @param   mixed   $callable    Callable.
     * @return  \Hoa\Core\Event
     */
    public function detach($callable)
    {
        unset($this->_callable[xcallable($callable)->getHash()]);

        return $this;
    }

    /**
     * Check if at least one callable is attached to an event.
     *
     * @return  bool
     */
    public function isListened()
    {
        return !empty($this->_callable);
    }

    /**
     * Notify, i.e. send data to observers.
     *
     * @param   string                             Event ID.
     * @param   \Hoa\Core\Event\Source  $source    Source.
     * @param   \Hoa\Core\Event\Bucket  $data      Data.
     * @return  void
     * @throws  \Hoa\Core\Exception
     */
    public static function notify($eventId, Source $source, Bucket $data)
    {
        if (false === self::eventExists($eventId)) {
            throw new Core\Exception(
                'Event ID %s does not exist, cannot send notification.',
                3,
                $eventId
            );
        }

        $data->setSource($source);
        $event = self::getEvent($eventId);

        foreach ($event->_callable as $callable) {
            $callable($data);
        }

        return;
    }

    /**
     * Check whether an event exists.
     *
     * @param   string  $eventId    Event ID.
     * @return  bool
     */
    public static function eventExists($eventId)
    {
        return
            array_key_exists($eventId, self::$_register) &&
            self::$_register[$eventId][1] !== null;
    }
}

/**
 * Interface \Hoa\Core\Event\Listenable.
 *
 * Each object which is listenable must implement this interface.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
interface Listenable extends Source
{
    /**
     * Attach a callable to a listenable component.
     *
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $callable      Callable.
     * @return  \Hoa\Core\Event\Listenable
     * @throws  \Hoa\Core\Exception
     */
    public function on($listenerId, $callable);
}

/**
 * Class \Hoa\Core\Event\Listener.
 *
 * A contrario of events, listeners are synchronous, identified at use and
 * useful for close interactions between one or some components.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Listener
{
    /**
     * Source of listener (for Bucket).
     *
     * @var \Hoa\Core\Event\Listenable
     */
    protected $_source = null;

    /**
     * All listener IDs and associated listeners.
     *
     * @var array
     */
    protected $_listen = null;



    /**
     * Build a listener.
     *
     * @param   \Hoa\Core\Event\Listenable  $source    Source (for Bucket).
     * @param   array                       $ids       Accepted ID.
     * @return  void
     */
    public function __construct(Listenable $source, Array $ids)
    {
        $this->_source = $source;
        $this->addIds($ids);

        return;
    }

    /**
     * Add acceptable ID (or reset).
     *
     * @param   array  $ids    Accepted ID.
     * @return  void
     */
    public function addIds(Array $ids)
    {
        foreach ($ids as $id) {
            $this->_listen[$id] = [];
        }

        return;
    }

    /**
     * Attach a callable to a listenable component.
     *
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $callable      Callable.
     * @return  \Hoa\Core\Event\Listener
     * @throws  \Hoa\Core\Exception
     */
    public function attach($listenerId, $callable)
    {
        if (false === $this->listenerExists($listenerId)) {
            throw new Core\Exception(
                'Cannot listen %s because it is not defined.',
                0,
                $listenerId
            );
        }

        $callable                                         = xcallable($callable);
        $this->_listen[$listenerId][$callable->getHash()] = $callable;

        return $this;
    }

    /**
     * Detach a callable from a listenable component.
     *
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $callable      Callable.
     * @return  \Hoa\Core\Event\Listener
     */
    public function detach($listenerId, $callable)
    {
        unset($this->_callable[$listenerId][xcallable($callable)->getHash()]);

        return $this;
    }

    /**
     * Detach all callables from a listenable component.
     *
     * @param  string  $listenerId    Listener ID.
     * @return \Hoa\Core\Event\Listener
     */
    public function detachAll($listenerId)
    {
        unset($this->_callable[$listenerId]);

        return $this;
    }

    /**
     * Check if a listener exists.
     *
     * @param   string  $listenerId    Listener ID.
     * @return  bool
     */
    public function listenerExists($listenerId)
    {
        return array_key_exists($listenerId, $this->_listen);
    }

    /**
     * Send/fire a bucket to a listener.
     *
     * @param   string                  $listenerId    Listener ID.
     * @param   \Hoa\Core\Event\Bucket  $data          Data.
     * @return  array
     * @throws  \Hoa\Core\Exception
     */
    public function fire($listenerId, Bucket $data)
    {
        if (false === $this->listenerExists($listenerId)) {
            throw new Core\Exception(
                'Cannot fire on %s because it is not defined.',
                1
            );
        }

        $data->setSource($this->_source);
        $out = [];

        foreach ($this->_listen[$listenerId] as $callable) {
            $out[] = $callable($data);
        }

        return $out;
    }
}

/**
 * Alias.
 */
class_alias('Hoa\Core\Event\Event', 'Hoa\Core\Event');
