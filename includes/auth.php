<?php
/**
 * Gestion de l'authentification des utilisateurs
 * Fichier : includes/auth.php
 */

require_once 'database.php';

/**
 * Inscription d'un nouvel utilisateur client
 * @param array $donnees Données de l'utilisateur
 * @return int|false ID de l'utilisateur créé ou false en cas d'échec
 */
function inscrireUtilisateur($donnees) {
    // Vérifier si l'email existe déjà
    $utilisateur = getUtilisateurByEmail($donnees['email']);
    if ($utilisateur) {
        return false; // L'email est déjà utilisé
    }
    
    // Hashage du mot de passe
    $donnees['mot_de_passe'] = password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT);
    
    // Ajouter le rôle client par défaut si non spécifié
    if (!isset($donnees['role']) || !in_array($donnees['role'], ['client', 'admin'])) {
        $donnees['role'] = 'client';
    }
    
    // Créer l'utilisateur
    return insert('utilisateurs', $donnees);
}

/**
 * Connexion d'un utilisateur
 * @param string $email Email de l'utilisateur
 * @param string $motDePasse Mot de passe de l'utilisateur
 * @return array|false Données de l'utilisateur si connexion réussie, sinon false
 */
function connecterUtilisateur($email, $motDePasse) {
    // Débogage - Logger les informations
    error_log("Tentative de connexion pour l'email: " . $email);
    
    $utilisateur = getUtilisateurByEmail($email);
    
    if (!$utilisateur) {
        error_log("Utilisateur non trouvé avec cet email");
        return false;
    }
    
    error_log("Utilisateur trouvé - ID: " . $utilisateur['id'] . ", Rôle: " . ($utilisateur['role'] ?? 'non défini'));
    
    // Vérifier si le champ mot_de_passe existe
    if (!isset($utilisateur['mot_de_passe'])) {
        error_log("Erreur: Le champ mot_de_passe n'existe pas pour cet utilisateur");
        return false;
    }
    
    // Vérifier le mot de passe
    $passwordMatch = password_verify($motDePasse, $utilisateur['mot_de_passe']);
    error_log("Vérification du mot de passe: " . ($passwordMatch ? "RÉUSSIE" : "ÉCHOUÉE"));
    
    if ($passwordMatch) {
        // Mettre à jour la date de dernière connexion
        update('utilisateurs', 
               ['derniere_connexion' => date('Y-m-d H:i:s')], 
               'id = ?', 
               [$utilisateur['id']]);
        
        // Ne pas renvoyer le mot de passe
        unset($utilisateur['mot_de_passe']);
        
        return $utilisateur;
    }
    
    return false;
}

/**
 * Récupère un utilisateur par son email
 * @param string $email Email de l'utilisateur
 * @return array|null Données de l'utilisateur ou null si non trouvé
 */
function getUtilisateurByEmail($email) {
    return fetchOne("SELECT * FROM utilisateurs WHERE email = ?", [$email]);
}

/**
 * Récupère un utilisateur par son ID
 * @param int $id ID de l'utilisateur
 * @return array|null Données de l'utilisateur ou null si non trouvé
 */
function getUtilisateurById($id) {
    return fetchOne("SELECT * FROM utilisateurs WHERE id = ?", [$id]);
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function estConnecte() {
    return isset($_SESSION['utilisateur']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string $role Rôle à vérifier
 * @return bool True si l'utilisateur a le rôle, false sinon
 */
function estRole($role) {
    if (!estConnecte()) {
        return false;
    }
    
    $utilisateur = getUtilisateurById($_SESSION['utilisateur']['id']);
    return $utilisateur && $utilisateur['role'] === $role;
}

/**
 * Déconnecte l'utilisateur
 */
function deconnecterUtilisateur() {
    unset($_SESSION['utilisateur']);
    session_regenerate_id(true);
}

/**
 * Met à jour le mot de passe d'un utilisateur
 * @param int $utilisateurId ID de l'utilisateur
 * @param string $nouveauMotDePasse Nouveau mot de passe
 * @return bool True si la mise à jour a réussi, false sinon
 */
function mettreAJourMotDePasse($utilisateurId, $nouveauMotDePasse) {
    $motDePasseHash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
    $result = update('utilisateurs', 
                    ['mot_de_passe' => $motDePasseHash], 
                    'id = ?', 
                    [$utilisateurId]);
    
    return $result > 0;
}

/**
 * Vérifie si l'utilisateur est administrateur
 * 
 * @return bool
 */
function estAdmin() {
    return estConnecte() && isset($_SESSION['utilisateur']['role']) && $_SESSION['utilisateur']['role'] === 'admin';
}

/**
 * Rediriger si l'utilisateur n'est pas connecté
 * 
 * @param string $redirect_url URL vers laquelle rediriger après connexion
 * @return void
 */
function necessiteConnexion($redirect_url = null) {
    if (!estConnecte()) {
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * Rediriger si l'utilisateur n'est pas administrateur
 * 
 * @return void
 */
function necessiteAdmin() {
    necessiteConnexion();
    if (!estAdmin()) {
        $_SESSION['erreur'] = "Vous n'avez pas les droits pour accéder à cette page.";
        header('Location: ../index.php');
        exit;
    }
}

/**
 * Enregistre un nouvel utilisateur
 * 
 * @param string $nom Nom de l'utilisateur
 * @param string $prenom Prénom de l'utilisateur
 * @param string $email Email de l'utilisateur
 * @param string $motDePasse Mot de passe de l'utilisateur
 * @return array|bool Données de l'utilisateur ou false en cas d'erreur
 */
function enregistrerUtilisateur($nom, $prenom, $email, $motDePasse) {
    global $connexion;
    
    // Vérifier si l'email existe déjà
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $existe = $stmt->fetchColumn();
    
    if ($existe) {
        return false;
    }
    
    // Hashage du mot de passe
    $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
    
    // Insertion de l'utilisateur
    $stmt = $connexion->prepare("
        INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, date_creation)
        VALUES (?, ?, ?, ?, 'client', NOW())
    ");
    
    try {
        $stmt->execute([$nom, $prenom, $email, $hash]);
        $id = $connexion->lastInsertId();
        
        // Récupérer les données de l'utilisateur
        $utilisateur = [
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role' => 'client'
        ];
        
        return $utilisateur;
    } catch (PDOException $e) {
        // Gérer l'erreur
        return false;
    }
} 