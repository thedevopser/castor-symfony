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

    private $projectDir;
    private $bundleDir;
    private $filesystem;

    public function __construct(KernelInterface $kernel, ?string $bundleDir = null)
    {
        parent::__construct();

        $this->projectDir = $kernel->getProjectDir();
        $this->bundleDir = $bundleDir ?? dirname(__DIR__, 1) . '/Data';
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->assertSourceFilesExist();
            $this->installCastorFiles($io);

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

        $requiredFiles = ['castor.php', 'castorPersonal.php'];

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
}
