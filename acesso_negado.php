<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado - Sistema de Cotações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .acesso-negado {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .acesso-negado i {
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .acesso-negado h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .acesso-negado p {
            margin-bottom: 30px;
            color: #666;
            font-size: 18px;
        }
        
        .btn-voltar {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .btn-voltar:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="acesso-negado">
        <i class="fas fa-exclamation-circle"></i>
        <h1>Acesso Negado</h1>
        <p>Você não tem permissão para acessar esta página.</p>
        <?php if (isset($_SESSION['usuario'])): ?>
            <a href="cotacoes.php" class="btn-voltar">Ir para Cotações</a>
        <?php else: ?>
            <a href="login.php" class="btn-voltar">Voltar para Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
