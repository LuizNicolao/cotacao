<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

if (!isset($_SESSION['usuario']) || !userCan('produtos', 'visualizar')) {
    header("Location: dashboard.php");
    exit;
}

$conn = conectarDB();

// Inicializar variáveis de filtro
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$unidade = isset($_GET['unidade']) ? $_GET['unidade'] : '';

// Construir a consulta SQL com filtros
$query = "SELECT * FROM produtos WHERE 1=1";

if (!empty($busca)) {
    $query .= " AND (nome LIKE :busca OR codigo LIKE :busca)";
}

if (!empty($unidade)) {
    $query .= " AND unidade = :unidade";
}

$query .= " ORDER BY nome";

// Preparar e executar a consulta
$stmt = $conn->prepare($query);

if (!empty($busca)) {
    $termo_busca = "%{$busca}%";
    $stmt->bindParam(':busca', $termo_busca, PDO::PARAM_STR);
}

if (!empty($unidade)) {
    $stmt->bindParam(':unidade', $unidade, PDO::PARAM_STR);
}

$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar unidades distintas para o filtro
$stmt_unidades = $conn->query("SELECT DISTINCT unidade FROM produtos ORDER BY unidade");
$unidades = $stmt_unidades->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/produtos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <div class="welcome">
                    <h2>Produtos</h2>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario']['nome']; ?></span>
                </div>
            </header>
            
            <!-- Filtros de busca -->
            <div class="filtros-container">
                <form action="" method="GET" class="form-filtros">
                    <div class="filtro-grupo">
                        <label for="busca">Buscar por Nome ou Código:</label>
                        <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Digite para buscar...">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label for="unidade">Unidade de Medida:</label>
                        <select id="unidade" name="unidade">
                            <option value="">Todas</option>
                            <?php foreach ($unidades as $un): ?>
                                <option value="<?php echo htmlspecialchars($un); ?>" <?php echo $un === $unidade ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($un); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-acoes">
                        <button type="submit" class="btn-filtrar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="produtos.php" class="btn-limpar">Limpar</a>
                    </div>
                </form>
            </div>
            
            <!-- Informações sobre os resultados -->
            <div class="resultados-info">
                <?php echo count($produtos); ?> produto(s) encontrado(s)
                <?php if (!empty($busca) || !empty($unidade)): ?>
                    com os filtros aplicados
                <?php endif; ?>
            </div>
            <div>
            <?php if(userCan('produtos', 'criar')): ?>
            <button class="btn-adicionar" onclick="abrirModalProduto()">
                <i class="fas fa-plus"></i> Novo Produto
            </button>
            <?php endif; ?>
            </div>
            <div class="content">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Unidade de Medida</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produtos) > 0): ?>
                            <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                <td><?php echo htmlspecialchars($produto['unidade']); ?></td>
                                <td class="acoes">
                                    <?php if(userCan('produtos', 'editar')): ?>
                                    <button class="btn-acao btn-editar" onclick="editarProduto(<?php echo $produto['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if(userCan('produtos', 'excluir')): ?>
                                    <button class="btn-acao btn-excluir" onclick="excluirProduto(<?php echo $produto['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Nenhum produto encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/modal_produto.php'; ?>
    <script src="assets/js/produtos.js"></script>
</body>
</html>
