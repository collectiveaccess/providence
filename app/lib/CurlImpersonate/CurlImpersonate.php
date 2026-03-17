<?php
// MIT License
// 
// Copyright (c) 2023 Kelvin Yuli Andrian
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.
//
// Obtained from https://github.com/kelvinzer0/curl-impersonate-php
namespace CurlImpersonate;
class CurlImpersonate {
    private $url;
    private $method = 'GET';
    private $headers = array();
    private $cookieFile;
    private $cookieJar;
    private $data;
    private $includeHeaders = false; 
    private $engineCurl = "curl"; 
    private $handle;

    public function setopt($option, $value) {
        switch ($option) {
            case CURLCMDOPT_URL:
                $this->url = $value;
                break;
            case CURLCMDOPT_METHOD:
                $this->method = strtoupper($value);
                break;
            case CURLCMDOPT_POSTFIELDS:
                $this->data = $value;
                break;
            case CURLCMDOPT_HTTP_HEADERS:
                $this->headers = array_merge($this->headers, $value);
                break;
            case CURLCMDOPT_HEADER:
                $this->includeHeaders = (bool)$value;
                break;
            case CURLCMDOPT_ENGINE:
                $this->engineCurl = $value;
                break;
            case CURLCMDOPT_COOKIEFILE:
                $this->cookieFile = $value;
                break;
            case CURLCMDOPT_COOKIEJAR:
                $this->cookieJar = $value;
                break;
            default:
                throw new \InvalidArgumentException("Invalid option: {$option}");
        }
    }

    private function prepareData() {
        if (is_array($this->data) || is_object($this->data)) {
            $this->data = json_encode($this->data);
        }
    }

    public function exec(?array $options=null) {
        $this->prepareData();

        $curlCommand = $this->engineCurl;
        if($options['head'] ?? null) { 
        	$curlCommand .= ' -I';
        }
        
        $curlCommand .= ' -s -X ' . escapeshellarg($this->method);

        if ($this->cookieFile !== null) {
            $curlCommand .= ' --cookie ' . escapeshellarg($this->cookieFile);
        }

        if ($this->cookieJar !== null) {
            $curlCommand .= ' --cookie-jar ' . escapeshellarg($this->cookieJar);
        }

        if ($this->data !== null) {
            $curlCommand .= ' -d ' . escapeshellarg($this->data);
        }

        foreach ($this->headers as $header) {
            $curlCommand .= ' -H ' . escapeshellarg($header);
        }

        if ($this->includeHeaders) {
            $curlCommand .= ' -i';
        }

        $curlCommand .= ' ' . escapeshellarg($this->url);

        return $curlCommand;
    }

    public function execStandard(?array $options=null) {
        $command = $this->exec($options);
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
        }

        return $output;
    }

    public function execStream() {
        $this->prepareData();

        $command = $this->exec();
        $this->handle = popen($command, 'r');
    }

    public function readStream($chunkSize = 4096) {
        if ($this->handle) {
            $output = fread($this->handle, $chunkSize);
            if ($output === false || feof($this->handle)) {
                $this->closeStream();
            }
            return $output;
        }
        return false;
    }

    public function closeStream() {
        if ($this->handle) {
            pclose($this->handle);
            $this->handle = null;
        }
    }
}


define('CURLCMDOPT_URL', 1);
define('CURLCMDOPT_METHOD', 2);
define('CURLCMDOPT_POSTFIELDS', 3);
define('CURLCMDOPT_HTTP_HEADERS', 4);
define('CURLCMDOPT_HEADER', 5);
define('CURLCMDOPT_ENGINE', 6);
define('CURLCMDOPT_COOKIEFILE', 7);
define('CURLCMDOPT_COOKIEJAR', 8);
