<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['fornecedor'])) {
        throw new Exception('Fornecedor não especificado');
    }

    $fornecedor = $_GET['fornecedor'];
    $conn = conectarDB();

    // Informações do fornecedor
    $sql = "SELECT 
                COUNT(DISTINCT s.id) as total_compras,
                AVG(s.valor_total_final) as valor_medio,
                SUM(s.economia) as economia_total,
                COUNT(DISTINCT si.item_id) as total_produtos
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $fornecedorInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cálculo da participação no mercado
    $sql = "SELECT 
                COUNT(DISTINCT s.id) as total_geral
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE s.status = 'concluido'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $totalGeral = $stmt->fetch(PDO::FETCH_ASSOC)['total_geral'];
    
    $participacaoMercado = $totalGeral > 0 ? 
        round(($fornecedorInfo['total_compras'] / $totalGeral) * 100, 2) : 0;

    // Buscar produtos mais comprados
    $query = "SELECT DISTINCT si.descricao
              FROM sawing_itens si
              JOIN sawing s ON s.id = si.sawing_id
              WHERE si.fornecedor = :fornecedor
              AND s.status = 'concluido'
              ORDER BY si.quantidade DESC
              LIMIT 5";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':fornecedor', $fornecedor);
    $stmt->execute();
    $produtos_top = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Produtos do fornecedor
    $sql = "SELECT 
                si.item_id,
                si.descricao,
                SUM(si.quantidade) as quantidade_total,
                AVG(si.valor_unitario_final) as preco_medio,
                MIN(si.valor_unitario_final) as menor_preco,
                MAX(si.valor_unitario_final) as maior_preco,
                SUM(si.quantidade * si.valor_unitario_final) as valor_total,
                MAX(s.data_registro) as ultima_compra
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'
            GROUP BY si.item_id, si.descricao
            ORDER BY quantidade_total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Histórico de compras
    $sql = "SELECT 
                s.data_registro as data,
                si.descricao,
                si.quantidade,
                si.valor_unitario_final as valor_unitario,
                (si.quantidade * si.valor_unitario_final) as valor_total,
                s.economia as economia
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'
            ORDER BY s.data_registro DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Métricas comparativas
    $sql = "SELECT 
                COUNT(DISTINCT si.item_id) as total_produtos_geral,
                COUNT(DISTINCT CASE WHEN si.fornecedor = ? THEN si.item_id END) as produtos_exclusivos
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE s.status = 'concluido'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $metricasComparativas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ranking de preços
    $sql = "SELECT 
                si.fornecedor,
                AVG(si.valor_unitario_final) as preco_medio
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE s.status = 'concluido'
            GROUP BY si.fornecedor
            ORDER BY preco_medio ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rankingPrecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $posicaoPrecos = 1;
    foreach ($rankingPrecos as $rank) {
        if ($rank['fornecedor'] === $fornecedor) break;
        $posicaoPrecos++;
    }

    // Ranking de economia
    $sql = "SELECT 
                si.fornecedor,
                SUM(s.economia) as economia_total
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE s.status = 'concluido'
            GROUP BY si.fornecedor
            ORDER BY economia_total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rankingEconomia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $posicaoEconomia = 1;
    foreach ($rankingEconomia as $rank) {
        if ($rank['fornecedor'] === $fornecedor) break;
        $posicaoEconomia++;
    }

    // Evolução de preços (últimos 6 meses)
    $sql = "SELECT 
                DATE_FORMAT(s.data_registro, '%Y-%m') as mes,
                AVG(si.valor_unitario_final) as preco_medio
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'
            AND s.data_registro >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(s.data_registro, '%Y-%m')
            ORDER BY mes ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $evolucaoPrecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distribuição de produtos
    $sql = "SELECT 
                si.descricao,
                COUNT(*) as quantidade
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'
            GROUP BY si.descricao
            ORDER BY quantidade DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $distribuicaoProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cálculo de tendências
    $sql = "SELECT 
                AVG(CASE 
                    WHEN s.data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    THEN si.valor_unitario_final 
                END) as preco_atual,
                AVG(CASE 
                    WHEN s.data_registro >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) 
                    AND s.data_registro < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    THEN si.valor_unitario_final 
                END) as preco_anterior
            FROM sawing s
            JOIN sawing_itens si ON s.id = si.sawing_id
            WHERE si.fornecedor = ? AND s.status = 'concluido'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fornecedor]);
    $tendencias = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $trendPreco = $tendencias['preco_anterior'] > 0 ? 
        round((($tendencias['preco_atual'] - $tendencias['preco_anterior']) / $tendencias['preco_anterior']) * 100, 2) : 0;

    // Montagem da resposta
    $response = [
        'success' => true,
        'fornecedor' => $fornecedor,
        'metricas' => [
            'total_compras' => $fornecedorInfo['total_compras'],
            'valor_medio' => $fornecedorInfo['valor_medio'],
            'economia_total' => $fornecedorInfo['economia_total'],
            'participacao_mercado' => $participacaoMercado,
            'produtos_top' => $produtos_top,
            'trend_preco' => $trendPreco,
            'trend_economia' => $trendPreco * -1 // Inverte a tendência do preço para economia
        ],
        'produtos' => $produtos,
        'historico' => $historico,
        'comparativo' => [
            'ranking_precos' => $posicaoPrecos . 'º de ' . count($rankingPrecos),
            'ranking_economia' => $posicaoEconomia . 'º de ' . count($rankingEconomia),
            'produtos_exclusivos' => $metricasComparativas['produtos_exclusivos'],
            'participacao_mercado' => $participacaoMercado,
            'evolucao_precos' => [
                'labels' => array_column($evolucaoPrecos, 'mes'),
                'valores' => array_column($evolucaoPrecos, 'preco_medio')
            ],
            'distribuicao_produtos' => [
                'labels' => array_column($distribuicaoProdutos, 'descricao'),
                'valores' => array_column($distribuicaoProdutos, 'quantidade')
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 