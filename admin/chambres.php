<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialisation des variables
$message = '';
$chambre = [
    'id' => '',
    'nom' => '',
    'description' => '',
    'prix' => '',
    'capacite' => '',
    'disponible' => 1,
    'nb_lits_simples' => 0,
    'nb_lits_doubles' => 0,
    'salle_bain_privee' => 1,
    'balcon' => 0,
    'vue_mer' => 0,
    'climatisation' => 1,
    'wifi' => 1,
    'minibar' => 0,
    'coffre_fort' => 0,
    'superficie' => '',
    'image' => 'default.jpg'
];

// Gestion des actions (ajouter, modifier, supprimer)
$action = $_GET['action'] ?? 'liste';

// Traitement du formulaire d'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $chambre = [
        'id' => $_POST['id'] ?? '',
        'nom' => $_POST['nom'] ?? '',
        'description' => $_POST['description'] ?? '',
        'prix' => $_POST['prix'] ?? '',
        'capacite' => $_POST['capacite'] ?? '',
        'disponible' => isset($_POST['disponible']) ? 1 : 0,
        'nb_lits_simples' => $_POST['nb_lits_simples'] ?? 0,
        'nb_lits_doubles' => $_POST['nb_lits_doubles'] ?? 0,
        'salle_bain_privee' => isset($_POST['salle_bain_privee']) ? 1 : 0,
        'balcon' => isset($_POST['balcon']) ? 1 : 0,
        'vue_mer' => isset($_POST['vue_mer']) ? 1 : 0,
        'climatisation' => isset($_POST['climatisation']) ? 1 : 0,
        'wifi' => isset($_POST['wifi']) ? 1 : 0,
        'minibar' => isset($_POST['minibar']) ? 1 : 0,
        'coffre_fort' => isset($_POST['coffre_fort']) ? 1 : 0,
        'superficie' => $_POST['superficie'] ?? '',
        'image' => $_POST['image'] ?? 'default.jpg'
    ];

    // Validation des données
    $errors = [];
    if (empty($chambre['nom'])) $errors[] = "Le nom de la chambre est requis";
    if (empty($chambre['prix']) || !is_numeric($chambre['prix'])) $errors[] = "Le prix doit être un nombre valide";
    if (empty($chambre['capacite']) || !is_numeric($chambre['capacite'])) $errors[] = "La capacité doit être un nombre valide";

    // Gestion de l'image
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileName = $_FILES['image_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $newFileName = 'room_' . time() . '.' . $fileExt;
            $uploadPath = '../images/rooms/' . $newFileName;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadPath)) {
                $chambre['image'] = $newFileName;
            } else {
                $errors[] = "Erreur lors du téléchargement de l'image";
            }
        } else {
            $errors[] = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
        }
    }

    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($errors)) {
        try {
            if (empty($chambre['id'])) {
                // Ajout d'une nouvelle chambre
                $sql = "INSERT INTO chambres (nom, description, prix, capacite, disponible, 
                nb_lits_simples, nb_lits_doubles, salle_bain_privee, balcon, vue_mer, 
                climatisation, wifi, minibar, coffre_fort, superficie, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $chambre['nom'], $chambre['description'], $chambre['prix'], $chambre['capacite'], 
                    $chambre['disponible'], $chambre['nb_lits_simples'], $chambre['nb_lits_doubles'], 
                    $chambre['salle_bain_privee'], $chambre['balcon'], $chambre['vue_mer'], 
                    $chambre['climatisation'], $chambre['wifi'], $chambre['minibar'], 
                    $chambre['coffre_fort'], $chambre['superficie'], $chambre['image']
                ]);
                
                $message = '<div class="alert alert-success">La chambre a été ajoutée avec succès.</div>';
                // Réinitialiser le formulaire
                $chambre = array_fill_keys(array_keys($chambre), '');
                $chambre['disponible'] = 1;
                $chambre['salle_bain_privee'] = 1;
                $chambre['climatisation'] = 1;
                $chambre['wifi'] = 1;
                $chambre['image'] = 'default.jpg';
            } else {
                // Modification d'une chambre existante
                $sql = "UPDATE chambres SET nom = ?, description = ?, prix = ?, capacite = ?, disponible = ?, 
                nb_lits_simples = ?, nb_lits_doubles = ?, salle_bain_privee = ?, balcon = ?, vue_mer = ?, 
                climatisation = ?, wifi = ?, minibar = ?, coffre_fort = ?, superficie = ?, 
                image = ? WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $chambre['nom'], $chambre['description'], $chambre['prix'], $chambre['capacite'], 
                    $chambre['disponible'], $chambre['nb_lits_simples'], $chambre['nb_lits_doubles'], 
                    $chambre['salle_bain_privee'], $chambre['balcon'], $chambre['vue_mer'], 
                    $chambre['climatisation'], $chambre['wifi'], $chambre['minibar'], 
                    $chambre['coffre_fort'], $chambre['superficie'], 
                    $chambre['image'], $chambre['id']
                ]);
                
                $message = '<div class="alert alert-success">La chambre a été mise à jour avec succès.</div>';
            }
            
            // Rediriger vers la liste après traitement
            header("Location: chambres.php?message=" . urlencode($message));
            exit();
            
        } catch(PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger"><ul>';
        foreach($errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul></div>';
    }
}

// Suppression d'une chambre
if ($action === 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM chambres WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">La chambre a été supprimée avec succès.</div>';
        header("Location: chambres.php?message=" . urlencode($message));
        exit();
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur lors de la suppression: ' . $e->getMessage() . '</div>';
    }
}

// Basculer rapidement la disponibilité d'une chambre
if ($action === 'toggle_disponibilite' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE chambres SET disponible = CASE WHEN disponible = 1 THEN 0 ELSE 1 END WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">Disponibilité de la chambre mise à jour.</div>';
        header("Location: chambres.php?message=" . urlencode($message));
        exit();
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur lors de la mise à jour de la disponibilité : ' . $e->getMessage() . '</div>';
    }
}

// Récupération d'une chambre pour modification
if ($action === 'modifier' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM chambres WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        if ($result) {
            $chambre = $result;
        } else {
            $message = '<div class="alert alert-danger">Chambre non trouvée.</div>';
            header("Location: chambres.php?message=" . urlencode($message));
            exit();
        }
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération de toutes les chambres pour la liste
$chambres = [];
if ($action === 'liste') {
    try {
        $stmt = $pdo->query("SELECT * FROM chambres ORDER BY id DESC");
        $chambres = $stmt->fetchAll();
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Afficher le message s'il provient d'une redirection
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chambres - Hôtel Neptune</title>
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .room-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="chambres.php">
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
                    <h1 class="h2">
                        <?php 
                        if ($action === 'ajouter') {
                            echo "Ajouter une chambre";
                        } elseif ($action === 'modifier') {
                            echo "Modifier une chambre";
                        } else {
                            echo "Gestion des chambres";
                        } 
                        ?>
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <?php if ($action === 'liste'): ?>
                            <a href="chambres.php?action=ajouter" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Ajouter une chambre
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <?php echo $message; ?>
                <?php endif; ?>
            
                <?php if ($action === 'liste'): ?>
                    <!-- Liste des chambres -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Nom</th>
                                            <th>Prix</th>
                                            <th>Capacité</th>
                                            <th>Lits</th>
                                            <th>Disponible</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($chambres)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Aucune chambre disponible</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($chambres as $c): ?>
                                                <tr>
                                                    <td><?php echo $c['id']; ?></td>
                                                    <td>
                                                        <img src="../images/rooms/<?php echo htmlspecialchars($c['image']); ?>" 
                                                            alt="<?php echo htmlspecialchars($c['nom']); ?>" 
                                                            class="room-image">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($c['nom']); ?></td>
                                                    <td><?php echo number_format($c['prix'], 2); ?> €</td>
                                                    <td><?php echo $c['capacite']; ?> pers.</td>
                                                    <td>
                                                        <?php 
                                                        $lits = [];
                                                        if ($c['nb_lits_simples'] > 0) $lits[] = $c['nb_lits_simples'] . ' simple(s)';
                                                        if ($c['nb_lits_doubles'] > 0) $lits[] = $c['nb_lits_doubles'] . ' double(s)';
                                                        echo implode(', ', $lits);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($c['disponible']): ?>
                                                            <span class="badge bg-success">Disponible</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Indisponible</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="chambres.php?action=modifier&id=<?php echo $c['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="chambres.php?action=toggle_disponibilite&id=<?php echo $c['id']; ?>" 
                                                           class="btn btn-sm <?php echo $c['disponible'] ? 'btn-warning' : 'btn-success'; ?>"
                                                           title="<?php echo $c['disponible'] ? 'Rendre indisponible' : 'Rendre disponible'; ?>"
                                                           onclick="return confirm('Confirmer le changement de disponibilité pour cette chambre ?')">
                                                            <i class="fas <?php echo $c['disponible'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                                        </a>
                                                        <a href="chambres.php?action=supprimer&id=<?php echo $c['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette chambre ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulaire d'ajout/modification -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <?php if (!empty($chambre['id'])): ?>
                                    <input type="hidden" name="id" value="<?php echo $chambre['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h4>Informations Générales</h4>
                                        
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom de la chambre *</label>
                                            <input type="text" class="form-control" id="nom" name="nom" 
                                                value="<?php echo htmlspecialchars($chambre['nom']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                rows="3"><?php echo htmlspecialchars($chambre['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="prix" class="form-label">Prix par nuit (€) *</label>
                                                <input type="number" step="0.01" class="form-control" id="prix" name="prix" 
                                                    value="<?php echo htmlspecialchars($chambre['prix']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="capacite" class="form-label">Capacité (personnes) *</label>
                                                <input type="number" class="form-control" id="capacite" name="capacite" 
                                                    value="<?php echo htmlspecialchars($chambre['capacite']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" 
                                                <?php echo $chambre['disponible'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="disponible">
                                                Chambre disponible à la réservation
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h4>Caractéristiques de la chambre</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nb_lits_simples" class="form-label">Nombre de lits simples</label>
                                                <input type="number" class="form-control" id="nb_lits_simples" name="nb_lits_simples" 
                                                    value="<?php echo htmlspecialchars($chambre['nb_lits_simples']); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="nb_lits_doubles" class="form-label">Nombre de lits doubles</label>
                                                <input type="number" class="form-control" id="nb_lits_doubles" name="nb_lits_doubles" 
                                                    value="<?php echo htmlspecialchars($chambre['nb_lits_doubles']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="superficie" class="form-label">Superficie (m²)</label>
                                                <input type="number" step="0.01" class="form-control" id="superficie" name="superficie" 
                                                    value="<?php echo htmlspecialchars($chambre['superficie']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Équipements et caractéristiques</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="salle_bain_privee" name="salle_bain_privee" 
                                                            <?php echo $chambre['salle_bain_privee'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="salle_bain_privee">
                                                            Salle de bain privée
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="balcon" name="balcon" 
                                                            <?php echo $chambre['balcon'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="balcon">
                                                            Balcon
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="vue_mer" name="vue_mer" 
                                                            <?php echo $chambre['vue_mer'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="vue_mer">
                                                            Vue sur la mer
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="climatisation" name="climatisation" 
                                                            <?php echo $chambre['climatisation'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="climatisation">
                                                            Climatisation
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="wifi" name="wifi" 
                                                            <?php echo $chambre['wifi'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="wifi">
                                                            WiFi
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="minibar" name="minibar" 
                                                            <?php echo $chambre['minibar'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="minibar">
                                                            Minibar
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="coffre_fort" name="coffre_fort" 
                                                            <?php echo $chambre['coffre_fort'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="coffre_fort">
                                                            Coffre-fort
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h4>Image de la chambre</h4>
                                        
                                        <?php if (!empty($chambre['image']) && $chambre['image'] !== 'default.jpg'): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Image actuelle</label>
                                                <div>
                                                    <img src="../images/rooms/<?php echo htmlspecialchars($chambre['image']); ?>" 
                                                        alt="<?php echo htmlspecialchars($chambre['nom']); ?>" 
                                                        class="img-thumbnail" style="max-width: 200px;">
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label for="image_file" class="form-label">Télécharger une nouvelle image</label>
                                            <input type="file" class="form-control" id="image_file" name="image_file">
                                            <input type="hidden" name="image" value="<?php echo htmlspecialchars($chambre['image']); ?>">
                                            <div class="form-text">Formats acceptés: JPG, PNG, GIF. Taille maximale: 2 Mo.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="chambres.php" class="btn btn-secondary">Annuler</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo (!empty($chambre['id'])) ? 'Mettre à jour' : 'Ajouter'; ?> la chambre
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 