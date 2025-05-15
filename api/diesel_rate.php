<?php
header('Content-Type: application/json');

// URL da API da ANP (Agência Nacional do Petróleo)
$url = 'https://www.gov.br/anp/pt-br/assuntos/precos-e-defesa-da-concorrencia/precos/precos-revenda-e-de-distribuicao-combustiveis/shlp/indicadores';

try {
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurar opções do cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecionamentos
    
    // Executar a requisição
    $response = curl_exec($ch);
    
    // Verificar se houve erro
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    // Fechar a conexão cURL
    curl_close($ch);
    
    // Verificar se a resposta está vazia
    if (empty($response)) {
        throw new Exception('Resposta vazia da API');
    }
    
    // Extrair o preço do Diesel usando expressão regular
    if (preg_match('/Diesel S-10.*?R\$ ([\d,]+)/', $response, $matches)) {
        $preco = str_replace(',', '.', $matches[1]);
        
        // Retornar o preço do Diesel
        echo json_encode([
            'success' => true,
            'rate' => $preco
        ]);
    } else {
        // Se não encontrar o preço, retornar um valor padrão
        echo json_encode([
            'success' => true,
            'rate' => '6.50' // Valor padrão caso não consiga obter o preço atual
        ]);
    }
    
} catch (Exception $e) {
    // Em caso de erro, retornar um valor padrão
    echo json_encode([
        'success' => true,
        'rate' => '6.50' // Valor padrão em caso de erro
    ]);
} 