<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
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
    'all' => 'coffee'
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

$success = '';
$error = '';

// Traitement de l'ajout d'un employé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = $_POST['employee_name'] ?? '';

    if (empty($employee_name)) {
        $error = 'Veuillez entrer un nom d\'employé';
    } else {
        // Si l'admin n'est pas global, ajouter le préfixe du périmètre
        if ($admin_perimeter !== 'all') {
            $employee_name = "[" . strtoupper($admin_perimeter) . "] " . $employee_name;
        }

        // Vérifier si l'employé existe déjà
        $existing_employee = getEmployeeByName($employee_name);

        if ($existing_employee) {
            $error = 'Cet employé existe déjà';
        } else {
            if (addEmployee($employee_name)) {
                $success = 'Employé ajouté avec succès';
            } else {
                $error = 'Erreur lors de l\'ajout de l\'employé';
            }
        }
    }
}

// Récupérer tous les employés
$all_employees = getAllEmployees();

// Filtrer les employés selon le périmètre de l'administrateur
$employees = [];
if ($admin_perimeter === 'all') {
    $employees = $all_employees;
} else {
    $prefix = "[" . strtoupper($admin_perimeter) . "]";
    foreach ($all_employees as $employee) {
        if (strpos($employee['name'], $prefix) === 0) {
            $employees[] = $employee;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Employés - <?= $perimeter_name ?> - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="employees.php">
                            <i class="fas fa-users me-1"></i>Gestion des employés
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
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-<?= $theme_color ?> text-white">
                        <h2 class="h5 mb-0"><i class="fas fa-user-plus me-2"></i>Ajouter un employé - <?= $perimeter_name ?></h2>
                    </div>
                    <div class="card-body">
                        <form action="employees.php" method="post">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Nom de l'employé</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                                </div>
                                <?php if ($admin_perimeter !== 'all'): ?>
                                    <div class="form-text text-muted">
                                        <small>Le préfixe [<?= strtoupper($admin_perimeter) ?>] sera automatiquement ajouté</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-<?= $theme_color ?>">
                                <i class="fas fa-plus-circle me-2"></i>Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-<?= $theme_color ?> text-white">
                        <h2 class="h5 mb-0"><i class="fas fa-list me-2"></i>Liste des employés - <?= $perimeter_name ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employees)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun employé enregistré pour ce périmètre.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-id-card me-1"></i>ID</th>
                                            <th><i class="fas fa-user me-1"></i>Nom</th>
                                            <th><i class="fas fa-calendar-plus me-1"></i>Date d'ajout</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?= $employee['id'] ?></td>
                                                <td><?= htmlspecialchars($employee['name']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($employee['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>