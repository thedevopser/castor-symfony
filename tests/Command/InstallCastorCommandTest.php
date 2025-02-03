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
    private $configDir;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir() . '/castor_test';
        $this->bundleDir = dirname(__DIR__) . '/Data';
        $this->configDir = $this->projectDir . '/config/packages';

        $this->kernel->method('getProjectDir')
            ->willReturn($this->projectDir);

        $this->filesystem->mkdir($this->projectDir);
        $this->filesystem->mkdir($this->bundleDir);

        $castorContent = file_get_contents(dirname(__DIR__, 2) . '/src/Data/castor.php');
        $this->filesystem->dumpFile($this->bundleDir . '/castor.php', $castorContent);
        $this->filesystem->dumpFile($this->bundleDir . '/castorPersonal.php', '<?php // Test personal file');
        $this->filesystem->dumpFile($this->bundleDir . '/castor.yaml', "castor:\n    vhost:\n        url: \"test\"\n        nom: ~\n        server: \"apache2\"\n        os: ~\n        ssl:\n            enabled: false\n            certificate: ~\n            certificate_key: ~");
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
        $this->filesystem->remove($this->bundleDir);
    }

    public function testSuccessfulInstallation(): void
    {
        $command = new InstallCastorCommand($this->kernel);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertFileExists($this->projectDir . '/castor.php');
        $this->assertFileExists($this->projectDir . '/castorPersonal.php');
        $this->assertFileExists($this->configDir . '/castor.yaml');

        $configContent = file_get_contents($this->configDir . '/castor.yaml');
        $this->assertStringContainsString('nom: null', $configContent);
        $this->assertMatchesRegularExpression('/os: null/', $configContent);
    }

    public function testExistingFilesWithNoOverwrite(): void
    {
        $command = new InstallCastorCommand($this->kernel);
        $commandTester = new CommandTester($command);

        $this->filesystem->dumpFile($this->projectDir . '/castor.php', '<?php // Existing file');

        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('annulée', $commandTester->getDisplay());
        $this->assertEquals('<?php // Existing file', file_get_contents($this->projectDir . '/castor.php'));
    }

    public function testExistingFilesWithOverwrite(): void
    {
        $command = new InstallCastorCommand($this->kernel);
        $commandTester = new CommandTester($command);

        $this->filesystem->dumpFile($this->projectDir . '/castor.php', '<?php // Existing file');
        $castorContent = file_get_contents($this->bundleDir . '/castor.php');

        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('succès', $commandTester->getDisplay());
        $this->assertEquals($castorContent, file_get_contents($this->projectDir . '/castor.php'));
    }

    public function testMissingSourceFiles(): void
    {
        $this->filesystem->remove($this->bundleDir . '/castor.php');
        clearstatcache();

        $command = new InstallCastorCommand($this->kernel, $this->bundleDir);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals(1, $commandTester->getStatusCode());

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        $this->assertStringContainsString('[ERROR]', $output);
        $this->assertStringContainsString('castor.php est introuvable', $output);
        $this->assertStringContainsString($this->bundleDir, $output);
    }
}
