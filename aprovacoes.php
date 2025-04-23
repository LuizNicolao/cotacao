<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

// Verificar se o usuário está logado e tem permissão para acessar aprovações
if (!isset($_SESSION['usuario']) || !userCan('aprovacoes', 'visualizar')) {
    header("Location: index.php");
    exit;
}

$conn = conectarDB();

// Modificar a consulta para buscar todas as cotações, não apenas as que estão aguardando aprovação
$query = "SELECT 
    c.id,
    c.data_criacao,
    c.status,
    c.usuario_id,
    u.nome as usuario_nome,
    COUNT(i.id) as total_itens,
    SUM(i.quantidade * (i.valor_unitario + (i.valor_unitario * i.difal / 100))) + COALESCE(SUM(i.frete), 0) as valor_total
FROM cotacoes c
JOIN usuarios u ON c.usuario_id = u.id
LEFT JOIN itens_cotacao i ON c.id = i.cotacao_id
GROUP BY c.id, c.data_criacao, c.status, c.usuario_id, u.nome
ORDER BY 
    CASE 
        WHEN c.status = 'aguardando_aprovacao' THEN 1
        WHEN c.status = 'pendente' THEN 2
        WHEN c.status = 'aprovado' THEN 3
        WHEN c.status = 'rejeitado' THEN 4
        ELSE 5
    END,
    c.data_criacao DESC";

$cotacoes = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovações - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/aprovacoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <h2>Aprovações de Cotações</h2>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario']['nome']; ?></span>
                </div>
            </header>

            <div class="filtros">
                <div class="filtro-grupo">
                    <label>Status:</label>
                    <select id="filtro-status">
                        <option value="">Todos</option>
                        <option value="aguardando_aprovacao" selected>Aguardando Aprovação</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="rejeitado">Rejeitado</option>
                        <option value="renegociacao">Em Renegociação</option>
                    </select>
                </div>
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
                <div class="filtro-grupo">
                    <label>Período:</label>
                    <input type="date" id="filtro-data-inicio">
                    <span>até</span>
                    <input type="date" id="filtro-data-fim">
                </div>
                <div class="filtro-botoes">
                    <button id="btn-filtrar" class="btn-filtrar">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button id="btn-limpar" class="btn-limpar">
                        <i class="fas fa-times"></i> Limpar Filtros
                    </button>
                </div>
            </div>

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
                            <button class="btn-acao btn-visualizar" onclick="analisarCotacao(<?php echo $cotacao['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/modal_aprovacoes.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar filtros
        const btnFiltrar = document.getElementById('btn-filtrar');
        const btnLimpar = document.getElementById('btn-limpar');
        
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', function() {
                filtrarCotacoes();
            });
        }

        if (btnLimpar) {
            btnLimpar.addEventListener('click', function() {
                limparFiltros();
            });
        }

        // Verificar se existem parâmetros na URL
        const urlParams = new URLSearchParams(window.location.search);
        const cotacaoId = urlParams.get('cotacao_id');
        const statusParam = urlParams.get('status');

        // Se houver parâmetros, aplicar os filtros
        if (cotacaoId || statusParam) {
            // Definir o status no select se fornecido
            if (statusParam) {
                const statusSelect = document.getElementById('filtro-status');
                statusSelect.value = statusParam;
            }

            // Aplicar os filtros
            filtrarCotacoes(cotacaoId);
        } else {
            // Caso contrário, aplicar filtro padrão (aguardando aprovação)
            filtrarCotacoes();
        }
        
        function limparFiltros() {
            // Limpar todos os campos de filtro
            document.getElementById('filtro-status').value = '';
            document.getElementById('filtro-comprador').value = '';
            document.getElementById('filtro-data-inicio').value = '';
            document.getElementById('filtro-data-fim').value = '';
            
            // Remover parâmetros da URL
            window.history.replaceState({}, document.title, window.location.pathname);
            
            // Mostrar todas as linhas
            const rows = document.querySelectorAll('#tabela-cotacoes tbody tr');
            rows.forEach(row => {
                row.style.display = '';
                row.classList.remove('destacado');
            });
        }
        
        function filtrarCotacoes(cotacaoEspecifica = null) {
            const status = document.getElementById('filtro-status').value;
            const comprador = document.getElementById('filtro-comprador').value;
            const dataInicio = document.getElementById('filtro-data-inicio').value;
            const dataFim = document.getElementById('filtro-data-fim').value;
            
            const rows = document.querySelectorAll('#tabela-cotacoes tbody tr');
            
            rows.forEach(row => {
                let mostrar = true;
                
                // Se houver uma cotação específica, mostrar apenas ela
                if (cotacaoEspecifica) {
                    mostrar = row.querySelector('td').textContent === cotacaoEspecifica;
                } else {
                    // Filtrar por status
                    if (status && row.getAttribute('data-status') !== status) {
                        mostrar = false;
                    }
                    
                    // Filtrar por comprador
                    if (comprador && row.getAttribute('data-usuario') !== comprador) {
                        mostrar = false;
                    }
                    
                    // Filtrar por data
                    if (dataInicio || dataFim) {
                        const dataCotacao = new Date(row.children[1].getAttribute('data-date'));
                        
                        if (dataInicio && new Date(dataInicio) > dataCotacao) {
                            mostrar = false;
                        }
                        
                        if (dataFim && new Date(dataFim) < dataCotacao) {
                            mostrar = false;
                        }
                    }
                }
                
                row.style.display = mostrar ? '' : 'none';
            });

            // Se houver uma cotação específica e ela existir, destacá-la
            if (cotacaoEspecifica) {
                const rows = document.querySelectorAll('#tabela-cotacoes tbody tr');
                for (const row of rows) {
                    const firstCell = row.querySelector('td:first-child');
                    if (firstCell && firstCell.textContent.trim() === cotacaoEspecifica.toString()) {
                        row.classList.add('destacado');
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        break;
                    }
                }
            }
        }
    });

    // Função para alternar entre as visualizações
    function alternarVisualizacao(viewId) {
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
        } else {
            console.warn(`View element with ID "${viewId}" not found`);
            return; // Sair da função se o elemento não for encontrado
        }
        
        // Adicionar classe 'active' ao botão correspondente
        const btnId = 'btn-' + viewId.replace('conteudo-', '');
        const btnElement = document.getElementById(btnId);
        
        if (btnElement) {
            btnElement.classList.add('active');
        } else {
            console.warn(`Button element with ID "${btnId}" not found`);
            // Não tente acessar classList se o elemento não existir
        }
    }


    
    </script>
</body>
</html>
