<?php
require_once 'conexao.php';

$stmt = $pdo->query("SELECT id, nome FROM nomes ORDER BY id ASC");
$nomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nomes Cadastrados</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Nomes Cadastrados</h1>

    <a href="cadastrar.php"><button>Cadastrar Novo Nome</button></a>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($nomes as $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($linha['id']) ?></td>
                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                    <td>
                        <a href="editar.php?id=<?= $linha['id'] ?>"><button>Editar</button></a>
                        <a href="deletar.php?id=<?= $linha['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este nome?');"><button>Deletar</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
