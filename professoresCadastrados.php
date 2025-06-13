<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' href='style.css' />
    <title>Professores Cadastrados</title>
</head>

<body>
    <section>
        <br>
        <a href="index.php" class='botao'>← Voltar para lista de TCCs</a>
        <?php
        require_once 'Professor.php';

        // Ativa exibição de erros
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            /* 9.1 Conexão PDO */
            $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Junta informações da tabela Professor com Tcc (para obter o título)
            /* 9.2 Leitura (SELECT com JOIN) */
            $stmt = $pdo->query("
        SELECT tcc.titulo, tcc.curso, professor.*
        FROM Professor professor
        JOIN Tcc tcc ON tcc.codTcc = professor.codTcc
    ");

            echo "<h2>Professores Cadastrados por TCC</h2>";
            echo "<hr>";

            /* 5.3 Laço while */
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<div class='cartao-tcc'>";
                echo "<h3>Título do TCC: <span>" . htmlspecialchars($row['titulo']) . "</span></h3>";
                echo "<h4>Curso: <span>" . htmlspecialchars($row['curso']) . "</span></h4>";
                echo "<ul class='lista-professores'>";

                /* 4.1 Uso de arrays + 5.2 Foreach */
                $tipos = ['orientador', 'coorientador', 'profConvidado1', 'profConvidado2'];
                foreach ($tipos as $tipo) {
                    /* 6.1 If / Else */
                    if (!empty($row[$tipo])) {
                        /* 7.4 Instanciação de objeto */
                        $prof = new Professor($row[$tipo], ucfirst($tipo));
                        /* 8.1 Função com passagem de parâmetros */
                        echo "<li>👨‍🏫 " . $prof->exibirProfessor() . "</li>";
                    }
                }

                echo "</ul>";
                echo "</div>";

            }

        } catch (PDOException $e) {
            echo "Erro ao conectar ou buscar dados: " . $e->getMessage();
        }
        ?>
    </section>

</body>

</html>