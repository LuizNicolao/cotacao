<?php
// filepath: c:\Xampp\htdocs\cotacao\cotacoes.php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION['usuario']) || !userCan('cotacoes', 'visualizar')) {
    header("Location: dashboard.php");
    exit;
}

$conn = conectarDB();

// Verificar o tipo de usuário - Garantir que estamos obtendo o tipo corretamente
$usuario_id = $_SESSION['usuario']['id'];
$usuario_tipo = $_SESSION['usuario']['tipo'] ?? '';

// Verificar se o tipo está sendo obtido corretamente
error_log("Tipo de usuário: " . $usuario_tipo);

// Definir quem são os administradores (que podem ver todas as cotações)
$is_admin = in_array(strtolower($usuario_tipo), ['administrador', 'gerencia', 'admin']);

// Construir a consulta SQL com base no tipo de usuário
$query = "SELECT 
    c.*, 
    u.nome as usuario_nome,
    COUNT(i.id) as total_itens,
     SUM(
            CASE 
                WHEN c.status = 'aprovado' AND i.aprovado = 1 THEN i.quantidade * i.valor_unitario
                WHEN c.status != 'aprovado' THEN i.quantidade * i.valor_unitario
                ELSE 0 
            END
        ) as valor_total
FROM cotacoes c
JOIN usuarios u ON c.usuario_id = u.id
LEFT JOIN itens_cotacao i ON c.id = i.cotacao_id";

// Se o usuário NÃO for admin, filtrar apenas suas cotações
if (!$is_admin) {
    $query .= " WHERE c.usuario_id = :usuario_id";
    error_log("Filtrando cotações para o usuário ID: " . $usuario_id);
}

$query .= " GROUP BY c.id ORDER BY c.data_criacao DESC";

error_log("Query SQL: " . $query);

// Preparar e executar a consulta
$stmt = $conn->prepare($query);

// Vincular parâmetros se não for admin
if (!$is_admin) {
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
}

$stmt->execute();
$cotacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar quantas cotações foram retornadas
error_log("Número de cotações retornadas: " . count($cotacoes));

function salvarVersaoCotacao($cotacaoId, $dados) {
    $versaoAtual = getUltimaVersao($cotacaoId) + 1;
    $dadosJson = json_encode($dados);
    
    $sql = "INSERT INTO cotacoes_versoes (cotacao_id, versao, dados_json, data_criacao, usuario_id) 
            VALUES (?, ?, ?, NOW(), ?)";
            
    // Execute query...
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotações - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cotacoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <div class="welcome">
                    <h2>Cotações</h2>
                    <?php if (!$is_admin): ?>
                    <p class="user-info-subtitle">Visualizando suas cotações</p>
                    <?php else: ?>
                    <p class="user-info-subtitle">Visualizando todas as cotações (<?php echo $usuario_tipo; ?>)</p>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario']['nome']; ?></span>
                    <span class="user-type">(<?php echo $usuario_tipo; ?>)</span>
                </div>
            </header>

            <div class="content">
                <!-- Adicionar filtros -->
                <div class="filtros">
                    <div class="filtro-grupo">
                        <label>Status:</label>
                        <select id="filtro-status">
        <option value="">Todos</option>
        <option value="pendente">Pendente</option>
        <option value="aguardando_aprovacao">Aguardando Aprovação</option>
        <option value="aprovado">Aprovado</option>
        <option value="rejeitado">Rejeitado</option>
        <option value="renegociacao">Em Renegociação</option>
    </select>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="filtro-grupo">
                        <label>Comprador:</label>
                        <select id="filtro-comprador">
                            <option value="">Todos</option>
                            <?php
                            $compradores = $conn->query("SELECT DISTINCT u.id, u.nome FROM usuarios u JOIN cotacoes c ON u.id = c.usuario_id ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($compradores as $comprador) {
                                echo "<option value='{$comprador['id']}'>{$comprador['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="filtro-grupo">
                        <label>Período:</label>
                        <input type="date" id="filtro-data-inicio">
                        <span>até</span>
                        <input type="date" id="filtro-data-fim">
                    </div>
                    <button id="btn-filtrar" class="btn-filtrar">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>

                <?php if(userCan('cotacoes', 'criar')): ?>
                <button class="btn-adicionar">
                    <i class="fas fa-plus"></i> Nova Cotação
                </button>
                <?php endif; ?>

                <table id="tabela-cotacoes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data Criação</th>
                            <th>Comprador</th>
                            <th>Total Itens</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cotacoes) > 0): ?>
                            <?php foreach ($cotacoes as $cotacao): ?>
                            <tr data-status="<?php echo $cotacao['status']; ?>" data-usuario="<?php echo $cotacao['usuario_id']; ?>">
                                <td><?php echo $cotacao['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cotacao['data_criacao'])); ?></td>
                                <td><?php echo htmlspecialchars($cotacao['usuario_nome']); ?></td>
                                <td><?php echo $cotacao['total_itens']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $cotacao['status']; ?>">
                                        <?php 
$status_texto = [
    'pendente' => 'Pendente',
    'aguardando_aprovacao' => 'Aguardando Aprovação',
    'aprovado' => 'Aprovado',
    'rejeitado' => 'Rejeitado',
    'renegociacao' => 'Em Renegociação'
];

                                            echo $status_texto[$cotacao['status']] ?? ucfirst($cotacao['status']); 
                                        ?>
                                    </span>
                                </td>
                                <td class="acoes">
                                    <button class="btn-acao btn-visualizar" data-id="<?php echo $cotacao['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if(userCan('cotacoes', 'editar') && ($cotacao['status'] == 'pendente' || $cotacao['status'] == 'renegociacao') && ($is_admin || $cotacao['usuario_id'] == $usuario_id)): ?>
<button class="btn-acao btn-editar" data-id="<?php echo $cotacao['id']; ?>">
    <i class="fas fa-edit"></i>
</button>
<button class="btn-acao btn-aprovar" onclick="enviarParaAprovacao(<?php echo $cotacao['id']; ?>)">
    <i class="fas fa-paper-plane"></i>
</button>
<?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">Nenhuma cotação encontrada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'includes/modal_cotacao.php'; ?>
    
    <div id="modalVisualizacao" class="modal">
      <div class="modal-content">
        <div class="info-cotacao">
          <!-- Content will be populated here -->
        </div>
        <!-- Other modal content -->
      </div>
    </div>

    <script src="assets/js/cotacoes.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar filtros
        const btnFiltrar = document.getElementById('btn-filtrar');
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', function() {
                filtrarCotacoes();
            });
        }
        
        // Função para filtrar cotações
        function filtrarCotacoes() {
            const status = document.getElementById('filtro-status').value;
            const comprador = <?php echo $is_admin ? "document.getElementById('filtro-comprador').value" : "''"; ?>;
            const dataInicio = document.getElementById('filtro-data-inicio').value;
            const dataFim = document.getElementById('filtro-data-fim').value;
            
            const rows = document.querySelectorAll('#tabela-cotacoes tbody tr');
            
            rows.forEach(row => {
                let mostrar = true;
                
                // Filtrar por status
                if (status && row.getAttribute('data-status') !== status) {
                    mostrar = false;
                }
                
                // Filtrar por comprador (apenas para administradores)
                <?php if ($is_admin): ?>
                if (comprador && row.getAttribute('data-usuario') !== comprador) {
                    mostrar = false;
                }
                <?php endif; ?>
                
                // Filtrar por data (implementar se necessário)
                // ...
                
                row.style.display = mostrar ? '' : 'none';
            });
        }
        
        // Initialize modal and attach listeners
        initializeModal();
    });
    </script>
</body>
</html>