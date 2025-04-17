<?php
require_once 'config/database.php';

// Vérifier la connexion à la base de données
$db_status = 'OK';
$db_error = '';
try {
    $conn = getConnection();
    if ($conn->connect_error) {
        $db_status = 'ERREUR';
        $db_error = $conn->connect_error;
    }
} catch (Exception $e) {
    $db_status = 'ERREUR';
    $db_error = $e->getMessage();
}

// Vérifier la table des administrateurs
$admin_table_status = 'OK';
$admin_count = 0;
$admin_error = '';
try {
    if ($db_status === 'OK') {
        $result = $conn->query("SELECT COUNT(*) as count FROM admins");
        if ($result) {
            $admin_count = $result->fetch_assoc()['count'];
        } else {
            $admin_table_status = 'ERREUR';
            $admin_error = $conn->error;
        }
    }
} catch (Exception $e) {
    $admin_table_status = 'ERREUR';
    $admin_error = $e->getMessage();
}

// Vérifier les tables de l'application
$tables = ['admins', 'employees', 'break_slots', 'break_reservations'];
$table_status = [];

try {
    if ($db_status === 'OK') {
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $table_status[$table] = $result->num_rows > 0 ? 'OK' : 'MANQUANTE';
        }
    }
} catch (Exception $e) {
    $table_error = $e->getMessage();
}

// Fermer la connexion
if (isset($conn) && $db_status === 'OK') {
    $conn->close();
}

// Vérifier la version de PHP
$php_version = phpversion();
$php_status = version_compare($php_version, '7.4.0', '>=') ? 'OK' : 'OBSOLÈTE';

// Vérifier les extensions PHP requises
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'session'];
$extension_status = [];

foreach ($required_extensions as $ext) {
    $extension_status[$ext] = extension_loaded($ext) ? 'OK' : 'MANQUANTE';
}

// Vérifier les permissions des dossiers
$folders = ['.', 'admin', 'assets', 'config', 'includes'];
$folder_status = [];

foreach ($folders as $folder) {
    $folder_status[$folder] = [
        'readable' => is_readable($folder) ? 'OK' : 'NON',
        'writable' => is_writable($folder) ? 'OK' : 'NON'
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations de Débogage - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container py-5">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0"><i class="fas fa-bug me-2"></i>Informations de Débogage</h1>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <p class="mb-0">Cette page affiche des informations techniques sur votre installation. Elle peut être utile pour résoudre des problèmes.</p>
                </div>

                <h2 class="h5 mt-4 mb-3"><i class="fas fa-database me-2"></i>Base de données</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Connexion</th>
                            <td>
                                <?php if ($db_status === 'OK'): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ERREUR</span>
                                    <p class="text-danger mb-0"><?= htmlspecialchars($db_error) ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Table des administrateurs</th>
                            <td>
                                <?php if ($admin_table_status === 'OK'): ?>
                                    <span class="badge bg-success">OK</span>
                                    <p class="mb-0">Nombre d'administrateurs: <?= $admin_count ?></p>
                                <?php else: ?>
                                    <span class="badge bg-danger">ERREUR</span>
                                    <p class="text-danger mb-0"><?= htmlspecialchars($admin_error) ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Tables de l'application</th>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($table_status as $table => $status): ?>
                                        <li>
                                            <?php if ($status === 'OK'): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">MANQUANTE</span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($table) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2 class="h5 mt-4 mb-3"><i class="fas fa-server me-2"></i>Environnement PHP</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Version PHP</th>
                            <td>
                                <?php if ($php_status === 'OK'): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">OBSOLÈTE</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($php_version) ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Extensions PHP</th>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($extension_status as $ext => $status): ?>
                                        <li>
                                            <?php if ($status === 'OK'): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">MANQUANTE</span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($ext) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2 class="h5 mt-4 mb-3"><i class="fas fa-folder me-2"></i>Permissions des dossiers</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Dossier</th>
                            <th>Lecture</th>
                            <th>Écriture</th>
                        </tr>
                        <?php foreach ($folder_status as $folder => $status): ?>
                            <tr>
                                <td><?= htmlspecialchars($folder) ?></td>
                                <td>
                                    <?php if ($status['readable'] === 'OK'): ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">NON</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status['writable'] === 'OK'): ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">NON</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary me-2">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </a>
                    <a href="reset-admin.php" class="btn btn-warning">
                        <i class="fas fa-user-shield me-2"></i>Réinitialiser l'administrateur
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>