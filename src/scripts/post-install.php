<?php

function detectOS(): string {
    if (file_exists('/etc/debian_version')) {
        return 'debian';
    }
    if (file_exists('/etc/redhat-release')) {
        return 'rhel';
    }
    return 'unknown';
}

function updateCastorConfig(): void {
    $os = detectOS();
    $projectName = basename(getcwd());

    $configPath = __DIR__ . '/../config/packages/castor.yaml';
    if (!file_exists($configPath)) {
        echo "Le fichier de configuration castor.yaml est introuvable.\n";
        return;
    }

    $configContent = file_get_contents($configPath);
    $configContent = preg_replace('/nom: .*/', "nom: \"$projectName\"", $configContent);
    $configContent = preg_replace('/os: .*/', "os: \"$os\"", $configContent);

    file_put_contents($configPath, $configContent);
    echo "Configuration castor.yaml mise à jour avec succès.\n";
}

updateCastorConfig();