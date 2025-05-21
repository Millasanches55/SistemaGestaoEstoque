<?php
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");
$tipos = $pdo->query("SELECT * FROM TipoTcc")->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'];
    $codTipoTcc = (int)$_POST['codTipoTcc'];
    $qtdPg = (int)$_POST['qtdPg'];
    $curso = $_POST['curso'];

    $aluno1 = trim($_POST['aluno1']);
    $aluno2 = trim($_POST['aluno2']);
    $aluno3 = trim($_POST['aluno3']);

    $orientador = $_POST['orientador'];
    $coorientador = $_POST['coorientador'];
    $profConvidado1 = $_POST['profConvidado1'];
    $profConvidado2 = $_POST['profConvidado2'];

    $dataHora = $_POST['dataHora'];
    $local = $_POST['local'];
    $notaFinal = (float)$_POST['notaFinal'];
    $cidade = $_POST['cidade'];

    $qtdAlunos = 0;
    if (!empty($aluno1)) $qtdAlunos++;
    if (!empty($aluno2)) $qtdAlunos++;
    if (!empty($aluno3)) $qtdAlunos++;

    $tipo = $pdo->prepare("SELECT maxPaginas FROM TipoTcc WHERE codTipoTcc = ?");
    $tipo->execute([$codTipoTcc]);
    $maxPg = $tipo->fetchColumn();

    if ($qtdPg > $maxPg) {
        echo "<p style='color:red;'>Erro: Número de páginas excede o limite do tipo de TCC selecionado.</p>";
    } else {
        $aprovado = ($notaFinal >= 6.0) ? "Sim" : "Não";

        $stmt = $pdo->prepare("INSERT INTO Tcc (titulo, codTipoTcc, qtdPg, qtdAlunos, curso, aprovado)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $codTipoTcc, $qtdPg, $qtdAlunos, $curso, $aprovado]);
        $codTcc = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO Aluno (codTcc, aluno1, aluno2, aluno3) VALUES (?, ?, ?, ?)")
            ->execute([$codTcc, $aluno1, $aluno2, $aluno3]);

        $pdo->prepare("INSERT INTO Professor (codTcc, orientador, coorientador, profConvidado1, profConvidado2)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([$codTcc, $orientador, $coorientador, $profConvidado1, $profConvidado2]);

        $pdo->prepare("INSERT INTO Agenda (codAgenda, dataHora, local, notaFinal, cidade)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([$codTcc, $dataHora, $local, $notaFinal, $cidade]);

        echo "<p style='color:green;'>TCC cadastrado com sucesso!</p>";
    }
}
?>





<h2>Cadastro de TCC</h2>
<form method="POST" style="max-width: 600px;">
    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0;">Informações Gerais</h3>
        <label>Título: <input type="text" name="titulo" required></label><br><br>

        <label>Tipo de TCC:</label>
        <select name="codTipoTcc" required>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= $tipo['codTipoTcc'] ?>"><?= htmlspecialchars($tipo['nomeTipoTcc']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Qtd. Páginas: <input type="number" name="qtdPg" required></label><br><br>
        <label>Curso: <input type="text" name="curso" required></label><br>
    </div>

    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0;">Alunos</h3>
        <label>Aluno 1: <input type="text" name="aluno1"></label><br>
        <label>Aluno 2: <input type="text" name="aluno2"></label><br>
        <label>Aluno 3: <input type="text" name="aluno3"></label><br>
    </div>

    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0;">Professores</h3>
        <label>Orientador: <input type="text" name="orientador"></label><br>
        <label>Coorientador: <input type="text" name="coorientador"></label><br>
        <label>Prof. Convidado 1: <input type="text" name="profConvidado1"></label><br>
        <label>Prof. Convidado 2: <input type="text" name="profConvidado2"></label><br>
    </div>

    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0;">Agenda</h3>
        <label>Data e Hora: <input type="datetime-local" name="dataHora" required></label><br>
        <label>Local: <input type="text" name="local" required></label><br>
        <label>Nota Final: <input type="number" step="0.01" name="notaFinal" required></label><br>
        <label>Cidade: <input type="text" name="cidade" required></label><br>
    </div>

    <button type="submit">Cadastrar TCC</button>
</form>
<?php
echo "<h3>Exemplo com FOR:</h3>";
for ($i = 0; $i < count($tipos); $i++) {
    $numeroTipo = $i + 1;
    echo "Tipo {$numeroTipo}: " . htmlspecialchars($tipos[$i]['nomeTipoTcc']) . "<br>";
}
?>