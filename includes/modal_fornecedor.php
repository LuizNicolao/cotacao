<div id="modalFornecedor" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <h3>Novo Fornecedor</h3>
        <form id="formFornecedor">
            <input type="hidden" id="fornecedorId">
            
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" id="nome" required>
            </div>

            <div class="form-group">
                <label>CNPJ:</label>
                <input type="text" id="cnpj" required>
            </div>
              <div class="form-group">
                  <label>Email:</label>
                  <input type="email" id="email" required>
              </div>

              <div class="form-group">
                  <label>CNPJ:</label>
                  <input type="text" id="cnpj" maxlength="18" required>
              </div>

              <div class="form-group">
                  <label>Telefone:</label>
                  <input type="text" id="telefone" maxlength="15" required>
              </div>
            <div class="form-group">
                <label>Status:</label>
                <select id="status" required>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>

            <button type="submit" class="btn-salvar">
                <i class="fas fa-save"></i> Salvar
            </button>
        </form>
    </div>
    
</div>
