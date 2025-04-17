<?php
require_once __DIR__ . '/../config/database.php';

// Récupérer les créneaux de pause (matin ou après-midi)
function getBreakSlots($period)
{
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, start_time, end_time FROM break_slots WHERE period = ? ORDER BY start_time");
    $stmt->bind_param("s", $period);
    $stmt->execute();
    $result = $stmt->get_result();

    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $slots;
}

// Obtenir le nombre d'employés inscrits à un créneau
function getSlotCount($slot_id)
{
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM break_reservations WHERE slot_id = ? AND reservation_date = CURDATE()");
    $stmt->bind_param("i", $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $row['count'];
}

// Vérifier si un créneau est complet
function isSlotFull($slot_id)
{
    return getSlotCount($slot_id) >= 3;
}

// Récupérer les pauses en cours
function getCurrentBreaks()
{
    $conn = getConnection();
    $current_time = date('H:i:s');

    $query = "SELECT br.id, e.name as employee_name, bs.start_time, bs.end_time, bs.period, 
                     br.status, br.start_timestamp, br.end_timestamp
              FROM break_reservations br
              JOIN break_slots bs ON br.slot_id = bs.id
              JOIN employees e ON br.employee_id = e.id
              WHERE br.reservation_date = CURDATE() 
              AND (
                  (br.status = 'started' AND br.start_timestamp IS NOT NULL) OR
                  (? BETWEEN bs.start_time AND bs.end_time AND br.status = 'reserved')
              )
              ORDER BY bs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    $breaks = [];
    while ($row = $result->fetch_assoc()) {
        $breaks[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $breaks;
}

// Récupérer l'historique des pauses
function getBreakHistory($date = null)
{
    $conn = getConnection();

    if ($date === null) {
        $date = date('Y-m-d');
    }

    $query = "SELECT br.id, e.name as employee_name, bs.start_time, bs.end_time, bs.period, 
                     br.reservation_date, br.status, br.start_timestamp, br.end_timestamp
              FROM break_reservations br
              JOIN break_slots bs ON br.slot_id = bs.id
              JOIN employees e ON br.employee_id = e.id
              WHERE br.reservation_date = ?
              ORDER BY bs.period, bs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $history;
}

// Récupérer tous les employés
function getAllEmployees()
{
    $conn = getConnection();
    $query = "SELECT * FROM employees ORDER BY name";
    $result = $conn->query($query);

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }

    $conn->close();

    return $employees;
}

// Récupérer un employé par son nom
function getEmployeeByName($name)
{
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    $employee = null;
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();

    return $employee;
}

// Récupérer un employé par son ID
function getEmployeeById($id)
{
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $employee = null;
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();

    return $employee;
}

// Ajouter un nouvel employé
function addEmployee($name)
{
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO employees (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $result = $stmt->execute();

    $stmt->close();
    $conn->close();

    return $result;
}

// Réserver un créneau de pause
function reserveBreak($employee_id, $slot_id)
{
    if (isSlotFull($slot_id)) {
        return false;
    }

    $conn = getConnection();
    $date = date('Y-m-d');

    // Vérifier si l'employé a déjà réservé ce créneau aujourd'hui
    $check_stmt = $conn->prepare("SELECT id FROM break_reservations WHERE employee_id = ? AND slot_id = ? AND reservation_date = ?");
    $check_stmt->bind_param("iis", $employee_id, $slot_id, $date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        return true; // Déjà réservé, on considère que c'est un succès
    }

    $check_stmt->close();

    // Insérer la nouvelle réservation
    $stmt = $conn->prepare("INSERT INTO break_reservations (employee_id, slot_id, reservation_date, status) VALUES (?, ?, ?, 'reserved')");
    $stmt->bind_param("iis", $employee_id, $slot_id, $date);
    $result = $stmt->execute();

    $stmt->close();
    $conn->close();

    return $result;
}

// Récupérer les statistiques des pauses
function getBreakStats($date = null)
{
    if ($date === null) {
        $date = date('Y-m-d');
    }

    $morning_slots = getBreakSlots('morning');
    $afternoon_slots = getBreakSlots('afternoon');
    $total_slots = count($morning_slots) + count($afternoon_slots);
    $total_capacity = $total_slots * 3;

    $history = getBreakHistory($date);
    $total_reservations = count($history);
    $usage_percentage = $total_capacity > 0 ? ($total_reservations / $total_capacity) * 100 : 0;

    return [
        'total_slots' => $total_slots,
        'total_capacity' => $total_capacity,
        'total_reservations' => $total_reservations,
        'usage_percentage' => $usage_percentage
    ];
}

// Récupérer les pauses d'un employé
function getEmployeeBreaks($employee_id)
{
    $conn = getConnection();

    $query = "SELECT br.id, bs.start_time, bs.end_time, bs.period, br.reservation_date, 
                     br.status, br.start_timestamp, br.end_timestamp
              FROM break_reservations br
              JOIN break_slots bs ON br.slot_id = bs.id
              WHERE br.employee_id = ?
              ORDER BY br.reservation_date DESC, bs.period, bs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $breaks = [];
    while ($row = $result->fetch_assoc()) {
        $breaks[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $breaks;
}

// Corriger la fonction getEmployeeActiveBreaks qui contient une erreur de syntaxe
// Remplacer la fonction actuelle par celle-ci:

// Récupérer les pauses actives d'un employé pour aujourd'hui
function getEmployeeActiveBreaks($employee_id)
{
    $conn = getConnection();
    $today = date('Y-m-d');

    $query = "SELECT br.id, bs.start_time, bs.end_time, bs.period, br.reservation_date, 
                     br.status, br.start_timestamp, br.end_timestamp
              FROM break_reservations br
              JOIN break_slots bs ON br.slot_id = bs.id
              WHERE br.employee_id = ? 
              AND br.reservation_date = ?
              AND br.status = 'started'
              AND br.start_timestamp IS NOT NULL";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    $breaks = [];
    while ($row = $result->fetch_assoc()) {
        $breaks[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $breaks;
}

// Récupérer les pauses à venir d'un employé pour aujourd'hui
function getEmployeeUpcomingBreaks($employee_id)
{
    $conn = getConnection();
    $today = date('Y-m-d');

    // Modifié pour récupérer toutes les pauses réservées pour aujourd'hui, quelle que soit l'heure
    $query = "SELECT br.id, bs.start_time, bs.end_time, bs.period, br.reservation_date, 
                     br.status, br.start_timestamp, br.end_timestamp, bs.id as slot_id
              FROM break_reservations br
              JOIN break_slots bs ON br.slot_id = bs.id
              WHERE br.employee_id = ? 
              AND br.reservation_date = ?
              AND br.status = 'reserved'
              ORDER BY bs.period, bs.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    $breaks = [];
    while ($row = $result->fetch_assoc()) {
        $breaks[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $breaks;
}

// Activer une pause
function activateBreak($reservation_id)
{
    $conn = getConnection();

    // Vérifier si la réservation existe et n'est pas déjà activée
    $check_stmt = $conn->prepare("SELECT br.id, bs.start_time, bs.end_time, br.status 
                                  FROM break_reservations br
                                  JOIN break_slots bs ON br.slot_id = bs.id
                                  WHERE br.id = ?");
    $check_stmt->bind_param("i", $reservation_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        $conn->close();
        return "Réservation non trouvée";
    }

    $reservation = $check_result->fetch_assoc();

    if ($reservation['status'] !== 'reserved') {
        $check_stmt->close();
        $conn->close();
        return "Cette pause a déjà été activée";
    }

    $check_stmt->close();

    // Déterminer si la pause est activée à l'heure prévue ou en retard
    $now = new DateTime();
    $scheduled_start = new DateTime($reservation['start_time']);
    $scheduled_start->setDate($now->format('Y'), $now->format('m'), $now->format('d'));

    $diff = $now->diff($scheduled_start);
    $minutes_diff = $diff->h * 60 + $diff->i;

    $status = 'started';
    if ($diff->invert && $minutes_diff > 5) {
        $status = 'delayed';
    }

    // Activer la pause
    $stmt = $conn->prepare("UPDATE break_reservations 
                           SET status = ?, start_timestamp = NOW() 
                           WHERE id = ?");
    $stmt->bind_param("si", $status, $reservation_id);
    $result = $stmt->execute();

    if (!$result) {
        $stmt->close();
        $conn->close();
        return "Erreur lors de l'activation: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    return true;
}

// Mettre à jour automatiquement le statut des pauses
function updateBreakStatuses()
{
    $conn = getConnection();
    $today = date('Y-m-d');

    // 1. Marquer les pauses comme terminées après 10 minutes d'activation
    $stmt = $conn->prepare("UPDATE break_reservations 
                           SET status = 'completed', end_timestamp = NOW() 
                           WHERE status = 'started' 
                           AND start_timestamp IS NOT NULL 
                           AND end_timestamp IS NULL
                           AND TIMESTAMPDIFF(MINUTE, start_timestamp, NOW()) >= 10");
    $stmt->execute();
    $stmt->close();

    // 2. Marquer les pauses comme manquées si elles n'ont pas été activées et que l'heure est passée
    $stmt = $conn->prepare("UPDATE break_reservations br
                           JOIN break_slots bs ON br.slot_id = bs.id
                           SET br.status = 'missed'
                           WHERE br.status = 'reserved'
                           AND br.reservation_date = ?
                           AND CONCAT(?, ' ', bs.end_time) < NOW()");
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    return true;
}

// Appeler cette fonction à chaque chargement de page pour maintenir les statuts à jour
updateBreakStatuses();
