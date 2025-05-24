<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier les paramètres
if (!isset($_POST['employee_id']) || !isset($_POST['slot_id']) || !isset($_POST['date'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_POST['employee_id'];
$slot_id = $_POST['slot_id'];
$date = $_POST['date'];
$perimeter = $_SESSION['perimeter'] ?? 'all';

// Vérifier si l'employé existe
$employee = getEmployeeById($employee_id);
        if (!$employee) {
    $_SESSION['error'] = 'Employé non trouvé';
    header('Location: ' . $perimeter . '.php');
    exit;
}

// Extraire le périmètre de l'employé à partir de son nom s'il a un préfixe
$employee_perimeter = 'all';
if (preg_match('/^\[(CAMPUS|ENTREPRISE|ASN)\]/', $employee['name'], $matches)) {
    $employee_perimeter = strtolower($matches[1]);
}

// Vérifier si le créneau existe
$slot = getSlotById($slot_id);
if (!$slot) {
    $_SESSION['error'] = 'Créneau non trouvé';
    header('Location: ' . $perimeter . '.php');
    exit;
}

// Vérifier si le créneau est actif
if (!isSlotActive($slot_id, $employee_perimeter)) {
    $_SESSION['error'] = 'Ce créneau n\'est pas disponible actuellement';
    header('Location: ' . $perimeter . '.php');
    exit;
}

// Vérifier si le quota du créneau est atteint
if (isSlotQuotaReached($slot_id, $date, $employee_perimeter)) {
    $_SESSION['error'] = 'Ce créneau a atteint son quota maximum de techniciens';
    header('Location: ' . $perimeter . '.php');
    exit;
}

// Vérifier si l'employé a déjà une réservation pour cette date et période
$existing_reservation = getEmployeeReservation($employee_id, $date, $slot['period']);
if ($existing_reservation) {
    $_SESSION['error'] = 'Vous avez déjà réservé une pause pour cette période';
    header('Location: ' . $perimeter . '.php');
    exit;
}

// Ajouter la réservation
if (addBreakReservation($employee_id, $slot_id, $date)) {
    $_SESSION['success'] = 'Pause réservée avec succès';
} else {
    $_SESSION['error'] = 'Erreur lors de la réservation de la pause';
}

// Rediriger vers la page appropriée
header('Location: ' . $perimeter . '.php');
exit;
