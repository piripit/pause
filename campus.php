<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';

// Redirection vers le tableau de bord admin si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

// Définir le périmètre pour cette page
$perimeter = 'campus';

// Obtenir les informations de thème
$theme = getThemeInfo($perimeter);
$theme_color = $theme['color'];
$theme_icon = $theme['icon'];
$perimeter_name = $theme['name'];

$_SESSION['perimeter'] = $perimeter;

$error = '';
$success = '';

// Traitement de la réservation de pause
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $employee_name = $_POST['employee_name'] ?? '';
    $morning_slot = $_POST['morning_slot'] ?? null;
    $afternoon_slot = $_POST['afternoon_slot'] ?? null;

    if (empty($employee_name)) {
        $error = 'Veuillez entrer votre nom';
    } elseif (!$morning_slot && !$afternoon_slot) {
        $error = 'Veuillez sélectionner au moins un créneau de pause';
    } else {
        // Ajouter le préfixe du périmètre au nom de l'employé
        $employee_name = "[CAMPUS] " . $employee_name;

        // Vérifier si l'employé existe, sinon le créer
        $employee = getEmployeeByName($employee_name);

        if (!$employee) {
            if (addEmployee($employee_name)) {
                $employee = getEmployeeByName($employee_name);
            } else {
                $error = 'Erreur lors de l\'ajout de l\'employé';
            }
        }

        if ($employee) {
            $morning_success = true;
            $afternoon_success = true;

            // Réserver le créneau du matin
            if ($morning_slot) {
                if (isSlotFull($morning_slot)) {
                    $morning_success = false;
                    $error = 'Le créneau du matin est complet';
                } else {
                    $morning_success = reserveBreak($employee['id'], $morning_slot);
                    if (!$morning_success) {
                        $error = 'Vous avez déjà une pause réservée pour le matin aujourd\'hui, ou le créneau n\'est pas disponible pour votre périmètre.';
                    }
                }
            }

            // Réserver le créneau de l'après-midi
            if ($afternoon_slot && $morning_success) {
                if (isSlotFull($afternoon_slot)) {
                    $afternoon_success = false;
                    $error = 'Le créneau de l\'après-midi est complet';
                } else {
                    $afternoon_success = reserveBreak($employee['id'], $afternoon_slot);
                    if (!$afternoon_success) {
                        $error = 'Vous avez déjà une pause réservée pour l\'après-midi aujourd\'hui, ou le créneau n\'est pas disponible pour votre périmètre.';
                    }
                }
            }

            if ($morning_success && $afternoon_success) {
                $success = 'Vos pauses ont été réservées avec succès';
            }
        }
    }
}

// Page d'accueil pour les employés (sans connexion)
$morning_slots = getBreakSlots('morning', $perimeter);
$afternoon_slots = getBreakSlots('afternoon', $perimeter);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pauses - Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-coffee me-2"></i>Gestion des Pauses
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-breaks.php?perimeter=campus">
                            <i class="fas fa-calendar-check me-1"></i>Mes pauses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="activate-break.php?perimeter=campus">
                            <i class="fas fa-play-circle me-1"></i>Activer ma pause
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-login.php">
                            <i class="fas fa-lock me-1"></i>Administration
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10 text-center">
                <h1 class="h2 mb-3"><i class="fas fa-university me-2 text-primary"></i>Espace Campus</h1>
                <p class="lead mb-0">Bienvenue dans l'espace de gestion des pauses pour les techniciens du périmètre Campus</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <p>N'oubliez pas d'<a href="activate-break.php?perimeter=campus" class="alert-link">activer votre pause</a> au moment de la prendre.</p>
                <p>Vous pouvez également consulter vos pauses réservées en cliquant sur <a href="my-breaks.php?perimeter=campus" class="alert-link">Mes pauses</a>.</p>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-calendar-alt me-2"></i>Réservation de Pauses - Campus</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <p class="mb-0">Bienvenue sur l'application de gestion des pauses pour le périmètre Campus. Vous pouvez réserver un créneau de 10 minutes pour votre pause du matin et de l'après-midi.</p>
                            <p class="mb-0"><strong>Important:</strong> N'oubliez pas d'activer votre pause au moment de la prendre en utilisant la page <a href="activate-break.php?perimeter=campus" class="alert-link">Activer ma pause</a>.</p>
                            <p class="mb-0"><strong>Limite:</strong> 3 employés maximum par créneau</p>
                        </div>

                        <form action="campus.php" method="post" class="mb-4">
                            <input type="hidden" name="reserve" value="1">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Votre nom</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                                </div>
                                <div class="form-text text-muted">Votre nom sera automatiquement préfixé par [CAMPUS]</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="h5 mb-3"><i class="fas fa-sun me-2"></i>Pause du matin</h3>
                                    <div class="list-group mb-3">
                                        <?php foreach ($morning_slots as $slot): ?>
                                            <?php
                                            $count = getSlotCount($slot['id']);
                                            $quota = getSlotQuota($slot['id']);
                                            $isActive = isSlotActive($slot['id'], 'campus');
                                            $disabled = $count >= $quota || !$isActive ? 'disabled' : '';
                                            $badge_class = $count >= $quota ? 'bg-danger' : 'bg-success';
                                            ?>
                                            <label class="list-group-item d-flex justify-content-between align-items-center <?= $disabled ?>">
                                                <div>
                                                    <input type="radio" name="morning_slot" value="<?= $slot['id'] ?>" class="form-check-input me-2" <?= $disabled ?>>
                                                    <i class="far fa-clock me-1"></i><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?>
                                                </div>
                                                <span class="badge rounded-pill <?= $badge_class ?>"><?= $count ?>/<?= $quota ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h3 class="h5 mb-3"><i class="fas fa-moon me-2"></i>Pause de l'après-midi</h3>
                                    <div class="list-group mb-3">
                                        <?php foreach ($afternoon_slots as $slot): ?>
                                            <?php
                                            $count = getSlotCount($slot['id']);
                                            $quota = getSlotQuota($slot['id']);
                                            $isActive = isSlotActive($slot['id'], 'campus');
                                            $disabled = $count >= $quota || !$isActive ? 'disabled' : '';
                                            $badge_class = $count >= $quota ? 'bg-danger' : 'bg-success';
                                            ?>
                                            <label class="list-group-item d-flex justify-content-between align-items-center <?= $disabled ?>">
                                                <div>
                                                    <input type="radio" name="afternoon_slot" value="<?= $slot['id'] ?>" class="form-check-input me-2" <?= $disabled ?>>
                                                    <i class="far fa-clock me-1"></i><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?>
                                                </div>
                                                <span class="badge rounded-pill <?= $badge_class ?>"><?= $count ?>/<?= $quota ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Réserver mes pauses
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour actualiser les créneaux disponibles
        function refreshSlots() {
            fetch('ajax/refresh-slots.php?perimeter=campus')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour les créneaux du matin
                        updateSlotSection('morning', data.morning_slots);
                        // Mettre à jour les créneaux de l'après-midi
                        updateSlotSection('afternoon', data.afternoon_slots);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de l\'actualisation des créneaux:', error);
                });
        }

        // Fonction pour mettre à jour une section de créneaux
        function updateSlotSection(period, slots) {
            const container = document.querySelector(`input[name="${period}_slot"]`).closest('.list-group');
            if (!container) return;

            // Sauvegarder la sélection actuelle
            const currentSelection = document.querySelector(`input[name="${period}_slot"]:checked`);
            const currentValue = currentSelection ? currentSelection.value : null;

            // Vider le conteneur
            container.innerHTML = '';

            // Reconstruire les créneaux
            slots.forEach(slot => {
                const count = slot.count;
                const quota = slot.quota;
                const isActive = slot.is_active;
                const disabled = count >= quota || !isActive;
                const badgeClass = count >= quota ? 'bg-danger' : 'bg-success';

                const slotHtml = `
                    <label class="list-group-item d-flex justify-content-between align-items-center ${disabled ? 'disabled' : ''}">
                        <div>
                            <input type="radio" name="${period}_slot" value="${slot.id}" class="form-check-input me-2" ${disabled ? 'disabled' : ''} ${currentValue == slot.id ? 'checked' : ''}>
                            <i class="far fa-clock me-1"></i>${slot.start_time} - ${slot.end_time}
                        </div>
                        <span class="badge rounded-pill ${badgeClass}">${count}/${quota}</span>
                    </label>
                `;
                container.insertAdjacentHTML('beforeend', slotHtml);
            });
        }

        // Actualiser les créneaux toutes les 30 secondes
        setInterval(refreshSlots, 30000);

        // Actualiser une première fois après 5 secondes
        setTimeout(refreshSlots, 5000);
    </script>
</body>

</html>