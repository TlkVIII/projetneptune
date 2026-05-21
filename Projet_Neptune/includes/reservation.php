<?php
/**
 * Gestion des réservations de chambres
 * Fichier : includes/reservation.php
 */

require_once 'database.php';

/**
 * Vérifie si une chambre est disponible pour les dates spécifiées
 * @param int $chambreId ID de la chambre
 * @param string $dateArrivee Date d'arrivée (format Y-m-d)
 * @param string $dateDepart Date de départ (format Y-m-d)
 * @return bool True si la chambre est disponible, false sinon
 */
function estChambreDisponible($chambreId, $dateArrivee, $dateDepart) {
    // Vérifier si la chambre existe et est disponible
    $chambre = fetchOne("SELECT * FROM chambres WHERE id = ? AND statut = 'disponible'", [$chambreId]);
    if (!$chambre) {
        return false;
    }
    
    // Vérifier s'il n'y a pas de réservation pour cette chambre aux dates demandées
    $sql = "SELECT COUNT(*) as nb_reservations FROM reservations 
            WHERE chambre_id = ? 
            AND statut IN ('confirmée', 'en attente')
            AND NOT (
                (date_arrivee >= ?) OR
                (date_depart <= ?)
            )";
    
    $result = fetchOne($sql, [$chambreId, $dateDepart, $dateArrivee]);
    
    return $result['nb_reservations'] == 0;
}

/**
 * Récupère les chambres disponibles pour les dates spécifiées
 * @param string $dateArrivee Date d'arrivée (format Y-m-d)
 * @param string $dateDepart Date de départ (format Y-m-d)
 * @param int $nbPersonnes Nombre de personnes
 * @return array Chambres disponibles
 */
function getChambresDisponibles($dateArrivee, $dateDepart, $nbPersonnes = 1) {
    // Récupérer les IDs des chambres qui ont des réservations aux dates demandées
    $sqlChambresReservees = "SELECT DISTINCT chambre_id FROM reservations 
                            WHERE statut IN ('confirmée', 'en attente')
                            AND NOT (
                                (date_arrivee >= ?) OR
                                (date_depart <= ?)
                            )";
    
    $chambresReservees = fetchAll($sqlChambresReservees, [$dateDepart, $dateArrivee]);
    
    // Préparer la liste des IDs de chambres réservées pour l'exclusion
    $idsReserves = [];
    foreach ($chambresReservees as $chambre) {
        $idsReserves[] = $chambre['chambre_id'];
    }
    
    // Requête pour obtenir les chambres disponibles
    $sql = "SELECT c.*, cc.nom as categorie_nom, cc.description, cc.tarif_base, cc.capacite, cc.image
            FROM chambres c
            JOIN categories_chambre cc ON c.categorie_id = cc.id
            WHERE c.statut = 'disponible'
            AND cc.capacite >= ?";
    
    $params = [$nbPersonnes];
    
    // Ajouter la condition d'exclusion des chambres réservées si nécessaire
    if (!empty($idsReserves)) {
        $placeholders = implode(',', array_fill(0, count($idsReserves), '?'));
        $sql .= " AND c.id NOT IN ({$placeholders})";
        $params = array_merge($params, $idsReserves);
    }
    
    // Ordonner les résultats par catégorie et tarif
    $sql .= " ORDER BY cc.tarif_base ASC";
    
    return fetchAll($sql, $params);
}

/**
 * Calcule le montant total d'une réservation
 * @param int $chambreId ID de la chambre
 * @param string $dateArrivee Date d'arrivée (format Y-m-d)
 * @param string $dateDepart Date de départ (format Y-m-d)
 * @return float Montant total
 */
function calculerMontantReservation($chambreId, $dateArrivee, $dateDepart) {
    // Récupérer le tarif de base de la chambre
    $chambre = fetchOne(
        "SELECT cc.tarif_base FROM chambres c
        JOIN categories_chambre cc ON c.categorie_id = cc.id
        WHERE c.id = ?", 
        [$chambreId]
    );
    
    if (!$chambre) {
        return 0;
    }
    
    // Calculer le nombre de jours
    $dateArriveeObj = new DateTime($dateArrivee);
    $dateDepartObj = new DateTime($dateDepart);
    $nbJours = $dateDepartObj->diff($dateArriveeObj)->days;
    
    // Calculer le montant total
    $montantTotal = $chambre['tarif_base'] * $nbJours;
    
    return $montantTotal;
}

/**
 * Crée une nouvelle réservation
 * @param array $donneesReservation Données de la réservation
 * @return int|false ID de la réservation créée ou false en cas d'échec
 */
function creerReservation($donneesReservation) {
    // Vérifier si la chambre est disponible
    if (!estChambreDisponible(
        $donneesReservation['chambre_id'],
        $donneesReservation['date_arrivee'],
        $donneesReservation['date_depart']
    )) {
        return false;
    }
    
    // Créer la réservation
    $idReservation = insert('reservations', $donneesReservation);
    
    return $idReservation;
}

/**
 * Annule une réservation
 * @param int $reservationId ID de la réservation
 * @return bool True si l'annulation a réussi, false sinon
 */
function annulerReservation($reservationId) {
    $result = update(
        'reservations',
        ['statut' => 'annulée'],
        'id = ?',
        [$reservationId]
    );
    
    return $result > 0;
}

/**
 * Récupère les détails d'une réservation
 * @param int $reservationId ID de la réservation
 * @return array|null Détails de la réservation ou null si non trouvée
 */
function getReservationDetails($reservationId) {
    $sql = "SELECT r.*, c.numero as numero_chambre, cc.nom as categorie_nom, 
            cl.nom as client_nom, cl.prenom as client_prenom, cl.email as client_email
            FROM reservations r
            JOIN chambres c ON r.chambre_id = c.id
            JOIN categories_chambre cc ON c.categorie_id = cc.id
            JOIN clients cl ON r.client_id = cl.id
            WHERE r.id = ?";
    
    return fetchOne($sql, [$reservationId]);
}

/**
 * Récupère les réservations d'un client
 * @param int $clientId ID du client
 * @return array Réservations du client
 */
function getReservationsClient($clientId) {
    $sql = "SELECT r.*, c.numero as numero_chambre, cc.nom as categorie_nom
            FROM reservations r
            JOIN chambres c ON r.chambre_id = c.id
            JOIN categories_chambre cc ON c.categorie_id = cc.id
            WHERE r.client_id = ?
            ORDER BY r.date_arrivee DESC";
    
    return fetchAll($sql, [$clientId]);
}

/**
 * Ajoute un service à une réservation
 * @param int $reservationId ID de la réservation
 * @param int $serviceId ID du service
 * @param int $quantite Quantité du service
 * @param string $dateService Date du service (format Y-m-d H:i:s)
 * @param string $commentaires Commentaires optionnels
 * @return int|false ID du service réservé ou false en cas d'échec
 */
function ajouterServiceReservation($reservationId, $serviceId, $quantite = 1, $dateService = null, $commentaires = '') {
    // Récupérer le prix du service
    $service = fetchOne("SELECT prix FROM services WHERE id = ?", [$serviceId]);
    if (!$service) {
        return false;
    }
    
    // Calculer le montant
    $montant = $service['prix'] * $quantite;
    
    // Ajouter le service à la réservation
    $donnees = [
        'reservation_id' => $reservationId,
        'service_id' => $serviceId,
        'quantite' => $quantite,
        'montant' => $montant,
        'commentaires' => $commentaires
    ];
    
    if ($dateService) {
        $donnees['date_service'] = $dateService;
    }
    
    return insert('reservations_services', $donnees);
}

/**
 * Récupère les services d'une réservation
 * @param int $reservationId ID de la réservation
 * @return array Services de la réservation
 */
function getServicesReservation($reservationId) {
    $sql = "SELECT rs.*, s.nom as service_nom, s.description, s.categorie
            FROM reservations_services rs
            JOIN services s ON rs.service_id = s.id
            WHERE rs.reservation_id = ?
            ORDER BY rs.date_service";
    
    return fetchAll($sql, [$reservationId]);
}
?> 