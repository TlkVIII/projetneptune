<?php
session_start();
require_once '../config/database.php';

// Migration légère: ajouter le champ de demande d'annulation si absent.
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'annulation_demandee'");
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN annulation_demandee TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (PDOException $e) {
    // Ne pas bloquer la requête utilisateur si la migration échoue.
}

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération de l'ID de la réservation
$reservation_id = $_POST['reservation_id'] ?? null;

if (!$reservation_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
    exit;
}

try {
    // Vérification que la réservation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id, statut, annulation_demandee 
        FROM reservations 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit;
    }

    if ($reservation['statut'] === 'en_attente') {
        // Annulation directe tant que la réservation n'est pas validée.
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET statut = 'annulee', annulation_demandee = 0
            WHERE id = ?
        ");
        $stmt->execute([$reservation_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Réservation annulée avec succès']);
        exit;
    }

    if ($reservation['statut'] === 'confirmee') {
        if ((int)($reservation['annulation_demandee'] ?? 0) === 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Une demande d\'annulation est déjà en cours']);
            exit;
        }

        // Réservation validée: envoyer une demande à traiter par l'admin.
        $stmt = $pdo->prepare("
            UPDATE reservations
            SET annulation_demandee = 1
            WHERE id = ?
        ");
        $stmt->execute([$reservation_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Demande d\'annulation envoyée à l\'administration']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cette réservation ne peut pas être annulée']);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'annulation de la réservation']);
} 