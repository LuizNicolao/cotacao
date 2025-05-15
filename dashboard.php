<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';


// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Verificar o tipo de usuário - permitir apenas Admin e gerencia
$usuario_tipo = $_SESSION['usuario']['tipo'] ?? '';
$tipos_permitidos = ['administrador', 'gerencia', 'admin', 'gerencia']; // Inclua todos os tipos que devem ter acesso

if (!in_array(strtolower($usuario_tipo), $tipos_permitidos)) {
    // Redirecionar para uma página apropriada para o tipo de usuário
    if (strtolower($usuario_tipo) == 'comprador') {
        header("Location: cotacoes.php"); // Redirecionar compradores para a página de cotações
    } else {
        header("Location: acesso_negado.php"); // Ou qualquer outra página para tipos não permitidos
    }
    exit;
}

$conn = conectarDB();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <div class="welcome">
                    <h2>Dashboard</h2>
                    <p class="user-info-subtitle">Painel de Controle (<?php echo $usuario_tipo; ?>)</p>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario']['nome']; ?></span>
                    <span class="user-type">(<?php echo $usuario_tipo; ?>)</span>
                </div>
            </header>

            <!-- Seção de Alertas -->
            <div class="alertas-section" id="alertas-container">
                <!-- Alertas serão inseridos via JavaScript -->
            </div>

            <!-- Cards de Estatísticas -->
            <div class="dashboard-cards">
                <div class="card">
                    <i class="fas fa-file-invoice"></i>
                    <div class="card-info">
                        <h3>Cotações Aguardando Aprovação</h3>
                        <span class="number" id="pendentes-count">0</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-check-circle"></i>
                    <div class="card-info">
                        <h3>Cotações Aprovadas</h3>
                        <span class="number" id="aprovadas-count">0</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-times-circle"></i>
                    <div class="card-info">
                        <h3>Cotações Rejeitadas</h3>
                        <span class="number" id="rejeitadas-count">0</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-sync-alt"></i>
                    <div class="card-info">
                        <h3>Cotações em Renegociação</h3>
                        <span class="number" id="renegociacao-count">0</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-dollar-sign"></i>
                    <div class="card-info">
                        <h3>Cotação do Dólar</h3>
                        <span class="number" id="dolar-rate">R$ 0,00</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-gas-pump"></i>
                    <div class="card-info">
                        <h3>Preço do Diesel</h3>
                        <span class="number" id="diesel-rate">R$ 0,00</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-calendar"></i>
                    <div class="card-info">
                        <h3>Cotações Programadas</h3>
                        <span class="number" id="programadas-count">0</span>
                    </div>
                </div>

                <div class="card">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="card-info">
                        <h3>Cotações Emergenciais</h3>
                        <span class="number" id="emergenciais-count">0</span>
                    </div>
                </div>
            </div>

            <!-- Estatísticas do Sawing -->
            <div class="sawing-stats">
                <h3>Métricas de Economia</h3>
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-piggy-bank"></i>
                        <div class="stat-info">
                            <h4>Economia Total</h4>
                            <span class="value" id="economia-total">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-percentage"></i>
                        <div class="stat-info">
                            <h4>Economia Percentual</h4>
                            <span class="value" id="economia-percentual">0%</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <div class="stat-info">
                            <h4>Total Negociado</h4>
                            <span class="value" id="total-negociado">R$ 0,00</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-double"></i>
                        <div class="stat-info">
                            <h4>Total Aprovado</h4>
                            <span class="value" id="total-aprovado">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="dashboard-charts">
                <div class="chart-container">
                    <h3>Distribuição de Status</h3>
                    <canvas id="status-chart"></canvas>
                </div>
            </div>

            <!-- Cotações Recentes -->
            <div class="recent-quotes">
                <h3>Cotações Recentes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Comprador</th>
                            <th>Tipo</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="cotacoes-recentes">
                        <!-- Dados serão inseridos via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
