<?php
session_start();
require_once '../config/database.php';

// Récupérer les chambres depuis la base de données
try {
    $stmt = $pdo->query("SELECT * FROM chambres WHERE disponible = 1 ORDER BY prix ASC LIMIT 3");
    $chambres = $stmt->fetchAll();
} catch (PDOException $e) {
    $chambres = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            padding-top: 76px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: rgba(44, 62, 80, 0.95) !important;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .carousel-item {
            height: 80vh;
            background-size: cover;
            background-position: center;
        }
        
        .carousel-caption {
            bottom: 100px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
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

        .feature-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }

        .feature-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .room-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
        }

        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .room-card img {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }

        .room-card .card-body {
            padding: 1.5rem;
        }

        .room-price {
            color: var(--accent-color);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .section-title {
            position: relative;
            margin-bottom: 3rem;
            padding-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 4rem 0 2rem;
        }

        .social-links a {
            font-size: 1.5rem;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color) !important;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .carousel-item {
                height: 60vh;
            }
            
            .carousel-caption {
                bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Carousel Section -->
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/slider/facade1.jpeg'); background-size: cover; background-position: center;">
                <div class="carousel-caption">
                    <h1 class="display-3 mb-4" data-aos="fade-up">Bienvenue à l'Hôtel Neptune</h1>
                    <p class="lead mb-5" data-aos="fade-up" data-aos-delay="200">Un séjour de luxe et de confort au cœur de la ville</p>
                    <a href="reservation.php" class="btn btn-primary btn-lg" data-aos="fade-up" data-aos-delay="400">Réserver maintenant</a>
                </div>
            </div>
            <div class="carousel-item" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/slider/pano1.jpeg'); background-size: cover; background-position: center;">
                <div class="carousel-caption">
                    <h1 class="display-3 mb-4">Une vue imprenable</h1>
                    <p class="lead mb-5">Profitez de panoramas exceptionnels depuis nos chambres</p>
                    <a href="chambres.php" class="btn btn-primary btn-lg">Découvrir nos chambres</a>
                </div>
            </div>
            <div class="carousel-item" style="background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/slider/facade2.jpeg'); background-size: cover; background-position: center;">
                <div class="carousel-caption">
                    <h1 class="display-3 mb-4">Service 5 étoiles</h1>
                    <p class="lead mb-5">Notre équipe est à votre disposition 24h/24</p>
                    <a href="services.php" class="btn btn-primary btn-lg">Découvrir nos services</a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    </div>

    <!-- Features Section -->
    <section class="py-5" data-aos="fade-up">
        <div class="container">
            <h2 class="text-center section-title">Nos Services</h2>
            <div class="row">
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card text-center p-4">
                        <img src="images/services/pis1.jpeg" alt="Piscine" class="img-fluid rounded mb-3" style="height: 200px; object-fit: cover; width: 100%;">
                        <i class="fas fa-swimming-pool feature-icon"></i>
                        <h3>Piscine de luxe</h3>
                        <p>Profitez de notre piscine intérieure chauffée avec vue panoramique sur la ville.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card text-center p-4">
                        <img src="images/services/res1.jpeg" alt="Restaurant" class="img-fluid rounded mb-3" style="height: 200px; object-fit: cover; width: 100%;">
                        <i class="fas fa-utensils feature-icon"></i>
                        <h3>Restaurant gastronomique</h3>
                        <p>Dégustez les plats raffinés préparés par notre chef étoilé et son équipe.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card text-center p-4">
                        <img src="images/services/spa1.jpeg" alt="Spa" class="img-fluid rounded mb-3" style="height: 200px; object-fit: cover; width: 100%;">
                        <i class="fas fa-spa feature-icon"></i>
                        <h3>Spa et bien-être</h3>
                        <p>Détendez-vous dans notre spa avec massages, sauna et soins du corps personnalisés.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="services.php" class="btn btn-outline-primary">Voir tous nos services</a>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section class="py-5 bg-light" data-aos="fade-up">
        <div class="container">
            <h2 class="text-center section-title">Nos Chambres</h2>
            <div class="row">
                <?php if (!empty($chambres)): ?>
                    <?php foreach ($chambres as $chambre): ?>
                        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo 100 * $loop_index ?? 100; ?>">
                            <div class="card room-card">
                                <img src="images/rooms/<?php echo htmlspecialchars($chambre['image'] ?? 'default-room.jpg'); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($chambre['nom']); ?>">
                                <div class="card-body text-center">
                                    <h3 class="card-title"><?php echo htmlspecialchars($chambre['nom']); ?></h3>
                                    <p class="room-price">À partir de <?php echo number_format($chambre['prix'], 0, ',', ' '); ?>€/nuit</p>
                                    <p class="card-text">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($chambre['capacite']); ?> personnes
                                    </p>
                                    <a href="reservation.php?chambre_id=<?php echo $chambre['id']; ?>" class="btn btn-primary">Réserver</a>
                                    <a href="chambres.php#chambre-<?php echo $chambre['id']; ?>" class="btn btn-outline-secondary mt-2">Détails</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Chambres par défaut si la base de données ne répond pas -->
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card room-card">
                            <img src="images/rooms/cstandard1.jpeg" class="card-img-top" alt="Chambre Standard">
                            <div class="card-body text-center">
                                <h3 class="card-title">Chambre Standard</h3>
                                <p class="room-price">À partir de 89€/nuit</p>
                                <p class="card-text"><i class="fas fa-user"></i> 2 personnes</p>
                                <a href="reservation.php" class="btn btn-primary">Réserver</a>
                                <a href="chambres.php" class="btn btn-outline-secondary mt-2">Détails</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card room-card">
                            <img src="images/rooms/cdeluxe1.jpeg" class="card-img-top" alt="Chambre Deluxe">
                            <div class="card-body text-center">
                                <h3 class="card-title">Chambre Deluxe</h3>
                                <p class="room-price">À partir de 150€/nuit</p>
                                <p class="card-text"><i class="fas fa-user"></i> 2 personnes</p>
                                <a href="reservation.php" class="btn btn-primary">Réserver</a>
                                <a href="chambres.php" class="btn btn-outline-secondary mt-2">Détails</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card room-card">
                            <img src="images/rooms/cpresident1.jpeg" class="card-img-top" alt="Suite">
                            <div class="card-body text-center">
                                <h3 class="card-title">Suite Présidentielle</h3>
                                <p class="room-price">À partir de 300€/nuit</p>
                                <p class="card-text"><i class="fas fa-user"></i> 4 personnes</p>
                                <a href="reservation.php" class="btn btn-primary">Réserver</a>
                                <a href="chambres.php" class="btn btn-outline-secondary mt-2">Détails</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center mt-4">
                <a href="chambres.php" class="btn btn-outline-primary">Voir toutes nos chambres</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>