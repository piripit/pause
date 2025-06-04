<?php
function getConnection()
{
    $host = 'localhost';
    $username = 'root';
    $password = 'root';
    $database = 'pause_management';

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
