<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirection vers le tableau de bord admin si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pauses - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .perimeter-card {
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }

        .perimeter-card:hover {
            transform: translateY(-10px);
        }

        .perimeter-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
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
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10 text-center">
                <h1 class="display-4 mb-3">Bienvenue dans l'application de gestion des pauses</h1>
                <p class="lead">Sélectionnez votre périmètre d'activité pour accéder à votre espace de gestion des pauses</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow text-center perimeter-card">
                    <div class="card-body py-5">
                        <i class="fas fa-university perimeter-icon text-primary"></i>
                        <h2 class="h3 mb-3">Campus</h2>
                        <p class="mb-4">Espace dédié aux techniciens du périmètre Campus</p>
                        <a href="campus.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow text-center perimeter-card">
                    <div class="card-body py-5">
                        <i class="fas fa-building perimeter-icon text-success"></i>
                        <h2 class="h3 mb-3">Entreprise</h2>
                        <p class="mb-4">Espace dédié aux techniciens du périmètre Entreprise</p>
                        <a href="entreprise.php" class="btn btn-success btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow text-center perimeter-card">
                    <div class="card-body py-5">
                        <i class="fas fa-shield-alt perimeter-icon text-danger"></i>
                        <h2 class="h3 mb-3">ASN</h2>
                        <p class="mb-4">Espace dédié aux techniciens du périmètre ASN</p>
                        <a href="asn.php" class="btn btn-danger btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h3 class="h5 mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><i class="fas fa-check-circle text-success me-2"></i>Réservez vos pauses facilement</p>
                                <p><i class="fas fa-check-circle text-success me-2"></i>Visualisez les créneaux disponibles</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-check-circle text-success me-2"></i>Activez vos pauses au moment de les prendre</p>
                                <p><i class="fas fa-check-circle text-success me-2"></i>Consultez l'historique de vos pauses</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>