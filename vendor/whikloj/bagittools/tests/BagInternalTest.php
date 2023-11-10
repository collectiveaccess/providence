<?php

namespace whikloj\BagItTools\Test;

use whikloj\BagItTools\Bag;

/**
 * Tests private or protected functions.
 * @package whikloj\BagItTools\Test
 */
class BagInternalTest extends BagItTestFramework
{

  /**
   * Test makeRelative
   * @group BagInternal
   * @covers \whikloj\BagItTools\Bag::makeRelative
   */
    public function testMakeRelativePlain()
    {
        $bag = Bag::create($this->tmpdir);
        $baseDir = $bag->getBagRoot();

        $valid_paths = [
        'data/image/someimage.jpg' => 'data/image/someimage.jpg',
        'data/picture.txt' => 'data/picture.txt',
        'data/images/subimages/../picture.jpg' => 'data/images/picture.jpg',
        'data/one/../two/../three/.././/eggs.txt' => 'data/eggs.txt',
        'somefile.txt' => 'somefile.txt',
        '/var/lib/somewhere' => 'var/lib/somewhere',
        ];

        $invalid_paths = [
        'data/../../../images/places/image.jpg',
        'data/one/..//./two/./../../three/.././../eggs.txt',
        ];

        foreach ($valid_paths as $path => $expected) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $path;
            $relative = $bag->makeRelative($fullpath);
            $this->assertEquals($expected, $relative);
        }

        foreach ($invalid_paths as $path) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $path;
            $relative = $bag->makeRelative($fullpath);
            $this->assertEquals('', $relative);
        }
    }

    /**
     * @covers \whikloj\BagItTools\Bag::makeAbsolute
     */
    public function testMakeAbsolutePlain()
    {
        $bag = Bag::create($this->tmpdir);
        $baseDir = $bag->getBagRoot();

        $valid_paths = [
            'data/image/someimage.jpg' => 'data/image/someimage.jpg',
            'data/picture.txt' => 'data/picture.txt',
            'data/images/subimages/../picture.jpg' => 'data/images/picture.jpg',
            'data/one/../two/../three/.././/eggs.txt' => 'data/eggs.txt',
            'somefile.txt' => 'somefile.txt',
            '/var/lib/somewhere' => 'var/lib/somewhere',
        ];

        $invalid_paths = [
            'data/../../../images/places/image.jpg' => 'image.jpg',
            'data/one/..//./two/./../../three/.././../eggs.txt' => 'eggs.txt',
        ];

        foreach ($valid_paths as $path => $expected) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $expected;
            $absolute = $bag->makeAbsolute($path);
            $this->assertEquals($fullpath, $absolute);
        }

        // There are no invalid paths as makeAbsolute only promises to prepend bagRoot + DIRECTORY_SEPARATOR to the
        // incoming value once normalized.
        foreach ($invalid_paths as $path => $expected) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $expected;
            $absolute = $bag->makeAbsolute($fullpath);
            $this->assertEquals($fullpath, $absolute);
        }
    }

  /**
   * Test pathInBagData
   * @group BagInternal
   * @covers \whikloj\BagItTools\Bag::pathInBagData
   */
    public function testPathInBagData()
    {
        $bag = Bag::create($this->tmpdir);

        $valid_paths = [
        'data/image/someimage.jpg',
        'data/picture.txt',
        'data/images/subimages/../picture.jpg',
        'data/one/../two/../three/.././/eggs.txt',
        ];
        $invalid_paths = [
        'data/../../../images/places/image.jpg',
        'somefile.txt',
        'Whatever',
        'data/one/../two/../three/.././../eggs.txt',
        ];

        foreach ($valid_paths as $path) {
            $relative = $bag->pathInBagData($path);
            $this->assertTrue($relative);
        }

        foreach ($invalid_paths as $path) {
            $relative = $bag->pathInBagData($path);
            $this->assertFalse($relative);
        }
    }

    /**
     * Test the BagInfo text wrapping function.
     * @group BagInternal
     * @covers \whikloj\BagItTools\Bag::wrapBagInfoText
     * @covers \whikloj\BagItTools\Bag::wrapAtLength
     * @throws \ReflectionException
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     */
    public function testWrapBagInfo()
    {
        $test_matrix = [
            "Source-Organization: Organization transferring the content." => [
                "Source-Organization: Organization transferring the content.",
            ],
            "Contact-Name: Person at the source organization who is responsible for the content transfer." => [
                "Contact-Name: Person at the source organization who is responsible for the",
                "  content transfer.",
            ],
            "Bag-Size: The size or approximate size of the bag being transferred, followed by an abbreviation such" .
            " as MB (megabytes), GB (gigabytes), or TB (terabytes): for example, 42600 MB, 42.6 GB, or .043 TB." .
            " Compared to Payload-Oxum (described next), Bag-Size is intended for human consumption. This metadata" .
            " element SHOULD NOT be repeated." => [
                "Bag-Size: The size or approximate size of the bag being transferred, followed",
                "  by an abbreviation such as MB (megabytes), GB (gigabytes), or TB (terabytes):",
                "  for example, 42600 MB, 42.6 GB, or .043 TB. Compared to Payload-Oxum",
                "  (described next), Bag-Size is intended for human consumption. This metadata",
                "  element SHOULD NOT be repeated.",
            ],
        ];

        $bag = Bag::create($this->tmpdir);
        $methodCall = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'wrapBagInfoText');

        foreach ($test_matrix as $string => $expected) {
            $output = $methodCall->invokeArgs($bag, [$string]);
            $this->assertEquals($expected, $output);
        }
    }

    /**
     * Test internal version comparison.
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::compareVersion
     * @throws \ReflectionException
     * @throws \whikloj\BagItTools\Exceptions\BagItException
     */
    public function testVersionCompare()
    {
        $bag = Bag::create($this->tmpdir);
        $method = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'compareVersion');

        // Current version is 1.0
        $this->assertEquals(-1, $method->invokeArgs($bag, ['0.97']));
        $this->assertEquals(0, $method->invokeArgs($bag, ['1.0']));
        $this->assertEquals(1, $method->invokeArgs($bag, ['1.1']));
    }


    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::resetErrorsAndWarnings
     */
    public function testResetErrorsAndWarnings()
    {
        $this->tmpdir = $this->copyTestBag(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'Test097Bag');
        $bag = Bag::load($this->tmpdir);
        $this->assertEquals('0.97', $bag->getVersionString());
        touch($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'oops.txt');
        $this->assertFalse($bag->validate());
        $this->assertCount(1, $bag->getErrors());
        $this->assertCount(2, $bag->getWarnings());

        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'resetErrorsAndWarnings'
        );
        $methodCall->invokeArgs($bag, []);

        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::addBagError
     */
    public function testAddBagError()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'addBagError'
        );
        $methodCall->invokeArgs($bag, ['some_file', 'some_error']);
        $this->assertCount(1, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::addBagWarning
     */
    public function testAddBagWarning()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'addBagWarning'
        );
        $methodCall->invokeArgs($bag, ['some_file', 'some_warning']);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(1, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::arrayKeyExistsNoCase
     */
    public function testArrayKeyExistsNoCase()
    {
        $test_array = [
            ['name' => 'BOB'],
        ];
        $bag = Bag::create($this->tmpdir);
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'arrayKeyExistsNoCase'
        );
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['BOB', 'name', $test_array]
        ));
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['bob', 'name', $test_array]
        ));
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['boB', 'name', $test_array]
        ));
        $this->assertFalse($methodCall->invokeArgs(
            $bag,
            ['BOO', 'name', $test_array]
        ));
    }
}
