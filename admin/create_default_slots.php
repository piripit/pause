<?php
// Affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

// Vérifier le périmètre demandé
$perimeter = $_POST['perimeter'] ?? $_SESSION['admin_perimeter'] ?? 'all';

// Vérifier que l'admin a le droit de créer des créneaux pour ce périmètre
if ($perimeter !== 'all' && $perimeter !== $_SESSION['admin_perimeter'] && $_SESSION['admin_perimeter'] !== 'all') {
    $_SESSION['error'] = "Vous n'êtes pas autorisé à gérer les créneaux pour ce périmètre";
    header('Location: slots.php');
    exit;
}

// Connexion à la base de données
$conn = getConnection();

// Vérifier d'abord si la colonne perimeter existe
$checkPerimeterColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'perimeter'");
if ($checkPerimeterColumn->num_rows === 0) {
    // Ajouter la colonne perimeter si elle n'existe pas
    $conn->query("ALTER TABLE break_slots ADD COLUMN perimeter ENUM('campus', 'entreprise', 'asn', 'all') NOT NULL DEFAULT 'all'");
}

// Vérifier si la colonne quota existe
$checkQuotaColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'quota'");
if ($checkQuotaColumn->num_rows === 0) {
    // Ajouter la colonne quota si elle n'existe pas
    $conn->query("ALTER TABLE break_slots ADD COLUMN quota INT NOT NULL DEFAULT 3");
}

// Vérifier si la colonne is_active existe
$checkActiveColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'is_active'");
if ($checkActiveColumn->num_rows === 0) {
    // Ajouter la colonne is_active si elle n'existe pas
    $conn->query("ALTER TABLE break_slots ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

// Supprimer les créneaux existants pour ce périmètre (pour éviter les doublons)
$stmt = $conn->prepare("DELETE FROM break_slots WHERE perimeter = ?");
$stmt->bind_param("s", $perimeter);
$stmt->execute();

// Définir les créneaux par défaut
$morning_slots = [
    ['09:00:00', '09:10:00'],
    ['09:15:00', '09:25:00'],
    ['09:30:00', '09:40:00'],
    ['09:45:00', '09:55:00'],
    ['10:00:00', '10:10:00'],
    ['10:15:00', '10:25:00'],
    ['10:30:00', '10:40:00'],
    ['10:45:00', '10:55:00'],
    ['11:00:00', '11:10:00'],
    ['11:15:00', '11:25:00'],
    ['11:30:00', '11:40:00'],
    ['11:45:00', '11:55:00'],
    ['12:00:00', '12:10:00'],
    ['12:15:00', '12:25:00'],
    ['12:30:00', '12:40:00']
];

$afternoon_slots = [
    ['14:00:00', '14:10:00'],
    ['14:15:00', '14:25:00'],
    ['14:30:00', '14:40:00'],
    ['14:45:00', '14:55:00'],
    ['15:00:00', '15:10:00'],
    ['15:15:00', '15:25:00'],
    ['15:30:00', '15:40:00'],
    ['15:45:00', '15:55:00'],
    ['16:00:00', '16:10:00'],
    ['16:15:00', '16:25:00'],
    ['16:30:00', '16:40:00'],
    ['16:45:00', '16:55:00'],
    ['17:00:00', '17:10:00'],
    ['17:15:00', '17:25:00'],
    ['17:30:00', '17:40:00']
];

// Préparer la requête d'insertion
$stmt = $conn->prepare("INSERT INTO break_slots (period, start_time, end_time, quota, is_active, perimeter) VALUES (?, ?, ?, 3, 1, ?)");

// Insérer les créneaux du matin
foreach ($morning_slots as $slot) {
    $period = 'morning';
    $start_time = $slot[0];
    $end_time = $slot[1];
    $stmt->bind_param("ssss", $period, $start_time, $end_time, $perimeter);
    $stmt->execute();
}

// Insérer les créneaux de l'après-midi
foreach ($afternoon_slots as $slot) {
    $period = 'afternoon';
    $start_time = $slot[0];
    $end_time = $slot[1];
    $stmt->bind_param("ssss", $period, $start_time, $end_time, $perimeter);
    $stmt->execute();
}

$conn->close();

// Rediriger vers la page de gestion des créneaux avec un message de succès
$_SESSION['success'] = "Les créneaux par défaut ont été créés avec succès pour le périmètre " . ucfirst($perimeter);
header('Location: slots.php');
exit;
