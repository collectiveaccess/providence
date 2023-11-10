<?php

namespace whikloj\BagItTools\Test;

use whikloj\BagItTools\Bag;
use whikloj\BagItTools\BagUtils;
use whikloj\BagItTools\Exceptions\BagItException;

/**
 * Class BagItTest
 * @package whikloj\BagItTools\Test
 * @coversDefaultClass \whikloj\BagItTools\Bag
 */
class BagTest extends BagItTestFramework
{

  /**
   * @group Bag
   * @covers ::__construct
   * @covers ::createNewBag
   * @covers ::updateBagIt
   */
    public function testConstructNewBag()
    {
        $this->assertFileDoesNotExist($this->tmpdir);
        $bag = Bag::create($this->tmpdir);
        $this->assertFileExists($this->tmpdir . DIRECTORY_SEPARATOR . "bagit.txt");
        $this->assertTrue(is_file($this->tmpdir . DIRECTORY_SEPARATOR . "bagit.txt"));
        $this->assertFileExists($this->tmpdir . DIRECTORY_SEPARATOR . "data");
        $this->assertTrue(is_dir($this->tmpdir . DIRECTORY_SEPARATOR . "data"));
        $this->assertTrue($bag->validate());
    }

    /**
     * Create a file with an absolute path.
     *
     * @group Bag
     * @covers ::create
     */
    public function testCreateBagAbsolute()
    {
        $this->assertFileDoesNotExist($this->tmpdir);
        $bag = Bag::create($this->tmpdir);
        $this->assertTrue($bag->validate());
    }

    /**
     * Create a bag using a relative path.
     *
     * @group Bag
     * @covers ::create
     * @covers \whikloj\BagItTools\BagUtils::getAbsolute
     */
    public function testCreateBagRelative()
    {
        mkdir($this->tmpdir);
        $newDir = $this->tmpdir . DIRECTORY_SEPARATOR . "some-new-dir";
        $this->assertDirectoryDoesNotExist($newDir);
        $curr = getcwd();
        chdir($this->tmpdir);
        $bag = Bag::create("some-new-dir");
        $this->assertTrue($bag->validate());
        $this->assertDirectoryExists($newDir);
        chdir($curr);
    }

    /**
     * Test creating a bag with a relative path.
     * @covers ::create
     * @covers \whikloj\BagItTools\BagUtils::getAbsolute
     */
    public function testCreateBagRelative2()
    {
        mkdir($this->tmpdir);
        $newDir = $this->tmpdir . DIRECTORY_SEPARATOR . "some-new-dir";
        $this->assertDirectoryDoesNotExist($newDir);
        $curr = getcwd();
        chdir($this->tmpdir);
        $bag = Bag::create("./some-new-dir");
        $this->assertTrue($bag->validate());
        $this->assertDirectoryExists($newDir);
        chdir($curr);
    }

  /**
   * @group Bag
   * @covers ::__construct
   * @covers ::loadBag
   * @covers ::loadBagIt
   * @covers ::loadPayloadManifests
   * @covers ::loadBagInfo
   * @covers ::loadTagManifests
   * @covers ::isExtended
   * @covers ::validate
   * @covers \whikloj\BagItTools\AbstractManifest::loadFile
   * @covers \whikloj\BagItTools\AbstractManifest::cleanUpRelPath
   * @covers \whikloj\BagItTools\AbstractManifest::addToNormalizedList
   */
    public function testOpenBag()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertCount(0, $bag->getErrors());
        $this->assertArrayHasKey('sha256', $bag->getPayloadManifests());
        $this->assertFalse($bag->isExtended());
        $this->assertTrue($bag->validate());
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * Test the getVersion function.
     * @group Bag
     * @covers ::getVersion
     */
    public function testGetVersion()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertArrayEquals(
            [ 'major' => 1, 'minor' => 0 ],
            $bag->getVersion()
        );
        file_put_contents(
            $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bagit.txt',
            "BagIt-Version: 0.97" . PHP_EOL . "Tag-File-Character-Encoding: UTF-8" . PHP_EOL
        );
        $newbag = Bag::load($this->tmpdir);
        $this->assertArrayEquals(
            [ 'major' => 0, 'minor' => 97 ],
            $newbag->getVersion()
        );
    }

  /**
   * Simple tests of reporting bag root and data directory
   * @group Bag
   * @covers ::getDataDirectory
   * @covers ::getBagRoot
   */
    public function testBagDirs()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertEquals($this->tmpdir, $bag->getBagRoot());
        $data = $this->tmpdir . DIRECTORY_SEPARATOR . 'data';
        $this->assertEquals($data, $bag->getDataDirectory());
    }

  /**
   * Test adding a file to a bag.
   * @group Bag
   * @covers ::addFile
   */
    public function testAddFile()
    {
        $source_file = self::TEST_IMAGE['filename'];
        $bag = Bag::create($this->tmpdir);
        $bag->addFile($source_file, "some/image.txt");
        $this->assertDirectoryExists($bag->getDataDirectory() .
        DIRECTORY_SEPARATOR . 'some');
        $this->assertFileExists($bag->getDataDirectory() .
        DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'image.txt');
    }

  /**
   * Test adding a file that doesn't exist.
   * @group Bag
   * @covers ::addFile
   */
    public function testAddFileNoSource()
    {
        $source_file = "some/fake/image.txt";

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("{$source_file} does not exist");

        $bag = Bag::create($this->tmpdir);
        $bag->addFile($source_file, "some/image.txt");
    }

  /**
   * Test adding a file with an invalid destination directory.
   * @group Bag
   * @covers ::addFile
   */
    public function testAddFileInvalidDestination()
    {
        $source_file = self::TEST_IMAGE['filename'];
        $destination = "data/../../../images/places/image.jpg";

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Path {$destination} resolves outside the bag.");

        $bag = Bag::create($this->tmpdir);
        $bag->addFile($source_file, $destination);
    }

    /**
     * Test adding a file to a bag twice.
     * @group Bag
     * @covers ::addFile
     */
    public function testAddFileTwice()
    {
        $source_file = self::TEST_IMAGE['filename'];
        $destination = "some/image.txt";

        $bag = Bag::create($this->tmpdir);
        $bag->addFile($source_file, $destination);
        $this->assertDirectoryExists($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some');
        $this->assertFileExists($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'image.txt');

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("File data/{$destination} already exists in the bag.");

        $bag->addFile($source_file, $destination);
    }

    /**
     * Test removing a file from a bag.
     * @group Bag
     * @covers ::removeFile
     */
    public function testRemoveFile()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertFileExists($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'jekyll_and_hyde.txt');
        $bag->removeFile('jekyll_and_hyde.txt');
        $this->assertFileDoesNotExist($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'jekyll_and_hyde.txt');
    }

    /**
     * Test adding a string to a bag.
     * @group Bag
     * @covers ::addFile
     * @covers ::createFile
     */
    public function testCreateFile()
    {
        $source = "Hi this is a test";
        $bag = Bag::create($this->tmpdir);
        $this->assertDirectoryDoesNotExist($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some');
        $this->assertFileDoesNotExist($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'text.txt');
        $bag->createFile($source, "some/text.txt");
        $this->assertDirectoryExists($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some');
        $this->assertFileExists($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'text.txt');
        $contents = file_get_contents($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'text.txt');
        $this->assertEquals($source, $contents);
    }

    /**
     * Test adding a string to a bag twice.
     * @group Bag
     * @covers ::addFile
     * @covers ::createFile
     */
    public function testCreateFileTwice()
    {
        $source = "Hi this is a test";
        $destination = "some/text.txt";
        $bag = Bag::create($this->tmpdir);
        $bag->createFile($source, $destination);
        $contents = file_get_contents($bag->getDataDirectory() .
            DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'text.txt');
        $this->assertEquals($source, $contents);

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("File data/{$destination} already exists in the bag.");

        $source_two = "This is new stuff";
        $bag->createFile($source_two, $destination);
    }

    /**
     * Ensure empty directories are removed as files are removed.
     * @group Bag
     * @covers ::removeFile
     * @covers ::checkForEmptyDir
     */
    public function testRemoveEmptyDirectories()
    {
        $this->tmpdir = $this->prepareBasicTestBag();

        $picturesDir = implode(DIRECTORY_SEPARATOR, [
            $this->tmpdir,
            'data',
            'pictures',
        ]);

        $bag = Bag::load($this->tmpdir);

        $this->assertFileExists($picturesDir . DIRECTORY_SEPARATOR . 'another_picture.txt');
        $this->assertFileExists($picturesDir . DIRECTORY_SEPARATOR . 'some_more_data.txt');

        // Don't reference the correct path.
        $bag->removeFile('some_more_data.txt');
        $this->assertFileExists($picturesDir . DIRECTORY_SEPARATOR . 'some_more_data.txt');

        // Reference with data/ prefix
        $bag->removeFile('data/pictures/some_more_data.txt');
        $this->assertFileDoesNotExist($picturesDir . DIRECTORY_SEPARATOR . 'some_more_data.txt');

        // Reference without data/ prefix
        $bag->removeFile('pictures/another_picture.txt');
        $this->assertFileDoesNotExist($picturesDir . DIRECTORY_SEPARATOR . 'another_picture.txt');

        // All files are gone so directory data/pictures should have been removed.
        $this->assertDirectoryDoesNotExist($picturesDir);
    }

    /**
     * Ensure a directory is not removed if there are hidden files inside it.
     * @group Bag
     * @covers ::removeFile
     * @covers ::checkForEmptyDir
     */
    public function testKeepDirectoryWithHiddenFile()
    {
        $this->tmpdir = $this->prepareBasicTestBag();

        $bag = Bag::load($this->tmpdir);
        // Directory doesn't exist.
        $this->assertDirectoryDoesNotExist($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'empty');
        // Add files.
        $bag->addFile(self::TEST_IMAGE['filename'], 'data/empty/test.jpg');
        $bag->addFile(self::TEST_TEXT['filename'], 'data/empty/.hidden');
        // Directory does exist.
        $this->assertDirectoryExists($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'empty');
        // Remove the image but leave a hidden file.
        $bag->removeFile('data/empty/test.jpg');
        // Directory does exist.
        $this->assertDirectoryExists($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'empty');
        // Remove the hidden file.
        $bag->removeFile('data/empty/.hidden');
        // Directory is removed too.
        $this->assertDirectoryDoesNotExist($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'empty');
    }

    /**
     * Test that changes made outside the API still are noticed.
     * @group Bag
     * @covers ::update
     * @covers \whikloj\BagItTools\AbstractManifest::getHashes
     */
    public function testUpdateOnDisk()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $manifest = $bag->getPayloadManifests()['sha256'];
        // File doesn't exist.
        $this->assertArrayNotHasKey('data/land.jpg', $manifest->getHashes());
        $this->assertFileDoesNotExist($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'land.jpg');

        // Add the file
        $bag->addFile(self::TEST_IMAGE['filename'], 'data/land.jpg');
        $manifest = $bag->getPayloadManifests()['sha256'];
        $this->assertArrayNotHasKey('data/land.jpg', $manifest->getHashes());
        $this->assertFileExists($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'land.jpg');
        // Update
        $bag->update();
        $manifest = $bag->getPayloadManifests()['sha256'];
        $this->assertArrayHasKey('data/land.jpg', $manifest->getHashes());

        // Remove it manually.
        unlink($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'land.jpg');
        $manifest = $bag->getPayloadManifests()['sha256'];
        // File is gone
        $this->assertFileDoesNotExist($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'land.jpg');
        // Still exists in the manifest.
        $this->assertArrayHasKey('data/land.jpg', $manifest->getHashes());
        // Update BagIt files on disk.
        $bag->update();
        $manifest = $bag->getPayloadManifests()['sha256'];
        // Gone from the payload manifest.
        $this->assertArrayNotHasKey('data/land.jpg', $manifest->getHashes());
    }

    /**
     * Test setting the file encoding.
     * @group Bag
     * @covers ::setFileEncoding
     * @covers ::getFileEncoding
     * @covers \whikloj\BagItTools\BagUtils::getValidCharset
     */
    public function testSetFileEncodingSuccess()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertEquals('UTF-8', $bag->getFileEncoding());

        $bag->setFileEncoding('ISO-8859-1');
        $this->assertEquals('ISO-8859-1', $bag->getFileEncoding());
        $bag->update();
        $this->assertBagItFileEncoding($bag, 'ISO-8859-1');

        $bag->setFileEncoding('US-ASCII');
        $this->assertEquals('US-ASCII', $bag->getFileEncoding());
        $bag->update();
        $this->assertBagItFileEncoding($bag, 'US-ASCII');

        // Also assert that case is not relevant
        $bag->setFileEncoding('EUC-jp');
        $this->assertEquals('EUC-JP', $bag->getFileEncoding());
        $bag->update();
        $this->assertBagItFileEncoding($bag, 'EUC-JP');
    }

    /**
     * Test exception for invalid character set.
     * @group Bag
     * @covers ::setFileEncoding
     * @covers \whikloj\BagItTools\BagUtils::getValidCharset
     */
    public function testSetFileEncodingFailure()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertEquals('UTF-8', $bag->getFileEncoding());

        $bag->setFileEncoding('gb2312');
        $this->assertEquals('GB2312', $bag->getFileEncoding());
        $bag->update();
        $this->assertBagItFileEncoding($bag, 'GB2312');

        $fake_encoding = 'fake-encoding';
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Character set {$fake_encoding} is not supported");

        // Now try a wrong encoding.
        $bag->setFileEncoding($fake_encoding);
    }

    /**
     * Test getting, adding and removing valid algorithms using internal names.
     *
     * @group Bag
     * @covers ::addAlgorithm
     * @covers ::removeAlgorithm
     * @covers ::getAlgorithms
     */
    public function testGetHashesNames()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertArrayEquals(['sha512'], $bag->getAlgorithms());
        // Set one
        $bag->addAlgorithm('sha1');
        $this->assertArrayEquals(['sha512', 'sha1'], $bag->getAlgorithms());
        // Set again
        $bag->addAlgorithm('sha1');
        $this->assertArrayEquals(['sha512', 'sha1'], $bag->getAlgorithms());
        // Set a third
        $bag->addAlgorithm('md5');
        $this->assertArrayEquals(['md5', 'sha512', 'sha1'], $bag->getAlgorithms());
        // Remove one
        $bag->removeAlgorithm('sha512');
        $this->assertArrayEquals(['md5', 'sha1'], $bag->getAlgorithms());
    }

    /**
     * Test getting, adding and removing valid algorithms using common names.
     *
     * @group Bag
     * @covers ::addAlgorithm
     * @covers ::removePayloadManifest
     * @covers ::removeAlgorithm
     * @covers ::getAlgorithms
     * @covers ::hasAlgorithm
     * @covers ::hasHash
     */
    public function testGetHashesCommon()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertArrayEquals(['sha512'], $bag->getAlgorithms());
        // Set one
        $bag->addAlgorithm('SHA1');
        $this->assertArrayEquals(['sha512', 'sha1'], $bag->getAlgorithms());
        // Remove it
        $bag->removeAlgorithm('SHA1');
        $this->assertArrayEquals(['sha512'], $bag->getAlgorithms());
        // Set again differently
        $bag->addAlgorithm('SHA-1');
        $this->assertTrue($bag->hasAlgorithm('sha1'));
        $this->assertTrue($bag->hasAlgorithm('sha-1'));
        $this->assertTrue($bag->hasAlgorithm('SHA1'));
        $this->assertTrue($bag->hasAlgorithm('SHA-1'));
        $this->assertArrayEquals(['sha512', 'sha1'], $bag->getAlgorithms());
        // Set a third
        $bag->addAlgorithm('SHA-224');
        $this->assertArrayEquals(['sha224', 'sha512', 'sha1'], $bag->getAlgorithms());
        // Remove one
        $bag->removeAlgorithm('SHA-512');
        $this->assertArrayEquals(['sha224', 'sha1'], $bag->getAlgorithms());
        // Remove one not set.
        $bag->removeAlgorithm('sha512');
        $this->assertArrayEquals(['sha224', 'sha1'], $bag->getAlgorithms());
        // Really remove it
        $bag->removeAlgorithm('sha224');
        $this->assertArrayEquals(['sha1'], $bag->getAlgorithms());
    }

    /**
     * Try to remove the last algorithm.
     *
     * @group Bag
     * @covers ::removeAlgorithm
     */
    public function testRemoveLastHash()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Cannot remove last payload algorithm, add one before removing this one");

        $bag = Bag::create($this->tmpdir);
        $this->assertArrayEquals(['sha512'], $bag->getAlgorithms());
        $bag->removeAlgorithm('SHA-512');
    }

    /**
     * Test
     * @group Bag
     * @covers ::algorithmIsSupported
     * @covers ::hashIsSupported
     */
    public function testIsSupportedHash()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertTrue($bag->algorithmIsSupported('sha-1'));
        $this->assertFalse($bag->algorithmIsSupported('bob'));
    }

    /**
     * Test setting a specific algorithm.
     * @group Bag
     * @covers ::setAlgorithm
     * @covers ::removeAllPayloadManifests
     * @covers ::removePayloadManifest
     */
    public function testSetAlgorithm()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->addAlgorithm('sha1');
        $bag->addAlgorithm('SHA-224');
        $this->assertArrayEquals(['sha512', 'sha1', 'sha224'], $bag->getAlgorithms());
        $bag->setAlgorithm('md5');
        $this->assertArrayEquals(['md5'], $bag->getAlgorithms());
    }

    /**
     * Test that Windows reserved names are rejected as filenames.
     * @group Bag
     * @covers ::addFile
     * @covers ::reservedFilename
     */
    public function testUseReservedFilename()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("The filename requested is reserved on Windows OSes.");

        $bag = Bag::create($this->tmpdir);
        $bag->addFile(self::TEST_TEXT['filename'], 'data/some/directory/com1');
    }

    /**
     * Test getting a warning when validating an MD5 bag.
     * @group Bag
     * @covers ::validate
     * @covers \whikloj\BagItTools\AbstractManifest::loadFile
     * @covers \whikloj\BagItTools\AbstractManifest::validate
     */
    public function testWarningOnMd5()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertCount(0, $bag->getWarnings());
        $bag->setAlgorithm('md5');
        $bag->update();
        $newBag = Bag::load($this->tmpdir);
        $this->assertTrue($newBag->validate());
        $this->assertCount(1, $newBag->getWarnings());
    }

    /**
     * Test opening a non-existant compressed file.
     * @group Bag
     * @covers ::load
     * @covers ::getExtensions
     * @covers ::isCompressed
     */
    public function testNonExistantCompressed()
    {
        $path = '/my/directory.tar';

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Path {$path} does not exist, could not load Bag.");

        Bag::load($path);
    }


    /**
     * Test opening a tar gzip
     * @group Bag
     * @covers ::load
     * @covers ::isCompressed
     * @covers ::uncompressBag
     * @covers ::getExtensions
     * @covers ::untarBag
     */
    public function testUncompressTarGz()
    {
        $bag = Bag::load(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testtar.tgz');
        $this->assertTrue($bag->validate());
        $this->assertTrue($bag->hasAlgorithm('sha224'));
        $manifest = $bag->getPayloadManifests()['sha224'];
        foreach ($manifest->getHashes() as $path => $hash) {
            $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . BagUtils::baseInData($path));
        }
        $this->assertNotEquals(
            self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testtar.tgz',
            $bag->getBagRoot()
        );
    }

    /**
     * Test opening a tar bzip2.
     * @group Bag
     * @covers ::load
     * @covers ::isCompressed
     * @covers ::uncompressBag
     * @covers ::getExtensions
     * @covers ::untarBag
     */
    public function testUncompressTarBzip()
    {
        $bag = Bag::load(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testtar.tar.bz2');
        $this->assertTrue($bag->validate());
        $this->assertTrue($bag->hasAlgorithm('sha224'));
        $manifest = $bag->getPayloadManifests()['sha224'];
        foreach ($manifest->getHashes() as $path => $hash) {
            $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . BagUtils::baseInData($path));
        }
        $this->assertNotEquals(
            self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testtar.tar.bz2',
            $bag->getBagRoot()
        );
    }

    /**
     * Test opening a zip file.
     * @group Bag
     * @covers ::isCompressed
     * @covers ::uncompressBag
     * @covers ::getExtensions
     * @covers ::unzipBag
     */
    public function testUncompressZip()
    {
        $bag = Bag::load(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testzip.zip');
        $this->assertTrue($bag->validate());
        $this->assertTrue($bag->hasAlgorithm('sha224'));
        $manifest = $bag->getPayloadManifests()['sha224'];
        foreach ($manifest->getHashes() as $path => $hash) {
            $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . BagUtils::baseInData($path));
        }
        $this->assertNotEquals(
            self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'testzip.zip',
            $bag->getBagRoot()
        );
    }

    /**
     * Test generating a zip.
     *
     * TODO: Re-extract and compare against source bag.
     * @group Bag
     * @covers ::package
     * @covers ::makePackage
     * @covers ::makeZip
     */
    public function testZipBag()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $archivefile = $this->getTempName();
        $archivefile .= ".zip";
        $this->assertFileDoesNotExist($archivefile);
        $bag->package($archivefile);
        $this->assertFileExists($archivefile);

        $newbag = Bag::load($archivefile);
        $this->assertTrue($newbag->validate());

        $this->assertEquals(
            $bag->getPayloadManifests()['sha256']->getHashes(),
            $newbag->getPayloadManifests()['sha256']->getHashes()
        );
    }

    /**
     * Test generating a tar.
     *
     * TODO: Re-extract and compare against source bag.
     * @group Bag
     * @covers ::package
     * @covers ::makePackage
     * @covers ::makeTar
     */
    public function testTarBag()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $archivefile = $this->getTempName();
        $archivefile .= ".tar";
        $this->assertFileDoesNotExist($archivefile);
        $bag->package($archivefile);
        $this->assertFileExists($archivefile);

        $newbag = Bag::load($archivefile);
        $this->assertTrue($newbag->validate());

        $this->assertEquals(
            $bag->getPayloadManifests()['sha256']->getHashes(),
            $newbag->getPayloadManifests()['sha256']->getHashes()
        );
    }

    /**
     * Test generating a tar.
     *
     * TODO: Re-extract and compare against source bag.
     * @group Bag
     * @covers ::package
     * @covers ::makePackage
     * @covers ::makeTar
     */
    public function testTarGzBag()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $archivefile = $this->getTempName();
        $archivefile .= ".tar.gz";
        $this->assertFileDoesNotExist($archivefile);
        $bag->package($archivefile);
        $this->assertFileExists($archivefile);

        $newbag = Bag::load($archivefile);
        $this->assertTrue($newbag->validate());

        $this->assertEquals(
            $bag->getPayloadManifests()['sha256']->getHashes(),
            $newbag->getPayloadManifests()['sha256']->getHashes()
        );
    }

    /**
     * Test generating a tar.
     *
     * TODO: Re-extract and compare against source bag.
     * @group Bag
     * @covers ::package
     * @covers ::makePackage
     * @covers ::makeTar
     */
    public function testTarBzipBag()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $archivefile = $this->getTempName();
        $archivefile .= ".tar.bz2";
        $this->assertFileDoesNotExist($archivefile);
        $bag->package($archivefile);
        $this->assertFileExists($archivefile);

        $newbag = Bag::load($archivefile);
        $this->assertTrue($newbag->validate());

        $this->assertEquals(
            $bag->getPayloadManifests()['sha256']->getHashes(),
            $newbag->getPayloadManifests()['sha256']->getHashes()
        );
    }

    /**
     * Test an unknown/invalid package extension.
     *
     * @covers ::package
     */
    public function testInvalidPackage()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $archivefile = $this->getTempName();
        $archivefile .= ".rar";
        $this->assertFileDoesNotExist($archivefile);

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Unknown archive type, the file extension must be one of (tar, tgz, tar.gz, " .
            "tar.bz2, zip)");

        $bag->package($archivefile);
    }

    /**
     * Test an upgrade of a v0.97 bag
     * @group Bag
     * @covers ::upgrade()
     * @covers ::getVersionString
     */
    public function testUpdateV07()
    {
        # Spaces on both sides of colon allowed.
        $v097_regex = "/^.*?\b\s*:\s+\b.*?$/";
        # Only spaces after the colon allowed.
        $v10_regex = "/^.*?\b:\s+\b.*?$/";

        $this->tmpdir = $this->copyTestBag(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'Test097Bag');
        $bag = Bag::load($this->tmpdir);
        $this->assertEquals('0.97', $bag->getVersionString());
        $this->assertTrue($bag->validate());
        $fp = fopen($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt', 'r');
        while (!feof($fp)) {
            $line = fgets($fp);
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $this->assertEquals(1, preg_match($v097_regex, $line));
            $this->assertEquals(0, preg_match($v10_regex, $line));
        }
        fclose($fp);
        $bag->upgrade();
        $this->assertEquals('1.0', $bag->getVersionString());
        $this->assertTrue($bag->validate());
        $fp = fopen($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt', 'r');
        while (!feof($fp)) {
            $line = fgets($fp);
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $this->assertEquals(1, preg_match($v10_regex, $line));
        }
        fclose($fp);
    }

    /**
     * Only upgrade bags that were loaded.
     *
     * @group Bag
     * @covers ::upgrade
     */
    public function testUpgradeCreatedBag()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("You can only upgrade loaded bags.");

        $bag = Bag::create($this->tmpdir);
        $bag->upgrade();
    }

    /**
     * Only upgrade bags that are not already v1.0
     *
     * @group Bag
     * @covers ::upgrade
     */
    public function testUpgradeV1Bag()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Bag is already at version 1.0");

        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertEquals('1.0', $bag->getVersionString());
        $bag->upgrade();
    }

    /**
     * Only upgrade bags that are valid.
     *
     * @group Bag
     * @covers ::upgrade
     */
    public function testUpgradeInvalid()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("This bag is not valid, we cannot automatically upgrade it.");

        $this->tmpdir = $this->copyTestBag(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'Test097Bag');
        $bag = Bag::load($this->tmpdir);
        $this->assertEquals('0.97', $bag->getVersionString());
        touch($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'oops.txt');
        $this->assertFalse($bag->validate());
        $bag->upgrade();
    }

    /**
     * @group Bag
     * @covers ::__construct
     * @covers ::createNewBag
     * @covers ::update
     * @covers ::validate
     */
    public function testEmptyBagShouldValidate()
    {
        $this->assertFileDoesNotExist($this->tmpdir);
        $bag = Bag::create($this->tmpdir);
        $this->assertTrue($bag->validate());
    }

    /**
     * Test when too many lines in bagit.txt
     * @group Bag
     * @covers ::loadBagIt
     */
    public function testBagItTooManyLines()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $fp = fopen($this->tmpdir . DIRECTORY_SEPARATOR . 'bagit.txt', 'a');
        fwrite($fp, "This is more stuff\n");
        fclose($fp);
        $bag = Bag::load($this->tmpdir);
        $this->assertCount(1, $bag->getErrors());
    }

    /**
     * Test when first line does not validate.
     * @group Bag
     * @covers ::loadBagIt
     */
    public function testBagItVersionLineInvalid()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $fp = fopen($this->tmpdir . DIRECTORY_SEPARATOR . 'bagit.txt', 'w');
        fwrite($fp, "BagIt-Version: M.N\nTag-File-Character-Encoding: UTF-8\n");
        fclose($fp);
        $bag = Bag::load($this->tmpdir);
        $this->assertCount(1, $bag->getErrors());
    }

    /**
     * Test when second line does not validate.
     * @group Bag
     * @covers ::loadBagIt
     */
    public function testBagItEncodingLineError()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $fp = fopen($this->tmpdir . DIRECTORY_SEPARATOR . 'bagit.txt', 'w');
        fwrite($fp, "BagIt-Version: 1.0\nTag-File-Encoding: UTF-8\n");
        fclose($fp);
        $bag = Bag::load($this->tmpdir);
        $this->assertCount(1, $bag->getErrors());
    }

    /**
     * Test that we fail when a bagit.txt is not UTF-8 encoded.
     * @group Bag
     * @covers ::loadBagIt
     */
    public function testFailOnEncodedBagIt()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $fp = fopen($this->tmpdir . DIRECTORY_SEPARATOR . 'bagit.txt', 'w');
        $encoded_string = mb_convert_encoding("BagIt-Version: 1.0\nTag-File-Encoding: UTF-8\n", "byte4le");
        fwrite($fp, $encoded_string);
        fclose($fp);
        $bag = Bag::load($this->tmpdir);
        $this->assertCount(1, $bag->getErrors());
    }

    /**
     * Test that for a non-extended bag, trying to add bag-info tags throws an error.
     * @group Bag
     * @covers ::addBagInfoTag
     */
    public function testAddBagInfoWhenNotExtended()
    {
        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("This bag is not extended, you need '\$bag->setExtended(true);'");

        $bag = Bag::create($this->tmpdir);
        $bag->addBagInfoTag("Contact-Name", "Jared Whiklo");
    }

    /**
     * Test that using a path directory name gets us an absolute path and when that path exists we get an error.
     * @group Bag
     * @covers \whikloj\BagItTools\BagUtils::getAbsolute
     * @covers ::createNewBag
     */
    public function testRelativePathsExists()
    {
        // Make the directory
        mkdir($this->tmpdir);
        $fullpath = $this->tmpdir . DIRECTORY_SEPARATOR . "existing_bag";
        mkdir($fullpath);

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("New bag directory {$fullpath} exists");

        $curr = getcwd();
        chdir($this->tmpdir);
        try {
            Bag::create("existing_bag");
        } finally {
            chdir($curr);
        }
    }

    /**
     * Test that using a path directory name gets us an absolute path and if that path doesn't exist we create the bag.
     * @group Bag
     * @covers \whikloj\BagItTools\BagUtils::getAbsolute
     * @covers ::createNewBag
     */
    public function testRelativePathDoesntExist()
    {
        // Make the directory
        mkdir($this->tmpdir);
        $curr = getcwd();
        $full_path = $this->tmpdir . DIRECTORY_SEPARATOR . "existing_bag";
        chdir($this->tmpdir);
        $bag = Bag::create("existing_bag");
        $bagroot = $bag->getBagRoot();
        self::assertEquals($full_path, $bagroot);
        chdir($curr);
    }
}
