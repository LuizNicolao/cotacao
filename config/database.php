<?php
// filepath: c:\Xampp\htdocs\cotacao\config\database.php
function conectarDB() {
    $host = 'localhost';
    $dbname = 'cotacao';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // Log the error
        error_log("Database connection error: " . $e->getMessage());

        // Return a JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        exit;
    }
}
?>