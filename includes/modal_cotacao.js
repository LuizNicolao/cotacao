let excelProducts = []; // Store Excel products globally

document.getElementById('excelFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        
        // Get data from columns C, D, E, F
        excelProducts = XLSX.utils.sheet_to_json(firstSheet, {
            range: 'C:F',
            header: ['quantidade', 'codigo', 'nome', 'unidade']
        });

        // Show add supplier button
        document.querySelector('.btn-adicionar-fornecedor').style.display = 'block';
    };

    reader.readAsArrayBuffer(file);
});

// Handle add supplier button click
document.querySelector('.btn-adicionar-fornecedor').addEventListener('click', function () {
    if (excelProducts.length === 0) {
        alert('Você precisa fazer o upload da planilha antes de adicionar fornecedores.');
        return;
    }

    const template = document.getElementById('template-fornecedor');
    const clone = template.content.cloneNode(true);

    const produtosContainer = clone.querySelector('.produtos-fornecedor');

    excelProducts.forEach(produto => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${produto.Nome}</td>
            <td>${produto.Qtde}</td>
            <td><input type="number" class="valor-unitario" step="0.0001" min="0" required></td>
            <td class="total">0,00000</td>
            <td>
                <button type="button" class="btn-remover-produto">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        produtosContainer.appendChild(tr);

        const valorInput = tr.querySelector('.valor-unitario');
        valorInput.addEventListener('input', () => {
            const quantidade = parseFloat(produto.Qtde) || 0;
            const valor = parseFloat(valorInput.value) || 0;
            tr.querySelector('.total').textContent = (quantidade * valor).toFixed(2);
        });

        tr.querySelector('.btn-remover-produto').addEventListener('click', () => tr.remove());
    });

    document.getElementById('fornecedores-container').appendChild(clone);
});
