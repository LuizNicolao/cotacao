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
                <label>Tipo de Compra: <span class="required">*</span></label>
                <div class="radio-group" required>
                    <label>
                        <input type="radio" name="tipo_compra" value="programada" checked required>
                        Compra Programada
                    </label>
                    <label>
                        <input type="radio" name="tipo_compra" value="emergencial" required>
                        Compra Emergencial
                    </label>
                </div>
            </div>

            <!-- Campo de justificativa para compra emergencial -->
            <div id="justificativa-emergencial" style="display: none;">
                <div class="form-group">
                    <label>Motivo da Compra Emergencial: <span class="required">*</span></label>
                    <select id="justificativa-padrao" class="form-control" required>
                        <option value="">Selecione o motivo</option>
                        <option value="atraso_fornecedor">Atraso fornecedor</option>
                        <option value="aumento_consumo">Aumento de consumo</option>
                        <option value="substituicao_reposicao">Substituição/Reposição de produtos (ponto a ponto)</option>
                        <option value="troca_cardapio">Troca de cardápio</option>
                        <option value="implantacao">Implantação</option>
                        <option value="substituicao_equipamento">Substituição de equipamento/utensílio por dano</option>
                        <option value="notificacao">Notificação</option>
                        <option value="outros">Outro(s)</option>
                    </select>
                </div>

                <!-- Campo específico para fornecedor com atraso -->
                <div id="fornecedor-atraso" class="form-group" style="display: none;">
                    <label>Fornecedor com Atraso: <span class="required">*</span></label>
                    <input type="text" id="fornecedor-atraso-input" class="form-control" placeholder="Informe o fornecedor que não entregou na data">
                </div>

                <!-- Campo específico para substituição/reposição -->
                <div id="fornecedor-problema" class="form-group" style="display: none;">
                    <label>Fornecedor que Gerou o Problema: <span class="required">*</span></label>
                    <input type="text" id="fornecedor-problema-input" class="form-control" placeholder="Informe o fornecedor que gerou o problema">
                </div>

                <!-- Campo para justificativa personalizada -->
                <div id="justificativa-personalizada-container" class="form-group" style="display: none;">
                    <label>Justificativa Detalhada: <span class="required">*</span></label>
                    <textarea id="justificativa-personalizada" class="form-control" rows="3" placeholder="Descreva detalhadamente o motivo da compra emergencial"></textarea>
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
      <input type="file" class="arquivo-cotacao" accept=".pdf,.jpg,.jpeg,.png">
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
            <button type="button" class="btn-remover-produto" onclick="this.closest('tr').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<!-- Modal de Importação de Produtos -->
<div id="modalImportacaoProdutos" class="modal">
    <div class="modal-content modal-large">
        <span class="close">×</span>
        <h3><i class="fas fa-file-import"></i> Importar Novos Produtos</h3>
        
        <div id="produtos-importacao-container" style="display: none;">
            <h4>Produtos Disponíveis</h4>
            <div class="table-container">
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th><i class="fas fa-check"></i></th>
                            <th><i class="fas fa-box"></i> Produto</th>
                            <th><i class="fas fa-hashtag"></i> Código</th>
                            <th><i class="fas fa-sort-numeric-up"></i> Quantidade</th>
                            <th><i class="fas fa-ruler"></i> UN</th>
                            <th><i class="fas fa-building"></i> Fornecedor</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody id="produtos-importacao-lista">
                        <!-- Produtos serão listados aqui -->
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="button" id="btn-confirmar-importacao" class="btn-primary">
                    <i class="fas fa-check"></i> Confirmar Importação
                </button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalImportacaoProdutos').style.display='none'">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para verificar se um produto já existe na cotação
    function produtoJaExisteNaCotacao(nome) {
        const produtosExistentes = [];
        document.querySelectorAll('.fornecedor-section').forEach(section => {
            const fornecedorNome = section.querySelector('.fornecedor-input')?.value;
            section.querySelectorAll('.produtos-fornecedor tr').forEach(row => {
                const produtoNome = row.querySelector('.produto-selected')?.textContent.trim();
                if (produtoNome === nome) {
                    produtosExistentes.push(fornecedorNome);
            }
            });
        });
        return produtosExistentes;
    }

    // Adicionar evento de clique para o botão de fechar do modal de importação
    const modalImportacao = document.getElementById('modalImportacaoProdutos');
    const closeButton = modalImportacao.querySelector('.close');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            modalImportacao.style.display = 'none';
            document.getElementById('produtos-importacao-container').style.display = 'none';
        });
    }

    // Função para obter lista de fornecedores atuais
    function getFornecedoresAtuais() {
        const fornecedores = [];
        document.querySelectorAll('.fornecedor-section').forEach(section => {
            const nome = section.querySelector('.fornecedor-input')?.value;
            if (nome) {
                fornecedores.push(nome);
            }
        });
        return fornecedores;
    }

    // Modificar o comportamento do botão "Importar Novos Produtos"
    const btnImportar = document.getElementById('btn-importar-novos-produtos');
    if (btnImportar) {
        btnImportar.addEventListener('click', function() {
            const cotacaoId = document.getElementById('cotacaoId').value;
            const fileInput = document.getElementById('excelFile');
            
            if (!fileInput.files[0]) {
                alert('Selecione um arquivo para importar.');
                return;
            }
            
            if (cotacaoId) {
                // Se é uma cotação existente, processar o arquivo e abrir o modal
                const file = fileInput.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, {type: 'array'});
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        
                        // Converter a planilha para JSON
                        const produtos = XLSX.utils.sheet_to_json(firstSheet, {
                            header: 1,
                            raw: false,
                            defval: ''
                        });

                        // Pular a primeira linha (cabeçalho)
                        const produtosSemCabecalho = produtos.slice(1);

                        // Filtrar linhas vazias e obter dados das colunas C, D, E, F
                        const produtosProcessados = produtosSemCabecalho
                            .filter(row => row.length >= 6)
                            .map(row => ({
                                quantidade: row[2] || '1', // Coluna C
                                codigo: row[3] || '', // Coluna D
                                nome: row[4] || '', // Coluna E
                                unidade: row[5] || 'UN' // Coluna F
                            }))
                            .filter(produto => produto.codigo && produto.nome);

                        // Obter fornecedores atuais
                        const fornecedores = getFornecedoresAtuais();
                        
                        if (fornecedores.length === 0) {
                            alert('Adicione pelo menos um fornecedor antes de importar produtos.');
                            return;
                        }
                        
                        // Renderizar lista de produtos
                        const tbody = document.getElementById('produtos-importacao-lista');
                        tbody.innerHTML = '';
                        
                        produtosProcessados.forEach(produto => {
                            const fornecedoresExistentes = produtoJaExisteNaCotacao(produto.nome);
                            const fornecedoresAtuais = getFornecedoresAtuais();
                            
                            // Verificar se o produto já existe em todos os fornecedores
                            const existeEmTodosFornecedores = fornecedoresAtuais.every(
                                fornecedor => fornecedoresExistentes.includes(fornecedor)
                            );
                            
                            // Se o produto já existe em todos os fornecedores, não mostrar no modal
                            if (existeEmTodosFornecedores) {
                                return;
                            }
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>
                                    <input type="checkbox" class="produto-check" 
                                           data-codigo="${produto.codigo}"
                                           data-nome="${produto.nome}"
                                           data-quantidade="${produto.quantidade}"
                                           data-unidade="${produto.unidade}">
                                </td>
                                <td>${produto.nome}</td>
                                <td>${produto.codigo}</td>
                                <td>${produto.quantidade}</td>
                                <td>${produto.unidade}</td>
                                <td>
                                    <div class="fornecedor-checkbox-dropdown">
                                        <button type="button" class="btn-toggle-fornecedores">
                                            Selecionar fornecedores
                                            <span class="fornecedor-count">(0/0)</span>
                                        </button>
                                        <div class="fornecedor-checkbox-list" style="display:none;">
                                            ${fornecedores.map(f => `
                                                <label>
                                                    <input type="checkbox" class="fornecedor-checkbox" value="${f}" 
                                                           ${fornecedoresExistentes.includes(f) ? 'disabled' : ''}>
                                                    <span>${f}</span>
                                                    ${fornecedoresExistentes.includes(f) ? 
                                                        '<span class="fornecedor-status existe">Já existe</span>' : ''}
                                                </label>
                                            `).join('')}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    ${fornecedoresExistentes.length > 0 ? 
                                        `<span class="status-badge ja-existe">Já existe em: ${fornecedoresExistentes.join(', ')}</span>` : 
                                        '<span class="status-badge novo">Novo produto</span>'}
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });

                        // Mostrar container de produtos e abrir o modal
                        document.getElementById('produtos-importacao-container').style.display = 'block';
                        document.getElementById('modalImportacaoProdutos').style.display = 'block';
                    } catch (error) {
                        console.error('Erro ao processar arquivo:', error);
                        alert('Erro ao processar o arquivo. Verifique se o formato está correto.');
                    }
                };

                reader.readAsArrayBuffer(file);
            } else {
                // Se é uma nova cotação, manter o comportamento atual
                const file = fileInput.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, {type: 'array'});
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        
                        // Converter a planilha para JSON
                        const produtos = XLSX.utils.sheet_to_json(firstSheet, {
                            header: 1,
                            raw: false,
                            defval: ''
                        });

                        // Filtrar linhas vazias e obter dados das colunas C, D, E, F
                        const produtosProcessados = produtos
                            .filter(row => row.length >= 6) // Garantir que a linha tem pelo menos 6 colunas
                            .map(row => ({
                                quantidade: row[2] || '1', // Coluna C
                                codigo: row[3] || '', // Coluna D
                                nome: row[4] || '', // Coluna E
                                unidade: row[5] || 'UN' // Coluna F
                            }))
                            .filter(produto => produto.codigo && produto.nome); // Remover produtos sem código ou nome

                        // Adicionar produtos à primeira seção de fornecedor
                        const primeiraSecao = document.querySelector('.fornecedor-section');
                        if (primeiraSecao) {
                            const produtosContainer = primeiraSecao.querySelector('.produtos-fornecedor');
                            produtosProcessados.forEach(produto => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>
                                        <div class="produto-selected">${produto.nome}</div>
                                        <input type="hidden" class="produto-id" value="${produto.codigo}">
                                    </td>
                                    <td><input type="number" class="quantidade" value="${produto.quantidade}" required></td>
                                    <td class="unidade-medida">${produto.unidade}</td>
                                    <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
                                    <td class="valor-unit-difal-frete">0,0000</td>
                                    <td class="total">0,0000</td>
                                    <td>
                                        <button type="button" class="btn-remover-produto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                `;
                                produtosContainer.appendChild(tr);
                            });
                        }
                    } catch (error) {
                        console.error('Erro ao processar arquivo:', error);
                        alert('Erro ao processar o arquivo. Verifique se o formato está correto.');
                    }
                };

                reader.readAsArrayBuffer(file);
            }
        });
    }

    // Função para confirmar importação
    const btnConfirmar = document.getElementById('btn-confirmar-importacao');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            const produtosSelecionados = document.querySelectorAll('#produtos-importacao-lista tr .produto-check:checked');
            
            if (produtosSelecionados.length === 0) {
                alert('Selecione pelo menos um produto para importar.');
                return;
            }
            
            let algumFornecedorSelecionado = false;
            produtosSelecionados.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const fornecedorCheckboxes = row.querySelectorAll('.fornecedor-checkbox:checked');
                if (fornecedorCheckboxes.length > 0) {
                    algumFornecedorSelecionado = true;
                }
            });
            if (!algumFornecedorSelecionado) {
                alert('Selecione pelo menos um fornecedor para cada produto selecionado.');
                return;
            }

            produtosSelecionados.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const fornecedorCheckboxes = row.querySelectorAll('.fornecedor-checkbox:checked');
                fornecedorCheckboxes.forEach(fornecedorCheckbox => {
                    const fornecedorNome = fornecedorCheckbox.value;
                    // Encontrar a seção do fornecedor
                    const fornecedorSection = Array.from(document.querySelectorAll('.fornecedor-section'))
                        .find(section => section.querySelector('.fornecedor-input')?.value === fornecedorNome);
                    if (fornecedorSection) {
                        const produtosContainer = fornecedorSection.querySelector('.produtos-fornecedor');
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <div class="produto-selected">${checkbox.dataset.nome}</div>
                                <input type="hidden" class="produto-id" value="${checkbox.dataset.codigo}">
                            </td>
                            <td><input type="number" class="quantidade" value="${checkbox.dataset.quantidade}" required></td>
                            <td class="unidade-medida">${checkbox.dataset.unidade}</td>
                            <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
                            <td class="valor-unit-difal-frete">0,0000</td>
                            <td class="total">0,0000</td>
                            <td>
                                <button type="button" class="btn-remover-produto">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        produtosContainer.appendChild(tr);

                        // Adicionar eventos de cálculo
                        const valorInput = tr.querySelector('.valor-unitario');
                        const quantidadeInput = tr.querySelector('.quantidade');
                        
                        const recalcular = () => {
                            const quantidade = parseFloat(quantidadeInput.value) || 0;
                            const valor = parseFloat(valorInput.value) || 0;
                            tr.querySelector('.total').textContent = (quantidade * valor).toFixed(4);
                        };

                        valorInput.addEventListener('input', recalcular);
                        quantidadeInput.addEventListener('input', recalcular);
                        tr.querySelector('.btn-remover-produto').addEventListener('click', () => {
                            tr.remove();
                            recalcularTotais();
                        });
                    }
                });
            });

            // Fechar modal e limpar arquivo
            document.getElementById('modalImportacaoProdutos').style.display = 'none';
            document.getElementById('excelFile').value = '';
            document.getElementById('produtos-importacao-container').style.display = 'none';
        });
    }

    // Elementos do formulário
    const tipoCompraRadios = document.querySelectorAll('input[name="tipo_compra"]');
    const justificativaEmerencial = document.getElementById('justificativa-emergencial');
    const justificativaPadrao = document.getElementById('justificativa-padrao');
    const justificativaPersonalizadaContainer = document.getElementById('justificativa-personalizada-container');
    const justificativaPersonalizada = document.getElementById('justificativa-personalizada');

    // Mostrar/esconder campos de emergência
    tipoCompraRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            justificativaEmerencial.style.display = this.value === 'emergencial' ? 'block' : 'none';
            
            // Resetar campos quando mudar para programada
            if (this.value === 'programada') {
                justificativaPadrao.value = '';
                justificativaPersonalizada.value = '';
                justificativaPersonalizadaContainer.style.display = 'none';
            }
        });
    });

    // Mostrar/esconder campo de justificativa personalizada
    justificativaPadrao.addEventListener('change', function() {
        const fornecedorAtrasoDiv = document.getElementById('fornecedor-atraso');
        const fornecedorProblemaDiv = document.getElementById('fornecedor-problema');
        const justificativaPersonalizadaDiv = document.getElementById('justificativa-personalizada-container');
        
        // Esconder todos os campos específicos primeiro
        fornecedorAtrasoDiv.style.display = 'none';
        fornecedorProblemaDiv.style.display = 'none';
        justificativaPersonalizadaDiv.style.display = 'none';
        
        // Mostrar o campo apropriado baseado na seleção
        switch(this.value) {
            case 'atraso_fornecedor':
                fornecedorAtrasoDiv.style.display = 'block';
                break;
            case 'substituicao_reposicao':
                fornecedorProblemaDiv.style.display = 'block';
                break;
            case 'outros':
                justificativaPersonalizadaDiv.style.display = 'block';
                document.getElementById('justificativa-personalizada').placeholder = 'Descreva detalhadamente o(s) motivo(s) da compra emergencial';
                break;
            case 'aumento_consumo':
            case 'troca_cardapio':
            case 'implantacao':
            case 'substituicao_equipamento':
            case 'notificacao':
                justificativaPersonalizadaDiv.style.display = 'block';
                document.getElementById('justificativa-personalizada').placeholder = 'Descreva detalhadamente o motivo da compra emergencial';
                break;
        }
    });

    // Modificar a função de salvar para incluir os novos campos
    const formCotacao = document.getElementById('formCotacao');
    formCotacao.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar tipo de compra
        const tipoCompra = document.querySelector('input[name="tipo_compra"]:checked');
        if (!tipoCompra) {
            alert('Por favor, selecione o tipo de compra.');
            return;
        }
        
        const tipoCompraValue = tipoCompra.value;
        let motivoEmergencial = '';
        
        if (tipoCompraValue === 'emergencial') {
            const justificativaPadrao = document.getElementById('justificativa-padrao');
            if (!justificativaPadrao.value) {
                alert('Por favor, selecione o motivo da compra emergencial.');
                return;
            }
            
            switch(justificativaPadrao.value) {
                case 'atraso_fornecedor':
                    const fornecedorAtraso = document.getElementById('fornecedor-atraso-input').value.trim();
                    if (!fornecedorAtraso) {
                        alert('Por favor, informe o fornecedor com atraso.');
                        return;
                    }
                    motivoEmergencial = `Atraso fornecedor - ${fornecedorAtraso}`;
                    break;
                    
                case 'substituicao_reposicao':
                    const fornecedorProblema = document.getElementById('fornecedor-problema-input').value.trim();
                    if (!fornecedorProblema) {
                        alert('Por favor, informe o fornecedor que gerou o problema.');
                        return;
                    }
                    motivoEmergencial = `Substituição/Reposição de produtos - ${fornecedorProblema}`;
                    break;
                    
                case 'outros':
                    const justificativaOutros = document.getElementById('justificativa-personalizada').value.trim();
                    if (!justificativaOutros) {
                        alert('Por favor, descreva detalhadamente o(s) motivo(s) da compra emergencial.');
                        return;
                    }
                    motivoEmergencial = `Outro(s) - ${justificativaOutros}`;
                    break;
                    
                default:
                    const justificativaPersonalizada = document.getElementById('justificativa-personalizada').value.trim();
                    if (!justificativaPersonalizada) {
                        alert('Por favor, descreva detalhadamente o motivo da compra emergencial.');
                        return;
                    }
                    motivoEmergencial = justificativaPersonalizada;
            }
        }

        // Adicionar os novos campos ao FormData
        const formData = new FormData(this);
        formData.append('tipo', tipoCompraValue);
        formData.append('motivo_emergencial', motivoEmergencial);

        // Continuar com o envio do formulário
        // ... existing code ...
    });
});

document.addEventListener('click', function(e) {
    // Toggle dropdown de fornecedores
    if (e.target.classList.contains('btn-toggle-fornecedores')) {
        const dropdown = e.target.closest('.fornecedor-checkbox-dropdown');
        const list = dropdown.querySelector('.fornecedor-checkbox-list');
        const isOpen = list.style.display === 'block';
        
        // Fecha todos os outros abertos
        document.querySelectorAll('.fornecedor-checkbox-list').forEach(el => {
            el.style.display = 'none';
            el.previousElementSibling.classList.remove('active');
        });
        
        if (!isOpen) {
            list.style.display = 'block';
            e.target.classList.add('active');
            
            // Atualiza o contador de selecionados
            const selectedCount = list.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').length;
            const totalCount = list.querySelectorAll('input[type="checkbox"]:not(:disabled)').length;
            e.target.querySelector('.fornecedor-count').textContent = `(${selectedCount}/${totalCount})`;
        }
        e.stopPropagation();
    } else {
        // Fecha dropdown se clicar fora
        document.querySelectorAll('.fornecedor-checkbox-list').forEach(el => {
            el.style.display = 'none';
            el.previousElementSibling.classList.remove('active');
        });
    }
});

// Adicionar evento para atualizar contador quando checkboxes são alterados
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('fornecedor-checkbox')) {
        const dropdown = e.target.closest('.fornecedor-checkbox-dropdown');
        const button = dropdown.querySelector('.btn-toggle-fornecedores');
        const selectedCount = dropdown.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').length;
        const totalCount = dropdown.querySelectorAll('input[type="checkbox"]:not(:disabled)').length;
        button.querySelector('.fornecedor-count').textContent = `(${selectedCount}/${totalCount})`;
    }
});
</script>

<style>
/* Estilos para o Modal de Importação de Produtos */
#modalImportacaoProdutos .modal-content {
    width: 90%;
    max-width: 1200px;
}

#produtos-importacao-container {
    margin-top: 20px;
}

#produtos-importacao-container .table-container {
    max-height: 400px;
    overflow-y: auto;
    margin: 15px 0;
}

#produtos-importacao-container .tabela-produtos {
    width: 100%;
    border-collapse: collapse;
}

#produtos-importacao-container .tabela-produtos th,
#produtos-importacao-container .tabela-produtos td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

#produtos-importacao-container .tabela-produtos th {
    background-color: #f5f5f5;
    position: sticky;
    top: 0;
    z-index: 1;
}

#produtos-importacao-container .produto-check {
    width: 20px;
    height: 20px;
}

#produtos-importacao-container .fornecedor-select {
    width: 100%;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#produtos-importacao-container .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

#produtos-importacao-container .status-badge.ja-existe {
    background-color: #ffebee;
    color: #c62828;
}

#produtos-importacao-container .status-badge.novo {
    background-color: #e8f5e9;
    color: #2e7d32;
}

#produtos-importacao-container .form-actions {
    margin-top: 20px;
    text-align: right;
}

#produtos-importacao-container .form-actions button {
    margin-left: 10px;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

#produtos-importacao-container .btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

#produtos-importacao-container .btn-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

#produtos-importacao-container .btn-primary:hover,
#produtos-importacao-container .btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Estilo para linhas com produtos já existentes */
#produtos-importacao-container tr.disabled {
    background-color: #f5f5f5;
    opacity: 0.7;
}

/* Estilo para o checkbox desabilitado */
#produtos-importacao-container .produto-check:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Estilo para o select desabilitado */
#produtos-importacao-container .fornecedor-select:disabled {
    cursor: not-allowed;
    opacity: 0.5;
    background-color: #f5f5f5;
}

#produtos-importacao-container .fornecedor-checkbox-dropdown {
    position: relative;
    width: 100%;
}

.btn-toggle-fornecedores {
    width: 100%;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
}

.btn-toggle-fornecedores:hover {
    background: #e9ecef;
    border-color: #ced4da;
}

.btn-toggle-fornecedores::after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    margin-left: 8px;
    transition: transform 0.2s ease;
}

.btn-toggle-fornecedores.active::after {
    transform: rotate(180deg);
}

.fornecedor-checkbox-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-top: 4px;
    padding: 8px;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
}

.fornecedor-checkbox-list label {
    display: flex;
    align-items: center;
    padding: 6px 8px;
    margin: 2px 0;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.fornecedor-checkbox-list label:hover {
    background-color: #f8f9fa;
}

.fornecedor-checkbox-list input[type="checkbox"] {
    margin-right: 8px;
}

.fornecedor-checkbox-list input[type="checkbox"]:disabled + span {
    color: #6c757d;
    font-style: italic;
}

.fornecedor-checkbox-list .fornecedor-status {
    margin-left: auto;
    font-size: 0.85em;
    padding: 2px 6px;
    border-radius: 3px;
}

.fornecedor-checkbox-list .fornecedor-status.existe {
    background-color: #e9ecef;
    color: #495057;
}

/* Estilo para o contador de fornecedores selecionados */
.fornecedor-count {
    font-size: 0.85em;
    color: #6c757d;
    margin-left: 8px;
}

.radio-group {
    display: flex;
    gap: 20px;
    margin: 10px 0;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}

#justificativa-emergencial {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

#justificativa-emergencial .form-group {
    margin-bottom: 15px;
}

#justificativa-emergencial label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

#justificativa-emergencial select,
#justificativa-emergencial input,
#justificativa-emergencial textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#justificativa-emergencial select:focus,
#justificativa-emergencial input:focus,
#justificativa-emergencial textarea:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.required {
    color: #dc3545;
    margin-left: 4px;
}
</style>