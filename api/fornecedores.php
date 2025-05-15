<?php
session_start();
require_once '../config/database.php';
require_once '../includes/check_permissions.php';

// Verificar se o usuário tem permissão (admin ou gerencia)
if (!in_array($_SESSION['usuario']['tipo'], ['admin', 'gerencia'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = conectarDB();
    
    // Obter parâmetros
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
    $offset = ($pagina - 1) * $limite;
    
    // Construir query base
    $query = "SELECT 
                si.fornecedor,
                COUNT(DISTINCT s.id) as total_compras,
                COUNT(DISTINCT si.item_id) as produtos_unicos,
                AVG(s.valor_total_final) as valor_medio,
                SUM(s.economia) as economia_total,
                MAX(s.data_registro) as ultima_compra
              FROM sawing s
              JOIN sawing_itens si ON s.id = si.sawing_id
              WHERE s.status = 'concluido'";
    
    // Adicionar filtros se fornecidos
    if (!empty($_GET['fornecedor'])) {
        $query .= " AND si.fornecedor LIKE :fornecedor";
    }
    if (!empty($_GET['data_inicio'])) {
        $query .= " AND s.data_registro >= :data_inicio";
    }
    if (!empty($_GET['data_fim'])) {
        $query .= " AND s.data_registro <= :data_fim";
    }
    
    // Agrupar e ordenar
    $query .= " GROUP BY si.fornecedor
                ORDER BY si.fornecedor ASC
                LIMIT :offset, :limite";
    
    // Preparar e executar a query
    $stmt = $conn->prepare($query);
    
    // Bind dos parâmetros
    if (!empty($_GET['fornecedor'])) {
        $stmt->bindValue(':fornecedor', '%' . $_GET['fornecedor'] . '%');
    }
    if (!empty($_GET['data_inicio'])) {
        $stmt->bindValue(':data_inicio', $_GET['data_inicio']);
    }
    if (!empty($_GET['data_fim'])) {
        $stmt->bindValue(':data_fim', $_GET['data_fim']);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    
    $stmt->execute();
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter total de registros para paginação
    $stmtTotal = $conn->query("SELECT COUNT(DISTINCT si.fornecedor) as total FROM sawing s JOIN sawing_itens si ON s.id = si.sawing_id WHERE s.status = 'concluido'");
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calcular métricas gerais
    $stmtMetricas = $conn->query("
        SELECT 
            COUNT(DISTINCT si.fornecedor) as total_fornecedores,
            COUNT(DISTINCT s.id) as total_compras,
            COUNT(DISTINCT si.item_id) as total_produtos,
            AVG(s.valor_total_final) as valor_medio
        FROM sawing s
        JOIN sawing_itens si ON s.id = si.sawing_id
        WHERE s.status = 'concluido'
    ");
    $metricas = $stmtMetricas->fetch(PDO::FETCH_ASSOC);
    
    // Retornar resposta
    echo json_encode([
        'success' => true,
        'fornecedores' => $fornecedores,
        'total' => $total,
        'pagina' => $pagina,
        'limite' => $limite,
        'resumo' => $metricas
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dados: ' . $e->getMessage()
    ]);
}
