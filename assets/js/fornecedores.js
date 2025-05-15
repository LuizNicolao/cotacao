document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Carregar dados iniciais
    carregarDados();
    
    // Configurar eventos de filtro
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
    const btnLimparFiltros = document.getElementById('btn-limpar-filtros');
    const btnFiltros = document.getElementById('btn-filtros');
    const btnExportar = document.getElementById('btn-exportar');
    
    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', () => carregarDados(1));
    }
    
    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', () => {
            const dataInicio = document.getElementById('data-inicio');
            const dataFim = document.getElementById('data-fim');
            const filtroFornecedor = document.getElementById('filtro-fornecedor');
            const filtroProduto = document.getElementById('filtro-produto');

            if (dataInicio) dataInicio.value = '';
            if (dataFim) dataFim.value = '';
            if (filtroFornecedor) filtroFornecedor.value = '';
            if (filtroProduto) filtroProduto.value = '';
            
            carregarDados(1);
        });
    }

    if (btnFiltros) {
        btnFiltros.addEventListener('click', () => {
            console.log('Botão de filtros clicado');
        });
    }

    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            console.log('Botão de exportar clicado');
        });
    }
    
    // Configurar eventos do modal
    const modal = document.getElementById('modalDetalhes');
    const closeBtn = modal?.querySelector('.close');

    if (closeBtn) {
        closeBtn.onclick = function() {
            fecharModalFornecedor();
        }
    }
    
    if (modal) {
        window.onclick = function(event) {
            if (event.target == modal) {
                fecharModalFornecedor();
            }
        }
    }

    // Configurar máscaras de input
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            e.target.value = value;
        });
    }
});

// Variáveis globais para armazenar as instâncias dos gráficos
let precosChart = null;
let produtosChart = null;

function abrirModalFornecedor(fornecedor) {
    const modal = document.getElementById('modalFornecedor');
    if (!modal) return;

    // Mostra o modal
    modal.style.display = 'block';
    
    // Carrega os dados do fornecedor
    fetch(`api/fornecedor_detalhes.php?fornecedor=${encodeURIComponent(fornecedor)}`)
        .then(response => response.json())
        .then(dados => {
            if (dados.success) {
                // Atualiza o cabeçalho do modal
                atualizarCabecalhoModal(dados);
                
                // Atualiza as métricas
                atualizarMetricas(dados.metricas);
                
                // Atualiza a tabela de produtos
                atualizarTabelaProdutos(dados.produtos);
                
                // Atualiza os gráficos
                atualizarGraficos(dados.produtos);
            } else {
                console.error('Erro ao carregar dados do fornecedor:', dados.error);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados do fornecedor:', error);
        });
}

function fecharModalFornecedor() {
    const modal = document.getElementById('modalDetalhes');
    if (modal) {
        modal.style.display = 'none';
        
        // Destruir os gráficos existentes
        if (precosChart) {
            precosChart.destroy();
            precosChart = null;
        }
        if (produtosChart) {
            produtosChart.destroy();
            produtosChart = null;
        }
    }
}

function editarFornecedor(id) {
    fetch(`api/fornecedores.php?id=${id}`)
        .then(response => response.json())
        .then(fornecedor => {
            document.getElementById('fornecedorId').value = fornecedor.id;
            document.getElementById('nome').value = fornecedor.nome;
            document.getElementById('cnpj').value = fornecedor.cnpj;
            document.getElementById('email').value = fornecedor.email;
            document.getElementById('telefone').value = fornecedor.telefone;
            document.getElementById('status').value = fornecedor.status;
            
            document.querySelector('#modalFornecedor h3').textContent = 'Editar Fornecedor';
            document.getElementById('modalFornecedor').style.display = 'block';
        });
}

function salvarFornecedor(e) {
    e.preventDefault();
    
    const dados = {
        id: document.getElementById('fornecedorId').value,
        nome: document.getElementById('nome').value,
        cnpj: document.getElementById('cnpj').value,
        email: document.getElementById('email').value,
        telefone: document.getElementById('telefone').value,
        status: document.getElementById('status').value
    };

    fetch('api/fornecedores.php', {
        method: dados.id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModalFornecedor();
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar fornecedor');
        }
    });
}

function excluirFornecedor(id) {
    if (confirm('Tem certeza que deseja excluir este fornecedor?')) {
        fetch(`api/fornecedores.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir fornecedor');
            }
        });
    }
}

// Função para carregar os dados dos fornecedores
function carregarDados(pagina = 1, limite = 10) {
    // Mostrar indicador de carregamento
    const tbody = document.getElementById('tabela-fornecedores-body');
    if (!tbody) {
        console.error('Elemento tbody não encontrado');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando dados...</td></tr>';
    
    // Obter filtros
    const filtros = obterFiltros();
    
    // Construir URL com parâmetros
    const url = `api/fornecedores.php?pagina=${pagina}&limite=${limite}${filtros}`;
    
    console.log("Carregando dados de:", url);
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao carregar dados');
            }
            return response.json();
        })
        .then(data => {
            console.log("Dados recebidos:", data);
            
            if (data.success && Array.isArray(data.fornecedores)) {
                renderizarTabela(data.fornecedores);
                atualizarResumo(data.resumo);
                
                if (typeof renderizarPaginacao === 'function' && data.total !== undefined) {
                    renderizarPaginacao(data.total, data.pagina || pagina, limite);
                }
            } else {
                renderizarTabela([]);
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Erro ao carregar dados: ${error.message}
                    </td>
                </tr>
            `;
        });
}

// Função para obter filtros
function obterFiltros() {
    const dataInicio = document.getElementById('data-inicio')?.value || '';
    const dataFim = document.getElementById('data-fim')?.value || '';
    const fornecedor = document.getElementById('filtro-fornecedor')?.value || '';
    const produto = document.getElementById('filtro-produto')?.value || '';
    
    let filtros = '';
    
    if (dataInicio) filtros += `&data_inicio=${dataInicio}`;
    if (dataFim) filtros += `&data_fim=${dataFim}`;
    if (fornecedor) filtros += `&fornecedor=${encodeURIComponent(fornecedor)}`;
    if (produto) filtros += `&produto=${encodeURIComponent(produto)}`;
    
    return filtros;
}

// Função para renderizar a tabela
function renderizarTabela(fornecedores) {
    const tbody = document.getElementById('tabela-fornecedores-body');
    if (!tbody) {
        console.error('Elemento tbody não encontrado');
        return;
    }
    
    if (!fornecedores || fornecedores.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
                    <i class="fas fa-info-circle"></i> Nenhum fornecedor encontrado
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    
    fornecedores.forEach(fornecedor => {
        html += `
            <tr>
                <td>${fornecedor.fornecedor}</td>
                <td>R$ ${formatarNumero(fornecedor.valor_medio)}</td>
                <td>${fornecedor.produtos_unicos}</td>
                <td>${fornecedor.total_compras}</td>
                <td class="${fornecedor.economia_total > 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                    R$ ${formatarNumero(fornecedor.economia_total)}
                </td>
                <td>${formatarData(fornecedor.ultima_compra)}</td>
                <td>
                    <button class="btn-detalhes" onclick="verDetalhes('${fornecedor.fornecedor}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Função para atualizar o resumo
function atualizarResumo(resumo) {
    if (!resumo) return;
    
    const totalFornecedores = document.getElementById('total-fornecedores');
    const totalCompras = document.getElementById('total-compras');
    const totalProdutos = document.getElementById('total-produtos');
    const prazoMedio = document.getElementById('prazo-medio');
    
    if (totalFornecedores) totalFornecedores.textContent = resumo.total_fornecedores || '0';
    if (totalCompras) totalCompras.textContent = `R$ ${formatarNumero(resumo.total_compras || 0)}`;
    if (totalProdutos) totalProdutos.textContent = resumo.total_produtos || '0';
    if (prazoMedio) prazoMedio.textContent = `${resumo.valor_medio || 0} dias`;
}

// Função para carregar os detalhes do fornecedor
async function carregarDetalhesFornecedor(fornecedor) {
    try {
        const response = await fetch(`api/fornecedor_detalhes.php?fornecedor=${encodeURIComponent(fornecedor)}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao carregar detalhes do fornecedor');
        }

        // Atualiza o título do modal
        document.querySelector('.modal-title').textContent = fornecedor;

        // Atualiza o resumo
        document.getElementById('resumo-total-compras').textContent = data.metricas.total_compras;
        document.getElementById('resumo-valor-medio').textContent = formatarMoeda(data.metricas.valor_medio);
        document.getElementById('resumo-economia').textContent = formatarMoeda(data.metricas.economia_total);
        document.getElementById('resumo-participacao').textContent = `${data.metricas.participacao_mercado}%`;

        // Renderiza os produtos
        renderizarProdutos(data.produtos);

        // Renderiza o histórico
        renderizarHistorico(data.historico);

        // Atualiza as métricas
        atualizarMetricas(data.metricas);

        // Atualiza o comparativo
        atualizarComparativo(data.comparativo);

        // Inicializa os gráficos
        inicializarGraficos(data.comparativo);

        // Adiciona eventos de filtro
        adicionarEventosFiltro();

        // Exibe o modal
        const modal = document.getElementById('modalDetalhes');
        modal.style.display = 'block';

        // Ativa a aba de produtos por padrão
        mudarTab('produtos');
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        alert('Erro ao carregar detalhes do fornecedor');
    }
}

// Função para atualizar o comparativo
function atualizarComparativo(comparativo) {
    document.getElementById('ranking-precos').textContent = comparativo.ranking_precos;
    document.getElementById('ranking-economia').textContent = comparativo.ranking_economia;
    document.getElementById('produtos-exclusivos').textContent = comparativo.produtos_exclusivos;
    document.getElementById('participacao-mercado').textContent = `${comparativo.participacao_mercado}%`;
}

// Função para inicializar os gráficos
function inicializarGraficos(comparativo) {
    // Destruir gráficos existentes antes de criar novos
    if (precosChart) {
        precosChart.destroy();
    }
    if (produtosChart) {
        produtosChart.destroy();
    }

    // Gráfico de evolução de preços
    const precosCtx = document.getElementById('precos-chart').getContext('2d');
    precosChart = new Chart(precosCtx, {
        type: 'line',
        data: {
            labels: comparativo.evolucao_precos.labels,
            datasets: [{
                label: 'Preço Médio',
                data: comparativo.evolucao_precos.valores,
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Gráfico de distribuição de produtos
    const produtosCtx = document.getElementById('produtos-chart').getContext('2d');
    produtosChart = new Chart(produtosCtx, {
        type: 'pie',
        data: {
            labels: comparativo.distribuicao_produtos.labels,
            datasets: [{
                data: comparativo.distribuicao_produtos.valores,
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Função para alternar entre as abas
function mudarTab(tabName) {
    // Esconde todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove a classe active de todos os botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostra a aba selecionada
    const selectedTab = document.getElementById(`tab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
    if (selectedTab) {
        selectedTab.style.display = 'block';
    }
    
    // Adiciona a classe active ao botão clicado
    const clickedButton = document.querySelector(`.tab-btn[onclick="mudarTab('${tabName}')"]`);
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
}

// Função para ver detalhes do fornecedor
async function verDetalhes(fornecedor) {
    try {
        const response = await fetch(`api/fornecedor_detalhes.php?fornecedor=${encodeURIComponent(fornecedor)}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        // Atualizar título do modal
        const modalTitle = document.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = `Detalhes do Fornecedor: ${fornecedor}`;
        }
        
        // Atualizar métricas no cabeçalho
        const metricasHtml = `
            <div class="row">
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${data.metricas.total_compras}</div>
                        <h5>Total de Compras</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${formatarMoeda(data.metricas.valor_medio)}</div>
                        <h5>Valor Médio</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${formatarMoeda(data.metricas.economia_total)}</div>
                        <h5>Economia Total</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">${data.metricas.participacao_mercado}%</div>
                        <h5>Participação no Mercado</h5>
                    </div>
                </div>
            </div>
        `;
        
        const fornecedorResumo = document.querySelector('.fornecedor-resumo');
        if (fornecedorResumo) {
            fornecedorResumo.innerHTML = metricasHtml;
        }
        
        // Renderizar produtos
        renderizarProdutos(data.produtos);
        
        // Renderizar histórico
        renderizarHistorico(data.historico);
        
        // Atualizar métricas
        atualizarMetricas(data.metricas);
        
        // Atualizar comparativo
        atualizarComparativo(data.comparativo);
        
        // Inicializar gráficos
        inicializarGraficos(data.comparativo);
        
        // Adicionar eventos de filtro
        adicionarEventosFiltro();
        
        // Mostrar modal
        const modal = document.getElementById('modalDetalhes');
        if (modal) {
            modal.style.display = 'block';
            // Ativar a aba de produtos por padrão
            mudarTab('produtos');
        }
        
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        alert('Erro ao carregar detalhes do fornecedor');
    }
}

// Função para formatar moeda
function formatarMoeda(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return 'R$ 0,00';
    }
    return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
}

// Função para renderizar os produtos
function renderizarProdutos(produtos) {
    const tbody = document.querySelector('#tabProdutos tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!produtos || produtos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum produto encontrado</td></tr>';
        return;
    }
    
    produtos.forEach(produto => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${produto.descricao}</td>
            <td>${produto.quantidade_total}</td>
            <td>${formatarMoeda(produto.preco_medio)}</td>
            <td>${formatarMoeda(produto.menor_preco)}</td>
            <td>${formatarMoeda(produto.maior_preco)}</td>
            <td class="${produto.valor_total >= 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                ${formatarMoeda(produto.valor_total)}
            </td>
            <td>${formatarData(produto.ultima_compra)}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Função para renderizar o histórico
function renderizarHistorico(historico) {
    const tbody = document.querySelector('#tabHistorico tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!historico || historico.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum histórico encontrado</td></tr>';
        return;
    }
    
    historico.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${formatarData(item.data)}</td>
            <td>${item.descricao}</td>
            <td>${item.quantidade}</td>
            <td>${formatarMoeda(item.valor_unitario)}</td>
            <td>${formatarMoeda(item.valor_total)}</td>
            <td class="${item.economia >= 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                ${formatarMoeda(item.economia)}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Função para atualizar as métricas
function atualizarMetricas(metricas) {
    // Atualiza economia total
    const economiaEl = document.querySelector('[data-metrica="economia"]');
    if (economiaEl) {
        economiaEl.textContent = formatarMoeda(metricas.economia_total);
    }
    
    // Atualiza produtos mais comprados
    const produtosTopEl = document.querySelector('[data-metrica="produtos-top"]');
    if (produtosTopEl) {
        // Remove duplicatas usando Set
        const produtosUnicos = [...new Set(metricas.produtos_top)];
        produtosTopEl.innerHTML = produtosUnicos.length > 0 
            ? produtosUnicos.map(p => `<div>${p}</div>`).join('')
            : 'Nenhum produto';
    }
    
    // Atualiza melhor prazo
    const melhorPrazoEl = document.querySelector('[data-metrica="melhor-prazo"]');
    if (melhorPrazoEl) {
        melhorPrazoEl.textContent = metricas.melhor_prazo || 'N/A';
    }
    
    // Atualiza preço médio
    const precoMedioEl = document.querySelector('[data-metrica="preco-medio"]');
    if (precoMedioEl) {
        precoMedioEl.textContent = formatarMoeda(metricas.valor_medio);
    }
    
    // Atualiza tendências
    const economiaTrend = document.querySelector('[data-trend="economia"]');
    const precoTrend = document.querySelector('[data-trend="preco"]');
    
    if (economiaTrend) {
        atualizarTrend(economiaTrend, metricas.trend_economia);
    }
    if (precoTrend) {
        atualizarTrend(precoTrend, metricas.trend_preco);
    }
}

// Função para formatar números
function formatarNumero(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '0,00';
    }
    return parseFloat(valor).toFixed(2).replace('.', ',');
}

// Função para formatar data
function formatarData(dataString) {
    if (!dataString) return '';
    
    const data = new Date(dataString);
    
    if (isNaN(data.getTime())) {
        return dataString;
    }
    
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');
    
    return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
}

// Função para atualizar indicadores de tendência
function atualizarTrend(element, valor) {
    const icon = element.querySelector('i');
    const span = element.querySelector('span');
    
    if (valor > 0) {
        icon.className = 'fas fa-arrow-up';
        element.className = 'metrica-trend positiva';
    } else if (valor < 0) {
        icon.className = 'fas fa-arrow-down';
        element.className = 'metrica-trend negativa';
    } else {
        icon.className = 'fas fa-minus';
        element.className = 'metrica-trend';
    }
    
    span.textContent = `${Math.abs(valor)}%`;
}

// Função para adicionar eventos de filtro
function adicionarEventosFiltro() {
    // Filtro de busca de produtos
    const searchInput = document.getElementById('search-produto');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#tabProdutos tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
  });
    }

    // Filtro de ordenação de produtos
    const orderSelect = document.getElementById('filter-ordem-produtos');
    if (orderSelect) {
        orderSelect.addEventListener('change', (e) => {
            const tbody = document.querySelector('#tabProdutos tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aValue = a.children[e.target.value === 'quantidade' ? 1 : 
                    e.target.value === 'economia' ? 5 : 2].textContent;
                const bValue = b.children[e.target.value === 'quantidade' ? 1 : 
                    e.target.value === 'economia' ? 5 : 2].textContent;
                
                if (e.target.value === 'preco') {
                    return parseFloat(aValue.replace(/[^0-9.-]+/g, '')) - 
                           parseFloat(bValue.replace(/[^0-9.-]+/g, ''));
                }
                return parseFloat(bValue) - parseFloat(aValue);
            });
            
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        });
    }

    // Filtro de data do histórico
    const dataInicio = document.getElementById('historico-data-inicio');
    const dataFim = document.getElementById('historico-data-fim');
    
    if (dataInicio && dataFim) {
        const filtrarPorData = () => {
            const inicio = new Date(dataInicio.value);
            const fim = new Date(dataFim.value);
            const rows = document.querySelectorAll('#tabHistorico tbody tr');
            
            rows.forEach(row => {
                const data = new Date(row.children[0].textContent);
                row.style.display = data >= inicio && data <= fim ? '' : 'none';
            });
        };
        
        dataInicio.addEventListener('change', filtrarPorData);
        dataFim.addEventListener('change', filtrarPorData);
    }
}

// Função para atualizar o cabeçalho do modal
function atualizarCabecalhoModal(dados) {
    const metricas = dados.metricas;
    
    // Atualiza o título do modal
    document.querySelector('.modal-title').textContent = `Detalhes do Fornecedor: ${dados.fornecedor}`;
    
    // Atualiza as métricas no cabeçalho
    const metricasHtml = `
        <div class="row">
            <div class="col-md-3">
                <div class="metric-card">
                    <h5>Total de Compras</h5>
                    <div class="metric-value">${metricas.total_compras}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <h5>Valor Médio</h5>
                    <div class="metric-value">${formatarMoeda(metricas.valor_medio)}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <h5>Economia Total</h5>
                    <div class="metric-value">${formatarMoeda(metricas.economia_total)}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <h5>Participação no Mercado</h5>
                    <div class="metric-value">${metricas.participacao_mercado}%</div>
                </div>
            </div>
        </div>
    `;
    
    document.querySelector('.fornecedor-resumo').innerHTML = metricasHtml;
}