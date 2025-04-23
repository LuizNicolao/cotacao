<?php
error_reporting(0);
ini_set('display_errors', 0);

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

$conn = conectarDB();

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT c.*, u.nome as usuario_nome
        FROM cotacoes c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $cotacao = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get items
    $stmt = $conn->prepare("SELECT * FROM itens_cotacao WHERE cotacao_id = ?");
    $stmt->execute([$_GET['id']]);
    $cotacao['itens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cotacao);
}
