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
}

function atualizarCards(stats) {
    document.getElementById('pendentes-count').textContent = stats.pendentes;
    document.getElementById('aprovadas-count').textContent = stats.aprovadas;
    document.getElementById('rejeitadas-count').textContent = stats.rejeitadas;
    document.getElementById('renegociacao-count').textContent = stats.renegociacao;
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
    const url = new URL('cotacao/aprovacoes.php', window.location.origin);
    url.searchParams.append('cotacao_id', id);
    url.searchParams.append('status', status);
    
    // Redirecionar para a página de aprovações com os filtros
    window.location.href = url.toString();
}
