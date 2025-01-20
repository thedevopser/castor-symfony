# Castor Bundle pour Symfony

[English version below](#symfony-castor-bundle)

Ce bundle fournit un ensemble de tâches Castor pour faciliter le développement et le déploiement d'applications Symfony.

## Installation

1. Installez le bundle via Composer :
```bash
composer require thedevopser/castor-symfony
```

2. Enregistrez le bundle dans votre application en ajoutant la ligne suivante dans `config/bundles.php` :
```php
return [
    // ...
    TheDevOpser\CastorBundle\CastorBundle::class => ['all' => true],
];
```

3. Installez le fichier castor.php à la racine de votre projet :
```bash
php bin/console castor:install
```

## Tâches disponibles

### Installation et Initialisation

- `castor project-init [--node] [--migrate]` : Initialise un nouveau projet Symfony
    - Options :
        - `--node` : Active l'installation des dépendances Node.js
        - `--migrate` : Execute les migrations après l'initialisation
- `castor install-packages` : Installe les dépendances Composer et Node.js

### Base de données

- `castor create-db` : Crée la base de données
- `castor create-migration` : Génère une nouvelle migration
- `castor migrate` : Exécute les migrations en attente

### Qualité de code

- `castor phpstan` : Analyse le code avec PHPStan
- `castor phpcsfixer` : Vérifie et corrige le formatage PSR-12
- `castor phpcbf` : Corrige automatiquement les erreurs de style PSR-12

### Gestion Git

- `castor pull-main [--migrate]` : Met à jour la branche principale
    - Options :
        - `--migrate` : Execute les migrations après la mise à jour
- `castor rebase {branch}` : Rebase une branche sur main

### Docker

- `castor docker-up` : Démarre les containers Docker
- `castor docker-down` : Arrête les containers Docker

### Maintenance

- `castor clean [env]` : Nettoie le cache
    - Arguments :
        - `env` : Environnement cible (défaut: 'dev')

---

# Symfony Castor Bundle

This bundle provides a set of Castor tasks to facilitate the development and deployment of Symfony applications.

## Installation

1. Install the bundle via Composer:
```bash
composer require thedevopser/castor-symfony
```

2. Register the bundle in your application by adding the following line in `config/bundles.php`:
```php
return [
    // ...
    TheDevOpser\CastorBundle\CastorBundle::class => ['all' => true],
];
```

3. Install the castor.php file at the root of your project:
```bash
php bin/console castor:install
```

## Available Tasks

### Installation and Initialization

- `castor project-init [--node] [--migrate]`: Initializes a new Symfony project
    - Options:
        - `--node`: Enables Node.js dependencies installation
        - `--migrate`: Executes migrations after initialization
- `castor install-packages`: Installs Composer and Node.js dependencies

### Database

- `castor create-db`: Creates the database
- `castor create-migration`: Generates a new migration
- `castor migrate`: Executes pending migrations

### Code Quality

- `castor phpstan`: Analyzes code with PHPStan
- `castor phpcsfixer`: Checks and fixes PSR-12 formatting
- `castor phpcbf`: Automatically fixes PSR-12 style errors

### Git Management

- `castor pull-main [--migrate]`: Updates the main branch
    - Options:
        - `--migrate`: Executes migrations after update
- `castor rebase {branch}`: Rebases a branch onto main

### Docker

- `castor docker-up`: Starts Docker containers
- `castor docker-down`: Stops Docker containers

### Maintenance

- `castor clean [env]`: Cleans the cache
    - Arguments:
        - `env`: Target environment (default: 'dev')