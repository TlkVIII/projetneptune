<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de réservation
$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reservation_id) {
    header('Location: reservations.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation = null;
$chambre = null;
$user = null;

try {
    // Récupération des détails de la réservation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as chambre_nom, c.prix, c.image, c.description
        FROM reservations r
        JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si la réservation n'existe pas ou n'appartient pas à l'utilisateur connecté
    if (!$reservation) {
        header('Location: reservations.php');
        exit;
    }
    
    // Récupération des informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Calcul de la durée du séjour
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$duree_sejour = $date_depart->diff($date_arrivee)->days;

// Calcul du montant total
$prix_total = $duree_sejour * $reservation['prix'];

// Formatage des dates
$date_arrivee_fr = $date_arrivee->format('d/m/Y');
$date_depart_fr = $date_depart->format('d/m/Y');

// Générer un numéro de facture unique
$numero_facture = 'FCT-' . date('Y') . '-' . str_pad($reservation_id, 5, '0', STR_PAD_LEFT);

// Date d'émission de la facture
$date_emission = date('d/m/Y');

// Pour les besoins de l'exemple, définir une taxe de séjour
$taxe_sejour_par_jour = 2.50; // 2.50€ par nuit et par personne
$montant_taxe_sejour = $duree_sejour * $taxe_sejour_par_jour * $reservation['nombre_personnes'];

// Calcul du montant TTC (avec TVA à 10% pour l'hébergement)
$tva_taux = 10; // 10%
$montant_ht = $prix_total / (1 + ($tva_taux / 100));
$montant_tva = $prix_total - $montant_ht;

// Montant total à payer
$montant_total = $prix_total + $montant_taxe_sejour;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?php echo $numero_facture; ?> - Hôtel Neptune</title>
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
        
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .invoice-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .invoice-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .invoice-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .client-details, .hotel-details {
            padding: 1rem 0;
        }
        
        .table-items thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table-items th, .table-items td {
            padding: 1rem;
        }
        
        .amount-due {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .payment-info {
            border-top: 2px solid #eee;
            margin-top: 2rem;
            padding-top: 1.5rem;
        }
        
        .footer-note {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .status-confirmee {
            background-color: #28a745;
            color: white;
        }
        
        .status-en_attente {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-annulee {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-print {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media print {
            body {
                padding-top: 0;
                background-color: white;
            }
            
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .invoice-title {
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="no-print">
        <?php include 'includes/header.php'; ?>
    </div>

    <div class="container invoice-container" id="invoice">
        <div class="row invoice-header">
            <div class="col-md-6">
                <div class="logo">
                    <i class="fas fa-hotel me-2"></i>Hôtel Neptune
                </div>
                <p>Montpellier, France 34000<br>
                Tél: 0695396132<br>
                fayed.amourani8@gmail.com</p>
            </div>
            <div class="col-md-6 text-md-end">
                <h1 class="invoice-title"><?php echo ($reservation['statut'] === 'confirmee') ? 'FACTURE' : 'PRO-FORMA'; ?></h1>
                <div>
                    <span class="status-badge status-<?php echo $reservation['statut']; ?>">
                        <?php
                        switch($reservation['statut']) {
                            case 'en_attente':
                                echo 'En attente de confirmation';
                                break;
                            case 'confirmee':
                                echo 'Réservation confirmée';
                                break;
                            case 'annulee':
                                echo 'Réservation annulée';
                                break;
                        }
                        ?>
                    </span>
                </div>
                <p><strong>N° <?php echo $numero_facture; ?></strong><br>
                Date d'émission: <?php echo $date_emission; ?><br>
                N° Réservation: <?php echo $reservation_id; ?></p>
            </div>
        </div>

        <div class="row invoice-details">
            <div class="col-md-6 client-details">
                <h5>Facturé à</h5>
                <p>
                    <strong><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong><br>
                    <?php echo htmlspecialchars($user['adresse'] ?? ''); ?><br>
                    <?php echo htmlspecialchars(($user['code_postal'] ?? '') . ' ' . ($user['ville'] ?? '')); ?><br>
                    <?php echo htmlspecialchars($user['pays'] ?? ''); ?><br>
                    Email: <?php echo htmlspecialchars($user['email']); ?><br>
                    Tél: <?php echo htmlspecialchars($user['telephone'] ?? 'Non spécifié'); ?>
                </p>
            </div>
            <div class="col-md-6 hotel-details">
                <h5>Détails du séjour</h5>
                <p>
                    <strong>Arrivée:</strong> <?php echo $date_arrivee_fr; ?> (à partir de 14h00)<br>
                    <strong>Départ:</strong> <?php echo $date_depart_fr; ?> (jusqu'à 12h00)<br>
                    <strong>Durée:</strong> <?php echo $duree_sejour; ?> nuit<?php echo ($duree_sejour > 1) ? 's' : ''; ?><br>
                    <strong>Nombre de personnes:</strong> <?php echo $reservation['nombre_personnes']; ?>
                </p>
            </div>
        </div>

        <table class="table table-items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-end">Prix unitaire</th>
                    <th class="text-end">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($reservation['chambre_nom']); ?></strong><br>
                        <?php echo htmlspecialchars(substr($reservation['description'], 0, 100) . '...'); ?>
                    </td>
                    <td class="text-center"><?php echo $duree_sejour; ?> nuit<?php echo ($duree_sejour > 1) ? 's' : ''; ?></td>
                    <td class="text-end"><?php echo number_format($reservation['prix'], 2, ',', ' '); ?>€</td>
                    <td class="text-end"><?php echo number_format($prix_total, 2, ',', ' '); ?>€</td>
                </tr>
                <tr>
                    <td>Taxe de séjour</td>
                    <td class="text-center"><?php echo $duree_sejour; ?> nuit<?php echo ($duree_sejour > 1) ? 's' : ''; ?> x <?php echo $reservation['nombre_personnes']; ?> pers.</td>
                    <td class="text-end"><?php echo number_format($taxe_sejour_par_jour, 2, ',', ' '); ?>€</td>
                    <td class="text-end"><?php echo number_format($montant_taxe_sejour, 2, ',', ' '); ?>€</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" rowspan="4"></td>
                    <th class="text-end">Montant HT</th>
                    <td class="text-end"><?php echo number_format($montant_ht, 2, ',', ' '); ?>€</td>
                </tr>
                <tr>
                    <th class="text-end">TVA (<?php echo $tva_taux; ?>%)</th>
                    <td class="text-end"><?php echo number_format($montant_tva, 2, ',', ' '); ?>€</td>
                </tr>
                <tr>
                    <th class="text-end">Taxe de séjour</th>
                    <td class="text-end"><?php echo number_format($montant_taxe_sejour, 2, ',', ' '); ?>€</td>
                </tr>
                <tr>
                    <th class="text-end">Montant total</th>
                    <td class="text-end amount-due"><?php echo number_format($montant_total, 2, ',', ' '); ?>€</td>
                </tr>
            </tfoot>
        </table>

        <div class="payment-info">
            <h5>Informations de paiement</h5>
            <?php if ($reservation['statut'] === 'confirmee'): ?>
                <p>Le montant total sera à régler à l'arrivée. Nous acceptons les cartes de crédit (Visa, Mastercard, American Express) et les espèces.</p>
            <?php else: ?>
                <p>Cette facture pro-forma est fournie à titre informatif. Le paiement ne sera exigé qu'après confirmation de votre réservation. Aucun montant n'a été débité pour le moment.</p>
            <?php endif; ?>
            
            <p><strong>Politique d'annulation:</strong> Annulation gratuite jusqu'à 24 heures avant la date d'arrivée. Au-delà, 100% du montant de la première nuit sera facturé.</p>
        </div>

        <div class="footer-note">
            <p>Hôtel Neptune - SIRET: 123 456 789 00012 - TVA: FR12345678900<br>
            Merci de votre confiance et au plaisir de vous accueillir.</p>
        </div>

        <div class="d-flex justify-content-between mt-4 no-print">
            <a href="reservations.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour aux réservations
            </a>
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Imprimer
            </button>
        </div>
    </div>
</body>
</html> 