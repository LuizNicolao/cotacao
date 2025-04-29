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
                            // Lógica de atualização (PUT)
                            // Buscar os valores originais antes de excluir os itens
                            $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, primeiro_valor, valor_unitario, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            $valoresOriginais = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                                $valoresOriginais[$key] = [
                                    'primeiro_valor' => $row['primeiro_valor'],
                                    'ultimo_preco' => $row['valor_unitario'], // valor atual se torna último preço
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
                            // Lógica de criação (POST)
                            // Inserir a cotação
                            $stmt = $conn->prepare("INSERT INTO cotacoes (usuario_id, data_criacao, status, prazo_pagamento) VALUES (?, NOW(), 'pendente', ?)");
                            $stmt->execute([$_SESSION['usuario']['id'], $dados['fornecedores'][0]['prazo_pagamento'] ?? null]);
                            $cotacao_id = $conn->lastInsertId();
                            
                            if (!$cotacao_id) {
                                throw new Exception('Falha ao criar a cotação. ID não gerado.');
                            }
                        }
                        
                        foreach ($dados['fornecedores'] as $index => $fornecedor) {
                            // Processar arquivo de cotação, se existir
                            $arquivo_cotacao = null;
                            $arquivo_key = "arquivo_cotacao_{$index}";
                            
                            if (isset($_FILES[$arquivo_key]) && $_FILES[$arquivo_key]['error'] === UPLOAD_ERR_OK) {
                                $tempFile = $_FILES[$arquivo_key]['tmp_name'];
                                $originalName = $_FILES[$arquivo_key]['name'];
                                $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
                                $newFileName = 'cotacao_' . $cotacao_id . '_fornecedor_' . $index . '_' . time() . '.' . $fileExt;
                                $targetFile = $uploadDir . $newFileName;
                                
                                if (move_uploaded_file($tempFile, $targetFile)) {
                                    $arquivo_cotacao = $newFileName;
                                } else {
                                    throw new Exception('Erro ao salvar o arquivo de cotação');
                                }
                            }
                            
                            foreach ($fornecedor['produtos'] as $produto) {
                                // Buscar o último produto_id para gerar o próximo ID
                                $stmt = $conn->prepare("SELECT MAX(produto_id) FROM itens_cotacao");
                                $stmt->execute();
                                $ultimoProdutoId = $stmt->fetchColumn();
                                
                                // Gerar o próximo produto_id
                                $produto_id = $ultimoProdutoId ? $ultimoProdutoId + 1 : 1;
                                
                                // Determinar valores para campos de histórico
                                $valorUnitario = $produto['valor_unitario'];
                                $key = $produto_id . '_' . $fornecedor['fornecedor_nome'];
                                
                                // Determinar o primeiro_valor (preservar se existir)
                                $primeiroValor = $isPut && isset($valoresOriginais[$key]) 
                                    ? $valoresOriginais[$key]['primeiro_valor']  // Preservar o valor original
                                    : $valorUnitario;                            // Novo valor para criação
                                
                                // Determinar o último preço
                                $ultimoPreco = $isPut && isset($valoresOriginais[$key]) 
                                    ? $valoresOriginais[$key]['ultimo_preco']    // Último preço registrado
                                    : null;                                      // NULL para novos itens
                                
                                // Determinar número de rodadas
                                $rodadas = 0;
                                if ($isPut && isset($valoresOriginais[$key])) {
                                    $rodadas = $valoresOriginais[$key]['rodadas'];
                                    // Incrementar rodadas se o valor mudou
                                    if ($valorUnitario != $valoresOriginais[$key]['ultimo_preco']) {
                                        $rodadas++;
                                    }
                                }
                                
                                // Inserir o item de cotação com o produto_id gerado manualmente
                                $stmt = $conn->prepare("
                                    INSERT INTO itens_cotacao (
                                        cotacao_id,
                                        fornecedor_nome,
                                        produto_id,
                                        produto_nome,
                                        produto_codigo,
                                        produto_unidade,
                                        quantidade,
                                        valor_unitario,
                                        primeiro_valor,
                                        ultimo_preco,
                                        rodadas,
                                        prazo_pagamento,
                                        prazo_entrega,
                                        frete,
                                        difal,
                                        arquivo_cotacao,
                                        edicoes_valor
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                                ");
                                
                                $stmt->execute([
                                    $cotacao_id,
                                    $fornecedor['fornecedor_nome'],
                                    $produto_id,
                                    $produto['nome'],
                                    $produto['codigo'],
                                    $produto['unidade'],
                                    $produto['quantidade'],
                                    $valorUnitario,
                                    $primeiroValor,     // Preservado ou novo
                                    $ultimoPreco,       // Último preço ou NULL
                                    $rodadas,           // Rodadas incrementadas se necessário
                                    $fornecedor['prazo_pagamento'] ?? '',
                                    $fornecedor['prazo_entrega'] ?? '',
                                    $fornecedor['frete'] ?? 0,
                                    $fornecedor['difal'] ?? 0,
                                    $arquivo_cotacao
                                ]);
                            }
                        }
                        
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
                        
                        foreach ($dados['fornecedores'] as $fornecedor) {
                            foreach ($fornecedor['produtos'] as $produto) {
                                // Buscar o último produto_id para gerar o próximo ID
                                $stmt = $conn->prepare("SELECT MAX(produto_id) FROM itens_cotacao");
                                $stmt->execute();
                                $ultimoProdutoId = $stmt->fetchColumn();
                                
                                // Gerar o próximo produto_id
                                $produto_id = $ultimoProdutoId ? $ultimoProdutoId + 1 : 1;
                                
                                // Determinar valores para campos de histórico
                                $valorUnitario = $produto['valor_unitario'];
                                $key = $produto_id . '_' . $fornecedor['fornecedor_nome'];
                                
                                // Determinar o primeiro_valor (preservar se existir)
                                $primeiroValor = $isPut && isset($valoresOriginais[$key]) 
                                    ? $valoresOriginais[$key]['primeiro_valor']  // Preservar o valor original
                                    : $valorUnitario;                            // Novo valor para criação
                                
                                // Determinar o último preço
                                $ultimoPreco = $isPut && isset($valoresOriginais[$key]) 
                                    ? $valoresOriginais[$key]['ultimo_preco']    // Último preço registrado
                                    : null;                                      // NULL para novos itens
                                
                                // Determinar número de rodadas
                                $rodadas = 0;
                                if ($isPut && isset($valoresOriginais[$key])) {
                                    $rodadas = $valoresOriginais[$key]['rodadas'];
                                    // Incrementar rodadas se o valor mudou
                                    if ($valorUnitario != $valoresOriginais[$key]['ultimo_preco']) {
                                        $rodadas++;
                                    }
                                }
                                
                                // Inserir o item de cotação
                                $stmt = $conn->prepare("
                                    INSERT INTO itens_cotacao (
                                        cotacao_id,
                                        fornecedor_nome,
                                        produto_id,
                                        produto_nome,
                                        produto_codigo,
                                        produto_unidade,
                                        quantidade,
                                        valor_unitario,
                                        primeiro_valor,
                                        ultimo_preco,
                                        rodadas,
                                        prazo_pagamento,
                                        prazo_entrega,
                                        frete,
                                        difal,
                                        data_cotacao
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                                ");
                                
                                $stmt->execute([
                                    $cotacao_id,
                                    $fornecedor['fornecedor_nome'],
                                    $produto_id,
                                    $produto['nome'],
                                    $produto['codigo'],
                                    $produto['unidade'],
                                    $produto['quantidade'],
                                    $valorUnitario,
                                    $primeiroValor,     // Preservado ou novo
                                    $ultimoPreco,       // Último preço ou NULL
                                    $rodadas,           // Rodadas incrementadas se necessário
                                    $fornecedor['prazo_pagamento'] ?? '',
                                    $fornecedor['prazo_entrega'] ?? '',
                                    $fornecedor['frete'] ?? 0,
                                    $fornecedor['difal'] ?? 0
                                ]);
                            }
                        }
                        
                        $conn->commit();
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                }
                break;
            
            

            case 'PUT':
                // Verificar se é um envio com arquivos (FormData) ou JSON
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                
                if (strpos($contentType, 'multipart/form-data') !== false) {
                    // Processamento de FormData com arquivos para PUT
                    if (!isset($_POST['dados'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Dados inválidos - campo "dados" não encontrado']);
                        exit;
                    }
                    
                    $dados = json_decode($_POST['dados'], true);
                    
                    if (!$dados || !isset($dados['id'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Dados JSON inválidos ou ID não fornecido']);
                        exit;
                    }
                    
                    try {
                        $conn->beginTransaction();
                        
                        // Criar diretório de uploads se não existir
                        $uploadDir = '../uploads/cotacoes/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // Buscar os valores atuais antes de qualquer modificação
                        $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, valor_unitario, primeiro_valor, ultimo_preco, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                        $stmt->execute([$dados['id']]);
                        $valoresAnteriores = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                            $valoresAnteriores[$key] = [
                                'primeiro_valor' => $row['primeiro_valor'] ?? $row['valor_unitario'],
                                'ultimo_preco' => $row['valor_unitario'], // atual vira ultimo
                                'rodadas' => $row['rodadas'] ? (int)$row['rodadas'] : 0
                            ];
                        }
                        
                        // Atualizar o status
                        $novoStatus = $dados['status'] ?? 'pendente';
                        $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
                        $stmt->execute([$novoStatus, $dados['id']]);
                        
                        foreach ($dados['fornecedores'] as $index => $fornecedor) {
                            // Processar arquivo de cotação, se existir
                            $arquivo_cotacao = null;
                            $arquivo_key = "arquivo_cotacao_{$index}";
                            
                            if (isset($_FILES[$arquivo_key]) && $_FILES[$arquivo_key]['error'] === UPLOAD_ERR_OK) {
                                $tempFile = $_FILES[$arquivo_key]['tmp_name'];
                                $originalName = $_FILES[$arquivo_key]['name'];
                                $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
                                $newFileName = 'cotacao_' . $dados['id'] . '_fornecedor_' . $index . '_' . time() . '.' . $fileExt;
                                $targetFile = $uploadDir . $newFileName;
                                
                                if (move_uploaded_file($tempFile, $targetFile)) {
                                    $arquivo_cotacao = $newFileName;
                                } else {
                                    throw new Exception('Erro ao salvar o arquivo de cotação');
                                }
                            }
                            
                            foreach ($fornecedor['produtos'] as $produto) {
                                $key = $produto['codigo'] . '_' . $fornecedor['fornecedor_nome'];
                                
                                // Verificar se o item já existe
                                $stmt = $conn->prepare("
                                    SELECT COUNT(*) FROM itens_cotacao 
                                    WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?
                                ");
                                $stmt->execute([$dados['id'], $produto['codigo'], $fornecedor['fornecedor_nome']]);
                                $itemExiste = $stmt->fetchColumn() > 0;
                                
                                if ($itemExiste) {
                                    // Primeiro, obtenha o valor unitário atual
                                    $stmtGetOldValue = $conn->prepare("SELECT valor_unitario FROM itens_cotacao WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?");
                                    $stmtGetOldValue->execute([$dados['id'], $produto['codigo'], $fornecedor['fornecedor_nome']]);
                                    $oldValueResult = $stmtGetOldValue->fetch(PDO::FETCH_ASSOC);
                                    $valorUnitarioAntigo = $oldValueResult ? floatval($oldValueResult['valor_unitario']) : null;
                                    
                                    // Novo valor unitário
                                    $valorUnitarioNovo = floatval($produto['valor_unitario']);

                                    // Atualização dos dados
                                    $stmt = $conn->prepare("
                                        UPDATE itens_cotacao SET
                                            produto_nome = ?,
                                            produto_codigo = ?,
                                            produto_unidade = ?,
                                            quantidade = ?,
                                            valor_unitario = ?,
                                            ultimo_preco = ?,
                                            prazo_pagamento = ?,
                                            prazo_entrega = ?,
                                            frete = ?,
                                            difal = ?,
                                            arquivo_cotacao = COALESCE(?, arquivo_cotacao)
                                        WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?
                                    ");
                                    
                                    $stmt->execute([
                                        $produto['nome'],
                                        $produto['codigo'],
                                        $produto['unidade'],
                                        $produto['quantidade'],
                                        $valorUnitarioNovo,
                                        $valorUnitarioNovo,
                                        $fornecedor['prazo_pagamento'],
                                        $fornecedor['prazo_entrega'],
                                        $fornecedor['frete'],
                                        $fornecedor['difal'],
                                        $arquivo_cotacao,
                                        $dados['id'],
                                        $produto['id'],
                                        $fornecedor['fornecedor_nome']
                                    ]);
                                } else {
                                    // Inserir novo item
                                    $stmt = $conn->prepare("
                                        INSERT INTO itens_cotacao (
                                            cotacao_id,
                                            fornecedor_nome,
                                            produto_id,
                                            produto_nome,
                                            produto_codigo,
                                            produto_unidade,
                                            quantidade,
                                            valor_unitario,
                                            primeiro_valor,
                                            ultimo_preco,
                                            rodadas,
                                            prazo_pagamento,
                                            prazo_entrega,
                                            frete,
                                            difal,
                                            arquivo_cotacao
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([
                                        $dados['id'],
                                        $fornecedor['fornecedor_nome'],
                                        $produto['id'],
                                        $produto['nome'],
                                        $produto['codigo'] ?? '',
                                        $produto['unidade'] ?? 'un',
                                        $produto['quantidade'],
                                        $produto['valor_unitario'],
                                        $produto['valor_unitario'], // primeiro_valor igual ao valor inicial
                                        null,
                                        0, // rodadas começa em 0
                                        $fornecedor['prazo_pagamento'] ?? '',
                                        $fornecedor['prazo_entrega'] ?? '',
                                        $fornecedor['frete'] ?? 0,
                                        $fornecedor['difal'] ?? 0,
                                        $arquivo_cotacao
                                    ]);
                                }
                            }
                        }
                        
                        // Remover produtos que não estão mais no payload
                        $produtosAtuais = [];
                        foreach ($dados['fornecedores'] as $fornecedor) {
                            foreach ($fornecedor['produtos'] as $produto) {
                                $produtosAtuais[] = [
                                    'produto_id' => $produto['id'],
                                    'fornecedor_nome' => $fornecedor['fornecedor_nome']
                                ];
                            }
                        }
                        
                        $condicoes = [];
                        $params = [$dados['id']];
                        foreach ($produtosAtuais as $produto) {
                            $condicoes[] = "(produto_id = ? AND fornecedor_nome = ?)";
                            $params[] = $produto['produto_id'];
                            $params[] = $produto['fornecedor_nome'];
                        }
                        
                        if (count($condicoes) > 0) {
                            $sql = "DELETE FROM itens_cotacao WHERE cotacao_id = ? AND NOT (" . implode(' OR ', $condicoes) . ")";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute($params);
                        }
                        
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Cotação atualizada com sucesso']);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro ao atualizar cotação',
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    // Processamento JSON padrão
                    $dados = json_decode(file_get_contents('php://input'), true);
                    
                    if (!$dados) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                        exit;
                    }
                
                    try {
                        $conn->beginTransaction();
                
                        // Verificação obrigatória do ID
                        if (!isset($dados['id'])) {
                            throw new Exception('ID da cotação não fornecido');
                        }
                        
                        // Lógica de renegociação
                        if ($dados['status'] === 'renegociacao' && isset($dados['produtos_renegociar'])) {
                            $cotacaoId = $dados['id'];
                            $produtosRenegociar = $dados['produtos_renegociar'];
                            $motivoRenegociacao = $dados['motivo_renegociacao']; // Recebendo o motivo da renegociação
                            
                            // Buscar os valores atuais para preservar ultimo_preco, primeiro_valor e rodadas
                            $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, valor_unitario, primeiro_valor, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$cotacaoId]);
                            $valoresAtuais = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                                $valoresAtuais[$key] = [
                                    'valor_unitario' => $row['valor_unitario'],
                                    'primeiro_valor' => $row['primeiro_valor'] ?? $row['valor_unitario'], // Preservar primeiro_valor
                                    'rodadas' => $row['rodadas'] ? (int)$row['rodadas'] : 0
                                ];
                            }
                            
                            // Apaga produtos renegociados anteriores
                            $stmt = $conn->prepare("DELETE FROM cotacoes_renegociacoes WHERE cotacao_id = ?");
                            $stmt->execute([$cotacaoId]);
                        
                            // Insere novos produtos renegociados
                            $stmtInsert = $conn->prepare("INSERT INTO cotacoes_renegociacoes (cotacao_id, produto_id, fornecedor_nome) VALUES (?, ?, ?)");
                        
                            foreach ($produtosRenegociar as $item) {
                                $stmtInsert->execute([$cotacaoId, $item['produto_id'], $item['fornecedor_nome']]);
                            }
                        
                            // Atualiza o status da cotação e o motivo da renegociação
                            $stmt = $conn->prepare("UPDATE cotacoes SET status = ?, motivo_renegociacao = ? WHERE id = ?");
                            $stmt->execute(['renegociacao', $motivoRenegociacao, $cotacaoId]);
                            
                            // Atualizar os valores como ultimo_preco e incrementar rodadas
                            foreach ($produtosRenegociar as $item) {
                                $key = $item['produto_id'] . '_' . $item['fornecedor_nome'];
                                if (isset($valoresAtuais[$key])) {
                                    $ultimoPreco = $valoresAtuais[$key]['valor_unitario'];
                                    $novaRodada = $valoresAtuais[$key]['rodadas'] + 1;
                                    
                                    // Atualiza ultimo_preco e rodadas (preserva primeiro_valor automaticamente)
                                    $stmt = $conn->prepare("UPDATE itens_cotacao SET ultimo_preco = ?, rodadas = ? WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?");
                                    $stmt->execute([
                                        $ultimoPreco,
                                        $novaRodada,
                                        $cotacaoId,
                                        $item['produto_id'],
                                        $item['fornecedor_nome']
                                    ]);
                                }
                            }
                        
                            $conn->commit();
                            echo json_encode(['success' => true, 'message' => 'Cotação marcada para renegociação']);
                            exit;
                        }
                        
                        // ATUALIZAÇÃO SIMPLES DE STATUS
                        if (isset($dados['status']) && !isset($dados['fornecedores'])) {
                            $statusPermitidos = ['pendente', 'aguardando_aprovacao', 'aprovado', 'rejeitado', 'renegociacao'];
                            
                            if (!in_array($dados['status'], $statusPermitidos)) {
                                throw new Exception('Status inválido');
                            }
                
                            // LÓGICA DE VERSIONAMENTO - CRIAR NOVA VERSÃO AO ENVIAR PARA APROVAÇÃO
if ($dados['status'] === 'aguardando_aprovacao') {
    error_log("Iniciando processo de envio para aprovação da cotação ID: " . $dados['id']);
    
    try {
        // Verificar se o usuário na sessão existe no banco de dados
        $usuarioId = $_SESSION['usuario']['id'];
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuarioExiste = $stmt->fetch();
        
        // Se o usuário não existir, usar o usuário da cotação
        if (!$usuarioExiste) {
            $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
            $stmt->execute([$dados['id']]);
            $usuarioId = $stmt->fetchColumn();
            
            // Se ainda não encontrar um usuário válido, usar um administrador
            if (!$usuarioId) {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1");
                $stmt->execute();
                $usuarioId = $stmt->fetchColumn();
                
                // Se não houver administrador, usar o primeiro usuário disponível
                if (!$usuarioId) {
                    $stmt = $conn->prepare("SELECT id FROM usuarios LIMIT 1");
                    $stmt->execute();
                    $usuarioId = $stmt->fetchColumn();
                    
                    // Se não houver nenhum usuário, lançar erro
                    if (!$usuarioId) {
                        throw new Exception("Não foi possível encontrar um usuário válido para registrar a versão");
                    }
                }
            }
            
            // Atualizar a sessão com o ID válido
            $_SESSION['usuario']['id'] = $usuarioId;
        }
        
        // 1. Busca a versão atual da cotação
        $stmt = $conn->prepare("SELECT COUNT(*) as total_versoes FROM cotacoes_versoes WHERE cotacao_id = ?");
        $stmt->execute([$dados['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $novaVersao = (int)$result['total_versoes'] + 1;
        error_log("Nova versão será: " . $novaVersao);

        // 2. Busca todos os dados atuais da cotação
        $stmt = $conn->prepare("SELECT * FROM cotacoes WHERE id = ?");
        $stmt->execute([$dados['id']]);
        $cotacao = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Busca todos os itens da cotação
        $stmt = $conn->prepare("SELECT * FROM itens_cotacao WHERE cotacao_id = ?");
        $stmt->execute([$dados['id']]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Prepara os dados para armazenar como versão
        $dadosVersao = [
            'cotacao' => $cotacao,
            'itens' => $itens,
            'motivo' => $dados['motivo'] ?? 'Envio para aprovação',
            'usuario' => $usuarioId // Usar o ID verificado
        ];

        // 5. Insere a nova versão
        $stmt = $conn->prepare("INSERT INTO cotacoes_versoes
                              (cotacao_id, versao, dados_json, usuario_id)
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $dados['id'],
            $novaVersao,
            json_encode($dadosVersao),
            $usuarioId // Usar o ID verificado
        ]);
    } catch (Exception $e) {
        error_log("Erro ao criar versão: " . $e->getMessage());
        throw $e; // Mantém a exceção para que seja tratada no nível superior
    }
}

// Atualiza o status da cotação e possivelmente o motivo
if ($dados['status'] === 'aprovado') {
    // Verificar se há motivo de aprovação
    if (!isset($dados['motivo_aprovacao']) || empty(trim($dados['motivo_aprovacao']))) {
        throw new Exception('Motivo da aprovação é obrigatório');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM cotacoes_renegociacoes WHERE cotacao_id = ?");
        $stmt->execute([$dados['id']]);
        error_log("Itens de renegociação removidos para a cotação #" . $dados['id']);
    } catch (Exception $e) {
        // Apenas log do erro, não interromper o fluxo de aprovação
        error_log("Erro ao remover itens de renegociação: " . $e->getMessage());
    }
    
    // Verificar se a coluna data_aprovacao existe
    try {
        $stmt = $conn->prepare("UPDATE cotacoes SET status = ?, motivo_aprovacao = ?, data_aprovacao = NOW() WHERE id = ?");
        $stmt->execute([$dados['status'], $dados['motivo_aprovacao'], $dados['id']]);
    } catch (Exception $e) {
        $stmt = $conn->prepare("UPDATE cotacoes SET status = ?, motivo_aprovacao = ? WHERE id = ?");
        $stmt->execute([$dados['status'], $dados['motivo_aprovacao'], $dados['id']]);
    }
    
    // Verificar se há itens aprovados específicos
    if (isset($dados['itens_aprovados']) && is_array($dados['itens_aprovados']) && count($dados['itens_aprovados']) > 0) {
        try {
            $stmt = $conn->prepare("UPDATE itens_cotacao SET aprovado = 0 WHERE cotacao_id = ?");
            $stmt->execute([$dados['id']]);
            
            foreach ($dados['itens_aprovados'] as $item) {
                $stmt = $conn->prepare("
                    UPDATE itens_cotacao 
                    SET aprovado = 1 
                    WHERE cotacao_id = ? 
                    AND produto_codigo = ?
                    AND fornecedor_nome = ?
                ");
                $stmt->execute([
                    $dados['id'],
                    $item['produto_id'],
                    $item['fornecedor_nome']
                ]);
            }
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE itens_cotacao ADD COLUMN aprovado TINYINT(1) DEFAULT 0");
            $stmt = $conn->prepare("UPDATE itens_cotacao SET aprovado = 0 WHERE cotacao_id = ?");
            $stmt->execute([$dados['id']]);
            
            foreach ($dados['itens_aprovados'] as $item) {
                $stmt = $conn->prepare("
                    UPDATE itens_cotacao 
                    SET aprovado = 1 
                    WHERE cotacao_id = ? 
                    AND produto_codigo = ?
                    AND fornecedor_nome = ?
                ");
                $stmt->execute([
                    $dados['id'],
                    $item['produto_id'],
                    $item['fornecedor_nome']
                ]);
            }
        }
        
        $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
        $stmt->execute([$dados['id']]);
        $usuario_id = $stmt->fetchColumn();
        
        $tipoAprovacao = isset($dados['tipo_aprovacao']) ? $dados['tipo_aprovacao'] : 'manual';
        $message = "Cotação #{$dados['id']} foi aprovada" . 
                  ($tipoAprovacao === 'melhor-preco' ? " (melhor preço)" : " (seleção manual)");
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id, 
                    cotacao_id, 
                    message, 
                    type, 
                    details
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $detalhes = json_encode([
                'tipo_aprovacao' => $tipoAprovacao,
                'itens_aprovados' => count($dados['itens_aprovados']),
                'motivo' => $dados['motivo_aprovacao']
            ]);
            $stmt->execute([$usuario_id, $dados['id'], $message, 'aprovado_parcial', $detalhes]);
        } catch (Exception $e) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id, 
                    cotacao_id, 
                    message, 
                    type
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$usuario_id, $dados['id'], $message, 'aprovado_parcial']);
        }
    } else {
        try {
            $stmt = $conn->prepare("UPDATE itens_cotacao SET aprovado = 1 WHERE cotacao_id = ?");
            $stmt->execute([$dados['id']]);
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE itens_cotacao ADD COLUMN aprovado TINYINT(1) DEFAULT 0");
            $stmt = $conn->prepare("UPDATE itens_cotacao SET aprovado = 1 WHERE cotacao_id = ?");
            $stmt->execute([$dados['id']]);
        }
    }
    
    // Após aprovar a cotação, criar registro de sawing
    try {
        // Verificar se o usuário na sessão existe no banco de dados
        $usuarioId = $_SESSION['usuario']['id'];
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuarioExiste = $stmt->fetch();
        
        // Se o usuário não existir, usar o usuário da cotação
        if (!$usuarioExiste) {
            $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
            $stmt->execute([$dados['id']]);
            $usuarioId = $stmt->fetchColumn();
            
            // Se ainda não encontrar um usuário válido, usar um administrador
            if (!$usuarioId) {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1");
                $stmt->execute();
                $usuarioId = $stmt->fetchColumn();
                
                // Se não houver administrador, usar o primeiro usuário disponível
                if (!$usuarioId) {
                    $stmt = $conn->prepare("SELECT id FROM usuarios LIMIT 1");
                    $stmt->execute();
                    $usuarioId = $stmt->fetchColumn();
                }
            }
            
            // Atualizar a sessão com o ID válido
            if ($usuarioId) {
                $_SESSION['usuario']['id'] = $usuarioId;
            }
        }
        
        // Buscar os itens aprovados com seus detalhes
        $itensAprovados = array();
        if (isset($dados['itens_aprovados']) && is_array($dados['itens_aprovados'])) {
            foreach ($dados['itens_aprovados'] as $item) {
                $stmt = $conn->prepare("
                    SELECT ic.* 
                    FROM itens_cotacao ic 
                    WHERE ic.cotacao_id = ? 
                    AND ic.produto_codigo = ? 
                    AND ic.fornecedor_nome = ?
                ");
                $stmt->execute([$dados['id'], $item['produto_id'], $item['fornecedor_nome']]);
                $itemData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($itemData) {
                    $itensAprovados[] = array(
                        'fornecedor_nome' => $itemData['fornecedor_nome'],
                        'produto_nome' => $itemData['produto_nome'],
                        'produto_id' => $itemData['produto_codigo'],
                        'quantidade' => $itemData['quantidade'],
                        'valor_unitario' => $itemData['valor_unitario'],
                        'primeiro_valor' => $itemData['primeiro_valor']
                    );
                }
            }
        }
        
        // Salvar histórico de aprovação
        salvarHistoricoCotacao(
            $conn,
            $dados['id'],
            $usuarioId,
            'aprovacao',
            $dados['motivo_aprovacao'] ?? null,
            $itensAprovados,
            $dados['tipo_aprovacao'] ?? 'manual'
        );

        // Criar registro na tabela sawing
        // Buscar valores iniciais e finais da cotação
        $valorInicial = 0;
        $valorFinal = 0;
        $rodadas = 1; // Valor padrão
        
        // Buscar o número de rodadas
        $stmt = $conn->prepare("SELECT MAX(versao) as max_versao FROM cotacoes_versoes WHERE cotacao_id = ?");
        $stmt->execute([$dados['id']]);
        $maxVersao = $stmt->fetchColumn();
        if ($maxVersao) {
            $rodadas = (int)$maxVersao;
        }
        
        // Calcular valores iniciais e finais
        if (count($itensAprovados) > 0) {
            foreach ($itensAprovados as $item) {
                $quantidade = floatval($item['quantidade']);
                $valorUnitario = floatval($item['valor_unitario']);
                $primeiroValor = floatval($item['primeiro_valor']);
                
                $valorFinal += $quantidade * $valorUnitario;
                $valorInicial += $quantidade * $primeiroValor;
            }
        } else {
            // Se não houver itens aprovados específicos, buscar todos os itens da cotação
            $stmt = $conn->prepare("
                SELECT quantidade, valor_unitario, primeiro_valor 
                FROM itens_cotacao 
                WHERE cotacao_id = ? AND aprovado = 1
            ");
            $stmt->execute([$dados['id']]);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($itens as $item) {
                $quantidade = floatval($item['quantidade']);
                $valorUnitario = floatval($item['valor_unitario']);
                $primeiroValor = floatval($item['primeiro_valor']);
                
                $valorFinal += $quantidade * $valorUnitario;
                $valorInicial += $quantidade * $primeiroValor;
            }
        }
        
        // Calcular economia (se houver valores iniciais)
        $economia = $valorInicial - $valorFinal;
        $economiaPercentual = $valorInicial > 0 ? ($economia / $valorInicial * 100) : 0;
        
        // Inserir na tabela sawing
        $stmt = $conn->prepare("
            INSERT INTO sawing (
                cotacao_id, 
                usuario_id, 
                data_registro, 
                valor_total_inicial, 
                valor_total_final, 
                economia, 
                economia_percentual, 
                rodadas, 
                status, 
                observacoes
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'concluido', ?)
        ");
        
        $observacoes = "Cotação aprovada: " . ($dados['motivo_aprovacao'] ?? 'Sem motivo informado');
        
        $stmt->execute([
            $dados['id'],
            $usuarioId,
            $valorInicial,
            $valorFinal,
            $economia,
            $economiaPercentual,
            $rodadas,
            $observacoes
        ]);
        
        $sawingId = $conn->lastInsertId();
        
        // Inserir itens na tabela sawing_itens
        if ($sawingId) {
            // Buscar todos os itens aprovados
            $stmt = $conn->prepare("
                SELECT id, produto_nome, quantidade, valor_unitario, primeiro_valor
                FROM itens_cotacao 
                WHERE cotacao_id = ? AND aprovado = 1
            ");
            $stmt->execute([$dados['id']]);
            $itensSawing = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($itensSawing as $item) {
                $valorInicialItem = floatval($item['primeiro_valor']);
                $valorFinalItem = floatval($item['valor_unitario']);
                $quantidade = floatval($item['quantidade']);
                
                $economiaItem = ($valorInicialItem - $valorFinalItem) * $quantidade;
                $economiaPercentualItem = $valorInicialItem > 0 ? (($valorInicialItem - $valorFinalItem) / $valorInicialItem * 100) : 0;
                
                $stmt = $conn->prepare("
                    INSERT INTO sawing_itens (
                        sawing_id, 
                        item_id, 
                        descricao, 
                        valor_unitario_inicial, 
                        valor_unitario_final, 
                        economia, 
                        economia_percentual, 
                        status,
                        quantidade
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'concluido', ?)
                ");
                
                $stmt->execute([
                    $sawingId,
                    $item['id'],
                    $item['produto_nome'],
                    $valorInicialItem,
                    $valorFinalItem,
                    $economiaItem,
                    $economiaPercentualItem,
                    $quantidade
                ]);
            }
        }

        error_log("Registro de sawing criado para cotação #{$dados['id']}");
    } catch (Exception $e) {
        error_log("Erro ao criar registro de sawing: " . $e->getMessage());
    }
} else if ($dados['status'] === 'rejeitado' && isset($dados['motivo_rejeicao'])) {
    $stmt = $conn->prepare("UPDATE cotacoes SET status = ?, motivo_rejeicao = ? WHERE id = ?");
    $stmt->execute([$dados['status'], $dados['motivo_rejeicao'], $dados['id']]);

    // Salvar histórico de rejeição
    salvarHistoricoCotacao(
        $conn,
        $dados['id'],
        $_SESSION['usuario']['id'],
        'rejeicao',
        $dados['motivo_rejeicao']
    );
} else {
    $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
    $stmt->execute([$dados['status'], $dados['id']]);
}

// Se for aprovação/rejeição, cria notificação
if ($dados['status'] === 'aprovado' || $dados['status'] === 'rejeitado') {
    $stmt = $conn->prepare("SELECT usuario_id FROM cotacoes WHERE id = ?");
    $stmt->execute([$dados['id']]);
    $usuario_id = $stmt->fetchColumn();

    $message = $dados['status'] === 'aprovado'
        ? "Sua cotação #{$dados['id']} foi aprovada"
        : "Sua cotação #{$dados['id']} foi rejeitada. Motivo: " . ($dados['motivo_rejeicao'] ?? 'Não informado');

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, cotacao_id, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $dados['id'], $message, $dados['status']]);
}

$conn->commit();
echo json_encode([
    'success' => true,
    'message' => isset($novaVersao)
        ? "Cotação enviada para aprovação (Versão {$novaVersao})"
        : 'Status atualizado com sucesso'
]);
exit;
                        }
                
                        // ATUALIZAÇÃO COMPLETA DA COTAÇÃO (com fornecedores e produtos)
                        if (isset($dados['fornecedores'])) {
                            $stmt = $conn->prepare("SELECT status FROM cotacoes WHERE id = ?");
                            $stmt->execute([$dados['id']]);
                            $statusAtual = $stmt->fetchColumn();
                
                            if ($statusAtual === 'renegociacao') {
                                // Busca a versão atual
                                $stmt = $conn->prepare("SELECT COUNT(*) as total_versoes FROM cotacoes_versoes WHERE cotacao_id = ?");
                                $stmt->execute([$dados['id']]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $novaVersao = (int)$result['total_versoes'] + 1;
                
                                // Busca os dados atuais
                                $stmt = $conn->prepare("SELECT * FROM cotacoes WHERE id = ?");
                                $stmt->execute([$dados['id']]);
                                $cotacao = $stmt->fetch(PDO::FETCH_ASSOC);
                
                                $stmt = $conn->prepare("SELECT * FROM itens_cotacao WHERE cotacao_id = ?");
                                $stmt->execute([$dados['id']]);
                                $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                                // Insere a versão
                                $stmt = $conn->prepare("INSERT INTO cotacoes_versoes
                                                      (cotacao_id, versao, dados_json, usuario_id)
                                                      VALUES (?, ?, ?, ?)");
                                $stmt->execute([
                                    $dados['id'],
                                    $novaVersao,
                                    json_encode([
                                        'cotacao' => $cotacao,
                                        'itens' => $itens,
                                        'motivo' => 'Renegociação',
                                        'usuario' => $_SESSION['usuario']['id']
                                    ]),
                                    $_SESSION['usuario']['id']
                                ]);
                            }
                
                            // Buscar os valores atuais antes de remover os itens
                            $stmt = $conn->prepare("SELECT produto_id, fornecedor_nome, valor_unitario, primeiro_valor, ultimo_preco, rodadas FROM itens_cotacao WHERE cotacao_id = ?");
                            $stmt->execute([$dados['id']]);
                            $valoresAnteriores = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $key = $row['produto_id'] . '_' . $row['fornecedor_nome'];
                                $valoresAnteriores[$key] = [
                                    'primeiro_valor' => $row['primeiro_valor'] ?? $row['valor_unitario'],
                                    'ultimo_preco' => $row['valor_unitario'], // atual vira ultimo
                                    'rodadas' => $row['rodadas'] ? (int)$row['rodadas'] : 0
                                ];
                            }
                            
                            // Atualiza o status
                            $novoStatus = $dados['status'] ?? 'pendente';
                            $stmt = $conn->prepare("UPDATE cotacoes SET status = ? WHERE id = ?");
                            $stmt->execute([$novoStatus, $dados['id']]);
                            
                            // Atualizar ou inserir produtos
                            foreach ($dados['fornecedores'] as $fornecedor) {
                                foreach ($fornecedor['produtos'] as $produto) {
                                    $key = $produto['id'] . '_' . $fornecedor['fornecedor_nome'];
                                    
                                    $stmt = $conn->prepare("
                                        SELECT COUNT(*) FROM itens_cotacao 
                                        WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?
                                    ");
                                    $stmt->execute([$dados['id'], $produto['id'], $fornecedor['fornecedor_nome']]);
                                    $itemExiste = $stmt->fetchColumn() > 0;
                                    
                                    if ($itemExiste) {
                                        // Primeiro, obtenha o valor unitário atual
                                        $stmtGetOldValue = $conn->prepare("SELECT valor_unitario FROM itens_cotacao WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?");
                                        $stmtGetOldValue->execute([$dados['id'], $produto['id'], $fornecedor['fornecedor_nome']]);
                                        $oldValueResult = $stmtGetOldValue->fetch(PDO::FETCH_ASSOC);
                                        $valorUnitarioAntigo = $oldValueResult ? floatval($oldValueResult['valor_unitario']) : null;
                                        
                                        // Novo valor unitário
                                        $valorUnitarioNovo = floatval($produto['valor_unitario']);

                                        // Atualização dos dados
                                        $stmt = $conn->prepare("
                                            UPDATE itens_cotacao SET
                                                produto_nome = ?,
                                                produto_codigo = ?,
                                                produto_unidade = ?,
                                                quantidade = ?,
                                                valor_unitario = ?,
                                                ultimo_preco = ?,
                                                prazo_pagamento = ?,
                                                prazo_entrega = ?,
                                                frete = ?,
                                                difal = ?,
                                                arquivo_cotacao = COALESCE(?, arquivo_cotacao)
                                            WHERE cotacao_id = ? AND produto_id = ? AND fornecedor_nome = ?
                                        ");
                                        
                                        $stmt->execute([
                                            $produto['nome'],
                                            $produto['codigo'],
                                            $produto['unidade'],
                                            $produto['quantidade'],
                                            $valorUnitarioNovo,
                                            $valorUnitarioNovo,
                                            $fornecedor['prazo_pagamento'],
                                            $fornecedor['prazo_entrega'],
                                            $fornecedor['frete'],
                                            $fornecedor['difal'],
                                            $arquivo_cotacao,
                                            $dados['id'],
                                            $produto['id'],
                                            $fornecedor['fornecedor_nome']
                                        ]);
                                    } else {
                                        // Inserir novo item
                                        $stmt = $conn->prepare("
                                            INSERT INTO itens_cotacao (
                                                cotacao_id,
                                                fornecedor_nome,
                                                produto_id,
                                                produto_nome,
                                                produto_codigo,
                                                produto_unidade,
                                                quantidade,
                                                valor_unitario,
                                                primeiro_valor,
                                                ultimo_preco,
                                                rodadas,
                                                prazo_pagamento,
                                                prazo_entrega,
                                                frete,
                                                difal,
                                                arquivo_cotacao
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                        ");
                                        $stmt->execute([
                                            $dados['id'],
                                            $fornecedor['fornecedor_nome'],
                                            $produto['id'],
                                            $produto['nome'],
                                            $produto['codigo'] ?? '',
                                            $produto['unidade'] ?? 'un',
                                            $produto['quantidade'],
                                            $produto['valor_unitario'],
                                            $produto['valor_unitario'], // primeiro_valor igual ao valor inicial
                                            null,
                                            0, // rodadas começa em 0
                                            $fornecedor['prazo_pagamento'] ?? '',
                                            $fornecedor['prazo_entrega'] ?? '',
                                            $fornecedor['frete'] ?? 0,
                                            $fornecedor['difal'] ?? 0,
                                            $arquivo_cotacao
                                        ]);
                                    }
                                }
                            }
                            
                            // Remover produtos que não estão mais no payload
                            $produtosAtuais = [];
                            foreach ($dados['fornecedores'] as $fornecedor) {
                                foreach ($fornecedor['produtos'] as $produto) {
                                    $produtosAtuais[] = [
                                        'produto_id' => $produto['id'],
                                        'fornecedor_nome' => $fornecedor['fornecedor_nome']
                                    ];
                                }
                            }
                            
                            $condicoes = [];
                            $params = [$dados['id']];
                            foreach ($produtosAtuais as $produto) {
                                $condicoes[] = "(produto_id = ? AND fornecedor_nome = ?)";
                                $params[] = $produto['produto_id'];
                                $params[] = $produto['fornecedor_nome'];
                            }
                            
                            if (count($condicoes) > 0) {
                                $sql = "DELETE FROM itens_cotacao WHERE cotacao_id = ? AND NOT (" . implode(' OR ', $condicoes) . ")";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute($params);
                            }
                            
                            $conn->commit();
                            echo json_encode([
                                'success' => true,
                                'message' => isset($novaVersao)
                                    ? "Cotação renegociada (Versão {$novaVersao})"
                                    : 'Cotação atualizada com sucesso'
                            ]);
                            exit;
                        }
                
                        throw new Exception('Nenhum dado válido para atualização fornecido');
                
                    } catch (Exception $e) {
                        $conn->rollBack();
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro ao atualizar cotação',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
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
