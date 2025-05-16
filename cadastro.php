

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Cadastro</title>
</head>
<body>
    <header>
        <h1>Sistema de TCC</h1>
    </header>

    <a class='botao' href="index.php">Voltar</a>

    <br>

    <h2>Cadastro de <?php echo $_POST['opcao-cadastro']; ?></h2>

    <?php
        $tipo_cadastro = $_POST['opcao-cadastro'];

        switch($tipo_cadastro) {
            case "Aluno":
                echo "Nome Completo: <input type='text' name='nome'> <br>";
                break;
            
            case "Professor":
            
                break;
        
            case "Agenda":
            
                break;

            case "TCC":
            
                break;
        }
    ?>
</body>
</html>