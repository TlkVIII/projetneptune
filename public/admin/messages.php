<?php
session_start();
require_once '../../config/database.php';

// Migration légère pour compatibilité des colonnes de réponse admin.
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
    // Ignorer si déjà en place ou si la migration échoue.
}

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Initialiser les variables
$message = '';
$message_details = null;
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$messages_liste = [];
$compteurs = [
    'total' => 0,
    'non_lus' => 0,
    'lus' => 0
];

try {
    // Récupérer les compteurs de messages
    $sql_compteurs = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN lu = 0 THEN 1 ELSE 0 END) as non_lus,
                        SUM(CASE WHEN lu = 1 THEN 1 ELSE 0 END) as lus
                      FROM messages";
    $stmt_compteurs = $pdo->prepare($sql_compteurs);
    $stmt_compteurs->execute();
    $compteurs = $stmt_compteurs->fetch(PDO::FETCH_ASSOC);
    
    // Gérer les actions
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($message_id > 0) {
            // Voir un message
            if ($action === 'voir') {
                // Récupérer les détails du message
                $sql = "SELECT * FROM messages WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                $message_details = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Marquer comme lu si pas encore lu
                if ($message_details && !$message_details['lu']) {
                    $sql_update = "UPDATE messages SET lu = 1 WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':id', $message_id, PDO::PARAM_INT);
                    $stmt_update->execute();
                    $message_details['lu'] = 1;
                }
            }
            // Marquer comme lu
            elseif ($action === 'marquer_lu') {
                $sql = "UPDATE messages SET lu = 1 WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Message marqué comme lu.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            // Marquer comme non lu
            elseif ($action === 'marquer_non_lu') {
                $sql = "UPDATE messages SET lu = 0 WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Message marqué comme non lu.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
            // Supprimer un message
            elseif ($action === 'supprimer') {
                $sql = "DELETE FROM messages WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Message supprimé avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
        }
    }
    
    // Traiter la réponse à un message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id']) && isset($_POST['reponse'])) {
        $message_id = intval($_POST['message_id']);
        $reponse = trim($_POST['reponse']);
        
        if (!empty($reponse)) {
            // Récupérer les informations du message
            $sql_message = "SELECT * FROM messages WHERE id = :id";
            $stmt_message = $pdo->prepare($sql_message);
            $stmt_message->bindParam(':id', $message_id, PDO::PARAM_INT);
            $stmt_message->execute();
            $msg_data = $stmt_message->fetch(PDO::FETCH_ASSOC);
            
            if ($msg_data) {
                // Mettre à jour le message comme répondu
                $sql_update = "UPDATE messages SET 
                               repondu = 1, 
                               reponse = :reponse, 
                               date_reponse = NOW(),
                               reponse_lue_client = 0,
                               lu = 1
                               WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':reponse', $reponse, PDO::PARAM_STR);
                $stmt_update->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt_update->execute();
                
                // Envoyer l'email de réponse (à implémenter selon vos besoins)
                $destinataire = $msg_data['email'];
                $sujet = "RE: " . $msg_data['sujet'] . " - Hôtel Neptune";
                
                $corps_message = "
                <html>
                <head>
                    <title>Réponse à votre message - Hôtel Neptune</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h2 style='color: #2c3e50;'>Hôtel Neptune</h2>
                            <p style='color: #7f8c8d;'>Réponse à votre message</p>
                        </div>
                        
                        <p>Bonjour " . htmlspecialchars($msg_data['nom']) . ",</p>
                        
                        <p>Nous vous remercions pour votre message concernant <strong>\"" . htmlspecialchars($msg_data['sujet']) . "\"</strong>.</p>
                        
                        <div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2c3e50; margin: 20px 0;'>
                            <p><strong>Votre message :</strong></p>
                            <p style='color: #555;'>" . nl2br(htmlspecialchars($msg_data['message'])) . "</p>
                        </div>
                        
                        <div style='background-color: #f0f7fb; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;'>
                            <p><strong>Notre réponse :</strong></p>
                            <p>" . nl2br(htmlspecialchars($reponse)) . "</p>
                        </div>
                        
                        <p>Si vous avez d'autres questions, n'hésitez pas à nous contacter.</p>
                        
                        <p>Cordialement,</p>
                        <p>L'équipe de l'Hôtel Neptune</p>
                        
                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center;'>
                            <p>Ceci est un email automatique, merci de ne pas y répondre directement.</p>
                            <p>© 2023 Hôtel Neptune - Tous droits réservés</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Hôtel Neptune <fayed.amourani8@gmail.com>" . "\r\n";
                
                // Envoi de l'email (décommentez la ligne suivante pour activer l'envoi)
                // mail($destinataire, $sujet, $corps_message, $headers);
                
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Votre réponse a été envoyée avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                            
                // Recharger les détails du message pour montrer la réponse
                $sql = "SELECT * FROM messages WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
                $stmt->execute();
                $message_details = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                       <i class="fas fa-exclamation-triangle me-2"></i>Veuillez saisir une réponse.
                       <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                       </div>';
        }
    }
    
    // Si aucun message spécifique n'est affiché, liste tous les messages
    if (!$message_details) {
        // Construire la requête SQL pour la liste des messages
        $sql = "SELECT * FROM messages WHERE 1=1";
        $params = [];
        
        // Ajouter les filtres
        if ($filtre === 'non_lus') {
            $sql .= " AND lu = 0";
        } else if ($filtre === 'lus') {
            $sql .= " AND lu = 1";
        }
        
        // Ajouter la recherche
        if (!empty($recherche)) {
            $sql .= " AND (nom LIKE :recherche OR email LIKE :recherche OR sujet LIKE :recherche OR message LIKE :recherche)";
            $params[':recherche'] = '%' . $recherche . '%';
        }
        
        // Ordonner par date, non lus en premier
        $sql .= " ORDER BY lu ASC, date_envoi DESC";
        
        // Exécuter la requête
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $messages_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Erreur : ' . $e->getMessage() . '</div>';
}

// Inclure le template HTML
include 'messages-template.php';
?> 