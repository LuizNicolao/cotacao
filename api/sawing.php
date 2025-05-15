<?php
// Limpar qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Configurações iniciais
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros diretamente

// Capturar erros e convertê-los em JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => "Erro PHP: $errstr",
        'arquivo' => $errfile,
        'linha' => $errline
    ]);
    exit;
});

// Capturar exceções não tratadas
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => "Exceção: " . $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
    exit;
});

// Incluir configuração do banco de dados
require_once '../config/database.php';

// Conectar ao banco de dados
try {
    $conn = conectarDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => "Erro de conexão: " . $e->getMessage()
    ]);
    exit;
}

// Verificar se a tabela sawing existe
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'sawing'");
    $stmt->execute();
    $tabelaExiste = $stmt->fetchColumn();
    
    if (!$tabelaExiste) {
        http_response_code(500);
        echo json_encode([
            'erro' => true,
            'mensagem' => "A tabela 'sawing' não existe no banco de dados"
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => "Erro ao verificar tabela: " . $e->getMessage()
    ]);
    exit;
}

// Função para retornar erro em formato JSON
function retornarErro($mensagem, $codigo = 500) {
    http_response_code($codigo);
    echo json_encode(['error' => $mensagem]);
    exit;
}

// Verificar autenticação
session_start();
if (!isset($_SESSION['usuario'])) {
    retornarErro('Não autorizado', 401);
}

// Verificar se a tabela sawing_itens existe
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'sawing_itens'");
    $stmt->execute();
    $tabelaItensExiste = $stmt->fetchColumn();
    
    if (!$tabelaItensExiste) {
        error_log("AVISO: A tabela 'sawing_itens' não existe no banco de dados");
    }
} catch (Exception $e) {
    error_log("Erro na verificação inicial: " . $e->getMessage());
    // Não interromper a execução por causa deste erro
}

// Verificar se é uma solicitação de exportação
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    try {
        exportarDados($conn);
    } catch (Exception $e) {
        retornarErro('Erro ao exportar dados: ' . $e->getMessage());
    }
    exit;
}

// Verificar se é uma solicitação de detalhes
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        obterDetalhes($conn, $id);
    } catch (Exception $e) {
        retornarErro('Erro ao obter detalhes: ' . $e->getMessage());
    }
    exit;
}

// Verificar se é uma solicitação de compradores
if (isset($_GET['acao']) && $_GET['acao'] === 'listar_compradores') {
    try {
        listarCompradores($conn);
    } catch (Exception $e) {
        retornarErro('Erro ao listar compradores: ' . $e->getMessage());
    }
    exit;
}

// Caso contrário, listar registros
try {
    listarRegistros($conn);
} catch (Exception $e) {
    retornarErro('Erro ao listar registros: ' . $e->getMessage());
}

// Função para listar registros
function listarRegistros($conn) {
    try {
        // Parâmetros de paginação
        $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
        $offset = ($pagina - 1) * $limite;
        
        // Construir a consulta SQL base
        $sqlBase = "
                FROM sawing s
                LEFT JOIN usuarios u ON s.usuario_id = u.id
                WHERE 1=1";
        
        // Aplicar filtros, se houver
        $filtros = "";
        $paramsConsulta = [];
        
        if (isset($_GET['comprador']) && !empty($_GET['comprador'])) {
            $filtros .= " AND s.usuario_id = ?";
            $paramsConsulta[] = $_GET['comprador'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filtros .= " AND s.status = ?";
            $paramsConsulta[] = $_GET['status'];
        }
        
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $filtros .= " AND s.tipo = ?";
            $paramsConsulta[] = $_GET['tipo'];
        }
        
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $filtros .= " AND DATE(s.data_registro) >= ?";
            $paramsConsulta[] = $_GET['data_inicio'];
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $filtros .= " AND DATE(s.data_registro) <= ?";
            $paramsConsulta[] = $_GET['data_fim'];
        }
        
        // Consulta para contar total de registros (sem LIMIT e OFFSET)
        $sqlCount = "SELECT COUNT(*) " . $sqlBase . $filtros;
        
        // Consulta principal (com LIMIT e OFFSET)
        // Usar LIMIT e OFFSET diretamente na string SQL em vez de parâmetros
        $sql = "SELECT s.*, u.nome as comprador_nome " . $sqlBase . $filtros . " ORDER BY s.data_registro DESC LIMIT " . $limite . " OFFSET " . $offset;
        
        // Executar consulta para contar total de registros
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->execute($paramsConsulta);
        $totalRegistros = $stmtCount->fetchColumn();
        
        // Executar consulta principal
        $stmt = $conn->prepare($sql);
        $stmt->execute($paramsConsulta);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular resumo
        $resumo = calcularResumo($conn, $paramsConsulta);
        
        // Retornar dados
        echo json_encode([
            'registros' => $registros,
            'total' => $totalRegistros,
            'pagina' => $pagina,
            'limite' => $limite,
            'resumo' => $resumo
        ]);
        
    } catch (Exception $e) {
        retornarErro('Erro ao listar registros: ' . $e->getMessage());
    }
}

// Função para obter detalhes de um registro
function obterDetalhes($conn, $id) {
    try {
        // Obter dados básicos do registro
        $stmt = $conn->prepare("
            SELECT s.*, u.nome as comprador_nome, c.data_aprovacao
            FROM sawing s
            LEFT JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN cotacoes c ON s.cotacao_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registro) {
            http_response_code(404);
            echo json_encode(['error' => 'Registro não encontrado']);
            exit;
        }
        
        // Inicializar arrays vazios para produtos e rodadas
        $registro['produtos'] = [];
        $registro['rodadas_historico'] = [];
        
        // Verificar se a tabela sawing_itens existe e buscar itens
        try {
            $checkTable = $conn->query("SHOW TABLES LIKE 'sawing_itens'");
            if ($checkTable->rowCount() > 0) {
                // Tabela existe, buscar itens
                $stmt = $conn->prepare("
                    SELECT si.*, ic.produto_nome, ic.produto_codigo, ic.produto_unidade, ic.fornecedor_nome
                    FROM sawing_itens si
                    LEFT JOIN itens_cotacao ic ON si.item_id = ic.id
                    WHERE si.sawing_id = ?
                ");
                $stmt->execute([$id]);
                $registro['produtos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Mapear campos para o formato esperado pelo frontend
                foreach ($registro['produtos'] as &$produto) {
                    $produto['nome'] = $produto['produto_nome'] ?? $produto['descricao'] ?? 'Produto sem nome';
                    $produto['quantidade'] = $produto['quantidade'] ?? 1;
                    $produto['fornecedor'] = $produto['fornecedor_nome'] ?? 'N/A';
                }
            }
        } catch (Exception $e) {
            // Ignorar erro e continuar com array vazio
            error_log("Erro ao buscar itens: " . $e->getMessage());
        }
        
        // Se não encontrou itens em sawing_itens, tentar buscar da cotação relacionada
        if (empty($registro['produtos']) && !empty($registro['cotacao_id'])) {
            try {
                // Buscar todos os itens da cotação
                $stmt = $conn->prepare("
                    SELECT 
                        ic.id,
                        ic.produto_id,
                        ic.quantidade,
                        ic.primeiro_valor,
                        ic.valor_unitario,
                        p.nome as produto_nome,
                        f.nome as fornecedor_nome
                    FROM itens_cotacao ic
                    LEFT JOIN produtos p ON ic.produto_id = p.id
                    LEFT JOIN fornecedores f ON ic.fornecedor_id = f.id
                    WHERE ic.cotacao_id = ?
                ");
                $stmt->execute([$id]);
                $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Agrupar itens por produto para encontrar o maior valor inicial
                $produtosAgrupados = [];
                foreach ($itens as $item) {
                    $produtoId = $item['produto_id'];
                    if (!isset($produtosAgrupados[$produtoId])) {
                        $produtosAgrupados[$produtoId] = [
                            'id' => $item['id'],
                            'produto_id' => $produtoId,
                            'produto_nome' => $item['produto_nome'],
                            'quantidade' => $item['quantidade'],
                            'primeiro_valor' => $item['primeiro_valor'],
                            'valor_unitario' => $item['valor_unitario'],
                            'fornecedor' => $item['fornecedor_nome']
                        ];
                    } else {
                        // Atualizar o maior valor inicial se necessário
                        if ($item['primeiro_valor'] > $produtosAgrupados[$produtoId]['primeiro_valor']) {
                            $produtosAgrupados[$produtoId]['primeiro_valor'] = $item['primeiro_valor'];
                        }
                    }
                }

                // Calcular economia para cada produto
                foreach ($produtosAgrupados as &$produto) {
                    // Calcular valor inicial total (quantidade * primeiro_valor)
                    $valorTotalInicial = $produto['quantidade'] * $produto['primeiro_valor'];
                    // Calcular valor final total (quantidade * valor_unitario)
                    $valorTotalFinal = $produto['quantidade'] * $produto['valor_unitario'];
                    // Calcular economia
                    $economia = $valorTotalInicial - $valorTotalFinal;
                    // Calcular economia percentual
                    $economiaPercentual = $valorTotalInicial > 0 ? ($economia / $valorTotalInicial * 100) : 0;

                    // Atualizar os valores no array do produto
                    $produto['valor_total_inicial'] = $valorTotalInicial;
                    $produto['valor_total_final'] = $valorTotalFinal;
                    $produto['valor_unitario_inicial'] = $produto['primeiro_valor'];
                    $produto['valor_unitario_final'] = $produto['valor_unitario'];
                    $produto['economia'] = $economia;
                    $produto['economia_percentual'] = $economiaPercentual;
                }

                $registro['produtos'] = array_values($produtosAgrupados);
            } catch (Exception $e) {
                error_log("Erro ao buscar itens da cotação: " . $e->getMessage());
            }
        }
        
        // Buscar histórico de rodadas (se existir)
        try {
            $checkTable = $conn->query("SHOW TABLES LIKE 'sawing_rodadas'");
            if ($checkTable->rowCount() > 0) {
                $stmt = $conn->prepare("
                    SELECT * FROM sawing_rodadas
                    WHERE sawing_id = ?
                    ORDER BY data ASC
                ");
                $stmt->execute([$id]);
                $registro['rodadas_historico'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Criar um histórico básico com base nos dados disponíveis
                $registro['rodadas_historico'] = [
                    [
                        'data' => $registro['data_registro'],
                        'valor' => $registro['valor_total_inicial'],
                        'economia_acumulada' => 0,
                        'economia_percentual' => 0,
                        'observacao' => 'Valor inicial'
                    ],
                    [
                        'data' => $registro['data_registro'],
                        'valor' => $registro['valor_total_final'],
                        'economia_acumulada' => $registro['economia'],
                        'economia_percentual' => $registro['economia_percentual'],
                        'observacao' => 'Valor final após negociação'
                    ]
                ];
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar rodadas: " . $e->getMessage());
        }
        
        // Retornar resultado como JSON
        echo json_encode($registro);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro ao obter detalhes: ' . $e->getMessage()
        ]);
    }
}

// Função para calcular resumo
function calcularResumo($conn, $params) {
    try {
        // Consulta para calcular resumo geral
        $sql = "
            SELECT 
                COALESCE(SUM(economia), 0) as economia_total,
                COALESCE(SUM(valor_total_inicial), 0) as valor_inicial_total,
                COALESCE(SUM(valor_total_final), 0) as valor_final_total,
                COUNT(*) as total_registros,
                COALESCE(SUM(rodadas), 0) as total_rodadas
            FROM sawing s
            WHERE 1=1
        ";
        
        // Aplicar os mesmos filtros da consulta principal
        if (isset($_GET['comprador']) && !empty($_GET['comprador'])) {
            $sql .= " AND s.usuario_id = ?";
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $sql .= " AND s.status = ?";
        }
        
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $sql .= " AND s.tipo = ?";
        }
        
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $sql .= " AND DATE(s.data_registro) >= ?";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $sql .= " AND DATE(s.data_registro) <= ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Consulta para calcular métricas por comprador
        $sqlCompradores = "
            SELECT 
                u.nome as comprador_nome,
                COUNT(*) as total_registros,
                COALESCE(SUM(s.economia), 0) as economia_total,
                COALESCE(SUM(s.valor_total_inicial), 0) as valor_inicial_total,
                COALESCE(SUM(s.valor_total_final), 0) as valor_final_total,
                COALESCE(SUM(s.rodadas), 0) as total_rodadas
            FROM sawing s
            LEFT JOIN usuarios u ON s.usuario_id = u.id
            WHERE 1=1
        ";
        
        // Aplicar os mesmos filtros
        if (isset($_GET['comprador']) && !empty($_GET['comprador'])) {
            $sqlCompradores .= " AND s.usuario_id = ?";
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $sqlCompradores .= " AND s.status = ?";
        }
        
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $sqlCompradores .= " AND s.tipo = ?";
        }
        
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $sqlCompradores .= " AND DATE(s.data_registro) >= ?";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $sqlCompradores .= " AND DATE(s.data_registro) <= ?";
        }
        
        $sqlCompradores .= " GROUP BY s.usuario_id, u.nome";
        
        $stmtCompradores = $conn->prepare($sqlCompradores);
        $stmtCompradores->execute($params);
        $compradores = $stmtCompradores->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular economia percentual média
        $economiaPercentual = $dados['valor_inicial_total'] > 0 ? 
            ($dados['economia_total'] / $dados['valor_inicial_total'] * 100) : 0;
        
        return [
            'economia_total' => $dados['economia_total'],
            'economia_percentual' => $economiaPercentual,
            'total_negociado' => $dados['valor_inicial_total'],
            'total_aprovado' => $dados['valor_final_total'],
            'total_rodadas' => $dados['total_rodadas'],
            'compradores' => $compradores
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular resumo: " . $e->getMessage());
        return [
            'economia_total' => 0,
            'economia_percentual' => 0,
            'total_negociado' => 0,
            'total_aprovado' => 0,
            'total_rodadas' => 0,
            'compradores' => []
        ];
    }
}

// Função para exportar
function exportarDados($conn) {
    $formato = $_GET['formato'] ?? 'excel';
    $basicas = isset($_GET['basicas']) ? explode(',', $_GET['basicas']) : [];
    $detalhes = isset($_GET['detalhes']) ? explode(',', $_GET['detalhes']) : [];
    $metricas = isset($_GET['metricas']) ? explode(',', $_GET['metricas']) : [];
    
    // Construir query base
    $sql = "SELECT s.*, u.nome as comprador_nome 
            FROM sawing s
            LEFT JOIN usuarios u ON s.id_comprador = u.id 
            WHERE 1=1";
    
    // Aplicar filtros
    if (isset($_GET['comprador']) && !empty($_GET['comprador'])) {
        $comprador = $conn->real_escape_string($_GET['comprador']);
        $sql .= " AND s.id_comprador = '$comprador'";
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = $conn->real_escape_string($_GET['status']);
        $sql .= " AND s.status = '$status'";
    }
    
    if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
        $data_inicio = $conn->real_escape_string($_GET['data_inicio']);
        $sql .= " AND s.data_registro >= '$data_inicio'";
    }
    
    if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
        $data_fim = $conn->real_escape_string($_GET['data_fim']);
        $sql .= " AND s.data_registro <= '$data_fim'";
    }
    
    $sql .= " ORDER BY s.data_registro DESC";
    
    $result = $conn->query($sql);
    
    if ($formato === 'excel') {
        // Configurar headers para Excel
    header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="sawing_export_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
    
        // Início do arquivo Excel
    echo '<table border="1">';
    
        // Cabeçalho
    echo '<tr>';
        if (in_array('id', $basicas)) echo '<th>ID</th>';
        if (in_array('cotacao', $basicas)) echo '<th>Cotação</th>';
        if (in_array('comprador', $basicas)) echo '<th>Comprador</th>';
        if (in_array('data', $basicas)) echo '<th>Data</th>';
        if (in_array('status', $basicas)) echo '<th>Status</th>';
        if (in_array('valor_total', $basicas)) echo '<th>Valor Total</th>';
        if (in_array('economia', $basicas)) echo '<th>Economia</th>';
        if (in_array('percentual', $basicas)) echo '<th>Percentual</th>';
    echo '</tr>';
    
    // Dados
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            if (in_array('id', $basicas)) echo '<td>' . $row['id'] . '</td>';
            if (in_array('cotacao', $basicas)) echo '<td>' . $row['cotacao'] . '</td>';
            if (in_array('comprador', $basicas)) echo '<td>' . $row['comprador_nome'] . '</td>';
            if (in_array('data', $basicas)) echo '<td>' . date('d/m/Y H:i', strtotime($row['data_registro'])) . '</td>';
            if (in_array('status', $basicas)) echo '<td>' . traduzirStatus($row['status']) . '</td>';
            if (in_array('valor_total', $basicas)) echo '<td>R$ ' . number_format($row['valor_total_final'], 2, ',', '.') . '</td>';
            if (in_array('economia', $basicas)) echo '<td>R$ ' . number_format($row['economia'], 2, ',', '.') . '</td>';
            if (in_array('percentual', $basicas)) echo '<td>' . number_format($row['percentual'], 2, ',', '.') . '%</td>';
            echo '</tr>';
            
            // Detalhes adicionais
            if (!empty($detalhes)) {
                $sql_itens = "SELECT si.*, p.descricao as produto_desc, f.nome as fornecedor_nome 
                             FROM sawing_itens si 
                             LEFT JOIN produtos p ON si.id_produto = p.id 
                             LEFT JOIN fornecedores f ON si.id_fornecedor = f.id 
                             WHERE si.id_sawing = " . $row['id'];
                $result_itens = $conn->query($sql_itens);
                
                if ($result_itens->num_rows > 0) {
                    echo '<tr><td colspan="' . count($basicas) . '">';
                    echo '<table border="1" style="margin-left: 20px;">';
                    echo '<tr>';
                    if (in_array('produtos', $detalhes)) echo '<th>Produto</th>';
                    if (in_array('fornecedores', $detalhes)) echo '<th>Fornecedor</th>';
                    if (in_array('quantidade', $detalhes)) echo '<th>Quantidade</th>';
                    if (in_array('valor_unitario', $detalhes)) echo '<th>Valor Unitário</th>';
                    if (in_array('valor_total', $detalhes)) echo '<th>Valor Total</th>';
                    echo '</tr>';
                    
                    while ($item = $result_itens->fetch_assoc()) {
                        echo '<tr>';
                        if (in_array('produtos', $detalhes)) echo '<td>' . $item['produto_desc'] . '</td>';
                        if (in_array('fornecedores', $detalhes)) echo '<td>' . $item['fornecedor_nome'] . '</td>';
                        if (in_array('quantidade', $detalhes)) echo '<td>' . $item['quantidade'] . '</td>';
                        if (in_array('valor_unitario', $detalhes)) echo '<td>R$ ' . number_format($item['valor_unitario_final'], 2, ',', '.') . '</td>';
                        if (in_array('valor_total', $detalhes)) echo '<td>R$ ' . number_format($item['valor_total_final'], 2, ',', '.') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</td></tr>';
                }
            }
        }
        
        // Métricas
        if (!empty($metricas)) {
            echo '<tr><td colspan="' . count($basicas) . '"><strong>Métricas</strong></td></tr>';
            
            if (in_array('comprador', $metricas)) {
                $sql_metricas = "SELECT 
                    u.nome as comprador,
                    COUNT(DISTINCT s.id) as total_cotacoes,
                    SUM(s.economia) as total_economia,
                    AVG(s.percentual) as media_percentual
                FROM sawing s
                LEFT JOIN usuarios u ON s.id_comprador = u.id
                GROUP BY s.id_comprador, u.nome";
                
                $result_metricas = $conn->query($sql_metricas);
                
                echo '<tr><td colspan="' . count($basicas) . '">';
                echo '<table border="1" style="margin-left: 20px;">';
                echo '<tr>';
                echo '<th>Comprador</th>';
                echo '<th>Total de Cotações</th>';
                echo '<th>Total Economia</th>';
                echo '<th>Média Percentual</th>';
                echo '</tr>';
                
                while ($metrica = $result_metricas->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $metrica['comprador'] . '</td>';
                    echo '<td>' . $metrica['total_cotacoes'] . '</td>';
                    echo '<td>R$ ' . number_format($metrica['total_economia'], 2, ',', '.') . '</td>';
                    echo '<td>' . number_format($metrica['media_percentual'], 2, ',', '.') . '%</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</td></tr>';
            }
            
            if (in_array('geral', $metricas)) {
                $sql_geral = "SELECT 
                    COUNT(DISTINCT id) as total_cotacoes,
                    SUM(economia) as total_economia,
                    AVG(percentual) as media_percentual,
                    COUNT(DISTINCT CASE WHEN status = 'aprovado' THEN id END) as total_aprovados,
                    COUNT(DISTINCT CASE WHEN status = 'rejeitado' THEN id END) as total_rejeitados
                FROM sawing";
                
                $result_geral = $conn->query($sql_geral);
                $metricas_gerais = $result_geral->fetch_assoc();
                
                echo '<tr><td colspan="' . count($basicas) . '">';
                echo '<table border="1" style="margin-left: 20px;">';
                echo '<tr>';
                echo '<th>Total de Cotações</th>';
                echo '<th>Total Economia</th>';
                echo '<th>Média Percentual</th>';
                echo '<th>Total Aprovados</th>';
                echo '<th>Total Rejeitados</th>';
                echo '</tr>';
                
        echo '<tr>';
                echo '<td>' . $metricas_gerais['total_cotacoes'] . '</td>';
                echo '<td>R$ ' . number_format($metricas_gerais['total_economia'], 2, ',', '.') . '</td>';
                echo '<td>' . number_format($metricas_gerais['media_percentual'], 2, ',', '.') . '%</td>';
                echo '<td>' . $metricas_gerais['total_aprovados'] . '</td>';
                echo '<td>' . $metricas_gerais['total_rejeitados'] . '</td>';
                echo '</tr>';
                echo '</table>';
                echo '</td></tr>';
            }
    }
    
    echo '</table>';
    } else {
        // Implementar exportação PDF aqui
        // Por enquanto, retornar erro
        http_response_code(400);
        echo json_encode(['erro' => 'Formato de exportação não suportado']);
    }
}

function traduzirStatus($status) {
    $status_map = [
        'pendente' => 'Pendente',
        'aprovado' => 'Aprovado',
        'rejeitado' => 'Rejeitado'
    ];
    return $status_map[$status] ?? $status;
}

// Função para listar compradores
function listarCompradores($conn) {
    try {
        $sql = "SELECT DISTINCT u.id, u.nome 
                FROM usuarios u 
                INNER JOIN sawing s ON u.id = s.usuario_id 
                ORDER BY u.nome";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $compradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'compradores' => $compradores
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensagem' => "Erro ao listar compradores: " . $e->getMessage()
        ]);
    }
}
