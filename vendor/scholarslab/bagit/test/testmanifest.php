<?php

require_once 'lib/bagit_manifest.php';
require_once 'lib/bagit_utils.php';

class BagItManifestTest extends PHPUnit_Framework_TestCase
{
    var $tmpdir;
    var $prefix;
    var $manifest;

    public function setUp()
    {
        $this->tmpdir = tmpdir();
        mkdir($this->tmpdir);

        $this->prefix = __DIR__ . '/TestBag';
        $src = "{$this->prefix}/manifest-sha1.txt";
        $dest = "{$this->tmpdir}/manifest-sha1.txt";

        copy($src, $dest);
        $this->manifest = new BagItManifest($dest, $this->prefix . '/');
    }

    public function tearDown()
    {
        rrmdir($this->tmpdir);
    }

    public function testPathPrefix()
    {
        $this->assertEquals($this->prefix . '/', $this->manifest->pathPrefix);
    }

    public function testFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->manifest->fileEncoding);

        $manifest = new BagItManifest(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->prefix,
            'ISO-8859-1'
        );
        $this->assertEquals('ISO-8859-1', $manifest->fileEncoding);
    }

    public function testFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->manifest->fileName
        );
    }

    public function testData()
    {
        $this->assertInternalType('array', $this->manifest->data);
        $this->assertEquals(7, count($this->manifest->data));

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->data['data/README.txt']
        );
    }

    public function testHashEncoding()
    {
        $this->assertEquals('sha1', $this->manifest->hashEncoding);

        $md5 = "{$this->tmpdir}/manifest-md5.txt";
        touch($md5);
        $md5Manifest = new BagItManifest($md5, $this->prefix);
        $this->assertEquals('md5', $md5Manifest->hashEncoding);
    }

    public function testRead()
    {
        file_put_contents(
            "{$this->tmpdir}/manifest-sha1.txt",
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file1.txt\n" .
            "abababababababababababababababababababab file2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd file3.txt\n"
        );

        $data = $this->manifest->read();

        $this->assertTrue($this->manifest->data === $data);

        $this->assertEquals(3, count($data));

        $keys = array_keys($data);
        sort($keys);
        $this->assertEquals('file1.txt', $keys[0]);
        $this->assertEquals('file2.txt', $keys[1]);
        $this->assertEquals('file3.txt', $keys[2]);
    }

    public function testReadFileName()
    {
        $filename = "{$this->tmpdir}/manifest-md5.txt";
        file_put_contents(
            $filename,
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-a.txt\n" .
            "abababababababababababababababab file-b.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcd file-c.txt\n"
        );

        $data = $this->manifest->read($filename);

        $this->assertTrue($this->manifest->data === $data);

        $this->assertEquals(3, count($data));

        $keys = array_keys($data);
        sort($keys);
        $this->assertEquals('file-a.txt', $keys[0]);
        $this->assertEquals('file-b.txt', $keys[1]);
        $this->assertEquals('file-c.txt', $keys[2]);

        $this->assertEquals($filename, $this->manifest->fileName);
        $this->assertEquals('md5', $this->manifest->getHashEncoding());
    }

    public function testClear()
    {
        $this->manifest->clear();

        $this->assertEquals(0, count($this->manifest->data));
        $this->assertEquals(0, filesize($this->manifest->fileName));
    }

    public function testUpdate()
    {
        // First, clear it out and verify it.
        $this->manifest->clear();
        $this->assertEquals(0, count($this->manifest->data));
        $this->assertEquals(0, filesize($this->manifest->fileName));

        // Now, regenerate it and test.
        $this->manifest->update(rls("{$this->prefix}/data"));
        $this->assertEquals(7, count($this->manifest->data));

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->data['data/README.txt']
        );
    }

    public function testCalculateHash()
    {
        $fileName = "{$this->tmpdir}/testCalculateHash";
        $data = "This space intentionally left blank.\n";
        file_put_contents($fileName, $data);

        $hash = $this->manifest->calculateHash($fileName);

        $this->assertEquals('a5c44171ca6618c6ee24c3f3f3019df8df09a2e0', $hash);
    }

    public function testWrite()
    {
        $this->manifest->data = array(
            'file-1.txt' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'file-2.txt' => 'abababababababababababababababababababab',
            'file-3.txt' => 'abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd'
        );

        $this->manifest->write();

        $this->assertEquals(
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-1.txt\n" .
            "abababababababababababababababababababab file-2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd file-3.txt\n",
            file_get_contents($this->manifest->fileName)
        );
    }

    public function testWriteFileName()
    {
        $this->manifest->data = array(
            'file-1.txt' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'file-2.txt' => 'abababababababababababababababababababab',
            'file-3.txt' => 'abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd'
        );

        $fileName = "{$this->tmpdir}/writetest-sha1.txt";
        $this->manifest->write($fileName);

        $this->assertEquals(
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-1.txt\n" .
            "abababababababababababababababababababab file-2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd file-3.txt\n",
            file_get_contents($fileName)
        );
        $this->assertEquals($fileName, $this->manifest->fileName);
    }

    public function testGetHash()
    {
        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->getHash('data/imgs/109x109xcoins1-150x150.jpg')
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->getHash('data/imgs/109x109xprosody.png')
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->getHash('data/imgs/110x108xmetaphor1.png')
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->getHash('data/imgs/fellows1-150x150.png')
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->getHash('data/imgs/fibtriangle-110x110.jpg')
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->getHash('data/imgs/uvalib.png')
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->getHash('data/README.txt')
        );
    }

    public function testGetHashMissing()
    {
        $this->assertNull($this->manifest->getHash('data/missing'));
    }

    public function testGetHashAbsolute()
    {
        $pre = $this->prefix;

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->getHash("$pre/data/imgs/109x109xcoins1-150x150.jpg")
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->getHash("$pre/data/imgs/109x109xprosody.png")
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->getHash("$pre/data/imgs/110x108xmetaphor1.png")
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->getHash("$pre/data/imgs/fellows1-150x150.png")
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->getHash("$pre/data/imgs/fibtriangle-110x110.jpg")
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->getHash("$pre/data/imgs/uvalib.png")
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->getHash("$pre/data/README.txt")
        );
    }

    public function testGetData()
    {
        $data = $this->manifest->getData();

        $this->assertInternalType('array', $data);
        $this->assertEquals(7, count($data));

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $data['data/README.txt']
        );
    }

    public function testGetFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->manifest->getFileName()
        );
    }

    public function testGetFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->manifest->getFileEncoding());

        $manifest = new BagItManifest(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->prefix,
            'ISO-8859-1'
        );
        $this->assertEquals('ISO-8859-1', $manifest->getFileEncoding());
    }

    public function testSetFileEncoding()
    {
        $this->manifest->setFileEncoding('ISO-8859-1');
        $this->assertEquals('ISO-8859-1', $this->manifest->fileEncoding);
    }

    public function testGetHashEncoding()
    {
        $this->assertEquals('sha1', $this->manifest->getHashEncoding());

        $md5 = "{$this->tmpdir}/manifest-md5.txt";
        touch($md5);
        $md5Manifest = new BagItManifest($md5, $this->prefix);
        $this->assertEquals('md5', $md5Manifest->getHashEncoding());
    }

    private function _testSetHashEncoding($hashEncoding) {
        $fileName = $this->manifest->fileName;

        $this->manifest->setHashEncoding($hashEncoding);

        $this->assertEquals($hashEncoding, $this->manifest->getHashEncoding());
        $this->assertEquals(
            "{$this->tmpdir}/manifest-{$hashEncoding}.txt",
            $this->manifest->fileName
        );

        if ($fileName != $this->manifest->fileName) {
            $this->assertFalse(file_exists($fileName));
        }
        $this->assertFileExists($this->manifest->fileName);
    }

    public function testSetHashEncodingMD5()
    {
        $this->_testSetHashEncoding('md5');
    }

    public function testSetHashEncodingSHA1()
    {
        $this->_testSetHashEncoding('sha1');
    }

    public function testSetHashEncodingERR()
    {
        $this->_testSetHashEncoding('err');
    }

    public function testValidateOK()
    {
        $errors = array();
        $this->assertTrue($this->manifest->validate($errors));
        $this->assertEquals(0, count($errors));
    }

    public function testValidateMissingManifest()
    {
        $manifest = new BagItManifest(
            '/tmp/probably/does/not/exist/missing.txt'
        );

        $errors = array();
        $this->assertFalse($manifest->validate($errors));
        $this->assertTrue(seenAtKey($errors, 0, 'missing.txt'));
        $this->assertTrue(seenAtKey($errors, 1, 'missing.txt does not exist.'));
    }

    public function testValidateMissingData()
    {
        $this->manifest->data['data/missing.txt']
            = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $errors = array();
        $this->assertFalse($this->manifest->validate($errors));

        $this->assertTrue(seenAtKey($errors, 0, 'data/missing.txt'));
        $this->assertTrue(seenAtKey($errors, 1, 'Missing data file.'));
    }

    public function testValidateChecksum()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir($tmp . '/data');
            file_put_contents(
                "$tmp/manifest-sha1.txt",
                "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa data/missing.txt\n"
            );

            touch("$tmp/data/missing.txt");

            $manifest = new BagItManifest("$tmp/manifest-sha1.txt", "$tmp/");
            $errors = array();
            $this->assertFalse($manifest->validate($errors));

            $this->assertTrue(seenAtKey($errors, 0, 'data/missing.txt'));
            $this->assertTrue(seenAtKey($errors, 1, 'Checksum mismatch.'));
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

}

?>
