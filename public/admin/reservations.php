<?php
session_start();
require_once '../../config/database.php';

// Migration légère: ajouter le champ de demande d'annulation si absent.
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'annulation_demandee'");
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN annulation_demandee TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (PDOException $e) {
    // Ignorer pour ne pas bloquer l'admin.
}

// Vérifier si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Traitement de la confirmation ou du rejet d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'confirm') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmee', annulation_demandee = 0 WHERE id = ?");
            $status = 'confirmée';
            $message_admin = "La réservation a été confirmée avec succès.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'rejetee', annulation_demandee = 0 WHERE id = ?");
            $status = 'rejetée';
            $message_admin = "La réservation a été rejetée.";
        } elseif ($action === 'approve_cancel') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'annulee', annulation_demandee = 0 WHERE id = ? AND statut = 'confirmee'");
            $status = 'annulée';
            $message_admin = "La demande d'annulation a été approuvée. La réservation est annulée.";
        } elseif ($action === 'reject_cancel') {
            $stmt = $pdo->prepare("UPDATE reservations SET annulation_demandee = 0 WHERE id = ? AND statut = 'confirmee'");
            $status = 'maintenue';
            $message_admin = "La demande d'annulation a été refusée. La réservation reste confirmée.";
        } else {
            throw new Exception("Action non reconnue");
        }
        
        $stmt->execute([$reservation_id]);
        
        // Récupérer les informations sur la réservation et l'utilisateur pour l'email
        $stmt = $pdo->prepare("
            SELECT r.*, u.nom, u.prenom, u.email, c.nom AS nom_chambre, c.prix 
            FROM reservations r
            INNER JOIN users u ON r.user_id = u.id
            INNER JOIN chambres c ON r.chambre_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Calculer la durée du séjour
            $arrivee = new DateTime($reservation['date_arrivee']);
            $depart = new DateTime($reservation['date_depart']);
            $duree = $depart->diff($arrivee)->days;
            
            // Calculer le prix total
            $prix_total = $reservation['prix'] * $duree;
            
            // Préparer le contenu de l'email
            $to = $reservation['email'];
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
                        <p>Bonjour " . htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']) . ",</p>";
                
            if ($action === 'confirm') {
                $message .= "
                        <p>Nous avons le plaisir de vous informer que votre demande de réservation a été <span class='status-confirmed'>confirmée</span>.</p>
                        <p>Nous nous réjouissons de vous accueillir prochainement dans notre établissement.</p>";
            } elseif ($action === 'reject') {
                $message .= "
                        <p>Nous sommes désolés de vous informer que votre demande de réservation a été <span class='status-rejected'>rejetée</span>.</p>
                        <p>Cela peut être dû à un problème de disponibilité ou à d'autres contraintes. N'hésitez pas à nous contacter pour plus d'informations ou pour effectuer une nouvelle réservation à d'autres dates.</p>";
            } elseif ($action === 'approve_cancel') {
                $message .= "
                        <p>Votre demande d'annulation a été <span class='status-confirmed'>acceptée</span>.</p>
                        <p>Votre réservation est maintenant annulée.</p>";
            } elseif ($action === 'reject_cancel') {
                $message .= "
                        <p>Votre demande d'annulation a été <span class='status-rejected'>refusée</span>.</p>
                        <p>Votre réservation reste confirmée.</p>";
            }
            
            $message .= "
                        <div class='reservation-details'>
                            <h3>Détails de votre réservation :</h3>
                            <p><strong>Numéro de réservation :</strong> " . $reservation_id . "</p>
                            <p><strong>Chambre :</strong> " . htmlspecialchars($reservation['nom_chambre']) . "</p>
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
        }
        
        $_SESSION['message'] = $message_admin;
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors du traitement de la réservation : " . $e->getMessage();
    }
    
    // Redirection pour éviter la resoumission du formulaire
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Filtres pour les réservations
$statut_filtre = $_GET['statut'] ?? 'all';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Construction de la requête SQL avec filtres
$sql = "
    SELECT r.*, u.nom AS user_nom, u.prenom AS user_prenom, c.nom AS chambre_nom, c.prix
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    WHERE 1=1
";

$params = [];

if ($statut_filtre !== 'all') {
    $sql .= " AND r.statut = ?";
    $params[] = $statut_filtre;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date_arrivee >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date_arrivee <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY r.date_creation DESC";

// Récupération des réservations
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Erreur lors de la récupération des réservations: ' . $e->getMessage() . '</div>';
    $reservations = [];
}

// Statistiques
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'confirmee' => 0,
    'annulee' => 0
];

try {
    $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM reservations GROUP BY statut");
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        $stats[$row['statut']] = $row['count'];
        $stats['total'] += $row['count'];
    }
} catch (PDOException $e) {
    // Ignorer l'erreur
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des réservations - Administration - Hôtel Neptune</title>
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
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
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

        .status-annulation_demandee {
            background-color: #6f42c1;
            color: white;
        }
        
        .nav-badge {
            position: absolute;
            top: 8px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 10px;
        }
        
        .nav-item {
            position: relative;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stats-total {
            background: linear-gradient(45deg, #3498db, #2c3e50);
        }
        
        .stats-pending {
            background: linear-gradient(45deg, #f39c12, #d35400);
        }
        
        .stats-confirmed {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        
        .stats-canceled {
            background: linear-gradient(45deg, #7f8c8d, #34495e);
        }
        
        .stats-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }

        .filters {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
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
                        <?php 
                        // Récupération du nombre de messages non lus pour le badge de navigation
                        try {
                            $stmt_msg = $pdo->query("SELECT COUNT(*) FROM messages WHERE lu = 0");
                            $nb_messages_non_lus = $stmt_msg->fetchColumn();
                            if ($nb_messages_non_lus > 0): 
                        ?>
                            <span class="nav-badge"><?= $nb_messages_non_lus ?></span>
                        <?php 
                            endif;
                        } catch (PDOException $e) {
                            // Ignorer l'erreur si la table n'existe pas
                        }
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> Voir le site
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
                <h1><i class="fas fa-calendar-check me-2"></i> Gestion des réservations</h1>
                <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                    <i class="fas fa-bars me-1"></i> Menu
                </button>
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
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card stats-total">
                        <div><i class="fas fa-calendar-alt fa-2x"></i></div>
                        <div class="stats-value"><?= $stats['total'] ?></div>
                        <div>Total des réservations</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-pending">
                        <div><i class="fas fa-hourglass-half fa-2x"></i></div>
                        <div class="stats-value"><?= $stats['en_attente'] ?? 0 ?></div>
                        <div>En attente</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-confirmed">
                        <div><i class="fas fa-check-circle fa-2x"></i></div>
                        <div class="stats-value"><?= $stats['confirmee'] ?? 0 ?></div>
                        <div>Confirmées</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-canceled">
                        <div><i class="fas fa-times-circle fa-2x"></i></div>
                        <div class="stats-value"><?= ($stats['annulee'] ?? 0) + ($stats['rejetee'] ?? 0) ?></div>
                        <div>Annulées/Rejetées</div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="filters">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="statut" class="form-label">Statut</label>
                        <select id="statut" name="statut" class="form-select">
                            <option value="all" <?= $statut_filtre === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                            <option value="en_attente" <?= $statut_filtre === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="confirmee" <?= $statut_filtre === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                            <option value="rejetee" <?= $statut_filtre === 'rejetee' ? 'selected' : '' ?>>Rejetée</option>
                            <option value="annulee" <?= $statut_filtre === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= $date_debut ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= $date_fin ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filtrer
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Liste des réservations -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list me-2"></i> Liste des réservations
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Aucune réservation trouvée.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Chambre</th>
                                        <th>Arrivée</th>
                                        <th>Départ</th>
                                        <th>Personnes</th>
                                        <th>Prix</th>
                                        <th>Statut</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($reservations as $reservation): 
                                        // Calcul du prix total
                                        $date_arrivee = new DateTime($reservation['date_arrivee']);
                                        $date_depart = new DateTime($reservation['date_depart']);
                                        $nuits = $date_depart->diff($date_arrivee)->days;
                                        $prix_total = $nuits * $reservation['prix'];
                                    ?>
                                    <tr>
                                        <td><?= $reservation['id'] ?></td>
                                        <td><?= htmlspecialchars($reservation['user_prenom'] . ' ' . $reservation['user_nom']) ?></td>
                                        <td><?= htmlspecialchars($reservation['chambre_nom']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($reservation['date_arrivee'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($reservation['date_depart'])) ?></td>
                                        <td><?= $reservation['nombre_personnes'] ?></td>
                                        <td><?= number_format($prix_total, 2, ',', ' ') ?> €</td>
                                        <td>
                                            <?php
                                            $statut_class = '';
                                            $statut_text = '';
                                            
                                            switch($reservation['statut']) {
                                                case 'en_attente':
                                                    $statut_class = 'status-en_attente';
                                                    $statut_text = 'En attente';
                                                    break;
                                                case 'confirmee':
                                                    if ((int)($reservation['annulation_demandee'] ?? 0) === 1) {
                                                        $statut_class = 'status-annulation_demandee';
                                                        $statut_text = 'Annulation demandée';
                                                    } else {
                                                        $statut_class = 'status-confirmee';
                                                        $statut_text = 'Confirmée';
                                                    }
                                                    break;
                                                case 'rejetee':
                                                    $statut_class = 'status-rejetee';
                                                    $statut_text = 'Rejetée';
                                                    break;
                                                case 'annulee':
                                                    $statut_class = 'status-annulee';
                                                    $statut_text = 'Annulée';
                                                    break;
                                                default:
                                                    $statut_class = '';
                                                    $statut_text = $reservation['statut'];
                                            }
                                            ?>
                                            <span class="status-badge <?= $statut_class ?>"><?= $statut_text ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($reservation['date_creation'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="reservation_details.php?id=<?= $reservation['id'] ?>" class="btn btn-info" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($reservation['statut'] === 'en_attente'): ?>
                                                    <form action="" method="post" class="d-inline">
                                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                                        <input type="hidden" name="action" value="confirm">
                                                        <button type="submit" class="btn btn-success" title="Confirmer"
                                                                onclick="return confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="" method="post" class="d-inline">
                                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger" title="Rejeter"
                                                                onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette réservation ?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($reservation['statut'] === 'confirmee' && (int)($reservation['annulation_demandee'] ?? 0) === 1): ?>
                                                    <form action="" method="post" class="d-inline">
                                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                                        <input type="hidden" name="action" value="approve_cancel">
                                                        <button type="submit" class="btn btn-warning" title="Approuver annulation"
                                                                onclick="return confirm('Approuver la demande annulation de cette réservation ?')">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    </form>

                                                    <form action="" method="post" class="d-inline">
                                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                                        <input type="hidden" name="action" value="reject_cancel">
                                                        <button type="submit" class="btn btn-secondary" title="Refuser annulation"
                                                                onclick="return confirm('Refuser la demande annulation et maintenir la réservation confirmée ?')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 