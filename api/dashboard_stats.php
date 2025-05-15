<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$conn = conectarDB();

try {
    $stats = [
        'pendentes' => 0,
        'aprovadas' => 0,
        'rejeitadas' => 0,
        'renegociacao' => 0,
        'programadas' => 0,
        'emergenciais' => 0
    ];

    // Buscar contagem por status
    $query = "SELECT status, COUNT(*) as total FROM cotacoes GROUP BY status";
    $result = $conn->query($query);
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['status'];
        if ($status == 'aguardando_aprovacao') {
            $stats['pendentes'] = (int)$row['total'];
        } else if ($status == 'aprovado') {
            $stats['aprovadas'] = (int)$row['total'];
        } else if ($status == 'rejeitado') {
            $stats['rejeitadas'] = (int)$row['total'];
        } else if ($status == 'renegociacao') {
            $stats['renegociacao'] = (int)$row['total'];
        }
    }

    // Buscar contagem por tipo
    $query = "SELECT tipo, COUNT(*) as total FROM cotacoes GROUP BY tipo";
    $result = $conn->query($query);
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $tipo = $row['tipo'];
        if ($tipo == 'programada') {
            $stats['programadas'] = (int)$row['total'];
        } else if ($tipo == 'emergencial') {
            $stats['emergenciais'] = (int)$row['total'];
        }
    }

    // Buscar cotações recentes
    $query = "SELECT c.*, u.nome as usuario_nome,
              (SELECT SUM(valor_total) FROM itens_cotacao WHERE cotacao_id = c.id) as valor_total
              FROM cotacoes c 
              JOIN usuarios u ON c.usuario_id = u.id 
              ORDER BY c.data_criacao DESC 
              LIMIT 5";
    
    $recentes = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

    // Buscar estatísticas do Sawing
    $sawing_stats = [
        'economia_total' => 0,
        'economia_percentual' => 0,
        'total_negociado' => 0,
        'total_aprovado' => 0,
        'total_rodadas' => 0
    ];

    // Verificar se a tabela sawing existe
    $table_exists = $conn->query("SHOW TABLES LIKE 'sawing'")->rowCount() > 0;
    
    if ($table_exists) {
        // Calcular economia total
        $query = "SELECT 
                    SUM(valor_total_inicial - valor_total_final) as economia_total,
                    SUM(valor_total_inicial) as total_negociado,
                    SUM(valor_total_final) as total_aprovado,
                    SUM(rodadas) as total_rodadas
                  FROM sawing";
        $result = $conn->query($query);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        
        $sawing_stats['economia_total'] = (float)$row['economia_total'] ?? 0;
        $sawing_stats['total_negociado'] = (float)$row['total_negociado'] ?? 0;
        $sawing_stats['total_aprovado'] = (float)$row['total_aprovado'] ?? 0;
        $sawing_stats['total_rodadas'] = (int)$row['total_rodadas'] ?? 0;
        
        // Calcular economia percentual
        if ($sawing_stats['total_negociado'] > 0) {
            $sawing_stats['economia_percentual'] = ($sawing_stats['economia_total'] / $sawing_stats['total_negociado']) * 100;
        }
    }

    // Buscar alertas/notificações
    $alertas = [];
    
    // Alerta para cotações pendentes há mais de 3 dias
    $query = "SELECT COUNT(*) as total FROM cotacoes 
              WHERE status = 'aguardando_aprovacao' 
              AND data_criacao < DATE_SUB(NOW(), INTERVAL 3 DAY)";
    $result = $conn->query($query);
    $pendentes_antigas = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($pendentes_antigas > 0) {
        $alertas[] = [
            'tipo' => 'warning',
            'mensagem' => "Existem {$pendentes_antigas} cotações aguardando aprovação há mais de 3 dias.",
            'icone' => 'exclamation-triangle'
        ];
    }
    
    // Alerta para economia significativa
    if ($sawing_stats['economia_percentual'] > 15) {
        $alertas[] = [
            'tipo' => 'success',
            'mensagem' => "Economia significativa de " . number_format($sawing_stats['economia_percentual'], 2) . "% nas negociações.",
            'icone' => 'chart-line'
        ];
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recentes' => $recentes,
        'sawing_stats' => $sawing_stats,
        'alertas' => $alertas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
