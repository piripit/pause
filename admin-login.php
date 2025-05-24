<?php
// Affichage des erreurs pour déboguer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirection vers le tableau de bord admin si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
$perimeters = [
    'campus' => 'Campus',
    'entreprise' => 'Entreprise',
    'asn' => 'ASN',
    'all' => 'Tous les périmètres'
];
$selectedPerimeter = '';

// Traitement de la connexion admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $conn = getConnection();

        // Vérifier la connexion à la base de données
        if ($conn->connect_error) {
            $error = 'Erreur de connexion à la base de données: ' . $conn->connect_error;
        } else {
            // Requête pour récupérer l'admin
            $stmt = $conn->prepare("SELECT id, username, password, perimeter FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Si le mot de passe est 'admin123' et l'utilisateur est valide, autoriser l'accès directement
                if ($password === 'admin123') {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_perimeter'] = $admin['perimeter'];

                    header('Location: admin/dashboard.php');
                    exit;
                }
                // Sinon vérifier avec password_verify
                elseif (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_perimeter'] = $admin['perimeter'];

                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error = 'Identifiants incorrects';
                }
            } else {
                $error = 'Identifiants incorrects';
            }

            $stmt->close();
            $conn->close();
        }
    }
}

// Déterminer la couleur du thème
$themeColor = 'primary';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-login.php">
                            <i class="fas fa-lock me-1"></i>Administration
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-lock me-2"></i>Connexion Administrateur</h1>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <p class="mb-0">Veuillez vous connecter pour accéder à l'interface d'administration de votre périmètre.</p>
                        </div>

                        <form action="admin-login.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-text text-muted">
                                    <small>Exemple: admin_campus, admin_entreprise, admin_asn ou admin pour tous les périmètres</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Connexion
                            </button>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>