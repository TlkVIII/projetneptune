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
$user = [
    'id' => '',
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
    'code_postal' => '',
    'ville' => '',
    'pays' => 'France',
    'role' => 'client',
    'date_creation' => ''
];

// Gestion des actions (voir, modifier, supprimer)
$action = $_GET['action'] ?? 'liste';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $user = [
        'id' => $_POST['id'] ?? '',
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'code_postal' => $_POST['code_postal'] ?? '',
        'ville' => $_POST['ville'] ?? '',
        'pays' => $_POST['pays'] ?? 'France',
        'role' => $_POST['role'] ?? 'client'
    ];

    // Validation des données
    $errors = [];
    if (empty($user['nom'])) $errors[] = "Le nom est requis";
    if (empty($user['prenom'])) $errors[] = "Le prénom est requis";
    if (empty($user['email'])) $errors[] = "L'email est requis";
    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    if (!empty($user['email'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$user['email'], $user['id']]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé par un autre utilisateur";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification de l'email: " . $e->getMessage();
        }
    }

    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($errors)) {
        try {
            // Modification d'un utilisateur existant
            $sql = "UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, 
                    adresse = ?, code_postal = ?, ville = ?, pays = ?, role = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user['nom'], $user['prenom'], $user['email'], $user['telephone'],
                $user['adresse'], $user['code_postal'], $user['ville'], $user['pays'],
                $user['role'], $user['id']
            ]);
            
            $message = '<div class="alert alert-success">L\'utilisateur a été mis à jour avec succès.</div>';
            
            // Rediriger vers la liste après traitement
            header("Location: utilisateurs.php?message=" . urlencode($message));
            exit();
            
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul></div>';
    }
}

// Suppression d'un utilisateur
if ($action === 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Vérifier qu'on ne supprime pas l'utilisateur actuellement connecté
    if ($id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger">Vous ne pouvez pas supprimer votre propre compte.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">L\'utilisateur a été supprimé avec succès.</div>';
            header("Location: utilisateurs.php?message=" . urlencode($message));
            exit();
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur lors de la suppression: ' . $e->getMessage() . '</div>';
        }
    }
}

// Récupération d'un utilisateur pour modification ou visualisation
if (($action === 'modifier' || $action === 'voir') && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        if ($result) {
            $user = $result;
        } else {
            $message = '<div class="alert alert-danger">Utilisateur non trouvé.</div>';
            header("Location: utilisateurs.php?message=" . urlencode($message));
            exit();
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération de tous les utilisateurs pour la liste
$users = [];
if ($action === 'liste') {
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY nom, prenom");
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
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
    <title>Gestion des Utilisateurs - Hôtel Neptune</title>
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
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .role-badge {
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-client {
            background-color: #28a745;
            color: white;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 10px;
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
                            <a class="nav-link active" href="utilisateurs.php">
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
                        if ($action === 'voir') {
                            echo "Détails de l'utilisateur";
                        } elseif ($action === 'modifier') {
                            echo "Modifier l'utilisateur";
                        } else {
                            echo "Gestion des utilisateurs";
                        } 
                        ?>
                    </h1>
                    <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".admin-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <?php if (!empty($message)): ?>
                    <?php echo $message; ?>
                <?php endif; ?>

                <?php if ($action === 'liste'): ?>
                    <!-- Liste des utilisateurs -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Liste des utilisateurs</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un utilisateur...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom complet</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Rôle</th>
                                            <th>Date inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Aucun utilisateur trouvé</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $u): ?>
                                                <tr>
                                                    <td><?php echo $u['id']; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar">
                                                                <?php echo strtoupper(substr($u['prenom'], 0, 1) . substr($u['nom'], 0, 1)); ?>
                                                            </div>
                                                            <?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['telephone'] ?? 'Non renseigné'); ?></td>
                                                    <td>
                                                        <span class="role-badge <?php echo ($u['role'] === 'admin') ? 'role-admin' : 'role-client'; ?>">
                                                            <?php echo ucfirst($u['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (isset($u['date_creation'])) {
                                                            echo date('d/m/Y', strtotime($u['date_creation']));
                                                        } else {
                                                            echo 'Non disponible';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="utilisateurs.php?action=voir&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-info me-1" title="Voir">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="utilisateurs.php?action=modifier&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary me-1" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                            <a href="utilisateurs.php?action=supprimer&id=<?php echo $u['id']; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')" title="Supprimer">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'voir'): ?>
                    <!-- Affichage détaillé d'un utilisateur -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Détails de l'utilisateur</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <div style="width: 150px; height: 150px; background-color: var(--secondary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto;">
                                        <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                    </div>
                                    <h4 class="mt-3"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                                    <span class="role-badge <?php echo ($user['role'] === 'admin') ? 'role-admin' : 'role-client'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                    <?php if (isset($user['date_creation']) && !empty($user['date_creation'])): ?>
                                        <p class="text-muted mt-2">
                                            <i class="fas fa-calendar-alt me-1"></i> Inscrit depuis le <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <h5>Informations personnelles</h5>
                                    <table class="table">
                                        <tr>
                                            <th style="width: 30%;">Email</th>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <td><?php echo !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : 'Non renseigné'; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Adresse</th>
                                            <td>
                                                <?php if (!empty($user['adresse'])): ?>
                                                    <?php echo htmlspecialchars($user['adresse']); ?><br>
                                                    <?php echo htmlspecialchars($user['code_postal'] . ' ' . $user['ville']); ?><br>
                                                    <?php echo htmlspecialchars($user['pays']); ?>
                                                <?php else: ?>
                                                    Non renseignée
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <div class="d-flex justify-content-end mt-3">
                                        <a href="utilisateurs.php" class="btn btn-secondary me-2">
                                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                                        </a>
                                        <a href="utilisateurs.php?action=modifier&id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> Modifier
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulaire de modification d'un utilisateur -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Modifier l'utilisateur</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h5>Informations personnelles</h5>
                                        
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom *</label>
                                            <input type="text" class="form-control" id="nom" name="nom" 
                                                value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="prenom" class="form-label">Prénom *</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" 
                                                value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="telephone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="telephone" name="telephone" 
                                                value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Rôle *</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="client" <?php echo ($user['role'] === 'client') ? 'selected' : ''; ?>>Client</option>
                                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5>Adresse</h5>
                                        
                                        <div class="mb-3">
                                            <label for="adresse" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" id="adresse" name="adresse" 
                                                value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="code_postal" class="form-label">Code postal</label>
                                                <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                                    value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-8 mb-3">
                                                <label for="ville" class="form-label">Ville</label>
                                                <input type="text" class="form-control" id="ville" name="ville" 
                                                    value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="pays" class="form-label">Pays</label>
                                            <input type="text" class="form-control" id="pays" name="pays" 
                                                value="<?php echo htmlspecialchars($user['pays'] ?? 'France'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="utilisateurs.php" class="btn btn-secondary">Annuler</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
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
    <script>
        // Fonction de recherche d'utilisateurs
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('usersTable');
                    const rows = table.getElementsByTagName('tr');
                    
                    for (let i = 1; i < rows.length; i++) {
                        const cells = rows[i].getElementsByTagName('td');
                        let found = false;
                        
                        for (let j = 0; j < cells.length; j++) {
                            const cellText = cells[j].textContent.toLowerCase();
                            if (cellText.indexOf(searchValue) > -1) {
                                found = true;
                                break;
                            }
                        }
                        
                        rows[i].style.display = found ? '' : 'none';
                    }
                });
            }
        });
    </script>
</body>
</html> 