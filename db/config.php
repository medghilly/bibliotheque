<?php
// config.php - Version sécurisée mais simple

// 1. Configuration de la connexion
$host = 'localhost';
$dbname = 'bibliotheque';
$username = 'root'; // À changer en production
$password = '';     // À changer absolument

// 2. Options de connexion PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Gestion des erreurs
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Résultats en tableau associatif
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" // Encodage UTF-8
];

// 3. Connexion à la base de données
try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password,
        $options
    );
} catch(PDOException $e) {
    // Message générique pour l'utilisateur
    die("Désolé, impossible de se connecter à la base de données. Réessayez plus tard.");
    
    // Pour debug (à enlever en production) :
    // die("Erreur : " . $e->getMessage());
}

// 4. Fonction utile pour sécuriser les affichages
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}