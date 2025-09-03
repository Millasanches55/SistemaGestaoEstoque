<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Perfil do Usuário</title>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <section>
        <h1>Sistema de Gestão de Estoque, Despesas e Arrecadações para Terreiros</h1>
        <br>
        <a href="painel.php" class='botao'><i class='bxr  bx-arrow-left-stroke'  ></i> Voltar</a>

        <h2>Perfil do Usuário</h2>
        <h3>Dados Pessoais</h3>
        <?php
            session_start();
            echo "<p><b>Nome:</b> " . $_SESSION["nome"] . "</p>";

            if ($_SESSION["tipo"] == "adm") echo "<p><b>Nível de Acesso:</b> Administrador";
            else if ($_SESSION["tipo"] == "auxiliar") echo "<p><b>Nível de Acesso:</b> Auxiliar";
        ?>
        <br>
        <br>
        <h3>Alterar Senha</h3>
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

                $id = $_SESSION["id_usuario"];

                $pdo = new PDO("mysql:host=localhost;dbname=db_terreiro;charset=utf8", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $usuario = $pdo->prepare("SELECT senha FROM usuarios");
                $usuario->execute();

                $usuario->setFetchMode(PDO::FETCH_ASSOC);

                if ($usuario->rowCount() > 0) {
                    $resultado = $usuario->fetchAll();

                    foreach ($resultado as $linha){
                        if (password_verify($senha_atual, $linha["senha"]) && $senha_nova == $confirmar_senha) {
                            $senha = password_hash($senha_nova, PASSWORD_DEFAULT);
                            $atualizacao = $pdo->prepare("UPDATE usuarios SET senha = $senha WHERE id = $id");
                            $atualizacao->execute();
                            echo "Senha atualizada com sucesso.";
                            break;
                        }
                    }
                    if (!(password_verify($senha_atual, $linha["senha"])) || $senha_nova != $confirmar_senha) {
                        echo "Senhas inválidas.";
                    }
                }
            }
        ?>
    </section>
</body>
</html>