<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <section>
        <h1>Sistema de Gestão de Estoque, Despesas e Arrecadações para Terreiros</h1>

        <?php
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=db_terreiro;charset=utf8", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $usuario = $pdo->prepare("SELECT * FROM usuarios");
                $usuario->execute();

                $usuario->setFetchMode(PDO::FETCH_ASSOC);

                if ($usuario->rowCount() > 0) {
                    $linha = $usuario->fetchAll();

                    echo "<h2>" . $linha[0]["nome"] . "</h2>";
                }

                
                $nome_terreiro = $pdo->prepare("SELECT nome_terreiro FROM terreiro");
                $nome_terreiro->execute();
                
                $nome_terreiro->setFetchMode(PDO::FETCH_ASSOC);

                if ($nome_terreiro->rowCount() > 0) {
                    $linha = $nome_terreiro->fetchAll(PDO::FETCH_COLUMN, 0);

                    echo "<p>" . $linha[0] . "</p>";
                }

            } catch(PDOException $e) {
                echo "Erro ao conectar: " . $e->getMessage();
            }
            


        ?>
    </section>
</body>
</html>