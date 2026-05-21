<?php
session_start();
require_once '../config/database.php';

// Définir le titre de la page
$page_title = "Nos Chambres";

// Récupération des filtres
$capacite = $_GET['capacite'] ?? '';
$prix_min = $_GET['prix_min'] ?? '';
$prix_max = $_GET['prix_max'] ?? '';

// Construction de la requête
$sql = "SELECT * FROM chambres WHERE 1=1";
$params = [];

if (!empty($capacite)) {
    $sql .= " AND capacite >= ?";
    $params[] = $capacite;
}

if (!empty($prix_min)) {
    $sql .= " AND prix >= ?";
    $params[] = $prix_min;
}

if (!empty($prix_max)) {
    $sql .= " AND prix <= ?";
    $params[] = $prix_max;
}

$sql .= " ORDER BY prix ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $chambres = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Une erreur est survenue lors de la récupération des chambres";
}

// Styles additionnels spécifiques à cette page
$additional_styles = '
<style>
    .filter-section {
        background: linear-gradient(135deg, #ffffff, #f7fbff);
        padding: 2rem;
        border-radius: 16px;
        border: 1px solid #e8eef7;
        box-shadow: 0 14px 35px rgba(44, 62, 80, 0.08);
        margin-bottom: 3rem;
    }

    .filter-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .filter-row {
        align-items: flex-end;
    }

    .filter-group .form-label {
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 0.45rem;
    }

    .filter-group .form-control,
    .filter-group .form-select {
        height: 48px;
        border-radius: 10px;
        border: 1px solid #d7e2f0;
        box-shadow: none;
        background-color: #fff;
    }

    .filter-group .form-control:focus,
    .filter-group .form-select:focus {
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.18);
    }

    .filter-btn {
        height: 48px;
        border-radius: 999px;
        font-weight: 600;
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.35);
    }

    .rooms-grid {
        row-gap: 24px;
    }

    .room-col {
        display: flex;
    }

    .room-card {
        width: 100%;
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 14px 35px rgba(44, 62, 80, 0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .room-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 42px rgba(44, 62, 80, 0.18);
    }

    .room-card .card-img-top {
        height: 220px;
        object-fit: cover;
    }

    .room-card .card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .room-card .card-text {
        color: #5b6677;
        margin-bottom: 0.8rem;
    }

    .card-main-content {
        flex: 1;
    }

    .room-features {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
    }

    .room-features li {
        margin-bottom: 0.5rem;
    }

    .room-features i {
        color: var(--secondary-color);
        margin-right: 0.5rem;
    }

    .btn-reserver {
        background: var(--secondary-color);
        border: none;
        padding: 0.8rem 1.5rem;
        transition: all 0.3s ease;
    }

    .btn-reserver:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
    }

    .card-actions {
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid #edf2f7;
    }

    .card-actions .btn {
        border-radius: 10px;
        font-weight: 600;
    }

    .card-actions .btn-outline-primary:hover {
        background-color: #1f6fb2;
        border-color: #1f6fb2;
    }

    .no-rooms {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
</style>';

// Inclure l'en-tête
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="display-4"><?= $page_title ?></h1>
        <p class="lead">Découvrez notre sélection de chambres luxueuses</p>
    </div>
</section>

<!-- Filtres -->
<div class="container">
    <div class="filter-section">
        <div class="filter-title"><i class="fas fa-sliders-h me-2"></i>Filtrer les chambres</div>
        <form method="GET" action="" class="row g-3 filter-row">
            <div class="col-lg-3 col-md-6 filter-group">
                <label for="capacite" class="form-label">Capacité minimale</label>
                <select class="form-select" id="capacite" name="capacite">
                    <option value="">Toutes</option>
                    <option value="1" <?php echo $capacite == '1' ? 'selected' : ''; ?>>1 personne</option>
                    <option value="2" <?php echo $capacite == '2' ? 'selected' : ''; ?>>2 personnes</option>
                    <option value="3" <?php echo $capacite == '3' ? 'selected' : ''; ?>>3 personnes</option>
                    <option value="4" <?php echo $capacite == '4' ? 'selected' : ''; ?>>4 personnes</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 filter-group">
                <label for="prix_min" class="form-label">Prix minimum</label>
                <input type="number" class="form-control" id="prix_min" name="prix_min" value="<?php echo $prix_min; ?>" placeholder="€">
            </div>
            <div class="col-lg-3 col-md-6 filter-group">
                <label for="prix_max" class="form-label">Prix maximum</label>
                <input type="number" class="form-control" id="prix_max" name="prix_max" value="<?php echo $prix_max; ?>" placeholder="€">
            </div>
            <div class="col-lg-3 col-md-6 d-grid">
                <button type="submit" class="btn btn-primary w-100 filter-btn">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des chambres -->
    <div class="row rooms-grid">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (empty($chambres)): ?>
            <div class="col-12">
                <div class="no-rooms">
                    <i class="fas fa-bed fa-3x mb-3" style="color: var(--secondary-color);"></i>
                    <h3>Aucune chambre ne correspond à vos critères</h3>
                    <p>Essayez de modifier vos filtres de recherche</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($chambres as $chambre): ?>
                <?php
                    $imageFile = $chambre['image'] ?? '';
                    $imagePath = __DIR__ . '/images/rooms/' . $imageFile;
                    if (empty($imageFile) || !file_exists($imagePath)) {
                        $imageFile = 'cstandard1.jpeg';
                    }
                ?>
                <div class="col-md-6 col-lg-4 room-col" data-aos="fade-up">
                    <div class="card room-card">
                        <img src="images/rooms/<?php echo htmlspecialchars($imageFile); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($chambre['nom']); ?>">
                        <div class="card-body">
                            <div class="card-main-content">
                                <h5 class="card-title"><?php echo htmlspecialchars($chambre['nom']); ?></h5>
                                <p class="room-price"><?php echo number_format($chambre['prix'], 2, ',', ' '); ?> € / nuit</p>
                                <p class="card-text"><?php echo htmlspecialchars($chambre['description']); ?></p>
                                <ul class="room-features">
                                    <li><i class="fas fa-users"></i> Capacité: <?php echo $chambre['capacite']; ?> personne(s)</li>
                                    <li><i class="fas fa-vector-square"></i> Superficie: <?php echo $chambre['superficie']; ?> m²</li>
                                    <?php if ($chambre['wifi']): ?>
                                        <li><i class="fas fa-wifi"></i> Wi-Fi gratuit</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['vue_mer']): ?>
                                        <li><i class="fas fa-water"></i> Vue sur la mer</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['balcon']): ?>
                                        <li><i class="fas fa-door-open"></i> Balcon privé</li>
                                    <?php endif; ?>
                                    <?php if ($chambre['climatisation']): ?>
                                        <li><i class="fas fa-snowflake"></i> Climatisation</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="d-grid gap-2 card-actions">
                                <a href="chambre-details.php?id=<?php echo $chambre['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-2"></i>Voir détails
                                </a>
                                <a href="reservation.php?chambre_id=<?php echo $chambre['id']; ?>" class="btn btn-primary btn-reserver">
                                    <i class="fas fa-calendar-alt me-2"></i>Réserver maintenant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 