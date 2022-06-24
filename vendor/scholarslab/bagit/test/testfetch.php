<?php

require_once 'lib/bagit_fetch.php';
require_once 'lib/bagit_utils.php';

class BagItFetchTest extends PHPUnit_Framework_TestCase
{
    var $tmpdir;
    var $fetch;

    public function setUp()
    {
        $this->tmpdir = tmpdir();
        mkdir($this->tmpdir);

        file_put_contents(
            "{$this->tmpdir}/fetch.txt",
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n"
        );

        $this->fetch = new BagItFetch("{$this->tmpdir}/fetch.txt");
    }

    public function tearDown()
    {
        rrmdir($this->tmpdir);
    }

    public function testFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/fetch.txt",
            $this->fetch->fileName
        );
    }

    public function testData()
    {
        $data = $this->fetch->data;

        $this->assertEquals(2, count($data));
        $this->assertEquals("http://www.google.com", $data[0]['url']);
        $this->assertEquals("http://www.yahoo.com", $data[1]['url']);
    }

    public function testRead()
    {
        file_put_contents(
            "{$this->tmpdir}/fetch.txt",
            "http://www.scholarslab.org/ - data/scholarslab/index.html"
        );

        $this->fetch->read();

        $this->assertFalse(
            array_key_exists('data/google/index.html', $this->fetch->data)
        );
        $this->assertFalse(
            array_key_exists('data/yahoo/index.html', $this->fetch->data)
        );
        $this->assertEquals(
            'data/scholarslab/index.html',
            $this->fetch->data[0]['filename']
        );
    }

    public function testWrite()
    {
        array_push(
            $this->fetch->data,
            array('url' => 'http://www.scholarslab.org/', 'length' => '-', 'filename' => 'data/scholarslab/index.html')
        );
        $this->fetch->write();
        $this->assertEquals(
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n" .
            "http://www.scholarslab.org/ - data/scholarslab/index.html\n",
            file_get_contents("{$this->tmpdir}/fetch.txt")
        );
    }

    public function testGetData()
    {
        $data = $this->fetch->getData();

        $this->assertEquals(2, count($data));
        $this->assertEquals("http://www.google.com", $data[0]['url']);
        $this->assertEquals("http://www.yahoo.com", $data[1]['url']);
    }

    public function testDownload()
    {
        $tmp = $this->tmpdir;

        $this->assertFalse(is_file("$tmp/data/google/index.html"));
        $this->assertFalse(is_file("$tmp/data/yahoo/index.html"));

        $errors = array();
        $this->fetch->download();

        $this->assertFileExists("$tmp/data/google/index.html");
        $this->assertFileExists("$tmp/data/yahoo/index.html");
    }

    public function testAdd()
    {
        $this->assertEquals(2, count($this->fetch->data));

        $this->fetch->add(
            'http://www.scholarslab.org/',
            'data/scholarslab/index.html'
        );

        $this->assertEquals(3, count($this->fetch->data));
        $this->assertEquals(
            'data/scholarslab/index.html',
            $this->fetch->data[2]['filename']
        );

        $this->assertEquals(
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n" .
            "http://www.scholarslab.org/ - data/scholarslab/index.html\n",
            file_get_contents("{$this->tmpdir}/fetch.txt")
        );
    }

    public function testClear()
    {
        $this->assertEquals(2, count($this->fetch->data));

        $this->fetch->clear();

        $this->assertEquals(0, count($this->fetch->data));
        $this->assertFalse(
            array_key_exists('data/google/index.html', $this->fetch->data)
        );
        $this->assertFalse(
            array_key_exists('data/yahoo/index.html', $this->fetch->data)
        );
    }

    public function testEmptyWrite()
    {
        $this->fetch->clear();
        $this->fetch->write();
        $this->assertFileNotExists("{$this->tmpdir}/fetch.txt");
    }

    public function testNewBagEmpty()
    {
        $bagdir = "{$this->tmpdir}/_bag";

        $bag    = new BagIt($bagdir);
        $this->assertFileNotExists("$bagdir/fetch.txt");

        $bag->update();
        $this->assertFileNotExists("$bagdir/fetch.txt");
    }

}

?>
