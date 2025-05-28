<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/theme.php';

// Vérification de l'authentification
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération des informations de l'administrateur
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Récupération du périmètre
// Utiliser current_perimeter s'il existe, sinon admin_perimeter
$selectedPerimeter = $_SESSION['current_perimeter'] ?? $_SESSION['admin_perimeter'] ?? 'campus';

// Vérifier que l'admin a accès à ce périmètre
if ($_SESSION['admin_perimeter'] !== 'all' && $selectedPerimeter !== $_SESSION['admin_perimeter']) {
    // Réinitialiser au périmètre de l'admin
    $selectedPerimeter = $_SESSION['admin_perimeter'];
    $_SESSION['current_perimeter'] = $selectedPerimeter;
}

$perimeter_name = ucfirst($selectedPerimeter);

// Récupération des informations du thème
$theme = getThemeInfo($selectedPerimeter);
$theme_color = 'primary';
$theme_icon = $theme['icon'];
$theme_name = $theme['name'];

// Traitement de l'ajout d'une pause
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_break'])) {
    $name = $_POST['name'];
    $duration = $_POST['duration'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("INSERT INTO breaks (name, duration, description, perimeter) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $duration, $description, $selectedPerimeter]);
        $success_message = "La pause a été ajoutée avec succès.";
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'ajout de la pause.";
    }
}

// Récupération des pauses
$stmt = $pdo->prepare("SELECT * FROM breaks WHERE perimeter = ? ORDER BY name");
$stmt->execute([$selectedPerimeter]);
$breaks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pauses - <?= $perimeter_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-home me-2"></i>Administration
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php">
                            <i class="fas fa-users me-2"></i>Employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="breaks.php">
                            <i class="fas fa-coffee me-2"></i>Pauses
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="perimeterDropdown" data-bs-toggle="dropdown">
                            <i class="<?= $theme_icon ?> me-2"></i><?= $theme_name ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?perimeter=campus">Campus</a></li>
                            <li><a class="dropdown-item" href="?perimeter=entreprise">Entreprise</a></li>
                            <li><a class="dropdown-item" href="?perimeter=asn">ASN</a></li>
                        </ul>
                    </div>
                    <a href="logout.php" class="btn btn-light ms-2">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0"><i class="fas fa-plus-circle me-2"></i>Ajouter une pause - <?= $perimeter_name ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de la pause</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="duration" class="form-label">Durée (en minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_break" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0"><i class="fas fa-list me-2"></i>Liste des pauses - <?= $perimeter_name ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Durée</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($breaks as $break): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($break['name']) ?></td>
                                            <td><?= $break['duration'] ?> minutes</td>
                                            <td><?= htmlspecialchars($break['description']) ?></td>
                                            <td>
                                                <a href="edit_break.php?id=<?= $break['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_break.php?id=<?= $break['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette pause ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>