<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Estabelecer conexão com o banco de dados
$conn = conectarDB();

// Receber parâmetros
$sawing_id = $_GET['sawing_id'] ?? null;
$data_registro = $_GET['data_registro'] ?? null;

// Validar parâmetros
if (!$sawing_id || !$data_registro) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Buscar produtos do sawing atual
    $stmt = $conn->prepare("
        SELECT descricao, quantidade, valor_unitario_final, fornecedor
        FROM sawing_itens
        WHERE sawing_id = ?
    ");
    $stmt->execute([$sawing_id]);
    $produtos_atuais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($produtos_atuais)) {
        echo json_encode(['error' => 'Nenhum produto encontrado no sawing atual']);
        exit;
    }

    // Array para armazenar produtos já comparados
    $produtos_comparados = [];
    $comparacoes = [];

    // Buscar sawings aprovados anteriores
    $stmt = $conn->prepare("
        SELECT DISTINCT s.id, s.data_registro
        FROM sawing s
        INNER JOIN sawing_itens si ON s.id = si.sawing_id
        WHERE s.status = 'concluido'
        AND s.data_registro < ?
        AND s.id != ?
        AND si.descricao IN (
            SELECT descricao 
            FROM sawing_itens 
            WHERE sawing_id = ?
        )
        ORDER BY s.data_registro DESC
    ");
    $stmt->execute([$data_registro, $sawing_id, $sawing_id]);
    $sawings_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sawings_anteriores as $sawing_anterior) {
        // Buscar produtos do sawing anterior
        $stmt = $conn->prepare("
            SELECT descricao, quantidade, valor_unitario_final, fornecedor
            FROM sawing_itens
            WHERE sawing_id = ?
            AND descricao IN (
                SELECT descricao 
                FROM sawing_itens 
                WHERE sawing_id = ?
            )
        ");
        $stmt->execute([$sawing_anterior['id'], $sawing_id]);
        $produtos_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filtrar apenas produtos que ainda não foram comparados
        $produtos_para_comparar = [];
        foreach ($produtos_anteriores as $produto) {
            if (!in_array($produto['descricao'], $produtos_comparados)) {
                $produtos_para_comparar[] = $produto;
                $produtos_comparados[] = $produto['descricao'];
            }
        }

        if (!empty($produtos_para_comparar)) {
            $comparacoes[] = [
                'data_registro' => $sawing_anterior['data_registro'],
                'produtos_anteriores' => $produtos_para_comparar
            ];
        }
    }

    if (empty($comparacoes)) {
        echo json_encode([
            'error' => 'Nenhum sawing anterior encontrado com produtos similares',
            'produtos_atuais' => $produtos_atuais,
            'comparacoes' => []
        ]);
        exit;
    }

    // Retornar dados
    echo json_encode([
        'produtos_atuais' => $produtos_atuais,
        'comparacoes' => $comparacoes
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
} 