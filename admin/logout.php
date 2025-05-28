<?php
session_start();

// Effacer toutes les variables de session admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_perimeter']);
unset($_SESSION['current_perimeter']);
unset($_SESSION['selected_perimeter']);

// Détruire complètement la session
session_destroy();

header('Location: ../index.php');
exit;
