/**
 * Carrega os dados do Sawing do servidor
 * @param {number} pagina - Número da página atual (padrão: 1)
 * @param {number} limite - Número de registros por página (padrão: 10)
 */
function carregarDados(pagina = 1, limite = 10) {
    // Mostrar indicador de carregamento
    const tbody = document.getElementById('tabela-sawing-body');
    tbody.innerHTML = '<tr><td colspan="11" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando dados...</td></tr>';
    
    // Obter filtros (se a função existir)
    const filtros = typeof obterFiltros === 'function' ? obterFiltros() : '';
    
    // Construir URL com parâmetros
    const url = `api/sawing.php?pagina=${pagina}&limite=${limite}${filtros}`;
    
    console.log("Carregando dados de:", url);
    
    fetch(url)
        .then(response => {
            // Verificar o tipo de conteúdo da resposta
            const contentType = response.headers.get('content-type');
            console.log("Tipo de conteúdo da resposta:", contentType);
            
            // Se não for JSON, mostrar o texto da resposta para diagnóstico
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error("Resposta não-JSON recebida:", text);
                    throw new Error("Resposta do servidor não é JSON válido");
                });
            }
            
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.mensagem || `Erro HTTP: ${response.status}`);
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log("Dados recebidos:", data);
            
            // Verificar se os dados são válidos
            if (data && Array.isArray(data.registros)) {
                // Renderizar a tabela com os dados
                renderizarTabela(data.registros);
                
                // Renderizar paginação se a função existir e houver dados de paginação
                if (typeof renderizarPaginacao === 'function' && data.total !== undefined) {
                    renderizarPaginacao(data.total, data.pagina || pagina, limite);
                }
                
                // Renderizar resumo se a função existir e houver dados de resumo
                if (typeof renderizarResumo === 'function' && data.resumo) {
                    // Adicionar os registros ao resumo
                    data.resumo.registros = data.registros;
                    renderizarResumo(data.resumo);
                }
            } else {
                // Se não houver dados válidos, mostrar mensagem
                renderizarTabela([]);
                
                // Limpar paginação se a função existir
                if (typeof renderizarPaginacao === 'function') {
                    const paginacaoElement = document.getElementById('paginacao');
                    if (paginacaoElement) paginacaoElement.innerHTML = '';
                }
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            
            // Em caso de erro, mostrar mensagem
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Erro ao carregar dados: ${error.message}
                    </td>
                </tr>
            `;
            
            // Atualizar a informação de registros
            const infoRegistros = document.getElementById('info-registros');
            if (infoRegistros) {
                infoRegistros.textContent = 'Erro ao carregar registros';
            }
            
            // Limpar paginação se o elemento existir
            const paginacaoElement = document.getElementById('paginacao');
            if (paginacaoElement) paginacaoElement.innerHTML = '';
        });
}

/**
 * Obtém os filtros aplicados no formulário
 * @returns {string} String de query parameters para a URL
 */
function obterFiltros() {
    // Obter valores dos campos de filtro
    const dataInicio = document.getElementById('data-inicio')?.value || '';
    const dataFim = document.getElementById('data-fim')?.value || '';
    const comprador = document.getElementById('filtro-comprador')?.value || '';
    const tipo = document.getElementById('filtro-tipo')?.value || '';
    const status = document.getElementById('filtro-status')?.value || '';
    
    console.log('Valores dos filtros:', {
        dataInicio,
        dataFim,
        comprador,
        tipo,
        status
    });
    
    // Construir string de filtros
    let filtros = '';
    
    if (dataInicio) filtros += `&data_inicio=${dataInicio}`;
    if (dataFim) filtros += `&data_fim=${dataFim}`;
    if (comprador) filtros += `&comprador=${encodeURIComponent(comprador)}`;
    if (tipo) filtros += `&tipo=${encodeURIComponent(tipo)}`;
    if (status) filtros += `&status=${status}`;
    
    console.log('String de filtros gerada:', filtros);
    return filtros;
}

// Função para renderizar a tabela com os dados
function renderizarTabela(registros) {
    const tbody = document.getElementById('tabela-sawing-body');
    const infoRegistros = document.getElementById('info-registros');
    
    // Verificar se há dados
    if (!registros || registros.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center">
                    <i class="fas fa-info-circle"></i> Nenhum registro encontrado
                </td>
            </tr>
        `;
        
        // Atualizar a informação de registros, se o elemento existir
        if (infoRegistros) {
            infoRegistros.textContent = 'Mostrando 0 registros';
        }
        return;
    }
    
    let html = '';
    
    registros.forEach(registro => {
        // Garantir que todos os valores numéricos sejam tratados corretamente
        const valorInicial = parseFloat(registro.valor_total_inicial || 0);
        const valorFinal = parseFloat(registro.valor_total_final || 0);
        
        // Calcular economia (caso não venha calculada do backend)
        const economia = registro.economia !== undefined ? 
            parseFloat(registro.economia) : (valorInicial - valorFinal);
            
        // Calcular percentual de economia (caso não venha calculado do backend)
        const economiaPercentual = registro.economia_percentual !== undefined ?
            parseFloat(registro.economia_percentual) : 
            (valorInicial > 0 ? (economia / valorInicial * 100) : 0);
        
        // Formatar valores para exibição
        const dataFormatada = new Date(registro.data_registro).toLocaleDateString('pt-BR');
        const valorInicialFormatado = formatarMoeda(valorInicial);
        const valorFinalFormatado = formatarMoeda(valorFinal);
        const economiaFormatada = formatarMoeda(economia);
        const economiaPercentualFormatada = economiaPercentual.toFixed(2) + '%';
        
        // Traduzir status
        const statusTraduzido = traduzirStatus(registro.status);
        
        // Determinar o tipo de badge e ícone com base no tipo
        const tipo = registro.tipo || 'programada';
        const badgeClass = tipo === 'emergencial' ? 'badge-warning' : 'badge-info';
        const badgeIcon = tipo === 'emergencial' ? 'fa-exclamation-circle' : 'fa-calendar';
        const badgeText = tipo === 'emergencial' ? 'Emergencial' : 'Programada';
        
        html += `
            <tr>
                <td>${registro.id}</td>
                <td>${registro.cotacao_id || 'N/A'}</td>
                <td>${registro.comprador_nome || 'N/A'}</td>
                <td>${dataFormatada}</td>
                <td>${valorInicialFormatado}</td>
                <td>${valorFinalFormatado}</td>
                <td>${economiaFormatada}</td>
                <td>${economiaPercentualFormatada}</td>
                <td>${registro.rodadas || '1'}</td>
                <td>
                    <span class="badge ${badgeClass}">
                        <i class="fas ${badgeIcon}"></i>
                        ${badgeText}
                    </span>
                </td>
                <td>
                    <button class="btn-detalhes" onclick="verDetalhes(${registro.id})" 
                            data-sawing-id="${registro.id}" 
                            data-data-registro="${registro.data_registro}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Atualizar a informação de registros, se o elemento existir
    if (infoRegistros) {
        const inicio = 1;
        const fim = registros.length;
        const total = registros.length;
        infoRegistros.textContent = `Mostrando ${inicio} a ${fim} de ${total} registros`;
    }
}

/**
 * Formata um valor numérico como moeda brasileira
 * @param {number} valor - Valor a ser formatado
 * @returns {string} Valor formatado como moeda
 */
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Função para renderizar a paginação
function renderizarPaginacao(total, paginaAtual, limite) {
    const paginacao = document.getElementById('paginacao');
    const totalPaginas = Math.ceil(total / limite);
    
    if (totalPaginas <= 1) {
        paginacao.innerHTML = '';
        return;
    }
    
    let html = '<ul class="pagination justify-content-center">';
    
    // Botão anterior
    html += `
        <li class="page-item ${paginaAtual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarDados(${paginaAtual - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Páginas
    for (let i = 1; i <= totalPaginas; i++) {
        if (
            i === 1 || // Primeira página
            i === totalPaginas || // Última página
            (i >= paginaAtual - 2 && i <= paginaAtual + 2) // 2 páginas antes e depois da atual
        ) {
            html += `
                <li class="page-item ${i === paginaAtual ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="carregarDados(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (
            (i === paginaAtual - 3 && paginaAtual > 3) ||
            (i === paginaAtual + 3 && paginaAtual < totalPaginas - 2)
        ) {
            // Adicionar reticências
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Botão próximo
    html += `
        <li class="page-item ${paginaAtual === totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarDados(${paginaAtual + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    html += '</ul>';
    paginacao.innerHTML = html;
}

/**
 * Renderiza o resumo com os dados recebidos do servidor
 * @param {Object} resumo - Objeto com os dados do resumo
 */
function renderizarResumo(resumo) {
    // Atualizar economia total
    const economiaTotal = document.getElementById('economia-total');
    if (economiaTotal) {
        economiaTotal.textContent = 'R$ ' + formatarNumero(resumo.economia_total || 0);
    }
    
    // Atualizar economia percentual média
    const economiaPercentual = document.getElementById('economia-percentual-media');
    if (economiaPercentual) {
        economiaPercentual.textContent = (resumo.economia_percentual || 0).toFixed(2) + '%';
    }
    
    // Atualizar total de rodadas
    const rodadasMedia = document.getElementById('rodadas-media');
    if (rodadasMedia) {
        rodadasMedia.textContent = resumo.total_rodadas || 0;
    }
    
    // Atualizar total negociado
    const totalNegociado = document.getElementById('total-negociado');
    if (totalNegociado) {
        totalNegociado.textContent = 'R$ ' + formatarNumero(resumo.total_negociado || 0);
    }
    
    // Atualizar total aprovado
    const totalAprovado = document.getElementById('total-aprovado');
    if (totalAprovado) {
        totalAprovado.textContent = 'R$ ' + formatarNumero(resumo.total_aprovado || 0);
    }
    
    // Renderizar cards de compradores
    if (resumo.compradores && resumo.compradores.length > 0) {
        // Encontrar melhor e pior comprador
        let melhorComprador = null;
        let piorComprador = null;
        let maiorEconomia = -Infinity;
        let menorEconomia = Infinity;
        
        resumo.compradores.forEach(comprador => {
            // Garantir que os valores sejam números
            const economiaTotal = parseFloat(comprador.economia_total || 0);
            
            if (economiaTotal > maiorEconomia) {
                maiorEconomia = economiaTotal;
                melhorComprador = comprador;
            }
            if (economiaTotal < menorEconomia) {
                menorEconomia = economiaTotal;
                piorComprador = comprador;
            }
        });

        // Renderizar card do melhor comprador
        const melhorCompradorElement = document.getElementById('melhor-comprador');
        if (melhorCompradorElement && melhorComprador) {
            // Garantir que os valores sejam números
            const valorInicial = parseFloat(melhorComprador.valor_inicial_total || 0);
            const economiaTotal = parseFloat(melhorComprador.economia_total || 0);
            const valorFinal = parseFloat(melhorComprador.valor_final_total || 0);
            const rodadas = parseInt(melhorComprador.total_rodadas || 0);
            const registros = parseInt(melhorComprador.total_registros || 0);
            
            // Calcular economia percentual
            const economiaPercentualMelhor = valorInicial > 0 ? 
                (economiaTotal / valorInicial * 100) : 0;

            melhorCompradorElement.innerHTML = `
                <div class="comprador-card melhor">
                    <div class="comprador-nome">
                        ${melhorComprador.comprador_nome || 'Sem nome'}
                        <span class="comprador-badge melhor"><i class="fas fa-trophy"></i> Melhor Comprador</span>
                    </div>
                    <div class="comprador-metrica economia">
                        <span class="comprador-metrica-label">Economia Total:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(economiaTotal)}</span>
                    </div>
                    <div class="comprador-metrica">
                        <span class="comprador-metrica-label">Economia (%):</span>
                        <span class="comprador-metrica-valor">${economiaPercentualMelhor.toFixed(2)}%</span>
                    </div>
                    <div class="comprador-metrica negociado">
                        <span class="comprador-metrica-label">Total Negociado:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(valorInicial)}</span>
                    </div>
                    <div class="comprador-metrica aprovado">
                        <span class="comprador-metrica-label">Total Aprovado:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(valorFinal)}</span>
                    </div>
                    <div class="comprador-metrica rodadas">
                        <span class="comprador-metrica-label">Total de Rodadas:</span>
                        <span class="comprador-metrica-valor">${rodadas}</span>
                    </div>
                    <div class="comprador-metrica">
                        <span class="comprador-metrica-label">Total de Registros:</span>
                        <span class="comprador-metrica-valor">${registros}</span>
                    </div>
                </div>
            `;
        }

        // Renderizar card do pior comprador
        const piorCompradorElement = document.getElementById('pior-comprador');
        if (piorCompradorElement && piorComprador) {
            // Garantir que os valores sejam números
            const valorInicial = parseFloat(piorComprador.valor_inicial_total || 0);
            const economiaTotal = parseFloat(piorComprador.economia_total || 0);
            const valorFinal = parseFloat(piorComprador.valor_final_total || 0);
            const rodadas = parseInt(piorComprador.total_rodadas || 0);
            const registros = parseInt(piorComprador.total_registros || 0);
            
            // Calcular economia percentual
            const economiaPercentualPior = valorInicial > 0 ? 
                (economiaTotal / valorInicial * 100) : 0;

            piorCompradorElement.innerHTML = `
                <div class="comprador-card pior">
                    <div class="comprador-nome">
                        ${piorComprador.comprador_nome || 'Sem nome'}
                        <span class="comprador-badge pior"><i class="fas fa-exclamation-circle"></i> Pior Comprador</span>
                    </div>
                    <div class="comprador-metrica economia">
                        <span class="comprador-metrica-label">Economia Total:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(economiaTotal)}</span>
                    </div>
                    <div class="comprador-metrica">
                        <span class="comprador-metrica-label">Economia (%):</span>
                        <span class="comprador-metrica-valor">${economiaPercentualPior.toFixed(2)}%</span>
                    </div>
                    <div class="comprador-metrica negociado">
                        <span class="comprador-metrica-label">Total Negociado:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(valorInicial)}</span>
                    </div>
                    <div class="comprador-metrica aprovado">
                        <span class="comprador-metrica-label">Total Aprovado:</span>
                        <span class="comprador-metrica-valor">R$ ${formatarNumero(valorFinal)}</span>
                    </div>
                    <div class="comprador-metrica rodadas">
                        <span class="comprador-metrica-label">Total de Rodadas:</span>
                        <span class="comprador-metrica-valor">${rodadas}</span>
                    </div>
                    <div class="comprador-metrica">
                        <span class="comprador-metrica-label">Total de Registros:</span>
                        <span class="comprador-metrica-valor">${registros}</span>
                    </div>
                </div>
            `;
        }

        // Renderizar os demais cards de compradores
        const compradoresCards = document.getElementById('compradores-cards');
        if (compradoresCards) {
            let html = '';
            resumo.compradores.forEach(comprador => {
                // Pular o melhor e o pior comprador, pois já foram renderizados
                if (comprador === melhorComprador || comprador === piorComprador) {
                    return;
                }

                // Garantir que os valores sejam números
                const valorInicial = parseFloat(comprador.valor_inicial_total || 0);
                const economiaTotal = parseFloat(comprador.economia_total || 0);
                const valorFinal = parseFloat(comprador.valor_final_total || 0);
                const rodadas = parseInt(comprador.total_rodadas || 0);
                const registros = parseInt(comprador.total_registros || 0);
                
                // Calcular economia percentual
                const economiaPercentual = valorInicial > 0 ? 
                    (economiaTotal / valorInicial * 100) : 0;

                html += `
                    <div class="comprador-card">
                        <div class="comprador-nome">
                            ${comprador.comprador_nome || 'Sem nome'}
                        </div>
                        <div class="comprador-metrica economia">
                            <span class="comprador-metrica-label">Economia Total:</span>
                            <span class="comprador-metrica-valor">R$ ${formatarNumero(economiaTotal)}</span>
                        </div>
                        <div class="comprador-metrica">
                            <span class="comprador-metrica-label">Economia (%):</span>
                            <span class="comprador-metrica-valor">${economiaPercentual.toFixed(2)}%</span>
                        </div>
                        <div class="comprador-metrica negociado">
                            <span class="comprador-metrica-label">Total Negociado:</span>
                            <span class="comprador-metrica-valor">R$ ${formatarNumero(valorInicial)}</span>
                        </div>
                        <div class="comprador-metrica aprovado">
                            <span class="comprador-metrica-label">Total Aprovado:</span>
                            <span class="comprador-metrica-valor">R$ ${formatarNumero(valorFinal)}</span>
                        </div>
                        <div class="comprador-metrica rodadas">
                            <span class="comprador-metrica-label">Total de Rodadas:</span>
                            <span class="comprador-metrica-valor">${rodadas}</span>
                        </div>
                        <div class="comprador-metrica">
                            <span class="comprador-metrica-label">Total de Registros:</span>
                            <span class="comprador-metrica-valor">${registros}</span>
                        </div>
                    </div>
                `;
            });
            compradoresCards.innerHTML = html;
        }
    }

    // Atualizar os cards de resumo com os registros
    if (resumo.registros) {
        atualizarResumoCards(resumo.registros);
    }
}

// Função para atualizar os cards de resumo
function atualizarResumoCards(data) {
    console.log('Dados recebidos em atualizarResumoCards:', data);
    
    // Contar cotações por tipo
    let totalProgramada = 0;
    let totalEmergencial = 0;
    
    // Verificar se data é um array
    if (Array.isArray(data)) {
        console.log('Data é um array, número de itens:', data.length);
        
        data.forEach((item, index) => {
            console.log(`Item ${index}:`, item);
            console.log(`Tipo do item ${index}:`, item.tipo);
            
            // Verificar se o item tem a propriedade tipo
            if (item.tipo) {
                if (item.tipo.toLowerCase() === 'programada') {
                    totalProgramada++;
                    console.log('Incrementando programada, total atual:', totalProgramada);
                } else if (item.tipo.toLowerCase() === 'emergencial') {
                    totalEmergencial++;
                    console.log('Incrementando emergencial, total atual:', totalEmergencial);
                }
            }
        });
    } else {
        console.log('Data não é um array:', typeof data);
    }
    
    console.log('Totais finais - Programada:', totalProgramada, 'Emergencial:', totalEmergencial);
    
    // Atualizar os cards de tipo
    const totalProgramadaElement = document.getElementById('total-programada');
    const totalEmergencialElement = document.getElementById('total-emergencial');
    
    console.log('Elementos DOM encontrados:', {
        totalProgramadaElement: !!totalProgramadaElement,
        totalEmergencialElement: !!totalEmergencialElement
    });
    
    if (totalProgramadaElement) {
        totalProgramadaElement.textContent = totalProgramada;
        console.log('Atualizado total programada para:', totalProgramada);
    }
    
    if (totalEmergencialElement) {
        totalEmergencialElement.textContent = totalEmergencial;
        console.log('Atualizado total emergencial para:', totalEmergencial);
    }
}

// Função para traduzir status
function traduzirStatus(status) {
    const traducoes = {
        'pendente': 'Pendente',
        'em_andamento': 'Em Andamento',
        'concluido': 'Concluído',
        'cancelado': 'Cancelado'
    };
    
    return traducoes[status] || status;
}

// Função para formatar valor monetário
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Função para carregar a comparação de produtos
async function carregarComparacaoProdutos(sawingId, dataRegistro) {
    console.log('Carregando comparação para sawing:', sawingId, 'data:', dataRegistro);
    
    try {
        const response = await fetch(`api/sawing/buscar_ultimo_aprovado.php?sawing_id=${sawingId}&data_registro=${dataRegistro}`);
        const data = await response.json();
        
        console.log('Resposta da API:', data);
        
        if (data.error) {
            console.error('Erro ao buscar comparação:', data.error);
            document.getElementById('comparacao-container').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${data.error}
                </div>
            `;
            return;
        }

        // Limpar container de comparação
        const container = document.getElementById('comparacao-container');
        if (!container) return;
        
        container.innerHTML = '';

        // Remover qualquer resumo de comparação existente
        const resumosExistentes = document.querySelectorAll('.resumo-comparacao');
        resumosExistentes.forEach(resumo => resumo.remove());

        // Se não houver comparações, mostrar mensagem
        if (!data.comparacoes || data.comparacoes.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nenhum sawing anterior encontrado com produtos similares
                </div>
            `;
            return;
        }

        // Calcular resumo da comparação
        let totalEconomia = 0;
        let totalProdutos = 0;
        let produtosComEconomia = 0;
        let maiorEconomia = 0;
        let maiorEconomiaProduto = '';

        data.comparacoes.forEach(comparacao => {
            data.produtos_atuais.forEach(produtoAtual => {
                const produtoAnterior = comparacao.produtos_anteriores.find(p => p.descricao === produtoAtual.descricao);
                if (produtoAnterior) {
                    const valorAtual = parseFloat(produtoAtual.valor_unitario_final);
                    const valorAnterior = parseFloat(produtoAnterior.valor_unitario_final);
                    const economia = valorAnterior - valorAtual;
                    
                    if (economia > 0) {
                        totalEconomia += economia;
                        produtosComEconomia++;
                        if (economia > maiorEconomia) {
                            maiorEconomia = economia;
                            maiorEconomiaProduto = produtoAtual.descricao;
                        }
                    }
                    totalProdutos++;
                }
            });
        });

        // Adicionar resumo da comparação ao cabeçalho do modal
        const resumoComparacao = document.createElement('div');
        resumoComparacao.className = 'resumo-comparacao';
        resumoComparacao.innerHTML = `
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Economia vs. Cotações Anteriores</div>
                    <div class="value ${totalEconomia > 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                        R$ ${formatarNumero(totalEconomia)}
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Produtos com Preço Menor que Anteriores</div>
                    <div class="value">
                        ${produtosComEconomia} de ${totalProdutos}
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Maior Redução de Preço</div>
                    <div class="value">
                        R$ ${formatarNumero(maiorEconomia)}
                    </div>
                </div>
            </div>
        `;

        // Inserir o resumo após o primeiro info-grid no modal
        const primeiroInfoGrid = document.querySelector('#modalDetalhesSawing .info-grid');
        if (primeiroInfoGrid) {
            primeiroInfoGrid.parentNode.insertBefore(resumoComparacao, primeiroInfoGrid.nextSibling);
        }

        // Criar botão de toggle
        const toggleButton = document.createElement('button');
        toggleButton.className = 'btn-toggle-comparacao';
        toggleButton.innerHTML = `
            <i class="fas fa-chevron-down"></i> Mostrar Comparação
        `;
        container.appendChild(toggleButton);

        // Criar div para conteúdo da comparação
        const comparacaoContent = document.createElement('div');
        comparacaoContent.className = 'comparacao-content';
        comparacaoContent.style.display = 'none';
        container.appendChild(comparacaoContent);

        // Adicionar evento de clique ao botão
        toggleButton.addEventListener('click', () => {
            const isVisible = comparacaoContent.style.display !== 'none';
            comparacaoContent.style.display = isVisible ? 'none' : 'block';
            toggleButton.innerHTML = `
                <i class="fas fa-chevron-${isVisible ? 'down' : 'up'}"></i>
                ${isVisible ? 'Mostrar' : 'Ocultar'} Comparação
            `;
        });

        // Criar cards de comparação para cada sawing anterior
        data.comparacoes.forEach((comparacao, index) => {
            const card = document.createElement('div');
            card.className = 'comparacao-card';
            card.innerHTML = `
                <div class="comparacao-header">
                    <h4>Comparação com Sawing Aprovado</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">Produto Atual</div>
                            <div class="value">${new Date(dataRegistro).toLocaleDateString('pt-BR')}</div>
                        </div>
                        <div class="info-item">
                            <div class="label">Último Aprovado</div>
                            <div class="value">${new Date(comparacao.data_registro).toLocaleDateString('pt-BR')}</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="tabela-comparacao">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Último Aprovado | Fornecedor</th>
                                <th>Atual Aprovado | Fornecedor</th>
                                <th>Economia</th>
                            </tr>
                        </thead>
                        <tbody id="comparacao-body-${index}">
                        </tbody>
                    </table>
                </div>
            `;
            comparacaoContent.appendChild(card);

            // Preencher tabela de comparação
            preencherTabelaComparacao(
                data.produtos_atuais,
                comparacao.produtos_anteriores,
                `comparacao-body-${index}`
            );
        });

    } catch (error) {
        console.error('Erro ao carregar comparação:', error);
        const container = document.getElementById('comparacao-container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Erro ao carregar comparação
                </div>
            `;
        }
    }
}

// Função para preencher a tabela de comparação
function preencherTabelaComparacao(produtosAtuais, produtosAnteriores, containerId) {
    const tbody = document.getElementById(containerId);
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    produtosAtuais.forEach(produtoAtual => {
        const produtoAnterior = produtosAnteriores.find(p => p.descricao === produtoAtual.descricao);
        if (produtoAnterior) {
            const valorAtual = parseFloat(produtoAtual.valor_unitario_final);
            const valorAnterior = parseFloat(produtoAnterior.valor_unitario_final);
            const economia = valorAnterior - valorAtual;
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${produtoAtual.descricao}</td>
                <td>${produtoAtual.quantidade}</td>
                <td>
                    <div class="valor-fornecedor">
                        <span class="valor">R$ ${formatarNumero(valorAnterior)}</span>
                        <span class="fornecedor">${produtoAnterior.fornecedor}</span>
                    </div>
                </td>
                <td>
                    <div class="valor-fornecedor">
                        <span class="valor">R$ ${formatarNumero(valorAtual)}</span>
                        <span class="fornecedor">${produtoAtual.fornecedor}</span>
                    </div>
                </td>
                <td class="${economia > 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                    R$ ${formatarNumero(economia)}
                </td>
            `;
            tbody.appendChild(tr);
        }
    });
}

// Função para ver detalhes de um registro
async function verDetalhes(id) {
    try {
        const response = await fetch(`api/sawing.php?id=${id}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        // Verificar se os dados do sawing existem
        if (!data) {
            throw new Error('Dados do sawing não encontrados');
        }

        // Preencher os dados do modal
        document.getElementById('sawing-id').textContent = data.id || '';
        document.getElementById('sawing-data').textContent = formatarData(data.data_registro);
        
        // Exibir data de aprovação se existir
        const dataAprovacao = data.data_aprovacao ? formatarData(data.data_aprovacao) : 'Não aprovado';
        document.getElementById('sawing-data-aprovacao').textContent = dataAprovacao;
            
        // Formatar valores monetários
        const valorInicial = parseFloat(data.valor_total_inicial || 0);
        const valorFinal = parseFloat(data.valor_total_final || 0);
        const economia = parseFloat(data.economia || 0);
        
        document.getElementById('sawing-valor-inicial').textContent = `R$ ${formatarNumero(valorInicial)}`;
        document.getElementById('sawing-valor-final').textContent = `R$ ${formatarNumero(valorFinal)}`;
        document.getElementById('sawing-economia').textContent = `R$ ${formatarNumero(economia)}`;
        
        // Verificar se economia_percentual é um número antes de chamar toFixed
        const economiaPercentual = parseFloat(data.economia_percentual || 0);
        document.getElementById('sawing-economia-percentual').textContent = `${isNaN(economiaPercentual) ? '0.00' : economiaPercentual.toFixed(2)}%`;
        
        // Adicionar classe de cor para economia
        const economiaElement = document.getElementById('sawing-economia');
        if (economia > 0) {
            economiaElement.classList.add('variacao-positiva');
        } else if (economia < 0) {
            economiaElement.classList.add('variacao-negativa');
        } else {
            economiaElement.classList.add('variacao-neutra');
        }
        
        // Adicionar classe de cor para economia percentual
        const economiaPercentualElement = document.getElementById('sawing-economia-percentual');
        if (economiaPercentual > 0) {
            economiaPercentualElement.classList.add('variacao-positiva');
        } else if (economiaPercentual < 0) {
            economiaPercentualElement.classList.add('variacao-negativa');
        } else {
            economiaPercentualElement.classList.add('variacao-neutra');
        }
        
        document.getElementById('sawing-rodadas').textContent = data.rodadas || '1';
        
        // Formatar status com classe de cor
        const statusElement = document.getElementById('sawing-status');
        const status = data.status || 'Pendente';
        statusElement.textContent = status;
        
        // Adicionar classe de cor para status
        statusElement.className = ''; // Limpar classes existentes
        if (status.toLowerCase() === 'concluido') {
            statusElement.classList.add('variacao-positiva');
        } else if (status.toLowerCase() === 'cancelado') {
            statusElement.classList.add('variacao-negativa');
        } else if (status.toLowerCase() === 'em_andamento') {
            statusElement.classList.add('variacao-neutra');
        }

        // Adicionar tipo de compra
        const tipoElement = document.getElementById('sawing-tipo');
        const tipo = data.tipo || 'programada';
        const badgeClass = tipo === 'emergencial' ? 'badge-warning' : 'badge-info';
        const badgeIcon = tipo === 'emergencial' ? 'fa-exclamation-circle' : 'fa-calendar';
        const badgeText = tipo === 'emergencial' ? 'Emergencial' : 'Programada';
        
        tipoElement.innerHTML = `
            <span class="badge ${badgeClass}">
                <i class="fas ${badgeIcon}"></i>
                ${badgeText}
            </span>
        `;

        // Mostrar/ocultar justificativa emergencial
        const justificativaContainer = document.getElementById('justificativa-emergencial-container');
        const justificativaElement = document.getElementById('sawing-justificativa-emergencial');
        
        if (tipo === 'emergencial' && data.motivo_emergencial) {
            justificativaElement.textContent = data.motivo_emergencial;
            justificativaContainer.style.display = 'block';
        } else {
            justificativaContainer.style.display = 'none';
        }
        
        document.getElementById('sawing-observacoes').textContent = data.observacoes || 'Nenhuma observação';

        // Renderizar os produtos
        if (data.produtos && data.produtos.length > 0) {
            renderizarProdutos(data.produtos.map(produto => ({
                ...produto,
                valor_unitario_inicial: produto.valor_unitario_inicial || 0,
                valor_unitario_final: produto.valor_unitario_final || 0
            })));
        } else {
            renderizarProdutos([]);
        }

        // Carregar a comparação com o último sawing aprovado
        if (data.id && data.data_registro) {
            carregarComparacaoProdutos(data.id, data.data_registro);
        }

        // Mostrar o modal
        const modal = document.getElementById('modalDetalhesSawing');
        modal.style.display = 'block';
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        alert('Erro ao carregar os detalhes do sawing: ' + error.message);
    }
}

// Função para renderizar produtos no modal de detalhes
function renderizarProdutos(produtos) {
    const container = document.getElementById('produtos-container');
    if (!container) return;

    let html = `
        <div class="table-responsive">
            <table class="itens-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Inicial</th>
                        <th>Valor Final</th>
                        <th>Economia</th>
                        <th>Fornecedor</th>
                    </tr>
                </thead>
                <tbody>
    `;

    produtos.forEach(produto => {
        const economia = produto.valor_unitario_inicial - produto.valor_unitario_final;
        const economiaPercentual = produto.valor_unitario_inicial > 0 ? 
            (economia / produto.valor_unitario_inicial * 100) : 0;
        
        // Determinar a classe de economia para estilização
        const economiaClass = economia > 0 ? 'variacao-positiva' : 
                            (economia < 0 ? 'variacao-negativa' : 'variacao-neutra');

        html += `
            <tr>
                <td>${produto.produto_nome}</td>
                <td>${produto.quantidade}</td>
                <td>R$ ${formatarNumero(produto.valor_unitario_inicial)}</td>
                <td>R$ ${formatarNumero(produto.valor_unitario_final)}</td>
            <td class="${economiaClass}">
                    R$ ${formatarNumero(economia)}
                <span class="economia-percentual">(${economiaPercentual.toFixed(2)}%)</span>
            </td>
                <td>${produto.fornecedor_nome}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

// Função auxiliar para formatar números
function formatarNumero(valor) {
    // Verificar se o valor é nulo, indefinido ou não é um número
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '0,00';
    }
    
    // Converter para número e formatar
    return parseFloat(valor).toFixed(2).replace('.', ',');
}

// Função para criar gráfico de economia
function criarGraficoEconomia(produtos) {
    // Destruir gráfico anterior se existir
    if (window.economiaChart) {
        window.economiaChart.destroy();
    }
    
    if (!produtos || produtos.length === 0) {
        document.getElementById('grafico-economia').innerHTML = 'Nenhum dado disponível para gráfico.';
        return;
    }
    
    // Preparar dados para o gráfico
    const labels = produtos.map(p => p.nome);
    const valoresIniciais = produtos.map(p => parseFloat(p.valor_total_inicial) || 0);
    const valoresFinais = produtos.map(p => parseFloat(p.valor_total_final) || 0);
    
    // Criar o gráfico
    const ctx = document.getElementById('grafico-economia').getContext('2d');
    window.economiaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Valor Inicial',
                    data: valoresIniciais,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Valor Final',
                    data: valoresFinais,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor (R$)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Produtos'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Comparação de Valores por Produto'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', {
                                    style: 'currency',
                                    currency: 'BRL'
                                }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// Função para renderizar histórico de rodadas
function renderizarHistoricoRodadas(historico) {
    const container = document.getElementById('historico-rodadas');
    
    if (!historico || historico.length === 0) {
        container.innerHTML = '<p>Nenhum histórico de rodadas disponível.</p>';
        return;
    }
    
    let html = '';
    
    historico.forEach((rodada, index) => {
        const dataFormatada = new Date(rodada.data).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">${dataFormatada}</div>
                    <div class="timeline-title">Rodada ${index + 1}</div>
                    <div class="timeline-description">
                        <p>Valor: ${parseFloat(rodada.valor).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        })}</p>
                        <p>Economia acumulada: ${parseFloat(rodada.economia_acumulada || 0).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        })} (${parseFloat(rodada.economia_percentual || 0).toFixed(2)}%)</p>
                        ${rodada.observacao ? `<p>Observação: ${rodada.observacao}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Função para alternar entre as abas
function mudarTab(tabId) {
    // Esconder todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remover classe ativa de todos os botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar a aba selecionada
    document.getElementById(tabId).classList.add('active');
    
    // Adicionar classe ativa ao botão correspondente
    document.querySelector(`.tab-btn[onclick="mudarTab('${tabId}')"]`).classList.add('active');
    
    // Se for a aba de gráfico, redimensionar para garantir que seja exibido corretamente
    if (tabId === 'tab-grafico' && window.economiaChart) {
        window.economiaChart.resize();
    }
}

// Função para exportar relatório detalhado
function exportarRelatorioDetalhado(id) {
    window.open(`api/sawing.php?id=${id}&exportar=pdf`, '_blank');
}

// Configurar eventos do modal
document.addEventListener('DOMContentLoaded', function() {
    // Fechar o modal quando clicar no X
    
    // Fechar o modal quando clicar no botão Fechar
    const btnFechar = document.getElementById('btn-fechar-modal');
    if (btnFechar) {
        btnFechar.addEventListener('click', function() {
            document.getElementById('modalDetalhes').style.display = 'none';
        });
    }
    
    // Fechar o modal quando clicar fora dele
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalDetalhes');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Configurar botão de exportar relatório
    const btnExportarDetalhes = document.getElementById('btn-exportar-detalhes');
    if (btnExportarDetalhes) {
        btnExportarDetalhes.addEventListener('click', function() {
            const id = document.getElementById('detalhe-id').textContent;
            exportarRelatorioDetalhado(id);
        });
    }
    
    // Carregar dados iniciais
    carregarDados();
});

// Configurar eventos do modal
document.addEventListener('DOMContentLoaded', function() {
    // Fechar o modal quando clicar no X
    
    // Fechar o modal quando clicar no botão Fechar
    const btnFechar = document.getElementById('btn-fechar-modal');
    if (btnFechar) {
        btnFechar.addEventListener('click', function() {
            document.getElementById('modalDetalhes').style.display = 'none';
        });
    }
    
    // Fechar o modal quando clicar fora dele
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalDetalhes');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Carregar dados iniciais
    carregarDados();
    
    // Configurar eventos de filtro
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
    const btnLimparFiltros = document.getElementById('btn-limpar-filtros');
    const btnExportar = document.getElementById('btn-exportar');
    
    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', aplicarFiltros);
    }
    
    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', limparFiltros);
    }
    
    if (btnExportar) {
        btnExportar.addEventListener('click', exportarExcel);
    }
});

// Função para aplicar filtros
function aplicarFiltros() {
    console.log('Aplicando filtros...');
    const filtros = obterFiltros();
    console.log('URL com filtros:', `api/sawing.php?pagina=1&limite=10${filtros}`);
    carregarDados(1); // Voltar para a primeira página ao aplicar filtros
}

// Função para limpar filtros
function limparFiltros() {
    // Limpar campos de filtro
    document.getElementById('filtro-comprador').value = '';
    document.getElementById('filtro-tipo').value = '';
    document.getElementById('data-inicio').value = '';
    document.getElementById('data-fim').value = '';
    
    // Recarregar dados
    carregarDados(1);
}

// Função para exportar
function exportarExcel() {
    // Mostrar modal de opções de exportação
    const modal = document.getElementById('modal-exportar');
    modal.style.display = 'block';
    
    // Configurar eventos do modal
    const btnCancelar = modal.querySelector('.btn-cancelar');
    const btnExportar = modal.querySelector('.btn-exportar');
    const btnFechar = modal.querySelector('.close');
    
    btnCancelar.onclick = () => {
        modal.style.display = 'none';
    };
    
    btnFechar.onclick = () => {
        modal.style.display = 'none';
    };
    
    btnExportar.onclick = () => {
        const formato = document.querySelector('input[name="formato"]:checked').value;
        const opcoesBasicas = Array.from(document.querySelectorAll('input[name="basicas"]:checked')).map(cb => cb.value);
        const opcoesDetalhes = Array.from(document.querySelectorAll('input[name="detalhes"]:checked')).map(cb => cb.value);
        const opcoesMetricas = Array.from(document.querySelectorAll('input[name="metricas"]:checked')).map(cb => cb.value);
        
        // Construir URL com as opções selecionadas
        let url = 'api/sawing.php?exportar=excel';
        url += '&formato=' + formato;
        
        if (opcoesBasicas.length > 0) {
            url += '&basicas=' + opcoesBasicas.join(',');
        }
        
        if (opcoesDetalhes.length > 0) {
            url += '&detalhes=' + opcoesDetalhes.join(',');
        }
        
        if (opcoesMetricas.length > 0) {
            url += '&metricas=' + opcoesMetricas.join(',');
        }
        
        // Adicionar filtros ativos
    const filtros = obterFiltros();
        if (filtros) {
            url += '&' + filtros;
        }
        
        // Fechar modal e iniciar download
        modal.style.display = 'none';
        window.location.href = url;
    };
    
    // Fechar modal ao clicar fora
    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

function obterOpcoesExportacao() {
    const formato = document.querySelector('input[name="formato"]:checked').value;
    const opcoes = {
        formato: formato,
        basicas: Array.from(document.querySelectorAll('input[name="basicas"]:checked')).map(cb => cb.value),
        detalhes: Array.from(document.querySelectorAll('input[name="detalhes"]:checked')).map(cb => cb.value),
        metricas: Array.from(document.querySelectorAll('input[name="metricas"]:checked')).map(cb => cb.value)
    };
    return opcoes;
}

// Event Listeners para o Modal de Exportação
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-exportar');
    const btnCancelar = document.querySelector('#modal-exportar .btn-cancelar');
    const btnExportar = document.querySelector('#modal-exportar .btn-exportar');
    const closeBtn = document.querySelector('#modal-exportar .close');
    
    if (modal && btnCancelar && btnExportar && closeBtn) {
        // Fechar modal ao clicar no X ou no botão Cancelar
        btnCancelar.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        // Fechar modal ao clicar fora dele
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Exportar ao clicar no botão Exportar
        btnExportar.addEventListener('click', () => {
            exportarComOpcoes();
        });
    }
});

// Carregar dados ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    carregarDados();
    carregarCompradores();
    
    // Configurar eventos de filtro - verificar se os elementos existem
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
    const btnLimparFiltros = document.getElementById('btn-limpar-filtros');
    const btnExportar = document.getElementById('btn-exportar');
    
    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', aplicarFiltros);
    }
    
    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', limparFiltros);
    }
    
    if (btnExportar) {
        btnExportar.addEventListener('click', exportarExcel);
    }
});

function exportarComOpcoes() {
    const opcoes = obterOpcoesExportacao();
    const filtros = obterFiltros();
    
    // Construir URL com as opções selecionadas
    let url = `api/sawing.php?acao=exportar&formato=${opcoes.formato}`;
    
    // Adicionar opções selecionadas
    if (opcoes.basicas.length > 0) {
        url += `&basicas=${opcoes.basicas.join(',')}`;
    }
    if (opcoes.detalhes.length > 0) {
        url += `&detalhes=${opcoes.detalhes.join(',')}`;
    }
    if (opcoes.metricas.length > 0) {
        url += `&metricas=${opcoes.metricas.join(',')}`;
    }
    
    // Adicionar filtros
    if (filtros) {
        url += `&${filtros}`;
    }
    
    // Iniciar download
    window.location.href = url;
    
    // Fechar modal
    const modal = document.getElementById('modal-exportar');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Função para formatar data
function formatarData(dataString) {
    if (!dataString) return '';
    
    const data = new Date(dataString);
    
    // Verificar se a data é válida
    if (isNaN(data.getTime())) {
        return dataString; // Retornar a string original se não for uma data válida
    }
    
    // Formatar para dd/mm/yyyy hh:mm
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');
    
    return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
}

// Adicionar event listeners para fechar o modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalDetalhesSawing');
    const btnFechar = document.getElementById('btn-fechar-modal');
    const btnFecharFooter = document.getElementById('btn-fechar-modal-footer');

    if (btnFechar) {
        btnFechar.onclick = function() {
            modal.style.display = 'none';
        }
    }

    if (btnFecharFooter) {
        btnFecharFooter.onclick = function() {
            modal.style.display = 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});

// Função para carregar compradores
function carregarCompradores() {
    fetch('api/sawing.php?acao=listar_compradores')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.compradores) {
                const select = document.getElementById('filtro-comprador');
                if (select) {
                    // Manter a opção "Todos"
                    select.innerHTML = '<option value="">Todos</option>';
                    
                    // Adicionar os compradores
                    data.compradores.forEach(comprador => {
                        const option = document.createElement('option');
                        option.value = comprador.id;
                        option.textContent = comprador.nome;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar compradores:', error);
        });
}

// Função para renderizar a comparação com último aprovado
function renderizarComparacao(comparacao) {
    const container = document.getElementById('comparacao-container');
    if (!container) return;

    // Agrupar produtos por data do último aprovado
    const produtosAgrupados = {};
    comparacao.forEach(item => {
        const dataAprovacao = item.data_ultimo_aprovado;
        if (!produtosAgrupados[dataAprovacao]) {
            produtosAgrupados[dataAprovacao] = [];
        }
        produtosAgrupados[dataAprovacao].push(item);
    });

    let html = '';
    
    // Ordenar as datas em ordem decrescente
    const datasOrdenadas = Object.keys(produtosAgrupados).sort((a, b) => new Date(b) - new Date(a));
    
    datasOrdenadas.forEach(dataAprovacao => {
        const produtos = produtosAgrupados[dataAprovacao];
        const dataFormatada = formatarData(dataAprovacao);
        
        html += `
            <div class="comparacao-grupo">
                <div class="comparacao-header">
                    <h4>Comparação com Sawing Aprovado</h4>
                    <div class="comparacao-datas">
                        <div class="data-atual">
                            <strong>Produto Atual:</strong> ${formatarData(produtos[0].data_atual)}
                        </div>
                        <div class="data-ultima">
                            <strong>Último Aprovado:</strong> ${dataFormatada}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Último Aprovado</th>
                                <th>Atual Aprovado</th>
                                <th>Economia</th>
                                <th>Fornecedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${produtos.map(item => `
                                <tr>
                                    <td>${item.produto}</td>
                                    <td>${item.quantidade}</td>
                                    <td>${formatarMoeda(item.valor_ultimo_aprovado)}</td>
                                    <td>${formatarMoeda(item.valor_atual)}</td>
                                    <td class="${item.economia >= 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                                        ${formatarMoeda(item.economia)} (${item.percentual_economia}%)
                                    </td>
                                    <td>${item.fornecedor}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}




