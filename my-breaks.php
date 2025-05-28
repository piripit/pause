<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';

session_start();

$error = '';
$success = '';
$employee = null;
$breaks = [];
$perimeters = ['campus' => 'Campus', 'entreprise' => 'Entreprise', 'asn' => 'ASN'];
$selectedPerimeter = '';
$hidePerimeterSelector = false;

// Si un périmètre est passé en paramètre GET, le présélectionner et cacher le sélecteur
if (isset($_GET['perimeter']) && array_key_exists($_GET['perimeter'], $perimeters)) {
    $selectedPerimeter = $_GET['perimeter'];
    $hidePerimeterSelector = true;
    $_SESSION['fixed_perimeter'] = $selectedPerimeter;
}

// Obtenir les informations de thème
$theme = getThemeInfo($selectedPerimeter);
$theme_color = $theme['color'];
$theme_icon = $theme['icon'];
$perimeter_name = $theme['name'];

// Traitement de l'activation d'une pause
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_break'])) {
    $reservation_id = $_POST['reservation_id'] ?? 0;
    $employee_id = $_POST['employee_id'] ?? 0;

    if (empty($reservation_id)) {
        $error = 'Erreur: Pause non spécifiée';
    } else {
        try {
            $result = activateBreak($reservation_id);

            if ($result === true) {
                $success = 'Votre pause a été activée avec succès';

                // Récupérer les informations de l'employé pour afficher ses pauses mises à jour
                if ($employee_id) {
                    $employee = getEmployeeById($employee_id);
                    if ($employee) {
                        $breaks = getEmployeeBreaks($employee['id']);

                        // Extraire le périmètre du nom de l'employé
                        if (preg_match('/^\[(CAMPUS|ENTREPRISE|ASN)\]/i', $employee['name'], $matches)) {
                            $selectedPerimeter = strtolower($matches[1]);
                        }
                    }
                }
            } else {
                $error = $result; // Afficher l'erreur exacte retournée par la fonction activateBreak
            }
        } catch (Exception $e) {
            $error = 'Une erreur est survenue lors de l\'activation de la pause';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['activate_break'])) {
    $employee_name = $_POST['employee_name'] ?? '';
    $perimeter = $_POST['perimeter'] ?? $selectedPerimeter;

    if (empty($employee_name)) {
        $error = 'Veuillez entrer votre nom';
    } elseif (empty($perimeter) || !array_key_exists($perimeter, $perimeters)) {
        $error = 'Veuillez sélectionner votre périmètre';
    } else {
        $selectedPerimeter = $perimeter;

        // Ajouter le préfixe du périmètre au nom
        $prefixedName = "[" . strtoupper($perimeter) . "] " . $employee_name;

        $employee = getEmployeeByName($prefixedName);

        if (!$employee) {
            $error = 'Aucun employé trouvé avec ce nom dans le périmètre sélectionné';
        } else {
            $breaks = getEmployeeBreaks($employee['id']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Pauses - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="30">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-<?= $theme_color ?>">
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
                        <a class="nav-link active" href="my-breaks.php<?= $hidePerimeterSelector ? '?perimeter=' . $selectedPerimeter : '' ?>">
                            <i class="fas fa-calendar-check me-1"></i>Mes pauses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="activate-break.php<?= $selectedPerimeter ? '?perimeter=' . $selectedPerimeter : '' ?>">
                            <i class="fas fa-play-circle me-1"></i>Activer ma pause
                        </a>
                    </li>
                    <?php if ($selectedPerimeter): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $selectedPerimeter ?>.php">
                                <i class="fas <?= $theme_icon ?> me-1"></i>Espace <?= $perimeters[$selectedPerimeter] ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-<?= $theme_color ?> text-white">
                        <h1 class="h4 mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Consulter mes pauses
                            <?php if ($hidePerimeterSelector && $selectedPerimeter): ?>
                                - <?= $perimeters[$selectedPerimeter] ?>
                            <?php endif; ?>
                        </h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <p class="mb-0">
                                <?php if ($hidePerimeterSelector): ?>
                                    Entrez votre nom pour consulter vos pauses réservées dans le périmètre <?= $perimeters[$selectedPerimeter] ?>.
                                <?php else: ?>
                                    Sélectionnez votre périmètre et entrez votre nom pour consulter vos pauses réservées.
                                <?php endif; ?>
                            </p>
                        </div>

                        <form action="my-breaks.php<?= $hidePerimeterSelector ? '?perimeter=' . $selectedPerimeter : '' ?>" method="post" class="mb-4">
                            <?php if (!$hidePerimeterSelector): ?>
                                <div class="mb-3">
                                    <label for="perimeter" class="form-label">Votre périmètre</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <select class="form-select" id="perimeter" name="perimeter" required>
                                            <option value="" <?= !$selectedPerimeter ? 'selected' : '' ?> disabled>Sélectionnez votre périmètre</option>
                                            <?php foreach ($perimeters as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $selectedPerimeter === $value ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="perimeter" value="<?= $selectedPerimeter ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Votre nom</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                                    <button type="submit" class="btn btn-<?= $theme_color ?>">
                                        <i class="fas fa-search me-2"></i>Rechercher
                                    </button>
                                </div>
                                <div class="form-text text-muted">
                                    Entrez votre nom sans préfixe, celui-ci sera ajouté automatiquement.
                                    <?php if ($hidePerimeterSelector): ?>
                                        (Préfixe: [<?= strtoupper($selectedPerimeter) ?>])
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <?php if ($employee && empty($breaks)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <p class="mb-0">Vous n'avez pas encore réservé de pause.</p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?= $selectedPerimeter ?>.php" class="btn btn-<?= $theme_color ?>">
                                    <i class="fas fa-calendar-plus me-2"></i>Réserver une pause
                                </a>
                            </div>
                        <?php elseif ($employee && !empty($breaks)): ?>
                            <h3 class="h5 mb-3">
                                <i class="fas fa-list me-2"></i>Pauses réservées pour <?= htmlspecialchars($employee['name']) ?>
                            </h3>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="fas fa-calendar-day me-1"></i>Date</th>
                                            <th><i class="fas fa-clock me-1"></i>Horaire</th>
                                            <th><i class="fas fa-sun me-1"></i>Période</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Statut</th>
                                            <th><i class="fas fa-play me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($breaks as $break): ?>
                                            <?php
                                            $date = new DateTime($break['reservation_date']);
                                            $today = new DateTime();
                                            $is_today = $date->format('Y-m-d') === $today->format('Y-m-d');

                                            $start_time = new DateTime($break['start_time']);
                                            $end_time = new DateTime($break['end_time']);
                                            $current_time = new DateTime();

                                            $status = 'À venir';
                                            $status_class = 'bg-info';
                                            $status_icon = 'fa-clock';

                                            // Vérifier si la pause peut être activée
                                            $can_activate = $is_today && $break['status'] === 'reserved';

                                            // Permettre l'activation 5 minutes avant le début et jusqu'à 5 minutes après la fin
                                            if ($can_activate) {
                                                $activation_time = clone $start_time;
                                                $activation_time->sub(new DateInterval('PT5M')); // 5 minutes avant

                                                $activation_end = clone $end_time;
                                                $activation_end->add(new DateInterval('PT5M')); // 5 minutes après la fin

                                                // La pause peut être activée entre 5 minutes avant le début et 5 minutes après la fin
                                                $can_activate = $current_time >= $activation_time && $current_time <= $activation_end;
                                            }

                                            if ($break['status'] === 'started') {
                                                $status = 'En cours';
                                                $status_class = 'bg-success';
                                                $status_icon = 'fa-play';
                                            } elseif ($break['status'] === 'completed') {
                                                $status = 'Terminée';
                                                $status_class = 'bg-primary';
                                                $status_icon = 'fa-check';
                                            } elseif ($break['status'] === 'missed') {
                                                $status = 'Non prise';
                                                $status_class = 'bg-danger';
                                                $status_icon = 'fa-times';
                                            } elseif ($break['status'] === 'delayed') {
                                                $status = 'Décalée';
                                                $status_class = 'bg-warning';
                                                $status_icon = 'fa-exclamation-triangle';
                                            } elseif (!$is_today || $date < $today) {
                                                $status = 'Terminée';
                                                $status_class = 'bg-secondary';
                                                $status_icon = 'fa-check';
                                            }
                                            ?>
                                            <tr>
                                                <td><?= $date->format('d/m/Y') ?></td>
                                                <td><?= $break['start_time'] ?> - <?= $break['end_time'] ?></td>
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
                                                <td>
                                                    <span class="badge <?= $status_class ?>">
                                                        <i class="fas <?= $status_icon ?> me-1"></i><?= $status ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($can_activate): ?>
                                                        <form action="my-breaks.php<?= $hidePerimeterSelector ? '?perimeter=' . $selectedPerimeter : '' ?>" method="post" class="d-inline">
                                                            <input type="hidden" name="activate_break" value="1">
                                                            <input type="hidden" name="reservation_id" value="<?= $break['id'] ?>">
                                                            <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-play me-1"></i>Activer
                                                            </button>
                                                        </form>
                                                    <?php elseif ($break['status'] === 'started'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-hourglass-half me-1"></i>En cours
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-3">
                                <a href="<?= $selectedPerimeter ?>.php" class="btn btn-<?= $theme_color ?>">
                                    <i class="fas fa-calendar-plus me-2"></i>Réserver une autre pause
                                </a>

                                <?php if ($selectedPerimeter): ?>
                                    <a href="<?= $selectedPerimeter ?>.php" class="btn btn-outline-<?= $theme_color ?> ms-2">
                                        <i class="fas <?= $theme_icon ?> me-2"></i>Retour à l'espace <?= $perimeters[$selectedPerimeter] ?>
                                    </a>
                                <?php endif; ?>
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