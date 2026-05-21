<?php
/**
 * Gestion des clients
 * Fichier : includes/client.php
 */

require_once 'database.php';

/**
 * Récupère un client par son email
 * @param string $email Email du client
 * @return array|null Données du client ou null si non trouvé
 */
function getClientByEmail($email) {
    return fetchOne("SELECT * FROM clients WHERE email = ?", [$email]);
}

/**
 * Récupère un client par son ID
 * @param int $id ID du client
 * @return array|null Données du client ou null si non trouvé
 */
function getClientById($id) {
    return fetchOne("SELECT * FROM clients WHERE id = ?", [$id]);
}

/**
 * Crée un nouveau client
 * @param array $donneesClient Données du client
 * @return int|false ID du client créé ou false en cas d'échec
 */
function creerClient($donneesClient) {
    // Vérifier si le client existe déjà
    $clientExistant = getClientByEmail($donneesClient['email']);
    if ($clientExistant) {
        return $clientExistant['id']; // Retourner l'ID du client existant
    }
    
    // Créer le nouveau client
    return insert('clients', $donneesClient);
}

/**
 * Met à jour les informations d'un client
 * @param int $clientId ID du client
 * @param array $donneesClient Nouvelles données du client
 * @return bool True si la mise à jour a réussi, false sinon
 */
function updateClient($clientId, $donneesClient) {
    $result = update('clients', $donneesClient, 'id = ?', [$clientId]);
    return $result > 0;
}

/**
 * Récupère l'historique des réservations d'un client
 * @param int $clientId ID du client
 * @return array Historique des réservations
 */
function getHistoriqueReservations($clientId) {
    $sql = "SELECT r.*, c.numero as numero_chambre, cc.nom as categorie_nom
            FROM reservations r
            JOIN chambres c ON r.chambre_id = c.id
            JOIN categories_chambre cc ON c.categorie_id = cc.id
            WHERE r.client_id = ?
            ORDER BY r.date_arrivee DESC";
    
    return fetchAll($sql, [$clientId]);
}

/**
 * Inscrit un client à la newsletter
 * @param string $email Email du client
 * @return bool True si l'inscription a réussi, false sinon
 */
function inscrireNewsletter($email) {
    // Vérifier si l'email est déjà inscrit
    $inscription = fetchOne("SELECT * FROM newsletters WHERE email = ?", [$email]);
    
    if ($inscription) {
        // Si l'email est déjà inscrit mais désactivé, le réactiver
        if (!$inscription['actif']) {
            return update('newsletters', ['actif' => true], 'email = ?', [$email]) > 0;
        }
        return true; // Déjà inscrit et actif
    }
    
    // Nouvelle inscription
    return insert('newsletters', ['email' => $email]) !== false;
}

/**
 * Désinscrit un client de la newsletter
 * @param string $email Email du client
 * @return bool True si la désinscription a réussi, false sinon
 */
function desinscrireNewsletter($email) {
    // Marquer comme inactif plutôt que supprimer
    $result = update('newsletters', ['actif' => false], 'email = ?', [$email]);
    return $result > 0;
}

/**
 * Enregistre un message de contact
 * @param array $donneesContact Données du message de contact
 * @return int|false ID du message créé ou false en cas d'échec
 */
function enregistrerContact($donneesContact) {
    return insert('contacts', $donneesContact);
}

/**
 * Récupère la liste des messages de contact non traités
 * @return array Messages de contact
 */
function getContactsNonTraites() {
    return fetchAll("SELECT * FROM contacts WHERE traite = 0 ORDER BY date_envoi DESC");
}

/**
 * Marque un message de contact comme traité
 * @param int $contactId ID du message de contact
 * @return bool True si la mise à jour a réussi, false sinon
 */
function marquerContactTraite($contactId) {
    $result = update('contacts', ['traite' => true], 'id = ?', [$contactId]);
    return $result > 0;
}

/**
 * Recherche des clients par nom ou email
 * @param string $terme Terme de recherche
 * @return array Clients correspondants
 */
function rechercheClients($terme) {
    $sql = "SELECT * FROM clients 
            WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? 
            ORDER BY nom, prenom";
    
    $param = "%{$terme}%";
    return fetchAll($sql, [$param, $param, $param]);
}
?> 