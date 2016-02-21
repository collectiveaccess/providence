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

namespace Hoa\Core\Bin;

use Hoa\Console;
use Hoa\Core;

/**
 * Class \Hoa\Core\Bin\Welcome.
 *
 * Welcome screen.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Welcome extends Console\Dispatcher\Kit
{
    /**
     * Options description.
     *
     * @var array
     */
    protected $options = [
        ['library',    Console\GetOption::REQUIRED_ARGUMENT, 'l'],
        ['no-verbose', Console\GetOption::NO_ARGUMENT,       'V'],
        ['help',       Console\GetOption::NO_ARGUMENT,       'h'],
        ['help',       Console\GetOption::NO_ARGUMENT,       '?']
    ];



    /**
     * The entry method.
     *
     * @return  int
     */
    public function main()
    {
        $library = null;
        $verbose = Console::isDirect(STDOUT);

        while (false !== $c = $this->getOption($v)) {
            switch ($c) {
                case 'l':
                    $library = $this->parser->parseSpecialValue($v);

                    break;

                case 'V':
                    $verbose = false;

                    break;

                case 'h':
                case '?':
                    return $this->usage();

                case '__ambiguous':
                    $this->resolveOptionAmbiguity($v);

                    break;
            }
        }

        if (true === $verbose) {
            echo
                Console\Chrome\Text::colorize(
                    '        _   _' . "\n" .
                    '       | | | | ___   __ _' . "\n" .
                    '       | |_| |/ _ \ / _` |' . "\n" .
                    '       |  _  | (_) | (_| |' . "\n" .
                    '       |_| |_|\___/ \__,_|' . "\n",
                     'foreground(yellow)'
                ), "\n\n",
                'Welcome in the command-line interface of Hoa :-).',  "\n\n",
                Console\Chrome\Text::colorize(
                    'List of available commands',
                    'foreground(green)'
                ), "\n\n";
        }

        if (null !== $library) {
            $library = array_map('mb_strtolower', $library);
        }

        $locations = resolve('hoa://Library', true, true);
        $iterator  = new \AppendIterator();

        foreach ($locations as $location) {
            $iterator->append(new \GlobIterator(
                $location . DS . '*' . DS . 'Bin' . DS . '*.php'
            ));
        }

        if (WITH_COMPOSER) {
            $iterator->append(new \GlobIterator(
                dirname(dirname(dirname(dirname(__DIR__)))) . DS . 'Bin' . DS . '*.php'
            ));
        }

        $binaries = [];

        foreach ($iterator as $entry) {
            $pathname = $entry->getPathname();
            $lib      = mb_strtolower(basename(dirname(dirname($pathname))));
            $bin      = mb_strtolower(
                mb_substr($entry->getBasename(), 0, -4)
            );

            if (null !== $library &&
                false === in_array($lib, $library)) {
                continue;
            }

            if ('core' === $lib && 'hoa' === $bin) {
                continue;
            }

            if (!isset($binaries[$lib])) {
                $binaries[$lib] = [];
            }

            $description = '';

            if (true === $verbose) {
                $lines = file($pathname);

                // Berk…
                for ($i = count($lines) - 1; $i >= 0; --$i) {
                    if ('__halt_compiler();' . "\n" === $lines[$i]) {
                        $description = trim(implode(
                            '',
                            array_slice($lines, $i + 1)
                        ));

                        break;
                    }
                }

                unset($lines);
            }

            $binaries[$lib][] = [
                'name'        => $bin,
                'description' => $description
            ];
        }

        if (true === $verbose) {
            $out = [];

            foreach ($binaries as $group => $commands) {
                $out[] = [mb_convert_case($group, MB_CASE_TITLE)];

                foreach ($commands as $binary) {
                    $out[] = [
                        '    ' .
                        Console\Chrome\Text::colorize(
                            $binary['name'],
                            'foreground(blue)'
                        ),
                        $binary['description']
                    ];
                }
            }

            echo Console\Chrome\Text::columnize($out);
        } else {
            $out = null;

            foreach ($binaries as $group => $commands) {
                foreach ($commands as $binary) {
                    $out .= $group . ':' . $binary['name'] . "\n";
                }
            }

            echo $out;
        }

        return;
    }

    /**
     * The command usage.
     *
     * @return  int
     */
    public function usage()
    {
        echo
            'Usage   : core:welcome <options>', "\n",
            'Options :', "\n",
            $this->makeUsageOptionsList([
                'l'    => 'Filter libraries to list (comma-separated).',
                'V'    => 'No-verbose, i.e. be as quiet as possible, just ' .
                          'print essential informations.',
                'help' => 'This help.'
            ]);

        return;
    }
}

__halt_compiler();
This page.
