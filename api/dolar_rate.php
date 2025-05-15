<?php
header('Content-Type: application/json');

// URL da API do Banco Central do Brasil
$url = 'https://www.bcb.gov.br/api/servico/sitebcb/indicadorCambio';

try {
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurar opções do cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Executar a requisição
    $response = curl_exec($ch);
    
    // Verificar se houve erro
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    // Fechar a conexão cURL
    curl_close($ch);
    
    // Decodificar a resposta JSON
    $data = json_decode($response, true);
    
    // Verificar se a resposta é válida
    if (!$data || !isset($data['conteudo'][0]['valorVenda'])) {
        throw new Exception('Dados inválidos recebidos da API');
    }
    
    // Retornar a cotação do dólar
    echo json_encode([
        'success' => true,
        'rate' => $data['conteudo'][0]['valorVenda']
    ]);
    
} catch (Exception $e) {
    // Em caso de erro, retornar uma mensagem de erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar cotação do dólar: ' . $e->getMessage()
    ]);
} 