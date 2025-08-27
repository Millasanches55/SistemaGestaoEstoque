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

                    echo "<p>Terreiro: " . $linha[0] . "</p>";
                }

            } catch(PDOException $e) {
                echo "Erro ao conectar: " . $e->getMessage();
            }
            


        ?>
        <br>
        <p><b>Alterar Senha</b></p>
        <form action="perfilDoUsuario.php" method="post">
            Senha atual: <input type="password" name="senha_atual" required> <br>
            Senha nova:  <input type="password" name="senha_nova" required> <br>
            Confirmar senha:  <input type="password" name="confirmar_senha" required> <br>
            <input type="submit" value="Confirmar">
        </form>
        
        <?php
            if (!empty($_POST)) {
                $senha_atual = $_POST["senha_atual"];
                $senha_nova = $_POST["senha_nova"];
                $confirmar_senha = $_POST["confirmar_senha"];

                $pdo = new PDO("mysql:host=localhost;dbname=db_terreiro;charset=utf8", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $usuario = $pdo->prepare("SELECT id, senha FROM usuarios");
                $usuario->execute();

                $usuario->setFetchMode(PDO::FETCH_ASSOC);

                if ($usuario->rowCount() > 0) {
                    $linha = $usuario->fetchAll();

                    if ($senha_atual == $linha[0]["senha"] && $senha_nova == $confirmar_senha) {
                        $atualizacao = $pdo->prepare("UPDATE usuarios SET senha = $senha_nova WHERE id = 1");
                        $atualizacao->execute();
                    }
                }
            }
        ?>
    </section>
</body>
</html>