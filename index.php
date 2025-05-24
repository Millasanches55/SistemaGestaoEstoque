<html>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Página Principal</title>
    <link rel='stylesheet' href='style.css'/>
    </head>
<body>
<section>
<header>
     <marquee behavior="scroll" direction="left">
    <img src="https://64.media.tumblr.com/e87d127229a9ea2deaf40dee8c961512/0256c413a444b1fd-a4/s2048x3072/aee11462d23c18b3256cc04448a90bee4501c3b2.gifv" alt="gif" style="height: 30px; vertical-align: middle;">
    Cadastro de TCCs
    <img src="https://64.media.tumblr.com/a7669e560cf0e6f52d29e4783a6cf0f2/b2576166f7e98a15-71/s250x400/d0c42473659321762bc1bd97ed2bfaacb874796f.gifv" alt="gif" style="height: 30px; vertical-align: middle;">
</marquee>

</header>

<?php
echo "";

$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

try {
    // Consulta para contar os TCCs
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM Tcc");
    $totalTcc = 0;

    // Laço while para percorrer os resultados (só 1 linha, mas usamos while para cumprir o critério)
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalTcc = $row['total'];
    }

    echo "<p><strong>Total de TCCs cadastrados:</strong> $totalTcc</p>";
} catch (PDOException $e) {
    echo "Erro ao contar TCCs: " . $e->getMessage();
}
?>
<?php
// Conexão PDO
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

// Busca todos os TCCs com JOIN para pegar nomeTipoTcc
$query = "
    SELECT 
        t.codTcc,
        t.codTipoTcc,
        t.titulo,
        tipo.nomeTipoTcc,
        t.qtdPg,
        t.qtdAlunos,
        t.curso,
        t.aprovado,
        t.codTcc AS codAgenda
    FROM Tcc t
    JOIN TipoTcc tipo ON t.codTipoTcc = tipo.codTipoTcc
";
$tccs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Deletar TCC
if (isset($_GET['delete'])) {
    $codTcc = (int)$_GET['delete'];

    // Apaga das tabelas relacionadas
    $pdo->prepare("DELETE FROM Aluno WHERE codTcc = ?")->execute([$codTcc]);
    $pdo->prepare("DELETE FROM Professor WHERE codTcc = ?")->execute([$codTcc]);
    $pdo->prepare("DELETE FROM Agenda WHERE codAgenda = ?")->execute([$codTcc]);
    $pdo->prepare("DELETE FROM Tcc WHERE codTcc = ?")->execute([$codTcc]);

    header("Location: index.php");
    exit;
}
?>

<!-- Botão para ir à página de professores e alunos -->
<div class="botoes-container">
    <a class='botao' href="cadastroTcc.php">Cadastrar Novo TCC</a>
    <a class='botao' href='professoresCadastrados.php'>Ver Professores Cadastrados</a>
    <a class='botao' href="alunosCadastrados.php">Ver Alunos Cadastrados</a>
</div>
<br>


<h2>TCC's Cadastrados</h2>
</section>
<table border="1">
    <thead>
        <tr>
            <th>codTcc</th>
            <th>codTipoTcc</th>
            <th>título</th>
            <th>nomeTipoTcc</th>
            <th>qtdPg</th>
            <th>qtdAlunos</th>
            <th>curso</th>
            <th>aprovado</th>
            <th>codAgenda</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tccs as $tcc): ?>
            <tr>
                <td><?= $tcc['codTcc'] ?></td>
                <td><?= $tcc['codTipoTcc'] ?></td>
                <td><?= htmlspecialchars($tcc['titulo']) ?></td>
                <td><?= $tcc['nomeTipoTcc'] ?></td>
                <td><?= $tcc['qtdPg'] ?></td>
                <td><?= $tcc['qtdAlunos'] ?></td>
                <td><?= htmlspecialchars($tcc['curso']) ?></td>
                <td><?= $tcc['aprovado'] ?></td>
                <td><?= $tcc['codAgenda'] ?></td>
                <td class="opcoes-tabela">
                    <a href="editarTcc.php?codTcc=<?= $tcc['codTcc'] ?>"><button class='botao'>Editar</button></a>
                    <a href="?delete=<?= $tcc['codTcc'] ?>" onclick="return confirm('Tem certeza que deseja excluir este TCC?');">
                        <button class='botao'>Deletar</button>
                    </a>



                    <a href="acessarAgenda.php?codAgenda=<?= $tcc['codAgenda'] ?>"><button class='botao'>Acessar Agenda</button></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</section>

</body>
</html>
