<?php
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

if (!isset($_GET['codTcc'])) {
    header('Location: index.php');
    exit;
}

$codTcc = (int)$_GET['codTcc'];

// Buscar dados atuais do TCC e do tipoTcc
$stmt = $pdo->prepare("
    SELECT t.codTcc, t.titulo, t.codTipoTcc, t.qtdPg, t.qtdAlunos, t.curso, tipo.nomeTipoTcc 
    FROM Tcc t
    JOIN TipoTcc tipo ON t.codTipoTcc = tipo.codTipoTcc
    WHERE t.codTcc = ?
");
$stmt->execute([$codTcc]);
$tcc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tcc) {
    echo "TCC não encontrado.";
    exit;
}

// Lista tipos para dropdown
$tipos = [
    1 => ['nome' => 'Monografia', 'maxPg' => 60],
    2 => ['nome' => 'Artigo técnico-científico', 'maxPg' => 30],
    3 => ['nome' => 'Relatório técnico-científico', 'maxPg' => 20],
    4 => ['nome' => 'Plano de negócios', 'maxPg' => 40],
];

// Atualizar dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $codTipoTcc = (int)$_POST['codTipoTcc'];
    $qtdPg = (int)$_POST['qtdPg'];
    $curso = trim($_POST['curso']);

    // Validação da qtdPg conforme tipo
    if ($qtdPg < 1 || $qtdPg > $tipos[$codTipoTcc]['maxPg']) {
        $erro = "Quantidade de páginas deve ser entre 1 e " . $tipos[$codTipoTcc]['maxPg'];
    } elseif (empty($titulo) || empty($curso)) {
        $erro = "Título e curso não podem estar vazios.";
    } else {
        // Atualizar
        $nomeTipoTcc = $tipos[$codTipoTcc]['nome'];

        $stmt = $pdo->prepare("
            UPDATE Tcc SET titulo=?, codTipoTcc=?, qtdPg=?, curso=?
            WHERE codTcc=?
        ");
        $stmt->execute([$titulo, $codTipoTcc, $qtdPg, $curso, $codTcc]);

        // Atualizar nomeTipoTcc na tabela TipoTcc não faz sentido porque é fixa, então mantemos só a tabela TipoTcc original

        header("Location: index.php");
        exit;
    }
}
?>
<link rel='stylesheet' href='style.css'/>
<a href="index.php">← Voltar para lista de TCCs</a>
<h2>Editar TCC (codTcc = <?= $codTcc ?>)</h2>

<?php if (isset($erro)): ?>
    <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
<?php endif; ?>

<form method="post">
    <label>Título:</label><br>
    <input type="text" name="titulo" value="<?= htmlspecialchars($tcc['titulo']) ?>" required><br><br>

    <label>Tipo de TCC:</label><br>
    <select name="codTipoTcc" required>
        <?php foreach ($tipos as $codigo => $tipo): ?>
            <option value="<?= $codigo ?>" <?= ($tcc['codTipoTcc'] == $codigo) ? 'selected' : '' ?>>
                <?= $tipo['nome'] ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Quantidade de Páginas (máximo depende do tipo):</label><br>
    <input type="number" name="qtdPg" min="1" max="<?= $tipos[$tcc['codTipoTcc']]['maxPg'] ?>" value="<?= $tcc['qtdPg'] ?>" required><br><br>

    <label>Curso:</label><br>
    <input type="text" name="curso" value="<?= htmlspecialchars($tcc['curso']) ?>" required><br><br>

    <button type="submit">Salvar Alterações</button>
</form>

<script>
// Atualizar dinamicamente o max das páginas baseado no tipo selecionado
const tipoSelect = document.querySelector('select[name="codTipoTcc"]');
const qtdPgInput = document.querySelector('input[name="qtdPg"]');

const maxPaginas = {
    1: 60,
    2: 30,
    3: 20,
    4: 40
};

tipoSelect.addEventListener('change', () => {
    const maxPg = maxPaginas[tipoSelect.value];
    qtdPgInput.max = maxPg;
    if (parseInt(qtdPgInput.value) > maxPg) {
        qtdPgInput.value = maxPg;
    }
});
</script>
