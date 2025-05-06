<?php
function getUnreadNotifications($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND read_at IS NULL 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markNotificationAsRead($conn, $notificationId) {
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET read_at = NOW() 
        WHERE id = ?
    ");
    return $stmt->execute([$notificationId]);
}

function createNotification($conn, $userId, $cotacaoId, $message, $type, $details = null) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_id, 
            cotacao_id, 
            message, 
            type, 
            details, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    return $stmt->execute([$userId, $cotacaoId, $message, $type, $details]);
}
?> 