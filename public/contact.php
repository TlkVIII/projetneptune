<?php
session_start();
require_once '../config/database.php';

$success = false;
$error = '';

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation des données
    if (empty($nom)) {
        $error = "Le nom est requis";
    } elseif (empty($email)) {
        $error = "L'adresse e-mail est requise";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse e-mail n'est pas valide";
    } elseif (empty($sujet)) {
        $error = "Le sujet est requis";
    } elseif (empty($message)) {
        $error = "Le message est requis";
    } else {
        try {
            // Insertion dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO messages (nom, email, sujet, message, date_envoi) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$nom, $email, $sujet, $message]);
            
            // Message envoyé avec succès
            $success = true;
            
            // Réinitialiser les champs
            $nom = $email = $sujet = $message = '';
            
        } catch (PDOException $e) {
            $error = "Une erreur s'est produite lors de l'envoi du message : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Hôtel Neptune</title>
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

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/contact1.jpeg');
            background-size: cover;
            background-position: center;
            height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .contact-info-card {
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
            padding: 2rem;
        }

        .contact-info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .contact-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .contact-info-card:hover .contact-icon {
            transform: scale(1.1);
            color: var(--accent-color);
        }

        .form-control {
            border-radius: 10px;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            border-color: var(--secondary-color);
        }

        .map-container {
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4">Contactez-nous</h1>
            <p class="lead">Nous sommes à votre écoute pour toute demande d'information</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">Nos Coordonnées</h2>
            <div class="row mb-5">
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-info-card text-center">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <h3>Adresse</h3>
                        <p>Montpellier, France 34000</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-info-card text-center">
                        <i class="fas fa-phone-alt contact-icon"></i>
                        <h3>Téléphone</h3>
                        <p>0695396132</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-info-card text-center">
                        <i class="fas fa-envelope contact-icon"></i>
                        <h3>Email</h3>
                        <p>fayed.amourani8@gmail.com</p>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6 mb-4" data-aos="fade-up">
                    <h2 class="mb-4">Envoyez-nous un message</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet</label>
                            <input type="text" class="form-control" id="sujet" name="sujet" value="<?php echo htmlspecialchars($sujet ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                        </button>
                    </form>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="mb-4">Notre emplacement</h2>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps?q=Montpellier,France&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <h2 class="text-center section-title mt-5" data-aos="fade-up">Questions fréquemment posées</h2>
            <div class="row mt-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-md-10 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Quelles sont les heures d'arrivée et de départ ?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    L'heure d'arrivée (check-in) est à partir de 14h00 et l'heure de départ (check-out) est jusqu'à 12h00. Des arrangements spéciaux peuvent être faits en contactant notre réception à l'avance.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Comment puis-je modifier ou annuler ma réservation ?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pour modifier ou annuler votre réservation, veuillez nous contacter par email à fayed.amourani8@gmail.com ou par téléphone au 0695396132 au moins 48 heures avant votre date d'arrivée prévue.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    L'hôtel propose-t-il un service de navette depuis l'aéroport ?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oui, nous proposons un service de navette depuis et vers l'aéroport. Ce service doit être réservé à l'avance. Veuillez nous contacter au moins 24 heures avant votre arrivée pour organiser votre transfert.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>