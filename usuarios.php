<?php
session_start();
require_once 'config/database.php';
require_once 'includes/check_permissions.php';

if (!isset($_SESSION['usuario']) || !userCan('usuarios', 'visualizar')) {
    header("Location: dashboard.php");
    exit;
}

$conn = conectarDB();
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-bar">
                <h2>Usuários</h2>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario']['nome']; ?></span>
                </div>
            </header>
            
            <?php if(userCan('usuarios', 'criar')): ?>
            <button class="btn-adicionar" onclick="abrirModalUsuario()">
                <i class="fas fa-plus"></i> Novo Usuário
            </button>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo ucfirst($usuario['tipo']); ?></td>
                        <td>
                            <span class="status-badge <?php echo isset($usuario['status']) ? ($usuario['status'] ? 'ativo' : 'inativo') : 'inativo'; ?>">
                                <?php echo isset($usuario['status']) ? ($usuario['status'] ? 'Ativo' : 'Inativo') : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="acoes">
                            <?php if(userCan('usuarios', 'editar')): ?>
                            <button class="btn-acao btn-editar" data-id="<?php echo $usuario['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if(userCan('usuarios', 'excluir')): ?>
                            <button class="btn-acao btn-excluir" data-id="<?php echo $usuario['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/modal_usuario.php'; ?>
    <script src="assets/js/usuarios.js"></script>
</body>
</html>
