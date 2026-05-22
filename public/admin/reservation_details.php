<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de réservation invalide.";
    header('Location: reservations.php');
    exit();
}

$reservation_id = $_GET['id'];

// Récupérer les détails de la réservation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email, u.telephone AS user_telephone,
               c.nom AS chambre_nom, c.description AS chambre_description, c.prix, c.image
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['error'] = "Réservation non trouvée.";
        header('Location: reservations.php');
        exit();
    }
    
    // Calculer la durée du séjour et le prix total
    $date_arrivee = new DateTime($reservation['date_arrivee']);
    $date_depart = new DateTime($reservation['date_depart']);
    $duree = $date_depart->diff($date_arrivee)->days;
    $prix_total = $duree * $reservation['prix'];
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des détails de la réservation : " . $e->getMessage();
    header('Location: reservations.php');
    exit();
}

// Traitement de la confirmation ou du rejet d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'confirm') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmee' WHERE id = ?");
            $status = 'confirmée';
            $message_admin = "La réservation a été confirmée avec succès.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'rejetee' WHERE id = ?");
            $status = 'rejetée';
            $message_admin = "La réservation a été rejetée.";
        }
        
        $stmt->execute([$reservation_id]);
        
        // Récupérer les informations pour l'email
        $to = $reservation['user_email'];
        $subject = "Mise à jour de votre réservation - Hôtel Neptune";
        
        $message = "
        <html>
        <head>
        <title>Mise à jour de votre réservation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .reservation-details { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 0.8em; color: #777; }
            .button { background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            .status-confirmed { color: #27ae60; font-weight: bold; }
            .status-rejected { color: #e74c3c; font-weight: bold; }
        </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Hôtel Neptune</h1>
                    <p>Mise à jour de votre réservation</p>
                </div>
                <div class='content'>
                    <p>Bonjour " . htmlspecialchars($reservation['user_prenom'] . ' ' . $reservation['user_nom']) . ",</p>";
            
        if ($action === 'confirm') {
            $message .= "
                    <p>Nous avons le plaisir de vous informer que votre demande de réservation a été <span class='status-confirmed'>confirmée</span>.</p>
                    <p>Nous nous réjouissons de vous accueillir prochainement dans notre établissement.</p>";
        } else {
            $message .= "
                    <p>Nous sommes désolés de vous informer que votre demande de réservation a été <span class='status-rejected'>rejetée</span>.</p>
                    <p>Cela peut être dû à un problème de disponibilité ou à d'autres contraintes. N'hésitez pas à nous contacter pour plus d'informations ou pour effectuer une nouvelle réservation à d'autres dates.</p>";
        }
        
        $message .= "
                    <div class='reservation-details'>
                        <h3>Détails de votre réservation :</h3>
                        <p><strong>Numéro de réservation :</strong> " . $reservation_id . "</p>
                        <p><strong>Chambre :</strong> " . htmlspecialchars($reservation['chambre_nom']) . "</p>
                        <p><strong>Date d'arrivée :</strong> " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . "</p>
                        <p><strong>Date de départ :</strong> " . date('d/m/Y', strtotime($reservation['date_depart'])) . "</p>
                        <p><strong>Nombre de nuits :</strong> " . $duree . "</p>
                        <p><strong>Nombre de personnes :</strong> " . $reservation['nombre_personnes'] . "</p>
                        <p><strong>Prix par nuit :</strong> " . number_format($reservation['prix'], 2, ',', ' ') . " €</p>
                        <p><strong>Prix total :</strong> " . number_format($prix_total, 2, ',', ' ') . " €</p>";
            
        if (!empty($reservation['demandes_speciales'])) {
            $message .= "<p><strong>Demandes spéciales :</strong> " . htmlspecialchars($reservation['demandes_speciales']) . "</p>";
        }
        
        $message .= "
                    </div>";
        
        if ($action === 'confirm') {
            $message .= "
                    <h3>Informations pratiques pour votre séjour :</h3>
                    <p><strong>Check-in :</strong> à partir de 14h00</p>
                    <p><strong>Check-out :</strong> avant 11h00</p>
                    <p><strong>Petit-déjeuner :</strong> servi de 7h00 à 10h00</p>
                    <p>Notre équipe se tient à votre disposition pour toute information complémentaire.</p>";
        }
        
        $message .= "
                    <p>Vous pouvez consulter les détails de votre réservation à tout moment en vous connectant à votre compte sur notre site.</p>
                    <p style='text-align: center;'><a href='http://" . $_SERVER['HTTP_HOST'] . "/public/reservations.php' class='button'>Voir mes réservations</a></p>
                </div>
                <div class='footer'>
                    <p>Hôtel Neptune - Montpellier, France 34000 - 0695396132 - fayed.amourani8@gmail.com</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // En-têtes pour l'email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Hôtel Neptune <fayed.amourani8@gmail.com>" . "\r\n";
        
        // Envoi de l'email
        mail($to, $subject, $message, $headers);
        
        $_SESSION['message'] = $message_admin;
        header('Location: reservation_details.php?id=' . $reservation_id);
        exit;
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors du traitement de la réservation : " . $e->getMessage();
        header('Location: reservation_details.php?id=' . $reservation_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la réservation - Administration - Hôtel Neptune</title>
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
            background-color: #f5f5f5;
        }
        
        .sidebar {
            background: var(--primary-color);
            color: white;
            min-height: 100vh;
            padding-top: 20px;
        }
        
        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px 15px;
            display: block;
        }
        
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .admin-brand {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            padding: 0 15px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .content {
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-en_attente {
            background-color: #f39c12;
            color: white;
        }
        
        .status-confirmee {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-rejetee {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-annulee {
            background-color: #7f8c8d;
            color: white;
        }
        
        .reservation-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .client-card {
            border-left: 5px solid var(--secondary-color);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--primary-color);
        }

        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 260px;
                z-index: 1040;
                overflow-y: auto;
            }
            .content {
                margin-left: 0 !important;
            }
            .reservation-image {
                height: 220px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="admin-brand text-center">
                <i class="fas fa-hotel"></i> Hôtel Neptune
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chambres.php" class="nav-link">
                        <i class="fas fa-bed me-2"></i> Chambres
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reservations.php" class="nav-link active">
                        <i class="fas fa-calendar-check me-2"></i> Réservations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="utilisateurs.php" class="nav-link">
                        <i class="fas fa-users me-2"></i> Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link">
                        <i class="fas fa-envelope me-2"></i> Messages
                    </a>
                </li>
                <li class="nav-item mt-5">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1><i class="fas fa-info-circle me-2"></i> Détails de la réservation #<?= $reservation_id ?></h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="reservations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <!-- Informations sur la réservation -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar-check me-2"></i> Informations sur la réservation</span>
                            <span class="status-badge status-<?= $reservation['statut'] ?>">
                                <?php
                                switch($reservation['statut']) {
                                    case 'en_attente':
                                        echo 'En attente';
                                        break;
                                    case 'confirmee':
                                        echo 'Confirmée';
                                        break;
                                    case 'rejetee':
                                        echo 'Rejetée';
                                        break;
                                    case 'annulee':
                                        echo 'Annulée';
                                        break;
                                    default:
                                        echo ucfirst($reservation['statut']);
                                }
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <img src="../images/rooms/<?= htmlspecialchars($reservation['image']) ?>" alt="<?= htmlspecialchars($reservation['chambre_nom']) ?>" class="reservation-image shadow mb-3">
                                </div>
                                <div class="col-md-6">
                                    <h4><?= htmlspecialchars($reservation['chambre_nom']) ?></h4>
                                    <p class="text-muted"><?= htmlspecialchars($reservation['chambre_description']) ?></p>
                                    <div class="mt-4">
                                        <div class="row mb-2">
                                            <div class="col-6 detail-label">Date d'arrivée:</div>
                                            <div class="col-6"><?= date('d/m/Y', strtotime($reservation['date_arrivee'])) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 detail-label">Date de départ:</div>
                                            <div class="col-6"><?= date('d/m/Y', strtotime($reservation['date_depart'])) ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 detail-label">Durée du séjour:</div>
                                            <div class="col-6"><?= $duree ?> nuit(s)</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 detail-label">Nombre de personnes:</div>
                                            <div class="col-6"><?= $reservation['nombre_personnes'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Informations financières</h5>
                                    <div class="row mb-2">
                                        <div class="col-6 detail-label">Prix par nuit:</div>
                                        <div class="col-6"><?= number_format($reservation['prix'], 2, ',', ' ') ?> €</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6 detail-label">Nombre de nuits:</div>
                                        <div class="col-6"><?= $duree ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6 detail-label">Prix total:</div>
                                        <div class="col-6 fw-bold"><?= number_format($prix_total, 2, ',', ' ') ?> €</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Détails de la réservation</h5>
                                    <div class="row mb-2">
                                        <div class="col-6 detail-label">Date de création:</div>
                                        <div class="col-6"><?= date('d/m/Y H:i', strtotime($reservation['date_creation'])) ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6 detail-label">Numéro de réservation:</div>
                                        <div class="col-6">#<?= $reservation['id'] ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['demandes_speciales'])): ?>
                                <div class="mt-4">
                                    <h5>Demandes spéciales:</h5>
                                    <div class="p-3 bg-light rounded">
                                        <i class="fas fa-quote-left text-muted me-2"></i>
                                        <?= nl2br(htmlspecialchars($reservation['demandes_speciales'])) ?>
                                        <i class="fas fa-quote-right text-muted ms-2"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                <div class="d-flex justify-content-end mt-4">
                                    <form action="" method="post" class="me-2">
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')">
                                            <i class="fas fa-check me-2"></i>Confirmer la réservation
                                        </button>
                                    </form>
                                    <form action="" method="post">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette réservation ?')">
                                            <i class="fas fa-times me-2"></i>Rejeter la réservation
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Informations sur le client -->
                <div class="col-md-4">
                    <div class="card client-card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user me-2"></i> Informations sur le client
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                    <?= strtoupper(substr($reservation['user_prenom'], 0, 1) . substr($reservation['user_nom'], 0, 1)) ?>
                                </div>
                                <h5 class="mb-0"><?= htmlspecialchars($reservation['user_prenom'] . ' ' . $reservation['user_nom']) ?></h5>
                                <p class="text-muted">Client</p>
                            </div>
                            
                            <div class="mb-3">
                                <p><i class="fas fa-envelope me-2 text-primary"></i> <?= htmlspecialchars($reservation['user_email']) ?></p>
                                <p><i class="fas fa-phone me-2 text-primary"></i> <?= htmlspecialchars($reservation['user_telephone']) ?></p>
                            </div>
                            
                            <a href="utilisateurs.php?view=<?= $reservation['user_id'] ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-circle me-2"></i>Voir le profil complet
                            </a>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-tasks me-2"></i> Actions
                        </div>
                        <div class="card-body">
                            <a href="../facture.php?id=<?= $reservation_id ?>" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fas fa-file-invoice me-2"></i>Voir la facture
                            </a>
                            <a href="javascript:window.print();" class="btn btn-outline-primary w-100">
                                <i class="fas fa-print me-2"></i>Imprimer cette page
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 