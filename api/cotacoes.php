<?php
// Configurar tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros diretamente

// Verificar e configurar diretório de uploads
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!is_writable($uploadDir)) {
    chmod($uploadDir, 0777);
    // Verificar novamente após a tentativa de correção
    if (!is_writable($uploadDir)) {
        error_log("ERRO: Diretório de uploads não tem permissões de escrita: " . $uploadDir);
    }
}

// Limpar qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Definir cabeçalho JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Configurar para retornar erros detalhados em formato JSON
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
});

// Aumentar limites de upload se necessário
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300);


session_start();
require_once '../config/database.php';
require_once '../includes/notifications.php';

// Verificação inicial da sessão e do usuário
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$conn = conectarDB();

// Verificar e corrigir o ID do usuário na sessão, se necessário
if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['id'])) {
    // Verificar se o usuário existe no banco de dados
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario']['id']]);
    if (!$stmt->fetch()) {
        // Se o usuário não existir, tentar encontrar um usuário válido
        $stmt = $conn->prepare("SELECT id FROM usuarios LIMIT 1");
        $stmt->execute();
        $usuarioId = $stmt->fetchColumn();
        
        if ($usuarioId) {
            // Atualizar a sessão com um ID válido
            $_SESSION['usuario']['id'] = $usuarioId;
        } else {
            // Se não houver usuários no banco de dados, isso é um problema mais sério
            error_log("ALERTA: Não há usuários no banco de dados!");
        }
    }
}

try {
    // Adicionar no início do arquivo, após os requires
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Se for uma requisição para listar cotações
        if (isset($_GET['list']) && $_GET['list'] === '1') {
            try {
                $query = "SELECT 
                    c.*, 
                    u.nome as usuario_nome,
                    COUNT(CASE 
                        WHEN c.status = 'aprovado' THEN 
                            CASE WHEN i.aprovado = 1 THEN 1 END
                        ELSE 
                            CASE WHEN i.id IS NOT NULL THEN 1 END
                    END) as total_itens
                FROM cotacoes c
                JOIN usuarios u ON c.usuario_id = u.id
                LEFT JOIN itens_cotacao i ON c.id = i.cotacao_id";

                // Se o usuário NÃO for admin, filtrar apenas suas cotações
                if (!$is_admin) {
                    $query .= " WHERE c.usuario_id = :usuario_id";
                }

                $query .= " GROUP BY c.id ORDER BY c.data_criacao DESC";

                $stmt = $conn->prepare($query);
                
                if (!$is_admin) {
                    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                }

                $stmt->execute();
                $cotacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                header('Content-Type: application/json');
                echo json_encode($cotacoes);
                exit;
            } catch (Exception $e) {
                error_log("Erro ao listar cotações: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao listar cotações']);
                exit;
            }
        }
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                try {
                    // Buscar dados da cotação
                    $stmt = $conn->prepare("SELECT c.*, u.nome as usuario_nome FROM cotacoes c JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = ?");
                    $stmt->execute([$_GET['id']]);
                    $cotacao = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$cotacao) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Cotação não encontrada']);
                        exit;
                    }

                    // Verificar se a coluna 'aprovado' existe na tabela itens_cotacao
                    try {
                        $checkColumn = $conn->query("SHOW COLUMNS FROM itens_cotacao LIKE 'aprovado'");
                        if ($checkColumn->rowCount() == 0) {
                            // A coluna não existe, vamos criá-la
                            $conn->exec("ALTER TABLE itens_cotacao ADD COLUMN aprovado TINYINT(1) DEFAULT 0");
                        }
                    } catch (Exception $e) {
                        // Ignorar erros aqui, pois a consulta principal ainda pode funcionar
                    }

                    // Buscar os itens da cotação com cálculo de variação
                    $stmt = $conn->prepare("
                        SELECT ic.*,
                               (SELECT valor_unitario 
                                FROM itens_cotacao ic2 
                                JOIN cotacoes c2 ON ic2.cotacao_id = c2.id 
                                WHERE ic2.produto_id = ic.produto_id 
                                  AND c2.status = 'aprovado' 
                                  AND c2.id != ? 
                                ORDER BY c2.data_criacao DESC 
                                LIMIT 1) as ultimo_preco_aprovado,
                               (SELECT valor_unitario 
                                FROM historico_cotacao hc 
                                WHERE hc.produto_nome = ic.produto_nome 
                                  AND hc.acao = 'aprovacao' 
                                ORDER BY hc.data_acao DESC 
                                LIMIT 1) as ultimo_valor_aprovado
                        FROM itens_cotacao ic 
                        WHERE ic.cotacao_id = ?
                    ");
                    $stmt->execute([$_GET['id'], $_GET['id']]);
                    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calcular a variação percentual para cada item
                    foreach ($itens as &$item) {
                        if (!empty($item['ultimo_preco_aprovado']) && $item['ultimo_preco_aprovado'] > 0) {
                            $valorAtual = floatval($item['valor_unitario']);
                            $valorAnterior = floatval($item['ultimo_preco_aprovado']);
                            $item['variacao'] = round((($valorAtual - $valorAnterior) / $valorAnterior) * 100, 2);
                            $item['valor_anterior'] = $valorAnterior;
                        } else {
                            $item['variacao'] = null;
                            $item['valor_anterior'] = null;
                        }
                    }

                    $cotacao['itens'] = $itens;

                    // Buscar os produtos marcados para renegociação (se houver)
                    $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome FROM cotacoes_renegociacoes WHERE cotacao_id = ?");
                    $stmt->execute([$_GET['id']]);
                    $cotacao['produtos_renegociar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Garantir que não há saída antes do JSON
                    if (ob_get_length()) ob_clean();

                    // Buscar o número máximo de versões para esta cotação
                    $stmt = $conn->prepare("SELECT MAX(versao) as max_versao FROM cotacoes_versoes WHERE cotacao_id = ?");
                    $stmt->execute([$_GET['id']]);
                    $maxVersao = $stmt->fetchColumn();

                    // Se não houver versões, definir como 1 (versão inicial)
                    $cotacao['numero_rodadas'] = $maxVersao ? (int)$maxVersao : 1;

                    // Buscar a data de aprovação/rejeição da tabela historico_cotacao
                    $stmt = $conn->prepare("
                        SELECT data_acao 
                        FROM historico_cotacao 
                        WHERE cotacao_id = ? 
                        AND (acao = 'aprovacao' OR acao = 'rejeicao') 
                        ORDER BY data_acao DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$_GET['id']]);
                    $dataAprovacaoHistorico = $stmt->fetchColumn();
                    
                    // Se não encontrar na tabela historico_cotacao, tentar buscar da coluna data_aprovacao da tabela cotacoes
                    if (!$dataAprovacaoHistorico) {
                        $stmt = $conn->prepare("SELECT data_aprovacao FROM cotacoes WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $dataAprovacaoCotacao = $stmt->fetchColumn();
                        
                        // Usar a data da tabela cotacoes se disponível
                        $dataAprovacao = $dataAprovacaoCotacao ?: null;
                    } else {
                        $dataAprovacao = $dataAprovacaoHistorico;
                    }
                    
                    // Adicionar a data de aprovação/rejeição aos dados da cotação
                    $cotacao['data_aprovacao'] = $dataAprovacao;

                    echo json_encode($cotacao);
                    exit;
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            } else {
                // Se não houver ID, listar todas as cotações ou retornar erro
                // ...
            }
            break;

            case 'POST':
                // Verificar se é um envio com arquivos (FormData) ou JSON
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                
                // Verificar se é uma simulação de PUT
                $isPut = isset($_POST['_method']) && $_POST['_method'] === 'PUT';
                
                if (strpos($contentType, 'multipart/form-data') !== false) {
                    // Processamento de FormData com arquivos
                    if (!isset($_POST['dados'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Dados inválidos - campo "dados" não encontrado']);
                        exit;
                    }
                    
                    $dados = json_decode($_POST['dados'], true);
                    
                    if (!$dados) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Dados JSON inválidos']);
                        exit;
                    }
                    
                    try {
                        $conn->beginTransaction();
                        
                        // Criar diretório de uploads se não existir
                        $uploadDir = '../uploads/cotacoes/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        if ($isPut) {
                            error_log("Iniciando edição da cotação ID: " . $dados['id']);
                            
                            // Buscar os valores originais antes de excluir os itens
                            $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, primeiro_valor, valor_unitario, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            $valoresOriginais = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                                $valoresOriginais[$key] = [
                                    'primeiro_valor' => $row['primeiro_valor'],
                                    'ultimo_preco' => $row['valor_unitario'],
                                    'rodadas' => $row['rodadas'] ?? 0
                                ];
                            }
                            
                            error_log("Itens originais encontrados: " . count($valoresOriginais));
                            
                            // Remover os itens existentes
                            $stmt = $conn->prepare("DELETE FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            error_log("Itens antigos removidos da cotação");
                            
                            // Atualizar o status
                            $novoStatus = $dados['status'] ?? 'pendente';
                            $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
                            $stmt->execute([$novoStatus, $dados['id']]);
                            error_log("Status atualizado para: " . $novoStatus);
                            
                            $cotacao_id = $dados['id'];
                        } else {
                            // Lógica de criação (POST)
                            // Inserir a cotação
                            $stmt = $conn->prepare("INSERT INTO cotacoes (usuario_id, data_criacao, status, prazo_pagamento) VALUES (?, NOW(), 'pendente', ?)");
                            $stmt->execute([$_SESSION['usuario']['id'], $dados['fornecedores'][0]['prazo_pagamento'] ?? null]);
                            $cotacao_id = $conn->lastInsertId();
                            
                            if (!$cotacao_id) {
                                throw new Exception('Falha ao criar a cotação. ID não gerado.');
                            }
                        }
                        
                        // Buscar o maior produto_id existente para esta cotação
                        $stmt = $conn->prepare("SELECT MAX(produto_id) as max_id FROM itens_cotacao WHERE cotacao_id = ?");
                        $stmt->execute([$cotacao_id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $produto_id = ($result['max_id'] ?? 0) + 1;

                        // Inserir novos itens
                        $stmt = $conn->prepare("
                            INSERT INTO itens_cotacao (
                                cotacao_id, 
                                produto_id,
                                produto_nome, 
                                fornecedor_nome, 
                                quantidade, 
                                valor_unitario, 
                                valor_total, 
                                unidade,
                                prazo_entrega,
                                frete,
                                difal,
                                prazo_pagamento,
                                primeiro_valor,
                                ultimo_preco,
                                rodadas
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $itensInseridos = 0;
                        foreach ($dados['fornecedores'] as $fornecedor) {
                            foreach ($fornecedor['produtos'] as $produto) {
                                // Verificar se o produto já existia
                                $key = $produto['codigo'] . '_' . $fornecedor['fornecedor_nome'];
                                $valoresOriginaisProduto = $valoresOriginais[$key] ?? null;
                                
                                // Se o produto já existia, manter o primeiro_valor e incrementar rodadas
                                $primeiroValor = $valoresOriginaisProduto ? $valoresOriginaisProduto['primeiro_valor'] : $produto['valor_unitario'];
                                $ultimoPreco = $valoresOriginaisProduto ? $valoresOriginaisProduto['ultimo_preco'] : null;
                                $rodadas = $valoresOriginaisProduto ? ($valoresOriginaisProduto['rodadas'] + 1) : 0;
                                
                                $stmt->execute([
                                    $cotacao_id,
                                    $produto['codigo'],
                                    $produto['nome'],
                                    $fornecedor['fornecedor_nome'],
                                    $produto['quantidade'],
                                    $produto['valor_unitario'],
                                    $produto['valor_total'],
                                    $produto['unidade'],
                                    $fornecedor['prazo_entrega'] ?? null,
                                    $fornecedor['frete'] ?? 0,
                                    $fornecedor['difal'] ?? 0,
                                    $fornecedor['prazo_pagamento'] ?? null,
                                    $primeiroValor,
                                    $ultimoPreco,
                                    $rodadas
                                ]);
                                $itensInseridos++;
                            }
                        }
                        
                        error_log("Total de itens inseridos: " . $itensInseridos);
                        
                        $conn->commit();
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    // Processamento JSON padrão (código existente)
                    $dados = json_decode(file_get_contents('php://input'), true);
                    try {
                        $conn->beginTransaction();
                        
                        // Verificar se é uma edição (PUT simulado via POST)
                        $isPut = isset($dados['id']) && !empty($dados['id']);
                        
                        if ($isPut) {
                            // Buscar os valores originais antes de excluir os itens
                            $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, primeiro_valor, valor_unitario, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            $valoresOriginais = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                                $valoresOriginais[$key] = [
                                    'primeiro_valor' => $row['primeiro_valor'],
                                    'ultimo_preco' => $row['valor_unitario'],
                                    'rodadas' => $row['rodadas'] ?? 0
                                ];
                            }
                            
                            // Remover os itens existentes
                            $stmt = $conn->prepare("DELETE FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            
                            // Atualizar o status
                            $novoStatus = $dados['status'] ?? 'pendente';
                            $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
                            $stmt->execute([$novoStatus, $dados['id']]);
                            
                            $cotacao_id = $dados['id'];
                        } else {
                            $prazo_pagamento = $dados['fornecedores'][0]['prazo_pagamento'] ?? null;
                            
                            // Inserir a cotação
                            $stmt = $conn->prepare("INSERT INTO cotacoes (usuario_id, data_criacao, status, prazo_pagamento) VALUES (?, NOW(), 'pendente', ?)");
                            $stmt->execute([$_SESSION['usuario']['id'], $prazo_pagamento]);
                            $cotacao_id = $conn->lastInsertId();
                            
                            if (!$cotacao_id) {
                                throw new Exception('Falha ao criar a cotação. ID não gerado.');
                            }
                        }
                        
                        // Buscar o maior produto_id existente para esta cotação
                        $stmt = $conn->prepare("SELECT MAX(produto_id) as max_id FROM itens_cotacao WHERE cotacao_id = ?");
                        $stmt->execute([$cotacao_id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $produto_id = ($result['max_id'] ?? 0) + 1;

                        // Inserir novos itens
                        $stmt = $conn->prepare("
                            INSERT INTO itens_cotacao (
                                cotacao_id, 
                                produto_id,
                                produto_nome, 
                                fornecedor_nome, 
                                quantidade, 
                                valor_unitario, 
                                valor_total, 
                                unidade,
                                prazo_entrega,
                                frete,
                                difal,
                                prazo_pagamento,
                                primeiro_valor,
                                ultimo_preco,
                                rodadas
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $itensInseridos = 0;
                        foreach ($dados['fornecedores'] as $fornecedor) {
                            foreach ($fornecedor['produtos'] as $produto) {
                                // Verificar se o produto já existia
                                $key = $produto['codigo'] . '_' . $fornecedor['fornecedor_nome'];
                                $valoresOriginaisProduto = $valoresOriginais[$key] ?? null;
                                
                                // Se o produto já existia, manter o primeiro_valor e incrementar rodadas
                                $primeiroValor = $valoresOriginaisProduto ? $valoresOriginaisProduto['primeiro_valor'] : $produto['valor_unitario'];
                                $ultimoPreco = $valoresOriginaisProduto ? $valoresOriginaisProduto['ultimo_preco'] : null;
                                $rodadas = $valoresOriginaisProduto ? ($valoresOriginaisProduto['rodadas'] + 1) : 0;
                                
                                $stmt->execute([
                                    $cotacao_id,
                                    $produto['codigo'],
                                    $produto['nome'],
                                    $fornecedor['fornecedor_nome'],
                                    $produto['quantidade'],
                                    $produto['valor_unitario'],
                                    $produto['valor_total'],
                                    $produto['unidade'],
                                    $fornecedor['prazo_entrega'] ?? null,
                                    $fornecedor['frete'] ?? 0,
                                    $fornecedor['difal'] ?? 0,
                                    $fornecedor['prazo_pagamento'] ?? null,
                                    $primeiroValor,
                                    $ultimoPreco,
                                    $rodadas
                                ]);
                                $itensInseridos++;
                            }
                        }
                        
                        error_log("Total de itens inseridos: " . $itensInseridos);
                        
                        $conn->commit();
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                }
                break;
            
            

            case 'PUT':
                    try {
                        $conn->beginTransaction();
                        
                    // Obter dados da requisição
                    $dados = json_decode(file_get_contents('php://input'), true);
                    error_log("Dados recebidos para atualização: " . print_r($dados, true));
                    
                    if (!$dados || !isset($dados['id'])) {
                        throw new Exception('Dados inválidos');
                        }
                        
                            $cotacaoId = $dados['id'];
                            
                    // Primeiro, remover todos os itens existentes
                    $stmt = $conn->prepare("DELETE FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$cotacaoId]);
                    error_log("Itens antigos removidos da cotação ID: " . $cotacaoId);
                    
                    // Atualizar status se fornecido
                    if (isset($dados['status'])) {
    $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
                        $stmt->execute([$dados['status'], $cotacaoId]);
                    }
                    
                    // Inserir apenas os itens atuais
                        if (isset($dados['fornecedores'])) {
                            foreach ($dados['fornecedores'] as $fornecedor) {
                                foreach ($fornecedor['produtos'] as $produto) {
                                $stmt = $conn->prepare("INSERT INTO itens_cotacao (
                                    cotacao_id, produto_id, produto_nome, quantidade, valor_unitario,
                                    fornecedor_nome, prazo_pagamento, prazo_entrega, frete, difal,
                                    primeiro_valor, ultimo_preco, rodadas
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        
                                        $stmt->execute([
                                    $cotacaoId,
                                    $produto['codigo'],
                                            $produto['nome'],
                                            $produto['quantidade'],
                                            $produto['valor_unitario'],
                                    $fornecedor['fornecedor_nome'],
                                    $fornecedor['prazo_pagamento'] ?? null,
                                            $fornecedor['prazo_entrega'] ?? null,
                                            $fornecedor['frete'] ?? 0,
                                            $fornecedor['difal'] ?? 0,
                                    $produto['primeiro_valor'] ?? $produto['valor_unitario'],
                                    $produto['ultimo_preco'] ?? null,
                                    $produto['rodadas'] ?? 1
                                        ]);
                                    }
                                }
                            }
                            
                    // Salvar versão se houver dados de versão
                    if (isset($dados['versao'])) {
                        $stmt = $conn->prepare("INSERT INTO cotacoes_versoes (cotacao_id, versao, dados_json, data_criacao, usuario_id) 
                                              VALUES (?, (SELECT COALESCE(MAX(versao), 0) + 1 FROM cotacoes_versoes WHERE cotacao_id = ?), ?, NOW(), ?)");
                        $stmt->execute([
                            $cotacaoId,
                            $cotacaoId,
                            $dados['versao']['dados_json'],
                            $dados['versao']['usuario_id']
                        ]);
                    }
                    
                    $conn->commit();
                    error_log("Cotação ID: " . $cotacaoId . " atualizada com sucesso");
                    
                    echo json_encode(['success' => true, 'message' => 'Cotação atualizada com sucesso']);
                
                    } catch (Exception $e) {
                        $conn->rollBack();
                    error_log("Erro ao atualizar cotação: " . $e->getMessage());
                        http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar cotação: ' . $e->getMessage()]);
                    }
                exit;
                break;            

        case 'DELETE':
            if (isset($_GET['id'])) {
                try {
                    $conn->beginTransaction();
                    $stmt = $conn->prepare("DELETE FROM itens_cotacao WHERE cotacao_id = ?");
                    $stmt->execute([$_GET['id']]);
                    $stmt = $conn->prepare("DELETE FROM cotacoes WHERE id = ?");
                    $stmt->execute([$_GET['id']]);
                    $conn->commit();
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
            }
            break;
    }

    if (isset($dados['status']) && ($dados['status'] === 'aprovado' || $dados['status'] === 'rejeitado')) {
        $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
        $stmt->execute([$dados['id']]);
        $usuario_id = $stmt->fetchColumn();

        $message = $dados['status'] === 'aprovado'
            ? "Sua cotação #" . $dados['id'] . " foi aprovada"
            : "Sua cotação #" . $dados['id'] . " foi rejeitada. Motivo: " . $dados['motivo_rejeicao'];

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, cotacao_id, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $dados['id'], $message, $dados['status']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Função para salvar histórico de cotação
function salvarHistoricoCotacao($conn, $cotacaoId, $usuarioId, $acao, $motivo = null, $itensAprovados = null, $tipoAprovacao = null) {
    try {
        // Preparar a query base
        $sql = "INSERT INTO historico_cotacao (
            cotacao_id, 
            usuario_id, 
            acao, 
            detalhes, 
            data_acao, 
            fornecedor, 
            produto_nome, 
            qtd, 
            valor_unitario,
            valor_aprovado, 
            total, 
            status
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // Se houver itens aprovados, inserir um registro para cada item
        if ($itensAprovados && is_array($itensAprovados)) {
            foreach ($itensAprovados as $item) {
                $detalhes = json_encode([
                    'motivo' => $motivo,
                    'tipo_aprovacao' => $tipoAprovacao
                ]);
                
                $total = $item['quantidade'] * $item['valor_unitario'];
                
                $stmt->execute([
                    $cotacaoId,
                    $usuarioId,
                    $acao,
                    $detalhes,
                    $item['fornecedor_nome'] ?? null,
                    $item['produto_nome'] ?? null,
                    $item['quantidade'] ?? null,
                    $item['valor_unitario'] ?? null,
                    $item['valor_unitario'] ?? null,
                    $total,
                    $tipoAprovacao
                ]);
            }
        } else {
            // Se não houver itens específicos, inserir um registro geral
            $detalhes = json_encode([
                'motivo' => $motivo,
                'tipo_aprovacao' => $tipoAprovacao
            ]);
            
            $stmt->execute([
                $cotacaoId,
                $usuarioId,
                $acao,
                $detalhes,
                null, // fornecedor
                null, // produto_nome
                null, // qtd
                null, // valor_unitario
                null, // valor_aprovado
                null, // total
                $tipoAprovacao
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao salvar histórico de cotação: " . $e->getMessage());
        return false;
    }
}

// Função auxiliar para verificar se uma coluna existe
function columnExists($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// When a cotação is sent for approval
if ($dados['status'] === 'aguardando_aprovacao') {
    // ... existing code ...

    // Create notification for approvers
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE tipo IN ('admin', 'gerencia', 'administrador')");
    $stmt->execute();
    $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($approvers as $approver) {
        createNotification(
            $conn,
            $approver['id'],
            $dados['id'],
            "Nova cotação #{$dados['id']} aguardando aprovação",
            'nova_cotacao'
        );
    }
}

// When a cotação is approved
if ($dados['status'] === 'aprovado') {
    // ... existing code ...

    // Create notification for the buyer
    $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
    $stmt->execute([$dados['id']]);
    $buyerId = $stmt->fetchColumn();

    if ($buyerId) {
        createNotification(
            $conn,
            $buyerId,
            $dados['id'],
            "Sua cotação #{$dados['id']} foi aprovada",
            'aprovado'
        );
    }
}
