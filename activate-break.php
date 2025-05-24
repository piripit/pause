<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ajouter cette fonction de journalisation des erreurs
function logError($message, $context = [])
{
    $logFile = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' - Context: ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

session_start();

// Gérer la déconnexion
if (isset($_GET['logout'])) {
    // Supprimer uniquement les variables de session liées à l'employé
    unset($_SESSION['employee_id']);
    unset($_SESSION['employee_name']);
    unset($_SESSION['employee_perimeter']);

    // Rediriger vers la même page pour éviter les problèmes de rafraîchissement
    header('Location: activate-break.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$employee = null;
$active_breaks = [];
$upcoming_breaks = [];
$perimeters = ['campus' => 'Campus', 'entreprise' => 'Entreprise', 'asn' => 'ASN'];
$selectedPerimeter = '';

// Si un périmètre est passé en paramètre GET, le présélectionner
if (isset($_GET['perimeter']) && array_key_exists($_GET['perimeter'], $perimeters)) {
    $selectedPerimeter = $_GET['perimeter'];
}

// Traitement de la recherche d'employé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_employee'])) {
    $employee_name = $_POST['employee_name'] ?? '';
    $perimeter = $_POST['perimeter'] ?? '';

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
            // Stocker l'ID de l'employé en session pour les actions suivantes
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_name'] = $employee['name'];
            $_SESSION['employee_perimeter'] = $perimeter;

            // Récupérer les pauses actives et à venir pour aujourd'hui
            $active_breaks = getEmployeeActiveBreaks($employee['id']);
            $upcoming_breaks = getEmployeeUpcomingBreaks($employee['id']);
        }
    }
}

// Traitement de l'activation d'une pause
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_break'])) {
    $reservation_id = $_POST['reservation_id'] ?? 0;

    if (empty($reservation_id)) {
        $error = 'Erreur: Pause non spécifiée';
        logError('Activation de pause: ID de réservation manquant');
    } else {
        try {
            $result = activateBreak($reservation_id);

            if ($result === true) {
                $success = 'Votre pause a été activée avec succès';

                // Rafraîchir les données
                if (isset($_SESSION['employee_id'])) {
                    $employee = getEmployeeById($_SESSION['employee_id']);
                    $active_breaks = getEmployeeActiveBreaks($_SESSION['employee_id']);
                    $upcoming_breaks = getEmployeeUpcomingBreaks($_SESSION['employee_id']);

                    if (isset($_SESSION['employee_perimeter'])) {
                        $selectedPerimeter = $_SESSION['employee_perimeter'];
                    }
                }
            } else {
                $error = 'Erreur lors de l\'activation de la pause: ' . $result;
                logError('Activation de pause: ' . $result, ['reservation_id' => $reservation_id]);
            }
        } catch (Exception $e) {
            $error = 'Une erreur est survenue lors de l\'activation de la pause';
            logError('Exception lors de l\'activation de pause: ' . $e->getMessage(), [
                'reservation_id' => $reservation_id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}

// Si l'employé est déjà en session, récupérer ses informations
if (isset($_SESSION['employee_id']) && !$employee) {
    $employee = getEmployeeById($_SESSION['employee_id']);
    $active_breaks = getEmployeeActiveBreaks($_SESSION['employee_id']);
    $upcoming_breaks = getEmployeeUpcomingBreaks($_SESSION['employee_id']);

    if (isset($_SESSION['employee_perimeter'])) {
        $selectedPerimeter = $_SESSION['employee_perimeter'];
    }
}

// Déterminer la couleur du thème en fonction du périmètre sélectionné
$themeColor = 'primary';
if ($selectedPerimeter === 'entreprise') {
    $themeColor = 'success';
} elseif ($selectedPerimeter === 'asn') {
    $themeColor = 'danger';
}

// Déterminer l'icône du thème en fonction du périmètre sélectionné
$themeIcon = 'fa-university';
if ($selectedPerimeter === 'entreprise') {
    $themeIcon = 'fa-building';
} elseif ($selectedPerimeter === 'asn') {
    $themeIcon = 'fa-shield-alt';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activer ma pause - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="30"> <!-- Rafraîchir la page toutes les 30 secondes -->
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-<?= $themeColor ?>">
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
                        <a class="nav-link" href="my-breaks.php<?= $selectedPerimeter ? '?perimeter=' . $selectedPerimeter : '' ?>">
                            <i class="fas fa-calendar-check me-1"></i>Mes pauses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="activate-break.php">
                            <i class="fas fa-play-circle me-1"></i>Activer ma pause
                        </a>
                    </li>
                    <?php if ($selectedPerimeter): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $selectedPerimeter ?>.php">
                                <i class="fas <?= $themeIcon ?> me-1"></i>Espace <?= $perimeters[$selectedPerimeter] ?>
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
            <div class="col-md-10">
                <div class="card shadow mb-4">
                    <div class="card-header bg-<?= $themeColor ?> text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-play-circle me-2"></i>Activer ma pause</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!$employee): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <p class="mb-0">Sélectionnez votre périmètre et entrez votre nom pour activer vos pauses réservées.</p>
                            </div>

                            <form action="activate-break.php" method="post" class="mb-4">
                                <input type="hidden" name="search_employee" value="1">
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
                                <div class="mb-3">
                                    <label for="employee_name" class="form-label">Votre nom</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                                        <button type="submit" class="btn btn-<?= $themeColor ?>">
                                            <i class="fas fa-search me-2"></i>Rechercher
                                        </button>
                                    </div>
                                    <div class="form-text text-muted">Entrez votre nom sans préfixe, celui-ci sera ajouté automatiquement.</div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-user-circle me-2"></i>
                                <p class="mb-0">Bonjour <strong><?= htmlspecialchars($employee['name']) ?></strong>, vous pouvez activer vos pauses ci-dessous.</p>
                                <p class="mb-0 mt-2">
                                    <a href="activate-break.php?logout=1" class="alert-link">
                                        <i class="fas fa-sign-out-alt me-1"></i>Ce n'est pas vous ?
                                    </a>
                                </p>
                            </div>

                            <div class="row">
                                <!-- Pauses actives -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success text-white">
                                            <h2 class="h5 mb-0"><i class="fas fa-hourglass-start me-2"></i>Pauses en cours</h2>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($active_breaks)): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <p class="mb-0">Vous n'avez aucune pause active en ce moment.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($active_breaks as $break): ?>
                                                    <div class="card mb-3 border-success">
                                                        <div class="card-body">
                                                            <h3 class="h6 mb-2">
                                                                <?php if ($break['period'] === 'morning'): ?>
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fas fa-sun me-1"></i>Pause du matin
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-moon me-1"></i>Pause de l'après-midi
                                                                    </span>
                                                                <?php endif; ?>
                                                            </h3>
                                                            <p class="mb-1">
                                                                <i class="far fa-clock me-1"></i>
                                                                <strong>Horaire prévu:</strong> <?= $break['start_time'] ?> - <?= $break['end_time'] ?>
                                                            </p>
                                                            <p class="mb-1">
                                                                <i class="fas fa-play me-1"></i>
                                                                <strong>Début:</strong> <?= date('H:i:s', strtotime($break['start_timestamp'])) ?>
                                                            </p>

                                                            <?php
                                                            // Calculer le temps restant
                                                            $start_time = new DateTime($break['start_timestamp']);
                                                            $end_time = clone $start_time;
                                                            $end_time->add(new DateInterval('PT10M')); // Ajouter 10 minutes
                                                            $now = new DateTime();
                                                            $remaining = $now->diff($end_time);
                                                            $total_seconds = ($remaining->i * 60) + $remaining->s;
                                                            $percentage = 100 - (($total_seconds / 600) * 100);
                                                            $percentage = max(0, min(100, $percentage));
                                                            ?>

                                                            <p class="mb-2">
                                                                <i class="fas fa-hourglass-half me-1"></i>
                                                                <strong>Temps restant:</strong>
                                                                <?php if ($now > $end_time): ?>
                                                                    <span class="text-danger">Temps écoulé</span>
                                                                <?php else: ?>
                                                                    <?= $remaining->format('%i min %s sec') ?>
                                                                <?php endif; ?>
                                                            </p>

                                                            <div class="progress mb-2">
                                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pauses à venir -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-<?= $themeColor ?> text-white">
                                            <h2 class="h5 mb-0"><i class="fas fa-calendar me-2"></i>Pauses à venir aujourd'hui</h2>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($upcoming_breaks)): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <p class="mb-0">Vous n'avez aucune pause à venir aujourd'hui.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($upcoming_breaks as $break): ?>
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h3 class="h6 mb-2">
                                                                <?php if ($break['period'] === 'morning'): ?>
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fas fa-sun me-1"></i>Pause du matin
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-moon me-1"></i>Pause de l'après-midi
                                                                    </span>
                                                                <?php endif; ?>
                                                            </h3>
                                                            <p class="mb-3">
                                                                <i class="far fa-clock me-1"></i>
                                                                <strong>Horaire:</strong> <?= $break['start_time'] ?> - <?= $break['end_time'] ?>
                                                            </p>

                                                            <?php
                                                            $start_time = new DateTime($break['start_time']);
                                                            $now = new DateTime();
                                                            $can_activate = ($start_time->format('H:i:s') === $now->format('H:i:s')) || ($now > $start_time);
                                                            ?>

                                                            <form action="activate-break.php" method="post">
                                                                <input type="hidden" name="activate_break" value="1">
                                                                <input type="hidden" name="reservation_id" value="<?= $break['id'] ?>">

                                                                <button type="submit" class="btn btn-<?= $themeColor ?> btn-sm" <?= $can_activate ? '' : 'disabled' ?>>
                                                                    <i class="fas fa-play-circle me-1"></i>Activer cette pause
                                                                </button>

                                                                <?php if (!$can_activate): ?>
                                                                    <div class="form-text text-muted mt-2">
                                                                        <i class="fas fa-info-circle me-1"></i>
                                                                        Vous pourrez activer cette pause à partir de <?= $break['start_time'] ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <?php if (empty($active_breaks) && empty($upcoming_breaks)): ?>
                                    <a href="<?= $selectedPerimeter ?>.php" class="btn btn-<?= $themeColor ?>">
                                        <i class="fas fa-calendar-plus me-2"></i>Réserver une pause
                                    </a>
                                <?php endif; ?>

                                <?php if ($selectedPerimeter): ?>
                                    <a href="<?= $selectedPerimeter ?>.php" class="btn btn-outline-<?= $themeColor ?> ms-2">
                                        <i class="fas <?= $themeIcon ?> me-2"></i>Retour à l'espace <?= $perimeters[$selectedPerimeter] ?>
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