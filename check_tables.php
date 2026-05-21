<?php
// Connexion à la base de données
require_once 'config/database.php';

// Vérification des tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables dans la base de données</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Vérification spécifique de la table chambres
    if (in_array('chambres', $tables)) {
        echo "<h2>Structure de la table chambres</h2>";
        $stmt = $pdo->query("DESCRIBE chambres");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Nom</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>".htmlspecialchars($value)."</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérification des données
        echo "<h2>Données dans la table chambres</h2>";
        $stmt = $pdo->query("SELECT * FROM chambres");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            echo "<table border='1'>";
            echo "<tr>";
            foreach ($rows[0] as $key => $value) {
                echo "<th>".htmlspecialchars($key)."</th>";
            }
            echo "</tr>";
            
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<td>".htmlspecialchars($value ?? 'NULL')."</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucune donnée dans la table chambres</p>";
        }
    } else {
        echo "<h2>La table chambres n'existe pas !</h2>";
        
        // Création de la table chambres
        echo "<h2>Création de la table chambres...</h2>";
        $pdo->exec("
            CREATE TABLE chambres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                prix DECIMAL(10,2) NOT NULL,
                capacite INT NOT NULL,
                disponible BOOLEAN DEFAULT 1,
                nb_lits_simples INT DEFAULT 0,
                nb_lits_doubles INT DEFAULT 0,
                salle_bain_privee BOOLEAN DEFAULT 1,
                balcon BOOLEAN DEFAULT 0,
                vue_mer BOOLEAN DEFAULT 0,
                climatisation BOOLEAN DEFAULT 1,
                wifi BOOLEAN DEFAULT 1,
                minibar BOOLEAN DEFAULT 0,
                coffre_fort BOOLEAN DEFAULT 0,
                service_etage BOOLEAN DEFAULT 0,
                superficie DECIMAL(6,2) DEFAULT NULL,
                etage INT DEFAULT 1,
                image VARCHAR(255) DEFAULT 'default.jpg'
            )
        ");
        echo "<p>Table chambres créée avec succès !</p>";
    }
    
} catch(PDOException $e) {
    echo "<h2>Erreur</h2>";
    echo "<p>".htmlspecialchars($e->getMessage())."</p>";
}

echo "<p><a href='update_chambres.php'>Ajouter des chambres</a></p>";
echo "<p><a href='public/reservation.php'>Aller à la page de réservation</a></p>"; 