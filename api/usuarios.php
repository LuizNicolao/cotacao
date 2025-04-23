<?php
// Desabilitar exibição de erros para evitar que erros PHP sejam incluídos na resposta JSON
error_reporting(0);
ini_set('display_errors', 0);

// Limpar qualquer saída anterior
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/check_permissions.php';

try {
    if (!isset($_SESSION['usuario'])) {
        http_response_code(401);
        exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
    }

    $conn = conectarDB();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!userCan('usuarios', 'visualizar')) {
                http_response_code(403);
                exit(json_encode(['success' => false, 'message' => 'Sem permissão para visualizar usuários']));
            }
            
            if (isset($_GET['id'])) {
                $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Não enviar a senha
                if (isset($usuario['senha'])) {
                    unset($usuario['senha']);
                }
                
                echo json_encode($usuario);
            }
            break;
            
        case 'POST':
            if (!userCan('usuarios', 'criar')) {
                http_response_code(403);
                exit(json_encode(['success' => false, 'message' => 'Sem permissão para criar usuários']));
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            
            // Verificar se o email já existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$dados['email']]);
            if ($stmt->fetchColumn() > 0) {
                exit(json_encode(['success' => false, 'message' => 'Este email já está em uso']));
            }
            
            // Ajustar a consulta para corresponder às colunas da sua tabela
            $stmt = $conn->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo, status, data_cadastro) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            try {
                $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
                $stmt->execute([
                    $dados['nome'],
                    $dados['email'],
                    $senha_hash,
                    $dados['tipo'],
                    $dados['status']
                ]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'PUT':
            if (!userCan('usuarios', 'editar')) {
                http_response_code(403);
                exit(json_encode(['success' => false, 'message' => 'Sem permissão para editar usuários']));
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            
            // Verificar se o email já existe para outro usuário
            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$dados['email'], $dados['id']]);
            if ($stmt->fetchColumn() > 0) {
                exit(json_encode(['success' => false, 'message' => 'Este email já está em uso por outro usuário']));
            }
            
            // Se a senha foi fornecida, atualiza a senha também
            if (!empty($dados['senha'])) {
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, tipo = ?, status = ? WHERE id = ?");
                $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
                
                try {
                    $stmt->execute([
                        $dados['nome'],
                        $dados['email'],
                        $senha_hash,
                        $dados['tipo'],
                        $dados['status'],
                        $dados['id']
                    ]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                // Se a senha não foi fornecida, não atualiza a senha
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ?, status = ? WHERE id = ?");
                
                try {
                    $stmt->execute([
                        $dados['nome'],
                        $dados['email'],
                        $dados['tipo'],
                        $dados['status'],
                        $dados['id']
                    ]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
            }
            break;
            
        case 'DELETE':
            if (!userCan('usuarios', 'excluir')) {
                http_response_code(403);
                exit(json_encode(['success' => false, 'message' => 'Sem permissão para excluir usuários']));
            }
            
            if (isset($_GET['id'])) {
                // Verificar se não está tentando excluir o próprio usuário logado
                if ($_GET['id'] == $_SESSION['usuario']['id']) {
                    exit(json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário']));
                }
                
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                try {
                    $stmt->execute([$_GET['id']]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
