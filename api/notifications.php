<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

$conn = conectarDB();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['usuario']['id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'PUT':
        // Mark notifications as read
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET read_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$_SESSION['usuario']['id']]);
        echo json_encode(['success' => true]);
        break;
}
