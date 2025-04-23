<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

if (!isset($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode(['success' => false, 'message' => 'Arquivo não enviado']));
}

try {
    $xml = simplexml_load_file($_FILES['xml']['tmp_name']);
    
    if (!$xml) {
        throw new Exception('Arquivo XML inválido');
    }

    $produtos = [];
    
    // Adapte este trecho de acordo com a estrutura do seu XML
    foreach ($xml->produto as $produto) {
        $produtos[] = [
            'nome' => (string)$produto->nome,
            'quantidade' => (float)$produto->quantidade,
            'unidade' => (string)$produto->unidade
        ];
    }

    echo json_encode([
        'success' => true,
        'produtos' => $produtos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
