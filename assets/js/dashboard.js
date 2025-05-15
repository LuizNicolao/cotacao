// Variável global para o gráfico
let statusChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Carregar dados iniciais
    atualizarDashboard();
    
    // Atualizar a cada 5 minutos
    setInterval(atualizarDashboard, 300000);
});

function atualizarDashboard() {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                atualizarCards(data.stats);
                atualizarSawingStats(data.sawing_stats);
                atualizarAlertas(data.alertas);
                atualizarGrafico(data.stats);
                atualizarCotacoesRecentes(data.recentes);
            }
        })
        .catch(error => console.error('Erro ao atualizar dashboard:', error));

    // Atualizar cotação do dólar e preço do diesel
    atualizarTaxas();
}

function atualizarCards(stats) {
    document.getElementById('pendentes-count').textContent = stats.pendentes;
    document.getElementById('aprovadas-count').textContent = stats.aprovadas;
    document.getElementById('rejeitadas-count').textContent = stats.rejeitadas;
    document.getElementById('renegociacao-count').textContent = stats.renegociacao;
    document.getElementById('programadas-count').textContent = stats.programadas || 0;
    document.getElementById('emergenciais-count').textContent = stats.emergenciais || 0;
}

function atualizarSawingStats(stats) {
    document.getElementById('economia-total').textContent = formatarMoeda(stats.economia_total);
    document.getElementById('economia-percentual').textContent = stats.economia_percentual.toFixed(2) + '%';
    document.getElementById('total-negociado').textContent = formatarMoeda(stats.total_negociado);
    document.getElementById('total-aprovado').textContent = formatarMoeda(stats.total_aprovado);
}

function atualizarAlertas(alertas) {
    const container = document.getElementById('alertas-container');
    container.innerHTML = '';
    
    alertas.forEach(alerta => {
        const alertaElement = document.createElement('div');
        alertaElement.className = `alerta ${alerta.tipo}`;
        alertaElement.innerHTML = `
            <i class="fas fa-${alerta.icone}"></i>
            <span>${alerta.mensagem}</span>
        `;
        container.appendChild(alertaElement);
    });
}

function atualizarGrafico(stats) {
    const ctx = document.getElementById('status-chart').getContext('2d');
    
    // Destruir gráfico existente se houver
    if (statusChart) {
        statusChart.destroy();
    }
    
    // Criar novo gráfico
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Aguardando Aprovação', 'Aprovadas', 'Rejeitadas', 'Em Renegociação'],
            datasets: [{
                data: [
                    stats.pendentes,
                    stats.aprovadas,
                    stats.rejeitadas,
                    stats.renegociacao
                ],
                backgroundColor: [
                    '#ffd700',
                    '#28a745',
                    '#dc3545',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function atualizarCotacoesRecentes(recentes) {
    const tbody = document.getElementById('cotacoes-recentes');
    tbody.innerHTML = '';
    
    recentes.forEach(cotacao => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${cotacao.id}</td>
            <td>${formatarData(cotacao.data_criacao)}</td>
            <td>
                <span class="status-badge ${cotacao.status}">
                    ${traduzirStatus(cotacao.status)}
                </span>
            </td>
            <td>${cotacao.usuario_nome}</td>
            <td>
                <span class="badge ${cotacao.tipo === 'emergencial' ? 'badge-warning' : 'badge-info'}">
                    <i class="fas ${cotacao.tipo === 'emergencial' ? 'fa-exclamation-circle' : 'fa-calendar'}"></i>
                    ${cotacao.tipo === 'emergencial' ? 'Emergencial' : 'Programada'}
                </span>
            </td>
            <td>${formatarMoeda(cotacao.valor_total || 0)}</td>
            <td class="acoes">
                <button onclick="visualizarCotacao(${cotacao.id}, '${cotacao.status}')" class="btn-acao btn-visualizar">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
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

function visualizarCotacao(id, status) {
    // Criar URL com os parâmetros de filtro
    const url = new URL('cotacaoluiz/aprovacoes.php', window.location.origin);
    url.searchParams.append('cotacao_id', id);
    url.searchParams.append('status', status);
    
    // Redirecionar para a página de aprovações com os filtros
    window.location.href = url.toString();
}

// Função para atualizar a cotação do dólar
async function atualizarCotacaoDolar() {
    try {
        const response = await fetch('api/dolar_rate.php');
        const data = await response.json();
        
        if (data.success) {
            const dolarElement = document.getElementById('dolar-rate');
            if (dolarElement) {
                dolarElement.textContent = `R$ ${parseFloat(data.rate).toFixed(2)}`;
            }
        }
    } catch (error) {
        console.error('Erro ao atualizar cotação do dólar:', error);
    }
}

// Função para atualizar o preço do diesel
async function atualizarPrecoDiesel() {
    try {
        const response = await fetch('api/diesel_rate.php');
        const data = await response.json();
        
        if (data.success) {
            const dieselElement = document.getElementById('diesel-rate');
            if (dieselElement) {
                dieselElement.textContent = `R$ ${parseFloat(data.rate).toFixed(2)}`;
            }
        }
    } catch (error) {
        console.error('Erro ao atualizar preço do diesel:', error);
    }
}

// Função para atualizar todas as taxas
function atualizarTaxas() {
    atualizarCotacaoDolar();
    atualizarPrecoDiesel();
}

// Atualizar imediatamente ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    atualizarTaxas();
    
    // Atualizar a cada 5 minutos (300000 ms)
    setInterval(atualizarTaxas, 300000);
});
