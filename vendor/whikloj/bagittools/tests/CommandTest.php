<?php

namespace whikloj\BagItTools\Test;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use whikloj\BagItTools\Commands\ValidateCommand;

/**
 * Class CommandTest
 * @package whikloj\BagItTools\Test
 * @coversDefaultClass \whikloj\BagItTools\Commands\ValidateCommand
 */
class CommandTest extends BagItTestFramework
{

    private $commandTester;


    public function setUp() : void
    {
        parent::setUp();
        $application = new Application();
        $application->add(new ValidateCommand());
        $command = $application->find('validate');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testValidateInvalidPath()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'DEVNULL';
        $this->commandTester->execute([
            'bag-path' => $path,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsStringWithoutNewlines("Path {$path} does not exist, cannot validate.", $output);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testValidBag()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . "TestBag";
        $this->commandTester->execute([
            'bag-path' => $path,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Bag is valid", $output);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testInvalidBag()
    {
        $this->tmpdir = self::prepareBasicTestBag();
        file_put_contents(
            $this->tmpdir . DIRECTORY_SEPARATOR . "bagit.txt",
            "BagIt-Version: M.N\nTag-File-Character-Encoding:\n"
        );
        $this->commandTester->execute([
            'bag-path' => $this->tmpdir,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Bag is NOT valid", $output);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testInvalidWithErrors()
    {
        $this->tmpdir = self::prepareBasicTestBag();
        unlink($this->tmpdir . DIRECTORY_SEPARATOR . "bagit.txt");
        $this->commandTester->execute([
            'bag-path' => $this->tmpdir,
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Bag is NOT valid", $output);
        $this->assertStringContainsString("[ERROR] Required file missing. -- file: bagit.txt", $output);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testInvalidWithWarnings()
    {
        $this->tmpdir = $this->copyTestBag(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'Test097Bag');
        $this->commandTester->execute([
            'bag-path' => $this->tmpdir,
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Bag is valid", $output);
        $this->assertStringContainsStringWithoutNewlines("[WARNING] This manifest is MD5, you should use " .
            "setAlgorithm('sha512') to upgrade. -- file: manifest-md5.txt", $output);
    }

    /**
     * @covers ::configure
     * @covers ::execute
     */
    public function testRelativeDirectoryToNoBag()
    {
        $this->commandTester->execute([
            'bag-path' => "subdirectory/to/bag",
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);
        $output = $this->commandTester->getDisplay();
        // Split this in two as we don't know what the actual root directory with be
        $this->assertStringContainsString("[ERROR] Path", $output);
        $this->assertStringContainsStringWithoutNewlines(
            "/subdirectory/to/bag does not exist, cannot validate.",
            $output
        );
    }
}
