<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Obter parâmetros
$produtoId = $_GET['produto_id'] ?? null;
$produtoNome = $_GET['produto_nome'] ?? '';

if (!$produtoNome) {
    echo json_encode(['success' => false, 'message' => 'Nome do produto não fornecido']);
    exit;
}

try {
    $conn = conectarDB();
    
    // Preparar o termo de busca - remover números no final e espaços extras
    $nomeLimpo = preg_replace('/\d+$/', '', $produtoNome);
    $nomeLimpo = trim($nomeLimpo);
    $termoBusca = '%' . str_replace(' ', '%', $nomeLimpo) . '%';
    
    // Buscar histórico do produto usando LIKE para comparação flexível
    $query = "SELECT 
        s.data_registro as data_criacao,
        u.nome as usuario_nome,
        si.valor_unitario_inicial as valor_inicial,
        si.valor_unitario_final as valor_final,
        si.quantidade,
        si.descricao as produto_nome,
        s.status,
        si.fornecedor as fornecedor_nome,
        s.tipo,
        (si.valor_unitario_final * si.quantidade) as valor_total
    FROM sawing s
    JOIN sawing_itens si ON s.id = si.sawing_id
    JOIN usuarios u ON s.usuario_id = u.id
    WHERE si.descricao LIKE :descricao
    AND (s.status = 'aprovado' OR s.status = 'concluido')
    ORDER BY s.data_registro DESC
    LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['descricao' => $termoBusca]);
    
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($historico)) {
        // Se não encontrar resultados, tentar uma busca mais flexível
        $termoBuscaMaisFlexivel = '%' . $nomeLimpo . '%';
        $stmt->execute(['descricao' => $termoBuscaMaisFlexivel]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'historico' => $historico
    ]);
} catch (PDOException $e) {
    error_log("Erro na busca de histórico: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar histórico',
        'message' => $e->getMessage()
    ]);
} 