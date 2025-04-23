<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$conn = conectarDB();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Verifica se CNPJ já existe
            $stmt = $conn->prepare("SELECT id FROM fornecedores WHERE cnpj = ?");
            $stmt->execute([$dados['cnpj']]);
            if ($stmt->fetch()) {
                throw new Exception('CNPJ já cadastrado');
            }

            $stmt = $conn->prepare("
                INSERT INTO fornecedores (nome, cnpj, email, telefone, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $dados['nome'],
                $dados['cnpj'],
                $dados['email'],
                $dados['telefone'],
                $dados['status']
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
            // Verifica se CNPJ já existe em outro fornecedor
            $stmt = $conn->prepare("
                SELECT id FROM fornecedores 
                WHERE cnpj = ? AND id != ?
            ");
            $stmt->execute([$dados['cnpj'], $dados['id']]);
            if ($stmt->fetch()) {
                throw new Exception('CNPJ já cadastrado em outro fornecedor');
            }

            $stmt = $conn->prepare("
                UPDATE fornecedores 
                SET nome = ?, cnpj = ?, email = ?, telefone = ?, status = ? 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $dados['nome'],
                $dados['cnpj'],
                $dados['email'],
                $dados['telefone'],
                $dados['status'],
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
                $stmt = $conn->prepare("DELETE FROM fornecedores WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Não é possível excluir este fornecedor'
                ]);
            }
        }
        break;
}
