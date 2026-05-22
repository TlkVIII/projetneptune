<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            padding-top: 56px;
            background-color: #f5f5f5;
        }
        
        .admin-sidebar {
            background-color: var(--primary-color);
            color: white;
            min-height: calc(100vh - 56px);
        }
        
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0;
            padding: 0.8rem 1rem;
        }
        
        .admin-sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: var(--secondary-color);
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .admin-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .admin-card .card-body {
            padding: 1.5rem;
        }
        
        .admin-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                width: 260px;
                z-index: 1035;
                overflow-y: auto;
                max-height: calc(100vh - 56px);
            }
            main.col-md-9 {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hotel me-2"></i>Administration
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>Voir le site
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user-circle me-2"></i>Mon profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="chambres.php">
                                <i class="fas fa-bed me-2"></i>Chambres
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reservations.php">
                                <i class="fas fa-calendar-alt me-2"></i>Réservations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="utilisateurs.php">
                                <i class="fas fa-users me-2"></i>Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope me-2"></i>Messages
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tableau de bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Aujourd'hui</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Cette semaine</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Ce mois</button>
                        </div>
                    </div>
                </div>

                <!-- Cartes d'information -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="card admin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-bed admin-icon"></i>
                                <?php
                                $stmt = $pdo->query("SELECT COUNT(*) FROM chambres");
                                $count = $stmt->fetchColumn();
                                ?>
                                <h2 class="card-title"><?php echo $count; ?></h2>
                                <p class="card-text">Chambres</p>
                                <a href="chambres.php" class="btn btn-primary">Gérer les chambres</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card admin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check admin-icon"></i>
                                <?php
                                $stmt = $pdo->query("SELECT COUNT(*) FROM reservations");
                                $count = $stmt->fetchColumn();
                                ?>
                                <h2 class="card-title"><?php echo $count; ?></h2>
                                <p class="card-text">Réservations</p>
                                <a href="reservations.php" class="btn btn-primary">Voir les réservations</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card admin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users admin-icon"></i>
                                <?php
                                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                $count = $stmt->fetchColumn();
                                ?>
                                <h2 class="card-title"><?php echo $count; ?></h2>
                                <p class="card-text">Utilisateurs</p>
                                <a href="utilisateurs.php" class="btn btn-primary">Gérer les utilisateurs</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liens rapides -->
                <h2 class="h4 mb-3">Liens rapides</h2>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card admin-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-plus-circle me-2 text-success"></i>Ajouter une chambre</h5>
                                <p class="card-text">Créez une nouvelle chambre avec toutes ses caractéristiques.</p>
                                <a href="chambres.php?action=ajouter" class="btn btn-outline-success">Ajouter</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card admin-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-search me-2 text-primary"></i>Rechercher des réservations</h5>
                                <p class="card-text">Trouvez rapidement des réservations par date ou par client.</p>
                                <a href="reservations.php" class="btn btn-outline-primary">Rechercher</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 