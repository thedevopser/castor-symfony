# Castor Bundle pour Symfony

[![Unit Tests](https://github.com/thedevopser/castor-symfony/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/thedevopser/castor-symfony/actions/workflows/unit-tests.yml)
[![Integration Tests](https://github.com/thedevopser/castor-symfony/actions/workflows/integration-tests.yml/badge.svg)](https://github.com/thedevopser/castor-symfony/actions/workflows/integration-tests.yml)

[English version below](#symfony-castor-bundle)

Ce bundle fournit un ensemble de tâches Castor pour faciliter le développement et le déploiement d'applications Symfony.

## Pré-requis

Ce bundle nécessite l'installation préalable de Castor CLI sur votre système. Pour l'installer, suivez les instructions sur la [page d'installation officielle de Castor](https://castor.jolicode.com/installation/).

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

3. Installez les fichiers castor.php et castorPersonal.php à la racine de votre projet :

```bash
php bin/console castor:install
```

**Note:** Lors des mises à jour du bundle, relancez cette commande pour obtenir la dernière version du fichier `castor.php`. Le fichier `castorPersonal.php` ne sera pas écrasé.

## Personnalisation

Le bundle installe deux fichiers à la racine de votre projet :

- `castor.php` : Le fichier principal contenant les tâches prédéfinies
- `castorPersonal.php` : Un fichier pour vos tâches personnalisées

Le fichier `castorPersonal.php` est créé lors de la première installation et n'est jamais écrasé lors des mises à jour du bundle. C'est l'endroit idéal pour ajouter vos propres tâches et personnalisations.

Exemple de personnalisation dans `castorPersonal.php` :

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\run;

#[AsTask(description: 'Ma tâche personnalisée')]
function maTask(): void
{
    run('echo "Hello from my custom task!"');
}
```

## Tâches disponibles

### Installation et Initialisation

- `castor project-init [--node] [--migrate]` : Initialise un nouveau projet Symfony
  - Options :
    - `--node` : Active l'installation des dépendances Node.js
    - `--migrate` : Execute les migrations après l'initialisation
- `castor install-packages` : Installe les dépendances Composer et Node.js
  - Options :
    - `--node` : Active la prise en charge de Node.Js

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

### Virtual Hosts

- `castor create-vhost [--ssl]` : Crée un virtual host pour votre projet
  - Options :
    - `--ssl` : Active la configuration SSL si les certificats sont configurés

#### Configuration

Ajoutez la configuration suivante dans `config/packages/castor.yaml` :

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    nom: null
    server: "%env(CASTOR_VHOST_SERVER)%"
    os: null
    ssl:
      enabled: "%env(CASTOR_VHOST_SSL_ENABLE)%"
      certificate: null
      certificate_key: null
```
Certaines de ces variables peuvent être définies dans le fichier .env :

```dotenv
CASTOR_VHOST_URL=local
CASTOR_VHOST_SERVER=apache2
CASTOR_VHOST_SSL_ENABLE=false
```

#### Exemples

Configuration basique:

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    server: "%env(CASTOR_VHOST_SERVER)%"
```

```dotenv
CASTOR_VHOST_SERVER=apache2
CASTOR_VHOST_URL="dev.local"
```

Configuration avec SSL:

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    nom: "mysuperproject"
    server: "%env(CASTOR_VHOST_SERVER)%"
    os: "rhel"
    ssl:
      enabled: "%env(CASTOR_VHOST_SSL_ENABLE)%"
      certificate: "/etc/ssl/certs/my-cert.pem"
      certificate_key: "/etc/ssl/private/my-cert.key"
```
```dotenv
CASTOR_VHOST_SERVER=nginx
CASTOR_VHOST_URL="dev.local"
CASTOR_VHOST_SSL_ENABLE=true
``` 

---

# Symfony Castor Bundle

[![Unit Tests](https://github.com/thedevopser/castor-symfony/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/thedevopser/castor-symfony/actions/workflows/unit-tests.yml)
[![Integration Tests](https://github.com/thedevopser/castor-symfony/actions/workflows/integration-tests.yml/badge.svg)](https://github.com/thedevopser/castor-symfony/actions/workflows/integration-tests.yml)

This bundle provides a set of Castor tasks to facilitate the development and deployment of Symfony applications.

## Prerequisites

This bundle requires Castor CLI to be installed on your system. To install it, follow the instructions on the [official Castor installation page](https://castor.jolicode.com/installation/).

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

3. Install the castor.php and castorPersonal.php files at the root of your project:

```bash
php bin/console castor:install
```

**Note:** During bundle updates, rerun this command to get the latest version of the `castor.php` file. The `castorPersonal.php` file will not be overwritten.

## Customization

The bundle installs two files at the root of your project:

- `castor.php`: The main file containing predefined tasks
- `castorPersonal.php`: A file for your custom tasks

The `castorPersonal.php` file is created during the first installation and is never overwritten during bundle updates. This is the ideal place to add your own tasks and customizations.

Example of customization in `castorPersonal.php`:

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\run;

#[AsTask(description: 'My custom task')]
function myTask(): void
{
    run('echo "Hello from my custom task!"');
}
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

### Virtual Hosts

- `castor create-vhost [--ssl]`: Creates a virtual host for your project
  - Options:
    - `--ssl`: Enables SSL configuration if certificates are configured

#### Configuration

Add the following configuration in `config/packages/castor.yaml`:

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    nom: null
    server: "%env(CASTOR_VHOST_SERVER)%"
    os: null
    ssl:
      enabled: "%env(CASTOR_VHOST_SSL_ENABLE)%"
      certificate: null
      certificate_key: null
```
Variables can be defined in the .env file:

```dotenv
CASTOR_VHOST_URL=local
CASTOR_VHOST_SERVER=apache2
CASTOR_VHOST_SSL_ENABLE=false
```

#### Examples

Basic configuration:

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    server: "%env(CASTOR_VHOST_SERVER)%"
```

```dotenv
CASTOR_VHOST_SERVER=apache2
CASTOR_VHOST_URL="dev.local"
```

SSL configuration:

```yaml
castor:
  vhost:
    url: "%env(CASTOR_VHOST_URL)%"
    nom: "mysuperproject"
    server: "%env(CASTOR_VHOST_SERVER)%"
    os: "rhel"
    ssl:
      enabled: "%env(CASTOR_VHOST_SSL_ENABLE)%"
      certificate: "/etc/ssl/certs/my-cert.pem"
      certificate_key: "/etc/ssl/private/my-cert.key"
```
```dotenv
CASTOR_VHOST_SERVER=nginx
CASTOR_VHOST_URL="dev.local"
CASTOR_VHOST_SSL_ENABLE=true
``` 
