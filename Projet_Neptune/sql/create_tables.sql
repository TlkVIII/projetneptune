CREATE DATABASE IF NOT EXISTS hotel_neptune;
USE hotel_neptune;

CREATE TABLE IF NOT EXISTS chambres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    capacite INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Création de la table "messages" pour stocker les messages du formulaire de contact
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT 0,
    repondu BOOLEAN DEFAULT 0,
    reponse TEXT,
    date_reponse TIMESTAMP NULL,
    INDEX (lu),
    INDEX (date_envoi)
);

-- Insertion de quelques chambres d'exemple
INSERT INTO chambres (nom, description, prix, image, capacite) VALUES
('Chambre Deluxe', 'Chambre spacieuse avec vue sur la mer', 250.00, 'deluxe.jpg', 2),
('Suite Présidentielle', 'Suite luxueuse avec salon privé et jacuzzi', 500.00, 'presidentielle.jpg', 2),
('Chambre Familiale', 'Chambre familiale avec deux lits doubles', 350.00, 'familiale.jpg', 4);

-- Insertion d'un administrateur par défaut
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'System', 'admin@hotel-neptune.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); 