<?php

namespace whikloj\BagItTools\Test;

use whikloj\BagItTools\Bag;
use whikloj\BagItTools\Exceptions\BagItException;

/**
 * Test of various classes for extended bag functions..
 * @package whikloj\BagItTools\Test
 * @coversDefaultClass \whikloj\BagItTools\Bag
 */
class ExtendedBagTest extends BagItTestFramework
{

    /**
     * @group Extended
     * @covers ::validate
     * @covers ::getErrors
     * @covers \whikloj\BagItTools\AbstractManifest::getErrors
     * @covers ::getWarnings
     * @covers \whikloj\BagItTools\AbstractManifest::getWarnings
     */
    public function testValidateExtendedBag()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * Test a non-extended bag has no tag manifest.
     * @group Extended
     * @covers \whikloj\BagItTools\AbstractManifest::calculateHash
     * @covers \whikloj\BagItTools\TagManifest::update
     * @covers \whikloj\BagItTools\AbstractManifest::update
     * @covers \whikloj\BagItTools\AbstractManifest::writeToDisk
     */
    public function testNoTagManifest()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertFalse($bag->isExtended());
        $payloads = array_keys($bag->getPayloadManifests());
        $hash = reset($payloads);
        $manifests = $bag->getTagManifests();
        $this->assertNull($manifests);

        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . "manifest-{$hash}.txt");
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . "tagmanifest-{$hash}.txt");
        // Make an extended bag
        $bag->setExtended(true);
        // Tag manifest not written.
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . "tagmanifest-{$hash}.txt");

        $bag->update();
        // Now it exists.
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . "tagmanifest-{$hash}.txt");
        $manifests = $bag->getTagManifests();
        $this->assertNotEmpty($manifests);
        $this->assertArrayHasKey($hash, $manifests);
    }

    /**
     * Test loading an extended bag properly and adding payload-oxum
     * @group Extended
     * @covers ::calculateTotalFileSizeAndAmountOfFiles
     * @covers ::convertToHumanReadable
     * @covers ::loadBagInfo
     * @covers ::updateBagInfo
     * @covers ::updateCalculateBagInfoFields
     * @covers ::update
     * @covers \whikloj\BagItTools\AbstractManifest::loadFile
     */
    public function testLoadExtendedBag()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->isExtended());
        $payloads = $bag->getPayloadManifests();
        $tags = $bag->getTagManifests();
        $this->assertCount(1, $payloads);
        $this->assertCount(1, $tags);
        $this->assertArrayHasKey('sha1', $payloads);
        $this->assertArrayHasKey('sha1', $tags);
        $this->assertCount(2, $payloads['sha1']->getHashes());
        $this->assertCount(4, $tags['sha1']->getHashes());
        $this->assertArrayHasKey('bagit.txt', $tags['sha1']->getHashes());
        $this->assertArrayHasKey('bag-info.txt', $tags['sha1']->getHashes());
        $this->assertArrayHasKey('manifest-sha1.txt', $tags['sha1']->getHashes());
        $this->assertArrayHasKey('alt_tags/random_tags.txt', $tags['sha1']->getHashes());

        $this->assertTrue($bag->hasBagInfoTag('contact-phone'));

        $this->assertFalse($bag->hasBagInfoTag('payload-oxum'));
        $this->assertFalse($bag->hasBagInfoTag('bag-size'));
        $bag->update();
        $this->assertTrue($bag->hasBagInfoTag('payload-oxum'));
        $this->assertTrue($bag ->hasBagInfoTag('bagging-date'));
        $oxums = $bag->getBagInfoByTag('payload-oxum');
        $this->assertCount(1, $oxums);
        $this->assertEquals('408183.2', $oxums[0]);
        $bagSize = $bag->getBagInfoByTag('bag-size');
        $this->assertCount(1, $bagSize);
        $this->assertEquals('398.62 KB', $bagSize[0]);
    }

    /**
     * Test getting bag info by key
     * @group Extended
     * @covers ::hasBagInfoTag
     * @covers ::getBagInfoByTag
     * @covers ::bagInfoTagExists
     */
    public function testGetBagInfoByKey()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->isExtended());
        $this->assertCount(7, $bag->getBagInfoData());
        $this->assertTrue($bag->hasBagInfoTag('CONTACT-NAME'));
        $contacts = $bag->getBagInfoByTag('CONTACT-name');
        $this->assertCount(3, $contacts);
        $this->assertTrue(in_array('Cris Carter', $contacts));
        $this->assertTrue(in_array('Randy Moss', $contacts));
        $this->assertTrue(in_array('Robert Smith', $contacts));
        $this->assertFalse(in_array('cris carter', $contacts));
    }

    /**
     * Test removing all entries for a tag.
     * @group Extended
     * @covers ::hasBagInfoTag
     * @covers ::getBagInfoByTag
     * @covers ::removeBagInfoTag
     * @covers ::bagInfoTagExists
     */
    public function testRemoveBagInfoByTag()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->isExtended());
        $this->assertCount(7, $bag->getBagInfoData());
        $this->assertTrue($bag->hasBagInfoTag('CONTACT-NAME'));
        $this->assertCount(3, $bag->getBagInfoByTag('CONTACT-name'));
        $bag->removeBagInfoTag('Contact-NAME');
        $this->assertFalse($bag->hasBagInfoTag('CONTACT-NAME'));
        $this->assertCount(0, $bag->getBagInfoByTag('ConTAct-NamE'));
    }

    /**
     * Test removing all entries for a tag.
     * @group Extended
     * @covers ::hasBagInfoTag
     * @covers ::getBagInfoByTag
     * @covers ::removeBagInfoTagIndex
     * @covers ::bagInfoTagExists
     */
    public function testRemoveBagInfoByTagIndex()
    {
        $original = [
            'Robert Smith',
            'Randy Moss',
            'Cris Carter',
        ];
        $final = [
            'Robert Smith',
            'Cris Carter',
        ];
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertTrue($bag->isExtended());
        $this->assertCount(7, $bag->getBagInfoData());
        $this->assertTrue($bag->hasBagInfoTag('CONTACT-NAME'));
        $this->assertCount(3, $bag->getBagInfoByTag('CONTACT-name'));
        $this->assertArrayEquals($original, $bag->getBagInfoByTag('CONTACT-name'));
        $bag->removeBagInfoTagIndex('Contact-NAME', 1);
        $this->assertTrue($bag->hasBagInfoTag('CONTACT-NAME'));
        $this->assertCount(2, $bag->getBagInfoByTag('ConTAct-NamE'));
        $this->assertArrayEquals($final, $bag->getBagInfoByTag('contact-name'));
    }

    /**
     * Test getting, adding and removing valid algorithms using common names.
     *
     * @group Bag
     * @covers ::addAlgorithm
     * @covers ::removeTagManifest
     * @covers ::clearTagManifests
     * @covers ::removeAlgorithm
     * @covers ::getAlgorithms
     * @covers ::updateTagManifests
     * @covers ::updatePayloadManifests
     * @covers ::ensureTagManifests
     */
    public function testGetHashesCommon()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertArrayEquals(['sha512'], $bag->getAlgorithms());
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');

        $bag->update();
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');

        $bag->setExtended(true);
        $bag->update();
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');

        // Set one
        $bag->addAlgorithm('SHA1');
        // Remove it
        $bag->removeAlgorithm('SHA1');

        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');

        // Set again differently
        $bag->addAlgorithm('SHA-1');
        // Set a third
        $bag->addAlgorithm('SHA-224');

        $bag->update();

        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');

        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha224.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');

        // Remove one
        $bag->removeAlgorithm('SHA-512');

        $bag->update();
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');

        $this->assertArrayEquals(['sha224', 'sha1'], $bag->getAlgorithms());
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');

        $bag->setExtended(false);
        $bag->update();
        // tag manifests are gone.
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');
        // but payload remain
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha224.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
    }

    /**
     * Test setting a specific algorithm.
     * @group Bag
     * @covers ::setAlgorithm
     * @covers ::removeAllPayloadManifests
     * @covers ::removePayloadManifest
     * @covers ::removeAllTagManifests
     * @covers ::removeTagManifest
     */
    public function testSetAlgorithm()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->setExtended(true);
        $bag->addAlgorithm('sha1');
        $bag->addAlgorithm('SHA-224');
        $this->assertArrayEquals(['sha512', 'sha1', 'sha224'], $bag->getAlgorithms());
        $bag->update();

        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha224.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');

        $bag->setAlgorithm('md5');
        $this->assertArrayEquals(['md5'], $bag->getAlgorithms());
        // Still the old manifests exist
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha224.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');
        // And the new one doesn't
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-md5.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-md5.txt');

        $bag->update();

        // Now the old manifests don't exist
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha1.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha224.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha1.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha512.txt');
        $this->assertFileDoesNotExist($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-sha224.txt');
        // And the new one does
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-md5.txt');
        $this->assertFileExists($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'tagmanifest-md5.txt');
    }

    /**
     * Test setting a bag info tag.
     * @group Extended
     * @covers ::addBagInfoTag
     * @covers ::bagInfoTagExists
     */
    public function testSetBagInfoElement()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->setExtended(true);
        $bag->addBagInfoTag('Contact-NAME', 'Monty Hall');
        $this->assertCount(1, $bag->getBagInfoData());
        $this->assertTrue($bag->hasBagInfoTag('contact-name'));
        $tags = $bag->getBagInfoByTag('CONTACT-NAME');
        $this->assertArrayEquals(['Monty Hall'], $tags);
        $baginfo = $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt';
        $this->assertFileDoesNotExist($baginfo);
        $bag->update();
        $this->assertFileExists($baginfo);
        $expected = 'Contact-NAME: Monty Hall' . PHP_EOL . 'Payload-Oxum: 0.0' . PHP_EOL . 'Bag-Size: 0 B' .
            PHP_EOL . 'Bagging-Date: ' . date('Y-m-d', time()) . PHP_EOL;
        $this->assertEquals($expected, file_get_contents($baginfo));

        $bag->addBagInfoTag('contact-nAME', 'Bob Barker');
        $tags = $bag->getBagInfoByTag('CONTACT-NAME');
        $this->assertArrayEquals(['Monty Hall', 'Bob Barker'], $tags);

        $bag->update();
        $expected = 'Contact-NAME: Monty Hall' . PHP_EOL . 'contact-nAME: Bob Barker' . PHP_EOL . 'Payload-Oxum: 0.0' .
            PHP_EOL . 'Bag-Size: 0 B' . PHP_EOL . 'Bagging-Date: ' . date('Y-m-d', time()) . PHP_EOL;
        $this->assertEquals($expected, file_get_contents($baginfo));
    }

    /**
     * Test the exception when trying to set a generated field.
     * @group Extended
     * @covers ::addBagInfoTag
     * @covers ::setExtended
     */
    public function testSetGeneratedField()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->setExtended(true);
        $bag->addBagInfoTag('Source-organization', 'Planet Earth');
        // Doesn't match due to underscore instead of hyphen.
        $bag->addBagInfoTag('PAYLOAD_OXUM', '123456.12');

        $this->expectException(BagItException::class);
        $this->expectExceptionMessage("Field payload-oxum is auto-generated and cannot be manually set.");

        // Now we explode.
        $bag->addBagInfoTag('payload-oxum', '123');
    }

    /**
     * Test that for a v1.0 bag you CAN'T have spaces at the start or end of a tag.
     * @group Extended
     * @covers ::loadBagInfo
     * @covers ::compareVersion
     */
    public function testInvalidBagInfov1()
    {
        $bag = Bag::create($this->tmpdir);
        copy(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'bag-infos' . DIRECTORY_SEPARATOR .
            'invalid-leading-spaces.txt', $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt');
        touch($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-md5.txt');
        $testbag = Bag::load($this->tmpdir);
        $this->assertCount(2, $testbag->getErrors());
    }

    /**
     * Test that for a v0.97 bag you CAN have spaces at the start or end of a tag.
     * @group Extended
     * @covers ::loadBagInfo
     * @covers ::compareVersion
     */
    public function testInvalidBagInfov097()
    {
        $bag = Bag::create($this->tmpdir);
        copy(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'bag-infos' . DIRECTORY_SEPARATOR .
            'invalid-leading-spaces.txt', $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt');
        file_put_contents(
            $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bagit.txt',
            "BagIt-Version: 0.97" . PHP_EOL . "Tag-File-Character-Encoding: UTF-8" . PHP_EOL
        );
        touch($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-md5.txt');
        $testbag = Bag::load($this->tmpdir);
        $this->assertCount(0, $testbag->getErrors());
    }

    /**
     * Test getting manifests for basic bag.
     * @group Extended
     * @covers ::load
     * @covers ::getPayloadManifests
     * @covers ::getTagManifests
     */
    public function testGetManifests()
    {
        $this->tmpdir = $this->prepareBasicTestBag();
        $bag = Bag::load($this->tmpdir);
        $payloads = $bag->getPayloadManifests();
        $this->assertTrue(is_array($payloads));
        $this->assertCount(1, $payloads);
        $tags = $bag->getTagManifests();
        $this->assertNull($tags);
    }

    /**
     * Test getting manifests for extended bag.
     * @group Extended
     * @covers ::load
     * @covers ::getPayloadManifests
     * @covers ::getTagManifests
     */
    public function testGetManifestsExtended()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $payloads = $bag->getPayloadManifests();
        $this->assertTrue(is_array($payloads));
        $this->assertCount(1, $payloads);
        $tags = $bag->getTagManifests();
        $this->assertTrue(is_array($tags));
        $this->assertCount(1, $tags);
    }

    /**
     * Test payload-oxum calculation is only done once independent of how
     * many hash algorithm are used.
     * @group Extended
     * @covers ::calculateTotalFileSizeAndAmountOfFiles
     * @covers ::getBagInfoByTag
     * @covers ::update
     */
    public function testOxumCalculationForManyHashAlogrithm()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);
        $this->assertTrue($bag->validate());
        $this->assertTrue($bag->isExtended());
        $bag->addAlgorithm('SHA-224');
        $bag->update();

        $oxums = $bag->getBagInfoByTag('payload-oxum');
        $this->assertCount(1, $oxums);
        $this->assertEquals('408183.2', $oxums[0]);
    }

    /**
     * Test setting of bag-size tag.
     * @group Extended
     * @covers ::calculateTotalFileSizeAndAmountOfFiles
     * @covers ::convertToHumanReadable
     * @covers ::getBagInfoByTag
     * @covers ::update
     */
    public function testCalculatedBagSize()
    {
        $this->tmpdir = $this->prepareExtendedTestBag();
        $bag = Bag::load($this->tmpdir);

        $bag->addFile(self::TEST_IMAGE['filename'], 'data/image1.jpg');
        $bag->update();
        $bagsize = $bag->getBagInfoByTag('bag-size');
        $this->assertCount(1, $bagsize);
        $this->assertEquals('787.53 KB', $bagsize[0]);
        $oxums = $bag->getBagInfoByTag('payload-oxum');
        $this->assertCount(1, $oxums);
        $this->assertEquals('806429.3', $oxums[0]);

        $bag->addFile(self::TEST_IMAGE['filename'], 'data/subdir/image1.jpg');
        $bag->addFile(self::TEST_IMAGE['filename'], 'data/subdir/image2.jpg');
        $bag->update();
        $bagsize = $bag->getBagInfoByTag('bag-size');
        $this->assertCount(1, $bagsize);
        $this->assertEquals('1.53 MB', $bagsize[0]);
        $oxums = $bag->getBagInfoByTag('payload-oxum');
        $this->assertCount(1, $oxums);
        $this->assertEquals('1602921.5', $oxums[0]);
    }

    /**
     * Test that long tag lines might contain colons and should still validate if
     * @group Extended
     * @covers ::loadBagInfo
     * @covers ::trimSpacesOnly
     */
    public function testLongBagInfoLinesWrap()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->setExtended(true);

        $bag->addBagInfoTag('Title', 'A really long long long long long long long long long long long ' .
            'title with a colon : between and more information are on the way');
        $bag->update();

        $testbag = Bag::load($this->tmpdir);
        $this->assertTrue($testbag->validate());
        $this->assertEquals('A really long long long long long long long long long long long title with a ' .
            'colon : between and more information are on the way', $testbag->getBagInfoByTag('Title')[0]);
    }

    /**
     * Test loading long lines with internal newlines from a bag-info.txt
     * @group Extended
     * @covers ::loadBagInfo
     */
    public function testLoadWrappedLines()
    {
        $bag = Bag::create($this->tmpdir);
        copy(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'bag-infos' . DIRECTORY_SEPARATOR .
            'long-lines-and-line-returns.txt', $bag->getBagRoot() . DIRECTORY_SEPARATOR . 'bag-info.txt');
        touch($bag->getBagRoot() . DIRECTORY_SEPARATOR . 'manifest-sha512.txt');

        // Load tag values as they exist on disk. Long lines (over 70 characters) get the newline removed
        $testbag = Bag::load($this->tmpdir);
        $this->assertCount(0, $testbag->getErrors());
        $this->assertEquals("This is some crazy information about a new way of searching for : the stuff. " .
            "Why do this?\nBecause we can.", $testbag->getBagInfoByTag('External-Description')[0]);
        $testbag->update();

        // We wrote the bag info again, so now it is stripped of
        $testbag2 = Bag::load($this->tmpdir);
        $this->assertCount(0, $testbag2->getErrors());
        $this->assertEquals("This is some crazy information about a new way of searching for : the stuff. " .
            "Why do this? Because we can.", $testbag2->getBagInfoByTag('External-Description')[0]);
    }
}
