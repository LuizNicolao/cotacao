// Modificação do arquivo cotacoes.js para remover a busca de produtos via API

window.adicionarProduto = null; // Inicializa a variável global

// Global variables
let fornecedorCounter = 0;
let produtoCounter = 0;
let todosProdutos = []; // Array para armazenar todos os produtos
let currentCotacaoId = null;
let produtosImportados = [];
let excelProducts = [];


// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - cotacoes.js');

    // ==== BLOCO: Modal principal ====
    const modal = document.getElementById('modalCotacao');
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }

    const btnAdicionar = document.querySelector('.btn-adicionar');
    const btnClose = modal.querySelector('.close');
    const btnAddFornecedor = document.querySelector('.btn-adicionar-fornecedor');
    const formCotacao = document.getElementById('formCotacao');

    if (!btnAdicionar) console.error('Add button not found!');
    if (!btnClose) console.error('Close button not found!');
    if (!btnAddFornecedor) console.error('Add fornecedor button not found!');
    if (!formCotacao) console.error('Form not found!');

    // Abrir modal
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', function () {
            console.log('Add button clicked');
            modal.style.display = 'block';
            formCotacao.reset();
            document.getElementById('fornecedores-container').innerHTML = '';
        });
    }

    // Fechar modal
    if (btnClose) {
        btnClose.addEventListener('click', function () {
            console.log('Close button clicked');
            modal.style.display = 'none';
        });
    }

    // Adicionar fornecedor
    if (btnAddFornecedor) {
        btnAddFornecedor.addEventListener('click', function (e) {
            e.preventDefault();
        
            if (excelProducts.length === 0) {
                return;
            }
        
            const template = document.getElementById('template-fornecedor');
            const clone = template.content.cloneNode(true);
            const produtosContainer = clone.querySelector('.produtos-fornecedor');
        
            excelProducts.forEach(produto => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div class="produto-selected">${produto.Nome}</div>
                        <input type="hidden" class="produto-id" value="${produto.Codigo}">
                    </td>
                    <td><input type="number" class="quantidade" value="${produto.Qtde}" required></td>
                    <td class="unidade-medida">${produto.UN}</td>
                    <td><input type="number" class="valor-unitario" value="" step="0.0001" min="0" required></td>
                    <td class="valor-unit-difal-frete">0,0000</td> <!-- Nova coluna -->
                    <td class="total">0,0000</td>
                    <td>
                        <button type="button" class="btn-remover-produto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
            
                produtosContainer.appendChild(tr);
            
                // Recalcular o total ao digitar
                const valorInput = tr.querySelector('.valor-unitario');
                const quantidadeInput = tr.querySelector('.quantidade');
                const recalcular = () => {
                    const quantidade = parseFloat(quantidadeInput.value) || 0;
                    const valor = parseFloat(valorInput.value) || 0;
                    let total = quantidade * valor;
                    
                    // Não incluir o DIFAL no total exibido
                    tr.querySelector('.total').textContent = total.toFixed(3);
                    
                    // Calcular o valor com DIFAL para uso em outros lugares se necessário
                    const fornecedorSection = tr.closest('.fornecedor-section');
                    const difalInput = fornecedorSection?.querySelector('.difal-percentual');
                    const difal = parseFloat(difalInput?.value) || 0;
                    const acrescimo = (total * difal) / 100;
                    const totalComDifal = total + acrescimo;
                    
                    // Armazenar o valor com DIFAL como um atributo de dados para uso posterior
                    tr.setAttribute('data-total-com-difal', totalComDifal.toFixed(2));
                };
                
            
                quantidadeInput.addEventListener('input', recalcular);
                valorInput.addEventListener('input', recalcular);
            
                tr.querySelector('.btn-remover-produto').addEventListener('click', () => tr.remove());
            });            
            document.getElementById('fornecedores-container').appendChild(clone);
        });        
    }
    

    // Submissão do formulário
    if (formCotacao) {
        formCotacao.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Form submitted');
            salvarCotacao();
        });
    }

    // Visualizar cotação
    document.querySelectorAll('.btn-visualizar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            visualizarCotacao(id);
        });
    });

    // Editar cotação
    document.querySelectorAll('.btn-editar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            editarCotacao(id);
        });
    });

    attachVisualizarEventListeners();
    initializeModal();

    // ==== BLOCO: Botões de visualização e filtros ====
    console.log('Configurando event listeners para filtros e visualizações');

    const btnViewFornecedor = document.getElementById('modal-btn-view-fornecedor');
    const btnViewProduto = document.getElementById('modal-btn-view-produto');
    const btnViewComparativo = document.getElementById('modal-btn-view-comparativo');
    const btnAplicarFiltros = document.getElementById('modal-btn-aplicar-filtros');
    const btnMelhorOpcao = document.getElementById('modal-btn-melhor-opcao');
    const btnExportarXlsx = document.getElementById('modal-btn-exportar-xlsx');

    // ==== BLOCO: Importar cotação ====
    const btnImportarCotacao = document.getElementById('btn-importar-cotacao');
    const importarCotacaoId = document.getElementById('importar-cotacao-id');

    if (btnImportarCotacao) {
        btnImportarCotacao.addEventListener('click', function () {
            const cotacaoId = importarCotacaoId?.value.trim();
            importarCotacao(cotacaoId);
        });
    }

    if (importarCotacaoId) {
        importarCotacaoId.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const cotacaoId = this.value.trim();
                importarCotacao(cotacaoId);
            }
        });
    }

    document.getElementById('btn-importar-novos-produtos').addEventListener('click', async () => {
        const fileInput = document.getElementById('excelFile');
        if (!fileInput.files[0]) {
            alert('Selecione um arquivo para importar.');
            return;
        }
    
        const reader = new FileReader();
        reader.onload = async function (e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    
            excelProducts = [];
    
            // C-F (índices 2 a 5): Qtde, Código, Nome, UN
            rows.slice(1).forEach(row => {
                const [qtde, codigo, nome, unidade] = row.slice(2, 6);
    
                if (!codigo || !nome) {
                    console.warn('Produto ignorado (sem código ou nome):', row);
                    return;
                }
    
                excelProducts.push({ Qtde: qtde, Codigo: codigo, Nome: nome, UN: unidade });
            });
    
            for (const produto of novosProdutos) {
                try {
                    const res = await fetch(`api/produtos.php?search=${encodeURIComponent(produto.codigo)}`);
                    const resultado = await res.json();
                    const produtoInfo = resultado.find(p => p.codigo === produto.codigo);
    
                    if (!produtoInfo) {
                        console.warn(`Produto não encontrado no banco: ${produto.codigo}`);
                        continue;
                    }
    
    document.querySelectorAll('.fornecedor-section').forEach(sec => {
    const tabela = sec.querySelector('.produtos-fornecedor');

    const jaExiste = Array.from(tabela.querySelectorAll('tr')).some(tr => {
        const codigoNaTabela = tr.querySelector('.produto-id')?.value;
        return codigoNaTabela === produtoInfo.id;
    });

    if (jaExiste) return;

    const novaLinha = document.createElement('tr');
    novaLinha.innerHTML = `
        <td>
            <div class="produto-selected">${produto.nome}</div>
            <input type="hidden" class="produto-id" value="${produtoInfo.id}">
        </td>
        <td><input type="number" class="quantidade" value="${produto.qtde}" required></td>
        <td class="unidade-medida">${produto.unidade}</td>
        <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
        <td class="total">0.0000</td>
        <td>
            <button type="button" class="btn-remover-produto"><i class="fas fa-trash"></i></button>
        </td>
    `;

    // Adiciona a nova linha ao DOM primeiro
    tabela.appendChild(novaLinha);

    // Só agora que podemos usar .closest com segurança
    const fornecedorSection = novaLinha.closest('.fornecedor-section');
    const valorInput = novaLinha.querySelector('.valor-unitario');
    const quantidadeInput = novaLinha.querySelector('.quantidade');

    const recalcular = () => {
        const quantidade = parseFloat(quantidadeInput.value) || 0;
        const valorUnitario = parseFloat(valorInput.value) || 0;
        let total = quantidade * valorUnitario;

        const difalInput = fornecedorSection?.querySelector('.difal-percentual');
        const difal = parseFloat(difalInput?.value) || 0;

        const acrescimo = (total * difal) / 100;
        const totalFinal = total + acrescimo;

        novaLinha.querySelector('.total').textContent = totalFinal.toFixed(2);
    };

    quantidadeInput.addEventListener('input', recalcular);
    valorInput.addEventListener('input', recalcular);
    recalcular(); // calcular ao criar

    novaLinha.querySelector('.btn-remover-produto').addEventListener('click', () => {
        novaLinha.remove();
    });
});

                } catch (error) {
                    console.error(`Erro ao buscar/adicionar produto ${produto.codigo}:`, error);
                }
            }
    
            console.log('Produtos da planilha importados com sucesso.');
            alert('Produtos importados com sucesso!');
        };
    
        reader.readAsArrayBuffer(fileInput.files[0]);
    });    
});

// Reset form for new cotação
function resetForm() {
    document.getElementById('formCotacao').reset();
    document.getElementById('cotacaoId').value = '';
    document.getElementById('fornecedores-container').innerHTML = '';
    fornecedorCounter = 0;
    produtoCounter = 0;
}

// Add a new fornecedor
function adicionarFornecedor() {
    const template = document.getElementById('template-fornecedor');
    const clone = template.content.cloneNode(true);
    
    const container = document.getElementById('fornecedores-container');
    if (!container) {
        console.error("Container de fornecedores não encontrado.");
        return;
    }

    container.appendChild(clone);

    // Preencher os produtos importados nessa nova seção
    const novaTabela = container.lastElementChild.querySelector('.produtos-fornecedor');
    if (produtosImportados.length > 0) {
        produtosImportados.forEach(produto => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${produto.nome || produto.DESCRICAO || ''}</td>
                <td>${produto.quantidade || produto.QUANTIDADE || 0}</td>
                <td>${produto.unidade || produto.UNIDADE || 'UN'}</td>
                <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
                <td class="total">0.0000</td>
                <td>
                    <button type="button" class="btn-remover-produto">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            novaTabela.appendChild(row);
        });

        atualizarEventosCalculos(); // se necessário
    }
}


// Get fornecedores options from the template
function getFornecedoresOptions() {
    // Wait for template to be available
    const template = document.getElementById('template-fornecedor');
    if (!template) {
        // Return empty string if template not found
        console.log('Template not found, using empty options');
        return '';
    }
    
    // Get select element from template
    const select = template.content.querySelector('.fornecedor-select');
    if (!select) {
        console.log('Select element not found in template');
        return '';
    }
    
    return select.innerHTML;
}

// Get produtos options from the template
function getProdutosOptions() {
    const template = document.getElementById('template-produto');
    if (template) {
        const select = template.content.querySelector('.produto-select');
        return select.innerHTML;
    }
    return '';
}

// Function to add a new product with search functionality
function adicionarProdutoSelect(fornecedorId) {
    console.log('Adding produto to', fornecedorId);
    produtoCounter++;
    
    const produtosTable = document.querySelector('.fornecedor-section:last-child .produtos-fornecedor');
    const produtoId = 'produto-' + produtoCounter;
    
    const produtoHTML = `
    <tr id="${produtoId}">
        <td>
            <div class="produto-search-container">
                <input type="text" class="produto-search" placeholder="Buscar produto por código ou nome...">
                <div class="produto-results" style="display:none;"></div>
                <input type="hidden" class="produto-id" required>
                <div class="produto-selected"></div>
            </div>
        </td>
        <td><input type="number" class="quantidade" min="1" required onchange="calcularTotal('${produtoId}')"></td>
        <td class="unidade-medida">-</td>
        <td><input type="number" class="valor-unitario" step="0.0001" min="0" required onchange="calcularTotal('${produtoId}')"></td>
        <td class="total">0.0000</td>
        <td>
            <button type="button" class="btn-remover-produto" onclick="removerProduto('${produtoId}')">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
`;

    
    produtosTable.insertAdjacentHTML('beforeend', produtoHTML);
    
    // Adicionar funcionalidade de busca ao campo recém-criado
    const row = document.getElementById(produtoId);
    const searchInput = row.querySelector('.produto-search');
    const resultsContainer = row.querySelector('.produto-results');
    const hiddenInput = row.querySelector('.produto-id');
    const selectedContainer = row.querySelector('.produto-selected');
    
    // Carregar todos os produtos do template
    const produtos = [];
    const template = document.getElementById('template-produto');
    if (template) {
        const options = template.content.querySelectorAll('.produto-select option');
        options.forEach(option => {
            if (option.value) {
                produtos.push({
                    id: option.value,
                    codigo: option.getAttribute('data-codigo') || '',
                    nome: option.textContent,
                    unidade: option.getAttribute('data-unidade') || ''
                });
            }
        });
    }
    
    // Adicionar evento de input para busca
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm.length < 2) {
            resultsContainer.style.display = 'none';
            return;
        }
        
        // Buscar produtos via API
        fetch(`api/produtos.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(produtos => {
                // Mostrar resultados
                if (produtos.length > 0) {
                    resultsContainer.innerHTML = '';
                    produtos.forEach(produto => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'produto-result-item';
                        resultItem.innerHTML = `<span class="produto-codigo">${produto.codigo}</span> ${produto.nome}`;
                        resultItem.dataset.id = produto.id;
                        resultItem.dataset.codigo = produto.codigo;
                        resultItem.dataset.nome = produto.nome;
                        resultItem.dataset.unidade = produto.unidade;
                        
                        resultItem.addEventListener('click', function() {
                            // Preencher o input escondido com o ID do produto
                            hiddenInput.value = this.dataset.id;
                            
                            // Mostrar o produto selecionado
                            selectedContainer.innerHTML = `
                                <span class="produto-codigo">${this.dataset.codigo}</span> 
                                ${this.dataset.nome} 
                                <small>(${this.dataset.unidade})</small>
                            `;
                            
                            // Limpar e esconder resultados
                            searchInput.value = '';
                            resultsContainer.style.display = 'none';
                            
                            // Focar no campo de quantidade
                            const quantidadeInput = row.querySelector('.quantidade');
                            if (quantidadeInput) {
                                quantidadeInput.focus();
                            }
                        });
                        
                        resultsContainer.appendChild(resultItem);
                    });
                    
                    resultsContainer.style.display = 'block';
                } else {
                    resultsContainer.innerHTML = '<div class="produto-result-item">Nenhum produto encontrado</div>';
                    resultsContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar produtos:', error);
                resultsContainer.innerHTML = '<div class="produto-result-item">Erro ao buscar produtos</div>';
                resultsContainer.style.display = 'block';
            });
    });
    
    // Esconder resultados quando clicar fora
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
    
    // Focar no campo de busca
    setTimeout(() => {
        searchInput.focus();
    }, 100);
}

// Calculate total for a produto
function calcularTotal(produtoId) {
    const row = document.getElementById(produtoId);
    const quantidade = parseFloat(row.querySelector('.quantidade').value) || 0;
    const valorUnitario = parseFloat(row.querySelector('.valor-unitario').value) || 0;
    const total = quantidade * valorUnitario;
    row.querySelector('.total').textContent = total.toFixed(2);
}

    // Remove a produto
function removerProduto(produtoId) {
    document.getElementById(produtoId).remove();
}
function validarProduto(produto) {
    const errors = {
        nome: !produto.nome ? 'Nome do produto ausente' : null,
        quantidade: !(produto.quantidade > 0) ? 'Quantidade inválida' : null,
        valor_unitario: !(produto.valor_unitario > 0) ? 'Valor unitário inválido' : null
    };

    const isValid = !errors.nome && !errors.quantidade && !errors.valor_unitario;

    return { isValid, errors };
}
  // Save cotação
  function salvarCotacao() {
    console.log('Table structure before save:', document.querySelectorAll('.produtos-fornecedor tr').length);
    document.querySelectorAll('.produtos-fornecedor tr').forEach((row, index) => {
        console.log(`Row ${index}:`, row.innerHTML);
        console.log('Saving cotação');
    });

    // Verificar se há arquivos para upload
    const hasFiles = Array.from(document.querySelectorAll('.arquivo-cotacao')).some(input => input.files.length > 0);
    
    // Coletar dados dos fornecedores (comum para ambos os métodos)
    const fornecedores = [];
    
    document.querySelectorAll('.fornecedor-section').forEach((section, index) => {
        const fornecedor = {
            fornecedor_nome: section.querySelector('.fornecedor-input')?.value.trim().toUpperCase() || '',
            prazo_entrega: section.querySelector('.prazo-entrega')?.value || '',
            frete: parseFloat(section.querySelector('.frete-valor')?.value || 0),
            difal: parseFloat(section.querySelector('.difal-percentual')?.value || 0),
            prazo_pagamento: section.querySelector('.prazo-pagamento')?.value || '',
            produtos: []
        };
        
        // Verificar se há arquivo de cotação (apenas para o método FormData)
        if (hasFiles) {
            const arquivoCotacao = section.querySelector('.arquivo-cotacao')?.files[0];
            if (arquivoCotacao) {
                fornecedor.tem_arquivo = true;
                fornecedor.arquivo_index = index;
            }
        }
        
        section.querySelectorAll('.produtos-fornecedor tr').forEach(row => {
            const produto = {
                nome: row.querySelector('.produto-selected')?.textContent.trim(),
                codigo: row.querySelector('.produto-id')?.value || null,
                unidade: row.querySelector('.unidade-medida')?.textContent.trim() || 'UN',
                quantidade: parseFloat(row.querySelector('.quantidade')?.value),
                valor_unitario: parseFloat(row.querySelector('.valor-unitario')?.value)
            };
            
            console.log('Verificando produto:', produto);
            
            const validation = validarProduto(produto);
            
            if (validation.isValid) {
                fornecedor.produtos.push(produto);
            } else {
                const errors = Object.values(validation.errors).filter(e => e);
                console.warn('Produto inválido:', errors.join(', '), row);
                alert(`Produto inválido: ${errors.join(', ')}`);
            }
        });
        
        if (fornecedor.produtos.length > 0) {
            fornecedores.push(fornecedor);
        }
    });
    
    if (fornecedores.length === 0) {
        alert('Produto ignorado (faltando nome, quantidade ou valor');
        return;
    }
    
    const cotacaoId = document.getElementById('cotacaoId').value;
    
    // Antes de enviar, vamos buscar os valores originais para preservar o primeiro_valor
    if (cotacaoId) {
        fetch(`api/cotacoes.php?id=${cotacaoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar dados da cotação');
                }
                return response.json();
            })
            .then(data => {
                // Criar um mapa dos valores originais
                const valoresOriginais = {};
                if (data.itens && Array.isArray(data.itens)) {
                    data.itens.forEach(item => {
                        const key = `${item.produto_id}_${item.fornecedor_nome}`;
                        valoresOriginais[key] = {
                            primeiro_valor: item.primeiro_valor || item.valor_unitario,
                            ultimo_preco: item.valor_unitario
                        };
                    });
                }
                
                // Adicionar o primeiro_valor para cada produto
                fornecedores.forEach(fornecedor => {
                    fornecedor.produtos.forEach(produto => {
                        const key = `${produto.codigo}_${fornecedor.fornecedor_nome}`;
                        if (valoresOriginais[key] && valoresOriginais[key].primeiro_valor) {
                            // Se temos o valor original, preservá-lo
                            produto.primeiro_valor = valoresOriginais[key].primeiro_valor;
                            produto.ultimo_preco = valoresOriginais[key].ultimo_preco;
                        } else {
                            // Se é um novo produto, o primeiro valor é o valor atual
                            produto.primeiro_valor = produto.valor_unitario;
                            produto.ultimo_preco = null;
                        }
                    });
                });
                
                // Continuar com o envio dos dados
                enviarDadosCotacao(cotacaoId, fornecedores, hasFiles);
            })
            .catch(error => {
                console.error('Erro ao buscar dados originais da cotação:', error);
                // Continuar mesmo com erro, usando o valor atual como primeiro valor
                fornecedores.forEach(fornecedor => {
                    fornecedor.produtos.forEach(produto => {
                        produto.primeiro_valor = produto.valor_unitario;
                        produto.ultimo_preco = null;
                    });
                });
                
                // Continuar com o envio dos dados
                enviarDadosCotacao(cotacaoId, fornecedores, hasFiles);
            });
    } else {
        // Se é uma nova cotação, o primeiro valor é o valor atual
        fornecedores.forEach(fornecedor => {
            fornecedor.produtos.forEach(produto => {
                produto.primeiro_valor = produto.valor_unitario;
                produto.ultimo_preco = null;
            });
        });
        
        // Continuar com o envio dos dados
        enviarDadosCotacao(cotacaoId, fornecedores, hasFiles);
    }
}

// Função auxiliar para enviar os dados da cotação
function enviarDadosCotacao(cotacaoId, fornecedores, hasFiles) {
    // Obter o tipo de compra e motivo emergencial
    const tipoCompra = document.querySelector('input[name="tipo_compra"]:checked').value;
    let motivoEmergencial = '';
    
    if (tipoCompra === 'emergencial') {
        const justificativaPadrao = document.getElementById('justificativa_padrao').value;
        if (justificativaPadrao === 'outro') {
            motivoEmergencial = document.getElementById('justificativa_personalizada').value;
        } else {
            motivoEmergencial = justificativaPadrao;
        }
    }

    const dados = {
        id: cotacaoId || null,
        tipo: tipoCompra,
        motivo_emergencial: motivoEmergencial,
        fornecedores: fornecedores,
        versao: {
            dados_json: JSON.stringify({
                fornecedores: fornecedores,
                data_criacao: new Date().toISOString(),
                usuario_id: getCurrentUserId(),
                tipo: tipoCompra,
                motivo_emergencial: motivoEmergencial
            })
        }
    };
    
    // Se tiver arquivos, usamos FormData para enviar
    if (hasFiles) {
        const formData = new FormData();
        
        // Adicionar arquivos ao FormData
        document.querySelectorAll('.fornecedor-section').forEach((section, index) => {
            const arquivoCotacao = section.querySelector('.arquivo-cotacao')?.files[0];
            if (arquivoCotacao) {
                formData.append(`arquivo_cotacao_${index}`, arquivoCotacao);
            }
        });
        
        // Adicionar dados JSON ao FormData
        formData.append('dados', JSON.stringify(dados));
        
        // MUDANÇA IMPORTANTE: Sempre usar POST para FormData, mesmo para atualizações
        // Se for uma atualização, adicionar um campo para indicar isso
        const method = 'POST'; // Sempre POST para FormData
        
        if (cotacaoId) {
            formData.append('_method', 'PUT'); // Indicar que é uma atualização
        }
        
        console.log('Fornecedores finais antes de enviar:', fornecedores);
        console.log('Enviando dados com arquivos via FormData');
        
        // Adicione antes do fetch com FormData
        console.log('FormData entries:');
        for (let pair of formData.entries()) {
            if (pair[0] === 'dados') {
                console.log(pair[0], '(JSON data)');
            } else if (pair[1] instanceof File) {
                console.log(pair[0], 'File:', pair[1].name, pair[1].size, 'bytes');
            } else {
                console.log(pair[0], pair[1]);
            }
        }
        
        fetch('api/cotacoes.php', {
            method: method,
            body: formData // Não definimos Content-Type para que o navegador defina automaticamente com boundary
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    console.error('Resposta não-JSON recebida:', text);
                    throw new Error('Resposta inválida do servidor');
                });
            }
        })
        .then(data => {
            console.log('Resposta:', data);
            if (data.success) {
                document.getElementById('modalCotacao').style.display = 'none';
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao salvar cotação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar requisição: ' + error.message);
        });
    } else {
        // Sem arquivos, usamos o método original com JSON
        console.log('Dados a enviar:', dados);
        const method = cotacaoId ? 'PUT' : 'POST';
        console.log('Fornecedores finais antes de enviar:', fornecedores);
        console.log('Payload final da cotação:', JSON.stringify(dados, null, 2));
        
        fetch('api/cotacoes.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dados)
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    console.error('Resposta não-JSON recebida:', text);
                    throw new Error('Resposta inválida do servidor');
                });
            }
        })
        .then(data => {
            console.log('Resposta:', data);
            if (data.success) {
                document.getElementById('modalCotacao').style.display = 'none';
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao salvar cotação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar requisição: ' + error.message);
        });
    }
}




function getCurrentUserId() {
    // Get user ID from a hidden input or data attribute
    return document.querySelector('[data-user-id]')?.dataset.userId || 
           document.getElementById('currentUserId')?.value || 
           1; // Fallback value
}

window.visualizarCotacao = function(id) {
    console.log('Visualizando cotação ID:', id);

    currentCotacaoId = id;

    const modal = document.getElementById('modalVisualizacao');
    modal.style.display = 'block';

    const infoCotacao = modal.querySelector('.info-cotacao');
    infoCotacao.innerHTML = 'Carregando...';

    // Hide sections if they exist
    const motivoRejeicaoView = document.getElementById('motivo-rejeicao-view');
    if (motivoRejeicaoView) motivoRejeicaoView.style.display = 'none';

    const filtrosAnalise = document.getElementById('filtros-analise-cotacao');
    if (filtrosAnalise) filtrosAnalise.style.display = 'none';

    const conteudoFornecedor = document.getElementById('modal-conteudo-analise');
    if (conteudoFornecedor) conteudoFornecedor.style.display = 'none';

    const conteudoProduto = document.getElementById('modal-conteudo-analise-produto');
    if (conteudoProduto) conteudoProduto.style.display = 'none';

    const conteudoComparativo = document.getElementById('modal-conteudo-analise-comparativo');
    if (conteudoComparativo) conteudoComparativo.style.display = 'none';

    fetch(`api/cotacoes.php?id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Erro na resposta da API: ' + response.status);
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            console.log('Status:', data.status);
            console.log('Número de itens:', data.itens ? data.itens.length : 0);

            // Basic info rendering
            infoCotacao.innerHTML = `
                <p><strong>ID:</strong> ${data.id}</p>
                <p><strong>Comprador:</strong> ${data.usuario_nome || 'N/A'}</p>
                <p><strong>Data Criação:</strong> ${formatarData(data.data_criacao)}</p>
                <p class="data-aprovacao"><strong>Data Aprovação/Rejeição:</strong> ${data.data_aprovacao ? formatarData(data.data_aprovacao) : 'Pendente'}</p>
                <p><strong>Status:</strong> <span class="status-badge ${data.status}">${traduzirStatus(data.status)}</span></p>
                <p><strong>Tipo:</strong> ${data.tipo === 'emergencial' ? 
                    '<span class="badge badge-warning"><i class="fas fa-exclamation-circle"></i> Emergencial</span>' : 
                    '<span class="badge badge-info"><i class="fas fa-calendar"></i> Programada</span>'}
                </p>
                ${data.tipo === 'emergencial' && data.motivo_emergencial ? 
                    `<p><strong>Motivo Emergencial:</strong> ${data.motivo_emergencial}</p>` : ''}
            `;
            atualizarResumoOrcamento(data);

            // Handle approval/rejection reasons
            const motivoContainer = document.getElementById('motivo-container');
            const motivoHeader = motivoContainer?.querySelector('.motivo-header');
            const motivoTexto = motivoContainer?.querySelector('.motivo-texto');

            if (motivoContainer && motivoHeader && motivoTexto) {
                // Clear previous status classes
                motivoContainer.classList.remove('rejeitado', 'aprovado', 'renegociacao');
                motivoContainer.style.display = 'none';

                if (data.status === 'rejeitado' && data.motivo_rejeicao) {
                    motivoHeader.textContent = 'Motivo da Rejeição:';
                    motivoTexto.textContent = data.motivo_rejeicao;
                    motivoContainer.style.display = 'block';
                    motivoContainer.classList.add('rejeitado');
                } 
                else if (data.status === 'aprovado' && data.motivo_aprovacao) {
                    motivoHeader.textContent = 'Observações da Aprovação:';
                    motivoTexto.textContent = data.motivo_aprovacao;
                    motivoContainer.style.display = 'block';
                    motivoContainer.classList.add('aprovado');
                }
                else if (data.status === 'renegociacao' && data.motivo_renegociacao) {
                    motivoHeader.textContent = 'Motivo da Renegociação:';
                    motivoTexto.textContent = data.motivo_renegociacao;
                    motivoContainer.style.display = 'block';
                    motivoContainer.classList.add('renegociacao');
                }
            }

            // Filter approved items if status is 'aprovado'
            if (data.status === 'aprovado' && data.itens && Array.isArray(data.itens)) {
                console.log("Filtrando itens aprovados...");
                
                const itensAprovados = data.itens.filter(item => {
                    console.log(`Item ${item.produto_nome}: aprovado=${item.aprovado}`);
                    return parseInt(item.aprovado) === 1;
                });
                
                console.log(`Itens aprovados encontrados: ${itensAprovados.length}`);
                
                if (itensAprovados.length > 0) {
                    data.itens = itensAprovados;
                    
                    // Add approval info message
                    const mensagemAprovacao = document.createElement('div');
                    mensagemAprovacao.className = 'aprovacao-info';
                    
                    if (motivoContainer && motivoContainer.parentNode) {
                        motivoContainer.insertAdjacentElement('afterend', mensagemAprovacao);
                    }
                }
            }

            // Render items by supplier
            if (data.itens && data.itens.length > 0) {
                console.log(`Renderizando ${data.itens.length} itens`);
                renderizarVisualizacaoPorFornecedorModal(data);
                
                const conteudoFornecedor = document.getElementById('modal-conteudo-analise');
                if (conteudoFornecedor) conteudoFornecedor.style.display = 'block';
            } else {
                console.warn('Nenhum item encontrado para esta cotação');
                const conteudoFornecedor = document.getElementById('modal-conteudo-analise');
                if (conteudoFornecedor) {
                    conteudoFornecedor.innerHTML = '<p>Nenhum item encontrado para esta cotação.</p>';
                    conteudoFornecedor.style.display = 'block';
                }
            }

            // Load version history if function exists
            if (typeof carregarHistoricoVersoes === 'function') {
                carregarHistoricoVersoes(id);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar cotação:', error);
            infoCotacao.innerHTML = `<p class="error">Erro ao carregar dados: ${error.message}</p>`;
        });
};


// Função para carregar o histórico de versões
function carregarHistoricoVersoes(cotacaoId) {
    const container = document.querySelector('.versoes-container') || document.getElementById('historico-versoes-container');

    if (!container) {
        console.warn('❗ Container de histórico de versões não encontrado.');
        return;
    }

    container.innerHTML = '<p>Carregando histórico...</p>';

    fetch(`api/cotacoes.php?id=${cotacaoId}&historico=1`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro ao carregar histórico: ${response.status}`);
            }
            return response.json();
        })
        .then(versoes => {
            if (!Array.isArray(versoes) || versoes.length === 0) {
                container.innerHTML = '<p>Nenhuma versão anterior encontrada.</p>';
                return;
            }

            const html = `
                <div class="historico-header">
                    <h3>Histórico de Versões</h3>
                    <small>Clique em uma versão para ver detalhes</small>
                </div>
                <div class="versoes-lista">
                    ${versoes.map(versao => `
                        <div class="versao-item" onclick="mostrarDetalhesVersao(${versao.cotacao_id}, ${versao.versao})">
                            <div class="versao-numero">Versão ${versao.versao}</div>
                            <div class="versao-info">
                                <span class="versao-data">${formatarData(versao.data_criacao)}</span>
                                <span class="versao-usuario">por ${versao.usuario_nome || 'Usuário desconhecido'}</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    `).join('')}
                </div>
            `;

            container.innerHTML = html;

            // Exibir o container, se for oculto
            const historicoBox = document.getElementById('historico-versoes');
            if (historicoBox) historicoBox.style.display = 'block';
        })
        .catch(error => {
            console.error('❌ Erro ao carregar histórico:', error);
            container.innerHTML = `<p class="error">Erro ao carregar histórico: ${error.message}</p>`;
        });
}


// Função para mostrar detalhes de uma versão específica
function mostrarDetalhesVersao(cotacaoId, versao) {
    const modal = document.getElementById('modalDetalhesVersao');
    const conteudo = modal.querySelector('.modal-conteudo');
    
    conteudo.innerHTML = '<p>Carregando versão...</p>';
    modal.style.display = 'block';

    fetch(`api/cotacoes.php?id=${cotacaoId}&versao=${versao}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao carregar versão');
            }
            return response.json();
        })
        .then(dadosVersao => {
            let html = `
                <div class="versao-header">
                    <h3>Detalhes da Versão ${versao}</h3>
                    <small>${new Date(dadosVersao.data_criacao).toLocaleString('pt-BR')}</small>
                </div>
                <div class="versao-detalhes">
                    <h4>Informações da Cotação</h4>
                    <p><strong>Status:</strong> ${traduzirStatus(dadosVersao.status)}</p>
                    <p><strong>Motivo:</strong> ${dadosVersao.motivo || 'Não especificado'}</p>
                    
                    <h4>Itens (${dadosVersao.itens.length})</h4>
                    <div class="versao-itens">
            `;

            dadosVersao.itens.forEach(item => {
                html += `
                    <div class="versao-item">
                        <p><strong>${item.produto_nome || 'Produto sem nome'}</strong></p>
                        <p>Fornecedor: ${item.fornecedor_nome}</p>
                        <p>Quantidade: ${item.quantidade} ${item.produto_unidade || 'un'}</p>
                        <p>Valor Unitário: R$ ${item.valor_unitario.toFixed(2).replace('.', ',')}</p>
                    </div>
                `;
            });

            html += `</div></div>`;
            conteudo.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao carregar versão:', error);
            conteudo.innerHTML = `<p class="error">Erro ao carregar versão: ${error.message}</p>`;
        });
}

function editarCotacao(cotacaoId) {
    console.log('Editing cotação', cotacaoId);
    document.getElementById('excelFile').removeAttribute('required');
    const menorValorPorProduto = {};

    if (!document.getElementById('template-fornecedor')) {
        console.log('Waiting for DOM elements...');
        setTimeout(() => window.editarCotacao(cotacaoId), 100);
        return;
    }

    fetch(`api/cotacoes.php?id=${cotacaoId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Dados recebidos para edição:', data);
            resetForm();
            document.getElementById('cotacaoId').value = data.id;

            // Primeiro, calcular o menor valor para cada produto
            data.itens.forEach(item => {
                const produtoNome = item.produto_nome;
                const valorUnitario = parseFloat(item.valor_unitario) || 0;
                
                if (!menorValorPorProduto[produtoNome] || valorUnitario < menorValorPorProduto[produtoNome].valor) {
                    menorValorPorProduto[produtoNome] = {
                        valor: valorUnitario,
                        fornecedor: item.fornecedor_nome
                    };
                }
            });

            if (data.status === 'renegociacao' && data.motivo_renegociacao) {
                const formHeader = document.querySelector('.form-header');
                // Check if the renegotiation reason has already been added
                if (!document.querySelector('.motivo-renegociacao-info')) {
                    const motivoRenegociacao = document.createElement('div');
                    motivoRenegociacao.className = 'motivo-renegociacao-info';
                    motivoRenegociacao.innerHTML = `
                        <h4>Motivo da Renegociação:</h4>
                        <div class="motivo-texto">${data.motivo_renegociacao}</div>
                    `;
                    formHeader.parentNode.insertBefore(motivoRenegociacao, formHeader.nextSibling);
                }
            }

            const itensPorFornecedor = {};
            data.itens.forEach(item => {
                const fornecedorNome = item.fornecedor_nome || 'Fornecedor Desconhecido';
                if (!itensPorFornecedor[fornecedorNome]) {
                    itensPorFornecedor[fornecedorNome] = {
                        fornecedor_nome: fornecedorNome,
                        prazo_pagamento: item.prazo_pagamento || '',
                        prazo_entrega: item.prazo_entrega || '',
                        frete: item.frete || 0,
                        difal: item.difal || 0,
                        arquivo_cotacao: item.arquivo_cotacao || '',
                        produtos: []
                    };
                }

                itensPorFornecedor[fornecedorNome].produtos.push({
                    produto_id: item.produto_id,
                    produto_nome: item.produto_nome || '',
                    produto_codigo: item.codigo || item.produto_codigo || '',
                    produto_unidade: item.unidade || item.produto_unidade || '-',
                    quantidade: item.quantidade || 0,
                    valor_unitario: item.valor_unitario || 0,
                    ultimo_valor_aprovado: item.ultimo_valor_aprovado || null
                });
            });

            Object.values(itensPorFornecedor).forEach(fornecedor => {
                adicionarFornecedor();

                const fornecedorSection = document.querySelector('.fornecedor-section:last-child');
                if (!fornecedorSection) {
                    console.error('Fornecedor section not found');
                    return;
                }

                fornecedorSection.querySelector('.fornecedor-input').value = fornecedor.fornecedor_nome;
                fornecedorSection.querySelector('.prazo-pagamento').value = fornecedor.prazo_pagamento;
                fornecedorSection.querySelector('.prazo-entrega').value = fornecedor.prazo_entrega;
                fornecedorSection.querySelector('.frete-valor').value = fornecedor.frete;
                fornecedorSection.querySelector('.difal-percentual').value = fornecedor.difal;
                
                // Mostrar arquivo existente, se houver
                const arquivoCotacaoInput = fornecedorSection.querySelector('.arquivo-cotacao');
                if (arquivoCotacaoInput && fornecedor.arquivo_cotacao) {
                    const arquivoContainer = document.createElement('div');
                    arquivoContainer.className = 'arquivo-existente';
                    arquivoContainer.innerHTML = `
                        <p>Arquivo atual: <a href="uploads/cotacoes/${fornecedor.arquivo_cotacao}" target="_blank">${fornecedor.arquivo_cotacao}</a></p>
                        <small>Selecione um novo arquivo abaixo para substituir</small>
                    `;
                    
                    arquivoCotacaoInput.parentNode.insertBefore(arquivoContainer, arquivoCotacaoInput);
                }

                fornecedor.produtos.forEach(produto => {
                    adicionarProdutoSelect();

                    const produtoRow = fornecedorSection.querySelector('.produtos-fornecedor tr:last-child');
                    produtoRow.querySelector('.produto-id').value = produto.produto_id;

                    // Verificar se é o menor valor para este produto
                    const produtoNome = produto.produto_nome;
                    const valorUnitario = parseFloat(produto.valor_unitario) || 0;
                    const menorValor = menorValorPorProduto[produtoNome]?.valor || 0;
                    const isMenorValor = valorUnitario === menorValor && valorUnitario > 0;
                    if (isMenorValor) {
                        produtoRow.classList.add('menor-valor-produto');
                    }

                    if (data.produtos_renegociar && data.produtos_renegociar.some(p => {
                        return (
                            (String(p.produto_id) === String(produto.produto_id) && 
                             p.fornecedor_nome === fornecedor.fornecedor_nome) ||
                            (String(p.produto_id) === String(produto.produto_codigo) && 
                             p.fornecedor_nome === fornecedor.fornecedor_nome)
                        );
                    })) {
                        produtoRow.classList.add('produto-renegociar');
                        console.log('Produto marcado para renegociação:', produto.produto_nome);
                    }

                    // Atualizar a estrutura da linha do produto
                    produtoRow.innerHTML = `
                        <td>
                            <div class="produto-selected">${produto.produto_nome}</div>
                            <input type="hidden" class="produto-id" value="${produto.produto_id}">
                        </td>
                        <td><input type="number" class="quantidade" value="${produto.quantidade}" required></td>
                        <td class="unidade-medida">${produto.produto_unidade || produto.unidade || 'UN'}</td>
                        <td class="ultimo-valor-aprovado">${produto.ultimo_valor_aprovado ? formatarNumero(produto.ultimo_valor_aprovado) : '-'}</td>
                        <td><input type="number" class="valor-unitario" value="${produto.valor_unitario}" step="0.0001" min="0" required></td>
                        <td class="valor-unit-difal-frete">0,0000</td>
                        <td class="total">0,0000</td>
                        <td>
                            <button type="button" class="btn-remover-produto">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;

                    const quantidadeInput = produtoRow.querySelector('.quantidade');
                    const valorUnitarioInput = produtoRow.querySelector('.valor-unitario');
                    const valorUnitDifalFreteCell = produtoRow.querySelector('.valor-unit-difal-frete');
                    const totalCell = produtoRow.querySelector('.total');

                    const recalcular = () => {
                        const quantidade = parseFloat(quantidadeInput.value) || 0;
                        const valorUnitario = parseFloat(valorUnitarioInput.value) || 0;
                        
                        // Calcular valor com DIFAL e frete
                        const difal = parseFloat(fornecedorSection.querySelector('.difal-percentual')?.value || 0);
                        const frete = parseFloat(fornecedorSection.querySelector('.frete-valor')?.value || 0);
                        
                        // Calcular valor total do produto
                        const valorTotalProduto = quantidade * valorUnitario;
                        
                        // Calcular valor total da compra do fornecedor
                        const valorTotalFornecedor = Array.from(fornecedorSection.querySelectorAll('.produtos-fornecedor tr'))
                            .reduce((total, row) => {
                                const qtd = parseFloat(row.querySelector('.quantidade')?.value || 0);
                                const val = parseFloat(row.querySelector('.valor-unitario')?.value || 0);
                                return total + (qtd * val);
                            }, 0);
                        
                        // Calcular frete proporcional ao valor do produto
                        const freteProporcional = valorTotalFornecedor > 0 ? 
                            (valorTotalProduto / valorTotalFornecedor) * frete : 0;
                        
                        // Calcular frete por unidade
                        const fretePorUnidade = quantidade > 0 ? freteProporcional / quantidade : 0;
                        
                        // Calcular valor com DIFAL
                        const valorComDifal = valorUnitario * (1 + (difal / 100));
                        
                        // Calcular valor final
                        const valorFinal = valorComDifal + fretePorUnidade;
                        
                        // Atualizar células
                        valorUnitDifalFreteCell.textContent = formatarNumero(valorFinal);
                        totalCell.textContent = formatarNumero(quantidade * valorFinal);
                    };

                    quantidadeInput.addEventListener('input', recalcular);
                    valorUnitarioInput.addEventListener('input', recalcular);
                    
                    // Adicionar listeners para DIFAL e frete
                    fornecedorSection.querySelector('.difal-percentual')?.addEventListener('input', recalcular);
                    fornecedorSection.querySelector('.frete-valor')?.addEventListener('input', recalcular);
                    
                    // Calcular valores iniciais
                    setTimeout(recalcular, 100); // Adicionar um pequeno delay para garantir que todos os elementos estejam carregados
                });
            });

            document.getElementById('modalCotacao').style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao carregar dados para edição:', error.message, error);
            alert('Erro ao carregar dados para edição: ' + error.message);
        });
}



function calcularTotaisFornecedor(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    let totalProdutos = 0;

    // 1. Somar todos os produtos
    section.querySelectorAll('.produtos-fornecedor tr').forEach(row => {
        const quantidade = parseFloat(row.querySelector('.quantidade')?.value || 0);
        const valorUnitario = parseFloat(row.querySelector('.valor-unitario')?.value || 0);
        totalProdutos += quantidade * valorUnitario;
    });

    // 2. Pegar DIFAL e aplicar
    const difalPercent = parseFloat(section.querySelector('.difal-percentual')?.value || 0);
    const difalValor = (totalProdutos * difalPercent) / 100;

    // 3. Pegar frete
    const freteValor = parseFloat(section.querySelector('.frete-valor')?.value || 0);

    // 4. Calcular total final
    const totalFinal = totalProdutos + difalValor + freteValor;

    // 5. Exibir o total na interface
    let totalDisplay = section.querySelector('.total-fornecedor');
    if (!totalDisplay) {
        totalDisplay = document.createElement('div');
        totalDisplay.className = 'total-fornecedor';
        totalDisplay.style = 'margin-top: 10px; font-weight: bold;';
        section.appendChild(totalDisplay);
    }

    totalDisplay.innerHTML = `
        Total Produtos: R$ ${totalProdutos.toFixed(2)}<br>
        DIFAL (${difalPercent}%): R$ ${difalValor.toFixed(2)}<br>
        Frete: R$ ${freteValor.toFixed(2)}<br>
        <strong>Total Final: R$ ${totalFinal.toFixed(2)}</strong>
    `;
}


window.enviarParaAprovacao = function(id) {
    console.log(`Iniciando envio da cotação ${id} para aprovação`);
    
    if (confirm('Tem certeza que deseja enviar esta cotação para aprovação?')) {
        console.log(`Confirmação aceita, enviando requisição PUT para api/cotacoes.php`);
        
        fetch('api/cotacoes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: 'aguardando_aprovacao'
            })
        })
        .then(async response => {
            console.log(`Resposta recebida com status: ${response.status}`);
            console.log(`Headers da resposta:`, Object.fromEntries([...response.headers]));
            
            // Clonar a resposta para poder ler o corpo duas vezes
            const clone = response.clone();
            
            // Tentar ler o corpo da resposta como texto
            const text = await clone.text();
            console.log(`Corpo da resposta:`, text);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
            }
            
            return text ? JSON.parse(text) : {};
        })
        .then(data => {
            console.log(`Dados processados com sucesso:`, data);
            
            if (data.success) {
                alert('Cotação enviada para aprovação com sucesso!');
                window.location.reload();
            } else {
                console.error(`Erro reportado pelo servidor:`, data);
                alert(data.message || 'Erro ao enviar cotação para aprovação');
            }
        })
        .catch(error => {
            console.error('Erro detalhado:', error);
            console.error('Stack trace:', error.stack);
            alert('Erro ao processar requisição: ' + error.message);
        });
    } else {
        console.log(`Envio para aprovação cancelado pelo usuário`);
    }
};


// Função para preencher o filtro de fornecedores
function preencherFiltrosFornecedores(data) {
    const selectFornecedor = document.getElementById('modal-filtro-fornecedor');
    selectFornecedor.innerHTML = '<option value="">Todos</option>';
    
    // Conjunto para armazenar fornecedores únicos
    const fornecedoresUnicos = new Set();
    
    // Adicionar fornecedores ao conjunto
    if (data.itens && data.itens.length > 0) {
        data.itens.forEach(item => {
            if (item.fornecedor && item.fornecedor_nome) {
                fornecedoresUnicos.add(JSON.stringify({
                    id: item.fornecedor,
                    nome: item.fornecedor_nome
                }));
            }
        });
    }
    
    // Converter o conjunto para array e ordenar por nome
    const fornecedores = Array.from(fornecedoresUnicos)
        .map(json => JSON.parse(json))
        .sort((a, b) => a.nome.localeCompare(b.nome));
    
    // Adicionar opções ao select
    fornecedores.forEach(fornecedor => {
        const option = document.createElement('option');
        option.value = fornecedor.id;
        option.textContent = fornecedor.nome;
        selectFornecedor.appendChild(option);
    });
}

// Função para preencher o filtro de produtos
function preencherFiltrosProdutos(data) {
    const selectProduto = document.getElementById('modal-filtro-produto');
    selectProduto.innerHTML = '<option value="">Todos</option>';
    
    // Conjunto para armazenar produtos únicos
    const produtosUnicos = new Set();
    
    // Adicionar produtos ao conjunto
    if (data.itens && data.itens.length > 0) {
        data.itens.forEach(item => {
            if (item.produto_id && item.produto_nome) {
                produtosUnicos.add(JSON.stringify({
                    id: item.produto_id,
                    nome: item.produto_nome
                }));
            } else if (item.descricao) {
                produtosUnicos.add(JSON.stringify({
                    id: item.id,
                    nome: item.descricao
                }));
            }
        });
    }
    
    // Converter o conjunto para array e ordenar por nome
    const produtos = Array.from(produtosUnicos)
        .map(json => JSON.parse(json))
        .sort((a, b) => a.nome.localeCompare(b.nome));
    
    // Adicionar opções ao select
    produtos.forEach(produto => {
        const option = document.createElement('option');
        option.value = produto.id;
        option.textContent = produto.nome;
        selectProduto.appendChild(option);
    });
}

// Função para renderizar visualização por fornecedor no modal
function renderizarVisualizacaoPorFornecedorModal(data) {
    const container = document.getElementById('modal-conteudo-analise');
    if (!container) return;
    
    // Primeiro, vamos identificar o menor valor para cada produto entre todos os fornecedores
    const menorValorPorProduto = {};
    if (data.itens && data.itens.length > 0) {
        data.itens.forEach(item => {
            const produtoNome = item.produto_nome || item.descricao;
            const valorUnitario = parseFloat(item.valor_unitario) || 0;
            
            if (!menorValorPorProduto[produtoNome] || valorUnitario < menorValorPorProduto[produtoNome].valor) {
                menorValorPorProduto[produtoNome] = {
                    valor: valorUnitario,
                    produto_nome: produtoNome
                };
            }
        });
    }
    
    // Agrupar itens por fornecedor
    const itensPorFornecedor = {};
    data.itens.forEach(item => {
        if (!itensPorFornecedor[item.fornecedor_nome]) {
            itensPorFornecedor[item.fornecedor_nome] = [];
        }
        itensPorFornecedor[item.fornecedor_nome].push(item);
    });
    
    // Gerar HTML para cada fornecedor
    let html = '<div class="fornecedores-container">';
    
    Object.entries(itensPorFornecedor).forEach(([fornecedor, itens]) => {
        // Calcular totais do fornecedor
        const totalFornecedor = itens.reduce((total, item) => {
            return total + (item.quantidade * item.valor_unitario);
        }, 0);
        
        // Pegar informações do primeiro item para dados do fornecedor
        const primeiroItem = itens[0];
        
        // Calcular DIFAL e frete
        const difal = parseFloat(primeiroItem.difal || 0);
        const frete = parseFloat(primeiroItem.frete || 0);
        const difalValor = totalFornecedor * (difal / 100);
        const totalComDifalEFrete = totalFornecedor + difalValor + frete;
        
        html += `
            <div class="fornecedor-card">
                <h4>${fornecedor}</h4>
                <div class="fornecedor-info">
                    <p><strong>Prazo de Pagamento:</strong> ${primeiroItem.prazo_pagamento || 'Não informado'}</p>
                    <p><strong>Prazo de Entrega:</strong> ${primeiroItem.prazo_entrega || 'Não informado'}</p>
                    <p><strong>Frete:</strong> R$ ${formatarNumero(primeiroItem.frete || 0)}</p>
                    <p><strong>DIFAL:</strong> ${primeiroItem.difal || '0'}%</p>
                    <p><strong>Valor Total:</strong> R$ ${formatarNumero(totalComDifalEFrete)}</p>
                </div>
                
                <table class="tabela-produtos">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>UN</th>
                            <th>Ult. Vlr. Aprovado</th>
                            <th>Valor Unit.</th>
                            <th>Valor Unit. + DIFAL + Frete</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itens.map(item => {
                            // Calcular valor total do produto
                            const valorTotalProduto = item.quantidade * item.valor_unitario;
                            
                            // Calcular frete proporcional
                            const freteProporcional = totalFornecedor > 0 ? 
                                (valorTotalProduto / totalFornecedor) * primeiroItem.frete : 0;
                            
                            // Calcular frete por unidade
                            const fretePorUnidade = item.quantidade > 0 ? 
                                freteProporcional / item.quantidade : 0;
                            
                            // Calcular valor com DIFAL
                            const valorComDifal = item.valor_unitario * (1 + (primeiroItem.difal / 100));
                            
                            // Calcular valor final
                            const valorUnitarioComDifalEFrete = valorComDifal + fretePorUnidade;
                            const total = item.quantidade * valorUnitarioComDifalEFrete;
                            
                            // Verificar se é o menor valor para este produto
                            const produtoNome = item.produto_nome || item.descricao;
                            const valorUnitario = parseFloat(item.valor_unitario) || 0;
                            const menorValor = menorValorPorProduto[produtoNome]?.valor || 0;
                            const isMenorValor = valorUnitario === menorValor && valorUnitario > 0;
                            const melhorPrecoClass = isMenorValor ? 'melhor-preco' : '';
                            
                            // Verificar se o produto está marcado para renegociação
                            const isRenegociacao = data.produtos_renegociar && data.produtos_renegociar.some(p => {
                                return (
                                    (String(p.produto_id) === String(item.produto_id) && 
                                     p.fornecedor_nome === item.fornecedor_nome) ||
                                    (String(p.produto_id) === String(item.produto_codigo) && 
                                     p.fornecedor_nome === item.fornecedor_nome)
                                );
                            });
                            
                            // Adicionar classe de renegociação se necessário
                            const renegociacaoClass = isRenegociacao ? 'produto-renegociar' : '';
                            
                            // Combinar as classes
                            const classes = [melhorPrecoClass, renegociacaoClass].filter(Boolean).join(' ');
                            
                            return `
                                <tr class="${classes}" data-fornecedor="${item.fornecedor}" data-produto="${item.produto_id}"
                                    data-fornecedor-nome="${item.fornecedor_nome}" data-produto-nome="${produtoNome}">
                                    <td>${produtoNome}</td>
                                    <td>${item.quantidade}</td>
                                    <td>${item.produto_unidade || item.unidade || 'UN'}</td>
                                    <td>${item.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(item.ultimo_valor_aprovado) : '-'}</td>
                                    <td>R$ ${formatarNumero(item.valor_unitario)}</td>
                                    <td>R$ ${formatarNumero(valorUnitarioComDifalEFrete)}</td>
                                    <td>R$ ${formatarNumero(total)}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

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
    
    // Agrupar itens por fornecedor para calcular DIFAL e frete
    const itensPorFornecedor = {};
    itensParaCalculo.forEach(item => {
        const fornecedorNome = item.fornecedor_nome;
        if (!itensPorFornecedor[fornecedorNome]) {
            itensPorFornecedor[fornecedorNome] = {
                itens: [],
                difal: parseFloat(item.difal || 0),
                frete: parseFloat(item.frete || 0)
            };
        }
        itensPorFornecedor[fornecedorNome].itens.push(item);
    });
    
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
    
    // Calcular valor total dos melhores preços incluindo DIFAL e frete
    let valorTotal = 0;
    Object.values(melhoresPrecos).forEach(item => {
        const fornecedor = itensPorFornecedor[item.fornecedor];
        if (fornecedor) {
            // Calcular valor base do produto
            const valorBase = item.valor * item.quantidade;
            
            // Calcular DIFAL
            const difalValor = valorBase * (fornecedor.difal / 100);
            
            // Calcular proporção do frete para este item
            const totalFornecedor = fornecedor.itens.reduce((sum, i) => 
                sum + (parseFloat(i.valor_unitario) * parseFloat(i.quantidade)), 0);
            const proporcaoFrete = totalFornecedor > 0 ? valorBase / totalFornecedor : 0;
            const freteProporcional = fornecedor.frete * proporcaoFrete;
            
            // Adicionar ao valor total
            valorTotal += valorBase + difalValor + freteProporcional;
        }
    });
    
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




function calcularValorUnitarioComDifalEFrete(item, fornecedor) {
    if (!item || !fornecedor) {
        console.error('Dados inválidos para cálculo de valor unitário com DIFAL e frete');
        return 0;
    }

    try {
        // Extrair valores numéricos, garantindo que sejam números válidos
        const valorUnitario = parseFloat(item.valor_unitario) || 0;
        const difal = parseFloat(fornecedor.difal) || 0;
        const frete = parseFloat(fornecedor.frete) || 0;
        const quantidade = parseFloat(item.quantidade) || 1;

        // Calcular o valor total do produto
        const valorTotalProduto = valorUnitario * quantidade;

        // Calcular o valor total da compra do fornecedor
        const valorTotalFornecedor = Array.from(document.querySelectorAll('.produtos-fornecedor tr'))
            .reduce((total, row) => {
                const qtd = parseFloat(row.querySelector('.quantidade')?.value || 0);
                const val = parseFloat(row.querySelector('.valor-unitario')?.value || 0);
                return total + (qtd * val);
            }, 0);

        // Calcular frete proporcional ao valor do produto
        const freteProporcional = valorTotalFornecedor > 0 ? 
            (valorTotalProduto / valorTotalFornecedor) * frete : 0;

        // Calcular frete por unidade
        const fretePorUnidade = quantidade > 0 ? freteProporcional / quantidade : 0;

        // Calcular valor com DIFAL
        const valorComDifal = valorUnitario * (1 + (difal / 100));

        // Calcular valor final
        const valorFinal = valorComDifal + fretePorUnidade;

        return valorFinal;
    } catch (error) {
        console.error('Erro ao calcular valor unitário com DIFAL e frete:', error);
        return 0;
    }
}


// Função para renderizar visualização por produto no modal
function renderizarVisualizacaoPorProdutoModal(data) {
    const container = document.getElementById('modal-conteudo-analise-produto');
    if (!container) return;
    
    // Agrupar itens por produto
    const itensPorProduto = {};
    data.itens.forEach(item => {
        if (!itensPorProduto[item.produto_nome]) {
            itensPorProduto[item.produto_nome] = [];
        }
        itensPorProduto[item.produto_nome].push(item);
    });
    
    // Gerar HTML para cada produto
    let html = '<div class="produtos-container">';
    
    Object.entries(itensPorProduto).forEach(([produto, itens]) => {
        // Ordenar itens por valor unitário (menor para maior)
        itens.sort((a, b) => parseFloat(a.valor_unitario) - parseFloat(b.valor_unitario));
        
        // Encontrar o melhor preço
        const melhorPreco = itens[0];
        
        html += `
            <div class="produto-card">
                <h4>${produto}</h4>
                <div class="produto-info">
                    <p><strong>Quantidade:</strong> ${melhorPreco.quantidade || 0} ${melhorPreco.produto_unidade || melhorPreco.unidade || 'UN'}</p>
                    <p><strong>Ult. Vlr. Aprovado:</strong> ${melhorPreco.ultimo_valor_aprovado ? 'R$ ' + formatarNumero(melhorPreco.ultimo_valor_aprovado) : '-'}</p>
                    <p><strong>Melhor Preço:</strong> R$ ${formatarNumero(melhorPreco.valor_unitario || 0)}</p>
                    <p><strong>Fornecedor:</strong> ${melhorPreco.fornecedor_nome}</p>
                </div>
                
                <table class="tabela-fornecedores">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                            <th>Prazo Pagamento</th>
                            <th>Prazo Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itens.map(item => {
                            // Verificar se é o melhor preço
                            const isMelhorPreco = item === melhorPreco;
                            const melhorPrecoClass = isMelhorPreco ? 'melhor-preco' : '';
                            
                            // Verificar se o produto está marcado para renegociação
                            const isRenegociacao = data.produtos_renegociar && data.produtos_renegociar.some(p => {
                                return (
                                    (String(p.produto_id) === String(item.produto_id) && 
                                     p.fornecedor_nome === item.fornecedor_nome) ||
                                    (String(p.produto_id) === String(item.produto_codigo) && 
                                     p.fornecedor_nome === item.fornecedor_nome)
                                );
                            });
                            
                            // Adicionar classe de renegociação se necessário
                            const renegociacaoClass = isRenegociacao ? 'produto-renegociar' : '';
                            
                            // Combinar as classes
                            const classes = [melhorPrecoClass, renegociacaoClass].filter(Boolean).join(' ');
                            
                            return `
                                <tr class="${classes}">
                                    <td>${item.fornecedor_nome}</td>
                                    <td>R$ ${formatarNumero(item.valor_unitario || 0)}</td>
                                    <td>R$ ${formatarNumero((item.quantidade || 0) * (item.valor_unitario || 0))}</td>
                                    <td>${item.prazo_pagamento || 'Não informado'}</td>
                                    <td>${item.prazo_entrega || 'Não informado'}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Função para renderizar visualização comparativa no modal
function renderizarVisualizacaoComparativoModal(data) {
    const conteudoAnaliseComparativoElement = document.getElementById('modal-conteudo-analise-comparativo');
    
    if (!conteudoAnaliseComparativoElement) {
        console.error('Elemento #modal-conteudo-analise-comparativo não encontrado no DOM');
        return;
    }
    
    // Preparar dados para a tabela comparativa
    const produtos = new Set();
    const fornecedores = new Set();
    const precos = {};
    
    if (data.itens && data.itens.length > 0) {
        data.itens.forEach(item => {
            const produtoId = item.produto_id || item.produto_nome || item.descricao;
            const fornecedorId = item.fornecedor;
            const produtoNome = item.produto_nome || item.descricao || 'N/A';
            const fornecedorNome = item.fornecedor_nome || `Fornecedor ${fornecedorId}`;
            
            produtos.add(produtoNome);
            fornecedores.add(fornecedorNome);
            
            if (!precos[produtoNome]) {
                precos[produtoNome] = {
                    quantidade: item.quantidade,
                    fornecedores: {}
                };
            }
            
            precos[produtoNome].fornecedores[fornecedorNome] = {
                valor: parseFloat(item.valor_unitario) || 0,
                fornecedor_id: item.fornecedor,
                produto_id: item.produto_id
            };
        });
    }
    
    // Converter conjuntos para arrays
    const produtosArray = Array.from(produtos);
    const fornecedoresArray = Array.from(fornecedores);
    
    // Encontrar o melhor preço para cada produto
    const melhoresPrecos = {};
    produtosArray.forEach(produto => {
        let melhorPreco = Infinity;
        
        Object.entries(precos[produto].fornecedores).forEach(([fornecedor, dados]) => {
            const valor = dados.valor;
            if (valor > 0 && valor < melhorPreco) {
                melhorPreco = valor;
            }
        });
        
        melhoresPrecos[produto] = melhorPreco !== Infinity ? melhorPreco : 0;
    });
    
    // Gerar cabeçalho da tabela
    let cabecalhoHTML = '<th>Produto</th><th>Qtd</th>';
    fornecedoresArray.forEach(fornecedor => {
        cabecalhoHTML += `<th>${fornecedor}</th>`;
    });
    
    // Gerar linhas da tabela
    let linhasHTML = '';
    produtosArray.forEach(produto => {
        // Verificar se o produto está marcado para renegociação
        const isRenegociacao = data.produtos_renegociar && data.produtos_renegociar.some(p => {
            return (
                (String(p.produto_id) === String(precos[produto].fornecedores[Object.keys(precos[produto].fornecedores)[0]].produto_id) && 
                 p.fornecedor_nome === Object.keys(precos[produto].fornecedores)[0]) ||
                (String(p.produto_id) === String(precos[produto].fornecedores[Object.keys(precos[produto].fornecedores)[0]].produto_id) && 
                 p.fornecedor_nome === Object.keys(precos[produto].fornecedores)[0])
            );
        });
        
        // Adicionar classe de renegociação se necessário
        const renegociacaoClass = isRenegociacao ? 'produto-renegociar' : '';
        
        linhasHTML += `<tr class="${renegociacaoClass}">`;
        linhasHTML += `<td>${produto}</td>`;
        linhasHTML += `<td>${precos[produto].quantidade || 0}</td>`;
        
        fornecedoresArray.forEach(fornecedor => {
            const dados = precos[produto].fornecedores[fornecedor];
            const valor = dados ? dados.valor : 0;
            const isMelhorPreco = valor === melhoresPrecos[produto] && valor > 0;
            
            linhasHTML += `<td class="${isMelhorPreco ? 'melhor-preco' : ''}">${valor > 0 ? 'R$ ' + formatarNumero(valor) : '-'}</td>`;
        });
        
        linhasHTML += '</tr>';
    });
    
    // Montar tabela completa
    const tabelaHTML = `
        <table class="tabela-comparativa">
            <thead>
                <tr>${cabecalhoHTML}</tr>
            </thead>
            <tbody>
                ${linhasHTML}
            </tbody>
        </table>
    `;
    
    conteudoAnaliseComparativoElement.innerHTML = tabelaHTML;
}

function renderizarComparativoVersoes(versaoAntiga, versaoAtual) {
    const comparativo = document.createElement('div');
    comparativo.className = 'comparativo-versoes';

    versaoAtual.itens.forEach(itemAtual => {
        const itemAntigo = versaoAntiga.itens.find(i => 
            i.produto_id === itemAtual.produto_id && 
            i.fornecedor_nome === itemAtual.fornecedor_nome
        );

        if (itemAntigo) {
            const diferenca = itemAtual.valor_unitario - itemAntigo.valor_unitario;
            const percentual = ((diferenca / itemAntigo.valor_unitario) * 100).toFixed(2);
            
            comparativo.innerHTML += `
                <div class="item-comparativo">
                    <h4>${itemAtual.produto_nome} - ${itemAtual.fornecedor_nome}</h4>
                    <div class="valores">
                        <span class="valor-antigo">R$ ${itemAntigo.valor_unitario}</span>
                        <span class="valor-novo ${diferenca < 0 ? 'reducao' : 'aumento'}">
                            R$ ${itemAtual.valor_unitario} (${percentual}%)
                        </span>
                    </div>
                </div>
            `;
        }
    });

    return comparativo;
}


// Função para alternar entre as visualizações no modal
function alternarVisualizacaoModal(viewId) {
    console.log('Alternando para visualização:', viewId);
    
    // Esconder todas as visualizações
    document.querySelectorAll('.view-content').forEach(el => {
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
    } else {
        console.warn(`View element with ID "${viewId}" not found`);
    }
    
    // Adicionar classe 'active' ao botão correspondente
    const btnId = viewId.replace('modal-conteudo-analise', 'modal-btn-view');
    if (viewId === 'modal-conteudo-analise') {
        const btnElement = document.getElementById('modal-btn-view-fornecedor');
        if (btnElement) {
            btnElement.classList.add('active');
        }
    } else {
        const btnElement = document.getElementById(btnId);
        if (btnElement) {
            btnElement.classList.add('active');
        } else {
            console.warn(`Button element with ID "${btnId}" not found`);
        }
    }
}


// Função para aplicar filtros no modal
function aplicarFiltrosModal() {
    const ordenacao = document.getElementById('modal-filtro-ordenacao').value;
    const fornecedor = document.getElementById('modal-filtro-fornecedor').value;
    const produto = document.getElementById('modal-filtro-produto').value;
    
    console.log('Aplicando filtros:', { ordenacao, fornecedor, produto });
    
    // Determinar qual visualização está ativa
    let activeView = null;
    document.querySelectorAll('.view-content').forEach(el => {
        if (el.style.display !== 'none') {
            activeView = el;
        }
    });
    
    if (!activeView) {
        console.warn('Nenhuma visualização ativa encontrada');
        return;
    }
    
    console.log('Visualização ativa:', activeView.id);
    
    // Filtrar linhas de tabela na visualização ativa
    const tabelas = activeView.querySelectorAll('table');
    
    tabelas.forEach(tabela => {
        const linhas = tabela.querySelectorAll('tbody tr');
        
        linhas.forEach(linha => {
            let mostrar = true;
            
            // Filtrar por fornecedor
            if (fornecedor && linha.getAttribute('data-fornecedor') !== fornecedor) {
                mostrar = false;
            }
            
            // Filtrar por produto
            if (produto && linha.getAttribute('data-produto') !== produto) {
                mostrar = false;
            }
            
            linha.style.display = mostrar ? '' : 'none';
        });
    });
    
    // Se a ordenação for solicitada, implementar a lógica de ordenação
    if (ordenacao) {
        ordenarTabelasModal(ordenacao, activeView);
    }
}

// Função para ordenar tabelas no modal
function ordenarTabelasModal(criterio) {
    const tabelas = document.querySelectorAll('#modalCotacao .view-content:not([style*="display: none"]) table');
    
    tabelas.forEach(tabela => {
        const tbody = tabela.querySelector('tbody');
        if (!tbody) return;
        
        const linhas = Array.from(tbody.querySelectorAll('tr'));
        
        // Ordenar as linhas com base no critério
        linhas.sort((a, b) => {
            if (criterio === 'menor-preco') {
                const precoA = parseFloat(a.querySelector('.preco')?.textContent?.replace('R$ ', '').replace(',', '.') || '0');
                const precoB = parseFloat(b.querySelector('.preco')?.textContent?.replace('R$ ', '').replace(',', '.') || '0');
                return precoA - precoB;
            } else if (criterio === 'maior-preco') {
                const precoA = parseFloat(a.querySelector('.preco')?.textContent?.replace('R$ ', '').replace(',', '.') || '0');
                const precoB = parseFloat(b.querySelector('.preco')?.textContent?.replace('R$ ', '').replace(',', '.') || '0');
                return precoB - precoA;
            } else if (criterio === 'fornecedor') {
                const fornecedorA = a.getAttribute('data-fornecedor-nome') || '';
                const fornecedorB = b.getAttribute('data-fornecedor-nome') || '';
                return fornecedorA.localeCompare(fornecedorB);
            } else if (criterio === 'produto') {
                const produtoA = a.getAttribute('data-produto-nome') || '';
                const produtoB = b.getAttribute('data-produto-nome') || '';
                return produtoA.localeCompare(produtoB);
            }
            return 0;
        });
        
        // Remover todas as linhas atuais
        linhas.forEach(linha => {
            if (linha.parentNode === tbody) {
                tbody.removeChild(linha);
            }
        });
        
        // Adicionar as linhas ordenadas
        linhas.forEach(linha => tbody.appendChild(linha));
    });
}

// Função para mostrar a melhor opção
function mostrarMelhorOpcaoModal() {
    // Esconder todas as linhas que não são a melhor opção
    const tabelas = document.querySelectorAll('#modalCotacao .view-content:not([style*="display: none"]) table');
    
    tabelas.forEach(tabela => {
        const linhas = tabela.querySelectorAll('tbody tr');
        
        linhas.forEach(linha => {
            const isMelhorPreco = linha.classList.contains('melhor-preco');
            linha.style.display = isMelhorPreco ? '' : 'none';
        });
    });
}

// Funções auxiliares
function formatarData(dataString) {
    if (!dataString) return 'N/A';
    
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
}

function formatarNumero(valor) {
    if (valor === null || valor === undefined || isNaN(parseFloat(valor))) {
        return '0,0000';
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

// Attach event listeners to "visualizar" buttons
function attachVisualizarEventListeners() {
    document.querySelectorAll('.btn-visualizar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            visualizarCotacao(id);
        });
    });
}

// Function to initialize the modal and attach event listeners
function initializeModal() {
    console.log('Initializing modal');
    attachVisualizarEventListeners(); // Attach listeners initially
}

// Function to safely get the infoCotacao element with a delay
// Modify the getInfoCotacaoElement function to create the element if it doesn't exist
function getInfoCotacaoElement(callback) {
    const modal = document.getElementById('modalVisualizacao');
    if (!modal) {
        console.error('Modal #modalVisualizacao not found');
        callback(null);
        return;
    }

    // Add a delay to allow the DOM to fully render
    setTimeout(function() {
        let infoCotacaoElement = modal.querySelector('.info-cotacao');
        
        // If the element doesn't exist, create it
        if (!infoCotacaoElement) {
            console.log('Creating .info-cotacao element as it was not found');
            infoCotacaoElement = document.createElement('div');
            infoCotacaoElement.className = 'info-cotacao';
            
            // Find a suitable container in the modal to append this element
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.appendChild(infoCotacaoElement);
            } else {
                // If no .modal-content, append directly to the modal
                modal.appendChild(infoCotacaoElement);
            }
        }
        
        callback(infoCotacaoElement);
    }, 100); // Adjust the delay as needed
}

// Função para exportar os melhores preços para XLSX
function exportarMelhoresPrecos(cotacaoId) {
    console.log('Exportando melhores preços para cotação ID:', cotacaoId || currentCotacaoId);
    
    // Usar o ID fornecido ou o ID global
    const id = cotacaoId || currentCotacaoId;
    
    if (!id) {
        console.error('ID da cotação não disponível');
        alert('ID da cotação não disponível');
        return;
    }
    
    // Buscar dados da cotação
    fetch(`api/cotacoes.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            console.log('Dados da cotação recebidos:', data);
            
            // Verificar se há itens na cotação
            if (!data.itens || data.itens.length === 0) {
                console.error('Não há itens na cotação');
                alert('Não há itens na cotação para exportar');
                return;
            }
            
            // Identificar o melhor preço para cada produto
            const produtosUnicos = new Map();
            
            // Agrupar itens por produto_id e encontrar o melhor preço
            data.itens.forEach(item => {
                console.log('Processando item:', item);
                
                // Usar produto_id se disponível, caso contrário usar uma combinação de outros campos
                const produtoId = item.produto_id || item.produto || 0;
                const produtoNome = item.produto_nome || item.descricao || 'Produto sem nome';
                const produtoCodigo = item.codigo || '';
                
                // Criar uma chave única para o produto
                // Se não tiver produto_id, usamos o nome como identificador
                const produtoKey = produtoId || produtoNome;
                
                if (!produtosUnicos.has(produtoKey)) {
                    produtosUnicos.set(produtoKey, {
                        id: produtoId,
                        nome: produtoNome,
                        codigo: produtoCodigo,
                        quantidade: parseFloat(item.quantidade) || 0,
                        melhorOferta: null,
                        ofertas: []
                    });
                }
                
                const produto = produtosUnicos.get(produtoKey);
                
                // Adicionar oferta
                const oferta = {
                    fornecedor_id: item.fornecedor,
                    fornecedor_nome: item.fornecedor_nome || `Fornecedor ${item.fornecedor}`,
                    valor_unitario: parseFloat(item.valor_unitario) || 0,
                    valor_total: parseFloat(item.valor_tot) || (parseFloat(item.quantidade) * parseFloat(item.valor_unitario)) || 0,
                    prazo_pagamento: item.prazo_pagamento || 'Não especificado'
                };
                
                produto.ofertas.push(oferta);
                
                // Atualizar melhor oferta se necessário
                if (!produto.melhorOferta || oferta.valor_unitario < produto.melhorOferta.valor_unitario) {
                    produto.melhorOferta = oferta;
                }
            });
            
            console.log('Produtos únicos com melhores ofertas:', produtosUnicos);
            
            // Converter para array de melhores ofertas
            const melhoresOfertas = [];
            produtosUnicos.forEach(produto => {
                if (produto.melhorOferta) {
                    melhoresOfertas.push({
                        'Fornecedor': produto.melhorOferta.fornecedor_nome,
                        'Produto': produto.nome,
                        'Quantidade': produto.quantidade.toString(),
                        'Valor Unitário': produto.melhorOferta.valor_unitario,
                        'Valor Total': produto.melhorOferta.valor_total,
                        'Prazo de Pagamento': produto.melhorOferta.prazo_pagamento
                    });
                }
            });
            
            console.log('Melhores ofertas:', melhoresOfertas);
            
            // Verificar se há ofertas para exportar
            if (melhoresOfertas.length === 0) {
                console.error('Não há melhores ofertas para exportar');
                alert('Não foi possível identificar as melhores ofertas para exportar');
                return;
            }
            
            // Agrupar por fornecedor para organizar a planilha
            const itensPorFornecedor = {};
            
            melhoresOfertas.forEach(item => {
                const fornecedor = item.Fornecedor;
                if (!itensPorFornecedor[fornecedor]) {
                    itensPorFornecedor[fornecedor] = [];
                }
                itensPorFornecedor[fornecedor].push({...item});
            });
            
            // Criar uma lista ordenada por fornecedor
            const itensOrdenados = [];
            
            // Para cada fornecedor, adicionar seus itens à lista
            Object.entries(itensPorFornecedor).forEach(([fornecedor, itens]) => {
                // Adicionar os itens do fornecedor
                itens.forEach(item => {
                    itensOrdenados.push(item);
                });
                
                // Calcular o subtotal do fornecedor
                const subtotalFornecedor = itens.reduce((total, item) => total + parseFloat(item['Valor Total'] || 0), 0);
                
                // Adicionar linha de subtotal para o fornecedor
                itensOrdenados.push({
                    'Fornecedor': '',
                    'Produto': `Subtotal - ${fornecedor}`,
                    'Quantidade': '',
                    'Valor Unitário': '',
                    'Valor Total': subtotalFornecedor,
                    'Prazo de Pagamento': ''
                });
                
                // Adicionar linha em branco após cada fornecedor
                itensOrdenados.push({
                    'Fornecedor': '',
                    'Produto': '',
                    'Quantidade': '',
                    'Valor Unitário': '',
                    'Valor Total': '',
                    'Prazo de Pagamento': ''
                });
            });
            
            // Calcular o valor total geral
            const valorTotalGeral = melhoresOfertas.reduce((total, item) => total + parseFloat(item['Valor Total'] || 0), 0);
            
            // Adicionar linha de total geral
            itensOrdenados.push({
                'Fornecedor': '',
                'Produto': 'TOTAL GERAL',
                'Quantidade': '',
                'Valor Unitário': '',
                'Valor Total': valorTotalGeral,
                'Prazo de Pagamento': ''
            });
            
            // Criar workbook
            const wb = XLSX.utils.book_new();
            
            // Criar planilha única com todos os itens
            const ws = XLSX.utils.json_to_sheet(itensOrdenados);
            
            // Definir larguras das colunas
            const wscols = [
                {wch: 30}, // Fornecedor
                {wch: 40}, // Produto
                {wch: 10}, // Quantidade
                {wch: 15}, // Valor Unitário
                {wch: 15}, // Valor Total
                {wch: 20}  // Prazo de Pagamento
            ];
            ws['!cols'] = wscols;
            
            // Adicionar planilha ao workbook
            XLSX.utils.book_append_sheet(wb, ws, 'Resumo de Melhores Preços');
            
            // Gerar arquivo XLSX
            const dataAtual = new Date().toISOString().split('T')[0]; // Formato YYYY-MM-DD
            const nomeArquivo = `Cotacao_${data.id}_MelhoresPrecos_${dataAtual}.xlsx`;
            
            XLSX.writeFile(wb, nomeArquivo);
            console.log('Arquivo XLSX gerado com sucesso:', nomeArquivo);
        })
        .catch(error => {
            console.error('Erro ao gerar arquivo XLSX:', error);
            alert('Erro ao gerar arquivo XLSX. Verifique o console para mais detalhes.');
        });
}

// Adicione esta função ao seu arquivo cotacoes.js
function importarCotacao(id) {
    console.log('Importando cotação ID:', id);
    
    // Verificar se o ID é válido
    if (!id || isNaN(id) || id <= 0) {
        alert('Por favor, informe um ID de cotação válido.');
        return;
    }
    
    // Limpar o formulário atual
    resetForm();
    
    // Buscar dados da cotação a ser importada
    fetch(`api/cotacoes.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Cotação não encontrada ou erro na requisição.');
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados da cotação importada:', data);
            
            // Verificar se há itens na cotação
            if (!data.itens || data.itens.length === 0) {
                alert('A cotação selecionada não possui itens para importar.');
                return;
            }
            
            // Agrupar itens por fornecedor
            const itensPorFornecedor = {};
            
            data.itens.forEach(item => {
                const fornecedorId = item.fornecedor;
                
                if (!itensPorFornecedor[fornecedorId]) {
                    itensPorFornecedor[fornecedorId] = {
                        fornecedor_id: fornecedorId,
                        prazo_pagamento: item.prazo_pagamento || '',
                        produtos: []
                    };
                }
                
                itensPorFornecedor[fornecedorId].produtos.push({
                    produto_id: item.produto_id,
                    produto_nome: item.produto_nome || '',
                    produto_codigo: item.codigo || item.produto_codigo || '',
                    produto_unidade: item.unidade || item.produto_unidade || '-', 
                    quantidade: item.quantidade || 0,
                    valor_unitario: item.valor_unitario || 0
                });
                
            });
            
            // Adicionar cada fornecedor e seus produtos ao formulário
            Object.values(itensPorFornecedor).forEach(fornecedor => {
                adicionarFornecedor();
                
                const fornecedorSection = document.querySelector('.fornecedor-section:last-child');
                const fornecedorSelect = fornecedorSection.querySelector('.fornecedor-select');
                
                // Definir o fornecedor
                if (fornecedorSelect) {
                    fornecedorSelect.value = fornecedor.fornecedor_id;
                }
                
                // Definir o prazo de pagamento
                const prazoPagamento = fornecedorSection.querySelector('.prazo-pagamento');
                if (prazoPagamento) {
                    prazoPagamento.value = fornecedor.prazo_pagamento;
                }
                
                // Adicionar cada produto
                fornecedor.produtos.forEach(produto => {
                    adicionarProdutoSelect(fornecedorSection.id);
                    
                    const produtoRow = fornecedorSection.querySelector('.produtos-fornecedor tr:last-child');
                    const produtoSelect = produtoRow.querySelector('.produto-select');
                    
                    if (produtoSelect) {
                        produtoSelect.value = produto.produto_id;
                    }
                    
                    produtoRow.querySelector('.quantidade').value = produto.quantidade;
                    produtoRow.querySelector('.valor-unitario').value = produto.valor_unitario;
                    
                    // Calcular o total
                    calcularTotal(produtoRow.id);
                });
            });
            
            // Mostrar mensagem de sucesso
            alert(`Cotação #${id} importada com sucesso! Você pode fazer as alterações necessárias antes de salvar.`);
            
        })
        .catch(error => {
            console.error('Erro ao importar cotação:', error);
            alert('Erro ao importar cotação: ' + error.message);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    // Handler de envio do Excel
    const excelFileInput = document.getElementById('excelFile');
    if (excelFileInput) {
        excelFileInput.addEventListener('change', function (e) {
            if (this.files.length > 0) {
                handleExcelUpload(this.files[0]);
            }
        });
    } else {
        console.warn('Campo de input de Excel (#excelFile) não encontrado no DOM.');
    }
});


document.getElementById('excelFile').addEventListener('change', handleExcelUpload);

function handleExcelUpload(event) {
    if (!event || !event.target || !event.target.files) {
        console.log('Evento inválido.');
        return;
    }

    const file = event.target.files[0];
    if (!file) {
        console.log('Nenhum arquivo selecionado.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        produtosImportados = XLSX.utils.sheet_to_json(firstSheet);
        
        console.log('Produtos importados:', produtosImportados);
        alert('Produtos importados com sucesso! Agora clique em "Adicionar Fornecedor" para continuar.');
    };

    reader.readAsArrayBuffer(file);
}

function preencherTabelaProdutos(produtos) {
    const tbody = document.querySelector('.produtos-fornecedor');
    tbody.innerHTML = '';
    
    produtos.forEach(produto => {
        const template = document.getElementById('template-produto');
        const clone = document.importNode(template.content, true);
        
        // Preencher os campos do produto
        const produtoSearch = clone.querySelector('.produto-search');
        const produtoId = clone.querySelector('.produto-id');
        const produtoSelected = clone.querySelector('.produto-selected');
        const quantidade = clone.querySelector('.quantidade');
        const unidade = clone.querySelector('.unidade');
        const ultimoValorAprovado = clone.querySelector('.ultimo-valor-aprovado');
        const valorUnitario = clone.querySelector('.valor-unitario');
        
        // Preencher os valores
        produtoId.value = produto.id || '';
        produtoSelected.textContent = produto.nome || '';
        quantidade.value = produto.quantidade || 1;
        unidade.value = produto.unidade || 'UN';
        
        // Preencher o último valor aprovado se disponível
        if (produto.ultimo_valor_aprovado) {
            ultimoValorAprovado.textContent = formatarNumero(produto.ultimo_valor_aprovado);
        } else {
            ultimoValorAprovado.textContent = '-';
        }
        
        // Adicionar a linha à tabela
        tbody.appendChild(clone);
        
        // Configurar eventos
        const row = tbody.lastElementChild;
        const btnRemover = row.querySelector('.btn-remover-produto');
        const inputQuantidade = row.querySelector('.quantidade');
        const inputValorUnitario = row.querySelector('.valor-unitario');
        
        // Evento para remover produto
        btnRemover.addEventListener('click', () => {
            row.remove();
            recalcular();
        });
        
        // Eventos para recalcular totais
        inputQuantidade.addEventListener('input', recalcular);
        inputValorUnitario.addEventListener('input', recalcular);
    });
    
    // Recalcular totais iniciais
    recalcular();
}

function carregarVersao(cotacaoId, versao) {
    fetch(`api/cotacoes.php?id=${cotacaoId}&versao=${versao}`)
        .then(response => response.json())
        .then(dadosVersao => {
            // Get current version for comparison
            fetch(`api/cotacoes.php?id=${cotacaoId}`)
                .then(response => response.json())
                .then(versaoAtual => {
                    const comparativo = renderizarComparativoVersoes(dadosVersao, versaoAtual);
                    document.getElementById('modal-conteudo-analise').appendChild(comparativo);
                });
        });
}


document.querySelector('.btn-adicionar-fornecedor').addEventListener('click', function () {
    if (produtosImportados.length === 0) {
        alert('Você precisa fazer o upload da planilha antes de adicionar fornecedores.');
        return;
    }

    const template = document.getElementById('template-fornecedor');
    const clone = template.content.cloneNode(true);

    const produtosContainer = clone.querySelector('.produtos-fornecedor');

    produtosImportados.forEach(produto => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="produto-selected">${produto.Nome}</div>
                <input type="hidden" class="produto-id" value="${produto.Codigo}">
            </td>
            <td><input type="number" class="quantidade" value="${produto.Qtde}" required></td>
            <td class="unidade-medida">${produto.UN}</td>
            <td><input type="number" class="valor-unitario" value="" step="0.0001" min="0" required></td>
            <td class="valor-unit-difal-frete">0,00</td> <!-- Nova coluna -->
            <td class="total">0,0000</td>
            <td>
                <button type="button" class="btn-remover-produto">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        produtosContainer.appendChild(tr);

        const valorInput = tr.querySelector('.valor-unitario');
        valorInput.addEventListener('input', () => {
            const quantidade = parseFloat(produto.Qtde) || 0;
            const valor = parseFloat(valorInput.value) || 0;
            tr.querySelector('.total').textContent = (quantidade * valor).toFixed(2);
        });

        tr.querySelector('.btn-remover-produto').addEventListener('click', () => tr.remove());
    });

    document.getElementById('fornecedores-container').appendChild(clone);
});

// Make helper functions globally available
window.adicionarFornecedor = adicionarFornecedor;
window.adicionarProdutoSelect = adicionarProdutoSelect;
window.calcularTotal = calcularTotal;
window.removerProduto = removerProduto;

// Função para atualizar os contadores dos cards
function atualizarContadoresCards() {
    const rows = document.querySelectorAll('#tabela-cotacoes tbody tr');
    const contadores = {
        'pendente': 0,
        'aguardando_aprovacao': 0,
        'aprovado': 0,
        'rejeitado': 0,
        'renegociacao': 0
    };

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (contadores.hasOwnProperty(status)) {
            contadores[status]++;
        }
    });

    // Atualizar os elementos na página
    document.getElementById('pendentes-count').textContent = contadores['pendente'];
    document.getElementById('aguardando-aprovacao-count').textContent = contadores['aguardando_aprovacao'];
    document.getElementById('aprovadas-count').textContent = contadores['aprovado'];
    document.getElementById('rejeitadas-count').textContent = contadores['rejeitado'];
    document.getElementById('renegociacao-count').textContent = contadores['renegociacao'];
}

// Chamar a função quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    atualizarContadoresCards();
});
