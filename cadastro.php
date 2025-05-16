

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
                echo "Nome Completo do Primeiro Integrante: <input type='text' name='nome1'> <br> <br>";
                echo "Nome Completo do Segundo Integrante: <input type='text' name='nome2'> <br> <br>";
                echo "Nome Completo do Terceiro Integrante: <input type='text' name='nome3'> <br> <br>";
                echo "Professor Orientador: <input type='text' name='nomeProfOrient'> <br> <br>";
                break;
            
            case "Professor":

            /*Nome Professor Orientador */
            echo "Professor Orientador: <input type='text' name='nomeProfOrient'> <br>";
            /*Nome professor convidado1 */

            /*Nome professor convidado2 */

            /*Codigo TCC */

            /*Codigo tipo TCC */

            /*Aluno1 */

            /*Aluno2 */

            /*Aluno3 */
                break;
        
            case "Agenda":
            
                break;

            case "TCC":
            
                break;
        }
    ?>
</body>
</html>