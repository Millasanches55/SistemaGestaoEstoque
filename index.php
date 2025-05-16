
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <header>
        <h1>Sistema de TCC</h1>
    </header>

    <hr>

    <section>
        <h2>Cadastro</h2>
        <form action="cadastro.php" method="post">
            <p>Por favor, selecione uma opção:</p>
            <select name="opcao-cadastro">
                <option>Aluno</option>
                <option>Professor</option>
                <option>Agenda</option>
                <option>TCC</option>
                <option>TipoTCC</option>
            </select>
            <input type="submit" value="Continuar" style='cursor: pointer;'>
        </form>
    </section>
</body>
</html>