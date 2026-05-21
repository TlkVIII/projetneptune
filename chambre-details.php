<?php
session_start();
require_once '../config/database.php';

// Définir le titre de la page
$page_title = "Détails de la chambre";

// Vérifier si l'ID de la chambre est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: chambres.php');
    exit;
}

$chambre_id = $_GET['id'];

// Récupérer les détails de la chambre
try {
    $stmt = $pdo->prepare("SELECT * FROM chambres WHERE id = ?");
    $stmt->execute([$chambre_id]);
    $chambre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chambre) {
        header('Location: chambres.php');
        exit;
    }
    
    // Mettre à jour le titre de la page avec le nom de la chambre
    $page_title = htmlspecialchars($chambre['nom']);

    $mainImage = $chambre['image'] ?? '';
    if (empty($mainImage) || !file_exists(__DIR__ . '/images/rooms/' . $mainImage)) {
        $mainImage = 'cstandard1.jpeg';
    }
    
    // Récupérer les images supplémentaires de la chambre (si disponibles)
    $images = [];
    // Si une colonne images existe dans la base de données, on pourrait la récupérer ici
    // Sinon, on peut avoir une convention de nommage pour les images
    $base_image = pathinfo($mainImage, PATHINFO_FILENAME);
    $ext = pathinfo($mainImage, PATHINFO_EXTENSION);
    
    // L'image principale
    $images[] = $mainImage;
    
    // Chercher des images supplémentaires potentielles (jusqu'à 3 images supplémentaires)
    for ($i = 2; $i <= 4; $i++) {
        $potential_image = $base_image . $i . '.' . $ext;
        if (file_exists('images/rooms/' . $potential_image)) {
            $images[] = $potential_image;
        }
    }
    
} catch(PDOException $e) {
    $error = "Une erreur est survenue lors de la récupération des détails de la chambre";
}

// Styles additionnels spécifiques à cette page
$additional_styles = '
<style>
    .gallery-image {
        height: 400px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .thumbnail {
        cursor: pointer;
        height: 80px;
        object-fit: cover;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .thumbnail.active {
        border: 3px solid var(--secondary-color);
    }
    
    .room-amenities {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 20px 0;
    }
    
    .amenity-badge {
        background-color: #f8f9fa;
        color: var(--primary-color);
        padding: 8px 15px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        font-size: 0.9rem;
    }
    
    .amenity-badge i {
        color: var(--secondary-color);
        margin-right: 8px;
    }
    
    .room-detail-section {
        margin-bottom: 30px;
    }
    
    .price-card {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .price-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--accent-color);
    }
    
    .room-description {
        line-height: 1.8;
    }
    
    .room-policy {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
    }
    
    .action-buttons {
        display: grid;
        gap: 10px;
    }
</style>';

// Inclure l'en-tête
include 'includes/header.php';
?>

<!-- Hero Section avec l'image principale de la chambre -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="chambres.php">Chambres</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($chambre['nom']) ?></li>
            </ol>
        </nav>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php else: ?>
            <div class="row">
                <!-- Galerie d'images -->
                <div class="col-lg-7 mb-4">
                    <div class="mb-3">
                        <img id="main-image" src="images/rooms/<?= htmlspecialchars($mainImage) ?>" 
                             class="img-fluid w-100 gallery-image" 
                             alt="<?= htmlspecialchars($chambre['nom']) ?>">
                    </div>
                    <div class="row">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="col-3 mb-3">
                                <img src="images/rooms/<?= htmlspecialchars($image) ?>" 
                                     class="img-fluid thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                                     alt="<?= htmlspecialchars($chambre['nom']) ?>"
                                     onclick="changeMainImage(this, 'images/rooms/<?= htmlspecialchars($image) ?>')">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Détails de la chambre et réservation -->
                <div class="col-lg-5">
                    <h1 class="mb-3"><?= htmlspecialchars($chambre['nom']) ?></h1>
                    
                    <div class="price-card mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="price-value"><?= number_format($chambre['prix'], 2, ',', ' ') ?> €</span>
                                <span class="text-muted">/nuit</span>
                            </div>
                            <div class="room-capacity">
                                <i class="fas fa-users me-2"></i>Max: <?= $chambre['capacite'] ?> personne(s)
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons mb-4">
                        <a href="reservation.php?chambre_id=<?= $chambre['id'] ?>" class="btn btn-primary btn-lg d-block">
                            <i class="fas fa-calendar-alt me-2"></i>Réserver maintenant
                        </a>
                        <button class="btn btn-outline-secondary d-block" onclick="history.back()">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux chambres
                        </button>
                    </div>
                    
                    <div class="room-amenities">
                        <div class="amenity-badge">
                            <i class="fas fa-vector-square"></i> <?= $chambre['superficie'] ?> m²
                        </div>
                        <?php if ($chambre['wifi']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-wifi"></i> Wi-Fi gratuit
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['climatisation']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-snowflake"></i> Climatisation
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['balcon']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-door-open"></i> Balcon privé
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['vue_mer']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-water"></i> Vue sur la mer
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['minibar']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-cocktail"></i> Minibar
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['coffre_fort']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-lock"></i> Coffre-fort
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['salle_bain_privee']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-bath"></i> Salle de bain privée
                            </div>
                        <?php endif; ?>
                        <?php if ($chambre['service_etage']): ?>
                            <div class="amenity-badge">
                                <i class="fas fa-concierge-bell"></i> Service d'étage
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Description détaillée -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="room-detail-section">
                        <h2 class="mb-3">Description</h2>
                        <div class="room-description">
                            <p><?= nl2br(htmlspecialchars($chambre['description'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="room-detail-section">
                        <h2 class="mb-3">Équipements et Services</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Configuration</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Superficie: <?= $chambre['superficie'] ?> m²</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Capacité maximale: <?= $chambre['capacite'] ?> personne(s)</li>
                                    <?php if ($chambre['balcon']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Balcon privé</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['vue_mer']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Vue sur la mer</li>
                                    <?php endif; ?>
                                </ul>
                                
                                <h5 class="mt-4">Couchage</h5>
                                <ul class="list-unstyled">
                                    <?php if (isset($chambre['nb_lits_simples']) && $chambre['nb_lits_simples'] > 0): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i><?= $chambre['nb_lits_simples'] ?> lit(s) simple(s)</li>
                                    <?php endif; ?>
                                    <?php if (isset($chambre['nb_lits_doubles']) && $chambre['nb_lits_doubles'] > 0): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i><?= $chambre['nb_lits_doubles'] ?> lit(s) double(s)</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Équipements</h5>
                                <ul class="list-unstyled">
                                    <?php if ($chambre['wifi']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Wi-Fi gratuit</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['climatisation']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Climatisation</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['salle_bain_privee']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Salle de bain privée</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['minibar']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Minibar</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['coffre_fort']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Coffre-fort</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['service_etage']): ?>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Service d'étage</li>
                                    <?php endif; ?>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Télévision à écran plat</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Articles de toilette gratuits</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Sèche-cheveux</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i>Bureau</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="room-detail-section">
                        <h2 class="mb-3">Règles et Conditions</h2>
                        <div class="room-policy">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Heures</h5>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-sign-in-alt me-2"></i><strong>Check-in:</strong> à partir de 14:00</li>
                                        <li><i class="fas fa-sign-out-alt me-2"></i><strong>Check-out:</strong> jusqu'à 11:00</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Politique d'annulation</h5>
                                    <p>Annulation gratuite jusqu'à 48 heures avant l'arrivée. Après cette période, le montant de la première nuit sera facturé.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recommandations de chambres similaires - À implémenter dans une future mise à jour -->
        <?php endif; ?>
    </div>
</section>

<!-- Script pour la galerie d'images -->
<script>
function changeMainImage(thumbnailElement, imageUrl) {
    // Mettre à jour l'image principale
    document.getElementById('main-image').src = imageUrl;
    
    // Mettre à jour la classe active des miniatures
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumbnail => {
        thumbnail.classList.remove('active');
    });
    
    thumbnailElement.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?> 