<?php

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\capture;
use function Castor\run;
use function Castor\import;

import(__DIR__ . '/castorPersonal.php');

/**
 * Installation et Initialisation
 */
#[AsTask(description: 'Initialisation du projet Symfony')]
function project_init(bool $node = false, bool $migrate = false) {


    io()->title('Initialisation du projet Symfony');

    $nodePacketManager = io()->ask('Quel packet manager utilisez-vous ? (yarn/npm)');

    io()->info('Installation des vendors');
    run('composer install');

    if ($node) {
        io()->info('Installation node_modules');
        run($nodePacketManager . ' install');
    }

    io()->info('Copie du .env');
    run('cp .env .env.local');

    io()->info('Mise en place des variables environnement');
    $dbUser = io()->ask('DatabaseUser:');
    $dbPassword = io()->askHidden('DatabasePassword:');
    $dbName = io()->ask('DatabaseName:');
    $dbHost = io()->ask('DatabaseHost:');
    $dbPort = io()->ask('DatabasePort:');

    $dbUrl = "DATABASE_URL=\"postgresql://$dbUser:$dbPassword@$dbHost:$dbPort/$dbName\"";
    run("sed -i 's|^DATABASE_URL=.*|$dbUrl|' .env.local");

    $smtpHost = io()->ask('SMTP Host:');
    $smtpPort = io()->ask('SMTP Port:');

    $mailerDsn = "MAILER_DSN=\"$smtpHost:$smtpPort\"";
    run("sed -i 's|^MAILER_DSN=.*|$mailerDsn|' .env.local");

    io()->info('Création de la BDD');
    run('bin/console d:d:c --if-not-exists');

    if ($migrate) {
        io()->info('Migration de la BDD');
        run('bin/console d:m:m -n');
    }
}

#[AsTask(description: 'Installe les paquets')]
function install_packages(bool $node = false): void
{
    io()->title('Installation du projet');

    run('composer install');
    
    if ($node) {
        $nodePacketManager = io()->ask('Quel packet manager utilisez-vous ? (yarn/npm)');
        run($nodePacketManager . ' install');
    }
    

    
    
}

/**
 * Base de données
 */
#[AsTask(description: 'Création de la BDD')]
function create_db(): void
{
    io()->title('Creation de la BDD');
    run('php bin/console d:d:c --if-not-exists');
}

#[AsTask(description: 'Création des migrations')]
function create_migration(): void
{
    io()->title('Creation des migrations');
    run('php bin/console make:migration');
}

#[AsTask(description: 'Migration de la base de données')]
function migrate(): void
{
    io()->title('Migration de la BDD');
    run('php bin/console d:m:m -n');
}

/**
 * Qualité de code
 */
#[AsTask(description: 'Applique la qualité au level max sur le projet')]
function phpstan(): void
{
    $projectPath = getcwd();
    io()->info('Analyse du projet avec PHPStan');
    run('docker run --rm -v ' . $projectPath . ':/app -w /app jakzal/phpqa:php8.3 phpstan analyse -c phpstan.neon');
}

#[AsTask(description: 'Check la validation en PSR12')]
function phpcsfixer(): void
{
    $projectPath = getcwd();
    io()->info('Analyse du projet avec PHPCsFixer');
    run('docker run --rm -v ' . $projectPath . ':/app -w /app jakzal/phpqa:php8.3 php-cs-fixer --config=.php-cs-fixer.dist.php fix');
}

#[AsTask(description: 'Fix les erreurs PSR12')]
function phpcbf(): void
{
    $projectPath = getcwd();
    io()->info('Analyse du projet avec PHPCbf');
    run('docker run --rm -v ' . $projectPath . ':/app -w /app jakzal/phpqa:php8.3 phpcbf --standard=PSR12 --colors src tests || true');
}

/**
 * Gestion Git
 */
#[AsTask(description: 'Récupère la dernière version de la branche principale')]
function pull_main(bool $migrate = false): void
{
    run('git checkout main');
    run('git pull origin main');
    run('castor install-packages');
    if ($migrate) {
        run('castor migrate');
    }
    run('castor clean');
}

#[AsTask(description: 'Rebase la branche actuelle avec master')]
function rebase(string $branch): void
{
    run('castor pull-main');
    run(sprintf('git checkout %s', escapeshellarg($branch)));
    run('git rebase main');
}

/**
 * Maintenance
 */
#[AsTask(description: 'Nettoie le projet')]
function clean(string $env = 'dev'): void
{
    run(sprintf('bin/console c:c --env=%s', escapeshellarg($env)));
}

/**
 * Docker
 */

#[AsTask(description: 'Démarrage des containers Docker')]
function docker_up(): void
{
    io()->title('Démarrage des containers Docker');
    run('docker-compose up -d');
}

#[AsTask(description: 'Arrêt des containers Docker')]
function docker_down():void
{
    io()->title('Arrêt des containers Docker');
    run('docker-compose down');
}

/**
 * Virtual Host
 */
#[AsTask(description: 'Crée un virtual host pour le projet')]
function createVhost(bool $ssl = false): void 
{
    $config = loadVhostConfig();
    assertSudoRights();
    
    $serverName = buildServerName($config);
    $documentRoot = sprintf('%s/public', getcwd());
    
    $vhostCreator = match($config['server']) {
        'apache2' => fn() => createApacheVhost($serverName, $documentRoot, $ssl, $config['os']),
        'nginx' => fn() => createNginxVhost($serverName, $documentRoot, $ssl),
        default => throw new \RuntimeException('Serveur web non supporté')
    };

    $vhostCreator();    
    io()->success(sprintf('Virtual host créé pour %s', $serverName));
}

function loadVhostConfig(): array 
{
    try {
        $projectDir = getcwd();
        require_once $projectDir . '/vendor/autoload.php';
        
        $kernelClass = $_SERVER['KERNEL_CLASS'] ?? 'App\\Kernel';
        $env = $_SERVER['APP_ENV'] ?? 'dev';
        $debug = (bool) ($_SERVER['APP_DEBUG'] ?? true);
        
        if (!class_exists($kernelClass)) {
            throw new \RuntimeException('Impossible de trouver la classe Kernel. Êtes-vous dans un projet Symfony ?');
        }
        
        /** @var \Symfony\Component\HttpKernel\KernelInterface $kernel */
        $kernel = new $kernelClass($env, $debug);
        $kernel->boot();
        
        $config = $kernel->getContainer()->getParameter('castor.vhost');
        
        if (empty($config)) {
            throw new \RuntimeException('Configuration vhost manquante dans config/packages/castor.yaml');
        }
        
        return $config;
    } catch (\Throwable $e) {
        throw new \RuntimeException(
            "Impossible de charger la configuration Symfony.\n" .
            "Erreur: " . $e->getMessage()
        );
    }
}

function assertSudoRights(): void 
{
    $result = run('sudo -n true 2>/dev/null', allowFailure: true);
    
    if (!$result->isSuccessful()) {
        throw new \RuntimeException('Droits sudo requis pour créer le vhost');
    }
}

function buildServerName(array $config): string 
{
    $projectName = $config['nom'] ?? basename(getcwd());
    $domain = $config['url'] ?? 'test';
    
    return sprintf('%s.%s', $projectName, $domain);
}

function detectOS(): string 
{
    if (file_exists('/etc/debian_version')) {
        return 'debian';
    }
    if (file_exists('/etc/redhat-release')) {
        return 'rhel';
    }
    return 'debian'; // default
}

function createApacheVhost(string $serverName, string $documentRoot, bool $ssl, string $os): void 
{
    $isDebianBased = in_array($os, ['debian', 'ubuntu']);
    $configPath = $isDebianBased ? '/etc/apache2/sites-available' : '/etc/httpd/conf.d';
    $configFile = sprintf('%s/%s.conf', $configPath, $serverName);

    // Vérifier si le fichier existe déjà
    if (file_exists($configFile)) {
        if (!io()->confirm(sprintf('Le fichier %s existe déjà. Voulez-vous le remplacer ?', $configFile), false)) {
            io()->warning('Création du vhost annulée.');
            return;
        }
    }

    $template = <<<EOF
<VirtualHost *:80>
    ServerName {$serverName}
    DocumentRoot {$documentRoot}
    
    <Directory {$documentRoot}>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/{$serverName}_error.log
    CustomLog \${APACHE_LOG_DIR}/{$serverName}_access.log combined
</VirtualHost>
EOF;

    file_put_contents('/tmp/vhost.conf', $template);
    run(sprintf('sudo mv /tmp/vhost.conf %s', $configFile));

    // Activation du vhost selon l'OS
    if ($isDebianBased) {
        run(sprintf('sudo a2ensite %s.conf', $serverName));
        run('sudo systemctl restart apache2');
    } else {
        run('sudo systemctl restart httpd');
    }
}

function createNginxVhost(string $serverName, string $documentRoot, bool $ssl): void 
{
    $template = <<<EOF
server {
    listen 80;
    server_name {$serverName};
    root {$documentRoot};
    
    location / {
        try_files \$uri /index.php\$is_args\$args;
    }
    
    location ~ ^/index\\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_split_path_info ^(.+\\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        internal;
    }
    
    location ~ \\.php$ {
        return 404;
    }
    
    error_log /var/log/nginx/{$serverName}_error.log;
    access_log /var/log/nginx/{$serverName}_access.log;
}
EOF;

    file_put_contents('/tmp/vhost.conf', $template);
    run('sudo mv /tmp/vhost.conf /etc/nginx/sites-available/' . $serverName . '.conf');
}

function canSudo(): bool 
{
    $result = run('sudo -n true 2>/dev/null', allowFailure: true);
    return $result->isSuccessful();
}