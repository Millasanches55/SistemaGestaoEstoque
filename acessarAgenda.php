<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Agenda</title>
    <link rel='stylesheet' href='style.css' />
</head>

<body>
    <section>

        <?php
        echo "<link rel='stylesheet' href='style.css'/>";
        // Conexão com PDO (Critério 9.1 Conexão PDO)
        $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $agenda = null;
        $erro = null;

        // Uso de operador ternário para atribuição (Critério 6.3 Operador Ternário, 3.3 Atribuição)
        $codAgenda = $_GET['codAgenda'] ?? $_POST['codAgenda'] ?? null;

        // Se houve submissão do formulário (salvar)
// Uso de operadores de comparação e lógicos (Critérios 3.4, 3.6, 6.1 If / Else)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar']) && $codAgenda) {
            // Dados dos alunos
            // String (Critério 3.2) e atribuições
            $aluno1 = trim($_POST['aluno1']);
            $aluno2 = trim($_POST['aluno2']);
            $aluno3 = trim($_POST['aluno3']);

            $qtdAlunos = 0;
            // Comparação + incremento (Critérios 3.4 e 3.5 Incremento)
            if ($aluno1 !== '')
                $qtdAlunos++;
            if ($aluno2 !== '')
                $qtdAlunos++;
            if ($aluno3 !== '')
                $qtdAlunos++;

            // Atribuições e strings
            $dataHora = $_POST['dataHora'] ?? '';
            $local = trim($_POST['local'] ?? '');
            $profConvidado1 = trim($_POST['profConvidado1'] ?? '');
            $profConvidado2 = trim($_POST['profConvidado2'] ?? '');
            $coorientador = trim($_POST['coorientador'] ?? '');
            $orientador = trim($_POST['orientador'] ?? '');
            $notaFinal = floatval($_POST['notaFinal'] ?? 0);
            $cidade = trim($_POST['cidade'] ?? '');

            // Switch e comparação (Critérios 6.2 Switch, 3.4 Comparação)
            switch (true) {
                case ($notaFinal >= 6.0):
                    $aprovado = "Sim";
                    break;
                default:
                    $aprovado = "Não";
            }


            try {
                $pdo->beginTransaction();

                // Atualização de dados no banco (Critério 9.3 Atualização)
                // Atualizar Agenda
                $stmt = $pdo->prepare("UPDATE Agenda SET dataHora = ?, local = ?, notaFinal = ?, cidade = ? WHERE codAgenda = ?");
                $stmt->execute([$dataHora, $local, $notaFinal, $cidade, $codAgenda]);

                // Atualizar Professor
                $stmt = $pdo->prepare("UPDATE Professor SET orientador = ?, coorientador = ?, profConvidado1 = ?, profConvidado2 = ? WHERE codTcc = ?");
                $stmt->execute([$orientador, $coorientador, $profConvidado1, $profConvidado2, $codAgenda]);

                // Atualizar Aluno
                $stmt = $pdo->prepare("UPDATE Aluno SET aluno1 = ?, aluno2 = ?, aluno3 = ? WHERE codTcc = ?");
                $stmt->execute([$aluno1, $aluno2, $aluno3, $codAgenda]);

                // Atualizar Tcc
                $stmt = $pdo->prepare("UPDATE Tcc SET aprovado = ?, qtdAlunos = ? WHERE codTcc = ?");
                $stmt->execute([$aprovado, $qtdAlunos, $codAgenda]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = "Erro ao atualizar agenda: " . $e->getMessage();
            }
        }

        // Leitura e apresentação de registros (Critério 9.2)
// Buscar dados para exibir
        if ($codAgenda) {
            $stmt = $pdo->prepare("
        SELECT 
            t.codTcc, t.codTipoTcc, t.titulo, t.qtdPg, t.qtdAlunos, t.curso, t.aprovado,
            tipo.nomeTipoTcc,
            a.dataHora, a.local, a.notaFinal, a.cidade,
            p.orientador, p.coorientador, p.profConvidado1, p.profConvidado2,
            al.aluno1, al.aluno2, al.aluno3
        FROM Tcc t
        INNER JOIN TipoTcc tipo ON t.codTipoTcc = tipo.codTipoTcc
        LEFT JOIN Agenda a ON a.codAgenda = t.codTcc
        LEFT JOIN Professor p ON p.codTcc = t.codTcc
        LEFT JOIN Aluno al ON al.codTcc = t.codTcc
        WHERE t.codTcc = ?
        LIMIT 1
    ");
            $stmt->execute([$codAgenda]);
            $agenda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$agenda) {
                $erro = "Nenhuma agenda encontrada para o código informado.";
            }
        }
        ?>
        <br>
        <a href="index.php" class='botao'>← Voltar para lista de TCCs</a>
        <h2>Acessar Agenda do TCC</h2>

        <?php if ($erro): ?>
            <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <?php if ($agenda): ?>
            <hr>
            <h3>Dados da Agenda e TCC (codAgenda = <?= htmlspecialchars($agenda['codTcc']) ?>)</h3>

            <form method="post">
                <input type="hidden" name="codAgenda" value="<?= htmlspecialchars($agenda['codTcc']) ?>">

                <p><b>codTipoTcc:</b> <?= htmlspecialchars($agenda['codTipoTcc']) ?> -
                    <?= htmlspecialchars($agenda['nomeTipoTcc']) ?></p>

                <label for="dataHora">Data e Hora:</label><br>
                <input type="datetime-local" id="dataHora" name="dataHora"
                    value="<?= date('Y-m-d\TH:i', strtotime($agenda['dataHora'])) ?>" required><br><br>

                <label for="local">Local:</label><br>
                <input type="text" id="local" name="local" value="<?= htmlspecialchars($agenda['local']) ?>"
                    required><br><br>

                <label for="profConvidado1">Professor Convidado 1:</label><br>
                <input type="text" id="profConvidado1" name="profConvidado1"
                    value="<?= htmlspecialchars($agenda['profConvidado1']) ?>"><br><br>

                <label for="profConvidado2">Professor Convidado 2:</label><br>
                <input type="text" id="profConvidado2" name="profConvidado2"
                    value="<?= htmlspecialchars($agenda['profConvidado2']) ?>"><br><br>

                <label for="coorientador">Coorientador:</label><br>
                <input type="text" id="coorientador" name="coorientador"
                    value="<?= htmlspecialchars($agenda['coorientador']) ?>"><br><br>

                <label for="orientador">Orientador:</label><br>
                <input type="text" id="orientador" name="orientador" value="<?= htmlspecialchars($agenda['orientador']) ?>"
                    required><br><br>

                <label for="aluno1">Aluno 1:</label><br>
                <input type="text" id="aluno1" name="aluno1" value="<?= htmlspecialchars($agenda['aluno1']) ?>"
                    required><br><br>

                <label for="aluno2">Aluno 2:</label><br>
                <input type="text" id="aluno2" name="aluno2" value="<?= htmlspecialchars($agenda['aluno2']) ?>"><br><br>

                <label for="aluno3">Aluno 3:</label><br>
                <input type="text" id="aluno3" name="aluno3" value="<?= htmlspecialchars($agenda['aluno3']) ?>"><br><br>

                <label for="notaFinal">Nota Final:</label><br>
                <input type="number" id="notaFinal" name="notaFinal" step="0.01" min="0" max="10"
                    value="<?= htmlspecialchars($agenda['notaFinal']) ?>" required><br><br>

                <p><b>Aprovado:</b> <?= htmlspecialchars($agenda['aprovado']) ?></p>

                <label for="cidade">Cidade:</label><br>
                <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($agenda['cidade']) ?>"
                    required><br><br>

                <p><b>Curso:</b> <?= htmlspecialchars($agenda['curso']) ?></p>

                <button type="submit" name="salvar" class="botao">Salvar Alterações</button>
            </form>
        <?php endif; ?>

    </section>
</body>

</html>