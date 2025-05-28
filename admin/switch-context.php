<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

// Si un périmètre est demandé, vérifier que l'admin y a accès
if (isset($_GET['perimeter'])) {
    $requested_perimeter = $_GET['perimeter'];
    $admin_perimeter = $_SESSION['admin_perimeter'];

    // Un admin 'all' peut accéder à tous les périmètres
    // Un admin spécifique ne peut accéder qu'à son périmètre
    if ($admin_perimeter === 'all' || $admin_perimeter === $requested_perimeter) {
        $_SESSION['current_perimeter'] = $requested_perimeter;
        header('Location: dashboard.php');
        exit;
    } else {
        // Accès refusé
        $_SESSION['error'] = "Vous n'avez pas accès à ce périmètre";
        header('Location: dashboard.php');
        exit;
    }
}

// Si on arrive ici, afficher la page de sélection
$admin_perimeter = $_SESSION['admin_perimeter'];
$available_perimeters = [];

if ($admin_perimeter === 'all') {
    $available_perimeters = [
        'all' => 'Tous les périmètres',
        'campus' => 'Campus',
        'entreprise' => 'Entreprise',
        'asn' => 'ASN'
    ];
} else {
    $available_perimeters[$admin_perimeter] = ucfirst($admin_perimeter);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer de contexte - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-exchange-alt me-2"></i>Changer de contexte</h1>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Sélectionnez le périmètre que vous souhaitez administrer :</p>

                        <div class="list-group">
                            <?php foreach ($available_perimeters as $key => $name): ?>
                                <a href="switch-context.php?perimeter=<?= $key ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-angle-right me-2"></i><?= $name ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>