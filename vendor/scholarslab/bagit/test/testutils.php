<?php

require_once 'lib/bagit_utils.php';

class BagItUtilsTest extends PHPUnit_Framework_TestCase
{

    public function testFilterArrayMatches()
    {
        $input = array(
            'abcde',
            'bcdef',
            'cdefg',
            'defgh',
            'efghi',
            'fghij'
        );

        $this->assertEquals(1, count(filterArrayMatches('/a/', $input)));

        $e = filterArrayMatches('/.*e.*/', $input);
        $this->assertEquals(5, count($e));
        $this->assertEquals('abcde', $e[0][0]);
        $this->assertEquals('bcdef', $e[1][0]);
        $this->assertEquals('cdefg', $e[2][0]);
        $this->assertEquals('defgh', $e[3][0]);
        $this->assertEquals('efghi', $e[4][0]);
    }

    public function testFilterArrayMatchesFail()
    {
        $input = array(
            'abcde',
            'bcdef',
            'cdefg',
            'defgh',
            'efghi',
            'fghij'
        );

        $this->assertEquals(0, count(filterArrayMatches('/z/', $input)));
    }

    public function testEndsWithTrue()
    {
        $this->assertTrue(endsWith("Scholars' Lab", 'b'));
        $this->assertTrue(endsWith("Scholars' Lab", 'ab'));
        $this->assertTrue(endsWith("Scholars' Lab", 'Lab'));
    }

    public function testEndsWithFalse()
    {
        $this->assertFalse(endsWith("Scholars' Lab", 'z'));
    }

    private function _testRls($dirnames) {
        $files = array();
        foreach ($dirnames as $dirname) {
            foreach (scandir($dirname) as $filename) {
                if ($filename[0] != '.' && is_file("$dirname/$filename")) {
                    array_push($files, "$dirname/$filename");
                }
            }
        }
        sort($files);

        $lsout = rls($dirnames[0]);
        sort($lsout);

        $this->assertEquals(count($files), count($lsout));

        for ($i=0; $i<count($files); $i++) {
            $this->assertEquals($files[$i], $lsout[$i]);
        }
    }

    public function testRlsShallow()
    {
        $dirname = __DIR__ . '/../lib';
        $this->_testRls(array($dirname));
    }

    public function testRlsDeep()
    {
        $dirname = __DIR__;
        $this->_testRls(
            array($dirname, "$dirname/TestBag", "$dirname/TestBag/data",
                  "$dirname/TestBag/data/imgs")
        );
    }

    public function testRrmdirShallow()
    {
        $tmpdir = tmpdir();

        mkdir($tmpdir);
        touch("$tmpdir/a");
        touch("$tmpdir/b");
        touch("$tmpdir/c");

        $this->assertFileExists("$tmpdir/a");

        rrmdir($tmpdir);

        $this->assertFalse(file_exists($tmpdir));
        $this->assertFalse(file_exists("$tmpdir/a"));
        $this->assertFalse(file_exists("$tmpdir/b"));
        $this->assertFalse(file_exists("$tmpdir/c"));
    }

    public function testRrmdirDeep()
    {
        $tmpdir = tmpdir();

        mkdir($tmpdir);
        mkdir("$tmpdir/sub");
        touch("$tmpdir/sub/a");
        touch("$tmpdir/sub/b");
        touch("$tmpdir/sub/c");

        $this->assertFileExists("$tmpdir/sub/c");

        rrmdir($tmpdir);

        $this->assertFalse(file_exists($tmpdir));
        $this->assertFalse(file_exists("$tmpdir/sub"));
        $this->assertFalse(file_exists("$tmpdir/sub/a"));
        $this->assertFalse(file_exists("$tmpdir/sub/b"));
        $this->assertFalse(file_exists("$tmpdir/sub/c"));
    }

    public function testRrmdirFile()
    {
        $tmpdir = tmpdir();
        touch($tmpdir);

        $this->assertFileExists($tmpdir);
        rrmdir($tmpdir);
        $this->assertFileExists($tmpdir);
    }

    public function testTmpdir()
    {
        $tmpdir = tmpdir();
        $this->assertFalse(file_exists($tmpdir));
        $this->assertTrue(strpos($tmpdir, sys_get_temp_dir()) !== false);
    }

    public function testTmpdirPrefix()
    {
        $tmpdir = tmpdir('test_');
        $this->assertStringStartsWith('test_', basename($tmpdir));
    }

    public function testSeenAtKeyIntegerKey()
    {
        $data = array(
            array('a', 'b', 'c'),
            array('d', 'e', 'f'),
            array('g', 'h', 'i')
        );

        $this->assertTrue(seenAtKey($data, 0, 'a'));
        $this->assertTrue(seenAtKey($data, 1, 'e'));
        $this->assertTrue(seenAtKey($data, 2, 'i'));
    }

    public function testSeenAtKeyStringKey()
    {
        $data = array(
            array('a' => 1, 'z' => 2),
            array('a' => 3, 'z' => 4),
            array('a' => 5, 'z' => 6),
            array('a' => 7, 'z' => 8)
        );

        $this->assertTrue(seenAtKey($data, 'a', 1));
        $this->assertTrue(seenAtKey($data, 'z', 4));
        $this->assertTrue(seenAtKey($data, 'a', 5));
        $this->assertTrue(seenAtKey($data, 'z', 8));
    }

    public function testSeenAtKeyFail()
    {
        $data = array(
            array('a' => 1, 'z' => 2),
            array('a' => 3, 'z' => 4),
            array('a' => 5, 'z' => 6),
            array('a' => 7, 'z' => 8)
        );

        $this->assertFalse(seenAtKey($data, 'a', 2));
        $this->assertFalse(seenAtKey($data, 'z', 5));
        $this->assertFalse(seenAtKey($data, 'a', 6));
        $this->assertFalse(seenAtKey($data, 'z', 9));
        $this->assertFalse(seenAtKey($data, 'm', 13));
    }

    public function testSaveUrl()
    {
        $tmpdir = tmpdir();
        mkdir($tmpdir);

        saveUrl('http://www.google.com', "$tmpdir/google.html");

        $this->assertFileExists("$tmpdir/google.html");
        $this->assertContains(
            'html',
            strtolower(file_get_contents("$tmpdir/google.html"))
        );
    }

    public function testFindFirstExistingPass()
    {
        $tmpdir = tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertEquals(
            "$tmpdir/c",
            findFirstExisting(array("$tmpdir/a", "$tmpdir/b", "$tmpdir/c"))
        );
    }

    public function testFindFirstExistingFail()
    {
        $tmpdir = tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertNull(
            findFirstExisting(array("$tmpdir/a", "$tmpdir/b", "$tmpdir/d"))
        );
    }

    public function testFindFirstExistingDefault()
    {
        $tmpdir = tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertEquals(
            "$tmpdir/default",
            findFirstExisting(array("$tmpdir/a", "$tmpdir/b", "$tmpdir/d"),
                              "$tmpdir/default")
        );
    }

    public function testReadFileText()
    {
        $this->assertEquals(
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n",
            readFileText(__DIR__ . '/TestBag/bagit.txt', 'UTF-8')
        );
    }

    public function testReadLines()
    {
        $lines = readLines(__DIR__ . '/TestBag/bagit.txt', 'UTF-8');
        $this->assertEquals(2, count($lines));
        $this->assertEquals("BagIt-Version: 0.96", $lines[0]);
        $this->assertEquals("Tag-File-Character-Encoding: UTF-8", $lines[1]);
    }

    public function testWriteFileText()
    {
        $tmpfile = tmpdir();

        writeFileText(
            $tmpfile,
            'UTF-8',
            "This is some text.\nYep, it sure is.\n"
        );

        $this->assertEquals(
            "This is some text.\nYep, it sure is.\n",
            file_get_contents($tmpfile)
        );
    }

    public function testBagIt_sanitizeFileNameWhiteSpace()
    {
        $this->assertEquals(
            "this_contained_significant_whitespace_at_one_time",
            BagIt_sanitizeFileName("this contained\tsignificant\t" .
                                   "whitespace   at      one        time")
        );
    }

    public function testBagIt_sanitizeFileNameRemove()
    {
        $this->assertEquals(
            'thisthatwow',
            BagIt_sanitizeFileName("this&that###wow!!!!~~~???")
        );
    }

    public function testBagIt_sanitizeFileNameDevs()
    {
        $this->assertStringStartsWith('nul_', BagIt_sanitizeFileName('NUL'));
        $this->assertStringStartsWith('aux_', BagIt_sanitizeFileName('AUX'));
        $this->assertStringStartsWith('com3_', BagIt_sanitizeFileName('COM3'));
        $this->assertStringStartsWith('lpt6_', BagIt_sanitizeFileName('LPT6'));
    }

    public function testBagIt_sanitizeFileName()
    {
        $this->assertEquals(
            'this-is-ok.txt',
            BagIt_sanitizeFileName('this-is-ok.txt')
        );
    }

    public function testBagIt_readBagItFile()
    {
        $filename = __DIR__ . '/TestBag/bagit.txt';
        list($versions, $encoding, $errors) = BagIt_readBagItFile($filename);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
        $this->assertEquals('UTF-8', $encoding);
        $this->assertEquals(0, count($errors));
    }

    public function testBagIt_readBagItFileNoVersion()
    {
        $tmpfile = tmpdir('bagit_');
        file_put_contents(
            $tmpfile,
            "Tag-File-Character-Encoding: ISO-8859-1\n"
        );

        list($versions, $encoding, $errors) = BagIt_readBagItFile($tmpfile);
        $this->assertNull($versions);
        $this->assertEquals('ISO-8859-1', $encoding);
        $this->assertEquals(1, count($errors));
        $this->assertEquals('bagit', $errors[0][0]);
        $this->assertEquals(
            'Error reading version information from bagit.txt file.',
            $errors[0][1]
        );
    }

    public function testBagIt_readBagItFileNoEncoding()
    {
        $tmpfile = tmpdir('bagit_');
        file_put_contents(
            $tmpfile,
            "BagIt-Version: 0.96\n"
        );

        list($versions, $encoding, $errors) = BagIt_readBagItFile($tmpfile);
        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);

        // I'm not entirely sure that this is the behavior I want here.
        // I think maybe it should set the default (UTF-8) and signal an
        // error.
        $this->assertNull($encoding);
        $this->assertEquals(0, count($errors));
    }

    public function testBagIt_readBagItFileMissing()
    {
        $filename = __DIR__ . '/doesn-not-exist';
        list($versions, $encoding, $errors) = BagIt_readBagItFile($filename);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
        $this->assertEquals('UTF-8', $encoding);
        $this->assertEquals(0, count($errors));
    }

    public function testBagIt_parseVersionStringPass()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $versions = BagIt_parseVersionString($data);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
    }

    public function testBagIt_parseVersionStringFail()
    {
        $data =
            "BagIt-Versions: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $versions = BagIt_parseVersionString($data);

        $this->assertNull($versions);
    }

    public function testBagIt_parseEncodingStringPass()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $encoding = BagIt_parseEncodingString($data);
        $this->assertEquals('UTF-8', $encoding);
    }

    public function testBagIt_parseEncodingStringFail()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-encoding: UTF-8\n";
        $encoding = BagIt_parseEncodingString($data);
        $this->assertNull($encoding);
    }

    private function _clearTagManifest() {
        // Other tests add a tagmanifest-sha1.txt, which isn't in the
        // archives, at the end of the list. Rm it.
        $rmfile = __DIR__ . '/TestBag/tagmanifest-sha1.txt';
        if (file_exists($rmfile)) {
            unlink($rmfile);
        }
    }

    public function testBagIt_uncompressBagZip()
    {
        $zipfile = __DIR__ . '/TestBag.zip';
        $output = BagIt_uncompressBag($zipfile);

        $this->assertFileExists($output);
        $this->assertTrue(strpos($output, sys_get_temp_dir()) !== false);

        $this->_clearTagManifest();

        $bagFiles = rls(__DIR__ . '/TestBag');
        sort($bagFiles);
        $outFiles = rls($output);
        sort($outFiles);

        $this->assertEquals(count($bagFiles), count($outFiles));
        for ($i=0; $i<count($outFiles); $i++) {
            $this->assertEquals(
                basename($bagFiles[$i]),
                basename($outFiles[$i])
            );
        }
    }

    public function testBagIt_uncompressBagTar()
    {
        $tarfile = __DIR__ . '/TestBag.tgz';
        $output = BagIt_uncompressBag($tarfile);

        $this->assertFileExists($output);
        $this->assertTrue(strpos($output, sys_get_temp_dir()) !== false);

        $this->_clearTagManifest();

        $bagFiles = rls(__DIR__ . '/TestBag');
        sort($bagFiles);
        $outFiles = rls($output);
        sort($outFiles);

        $this->assertEquals(count($bagFiles), count($outFiles));
        for ($i=0; $i<count($outFiles); $i++) {
            $this->assertEquals(
                basename($bagFiles[$i]),
                basename($outFiles[$i])
            );
        }
    }

    /**
     * @expectedException ErrorException
     */
    public function testBagIt_uncompressBagError()
    {
        BagIt_uncompressBag(__DIR__ . '/TestBag');
    }

    /* TODO: Fix these so that they're testing correctly.
    public function testBagIt_compressBagZip()
    {
        $this->_clearTagManifest();

        $output = tmpdir() . '.zip';
        BagIt_compressBag(__DIR__ . '/TestBag', $output, 'zip');

        $this->assertFileEquals(__DIR__ . '/TestBag.zip', $output);
    }

    public function testBagIt_compressBagTar()
    {
        $this->_clearTagManifest();

        $output = tmpdir() . '.tgz';
        BagIt_compressBag(__DIR__ . '/TestBag', $output, 'tgz');

        $this->assertFileEquals(__DIR__ . '/TestBag.tgz', $output);
    }
     */

    public function testBagIt_validateExistsPass()
    {
        $errors = array();
        $this->assertTrue(BagIt_validateExists(__FILE__, $errors));
        $this->assertEquals(0, count($errors));
    }

    public function testBagIt_validateExistsFail()
    {
        $errors = array();
        $this->assertFalse(
            BagIt_validateExists(__DIR__ . '/not-here', $errors)
        );
        $this->assertEquals(1, count($errors));
        $this->assertEquals('not-here', $errors[0][0]);
        $this->assertEquals('not-here does not exist.', $errors[0][1]);
    }

    public function testBagIt_parseBaseInfoEmptyLine()
    {
        $lines = array(
            'some: here',
            '',
            'other: there'
        );

        $info = BagIt_parseBagInfo($lines);
        $this->assertEquals('here', $info['some']);
        $this->assertEquals('there', $info['other']);
    }

    public function testBagIt_parseBaseInfoContinued()
    {
        $lines = array(
            'some: here',
            ' and there',
            'other: there',
            "\tand here"
        );

        $info = BagIt_parseBagInfo($lines);
        $this->assertEquals('here and there', $info['some']);
        $this->assertEquals('there and here', $info['other']);
    }

    public function testBagIt_parseBaseInfoStandard()
    {
        $lines = array(
            'some: here',
            'other: there'
        );

        $info = BagIt_parseBagInfo($lines);
        $this->assertEquals('here', $info['some']);
        $this->assertEquals('there', $info['other']);
    }

}

?>
