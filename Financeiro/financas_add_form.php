<!-- Formulário HTML para registrar a movimentação -->
<h2>Registrar Movimentação</h2>
<form method="POST" action="financas_add.php">
    <label>Tipo:</label>
    <select name="tipo">
        <option value="arrecadacao">Arrecadação</option>
        <option value="despesa">Despesa</option>
    </select><br><br>

    <label>Descrição:</label>
    <input type="text" name="descricao" required><br><br>

    <label>Valor:</label>
    <input type="number" step="0.01" name="valor" required><br><br>

    <label>Data:</label>
    <input type="date" name="data" required><br><br>

    <button type="submit">Salvar</button>
</form>
