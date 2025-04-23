<?php
function userCan($modulo, $acao) {
    if (!isset($_SESSION['usuario'])) {
        return false;
    }

    $tipo_usuario = $_SESSION['usuario']['tipo'];

    // Admin tem acesso a tudo
    if ($tipo_usuario === 'admin' || $tipo_usuario === 'administrador') {
        return true;
    }

    // Definir permissões para cada tipo de usuário
    $permissoes = [
        'gerencia' => [
            'dashboard' => ['visualizar'],
            'aprovacoes' => ['visualizar', 'aprovar', 'rejeitar'],
            'sawing' => ['visualizar'],
        ],
        'comprador' => [
            'cotacoes' => ['visualizar', 'criar', 'editar', 'excluir'],
            'fornecedores' => ['visualizar', 'criar', 'editar', 'excluir'],
            'produtos' => ['visualizar', 'criar', 'editar', 'excluir'],
        ]
    ];

    // Verificar se o tipo de usuário existe nas permissões
    if (!isset($permissoes[$tipo_usuario])) {
        return false;
    }

    // Verificar se o módulo existe para este tipo de usuário
    if (!isset($permissoes[$tipo_usuario][$modulo])) {
        return false;
    }

    // Verificar se a ação está permitida para este módulo
    return in_array($acao, $permissoes[$tipo_usuario][$modulo]);
}
