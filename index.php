<?php
    session_start();
    if (isset($_SESSION["id_usuario"])) {
        header("Location: painel.php");
        exit;
}?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Gestão do Terreiro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Login</h2>
    <form action="login.php" method="post">
        Usuário: <input type="text" name="usuario" required><br><br>
        Senha: <input type="password" name="senha" required><br><br>
        <button type="submit">Entrar</button>
    </form>
 <br><br>
    <h2>Ainda não tem cadastro?</h2>
    <form action="cadastro.php" method="get">
        <button type="submit">Cadastrar Administrador e Assistente</button>
    </form>
</body>
</html>
