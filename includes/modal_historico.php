?<?php
require_once 'config/database.php';
?>

<!-- Modal de Histórico -->
<div id="modalHistorico" class="modal">
    <div class="modal-content modal-historico">
        <div class="modal-header">
            <h2>Histórico do Produto</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="info-produto">
                <h3 id="nomeProdutoHistorico"></h3>
            </div>
            <div id="historicoFornecedores">
                <!-- Histórico por fornecedor será inserido aqui -->
            </div>
            <div id="semHistorico" class="sem-historico" style="display: none;">
                <i class="fas fa-history"></i>
                <p>Nenhum histórico encontrado para este produto.</p>
            </div>
        </div>
    </div>
</div>

<style>
.modal-historico {
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    margin: 5vh auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-historico .modal-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-historico .modal-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.5rem;
}

.modal-historico .modal-body {
    padding: 20px;
    max-height: calc(90vh - 70px);
    overflow-y: auto;
}

.info-produto {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.info-produto h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2rem;
}

.fornecedor-section {
    margin-bottom: 30px;
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.fornecedor-header {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px 6px 0 0;
    border-bottom: 1px solid #e9ecef;
}

.fornecedor-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.1rem;
}

.historico-container {
    padding: 15px;
}

.historico-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.historico-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
}

.historico-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    color: #495057;
}

.historico-table tr:hover {
    background-color: #f8f9fa;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-aprovado {
    background-color: #d4edda;
    color: #155724;
}

.status-concluido {
    background-color: #cce5ff;
    color: #004085;
}

.valor-diferenca {
    font-size: 0.85rem;
    padding: 3px 8px;
    border-radius: 12px;
    margin-left: 8px;
}

.valor-aumento {
    background-color: #f8d7da;
    color: #721c24;
}

.valor-diminuicao {
    background-color: #d4edda;
    color: #155724;
}

.sem-historico {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.sem-historico i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #dee2e6;
}

.sem-historico p {
    margin: 0;
    font-size: 1.1rem;
}

.close {
    color: #6c757d;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close:hover {
    color: #343a40;
}

@media (max-width: 768px) {
    .modal-historico {
        width: 100%;
        margin: 0;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .historico-table {
        display: block;
        overflow-x: auto;
    }
    
    .historico-table th,
    .historico-table td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}

.economia-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.economia-valor {
    font-weight: 500;
}

.economia-porcentagem {
    font-size: 0.85rem;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-block;
}

.economia-positiva {
    color: #155724;
    background-color: #d4edda;
}

.economia-negativa {
    color: #721c24;
    background-color: #f8d7da;
}

.tipo-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.tipo-programada {
    background-color: #e3f2fd;
    color: #1976d2;
}

.tipo-emergencial {
    background-color: #fce4ec;
    color: #c2185b;
}

.tipo-nao-especificado {
    background-color: #f5f5f5;
    color: #757575;
}
</style>

<script>
function abrirModalHistorico(produtoId, produtoNome, fornecedorNome) {
    const modal = document.getElementById('modalHistorico');
    const nomeProduto = document.getElementById('nomeProdutoHistorico');
    const historicoFornecedores = document.getElementById('historicoFornecedores');
    
    nomeProduto.textContent = produtoNome;
    
    // Buscar histórico do produto
    fetch(`api/historico_produto.php?produto_id=${produtoId}&produto_nome=${encodeURIComponent(produtoNome)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Agrupar histórico por fornecedor
                const historicoPorFornecedor = {};
                
                data.historico.forEach(item => {
                    const fornecedor = item.fornecedor_nome || 'Não especificado';
                    if (!historicoPorFornecedor[fornecedor]) {
                        historicoPorFornecedor[fornecedor] = [];
                    }
                    historicoPorFornecedor[fornecedor].push(item);
                });
                
                // Gerar HTML para cada fornecedor
                let html = '';
                Object.entries(historicoPorFornecedor).forEach(([fornecedor, historico]) => {
                    html += `
                        <div class="fornecedor-section">
                            <div class="fornecedor-header">
                                <h4>Fornecedor: ${fornecedor}</h4>
                            </div>
                            <div class="historico-container">
                                <table class="historico-table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Comprador</th>
                                            <th>Valor Inicial</th>
                                            <th>Valor Final</th>
                                            <th>Quantidade</th>
                                            <th>Valor Total</th>
                                            <th>Economia</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${historico.map(item => {
                                            const variacao = ((item.valor_final - item.valor_inicial) / item.valor_inicial) * 100;
                                            const variacaoClass = variacao > 0 ? 'valor-aumento' : variacao < 0 ? 'valor-diminuicao' : '';
                                            const variacaoSinal = variacao > 0 ? '+' : '';
                                            
                                            return `
                                                <tr>
                                                    <td>${formatarData(item.data_criacao)}</td>
                                                    <td>${item.usuario_nome}</td>
                                                    <td>R$ ${formatarNumero(item.valor_inicial)}</td>
                                                    <td>R$ ${formatarNumero(item.valor_final)} ${variacaoSinal}${formatarNumero(variacao)}%</td>
                                                    <td>${item.quantidade}</td>
                                                    <td>R$ ${formatarNumero(item.valor_total)}</td>
                                                    <td>
                                                        <div class="economia-info">
                                                            <span class="economia-valor">R$ ${formatarNumero(Math.abs(item.valor_final - item.valor_inicial) * item.quantidade)}</span>
                                                            <span class="economia-porcentagem ${variacaoClass}">${variacaoSinal}${formatarNumero(variacao)}%</span>
                                                        </div>
                                                    </td>
                                                    <td><span class="tipo-badge tipo-${item.tipo?.toLowerCase() || 'nao-especificado'}">${item.tipo === 'programada' ? 'Programada' : item.tipo === 'emergencial' ? 'Emergencial' : 'Não Especificado'}</span></td>
                                                    <td><span class="status-badge status-${item.status.toLowerCase()}">${traduzirStatus(item.status)}</span></td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
                
                historicoFornecedores.innerHTML = html;
                document.getElementById('semHistorico').style.display = 'none';
            } else {
                historicoFornecedores.innerHTML = '';
                document.getElementById('semHistorico').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erro ao buscar histórico:', error);
            historicoFornecedores.innerHTML = '';
            document.getElementById('semHistorico').style.display = 'block';
        });
    
    modal.style.display = 'block';
}

// Funções auxiliares
function formatarData(dataString) {
    if (!dataString) return 'N/A';
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR');
}

function formatarNumero(valor) {
    if (valor === null || valor === undefined || isNaN(parseFloat(valor))) {
        return '0,00';
    }
    return parseFloat(valor).toFixed(2).replace('.', ',');
}

function traduzirStatus(status) {
    const traducoes = {
        'aprovado': 'Aprovado',
        'concluido': 'Concluído',
        'rejeitado': 'Rejeitado',
        'renegociacao': 'Em Renegociação'
    };
    return traducoes[status.toLowerCase()] || status;
}

// Configurar o botão de fechar o modal
document.querySelector('#modalHistorico .close').addEventListener('click', function() {
    document.getElementById('modalHistorico').style.display = 'none';
});

// Fechar o modal quando clicar fora dele
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalHistorico');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});
</script> 