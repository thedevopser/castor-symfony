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

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct();

        $this->projectDir = $kernel->getProjectDir();
        $this->bundleDir = dirname(__DIR__ , 1) . '/Data';
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceFile = $this->bundleDir . '/castor.php';
        $targetFile = $this->projectDir . '/castor.php';

        try {
            // Vérifier si le fichier source existe
            if (!$this->filesystem->exists($sourceFile)) {
                throw new \RuntimeException('Le fichier source castor.php est introuvable dans le bundle.');
            }

            // Vérifier si le fichier cible existe déjà
            if ($this->filesystem->exists($targetFile)) {
                if (!$io->confirm('Le fichier castor.php existe déjà. Voulez-vous le remplacer ?', false)) {
                    $io->warning('Installation annulée.');
                    return Command::SUCCESS;
                }
            }

            // Copier le fichier
            $this->filesystem->copy($sourceFile, $targetFile, true);

            $io->success('Le fichier castor.php a été installé avec succès à la racine du projet.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue lors de l\'installation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}