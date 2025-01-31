<?php

namespace TheDevOpser\CastorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class InstallCastorCommand extends Command
{
    protected static $defaultName = 'castor:install';
    protected static $defaultDescription = 'Installe le fichier castor.php à la racine du projet';

    private string $projectDir;
    private string $bundleDir;
    private Filesystem $filesystem;

    public function __construct(
        KernelInterface $kernel,
        ?string $bundleDir = null
    ) {
        parent::__construct();

        $this->projectDir = $kernel->getProjectDir();
        $this->bundleDir = $bundleDir ?? dirname(__DIR__, 1) . '/Data';
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this->setDescription('Installe le fichier castor.php à la racine du projet');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->assertSourceFilesExist();
            $this->installCastorFiles($io);
            $this->installConfiguration($io);

            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue lors de l\'installation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function assertSourceFilesExist(): void
    {
        clearstatcache();

        $requiredFiles = ['castor.php', 'castorPersonal.php', 'castor.yaml'];

        foreach ($requiredFiles as $file) {
            $filePath = $this->bundleDir . '/' . $file;
            if (!$this->filesystem->exists($filePath)) {
                throw new \RuntimeException(sprintf('Le fichier source %s est introuvable dans %s.', $file, $this->bundleDir));
            }
        }
    }

    private function installCastorFiles(SymfonyStyle $io): void
    {
        $this->installMainCastorFile($io);
        $this->installPersonalCastorFile($io);
    }

    private function installMainCastorFile(SymfonyStyle $io): void
    {
        $targetFile = $this->projectDir . '/castor.php';
        $this->handleExistingFile($targetFile, $io);

        $this->filesystem->copy($this->bundleDir . '/castor.php', $targetFile, true);
        $io->success('Le fichier castor.php a été installé avec succès.');
    }

    private function installPersonalCastorFile(SymfonyStyle $io): void
    {
        $targetFile = $this->projectDir . '/castorPersonal.php';
        $this->createFileIfNotExists($targetFile, $io);
    }

    private function handleExistingFile(string $file, SymfonyStyle $io): void
    {
        $fileExists = $this->filesystem->exists($file);
        $shouldReplace = $fileExists && $io->confirm(
            sprintf('Le fichier %s existe déjà. Voulez-vous le remplacer ?', basename($file)),
            false
        );

        if ($fileExists && !$shouldReplace) {
            throw new \RuntimeException('Installation annulée par l\'utilisateur.');
        }
    }

    private function createFileIfNotExists(string $file, SymfonyStyle $io): void
    {
        $exists = $this->filesystem->exists($file);
        $message = $exists
            ? 'Le fichier %s existe déjà et a été conservé.'
            : 'Le fichier %s a été créé avec succès.';

        $exists || $this->filesystem->copy($this->bundleDir . '/castorPersonal.php', $file);
        $io->info(sprintf($message, basename($file)));
    }

    private function installConfiguration(SymfonyStyle $io): void
    {
        $configDir = $this->projectDir . '/config/packages';
        $targetFile = $configDir . '/castor.yaml';

        $this->filesystem->exists($targetFile)
            ? $io->info('Le fichier de configuration existe déjà et a été conservé.')
            : $this->createConfiguration($configDir, $targetFile, $io);
    }

    private function createConfiguration(string $configDir, string $targetFile, SymfonyStyle $io): void
    {
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile($targetFile, $this->prepareConfigurationContent());
        $io->success('Le fichier de configuration castor.yaml a été créé avec succès.');
    }

    private function prepareConfigurationContent(): string
    {
        $config = file_get_contents($this->bundleDir . '/castor.yaml');
        $projectName = basename($this->projectDir);
        $os = $this->detectOS();

        return strtr($config, [
            'nom: ~' => sprintf('nom: "%s"', $projectName),
            'os: ~' => sprintf('os: "%s"', $os)
        ]);
    }

    private function detectOS(): string
    {
        return file_exists('/etc/debian_version')
            ? 'debian'
            : (file_exists('/etc/redhat-release') ? 'rhel' : 'debian');
    }
}
