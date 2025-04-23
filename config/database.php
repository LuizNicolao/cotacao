<?php
// filepath: c:\Xampp\htdocs\cotacao\config\database.php
function conectarDB() {
    $host = getenv('DB_HOST') ;
    $port = getenv('DB_PORT') ;
    $dbname = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    try {
        $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error',
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

?>