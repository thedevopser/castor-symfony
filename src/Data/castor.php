<?php

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\io;
use function Castor\run;
use function Castor\import;
use function Castor\yaml_parse;

import(__DIR__ . '/castorPersonal.php');

/**
 * Installation et Initialisation
 */
#[AsTask(description: 'Initialisation du projet Symfony')]
function project_init(bool $node = false, bool $migrate = false)
{


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
    run('php bin/console d:d:c --if-not-exists');

    if ($migrate) {
        io()->info('Migration de la BDD');
        run('php bin/console d:m:m -n');
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
    run('php php bin/console d:d:c --if-not-exists');
}

#[AsTask(description: 'Création des migrations')]
function create_migration(): void
{
    io()->title('Creation des migrations');
    run('php php bin/console make:migration');
}

#[AsTask(description: 'Migration de la base de données')]
function migrate(): void
{
    io()->title('Migration de la BDD');
    run('php php bin/console d:m:m -n');
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
    run(sprintf('php bin/console c:c --env=%s', escapeshellarg($env)));
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
function docker_down(): void
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
    $settings = loadSystemSettings();
    verifyRootAccess();

    $hostName = generateHostName($settings);
    $webRoot = sprintf('%s/public', getcwd());

    $webServer = match ($settings['server']) {
        'apache2' => fn() => setupApacheHost($hostName, $webRoot, $ssl, $settings['os'], $settings),
        'nginx' => fn() => setupNginxHost($hostName, $webRoot, $ssl, $settings),
        default => throw new \RuntimeException('Serveur web non supporté')
    };

    $webServer();
    io()->success(sprintf('Virtual host créé pour %s', $hostName));
}

function loadSystemSettings(): array
{
    try {
        $yamlPath = sprintf('%s/config/packages/castor.yaml', getcwd());

        !file_exists($yamlPath) && throw new \RuntimeException(
            'Configuration castor.yaml manquante dans config/packages/'
        );

        $settings = yaml_parse(file_get_contents($yamlPath));

        !isset($settings['castor']['vhost']) && throw new \RuntimeException(
            'Configuration vhost manquante dans castor.yaml'
        );

        return $settings['castor']['vhost'];
    } catch (\Throwable $e) {
        throw new \RuntimeException(
            "Impossible de charger la configuration.\n" .
                "Erreur: " . $e->getMessage()
        );
    }
}

function verifyRootAccess(): void
{
    $context = new Context();
    $context->withAllowFailure();

    $result = run('sudo -n true 2>/dev/null', context: $context);

    !$result->isSuccessful() && throw new \RuntimeException('Droits sudo requis pour créer le vhost');
}

function generateHostName(array $settings): string
{
    $projectName = $settings['nom'] ?? basename(getcwd());
    $domain = $settings['url'] ?? 'test';

    return sprintf('%s.%s', $projectName, $domain);
}

function setupApacheHost(string $hostName, string $webRoot, bool $enableSsl, string $os, array $settings): void
{
    $isDebianBased = in_array($os, ['debian', 'ubuntu']);
    $vhostDir = $isDebianBased ? '/etc/apache2/sites-available' : '/etc/httpd/conf.d';
    $logsDir = $isDebianBased ? '/var/log/apache2' : '/var/log/httpd';
    $vhostPath = sprintf('%s/%s.conf', $vhostDir, $hostName);

    $exists = file_exists($vhostPath);
    $canOverwrite = !$exists || io()->confirm(
        sprintf('Le fichier %s existe déjà. Voulez-vous le remplacer ?', $vhostPath),
        false
    );

    (!$canOverwrite) && throw new \RuntimeException('Création du vhost annulée.');

    $httpVhost = buildApacheHttpVhost($hostName, $webRoot, $logsDir);
    $httpsVhost = buildApacheHttpsVhost($hostName, $webRoot, $logsDir, $settings);

    $finalVhost = $enableSsl && hasSslCertificates($settings)
        ? $httpVhost . "\n" . $httpsVhost
        : $httpVhost;

    file_put_contents('/tmp/vhost.conf', $finalVhost);
    run(sprintf('sudo mv /tmp/vhost.conf %s', $vhostPath));

    $context = new Context();
    $context->withAllowFailure();

    $restartCommand = $isDebianBased
        ? sprintf('sudo a2ensite %s.conf && sudo systemctl restart apache2', $hostName)
        : 'sudo systemctl restart httpd';

    $result = run($restartCommand, context: $context);

    (!$result->isSuccessful()) && throw new \RuntimeException(sprintf(
        "Erreur lors du redémarrage du serveur web.\nConsultez les logs avec : %s",
        $isDebianBased ? 'journalctl -xe apache2' : 'journalctl -xeu httpd.service'
    ));
}

function buildApacheHttpVhost(string $hostName, string $webRoot, string $logsDir): string
{
    return <<<EOF
<VirtualHost *:80>
    ServerName {$hostName}
    DocumentRoot {$webRoot}
    
    <Directory {$webRoot}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog {$logsDir}/{$hostName}_error.log
    CustomLog {$logsDir}/{$hostName}_access.log combined
</VirtualHost>
EOF;
}

function buildApacheHttpsVhost(string $hostName, string $webRoot, string $logsDir, array $settings): string
{
    return <<<EOF
<VirtualHost *:443>
    ServerName {$hostName}
    DocumentRoot {$webRoot}
    
    SSLEngine on
    SSLCertificateFile {$settings['ssl']['certificate']}
    SSLCertificateKeyFile {$settings['ssl']['certificate_key']}
    
    <Directory {$webRoot}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog {$logsDir}/{$hostName}_ssl_error.log
    CustomLog {$logsDir}/{$hostName}_ssl_access.log combined
</VirtualHost>
EOF;
}

function hasSslCertificates(array $settings): bool
{
    return isset($settings['ssl'])
        && $settings['ssl']['enabled']
        && !empty($settings['ssl']['certificate'])
        && !empty($settings['ssl']['certificate_key']);
}

function setupNginxHost(string $hostName, string $webRoot, bool $enableSsl, array $settings): void
{
    $vhostPath = sprintf('/etc/nginx/conf.d/%s.conf', $hostName);

    $exists = file_exists($vhostPath);
    $canOverwrite = !$exists || io()->confirm(
        sprintf('Le fichier %s existe déjà. Voulez-vous le remplacer ?', $vhostPath),
        false
    );

    (!$canOverwrite) && throw new \RuntimeException('Création du vhost annulée.');

    $httpVhost = buildNginxHttpVhost($hostName, $webRoot);
    $httpsVhost = $enableSsl && hasSslCertificates($settings)
        ? buildNginxHttpsVhost($hostName, $webRoot, $settings)
        : '';

    $finalVhost = $httpVhost . "\n" . $httpsVhost;

    file_put_contents('/tmp/vhost.conf', $finalVhost);
    run(sprintf('sudo mv /tmp/vhost.conf %s', $vhostPath));

    // Plus besoin de créer de symlink car on écrit directement dans conf.d
    $context = new Context();
    $context->withAllowFailure();

    $result = run('sudo systemctl restart nginx', context: $context);

    (!$result->isSuccessful()) && throw new \RuntimeException(
        "Erreur lors du redémarrage de Nginx.\n" .
            "Consultez les logs avec : journalctl -xeu nginx.service"
    );
}

function buildNginxHttpVhost(string $hostName, string $webRoot): string
{
    return <<<EOF
server {
    listen 80;
    server_name {$hostName};
    root {$webRoot};
    
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
    
    error_log /var/log/nginx/{$hostName}_error.log;
    access_log /var/log/nginx/{$hostName}_access.log;
}
EOF;
}

function buildNginxHttpsVhost(string $hostName, string $webRoot, array $settings): string
{
    return <<<EOF
server {
    listen 443 ssl;
    server_name {$hostName};
    root {$webRoot};
    
    ssl_certificate {$settings['ssl']['certificate']};
    ssl_certificate_key {$settings['ssl']['certificate_key']};
    
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
    
    error_log /var/log/nginx/{$hostName}_ssl_error.log;
    access_log /var/log/nginx/{$hostName}_ssl_access.log;
}
EOF;
}

function canSudo(): bool
{
    $context = new Context();
    $context->withAllowFailure();

    $result = run('sudo -n true 2>/dev/null', context: $context);
    return $result->isSuccessful();
}
