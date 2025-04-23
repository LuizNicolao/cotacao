window.abrirModalProduto = function() {
    document.getElementById('modalProduto').style.display = 'block';
    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = '';
    document.querySelector('#modalProduto h3').textContent = 'Novo Produto';
}

document.addEventListener('DOMContentLoaded', function() {
    const btnAdicionar = document.querySelector('.btn-adicionar');
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', abrirModalProduto);
    }

    document.querySelector('.close').addEventListener('click', fecharModalProduto);
    document.getElementById('formProduto').onsubmit = salvarProduto;
});

function fecharModalProduto() {
    document.getElementById('modalProduto').style.display = 'none';
}

function editarProduto(id) {
    fetch(`api/produtos.php?id=${id}`)
        .then(response => response.json())
        .then(produto => {
            document.getElementById('produtoId').value = produto.id;
            document.getElementById('codigo').value = produto.codigo;
            document.getElementById('nome').value = produto.nome;
            document.getElementById('unidade').value = produto.unidade;
            
            document.querySelector('#modalProduto h3').textContent = 'Editar Produto';
            document.getElementById('modalProduto').style.display = 'block';
        });
}

function salvarProduto(e) {
    e.preventDefault();
    
    const dados = {
        id: document.getElementById('produtoId').value,
        codigo: document.getElementById('codigo').value,
        nome: document.getElementById('nome').value,
        unidade: document.getElementById('unidade').value
    };

    fetch('api/produtos.php', {
        method: dados.id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModalProduto();
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar produto');
        }
    });
}

function excluirProduto(id) {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
        fetch(`api/produtos.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir produto');
            }
        });
    }
}