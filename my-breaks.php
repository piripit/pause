<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$employee = null;
$breaks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = $_POST['employee_name'] ?? '';

    if (empty($employee_name)) {
        $error = 'Veuillez entrer votre nom';
    } else {
        $employee = getEmployeeByName($employee_name);

        if (!$employee) {
            $error = 'Aucun employé trouvé avec ce nom';
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
                        <a class="nav-link active" href="my-breaks.php">
                            <i class="fas fa-calendar-check me-1"></i>Mes pauses
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
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-calendar-check me-2"></i>Consulter mes pauses</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <p class="mb-0">Entrez votre nom pour consulter vos pauses réservées.</p>
                        </div>

                        <form action="my-breaks.php" method="post" class="mb-4">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Votre nom</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Rechercher
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($employee && empty($breaks)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <p class="mb-0">Vous n'avez pas encore réservé de pause.</p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-primary">
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

                                            if (!$is_today || $date < $today) {
                                                $status = 'Terminée';
                                                $status_class = 'bg-secondary';
                                                $status_icon = 'fa-check';
                                            } elseif ($current_time >= $start_time && $current_time <= $end_time) {
                                                $status = 'En cours';
                                                $status_class = 'bg-success';
                                                $status_icon = 'fa-play';
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Réserver une autre pause
                                </a>
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