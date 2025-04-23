<div id="modalProduto" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <h3>Novo Produto</h3>
        <form id="formProduto">
            <input type="hidden" id="produtoId">
            
            <div class="form-group">
                <label>Código:</label>
                <input type="text" id="codigo" required>
            </div>

            <div class="form-group">
                <label>Nome:</label>
                <input type="text" id="nome" required>
            </div>

            <div class="form-group">
                <label>Unidade de Medida:</label>
                <select id="unidade" required>
                    <option value="">Selecione</option>
                    <option value="UN">UN</option>
                    <option value="KG">KG</option>
                    <option value="LT">LT</option>
                    <option value="CX">CX</option>
                    <option value="PCT">PCT</option>
                </select>
            </div>

            <button type="submit" class="btn-salvar">
                <i class="fas fa-save"></i> Salvar
            </button>
        </form>
    </div>
</div>
