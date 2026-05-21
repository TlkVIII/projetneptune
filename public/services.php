<?php
session_start();
require_once '../config/database.php';

// Définir le titre de la page
$page_title = "Nos Services";

// Styles additionnels spécifiques à cette page
$additional_styles = '
<style>
    .service-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        height: 100%;
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .service-icon {
        font-size: 3rem;
        color: var(--secondary-color);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .service-card:hover .service-icon {
        transform: scale(1.1);
        color: var(--accent-color);
    }

    .service-image {
        height: 200px;
        object-fit: cover;
        width: 100%;
    }
</style>';

// Inclure l'en-tête
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="display-4"><?= $page_title ?></h1>
        <p class="lead">Découvrez tous les services que nous vous proposons</p>
    </div>
</section>

<!-- Services Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center section-title" data-aos="fade-up">Services Hôteliers</h2>
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card service-card text-center">
                    <img src="images/services/res1.jpeg" class="card-img-top service-image" alt="Restaurant">
                    <div class="card-body">
                        <i class="fas fa-utensils service-icon"></i>
                        <h3>Restaurant</h3>
                        <p class="card-text">Dégustez nos plats raffinés préparés par nos chefs étoilés dans notre restaurant gastronomique.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card service-card text-center">
                    <img src="images/services/spa2.jpeg" class="card-img-top service-image" alt="Spa">
                    <div class="card-body">
                        <i class="fas fa-spa service-icon"></i>
                        <h3>Spa & Bien-être</h3>
                        <p class="card-text">Détendez-vous dans notre spa luxueux avec nos soins et massages exclusifs.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card service-card text-center">
                    <img src="images/services/pis1.jpeg" class="card-img-top service-image" alt="Piscine">
                    <div class="card-body">
                        <i class="fas fa-swimming-pool service-icon"></i>
                        <h3>Piscine</h3>
                        <p class="card-text">Profitez de notre piscine à débordement avec vue panoramique sur la mer.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Additionnels -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title" data-aos="fade-up">Services Additionnels</h2>
        <div class="row">
            <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card service-card text-center">
                    <img src="images/services/sercon3.jpeg" class="card-img-top service-image" alt="Conciergerie">
                    <div class="card-body">
                        <i class="fas fa-concierge-bell service-icon"></i>
                        <h3>Service de Conciergerie</h3>
                        <p class="card-text">Notre conciergerie est à votre disposition pour organiser vos activités et excursions.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card service-card text-center">
                    <img src="images/services/parking1.jpeg" class="card-img-top service-image" alt="Parking">
                    <div class="card-body">
                        <i class="fas fa-car service-icon"></i>
                        <h3>Parking Privé</h3>
                        <p class="card-text">Parking sécurisé et gardé disponible pour tous nos clients.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card service-card text-center">
                    <img src="images/services/wifi.png" class="card-img-top service-image" alt="WiFi">
                    <div class="card-body">
                        <i class="fas fa-wifi service-icon"></i>
                        <h3>WiFi Haut Débit</h3>
                        <p class="card-text">Accès WiFi gratuit dans tout l'hôtel pour rester connecté.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="400">
                <div class="card service-card text-center">
                    <img src="images/services/sergarde.jpg" class="card-img-top service-image" alt="Garde d'enfants">
                    <div class="card-body">
                        <i class="fas fa-baby service-icon"></i>
                        <h3>Service Garde d'Enfants</h3>
                        <p class="card-text">Service de garde d'enfants disponible sur demande.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 