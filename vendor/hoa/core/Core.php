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

namespace Hoa\Core {

/**
 * Check if Hoa was well-included.
 */
!(
    !defined('HOA') and define('HOA', true)
)
and
    exit('Hoa main file (Core.php) must be included once.');

(
    !defined('PHP_VERSION_ID') or PHP_VERSION_ID < 50400
)
and
    exit('Hoa needs at least PHP5.4 to work; you have ' . phpversion() . '.');

/**
 * \Hoa\Core\Consistency
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Consistency.php';

/**
 * \Hoa\Core\Event, (hard-preloaded in Hoa\Core\Consistency).
 */
//require_once __DIR__ . DIRECTORY_SEPARATOR . 'Event.php';

/**
 * \Hoa\Core\Exception, (hard-preloaded in Hoa\Core\Consistency).
 */
//require_once __DIR__ . DIRECTORY_SEPARATOR . 'Exception.php';

/**
 * \Hoa\Core\Parameter
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Parameter.php';

/**
 * \Hoa\Core\Protocol
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Protocol.php';

/**
 * \Hoa\Core\Data, (hard-preloaded in Hoa\Core\Consistency).
 */
//require_once __DIR__ . DIRECTORY_SEPARATOR . 'Data.php';

/**
 * Class \Hoa\Core.
 *
 * \Hoa\Core is the base of all libraries.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Core implements Parameter\Parameterizable
{
    /**
     * Stack of all registered shutdown function.
     *
     * @var array
     */
    private static $_rsdf     = [];

    /**
     * Tree of components, starts by the root.
     *
     * @var \Hoa\Core\Protocol\Root
     */
    private static $_root     = null;

    /**
     * Parameters.
     *
     * @var \Hoa\Core\Parameter
     */
    protected $_parameters    = null;

    /**
     * Singleton.
     *
     * @var \Hoa\Core
     */
    private static $_instance = null;



    /**
     * Singleton.
     *
     * @return  void
     */
    private function __construct()
    {
        static::_define('SUCCEED',        true);
        static::_define('FAILED',         false);
        static::_define('…',              '__hoa_core_fill');
        static::_define('DS',             DIRECTORY_SEPARATOR);
        static::_define('PS',             PATH_SEPARATOR);
        static::_define('ROOT_SEPARATOR', ';');
        static::_define('RS',             ROOT_SEPARATOR);
        static::_define('CRLF',           "\r\n");
        static::_define('OS_WIN',         defined('PHP_WINDOWS_VERSION_PLATFORM'));
        static::_define('S_64_BITS',      PHP_INT_SIZE == 8);
        static::_define('S_32_BITS',      !S_64_BITS);
        static::_define('PHP_INT_MIN',    ~PHP_INT_MAX);
        static::_define('PHP_FLOAT_MIN',  (float) PHP_INT_MIN);
        static::_define('PHP_FLOAT_MAX',  (float) PHP_INT_MAX);
        static::_define('π',              M_PI);
        static::_define('void',           (unset) null);
        static::_define('_public',        1);
        static::_define('_protected',     2);
        static::_define('_private',       4);
        static::_define('_static',        8);
        static::_define('_abstract',      16);
        static::_define('_pure',          32);
        static::_define('_final',         64);
        static::_define('_dynamic',       ~_static);
        static::_define('_concrete',      ~_abstract);
        static::_define('_overridable',   ~_final);
        static::_define('WITH_COMPOSER',  class_exists('Composer\Autoload\ClassLoader', false) ||
                                          ('cli' === PHP_SAPI &&
                                          file_exists(__DIR__ . DS . '..' . DS . '..' . DS . 'autoload.php')));

        if (false !== $wl = ini_get('suhosin.executor.include.whitelist')) {
            if (false === in_array('hoa', explode(',', $wl))) {
                throw new Exception(
                    'The URL scheme hoa:// is not authorized by Suhosin. ' .
                    'You must add this to your php.ini or suhosin.ini: ' .
                    'suhosin.executor.include.whitelist="%s", thanks :-).',
                    0,
                    implode(
                        ',',
                        array_merge(
                            preg_split('#,#', $wl, -1, PREG_SPLIT_NO_EMPTY),
                            ['hoa']
                        )
                    )
                );
            }
        }

        if (true === function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        if (true === function_exists('mb_regex_encoding')) {
            mb_regex_encoding('UTF-8');
        }

        return;
    }

    /**
     * Singleton.
     *
     * @return  \Hoa\Core
     */
    public static function getInstance()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * Initialize the core.
     *
     * @param   array   $parameters    Parameters of \Hoa\Core.
     * @return  \Hoa\Core
     */
    public function initialize(Array $parameters = [])
    {
        $root = dirname(dirname(__DIR__));
        $cwd  =
            'cli' === PHP_SAPI
                ? dirname(realpath($_SERVER['argv'][0]))
                : getcwd();
        $this->_parameters = new Parameter\Parameter(
            $this,
            [
                'root' => $root,
                'cwd'  => $cwd
            ],
            [
                'root.hoa'         => '(:root:)',
                'root.application' => '(:cwd:h:)',
                'root.data'        => '(:%root.application:h:)' . DS . 'Data' . DS,

                'protocol.Application'            => '(:%root.application:)' . DS,
                'protocol.Application/Public'     => 'Public' . DS,
                'protocol.Data'                   => '(:%root.data:)',
                'protocol.Data/Etc'               => 'Etc' . DS,
                'protocol.Data/Etc/Configuration' => 'Configuration' . DS,
                'protocol.Data/Etc/Locale'        => 'Locale' . DS,
                'protocol.Data/Library'           => 'Library' . DS . 'Hoathis' . DS . RS .
                                                     'Library' . DS . 'Hoa' . DS,
                'protocol.Data/Lost+found'        => 'Lost+found' . DS,
                'protocol.Data/Temporary'         => 'Temporary' . DS,
                'protocol.Data/Variable'          => 'Variable' . DS,
                'protocol.Data/Variable/Cache'    => 'Cache' . DS,
                'protocol.Data/Variable/Database' => 'Database' . DS,
                'protocol.Data/Variable/Log'      => 'Log' . DS,
                'protocol.Data/Variable/Private'  => 'Private' . DS,
                'protocol.Data/Variable/Run'      => 'Run' . DS,
                'protocol.Data/Variable/Test'     => 'Test' . DS,
                'protocol.Library'                => '(:%protocol.Data:)Library' . DS . 'Hoathis' . DS . RS .
                                                     '(:%protocol.Data:)Library' . DS . 'Hoa' . DS . RS .
                                                     '(:%root.hoa:)' . DS . 'Hoathis' . DS . RS .
                                                     '(:%root.hoa:)' . DS . 'Hoa' . DS,

                'namespace.prefix.*'           => '(:%protocol.Data:)Library' . DS . RS . '(:%root.hoa:)' . DS,
                'namespace.prefix.Application' => '(:%root.application:h:)' . DS,
            ]
        );

        $this->_parameters->setKeyword('root', $root);
        $this->_parameters->setKeyword('cwd',  $cwd);
        $this->_parameters->setParameters($parameters);
        $this->setProtocol();

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return  \Hoa\Core\Parameter
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Set protocol according to the current parameter.
     *
     * @param   string  $path     Path (e.g. hoa://Data/Temporary).
     * @param   string  $reach    Reach value.
     * @return  void
     */
    public function setProtocol($path = null, $reach = null)
    {
        $root = static::getProtocol();

        if (null === $path && null === $reach) {
            if (!isset($root['Library'])) {
                static::$_root = null;
                $root          = static::getProtocol();
            }

            $protocol = $this->getParameters()->unlinearizeBranche('protocol');

            foreach ($protocol as $components => $reach) {
                $parts  = explode('/', trim($components, '/'));
                $last   = array_pop($parts);
                $handle = $root;

                foreach ($parts as $part) {
                    $handle = $handle[$part];
                }

                if ('Library' === $last) {
                    $handle[] = new Protocol\Library($last, $reach);
                } else {
                    $handle[] = new Protocol\Generic($last, $reach);
                }
            }

            return;
        }

        if ('hoa://' === substr($path, 0, 6)) {
            $path = substr($path, 6);
        }

        $path   = trim($path, '/');
        $parts  = explode('/', $path);
        $handle = $root;

        foreach ($parts as $part) {
            $handle = $handle[$part];
        }

        $handle->setReach($reach);
        $root->clearCache();
        $this->getParameters()->setParameter('protocol.' . $path, $reach);

        return;
    }

    /**
     * Check if a constant is already defined.
     * If the constant is defined, this method returns false.
     * Else this method declares the constant.
     *
     * @param   string  $name     The name of the constant.
     * @param   string  $value    The value of the constant.
     * @param   bool    $case     True set the case-insensitive.
     * @return  bool
     */
    public static function _define($name, $value, $case = false)
    {
        if (!defined($name)) {
            return define($name, $value, $case);
        }

        return false;
    }

    /**
     * Get protocol's root.
     *
     * @return  \Hoa\Core\Protocol\Root
     */
    public static function getProtocol()
    {
        if (null === static::$_root) {
            static::$_root = new Protocol\Root();
        }

        return static::$_root;
    }

    /**
     * Enable exception handler: catch uncaught exception.
     *
     * @param   bool  $enable    Enable.
     * @return  mixed
     */
    public static function enableExceptionHandler($enable = true)
    {
        if (false === $enable) {
            return restore_exception_handler();
        }

        return set_exception_handler(function ($exception) {
            return Exception\Idle::uncaught($exception);
        });
    }

    /**
     * Enable error handler: transform PHP error into \Hoa\Core\Exception\Error.
     *
     * @param   bool  $enable    Enable.
     * @return  mixed
     */
    public static function enableErrorHandler($enable = true)
    {
        if (false === $enable) {
            return restore_error_handler();
        }

        return set_error_handler(function ($no, $str, $file = null, $line = null, $ctx = null) {
            return Exception\Idle::error($no, $str, $file, $line, $ctx);
        });
    }

    /**
     * Apply and save a register shutdown function.
     * It may be analogous to a static __destruct, but it allows us to make more
     * that a __destruct method.
     *
     * @param   string  $class     Class.
     * @param   string  $method    Method.
     * @return  bool
     */
    public static function registerShutdownFunction($class = '', $method = '')
    {
        if (!isset(static::$_rsdf[$class][$method])) {
            static::$_rsdf[$class][$method] = true;

            return register_shutdown_function([$class, $method]);
        }

        return false;
    }

    /**
     * Get PHP executable.
     *
     * @return  string
     */
    public static function getPHPBinary()
    {
        if (defined('PHP_BINARY')) {
            return PHP_BINARY;
        }

        if (isset($_SERVER['_'])) {
            return $_SERVER['_'];
        }

        foreach (['', '.exe'] as $extension) {
            if (file_exists($_ = PHP_BINDIR . DS . 'php' . $extension)) {
                return realpath($_);
            }
        }

        return null;
    }

    /**
     * Generate an Universal Unique Identifier (UUID).
     *
     * @return  string
     */
    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Return the copyright and license of Hoa.
     *
     * @return  string
     */
    public static function ©()
    {
        return
            'Copyright © 2007-2015 Ivan Enderlin. All rights reserved.' . "\n" .
            'New BSD License.';
    }
}

}

namespace {

/**
 * Alias.
 */
class_alias('Hoa\Core\Core', 'Hoa\Core');

/**
 * Alias of \Hoa\Core::_define().
 *
 * @param   string  $name     The name of the constant.
 * @param   string  $value    The value of the constant.
 * @param   bool    $case     True set the case-insentisitve.
 * @return  bool
 */
if (!function_exists('_define')) {
    function _define($name, $value, $case = false)
    {
        return Hoa\Core::_define($name, $value, $case);
    }
}

/**
 * Alias of the \Hoa\Core\Event::getEvent() method.
 *
 * @param   string  $eventId    Event ID.
 * @return  \Hoa\Core\Event
 */
if (!function_exists('event')) {
    function event($eventId)
    {
        return Hoa\Core\Event\Event::getEvent($eventId);
    }
}

/**
 * Then, initialize Hoa.
 */
Hoa\Core::getInstance()->initialize();

}
