@echo off

REM **
REM * Hoa
REM *
REM *
REM * @license
REM *
REM * New BSD License
REM *
REM * Copyright © 2007-2015, Ivan Enderlin. All rights reserved.
REM *
REM * Redistribution and use in source and binary forms, with or without
REM * modification, are permitted provided that the following conditions are met:
REM *     * Redistributions of source code must retain the above copyright
REM *       notice, this list of conditions and the following disclaimer.
REM *     * Redistributions in binary form must reproduce the above copyright
REM *       notice, this list of conditions and the following disclaimer in the
REM *       documentation and/or other materials provided with the distribution.
REM *     * Neither the name of the Hoa nor the names of its contributors may be
REM *       used to endorse or promote products derived from this software without
REM *       specific prior written permission.
REM *
REM * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
REM * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
REM * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
REM * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
REM * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
REM * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
REM * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
REM * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
REM * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
REM * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
REM * POSSIBILITY OF SUCH DAMAGE.
REM **
REM
REM **
REM * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
REM * @copyright  Copyright © 2007-2015 Ivan Enderlin.
REM * @license    New BSD License
REM **

BREAK=ON
set PHP="php.exe"
set SCRIPT_DIR=%~dp0
set HOA=%SCRIPT_DIR%Hoa.php

"%PHP%" "%HOA%" %*
