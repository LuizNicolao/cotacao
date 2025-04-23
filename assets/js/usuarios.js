window.abrirModalUsuario = function() {
    document.getElementById('modalUsuario').style.display = 'block';
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.querySelector('#modalUsuario h3').textContent = 'Novo Usuário';
}

document.addEventListener('DOMContentLoaded', function() {
    // Configurar botão de adicionar
    const btnAdicionar = document.querySelector('.btn-adicionar');
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', abrirModalUsuario);
    }
    
    // Configurar botão de fechar modal
    const closeBtn = document.querySelector('#modalUsuario .close');
    if (closeBtn) {
        closeBtn.addEventListener('click', fecharModalUsuario);
    }
    
    // Configurar formulário
    const formUsuario = document.getElementById('formUsuario');
    if (formUsuario) {
        formUsuario.addEventListener('submit', salvarUsuario);
    }
    
    // Configurar botões de editar
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            editarUsuario(id);
        });
    });
    
    // Configurar botões de excluir
    document.querySelectorAll('.btn-excluir').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            excluirUsuario(id);
        });
    });
});

function fecharModalUsuario() {
    document.getElementById('modalUsuario').style.display = 'none';
}

function editarUsuario(id) {
    fetch(`api/usuarios.php?id=${id}`)
        .then(response => response.json())
        .then(usuario => {
            document.getElementById('usuarioId').value = usuario.id;
            document.getElementById('nome').value = usuario.nome;
            document.getElementById('email').value = usuario.email;
            document.getElementById('tipo').value = usuario.tipo;
            document.getElementById('status').value = usuario.status;
            
            document.querySelector('#modalUsuario h3').textContent = 'Editar Usuário';
            document.getElementById('modalUsuario').style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao carregar dados do usuário:', error);
            alert('Erro ao carregar dados do usuário');
        });
}

function salvarUsuario(e) {
    e.preventDefault();
    
    const dados = {
        id: document.getElementById('usuarioId').value,
        nome: document.getElementById('nome').value,
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value,
        tipo: document.getElementById('tipo').value,
        status: document.getElementById('status').value
    };

    fetch('api/usuarios.php', {
        method: dados.id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => {
        // Verificar se a resposta é JSON válido
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, obter o texto e lançar um erro
            return response.text().then(text => {
                throw new Error('Resposta não-JSON do servidor: ' + text.substring(0, 100) + '...');
            });
        }
    })
    .then(data => {
        if (data.success) {
            fecharModalUsuario();
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar usuário');
        }
    })
    .catch(error => {
        console.error('Erro ao salvar usuário:', error);
        alert('Erro ao processar requisição: ' + error.message);
    });
}
function excluirUsuario(id) {
    if (confirm('Tem certeza que deseja excluir este usuário?')) {
        fetch(`api/usuarios.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir usuário');
            }
        })
        .catch(error => {
            console.error('Erro ao excluir usuário:', error);
            alert('Erro ao processar requisição');
        });
    }
}

// Tornar funções disponíveis globalmente
window.editarUsuario = editarUsuario;
window.excluirUsuario = excluirUsuario;
window.fecharModalUsuario = fecharModalUsuario;
window.salvarUsuario = salvarUsuario;