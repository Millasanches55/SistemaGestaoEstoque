<?php
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

class Tcc {
    public $codTcc;
    public $titulo;
    public $aluno1;
    public $aluno2;
    public $aluno3;
    public $curso;
    public $orientador;

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Método para salvar um TCC no banco
    public function salvar() {
        $sql = "INSERT INTO Tcc (titulo, aluno1, aluno2, aluno3, curso, orientador) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->titulo, $this->aluno1, $this->aluno2, $this->aluno3, $this->curso, $this->orientador]);
    }

    // Método para listar todos os TCCs
    public function listarTodos() {
        $stmt = $this->pdo->query("SELECT * FROM Tcc");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para deletar um TCC pelo código
    public function deletar($codTcc) {
        $stmt = $this->pdo->prepare("DELETE FROM Tcc WHERE codTcc = ?");
        return $stmt->execute([$codTcc]);
    }
}
?>
