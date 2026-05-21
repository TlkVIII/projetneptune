<?php
/**
 * Configuration générale du site
 */

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Informations de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_neptune');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_NAME', 'Hôtel Neptune');
define('SITE_URL', 'http://localhost/Projet_Neptune');
define('ADMIN_EMAIL', 'fayed.amourani8@gmail.com');

// Chemins des dossiers
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('TEMPLATES_PATH', ROOT_PATH . 'templates/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');
define('UPLOADS_PATH', PUBLIC_PATH . 'uploads/');

// Paramètres de sécurité
define('BCRYPT_COST', 12); // Coût de hachage bcrypt

// Activation du mode de débogage
define('DEBUG_MODE', true);

// En mode débogage, afficher toutes les erreurs
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
} 