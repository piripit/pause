<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = $_POST['employee_name'] ?? '';
    $morning_slot = $_POST['morning_slot'] ?? null;
    $afternoon_slot = $_POST['afternoon_slot'] ?? null;

    if (empty($employee_name)) {
        $error = 'Veuillez entrer votre nom';
    } elseif (!$morning_slot && !$afternoon_slot) {
        $error = 'Veuillez sélectionner au moins un créneau de pause';
    } else {
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
                        $error = 'Erreur lors de la réservation du créneau du matin';
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
                        $error = 'Erreur lors de la réservation du créneau de l\'après-midi';
                    }
                }
            }

            if ($morning_success && $afternoon_success) {
                $success = 'Vos pauses ont été réservées avec succès';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation de Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0">Réservation de Pauses</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p><?= $success ?></p>
                        <p>Merci, <?= htmlspecialchars($employee_name) ?>, vos pauses ont été enregistrées.</p>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>