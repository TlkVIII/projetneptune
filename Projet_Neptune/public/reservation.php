<?php
session_start();
require_once '../config/database.php';

// Initialisation de $chambres comme tableau vide
$chambres = [];

// Récupération des chambres disponibles
try {
    // Requête simplifiée pour récupérer uniquement les données de base des chambres
    $stmt = $pdo->query("SELECT * FROM chambres WHERE disponible = 1");
    $chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($chambres)) {
        $error = "Aucune chambre disponible pour le moment.";
    }
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des chambres : " . $e->getMessage();
}

// Récupérer le paramètre chambre_id de l'URL s'il existe
$chambre_id_preselection = isset($_GET['chambre_id']) ? (int)$_GET['chambre_id'] : null;

// Vérification de disponibilité en temps réel (AJAX)
if (isset($_GET['check_availability']) && $_GET['check_availability'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $chambreId = (int)($_GET['chambre_id'] ?? 0);
    $dateArrivee = trim($_GET['date_arrivee'] ?? '');
    $dateDepart = trim($_GET['date_depart'] ?? '');

    if ($chambreId <= 0 || empty($dateArrivee) || empty($dateDepart)) {
        echo json_encode([
            'ok' => false,
            'available' => false,
            'message' => 'Sélectionnez une chambre et des dates valides.'
        ]);
        exit;
    }

    if (strtotime($dateDepart) <= strtotime($dateArrivee)) {
        echo json_encode([
            'ok' => true,
            'available' => false,
            'message' => 'La date de départ doit être après la date d\'arrivée.'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reservations
            WHERE chambre_id = ?
              AND statut IN ('en_attente', 'confirmee')
              AND (
                    (date_arrivee <= ? AND date_depart >= ?)
                 OR (date_arrivee <= ? AND date_depart >= ?)
                 OR (date_arrivee >= ? AND date_depart <= ?)
              )
        ");
        $stmt->execute([$chambreId, $dateArrivee, $dateArrivee, $dateDepart, $dateDepart, $dateArrivee, $dateDepart]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            echo json_encode([
                'ok' => true,
                'available' => false,
                'message' => 'Les dates sélectionnées sont déjà prises ou en attente pour cette chambre.'
            ]);
        } else {
            echo json_encode([
                'ok' => true,
                'available' => true,
                'message' => 'Cette chambre est disponible pour les dates sélectionnées.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'ok' => false,
            'available' => false,
            'message' => 'Erreur lors de la vérification de disponibilité.'
        ]);
    }
    exit;
}

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['reservation_error'] = "Vous devez être connecté pour effectuer une réservation.";
        header('Location: login.php');
        exit;
    }

    $chambre_id = $_POST['chambre_id'] ?? '';
    $date_arrivee = $_POST['date_arrivee'] ?? '';
    $date_depart = $_POST['date_depart'] ?? '';
    $nombre_personnes = $_POST['nombre_personnes'] ?? '';
    $demandes_speciales = $_POST['demandes_speciales'] ?? '';
    
    // Validation des données
    $errors = [];
    if (empty($chambre_id)) $errors[] = "Veuillez sélectionner une chambre";
    if (empty($date_arrivee)) $errors[] = "La date d'arrivée est requise";
    if (empty($date_depart)) $errors[] = "La date de départ est requise";
    if (empty($nombre_personnes)) $errors[] = "Le nombre de personnes est requis";
    if (!empty($date_arrivee) && !empty($date_depart) && strtotime($date_depart) <= strtotime($date_arrivee)) {
        $errors[] = "La date de départ doit être après la date d'arrivée";
    }
    
    if (empty($errors)) {
        try {
            // Vérification de la disponibilité de la chambre
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservations 
                WHERE chambre_id = ? 
                AND statut IN ('en_attente', 'confirmee')
                AND (
                    (date_arrivee <= ? AND date_depart >= ?)
                    OR (date_arrivee <= ? AND date_depart >= ?)
                    OR (date_arrivee >= ? AND date_depart <= ?)
                )
            ");
            $stmt->execute([$chambre_id, $date_arrivee, $date_arrivee, $date_depart, $date_depart, $date_arrivee, $date_depart]);
            $reservations_existantes = $stmt->fetchColumn();

            if ($reservations_existantes > 0) {
                $errors[] = "La chambre n'est pas disponible pour les dates sélectionnées";
            } else {
                $user_id = (int) $_SESSION['user_id'];
                
                // Insertion de la réservation
                $stmt = $pdo->prepare("
                    INSERT INTO reservations (
                        user_id, chambre_id, date_arrivee, date_depart, 
                        nombre_personnes, demandes_speciales, statut
                    ) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')
                ");
                
                $stmt->execute([
                    $user_id,
                    $chambre_id,
                    $date_arrivee,
                    $date_depart,
                    $nombre_personnes,
                    $demandes_speciales
                ]);
                
                $reservation_id = $pdo->lastInsertId();
                
                // Récupérer les informations de l'utilisateur pour l'email
                $stmt = $pdo->prepare("SELECT nom, prenom, email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Récupérer les informations de la chambre
                $stmt = $pdo->prepare("SELECT nom, prix FROM chambres WHERE id = ?");
                $stmt->execute([$chambre_id]);
                $chambre = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculer la durée du séjour
                $arrivee = new DateTime($date_arrivee);
                $depart = new DateTime($date_depart);
                $duree = $depart->diff($arrivee)->days;
                
                // Calculer le prix total
                $prix_total = $chambre['prix'] * $duree;
                
                // Préparer le contenu de l'email
                $to = $user['email'];
                $subject = "Confirmation de votre demande de réservation - Hôtel Neptune";
                
                $message = "
                <html>
                <head>
                <title>Confirmation de demande de réservation</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .reservation-details { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; font-size: 0.8em; color: #777; }
                    .button { background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Hôtel Neptune</h1>
                            <p>Confirmation de demande de réservation</p>
                        </div>
                        <div class='content'>
                            <p>Bonjour " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . ",</p>
                            <p>Nous avons bien reçu votre demande de réservation. Elle est actuellement <strong>en attente de confirmation</strong> par notre équipe.</p>
                            
                            <div class='reservation-details'>
                                <h3>Détails de votre réservation :</h3>
                                <p><strong>Chambre :</strong> " . htmlspecialchars($chambre['nom']) . "</p>
                                <p><strong>Date d'arrivée :</strong> " . date('d/m/Y', strtotime($date_arrivee)) . "</p>
                                <p><strong>Date de départ :</strong> " . date('d/m/Y', strtotime($date_depart)) . "</p>
                                <p><strong>Nombre de nuits :</strong> " . $duree . "</p>
                                <p><strong>Nombre de personnes :</strong> " . $nombre_personnes . "</p>
                                <p><strong>Prix par nuit :</strong> " . number_format($chambre['prix'], 2, ',', ' ') . " €</p>
                                <p><strong>Prix total estimé :</strong> " . number_format($prix_total, 2, ',', ' ') . " €</p>";
                
                if (!empty($demandes_speciales)) {
                    $message .= "<p><strong>Demandes spéciales :</strong> " . htmlspecialchars($demandes_speciales) . "</p>";
                }
                
                $message .= "
                            </div>
                            
                            <p>Un membre de notre équipe examinera votre demande dans les plus brefs délais et vous enverra une confirmation définitive.</p>
                            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                            
                            <p>Vous pouvez consulter l'état de votre réservation à tout moment en vous connectant à votre compte sur notre site.</p>
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
                
                // Tentative d'envoi d'email
                try {
                    // Si en développement local, enregistrer dans un fichier log au lieu d'envoyer un email
                    // Pour la production, remplacer par l'utilisation de PHPMailer ou autre bibliothèque
                    $log_message = "==== " . date('Y-m-d H:i:s') . " ====\n";
                    $log_message .= "Réservation #" . $reservation_id . " créée\n";
                    $log_message .= "Utilisateur: " . $user_id . "\n";
                    $log_message .= "Chambre: " . $chambre_id . "\n";
                    $log_message .= "Dates: " . $date_arrivee . " au " . $date_depart . "\n";
                    $log_message .= "Statut: en_attente\n\n";
                    
                    // Créer le dossier logs s'il n'existe pas
                    if (!file_exists('../logs')) {
                        mkdir('../logs', 0777, true);
                    }
                    
                    // Enregistrer dans un fichier
                    file_put_contents('../logs/reservations.log', $log_message, FILE_APPEND);
                    
                    // Redirection avec message de succès
                    $_SESSION['reservation_success'] = true;
                    header('Location: reservation_confirmation.php?id=' . $reservation_id);
                    exit;
                } catch (Exception $e) {
                    // En cas d'erreur, on continue quand même - l'email n'est pas critique
                    // La réservation a bien été enregistrée en base de données
                    $_SESSION['reservation_success'] = true;
                    header('Location: reservation_confirmation.php?id=' . $reservation_id);
                    exit;
                }
            }
        } catch(Exception $e) {
            $errors[] = "Erreur lors de la réservation : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            padding-top: 76px;
            background: #f4f7fb;
        }

        .hero-section {
            background: linear-gradient(120deg, rgba(30, 51, 74, 0.82), rgba(52, 152, 219, 0.52)), url('images/slider/facade1.jpeg');
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

        .hero-section h1 {
            font-weight: 700;
            letter-spacing: 0.3px;
            text-shadow: 0 8px 22px rgba(0, 0, 0, 0.25);
        }

        .hero-section .lead {
            max-width: 680px;
            margin: 0 auto;
            opacity: 0.95;
        }

        .reservation-form {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border-radius: 18px;
            padding: 2.1rem;
            border: 1px solid #e6edf7;
            box-shadow: 0 18px 44px rgba(44, 62, 80, 0.12);
            overflow: hidden;
        }

        .reservation-form h2 {
            color: var(--primary-color);
            font-weight: 700;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #dbe5f1;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .room-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .room-image {
            height: 200px;
            object-fit: cover;
        }

        .room-price {
            color: var(--accent-color);
            font-size: 1.2rem;
            font-weight: bold;
        }

        .room-type-selector {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .room-type-option {
            position: relative;
            min-width: 0;
        }

        .room-type-card {
            display: block;
            padding: 1rem 1rem 0.95rem;
            border: 1px solid #e1e8f2;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
            min-width: 0;
        }

        .room-type-card:hover {
            border-color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(52, 152, 219, 0.15);
        }

        .room-type-card::before {
            content: '';
            position: absolute;
            left: -10px;
            top: -10px;
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            transform: rotate(45deg);
            opacity: 0;
            transition: all 0.3s ease;
        }

        input[type="radio"]:checked + .room-type-card {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.08);
            box-shadow: 0 12px 28px rgba(52, 152, 219, 0.2);
        }

        input[type="radio"]:checked + .room-type-card::before {
            opacity: 1;
        }

        .room-type-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.35rem;
            margin-bottom: 0.6rem;
            min-width: 0;
        }

        .room-type-header h5 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.02rem;
            line-height: 1.25;
            word-break: break-word;
        }

        .room-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 0.98rem;
            white-space: nowrap;
            background: rgba(231, 76, 60, 0.08);
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
        }

        .room-type-capacity {
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .room-type-capacity i {
            margin-right: 0.5rem;
            color: var(--secondary-color);
        }

        @media (max-width: 991px) {
            .room-type-selector {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        @media (max-width: 767px) {
            .reservation-form {
                padding: 1.35rem;
            }
        }

        .number-input-container {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .number-input-container .form-control {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 500;
            height: calc(3rem + 2px);
            border-radius: 10px;
            margin-right: 0.5rem;
        }

        .number-controls {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .number-controls .btn {
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .form-label.fw-bold {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .reservation-sidebar .card {
            border: 1px solid #e6edf7;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(44, 62, 80, 0.12);
        }

        .reservation-sidebar .card-title {
            color: var(--primary-color);
            font-weight: 700;
        }

        .reservation-sidebar {
            position: sticky;
            top: 95px;
        }

        @media (max-width: 991px) {
            .reservation-sidebar {
                position: static;
                margin-top: 1.2rem;
            }
        }

        #details-chambre .card {
            border-radius: 14px;
            border: 1px solid #e4ebf5;
            box-shadow: 0 10px 22px rgba(44, 62, 80, 0.1);
        }

        .alert {
            border-radius: 12px;
            border: 0;
        }

        #availability-message {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4">Réservez votre séjour</h1>
            <p class="lead">Profitez d'un séjour inoubliable à l'Hôtel Neptune</p>
        </div>
    </section>

    <!-- Réservation Section -->
    <section class="py-5">
        <div class="container">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-warning text-center">
                    <p>Vous devez être connecté pour effectuer une réservation.</p>
                    <a href="login.php" class="btn btn-primary">Se connecter</a>
                    <a href="register.php" class="btn btn-outline-primary ms-2">S'inscrire</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Formulaire de réservation -->
                    <div class="col-lg-8">
                        <div class="reservation-form">
                            <h2 class="mb-4">Formulaire de réservation</h2>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($success) && $success): ?>
                                <div class="alert alert-success">
                                    Votre demande de réservation a été enregistrée avec succès. Nous vous contacterons bientôt pour confirmer votre réservation.
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_arrivee">Date d'arrivée</label>
                                            <input type="text" class="form-control" id="date_arrivee" name="date_arrivee" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_depart">Date de départ</label>
                                            <input type="text" class="form-control" id="date_depart" name="date_depart" required>
                                        </div>
                                    </div>
                                </div>
                                <div id="availability-message" class="alert mt-2 mb-3"></div>
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="chambre_id" class="form-label fw-bold mb-3">Type de chambre</label>
                                            <div class="room-type-selector">
                                                <?php foreach ($chambres as $chambre): ?>
                                                    <div class="room-type-option" onclick="selectRoom(this, <?php echo $chambre['id']; ?>)">
                                                        <input type="radio" name="chambre_id" value="<?php echo $chambre['id']; ?>" 
                                                               id="chambre_<?php echo $chambre['id']; ?>"
                                                               data-nom="<?php echo htmlspecialchars($chambre['nom']); ?>"
                                                               data-prix="<?php echo $chambre['prix']; ?>"
                                                               data-capacite="<?php echo $chambre['capacite']; ?>"
                                                               data-services="<?php echo htmlspecialchars($chambre['services'] ?? ''); ?>"
                                                               data-equipements="<?php echo htmlspecialchars($chambre['equipements'] ?? ''); ?>"
                                                               data-description="<?php echo htmlspecialchars($chambre['description']); ?>"
                                                               style="display: none;">
                                                        <label for="chambre_<?php echo $chambre['id']; ?>" class="room-type-card">
                                                            <div class="room-type-header">
                                                                <h5>
                                                                <?php 
                                                                    $icon = '';
                                                                    if (strpos($chambre['nom'], 'Deluxe') !== false) {
                                                                        $icon = '<i class="fas fa-star text-warning me-2"></i>';
                                                                    } elseif (strpos($chambre['nom'], 'Familiale') !== false) {
                                                                        $icon = '<i class="fas fa-users text-primary me-2"></i>';
                                                                    } elseif (strpos($chambre['nom'], 'Présidentielle') !== false) {
                                                                        $icon = '<i class="fas fa-crown text-warning me-2"></i>';
                                                                    } elseif (strpos($chambre['nom'], 'VIP') !== false) {
                                                                        $icon = '<i class="fas fa-gem text-danger me-2"></i>';
                                                                    } elseif (strpos($chambre['nom'], 'Star') !== false) {
                                                                        $icon = '<i class="fas fa-star-of-life text-info me-2"></i>';
                                                                    }
                                                                    echo $icon . htmlspecialchars($chambre['nom']);
                                                                ?>
                                                                </h5>
                                                                <span class="room-price"><?php echo number_format($chambre['prix'], 2, ',', ' '); ?>€/nuit</span>
                                                            </div>
                                                            <div class="room-type-capacity">
                                                                <i class="fas fa-user-friends"></i> <?php echo $chambre['capacite']; ?> personnes max.
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Détails de la chambre sélectionnée -->
                                <div id="details-chambre" class="row mb-4" style="display: none;">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title" id="nom-chambre"></h5>
                                                <p class="card-text" id="description-chambre"></p>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="mt-3">Services inclus :</h6>
                                                        <ul class="list-unstyled" id="services-chambre"></ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="mt-3">Équipements :</h6>
                                                        <ul class="list-unstyled" id="equipements-chambre"></ul>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <p class="mb-1"><strong>Capacité :</strong> <span id="capacite-chambre"></span> personnes</p>
                                                    <p class="mb-1"><strong>Prix :</strong> <span id="prix-chambre"></span>€/nuit</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre_personnes" class="form-label fw-bold">Nombre de personnes</label>
                                            <div class="number-input-container">
                                                <input type="number" class="form-control form-control-lg" id="nombre_personnes" name="nombre_personnes" min="1" max="4" value="1" required>
                                                <div class="number-controls">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="changeNumber('nombre_personnes', -1)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="changeNumber('nombre_personnes', 1)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-text text-muted">Sélectionnez le nombre de personnes qui séjourneront dans la chambre</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <label for="demandes_speciales">Demandes spéciales</label>
                                    <textarea class="form-control" id="demandes_speciales" name="demandes_speciales" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-2">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer la demande
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Informations importantes -->
                    <div class="col-lg-4">
                        <div class="reservation-sidebar">
                        <div class="card border-0 shadow">
                            <div class="card-body">
                                <h3 class="card-title mb-4">Informations importantes</h3>
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <strong>Check-in :</strong> À partir de 14h00
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <strong>Check-out :</strong> Jusqu'à 12h00
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-credit-card text-primary me-2"></i>
                                        <strong>Paiement :</strong> À l'arrivée
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-ban text-primary me-2"></i>
                                        <strong>Annulation :</strong> Gratuite jusqu'à 24h avant
                                    </li>
                                </ul>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        const dateArriveeInput = document.getElementById("date_arrivee");
        const dateDepartInput = document.getElementById("date_depart");
        const submitButton = document.querySelector('form button[type="submit"]');
        const availabilityMessage = document.getElementById('availability-message');

        function setAvailabilityState(type, message) {
            if (!availabilityMessage) return;
            availabilityMessage.style.display = 'block';
            availabilityMessage.className = 'alert mt-2 mb-3';
            if (type === 'success') {
                availabilityMessage.classList.add('alert-success');
            } else if (type === 'warning') {
                availabilityMessage.classList.add('alert-warning');
            } else {
                availabilityMessage.classList.add('alert-danger');
            }
            availabilityMessage.textContent = message;
        }

        function clearAvailabilityState() {
            if (!availabilityMessage) return;
            availabilityMessage.style.display = 'none';
            availabilityMessage.textContent = '';
        }

        async function checkAvailabilityNow() {
            const selectedRoom = document.querySelector('.room-type-option input[type="radio"]:checked');
            const chambreId = selectedRoom ? selectedRoom.value : '';
            const dateArrivee = dateArriveeInput ? dateArriveeInput.value : '';
            const dateDepart = dateDepartInput ? dateDepartInput.value : '';

            if (submitButton) {
                submitButton.disabled = false;
            }

            if (!chambreId || !dateArrivee || !dateDepart) {
                clearAvailabilityState();
                return;
            }

            if (new Date(dateDepart) <= new Date(dateArrivee)) {
                if (submitButton) submitButton.disabled = true;
                setAvailabilityState('danger', 'La date de départ doit être après la date d\'arrivée.');
                return;
            }

            try {
                const url = `reservation.php?check_availability=1&chambre_id=${encodeURIComponent(chambreId)}&date_arrivee=${encodeURIComponent(dateArrivee)}&date_depart=${encodeURIComponent(dateDepart)}`;
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await response.json();

                if (!data.ok) {
                    if (submitButton) submitButton.disabled = true;
                    setAvailabilityState('danger', data.message || 'Erreur de vérification.');
                    return;
                }

                if (data.available) {
                    if (submitButton) submitButton.disabled = false;
                    setAvailabilityState('success', data.message);
                } else {
                    if (submitButton) submitButton.disabled = true;
                    setAvailabilityState('warning', data.message);
                }
            } catch (e) {
                if (submitButton) submitButton.disabled = true;
                setAvailabilityState('danger', 'Impossible de vérifier la disponibilité pour le moment.');
            }
        }

        let availabilityTimer = null;
        function scheduleAvailabilityCheck() {
            if (availabilityTimer) clearTimeout(availabilityTimer);
            availabilityTimer = setTimeout(checkAvailabilityNow, 250);
        }

        flatpickr("#date_arrivee", {
            dateFormat: "Y-m-d",
            minDate: "today",
            locale: "fr",
            onChange: function(selectedDates, dateStr, instance) {
                // Mettre à jour la date minimum pour le départ
                const departInstance = flatpickr("#date_depart", {});
                departInstance.set("minDate", dateStr);
                
                // Si la date de départ est antérieure à la date d'arrivée, la réinitialiser
                if (new Date(departInstance.input.value) <= new Date(dateStr)) {
                    // Ajouter un jour à la date d'arrivée pour la date de départ
                    const nextDay = new Date(selectedDates[0]);
                    nextDay.setDate(nextDay.getDate() + 1);
                    departInstance.setDate(nextDay);
                }
                scheduleAvailabilityCheck();
            }
        });
        
        flatpickr("#date_depart", {
            dateFormat: "Y-m-d",
            minDate: document.getElementById("date_arrivee").value || "today",
            locale: "fr",
            onChange: function() {
                scheduleAvailabilityCheck();
            }
        });

        function afficherDetailsChambre(chambreId) {
            const detailsDiv = document.getElementById('details-chambre');
            const radio = document.getElementById('chambre_' + chambreId);
            
            if (radio && radio.checked) {
                detailsDiv.style.display = 'block';
                document.getElementById('nom-chambre').textContent = radio.getAttribute('data-nom');
                document.getElementById('description-chambre').textContent = radio.getAttribute('data-description');
                document.getElementById('capacite-chambre').textContent = radio.getAttribute('data-capacite');
                document.getElementById('prix-chambre').textContent = radio.getAttribute('data-prix');

                // Vérifier si des services sont disponibles
                const servicesData = radio.getAttribute('data-services');
                const servicesSection = document.getElementById('services-chambre').parentElement.parentElement;
                
                if (servicesData && servicesData.trim() !== '') {
                    servicesSection.style.display = 'block';
                    // Afficher les services
                    const servicesList = document.getElementById('services-chambre');
                    servicesList.innerHTML = '';
                    const services = servicesData.split(',');
                    services.forEach(service => {
                        if (service && service.trim()) {
                            const li = document.createElement('li');
                            li.innerHTML = `<i class="fas fa-check text-success me-2"></i>${service.trim()}`;
                            servicesList.appendChild(li);
                        }
                    });
                } else {
                    servicesSection.style.display = 'none';
                }

                // Vérifier si des équipements sont disponibles
                const equipementsData = radio.getAttribute('data-equipements');
                const equipementsSection = document.getElementById('equipements-chambre').parentElement.parentElement;
                
                if (equipementsData && equipementsData.trim() !== '') {
                    equipementsSection.style.display = 'block';
                    // Afficher les équipements
                    const equipementsList = document.getElementById('equipements-chambre');
                    equipementsList.innerHTML = '';
                    const equipements = equipementsData.split(',');
                    equipements.forEach(equipement => {
                        if (equipement && equipement.trim()) {
                            const li = document.createElement('li');
                            li.innerHTML = `<i class="fas fa-check text-success me-2"></i>${equipement.trim()}`;
                            equipementsList.appendChild(li);
                        }
                    });
                } else {
                    equipementsSection.style.display = 'none';
                }

                // Mettre à jour le nombre maximum de personnes
                document.getElementById('nombre_personnes').max = radio.getAttribute('data-capacite');
            } else {
                detailsDiv.style.display = 'none';
            }
        }

        function selectRoom(element, chambreId) {
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
            afficherDetailsChambre(chambreId);
            scheduleAvailabilityCheck();
        }

        function changeNumber(inputId, delta) {
            const input = document.getElementById(inputId);
            const currentValue = parseInt(input.value) || 0;
            const newValue = currentValue + delta;
            
            if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
                input.value = newValue;
                
                // Déclencher un événement de changement pour mettre à jour les validations
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        }

        // Sélectionner automatiquement la chambre si un ID est spécifié dans l'URL
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($chambre_id_preselection): ?>
            const preselectionElement = document.querySelector(`.room-type-option input[value="<?php echo $chambre_id_preselection; ?>"]`);
            if (preselectionElement) {
                const parentElement = preselectionElement.closest('.room-type-option');
                if (parentElement) {
                    selectRoom(parentElement, <?php echo $chambre_id_preselection; ?>);
                    // Scroll jusqu'à l'élément sélectionné
                    parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            <?php endif; ?>

            if (dateArriveeInput) {
                dateArriveeInput.addEventListener('change', scheduleAvailabilityCheck);
            }
            if (dateDepartInput) {
                dateDepartInput.addEventListener('change', scheduleAvailabilityCheck);
            }
        });
    </script>
</body>
</html> 