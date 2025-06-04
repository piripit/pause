<?php
function getConnection()
{
    $host = 'localhost';
    $username = 'root';
    $password = '';  // Pas de mot de passe pour root avec sudo mysql
    $database = 'pause_management';

    // Essayer mysqli d'abord
    if (extension_loaded('mysqli')) {
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    }

    // Sinon utiliser PDO
    if (extension_loaded('pdo_mysql')) {
        try {
            $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
            $conn = new PDO($dsn, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    die("Neither mysqli nor PDO MySQL extension is available!");
}
