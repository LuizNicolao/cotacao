<?php
// filepath: c:\Xampp\htdocs\cotacao\includes\modal_aprovacoes.php
?>
<link rel="stylesheet" href="assets/css/modal_aprovacoes.css">
<div id="modalAnalise" class="modal">
    <div class="modal-content modal-analise">
        <span class="close">×</span>
        <div class="analise-header">
            <div class="analise-info">
                <h3>Análise Detalhada de Cotação</h3>
                <div id="info-cotacao" class="info-cotacao">
                    <!-- Informações básicas da cotação serão inseridas aqui -->
                </div>
            </div>
        </div>


        <!-- Novo cabeçalho de resumo -->
<div class="resumo-orcamento">
    <h4>Resumo Orçamento Melhor Preço</h4>
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

<!-- Nova seção de comparação com última cotação -->
<div class="comparacao-cotacao">
    <h4>Comparação com Última Cotação Aprovada</h4>
    <div class="tabela-comparacao">
        <table>
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th>Valor Total Atual</th>
                    <th>Último Valor Total</th>
                    <th>Variação</th>
                </tr>
            </thead>
            <tbody id="comparacao-cotacao-body">
                <!-- Dados serão inseridos via JavaScript -->
            </tbody>
            <tfoot id="comparacao-cotacao-footer">
                <!-- Total geral será inserido via JavaScript -->
            </tfoot>
        </table>
    </div>
</div>

<!-- Botões de alternância -->
<div class="visualizacoes-toggle" style="display: flex; justify-content: center;">
<button id="btn-visualizacao-padrao" class="btn-view active" onclick="forcarVisualizacao('visualizacao-padrao')">
    <i class="fas fa-list"></i> Visualização Padrão
</button>
<button id="btn-visualizacao-melhor-preco" class="btn-view" onclick="forcarVisualizacao('visualizacao-melhor-preco')">
    <i class="fas fa-tag"></i> Melhor Preço
</button>
<button id="btn-visualizacao-melhor-entrega" class="btn-view" onclick="forcarVisualizacao('visualizacao-melhor-entrega')">
    <i class="fas fa-truck"></i> Melhor Prazo de Entrega
</button>
<button id="btn-visualizacao-melhor-pagamento" class="btn-view" onclick="forcarVisualizacao('visualizacao-melhor-pagamento')">
    <i class="fas fa-credit-card"></i> Melhor Prazo de Pagamento
</button>
</div>


        
<div class="view-content">
    <!-- Visualização padrão (atual) -->
    <div id="visualizacao-padrao" class="visualizacao-container">
        <div class="historico-versoes">
            <h4>Histórico de Versões</h4>
            <div class="versoes-list"></div>
        </div>
        
        <div class="detalhes-cotacao">
            <div class="resumo-geral">
                <!-- Cards com totais e informações gerais -->
            </div>
            <div class="tabela-comparativa">
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Ult. Vlr. Aprovado</th>
                            <th>Valor Anterior</th>
                            <th>Valor Unit.</th>
                            <th>Valor Unit. + Difal/Frete</th>
                            <th>Total</th>
                            <th>Variação %</th>
                            <th>Variação R$</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dados dinâmicos aqui -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Nova visualização: Melhor Preço -->
<div id="visualizacao-melhor-preco" class="visualizacao-container" style="display: none;">
    <div class="detalhes-cotacao">
        <h3>Itens com Melhor Preço</h3>
        <div class="tabela-melhor-preco">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Fornecedor</th>
                        <th>Qtd</th>
                        <th>Valor Unitário</th>
                        <th>Prazo Entrega</th>
                        <th>Melhor Prz Entrg | Fornecedor</th>
                        <th>Prazo Pagamento</th>
                        <th>Melhor Prz Pg | Fornecedor</th>
                    </tr>
                </thead>
                <tbody id="tabela-melhor-preco-body">
                    <!-- Dados dinâmicos aqui -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Nova visualização: Melhor Prazo de Entrega -->
<div id="visualizacao-melhor-entrega" class="visualizacao-container" style="display: none;">
    <div class="detalhes-cotacao">
        <h3>Itens com Melhor Prazo de Entrega</h3>
        <div class="tabela-melhor-entrega">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Fornecedor</th>
                        <th>Qtd</th>
                        <th>Prazo Entrega</th>
                        <th>Valor Unitário</th>
                        <th>Prazo Pagamento</th>
                    </tr>
                </thead>
                <tbody id="tabela-melhor-entrega-body">
                    <!-- Dados dinâmicos aqui -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Nova visualização: Melhor Prazo de Pagamento -->
<div id="visualizacao-melhor-pagamento" class="visualizacao-container" style="display: none;">
    <div class="detalhes-cotacao">
        <h3>Itens com Melhor Prazo de Pagamento</h3>
        <div class="tabela-melhor-pagamento">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Fornecedor</th>
                        <th>Qtd</th>
                        <th>Prazo Pagamento</th>
                        <th>Valor Unitário</th>
                        <th>Prazo Entrega</th>
                    </tr>
                </thead>
                <tbody id="tabela-melhor-pagamento-body">
                    <!-- Dados dinâmicos aqui -->
                </tbody>
            </table>
        </div>
    </div>
</div>


</div>

        <!-- Movido os botões para fora do cabeçalho e para o final do modal -->
        <div id="analise-acoes" class="analise-acoes-footer">
            <!-- Botões de ação serão inseridos aqui -->
        </div>
        
        <div id="motivo-rejeicao" class="motivo-rejeicao" style="display: none;">
            <h4>Motivo da Rejeição</h4>
            <textarea id="texto-motivo-rejeicao" placeholder="Informe o motivo da rejeição..."></textarea>
            <button id="btn-confirmar-rejeicao" class="btn-grande btn-rejeitar-grande" onclick="confirmarRejeicao(currentCotacaoId, document.getElementById('texto-motivo-rejeicao').value)">
                <i class="fas fa-times"></i> Confirmar Rejeição
            </button>
        </div>

        <!-- Adicionar nova seção para renegociação -->
        <div id="motivo-renegociacao" class="motivo-rejeicao" style="display: none;">
            <h4>Motivo da Renegociação</h4>
            <textarea id="texto-motivo-renegociacao" placeholder="Informe o motivo da renegociação e as sugestões de ajustes..."></textarea>
            <button id="btn-confirmar-renegociacao" class="btn-grande btn-renegociar-grande" onclick="renegociarCotacao(currentCotacaoId, document.getElementById('texto-motivo-renegociacao').value)">
    <i class="fas fa-sync-alt"></i> Confirmar Renegociação
</button>


        </div>

        <div id="motivo-aprovacao" class="motivo-rejeicao" style="display: none;">
    <h4>Aprovação de Cotação</h4>
   
    <div class="opcoes-aprovacao" style="display: flex; flex-wrap: wrap; gap: 5px;">
        <label>
            <input type="radio" name="tipo-aprovacao" id="aprovacao-manual" value="manual" checked>
            Selecionar itens manualmente
        </label>
        <label>
            <input type="radio" name="tipo-aprovacao" id="aprovacao-melhor-preco" value="melhor-preco">
            Aprovar automaticamente itens com melhor preço
        </label>
        <label>
            <input type="radio" name="tipo-aprovacao" id="aprovacao-melhor-prazo-entrega" value="melhor-prazo-entrega">
            Aprovar automaticamente itens com melhor prazo de entrega
        </label>
        <label>
            <input type="radio" name="tipo-aprovacao" id="aprovacao-melhor-prazo-pagamento" value="melhor-prazo-pagamento">
            Aprovar automaticamente itens com melhor prazo de pagamento
        </label>
    </div>
   
    <div id="selecao-manual-container">
        <div id="lista-itens-aprovacao">
            <!-- Itens para seleção manual serão inseridos aqui -->
        </div>
    </div>
   
    <div id="resumo-melhor-preco" style="display: none;">
        <div id="lista-melhor-preco">
            <!-- Itens com melhor preço serão inseridos aqui -->
        </div>
    </div>

    <div id="resumo-melhor-prazo-entrega" style="display: none;">
        <div id="lista-melhor-prazo-entrega">
            <!-- Itens com melhor prazo de entrega serão inseridos aqui -->
        </div>
    </div>

    <div id="resumo-melhor-prazo-pagamento" style="display: none;">
        <div id="lista-melhor-prazo-pagamento">
            <!-- Itens com melhor prazo de pagamento serão inseridos aqui -->
        </div>
    </div>
   
    <textarea id="texto-motivo-aprovacao" placeholder="Informe o motivo da aprovação..."></textarea>
    <button id="btn-confirmar-aprovacao" class="btn-grande btn-aprovar-grande" onclick="confirmarAprovacao()">
        <i class="fas fa-check"></i> Confirmar Aprovação
    </button>
</div>




        <!-- Add inside modalAnalise -->
        <div id="historico-versoes" class="historico-versoes" style="display: none;">
            <h4>Histórico de Versões</h4>
            <div class="versoes-container"></div>
        </div>
    </div>
</div>


<script>
let currentCotacaoId = null;
let cotacaoData = null;
let itensMelhorPreco = [];
let itensSelecionadosManualmente = [];
let itensMelhorPrazoEntrega = []; // Nova variável
let itensMelhorPrazoPagamento = []; // Nova variável

// Função para calcular o número de rodadas com base nas versões
function calcularRodadas(versoes) {
    if (!versoes || versoes.length === 0) {
        return 1; // Se não houver versões, é a primeira rodada
    }
    
    // Filtrar apenas as versões que representam renegociações
    // Uma renegociação ocorre quando o status muda para 'renegociacao' e depois volta para 'aguardando_aprovacao'
    let rodadas = 1; // Começa com 1 (a rodada inicial)
    let ultimoStatus = null;
    
    // Ordenar versões por data (da mais antiga para a mais recente)
    const versoesOrdenadas = [...versoes].sort((a, b) => {
        return new Date(a.data_criacao) - new Date(b.data_criacao);
    });
    
    // Contar transições de status que indicam uma nova rodada
    versoesOrdenadas.forEach(versao => {
        // Se o status mudou de 'renegociacao' para 'aguardando_aprovacao', é uma nova rodada
        if (ultimoStatus === 'renegociacao' && versao.status === 'aguardando_aprovacao') {
            rodadas++;
        }
        ultimoStatus = versao.status;
    });
    
    return rodadas;
}



// Função para analisar uma cotação
function analisarCotacao(id) {
    console.log('Iniciando análise da cotação:', id);
    
    Promise.all([
        fetch(`api/cotacoes.php?id=${id}`).then(response => {
            if (!response.ok) throw new Error('Erro ao buscar dados da cotação');
            return response.json();
        }),
        fetch(`api/cotacoes.php?id=${id}&versoes=true`).then(response => {
            if (!response.ok) throw new Error('Erro ao buscar versões da cotação');
            return response.json();
        })
    ])
    .then(([data, versoesData]) => {
        console.log('Dados completos da cotação:', data);
        console.log('Dados completos das versões:', versoesData);
        cotacaoData = data;

        // Obter o número da versão atual (que representa o número de rodadas)
        let numeroRodadas = 1; // Valor padrão

        console.log('versoesData:', versoesData);
        console.log('versao_atual:', versoesData.versao_atual);
        console.log('versoes array:', versoesData.versoes);

        if (versoesData.versao_atual) {
    numeroRodadas = versoesData.versao_atual;
    console.log('Usando versao_atual:', numeroRodadas);
    } else if (versoesData.versoes && versoesData.versoes.length > 0) {
    // Verificar os valores de versão no array
    const versaoNumeros = versoesData.versoes.map(v => {
        const versaoNum = parseInt(v.versao) || 1;
        console.log(`Versão ${v.versao} convertida para ${versaoNum}`);
        return versaoNum;
    });
    
    console.log('Array de números de versão:', versaoNumeros);
    numeroRodadas = Math.max(...versaoNumeros);
    console.log('Maior número de versão encontrado:', numeroRodadas);
    }

    console.log('Número final de rodadas:', numeroRodadas);
        
        // Preencher informações básicas
        document.getElementById('info-cotacao').innerHTML = `
            <p><strong>ID:</strong> ${data.id}</p>
            <p><strong>Comprador:</strong> ${data.usuario_nome}</p>
            <p><strong>Data Criação:</strong> ${formatarData(data.data_criacao)}</p>
            <p class="data-aprovacao"><strong>Data Aprovação/Rejeição:</strong> ${data.data_aprovacao ? formatarData(data.data_aprovacao) : 'Pendente'}</p>
            <p><strong>Status:</strong> <span
             class="status-badge ${data.status}">${traduzirStatus(data.status)}</span></p>
            <p><strong>Rodadas:</strong> <span class="rodadas-badge">${data.numero_rodadas || 1}</span></p>
        `;

        // Atualizar o resumo do orçamento
        atualizarResumoOrcamento(data);
        
        // Renderizar itens da cotação atual
        renderizarItensCotacaoParaRenegociacao(data);

        renderizarMelhorPreco();
        renderizarMelhorEntrega();
        renderizarMelhorPagamento();
        
        // Processar versões se existirem
        if (versoesData.versoes && versoesData.versoes.length > 0) {
            const versoes = versoesData.versoes;
            const versaoAtual = versoesData.versao_atual;
            
            renderizarHistoricoVersoes(versoes);
            
            // Comparar com versão anterior se houver mais de uma versão
            if (versoes.length > 1) {
                renderizarComparativo(versaoAtual, versoes[versoes.length - 2]);
            } else {
                console.log('Apenas uma versão disponível para comparação');
                document.getElementById('historico-versoes').innerHTML = '<p>Apenas uma versão disponível</p>';
            }
        } else {
            console.log('Nenhuma versão histórica disponível');
            document.getElementById('historico-versoes').innerHTML = '<p>Nenhuma versão histórica disponível</p>';
        }
        
        // Configurar botões de ação
        const acoesDiv = document.getElementById('analise-acoes');
        if (data.status === 'aguardando_aprovacao') {
            acoesDiv.innerHTML = `
                <button class="btn-grande btn-aprovar-grande" onclick="aprovarCotacao(${data.id})">
                    <i class="fas fa-check"></i> Aprovar Cotação
                </button>
                <button class="btn-grande btn-renegociar-grande" onclick="mostrarMotivoRenegociacao(${data.id})">
                    <i class="fas fa-sync-alt"></i> Renegociar Cotação
                </button>
                <button class="btn-grande btn-rejeitar-grande" onclick="mostrarMotivoRejeicao(${data.id})">
                    <i class="fas fa-times"></i> Rejeitar Cotação
                </button>
            `;
        } else {
            acoesDiv.innerHTML = '';
        }
        
        // Esconder o campo de motivo de rejeição
        document.getElementById('motivo-rejeicao').style.display = 'none';
        
        // Mostrar o modal
        document.getElementById('modalAnalise').style.display = 'block';

        // Adicionar a nova comparação de cotações
        atualizarComparacaoCotacao(data);
    })
    .catch(error => {
        console.error('Erro ao carregar detalhes da cotação:', error);
        alert('Erro ao carregar detalhes da cotação: ' + error.message);
    });
}

function renderizarItensCotacao(itens) {
    const tbody = document.querySelector('#itensCotacao tbody');
    tbody.innerHTML = '';

        itens.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.produto_nome}</td>
            <td>${item.qtd}</td>
            <td>${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : '-'}</td>
            <td>${item.ultimo_valor ? 'R$ ' + formatarNumero(item.ultimo_valor) : '-'}</td>
            <td>${item.valor_unitario ? 'R$ ' + formatarNumero(item.valor_unitario) : '-'}</td>
            <td>${item.valor_unitario_difal_frete ? 'R$ ' + formatarNumero(item.valor_unitario_difal_frete) : '-'}</td>
            <td>${item.total ? 'R$ ' + formatarNumero(item.total) : '-'}</td>
            <td>${item.variacao_percentual ? formatarNumero(item.variacao_percentual) + '%' : '-'}</td>
            <td>${item.variacao_reais ? 'R$ ' + formatarNumero(item.variacao_reais) : '-'}</td>
        `;
        tbody.appendChild(tr);
    });
}


// Função para mostrar o campo de motivo de aprovação
function mostrarMotivoAprovacao(id) {
    // Esconder outros campos de motivo que possam estar visíveis
    document.getElementById('motivo-rejeicao').style.display = 'none';
    document.getElementById('motivo-renegociacao').style.display = 'none';

    // Limpar seleções anteriores
    itensSelecionadosManualmente = [];
    itensMelhorPreco = [];
    itensMelhorPrazoEntrega = [];
    itensMelhorPrazoPagamento = [];

    // Mostrar o campo de motivo de aprovação
    const motivoAprovacaoDiv = document.getElementById('motivo-aprovacao');
    if (!motivoAprovacaoDiv) {
        console.error('Modal de aprovação não encontrado');
        return;
    }

    // Verificar e criar containers necessários dinamicamente
    const textoMotivoInput = document.getElementById('texto-motivo-aprovacao');

    const containers = [
        { id: 'selecao-manual-container', inner: '<div id="lista-itens-aprovacao"></div>' },
        { id: 'resumo-melhor-preco', inner: '<div id="lista-melhor-preco"></div>' },
        { id: 'resumo-melhor-prazo-entrega', inner: '<div id="lista-melhor-prazo-entrega"></div>' },
        { id: 'resumo-melhor-prazo-pagamento', inner: '<div id="lista-melhor-prazo-pagamento"></div>' }
    ];

    containers.forEach(cfg => {
        if (!document.getElementById(cfg.id)) {
            const container = document.createElement('div');
            container.id = cfg.id;
            container.style.display = 'none';
            container.innerHTML = cfg.inner;
            motivoAprovacaoDiv.insertBefore(container, textoMotivoInput);
        }
    });

    motivoAprovacaoDiv.style.display = 'block';

    // Limpar o texto anterior, se houver
    textoMotivoInput.value = '';

    // Armazenar o ID da cotação para uso posterior
    currentCotacaoId = id;

    // Preparar os itens para seleção manual
    prepararItensParaAprovacao();

    // Configurar os radio buttons
    document.getElementById('aprovacao-manual').addEventListener('change', function () {
        document.getElementById('selecao-manual-container').style.display = 'block';
        document.getElementById('resumo-melhor-preco').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-entrega').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-pagamento').style.display = 'none';
    });

    document.getElementById('aprovacao-melhor-preco').addEventListener('change', function () {
        document.getElementById('selecao-manual-container').style.display = 'none';
        document.getElementById('resumo-melhor-preco').style.display = 'block';
        document.getElementById('resumo-melhor-prazo-entrega').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-pagamento').style.display = 'none';
    });

    document.getElementById('aprovacao-melhor-prazo-entrega').addEventListener('change', function () {
        document.getElementById('selecao-manual-container').style.display = 'none';
        document.getElementById('resumo-melhor-preco').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-entrega').style.display = 'block';
        document.getElementById('resumo-melhor-prazo-pagamento').style.display = 'none';
    });

    document.getElementById('aprovacao-melhor-prazo-pagamento').addEventListener('change', function () {
        document.getElementById('selecao-manual-container').style.display = 'none';
        document.getElementById('resumo-melhor-preco').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-entrega').style.display = 'none';
        document.getElementById('resumo-melhor-prazo-pagamento').style.display = 'block';
    });

    // Calcular os itens com melhores critérios
    if (cotacaoData && cotacaoData.itens) {
        const produtosAgrupados = {};

        cotacaoData.itens.forEach(item => {
            const nomeProduto = item.produto_nome;
            if (!produtosAgrupados[nomeProduto]) {
                produtosAgrupados[nomeProduto] = [];
            }
            produtosAgrupados[nomeProduto].push(item);
        });

        Object.entries(produtosAgrupados).forEach(([nomeProduto, itens]) => {
            // Melhor preço
            const itensPorPreco = [...itens].sort((a, b) => parseFloat(a.valor_unitario) - parseFloat(b.valor_unitario));
            if (itensPorPreco.length > 0) {
                itensMelhorPreco.push({
                    produto_id: itensPorPreco[0].produto_id,
                    fornecedor_nome: itensPorPreco[0].fornecedor_nome
                });
            }

            // Melhor prazo de entrega
            const itensComPrazoEntrega = itens.filter(item => item.prazo_entrega && item.prazo_entrega.trim() !== '');
            if (itensComPrazoEntrega.length > 0) {
                const itensPorPrazoEntrega = [...itensComPrazoEntrega].sort((a, b) => {
                    const diasA = parseInt(a.prazo_entrega.match(/\d+/)[0] || 999);
                    const diasB = parseInt(b.prazo_entrega.match(/\d+/)[0] || 999);
                    return diasA - diasB;
                });

                itensMelhorPrazoEntrega.push({
                    produto_id: itensPorPrazoEntrega[0].produto_id,
                    fornecedor_nome: itensPorPrazoEntrega[0].fornecedor_nome
                });
            } else if (itens.length > 0) {
                itensMelhorPrazoEntrega.push({
                    produto_id: itens[0].produto_id,
                    fornecedor_nome: itens[0].fornecedor_nome
                });
            }

            // Melhor prazo de pagamento
            const itensComPrazoPagamento = itens.filter(item => item.prazo_pagamento && item.prazo_pagamento.trim() !== '');
            if (itensComPrazoPagamento.length > 0) {
                const itensPorPrazoPagamento = [...itensComPrazoPagamento].sort((a, b) => {
                    const diasA = parseInt(a.prazo_pagamento.match(/\d+/)[0] || 0);
                    const diasB = parseInt(b.prazo_pagamento.match(/\d+/)[0] || 0);
                    return diasB - diasA; // Ordem decrescente (maior prazo é melhor)
                });

                itensMelhorPrazoPagamento.push({
                    produto_id: itensPorPrazoPagamento[0].produto_id,
                    fornecedor_nome: itensPorPrazoPagamento[0].fornecedor_nome
                });
            } else if (itens.length > 0) {
                itensMelhorPrazoPagamento.push({
                    produto_id: itens[0].produto_id,
                    fornecedor_nome: itens[0].fornecedor_nome
                });
            }
        });

        console.log('Itens com melhor preço:', itensMelhorPreco);
        console.log('Itens com melhor prazo de entrega:', itensMelhorPrazoEntrega);
        console.log('Itens com melhor prazo de pagamento:', itensMelhorPrazoPagamento);
    }

    // Focar no campo de texto
    textoMotivoInput.focus();
}


function renderizarHistoricoVersoes(versoes) {
    const container = document.querySelector('.versoes-list');
    let html = '<div class="versoes-timeline">';
    
    // Show all versions in reverse order (newest first)
    versoes.reverse().forEach((versao, index) => {
        const isLatest = index === 0;
        const isOriginal = index === versoes.length - 1;
        
        html += `
            <div class="versao-item ${isLatest ? 'versao-atual' : ''} ${isOriginal ? 'versao-original' : ''}">
                <div class="versao-numero">
                    Versão ${versao.versao}
                    ${isLatest ? ' (Atual)' : ''}
                    ${isOriginal ? ' (Original)' : ''}
                </div>
                <div class="versao-data">${formatarData(versao.data_criacao)}</div>
                <div class="versao-usuario">por ${versao.usuario_nome}</div>
                <div class="versao-motivo">${versao.motivo_renegociacao || ''}</div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function renderizarComparativo(versaoAtual, versaoAnterior) {
    const container = document.querySelector('.tabela-comparativa');
    let html = `
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Ult. Vlr. Aprovado</th>
                    <th>Valor Anterior</th>
                    <th>Valor Unit.</th>
                    <th>Valor Unit. + Difal/Frete</th>
                    <th>Total</th>
                    <th>Variação %</th>
                </tr>
            </thead>
            <tbody>
    `;

    // Obter todos os produtos da versão atual
    const todosProdutos = versaoAtual.itens.map(i => i.produto_nome);
    const produtosUnicos = [...new Set(todosProdutos)];

    // Mapear menor preço por produto
    const menoresPrecos = {};
    produtosUnicos.forEach(nome => {
        const precos = versaoAtual.itens
            .filter(i => i.produto_nome === nome)
            .map(i => parseFloat(i.valor_unitario));
        menoresPrecos[nome] = Math.min(...precos);
    });

    // Agrupar por fornecedor
    const fornecedores = {};
    versaoAtual.itens.forEach(item => {
        if (!fornecedores[item.fornecedor_nome]) {
            fornecedores[item.fornecedor_nome] = {
                nome: item.fornecedor_nome,
                prazo_pagamento: item.prazo_pagamento,
                prazo_entrega: item.prazo_entrega,
                frete: item.frete,
                difal: item.difal,
                itens: []
            };
        }
        fornecedores[item.fornecedor_nome].itens.push(item);
    });

    // Renderizar comparativo por fornecedor
    Object.values(fornecedores).forEach(fornecedor => {
        html += `
            <div class="fornecedor-section">
                <div class="fornecedor-info">
                    <h4>${fornecedor.nome}</h4>
                    <div class="info-grid">
                        <div class="info-item"><strong>Pagamento:</strong> ${fornecedor.prazo_pagamento}</div>
                        <div class="info-item"><strong>Entrega:</strong> ${fornecedor.prazo_entrega}</div>
                        <div class="info-item"><strong>Frete:</strong> R$ ${formatarNumero(fornecedor.frete)}</div>
                        <div class="info-item"><strong>DIFAL:</strong> ${fornecedor.difal}%</div>
                        <div class="info-item"><strong>Valor Total:</strong> R$ ${formatarNumero(fornecedor.itens[0].valor_total || 0)}</div>
                    </div>
                </div>

                <table class="comparativo-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Ult. Vlr. Aprovado</th>
                            <th>Valor Anterior</th>
                            <th>Valor Atual</th>
                            <th>Variação</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${fornecedor.itens.map(itemAtual => {
                            const nome = itemAtual.produto_nome;
                            const valorAtual = parseFloat(itemAtual.valor_unitario);
                            const ehMelhorPreco = valorAtual === menoresPrecos[nome];

                            // Determinar valor anterior
                            let valorAnterior = itemAtual.valor_anterior ? parseFloat(itemAtual.valor_anterior) : 0;
                            if (!valorAnterior && versaoAnterior) {
                                const itemAnterior = versaoAnterior.itens.find(i =>
                                    i.produto_nome === nome &&
                                    i.fornecedor_nome === itemAtual.fornecedor_nome
                                );
                                valorAnterior = itemAnterior ? parseFloat(itemAnterior.valor_unitario) : 0;
                            }

                            // Determinar variação com base no último valor aprovado
                            let variacao = 0;
                            let variacaoClass = 'variacao-neutra';
                            let variacaoTexto = '0%';
                            let variacaoReais = 'R$ 0,00';

                            if (itemAtual.ultimo_valor_aprovado && parseFloat(itemAtual.ultimo_valor_aprovado) > 0) {
                                const valorAtual = parseFloat(itemAtual.valor_unitario);
                                const ultimoValorAprovado = parseFloat(itemAtual.ultimo_valor_aprovado);
                                variacao = ((valorAtual - ultimoValorAprovado) / ultimoValorAprovado) * 100;
                                const variacaoValor = valorAtual - ultimoValorAprovado;

                                if (variacao > 0) {
                                    variacaoClass = 'variacao-positiva';
                                    variacaoTexto = `+${variacao.toFixed(2)}%`;
                                    variacaoReais = `+R$ ${formatarNumero(variacaoValor)}`;
                                } else if (variacao < 0) {
                                    variacaoClass = 'variacao-negativa';
                                    variacaoTexto = `${variacao.toFixed(2)}%`;
                                    variacaoReais = `-R$ ${formatarNumero(Math.abs(variacaoValor))}`;
                                } else {
                                    variacaoReais = 'R$ 0,00';
                                }
                            } else {
                                variacaoTexto = 'N/A';
                                variacaoReais = 'N/A';
                            }

                            const variacaoFormatada = `<span class="coluna-variacao ${variacaoClass}" title="Último valor aprovado: ${itemAtual.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(itemAtual.ultimo_valor_aprovado) : 'N/A'}">${variacaoTexto}</span>`;
                            const variacaoReaisFormatada = `<span class="coluna-variacao ${variacaoClass}" title="Último valor aprovado: ${itemAtual.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(itemAtual.ultimo_valor_aprovado) : 'N/A'}">${variacaoReais}</span>`;

                            return `
                                <tr>
                                    <td>${nome}</td>
                                    <td>${itemAtual.quantidade}</td>
                                    <td>${itemAtual.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(itemAtual.ultimo_valor_aprovado) : '-'}</td>
                                    <td>R$ ${formatarNumero(valorAnterior)}</td>
                                    <td class="${ehMelhorPreco ? 'melhor-preco' : ''}">R$ ${formatarNumero(valorAtual)}</td>
                                    <td class="${variacaoClass}" title="Último valor aprovado: ${itemAtual.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(itemAtual.ultimo_valor_aprovado) : 'N/A'}">${variacaoFormatada}</td>
                                    <td class="${variacaoClass}" title="Último valor aprovado: ${itemAtual.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(itemAtual.ultimo_valor_aprovado) : 'N/A'}">${variacaoReaisFormatada}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    });

    html += `
        </tbody>
    </table>
    `;

    container.innerHTML = html;
}


function renderizarItensCotacaoParaRenegociacao(data) {
    const container = document.querySelector('.tabela-comparativa tbody');
    let html = '';

    const cotacaoAprovada = data.status === 'aprovado';

    let itensParaExibir = data.itens;
    if (cotacaoAprovada) {
        itensParaExibir = data.itens.filter(item => item.aprovado === 1 || item.aprovado === '1' || item.aprovado === true);
        console.log('Exibindo apenas itens aprovados:', itensParaExibir.length, 'de', data.itens.length);
    }

    if (!itensParaExibir || itensParaExibir.length === 0) {
        container.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum item aprovado para exibição</td></tr>';
        return;
    }

    const produtosRenegociar = {};
    console.log('Dados completos:', data);
    console.log('Produtos para renegociar:', data.produtos_renegociar);

    if (data.produtos_renegociar && Array.isArray(data.produtos_renegociar)) {
        data.produtos_renegociar.forEach(item => {
            const prodId = String(item.produto_id);
            const fornecedor = String(item.fornecedor_nome);
            produtosRenegociar[`${prodId}_${fornecedor}`] = true;
            produtosRenegociar[`${prodId}`] = true;
        });
    }

    const itensPorFornecedor = {};
    itensParaExibir.forEach(item => {
        if (!itensPorFornecedor[item.fornecedor_nome]) {
            itensPorFornecedor[item.fornecedor_nome] = [];
        }
        itensPorFornecedor[item.fornecedor_nome].push(item);
    });

    Object.entries(itensPorFornecedor).forEach(([fornecedorNome, itens]) => {
        const fornecedorObj = {
            nome: fornecedorNome,
            difal: parseFloat(itens[0].difal || 0),
            frete: parseFloat(itens[0].frete || 0),
            itens: itens
        };

        const valorTotal = itens.reduce((total, item) => {
            const baseValue = item.quantidade * item.valor_unitario;
            const difalValue = baseValue * (item.difal / 100);
            return total + baseValue + difalValue;
        }, 0) + parseFloat(itens[0].frete || 0);

        html += `
            <tr class="fornecedor-header">
                <td colspan="7">
                    <h4>${fornecedorNome}</h4>
                    <div class="fornecedor-detalhes-linha">
                        <span><strong>Pagamento:</strong> ${itens[0].prazo_pagamento || 'N/A'}</span>
                        <span><strong>Entrega:</strong> ${itens[0].prazo_entrega || 'N/A'}</span>
                        <span><strong>Frete:</strong> R$ ${formatarNumero(itens[0].frete || 0)}</span>
                        <span><strong>DIFAL:</strong> ${itens[0].difal || '0'}%</span>
                        <span><strong>Valor Total:</strong> R$ ${formatarNumero(valorTotal)}</span>
                    </div>
                </td>
            </tr>
        `;

        itens.forEach(item => {
            const valorItemTotal = item.quantidade * item.valor_unitario;
            const produtoId = String(item.produto_id || item.produto_codigo || '');
            const fornecedorNomeStr = String(item.fornecedor_nome);
            const key1 = `${produtoId}_${fornecedorNomeStr}`;
            const key2 = `${produtoId}`;
            const estaMarcado = produtosRenegociar[key1] || produtosRenegociar[key2] ? 'checked' : '';
            const classeAprovado = cotacaoAprovada ? 'item-aprovado' : '';

            let valorUnitarioComDifalEFrete;
            try {
                valorUnitarioComDifalEFrete = calcularValorUnitarioComDifalEFrete(item, fornecedorObj);
            } catch (error) {
                console.error('Erro ao calcular valor unitário com DIFAL e frete:', error);
                const valorUnitario = parseFloat(item.valor_unitario) || 0;
                const difalPercentual = parseFloat(itens[0].difal || 0);
                const difalUnitario = (valorUnitario * difalPercentual) / 100;
                valorUnitarioComDifalEFrete = valorUnitario + difalUnitario;
            }

            const ultimoValor = item.ultimo_preco || item.valor_unitario;

            let variacao = 0;
            let variacaoClass = 'variacao-neutra';
            let variacaoTexto = '0%';
            let variacaoReais = 'R$ 0,00';

            // Novo cálculo da variação baseado no último valor aprovado
            if (item.ultimo_valor_aprovado && parseFloat(item.ultimo_valor_aprovado) > 0) {
                const valorAtual = parseFloat(item.valor_unitario);
                const ultimoValorAprovado = parseFloat(item.ultimo_valor_aprovado);
                variacao = ((valorAtual - ultimoValorAprovado) / ultimoValorAprovado) * 100;
                const variacaoValor = valorAtual - ultimoValorAprovado;

                if (variacao > 0) {
                    variacaoClass = 'variacao-positiva';
                    variacaoTexto = `+${variacao.toFixed(2)}%`;
                    variacaoReais = `+R$ ${formatarNumero(variacaoValor)}`;
                } else if (variacao < 0) {
                    variacaoClass = 'variacao-negativa';
                    variacaoTexto = `${variacao.toFixed(2)}%`;
                    variacaoReais = `-R$ ${formatarNumero(Math.abs(variacaoValor))}`;
                } else {
                    variacaoReais = 'R$ 0,00';
                }
            } else {
                variacaoTexto = 'N/A';
                variacaoReais = 'N/A';
            }

            const variacaoFormatada = `<span class="coluna-variacao ${variacaoClass}" title="Último valor aprovado: ${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : 'N/A'}">${variacaoTexto}</span>`;
            const variacaoReaisFormatada = `<span class="coluna-variacao ${variacaoClass}" title="Último valor aprovado: ${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : 'N/A'}">${variacaoReais}</span>`;

    // Obter o número de rodadas (ou 0 se não existir)
    const numRodadas = item.rodadas ? parseInt(item.rodadas) : 0;

    // Definir a classe CSS para o badge de rodadas
    let rodadasClass = 'rodadas-badge';
    if (numRodadas >= 3) {
        rodadasClass += ' rodadas-alta';
    } else if (numRodadas >= 2) {
        rodadasClass += ' rodadas-media';
    } else {
        rodadasClass += ' rodadas-baixa';
    }

    // Criar texto para o tooltip das rodadas
    let tooltipRodadas = '';
    if (numRodadas === 0) {
        tooltipRodadas = 'Primeira negociação';
    } else if (numRodadas === 1) {
        tooltipRodadas = '1 rodada de renegociação';
    } else {
        tooltipRodadas = `${numRodadas} rodadas de renegociação`;
    }

    const rodadasBadge = `<span class="${rodadasClass}" title="${tooltipRodadas}">${numRodadas}</span>`;

    // E então modifique a linha onde você adiciona o rodadasBadge ao HTML:
    html += `
        <tr class="${classeAprovado}">
            <td>
                ${!cotacaoAprovada ? `
                    <input
                        type="checkbox"
                        class="checkbox-renegociar"
                        data-produto-id="${produtoId}"
                        data-fornecedor="${fornecedorNomeStr}"
                        style="margin-right: 6px;"
                        ${estaMarcado}
                    >
                ` : ''}
                ${item.produto_nome} ${rodadasBadge}
            </td>
            <td>${item.quantidade}</td>
            <td>${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : '-'}</td>
            <td>R$ ${formatarNumero(ultimoValor)}</td>
            <td>R$ ${formatarNumero(item.valor_unitario)}</td>
            <td>R$ ${formatarNumero(valorUnitarioComDifalEFrete)}</td>
            <td>R$ ${formatarNumero(valorItemTotal)}</td>
            <td>${variacaoFormatada}</td>
            <td>${variacaoReaisFormatada}</td>
        </tr>
    `;


        });
    });

    container.innerHTML = html;

    if (cotacaoAprovada) {
        const style = document.createElement('style');
        style.textContent = `
            .item-aprovado {
                background-color: rgba(46, 204, 113, 0.1);
            }
            .item-aprovado:hover {
                background-color: rgba(46, 204, 113, 0.2);
            }
            .coluna-variacao {
                font-weight: bold;
            }
            .variacao-positiva {
                color: red;
            }
            .variacao-negativa {
                color: green;
            }
            .variacao-neutra {
                color: #888;
            }
        `;
        document.head.appendChild(style);
    }
}






function renegociarCotacao(id, motivoTexto) {
    let motivoRenegociacao = motivoTexto || document.getElementById('texto-motivo-renegociacao').value.trim();

    const produtosSelecionados = Array.from(document.querySelectorAll('.checkbox-renegociar:checked'))
        .map(cb => {
            const produtoId = parseInt(cb.dataset.produtoId);
            const fornecedorNome = cb.dataset.fornecedor?.trim();
            
            // Validação mais rigorosa dos dados
            if (isNaN(produtoId) || produtoId <= 0 || !fornecedorNome) {
                console.warn('Produto inválido ignorado:', { produtoId, fornecedorNome });
                return null;
            }
            
            return {
                produto_id: produtoId,
                fornecedor_nome: fornecedorNome
            };
        })
        .filter(p => p !== null);

    if (produtosSelecionados.length === 0) {
        alert('Selecione ao menos um produto válido para renegociar.');
        return;
    }

    if (!motivoRenegociacao) {
        alert('Por favor, informe o motivo da renegociação.');
        document.getElementById('texto-motivo-renegociacao').focus();
        return;
    }

    if (confirm('Tem certeza que deseja enviar esta cotação para renegociação?')) {
        // Buscar a cotação original para preservar os primeiros valores
        fetch(`api/cotacoes.php?id=${id}`)
            .then(response => response.json())
            .then(cotacaoOriginal => {
                const primeirosValores = {};
                cotacaoOriginal.itens.forEach(item => {
                    const chave = `${item.produto_id}_${item.fornecedor_nome}`;
                    primeirosValores[chave] = item.primeiro_valor || item.valor_unitario;
                });

                // Montar payload completo com os fornecedores e produtos
                const fornecedores = [];

                produtosSelecionados.forEach(p => {
                    let fornecedor = fornecedores.find(f => f.fornecedor_nome === p.fornecedor_nome);
                    if (!fornecedor) {
                        fornecedor = { fornecedor_nome: p.fornecedor_nome, produtos: [] };
                        fornecedores.push(fornecedor);
                    }

                    const chave = `${p.produto_id}_${p.fornecedor_nome}`;
                    fornecedor.produtos.push({
                        id: p.produto_id,
                        primeiro_valor: primeirosValores[chave] || null
                    });
                });

                const payload = {
                    id: id,
                    status: 'renegociacao',
                    motivo_renegociacao: motivoRenegociacao,
                    produtos_renegociar: produtosSelecionados,
                    fornecedores: fornecedores,
                    primeiros_valores: primeirosValores // Adicionando os primeiros valores ao payload
                };

                console.log('Enviando renegociação:', payload);

                fetch('api/cotacoes.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Cotação enviada para renegociação com sucesso!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erro ao enviar cotação para renegociação');
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar renegociação:', error);
                    alert('Erro ao processar requisição');
                });
            })
            .catch(error => {
                console.error('Erro ao buscar cotação original:', error);
                alert('Erro ao buscar dados da cotação');
            });
    }
}



function aprovarCotacao(id) {
    mostrarMotivoAprovacao(id);
}

function mostrarMotivoRejeicao(id) {
    console.log('Mostrando campo de motivo de rejeição para cotação', id);
    currentCotacaoId = id;
    
    // Exibir o campo de motivo
    const motivoDiv = document.getElementById('motivo-rejeicao');
    motivoDiv.style.display = 'block';
    
    // Limpar qualquer texto anterior
    document.getElementById('texto-motivo-rejeicao').value = '';
    
    // Rolar para o campo de motivo
    motivoDiv.scrollIntoView({ behavior: 'smooth' });
}

function rejeitarCotacao(id) {
    // Esconder outros campos de motivo que possam estar visíveis
    document.getElementById('motivo-aprovacao').style.display = 'none';
    document.getElementById('motivo-renegociacao').style.display = 'none';
    
    // Mostrar o campo de motivo de rejeição
    const motivoRejeicaoDiv = document.getElementById('motivo-rejeicao');
    motivoRejeicaoDiv.style.display = 'block';
    
    // Limpar o texto anterior, se houver
    document.getElementById('texto-motivo-rejeicao').value = '';
    
    // Armazenar o ID da cotação para uso posterior
    currentCotacaoId = id;
    
    // Focar no campo de texto
    document.getElementById('texto-motivo-rejeicao').focus();
    
    // O botão de confirmar rejeição já deve estar configurado no HTML para chamar
    // a função confirmarRejeicao(currentCotacaoId, motivo)
}

// Função para confirmar a rejeição após o preenchimento do motivo
function confirmarRejeicao(id, motivo) {
    // Validar se o motivo foi preenchido
    if (!motivo.trim()) {
        alert('Por favor, informe o motivo da rejeição.');
        return;
    }
    
    // Confirmação final
    if (confirm('Tem certeza que deseja rejeitar esta cotação?')) {
        fetch('api/cotacoes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: 'rejeitado',
                motivo_rejeicao: motivo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cotação rejeitada com sucesso!');
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao rejeitar cotação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar requisição');
        });
    }
}

let produtosSelecionadosParaRenegociacao = [];

function mostrarMotivoRenegociacao(id) {
    console.log('Mostrando campo de motivo de renegociação para cotação', id);
    
    // Esconder outros campos de motivo que possam estar visíveis
    document.getElementById('motivo-rejeicao').style.display = 'none';
    document.getElementById('motivo-aprovacao').style.display = 'none';
    
    // Mostrar o campo de motivo de renegociação
    const motivoDiv = document.getElementById('motivo-renegociacao');
    motivoDiv.style.display = 'block';
    
    // Limpar o texto anterior, se houver
    document.getElementById('texto-motivo-renegociacao').value = '';
    
    // Armazenar o ID da cotação para uso posterior
    currentCotacaoId = id;
    
    // Focar no campo de texto
    document.getElementById('texto-motivo-renegociacao').focus();
    
    // Verificar se os checkboxes já existem, se não, adicioná-los
    const linhasProdutos = document.querySelectorAll('.tabela-comparativa tbody tr:not(.fornecedor-header)');
    linhasProdutos.forEach(linha => {
        const primeiraCelula = linha.querySelector('td:first-child');
        if (primeiraCelula && !primeiraCelula.querySelector('.checkbox-renegociar')) {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'checkbox-renegociar';
            checkbox.dataset.produtoId = linha.dataset.produtoId || '';
            checkbox.dataset.fornecedor = linha.dataset.fornecedor || '';
            checkbox.style.marginRight = '6px';
            primeiraCelula.insertBefore(checkbox, primeiraCelula.firstChild);
        }
    });
    
    // Rolar para o campo de motivo
    motivoDiv.scrollIntoView({ behavior: 'smooth' });
}

// Funções auxiliares
function formatarData(dataString) {
    if (!dataString) return 'N/A';
    
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR');
}

function formatarNumero(valor) {
    if (valor === null || valor === undefined || isNaN(parseFloat(valor))) {
        return '0,000';
    }
    return parseFloat(valor).toFixed(4).replace('.', ',');
}

function traduzirStatus(status) {
    const traducoes = {
        'pendente': 'Pendente',
        'aguardando_aprovacao': 'Aguardando Aprovação',
        'aprovado': 'Aprovado',
        'rejeitado': 'Rejeitado',
        'renegociacao': 'Em Renegociação'
    };
    
    return traducoes[status] || status;
}

// Função para alternar entre as visualizações
function alternarVisualizacao(viewId) {
    console.log('Alternando para visualização:', viewId);
    
    // Esconder todas as visualizações definindo explicitamente o estilo inline
    document.querySelectorAll('.visualizacao-container').forEach(el => {
        el.style.display = 'none';
    });
    
    // Remover classe 'active' de todos os botões
    document.querySelectorAll('.btn-view').forEach(el => {
        el.classList.remove('active');
    });
    
    // Mostrar a visualização selecionada
    const viewElement = document.getElementById(viewId);
    if (viewElement) {
        viewElement.style.display = 'block';
        console.log('Exibindo elemento:', viewId);
        
        // Forçar a renderização dos dados na visualização selecionada
        if (viewId === 'visualizacao-melhor-preco') {
            console.log('Forçando renderização de melhor preço');
            renderizarMelhorPreco();
        } else if (viewId === 'visualizacao-melhor-entrega') {
            console.log('Forçando renderização de melhor entrega');
            renderizarMelhorEntrega();
        } else if (viewId === 'visualizacao-melhor-pagamento') {
            console.log('Forçando renderização de melhor pagamento');
            renderizarMelhorPagamento();
        }
    } else {
        console.error('Elemento não encontrado:', viewId);
    }
    
    // Adicionar classe 'active' ao botão correspondente
    const btnElement = document.getElementById('btn-' + viewId);
    if (btnElement) btnElement.classList.add('active');
    
    // Verificar se a alternância foi bem-sucedida
    setTimeout(() => {
        let visiveis = 0;
        document.querySelectorAll('.visualizacao-container').forEach(el => {
            if (el.style.display === 'block') visiveis++;
        });
        console.log(`Após alternância: ${visiveis} visualização(ões) visível(is)`);
    }, 100);
}

function renderizarMelhorPreco() {
    if (!cotacaoData || !cotacaoData.itens) {
        console.error('Dados da cotação não disponíveis');
        return;
    }
    
    const tbody = document.getElementById('tabela-melhor-preco-body');
    let html = '';
    let valorTotal = 0;
    
    // Agrupar produtos por nome
    const produtosAgrupados = {};
    
    cotacaoData.itens.forEach(item => {
        const nomeProduto = item.produto_nome;
        if (!produtosAgrupados[nomeProduto]) {
            produtosAgrupados[nomeProduto] = [];
        }
        produtosAgrupados[nomeProduto].push(item);
    });
    
    // Para cada produto, encontrar o item com melhor preço, melhor prazo de entrega e melhor prazo de pagamento
    Object.entries(produtosAgrupados).forEach(([nomeProduto, itens]) => {
        // Ordenar por valor unitário (menor para maior)
        const itensPorPreco = [...itens].sort((a, b) => parseFloat(a.valor_unitario) - parseFloat(b.valor_unitario));
        
        // Pegar o item com menor preço
        const melhorPrecoItem = itensPorPreco[0];
        
        // Calcular o valor total para este item
        const quantidade = parseFloat(melhorPrecoItem.quantidade || 0);
        const valorUnitario = parseFloat(melhorPrecoItem.valor_unitario || 0);
        const valorItem = quantidade * valorUnitario;
        valorTotal += valorItem;
        
        // Ordenar por prazo de entrega (menor para maior)
        const itensComPrazoEntrega = itens.filter(item => item.prazo_entrega && item.prazo_entrega.trim() !== '');
        let melhorPrazoEntregaItem = null;
        
        if (itensComPrazoEntrega.length > 0) {
            melhorPrazoEntregaItem = [...itensComPrazoEntrega].sort((a, b) => {
                const diasA = parseInt(a.prazo_entrega.match(/\d+/)[0] || 999);
                const diasB = parseInt(b.prazo_entrega.match(/\d+/)[0] || 999);
                return diasA - diasB;
            })[0];
        }
        
        // Ordenar por prazo de pagamento (maior para menor)
        const itensComPrazoPagamento = itens.filter(item => item.prazo_pagamento && item.prazo_pagamento.trim() !== '');
        let melhorPrazoPagamentoItem = null;
        
        if (itensComPrazoPagamento.length > 0) {
            melhorPrazoPagamentoItem = [...itensComPrazoPagamento].sort((a, b) => {
                const diasA = parseInt(a.prazo_pagamento.match(/\d+/)[0] || 0);
                const diasB = parseInt(b.prazo_pagamento.match(/\d+/)[0] || 0);
                return diasB - diasA; // Ordem decrescente (maior prazo é melhor)
            })[0];
        }
        
        html += `
            <tr>
                <td>${melhorPrecoItem.produto_nome}</td>
                <td>${melhorPrecoItem.fornecedor_nome}</td>
                <td>${melhorPrecoItem.quantidade}</td>
                <td>R$ ${formatarNumero(melhorPrecoItem.valor_unitario)}</td>
                <td>${melhorPrecoItem.prazo_entrega || 'Não informado'}</td>
                <td>
                    ${melhorPrazoEntregaItem ? 
                        `${melhorPrazoEntregaItem.prazo_entrega} | ${melhorPrazoEntregaItem.fornecedor_nome}` : 
                        'Não informado'}
                </td>
                <td>${melhorPrecoItem.prazo_pagamento || 'Não informado'}</td>
                <td>
                    ${melhorPrazoPagamentoItem ? 
                        `${melhorPrazoPagamentoItem.prazo_pagamento} | ${melhorPrazoPagamentoItem.fornecedor_nome}` : 
                        'Não informado'}
                </td>
            </tr>
        `;
    });
    
    // Adicionar linha de valor total
    html += `
        <tr class="total-row">
            <td colspan="2"><strong>Valor Total</strong></td>
            <td colspan="6"><strong>R$ ${formatarNumero(valorTotal)}</strong></td>
        </tr>
    `;
    
    tbody.innerHTML = html;
}

// Função para renderizar a visualização de melhor prazo de entrega
function renderizarMelhorEntrega() {
    if (!cotacaoData || !cotacaoData.itens) {
        console.error('Dados da cotação não disponíveis');
        return;
    }

    const tbody = document.getElementById('tabela-melhor-entrega-body');
    let html = '';
    let valorTotal = 0;

    // Agrupar produtos por nome
    const produtosAgrupados = {};

    cotacaoData.itens.forEach(item => {
        const nomeProduto = item.produto_nome;
        if (!produtosAgrupados[nomeProduto]) {
            produtosAgrupados[nomeProduto] = [];
        }
        produtosAgrupados[nomeProduto].push(item);
    });

    // Para cada produto, encontrar o item com melhor prazo de entrega
    Object.entries(produtosAgrupados).forEach(([nomeProduto, itens]) => {
        // Filtrar itens que têm prazo de entrega definido
        const itensComPrazo = itens.map(item => {
            let diasEntrega = 999;
            if (item.prazo_entrega && item.prazo_entrega.trim() !== '') {
                const match = item.prazo_entrega.match(/(\d+)/);
                diasEntrega = match ? parseInt(match[1]) : 999;
            }
            return { ...item, dias_entrega: diasEntrega };
        });

        // Ordenar por prazo de entrega (menor para maior)
        itensComPrazo.sort((a, b) => a.dias_entrega - b.dias_entrega);

        // Pegar o item com menor prazo de entrega
        const melhorItem = itensComPrazo[0];
        
        // Calcular o valor total para este item
        const quantidade = parseFloat(melhorItem.quantidade || 0);
        const valorUnitario = parseFloat(melhorItem.valor_unitario || 0);
        const valorItem = quantidade * valorUnitario;
        valorTotal += valorItem;

        const badge = melhorItem.dias_entrega !== 999 ? '<span class="badge-entrega">Entrega Rápida</span>' : '';

        html += `
            <tr class="indicador-entrega">
                <td>${melhorItem.produto_nome}</td>
                <td>${melhorItem.fornecedor_nome}</td>
                <td>${melhorItem.quantidade}</td>
                <td>
                    ${badge}
                    ${melhorItem.prazo_entrega || 'Não informado'}
                </td>
                <td>R$ ${formatarNumero(melhorItem.valor_unitario)}</td>
                <td>${melhorItem.prazo_pagamento || 'Não informado'}</td>
            </tr>
        `;
    });
    
    // Adicionar linha de valor total
    html += `
        <tr class="total-row">
            <td colspan="2"><strong>Valor Total</strong></td>
            <td colspan="4"><strong>R$ ${formatarNumero(valorTotal)}</strong></td>
        </tr>
    `;

    tbody.innerHTML = html;

    // Adicionar resumo com estatísticas (se necessário)
    if (typeof adicionarResumoEntrega === 'function') {
        adicionarResumoEntrega();
    }
}

// Função para renderizar a visualização de melhor prazo de pagamento
function renderizarMelhorPagamento() {
    if (!cotacaoData || !cotacaoData.itens) {
        console.error('Dados da cotação não disponíveis');
        return;
    }
    
    const tbody = document.getElementById('tabela-melhor-pagamento-body');
    let html = '';
    let valorTotal = 0;
    
    // Agrupar produtos por nome
    const produtosAgrupados = {};
    
    cotacaoData.itens.forEach(item => {
        const nomeProduto = item.produto_nome;
        if (!produtosAgrupados[nomeProduto]) {
            produtosAgrupados[nomeProduto] = [];
        }
        produtosAgrupados[nomeProduto].push(item);
    });
    
    // Para cada produto, encontrar o item com melhor prazo de pagamento
    Object.entries(produtosAgrupados).forEach(([nomeProduto, itens]) => {
        // Extrair dias de pagamento (assumindo formato "X dias" ou múltiplos como "30/60/90")
        itens.forEach(item => {
            if (item.prazo_pagamento && item.prazo_pagamento.trim() !== '') {
                // Pegar o último valor em caso de múltiplos prazos (ex: "30/60/90" -> 90)
                const prazos = item.prazo_pagamento.split('/');
                const ultimoPrazo = prazos[prazos.length - 1];
                const match = ultimoPrazo.match(/(\d+)/);
                item.dias_pagamento = match ? parseInt(match[1]) : 0;
            } else {
                item.dias_pagamento = 0;
            }
        });
        
        // Filtrar itens que têm prazo de pagamento definido (dias_pagamento > 0)
        const itensComPrazo = itens.filter(item => item.dias_pagamento > 0);
        
        if (itensComPrazo.length === 0) {
            // Se nenhum item tiver prazo definido, usar o primeiro item
            const melhorItem = itens[0];
            
            // Calcular o valor total para este item
            const quantidade = parseFloat(melhorItem.quantidade || 0);
            const valorUnitario = parseFloat(melhorItem.valor_unitario || 0);
            const valorItem = quantidade * valorUnitario;
            valorTotal += valorItem;
            
            html += `
                <tr>
                    <td>${melhorItem.produto_nome}</td>
                    <td>${melhorItem.fornecedor_nome}</td>
                    <td>${melhorItem.quantidade}</td>
                    <td>Não informado</td>
                    <td>R$ ${formatarNumero(melhorItem.valor_unitario)}</td>
                    <td>${melhorItem.prazo_entrega || 'Não informado'}</td>
                </tr>
            `;
            return;
        }
        
        // Ordenar por dias de pagamento (maior para menor)
        itensComPrazo.sort((a, b) => b.dias_pagamento - a.dias_pagamento);
        
        // Pegar o item com maior prazo de pagamento
        const melhorItem = itensComPrazo[0];
        
        // Calcular o valor total para este item
        const quantidade = parseFloat(melhorItem.quantidade || 0);
        const valorUnitario = parseFloat(melhorItem.valor_unitario || 0);
        const valorItem = quantidade * valorUnitario;
        valorTotal += valorItem;
        
        html += `
            <tr class="indicador-pagamento">
                <td>${melhorItem.produto_nome}</td>
                <td>${melhorItem.fornecedor_nome}</td>
                <td>${melhorItem.quantidade}</td>
                <td class="melhor-valor">
                    <span class="badge-pagamento">Melhor Prazo</span>
                    ${melhorItem.prazo_pagamento}
                </td>
                <td>R$ ${formatarNumero(melhorItem.valor_unitario)}</td>
                <td>${melhorItem.prazo_entrega || 'Não informado'}</td>
            </tr>
        `;
    });
    
    // Adicionar linha de valor total
    html += `
        <tr class="total-row">
            <td colspan="2"><strong>Valor Total</strong></td>
            <td colspan="4"><strong>R$ ${formatarNumero(valorTotal)}</strong></td>
        </tr>
    `;
    
    tbody.innerHTML = html;
    
    // Adicionar resumo com estatísticas (se a função existir)
    if (typeof adicionarResumoPagamento === 'function') {
        adicionarResumoPagamento();
    }
}

// Adicionar estilos CSS para a linha de total
const styleTotal = document.createElement('style');
styleTotal.textContent = `
    .total-row {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .total-row td {
        padding: 12px 15px;
        border-top: 2px solid #ddd;
    }
`;
document.head.appendChild(styleTotal);

// ... rest of existing code ...

// Make functions globally available
window.analisarCotacao = analisarCotacao;
window.aprovarCotacao = aprovarCotacao;
window.rejeitarCotacao = rejeitarCotacao;
window.renegociarCotacao = renegociarCotacao;
window.mostrarMotivoRejeicao = mostrarMotivoRejeicao;
window.mostrarMotivoRenegociacao = mostrarMotivoRenegociacao;
window.alternarVisualizacao = alternarVisualizacao;


// Configurar o botão de fechar o modal
document.querySelector('#modalAnalise .close').addEventListener('click', function() {
    document.getElementById('modalAnalise').style.display = 'none';
});

// Fechar o modal quando clicar fora dele
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalAnalise');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

// Configurar os botões de alternância de visualização
// Make sure all functions and event listeners are properly closed
document.addEventListener('DOMContentLoaded', function() {
    const closeButton = document.querySelector('#modalAnalise .close');
    if(closeButton) {
        closeButton.addEventListener('click', function() {
            document.getElementById('modalAnalise').style.display = 'none';
        });
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalAnalise');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}); // Close DOMContentLoaded


function forcarVisualizacao(viewId) {
    console.log('Forçando visualização:', viewId);
   
    // Obter referências a todos os containers
    const padrao = document.getElementById('visualizacao-padrao');
    const melhorPreco = document.getElementById('visualizacao-melhor-preco');
    const melhorEntrega = document.getElementById('visualizacao-melhor-entrega');
    const melhorPagamento = document.getElementById('visualizacao-melhor-pagamento');
   
    // Esconder TODOS explicitamente
    if (padrao) padrao.style.display = 'none';
    if (melhorPreco) melhorPreco.style.display = 'none';
    if (melhorEntrega) melhorEntrega.style.display = 'none';
    if (melhorPagamento) melhorPagamento.style.display = 'none';
   
    // Mostrar APENAS o selecionado
    const selecionado = document.getElementById(viewId);
    if (selecionado) {
        selecionado.style.display = 'block';
        console.log(`Elemento ${viewId} agora está visível`);
    }
   
    // Atualizar classes dos botões
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.classList.remove('active');
    });
   
    const btnAtivo = document.getElementById('btn-' + viewId);
    if (btnAtivo) btnAtivo.classList.add('active');
   
    // Renderizar dados e adicionar resumos conforme necessário
    if (viewId === 'visualizacao-melhor-preco') {
        renderizarMelhorPreco();
        adicionarResumoPreco();
    } else if (viewId === 'visualizacao-melhor-entrega') {
        renderizarMelhorEntrega();

    } else if (viewId === 'visualizacao-melhor-pagamento') {
        renderizarMelhorPagamento();

    }
   
    // Verificar resultado
    setTimeout(() => {
        let visiveis = [];
        ['visualizacao-padrao', 'visualizacao-melhor-preco', 'visualizacao-melhor-entrega', 'visualizacao-melhor-pagamento'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.style.display === 'block') {
                visiveis.push(id);
            }
        });
        console.log('Visualizações visíveis após forçar:', visiveis);
    }, 100);
}

function adicionarResumoPreco() {
    if (!cotacaoData || !cotacaoData.itens) return;
    
    // Calcular economia total potencial
    let economiaTotal = 0;
    let valorTotalMelhorPreco = 0;
    let valorTotalMedio = 0;
    
    // Agrupar produtos por nome
    const produtosAgrupados = {};
    cotacaoData.itens.forEach(item => {
        const nomeProduto = item.produto_nome;
        if (!produtosAgrupados[nomeProduto]) {
            produtosAgrupados[nomeProduto] = [];
        }
        produtosAgrupados[nomeProduto].push(item);
    });
    
    // Calcular economia para cada produto
    Object.values(produtosAgrupados).forEach(itens => {
        // Ordenar por valor unitário (menor para maior)
        itens.sort((a, b) => parseFloat(a.valor_unitario) - parseFloat(b.valor_unitario));
        
        const melhorPreco = parseFloat(itens[0].valor_unitario);
        const precoMedio = itens.reduce((sum, item) => sum + parseFloat(item.valor_unitario), 0) / itens.length;
        const quantidade = parseInt(itens[0].quantidade) || 1;
        
        valorTotalMelhorPreco += melhorPreco * quantidade;
        valorTotalMedio += precoMedio * quantidade;
    });
    
    economiaTotal = valorTotalMedio - valorTotalMelhorPreco;
    const economiaPorcentagem = valorTotalMedio > 0 ? (economiaTotal / valorTotalMedio * 100) : 0;
    
    // Criar resumo
    const container = document.querySelector('#visualizacao-melhor-preco .detalhes-cotacao');
    if (!container) return;
    
    let resumo = container.querySelector('.resumo-geral');
    if (!resumo) {
        resumo = document.createElement('div');
        resumo.className = 'resumo-geral';
        container.insertBefore(resumo, container.firstChild);
    }
    
    resumo.innerHTML = `

    `;
}


function criarGraficoPreco() {
    if (!cotacaoData || !cotacaoData.itens) return;
    
    // Preparar dados para o gráfico
    const produtosUnicos = [...new Set(cotacaoData.itens.map(item => item.produto_nome))];
    const datasets = [];
    
    // Para cada produto, encontrar o melhor e o preço médio
    produtosUnicos.forEach(produto => {
        const itens = cotacaoData.itens.filter(item => item.produto_nome === produto);
        const precos = itens.map(item => parseFloat(item.valor_unitario));
        const melhorPreco = Math.min(...precos);
        const precoMedio = precos.reduce((a, b) => a + b, 0) / precos.length;
        
        datasets.push({
            produto: produto,
            melhorPreco: melhorPreco,
            precoMedio: precoMedio
        });
    });
    
    // Limitar a 5 produtos para não sobrecarregar o gráfico
    const dadosGrafico = datasets.slice(0, 5);
    
    // Criar o gráfico
    const ctx = document.getElementById('grafico-preco').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dadosGrafico.map(d => d.produto),
            datasets: [
                {
                    label: 'Melhor Preço',
                    data: dadosGrafico.map(d => d.melhorPreco),
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Preço Médio',
                    data: dadosGrafico.map(d => d.precoMedio),
                    backgroundColor: 'rgba(149, 165, 166, 0.7)',
                    borderColor: 'rgba(149, 165, 166, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Comparação de Preços'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor (R$)'
                    }
                }
            }
        }
    });
}

// Função para preparar os itens para aprovação
function prepararItensParaAprovacao() {
    itensMelhorPreco = [];
    itensSelecionadosManualmente = [];
    itensMelhorPrazoEntrega = [];
    itensMelhorPrazoPagamento = [];
    if (!cotacaoData || !cotacaoData.itens) {
        console.error('Dados da cotação não disponíveis');
        return;
    }
    
    // Agrupar produtos por nome
    const produtosAgrupados = {};
    
    cotacaoData.itens.forEach(item => {
        const nomeProduto = item.produto_nome;
        if (!produtosAgrupados[nomeProduto]) {
            produtosAgrupados[nomeProduto] = [];
        }
        produtosAgrupados[nomeProduto].push(item);
    });
    
    // Limpar arrays
    itensMelhorPreco = [];
    itensSelecionadosManualmente = [];
    itensMelhorPrazoEntrega = [];
    itensMelhorPrazoPagamento = [];
    
    // Containers para os itens
    const listaItensAprovacao = document.getElementById('lista-itens-aprovacao');
    const listaMelhorPreco = document.getElementById('lista-melhor-preco');
    const listaMelhorPrazoEntrega = document.getElementById('lista-melhor-prazo-entrega');
    const listaMelhorPrazoPagamento = document.getElementById('lista-melhor-prazo-pagamento');
    
    // Verificar se os elementos existem
    if (!listaItensAprovacao || !listaMelhorPreco || !listaMelhorPrazoEntrega || !listaMelhorPrazoPagamento) {
        console.error('Alguns elementos de lista não foram encontrados no DOM:', {
            listaItensAprovacao: !!listaItensAprovacao,
            listaMelhorPreco: !!listaMelhorPreco,
            listaMelhorPrazoEntrega: !!listaMelhorPrazoEntrega,
            listaMelhorPrazoPagamento: !!listaMelhorPrazoPagamento
        });
        return;
    }
    
    // Limpar containers
    listaItensAprovacao.innerHTML = '';
    listaMelhorPreco.innerHTML = '';
    listaMelhorPrazoEntrega.innerHTML = '';
    listaMelhorPrazoPagamento.innerHTML = '';
    
    // Para cada produto, encontrar o item com melhor preço, prazo de entrega e prazo de pagamento
    Object.entries(produtosAgrupados).forEach(([nomeProduto, itens]) => {
        // ===== MELHOR PREÇO =====
        // Ordenar por valor unitário (menor para maior)
        const itensPorPreco = [...itens].sort((a, b) => parseFloat(a.valor_unitario) - parseFloat(b.valor_unitario));
        const melhorItemPreco = itensPorPreco[0];
        
        // Adicionar à lista de melhores preços
        itensMelhorPreco.push({
            produto_id: melhorItemPreco.produto_codigo,
            fornecedor_nome: melhorItemPreco.fornecedor_nome,
            valor_unitario: melhorItemPreco.valor_unitario,
            produto_nome: melhorItemPreco.produto_nome
        });
        
        // Adicionar à lista de visualização de melhores preços
        const itemMelhorPrecoHTML = `
            <div class="item-selecao">
                <span class="melhor-preco-badge">Melhor Preço</span>
                <label>
                    ${melhorItemPreco.produto_nome}
                    <div class="preco">R$ ${formatarNumero(melhorItemPreco.valor_unitario)}</div>
                    <div class="fornecedor">Fornecedor: ${melhorItemPreco.fornecedor_nome}</div>
                </label>
            </div>
        `;
        listaMelhorPreco.innerHTML += itemMelhorPrecoHTML;
        
        // ===== MELHOR PRAZO DE ENTREGA =====
        // Filtrar itens com prazo de entrega definido
        const itensComPrazoEntrega = itens.filter(item => item.prazo_entrega && item.prazo_entrega.trim() !== '');
        let melhorItemPrazoEntrega;
        
        if (itensComPrazoEntrega.length > 0) {
            // Ordenar por prazo de entrega (menor para maior)
            const itensPorPrazoEntrega = [...itensComPrazoEntrega].sort((a, b) => {
                const diasA = parseInt(a.prazo_entrega.match(/\d+/)[0] || 999);
                const diasB = parseInt(b.prazo_entrega.match(/\d+/)[0] || 999);
                return diasA - diasB;
            });
            melhorItemPrazoEntrega = itensPorPrazoEntrega[0];
        } else {
            // Se nenhum item tiver prazo definido, usar o primeiro item
            melhorItemPrazoEntrega = itens[0];
        }
        
        // Adicionar à lista de melhores prazos de entrega
        itensMelhorPrazoEntrega.push({
            produto_id: melhorItemPrazoEntrega.produto_codigo,
            fornecedor_nome: melhorItemPrazoEntrega.fornecedor_nome,
            valor_unitario: melhorItemPrazoEntrega.valor_unitario,
            produto_nome: melhorItemPrazoEntrega.produto_nome
        });
        
        // Adicionar à lista de visualização de melhores prazos de entrega
        const itemMelhorPrazoEntregaHTML = `
            <div class="item-selecao">
                <span class="melhor-prazo-badge">Melhor Prazo de Entrega</span>
                <label>
                    ${melhorItemPrazoEntrega.produto_nome}
                    <div class="prazo">Prazo: ${melhorItemPrazoEntrega.prazo_entrega || 'Não informado'}</div>
                    <div class="preco">R$ ${formatarNumero(melhorItemPrazoEntrega.valor_unitario)}</div>
                    <div class="fornecedor">Fornecedor: ${melhorItemPrazoEntrega.fornecedor_nome}</div>
                </label>
            </div>
        `;
        listaMelhorPrazoEntrega.innerHTML += itemMelhorPrazoEntregaHTML;
        
        // ===== MELHOR PRAZO DE PAGAMENTO =====
        // Filtrar itens com prazo de pagamento definido
        const itensComPrazoPagamento = itens.filter(item => item.prazo_pagamento && item.prazo_pagamento.trim() !== '');
        let melhorItemPrazoPagamento;
        
        if (itensComPrazoPagamento.length > 0) {
            // Ordenar por prazo de pagamento (maior para menor)
            const itensPorPrazoPagamento = [...itensComPrazoPagamento].sort((a, b) => {
                const diasA = parseInt(a.prazo_pagamento.match(/\d+/)[0] || 0);
                const diasB = parseInt(b.prazo_pagamento.match(/\d+/)[0] || 0);
                return diasB - diasA; // Ordem decrescente (maior prazo é melhor)
            });
            melhorItemPrazoPagamento = itensPorPrazoPagamento[0];
        } else {
            // Se nenhum item tiver prazo definido, usar o primeiro item
            melhorItemPrazoPagamento = itens[0];
        }
        
        // Adicionar à lista de melhores prazos de pagamento
        itensMelhorPrazoPagamento.push({
            produto_id: melhorItemPrazoPagamento.produto_codigo,
            fornecedor_nome: melhorItemPrazoPagamento.fornecedor_nome,
            valor_unitario: melhorItemPrazoPagamento.valor_unitario,
            produto_nome: melhorItemPrazoPagamento.produto_nome
        });
        
        // Adicionar à lista de visualização de melhores prazos de pagamento
        const itemMelhorPrazoPagamentoHTML = `
            <div class="item-selecao">
                <span class="melhor-pagamento-badge">Melhor Prazo de Pagamento</span>
                <label>
                    ${melhorItemPrazoPagamento.produto_nome}
                    <div class="prazo">Prazo: ${melhorItemPrazoPagamento.prazo_pagamento || 'Não informado'}</div>
                    <div class="preco">R$ ${formatarNumero(melhorItemPrazoPagamento.valor_unitario)}</div>
                    <div class="fornecedor">Fornecedor: ${melhorItemPrazoPagamento.fornecedor_nome}</div>
                </label>
            </div>
        `;
        listaMelhorPrazoPagamento.innerHTML += itemMelhorPrazoPagamentoHTML;
        
        // ===== SELEÇÃO MANUAL =====
        // Adicionar todos os itens à lista de seleção manual
        itens.forEach((item, index) => {
            const ehMelhorPreco = index === 0; // O primeiro item é o de melhor preço
            
            const itemHTML = `
                <div class="item-selecao">
                    <input type="checkbox" id="item-${item.produto_codigo}-${item.fornecedor_nome.replace(/\s+/g, '-')}" 
                           data-produto-id="${item.produto_codigo}"
                           data-fornecedor="${item.fornecedor_nome}"
                           data-valor="${item.valor_unitario}"
                           data-produto-nome="${item.produto_nome}"
                           ${ehMelhorPreco ? 'checked' : ''}>
                    <label for="item-${item.produto_codigo}-${item.fornecedor_nome.replace(/\s+/g, '-')}">
                        ${item.produto_nome}
                        <div class="preco">R$ ${formatarNumero(item.valor_unitario)}</div>
                        <div class="fornecedor">Fornecedor: ${item.fornecedor_nome}</div>
                        ${ehMelhorPreco ? '<span class="melhor-preco-badge">Melhor Preço</span>' : ''}
                    </label>
                </div>
            `;
            listaItensAprovacao.innerHTML += itemHTML;
            
            // Se for o melhor preço, adicionar à lista de selecionados por padrão
            if (ehMelhorPreco) {
                itensSelecionadosManualmente.push({
                    produto_id: item.produto_codigo,
                    fornecedor_nome: item.fornecedor_nome,
                    valor_unitario: item.valor_unitario,
                    produto_nome: item.produto_nome
                });
            }
        });
    });
    
    // Adicionar event listeners para os checkboxes
    document.querySelectorAll('#lista-itens-aprovacao input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log('Checkbox alterado:', this.checked, this.dataset);
            atualizarItensSelecionadosManualmente();
        });
    });
}

// Função para atualizar a lista de itens selecionados manualmente

function atualizarItensSelecionadosManualmente() {
    itensSelecionadosManualmente = [];
    
    const checkboxes = document.querySelectorAll('#lista-itens-aprovacao input[type="checkbox"]:checked');
    console.log('Total de checkboxes selecionados:', checkboxes.length);
    
    checkboxes.forEach(checkbox => {
        const item = {
            produto_id: checkbox.dataset.produtoId,
            fornecedor_nome: checkbox.dataset.fornecedor,
            valor_unitario: checkbox.dataset.valor,
            produto_nome: checkbox.dataset.produtoNome
        };
        console.log('Adicionando item:', item);
        itensSelecionadosManualmente.push(item);
    });
    
    console.log('Total de itens selecionados manualmente:', itensSelecionadosManualmente.length);
    console.log('Itens selecionados manualmente:', itensSelecionadosManualmente);
}

// Função para confirmar a aprovação
// Função para confirmar a aprovação
function confirmarAprovacao() {
    const motivo = document.getElementById('texto-motivo-aprovacao').value.trim();
   
    // Validar se o motivo foi preenchido
    if (!motivo) {
        alert('Por favor, informe o motivo da aprovação.');
        return;
    }
   
    // Determinar o tipo de aprovação selecionado
    const tipoAprovacao = document.querySelector('input[name="tipo-aprovacao"]:checked').value;
   
    // Determinar quais itens serão aprovados
    let itensAprovados = [];
    
    switch (tipoAprovacao) {
        case 'manual':
            // Atualizar a lista de itens selecionados manualmente antes de usar
            atualizarItensSelecionadosManualmente();
            itensAprovados = [...itensSelecionadosManualmente];
            console.log('Itens a serem aprovados (manual):', itensAprovados);
            break;
        case 'melhor-preco':
            itensAprovados = [...itensMelhorPreco];
            break;
        case 'melhor-prazo-entrega':
            itensAprovados = [...itensMelhorPrazoEntrega];
            break;
        case 'melhor-prazo-pagamento':
            itensAprovados = [...itensMelhorPrazoPagamento];
            break;
    }
   
    // Remover duplicatas (se houver)
    const itensUnicos = [];
    const itemsMap = new Map();
    
    for (const item of itensAprovados) {
        // Criar uma chave única baseada no produto e fornecedor
        const key = `${item.produto_nome}_${item.fornecedor_nome}`;
        if (!itemsMap.has(key)) {
            itemsMap.set(key, true);
            itensUnicos.push(item);
        }
    }
    
    // Usar apenas itens únicos
    itensAprovados = itensUnicos;
    
    console.log('Total de itens a serem aprovados:', itensAprovados.length);
    console.log('Itens a serem aprovados:', itensAprovados);
    
    // Verificar se há itens selecionados
    if (itensAprovados.length === 0) {
        alert('Por favor, selecione pelo menos um item para aprovação.');
        return;
    }
   
    // Confirmar a aprovação
    if (confirm(`Tem certeza que deseja aprovar ${itensAprovados.length} item${itensAprovados.length > 1 ? 's' : ''}?`)) {
        const payload = {
            id: currentCotacaoId,
            status: 'aprovado',
            motivo_aprovacao: motivo,
            itens_aprovados: itensAprovados,
            tipo_aprovacao: tipoAprovacao
        };
       
        console.log('Enviando aprovação:', payload);
       
        fetch('api/cotacoes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cotação aprovada com sucesso!');
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao aprovar cotação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar requisição');
        });
    }
}




// Adicione esta função no bloco <script> do arquivo modal_aprovacoes.php
function calcularValorUnitarioComDifalEFrete(item, fornecedor) {
    // Obter valores básicos
    const valorUnitario = parseFloat(item.valor_unitario) || 0;
    const difalPercentual = parseFloat(fornecedor.difal || 0);
    const freteTotalFornecedor = parseFloat(fornecedor.frete || 0);
    
    // Calcular DIFAL por unidade
    const difalUnitario = (valorUnitario * difalPercentual) / 100;
    
    // Calcular valor total de produtos para determinar proporções
    let valorTotalProdutos = 0;
    fornecedor.itens.forEach(i => {
        const qtd = parseFloat(i.quantidade) || 0;
        const val = parseFloat(i.valor_unitario) || 0;
        valorTotalProdutos += qtd * val;
    });
    
    // Calcular a proporção deste item no total
    const quantidade = parseFloat(item.quantidade) || 0;
    const proporcaoItem = valorTotalProdutos > 0 ? 
        ((quantidade * valorUnitario) / valorTotalProdutos) : 0;
    
    // Calcular o frete proporcional para este item
    const freteProporcionalItem = freteTotalFornecedor * proporcaoItem;
    
    // Calcular o frete por unidade
    const fretePorUnidade = quantidade > 0 ? freteProporcionalItem / quantidade : 0;
    
    // Retornar o valor unitário com DIFAL e frete
    return valorUnitario + difalUnitario + fretePorUnidade;
}


// Função para calcular e exibir o resumo do orçamento
// Função para calcular e exibir o resumo do orçamento
function atualizarResumoOrcamento(data) {
    if (!data || !data.itens) {
        console.error('Dados inválidos para resumo do orçamento');
        return;
    }
    
    // Verificar se a cotação está aprovada
    const cotacaoAprovada = data.status === 'aprovado';
    
    // Se a cotação estiver aprovada, filtrar apenas os itens aprovados
    let itensParaCalculo = data.itens;
    if (cotacaoAprovada) {
        itensParaCalculo = data.itens.filter(item => item.aprovado === 1 || item.aprovado === '1' || item.aprovado === true);
        console.log('Calculando resumo apenas com itens aprovados:', itensParaCalculo.length, 'de', data.itens.length);
    }
    
    // Encontrar o melhor preço para cada produto
    const melhoresPrecos = {};
    itensParaCalculo.forEach(item => {
        const produtoNome = item.produto_nome;
        const valorUnitario = parseFloat(item.valor_unitario) || 0;
        
        if (!melhoresPrecos[produtoNome] || valorUnitario < melhoresPrecos[produtoNome].valor) {
            melhoresPrecos[produtoNome] = {
                valor: valorUnitario,
                quantidade: parseFloat(item.quantidade) || 0,
                fornecedor: item.fornecedor_nome
            };
        }
    });
    
    // Calcular produtos únicos (considerando apenas itens aprovados se for o caso)
    const produtosUnicos = Object.keys(melhoresPrecos);
    const totalProdutos = produtosUnicos.length;
    
    // Calcular fornecedores únicos dos melhores preços
    const fornecedoresUnicos = [...new Set(Object.values(melhoresPrecos).map(item => item.fornecedor))];
    const totalFornecedores = fornecedoresUnicos.length;
    
    // Calcular quantidade total dos melhores preços
    const quantidadeTotal = Object.values(melhoresPrecos).reduce((total, item) => {
        return total + item.quantidade;
    }, 0);
    
    // Calcular valor total dos melhores preços
    const valorTotal = Object.values(melhoresPrecos).reduce((total, item) => {
        return total + (item.valor * item.quantidade);
    }, 0);
    
    // Atualizar os elementos HTML
    document.getElementById('total-produtos').textContent = totalProdutos;
    document.getElementById('total-fornecedores').textContent = totalFornecedores;
    document.getElementById('total-quantidade').textContent = quantidadeTotal.toFixed(2).replace('.', ',');
    document.getElementById('total-valor').textContent = 'R$ ' + valorTotal.toFixed(2).replace('.', ',');
    
    console.log('Resumo do orçamento atualizado (melhores preços):', {
        status: data.status,
        aprovado: cotacaoAprovada,
        produtos: totalProdutos,
        fornecedores: totalFornecedores,
        quantidade: quantidadeTotal,
        valor: valorTotal
    });
}

// Adicionar esta função no arquivo modal_aprovacoes.php
function adicionarResumoRodadas(data) {
    if (!data || !data.itens || data.itens.length === 0) return;
    
    // Calcular estatísticas
    let totalRodadas = 0;
    let maxRodadas = 0;
    let produtosComRodadas = 0;
    let produtoMaisRodadas = '';
    
    data.itens.forEach(item => {
        const rodadas = parseInt(item.rodadas || 0);
        totalRodadas += rodadas;
        
        if (rodadas > 0) {
            produtosComRodadas++;
        }
        
        if (rodadas > maxRodadas) {
            maxRodadas = rodadas;
            produtoMaisRodadas = item.produto_nome;
        }
    });
    
    const mediaRodadas = produtosComRodadas > 0 ? (totalRodadas / produtosComRodadas).toFixed(1) : 0;
    const percentualRenegociados = ((produtosComRodadas / data.itens.length) * 100).toFixed(0);
    
    // Criar o HTML do resumo
    const resumoHTML = `
        <div class="resumo-rodadas">
            <h4>Resumo de Renegociações</h4>
            <div class="resumo-rodadas-cards">
                <div class="resumo-card">
                    <div class="resumo-valor">${produtosComRodadas}</div>
                    <div class="resumo-label">Produtos Renegociados</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor">${percentualRenegociados}%</div>
                    <div class="resumo-label">da Cotação</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor">${mediaRodadas}</div>
                    <div class="resumo-label">Média de Rodadas</div>
                </div>
                <div class="resumo-card">
                    <div class="resumo-valor">${maxRodadas}</div>
                    <div class="resumo-label">Máximo de Rodadas</div>
                </div>
            </div>
            ${maxRodadas > 0 ? `<div class="resumo-destaque">Produto mais renegociado: <strong>${produtoMaisRodadas}</strong> (${maxRodadas} rodadas)</div>` : ''}
        </div>
    `;
    
    // Inserir o resumo no topo do modal
    const container = document.querySelector('.analise-header');
    if (container) {
        // Verificar se o resumo já existe e removê-lo se necessário
        const resumoExistente = document.querySelector('.resumo-rodadas');
        if (resumoExistente) {
            resumoExistente.remove();
        }
        
        // Inserir o novo resumo
        container.insertAdjacentHTML('afterend', resumoHTML);
    }
}

function atualizarComparacaoCotacao(data) {
    if (!data || !data.itens) {
        console.error('Dados inválidos para comparação da cotação');
        return;
    }

    // Agrupar itens por fornecedor
    const itensPorFornecedor = {};
    data.itens.forEach(item => {
        if (!itensPorFornecedor[item.fornecedor_nome]) {
            itensPorFornecedor[item.fornecedor_nome] = {
                valorAtual: 0,
                valorUltimo: 0,
                itens: []
            };
        }
        itensPorFornecedor[item.fornecedor_nome].itens.push(item);
    });

    // Calcular totais por fornecedor
    Object.entries(itensPorFornecedor).forEach(([fornecedor, dados]) => {
        dados.itens.forEach(item => {
            const quantidade = parseFloat(item.quantidade || 0);
            const valorUnitarioAtual = parseFloat(item.valor_unitario || 0);
            const valorUnitarioUltimo = parseFloat(item.ultimo_valor_aprovado || 0);

            dados.valorAtual += quantidade * valorUnitarioAtual;
            dados.valorUltimo += quantidade * valorUnitarioUltimo;
        });
    });

    // Encontrar o fornecedor com menor valor total atual
    let melhorFornecedor = {
        nome: '',
        valorAtual: Infinity,
        valorUltimo: 0
    };

    Object.entries(itensPorFornecedor).forEach(([fornecedor, dados]) => {
        if (dados.valorAtual < melhorFornecedor.valorAtual) {
            melhorFornecedor = {
                nome: fornecedor,
                valorAtual: dados.valorAtual,
                valorUltimo: dados.valorUltimo
            };
        }
    });

    // Gerar HTML para a tabela
    let html = '';
    let totalAtual = 0;
    let totalUltimo = 0;

    Object.entries(itensPorFornecedor).forEach(([fornecedor, dados]) => {
        const variacao = dados.valorUltimo > 0 ? 
            ((dados.valorAtual - dados.valorUltimo) / dados.valorUltimo) * 100 : 0;
        const variacaoAbsoluta = dados.valorAtual - dados.valorUltimo;
        
        const variacaoClass = variacao > 0 ? 'variacao-positiva' : 
                            variacao < 0 ? 'variacao-negativa' : 'variacao-neutra';
        
        const variacaoSinal = variacao > 0 ? '+' : '';
        const variacaoAbsolutaSinal = variacaoAbsoluta > 0 ? '+' : '';

        totalAtual += dados.valorAtual;
        totalUltimo += dados.valorUltimo;

        html += `
            <tr>
                <td>${fornecedor}</td>
                <td>R$ ${formatarNumero(dados.valorAtual)}</td>
                <td>R$ ${formatarNumero(dados.valorUltimo)}</td>
                <td class="${variacaoClass}">
                    ${variacaoSinal}${formatarNumero(variacao)}% 
                    (${variacaoAbsolutaSinal}R$ ${formatarNumero(variacaoAbsoluta)})
                </td>
            </tr>
        `;
    });

    // Adicionar linha do melhor valor
    const variacaoMelhor = melhorFornecedor.valorUltimo > 0 ? 
        ((melhorFornecedor.valorAtual - melhorFornecedor.valorUltimo) / melhorFornecedor.valorUltimo) * 100 : 0;
    const variacaoAbsolutaMelhor = melhorFornecedor.valorAtual - melhorFornecedor.valorUltimo;
    
    const variacaoClassMelhor = variacaoMelhor > 0 ? 'variacao-positiva' : 
                               variacaoMelhor < 0 ? 'variacao-negativa' : 'variacao-neutra';
    
    const variacaoSinalMelhor = variacaoMelhor > 0 ? '+' : '';
    const variacaoAbsolutaSinalMelhor = variacaoAbsolutaMelhor > 0 ? '+' : '';

    html += `
        <tr class="melhor-valor-row">
            <td><strong>Melhor Valor</strong></td>
            <td>R$ ${formatarNumero(melhorFornecedor.valorAtual)}</td>
            <td>R$ ${formatarNumero(melhorFornecedor.valorUltimo)}</td>
            <td class="${variacaoClassMelhor}">
                ${variacaoSinalMelhor}${formatarNumero(variacaoMelhor)}% 
                (${variacaoAbsolutaSinalMelhor}R$ ${formatarNumero(variacaoAbsolutaMelhor)})
            </td>
        </tr>
    `;

    // Atualizar a tabela
    document.getElementById('comparacao-cotacao-body').innerHTML = html;
}

// Adicionar estilos CSS para a nova seção
const style = document.createElement('style');
style.textContent = `
    .comparacao-cotacao {
        margin: 20px 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
    }

    .comparacao-cotacao h4 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.1em;
        font-weight: 600;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .tabela-comparacao {
        overflow-x: auto;
    }

    .tabela-comparacao table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 0.9em;
    }

    .tabela-comparacao th,
    .tabela-comparacao td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .tabela-comparacao th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #555;
    }

    .tabela-comparacao tr:hover {
        background-color: #f8f9fa;
    }

    .variacao-positiva {
        color: #dc3545;
        font-weight: 500;
    }

    .variacao-negativa {
        color: #28a745;
        font-weight: 500;
    }

    .variacao-neutra {
        color: #6c757d;
    }

    .melhor-valor-row {
        background-color: #f8fff8;
        font-weight: 500;
    }

    .melhor-valor-row td {
        border-top: 2px solid #ddd;
        padding: 12px 15px;
    }

    .melhor-valor-row td:first-child {
        color: #28a745;
    }

    @media (max-width: 768px) {
        .tabela-comparacao {
            font-size: 0.85em;
        }
        
        .tabela-comparacao th,
        .tabela-comparacao td {
            padding: 8px 10px;
        }
    }
`;
document.head.appendChild(style);

</script>

<style>
    .edicoes-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.8em;
        margin-left: 5px;
        background-color: #fd7e14; /* Cor laranja específica solicitada */
        color: white;
        font-weight: bold;
    }
    
    .rodadas-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.8em;
        margin-left: 5px;
        margin-right: 3px;
        background-color: #007bff;
        color: white;
        font-weight: bold;
    }
    
    /* Ajuste para que os badges fiquem lado a lado */
    .rodadas-badge, .edicoes-badge {
        display: inline-block;
        margin-right: 3px;
    }
</style>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
