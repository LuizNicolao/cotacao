<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Verificar permissões
if (!userCan('sawing', 'visualizar')) {
    header('Location: dashboard.php?erro=permissao');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sawing - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sawing.css">
    <link rel="stylesheet" href="assets/css/modal_aprovacoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Sawing - Análise de Economia</h1>
                <p class="page-subtitle">Monitore o impacto financeiro das negociações e economias obtidas</p>
            </div>

            <div class="filtros">
                <div class="filtro-grupo">
                    <label for="filtro-comprador">Comprador:</label>
                    <select id="filtro-comprador">
                        <option value="">Todos</option>
                        <!-- Opções serão carregadas via JavaScript -->
                    </select>
                </div>

                <div class="filtro-grupo">
                    <label for="filtro-status">Status:</label>
                    <select id="filtro-status">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="em_andamento">Em andamento</option>
                        <option value="concluido">Concluído</option>
                        <option value="cancelado">Cancelado</option>
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

                <button id="btn-exportar" class="btn-exportar">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
            </div>

            <div class="resumo-orcamento">
                <div class="resumo-cards">
                    <div class="resumo-card">
                        <div class="resumo-valor" id="economia-total">R$ 0,00</div>
                        <div class="resumo-label">Economia Total</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="economia-percentual-media">0%</div>
                        <div class="resumo-label">Economia Total (%)</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="rodadas-media">0</div>
                        <div class="resumo-label">Total de Rodadas</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="total-negociado">R$ 0,00</div>
                        <div class="resumo-label">Total Negociado</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="total-aprovado">R$ 0,00</div>
                        <div class="resumo-label">Total Aprovado</div>
                    </div>
                </div>
            </div>

            <div class="compradores-section">
                <h2>Métricas por Comprador</h2>
                <div class="compradores-destaque">
                    <div class="melhor-comprador" id="melhor-comprador">
                        <!-- Card do melhor comprador será carregado via JavaScript -->
                    </div>
                    <div class="pior-comprador" id="pior-comprador">
                        <!-- Card do pior comprador será carregado via JavaScript -->
                    </div>
                </div>
                <div class="compradores-cards" id="compradores-cards">
                    <!-- Cards serão carregados via JavaScript -->
                </div>
            </div>

            <table id="tabela-sawing">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cotação</th>
                        <th>Comprador</th>
                        <th>Data</th>
                        <th>Valor Inicial</th>
                        <th>Valor Final</th>
                        <th>Economia (R$)</th>
                        <th>Economia (%)</th>
                        <th>Rodadas</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-sawing-body">
                    <!-- Dados serão carregados via JavaScript -->
                </tbody>
            </table>

            <!-- Adicione isso ao seu HTML se ainda não existir -->
            <div class="paginacao-container">
                <div class="row">
                    <div class="col-md-6">
                        <p id="info-registros">Mostrando 0 a 0 de 0 registros</p>
                    </div>
                    <div class="col-md-6">
                        <ul class="pagination" id="paginacao">
                            <!-- Paginação será gerada via JavaScript -->
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div id="modalDetalhesSawing" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Sawing</h2>
                <span class="close" id="btn-fechar-modal">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Resumo com cards -->
                <div class="resumo-cards">
                    <div class="resumo-card">
                        <div class="resumo-valor" id="sawing-valor-inicial">R$ 0,00</div>
                        <div class="resumo-label">Valor Inicial</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="sawing-valor-final">R$ 0,00</div>
                        <div class="resumo-label">Valor Final</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="sawing-economia">R$ 0,00</div>
                        <div class="resumo-label">Economia</div>
                    </div>
                    <div class="resumo-card">
                        <div class="resumo-valor" id="sawing-economia-percentual">0%</div>
                        <div class="resumo-label">Economia (%)</div>
                    </div>
                </div>

                <!-- Informações detalhadas -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">ID</div>
                        <div class="value" id="sawing-id"></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Data de Criação</div>
                        <div class="value" id="sawing-data"></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Data de Aprovação</div>
                        <div class="value" id="sawing-data-aprovacao"></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Rodadas</div>
                        <div class="value" id="sawing-rodadas"></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Status</div>
                        <div class="value" id="sawing-status"></div>
                    </div>
                </div>

                <!-- Observações -->
                <div class="info-cotacao">
                    <h4>Observações</h4>
                    <p id="sawing-observacoes">Nenhuma observação</p>
                </div>

                <!-- Produtos -->
                <div class="produto-section">
                    <h4>Produtos Negociados</h4>
                    <div id="produtos-container">
                        <!-- A tabela de produtos será inserida aqui via JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-grande" id="btn-fechar-modal-footer">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Exportação -->
    <div id="modal-exportar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Opções de Exportação</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <div class="export-section">
                        <h3>Formato</h3>
                        <div class="checkbox-group">
                            <label>
                                <input type="radio" name="formato" value="excel" checked>
                                Excel (.xls)
                            </label>
                            <label>
                                <input type="radio" name="formato" value="csv">
                                CSV (.csv)
                            </label>
                        </div>
                    </div>

                    <div class="export-section">
                        <h3>Informações Básicas</h3>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="basicas" value="id" checked>
                                ID
                            </label>
                            <label>
                                <input type="checkbox" name="basicas" value="cotacao" checked>
                                Cotação
                            </label>
                            <label>
                                <input type="checkbox" name="basicas" value="comprador" checked>
                                Comprador
                            </label>
                            <label>
                                <input type="checkbox" name="basicas" value="data" checked>
                                Data
                            </label>
                            <label>
                                <input type="checkbox" name="basicas" value="status" checked>
                                Status
                            </label>
                        </div>
                    </div>

                    <div class="export-section">
                        <h3>Detalhes Adicionais</h3>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="detalhes" value="produtos">
                                Produtos Negociados
                            </label>
                            <label>
                                <input type="checkbox" name="detalhes" value="fornecedores">
                                Fornecedores
                            </label>
                            <label>
                                <input type="checkbox" name="detalhes" value="historico">
                                Histórico de Rodadas
                            </label>
                        </div>
                    </div>

                    <div class="export-section">
                        <h3>Métricas</h3>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="metricas" value="comprador">
                                Métricas por Comprador
                            </label>
                            <label>
                                <input type="checkbox" name="metricas" value="geral">
                                Métricas Gerais
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancelar">Cancelar</button>
                <button class="btn btn-primary btn-exportar">Exportar</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/sawing.js"></script>
</body>

</html>