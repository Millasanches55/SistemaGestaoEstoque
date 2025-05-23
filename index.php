<html>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Página Principal</title>
    <link rel='stylesheet' href='style.css'/>
    </head>
<section>
<body>
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

<h2>TCC's Cadastrados</h2>

<a class='botao' href="cadastroTcc.php">Cadastrar Novo TCC</a>
<br><br>

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
                <td>
                    <a href="editarTcc.php?codTcc=<?= $tcc['codTcc'] ?>"><button>Editar</button></a>
                    <a href="?delete=<?= $tcc['codTcc'] ?>" onclick="return confirm('Tem certeza que deseja excluir este TCC?');">
                        <button>Deletar</button>
                    </a>



                    <a href="acessarAgenda.php?codAgenda=<?= $tcc['codAgenda'] ?>"><button>Acessar Agenda</button></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

require 'Tcc.php';


$tcc = new Tcc($pdo);
$tccs = $tcc->listarTodos();

echo "<h2>Lista de TCCs Cadastrados e Seus Cursos</h2>";
foreach ($tccs as $item) {
    echo "<p><strong>" . htmlspecialchars($item['titulo']) . "</strong> - " . 
         htmlspecialchars($item['aluno1']) . 
         " (<em>" . htmlspecialchars($item['curso']) . "</em>)</p>";
}
?>



    <!-- Botão para ir à página de professores -->
 <a class='botao' href='professoresCadastrados.php'>Ver Professores Cadastrados</a>
<a class='botao' href="alunosCadastrados.php">Ver Alunos Cadastrados</a>

</section>

</body>
</html>
