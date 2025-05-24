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

$success = '';
$error = '';

// Connexion à la base de données
$conn = getConnection();

// Vérifier si la colonne quota existe, sinon la créer
$checkQuotaColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'quota'");
if ($checkQuotaColumn->num_rows === 0) {
    $conn->query("ALTER TABLE break_slots ADD COLUMN quota INT NOT NULL DEFAULT 3");
}

// Vérifier si la colonne is_active existe, sinon la créer  
$checkActiveColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'is_active'");
if ($checkActiveColumn->num_rows === 0) {
    $conn->query("ALTER TABLE break_slots ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

// Vérifier si la colonne perimeter existe, sinon la créer
$checkPerimeterColumn = $conn->query("SHOW COLUMNS FROM break_slots LIKE 'perimeter'");
if ($checkPerimeterColumn->num_rows === 0) {
    // Ajout de la colonne sans dupliquer immédiatement
    $conn->query("ALTER TABLE break_slots ADD COLUMN perimeter ENUM('campus', 'entreprise', 'asn', 'all') NOT NULL DEFAULT 'all'");

    // Mettre à jour les créneaux existants
    $conn->query("UPDATE break_slots SET perimeter = 'all'");

    // Obtenir les créneaux existants pour duplication
    $existingSlots = $conn->query("SELECT id, period, start_time, end_time FROM break_slots WHERE perimeter = 'all'");

    // Préparer la requête d'insertion
    $insertStmt = $conn->prepare("INSERT INTO break_slots 
                                 (period, start_time, end_time, quota, is_active, perimeter) 
                                 VALUES (?, ?, ?, 3, 1, ?)");

    // Dupliquer chaque créneau pour chaque périmètre
    while ($slot = $existingSlots->fetch_assoc()) {
        foreach (['campus', 'entreprise', 'asn'] as $perimeter) {
            $insertStmt->bind_param("ssss", $slot['period'], $slot['start_time'], $slot['end_time'], $perimeter);
            $insertStmt->execute();
        }
    }
}

// Traiter la mise à jour du quota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quota'])) {
    $slot_id = $_POST['slot_id'];
    $new_quota = $_POST['quota'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Vérifier que l'admin peut modifier ce créneau (son périmètre)
    $stmt = $conn->prepare("SELECT perimeter FROM break_slots WHERE id = ?");
    $stmt->bind_param("i", $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $slot = $result->fetch_assoc();

    if ($admin_perimeter === 'all' || $slot['perimeter'] === $admin_perimeter) {
        $stmt = $conn->prepare("UPDATE break_slots SET quota = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("iii", $new_quota, $is_active, $slot_id);

        if ($stmt->execute()) {
            $success = "Le créneau a été mis à jour avec succès";
        } else {
            $error = "Erreur lors de la mise à jour du créneau: " . $conn->error;
        }
    } else {
        $error = "Vous n'êtes pas autorisé à modifier ce créneau";
    }
}

// Récupérer les créneaux selon le périmètre de l'administrateur
if ($admin_perimeter === 'all') {
    $stmt = $conn->prepare("SELECT * FROM break_slots ORDER BY period, start_time");
} else {
    $stmt = $conn->prepare("SELECT * FROM break_slots WHERE perimeter = ? ORDER BY period, start_time");
    $stmt->bind_param("s", $admin_perimeter);
}

$stmt->execute();
$result = $stmt->get_result();
$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

$conn->close();

// Si aucun créneau n'a été trouvé pour ce périmètre, afficher un message et un bouton pour les créer
$has_slots = !empty($slots);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Créneaux - <?= $perimeter_name ?> - Gestion des Pauses</title>
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
                        <a class="nav-link" href="employees.php">
                            <i class="fas fa-users me-1"></i>Gestion des employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="slots.php">
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

        <?php if (!$has_slots): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <p class="mb-2">Aucun créneau n'a été trouvé pour le périmètre "<?= $perimeter_name ?>".</p>
                <form action="init_slots.php" method="post">
                    <input type="hidden" name="perimeter" value="<?= $admin_perimeter ?>">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-plus-circle me-2"></i>Créer les créneaux par défaut
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-<?= $theme_color ?> text-white">
                <h2 class="h5 mb-0"><i class="fas fa-clock me-2"></i>Gestion des créneaux de pause - <?= $perimeter_name ?></h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <p class="mb-0">En tant qu'administrateur, vous pouvez :</p>
                    <ul class="mb-0">
                        <li>Définir le nombre maximum de techniciens par créneau</li>
                        <li>Activer ou désactiver des créneaux</li>
                    </ul>
                </div>

                <ul class="nav nav-tabs mb-4" id="slotTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="morning-tab" data-bs-toggle="tab" data-bs-target="#morning" type="button" role="tab">
                            <i class="fas fa-sun me-2"></i>Créneaux du matin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="afternoon-tab" data-bs-toggle="tab" data-bs-target="#afternoon" type="button" role="tab">
                            <i class="fas fa-moon me-2"></i>Créneaux de l'après-midi
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="slotTabContent">
                    <!-- Créneaux du matin -->
                    <div class="tab-pane fade show active" id="morning" role="tabpanel" aria-labelledby="morning-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-clock me-1"></i>Horaire</th>
                                        <th><i class="fas fa-users me-1"></i>Quota</th>
                                        <th><i class="fas fa-toggle-on me-1"></i>Statut</th>
                                        <th><i class="fas fa-edit me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $slot): ?>
                                        <?php if ($slot['period'] === 'morning'): ?>
                                            <tr>
                                                <td><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-users me-1"></i><?= $slot['quota'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($slot['is_active']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i>Actif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Inactif
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-slot"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editSlotModal"
                                                        data-id="<?= $slot['id'] ?>"
                                                        data-start="<?= $slot['start_time'] ?>"
                                                        data-end="<?= $slot['end_time'] ?>"
                                                        data-quota="<?= $slot['quota'] ?>"
                                                        data-active="<?= $slot['is_active'] ?>">
                                                        <i class="fas fa-edit me-1"></i>Modifier
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Créneaux de l'après-midi -->
                    <div class="tab-pane fade" id="afternoon" role="tabpanel" aria-labelledby="afternoon-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-clock me-1"></i>Horaire</th>
                                        <th><i class="fas fa-users me-1"></i>Quota</th>
                                        <th><i class="fas fa-toggle-on me-1"></i>Statut</th>
                                        <th><i class="fas fa-edit me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $slot): ?>
                                        <?php if ($slot['period'] === 'afternoon'): ?>
                                            <tr>
                                                <td><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-users me-1"></i><?= $slot['quota'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($slot['is_active']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i>Actif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Inactif
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-slot"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editSlotModal"
                                                        data-id="<?= $slot['id'] ?>"
                                                        data-start="<?= $slot['start_time'] ?>"
                                                        data-end="<?= $slot['end_time'] ?>"
                                                        data-quota="<?= $slot['quota'] ?>"
                                                        data-active="<?= $slot['is_active'] ?>">
                                                        <i class="fas fa-edit me-1"></i>Modifier
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition de créneau -->
    <div class="modal fade" id="editSlotModal" tabindex="-1" aria-labelledby="editSlotModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-<?= $theme_color ?> text-white">
                    <h5 class="modal-title" id="editSlotModalLabel"><i class="fas fa-edit me-2"></i>Modifier le créneau</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="slots.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="slot_id" id="edit_slot_id">
                        <input type="hidden" name="update_quota" value="1">

                        <div class="mb-3">
                            <label class="form-label">Horaire:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control" id="edit_slot_time" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quota" class="form-label">Nombre maximum de techniciens:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                <input type="number" class="form-control" id="edit_quota" name="quota" min="1" max="20" required>
                            </div>
                            <div class="form-text">Limitez le nombre de techniciens qui peuvent réserver ce créneau.</div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">Créneau actif</label>
                        </div>
                        <div class="form-text">Désactiver un créneau empêche toute nouvelle réservation.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-<?= $theme_color ?>">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour pré-remplir le modal d'édition
        document.addEventListener('DOMContentLoaded', function() {
            var editButtons = document.querySelectorAll('.edit-slot');

            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var slotId = this.getAttribute('data-id');
                    var startTime = this.getAttribute('data-start');
                    var endTime = this.getAttribute('data-end');
                    var quota = this.getAttribute('data-quota');
                    var isActive = this.getAttribute('data-active') === '1';

                    document.getElementById('edit_slot_id').value = slotId;
                    document.getElementById('edit_slot_time').value = startTime + ' - ' + endTime;
                    document.getElementById('edit_quota').value = quota;
                    document.getElementById('edit_is_active').checked = isActive;
                });
            });
        });
    </script>
</body>

</html>