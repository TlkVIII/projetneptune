<?php
// Assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);

// Chemin relatif à la racine pour les ressources
$root_path = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $root_path = '../';
}

$adminRepliesUnread = 0;
if (isset($_SESSION['user_id'], $pdo)) {
    try {
        $stmtUserEmail = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmtUserEmail->execute([$_SESSION['user_id']]);
        $sessionUserEmail = (string)($stmtUserEmail->fetchColumn() ?: '');

        if ($sessionUserEmail !== '') {
            $stmtNotif = $pdo->prepare("
                SELECT COUNT(*)
                FROM messages
                WHERE email = ?
                  AND repondu = 1
                  AND COALESCE(reponse_lue_client, 0) = 0
            ");
            $stmtNotif->execute([$sessionUserEmail]);
            $adminRepliesUnread = (int)$stmtNotif->fetchColumn();
        }
    } catch (PDOException $e) {
        $adminRepliesUnread = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="<?= $root_path ?>css/style.css">
    <?php if (isset($additional_styles)): ?>
        <?= $additional_styles ?>
    <?php endif; ?>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(44, 62, 80, 0.95);">
    <div class="container">
        <a class="navbar-brand" href="<?= $root_path ?>index.php">
            <i class="fas fa-hotel me-2"></i>Hôtel Neptune
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>" href="<?= $root_path ?>index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'chambres.php') ? 'active' : '' ?>" href="<?= $root_path ?>chambres.php">Chambres</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'services.php') ? 'active' : '' ?>" href="<?= $root_path ?>services.php">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'contact.php') ? 'active' : '' ?>" href="<?= $root_path ?>contact.php">Contact</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item <?= ($current_page === 'reservation.php') ? 'active' : '' ?>">
                        <a class="nav-link" href="<?= $root_path ?>reservation.php">Réservation</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            <?php if ($adminRepliesUnread > 0): ?>
                                <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem; top: 12px; right: -4px;">
                                    <?= $adminRepliesUnread > 99 ? '99+' : $adminRepliesUnread ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $root_path ?>admin/index.php"><i class="fas fa-cog me-2"></i>Administration</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $root_path ?>profile.php"><i class="fas fa-user-circle me-2"></i>Mon profil</a></li>
                            <li><a class="dropdown-item" href="<?= $root_path ?>reservations.php"><i class="fas fa-calendar-alt me-2"></i>Mes réservations</a></li>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?= $root_path ?>messages.php">
                                    <span><i class="fas fa-envelope me-2"></i>Mes messages</span>
                                    <?php if ($adminRepliesUnread > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $adminRepliesUnread > 99 ? '99+' : $adminRepliesUnread ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $root_path ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'login.php') ? 'active' : '' ?>" href="<?= $root_path ?>login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'register.php') ? 'active' : '' ?>" href="<?= $root_path ?>register.php">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 