<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

// Verificar se o usuário tem permissão (admin ou gerencia)
if (!in_array($_SESSION['usuario']['tipo'], ['admin', 'gerencia'])) {
    header('Location: index.php');
    exit;
}

$pagina_atual = 'fornecedores';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/fornecedores.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Análise de Fornecedores</h1>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <div class="filtro-grupo">
                <label for="filtro-fornecedor">Fornecedor:</label>
                <select id="filtro-fornecedor">
                    <option value="">Todos</option>
                    <!-- Opções serão carregadas via JavaScript -->
                </select>
            </div>

            <div class="filtro-grupo">
                <label for="filtro-produto">Produto:</label>
                <select id="filtro-produto">
                    <option value="">Todos</option>
                    <!-- Opções serão carregadas via JavaScript -->
                </select>
            </div>

            <div class="filtro-grupo">
                <label for="data-inicio">Data Início:</label>
                <input type="date" id="data-inicio" name="data_inicio">
            </div>

            <div class="filtro-grupo">
                <label for="data-fim">Data Fim:</label>
                <input type="date" id="data-fim" name="data_fim">
            </div>

            <button id="btn-filtros" class="btn-filtrar">
                <i class="fas fa-filter"></i> Filtros
            </button>

            <button id="btn-aplicar-filtros" class="btn-aplicar">
                <i class="fas fa-check"></i> Aplicar
            </button>

            <button id="btn-limpar-filtros" class="btn-limpar">
                <i class="fas fa-eraser"></i> Limpar
            </button>

          <!--  <button id="btn-exportar" class="btn-exportar">
                <i class="fas fa-file-excel"></i> Exportar
            </button> -->
        </div>

        <!-- Cards de Resumo -->
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-truck"></i>
                <h3>Total de Fornecedores</h3>
                <span class="number" id="total-fornecedores">0</span>
            </div>
            <div class="card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Total de Compras</h3>
                <span class="number" id="total-compras">R$ 0,00</span>
            </div>
            <div class="card">
                <i class="fas fa-box"></i>
                <h3>Produtos Únicos</h3>
                <span class="number" id="total-produtos">0</span>
            </div>
            <div class="card">
                <i class="fas fa-clock"></i>
                <h3>Prazo Médio</h3>
                <span class="number" id="prazo-medio">0 dias</span>
            </div>
        </div>

        <!-- Tabela de Fornecedores -->
        <div class="table-section">
            <div class="table-header">
                <h2>Fornecedores</h2>
            </div>
            <div class="table-responsive">
                <table class="tabela-fornecedores">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Total Compras</th>
                            <th>Produtos Únicos</th>
                            <th>Prazo Médio</th>
                            <th>Economia Total</th>
                            <th>Última Compra</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-fornecedores-body">
                        <tr>
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Carregando dados...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="paginacao" class="pagination-container"></div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal" id="modalDetalhes">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"></h2>
                <button type="button" class="close" onclick="document.getElementById('modalDetalhes').style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Resumo do Fornecedor -->
                <div class="fornecedor-resumo">
                    <div class="resumo-card">
                        <div class="resumo-titulo">Total de Compras</div>
                        <div class="resumo-valor" id="resumo-total-compras">0</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-titulo">Valor Médio</div>
                        <div class="resumo-valor" id="resumo-valor-medio">R$ 0,00</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-titulo">Economia Total</div>
                        <div class="resumo-valor" id="resumo-economia">R$ 0,00</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-titulo">Participação no Mercado</div>
                        <div class="resumo-valor" id="resumo-participacao">0%</div>
                    </div>
                </div>

                <div class="tabs">
                    <button class="tab-btn active" onclick="mudarTab('produtos')">Produtos</button>
                    <button class="tab-btn" onclick="mudarTab('historico')">Histórico</button>
                    <button class="tab-btn" onclick="mudarTab('metricas')">Métricas</button>
                    <button class="tab-btn" onclick="mudarTab('comparativo')">Comparativo</button>
                </div>
                
                <div class="tab-content" id="tabProdutos">
                    <div class="table-filters">
                        <div class="search-box">
                            <input type="text" id="search-produto" placeholder="Buscar produto...">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="filter-box">
                            <select id="filter-ordem-produtos">
                                <option value="quantidade">Mais Comprados</option>
                                <option value="economia">Maior Economia</option>
                                <option value="preco">Menor Preço</option>
                            </select>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade Total</th>
                                <th>Preço Médio</th>
                                <th>Menor Preço</th>
                                <th>Maior Preço</th>
                                <th>Economia</th>
                                <th>Última Compra</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="tabHistorico" style="display: none;">
                    <div class="table-filters">
                        <div class="date-range">
                            <input type="date" id="historico-data-inicio">
                            <span>até</span>
                            <input type="date" id="historico-data-fim">
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário</th>
                                <th>Valor Total</th>
                                <th>Economia</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="tabMetricas" style="display: none;">
                    <div class="metricas-grid">
                        <div class="metrica-card">
                            <div class="metrica-titulo">Economia Total</div>
                            <div class="metrica-valor" data-metrica="economia">R$ 0,00</div>
                            <div class="metrica-trend" data-trend="economia">
                                <i class="fas fa-arrow-up"></i>
                                <span>0%</span>
                            </div>
                        </div>
                        <div class="metrica-card">
                            <div class="metrica-titulo">Produtos Mais Comprados</div>
                            <div class="metrica-valor" data-metrica="produtos-top">Nenhum produto</div>
                        </div>
                        <div class="metrica-card">
                            <div class="metrica-titulo">Melhor Prazo</div>
                            <div class="metrica-valor" data-metrica="melhor-prazo">N/A</div>
                        </div>
                        <div class="metrica-card">
                            <div class="metrica-titulo">Preço Médio</div>
                            <div class="metrica-valor" data-metrica="preco-medio">R$ 0,00</div>
                            <div class="metrica-trend" data-trend="preco">
                                <i class="fas fa-arrow-down"></i>
                                <span>0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="tabComparativo" style="display: none;">
                    <div class="comparativo-section">
                        <h3>Comparativo com Outros Fornecedores</h3>
                        <div class="comparativo-grid">
                            <div class="comparativo-card">
                                <div class="comparativo-titulo">Ranking de Preços</div>
                                <div class="comparativo-valor" id="ranking-precos">-</div>
                                <div class="comparativo-desc">Posição entre todos os fornecedores</div>
                            </div>
                            <div class="comparativo-card">
                                <div class="comparativo-titulo">Ranking de Economia</div>
                                <div class="comparativo-valor" id="ranking-economia">-</div>
                                <div class="comparativo-desc">Posição entre todos os fornecedores</div>
                            </div>
                            <div class="comparativo-card">
                                <div class="comparativo-titulo">Produtos Exclusivos</div>
                                <div class="comparativo-valor" id="produtos-exclusivos">0</div>
                                <div class="comparativo-desc">Produtos únicos deste fornecedor</div>
                            </div>
                            <div class="comparativo-card">
                                <div class="comparativo-titulo">Participação de Mercado</div>
                                <div class="comparativo-valor" id="participacao-mercado">0%</div>
                                <div class="comparativo-desc">Porcentagem do total de compras</div>
                            </div>
                        </div>
                    </div>
                    <div class="comparativo-charts">
                        <div class="chart-container">
                            <h4>Evolução de Preços</h4>
                            <canvas id="precos-chart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h4>Distribuição de Produtos</h4>
                            <canvas id="produtos-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/fornecedores.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
