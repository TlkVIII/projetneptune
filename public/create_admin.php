<?php
// Script temporaire pour créer un compte administrateur
require_once '../config/database.php';

// Informations de l'administrateur
$nom = 'Fayed';
$prenom = 'Admin';
$email = 'fayed.amourani@ecoles-epsi.net';
$mot_de_passe = 'Fay0fayed';

try {
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // L'utilisateur existe déjà, mettre à jour ses informations
        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, password = ?, role = 'admin' WHERE email = ?");
        $stmt->execute([$nom, $prenom, $hashed_password, $email]);
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>
              <h3>Compte administrateur mis à jour</h3>
              <p>Le compte administrateur existant a été mis à jour avec succès.</p>
              <ul>
                <li><strong>Nom :</strong> $nom</li>
                <li><strong>Prénom :</strong> $prenom</li>
                <li><strong>Email :</strong> $email</li>
                <li><strong>Rôle :</strong> Administrateur</li>
              </ul>
              <p><a href='login.php'>Se connecter</a></p>
              </div>";
    } else {
        // Créer un nouveau compte administrateur
        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role, date_creation) VALUES (?, ?, ?, ?, 'admin', NOW())");
        $stmt->execute([$nom, $prenom, $email, $hashed_password]);
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>
              <h3>Compte administrateur créé</h3>
              <p>Un nouveau compte administrateur a été créé avec succès.</p>
              <ul>
                <li><strong>Nom :</strong> $nom</li>
                <li><strong>Prénom :</strong> $prenom</li>
                <li><strong>Email :</strong> $email</li>
                <li><strong>Rôle :</strong> Administrateur</li>
              </ul>
              <p><a href='login.php'>Se connecter</a></p>
              </div>";
    }
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>
          <h3>Erreur</h3>
          <p>Une erreur est survenue lors de la création du compte administrateur :</p>
          <p>" . $e->getMessage() . "</p>
          </div>";
}

// Supprimer ce script après utilisation pour des raisons de sécurité
echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px;'>
      <h3>Avertissement de sécurité</h3>
      <p>Pour des raisons de sécurité, veuillez supprimer ce fichier après utilisation.</p>
      </div>";
?> 