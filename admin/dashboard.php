<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

// Récupérer le périmètre de l'administrateur
$admin_perimeter = $_SESSION['admin_perimeter'] ?? 'all';

// Définir les couleurs et icônes selon le périmètre
$theme_colors = [
    'campus' => 'primary',
    'entreprise' => 'success',
    'asn' => 'danger',
    'all' => 'primary'
];

$theme_icons = [
    'campus' => 'fa-university',
    'entreprise' => 'fa-building',
    'asn' => 'fa-shield-alt',
    'all' => 'fa-coffee'
];

$perimeter_names = [
    'campus' => 'Campus',
    'entreprise' => 'Entreprise',
    'asn' => 'ASN',
    'all' => 'Tous les périmètres'
];

$theme_color = $theme_colors[$admin_perimeter];
$theme_icon = $theme_icons[$admin_perimeter];
$perimeter_name = $perimeter_names[$admin_perimeter];

// Filtrer les employés selon le périmètre de l'administrateur
function filterEmployeesByPerimeter($employees, $perimeter)
{
    if ($perimeter == 'all') {
        return $employees;
    }

    $filtered = [];
    $prefix = '[' . strtoupper($perimeter) . ']';

    foreach ($employees as $employee) {
        if (strpos($employee['name'], $prefix) === 0) {
            $filtered[] = $employee;
        }
    }

    return $filtered;
}

// Récupérer les pauses en cours (filtrer par périmètre si nécessaire)
$current_breaks = getCurrentBreaks();
if ($admin_perimeter != 'all') {
    $current_breaks = array_filter($current_breaks, function ($break) use ($admin_perimeter) {
        $prefix = '[' . strtoupper($admin_perimeter) . ']';
        return strpos($break['employee_name'], $prefix) === 0;
    });
}

// Récupérer la date sélectionnée pour l'historique
$selected_date = $_GET['date'] ?? date('Y-m-d');
$break_history = getBreakHistory($selected_date);
if ($admin_perimeter != 'all') {
    $break_history = array_filter($break_history, function ($break) use ($admin_perimeter) {
        $prefix = '[' . strtoupper($admin_perimeter) . ']';
        return strpos($break['employee_name'], $prefix) === 0;
    });
}

// Récupérer les statistiques
$stats = getBreakStats($selected_date);

// Si l'administrateur n'est pas 'all', nous devons calculer des statistiques spécifiques
if ($admin_perimeter != 'all') {
    // Nous devrons calculer manuellement les statistiques pour ce périmètre
    $total_reservations = count($break_history);

    // Nous gardons la capacité totale inchangée car elle est basée sur les créneaux
    $total_slots = $stats['total_slots'];
    $total_capacity = $stats['total_capacity'];

    // Calculer le pourcentage d'utilisation spécifique à ce périmètre
    $usage_percentage = $total_capacity > 0 ? ($total_reservations / $total_capacity) * 100 : 0;

    $stats = [
        'total_slots' => $total_slots,
        'total_capacity' => $total_capacity,
        'total_reservations' => $total_reservations,
        'usage_percentage' => $usage_percentage
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - <?= $perimeter_name ?> - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta http-equiv="refresh" content="60"> <!-- Rafraîchir la page toutes les 60 secondes -->
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-<?= $theme_color ?>">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas <?= $theme_icon ?> me-2"></i>Gestion des Pauses - <?= $perimeter_name ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php">
                            <i class="fas fa-users me-1"></i>Gestion des employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="slots.php">
                            <i class="fas fa-clock me-1"></i>Gestion des créneaux
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['admin_username']) ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow h-100">
                    <div class="card-header bg-<?= $theme_color ?> text-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><i class="fas fa-clock me-2"></i>Pauses en cours - <?= $perimeter_name ?></h2>
                        <span class="badge bg-light text-dark">
                            <i class="far fa-clock me-1"></i><?= date('H:i') ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($current_breaks)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucune pause en cours actuellement pour ce périmètre.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i>Employé</th>
                                            <th><i class="fas fa-calendar-day me-1"></i>Période</th>
                                            <th><i class="far fa-clock me-1"></i>Horaire</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($current_breaks as $break): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($break['employee_name']) ?></td>
                                                <td>
                                                    <?php if ($break['period'] === 'morning'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-sun me-1"></i>Matin
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-moon me-1"></i>Après-midi
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $break['start_time'] ?> - <?= $break['end_time'] ?></td>
                                                <td>
                                                    <?php
                                                    switch ($break['status']) {
                                                        case 'reserved':
                                                            echo '<span class="badge bg-secondary"><i class="fas fa-calendar-check me-1"></i>Réservée</span>';
                                                            break;
                                                        case 'started':
                                                            echo '<span class="badge bg-success"><i class="fas fa-play me-1"></i>En cours</span>';
                                                            if ($break['start_timestamp']) {
                                                                $start_time = new DateTime($break['start_timestamp']);
                                                                $now = new DateTime();
                                                                $diff = $start_time->diff($now);
                                                                $minutes = $diff->i;
                                                                $seconds = $diff->s;
                                                                echo ' <small>(' . $minutes . 'm ' . $seconds . 's)</small>';
                                                            }
                                                            break;
                                                        case 'completed':
                                                            echo '<span class="badge bg-primary"><i class="fas fa-check me-1"></i>Terminée</span>';
                                                            break;
                                                        case 'missed':
                                                            echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Non prise</span>';
                                                            break;
                                                        case 'delayed':
                                                            echo '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>Décalée</span>';
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow h-100">
                    <div class="card-header bg-<?= $theme_color ?> text-white">
                        <h2 class="h5 mb-0"><i class="fas fa-chart-pie me-2"></i>Statistiques du jour - <?= $perimeter_name ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="h2 mb-0"><?= $stats['total_reservations'] ?></h3>
                                        <p class="text-muted">
                                            <i class="fas fa-calendar-check me-1"></i>Réservations
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="h2 mb-0"><?= $stats['total_capacity'] ?></h3>
                                        <p class="text-muted">
                                            <i class="fas fa-users me-1"></i>Capacité totale
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h3 class="h2 mb-0"><?= round($stats['usage_percentage']) ?>%</h3>
                                        <p class="text-muted">
                                            <i class="fas fa-percentage me-1"></i>Taux d'utilisation
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="progress mt-2" style="height: 20px;">
                            <?php
                            $percentage = round($stats['usage_percentage']);
                            $color_class = 'bg-success';
                            if ($percentage > 80) {
                                $color_class = 'bg-danger';
                            } elseif ($percentage > 50) {
                                $color_class = 'bg-warning';
                            }
                            ?>
                            <div class="progress-bar <?= $color_class ?>" role="progressbar"
                                style="width: <?= $percentage ?>%"
                                aria-valuenow="<?= $percentage ?>"
                                aria-valuemin="0"
                                aria-valuemax="100">
                                <?= $percentage ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-<?= $theme_color ?> text-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="fas fa-history me-2"></i>Historique des pauses - <?= $perimeter_name ?></h2>
                <form action="dashboard.php" method="get" class="d-flex">
                    <input type="date" name="date" value="<?= $selected_date ?>" class="form-control form-control-sm me-2">
                    <button type="submit" class="btn btn-sm btn-light">
                        <i class="fas fa-search me-1"></i>Afficher
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($break_history)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucune pause enregistrée pour cette date dans ce périmètre.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-1"></i>Employé</th>
                                    <th><i class="fas fa-calendar-day me-1"></i>Période</th>
                                    <th><i class="far fa-clock me-1"></i>Horaire prévu</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Statut</th>
                                    <th><i class="fas fa-hourglass-start me-1"></i>Début réel</th>
                                    <th><i class="fas fa-hourglass-end me-1"></i>Fin réelle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($break_history as $break): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($break['employee_name']) ?></td>
                                        <td>
                                            <?php if ($break['period'] === 'morning'): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-sun me-1"></i>Matin
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-moon me-1"></i>Après-midi
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $break['start_time'] ?> - <?= $break['end_time'] ?></td>
                                        <td>
                                            <?php
                                            switch ($break['status']) {
                                                case 'reserved':
                                                    echo '<span class="badge bg-secondary"><i class="fas fa-calendar-check me-1"></i>Réservée</span>';
                                                    break;
                                                case 'started':
                                                    echo '<span class="badge bg-success"><i class="fas fa-play me-1"></i>En cours</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="badge bg-primary"><i class="fas fa-check me-1"></i>Terminée</span>';
                                                    break;
                                                case 'missed':
                                                    echo '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Non prise</span>';
                                                    break;
                                                case 'delayed':
                                                    echo '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>Décalée</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?= $break['start_timestamp'] ? date('H:i:s', strtotime($break['start_timestamp'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $break['end_timestamp'] ? date('H:i:s', strtotime($break['end_timestamp'])) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>