<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = conectarDB();
$userId = $_SESSION['usuario']['id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $notifications = getUnreadNotifications($conn, $userId);
        echo json_encode(['notifications' => $notifications]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['notification_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Notification ID is required']);
            exit;
        }

        $success = markNotificationAsRead($conn, $data['notification_id']);
        echo json_encode(['success' => $success]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
