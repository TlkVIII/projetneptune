<?php
session_start();
require_once '../config/database.php';

try {
    $columns = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('repondu', $columns, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN repondu TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('reponse', $columns, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN reponse TEXT NULL");
    }
    if (!in_array('date_reponse', $columns, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN date_reponse DATETIME NULL");
    }
    if (!in_array('reponse_lue_client', $columns, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN reponse_lue_client TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('conversation_id', $columns, true)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN conversation_id INT NULL");
    }
} catch (PDOException $e) {
    // Ignorer.
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userEmail = '';
$userNom = '';
$userPrenom = '';
$messages = [];
$messageDetails = null;
$conversationItems = [];
$error = '';
$success = '';

try {
    $stmt = $pdo->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $userEmail = (string)($userData['email'] ?? '');
    $userNom = (string)($userData['nom'] ?? '');
    $userPrenom = (string)($userData['prenom'] ?? '');

    if ($userEmail === '') {
        throw new Exception("Adresse email introuvable.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'], $_POST['reply_text'])) {
        $replyMessageId = (int) $_POST['reply_message_id'];
        $replyText = trim((string) $_POST['reply_text']);

        if ($replyMessageId > 0 && $replyText !== '') {
            $stmt = $pdo->prepare("
                SELECT id, sujet, COALESCE(conversation_id, id) AS conv_id
                FROM messages
                WHERE id = ? AND email = ? AND repondu = 1
            ");
            $stmt->execute([$replyMessageId, $userEmail]);
            $originalMessage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($originalMessage) {
                $replySubject = 'RE: ' . (string)$originalMessage['sujet'];
                $stmt = $pdo->prepare("
                    INSERT INTO messages (nom, email, sujet, message, lu, repondu, conversation_id)
                    VALUES (?, ?, ?, ?, 0, 0, ?)
                ");
                $stmt->execute([
                    trim($userPrenom . ' ' . $userNom),
                    $userEmail,
                    $replySubject,
                    $replyText,
                    (int)$originalMessage['conv_id']
                ]);
                $success = "Votre réponse a été envoyée à l'administration.";
            } else {
                $error = "Message d'origine introuvable.";
            }
        } else {
            $error = "Veuillez saisir un message de réponse.";
        }
    }

    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'voir') {
        $messageId = (int) $_GET['id'];
        $stmt = $pdo->prepare("
            SELECT id, sujet, message, reponse, date_envoi, date_reponse, reponse_lue_client, COALESCE(conversation_id, id) AS conv_id
            FROM messages
            WHERE id = ? AND email = ? AND repondu = 1
        ");
        $stmt->execute([$messageId, $userEmail]);
        $messageDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($messageDetails) {
            $stmt = $pdo->prepare("UPDATE messages SET reponse_lue_client = 1 WHERE id = ?");
            $stmt->execute([$messageId]);
            $messageDetails['reponse_lue_client'] = 1;

            $convId = (int)$messageDetails['conv_id'];
            $stmt = $pdo->prepare("
                SELECT id, sujet, message, reponse, date_envoi, date_reponse, repondu
                FROM messages
                WHERE email = ?
                  AND COALESCE(conversation_id, id) = ?
                ORDER BY date_envoi ASC, id ASC
            ");
            $stmt->execute([$userEmail, $convId]);
            $conversationRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($conversationRows as $row) {
                $conversationItems[] = [
                    'sender' => 'client',
                    'content' => (string)$row['message'],
                    'date' => $row['date_envoi'],
                ];

                if (!empty($row['repondu']) && !empty($row['reponse'])) {
                    $conversationItems[] = [
                        'sender' => 'admin',
                        'content' => (string)$row['reponse'],
                        'date' => $row['date_reponse'] ?: $row['date_envoi'],
                    ];
                }
            }
        }
    }

    if (!$messageDetails) {
        $stmt = $pdo->prepare("
            SELECT id, sujet, message, reponse, date_envoi, date_reponse, reponse_lue_client
            FROM messages
            WHERE email = ? AND repondu = 1
            ORDER BY date_reponse DESC, date_envoi DESC
        ");
        $stmt->execute([$userEmail]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Erreur lors du chargement des messages.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes messages - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { padding-top: 76px; background: #f8f9fa; }
        .messages-container { max-width: 980px; margin: 2rem auto; }
        .message-card { border: 0; border-radius: 14px; box-shadow: 0 8px 22px rgba(44,62,80,.1); }
        .message-unread { border-left: 5px solid #dc3545; }
        .message-read { border-left: 5px solid #28a745; }
        .thread {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e6edf7;
            padding: 1rem;
        }
        .bubble {
            max-width: 82%;
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
            margin-bottom: 0.75rem;
            line-height: 1.45;
        }
        .bubble-client {
            margin-right: auto;
            background: #f4f7fb;
            border: 1px solid #e3e9f3;
        }
        .bubble-admin {
            margin-left: auto;
            background: #e8f3ff;
            border: 1px solid #cfe4ff;
        }
        .bubble-meta {
            display: block;
            margin-top: 0.4rem;
            font-size: 0.78rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container messages-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-envelope me-2"></i>Mes messages</h2>
        <?php if ($messageDetails): ?>
            <a href="messages.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Retour à la liste</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($messageDetails): ?>
        <div class="card message-card">
            <div class="card-body">
                <h4><?= htmlspecialchars($messageDetails['sujet']) ?></h4>
                <p class="text-muted mb-3"><i class="fas fa-calendar me-2"></i>Envoyé le <?= date('d/m/Y H:i', strtotime($messageDetails['date_envoi'])) ?></p>
                <h6 class="mb-3">Fil de discussion</h6>
                <div class="thread mb-3">
                    <?php foreach ($conversationItems as $item): ?>
                        <div class="bubble <?= $item['sender'] === 'admin' ? 'bubble-admin' : 'bubble-client' ?>">
                            <strong><?= $item['sender'] === 'admin' ? 'Administration' : 'Vous' ?></strong><br>
                            <?= nl2br(htmlspecialchars($item['content'])) ?>
                            <span class="bubble-meta">
                                <i class="fas fa-clock me-1"></i><?= !empty($item['date']) ? date('d/m/Y H:i', strtotime($item['date'])) : '-' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">
                <div>
                    <h6><i class="fas fa-reply me-2"></i>Répondre à l'administration</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="reply_message_id" value="<?= (int)$messageDetails['id'] ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="reply_text" rows="4" placeholder="Écrivez votre réponse..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer ma réponse
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif (empty($messages)): ?>
        <div class="alert alert-info">Vous n'avez pas encore de réponse de l'administration.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($messages as $m): ?>
                <div class="col-12">
                    <div class="card message-card <?= ((int)$m['reponse_lue_client'] === 0) ? 'message-unread' : 'message-read' ?>">
                        <div class="card-body d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($m['sujet']) ?></h5>
                                <p class="mb-2 text-muted"><?= htmlspecialchars(strlen((string)$m['reponse']) > 120 ? substr((string)$m['reponse'], 0, 120) . '...' : (string)$m['reponse']) ?></p>
                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?= !empty($m['date_reponse']) ? date('d/m/Y H:i', strtotime($m['date_reponse'])) : '-' ?></small>
                            </div>
                            <a href="messages.php?action=voir&id=<?= (int)$m['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Lire
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
