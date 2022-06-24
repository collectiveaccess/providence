<?php

require_once 'lib/bagit.php';
require_once 'lib/bagit_utils.php';

class BagItTest extends PHPUnit_Framework_TestCase
{
    var $tmpdir;
    var $bag;

    private function _createBagItTxt($dirname)
    {
        file_put_contents(
            "$dirname/bagit.txt",
            "BagIt-Version: 1.3\n" .
            "Tag-File-Character-Encoding: ISO-8859-1\n"
        );
    }

    public function setUp()
    {
        $this->tmpdir = tmpdir();
        $this->bag = new BagIt($this->tmpdir);
    }

    public function tearDown()
    {
        rrmdir($this->tmpdir);
    }

    public function testBagDirectory()
    {
        $this->assertEquals($this->tmpdir, $this->bag->bagDirectory);
    }

    public function testExtended()
    {
        $this->assertTrue($this->bag->extended);

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            touch($tmp2 . "/bag-info.txt");
            $bag = new BagIt($tmp2, false, false);
            $this->assertFalse($bag->extended);
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagVersion()
    {
        $this->assertEquals(0, $this->bag->bagVersion['major']);
        $this->assertEquals(96, $this->bag->bagVersion['minor']);

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            $this->_createBagItTxt($tmp2);
            $bag = new BagIt($tmp2);
            $this->assertEquals(1, $bag->bagVersion['major']);
            $this->assertEquals(3, $bag->bagVersion['minor']);
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testTagFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->bag->tagFileEncoding);

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            $this->_createBagItTxt($tmp2);
            $bag = new BagIt($tmp2);
            $this->assertEquals('ISO-8859-1', $bag->tagFileEncoding);
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagitFile()
    {
        $this->assertEquals(
            $this->tmpdir . "/bagit.txt",
            $this->bag->bagitFile
        );
        $this->assertFileExists($this->bag->bagitFile);
        $this->assertEquals(
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n",
            file_get_contents($this->bag->bagitFile)
        );
    }

    public function testManifest()
    {
        $this->assertInstanceOf('BagItManifest', $this->bag->manifest);
    }

    public function testTagManifest()
    {
        $this->assertInstanceOf('BagItManifest', $this->bag->tagManifest);
    }

    public function testFetch()
    {
        $this->assertInstanceOf('BagItFetch', $this->bag->fetch);
    }

    public function testBagInfoFile()
    {
        $this->assertEquals(
            $this->tmpdir . "/bag-info.txt",
            $this->bag->bagInfoFile
        );
        $this->assertFileExists($this->bag->bagInfoFile);
    }

    public function testBagInfoContructor()
    {
        $tmp2 = tmpdir();
        try
        {
            $bag = new BagIt($tmp2, FALSE, FALSE, FALSE, array(
                'source-organization' => 'University of Virginia',
                'contact-name'        => 'Someone'
            ));
            $this->assertTrue($bag->extended);
            $this->assertNotNull($bag->bagInfoData);
            $this->assertTrue($bag->hasBagInfoData("source-organization"));
            $this->assertTrue($bag->hasBagInfoData("contact-name"));
            $this->assertFalse($bag->hasBagInfoData("bag-date"));
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoData()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            $this->_createBagItTxt($tmp2);
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->bagInfoData);
            $this->assertCount(3, $bag->bagInfoData);
            $this->assertTrue($bag->hasBagInfoData("Source-organization"));
            $this->assertTrue($bag->hasBagInfoData("Contact-name"));
            $this->assertTrue($bag->hasBagInfoData("Bag-size"));
            $this->assertFalse($bag->hasBagInfoData("bag-size"));
            $this->assertFalse($bag->hasBagInfoData("BAG-SIZE"));
            $this->assertFalse($bag->hasBagInfoData("bag-date"));
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoDuplicateData()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            $this->_createBagItTxt($tmp2);
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "DC-Author: Me\n" .
                "DC-Author: Myself\n" .
                "DC-Author: The other\n" .
                " and more\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->bagInfoData);
            $this->assertCount(4, $bag->bagInfoData);

            $this->assertTrue($bag->hasBagInfoData('DC-Author'));
            $this->assertEquals(
                array( 'Me', 'Myself', 'The other and more' ),
                $bag->getBagInfoData('DC-Author')
            );
        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoDuplicateSetBagData()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "DC-Author: Me\n" .
                "DC-Author: Myself\n" .
                "DC-Author: The other\n"
            );
            $bag = new BagIt($tmp2);

            $bag->setBagInfoData('First', 'This is the first tag value.');
            $bag->setBagInfoData('Second', 'This is the second tag value.');
            $bag->setBagInfoData('Second', 'This is the third tag value.');
            $bag->setBagInfoData('Third', 'This is the fourth tag value.');
            $bag->setBagInfoData('Third', 'This is the fifth tag value.');
            $bag->setBagInfoData('Third', 'This is the sixth tag value.');

            $this->assertEquals(
                'This is the first tag value.',
                $bag->getBagInfoData('First')
            );
            $this->assertEquals(
                array( 'This is the second tag value.', 'This is the third tag value.' ),
                $bag->getBagInfoData('Second')
            );
            $this->assertEquals(
                array(
                    'This is the fourth tag value.',
                    'This is the fifth tag value.',
                    'This is the sixth tag value.'
                ),
                $bag->getBagInfoData('Third')
            );

        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoDuplicateClearBagData()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "DC-Author: Me\n" .
                "DC-Author: Myself\n" .
                "DC-Author: The other\n"
            );
            $bag = new BagIt($tmp2);

            $bag->setBagInfoData('First',  'This is the first tag value.');
            $bag->setBagInfoData('Second', 'This is the second tag value.');
            $bag->setBagInfoData('Second', 'This is the third tag value.');
            $bag->setBagInfoData('Third',  'This is the fourth tag value.');
            $bag->setBagInfoData('Third',  'This is the fifth tag value.');
            $bag->setBagInfoData('Third',  'This is the sixth tag value.');

            $this->assertEquals(
                'This is the first tag value.',
                $bag->getBagInfoData('First')
            );
            $this->assertEquals(
                array( 'This is the second tag value.', 'This is the third tag value.' ),
                $bag->getBagInfoData('Second')
            );
            $this->assertEquals(
                array(
                    'This is the fourth tag value.',
                    'This is the fifth tag value.',
                    'This is the sixth tag value.'
                ),
                $bag->getBagInfoData('Third')
            );

            $bag->clearBagInfoData('Third');
            $this->assertNotNull($bag->getBagInfoData('First'));
            $this->assertNotNull($bag->getBagInfoData('Second'));
            $this->assertNull(   $bag->getBagInfoData('Third'));

        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoDuplicateDataWrite()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            mkdir("$tmp2/data");
            $this->_createBagItTxt($tmp2);
            file_put_contents(
                $tmp2 . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "DC-Author: Me\n" .
                "DC-Author: Myself\n" .
                "DC-Author: The other\n"
            );
            $bag = new BagIt($tmp2);

            $bag->setBagInfoData('First', 'This is the first tag value.');
            $bag->setBagInfoData('Second', 'This is the second tag value.');
            $bag->setBagInfoData('Second', 'This is the third tag value.');
            $bag->setBagInfoData('Third', 'This is the fourth tag value.');
            $bag->setBagInfoData('Third', 'This is the fifth tag value.');
            $bag->setBagInfoData('Third', 'This is the sixth tag value.');

            $bag->update();

            $this->assertEquals(
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "DC-Author: Me\n" .
                "DC-Author: Myself\n" .
                "DC-Author: The other\n" .
                "First: This is the first tag value.\n" .
                "Second: This is the second tag value.\n" .
                "Second: This is the third tag value.\n" .
                "Third: This is the fourth tag value.\n" .
                "Third: This is the fifth tag value.\n" .
                "Third: This is the sixth tag value.\n",
                file_get_contents("$tmp2/bag-info.txt")
            );

        }
        catch (Exception $e)
        {
            rrmdir($tmp2);
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoWrite()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            mkdir("$tmp2/data");

            file_put_contents(
                "$tmp2/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->bagInfoData);

            $bag->setBagInfoData('First', 'This is the first tag value.');
            $bag->setBagInfoData('Second', 'This is the second tag value.');

            $bag->update();
            $bag->package("$tmp2.tgz");
            rrmdir($tmp2);

            $bag2 = new BagIt("$tmp2.tgz");
            $tmp2 = $bag2->bagDirectory;

            $this->assertTrue($bag2->hasBagInfoData('First'));
            $this->assertEquals(
                'This is the first tag value.',
                $bag2->getBagInfoData('First')
            );
            $this->assertTrue($bag2->hasBagInfoData('Second'));
            $this->assertEquals(
                'This is the second tag value.',
                $bag2->getBagInfoData('Second')
            );
        }
        catch (Exception $e)
        {
            if (file_exists($tmp2)) {
                rrmdir($tmp2);
            }
            if (file_exists("$tmp2.tgz")) {
                unlink("$tmp2.tgz");
            }
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testBagInfoWriteTagCase()
    {
        $this->assertEquals(0, count($this->bag->bagInfoData));

        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            mkdir("$tmp2/data");

            $this->_createBagItTxt($tmp2);
            file_put_contents(
                "$tmp2/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);
            $this->assertNotNull($bag->bagInfoData);

            $bag->setBagInfoData('First', 'This is the first tag value.');
            $bag->setBagInfoData('Second', 'This is the second tag value.');

            $bag->update();

            $this->assertEquals(
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n" .
                "First: This is the first tag value.\n" .
                "Second: This is the second tag value.\n",
                file_get_contents("$tmp2/bag-info.txt")
            );
        }
        catch (Exception $e)
        {
            if (file_exists($tmp2)) {
                rrmdir($tmp2);
            }
            if (file_exists("$tmp2.tgz")) {
                unlink("$tmp2.tgz");
            }
            throw $e;
        }
    }

    public function testBagInfoNull()
    {
        $this->bag->bagInfoData = null;
        $this->assertNull($this->bag->bagInfoData);
        $this->bag->hasBagInfoData('hi');
        $this->assertFalse(is_null($this->bag->bagInfoData));
        $this->assertCount(0, $this->bag->bagInfoData);
    }

    public function testHasBagInfoData()
    {
        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            mkdir("$tmp2/data");

            $this->_createBagItTxt($tmp2);
            file_put_contents(
                "$tmp2/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);

            $this->assertTrue( $bag->hasBagInfoData('Source-organization'));
            $this->assertFalse($bag->hasBagInfoData('source-organization'));
            $this->assertFalse($bag->hasBagInfoData('SOURCE-ORGANIZATION'));
            $this->assertFalse($bag->hasBagInfoData('Source-Organization'));
            $this->assertFalse($bag->hasBagInfoData('SoUrCe-oRgAnIzAtIoN'));

            $this->assertTrue( $bag->hasBagInfoData('Contact-name'));
            $this->assertFalse($bag->hasBagInfoData('contact-name'));
            $this->assertFalse($bag->hasBagInfoData('CONTACT-NAME'));
            $this->assertFalse($bag->hasBagInfoData('Contact-Name'));
            $this->assertFalse($bag->hasBagInfoData('CoNtAcT-NaMe'));

            $this->assertTrue( $bag->hasBagInfoData('Bag-size'));
            $this->assertFalse($bag->hasBagInfoData('bag-size'));
            $this->assertFalse($bag->hasBagInfoData('BAG-SIZE'));
            $this->assertFalse($bag->hasBagInfoData('Bag-Size'));
            $this->assertFalse($bag->hasBagInfoData('BaG-SiZe'));

            $this->assertFalse($bag->hasBagInfoData('copyright-date'));
            $this->assertFalse($bag->hasBagInfoData('other-metadata'));
            $this->assertFalse($bag->hasBagInfoData('thrown-away-the-key'));
        }
        catch (Exception $e)
        {
            if (file_exists($tmp2)) {
                rrmdir($tmp2);
            }
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testGetBagInfoData()
    {
        $tmp2 = tmpdir();
        try
        {
            mkdir($tmp2);
            mkdir("$tmp2/data");

            $this->_createBagItTxt($tmp2);
            file_put_contents(
                "$tmp2/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp2);

            $this->assertEquals(   'University of Virginia Alderman Library', $bag->getBagInfoData('Source-organization'));
            $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('source-organization'));
            $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('SOURCE-ORGANIZATION'));
            $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('Source-Organization'));
            $this->assertNotEquals('University of Virginia Alderman Library', $bag->getBagInfoData('SoUrCe-oRgAnIzAtIoN'));

            $this->assertEquals(   'Eric Rochester', $bag->getBagInfoData('Contact-name'));
            $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('contact-name'));
            $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('CONTACT-NAME'));
            $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('Contact-Name'));
            $this->assertNotEquals('Eric Rochester', $bag->getBagInfoData('CoNtAcT-NaMe'));

            $this->assertEquals(   'very, very small', $bag->getBagInfoData('Bag-size'));
            $this->assertNotEquals('very, very small', $bag->getBagInfoData('bag-size'));
            $this->assertNotEquals('very, very small', $bag->getBagInfoData('BAG-SIZE'));
            $this->assertNotEquals('very, very small', $bag->getBagInfoData('Bag-Size'));
            $this->assertNotEquals('very, very small', $bag->getBagInfoData('BaG-SiZe'));

            $this->assertNull($bag->getBagInfoData('copyright-date'));
            $this->assertNull($bag->getBagInfoData('other-metadata'));
            $this->assertNull($bag->getBagInfoData('thrown-away-the-key'));
        }
        catch (Exception $e)
        {
            if (file_exists($tmp2)) {
                rrmdir($tmp2);
            }
            throw $e;
        }
        rrmdir($tmp2);
    }

    public function testSetBagInfoData()
    {
        $this->assertCount(0, $this->bag->bagInfoData);
        $this->bag->setBagInfoData('hi', 'some value');

        $this->assertTrue($this->bag->hasBagInfoData('hi'));
        $this->assertFalse($this->bag->hasBagInfoData('HI'));
        $this->assertFalse($this->bag->hasBagInfoData('Hi'));
        $this->assertFalse($this->bag->hasBagInfoData('hI'));

        $this->assertEquals('some value', $this->bag->getBagInfoData('hi'));
        $this->assertCount(1, $this->bag->bagInfoData);
    }

    public function testBagCompression()
    {
        $this->assertNull($this->bag->bagCompression);
    }

    public function testBagErrors()
    {
        $this->assertInternalType('array', $this->bag->bagErrors);
        $this->assertEquals(0, count($this->bag->bagErrors));
    }

    public function testConstructorValidate()
    {
        $this->assertTrue($this->bag->isValid());
        $this->assertEquals(0, count($this->bag->bagErrors));

        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            $this->_createBagItTxt($tmp);
            $bag = new BagIt($tmp, true);
            $this->assertFalse($bag->isValid());
            $this->assertGreaterThan(0, count($bag->bagErrors));
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorExtended()
    {
        $this->assertFileExists($this->tmpdir . '/bag-info.txt');
        $this->assertFileNotExists($this->tmpdir . '/fetch.txt');
        $this->assertFileExists($this->tmpdir . '/tagmanifest-sha1.txt');

        $tmp = tmpdir();
        try
        {
            $bag = new BagIt($tmp, false, false);
            $this->assertFalse(is_file($tmp . '/bag-info.txt'));
            $this->assertFalse(is_file($tmp . '/fetch.txt'));
            $this->assertFalse(is_file($tmp . '/tagmanifest-sha1.txt'));
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorFetch()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - google/index.html\n" .
                "http://www.yahoo.com - yahoo/index.html\n"
            );
            $bag = new BagIt($tmp, false, true, false);
            $this->assertFalse(
                is_file($bag->getDataDirectory() . '/google/index.html')
            );
            $this->assertFalse(
                is_file($bag->getDataDirectory() . '/yahoo/index.html')
            );
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp, false, true, true);
            $this->assertFileExists($bag->getDataDirectory() . '/google/index.html');
            $this->assertFileExists($bag->getDataDirectory() . '/yahoo/index.html');
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testConstructorInvalidBagitFile()
    {
        $this->assertEquals(0, $this->bag->bagVersion['major']);

        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/bagit.txt",
                "BagIt-Version: a.b\n" .
                "Tag-File-Character-Encoding: ISO-8859-1\n"
            );
            $bag = new BagIt($tmp);
            $this->assertFalse($bag->isValid());
            $bagErrors = $bag->getBagErrors();
            $this->assertTrue(seenAtKey($bagErrors, 0, 'bagit'));
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    private function _testSampleBag($bag)
    {
        $this->assertTrue($bag->isValid());

        // Testing what's in the bag (relativize the paths).
        $stripLen = strlen($bag->bagDirectory) + 1;
        $files = $bag->getBagContents();
        for ($i=0, $lsLen=count($files); $i<$lsLen; $i++)
        {
            $files[$i] = substr($files[$i], $stripLen);
        }
        $this->assertContains('data/imgs/109x109xcoins1-150x150.jpg', $files);
        $this->assertContains('data/imgs/109x109xprosody.png', $files);
        $this->assertContains('data/imgs/110x108xmetaphor1.png', $files);
        $this->assertContains('data/imgs/fellows1-150x150.png', $files);
        $this->assertContains('data/imgs/fibtriangle-110x110.jpg', $files);
        $this->assertContains('data/imgs/uvalib.png', $files);
        $this->assertContains('data/README.txt', $files);

        // Testing the checksums.
        $this->assertEquals('547b21e9c710f562d448a6cd7d32f8257b04e561', $bag->manifest->data['data/imgs/109x109xcoins1-150x150.jpg']);
        $this->assertEquals('fba552acae866d24fb143fef0ddb24efc49b097a', $bag->manifest->data['data/imgs/109x109xprosody.png']);
        $this->assertEquals('4beed314513ad81e1f5fad42672a3b1bd3a018ea', $bag->manifest->data['data/imgs/110x108xmetaphor1.png']);
        $this->assertEquals('4372383348c55775966bb1deeeb2b758b197e2a1', $bag->manifest->data['data/imgs/fellows1-150x150.png']);
        $this->assertEquals('b8593e2b3c2fa3756d2b206a90c7259967ff6650', $bag->manifest->data['data/imgs/fibtriangle-110x110.jpg']);
        $this->assertEquals('aec60202453733a976433833c9d408a449f136b3', $bag->manifest->data['data/imgs/uvalib.png']);
        $this->assertEquals('0de174b95ebacc2d91b0839cb2874b2e8f604b98', $bag->manifest->data['data/README.txt']);

        // Testing the fetch file.
        $data = $bag->fetch->getData();
        $this->assertEquals('http://www.scholarslab.org', $data[0]['url']);
        $this->assertEquals('data/index.html', $data[0]['filename']);
    }

    public function testConstructorDir()
    {
        $bagDir = __DIR__ . '/TestBag';
        $bag = new BagIt($bagDir);

        $this->assertNull($bag->bagCompression);
        $this->_testSampleBag($bag);
    }

    public function testConstructorZip()
    {
        $bagZip = __DIR__ . '/TestBag.zip';
        $bag = new BagIt($bagZip);

        $this->assertEquals('zip', $bag->bagCompression);
        $this->_testSampleBag($bag);
    }

    public function testConstructorTGz()
    {
        $bagTar = __DIR__ . '/TestBag.tgz';
        $bag = new BagIt($bagTar);

        $this->assertEquals('tgz', $bag->bagCompression);
        $this->_testSampleBag($bag);
    }

    public function testIsValid()
    {
        $this->assertTrue($this->bag->isValid());
    }

    public function testIsExtended()
    {
        $this->assertTrue($this->bag->isExtended());

        $tmp = tmpdir();
        try
        {
            $bag = new BagIt($tmp, false, false);
            $this->assertFalse($bag->isExtended());
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);

        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/bag-info.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp, false, false);
            $this->assertFalse($bag->isExtended());
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testGetBagInfo()
    {
        $bagInfo = $this->bag->getBagInfo();

        $this->assertInternalType('array', $bagInfo);

        $this->assertArrayHasKey('version', $bagInfo);
        $this->assertArrayHasKey('encoding', $bagInfo);
        $this->assertArrayHasKey('hash', $bagInfo);

        $this->assertEquals('0.96', $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha1', $bagInfo['hash']);
    }

    public function testGetDataDirectory()
    {
        $dataDir = $this->bag->getDataDirectory();
        $this->assertStringStartsWith($this->tmpdir, $dataDir);
    }

    public function testGetHashEncoding()
    {
        $hash = $this->bag->getHashEncoding();
        $this->assertEquals('sha1', $hash);
    }

    public function testSetHashEncodingMD5()
    {
        $this->bag->setHashEncoding('md5');
        $this->assertEquals('md5', $this->bag->getHashEncoding());
    }

    public function testSetHashEncodingSHA1()
    {
        $this->bag->setHashEncoding('md5');
        $this->bag->setHashEncoding('sha1');
        $this->assertEquals('sha1', $this->bag->getHashEncoding());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetHashEncodingERR()
    {
        $this->bag->setHashEncoding('err');
    }

    public function testSetHashEncodingBoth()
    {
        $this->bag->setHashEncoding('md5');
        $this->assertEquals('md5', $this->bag->manifest->getHashEncoding());
        $this->assertEquals('md5', $this->bag->tagManifest->getHashEncoding());
    }

    public function testGetBagContents()
    {
        $bagContents = $this->bag->getBagContents();

        $this->assertInternalType('array', $bagContents);
        $this->assertEquals(0, count($bagContents));

        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir("$tmp/data");
            file_put_contents(
                $tmp . "/data/something.txt",
                "Source-organization: University of Virginia Alderman Library\n" .
                "Contact-name: Eric Rochester\n" .
                "Bag-size: very, very small\n"
            );
            $bag = new BagIt($tmp);

            $bagContents = $bag->getBagContents();
            $this->assertEquals(1, count($bagContents));
            $this->assertEquals($tmp . '/data/something.txt', $bagContents[0]);
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testGetBagErrors()
    {
        $bagErrors = $this->bag->getBagErrors();
        $this->assertInternalType('array', $bagErrors);
        $this->assertEquals(0, count($bagErrors));

        rrmdir($this->bag->getDataDirectory());
        $this->bag->validate();
        $this->assertGreaterThan(0, count($this->bag->getBagErrors()));
    }

    public function testGetBagErrorsValidate()
    {
        rrmdir($this->bag->getDataDirectory());
        $bagErrors = $this->bag->getBagErrors(true);
        $this->assertInternalType('array', $bagErrors);
        $this->assertGreaterThan(0, count($bagErrors));
    }

    public function testValidateMissingBagFile()
    {
        unlink($this->bag->bagitFile);

        $this->bag->validate();
        $bagErrors = $this->bag->getBagErrors();

        $this->assertFalse($this->bag->isValid());
        $this->assertTrue(seenAtKey($bagErrors, 0, 'bagit.txt'));
    }

    public function testValidateChecksum()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            touch($tmp . '/data/missing.txt');
            $bag = new BagIt($tmp);
            $bag->validate();
            $bagErrors = $bag->getBagErrors();

            $this->assertFalse($bag->isValid());
            $this->assertTrue(seenAtKey($bagErrors, 0, 'data/missing.txt'));
            $this->assertTrue(seenAtKey($bagErrors, 1, 'Checksum mismatch.'));
        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateCreateMissing()
    {
        $tmp = tmpdir();
        try
        {
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFileExists($tmp . '/bagit.txt');
            $this->assertFileExists($tmp . '/manifest-sha1.txt');
            $this->assertTrue(is_dir($tmp . '/data'));

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateSanitize()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir($tmp . '/data');
            touch($tmp . '/data/has space');
            touch($tmp . '/data/PRN');
            touch($tmp . '/data/backup~');
            touch($tmp . '/data/.hidden');
            touch($tmp . '/data/quoted "yep" quoted');

            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFalse(is_file($tmp . '/data/has space'));
            $this->assertFileExists($tmp . '/data/has_space');

            $this->assertFalse(is_file($tmp . '/data/PRN'));
            $this->assertEquals(1, count(glob($tmp . '/data/prn_*')));

            $this->assertFalse(is_file($tmp . '/data/backup~'));

            $this->assertFalse(is_file($tmp . '/data/quoted "yep" quoted'));
            $this->assertFileExists($tmp . '/data/quoted_yep_quoted');

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateChecksums()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "abababababababababababababababababababab data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                "This space intentionally left blank.\n"
            );
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                "a5c44171ca6618c6ee24c3f3f3019df8df09a2e0 data/missing.txt\n",
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertEquals(
                'a5c44171ca6618c6ee24c3f3f3019df8df09a2e0',
                $bag->manifest->data['data/missing.txt']
            );

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateNewFiles()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                "This space intentionally left blank.\n"
            );
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                "a5c44171ca6618c6ee24c3f3f3019df8df09a2e0 data/missing.txt\n",
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertEquals(
                'a5c44171ca6618c6ee24c3f3f3019df8df09a2e0',
                $bag->manifest->data['data/missing.txt']
            );

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateDeletedFiles()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            file_put_contents(
                $tmp . "/manifest-sha1.txt",
                "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd data/missing.txt\n"
            );
            mkdir($tmp . '/data');
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertEquals(
                '',
                file_get_contents($tmp . '/manifest-sha1.txt')
            );
            $this->assertFalse(
                array_key_exists('data/missing.txt', $bag->manifest->data)
            );

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testUpdateExtended()
    {
        $tmp = tmpdir();
        try
        {
            $bag = new BagIt($tmp);
            $bag->update();

            $this->assertFileExists($tmp . '/bag-info.txt');
            $this->assertFileExists($tmp . '/tagmanifest-sha1.txt');
            $this->assertFileNotExists($tmp . '/fetch.txt');

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testAddFile()
    {
        $srcdir = __DIR__ . '/TestBag/data';

        $this->bag->addFile("$srcdir/README.txt", 'data/README.txt');

        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/README.txt");
        $this->assertFileEquals("$srcdir/README.txt", "{$datadir}/README.txt");

        $this->bag->addFile("$srcdir/imgs/uvalib.png", "data/pics/uvalib.png");

        $this->assertFileExists("{$datadir}/pics/uvalib.png");
        $this->assertFileEquals(
            "$srcdir/imgs/uvalib.png",
            "{$datadir}/pics/uvalib.png"
        );
    }

    public function testAddFileAddDataDir()
    {
        $srcdir = __DIR__ . '/TestBag/data';

        $this->bag->addFile("$srcdir/README.txt", 'README.txt');

        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/README.txt");
        $this->assertFileEquals("$srcdir/README.txt", "{$datadir}/README.txt");

        $this->bag->addFile("$srcdir/imgs/uvalib.png", "pics/uvalib.png");

        $this->assertFileExists("{$datadir}/pics/uvalib.png");
        $this->assertFileEquals(
            "$srcdir/imgs/uvalib.png",
            "{$datadir}/pics/uvalib.png"
        );
    }

    public function testCreateFile()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "data/testCreateFile.txt");
        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/testCreateFile.txt");
        $content = file_get_contents("{$datadir}/testCreateFile.txt");
        $this->assertEquals($content, $testContent);
    }

    public function testCreateFileAddDataDir()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "testCreateFile.txt");
        $datadir = $this->bag->getDataDirectory();
        $this->assertFileExists("{$datadir}/testCreateFile.txt");
        $content = file_get_contents("{$datadir}/testCreateFile.txt");
        $this->assertEquals($content, $testContent);
    }

    /**
     * @expectedException BagitException
     */
    public function testCreateFileDuplicate()
    {
        $testContent = "This is some test content.";

        $this->bag->createFile($testContent, "testCreateFile.txt");
        $this->bag->createFile('', "testCreateFile.txt");
    }



    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testAddFileMissing()
    {
        $srcdir = __DIR__ . '/TestBag/data';
        $this->bag->addFile("$srcdir/missing.txt", 'data/missing.txt');
    }

    public function testPackageZip()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $bag->update();

            $bag->package($tmp . '/../bagtmp1.zip', 'zip');
            $this->assertFileExists($tmp . '/../bagtmp1.zip');

            $bag->package($tmp . '/../bagtmp2', 'zip');
            $this->assertFileExists($tmp . '/../bagtmp2.zip');

            // TODO: Test the contents of the package.

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testPackageTGz()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);
            mkdir($tmp . '/data');
            file_put_contents(
                $tmp . '/data/missing.txt',
                'This space intentionally left blank.\n'
            );
            file_put_contents(
                $tmp . "/fetch.txt",
                "http://www.google.com - data/google/index.html\n" .
                "http://www.yahoo.com - data/yahoo/index.html\n"
            );
            $bag = new BagIt($tmp);

            $bag->update();

            $bag->package($tmp . '/../bagtmp1.tgz', 'tgz');
            $this->assertFileExists($tmp . '/../bagtmp1.tgz');
            rename("{$tmp}/../bagtmp1.tgz", "/tmp/bagtmp1.tgz");

            $bag->package($tmp . '/../bagtmp2', 'tgz');
            $this->assertFileExists($tmp . '/../bagtmp2.tgz');
            rename("{$tmp}/../bagtmp2.tgz", "/tmp/bagtmp2.tgz");

            // TODO: Test the contents of the package.

        }
        catch (Exception $e)
        {
            rrmdir($tmp);
            throw $e;
        }
        rrmdir($tmp);
    }

    public function testEmptyDirectory()
    {
        $tmp = tmpdir();
        try
        {
            mkdir($tmp);

            $bag = new BagIt($tmp);
            $this->assertFileExists("$tmp/bagit.txt");
            $this->assertFileExists("$tmp/manifest-sha1.txt");
            $this->assertFileExists("$tmp/bag-info.txt");
            $this->assertFileNotExists("$tmp/fetch.txt");
            $this->assertFileExists("$tmp/tagmanifest-sha1.txt");
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
