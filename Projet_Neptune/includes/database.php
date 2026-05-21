<?php
/**
 * Gestion de la connexion à la base de données
 * Fichier : includes/database.php
 */

// Définir la constante de jeu de caractères si elle n'est pas déjà définie
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Établit une connexion à la base de données
 * @return PDO Instance de connexion PDO
 */
function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // En production, on loggera l'erreur au lieu de l'afficher
        error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
        die("Impossible de se connecter à la base de données. Veuillez contacter l'administrateur.");
    }
}

/**
 * Exécute une requête SQL avec les paramètres fournis
 * @param string $sql Requête SQL à exécuter
 * @param array $params Paramètres pour la requête préparée
 * @return PDOStatement Résultat de la requête
 */
function executeQuery($sql, $params = []) {
    $db = connectDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Récupère une seule ligne de résultat
 * @param string $sql Requête SQL à exécuter
 * @param array $params Paramètres pour la requête préparée
 * @return array|null Ligne de résultat ou null si aucun résultat
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Récupère toutes les lignes de résultat
 * @param string $sql Requête SQL à exécuter
 * @param array $params Paramètres pour la requête préparée
 * @return array Lignes de résultat
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Effectue une insertion et retourne l'ID généré
 * @param string $table Nom de la table
 * @param array $data Données à insérer (clé => valeur)
 * @return int|false ID de la ligne insérée ou false en cas d'échec
 */
function insert($table, $data) {
    $db = connectDB();
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log('Erreur d\'insertion : ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour des enregistrements dans la base de données
 * @param string $table Nom de la table
 * @param array $data Données à mettre à jour (clé => valeur)
 * @param string $whereClause Condition WHERE
 * @param array $whereParams Paramètres pour la condition WHERE
 * @return int Nombre de lignes affectées
 */
function update($table, $data, $whereClause, $whereParams = []) {
    $db = connectDB();
    
    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "{$column} = ?";
    }
    $setClause = implode(', ', $setParts);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
    
    try {
        $stmt = $db->prepare($sql);
        $params = array_merge(array_values($data), $whereParams);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Erreur de mise à jour : ' . $e->getMessage());
        return 0;
    }
}

/**
 * Supprime des enregistrements de la base de données
 * @param string $table Nom de la table
 * @param string $whereClause Condition WHERE
 * @param array $whereParams Paramètres pour la condition WHERE
 * @return int Nombre de lignes affectées
 */
function delete($table, $whereClause, $whereParams = []) {
    $db = connectDB();
    
    $sql = "DELETE FROM {$table} WHERE {$whereClause}";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($whereParams);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Erreur de suppression : ' . $e->getMessage());
        return 0;
    }
}
?> 