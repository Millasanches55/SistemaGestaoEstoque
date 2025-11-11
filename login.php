<?php
session_start();
include("conexao.php");

$redirect_seconds = 4; // tempo antes de voltar para a página de login/cadastro

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'] ?? '';
    $senha   = $_POST['senha'] ?? '';

    // Busca usuário no banco
    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $redirect_target = 'index.php'; // padrão: voltar para login/cadastro

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verifica senha (criptografada com password_hash no cadastro)
        if (password_verify($senha, $row['senha'])) {
            // Cria sessão
            $_SESSION['id_usuario'] = $row['id'];
            $_SESSION['nome']       = $row['nome'];
            $_SESSION['tipo']       = $row['tipo']; // adm ou auxiliar
            $_SESSION['id_terreiro']= $row['id_terreiro'];
            $_SESSION['tema'] = 'style.css';

            // Redireciona para painel
            header("Location: painel.php");
            exit();
        } else {
            // Senha incorreta: mantém comportamento atual (volta para tela de login)
            $error_message = "Senha incorreta.";
            $redirect_target = 'index.php';
        }
    } else {
        // Usuário não encontrado: direcionar para página de cadastro
        $error_message = "Usuário não encontrado.";
        $redirect_target = 'cadastro.php';
    }

    // Se houve erro de login, mostra mensagem temporária e redireciona para o destino apropriado
    if (!empty($error_message)) {
        // HTML simples com redirecionamento JS/meta
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Login - Erro</title>
            <!-- Redirect para a página alvo -->
            <meta http-equiv="refresh" content="<?php echo (int)$redirect_seconds; ?>;url=<?php echo htmlspecialchars($redirect_target); ?>">
            <style>
                body{font-family: Arial, sans-serif; background:#f8f8f8; display:flex; align-items:center; justify-content:center; height:100vh;}
                .msg { background: #fff; border: 1px solid #ddd; padding:20px 24px; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.08); max-width:420px; text-align:center;}
                .error { color: #c0392b; font-weight:600; margin-bottom:8px; }
                .info { color:#666; font-size:0.9rem; }
                /* botão estilizado para voltar/ir agora */
                .link {
                    display:inline-block;
                    margin-top:12px;
                    text-decoration:none;
                    color:#fff;
                    background:#2d89ef;
                    padding:10px 16px;
                    border-radius:6px;
                    font-weight:600;
                    box-shadow: 0 4px 8px rgba(45,137,239,0.12);
                    transition: background .15s ease, transform .08s ease;
                }
                .link:hover { background:#1f6fd6; transform: translateY(-1px); text-decoration:none; }
                .link:active { transform: translateY(0); }
            </style>
        </head>
        <body>
            <div class="msg" role="status" aria-live="polite">
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <div class="info">
                    Você será redirecionado em <?php echo (int)$redirect_seconds; ?> segundos.
                    <?php if ($redirect_target === 'cadastro.php'): ?> Será levado para a página de cadastro.<?php endif; ?>
                </div>
                <a class="link" href="<?php echo htmlspecialchars($redirect_target); ?>" role="button">
                    Ir agora para <?php echo ($redirect_target === 'cadastro.php') ? 'cadastro' : 'login'; ?>
                </a>
            </div>

            <script>
                // redireciona após X segundos (fallback para meta refresh)
                setTimeout(function(){
                    window.location.href = '<?php echo addslashes($redirect_target); ?>';
                }, <?php echo ((int)$redirect_seconds * 1000); ?>);
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
