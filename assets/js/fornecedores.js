document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    const modal = document.getElementById('modalFornecedor');
    const btnAdicionar = document.querySelector('.btn-adicionar');
    const closeBtn = document.querySelector('.close');

    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', function() {
            modal.style.display = 'block';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    document.getElementById('formFornecedor').onsubmit = salvarFornecedor;
});

function abrirModalFornecedor() {
    document.getElementById('modalFornecedor').style.display = 'block';
    document.getElementById('formFornecedor').reset();
    document.getElementById('fornecedorId').value = '';
    document.querySelector('#modalFornecedor h3').textContent = 'Novo Fornecedor';
}

function fecharModalFornecedor() {
    document.getElementById('modalFornecedor').style.display = 'none';
}

function editarFornecedor(id) {
    fetch(`api/fornecedores.php?id=${id}`)
        .then(response => response.json())
        .then(fornecedor => {
            document.getElementById('fornecedorId').value = fornecedor.id;
            document.getElementById('nome').value = fornecedor.nome;
            document.getElementById('cnpj').value = fornecedor.cnpj;
            document.getElementById('email').value = fornecedor.email;
            document.getElementById('telefone').value = fornecedor.telefone;
            document.getElementById('status').value = fornecedor.status;
            
            document.querySelector('#modalFornecedor h3').textContent = 'Editar Fornecedor';
            document.getElementById('modalFornecedor').style.display = 'block';
        });
}

function salvarFornecedor(e) {
    e.preventDefault();
    
    const dados = {
        id: document.getElementById('fornecedorId').value,
        nome: document.getElementById('nome').value,
        cnpj: document.getElementById('cnpj').value,
        email: document.getElementById('email').value,
        telefone: document.getElementById('telefone').value,
        status: document.getElementById('status').value
    };

    fetch('api/fornecedores.php', {
        method: dados.id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModalFornecedor();
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar fornecedor');
        }
    });
}

function excluirFornecedor(id) {
    if (confirm('Tem certeza que deseja excluir este fornecedor?')) {
        fetch(`api/fornecedores.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir fornecedor');
            }
        });
    }
}
  document.addEventListener('DOMContentLoaded', function() {
      // CNPJ Mask
      const cnpjInput = document.getElementById('cnpj');
      cnpjInput.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 14) {
              value = value.replace(/^(\d{2})(\d)/, '$1.$2');
              value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
              value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
              value = value.replace(/(\d{4})(\d)/, '$1-$2');
          }
          e.target.value = value;
      });

      // Phone Mask
      const telefoneInput = document.getElementById('telefone');
      telefoneInput.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          if (value.length <= 11) {
              value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
              value = value.replace(/(\d)(\d{4})$/, '$1-$2');
          }
          e.target.value = value;
      });
  });