<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

if (!isset($_SESSION['usuario']) || !userCan('fornecedores', 'visualizar')) {
    header("Location: dashboard.php");
    exit;
}

$conn = conectarDB();

// Inicializar variáveis de filtro
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Construir a consulta SQL com filtros
$query = "SELECT * FROM fornecedores WHERE 1=1";

if (!empty($busca)) {
    $query .= " AND (nome LIKE :busca OR cnpj LIKE :busca OR email LIKE :busca)";
}

if ($status !== '') {
    $query .= " AND status = :status";
}

$query .= " ORDER BY nome";

// Preparar e executar a consulta
$stmt = $conn->prepare($query);

if (!empty($busca)) {
    $termo_busca = "%{$busca}%";
    $stmt->bindParam(':busca', $termo_busca, PDO::PARAM_STR);
}

if ($status !== '') {
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
}

$stmt->execute();
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/fornecedores.css">
    <style>
        .filtros-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filtro-grupo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filtro-grupo label {
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .filtro-grupo input,
        .filtro-grupo select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }
        
        .btn-filtrar {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-limpar {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-filtrar:hover, .btn-limpar:hover {
            opacity: 0.9;
        }
        
        .resultados-info {
            margin-bottom: 15px;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <div class="welcome">
                    <h2>Fornecedores</h2>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?></span>
                </div>
            </header>

            <!-- Filtros de busca -->
            <div class="filtros-container">
                <form action="" method="GET" class="form-filtros">
                    <div class="filtro-grupo">
                        <label for="busca">Buscar por Nome, CNPJ ou Email:</label>
                        <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Digite para buscar...">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    
                    <div class="filtro-acoes">
                        <button type="submit" class="btn-filtrar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="fornecedores.php" class="btn-limpar">Limpar</a>
                    </div>
                </form>
            </div>
            
            <!-- Informações sobre os resultados -->
            <div class="resultados-info">
                <?php echo count($fornecedores); ?> fornecedor(es) encontrado(s)
                <?php if (!empty($busca) || $status !== ''): ?>
                    com os filtros aplicados
                <?php endif; ?>
            </div>
            <div>
            <?php if(userCan('fornecedores', 'criar')): ?>
            <button class="btn-adicionar">
                <i class="fas fa-plus"></i> Novo Fornecedor
            </button>
            <?php endif; ?>
            </div>

            <div class="content">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($fornecedores) > 0): ?>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fornecedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($fornecedor['cnpj']); ?></td>
                                <td><?php echo htmlspecialchars($fornecedor['email']); ?></td>
                                <td><?php echo htmlspecialchars($fornecedor['telefone']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $fornecedor['status'] ? 'ativo' : 'inativo'; ?>">
                                        <?php echo $fornecedor['status'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="acoes">
                                    <?php if(userCan('fornecedores', 'editar')): ?>
                                    <button class="btn-acao btn-editar" onclick="editarFornecedor(<?php echo $fornecedor['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if(userCan('fornecedores', 'excluir')): ?>
                                    <button class="btn-acao btn-excluir" onclick="excluirFornecedor(<?php echo $fornecedor['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum fornecedor encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Fornecedor -->
    <div id="modalFornecedor" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Fornecedor</h3>
            <form id="formFornecedor">
                <input type="hidden" id="fornecedorId">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" id="nome" required>
                </div>
                <div class="form-group">
                    <label>CNPJ:</label>
                    <input type="text" id="cnpj" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Telefone:</label>
                    <input type="text" id="telefone" required>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select id="status">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
                <button type="submit" class="btn-salvar">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="assets/js/fornecedores.js"></script>
    <script>
        // Adicionar máscara para CNPJ quando o documento estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined' && $.fn.mask) {
                $('#cnpj').mask('00.000.000/0000-00');
                $('#telefone').mask('(00) 00000-0000');
            }
        });
    </script>
</body>
</html>
