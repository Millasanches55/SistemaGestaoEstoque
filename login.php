<?php
session_start();
include("conexao.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $senha   = $_POST['senha'];

    // Busca usuário no banco
    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verifica senha (criptografada com password_hash no cadastro)
        if (password_verify($senha, $row['senha'])) {
            // Cria sessão
            $_SESSION['id_usuario'] = $row['id'];
            $_SESSION['nome']       = $row['nome'];
            $_SESSION['tipo']       = $row['tipo']; // adm ou auxiliar
            $_SESSION['id_terreiro']= $row['id_terreiro'];

            // Redireciona para painel
            header("Location: painel.php");
            exit();
        } else {
            echo "Senha incorreta.";
        }
    } else {
        echo "Usuário não encontrado.";
    }
}
?>
