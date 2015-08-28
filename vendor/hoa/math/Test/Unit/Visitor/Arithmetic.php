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

namespace Hoa\Math\Test\Unit\Visitor;

use Hoa\Compiler;
use Hoa\File;
use Hoa\Math as LUT;
use Hoa\Math\Visitor\Arithmetic as CUT;
use Hoa\Regex;
use Hoa\Test;

/**
 * Class \Hoa\Math\Test\Unit\Visitor\Arithmetic.
 *
 * Test suite of the hoa://Library/Math/Arithmetic.pp grammar and the
 * Hoa\Math\Visitor\Arithmetic class.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Arithmetic extends Test\Unit\Suite
{
    public function case_visitor_exhaustively()
    {
        $this
            ->given(
                $sampler  = new Compiler\Llk\Sampler\BoundedExhaustive(
                    Compiler\Llk\Llk::load(
                        new File\Read('hoa://Library/Math/Test/Unit/Arithmetic.pp')
                    ),
                    new Regex\Visitor\Isotropic(
                        new LUT\Sampler\Random()
                    ),
                    9
                ),
                $compiler = Compiler\Llk\Llk::load(
                    new File\Read('hoa://Library/Math/Arithmetic.pp')
                ),
                $visitor  = new CUT()
            )
            ->executeOnFailure(function () use (&$expression) {
                echo 'Failed expression: ', $expression, '.', "\n";
            })
            ->when(function () use (&$sampler, &$compiler, &$visitor) {
                foreach ($sampler as $expression) {
                    $dump = $expression;

                    try {
                        $x = (float) $visitor->visit(
                            $compiler->parse($expression)
                        );
                    } catch (\Exception $e) {
                        continue;
                    }

                    eval('$y = (float) ' . $expression . ';');

                    if (is_nan($x) || is_nan($y)) {
                        $this->boolean(true);

                        continue;
                    }

                    $this
                        ->float($x)
                            ->isNearlyEqualTo($y);
                }
            });
    }
}
