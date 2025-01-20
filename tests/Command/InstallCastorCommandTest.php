<?php

namespace TheDevOpser\CastorBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use TheDevOpser\CastorBundle\Command\InstallCastorCommand;

class InstallCastorCommandTest extends TestCase
{
    private $kernel;
    private $filesystem;
    private $projectDir;
    private $bundleDir;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir() . '/castor_test';
        $this->bundleDir = dirname(__DIR__, 2) . '/src/Data';

        $this->kernel->method('getProjectDir')
            ->willReturn($this->projectDir);

        $this->filesystem->mkdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testExecute(): void
    {
        $command = new InstallCastorCommand($this->kernel);
        $commandTester = new CommandTester($command);

        $this->filesystem->dumpFile($this->bundleDir . '/castor.php', '<?php // Test file');

        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertFileExists($this->projectDir . '/castor.php');
        $this->assertStringContainsString('succès', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingFile(): void
    {
        $command = new InstallCastorCommand($this->kernel);
        $commandTester = new CommandTester($command);

        $this->filesystem->dumpFile($this->bundleDir . '/castor.php', '<?php // Test file');

        $this->filesystem->dumpFile($this->projectDir . '/castor.php', '<?php // Existing file');

        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('annulée', $commandTester->getDisplay());
    }
}