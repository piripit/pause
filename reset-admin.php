<?php
require_once 'config/database.php';

// Script pour réinitialiser le mot de passe administrateur
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['new_username'] ?? 'admin';
    $new_password = $_POST['new_password'] ?? 'admin123';

    if (empty($new_username) || empty($new_password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $conn = getConnection();

        // Vérifier si l'admin existe déjà
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check_stmt->bind_param("s", $new_username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Mettre à jour le mot de passe de l'admin existant
            $admin_id = $check_result->fetch_assoc()['id'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $admin_id);

            if ($update_stmt->execute()) {
                $success = true;
            } else {
                $error = 'Erreur lors de la mise à jour du mot de passe: ' . $conn->error;
            }

            $update_stmt->close();
        } else {
            // Créer un nouvel admin
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $insert_stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $new_username, $hashed_password);

            if ($insert_stmt->execute()) {
                $success = true;
            } else {
                $error = 'Erreur lors de la création de l\'administrateur: ' . $conn->error;
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation Admin - Gestion des Pauses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0"><i class="fas fa-user-shield me-2"></i>Réinitialisation Administrateur</h1>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <p class="mb-0">L'administrateur a été créé ou mis à jour avec succès.</p>
                                <p class="mb-0">Nom d'utilisateur: <strong><?= htmlspecialchars($_POST['new_username'] ?? 'admin') ?></strong></p>
                                <p class="mb-0">Mot de passe: <strong><?= htmlspecialchars($_POST['new_password'] ?? 'admin123') ?></strong></p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="admin-login.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <p class="mb-0">Ce script permet de réinitialiser ou créer un compte administrateur.</p>
                                <p class="mb-0">Utilisez-le uniquement si vous ne pouvez pas vous connecter à l'interface d'administration.</p>
                            </div>

                            <form action="reset-admin.php" method="post">
                                <div class="mb-3">
                                    <label for="new_username" class="form-label">Nom d'utilisateur</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="new_username" name="new_username" value="admin" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="text" class="form-control" id="new_password" name="new_password" value="admin123" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Créer/Réinitialiser l'administrateur
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>