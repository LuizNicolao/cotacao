<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $conn = conectarDB();
    
    // Buscar fornecedores únicos
    $sql = "SELECT DISTINCT fornecedor 
            FROM sawing_itens si 
            JOIN sawing s ON s.id = si.sawing_id 
            WHERE s.status = 'concluido' 
            ORDER BY fornecedor";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar produtos únicos
    $sql = "SELECT DISTINCT descricao 
            FROM sawing_itens si 
            JOIN sawing s ON s.id = si.sawing_id 
            WHERE s.status = 'concluido' 
            ORDER BY descricao";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'fornecedores' => array_column($fornecedores, 'fornecedor'),
        'produtos' => array_column($produtos, 'descricao')
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 