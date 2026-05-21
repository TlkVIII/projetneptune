<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur vient bien de faire une réservation
if (!isset($_SESSION['reservation_success']) || !isset($_GET['id'])) {
    header('Location: reservation.php');
    exit;
}

$reservation_id = intval($_GET['id']);
$reservation = null;
$chambre = null;

try {
    // Récupérer les détails de la réservation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as chambre_nom, c.prix, c.image
        FROM reservations r
        JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si la réservation n'existe pas
    if (!$reservation) {
        header('Location: reservation.php');
        exit;
    }
} catch (PDOException $e) {
    // En cas d'erreur, rediriger vers la page de réservation
    header('Location: reservation.php');
    exit;
}

// Calculer la durée du séjour
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$duree_sejour = $date_depart->diff($date_arrivee)->days;

// Formatage des dates
$date_arrivee_fr = $date_arrivee->format('d/m/Y');
$date_depart_fr = $date_depart->format('d/m/Y');

// Nettoyer la variable de session
unset($_SESSION['reservation_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Réservation - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 76px;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .confirmation-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .room-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .room-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        
        .room-info {
            padding: 1.5rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .status-en_attente {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .next-steps {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--secondary-color);
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container confirmation-container">
        <div class="confirmation-header">
            <i class="fas fa-check-circle confirmation-icon"></i>
            <h1>Demande de réservation envoyée !</h1>
            <p class="lead">Nous avons bien reçu votre demande de réservation.</p>
        </div>
        
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Votre réservation est en attente de confirmation par notre équipe.</strong>
        </div>
        
        <div class="room-card">
            <div class="room-image" style="background-image: url('images/rooms/<?php echo htmlspecialchars($reservation['image']); ?>')"></div>
            <div class="room-info">
                <span class="status-badge status-<?php echo $reservation['statut']; ?>">
                    <?php echo ($reservation['statut'] === 'en_attente') ? 'En attente de confirmation' : $reservation['statut']; ?>
                </span>
                <h3><?php echo htmlspecialchars($reservation['chambre_nom']); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <p><i class="fas fa-calendar-check me-2"></i><strong>Arrivée:</strong> <?php echo $date_arrivee_fr; ?></p>
                        <p><i class="fas fa-calendar-times me-2"></i><strong>Départ:</strong> <?php echo $date_depart_fr; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-bed me-2"></i><strong>Durée:</strong> <?php echo $duree_sejour; ?> nuit<?php echo ($duree_sejour > 1) ? 's' : ''; ?></p>
                        <p><i class="fas fa-users me-2"></i><strong>Personnes:</strong> <?php echo $reservation['nombre_personnes']; ?></p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="h4"><?php echo number_format($reservation['prix'] * $duree_sejour, 2, ',', ' '); ?> €</p>
                </div>
            </div>
        </div>
        
        <div class="next-steps">
            <h4><i class="fas fa-tasks me-2"></i>Prochaines étapes</h4>
            <ul>
                <li>Notre équipe va examiner votre demande dans les plus brefs délais.</li>
                <li>Vous recevrez prochainement un email de confirmation.</li>
                <li>Vous pouvez consulter l'état de votre réservation à tout moment dans votre espace client.</li>
            </ul>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Retour à l'accueil
            </a>
            <a href="reservations.php" class="btn btn-primary">
                <i class="fas fa-list-alt me-2"></i>Mes réservations
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 