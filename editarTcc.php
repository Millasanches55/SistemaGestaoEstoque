<html>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Editar TCC</title>
    <link rel='stylesheet' href='style.css' />
</head>

<body>
    <section>

        <?php
        // 9.1 Conexão PDO
        $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

        // 6.1 If / Else
        if (!isset($_GET['codTcc'])) {
            // 6.1 If / Else + redirecionamento
            header('Location: index.php');
            exit;
        }

        // 3.3 Atribuição + 3.4 Comparação + 2.1 Camel Case
        $codTcc = (int) $_GET['codTcc'];

        // Buscar dados atuais do TCC e do tipoTcc
// 9.2 Leitura e apresentação de registro + 3.4 Comparação
        $stmt = $pdo->prepare("
    SELECT t.codTcc, t.titulo, t.codTipoTcc, t.qtdPg, t.qtdAlunos, t.curso, tipo.nomeTipoTcc 
    FROM Tcc t
    JOIN TipoTcc tipo ON t.codTipoTcc = tipo.codTipoTcc
    WHERE t.codTcc = ?
");
        $stmt->execute([$codTcc]);  // 9.2 Leitura (execução da query)
        $tcc = $stmt->fetch(PDO::FETCH_ASSOC);  // 4.1 Uso de Arrays
        

        // 6.1 If / Else
        if (!$tcc) {
            echo "TCC não encontrado.";
            exit;
        }

        // Lista tipos para dropdown
// 4.1 Arrays (associativo com dados de tipos de TCC)
        $tipos = [
            1 => ['nome' => 'Monografia', 'maxPg' => 60],
            2 => ['nome' => 'Artigo técnico-científico', 'maxPg' => 30],
            3 => ['nome' => 'Relatório técnico-científico', 'maxPg' => 20],
            4 => ['nome' => 'Plano de negócios', 'maxPg' => 40],
        ];

        // Atualizar dados
// 6.1 If / Else + 3.4 Comparação
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 3.3 Atribuição + 3.2 String
            $titulo = trim($_POST['titulo']);
            $codTipoTcc = (int) $_POST['codTipoTcc'];
            $qtdPg = (int) $_POST['qtdPg'];
            $curso = trim($_POST['curso']);

            // Validação da qtdPg conforme tipo
            // 6.1 If / Else + 3.4 Comparação + 3.6 Lógico + 4.1 Arrays
            if ($qtdPg < 1 || $qtdPg > $tipos[$codTipoTcc]['maxPg']) {
                // 3.2 String (concatenação)
                $erro = "Quantidade de páginas deve ser entre 1 e " . $tipos[$codTipoTcc]['maxPg'];
            } elseif (empty($titulo) || empty($curso)) {
                $erro = "Título e curso não podem estar vazios.";
            } else {
                // 9.3 Atualização + 8.1 Função com parâmetros (prepare e execute) + 3.3 Atribuição
                $nomeTipoTcc = $tipos[$codTipoTcc]['nome'];

                $stmt = $pdo->prepare("
            UPDATE Tcc SET titulo=?, codTipoTcc=?, qtdPg=?, curso=?
            WHERE codTcc=?
        ");
                $stmt->execute([$titulo, $codTipoTcc, $qtdPg, $curso, $codTcc]);

                // Atualizar nomeTipoTcc na tabela TipoTcc não faz sentido porque é fixa, então mantemos só a tabela TipoTcc original
        
                header("Location: index.php"); // Redirecionamento após salvar
                exit;
            }
        }
        ?>
        <br>
        <a href="index.php" class='botao'>← Voltar para lista de TCCs</a>
        <!-- 3.2 String (HTML com interpolação) -->
        <h2>Editar TCC (codTcc = <?= $codTcc ?>)</h2>

        <!-- 6.1 If / Else com bloco PHP -->
        <?php if (isset($erro)): ?>
            <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form method="post">
            <!-- 4.1 Arrays + 5.2 Foreach -->
            <label>Título:</label><br>
            <input type="text" name="titulo" value="<?= htmlspecialchars($tcc['titulo']) ?>" required><br><br>
            <label>Tipo de TCC:</label><br>
            <select name="codTipoTcc" required>
                <?php foreach ($tipos as $codigo => $tipo): ?>
                    <option value="<?= $codigo ?>" <?= ($tcc['codTipoTcc'] == $codigo) ? 'selected' : '' ?>>
                        <?= $tipo['nome'] ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Quantidade de Páginas (máximo depende do tipo):</label><br>
            <input type="number" name="qtdPg" min="1" max="<?= $tipos[$tcc['codTipoTcc']]['maxPg'] ?>"
                value="<?= $tcc['qtdPg'] ?>" required><br><br>

            <label>Curso:</label><br>
            <input type="text" name="curso" value="<?= htmlspecialchars($tcc['curso']) ?>" required><br><br>

            <button class='botao' type="submit">Salvar Alterações</button>
        </form>
    </section>
    <script>
        // 3.3 Atribuição + 3.4 Comparação + 5.1 For (implícito em evento)
        const tipoSelect = document.querySelector('select[name="codTipoTcc"]');
        const qtdPgInput = document.querySelector('input[name="qtdPg"]');

        // 4.1 Arrays (em JS)
        const maxPaginas = {
            1: 60,
            2: 30,
            3: 20,
            4: 40
        };

        // 8.1 Função com parâmetro implícito (arrow function com evento)
        tipoSelect.addEventListener('change', () => {
            const maxPg = maxPaginas[tipoSelect.value];
            qtdPgInput.max = maxPg;
            if (parseInt(qtdPgInput.value) > maxPg) {
                qtdPgInput.value = maxPg;  //3.5 Decremento (condicional)
            }
        });
    </script>