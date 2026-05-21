<?php
/**
 * Fonctions pour envoyer des emails concernant les réservations
 */

/**
 * Envoie un email de confirmation de demande de réservation au client
 * 
 * @param array $reservation Les détails de la réservation
 * @param array $chambre Les détails de la chambre
 * @param array $user Les informations de l'utilisateur
 * @return bool Succès ou échec de l'envoi
 */
function envoyerEmailDemandeReservation($reservation, $chambre, $user) {
    $sujet = "Confirmation de votre demande de réservation - Hôtel Neptune";
    
    // Calcul de la durée du séjour
    $date_arrivee = new DateTime($reservation['date_arrivee']);
    $date_depart = new DateTime($reservation['date_depart']);
    $duree_sejour = $date_depart->diff($date_arrivee)->days;
    
    // Formatage des dates
    $date_arrivee_fr = $date_arrivee->format('d/m/Y');
    $date_depart_fr = $date_depart->format('d/m/Y');
    
    // Calcul du montant total
    $prix_total = $duree_sejour * $chambre['prix'];
    
    // Construction du message HTML
    $message = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #2c3e50;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .reservation-details {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .button {
                display: inline-block;
                background-color: #3498db;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Confirmation de votre demande de réservation</h2>
            </div>
            <div class='content'>
                <p>Bonjour " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . ",</p>
                
                <p>Nous vous remercions d'avoir choisi l'Hôtel Neptune pour votre prochain séjour. Votre demande de réservation a bien été reçue et est actuellement <strong>en attente de confirmation</strong>.</p>
                
                <p>Notre équipe va examiner votre demande dans les meilleurs délais et vous recevrez une confirmation définitive par email.</p>
                
                <div class='reservation-details'>
                    <h3>Détails de votre réservation :</h3>
                    <p><strong>Chambre :</strong> " . htmlspecialchars($chambre['nom']) . "</p>
                    <p><strong>Arrivée :</strong> " . $date_arrivee_fr . " (à partir de 14h00)</p>
                    <p><strong>Départ :</strong> " . $date_depart_fr . " (jusqu'à 12h00)</p>
                    <p><strong>Durée du séjour :</strong> " . $duree_sejour . " nuit" . ($duree_sejour > 1 ? 's' : '') . "</p>
                    <p><strong>Nombre de personnes :</strong> " . $reservation['nombre_personnes'] . "</p>
                    <p><strong>Prix total estimé :</strong> " . number_format($prix_total, 2, ',', ' ') . " €</p>
                </div>
                
                <p>Vous pouvez consulter l'état de votre réservation à tout moment en vous connectant à votre compte sur notre site.</p>
                
                <p style='text-align: center;'>
                    <a href='http://www.hotel-neptune.com/reservations.php' class='button'>Voir mes réservations</a>
                </p>
                
                <p>Si vous avez des questions ou besoin d'informations supplémentaires, n'hésitez pas à nous contacter.</p>
                
                <p>Cordialement,<br>
                L'équipe de l'Hôtel Neptune</p>
            </div>
            <div class='footer'>
                <p>Hôtel Neptune - Montpellier, France 34000 - Tél: 0695396132 - Email: fayed.amourani8@gmail.com</p>
                <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // En-têtes de l'email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Hôtel Neptune <fayed.amourani8@gmail.com>" . "\r\n";
    
    // Envoi de l'email (en production, utiliser une bibliothèque comme PHPMailer ou SwiftMailer)
    return mail($user['email'], $sujet, $message, $headers);
}

/**
 * Envoie un email de confirmation de réservation au client
 * 
 * @param array $reservation Les détails de la réservation
 * @param array $chambre Les détails de la chambre
 * @param array $user Les informations de l'utilisateur
 * @return bool Succès ou échec de l'envoi
 */
function envoyerEmailConfirmationReservation($reservation, $chambre, $user) {
    $sujet = "Votre réservation est confirmée - Hôtel Neptune";
    
    // Calcul de la durée du séjour
    $date_arrivee = new DateTime($reservation['date_arrivee']);
    $date_depart = new DateTime($reservation['date_depart']);
    $duree_sejour = $date_depart->diff($date_arrivee)->days;
    
    // Formatage des dates
    $date_arrivee_fr = $date_arrivee->format('d/m/Y');
    $date_depart_fr = $date_depart->format('d/m/Y');
    
    // Calcul du montant total
    $prix_total = $duree_sejour * $chambre['prix'];
    
    // Construction du message HTML
    $message = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #2c3e50;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .confirmation-banner {
                background-color: #28a745;
                color: white;
                padding: 10px;
                text-align: center;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .content {
                padding: 20px;
            }
            .reservation-details {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .important-info {
                border-left: 4px solid #3498db;
                padding-left: 15px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .button {
                display: inline-block;
                background-color: #3498db;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Confirmation de réservation</h2>
            </div>
            <div class='content'>
                <div class='confirmation-banner'>
                    <h3>Votre réservation est confirmée !</h3>
                </div>
                
                <p>Bonjour " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . ",</p>
                
                <p>Nous avons le plaisir de vous confirmer votre réservation à l'Hôtel Neptune. Nous sommes ravis de vous accueillir prochainement.</p>
                
                <div class='reservation-details'>
                    <h3>Détails de votre séjour :</h3>
                    <p><strong>Numéro de réservation :</strong> #" . $reservation['id'] . "</p>
                    <p><strong>Chambre :</strong> " . htmlspecialchars($chambre['nom']) . "</p>
                    <p><strong>Arrivée :</strong> " . $date_arrivee_fr . " (à partir de 14h00)</p>
                    <p><strong>Départ :</strong> " . $date_depart_fr . " (jusqu'à 12h00)</p>
                    <p><strong>Durée du séjour :</strong> " . $duree_sejour . " nuit" . ($duree_sejour > 1 ? 's' : '') . "</p>
                    <p><strong>Nombre de personnes :</strong> " . $reservation['nombre_personnes'] . "</p>
                    <p><strong>Prix total :</strong> " . number_format($prix_total, 2, ',', ' ') . " €</p>
                </div>
                
                <div class='important-info'>
                    <h3>Informations importantes :</h3>
                    <ul>
                        <li>Check-in : à partir de 14h00 le jour de votre arrivée</li>
                        <li>Check-out : jusqu'à 12h00 le jour de votre départ</li>
                        <li>Une pièce d'identité vous sera demandée à l'arrivée</li>
                        <li>Le paiement s'effectuera à votre arrivée à l'hôtel</li>
                        <li>Un dépôt de garantie pourra vous être demandé</li>
                    </ul>
                </div>
                
                <p style='text-align: center;'>
                    <a href='http://www.hotel-neptune.com/facture.php?id=" . $reservation['id'] . "' class='button'>Voir ma facture</a>
                </p>
                
                <p>Si vous avez des questions ou des demandes particulières, n'hésitez pas à nous contacter par téléphone au 0695396132 ou par email à fayed.amourani8@gmail.com.</p>
                
                <p>Nous vous remercions pour votre confiance et nous réjouissons de vous accueillir très bientôt !</p>
                
                <p>Cordialement,<br>
                L'équipe de l'Hôtel Neptune</p>
            </div>
            <div class='footer'>
                <p>Hôtel Neptune - Montpellier, France 34000 - Tél: 0695396132 - Email: fayed.amourani8@gmail.com</p>
                <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // En-têtes de l'email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Hôtel Neptune <fayed.amourani8@gmail.com>" . "\r\n";
    
    // Envoi de l'email (en production, utiliser une bibliothèque comme PHPMailer ou SwiftMailer)
    return mail($user['email'], $sujet, $message, $headers);
}

/**
 * Envoie un email de rejet de réservation au client
 * 
 * @param array $reservation Les détails de la réservation
 * @param array $chambre Les détails de la chambre
 * @param array $user Les informations de l'utilisateur
 * @param string $motif Le motif du rejet (optionnel)
 * @return bool Succès ou échec de l'envoi
 */
function envoyerEmailRejetReservation($reservation, $chambre, $user, $motif = '') {
    $sujet = "Information concernant votre demande de réservation - Hôtel Neptune";
    
    // Formatage des dates
    $date_arrivee = new DateTime($reservation['date_arrivee']);
    $date_depart = new DateTime($reservation['date_depart']);
    $date_arrivee_fr = $date_arrivee->format('d/m/Y');
    $date_depart_fr = $date_depart->format('d/m/Y');
    
    // Construction du message HTML
    $message = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #2c3e50;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .reservation-details {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .rejection-info {
                border-left: 4px solid #e74c3c;
                padding-left: 15px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #666;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .button {
                display: inline-block;
                background-color: #3498db;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
            .alternatives {
                background-color: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Information sur votre demande de réservation</h2>
            </div>
            <div class='content'>
                <p>Bonjour " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . ",</p>
                
                <p>Nous vous remercions pour votre demande de réservation à l'Hôtel Neptune.</p>
                
                <div class='rejection-info'>
                    <p>Nous sommes au regret de vous informer que nous ne pouvons pas confirmer votre réservation pour les dates demandées.</p>
                    
                    " . ($motif ? "<p><strong>Motif :</strong> " . htmlspecialchars($motif) . "</p>" : "") . "
                </div>
                
                <div class='reservation-details'>
                    <h3>Rappel de votre demande :</h3>
                    <p><strong>Chambre :</strong> " . htmlspecialchars($chambre['nom']) . "</p>
                    <p><strong>Dates souhaitées :</strong> du " . $date_arrivee_fr . " au " . $date_depart_fr . "</p>
                    <p><strong>Nombre de personnes :</strong> " . $reservation['nombre_personnes'] . "</p>
                </div>
                
                <div class='alternatives'>
                    <h3>Nous vous proposons :</h3>
                    <ul>
                        <li>D'effectuer une nouvelle demande pour des dates différentes</li>
                        <li>De consulter nos autres types de chambres disponibles</li>
                        <li>De nous contacter directement pour trouver ensemble la meilleure solution</li>
                    </ul>
                    
                    <p style='text-align: center; margin-top: 15px;'>
                        <a href='http://www.hotel-neptune.com/reservation.php' class='button'>Faire une nouvelle réservation</a>
                    </p>
                </div>
                
                <p>Nous restons à votre disposition pour tout renseignement complémentaire au 0695396132 ou par email à fayed.amourani8@gmail.com.</p>
                
                <p>Nous espérons avoir l'occasion de vous accueillir prochainement dans notre établissement.</p>
                
                <p>Cordialement,<br>
                L'équipe de l'Hôtel Neptune</p>
            </div>
            <div class='footer'>
                <p>Hôtel Neptune - Montpellier, France 34000 - Tél: 0695396132 - Email: fayed.amourani8@gmail.com</p>
                <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // En-têtes de l'email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Hôtel Neptune <fayed.amourani8@gmail.com>" . "\r\n";
    
    // Envoi de l'email (en production, utiliser une bibliothèque comme PHPMailer ou SwiftMailer)
    return mail($user['email'], $sujet, $message, $headers);
} 