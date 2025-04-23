<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<div id="modalCotacao" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <h3><i class="fas fa-file-invoice-dollar"></i> Nova Cotação</h3>
        
        <form id="formCotacao">
            <input type="hidden" id="cotacaoId">
            <div class="form-header">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Comprador:</label>
                    <input type="text" value="<?php echo $_SESSION['usuario']['nome']; ?>" readonly>
                </div>
            </div>

            <div class="form-group">
                <label for="excelFile"><i class="fas fa-file-excel"></i> Upload da Planilha:</label>
                <input type="file" id="excelFile" accept=".xlsx,.xls" data-required="true">
                <small>Formatos aceitos: XLSX, XLS</small>
            </div>
            <div class="form-actions-top">
                <button type="button" id="btn-importar-novos-produtos" class="btn-secondary">
                    <i class="fas fa-file-import"></i> Importar Novos Produtos
                </button>
                <button type="button" class="btn-adicionar-fornecedor">
                    <i class="fas fa-plus"></i> Adicionar Fornecedor
                </button>
            </div>

            <div id="fornecedores-container">
                <!-- Fornecedores serão adicionados aqui após o upload -->
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-salvar">
                    <i class="fas fa-save"></i> Salvar Cotação
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template para seção de fornecedor -->
<template id="template-fornecedor">
  <div class="fornecedor-section">
    <h4>Fornecedor</h4>

    <div class="form-grid">
      <div class="form-group">
        <label for="fornecedor-nome"><i class="fas fa-building"></i> Nome do Fornecedor:</label>
        <input type="text" class="fornecedor-input" id="fornecedor-nome" required style="text-transform: uppercase;" placeholder="Digite o nome do fornecedor">
      </div>

      <div class="form-group">
        <label for="prazo-pagamento"><i class="fas fa-calendar-alt"></i> Prazo de Pagamento:</label>
        <input type="text" class="prazo-pagamento" id="prazo-pagamento" placeholder="Ex: 30 dias">
      </div>

      <div class="form-group">
        <label for="prazo-entrega"><i class="fas fa-truck"></i> Prazo de Entrega:</label>
        <input type="text" class="prazo-entrega" id="prazo-entrega" placeholder="Ex: 5 dias">
      </div>

      <div class="form-group">
        <label for="frete-valor"><i class="fas fa-shipping-fast"></i> Frete (R$):</label>
        <input type="number" class="frete-valor" id="frete-valor" step="0.01" min="0" value="0">
      </div>

      <div class="form-group">
        <label for="difal-percentual"><i class="fas fa-percentage"></i> DIFAL (%):</label>
        <input type="number" class="difal-percentual" id="difal-percentual" step="0.01" min="0" max="100" value="0">
      </div>
    </div>

    <div class="form-group anexar-cotacao">
      <label for="arquivo-cotacao"><i class="fas fa-paperclip"></i> Anexar Cotação:</label>
      <input type="file" class="arquivo-cotacao" accept=".pdf,.jpg,.jpeg,.png" required>
      <small>Formatos aceitos: PDF, JPG, JPEG, PNG</small>
    </div>

    <div class="table-container">
      <table class="tabela-produtos">
        <thead>
          <tr>
            <th><i class="fas fa-box"></i> Produto</th>
            <th><i class="fas fa-hashtag"></i> Qtd</th>
            <th><i class="fas fa-ruler"></i> UN</th>
            <th><i class="fas fa-check-circle"></i> Ult. Vlr. Aprovado</th>
            <th><i class="fas fa-dollar-sign"></i> Valor Unit.</th>
            <th><i class="fas fa-calculator"></i> Valor Unit. Difal/Frete</th>
            <th><i class="fas fa-sort-numeric-up"></i> Total</th>
            <th><i class="fas fa-cogs"></i> Ações</th>
          </tr>
        </thead>
        <tbody class="produtos-fornecedor">
          <!-- Produtos do Excel serão clonados aqui -->
        </tbody>
      </table>
    </div>
  </div>
</template>

<div id="modalVisualizacao" class="modal">
    <div class="modal-content modal-large">
        <span class="close" onclick="document.getElementById('modalVisualizacao').style.display='none'">×</span>
        <h3><i class="fas fa-eye"></i> Detalhes da Cotação</h3>
        
        <div class="info-cotacao">
            <!-- Informações básicas da cotação serão inseridas aqui -->
        </div>
        
        <!-- Motivo integrado (será exibido diretamente na info-cotacao) -->
        <div id="motivo-container" class="motivo-container" style="display: none;">
            <div class="motivo-header"></div>
            <div class="motivo-texto"></div>
        </div>

        <div class="resumo-orcamento">
            <h4><i class="fas fa-chart-pie"></i> Resumo Orçamento Melhor Preço</h4>
            <div class="resumo-cards">
                <div class="resumo-card">
                    <div class="resumo-valor" id="total-produtos">0</div>
                    <div class="resumo-label">Produtos</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor" id="total-fornecedores">0</div>
                    <div class="resumo-label">Fornecedores</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor" id="total-quantidade">0</div>
                    <div class="resumo-label">Quantidade Total</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor" id="total-valor">R$ 0,00</div>
                    <div class="resumo-label">Valor Total</div>
                </div>
            </div>
        </div>

        <!-- Adicione a div itens-cotacao aqui -->
        <div class="itens-cotacao">
            <!-- Itens da cotação serão inseridos aqui -->
        </div>
        
        <!-- Resto do código permanece igual -->
        <!-- Filtros de análise -->
        
        <!-- Conteúdos de visualização -->
        <div id="modal-conteudo-analise" class="view-content" style="display: none;">
            <!-- Conteúdo da visualização por fornecedor será inserido aqui -->
        </div>

        <div id="modal-conteudo-analise-produto" class="view-content" style="display: none;">
            <!-- Conteúdo da visualização por produto será inserido aqui -->
        </div>

        <div id="modal-conteudo-analise-comparativo" class="view-content" style="display: none;">
            <!-- Conteúdo da visualização comparativa será inserido aqui -->
        </div>
        <div id="historico-versoes" class="historico-versoes" style="display: none;">
            <h4><i class="fas fa-history"></i> Histórico de Versões</h4>
            <div class="versoes-container"></div>
        </div>
    </div>
</div>

<!-- Template para nova linha de produto -->
<template id="template-produto">
    <tr>
        <td>
            <div class="produto-search-container">
                <input type="text" class="produto-search" placeholder="Buscar produto...">
                <div class="produto-results" style="display:none;"></div>
                <input type="hidden" class="produto-id" name="produto_id" required>
                <div class="produto-selected"></div>
            </div>
        </td>
        <td><input type="number" class="quantidade" min="1" required></td>
        <td><input type="text" class="unidade" readonly></td>
        <td class="ultimo-valor-aprovado">-</td>
        <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
        <td class="valor-unit-difal-frete">0,0000</td>
        <td class="total">0,0000</td>
        <td>
            <button type="button" class="btn-remover-produto" title="Remover produto">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>



function renderizarItensCotacao(itens) {
    console.log("Iniciando renderização de itens:", itens);
    
    const container = document.querySelector('#modalVisualizacao .itens-cotacao');
    
    if (!container) {
        console.error('Container para itens não encontrado');
        return;
    }
    
    if (!itens || !Array.isArray(itens) || itens.length === 0) {
        console.log("Nenhum item para renderizar");
        container.innerHTML = '<p>Nenhum item disponível para exibição</p>';
        return;
    }
    
    console.log("Agrupando itens por fornecedor");
    // Agrupar itens por fornecedor
    const itensPorFornecedor = itens.reduce((acc, item) => {
        if (!acc[item.fornecedor_nome]) {
            acc[item.fornecedor_nome] = [];
        }
        acc[item.fornecedor_nome].push(item);
        return acc;
    }, {});
    
    console.log("Fornecedores agrupados:", Object.keys(itensPorFornecedor));
    
    // Gerar HTML para cada fornecedor
    const html = Object.entries(itensPorFornecedor).map(([fornecedor, itensDoFornecedor]) => {
        const primeiroItem = itensDoFornecedor[0];
        
        return `
            <div class="fornecedor-section">
                <h4>${fornecedor}</h4>
                <div class="fornecedor-info">
                    <p><strong>Prazo de Pagamento:</strong> ${primeiroItem.prazo_pagamento || 'Não informado'}</p>
                    <p><strong>Prazo de Entrega:</strong> ${primeiroItem.prazo_entrega || 'Não informado'}</p>
                    <p><strong>Frete:</strong> R$ ${formatarNumero(primeiroItem.frete || 0)}</p>
                    <p><strong>DIFAL:</strong> ${primeiroItem.difal || '0'}%</p>
                </div>
                
                <table class="tabela-itens">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Ult. Vlr. Aprovado</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itensDoFornecedor.map(item => `
                            <tr>
                                <td>${item.produto_nome || 'Produto não especificado'}</td>
                                <td>${item.quantidade || 0}</td>
                                <td>${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : '-'}</td>
                                <td>R$ ${formatarNumero(item.valor_unitario || 0)}</td>
                                <td>R$ ${formatarNumero((item.quantidade || 0) * (item.valor_unitario || 0))}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }).join('');
    
    console.log("HTML gerado, atualizando container");
    container.innerHTML = html || '<p>Nenhum item disponível para exibição</p>';
}

// Função auxiliar para formatar números
function formatarNumero(valor) {
    if (valor === null || valor === undefined || isNaN(parseFloat(valor))) {
        return '0,0000';
    }
    return parseFloat(valor).toFixed(3).replace('.', ',');
}

// Função auxiliar para formatar data
function formatarData(dataString) {
    if (!dataString) return '';
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
}

</script>