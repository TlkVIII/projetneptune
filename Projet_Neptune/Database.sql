-- Création de la base de données
CREATE DATABASE IF NOT EXISTS hotel_neptune;
USE hotel_neptune;
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse VARCHAR(255),
    code_postal VARCHAR(10),
    ville VARCHAR(100),
    pays VARCHAR(100),
    role ENUM('client', 'admin') DEFAULT 'client',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des chambres
CREATE TABLE IF NOT EXISTS chambres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10, 2) NOT NULL CHECK (prix > 0),
    capacite INT NOT NULL CHECK (capacite > 0),
    nb_lits_simples INT DEFAULT 0,
    nb_lits_doubles INT DEFAULT 0,
    superficie INT,
    image VARCHAR(255),
    disponible BOOLEAN DEFAULT TRUE,
    wifi BOOLEAN DEFAULT FALSE,
    climatisation BOOLEAN DEFAULT FALSE,
    balcon BOOLEAN DEFAULT FALSE,
    vue_mer BOOLEAN DEFAULT FALSE,
    minibar BOOLEAN DEFAULT FALSE,
    coffre_fort BOOLEAN DEFAULT FALSE,
    salle_bain_privee BOOLEAN DEFAULT TRUE,
    service_etage BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chambre_id INT NOT NULL,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL CHECK (date_depart > date_arrivee),
    nombre_personnes INT NOT NULL CHECK (nombre_personnes > 0),
    statut ENUM('en_attente', 'confirmee', 'rejetee', 'annulee') DEFAULT 'en_attente',
    demandes_speciales TEXT,
    annulation_demandee TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (chambre_id) REFERENCES chambres(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des messages de contact
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    repondu TINYINT(1) NOT NULL DEFAULT 0,
    reponse TEXT NULL,
    date_reponse DATETIME NULL,
    reponse_lue_client TINYINT(1) NOT NULL DEFAULT 0,
    conversation_id INT NULL,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des services annexes
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    description TEXT,
    categorie VARCHAR(80) DEFAULT 'general',
    prix DECIMAL(10,2) NOT NULL CHECK (prix >= 0),
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de relation réservations <-> services
CREATE TABLE IF NOT EXISTS reservations_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    service_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1 CHECK (quantite > 0),
    montant DECIMAL(10,2) NOT NULL CHECK (montant >= 0),
    date_service DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaires TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création d'un compte administrateur par défaut
-- Vérifier d'abord si l'utilisateur admin existe déjà
SELECT @admin_exists := COUNT(*) FROM users WHERE email = 'admin@hotel-neptune.com';

-- Insérer uniquement si l'admin n'existe pas déjà
SET @admin_password = '$2y$10$IZUgXksj9/t8xX3kmiLdNO0yrmlUxsB1EfDjLC4FSw0TfUB4XN0xa';

-- Insertion conditionnelle
INSERT INTO users (nom, prenom, email, password, role)
SELECT 'Admin', 'System', 'admin@hotel-neptune.com', @admin_password, 'admin'
FROM dual
WHERE @admin_exists = 0;

-- Insertion de chambres d'exemple (avec vérification d'existence)
-- Chambre Standard
SELECT @count := COUNT(*) FROM chambres WHERE nom = 'Chambre Standard';
INSERT INTO chambres (nom, description, prix, capacite, nb_lits_simples, nb_lits_doubles, superficie, image, disponible, wifi, climatisation, balcon, vue_mer, minibar, coffre_fort, salle_bain_privee, service_etage)
SELECT 'Chambre Standard', 'Une chambre confortable avec tout le nécessaire pour un séjour agréable.', 100.00, 2, 2, 0, 20, 'standard1.jpg', TRUE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE, TRUE, FALSE
FROM dual 
WHERE @count = 0;

-- Chambre Deluxe
SELECT @count := COUNT(*) FROM chambres WHERE nom = 'Chambre Deluxe';
INSERT INTO chambres (nom, description, prix, capacite, nb_lits_simples, nb_lits_doubles, superficie, image, disponible, wifi, climatisation, balcon, vue_mer, minibar, coffre_fort, salle_bain_privee, service_etage)
SELECT 'Chambre Deluxe', 'Une chambre spacieuse avec un grand lit et une vue sur la ville.', 150.00, 2, 0, 1, 30, 'cdeluxe1.jpg', TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, FALSE
FROM dual 
WHERE @count = 0;

-- Suite Junior
SELECT @count := COUNT(*) FROM chambres WHERE nom = 'Suite Junior';
INSERT INTO chambres (nom, description, prix, capacite, nb_lits_simples, nb_lits_doubles, superficie, image, disponible, wifi, climatisation, balcon, vue_mer, minibar, coffre_fort, salle_bain_privee, service_etage)
SELECT 'Suite Junior', 'Suite élégante avec un espace de vie séparé et tous les conforts modernes.', 250.00, 3, 1, 1, 45, 'csuite1.jpg', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE
FROM dual 
WHERE @count = 0;

-- Suite Présidentielle
SELECT @count := COUNT(*) FROM chambres WHERE nom = 'Suite Présidentielle';
INSERT INTO chambres (nom, description, prix, capacite, nb_lits_simples, nb_lits_doubles, superficie, image, disponible, wifi, climatisation, balcon, vue_mer, minibar, coffre_fort, salle_bain_privee, service_etage)
SELECT 'Suite Présidentielle', 'Notre suite la plus luxueuse avec vue panoramique sur la mer et service personnalisé.', 500.00, 4, 0, 2, 80, 'cpresident1.jpeg', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE
FROM dual 
WHERE @count = 0;

-- Création des index (si besoin)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_reservations_user_id ON reservations(user_id);
CREATE INDEX IF NOT EXISTS idx_reservations_chambre_id ON reservations(chambre_id);
CREATE INDEX IF NOT EXISTS idx_reservations_statut ON reservations(statut);
CREATE INDEX IF NOT EXISTS idx_reservations_dates ON reservations(date_arrivee, date_depart);
CREATE INDEX IF NOT EXISTS idx_reservations_annulation ON reservations(annulation_demandee);
CREATE INDEX IF NOT EXISTS idx_messages_lu ON messages(lu);
CREATE INDEX IF NOT EXISTS idx_messages_repondu ON messages(repondu);
CREATE INDEX IF NOT EXISTS idx_messages_email_date ON messages(email, date_envoi);
CREATE INDEX IF NOT EXISTS idx_messages_conversation_id ON messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_services_categorie ON services(categorie);
CREATE INDEX IF NOT EXISTS idx_res_services_reservation ON reservations_services(reservation_id);
CREATE INDEX IF NOT EXISTS idx_res_services_service ON reservations_services(service_id);

-- Suppression des procédures et triggers existants pour les recréer proprement
DROP PROCEDURE IF EXISTS check_room_availability;
DROP TRIGGER IF EXISTS after_reservation_status_update;

-- Procédure stockée pour vérifier la disponibilité d'une chambre
DELIMITER //
CREATE PROCEDURE check_room_availability(IN p_chambre_id INT, IN p_date_arrivee DATE, IN p_date_depart DATE)
BEGIN
    SELECT COUNT(*) AS reservations_count 
    FROM reservations 
    WHERE chambre_id = p_chambre_id 
    AND statut IN ('confirmee', 'en_attente')
    AND ((date_arrivee BETWEEN p_date_arrivee AND DATE_SUB(p_date_depart, INTERVAL 1 DAY)) 
    OR (date_depart BETWEEN DATE_ADD(p_date_arrivee, INTERVAL 1 DAY) AND p_date_depart)
    OR (date_arrivee <= p_date_arrivee AND date_depart >= p_date_depart));
END //
DELIMITER ;

-- Déclencheur pour mettre à jour le champ disponible d'une chambre
DELIMITER //
CREATE TRIGGER after_reservation_status_update
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    -- Déclaration des variables en début de bloc
    DECLARE count_overlapping INT;
    DECLARE count_active INT;
    
    IF NEW.statut = 'confirmee' THEN
        -- Vérifier si la chambre a d'autres réservations pour cette période
        SELECT COUNT(*) INTO count_overlapping
        FROM reservations
        WHERE chambre_id = NEW.chambre_id
        AND id != NEW.id
        AND statut = 'confirmee'
        AND ((date_arrivee BETWEEN NEW.date_arrivee AND DATE_SUB(NEW.date_depart, INTERVAL 1 DAY))
        OR (date_depart BETWEEN DATE_ADD(NEW.date_arrivee, INTERVAL 1 DAY) AND NEW.date_depart)
        OR (date_arrivee <= NEW.date_arrivee AND date_depart >= NEW.date_depart));
        
        -- Si pas d'autres réservations, marquer comme non disponible pendant cette période
        IF count_overlapping = 0 THEN
            UPDATE chambres SET disponible = FALSE WHERE id = NEW.chambre_id;
        END IF;
    END IF;
    
    -- Si une réservation est annulée ou rejetée, vérifier si la chambre peut être remise en disponible
    IF NEW.statut IN ('annulee', 'rejetee') AND OLD.statut = 'confirmee' THEN
        SELECT COUNT(*) INTO count_active
        FROM reservations
        WHERE chambre_id = NEW.chambre_id
        AND id != NEW.id
        AND statut = 'confirmee';
        
        -- Si pas d'autres réservations actives, remettre la chambre en disponible
        IF count_active = 0 THEN
            UPDATE chambres SET disponible = TRUE WHERE id = NEW.chambre_id;
        END IF;
    END IF;
END //
DELIMITER ;