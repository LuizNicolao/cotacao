<div class="sidebar">
    <div class="logo">
        <h2>Sistema de Cotações</h2>
    </div>
    <nav>
        <ul>
            <?php
            // Verificar o tipo de usuário
            $usuario_tipo = strtolower($_SESSION['usuario']['tipo'] ?? '');
            ?>
            
            <?php if (userCan('dashboard', 'visualizar')): ?>
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (userCan('cotacoes', 'visualizar')): ?>
            <li>
                <a href="cotacoes.php">
                    <i class="fas fa-file-invoice-dollar"></i> Cotações
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (userCan('aprovacoes', 'visualizar')): ?>
            <li>
                <a href="aprovacoes.php">
                    <i class="fas fa-check-circle"></i> Aprovações
                </a>
            </li>
            <?php endif; ?>

            <?php if ($_SESSION['usuario']['tipo'] === 'admin' || $_SESSION['usuario']['tipo'] === 'gerencia'): ?>
            <li class="nav-item">
                <a href="sawing.php" class="nav-link <?php echo $pagina_atual == 'sawing' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Sawing</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (userCan('usuarios', 'visualizar')): ?>
            <li>
                <a href="usuarios.php">
                    <i class="fas fa-users"></i> Usuários
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </li>
        </ul>
    </nav>
</div>
