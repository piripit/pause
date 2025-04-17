<?php
// Fichier de débogage pour identifier les erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Rediriger vers la page d'activation des pauses
header('Location: activate-break.php');
exit;
