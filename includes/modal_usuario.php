<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Novo Usuário</h3>
        <form id="formUsuario">
            <input type="hidden" id="usuarioId">
            
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" id="nome" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" required>
            </div>

            <div class="form-group">
                <label>Senha:</label>
                <input type="password" id="senha">
                <small>Deixe em branco para manter a senha atual (ao editar)</small>
            </div>

            <div class="form-group">
                <label>Tipo:</label>
                <select id="tipo" required>
                    <option value="">Selecione um tipo</option>
                    <option value="admin">Administrador</option>
                    <option value="comprador">Comprador</option>
                    <option value="gerencia">gerencia</option>
                </select>
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
