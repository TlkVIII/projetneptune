<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user = null;
$message = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = '<div class="alert alert-danger">Utilisateur non trouvé.</div>';
    }
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Une erreur technique est survenue lors du chargement du profil.</div>';
}

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $code_postal = $_POST['code_postal'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $pays = $_POST['pays'] ?? 'France';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format d'email invalide";
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé par un autre utilisateur";
        }
    }
    
    // Validation du mot de passe si l'utilisateur souhaite le changer
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Vous devez saisir votre mot de passe actuel pour le changer";
        } else {
            // Vérification obligatoire du mot de passe actuel
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = "Le mot de passe actuel est incorrect";
            }
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas";
        }
    }
    
    // Si pas d'erreurs, mettre à jour le profil
    if (empty($errors)) {
        try {
            // Préparation des données pour la mise à jour
            $updateData = [
                $nom,
                $prenom,
                $email,
                $telephone,
                $adresse,
                $code_postal,
                $ville,
                $pays
            ];
            
            $sql = "UPDATE users SET 
                   nom = ?, prenom = ?, email = ?, 
                   telephone = ?, adresse = ?, code_postal = ?, 
                   ville = ?, pays = ?
                   WHERE id = ?";
            
            // Si le mot de passe doit être changé
            if (!empty($new_password)) {
                $sql = "UPDATE users SET 
                       nom = ?, prenom = ?, email = ?, 
                       telephone = ?, adresse = ?, code_postal = ?, 
                       ville = ?, pays = ?, password = ?
                       WHERE id = ?";
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateData[] = $hashed_password;
            }
            $updateData[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateData);
            
            // Mettre à jour les informations de session
            $_SESSION['user_name'] = $prenom . ' ' . $nom;
            
            $message = '<div class="alert alert-success">Votre profil a été mis à jour avec succès.</div>';
            
            // Recharger les données de l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur technique lors de la mise à jour du profil.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) {
            $message .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $message .= '</ul></div>';
    }
}

// Traitement du formulaire de suppression du compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'] ?? '';
    
    if ($confirm_delete === 'supprimer') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Déconnecter l'utilisateur
            session_destroy();
            
            // Rediriger vers la page d'accueil
            header('Location: index.php?message=' . urlencode('Votre compte a été supprimé avec succès.'));
            exit;
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur technique lors de la suppression du compte.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Veuillez écrire "supprimer" pour confirmer la suppression de votre compte.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            padding-top: 76px;
            background-color: #f8f9fa;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .profile-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            margin: 0 auto 1rem;
            border: 5px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .nav-tabs .nav-item .nav-link {
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-item .nav-link.active {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
            border-bottom-color: transparent;
            font-weight: 500;
        }
        
        .reservation-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-en_attente {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-confirmee {
            background-color: #28a745;
            color: #fff;
        }
        
        .status-annulee {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container profile-container">
        <!-- Header du profil -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
            </div>
            <h1><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h1>
            <p class="mb-0">Membre depuis <?php echo date('M Y', strtotime($user['date_creation'] ?? 'now')); ?></p>
        </div>

        <!-- Contenu du profil -->
        <div class="profile-content">
            <?php if (!empty($message)): ?>
                <?php echo $message; ?>
            <?php endif; ?>

            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                        <i class="fas fa-user me-2"></i>Mon Profil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab" aria-controls="reservations" aria-selected="false">
                        <i class="fas fa-calendar-alt me-2"></i>Mes Réservations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">
                        <i class="fas fa-cog me-2"></i>Paramètres
                    </button>
                </li>
            </ul>

            <!-- Contenu des onglets -->
            <div class="tab-content" id="profileTabsContent">
                <!-- Onglet Profil -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4 class="mb-3">Informations personnelles</h4>
                                
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h4 class="mb-3">Adresse</h4>
                                
                                <div class="mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="code_postal" class="form-label">Code postal</label>
                                            <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="ville" class="form-label">Ville</label>
                                            <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="pays" class="form-label">Pays</label>
                                    <input type="text" class="form-control" id="pays" name="pays" value="<?php echo htmlspecialchars($user['pays'] ?? 'France'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Onglet Réservations -->
                <div class="tab-pane fade" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
                    <h4 class="mb-4">Mes réservations</h4>
                    
                    <?php
                    // Récupérer les réservations de l'utilisateur
                    $reservations = [];
                    try {
                        $stmt = $pdo->prepare("
                            SELECT r.*, c.nom as chambre_nom, c.prix, c.image
                            FROM reservations r
                            JOIN chambres c ON r.chambre_id = c.id
                            WHERE r.user_id = ?
                            ORDER BY r.date_arrivee DESC
                        ");
                        $stmt->execute([$user_id]);
                        $reservations = $stmt->fetchAll();
                    } catch(PDOException $e) {
                        echo '<div class="alert alert-danger">Erreur lors de la récupération des réservations : ' . $e->getMessage() . '</div>';
                    }
                    
                    if (empty($reservations)):
                    ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                            <h5>Vous n'avez pas encore de réservation</h5>
                            <p class="text-muted">Découvrez nos chambres et réservez votre séjour dès maintenant.</p>
                            <a href="chambres.php" class="btn btn-primary mt-2">
                                <i class="fas fa-bed me-2"></i>Voir nos chambres
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($reservations as $reservation): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card reservation-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($reservation['chambre_nom']); ?></h5>
                                            <span class="status-badge status-<?php echo $reservation['statut']; ?>">
                                                <?php
                                                switch($reservation['statut']) {
                                                    case 'en_attente':
                                                        echo 'En attente';
                                                        break;
                                                    case 'confirmee':
                                                        echo 'Confirmée';
                                                        break;
                                                    case 'annulee':
                                                        echo 'Annulée';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <img src="images/rooms/<?php echo htmlspecialchars($reservation['image']); ?>" 
                                                         class="img-fluid rounded" 
                                                         alt="<?php echo htmlspecialchars($reservation['chambre_nom']); ?>">
                                                </div>
                                                <div class="col-md-7">
                                                    <p><i class="fas fa-calendar-alt me-2"></i><strong>Arrivée:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></p>
                                                    <p><i class="fas fa-calendar-alt me-2"></i><strong>Départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                                                    <p><i class="fas fa-users me-2"></i><strong>Personnes:</strong> <?php echo $reservation['nombre_personnes']; ?></p>
                                                    <p><i class="fas fa-euro-sign me-2"></i><strong>Prix:</strong> <?php echo number_format($reservation['prix'], 2, ',', ' '); ?>€/nuit</p>
                                                    
                                                    <?php if ($reservation['statut'] === 'en_attente'): ?>
                                                        <button class="btn btn-outline-danger btn-sm mt-2" 
                                                                onclick="annulerReservation(<?php echo $reservation['id']; ?>)">
                                                            <i class="fas fa-times me-1"></i>Annuler
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($reservation['demandes_speciales'])): ?>
                                                <div class="mt-3">
                                                    <strong>Demandes spéciales:</strong>
                                                    <p class="text-muted"><?php echo htmlspecialchars($reservation['demandes_speciales']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Onglet Paramètres -->
                <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Changer de mot de passe</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="update_profile" value="1">
                                        <!-- Champs cachés pour conserver les données actuelles -->
                                        <input type="hidden" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>">
                                        <input type="hidden" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                        <input type="hidden" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                        <input type="hidden" name="adresse" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                                        <input type="hidden" name="code_postal" value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>">
                                        <input type="hidden" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                                        <input type="hidden" name="pays" value="<?php echo htmlspecialchars($user['pays'] ?? 'France'); ?>">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                            <div class="form-text text-muted">Pour les tests, vous pouvez utiliser n'importe quel mot de passe.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                            <div class="form-text">Minimum 8 caractères</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-trash-alt me-2"></i>Supprimer mon compte</h5>
                                </div>
                                <div class="card-body">
                                    <p>Attention : la suppression de votre compte est irréversible et entraînera la perte de toutes vos données et réservations.</p>
                                    
                                    <form method="POST" action="" onsubmit="return confirm('Êtes-vous vraiment sûr de vouloir supprimer votre compte ? Cette action est irréversible.')">
                                        <input type="hidden" name="delete_account" value="1">
                                        
                                        <div class="mb-3">
                                            <label for="confirm_delete" class="form-label">Tapez "supprimer" pour confirmer</label>
                                            <input type="text" class="form-control" id="confirm_delete" name="confirm_delete" required>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash-alt me-2"></i>Supprimer mon compte
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function annulerReservation(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                const formData = new FormData();
                formData.append('reservation_id', reservationId);
                
                fetch('annuler_reservation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Réservation annulée avec succès.');
                        // Recharger la page pour mettre à jour l'affichage
                        window.location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'annulation de la réservation.');
                });
            }
        }
    </script>
</body>
</html> 