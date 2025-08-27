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
                
                $resultado = $pdo->prepare("SELECT nome_terreiro FROM terreiro");
                $resultado->execute();
                
                $resultado->setFetchMode(PDO::FETCH_ASSOC);

                if ($resultado->rowCount() > 0) {
                    $linha = $resultado->fetchAll();

                    echo "<p>" . $linha[1][0] . "</p>";
                }

            } catch(PDOException $e) {
                echo "Erro ao conectar: " . $e->getMessage();
            }
            


        ?>
    </section>
</body>
</html>