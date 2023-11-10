<?php

namespace whikloj\BagItTools\Test;

use whikloj\BagItTools\Bag;

/**
 * Class ManifestTest
 * @package whikloj\BagItTools\Test
 * @coversDefaultClass \whikloj\BagItTools\AbstractManifest
 */
class ManifestTest extends BagItTestFramework
{

  /**
   * Test creation of default payload manifest with construction.
   * @group Manifest
   * @covers ::__construct
   * @covers \whikloj\BagItTools\PayloadManifest::__construct
   * @covers ::getFilename
   * @covers ::getAlgorithm
   */
    public function testCreateManifest()
    {
        $bag = Bag::create($this->tmpdir);
        $manifests = $bag->getPayloadManifests();
        $this->assertArrayHasKey('sha512', $manifests);
        $manifest = $manifests['sha512'];
        $this->assertEquals("manifest-sha512.txt", $manifest->getFilename());
        $this->assertEquals('sha512', $manifest->getAlgorithm());
    }

    /**
     * Test that manifests files are appropriately filled out.
     * @group Manifest
     * @covers ::__construct
     * @covers \whikloj\BagItTools\TagManifest::__construct
     * @covers \whikloj\BagItTools\PayloadManifest::__construct
     * @covers ::update
     * @covers \whikloj\BagItTools\TagManifest::update
     * @covers \whikloj\BagItTools\PayloadManifest::update
     */
    public function testCheckManifests()
    {
        $bag = Bag::create($this->tmpdir);
        $test_files = [
            'baginfo' => $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt',
            'payload' => $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha256.txt',
            'tag' => $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha256.txt',
        ];
        $bag->setExtended(true);
        $bag->addBagInfoTag('Contact-name', 'Jared Whiklo');
        $bag->setAlgorithm('sha256');
        $bag->createFile("This is some sample text", 'some/directory/file.txt');

        foreach ($test_files as $file) {
            $this->assertFileDoesNotExist($file);
        }

        $bag->update();

        foreach ($test_files as $file) {
            $this->assertFileExists($file);
        }

        $fp = fopen($test_files['payload'], 'rb');
        $line = self::getLine($fp, $bag->getFileEncoding());
        $expected_filepath = 'data/some/directory/file.txt';
        $constraint1 = self::stringEndsWith($expected_filepath);
        $this->assertTrue($constraint1->evaluate($line, '', true));
        fclose($fp);

        $fp = fopen($test_files['tag'], 'rb');
        $constraints = self::logicalOr(
            self::stringEndsWith('bagit.txt'),
            self::stringEndsWith('bag-info.txt'),
            self::stringEndsWith('manifest-sha256.txt')
        );
        while (feof($fp)) {
            $line = $this->getLine($fp, $bag->getFileEncoding());
            $this->assertTrue($constraints->evaluate($line, '', true));
        }
        fclose($fp);
    }

    /**
     * @group Manifest
     * @covers ::validate
     * @covers ::validatePath
     * @covers ::calculateHash
     * @covers ::loadFile
     * @covers ::normalizePath
     * @covers ::matchNormalizedList
     * @covers ::addToNormalizedList
     * @covers \whikloj\BagItTools\TagManifest::validate
     * @covers \whikloj\BagItTools\PayloadManifest::validate
     */
    public function testValidateManifests()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());

        file_put_contents($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'oops.txt', "Slip up");
        $this->assertFalse($bag->validate());
    }

    /**
     * Test that a relative path in a manifest file is a warning.
     * @group Manifest
     * @covers ::loadFile
     * @covers ::addLoadWarning
     * @covers ::normalizePath
     */
    public function testRelativeManifestPaths()
    {
        $this->prepareManifest('manifest-with-relative-paths-sha256.txt');
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(1, $bag->getWarnings());
    }

    /**
     * Test that a duplicate path in a manifest file is an error.
     * @group Manifest
     * @covers ::loadFile
     * @covers ::addLoadError
     * @covers ::normalizePath
     */
    public function testDuplicateManifestPaths()
    {
        $this->prepareManifest('manifest-with-duplicate-lines-sha256.txt');
        $bag = Bag::load($this->tmpdir);
        $this->assertFalse($bag->validate());
        $this->assertCount(1, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * Test that a duplicate path with different case in a manifest file is a warning.
     * @group Manifest
     * @covers ::loadFile
     * @covers ::addLoadWarning
     * @covers ::normalizePath
     */
    public function testDuplicateCaseInsensitiveManifestPaths()
    {
        $this->prepareManifest('manifest-with-case-insensitive-duplicates-sha256.txt');
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(1, $bag->getWarnings());
    }

    /**
     * Utility to set a bag with a specific manifest file.
     * @param string $manifest_filename
     *   File name from tests/resources/manifests to put in bag.
     */
    private function prepareManifest($manifest_filename)
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        file_put_contents(
            $this->tmpdir . DIRECTORY_SEPARATOR . 'manifest-sha256.txt',
            file_get_contents(self::TEST_MANIFEST_DIR . DIRECTORY_SEPARATOR .
                $manifest_filename)
        );
    }

    /**
     * Get a line from a Bagit file using the provided encoding.
     *
     * @param resource $fp
     *   The file resource
     * @param string $file_encoding
     *   The file encoding
     * @return string
     *   The line from the file decoded to UTF-8.
     */
    private static function getLine($fp, $file_encoding)
    {
        $line = fgets($fp);
        $line = mb_convert_encoding($line, 'UTF-8', $file_encoding);
        return trim($line);
    }
}
