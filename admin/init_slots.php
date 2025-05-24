<?php
// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupérer le périmètre de l'administrateur
$admin_perimeter = $_SESSION['admin_perimeter'] ?? 'all';

// Connexion à la base de données
$conn = getConnection();
if (!$conn) {
    die("Erreur de connexion à la base de données");
}

// Vérifier si les colonnes nécessaires existent, sinon les ajouter
$result = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'quota'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE break_slots ADD COLUMN quota INT NOT NULL DEFAULT 3");
}

$result = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'is_active'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE break_slots ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

$result = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'perimeter'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE break_slots ADD COLUMN perimeter ENUM('campus', 'entreprise', 'asn', 'all') NOT NULL DEFAULT 'all'");
}

// Liste des créneaux
$slots = [
    // Matin
    ["morning", "09:00:00", "09:10:00"],
    ["morning", "09:15:00", "09:25:00"],
    ["morning", "09:30:00", "09:40:00"],
    ["morning", "09:45:00", "09:55:00"],
    ["morning", "10:00:00", "10:10:00"],
    ["morning", "10:15:00", "10:25:00"],
    ["morning", "10:30:00", "10:40:00"],
    ["morning", "10:45:00", "10:55:00"],
    ["morning", "11:00:00", "11:10:00"],
    ["morning", "11:15:00", "11:25:00"],
    ["morning", "11:30:00", "11:40:00"],
    ["morning", "11:45:00", "11:55:00"],
    // Après-midi
    ["afternoon", "14:00:00", "14:10:00"],
    ["afternoon", "14:15:00", "14:25:00"],
    ["afternoon", "14:30:00", "14:40:00"],
    ["afternoon", "14:45:00", "14:55:00"],
    ["afternoon", "15:00:00", "15:10:00"],
    ["afternoon", "15:15:00", "15:25:00"],
    ["afternoon", "15:30:00", "15:40:00"],
    ["afternoon", "15:45:00", "15:55:00"],
    ["afternoon", "16:00:00", "16:10:00"],
    ["afternoon", "16:15:00", "16:25:00"],
    ["afternoon", "16:30:00", "16:40:00"],
    ["afternoon", "16:45:00", "16:55:00"]
];

// Supprimer les créneaux existants pour ce périmètre
$stmt = $conn->prepare("DELETE FROM break_slots WHERE perimeter = ?");
$stmt->bind_param("s", $admin_perimeter);
$stmt->execute();

// Vérifier si la structure de la table a une contrainte d'unicité sur period_start_time
$result = $conn->query("SHOW CREATE TABLE break_slots");
$row = $result->fetch_assoc();
$createTableSql = $row['Create Table'];

// Modifier la table si nécessaire pour ajouter perimeter à la contrainte d'unicité
if (strpos($createTableSql, 'UNIQUE KEY `period_start_time` (`period`,`start_time`)') !== false) {
    // Supprimer l'ancienne contrainte
    $conn->query("ALTER TABLE break_slots DROP INDEX period_start_time");
    // Ajouter une nouvelle contrainte incluant perimeter
    $conn->query("ALTER TABLE break_slots ADD UNIQUE KEY `period_start_time_perimeter` (`period`,`start_time`,`perimeter`)");
}

// Insérer les nouveaux créneaux
$stmt = $conn->prepare("INSERT INTO break_slots (period, start_time, end_time, quota, is_active, perimeter) VALUES (?, ?, ?, 3, 1, ?)");

foreach ($slots as $slot) {
    $period = $slot[0];
    $start_time = $slot[1];
    $end_time = $slot[2];
    $stmt->bind_param("ssss", $period, $start_time, $end_time, $admin_perimeter);
    $stmt->execute();
}

$conn->close();

// Rediriger vers la page des créneaux
$_SESSION['success'] = "Les créneaux ont été initialisés avec succès pour le périmètre " . ucfirst($admin_perimeter);
header("Location: slots.php");
exit;
