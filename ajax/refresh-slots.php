<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$perimeter = $_GET['perimeter'] ?? 'all';

// Valider le périmètre
$valid_perimeters = ['campus', 'entreprise', 'asn', 'all'];
if (!in_array($perimeter, $valid_perimeters)) {
    echo json_encode(['success' => false, 'error' => 'Périmètre invalide']);
    exit;
}

try {
    // Récupérer les créneaux du matin et de l'après-midi
    $morning_slots = getBreakSlots('morning', $perimeter);
    $afternoon_slots = getBreakSlots('afternoon', $perimeter);

    // Enrichir les données avec les informations de quota et d'activité
    foreach ($morning_slots as &$slot) {
        $slot['count'] = getSlotCount($slot['id']);
        $slot['quota'] = getSlotQuota($slot['id']);
        $slot['is_active'] = isSlotActive($slot['id'], $perimeter);
    }

    foreach ($afternoon_slots as &$slot) {
        $slot['count'] = getSlotCount($slot['id']);
        $slot['quota'] = getSlotQuota($slot['id']);
        $slot['is_active'] = isSlotActive($slot['id'], $perimeter);
    }

    echo json_encode([
        'success' => true,
        'morning_slots' => $morning_slots,
        'afternoon_slots' => $afternoon_slots,
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des créneaux: ' . $e->getMessage()
    ]);
}
