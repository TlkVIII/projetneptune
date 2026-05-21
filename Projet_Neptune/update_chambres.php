<?php
// Connexion à la base de données
require_once 'config/database.php';

// Vérifier si la table a déjà la colonne "disponible"
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM chambres LIKE 'disponible'");
    if ($stmt->rowCount() == 0) {
        // La colonne n'existe pas, on la crée
        $pdo->exec("ALTER TABLE chambres ADD COLUMN disponible BOOLEAN DEFAULT 1");
        echo "Colonne 'disponible' ajoutée à la table 'chambres'.<br>";
    } else {
        echo "La colonne 'disponible' existe déjà.<br>";
    }
} catch(PDOException $e) {
    echo "Erreur lors de la vérification de la colonne 'disponible' : " . $e->getMessage() . "<br>";
}

// Supprimer les chambres existantes
try {
    $pdo->exec("DELETE FROM chambres");
    echo "Anciennes chambres supprimées.<br>";
} catch(PDOException $e) {
    echo "Erreur lors de la suppression des chambres : " . $e->getMessage() . "<br>";
}

// Insertion des nouveaux types de chambres
$chambres = [
    [
        'nom' => 'Chambre Deluxe',
        'description' => 'Une chambre élégante et confortable avec vue sur la ville. Parfaite pour les voyageurs d\'affaires ou les couples.',
        'prix' => 150.00,
        'capacite' => 2,
        'disponible' => 1
    ],
    [
        'nom' => 'Chambre Familiale',
        'description' => 'Spacieuse chambre idéale pour les familles avec espace salon et lits supplémentaires. Peut accueillir jusqu\'à 4 personnes.',
        'prix' => 250.00,
        'capacite' => 4,
        'disponible' => 1
    ],
    [
        'nom' => 'Suite Présidentielle',
        'description' => 'Notre suite la plus luxueuse avec salon privé, salle à manger et vue panoramique sur la ville. Service de majordome inclus.',
        'prix' => 500.00,
        'capacite' => 2,
        'disponible' => 1
    ],
    [
        'nom' => 'Suite VIP',
        'description' => 'Suite exclusive avec jacuzzi privé, salon séparé et service de majordome. Idéale pour un séjour de luxe.',
        'prix' => 400.00,
        'capacite' => 2,
        'disponible' => 1
    ],
    [
        'nom' => 'Suite Star',
        'description' => 'Notre meilleure suite avec terrasse privée, jacuzzi extérieur et services premium. Vue imprenable sur la mer.',
        'prix' => 600.00,
        'capacite' => 2,
        'disponible' => 1
    ]
];

try {
    $stmt = $pdo->prepare("
        INSERT INTO chambres (nom, description, prix, capacite, disponible) 
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($chambres as $chambre) {
        $stmt->execute([
            $chambre['nom'],
            $chambre['description'],
            $chambre['prix'],
            $chambre['capacite'],
            $chambre['disponible']
        ]);
    }
    
    echo "Nouvelles chambres insérées avec succès !<br>";
} catch(PDOException $e) {
    echo "Erreur lors de l'insertion des chambres : " . $e->getMessage() . "<br>";
}

echo "<br><a href='public/reservation.php'>Aller à la page de réservation</a>"; 