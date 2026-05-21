# Docker AMP Vanilla

The purpose of this template project is to provide a quick and easy way to get 
a vanilla PHP project up and running with Docker. Ths project uses Apache, MySQL
and PHP.

## Requirements

- [Docker](https://www.docker.com/)
- [Docker Composer](https://docs.docker.com/compose/)
- [Make](https://www.gnu.org/software/make/manual/make.html) (optional — [install `make` for Windows](https://stackoverflow.com/questions/2532234/how-to-run-a-makefile-in-windows))

## Usage

The first thing to do is to change a little bit the `compose.yml` file. You can
change the `MYSQL_ROOT_PASSWORD` and `MYSQL_DATABASE` environment variables to
whatever you want. You really should change the `name` of the container to
something more meaningful.

```diff
# compose.yml
- name: project-name
+ name: name-of-your-project
```

Then, you can run the following command if you have `make`.

It will:
- Build the containers
- Start the containers

```bash
make init
```

### Or, step by step

You can run the following command to build the containers:

```bash
make build # or `docker-compose build` if you don't have `make`
```

An image with [PHP](https://www.php.net), [Apache](https://httpd.apache.org), [MariaDB](https://mariadb.org) and [Composer](https://getcomposer.org) ready to use will be built.

After that, you can run the following command to start the containers:

```bash
make up # or `docker-compose up -d` if you don't have `make`
```

Apache should be ready to serve your files. You can enter the Apache container by running the following command:

```bash
make exec # or `docker-compose exec apache bash` if you don't have `make`
          # it will open a bash session inside the container
```

You can now access your project at `http://localhost:8080`.

Remember that every time you want to run a Composer command, you should run it
inside the container thanks to the `make exec` command.

# Hôtel Neptune

Site web et système de réservation pour l'Hôtel Neptune

## Description

Ce projet est un site web complet pour un hôtel fictif nommé "Hôtel Neptune". Il comprend :
- Un site web vitrine présentant l'hôtel et ses services
- Un système de réservation en ligne
- Une base de données pour gérer les chambres, clients et réservations

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Composer (optionnel, pour les dépendances futures)

## Installation

1. Clonez le repository dans votre dossier web (ex: htdocs pour XAMPP) :
   ```
   git clone https://github.com/votre-utilisateur/hotel-neptune.git
   ```

2. Démarrez votre serveur web et MySQL (via XAMPP, WAMP, MAMP ou autre)

3. Initialisez la base de données en accédant à l'URL suivante dans votre navigateur :
   ```
   http://localhost/Projet_Neptune/sql/init_database.php
   ```

4. Accédez au site web :
   ```
   http://localhost/Projet_Neptune/public/
   ```

## Structure de la base de données

La base de données `hotel_neptune` comprend les tables suivantes :

- `categories_chambre` : Types de chambres disponibles (standard, deluxe, suite)
- `chambres` : Chambres individuelles avec leur numéro, étage, catégorie
- `clients` : Informations sur les clients
- `reservations` : Réservations effectuées par les clients
- `services` : Services proposés par l'hôtel (restaurant, spa, etc.)
- `reservations_services` : Relation entre réservations et services
- `utilisateurs` : Comptes d'administration et du personnel
- `contacts` : Messages de contact envoyés via le site
- `newsletters` : Abonnements à la newsletter

## Structure du projet

```
Projet_Neptune/
├── config/              # Configuration du site
├── includes/            # Fichiers PHP partagés
│   ├── database.php     # Connexion à la base de données
│   ├── reservation.php  # Fonctions de réservation
│   └── client.php       # Fonctions de gestion des clients
├── public/              # Fichiers accessibles publiquement
│   ├── css/             # Feuilles de style CSS
│   ├── js/              # Fichiers JavaScript
│   ├── images/          # Images du site
│   ├── index.php        # Page d'accueil
│   ├── chambres.php     # Page des chambres
│   ├── contact.php      # Formulaire de contact
│   ├── reservation.php  # Système de réservation
│   └── ...
└── sql/                 # Fichiers SQL
    ├── hotel_neptune.sql    # Structure de la base de données
    └── init_database.php    # Script d'initialisation
```

## Fonctionnalités

### Front-end
- Page d'accueil présentant l'hôtel
- Page détaillée des chambres
- Formulaire de contact
- Système de réservation en ligne
- Inscription à la newsletter

### Back-end
- Gestion des chambres et disponibilités
- Gestion des réservations
- Gestion des clients
- Traitement des messages de contact
- Gestion des abonnements à la newsletter

## Crédits

- Images : [Unsplash](https://unsplash.com)
- Icônes : [Font Awesome](https://fontawesome.com)

## Licence

Ce projet est distribué sous la licence MIT. Voir le fichier `LICENSE` pour plus d'informations.
