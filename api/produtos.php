<?php
// Configurações iniciais (headers, tratamento de erros, etc.)
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Endpoint para buscar o último valor aprovado de um produto
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ultimo_valor_aprovado'])) {
    $produtoId = $_GET['ultimo_valor_aprovado'];
    
    try {
        // Consulta para buscar o último valor aprovado para o produto
        $query = "
            SELECT ci.valor_unitario 
            FROM cotacao_itens ci
            JOIN cotacoes c ON ci.cotacao_id = c.id
            WHERE ci.produto_codigo = ? 
            AND c.status = 'aprovado'
            ORDER BY c.data_aprovacao DESC, c.id DESC
            LIMIT 1
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$produtoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            echo json_encode([
                'success' => true,
                'ultimo_valor' => $resultado['valor_unitario']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'ultimo_valor' => null,
                'message' => 'Nenhum valor aprovado anteriormente'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar último valor aprovado',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } elseif (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            
            $stmt = $conn->prepare("
                SELECT id, codigo, nome, unidade 
                FROM produtos 
                WHERE (nome LIKE ? OR codigo LIKE ?) 
                ORDER BY nome
                LIMIT 20
            ");
            $stmt->execute([$search, $search]);
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($produtos);
        } elseif (isset($_GET['codigo'])) {
            $codigo = $_GET['codigo'];
            $stmt = $conn->prepare("
                SELECT id, codigo, nome, unidade 
                FROM produtos 
                WHERE codigo = ?
                LIMIT 1
            ");
            $stmt->execute([$codigo]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($produto ? [$produto] : []); // Retorna como array
        } else {
            echo json_encode([]); // resposta segura para chamadas sem parâmetros
        }
        break;
    case 'POST':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Verifica se código já existe
            $stmt = $conn->prepare("SELECT id FROM produtos WHERE codigo = ?");
            $stmt->execute([$dados['codigo']]);
            if ($stmt->fetch()) {
                throw new Exception('Código já cadastrado');
            }

            $stmt = $conn->prepare("
                INSERT INTO produtos (codigo, nome, unidade) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $dados['codigo'],
                $dados['nome'],
                $dados['unidade']
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Verifica se código já existe em outro produto
            $stmt = $conn->prepare("
                SELECT id FROM produtos 
                WHERE codigo = ? AND id != ?
            ");
            $stmt->execute([$dados['codigo'], $dados['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Código já cadastrado em outro produto');
            }

            $stmt = $conn->prepare("
                UPDATE produtos 
                SET codigo = ?, nome = ?, unidade = ? 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $dados['codigo'],
                $dados['nome'],
                $dados['unidade'],
                $dados['id']
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            try {
                $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Não é possível excluir este produto'
                ]);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Requisição inválida ou parâmetros ausentes']);
        break;
    


}