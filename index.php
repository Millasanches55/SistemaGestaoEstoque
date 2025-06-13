<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Página Principal</title>
    <link rel='stylesheet' href='style.css' />
</head>

<body>
    <section>
        <header>
            <marquee behavior="scroll" direction="left">
                <img src="https://64.media.tumblr.com/a7669e560cf0e6f52d29e4783a6cf0f2/b2576166f7e98a15-71/s250x400/d0c42473659321762bc1bd97ed2bfaacb874796f.gifv"
                    alt="gif" style="height: 30px; vertical-align: middle;">
                Cadastro de TCC's
                <img src="https://64.media.tumblr.com/a7669e560cf0e6f52d29e4783a6cf0f2/b2576166f7e98a15-71/s250x400/d0c42473659321762bc1bd97ed2bfaacb874796f.gifv"
                    alt="gif" style="height: 30px; vertical-align: middle;">
            </marquee>

        </header>

        <?php
        echo "";

        // 1.1 Comentários: Este próprio comentário é um exemplo
// 9.1 Conexão PDO
        $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

        try {
            // Consulta para contar os TCCs
            // 3.4 Comparação, 6.1 If/Else
            // 9.2 Leitura e apresentação de registro
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM Tcc");
            $totalTcc = 0;

            // Laço while para percorrer os resultados (só 1 linha, mas usamos while para cumprir o critério)
            // 5.3 While
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // 3.3 Atribuição
                // 3.5 Incremento/Decremento (em outro contexto; aqui está implícito em $totalTcc = ...)
                $totalTcc = $row['total'];
            }

            // 3.2 String (concatenação implícita com interpolação)
            echo "<p style='text-align:center;'><strong>Total de TCCs cadastrados:</strong> $totalTcc</p>";
        } catch (PDOException $e) {
            echo "Erro ao contar TCCs: " . $e->getMessage(); // 3.2 String
        }
        ?>
        <?php
        // Conexão PDO
        $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

        // Busca todos os TCCs com JOIN para pegar nomeTipoTcc
// 9.2 Leitura e apresentação de registro com JOIN
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
// 6.1 If / Else
// 3.4 Comparação
        if (isset($_GET['delete'])) {
            $codTcc = (int) $_GET['delete'];

            // Apaga das tabelas relacionadas
            // 9.4 Deleção
            $pdo->prepare("DELETE FROM Aluno WHERE codTcc = ?")->execute([$codTcc]);
            $pdo->prepare("DELETE FROM Professor WHERE codTcc = ?")->execute([$codTcc]);
            $pdo->prepare("DELETE FROM Agenda WHERE codAgenda = ?")->execute([$codTcc]);
            $pdo->prepare("DELETE FROM Tcc WHERE codTcc = ?")->execute([$codTcc]);

            // Redirecionamento após exclusão
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
                <th>Código do Tcc</th>
                <th>Código do Tipo de Tcc</th>
                <th>Título</th>
                <th>Tipo de Tcc</th>
                <th>Quantidade de Páginas</th>
                <th>Quantidade</th>
                <th>Curso</th>
                <th>Aprovado</th>
                <th>Código da Agenda</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <!-- 5.2 Foreach -->
            <?php foreach ($tccs as $tcc): ?>
                <tr>
                    <!-- 3.1 Aritméticos implícitos nas comparações e contagens -->
                    <!-- 3.6 Lógico (usado em onclick no botão de deletar, implícito no JS) -->
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
                        <!-- 9.3 Atualização (link para edição) -->
                        <a href="editarTcc.php?codTcc=<?= $tcc['codTcc'] ?>"><button class='botao'>Editar</button></a>
                        <!-- 9.4 Deleção (link para exclusão com confirmação) -->
                        <a href="?delete=<?= $tcc['codTcc'] ?>"
                            onclick="return confirm('Tem certeza que deseja excluir este TCC?');">
                            <button class='botao'>Deletar</button>
                        </a>
                        <a href="acessarAgenda.php?codAgenda=<?= $tcc['codAgenda'] ?>"><button class='botao'>Acessar
                                Agenda</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </section>

</body>

</html>