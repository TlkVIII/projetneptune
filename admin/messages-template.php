<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des messages - Administration - Hôtel Neptune</title>
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
            background-color: #f5f5f5;
        }
        
        .sidebar {
            background: var(--primary-color);
            color: white;
            min-height: 100vh;
            padding-top: 20px;
        }
        
        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px 15px;
            display: block;
        }
        
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .admin-brand {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            padding: 0 15px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .content {
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-unread {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-read {
            background-color: #2ecc71;
            color: white;
        }
        
        .nav-badge {
            position: absolute;
            top: 8px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 10px;
        }
        
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nav-item {
            position: relative;
        }
        
        .message-detail {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .message-content {
            white-space: pre-line;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        
        .reply-form {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stats-total {
            background: linear-gradient(45deg, #3498db, #2c3e50);
        }
        
        .stats-unread {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        
        .stats-read {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        
        .stats-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }

        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 260px;
                z-index: 1040;
                overflow-y: auto;
            }
            .content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="admin-brand text-center">
                <i class="fas fa-hotel"></i> Hôtel Neptune
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chambres.php" class="nav-link">
                        <i class="fas fa-bed me-2"></i> Chambres
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reservations.php" class="nav-link">
                        <i class="fas fa-calendar-check me-2"></i> Réservations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="utilisateurs.php" class="nav-link">
                        <i class="fas fa-users me-2"></i> Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link active">
                        <i class="fas fa-envelope me-2"></i> Messages
                        <?php if ($compteurs['non_lus'] > 0): ?>
                            <span class="nav-badge"><?= $compteurs['non_lus'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> Voir le site
                    </a>
                </li>
                <li class="nav-item mt-5">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1><i class="fas fa-envelope me-2"></i> Gestion des messages</h1>
                <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                    <i class="fas fa-bars me-1"></i> Menu
                </button>
            </div>
            
            <?php if (isset($message)): ?>
                <?= $message ?>
            <?php endif; ?>
            
            <?php if ($message_details): ?>
                <!-- Affichage d'un message spécifique -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-envelope-open-text me-2"></i> 
                                    Message de <?= htmlspecialchars($message_details['nom']) ?>
                                </div>
                                <div>
                                    <a href="messages.php" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="message-detail">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong><i class="fas fa-user me-2"></i> De :</strong> <?= htmlspecialchars($message_details['nom']) ?></p>
                                            <p><strong><i class="fas fa-envelope me-2"></i> Email :</strong> <?= htmlspecialchars($message_details['email']) ?></p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <p><strong><i class="fas fa-calendar me-2"></i> Date :</strong> <?= date('d/m/Y à H:i', strtotime($message_details['date_envoi'])) ?></p>
                                            <p>
                                                <span class="status-badge <?= $message_details['lu'] ? 'status-read' : 'status-unread' ?>">
                                                    <i class="fas <?= $message_details['lu'] ? 'fa-envelope-open' : 'fa-envelope' ?> me-1"></i>
                                                    <?= $message_details['lu'] ? 'Lu' : 'Non lu' ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mt-3"><i class="fas fa-heading me-2"></i> Sujet : <?= htmlspecialchars($message_details['sujet']) ?></h5>
                                    
                                    <div class="message-content">
                                        <?= nl2br(htmlspecialchars($message_details['message'])) ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <?php if ($message_details['lu']): ?>
                                                <a href="messages.php?action=marquer_non_lu&id=<?= $message_details['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-envelope me-1"></i> Marquer comme non lu
                                                </a>
                                            <?php else: ?>
                                                <a href="messages.php?action=marquer_lu&id=<?= $message_details['id'] ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-envelope-open me-1"></i> Marquer comme lu
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="messages.php?action=supprimer&id=<?= $message_details['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                                <i class="fas fa-trash me-1"></i> Supprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($message_details['repondu'])): ?>
                                    <!-- Afficher la réponse -->
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <i class="fas fa-reply me-2"></i> Votre réponse (<?= date('d/m/Y à H:i', strtotime($message_details['date_reponse'])) ?>)
                                        </div>
                                        <div class="card-body">
                                            <div class="message-content">
                                                <?= nl2br(htmlspecialchars($message_details['reponse'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Formulaire de réponse -->
                                    <div class="reply-form mt-4">
                                        <h5><i class="fas fa-reply me-2"></i> Répondre à ce message</h5>
                                        <form method="post" action="messages.php">
                                            <input type="hidden" name="message_id" value="<?= $message_details['id'] ?>">
                                            <div class="mb-3">
                                                <label for="reponse" class="form-label">Votre réponse :</label>
                                                <textarea name="reponse" id="reponse" class="form-control" rows="6" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i> Envoyer la réponse
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Liste des messages -->
                <div class="row mb-4">
                    <!-- Filtres et recherche -->
                    <div class="col-md-8 mb-3">
                        <form action="messages.php" method="get" class="d-flex">
                            <select name="filtre" class="form-select me-2" style="max-width: 200px;">
                                <option value="tous" <?= $filtre === 'tous' ? 'selected' : '' ?>>Tous les messages</option>
                                <option value="non_lus" <?= $filtre === 'non_lus' ? 'selected' : '' ?>>Non lus uniquement</option>
                                <option value="lus" <?= $filtre === 'lus' ? 'selected' : '' ?>>Lus uniquement</option>
                            </select>
                            <div class="input-group">
                                <input type="text" name="recherche" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($recherche) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card stats-total">
                            <div><i class="fas fa-envelope fa-2x"></i></div>
                            <div class="stats-value"><?= $compteurs['total'] ?></div>
                            <div>Total des messages</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card stats-unread">
                            <div><i class="fas fa-envelope-circle-check fa-2x"></i></div>
                            <div class="stats-value"><?= $compteurs['non_lus'] ?></div>
                            <div>Messages non lus</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card stats-read">
                            <div><i class="fas fa-envelope-open fa-2x"></i></div>
                            <div class="stats-value"><?= $compteurs['lus'] ?></div>
                            <div>Messages lus</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-envelope-open-text me-2"></i> Liste des messages
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages_liste)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Aucun message trouvé.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Statut</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Sujet</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($messages_liste as $msg): ?>
                                        <tr class="<?= $msg['lu'] ? '' : 'table-light fw-bold' ?>">
                                            <td><?= $msg['id'] ?></td>
                                            <td>
                                                <span class="status-badge <?= $msg['lu'] ? 'status-read' : 'status-unread' ?>">
                                                    <i class="fas <?= $msg['lu'] ? 'fa-envelope-open' : 'fa-envelope' ?>"></i>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($msg['nom']) ?></td>
                                            <td><?= htmlspecialchars($msg['email']) ?></td>
                                            <td><?= htmlspecialchars($msg['sujet']) ?></td>
                                            <td class="message-preview"><?= htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="messages.php?action=voir&id=<?= $msg['id'] ?>" class="btn btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($msg['lu']): ?>
                                                        <a href="messages.php?action=marquer_non_lu&id=<?= $msg['id'] ?>" class="btn btn-info" title="Marquer comme non lu">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="messages.php?action=marquer_lu&id=<?= $msg['id'] ?>" class="btn btn-success" title="Marquer comme lu">
                                                            <i class="fas fa-envelope-open"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="messages.php?action=supprimer&id=<?= $msg['id'] ?>" 
                                                       class="btn btn-danger" 
                                                       title="Supprimer"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 