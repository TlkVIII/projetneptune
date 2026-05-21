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
    // Ignorer en affichage.
}

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des réservations de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as chambre_nom, c.prix, c.image
        FROM reservations r
        JOIN chambres c ON r.chambre_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.date_arrivee DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des réservations : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            padding-top: 76px;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/slider/pano2.jpeg');
            background-size: cover;
            background-position: center;
            height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .reservation-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .reservation-image {
            height: 200px;
            object-fit: cover;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-en_attente {
            background-color: #ffc107;
            color: #000;
        }

        .status-confirmee {
            background-color: #28a745;
            color: #fff;
        }

        .status-annulee {
            background-color: #dc3545;
            color: #fff;
        }

        .status-annulation_demandee {
            background-color: #6f42c1;
            color: #fff;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4">Mes Réservations</h1>
            <p class="lead">Consultez l'état de vos réservations</p>
        </div>
    </section>

    <!-- Réservations Section -->
    <section class="py-5">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <div class="text-center">
                    <i class="fas fa-calendar-times fa-4x mb-4 text-muted"></i>
                    <h3>Vous n'avez pas encore de réservation</h3>
                    <p class="text-muted">Découvrez nos chambres et réservez votre séjour dès maintenant.</p>
                    <a href="chambres.php" class="btn btn-primary">Voir nos chambres</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card reservation-card">
                                <img src="images/rooms/<?php echo htmlspecialchars($reservation['image']); ?>" 
                                     class="card-img-top reservation-image" 
                                     alt="<?php echo htmlspecialchars($reservation['chambre_nom']); ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($reservation['chambre_nom']); ?></h5>
                                        <span class="status-badge <?php echo ($reservation['statut'] === 'confirmee' && (int)($reservation['annulation_demandee'] ?? 0) === 1) ? 'status-annulation_demandee' : ('status-' . $reservation['statut']); ?>">
                                            <?php
                                            switch($reservation['statut']) {
                                                case 'en_attente':
                                                    echo 'En attente';
                                                    break;
                                                case 'confirmee':
                                                    echo ((int)($reservation['annulation_demandee'] ?? 0) === 1) ? 'Annulation demandée' : 'Confirmée';
                                                    break;
                                                case 'annulee':
                                                    echo 'Annulée';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?php 
                                        echo date('d/m/Y', strtotime($reservation['date_arrivee'])); 
                                        echo ' - ';
                                        echo date('d/m/Y', strtotime($reservation['date_depart']));
                                        ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-users me-2"></i>
                                        <?php echo $reservation['nombre_personnes']; ?> personne(s)
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-euro-sign me-2"></i>
                                        <?php echo number_format($reservation['prix'], 2, ',', ' '); ?>€/nuit
                                    </p>
                                    <?php if (!empty($reservation['demandes_speciales'])): ?>
                                        <p class="card-text">
                                            <i class="fas fa-comment me-2"></i>
                                            <?php echo htmlspecialchars($reservation['demandes_speciales']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($reservation['statut'] === 'en_attente'): ?>
                                        <div class="mt-3 d-flex">
                                            <button class="btn btn-outline-danger btn-sm me-2" 
                                                    onclick="annulerReservation(<?php echo $reservation['id']; ?>)">
                                                <i class="fas fa-times me-1"></i>Annuler
                                            </button>
                                            <a href="facture.php?id=<?php echo $reservation['id']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-file-invoice me-1"></i>Facture
                                            </a>
                                        </div>
                                    <?php elseif ($reservation['statut'] === 'confirmee'): ?>
                                        <div class="mt-3 d-flex">
                                            <?php if ((int)($reservation['annulation_demandee'] ?? 0) === 1): ?>
                                                <button class="btn btn-outline-secondary btn-sm me-2" disabled>
                                                    <i class="fas fa-hourglass-half me-1"></i>Demande envoyée
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-warning btn-sm me-2" 
                                                        onclick="demanderAnnulationReservation(<?php echo $reservation['id']; ?>)">
                                                    <i class="fas fa-paper-plane me-1"></i>Demander annulation
                                                </button>
                                            <?php endif; ?>
                                            <a href="facture.php?id=<?php echo $reservation['id']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-file-invoice me-1"></i>Facture
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3">
                                            <a href="facture.php?id=<?php echo $reservation['id']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-file-invoice me-1"></i>Facture
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function annulerReservation(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                // Envoyer une requête AJAX pour annuler la réservation
                fetch('annuler_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + reservationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de l\'annulation de la réservation : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de l\'annulation de la réservation');
                });
            }
        }

        function demanderAnnulationReservation(reservationId) {
            if (confirm('Envoyer une demande d\'annulation à l\'administration ?')) {
                fetch('annuler_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + reservationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de la demande d\'annulation');
                });
            }
        }
    </script>
</body>
</html> 