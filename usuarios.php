<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <section>
        <a href="painel.php" class='botao'><i class='bxr  bx-arrow-left-stroke'  ></i> Voltar</a>
        <h2>Gerenciar Usuários</h2>
        <?php
            try {
                    $pdo = new PDO("mysql:host=localhost;dbname=db_terreiro;charset=utf8", "root", "");
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $usuario = $pdo->prepare("SELECT * FROM usuarios");
                    $usuario->execute();

                    $usuario->setFetchMode(PDO::FETCH_ASSOC);


            
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Usuário</th>
                <th>Ações</th>
            </tr>
            
        
        <?php
            }
            catch(PDOException $e) {
                    echo "Erro ao conectar: " . $e->getMessage();
            }
        ?>
        </table>
    </section>
</body>
</html>